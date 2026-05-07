<?php
/**
 * Admin Messages — broadcast tool for sending dashboard messages to users.
 *
 * What this gives you:
 *   - A new admin page under "IBD Research Centre" → "User Messages"
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
add_action( 'init', 'vance_register_message_cpt' );
function vance_register_message_cpt() {
    register_post_type( 'vance_message', array(
        'labels' => array(
            'name'          => 'User Messages',
            'singular_name' => 'User Message',
        ),
        'public'              => false,
        'publicly_queryable'  => false,
        'show_ui'             => false,         // we provide our own admin UI
        'show_in_menu'        => false,
        'show_in_rest'        => false,
        'has_archive'         => false,
        'rewrite'             => false,
        'query_var'           => false,
        'capability_type'     => 'post',
        'supports'            => array( 'title', 'editor', 'author' ),
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
add_action( 'admin_menu', 'vance_register_admin_messages_menu', 25 );
function vance_register_admin_messages_menu() {
    add_submenu_page(
        'vance-content-hub',
        'User Messages',
        'User Messages',
        'administrator',
        'vance-user-messages',
        'vance_render_admin_messages_page'
    );
}

// ─── Admin page renderer ─────────────────────────────────────────────────────
function vance_render_admin_messages_page() {
    if ( ! current_user_can( 'administrator' ) ) {
        wp_die( 'Insufficient permissions.' );
    }

    $action = isset( $_GET['vance_action'] ) ? sanitize_key( $_GET['vance_action'] ) : '';
    $msg_id = isset( $_GET['msg_id'] ) ? absint( $_GET['msg_id'] ) : 0;

    // Handle inline GET-actions (resend / delete) with nonce check.
    if ( $action && $msg_id && check_admin_referer( 'vance_msg_action_' . $msg_id ) ) {
        if ( $action === 'delete' ) {
            wp_delete_post( $msg_id, true );
            wp_safe_redirect( add_query_arg( array( 'page' => 'vance-user-messages', 'vance_notice' => 'deleted' ), admin_url( 'admin.php' ) ) );
            exit;
        }
        if ( $action === 'resend' ) {
            // Resend = clear read-by meta + republish (set publish date to now).
            update_post_meta( $msg_id, '_sla_msg_read_by', array() );
            wp_update_post( array( 'ID' => $msg_id, 'post_date' => current_time( 'mysql' ), 'post_date_gmt' => current_time( 'mysql', true ), 'post_status' => 'publish' ) );
            wp_safe_redirect( add_query_arg( array( 'page' => 'vance-user-messages', 'vance_notice' => 'resent' ), admin_url( 'admin.php' ) ) );
            exit;
        }
    }

    // Handle send form submission.
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

    $search = isset( $_GET['vance_search'] ) ? sanitize_text_field( wp_unslash( $_GET['vance_search'] ) ) : '';
    $messages = vance_admin_messages_list( $search );

    // Build the user dropdown (limit to 500 to keep the page snappy; admins
    // typing into a search box will be more efficient on larger sites).
    $users = get_users( array( 'number' => 500, 'fields' => array( 'ID', 'user_email', 'display_name' ), 'orderby' => 'display_name' ) );
    ?>
    <div class="wrap" style="max-width: 1200px;">
        <h1 style="display: flex; align-items: center; gap: 12px;">
            <span class="dashicons dashicons-email-alt" style="font-size: 28px; width: 28px; height: 28px;"></span>
            User Messages
        </h1>
        <p>Send a message to dashboard users. Messages appear at the top of <code>/dashboard/</code> and on the <em>My Messages</em> tab.</p>

        <?php if ( ! empty( $_GET['vance_notice'] ) ) : ?>
            <div class="notice notice-success is-dismissible"><p><?php
                echo $_GET['vance_notice'] === 'deleted' ? 'Message deleted.' :
                    ( $_GET['vance_notice'] === 'resent' ? 'Message resent — read receipts cleared.' : 'Done.' );
            ?></p></div>
        <?php endif; ?>
        <?php if ( $send_error )  : ?><div class="notice notice-error is-dismissible"><p><?php echo esc_html( $send_error ); ?></p></div><?php endif; ?>
        <?php if ( $send_notice ) : ?><div class="notice notice-success is-dismissible"><p><?php echo esc_html( $send_notice ); ?></p></div><?php endif; ?>

        <h2 style="margin-top: 32px;">Compose new message</h2>
        <form method="post" style="background: white; padding: 20px 24px; border: 1px solid #c3c4c7; box-shadow: 0 1px 1px rgba(0,0,0,0.04); margin-bottom: 32px;">
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
                        <p class="description">Optional. After this date, message stops appearing on the dashboard. Blank = never expires.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="msg_schedule">Schedule send</label></th>
                    <td>
                        <input type="datetime-local" id="msg_schedule" name="msg_schedule">
                        <p class="description">Optional. If set in the future, the message stays scheduled until that time. Blank = send now.</p>
                    </td>
                </tr>
            </table>
            <p class="submit">
                <button type="submit" class="button button-primary">Send message</button>
            </p>
        </form>

        <h2 style="margin-top: 32px; display: flex; align-items: center; justify-content: space-between; gap: 16px;">
            <span>Previous messages</span>
            <form method="get" style="margin: 0;">
                <input type="hidden" name="page" value="vance-user-messages">
                <input type="search" name="vance_search" value="<?php echo esc_attr( $search ); ?>" placeholder="Search title or body…" style="width: 280px;">
                <button class="button">Search</button>
                <?php if ( $search ) : ?><a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=vance-user-messages' ) ); ?>">Clear</a><?php endif; ?>
            </form>
        </h2>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 26%;">Title</th>
                    <th style="width: 12%;">Severity</th>
                    <th style="width: 18%;">Audience</th>
                    <th style="width: 14%;">Sent</th>
                    <th style="width: 10%;">Status</th>
                    <th style="width: 10%;">Reads</th>
                    <th style="width: 10%;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ( empty( $messages ) ) : ?>
                    <tr><td colspan="7" style="padding: 20px; text-align: center; color: #666;">No messages yet — compose one above.</td></tr>
                <?php else : foreach ( $messages as $m ) :
                    $sev   = get_post_meta( $m->ID, '_sla_msg_severity', true ) ?: 'info';
                    $aud   = get_post_meta( $m->ID, '_sla_msg_audience', true ) ?: 'all';
                    $reads = (array) get_post_meta( $m->ID, '_sla_msg_read_by', true );
                    $exp   = (int)  get_post_meta( $m->ID, '_sla_msg_expires', true );
                    $aud_label = $aud === 'all' ? 'All users' : ( $aud === 'users' ? 'Selected users' : 'Role: ' . esc_html( get_post_meta( $m->ID, '_sla_msg_role', true ) ?: '—' ) );
                    $resend_url = wp_nonce_url( admin_url( 'admin.php?page=vance-user-messages&vance_action=resend&msg_id=' . $m->ID ), 'vance_msg_action_' . $m->ID );
                    $delete_url = wp_nonce_url( admin_url( 'admin.php?page=vance-user-messages&vance_action=delete&msg_id=' . $m->ID ), 'vance_msg_action_' . $m->ID );
                    $is_expired = $exp && $exp < time();
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
                        <td>
                            <a href="<?php echo esc_url( $resend_url ); ?>">Resend</a> |
                            <a href="<?php echo esc_url( $delete_url ); ?>" onclick="return confirm('Delete this message?');" style="color: #b32d2e;">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>

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

    $posts = get_posts( array(
        'post_type'      => 'vance_message',
        'post_status'    => 'publish',
        'posts_per_page' => 50,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ) );

    $out = array();
    foreach ( $posts as $p ) {
        $exp = (int) get_post_meta( $p->ID, '_sla_msg_expires', true );
        if ( $exp && $exp < $now ) continue;

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
