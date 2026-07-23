<?php
/**
 * Admin Messages — broadcast tool for sending dashboard messages to users.
 *
 * What this gives you:
 *   - A new admin page under "Gastro Health Hub" → "User Messages"
 *     (positioned just under "AI Visibility").
 *   - Ability to send a message to ALL users, a multi-select user list, or
 *     all users with a given audience role (`_sla_audience_role` meta).
 *   - Severity levels (info / important / announcement) that drive colour.
 *   - Optional CTA button (label + URL).
 *   - Optional expiry date (after which the message stops appearing).
 *   - Optional schedule date (publish in the future via WP's `future` status).
 *   - Read-receipt tracking — admin sees how many recipients have viewed.
 *   - Resend / delete from the admin list.
 *   - Full search of previous messages.
 *   - Markdown-style formatting: line breaks preserved, **bold** + *italic*
 *     translated to HTML.
 *
 * Frontend:
 *   - vance_admin_messages_for_user( $user_id ) returns the active, unread
 *     messages for a user. The dashboard banner and "My Messages" tab call
 *     this. Read-tracking happens when the dashboard renders the message.
 *
 * Storage:
 *   - Custom Post Type `vance_message` (private, not publicly queryable).
 *   - Post meta keys (all `_sla_*` per CLAUDE.md constraint #2):
 *       _sla_msg_severity   string  info|important|announcement
 *       _sla_msg_audience   string  all|users|role
 *       _sla_msg_user_ids   int[]   when audience=users
 *       _sla_msg_role       string  when audience=role (patient|hcp|...)
 *       _sla_msg_cta_label  string
 *       _sla_msg_cta_url    string
 *       _sla_msg_expires    int     unix ts; 0 = never
 *       _sla_msg_read_by    int[]   user IDs who have seen it
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// ─── CPT registration ────────────────────────────────────────────────────────
//
// Two CPTs: top-level admin broadcasts (`vance_message`) and threaded user
// replies (`vance_message_reply`). Replies use `post_parent` to link to the
// original message — gives us threading for free via the standard WP query API.
//
add_action( 'init', 'vance_register_message_cpt' );
function vance_register_message_cpt() {
    register_post_type( 'vance_message', array(
        'labels'             => array( 'name' => 'User Messages', 'singular_name' => 'User Message' ),
        'public'             => false, 'publicly_queryable' => false, 'show_ui' => false,
        'show_in_menu'       => false, 'show_in_rest' => false, 'has_archive' => false,
        'rewrite'            => false, 'query_var'    => false,
        'capability_type'    => 'post',
        'supports'           => array( 'title', 'editor', 'author' ),
    ) );
    register_post_type( 'vance_message_reply', array(
        'labels'             => array( 'name' => 'User Message Replies', 'singular_name' => 'Reply' ),
        'public'             => false, 'publicly_queryable' => false, 'show_ui' => false,
        'show_in_menu'       => false, 'show_in_rest' => false, 'has_archive' => false,
        'rewrite'            => false, 'query_var'    => false,
        'capability_type'    => 'post',
        'hierarchical'       => true, // enables post_parent
        'supports'           => array( 'title', 'editor', 'author', 'page-attributes' ),
    ) );
}

/**
 * Returns true if this user has soft-deleted a given message (per-user delete).
 * Soft-deletes hide the message from THIS user's inbox without removing it
 * from the admin's audit trail. Admin uses wp_trash_post for hard delete.
 *
 * @param int $message_id
 * @param int $user_id
 */
function vance_msg_is_user_deleted( $message_id, $user_id ) {
    $deleted = (array) get_post_meta( (int) $message_id, '_sla_msg_user_deleted', true );
    return in_array( (int) $user_id, array_map( 'intval', $deleted ), true );
}

/**
 * Add user to the per-user soft-delete list for a message.
 */
function vance_msg_mark_user_deleted( $message_id, $user_id ) {
    $message_id = (int) $message_id;
    $user_id    = (int) $user_id;
    if ( $message_id <= 0 || $user_id <= 0 ) return false;
    $deleted = (array) get_post_meta( $message_id, '_sla_msg_user_deleted', true );
    if ( ! in_array( $user_id, array_map( 'intval', $deleted ), true ) ) {
        $deleted[] = $user_id;
        update_post_meta( $message_id, '_sla_msg_user_deleted', $deleted );
    }
    return true;
}

/**
 * Get all replies for a message (oldest-first, like a chat transcript).
 * @return WP_Post[]
 */
function vance_msg_get_replies( $message_id ) {
    $message_id = (int) $message_id;
    if ( $message_id <= 0 ) return array();
    return get_posts( array(
        'post_type'      => 'vance_message_reply',
        'post_parent'    => $message_id,
        'post_status'    => 'publish',
        'orderby'        => 'date',
        'order'          => 'ASC',
        'posts_per_page' => 100,
    ) );
}

// ─── Friendly-URL redirect ───────────────────────────────────────────────────
// Some users (and any external links) might hit the bare slug URL
// /wp-admin/vance-user-messages without the admin.php?page= prefix. WP admin
// pages don't have path-based routing, so that URL resolves to a front-end
// 404 (theme's not-found template). Intercept early and 301 to the canonical
// admin URL so the menu always lands users on the right page.
add_action( 'init', 'vance_msg_legacy_url_redirect', 1 );
function vance_msg_legacy_url_redirect() {
    if ( empty( $_SERVER['REQUEST_URI'] ) ) return;
    $path = strtok( $_SERVER['REQUEST_URI'], '?' );
    if ( $path === '/wp-admin/vance-user-messages' || $path === '/wp-admin/vance-user-messages/' ) {
        wp_safe_redirect( admin_url( 'admin.php?page=vance-user-messages' ), 301 );
        exit;
    }
}

// ─── Admin menu ──────────────────────────────────────────────────────────────
//
// Access policy: any user who satisfies *any* of these is treated as admin —
//   - is_super_admin() (multisite super-admin)
//   - has 'administrator' anywhere in their $user->roles array
//   - has 'administrator' as a cap key in $user->allcaps
//   - has 'manage_options' or 'edit_users' caps (default admin-only caps)
//
// The page itself is registered with the permissive 'read' cap so EVERY
// logged-in request reaches our render function — that way the role check
// has a chance to run, and a rejection produces our diagnostic wp_die rather
// than WP's stock "Sorry, you are not allowed" message (which gives us
// nothing to debug from).
//
// Menu visibility (sidebar item) is gated separately via the post-registration
// $submenu filter, so subscribers don't see a tantalising "User Messages"
// link they can't use.
//
// Priority 1000 — must run AFTER vance_register_content_hub_menu (priority 999
// in functions.php) so $admin_page_hooks['vance-content-hub'] is populated
// when add_submenu_page() computes our hookname. Registering before the parent
// produces a malformed hookname like '_page_vance-user-messages', which
// mismatches what admin.php looks up at request time → cap check fails → user
// sees WP's stock "Sorry, you are not allowed to access this page."
add_action( 'admin_menu', 'vance_register_admin_messages_menu', 1000 );
function vance_register_admin_messages_menu() {
    add_submenu_page(
        'vance-content-hub',
        'User Messages',
        'User Messages',
        'read',
        'vance-user-messages',
        'vance_render_admin_messages_page'
    );
}

// Hide the menu link from non-admins (page itself still answers; the gate is
// enforced in the render function with a diagnostic wp_die for visibility).
add_action( 'admin_menu', 'vance_hide_admin_messages_menu_for_non_admins', 1001 );
function vance_hide_admin_messages_menu_for_non_admins() {
    if ( vance_msg_user_is_admin() ) {
        return;
    }
    global $submenu;
    if ( ! isset( $submenu['vance-content-hub'] ) ) {
        return;
    }
    foreach ( $submenu['vance-content-hub'] as $idx => $item ) {
        if ( isset( $item[2] ) && $item[2] === 'vance-user-messages' ) {
            unset( $submenu['vance-content-hub'][ $idx ] );
        }
    }
}

/**
 * Permissive admin check — true if any of these are true for the current
 * user (or supplied user_id):
 *   - WP super-admin
 *   - 'administrator' role
 *   - 'administrator' cap in allcaps
 *   - 'manage_options' cap
 *   - 'edit_users' cap (admin-only default)
 *
 * @param int|null $user_id  Optional user ID; defaults to current.
 */
function vance_msg_user_is_admin( $user_id = null ) {
    $user = $user_id ? get_userdata( (int) $user_id ) : wp_get_current_user();
    if ( ! $user || empty( $user->ID ) ) {
        return false;
    }
    if ( function_exists( 'is_super_admin' ) && is_super_admin( $user->ID ) ) return true;
    if ( in_array( 'administrator', (array) $user->roles, true ) )           return true;
    if ( ! empty( $user->allcaps['administrator'] ) )                         return true;
    if ( ! empty( $user->allcaps['manage_options'] ) )                        return true;
    if ( ! empty( $user->allcaps['edit_users'] ) )                            return true;
    return false;
}

// ─── Admin page renderer (tabbed: Send New / Replies / Previous / Deleted) ──
function vance_render_admin_messages_page() {
    if ( ! vance_msg_user_is_admin() ) {
        $cu = wp_get_current_user();
        $roles = $cu && ! empty( $cu->roles ) ? implode( ', ', $cu->roles ) : '(none)';
        $caps_subset = array();
        foreach ( array( 'administrator', 'manage_options', 'edit_users', 'edit_posts', 'read' ) as $c ) {
            if ( $cu && ! empty( $cu->allcaps[ $c ] ) ) $caps_subset[] = $c;
        }
        wp_die(
            'Insufficient permissions — administrator role required.<br><br>' .
            '<strong>Your roles:</strong> <code>' . esc_html( $roles ) . '</code><br>' .
            '<strong>Admin-relevant caps detected:</strong> <code>' . esc_html( $caps_subset ? implode( ', ', $caps_subset ) : '(none)' ) . '</code><br>' .
            '<strong>User ID:</strong> <code>' . (int) ( $cu->ID ?? 0 ) . '</code>',
            'Access denied',
            array( 'response' => 403 )
        );
    }

    $action   = isset( $_GET['vance_action'] ) ? sanitize_key( $_GET['vance_action'] ) : '';
    $msg_id   = isset( $_GET['msg_id'] )       ? absint( $_GET['msg_id'] )           : 0;
    $reply_id = isset( $_GET['reply_id'] )     ? absint( $_GET['reply_id'] )         : 0;

    // Inline GET-actions on a parent message (resend/trash/restore/force_delete/archive).
    // 'archive' is a UI-only alias for 'delete' (wp_trash_post) — kept distinct so the
    // notice copy reads naturally on the Replies tab ("Thread archived").
    if ( $action && $msg_id && check_admin_referer( 'vance_msg_action_' . $msg_id ) ) {
        if ( $action === 'delete' ) {
            wp_trash_post( $msg_id );
            wp_safe_redirect( add_query_arg( array( 'page' => 'vance-user-messages', 'vance_notice' => 'deleted' ), admin_url( 'admin.php' ) ) );
            exit;
        }
        if ( $action === 'archive' ) {
            wp_trash_post( $msg_id );
            wp_safe_redirect( add_query_arg( array( 'page' => 'vance-user-messages', 'vance_tab' => 'replies', 'vance_notice' => 'archived' ), admin_url( 'admin.php' ) ) );
            exit;
        }
        if ( $action === 'restore' ) {
            wp_untrash_post( $msg_id );
            wp_safe_redirect( add_query_arg( array( 'page' => 'vance-user-messages', 'vance_tab' => 'previous', 'vance_notice' => 'restored' ), admin_url( 'admin.php' ) ) );
            exit;
        }
        if ( $action === 'force_delete' ) {
            wp_delete_post( $msg_id, true );
            wp_safe_redirect( add_query_arg( array( 'page' => 'vance-user-messages', 'vance_tab' => 'deleted', 'vance_notice' => 'force_deleted' ), admin_url( 'admin.php' ) ) );
            exit;
        }
        if ( $action === 'resend' ) {
            update_post_meta( $msg_id, '_sla_msg_read_by', array() );
            wp_update_post( array( 'ID' => $msg_id, 'post_date' => current_time( 'mysql' ), 'post_date_gmt' => current_time( 'mysql', true ), 'post_status' => 'publish' ) );
            wp_safe_redirect( add_query_arg( array( 'page' => 'vance-user-messages', 'vance_notice' => 'resent' ), admin_url( 'admin.php' ) ) );
            exit;
        }
    }

    // Inline GET-action on a single reply (delete_reply). Separate nonce because
    // the resource is the reply post itself, not its parent.
    if ( $action === 'delete_reply' && $reply_id && check_admin_referer( 'vance_reply_action_' . $reply_id ) ) {
        if ( get_post_type( $reply_id ) === 'vance_message_reply' ) {
            wp_delete_post( $reply_id, true );
        }
        wp_safe_redirect( add_query_arg( array( 'page' => 'vance-user-messages', 'vance_tab' => 'replies', 'vance_notice' => 'reply_deleted' ), admin_url( 'admin.php' ) ) );
        exit;
    }

    // Admin reply submission (from the Replies tab).
    $reply_error = ''; $reply_notice = '';
    if ( isset( $_POST['vance_admin_reply_nonce'] ) ) {
        $r_msg_id = isset( $_POST['reply_msg_id'] ) ? absint( $_POST['reply_msg_id'] ) : 0;
        $r_body   = isset( $_POST['reply_body'] ) ? trim( wp_kses_post( wp_unslash( $_POST['reply_body'] ) ) ) : '';
        if ( ! $r_msg_id || ! wp_verify_nonce( $_POST['vance_admin_reply_nonce'], 'vance_admin_reply_' . $r_msg_id ) ) {
            $reply_error = 'Security check failed — please reload and retry.';
        } elseif ( strlen( $r_body ) < 3 ) {
            $reply_error = 'Reply body too short.';
        } else {
            $rid = wp_insert_post( array(
                'post_type'    => 'vance_message_reply',
                'post_status'  => 'publish',
                'post_parent'  => $r_msg_id,
                'post_author'  => get_current_user_id(),
                'post_title'   => 'Re: ' . wp_trim_words( get_the_title( $r_msg_id ), 8, '…' ) . ' (admin reply)',
                'post_content' => $r_body,
            ), true );
            if ( is_wp_error( $rid ) ) {
                $reply_error = $rid->get_error_message();
            } else {
                update_post_meta( $rid, '_sla_reply_author_roles', array( 'administrator' ) );
                update_post_meta( $rid, '_sla_reply_author_id',    get_current_user_id() );
                $reply_notice = 'Reply sent.';
                // Drop the new-reply-pending stamp now that the admin has responded.
                delete_post_meta( $r_msg_id, '_sla_msg_has_new_reply' );
            }
        }
    }

    // Compose form submission.
    $send_error  = '';
    $send_notice = '';
    if ( isset( $_POST['vance_msg_send_nonce'] ) && wp_verify_nonce( $_POST['vance_msg_send_nonce'], 'vance_msg_send' ) ) {
        $send_result = vance_admin_messages_handle_submit( $_POST );
        if ( is_wp_error( $send_result ) ) {
            $send_error = $send_result->get_error_message();
        } else {
            $send_notice = sprintf( 'Message #%d created.', (int) $send_result );
        }
    }

    $tab    = isset( $_GET['vance_tab'] )    ? sanitize_key( $_GET['vance_tab'] )       : 'send';
    if ( ! in_array( $tab, array( 'send', 'replies', 'previous', 'deleted' ), true ) ) $tab = 'send';
    $search = isset( $_GET['vance_search'] ) ? sanitize_text_field( wp_unslash( $_GET['vance_search'] ) ) : '';

    // Pending-reply count for the Replies tab badge (messages with admin
    // reply pending = has new user reply since last admin response).
    $pending_replies = (int) ( new WP_Query( array(
        'post_type'      => 'vance_message',
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'meta_key'       => '_sla_msg_has_new_reply',
        'fields'         => 'ids',
        'no_found_rows'  => false,
    ) ) )->found_posts;

    $tab_url = function( $t ) { return esc_url( add_query_arg( array( 'page' => 'vance-user-messages', 'vance_tab' => $t ), admin_url( 'admin.php' ) ) ); };
    ?>
    <div class="wrap" style="max-width: 1200px;">
        <h1 style="display: flex; align-items: center; gap: 12px;">
            <span class="dashicons dashicons-email-alt" style="font-size: 28px; width: 28px; height: 28px;"></span>
            User Messages
        </h1>
        <p>Two-way communication between admins and dashboard users. New broadcasts via <em>Send New</em>; user replies appear in <em>Replies</em>; full history in <em>Previous</em>; trashed messages in <em>Deleted</em>.</p>

        <?php if ( ! empty( $_GET['vance_notice'] ) ) : ?>
            <div class="notice notice-success is-dismissible"><p><?php
                $n = $_GET['vance_notice'];
                if ( $n === 'deleted' )           echo 'Message moved to trash.';
                elseif ( $n === 'archived' )      echo 'Thread archived (moved to trash). Restore from the Deleted tab if needed.';
                elseif ( $n === 'restored' )      echo 'Message restored.';
                elseif ( $n === 'force_deleted' ) echo 'Message permanently deleted.';
                elseif ( $n === 'reply_deleted' ) echo 'Reply deleted.';
                elseif ( $n === 'resent' )        echo 'Message resent — read receipts cleared.';
                else                              echo 'Done.';
            ?></p></div>
        <?php endif; ?>
        <?php if ( $send_error )   : ?><div class="notice notice-error is-dismissible"><p><?php echo esc_html( $send_error ); ?></p></div><?php endif; ?>
        <?php if ( $send_notice )  : ?><div class="notice notice-success is-dismissible"><p><?php echo esc_html( $send_notice ); ?></p></div><?php endif; ?>
        <?php if ( $reply_error )  : ?><div class="notice notice-error is-dismissible"><p><?php echo esc_html( $reply_error ); ?></p></div><?php endif; ?>
        <?php if ( $reply_notice ) : ?><div class="notice notice-success is-dismissible"><p><?php echo esc_html( $reply_notice ); ?></p></div><?php endif; ?>

        <h2 class="nav-tab-wrapper" style="margin-top: 28px;">
            <a href="<?php echo $tab_url( 'send' );     ?>" class="nav-tab<?php echo $tab === 'send'     ? ' nav-tab-active' : ''; ?>">Send New</a>
            <a href="<?php echo $tab_url( 'replies' );  ?>" class="nav-tab<?php echo $tab === 'replies'  ? ' nav-tab-active' : ''; ?>">Replies<?php if ( $pending_replies > 0 ) : ?> <span style="background: #008080; color: white; border-radius: 10px; padding: 1px 8px; font-size: 11px; margin-left: 4px;"><?php echo (int) $pending_replies; ?></span><?php endif; ?></a>
            <a href="<?php echo $tab_url( 'previous' ); ?>" class="nav-tab<?php echo $tab === 'previous' ? ' nav-tab-active' : ''; ?>">Previous</a>
            <a href="<?php echo $tab_url( 'deleted' );  ?>" class="nav-tab<?php echo $tab === 'deleted'  ? ' nav-tab-active' : ''; ?>">Deleted</a>
        </h2>

        <?php
        // ───────── TAB: SEND NEW ─────────
        if ( $tab === 'send' ) :
            $users = get_users( array( 'number' => 500, 'fields' => array( 'ID', 'user_email', 'display_name' ), 'orderby' => 'display_name' ) );
        ?>
            <form method="post" style="background: white; padding: 20px 24px; border: 1px solid #c3c4c7; box-shadow: 0 1px 1px rgba(0,0,0,0.04); margin-top: 20px;">
                <?php wp_nonce_field( 'vance_msg_send', 'vance_msg_send_nonce' ); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="msg_title">Title</label></th>
                        <td><input type="text" id="msg_title" name="msg_title" class="regular-text" required maxlength="120" style="width: 100%; max-width: 600px;"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="msg_body">Body</label></th>
                        <td>
                            <textarea id="msg_body" name="msg_body" rows="6" required style="width: 100%; max-width: 800px;"></textarea>
                            <p class="description">Plain text. Line breaks preserved. <code>**bold**</code> and <code>*italic*</code> become HTML; URLs auto-link.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="msg_severity">Severity</label></th>
                        <td>
                            <select id="msg_severity" name="msg_severity">
                                <option value="info">Info (teal banner)</option>
                                <option value="important">Important (amber banner)</option>
                                <option value="announcement">Announcement (dark teal banner)</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Audience</th>
                        <td>
                            <fieldset>
                                <label><input type="radio" name="msg_audience" value="all" checked> All users</label><br>
                                <label><input type="radio" name="msg_audience" value="users"> Selected users (multi-select below)</label><br>
                                <label><input type="radio" name="msg_audience" value="role"> All users with a specific role</label>
                            </fieldset>
                        </td>
                    </tr>
                    <tr id="row_users" style="display: none;">
                        <th scope="row"><label for="msg_user_ids">Pick users</label></th>
                        <td>
                            <select id="msg_user_ids" name="msg_user_ids[]" multiple size="8" style="width: 100%; max-width: 600px;">
                                <?php foreach ( $users as $u ) : ?>
                                    <option value="<?php echo (int) $u->ID; ?>"><?php echo esc_html( $u->display_name . ' — ' . $u->user_email ); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">Hold ⌘/Ctrl to select multiple. Up to 500 users shown.</p>
                        </td>
                    </tr>
                    <tr id="row_role" style="display: none;">
                        <th scope="row"><label for="msg_role">Role filter</label></th>
                        <td>
                            <select id="msg_role" name="msg_role">
                                <option value="patient">Patient</option>
                                <option value="caregiver">Caregiver / Family</option>
                                <option value="hcp">Healthcare Professional</option>
                                <option value="researcher">Researcher</option>
                                <option value="other">Other</option>
                            </select>
                            <p class="description">Matches the audience role users self-selected at signup (<code>_sla_audience_role</code>).</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="msg_cta_label">Call-to-action button (optional)</label></th>
                        <td>
                            <input type="text" id="msg_cta_label" name="msg_cta_label" class="regular-text" placeholder="e.g. Read the protocol" style="width: 280px;">
                            <input type="url"  id="msg_cta_url"   name="msg_cta_url"   class="regular-text" placeholder="https://…" style="width: 320px;">
                            <p class="description">Leave both blank to omit the button.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="msg_expires">Expires</label></th>
                        <td>
                            <input type="date" id="msg_expires" name="msg_expires">
                            <p class="description">Optional. After this date, message stops appearing on the dashboard.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="msg_schedule">Schedule send</label></th>
                        <td>
                            <input type="datetime-local" id="msg_schedule" name="msg_schedule">
                            <p class="description">Optional. Future-dated messages stay scheduled until that time. Blank = send now.</p>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <button type="submit" class="button button-primary">Send message</button>
                </p>
            </form>
            <script>
            (function () {
                var radios = document.getElementsByName('msg_audience');
                var rowUsers = document.getElementById('row_users');
                var rowRole  = document.getElementById('row_role');
                function update() {
                    var v = document.querySelector('input[name="msg_audience"]:checked').value;
                    rowUsers.style.display = v === 'users' ? '' : 'none';
                    rowRole.style.display  = v === 'role'  ? '' : 'none';
                }
                for (var i = 0; i < radios.length; i++) radios[i].addEventListener('change', update);
                update();
            })();
            </script>

        <?php
        // ───────── TAB: REPLIES (chronological list of user replies) ─────────
        elseif ( $tab === 'replies' ) :
            // Pull all replies with parent message info; latest first.
            $reply_q = new WP_Query( array(
                'post_type'      => 'vance_message_reply',
                'post_status'    => 'publish',
                'posts_per_page' => 100,
                'orderby'        => 'date',
                'order'          => 'DESC',
                's'              => $search,
            ) );
        ?>
            <div style="margin-top: 20px; display: flex; align-items: center; justify-content: space-between; gap: 16px;">
                <div style="font-size: 13px; color: #555;">
                    <?php echo (int) $reply_q->found_posts; ?> replies total. <?php echo (int) $pending_replies; ?> message<?php echo $pending_replies === 1 ? '' : 's'; ?> awaiting admin response.
                </div>
                <form method="get" style="margin: 0;">
                    <input type="hidden" name="page" value="vance-user-messages">
                    <input type="hidden" name="vance_tab" value="replies">
                    <input type="search" name="vance_search" value="<?php echo esc_attr( $search ); ?>" placeholder="Search reply text…" style="width: 240px;">
                    <button class="button">Search</button>
                    <?php if ( $search ) : ?><a class="button" href="<?php echo $tab_url( 'replies' ); ?>">Clear</a><?php endif; ?>
                </form>
            </div>
            <?php if ( ! $reply_q->have_posts() ) : ?>
                <p style="margin-top: 20px; padding: 32px; text-align: center; background: #f8fafc; border: 1px dashed #c3c4c7;">
                    No replies yet. When users reply to your broadcasts, the conversations will appear here.
                </p>
            <?php else :
                while ( $reply_q->have_posts() ) : $reply_q->the_post();
                    $reply       = get_post();
                    $parent_id   = (int) $reply->post_parent;
                    $parent      = $parent_id ? get_post( $parent_id ) : null;
                    $author      = get_userdata( (int) $reply->post_author );
                    $is_admin    = $author && in_array( 'administrator', (array) $author->roles, true );
                    $admin_reply_nonce = wp_create_nonce( 'vance_admin_reply_' . $parent_id );
            ?>
                <?php
                // Per-reply action URLs (delete this reply, archive parent thread).
                $reply_delete_url = wp_nonce_url(
                    admin_url( 'admin.php?page=vance-user-messages&vance_action=delete_reply&reply_id=' . $reply->ID ),
                    'vance_reply_action_' . $reply->ID
                );
                $thread_archive_url = $parent_id ? wp_nonce_url(
                    admin_url( 'admin.php?page=vance-user-messages&vance_action=archive&msg_id=' . $parent_id ),
                    'vance_msg_action_' . $parent_id
                ) : '';
                ?>
                <div style="background: white; border: 1px solid #c3c4c7; padding: 16px 20px; margin-top: 16px;">
                    <div style="display: flex; justify-content: space-between; gap: 12px; margin-bottom: 8px; align-items: baseline;">
                        <div>
                            <strong style="font-size: 14px;"><?php echo $author ? esc_html( $author->display_name ) : '—'; ?></strong>
                            <?php if ( $is_admin ) : ?><span style="font-size: 11px; color: #008080; margin-left: 6px;">(admin)</span><?php endif; ?>
                            <span style="color: #666; font-size: 12px; margin-left: 8px;">replied <?php echo esc_html( get_the_date( 'M j, Y g:i a', $reply ) ); ?></span>
                        </div>
                        <?php if ( $parent ) : ?>
                            <span style="font-size: 12px; color: #666;">on <em><?php echo esc_html( $parent->post_title ); ?></em></span>
                        <?php endif; ?>
                    </div>
                    <div style="font-size: 14px; line-height: 1.6; padding: 10px 14px; background: <?php echo $is_admin ? '#def4f4' : '#f8fafc'; ?>; border-left: 3px solid <?php echo $is_admin ? '#008080' : '#94a3b8'; ?>;">
                        <?php echo wpautop( wp_kses_post( $reply->post_content ) ); ?>
                    </div>

                    <!-- Per-reply action row: Delete this reply / Archive thread -->
                    <div style="display: flex; gap: 14px; margin-top: 8px; font-size: 12px;">
                        <a href="<?php echo esc_url( $reply_delete_url ); ?>" onclick="return confirm('Permanently delete this reply? This cannot be undone.');" style="color: #b32d2e;">Delete this reply</a>
                        <?php if ( $thread_archive_url ) : ?>
                            <a href="<?php echo esc_url( $thread_archive_url ); ?>" onclick="return confirm('Archive this entire thread? It will move to the Deleted tab where you can restore it.');" style="color: #94a3b8;">Archive thread</a>
                        <?php endif; ?>
                    </div>

                    <?php if ( $parent ) : ?>
                        <!-- Always show the admin reply form so admins can post follow-ups
                             even after they've already replied (no `! $is_admin` gate). -->
                        <details style="margin-top: 10px;" <?php echo ! $is_admin ? 'open' : ''; ?>>
                            <summary style="cursor: pointer; font-size: 12px; font-weight: 600; color: #008080;">
                                <?php echo $is_admin ? 'Add another admin reply' : 'Reply as admin'; ?>
                            </summary>
                            <form method="post" style="margin-top: 8px;">
                                <?php wp_nonce_field( 'vance_admin_reply_' . $parent_id, 'vance_admin_reply_nonce' ); ?>
                                <input type="hidden" name="reply_msg_id" value="<?php echo (int) $parent_id; ?>">
                                <textarea name="reply_body" rows="3" required minlength="3" maxlength="4000" style="width: 100%; box-sizing: border-box; font-family: inherit; padding: 8px;" placeholder="Reply to this thread…"></textarea>
                                <button type="submit" class="button button-primary" style="margin-top: 6px;">Send admin reply</button>
                            </form>
                        </details>
                    <?php endif; ?>
                </div>
            <?php endwhile; wp_reset_postdata(); endif; ?>

        <?php
        // ───────── TAB: PREVIOUS (existing message list) ─────────
        elseif ( $tab === 'previous' ) :
            $messages = vance_admin_messages_list( $search );
        ?>
            <div style="margin-top: 20px; display: flex; align-items: center; justify-content: space-between; gap: 16px;">
                <div style="font-size: 13px; color: #555;"><?php echo count( $messages ); ?> messages.</div>
                <form method="get" style="margin: 0;">
                    <input type="hidden" name="page" value="vance-user-messages">
                    <input type="hidden" name="vance_tab" value="previous">
                    <input type="search" name="vance_search" value="<?php echo esc_attr( $search ); ?>" placeholder="Search title or body…" style="width: 280px;">
                    <button class="button">Search</button>
                    <?php if ( $search ) : ?><a class="button" href="<?php echo $tab_url( 'previous' ); ?>">Clear</a><?php endif; ?>
                </form>
            </div>
            <table class="wp-list-table widefat fixed striped" style="margin-top: 16px;">
                <thead>
                    <tr>
                        <th style="width: 26%;">Title</th>
                        <th style="width: 12%;">Severity</th>
                        <th style="width: 18%;">Audience</th>
                        <th style="width: 14%;">Sent</th>
                        <th style="width: 8%;">Status</th>
                        <th style="width: 8%;">Reads</th>
                        <th style="width: 8%;">Replies</th>
                        <th style="width: 12%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty( $messages ) ) : ?>
                        <tr><td colspan="8" style="padding: 20px; text-align: center; color: #666;">No messages yet — switch to <em>Send New</em> to compose one.</td></tr>
                    <?php else : foreach ( $messages as $m ) :
                        $sev   = get_post_meta( $m->ID, '_sla_msg_severity', true ) ?: 'info';
                        $aud   = get_post_meta( $m->ID, '_sla_msg_audience', true ) ?: 'all';
                        $reads = (array) get_post_meta( $m->ID, '_sla_msg_read_by', true );
                        $exp   = (int)  get_post_meta( $m->ID, '_sla_msg_expires', true );
                        $aud_label = $aud === 'all' ? 'All users' : ( $aud === 'users' ? 'Selected users' : 'Role: ' . esc_html( get_post_meta( $m->ID, '_sla_msg_role', true ) ?: '—' ) );
                        $resend_url = wp_nonce_url( admin_url( 'admin.php?page=vance-user-messages&vance_action=resend&msg_id=' . $m->ID ), 'vance_msg_action_' . $m->ID );
                        $delete_url = wp_nonce_url( admin_url( 'admin.php?page=vance-user-messages&vance_action=delete&msg_id=' . $m->ID ), 'vance_msg_action_' . $m->ID );
                        $is_expired = $exp && $exp < time();
                        $reply_count = count( vance_msg_get_replies( $m->ID ) );
                        $has_pending = (bool) get_post_meta( $m->ID, '_sla_msg_has_new_reply', true );
                    ?>
                        <tr>
                            <td><strong><?php echo esc_html( $m->post_title ); ?></strong><br>
                                <span style="color: #666; font-size: 12px;"><?php echo esc_html( wp_trim_words( $m->post_content, 18 ) ); ?></span></td>
                            <td><span style="padding: 3px 9px; border-radius: 12px; font-size: 11px; font-weight: 600; background: <?php echo $sev === 'important' ? '#fff3cd; color: #856404' : ( $sev === 'announcement' ? '#0A1929; color: #fff' : '#def4f4; color: #008080' ); ?>;"><?php echo esc_html( ucfirst( $sev ) ); ?></span></td>
                            <td><?php echo $aud_label; ?></td>
                            <td><?php echo esc_html( get_the_date( 'M j, Y g:i a', $m ) ); ?></td>
                            <td><?php
                                if ( $m->post_status === 'future' ) echo '<span style="color: #b07d00;">Scheduled</span>';
                                elseif ( $is_expired )              echo '<span style="color: #999;">Expired</span>';
                                else                                echo '<span style="color: #008080;">Active</span>';
                            ?></td>
                            <td><?php echo (int) count( $reads ); ?></td>
                            <td><?php echo (int) $reply_count; ?><?php if ( $has_pending ) : ?> <span style="color: #008080;" title="New reply awaiting admin">●</span><?php endif; ?></td>
                            <td>
                                <a href="<?php echo esc_url( $resend_url ); ?>">Resend</a> |
                                <a href="<?php echo esc_url( $delete_url ); ?>" onclick="return confirm('Move this message to trash?');" style="color: #b32d2e;">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>

        <?php
        // ───────── TAB: DELETED (trashed messages) ─────────
        elseif ( $tab === 'deleted' ) :
            $trashed = get_posts( array(
                'post_type'      => 'vance_message',
                'post_status'    => 'trash',
                'posts_per_page' => 100,
                'orderby'        => 'modified',
                'order'          => 'DESC',
                's'              => $search,
            ) );
        ?>
            <div style="margin-top: 20px;">
                <p style="font-size: 13px; color: #555;">Trashed messages stop appearing on dashboards but remain stored. Restore to revert, or permanently delete to remove for good.</p>
            </div>
            <table class="wp-list-table widefat fixed striped" style="margin-top: 12px;">
                <thead>
                    <tr>
                        <th style="width: 36%;">Title</th>
                        <th style="width: 16%;">Severity</th>
                        <th style="width: 22%;">Originally sent</th>
                        <th style="width: 26%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty( $trashed ) ) : ?>
                        <tr><td colspan="4" style="padding: 20px; text-align: center; color: #666;">Trash is empty.</td></tr>
                    <?php else : foreach ( $trashed as $t ) :
                        $sev = get_post_meta( $t->ID, '_sla_msg_severity', true ) ?: 'info';
                        $restore_url = wp_nonce_url( admin_url( 'admin.php?page=vance-user-messages&vance_action=restore&msg_id=' . $t->ID ), 'vance_msg_action_' . $t->ID );
                        $force_url   = wp_nonce_url( admin_url( 'admin.php?page=vance-user-messages&vance_action=force_delete&msg_id=' . $t->ID ), 'vance_msg_action_' . $t->ID );
                    ?>
                        <tr>
                            <td><strong><?php echo esc_html( $t->post_title ); ?></strong><br>
                                <span style="color: #666; font-size: 12px;"><?php echo esc_html( wp_trim_words( $t->post_content, 18 ) ); ?></span></td>
                            <td><?php echo esc_html( ucfirst( $sev ) ); ?></td>
                            <td><?php echo esc_html( get_the_date( 'M j, Y', $t ) ); ?></td>
                            <td>
                                <a href="<?php echo esc_url( $restore_url ); ?>">Restore</a> |
                                <a href="<?php echo esc_url( $force_url ); ?>" onclick="return confirm('Permanently delete this message and its replies? This cannot be undone.');" style="color: #b32d2e;">Delete permanently</a>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Handle the new-message form POST. Returns post ID or WP_Error.
 */
function vance_admin_messages_handle_submit( $post_data ) {
    $title = isset( $post_data['msg_title'] ) ? sanitize_text_field( wp_unslash( $post_data['msg_title'] ) ) : '';
    $body  = isset( $post_data['msg_body'] )  ? wp_kses_post( wp_unslash( $post_data['msg_body'] ) )       : '';
    if ( $title === '' || $body === '' ) {
        return new WP_Error( 'missing', 'Title and body are required.' );
    }

    $severity = isset( $post_data['msg_severity'] ) ? sanitize_key( $post_data['msg_severity'] ) : 'info';
    if ( ! in_array( $severity, array( 'info', 'important', 'announcement' ), true ) ) $severity = 'info';

    $audience = isset( $post_data['msg_audience'] ) ? sanitize_key( $post_data['msg_audience'] ) : 'all';
    if ( ! in_array( $audience, array( 'all', 'users', 'role' ), true ) ) $audience = 'all';

    $user_ids = array();
    if ( $audience === 'users' ) {
        $raw = isset( $post_data['msg_user_ids'] ) ? (array) $post_data['msg_user_ids'] : array();
        $user_ids = array_values( array_unique( array_filter( array_map( 'absint', $raw ) ) ) );
        if ( empty( $user_ids ) ) {
            return new WP_Error( 'no_users', 'Audience is "selected users" but no users were picked.' );
        }
    }

    $role = '';
    if ( $audience === 'role' ) {
        $role = isset( $post_data['msg_role'] ) ? sanitize_key( $post_data['msg_role'] ) : '';
        if ( ! $role ) {
            return new WP_Error( 'no_role', 'Audience is "role" but no role was selected.' );
        }
    }

    $cta_label = isset( $post_data['msg_cta_label'] ) ? sanitize_text_field( wp_unslash( $post_data['msg_cta_label'] ) ) : '';
    $cta_url   = isset( $post_data['msg_cta_url'] )   ? esc_url_raw( wp_unslash( $post_data['msg_cta_url'] ) )           : '';

    $expires_in = isset( $post_data['msg_expires'] ) ? trim( $post_data['msg_expires'] ) : '';
    $expires_ts = 0;
    if ( $expires_in ) {
        // <input type="date"> gives YYYY-MM-DD; treat as end-of-day site-local.
        $expires_ts = strtotime( $expires_in . ' 23:59:59' );
        if ( ! $expires_ts ) $expires_ts = 0;
    }

    $schedule_in = isset( $post_data['msg_schedule'] ) ? trim( $post_data['msg_schedule'] ) : '';
    $schedule_ts = 0;
    $post_status = 'publish';
    $post_date_gmt = '';
    $post_date     = '';
    if ( $schedule_in ) {
        $schedule_ts = strtotime( $schedule_in );
        if ( $schedule_ts && $schedule_ts > time() + 60 ) {
            $post_status   = 'future';
            $post_date     = date( 'Y-m-d H:i:s', $schedule_ts );
            $post_date_gmt = gmdate( 'Y-m-d H:i:s', $schedule_ts );
        }
    }

    $insert = array(
        'post_type'    => 'vance_message',
        'post_status'  => $post_status,
        'post_title'   => $title,
        'post_content' => $body,
        'post_author'  => get_current_user_id(),
    );
    if ( $post_status === 'future' ) {
        $insert['post_date']     = $post_date;
        $insert['post_date_gmt'] = $post_date_gmt;
    }

    $post_id = wp_insert_post( $insert, true );
    if ( is_wp_error( $post_id ) ) return $post_id;

    update_post_meta( $post_id, '_sla_msg_severity',  $severity );
    update_post_meta( $post_id, '_sla_msg_audience',  $audience );
    update_post_meta( $post_id, '_sla_msg_user_ids',  $user_ids );
    update_post_meta( $post_id, '_sla_msg_role',      $role );
    update_post_meta( $post_id, '_sla_msg_cta_label', $cta_label );
    update_post_meta( $post_id, '_sla_msg_cta_url',   $cta_url );
    update_post_meta( $post_id, '_sla_msg_expires',   (int) $expires_ts );
    update_post_meta( $post_id, '_sla_msg_read_by',   array() );
    return $post_id;
}

/**
 * Fetch admin-list messages (publish + future), optionally filtered by search term.
 */
function vance_admin_messages_list( $search = '' ) {
    $q = array(
        'post_type'      => 'vance_message',
        'post_status'    => array( 'publish', 'future' ),
        'posts_per_page' => 100,
        'orderby'        => 'date',
        'order'          => 'DESC',
    );
    if ( $search ) $q['s'] = $search;
    $posts = get_posts( $q );
    return $posts ?: array();
}

// ─── Front-end helpers ──────────────────────────────────────────────────────

/**
 * Get all currently-active messages for a given user. Filters by audience
 * targeting and expiry. Returns posts in newest-first order.
 *
 * @param int  $user_id
 * @param bool $include_read  default false — only unread messages.
 * @return WP_Post[]
 */
function vance_admin_messages_for_user( $user_id, $include_read = false ) {
    $user_id = (int) $user_id;
    if ( $user_id <= 0 ) return array();

    $now = time();
    $user_role_meta = get_user_meta( $user_id, '_sla_audience_role', true );
    $user_data = get_userdata( $user_id );
    $joined_ts = $user_data ? strtotime( $user_data->user_registered ) : 0;

    $posts = get_posts( array(
        'post_type'      => 'vance_message',
        'post_status'    => 'publish',
        'posts_per_page' => 50,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ) );

    $out = array();
    foreach ( $posts as $p ) {
        // Only show messages sent on or after the day this user joined.
        if ( $joined_ts && strtotime( $p->post_date ) < strtotime( 'midnight', $joined_ts ) ) continue;

        $exp = (int) get_post_meta( $p->ID, '_sla_msg_expires', true );
        if ( $exp && $exp < $now ) continue;

        // Skip messages this user has soft-deleted from their inbox.
        if ( vance_msg_is_user_deleted( $p->ID, $user_id ) ) continue;

        $aud = get_post_meta( $p->ID, '_sla_msg_audience', true ) ?: 'all';
        if ( $aud === 'users' ) {
            $allow = (array) get_post_meta( $p->ID, '_sla_msg_user_ids', true );
            if ( ! in_array( $user_id, array_map( 'intval', $allow ), true ) ) continue;
        } elseif ( $aud === 'role' ) {
            $role = get_post_meta( $p->ID, '_sla_msg_role', true );
            if ( $role && $role !== $user_role_meta ) continue;
        }

        if ( ! $include_read ) {
            $reads = (array) get_post_meta( $p->ID, '_sla_msg_read_by', true );
            if ( in_array( $user_id, array_map( 'intval', $reads ), true ) ) continue;
        }

        $out[] = $p;
    }
    return $out;
}

/**
 * Mark messages as read by a user (call when the dashboard renders them).
 *
 * @param int   $user_id
 * @param int[] $message_ids
 */
function vance_admin_messages_mark_read( $user_id, $message_ids ) {
    $user_id = (int) $user_id;
    if ( $user_id <= 0 || empty( $message_ids ) ) return;
    foreach ( $message_ids as $mid ) {
        $mid = (int) $mid;
        if ( $mid <= 0 ) continue;
        $reads = (array) get_post_meta( $mid, '_sla_msg_read_by', true );
        if ( ! in_array( $user_id, array_map( 'intval', $reads ), true ) ) {
            $reads[] = $user_id;
            update_post_meta( $mid, '_sla_msg_read_by', $reads );
        }
    }
}

/**
 * Render a single message as HTML for the dashboard banner / list.
 * Severity → colour palette is brand-teal-first.
 */
function vance_admin_messages_render( $post, $context = 'banner' ) {
    if ( ! $post ) return '';
    $sev       = get_post_meta( $post->ID, '_sla_msg_severity', true ) ?: 'info';
    $cta_label = get_post_meta( $post->ID, '_sla_msg_cta_label', true );
    $cta_url   = get_post_meta( $post->ID, '_sla_msg_cta_url', true );
    $body      = $post->post_content;
    // Basic markdown: bold + italic + auto-link + line-breaks.
    $body = preg_replace( '/\*\*(.+?)\*\*/', '<strong>$1</strong>', $body );
    $body = preg_replace( '/(^|[^\*])\*([^\*]+)\*([^\*]|$)/', '$1<em>$2</em>$3', $body );
    $body = make_clickable( $body );
    $body = nl2br( $body );

    $palette = array(
        'info'         => array( 'bg' => '#def4f4', 'fg' => '#0A1929', 'accent' => '#008080' ),
        'important'    => array( 'bg' => '#fff3cd', 'fg' => '#5b4400', 'accent' => '#b07d00' ),
        'announcement' => array( 'bg' => '#0A1929', 'fg' => '#ffffff', 'accent' => '#78bfbf' ),
    );
    $p = $palette[ $sev ] ?? $palette['info'];

    $banner_extra = $context === 'banner'
        ? 'margin: 0 0 18px; border-radius: 8px;'
        : 'margin: 0 0 14px; border-radius: 6px;';

    ob_start(); ?>
    <article class="vance-msg vance-msg--<?php echo esc_attr( $sev ); ?>" data-msg-id="<?php echo (int) $post->ID; ?>"
             style="padding: 16px 20px; background: <?php echo esc_attr( $p['bg'] ); ?>; color: <?php echo esc_attr( $p['fg'] ); ?>; border-left: 4px solid <?php echo esc_attr( $p['accent'] ); ?>; <?php echo $banner_extra; ?>">
        <header style="display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 6px;">
            <h4 style="margin: 0; font-size: 15px; font-weight: 700; color: inherit;"><?php echo esc_html( $post->post_title ); ?></h4>
            <small style="opacity: 0.72; font-size: 11px;"><?php echo esc_html( get_the_date( 'M j, Y', $post ) ); ?></small>
        </header>
        <div style="font-size: 14px; line-height: 1.6;"><?php echo $body; ?></div>
        <?php if ( $cta_label && $cta_url ) : ?>
            <p style="margin: 12px 0 0;">
                <a href="<?php echo esc_url( $cta_url ); ?>" class="button" style="background: <?php echo esc_attr( $p['accent'] ); ?>; color: white; border: none; padding: 8px 18px; font-weight: 600; text-decoration: none; display: inline-block; border-radius: 4px;">
                    <?php echo esc_html( $cta_label ); ?> →
                </a>
            </p>
        <?php endif; ?>
    </article>
    <?php
    return ob_get_clean();
}

/**
 * Render a message with its reply thread + reply form (for the user's
 * /dashboard/?tab=messages view). Uses vance_admin_messages_render() for the
 * top message body, then appends each reply and an inline reply form.
 */
function vance_admin_messages_render_with_thread( $post, $current_user_id ) {
    if ( ! $post ) return '';
    $current_user_id = (int) $current_user_id;

    $body_html = vance_admin_messages_render( $post, 'list' );

    $replies = vance_msg_get_replies( $post->ID );
    $delete_nonce = wp_create_nonce( 'vance_msg_user_delete_' . $post->ID );
    $reply_nonce  = wp_create_nonce( 'vance_msg_user_reply_' . $post->ID );

    ob_start(); ?>
    <div class="vance-msg-thread" data-msg-id="<?php echo (int) $post->ID; ?>" style="margin-bottom: 18px;">
        <?php echo $body_html; ?>

        <?php if ( $replies ) : ?>
            <div class="vance-msg-replies" style="margin: 8px 0 0 18px; padding: 0 0 0 14px; border-left: 2px solid #def4f4;">
                <?php foreach ( $replies as $r ) :
                    $author = get_userdata( (int) $r->post_author );
                    $is_admin_reply = $author && in_array( 'administrator', (array) $author->roles, true );
                    $author_label = $author
                        ? esc_html( $author->display_name . ( $is_admin_reply ? ' (Vance Medical team)' : '' ) )
                        : '—';
                    $r_body = wp_kses_post( $r->post_content );
                    $r_body = preg_replace( '/\*\*(.+?)\*\*/', '<strong>$1</strong>', $r_body );
                    $r_body = make_clickable( $r_body );
                    $r_body = nl2br( $r_body );
                ?>
                    <div class="vance-msg-reply" data-reply-id="<?php echo (int) $r->ID; ?>" style="margin: 10px 0; padding: 12px 16px; background: <?php echo $is_admin_reply ? '#def4f4' : '#f8fafc'; ?>; border-left: 3px solid <?php echo $is_admin_reply ? '#008080' : '#94a3b8'; ?>;">
                        <header style="display: flex; justify-content: space-between; gap: 10px; font-size: 11px; color: #64748b; margin-bottom: 4px; font-weight: 600;">
                            <span><?php echo $author_label; ?></span>
                            <span><?php echo esc_html( get_the_date( 'M j, g:ia', $r ) ); ?></span>
                        </header>
                        <div style="font-size: 13px; line-height: 1.55; color: #0F172A;"><?php echo $r_body; ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="vance-msg-actions" style="display: flex; gap: 8px; margin: 8px 0 0; align-items: center; flex-wrap: wrap;">
            <button type="button"
                    class="vance-msg-reply-toggle button"
                    data-msg-id="<?php echo (int) $post->ID; ?>"
                    style="background: #008080; color: white; border: none; padding: 6px 14px; font-size: 12px; font-weight: 600; cursor: pointer; border-radius: 0;">
                Reply
            </button>
            <button type="button"
                    class="vance-msg-delete button"
                    data-msg-id="<?php echo (int) $post->ID; ?>"
                    data-nonce="<?php echo esc_attr( $delete_nonce ); ?>"
                    style="background: transparent; color: #94a3b8; border: 1px solid #e2e8f0; padding: 6px 14px; font-size: 12px; font-weight: 600; cursor: pointer; border-radius: 0;">
                Delete from my inbox
            </button>
        </div>

        <form class="vance-msg-reply-form"
              data-msg-id="<?php echo (int) $post->ID; ?>"
              data-nonce="<?php echo esc_attr( $reply_nonce ); ?>"
              style="display: none; margin-top: 12px; padding: 14px; background: white; border: 1px solid #e2e8f0;">
            <textarea required minlength="3" maxlength="4000" placeholder="Write your reply… plain text, **bold**, *italic*, and URLs work." rows="4" style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; font-size: 13px; line-height: 1.55; box-sizing: border-box; resize: vertical; font-family: inherit;"></textarea>
            <div style="display: flex; gap: 8px; margin-top: 10px;">
                <button type="submit" class="button" style="background: #008080; color: white; border: none; padding: 8px 18px; font-size: 13px; font-weight: 700; cursor: pointer; border-radius: 0;">Send reply</button>
                <button type="button" class="vance-msg-reply-cancel button" style="background: transparent; color: #64748b; border: 1px solid #e2e8f0; padding: 8px 14px; font-size: 13px; font-weight: 600; cursor: pointer; border-radius: 0;">Cancel</button>
                <span class="vance-msg-reply-status" style="margin-left: auto; align-self: center; font-size: 12px; color: #64748b;"></span>
            </div>
        </form>
    </div>
    <?php
    return ob_get_clean();
}

/* ============================================================================
 * USER-SIDE AJAX: reply + soft-delete
 * ============================================================================ */

/**
 * AJAX: user replies to a message. Creates a vance_message_reply post with
 * post_parent linking back to the original message.
 *
 * POST: nonce, message_id, body
 */
function vance_msg_ajax_user_reply() {
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => 'Please sign in to reply.' ), 401 );
    }
    $msg_id = isset( $_POST['message_id'] ) ? absint( $_POST['message_id'] ) : 0;
    if ( ! $msg_id || get_post_type( $msg_id ) !== 'vance_message' ) {
        wp_send_json_error( array( 'message' => 'Invalid message.' ), 400 );
    }
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'vance_msg_user_reply_' . $msg_id ) ) {
        wp_send_json_error( array( 'message' => 'Security check failed.' ), 403 );
    }

    $body = isset( $_POST['body'] ) ? trim( wp_kses_post( wp_unslash( $_POST['body'] ) ) ) : '';
    if ( strlen( $body ) < 3 ) {
        wp_send_json_error( array( 'message' => 'Reply too short — please write at least 3 characters.' ) );
    }
    if ( strlen( $body ) > 4000 ) {
        wp_send_json_error( array( 'message' => 'Reply too long — 4000 characters maximum.' ) );
    }

    $current = wp_get_current_user();
    $title_seed = wp_trim_words( wp_strip_all_tags( $body ), 8, '…' );

    $reply_id = wp_insert_post( array(
        'post_type'    => 'vance_message_reply',
        'post_status'  => 'publish',
        'post_parent'  => $msg_id,
        'post_author'  => (int) $current->ID,
        'post_title'   => 'Re: ' . wp_trim_words( get_the_title( $msg_id ), 8, '…' ) . ' — ' . $title_seed,
        'post_content' => $body,
    ), true );

    if ( is_wp_error( $reply_id ) ) {
        wp_send_json_error( array( 'message' => $reply_id->get_error_message() ) );
    }

    // Stamp the reply with role context for the admin tool to render correctly.
    update_post_meta( $reply_id, '_sla_reply_author_roles', (array) $current->roles );
    update_post_meta( $reply_id, '_sla_reply_author_id',    (int) $current->ID );

    // Optional: re-mark the parent message as unread for admins so they see
    // the new reply. Stored as a separate "needs-attention" meta so we don't
    // clobber the read-receipts for end-users.
    update_post_meta( $msg_id, '_sla_msg_has_new_reply', time() );

    wp_send_json_success( array(
        'message'  => 'Reply sent.',
        'reply_id' => (int) $reply_id,
    ) );
}
add_action( 'wp_ajax_vance_msg_user_reply', 'vance_msg_ajax_user_reply' );

/**
 * AJAX: user soft-deletes a message from their own inbox. Doesn't affect
 * other users' visibility or the admin's audit trail.
 *
 * POST: nonce, message_id
 */
function vance_msg_ajax_user_delete() {
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => 'Please sign in.' ), 401 );
    }
    $msg_id = isset( $_POST['message_id'] ) ? absint( $_POST['message_id'] ) : 0;
    if ( ! $msg_id || get_post_type( $msg_id ) !== 'vance_message' ) {
        wp_send_json_error( array( 'message' => 'Invalid message.' ), 400 );
    }
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'vance_msg_user_delete_' . $msg_id ) ) {
        wp_send_json_error( array( 'message' => 'Security check failed.' ), 403 );
    }

    vance_msg_mark_user_deleted( $msg_id, get_current_user_id() );
    wp_send_json_success( array( 'message' => 'Removed from your inbox.' ) );
}
add_action( 'wp_ajax_vance_msg_user_delete', 'vance_msg_ajax_user_delete' );
