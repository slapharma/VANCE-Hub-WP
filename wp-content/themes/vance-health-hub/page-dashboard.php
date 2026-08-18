<?php
/**
 * Template Name: User Dashboard
 */

// 1. Session & Auth Check
$current_user = wp_get_current_user();
$is_logged_in = is_user_logged_in();

// Logged-out visitors get sent to the themed login screen with redirect_to
// set, same as /my-notes/ and wp-login.php — previously this page instead
// rendered its own in-page "Login Hero" at its own URL with no redirect_to
// anywhere, so a sign-in from here had no guaranteed way back to /dashboard/.
if ( ! $is_logged_in ) {
    wp_safe_redirect( add_query_arg( 'redirect_to', urlencode( home_url( '/dashboard/' ) ), home_url( '/login/' ) ) );
    exit;
}

// 2. Handle PDF Print Request
if ( isset($_GET['print_note']) && is_user_logged_in() ) {
    $note_id = sanitize_text_field($_GET['print_note']);
    $my_notes = get_user_meta(get_current_user_id(), '_sla_user_notes', true) ?: array();
    $target_note = null;
    foreach($my_notes as $n) { if(isset($n['id']) && $n['id'] === $note_id) { $target_note = $n; break; } }
    
    if($target_note) {
        $u = wp_get_current_user();
        $fullname = trim($u->first_name . ' ' . $u->last_name) ?: $u->display_name;
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title><?php echo esc_html($target_note['title']); ?> - PDF</title>
            <style>
                body { font-family: sans-serif; padding: 40px; color: #333; line-height: 1.6; max-width: 800px; margin: 0 auto; }
                .header { border-bottom: 2px solid #008080; padding-bottom: 20px; margin-bottom: 40px; }
                .logo-area { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; }
                .logo { font-size: 24px; font-weight: 800; color: #0A1929; }
                .badge { background: #008080; color: white; padding: 4px 12px; border-radius: 0; font-size: 12px; font-weight: 700; }
                .meta { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 12px; color: #64748B; background: #F8FAFC; padding: 15px; border-radius: 0; }
                h1 { font-size: 28px; margin: 0 0 20px 0; color: #0F172A; }
                .content { font-size: 14px; white-space: pre-wrap; }
            </style>
        </head>
        <body onload="window.print()">
            <div class="header">
                <div class="logo-area">
                    <div class="logo">Vance Medical Hub</div>
                    <div class="badge">IBD RESEARCH CENTRE NOTE</div>
                </div>
                <div class="meta">
                    <div><strong>Note Name:</strong> <?php echo esc_html($target_note['title']); ?></div>
                    <div><strong>User:</strong> <?php echo esc_html($fullname); ?></div>
                    <div><strong>Created:</strong> <?php echo date('M j, Y H:i', strtotime($target_note['date'])); ?></div>
                    <div><strong>Downloaded:</strong> <?php echo date('M j, Y H:i'); ?></div>
                </div>
            </div>
            <h1><?php echo esc_html($target_note['title']); ?></h1>
            <div class="content"><?php echo wp_kses_post($target_note['content']); ?></div>
        </body>
        </html>
        <?php
        exit;
    }
}

get_header(); 
?>

<!-- HIDE GLOBAL HEADER FOR DASHBOARD -->
<style>
    .site-header { display: none !important; }
    /* Reset margins for dashboard */
    body { margin: 0; padding: 0; overflow-x: hidden; background-color: #F8FAFC; }
</style>

<?php
    // DATA PREP
    $first_name = get_user_meta( $current_user->ID, 'first_name', true ) ?: $current_user->display_name;
    $job_title = get_user_meta( $current_user->ID, '_sla_job_title', true ) ?: 'Add Job Title';
    $org = get_user_meta( $current_user->ID, '_sla_organization', true ) ?: 'Add Organization';
    $bookmarks = get_user_meta( $current_user->ID, '_sla_reading_list', true ) ?: array();
    $profile_img = get_avatar_url( $current_user->ID, array('size' => 128) );
    
    // Role Logic
    $user_roles = (array) $current_user->roles;
    $is_practitioner = in_array( 'practitioner', $user_roles );
    
    // Theme Vars — the refreshed light/teal design applies to every role now;
    // $is_practitioner is only used below for a couple of content differences
    // (professional title, home greeting), not for styling.
    $theme_primary = '#008080';
    $theme_sidebar = '#FFFFFF';
    $theme_sidebar_text = '#64748B';
    $nav_label_weight = '600';
    $nav_item_weight = '400';
    $sidebar_logo_color = '#0A1929';
    $nav_hover_bg = '#F1F5F9';
    $nav_active_color = '#008080';
    $nav_active_bg = '#def4f4';

    // Navigation Configuration (Global)
    $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'home';
    // Back-compat: legacy ?tab=calculators links resolve to the new tools tab.
    if ( $current_tab === 'calculators' ) { $current_tab = 'tools'; }
    // Back-compat: legacy ?tab=clinical-profile links resolve to the renamed Health Profile tab.
    if ( $current_tab === 'clinical-profile' ) { $current_tab = 'health-profile'; }
    // The sidebar is built from the feature registry, not hardcoded here:
    // Appearance → Customize → Member Dashboard controls which tabs exist, what
    // they are called and which group they sit in. See inc/dashboard-features.php.
    // Building it in one place is what keeps the sidebar, the breadcrumb, the
    // router and the home grid from disagreeing about any of that.
    $nav_items = vance_dashboard_nav_items();

    // A hidden tab must not render just because someone kept the link: an
    // external page, an old bookmark and a couple of in-theme buttons all point
    // straight at ?tab=ai-chats and friends. Fall back to home rather than
    // showing an empty shell with a breadcrumb for a feature that is off.
    if ( ! vance_dashboard_feature_enabled($current_tab) ) {
        $current_tab = 'home';
    }
    // Shorthands for the home grid below, which shows a summary card per
    // feature: the card goes when the tab does, and carries the tab's name.
    $dash_on    = 'vance_dashboard_feature_enabled';
    $dash_label = 'vance_dashboard_feature_label';
?>

<!-- DASHBOARD STYLES (Scoped) -->
<style>
:root {
    --dash-primary: <?php echo $theme_primary; ?>;
    --dash-sidebar: <?php echo $theme_sidebar; ?>;
    --dash-text: #1F2937;
    --dash-border: #E2E8F0;
}
.dashboard-wrap { display: flex; min-height: 100vh; font-family: 'Inter', sans-serif; }
.dash-sidebar { width: 260px; background: var(--dash-sidebar); border-right: 1px solid var(--dash-border); position: fixed; height: 100vh; z-index: 999; display: flex; flex-direction: column; overflow-y: auto; }
.dash-main { margin-left: 260px; flex: 1; background: #F0F4F8; display: flex; flex-direction: column; width: calc(100% - 260px); }

/* Sidebar */
.sidebar-header { height: 64px; display: flex; align-items: center; padding: 0 24px; border-bottom: 1px solid rgba(0,0,0,0.05); }
/* Sidebar logo, sized exactly like the site header's.
   main.css sizes the header logo with a crop window, not a height: .logo-area
   is 145x36 (125x32 below 768px) with overflow:hidden, and the img is scaled to
   that WIDTH, so the artwork's built-in whitespace is trimmed off the top and
   bottom. The dashboard set `height: 50px` instead, which fits the whole PNG,
   whitespace included — so the wordmark itself rendered about a third smaller
   than the one on the homepage. Same numbers as main.css's .logo-area rules;
   change them together. */
.dash-logo { font-family: 'Outfit', sans-serif; font-weight: 800; font-size: 20px; color: <?php echo $sidebar_logo_color; ?>; display: flex; align-items: center; gap: 8px; text-decoration: none; width: 125px; height: 32px; overflow: hidden; flex: 0 0 auto; }
.dash-logo img { width: 125px; height: auto; max-width: none; display: block; }
@media (min-width: 768px) {
    .dash-logo { width: 145px; height: 36px; }
    .dash-logo img { width: 145px; }
}
.dash-nav { padding: 20px 12px; flex: 1; }
.nav-section { margin-bottom: 24px; }
.nav-label { font-size: 11px; font-weight: <?php echo $nav_label_weight; ?>; color: <?php echo $theme_sidebar_text; ?>; text-transform: uppercase; margin: 0 0 8px 12px; letter-spacing: 0.5px; opacity: 0.7; }
.nav-item { display: flex; align-items: center; gap: 12px; padding: 10px 12px; color: <?php echo $theme_sidebar_text; ?>; text-decoration: none; border-radius: 0; font-size: 14px; font-weight: <?php echo $nav_item_weight; ?>; transition: all 0.2s; margin-bottom: 2px; }
.nav-item:hover { background: <?php echo $nav_hover_bg; ?>; color: var(--dash-primary); }
.nav-item.active { background: <?php echo $nav_active_bg; ?>; color: <?php echo $nav_active_color; ?>; }

/* Header */
.dash-header { height: 64px; background: white; border-bottom: 1px solid var(--dash-border); display: flex; align-items: center; justify-content: space-between; padding: 0 32px; position: sticky; top: 0; z-index: 998; }
.page-title { font-family: 'Outfit', sans-serif; font-size: 18px; font-weight: 600; color: #0A1929; display: flex; align-items: center; gap: 8px; }

/* Header breadcrumb — "My Dashboard › My Profile".
   The trail is only ever two deep (the dashboard has no nested tabs), so this
   is a flat list rather than anything recursive. The separator is a real
   element with aria-hidden rather than a ::before, so screen readers get
   "My Dashboard, My Profile" instead of the chevron being announced. */
.dash-crumbs { display: flex; align-items: center; gap: 8px; margin: 0; padding: 0; list-style: none; font-family: 'Outfit', sans-serif; font-size: 18px; font-weight: 600; }
.dash-crumbs li { display: flex; align-items: center; gap: 8px; }
.dash-crumbs a { color: #64748B; text-decoration: none; transition: color 0.2s; }
.dash-crumbs a:hover, .dash-crumbs a:focus-visible { color: var(--dash-primary); text-decoration: underline; }
.dash-crumb-sep { color: #CBD5E1; font-weight: 400; }
.dash-crumb-current { color: #0A1929; }
@media (max-width: 640px) {
    /* The root crumb is the one thing a phone header can afford to lose — the
       sidebar toggle sits immediately to its left and does the same job. Only
       the linked form is dropped: on the home tab the root IS the current page,
       and hiding that would leave an empty breadcrumb. */
    .dash-crumbs { font-size: 16px; }
    .dash-crumbs .dash-crumb-root--link { display: none; }
}
.user-profile { display: flex; align-items: center; gap: 12px; cursor: pointer; }
.profile-avatar { width: 32px; height: 32px; border-radius: 0; object-fit: cover; border: 1px solid #E2E8F0; }

/* My Profile: platform-icon row under the avatar photo.
   2026-08-11: previously a negative top margin (-8px) pulled this row up
   against the 120px avatar's bottom edge, reading as "attached to" the photo
   (reported). Real margin-top instead, and doubled 28px -> 56px per request.
   #vance-profile-social-icons always renders (hidden via display:none when no
   socials are saved yet) so JS can insert the first icon without a reload —
   see vanceRefreshSocialIcon() below. Fixed 3-column grid (not flex-wrap) so
   the row always breaks 3/3 regardless of how many platforms are saved. */
.vance-profile-social-icon {
    width: 56px; height: 56px; border-radius: 50%; background: #F1F5F5;
    color: var(--dash-primary, #008080); display: flex; align-items: center;
    justify-content: center; opacity: 0.85; transition: opacity 0.2s, transform 0.2s;
}
.vance-profile-social-icon:hover { opacity: 1; transform: translateY(-1px); }
.vance-profile-social-icon svg { width: 28px; height: 28px; }

/* Plain-text My Links list under the icon row — protocol-stripped display
   text, real URL still in href. Social Profiles don't get this (they have
   icons already); request 2026-08-11. */
.vance-profile-link-text {
    display: block; font-size: 13px; font-weight: 500; color: var(--dash-primary, #008080);
    text-decoration: none; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    max-width: 100%;
}
.vance-profile-link-text:hover { text-decoration: underline; }

/* Inline per-field save control for Social Profiles / My Links. The page's
   single "Update Profile" button at the bottom still saves everything at
   once, but that gives no feedback about which field changed — this adds an
   instant per-field confirmation (and, for social fields, refreshes the icon
   row above without waiting on a full-form submit). */
#profile-form-main input[type="text"],
#profile-form-main input[type="email"],
#profile-form-main input[type="url"],
#profile-form-main textarea {
    font-size: 14px; /* was unset (name/email/job/org/bio) or 13px (social/links) — unified */
}
/* Lighter placeholder text so an empty field reads as visibly empty rather
   than looking like it already has a (dim) value in it — request 2026-08-11. */
#profile-form-main input::placeholder,
#profile-form-main textarea::placeholder {
    color: #C1C8D2;
    opacity: 1; /* Firefox dims placeholder color further via opacity by default */
}

/* Leading icon inside each input/textarea — request 2026-08-11: "add relevant
   icons next to each input box". .vance-input-icon-wrap is the positioning
   context; the icon is decorative (the label already names the field), so
   aria-hidden + pointer-events:none keeps it out of the a11y tree and clicks
   pass through to the input beneath. */
.vance-input-icon-wrap { position: relative; }
.vance-field-save-row .vance-input-icon-wrap { flex: 1; min-width: 0; }
/* Single source of truth for the box model on every icon-fronted field —
   width/padding/border used to be set inline per-input (or, for the Social
   Profiles/My Links rewrite, accidentally not set at all: those inputs
   rendered at native browser size/border with no left-padding, so the icon —
   centered against the WRAPPER, which the flex row stretches to the save
   button's 44px — ended up floating well above the input's own tiny box.
   That's the "icons off-set" reported 2026-08-11. Centralising here, and
   giving the save-row variant an explicit 44px to match its button exactly,
   removes the mismatch instead of just approximating it. */
.vance-input-icon-wrap input,
.vance-input-icon-wrap textarea {
    width: 100%; box-sizing: border-box; font-family: inherit;
    padding: 10px 10px 10px 36px; border: 1px solid #E2E8F0; border-radius: 0;
}
.vance-input-icon-wrap textarea { resize: none; }
.vance-field-save-row .vance-input-icon-wrap input {
    height: 44px; padding-top: 0; padding-bottom: 0;
}
.vance-input-icon {
    position: absolute; left: 10px; top: 50%; transform: translateY(-50%);
    display: flex; color: #94A3B8; pointer-events: none;
}
.vance-input-icon--top { top: 13px; transform: none; }
.vance-input-icon svg { width: 16px; height: 16px; }

.vance-field-save-row { display: flex; gap: 8px; align-items: stretch; }
.vance-field-save-btn {
    flex: 0 0 auto; width: 44px; height: 44px; display: flex; align-items: center;
    justify-content: center; border: 1px solid #E2E8F0; background: #fff;
    color: #94A3B8; cursor: pointer; transition: all 0.2s;
}
.vance-field-save-btn:hover:not(:disabled) { border-color: var(--dash-primary, #008080); color: var(--dash-primary, #008080); }
.vance-field-save-btn:disabled { opacity: 0.6; cursor: default; }
.vance-field-save-btn.is-saved { background: var(--dash-primary, #008080); border-color: var(--dash-primary, #008080); color: #fff; }
.vance-field-save-btn svg { width: 18px; height: 18px; flex: 0 0 auto; }
/* Two icons per button, toggled by data-empty (set server-side on load, kept
   live by an `input` listener — see vanceUpdateFieldEmptyState() below):
   checkmark = field has a value; circle-slash = field is empty, nothing to
   save. Request 2026-08-11: "the tick should be changed to a circle with a
   line through it to symbolise empty." The save-confirmation flash
   (.is-saved) always shows the checkmark regardless of data-empty — "saved"
   is a universal confirmation, not a value-state indicator. */
.vance-field-save-btn .vance-icon-empty { display: none; }
.vance-field-save-btn[data-empty="1"] .vance-icon-check { display: none; }
.vance-field-save-btn[data-empty="1"] .vance-icon-empty { display: block; }
.vance-field-save-btn.is-saved .vance-icon-check { display: block !important; }
.vance-field-save-btn.is-saved .vance-icon-empty { display: none !important; }

/* Delete button — request 2026-08-11: "add a delete button next to each
   social and link input field". Clears + persists the field; the JS no-ops
   on an already-empty field rather than hiding/disabling the button outright
   (avoids needing extra sync JS), but it dims via this sibling selector off
   the save button's own [data-empty] so it still reads as "nothing to do"
   at a glance. */
.vance-field-delete-btn {
    flex: 0 0 auto; width: 44px; height: 44px; display: flex; align-items: center;
    justify-content: center; border: 1px solid #E2E8F0; background: #fff;
    color: #94A3B8; cursor: pointer; transition: all 0.2s;
}
.vance-field-delete-btn:hover:not(:disabled) { border-color: #DC2626; color: #DC2626; }
.vance-field-delete-btn:disabled { opacity: 0.6; cursor: default; }
.vance-field-delete-btn svg { width: 18px; height: 18px; flex: 0 0 auto; }
.vance-field-save-btn[data-empty="1"] ~ .vance-field-delete-btn { opacity: 0.4; }

/* Content */
.dash-content { padding: 32px; max-width: 1400px; margin: 0 auto; width: 100%; }
.dash-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px; }
.card-wide { grid-column: 1 / -1; }
@media (min-width: 1100px) { .card-wide { grid-column: span 2; } }

.dash-card { background: white; border-radius: 0; padding: 24px; border: 1px solid #E2E8F0; transition: all 0.2s; display: flex; flex-direction: column; }
.dash-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.05); transform: translateY(-2px); }
.card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.card-title { font-family: 'Outfit', sans-serif; font-size: 18px; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 10px; margin: 0; }
.card-icon { width: 36px; height: 36px; background: #F8FAFC; border-radius: 0; display: flex; align-items: center; justify-content: center; font-size: 18px; color: var(--dash-primary); }
.card-link { font-size: 13px; font-weight: 600; color: #008080; text-decoration: none; cursor: pointer; border: none; background: none; }

/* List Items */
.dash-list { display: flex; flex-direction: column; gap: 0; }
.list-item { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid #2f4f6f; }
.list-item:last-child { border-bottom: none; }
.item-title { font-size: 14px; font-weight: 600; color: #0F172A; margin-bottom: 2px; }
.item-meta { font-size: 12px; color: #64748B; }

/* Reading list row actions.
   These were four elements styled inline — two <a>, two <button> — and the
   buttons came out in the browser's default UI font while the anchors
   inherited Inter, so "Copy Link" sat visibly wrong next to "Open in New Tab".
   A shared class with an explicit `font: inherit` is the fix; keep new row
   actions on it rather than reintroducing inline styles. */
.rl-actions { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
.rl-btn {
    font-family: inherit; font-size: 13px; font-weight: 500; line-height: 1.2;
    padding: 8px 12px; border: 1px solid #E2E8F0; border-radius: 0;
    background: #FFFFFF; color: #475569; cursor: pointer; text-decoration: none;
    display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s;
}
.rl-btn:hover { border-color: var(--dash-primary); color: var(--dash-primary); }
.rl-btn:focus-visible { outline: 2px solid var(--dash-primary); outline-offset: 2px; }
.rl-btn--primary { background: var(--dash-primary); border-color: var(--dash-primary); color: #FFFFFF; font-weight: 600; }
.rl-btn--primary:hover { background: #006A6A; border-color: #006A6A; color: #FFFFFF; }
.rl-btn--text { border-color: transparent; background: none; color: #EF4444; font-weight: 600; }
.rl-btn--text:hover { border-color: transparent; color: #B91C1C; text-decoration: underline; }

/* Minimalist article reader — body copy only, no sidebar/footer/read-next. */
.rl-reader { position: fixed; inset: 0; z-index: 10002; display: none; align-items: center; justify-content: center; padding: 20px; background: rgba(10,25,41,0.55); }
.rl-reader.is-open { display: flex; }
.rl-reader__panel { background: #FFFFFF; width: 100%; max-width: 780px; max-height: 90vh; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 20px 50px rgba(0,0,0,0.2); }
.rl-reader__head { padding: 20px 24px; border-bottom: 1px solid #2f4f6f; display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; }
.rl-reader__title { margin: 0; font-family: 'Outfit', sans-serif; font-size: 20px; line-height: 1.3; color: #0A1929; }
.rl-reader__meta { margin: 4px 0 0; font-size: 12px; color: #64748B; }
.rl-reader__close { font-size: 26px; line-height: 1; border: 1px solid #E2E8F0; background: #FFFFFF; color: #64748B; cursor: pointer; width: 38px; height: 38px; flex: 0 0 auto; display: flex; align-items: center; justify-content: center; }
.rl-reader__close:hover { color: #EF4444; border-color: #EF4444; }
.rl-reader__body { flex: 1; overflow-y: auto; padding: 28px 32px; color: #334155; font-size: 16px; line-height: 1.7; }
.rl-reader__body img { max-width: 100%; height: auto; }
.rl-reader__body h1, .rl-reader__body h2, .rl-reader__body h3 { font-family: 'Outfit', sans-serif; color: #0F172A; line-height: 1.3; margin: 1.6em 0 0.5em; }
.rl-reader__body h1 { font-size: 24px; } .rl-reader__body h2 { font-size: 20px; } .rl-reader__body h3 { font-size: 17px; }
.rl-reader__body p { margin: 0 0 1.1em; }
.rl-reader__body a { color: var(--dash-primary); }
.rl-reader__body ul, .rl-reader__body ol { margin: 0 0 1.1em 1.3em; }
.rl-reader__body blockquote { margin: 1.2em 0; padding: 2px 0 2px 16px; border-left: 3px solid var(--dash-primary); color: #475569; }
.rl-reader__body table { width: 100%; border-collapse: collapse; margin: 0 0 1.1em; }
.rl-reader__body td, .rl-reader__body th { border: 1px solid #E2E8F0; padding: 8px 10px; text-align: left; }
.rl-reader__foot { padding: 14px 24px; border-top: 1px solid #2f4f6f; display: flex; gap: 8px; justify-content: flex-end; align-items: center; }
.rl-reader__hero { width: 100%; height: 200px; object-fit: cover; display: block; margin: 0 0 22px; }
.rl-reader__state { padding: 48px 24px; text-align: center; color: #64748B; }
@media (max-width: 640px) {
    .rl-reader { padding: 0; }
    .rl-reader__panel { max-width: none; max-height: 100vh; height: 100vh; }
    .rl-reader__body { padding: 20px; }
}

/* "Add to Note" picker — a small popover anchored to whichever button opened
   it. Appended to <body> rather than the button's parent so it is never
   clipped by the chat modal's own overflow:hidden. */
.vn-pick { position: absolute; z-index: 10050; width: 300px; max-width: calc(100vw - 24px); background: #FFFFFF; border: 1px solid #E2E8F0; box-shadow: 0 12px 32px rgba(10,25,41,0.18); font-family: 'Inter', sans-serif; }
.vn-pick__head { padding: 12px 14px; border-bottom: 1px solid #2f4f6f; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.6px; color: #64748B; display: flex; justify-content: space-between; align-items: center; gap: 8px; }
.vn-pick__close { border: none; background: none; font-size: 20px; line-height: 1; cursor: pointer; color: #94A3B8; padding: 0; }
.vn-pick__close:hover { color: #EF4444; }
.vn-pick__list { max-height: 210px; overflow-y: auto; }
.vn-pick__item { display: block; width: 100%; text-align: left; border: none; background: none; padding: 10px 14px; cursor: pointer; font-family: inherit; font-size: 13.5px; color: #0F172A; border-bottom: 1px solid #2f4f6f; }
.vn-pick__item:hover { background: #F1F5F9; color: var(--dash-primary); }
.vn-pick__item span { display: block; font-size: 11px; color: #94A3B8; margin-top: 2px; }
.vn-pick__empty { padding: 14px; font-size: 13px; color: #64748B; }
.vn-pick__new { padding: 12px 14px; border-top: 1px solid #2f4f6f; background: #F8FAFC; display: flex; gap: 8px; }
.vn-pick__new input { flex: 1; min-width: 0; padding: 8px 10px; border: 1px solid #E2E8F0; border-radius: 0; font-family: inherit; font-size: 13px; }
.vn-pick__new input:focus { outline: none; border-color: var(--dash-primary); }
.vn-pick__new button { border: none; background: var(--dash-primary); color: #FFFFFF; font-family: inherit; font-size: 13px; font-weight: 600; padding: 8px 12px; cursor: pointer; }
.vn-pick__status { padding: 10px 14px; font-size: 12.5px; color: #64748B; border-top: 1px solid #2f4f6f; }
.vn-pick__status--error { color: #B91C1C; }
.vn-pick__status--ok { color: #047857; }
.vn-pick.is-busy { opacity: 0.6; pointer-events: none; }

/* Small round icon button sitting under each VANCE-Ai answer. */
.vn-save-answer { display: inline-flex; align-items: center; gap: 6px; margin-top: 10px; padding: 5px 10px; border: 1px solid #E2E8F0; background: #FFFFFF; color: #64748B; font-family: inherit; font-size: 11.5px; font-weight: 600; cursor: pointer; border-radius: 0; }
.vn-save-answer:hover { border-color: var(--dash-primary); color: var(--dash-primary); }
.vn-save-answer svg { width: 13px; height: 13px; }

/* Mobile */
@media (max-width: 768px) {
    .dash-sidebar { transform: translateX(-100%); transition: transform 0.3s; }
    .dash-sidebar.active { transform: translateX(0); }
    .dash-main { margin-left: 0; width: 100%; }
    .mobile-toggle { display: block !important; margin-right: 16px; font-size: 24px; cursor: pointer; }
}
.mobile-toggle { display: none; }
</style>

<div class="dashboard-wrap">
    
    <!-- SIDEBAR -->
    <aside class="dash-sidebar" id="sidebar">
        <div class="sidebar-header">
            <?php // Sizing lives in .dash-logo above so it stays in step with the site header. ?>
            <a href="/" class="dash-logo">
                <img src="<?php echo get_template_directory_uri(); ?>/assets/img/logo.png" alt="Vance Medical">
            </a>
            <button class="mobile-toggle" style="margin-left: auto; color: #0A1929;" onclick="toggleSidebar()">✕</button>
        </div>

        <!-- Nav Items Loop -->

        <nav class="dash-nav">
            <?php foreach($nav_items as $section => $items):
                // Editable per section, and empty for the top group, which sits
                // under the logo where a heading reads as clutter.
                $section_heading = vance_dashboard_section_label($section);
                ?>
                <div class="nav-section">
                    <?php if($section_heading !== ''): ?>
                        <div class="nav-label"><?php echo esc_html($section_heading); ?></div>
                    <?php endif; ?>
                    <?php foreach($items as $slug => $data): ?>
                        <a href="?tab=<?php echo esc_attr($slug); ?>" class="nav-item <?php echo $current_tab === $slug ? 'active' : ''; ?>">
                            <span style="width:20px;text-align:center;"><?php echo $data['icon']; ?></span> <?php echo esc_html($data['label']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </nav>
    </aside>

    <!-- MAIN -->
    <main class="dash-main">
        <header class="dash-header">
            <div style="display:flex; align-items:center;">
                <span class="mobile-toggle" onclick="toggleSidebar()" style="color:#0f172a;">☰</span>
                <?php
                // Resolved once here and reused by the content H1 below, rather
                // than the two separate copies of this loop that used to drift.
                // Read from the registry rather than from $nav_items so a renamed
                // tab still titles its own page even in the cases where it is not
                // in the sidebar.
                $tab_label = vance_dashboard_feature_label($current_tab);
                if ('' === $tab_label) { $tab_label = 'Overview'; }
                // On the home tab the trail would read "My Dashboard › Dashboard",
                // so the root stands alone there instead of repeating itself.
                $is_dash_root = ( 'home' === $current_tab );
                ?>
                <nav class="page-title" aria-label="Breadcrumb">
                    <ol class="dash-crumbs">
                        <li class="dash-crumb-root<?php echo $is_dash_root ? '' : ' dash-crumb-root--link'; ?>">
                            <?php if ( $is_dash_root ) : ?>
                                <span class="dash-crumb-current" aria-current="page">My Dashboard</span>
                            <?php else : ?>
                                <a href="?tab=home">My Dashboard</a>
                                <span class="dash-crumb-sep" aria-hidden="true">&rsaquo;</span>
                            <?php endif; ?>
                        </li>
                        <?php if ( ! $is_dash_root ) : ?>
                            <li><span class="dash-crumb-current" aria-current="page"><?php echo esc_html($tab_label); ?></span></li>
                        <?php endif; ?>
                    </ol>
                </nav>
            </div>

            <div style="display: flex; align-items: center; gap: 20px;">
                <div class="user-profile">
                    <div class="dash-user-meta">
                        <div style="font-size: 14px; font-weight: 600; color: #0F172A;"><?php echo esc_html($first_name); ?></div>
                        <div style="font-size: 11px; color: #64748B;"><?php echo esc_html($is_practitioner ? 'MD, ' . $org : 'Member'); ?></div>
                    </div>
                    <img src="<?php echo esc_url($profile_img); ?>" class="profile-avatar">
                </div>
                <a href="<?php echo wp_logout_url(home_url()); ?>" title="Log Out" aria-label="Log Out" style="display:flex; align-items:center; justify-content:center; width:36px; height:36px; border:1px solid #E2E8F0; border-radius:0; color:#64748B; text-decoration:none; transition:all 0.2s;" onmouseover="this.style.background='#F1F5F9'; this.style.color='#EF4444';" onmouseout="this.style.background=''; this.style.color='#64748B';">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                </a>
            </div>
        </header>

        <div class="dash-content">
            <?php // $tab_label is resolved once alongside the header breadcrumb above. ?>
            <div style="margin-bottom: 32px; display: flex; justify-content: space-between; align-items: flex-end;">
                <div>
                    <h1 style="font-family:'Outfit'; font-size:28px; color:<?php echo $theme_primary; ?>; margin:0 0 8px 0;"><?php echo esc_html($tab_label); ?></h1>
                    <p style="color:#64748B; margin:0;">
                        <?php 
                        switch($current_tab) {
                            case 'home': echo $is_practitioner ? 'You have 3 patient updates pending review.' : "Hi {$first_name}, welcome back to your Gastro Health Hub."; break;
                            case 'health-profile': echo 'View your health discovery results and update your health profile details.'; break;
                            case 'notes': echo 'Your private clinical and personal notes.'; break;
                            case 'ai-chats': echo 'History of your conversations with VANCE-Ai.'; break;
                            default: echo '';
                        }
                        ?>
                    </p>
                </div>
                <?php if($current_tab === 'notes'): ?>
                    <a href="/my-notes/?new=1" class="btn-primary" style="background:<?php echo $theme_primary; ?>; color:white; text-decoration:none; padding:10px 20px; border-radius:0; font-weight:600; font-size:14px;">+ New Note</a>
                <?php elseif($current_tab === 'ai-chats'): ?>
                    <?php // Opens the shared VANCE-Ai modal on a fresh conversation rather than navigating away. ?>
                    <button type="button" data-vance-askai-open="new" class="btn-primary" style="display:inline-flex; align-items:center; background:<?php echo $theme_primary; ?>; color:white; border:none; padding:10px 20px; min-height:44px; border-radius:0; font-weight:600; font-size:14px; font-family:inherit; cursor:pointer;">+ New Chat</button>
                <?php endif; ?>
            </div>

            <?php switch($current_tab) :
                case 'home':
                    // Admin-broadcast messages: compute unread count + latest items
                    // for the Messages card below. We do NOT auto-mark-read here;
                    // marking only happens when the user opens the My Messages tab.
                    $vance_dashboard_msgs = function_exists( 'vance_admin_messages_for_user' )
                        ? vance_admin_messages_for_user( $current_user->ID, true ) // include read for "latest" display
                        : array();
                    $vance_unread_count = 0;
                    foreach ( $vance_dashboard_msgs as $m ) {
                        $r = (array) get_post_meta( $m->ID, '_sla_msg_read_by', true );
                        if ( ! in_array( (int) $current_user->ID, array_map( 'intval', $r ), true ) ) {
                            $vance_unread_count++;
                        }
                    }
                    $vance_recent_msgs = array_slice( $vance_dashboard_msgs, 0, 3 );
                    ?>
                    <style>
                        .dash-grid-v2 { display: grid; grid-template-columns: repeat(12, 1fr); gap: 24px; }
                        .d-card { background: white; border-radius: 0; padding: 24px; border: 1px solid #E2E8F0; display: flex; flex-direction: column; transition: transform 0.2s, box-shadow 0.2s; }
                        .d-card:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
                        .d-col-4 { grid-column: span 4; }
                        .d-col-6 { grid-column: span 6; }
                        .d-col-8 { grid-column: span 8; }
                        .d-col-12 { grid-column: span 12; }
                        
                        @media (max-width: 1024px) { .d-col-4, .d-col-8 { grid-column: span 6; } }
                        @media (max-width: 768px) { .d-col-4, .d-col-6, .d-col-8 { grid-column: span 12; } }

                        .d-card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
                        .d-card-title { font-family: 'Outfit', sans-serif; font-size: 18px; font-weight: 700; color: #0F172A; display: flex; align-items: center; gap: 10px; }
                        .d-icon-box { width: 32px; height: 32px; border-radius: 0; background: #F1F5F9; display: flex; align-items: center; justify-content: center; font-size: 16px; }
                        
                        .msg-empty-state { text-align: center; padding: 32px 0; color: #94A3B8; font-size: 14px; background: #F8FAFC; border-radius: 0; border: 1px dashed #E2E8F0; }
                    </style>

                    <?php // Cards follow their tab's toggle. The 12-column grid
                          // auto-flows, so a removed card closes up rather than
                          // leaving a hole. ?>
                    <div class="dash-grid-v2">
                        <!-- 1. READING LIST (Wide) -->
                        <?php if ($dash_on('reading-list')): ?>
                        <div class="d-card d-col-8">
                             <div class="d-card-header">
                                <div class="d-card-title"><span class="d-icon-box">📚</span> <?php echo esc_html($dash_label("reading-list")); ?></div>
                                <a href="?tab=reading-list" class="card-link">Library</a>
                             </div>
                             <?php if(empty($bookmarks)): ?>
                                <div class="msg-empty-state">No saved articles.</div>
                             <?php else: ?>
                                <div class="dash-list">
                                    <?php
                                    $b_query = new WP_Query(array('post__in' => array_reverse($bookmarks), 'post_type' => 'any', 'posts_per_page' => 5, 'orderby' => 'post__in'));
                                    while($b_query->have_posts()): $b_query->the_post();
                                    ?>
                                    <div class="list-item">
                                        <div style="flex:1; overflow:hidden;">
                                            <div class="item-title" style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></div>
                                            <div class="item-meta"><?php echo get_the_date('M j'); ?></div>
                                        </div>
                                    </div>
                                    <?php endwhile; wp_reset_postdata(); ?>
                                </div>
                             <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <!-- 2. MESSAGES (Tall/Side) — admin-broadcast messages live here -->
                        <?php if ($dash_on('messages')): ?>
                        <div class="d-card d-col-4">
                            <div class="d-card-header">
                                <div class="d-card-title"><span class="d-icon-box">💬</span> <?php echo esc_html($dash_label("messages")); ?></div>
                                <span style="font-size:12px; font-weight:700; padding:4px 10px; border-radius:0;
                                    <?php echo $vance_unread_count > 0
                                        ? 'background:#008080; color:white;'
                                        : 'background:#F1F5F9; color:#64748B;'; ?>">
                                    <?php echo (int) $vance_unread_count; ?> New
                                </span>
                            </div>
                            <?php if ( empty( $vance_recent_msgs ) ) : ?>
                                <div class="msg-empty-state">
                                    <strong>Inbox Zero!</strong><br>No messages from the team yet.
                                </div>
                            <?php else : ?>
                                <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 10px;">
                                    <?php foreach ( $vance_recent_msgs as $vm ) :
                                        $vm_reads = (array) get_post_meta( $vm->ID, '_sla_msg_read_by', true );
                                        $vm_unread = ! in_array( (int) $current_user->ID, array_map( 'intval', $vm_reads ), true );
                                        $vm_sev = get_post_meta( $vm->ID, '_sla_msg_severity', true ) ?: 'info';
                                        $vm_dot = $vm_sev === 'important' ? '#b07d00' : ( $vm_sev === 'announcement' ? '#0A1929' : '#008080' );
                                    ?>
                                        <li>
                                            <a href="?tab=messages" style="display: flex; gap: 12px; padding: 10px 12px; background: <?php echo $vm_unread ? '#F4FFFF' : '#F8FAFC'; ?>; border-left: 3px solid <?php echo esc_attr( $vm_dot ); ?>; text-decoration: none; color: inherit; transition: background 0.15s;" onmouseover="this.style.background='#def4f4'" onmouseout="this.style.background='<?php echo $vm_unread ? '#F4FFFF' : '#F8FAFC'; ?>'">
                                                <span style="flex-shrink: 0; width: 8px; height: 8px; border-radius: 50%; margin-top: 7px; background: <?php echo $vm_unread ? esc_attr( $vm_dot ) : 'transparent'; ?>;" aria-hidden="true"></span>
                                                <div style="flex: 1; min-width: 0;">
                                                    <div style="font-size: 13px; font-weight: <?php echo $vm_unread ? '700' : '600'; ?>; color: #0F172A; line-height: 1.3; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo esc_html( $vm->post_title ); ?></div>
                                                    <div style="font-size: 11px; color: #64748B; margin-top: 3px;"><?php echo esc_html( get_the_date( 'M j', $vm ) ); ?> · <?php echo esc_html( ucfirst( $vm_sev ) ); ?></div>
                                                </div>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                                <a href="?tab=messages" style="display: block; text-align: center; margin-top: 12px; font-size: 12px; font-weight: 700; color: #008080; text-decoration: none; padding: 8px; border-top: 1px solid #E2E8F0;">View all messages →</a>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <!-- 3. NOTES -->
                        <?php if ($dash_on('notes')): ?>
                        <div class="d-card d-col-4">
                            <div class="d-card-header">
                                <div class="d-card-title"><span class="d-icon-box">📝</span> <?php echo esc_html($dash_label("notes")); ?></div>
                                <a href="?tab=notes" class="card-link">All Notes</a>
                            </div>
                            <div class="dash-list">
                                <?php 
                                $my_notes = get_user_meta($current_user->ID, '_sla_user_notes', true) ?: array();
                                if(empty($my_notes)): ?>
                                    <div class="msg-empty-state">No notes found.</div>
                                <?php else: 
                                    $recent_notes = is_array($my_notes) ? array_slice($my_notes, -3) : array();
                                    foreach(array_reverse($recent_notes) as $note): ?>
                                    <div class="list-item">
                                        <div>
                                            <div class="item-title"><?php echo esc_html($note['title'] ?: 'Untitled'); ?></div>
                                            <div class="item-meta"><?php echo date('M j', strtotime($note['date'])); ?></div>
                                        </div>
                                        <a href="/my-notes/?id=<?php echo $note['id']; ?>" style="text-decoration:none; color:#64748B;">✏️</a>
                                    </div>
                                <?php endforeach; endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- 4. MY VANCE-AI -->
                        <?php if ($dash_on('ai-chats')): ?>
                        <div class="d-card d-col-4">
                            <div class="d-card-header">
                                <div class="d-card-title"><span class="d-icon-box">🤖</span> <?php echo esc_html($dash_label("ai-chats")); ?></div>
                                <a href="?tab=ai-chats" class="card-link">View All</a>
                            </div>
                            <?php
                            $home_ai_chats = get_user_meta($current_user->ID, '_sla_saved_chats', true);
                            if (!is_array($home_ai_chats)) $home_ai_chats = array();
                            if(empty($home_ai_chats)): ?>
                                <div class="msg-empty-state">No VANCE-Ai conversations yet.</div>
                            <?php else: ?>
                                <div class="dash-list">
                                    <?php foreach(array_slice(array_reverse($home_ai_chats), 0, 3) as $home_chat):
                                        $home_chat_title = !empty($home_chat['title']) ? wp_trim_words($home_chat['title'], 6, '...') : 'VANCE-Ai conversation';
                                        $home_chat_date = !empty($home_chat['updated']) ? $home_chat['updated'] : ( !empty($home_chat['date']) ? $home_chat['date'] : '' );
                                    ?>
                                    <div class="list-item">
                                        <div style="flex:1; overflow:hidden;">
                                            <div class="item-title" style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?php echo esc_html($home_chat_title); ?></div>
                                            <div class="item-meta"><?php echo $home_chat_date ? esc_html(date('M j', strtotime($home_chat_date))) : ''; ?></div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <!-- 5. HEALTH PROFILE PROMO -->
                        <?php if ($dash_on('health-profile')): ?>
                        <?php $has_health_profile = (bool) get_user_meta( $current_user->ID, '_sla_clinical_profile', true ); ?>
                        <div class="d-card d-col-4" style="background: linear-gradient(135deg, #008080, #0A1929); color: white; border: none;">
                            <div class="d-card-header">
                                <div class="d-card-title" style="color: white;"><span class="d-icon-box" style="background: rgba(255,255,255,0.15);">🩺</span> <?php echo esc_html($dash_label("health-profile")); ?></div>
                            </div>
                            <p style="font-size:13px; color:rgba(255,255,255,0.85); line-height:1.5; margin: 0 0 16px 0;">
                                <?php echo $has_health_profile
                                    ? 'Keep your Health Profile up to date so your care stays personalised.'
                                    : 'Complete your Health Profile to get personalised content and tools.'; ?>
                            </p>
                            <a href="?tab=health-profile" class="card-link" style="color:white; font-weight:700;"><?php echo $has_health_profile ? 'Update Health Profile →' : 'Complete Health Profile →'; ?></a>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php break;

                case 'profile': 
                    // Prepare data
                    $socials = array(
                        // 'website' removed 2026-08-11 along with its form field — it was
                        // still being fetched here, so a legacy saved value kept showing
                        // as an icon under the avatar with no way to clear it (reported:
                        // "deleted all the social and web links but it's still showing
                        // one web icon"). Not read at all now; the old _sla_website meta
                        // is simply orphaned, not deleted — harmless if it's ever restored.
                        'twitter' => get_user_meta($current_user->ID, '_sla_twitter', true), // X
                        'linkedin' => get_user_meta($current_user->ID, '_sla_linkedin', true),
                        'instagram' => get_user_meta($current_user->ID, '_sla_instagram', true),
                        'facebook' => get_user_meta($current_user->ID, '_sla_facebook', true),
                        'tiktok' => get_user_meta($current_user->ID, '_sla_tiktok', true),
                    );
                    // Inline SVG per platform — same icon set as header.php's site-wide
                    // social row (footer.php's is an unused placeholder), reused here
                    // keyed off the profile's own per-user values instead of the
                    // site-wide vance_social_* theme_mods.
                    $social_icons = array(
                        'website'   => '<svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm7.93 6h-3.5a15.6 15.6 0 0 0-1.4-4.3A8.03 8.03 0 0 1 19.93 8zM12 4.04c.9 1.13 1.99 3.13 2.43 3.96H9.57C10.01 7.17 11.1 5.17 12 4.04zM4.26 14A7.9 7.9 0 0 1 4 12c0-.69.1-1.36.26-2h3.94A18.6 18.6 0 0 0 8 12c0 .69.07 1.35.2 2H4.26zm.81 2h3.5a15.6 15.6 0 0 0 1.4 4.3A8.03 8.03 0 0 1 5.07 16zm3.5-8H5.07a8.03 8.03 0 0 1 4.9-4.3A15.6 15.6 0 0 0 8.57 8zM12 19.96c-.9-1.13-1.99-3.13-2.43-3.96h4.86c-.44.83-1.53 2.83-2.43 3.96zM14.8 14H9.2A16.6 16.6 0 0 1 9 12c0-.69.08-1.35.2-2h5.6c.12.65.2 1.31.2 2s-.08 1.35-.2 2zm.2 5.96A16.6 16.6 0 0 0 16.4 16h3.5a8.03 8.03 0 0 1-4.9 3.96zM16.8 14c.13-.65.2-1.31.2-2s-.07-1.35-.2-2h3.94c.16.64.26 1.31.26 2s-.1 1.36-.26 2z"/></svg>',
                        'twitter'   => '<svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M18.9 2H22l-7.7 8.8L23.3 22h-6.9l-5.4-6.9L4.8 22H1.6l8.2-9.4L1 2h7l4.9 6.4L18.9 2Zm-1.2 18h1.9L7.4 4H5.4l12.3 16Z"/></svg>',
                        'linkedin'  => '<svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M19 0h-14C2.24 0 0 2.24 0 5v14c0 2.76 2.24 5 5 5h14c2.76 0 5-2.24 5-5V5c0-2.76-2.24-5-5-5ZM8 19H5V9h3v10ZM6.5 7.7A1.7 1.7 0 1 1 6.5 4.3a1.7 1.7 0 0 1 0 3.4ZM20 19h-3v-5.4c0-1.3-.5-2.2-1.6-2.2-.9 0-1.4.6-1.6 1.2-.1.2-.1.5-.1.8V19h-3V9h3v1.3c.4-.6 1.1-1.5 2.8-1.5 2 0 3.5 1.3 3.5 4.2V19Z"/></svg>',
                        'instagram' => '<svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.2c3.2 0 3.6 0 4.9.1 1.2.1 2 .2 2.4.4.6.2 1 .5 1.5 1 .4.4.7.9 1 1.5.2.4.3 1.2.4 2.4.1 1.3.1 1.7.1 4.9s0 3.6-.1 4.9c-.1 1.2-.2 2-.4 2.4-.2.6-.5 1-1 1.5-.4.4-.9.7-1.5 1-.4.2-1.2.3-2.4.4-1.3.1-1.7.1-4.9.1s-3.6 0-4.9-.1c-1.2-.1-2-.2-2.4-.4-.6-.2-1-.5-1.5-1-.4-.4-.7-.9-1-1.5-.2-.4-.3-1.2-.4-2.4-.1-1.3-.1-1.7-.1-4.9s0-3.6.1-4.9c.1-1.2.2-2 .4-2.4.2-.6.5-1 1-1.5.4-.4.9-.7 1.5-1 .4-.2 1.2-.3 2.4-.4C8.4 2.2 8.8 2.2 12 2.2Zm0 1.8c-3.1 0-3.5 0-4.7.1-1 .1-1.6.2-1.9.3-.5.2-.8.4-1.2.7-.4.4-.6.7-.7 1.2-.1.3-.3.9-.3 1.9C3.1 8.5 3.1 8.9 3.1 12s0 3.5.1 4.7c.1 1 .2 1.6.3 1.9.2.5.4.8.7 1.2.4.4.7.6 1.2.7.3.1.9.3 1.9.3 1.2.1 1.6.1 4.7.1s3.5 0 4.7-.1c1-.1 1.6-.2 1.9-.3.5-.2.8-.4 1.2-.7.4-.4.6-.7.7-1.2.1-.3.3-.9.3-1.9.1-1.2.1-1.6.1-4.7s0-3.5-.1-4.7c-.1-1-.2-1.6-.3-1.9-.2-.5-.4-.8-.7-1.2-.4-.4-.7-.6-1.2-.7-.3-.1-.9-.3-1.9-.3-1.2-.1-1.6-.1-4.7-.1Zm0 3.5a4.5 4.5 0 1 1 0 9 4.5 4.5 0 0 1 0-9Zm0 1.8a2.7 2.7 0 1 0 0 5.4 2.7 2.7 0 0 0 0-5.4Zm4.7-2a1.05 1.05 0 1 1 0 2.1 1.05 1.05 0 0 1 0-2.1Z"/></svg>',
                        'facebook'  => '<svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M22 12a10 10 0 1 0-11.6 9.9v-7H7.9V12h2.5V9.8c0-2.5 1.5-3.9 3.8-3.9 1.1 0 2.2.2 2.2.2v2.5h-1.3c-1.2 0-1.6.8-1.6 1.6V12h2.8l-.4 2.9h-2.4v7A10 10 0 0 0 22 12Z"/></svg>',
                        'tiktok'    => '<svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M16.6 2h-3.2v13.7a2.9 2.9 0 1 1-2-2.75v-3.3a6.2 6.2 0 1 0 5.2 6.13V8.8a7.8 7.8 0 0 0 4.5 1.44V7.05a4.6 4.6 0 0 1-4.5-4.6V2Z"/></svg>',
                    );
                    $profile_docs = get_user_meta($current_user->ID, '_sla_profile_docs', true) ?: array();
                    $profile_links = get_user_meta($current_user->ID, '_sla_profile_links', true) ?: array();
                    // Pad links to 5
                    while(count($profile_links) < 5) $profile_links[] = '';
                    ?>
                    <div class="dash-card" style="max-width: 900px;">
                        <form id="profile-form-main">
                            <?php wp_nonce_field( 'vance_dashboard_nonce', 'profile_nonce' ); ?>
                            <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 40px;">
                                <!-- Left Col: Avatar & Media -->
                                <div>
                                    <div style="position: relative; width: 120px; height: 120px; margin-bottom: 20px;">
                                        <img src="<?php echo esc_url($profile_img); ?>" id="profile-preview" style="width: 100%; height: 100%; border-radius: 0; object-fit: cover; border: 4px solid white; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                                        <button type="button" onclick="triggerAvatarUpload()" style="position: absolute; bottom: 0; right: 0; background: white; border: 1px solid #E2E8F0; width: 32px; height: 32px; border-radius: 0; cursor: pointer; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">📸</button>
                                        <input type="file" id="avatar-input" style="display: none;" accept="image/*" onchange="uploadAvatar(this)">
                                    </div>

                                    <?php
                                    // One icon per platform that actually has a saved URL. The
                                    // wrapper itself always renders (just hidden when empty) so
                                    // vanceRefreshSocialIcon() can insert the first icon live,
                                    // after a per-field save, with no page reload.
                                    $any_social = array_filter( $socials );
                                    ?>
                                    <?php // Fixed 3-column grid (not flex-wrap) so the row always
                                          // breaks 3/3 — request 2026-08-11: "icons should be 3 per row". ?>
                                    <?php // margin-top:150px is a fixed gap from the avatar's bottom
                                          // edge, not just spacing — request 2026-08-11: "always be
                                          // 150px padded from the bottom of the profile pic". Margins
                                          // between block siblings collapse to the larger value, so this
                                          // wins outright over the avatar wrapper's own 20px margin-bottom
                                          // above regardless of that value. ?>
                                    <div id="vance-profile-social-icons" style="display:<?php echo $any_social ? 'grid' : 'none'; ?>; grid-template-columns: repeat(3, 56px); gap:10px; margin:150px 0 20px;">
                                        <?php foreach ( $socials as $key => $url ) : if ( ! $url ) { continue; } ?>
                                        <a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener" data-platform="<?php echo esc_attr( $key ); ?>" title="<?php echo esc_attr( ucfirst( $key ) ); ?>" class="vance-profile-social-icon">
                                            <?php echo $social_icons[ $key ]; ?>
                                        </a>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php // Hidden SVG templates, one per platform, so JS can build a
                                          // brand-new icon <a> for a platform that had no saved value
                                          // at page load (vanceRefreshSocialIcon() below). ?>
                                    <div style="display:none;" aria-hidden="true">
                                        <?php foreach ( $social_icons as $key => $svg ) : ?>
                                        <span id="vance-social-icon-tpl-<?php echo esc_attr( $key ); ?>"><?php echo $svg; ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php
                                    // Plain-text link list under the icon row — My Links only. Social
                                    // Profiles already have icons right above and don't need a
                                    // redundant text list (first pass put this under Social Profiles;
                                    // corrected 2026-08-11). Protocol stripped for display only, the
                                    // href keeps the real, full URL.
                                    $any_profile_links = array_filter( $profile_links );
                                    ?>
                                    <div id="vance-profile-my-links-list" style="display:<?php echo $any_profile_links ? 'flex' : 'none'; ?>; flex-direction:column; gap:6px; margin:0 0 20px;">
                                        <?php foreach ( $profile_links as $link ) : if ( ! $link ) { continue; } ?>
                                        <a href="<?php echo esc_url( $link ); ?>" target="_blank" rel="noopener" class="vance-profile-link-text"><?php echo esc_html( preg_replace( '#^https?://(www\.)?#i', '', rtrim( $link, '/' ) ) ); ?></a>
                                        <?php endforeach; ?>
                                    </div>

                                    <?php
                                    // Document upload moved to its own My Documents tab, where it
                                    // has room for a reader, the Ask VANCE-Ai flow and the
                                    // disclaimers that belong with health records. The signpost
                                    // stays so anyone who uploaded here before still finds them —
                                    // the files themselves did not move, both views read the same
                                    // _sla_profile_docs meta.
                                    $profile_doc_count = count( $profile_docs );
                                    ?>
                                    <?php // Signposts a tab that may be switched off, so it follows the toggle. ?>
                                    <?php if ($dash_on('documents')): ?>
                                    <div style="margin-top: 30px; border-top: 1px solid #E2E8F0; padding-top: 20px;">
                                        <label style="display:block; font-size:13px; font-weight:600; margin-bottom:8px;"><?php echo esc_html($dash_label("documents")); ?></label>
                                        <p style="font-size:12px; color:#64748B; line-height:1.5; margin:0 0 12px;">
                                            <?php if ( $profile_doc_count ) : ?>
                                                You have <?php echo (int) $profile_doc_count; ?> saved document<?php echo 1 === $profile_doc_count ? '' : 's'; ?>.
                                            <?php else : ?>
                                                Upload letters, test results and care plans, then ask VANCE-Ai about them.
                                            <?php endif; ?>
                                        </p>
                                        <a href="?tab=documents" class="rl-btn" style="width:100%; justify-content:center; box-sizing:border-box;"><?php echo esc_html(sprintf('Go to %s', $dash_label('documents'))); ?></a>
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Right Col: Info & Links -->
                                <div>
                                    <?php
                                    // Leading icons for the plain (non-save-row) fields below —
                                    // request 2026-08-11: "add relevant icons next to each input
                                    // box". Kept local to this block since they're one-offs, unlike
                                    // $social_icons which is reused for both the input icon AND the
                                    // avatar-row badge.
                                    $icon_person    = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"></circle><path d="M4 21c0-4.4 3.6-7 8-7s8 2.6 8 7"></path></svg>';
                                    $icon_envelope  = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="1"></rect><path d="M3 7l9 6 9-6"></path></svg>';
                                    $icon_briefcase = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="1"></rect><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><path d="M2 13h20"></path></svg>';
                                    $icon_building  = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="3" width="16" height="18" rx="1"></rect><path d="M9 21v-4h6v4"></path><path d="M8 7h2M14 7h2M8 11h2M14 11h2"></path></svg>';
                                    $icon_notes     = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="6" x2="20" y2="6"></line><line x1="4" y1="12" x2="20" y2="12"></line><line x1="4" y1="18" x2="14" y2="18"></line></svg>';
                                    $icon_link      = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 14a5 5 0 0 0 7 0l3-3a5 5 0 0 0-7-7l-1 1"></path><path d="M14 10a5 5 0 0 0-7 0l-3 3a5 5 0 0 0 7 7l1-1"></path></svg>';
                                    // Save-tick vs. empty-circle-slash — the two icons every
                                    // .vance-field-save-btn carries; CSS (above) shows one or the
                                    // other via [data-empty], and always the check while .is-saved.
                                    $icon_check_svg = '<svg class="vance-icon-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>';
                                    $icon_empty_svg = '<svg class="vance-icon-empty" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"></circle><line x1="5.5" y1="18.5" x2="18.5" y2="5.5"></line></svg>';
                                    // Delete button beside each save button — request 2026-08-11.
                                    $icon_trash_svg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path><path d="M10 11v6M14 11v6"></path><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"></path></svg>';
                                    ?>
                                    <h3 style="margin:0 0 20px 0; font-size:18px; color:#0F172A;">Personal Information</h3>
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px;">
                                        <div>
                                            <label style="display:block; margin-bottom:8px; font-size:13px; font-weight:600;">First Name</label>
                                            <div class="vance-input-icon-wrap">
                                                <span class="vance-input-icon" aria-hidden="true"><?php echo $icon_person; ?></span>
                                                <input type="text" name="first_name" value="<?php echo esc_attr($first_name); ?>">
                                            </div>
                                        </div>
                                        <div>
                                            <label style="display:block; margin-bottom:8px; font-size:13px; font-weight:600;">Last Name</label>
                                            <div class="vance-input-icon-wrap">
                                                <span class="vance-input-icon" aria-hidden="true"><?php echo $icon_person; ?></span>
                                                <input type="text" name="last_name" value="<?php echo esc_attr($current_user->last_name); ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <div style="margin-bottom: 24px;">
                                        <label style="display:block; margin-bottom:8px; font-size:13px; font-weight:600;">Email Address</label>
                                        <div class="vance-input-icon-wrap">
                                            <span class="vance-input-icon" aria-hidden="true"><?php echo $icon_envelope; ?></span>
                                            <input type="email" name="user_email" value="<?php echo esc_attr($current_user->user_email); ?>">
                                        </div>
                                    </div>

                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px;">
                                        <div>
                                            <label style="display:block; margin-bottom:8px; font-size:13px; font-weight:600;">Job Title</label>
                                            <div class="vance-input-icon-wrap">
                                                <span class="vance-input-icon" aria-hidden="true"><?php echo $icon_briefcase; ?></span>
                                                <input type="text" name="vance_job_title" value="<?php echo esc_attr($job_title); ?>">
                                            </div>
                                        </div>
                                        <div>
                                            <label style="display:block; margin-bottom:8px; font-size:13px; font-weight:600;">Organization</label>
                                            <div class="vance-input-icon-wrap">
                                                <span class="vance-input-icon" aria-hidden="true"><?php echo $icon_building; ?></span>
                                                <input type="text" name="vance_organization" value="<?php echo esc_attr($org); ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <div style="margin-bottom: 24px;">
                                        <label style="display:block; margin-bottom:8px; font-size:13px; font-weight:600;">Biography</label>
                                        <div class="vance-input-icon-wrap">
                                            <span class="vance-input-icon vance-input-icon--top" aria-hidden="true"><?php echo $icon_notes; ?></span>
                                            <textarea name="description" rows="4"><?php echo esc_textarea($current_user->description); ?></textarea>
                                        </div>
                                    </div>

                                    <!-- Social Links -->
                                    <h3 style="margin:30px 0 20px 0; font-size:16px; color:#0F172A; border-top:1px solid #E2E8F0; padding-top:20px;">Social Profiles</h3>
                                    <div style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 24px;">
                                        <?php
                                        $social_field_meta = array(
                                            // 'website' removed from this form 2026-08-11 — still in
                                            // $socials/$social_icons above, so a legacy saved value (if
                                            // any) keeps showing in the icon row under the avatar photo.
                                            'twitter'   => array( 'name' => 'vance_twitter',   'placeholder' => 'https://x.com/vancehealthhub' ),
                                            'linkedin'  => array( 'name' => 'vance_linkedin',  'placeholder' => 'https://linkedin.com/company/vancehealthhub' ),
                                            'instagram' => array( 'name' => 'vance_instagram', 'placeholder' => 'https://instagram.com/@vancehealthhub' ),
                                            'facebook'  => array( 'name' => 'vance_facebook',  'placeholder' => 'https://facebook.com/vancehealthhub' ),
                                            'tiktok'    => array( 'name' => 'vance_tiktok',    'placeholder' => 'https://tiktok.com/@vancehealthhub' ),
                                        );
                                        foreach ( $social_field_meta as $key => $meta ) :
                                        ?>
                                        <div class="vance-field-save-row" data-platform="<?php echo esc_attr( $key ); ?>">
                                            <div class="vance-input-icon-wrap">
                                                <span class="vance-input-icon" aria-hidden="true"><?php echo $social_icons[ $key ]; ?></span>
                                                <input type="url" name="<?php echo esc_attr( $meta['name'] ); ?>" placeholder="<?php echo esc_attr( $meta['placeholder'] ); ?>" value="<?php echo esc_attr( $socials[ $key ] ); ?>">
                                            </div>
                                            <button type="button" class="vance-field-save-btn" data-empty="<?php echo '' === $socials[ $key ] ? '1' : '0'; ?>" aria-label="Save <?php echo esc_attr( ucfirst( $key ) ); ?> link" onclick="vanceSaveProfileField(this)">
                                                <?php echo $icon_check_svg . $icon_empty_svg; ?>
                                            </button>
                                            <button type="button" class="vance-field-delete-btn" aria-label="Clear <?php echo esc_attr( ucfirst( $key ) ); ?> link" onclick="vanceDeleteProfileField(this)">
                                                <?php echo $icon_trash_svg; ?>
                                            </button>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>

                                    <!-- External Links -->
                                    <h3 style="margin:30px 0 20px 0; font-size:16px; color:#0F172A; border-top:1px solid #E2E8F0; padding-top:20px;">My Links (Up to 5)</h3>
                                    <div class="vance-profile-links-group" style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 30px;">
                                        <?php foreach($profile_links as $i => $link): ?>
                                        <div class="vance-field-save-row">
                                            <div class="vance-input-icon-wrap">
                                                <span class="vance-input-icon" aria-hidden="true"><?php echo $icon_link; ?></span>
                                                <input type="url" name="profile_links[]" placeholder="https://" value="<?php echo esc_attr($link); ?>">
                                            </div>
                                            <button type="button" class="vance-field-save-btn" data-empty="<?php echo '' === $link ? '1' : '0'; ?>" aria-label="Save link <?php echo (int) $i + 1; ?>" onclick="vanceSaveProfileField(this)">
                                                <?php echo $icon_check_svg . $icon_empty_svg; ?>
                                            </button>
                                            <button type="button" class="vance-field-delete-btn" aria-label="Clear link <?php echo (int) $i + 1; ?>" onclick="vanceDeleteProfileField(this)">
                                                <?php echo $icon_trash_svg; ?>
                                            </button>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>

                                    <div style="display: flex; justify-content: flex-end;">
                                        <button type="submit" class="btn-primary" style="background:<?php echo $theme_primary; ?>; color:white; border:none; padding:12px 32px; border-radius:0; font-weight:600; cursor:pointer;">Update Profile</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <?php // Document upload/delete JS moved with the feature to the documents tab. ?>

                    <?php
                    $ref_code  = get_user_meta( $current_user->ID, '_sla_referral_code', true );
                    $ref_count = (int) get_user_meta( $current_user->ID, '_sla_referral_count', true );
                    $ref_link  = $ref_code ? home_url( '/?ref=' . rawurlencode( $ref_code ) ) : '';
                    ?>
                    <?php if ( $ref_link ) : ?>
                    <div class="dash-card" style="max-width: 900px; margin-top:24px;">
                        <h3 style="margin:0 0 8px 0; font-size:16px; color:#0F172A;">Invite friends</h3>
                        <p style="color:#64748B; font-size:13px; margin:0 0 16px 0;">Share your personal link — anyone who joins the Hub through it counts toward your invites below.</p>
                        <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                            <input type="text" readonly id="ref-link-input" value="<?php echo esc_attr( $ref_link ); ?>" style="flex:1; min-width:240px; padding:10px; border:1px solid #E2E8F0; border-radius:0; font-size:13px; background:#F8FAFC; color:#0F172A;">
                            <button type="button" class="ref-copy" data-url="<?php echo esc_attr( $ref_link ); ?>" style="background:<?php echo $theme_primary; ?>; color:white; border:none; padding:10px 20px; border-radius:0; font-weight:600; cursor:pointer;">Copy link</button>
                        </div>
                        <p style="margin:16px 0 0 0; font-size:13px; color:#0F172A;"><strong><?php echo esc_html( $ref_count ); ?></strong> <?php echo 1 === $ref_count ? 'person has' : 'people have'; ?> joined using your link.</p>
                    </div>
                    <script>
                    (function () {
                        var btn = document.querySelector('.ref-copy');
                        if (!btn) { return; }
                        btn.addEventListener('click', function () {
                            var url = btn.getAttribute('data-url') || '';
                            var done = function () {
                                var was = btn.textContent;
                                btn.textContent = 'Copied ✓';
                                setTimeout(function () { btn.textContent = was; }, 1600);
                            };
                            if (navigator.clipboard && navigator.clipboard.writeText) {
                                navigator.clipboard.writeText(url).then(done, function () { window.prompt('Copy this link:', url); });
                            } else {
                                window.prompt('Copy this link:', url);
                            }
                        });
                    })();
                    </script>
                    <?php endif; ?>
                <?php break;

                case 'health-profile':
                    $quiz_results = get_user_meta($current_user->ID, '_sla_healthcare_quiz_results', true) ?: array();
                    $clinical_profile = get_user_meta($current_user->ID, '_sla_clinical_profile', true) ?: array();
                    // Saved malnutrition screenings (written by vance_save_tool_result).
                    $malnutrition_history = function_exists('vance_get_tool_history')
                        ? vance_get_tool_history($current_user->ID, 'malnutrition-calculator', 10)
                        : array();
                    // Saved meal plans from the IBD Recipes planner (same save path).
                    $meal_plan_history = function_exists('vance_get_tool_history')
                        ? vance_get_tool_history($current_user->ID, 'ibd-recipes', 10)
                        : array();

                    // Every clinical-profile key, empty by default, so a field added
                    // to vance_clinical_profile_fields() reads safely here without
                    // a local defaults list going stale (functions.php).
                    $profile = array_merge( vance_clinical_profile_defaults(), is_array($clinical_profile) ? $clinical_profile : array() );

                    // Quiz field order and labels come from the quiz itself, so a
                    // row's "Edit" opens the step that actually asks that question.
                    // The old local list still named `current_tools` and
                    // `learning_pref` — questions the quiz stopped asking — which
                    // put every step index after "duration" off by one or two.
                    $quiz_fields = vance_quiz_field_labels();
                    $quiz_steps  = array_keys( $quiz_fields );
                    ?>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 32px;">
                        <!-- Quiz Results Section -->
                        <div class="dash-card">
                            <div class="card-header">
                                <h3 class="card-title">My Health Profile Responses</h3>
                                <?php if($quiz_results): ?>
                                    <button type="button" onclick="openQuizModal(1)" class="vance-btn-inverted vance-btn--sm">Edit Answers</button>
                                <?php endif; ?>
                            </div>
                            <?php if(empty($quiz_results)): ?>
                                <div style="text-align:center; padding:40px;">
                                    <p style="color:#64748B; margin-bottom:20px;">You haven't completed your health profile responses yet.</p>
                                    <button onclick="openQuizModal()" class="btn-primary" style="display:inline-block; background:#008080; color:white; border:none; padding:10px 24px; border-radius:0; font-weight:600; cursor:pointer;">Start Discovery Quiz</button>
                                </div>
                            <?php else: ?>
                                <div class="dash-list">
                                    <?php
                                    // Walk the quiz's own field order, not the saved
                                    // answers' order, so rows always read in the order
                                    // the questions were asked and an unanswered
                                    // question is simply skipped.
                                    foreach ( $quiz_fields as $key => $label ) :
                                        if ( empty( $quiz_results[ $key ] ) ) { continue; }
                                        $step = array_search( $key, $quiz_steps, true ) + 1;
                                        ?>
                                        <div class="list-item" style="cursor:pointer;" onclick="openQuizModal(<?php echo (int) $step; ?>, true)">
                                            <span style="font-size:13px; font-weight:600; color:#64748B;"><?php echo esc_html( $label ); ?></span>
                                            <div style="display:flex; align-items:center; gap:8px;">
                                                <span style="font-size:14px; color:#0F172A; font-weight:700;"><?php echo esc_html( ucfirst( (string) $quiz_results[ $key ] ) ); ?></span>
                                                <span style="font-size:12px; color:#008080; opacity:0; transition:opacity 0.2s;" class="edit-hint">Edit &rarr;</span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div style="margin-top:24px; text-align:center;">
                                    <button onclick="openQuizModal()" style="font-size:12px; color:#008080; font-weight:600; background:none; border:none; cursor:pointer;">Retake Entire Quiz &rarr;</button>
                                </div>
                                <style>
                                    .list-item:hover .edit-hint { opacity: 1 !important; }
                                    .list-item:hover { background: #f8fafc; }
                                </style>
                            <?php endif; ?>
                        </div>

                        <!-- Combined Profile Details Section -->
                        <div class="dash-card">
                            <div class="card-header">
                                <h3 class="card-title">Health Details & Lifestyle</h3>
                                <button type="button" onclick="openClinicalInfoModal()" class="vance-btn-inverted vance-btn--sm">Edit Details</button>
                            </div>
                            
                            <?php
                            // Rows are data-driven so the panel and the editor modal
                            // list the same fields in the same order. `wide` renders
                            // the value on its own line for free-text answers.
                            $bmi = '';
                            if ( is_numeric($profile['weight']) && is_numeric($profile['height']) && (float) $profile['height'] > 0 ) {
                                $m   = (float) $profile['height'] / 100;
                                $bmi = number_format( (float) $profile['weight'] / ( $m * $m ), 1 );
                            }
                            $wl = '';
                            if ( is_numeric($profile['weight']) && is_numeric($profile['usual_weight']) && (float) $profile['usual_weight'] > 0 ) {
                                $diff = (float) $profile['usual_weight'] - (float) $profile['weight'];
                                if ( abs($diff) >= 0.1 ) {
                                    $wl = ( $diff > 0 ? '−' : '+' ) . number_format( abs($diff), 1 ) . 'kg vs usual';
                                }
                            }
                            $appt = '';
                            if ( $profile['next_appointment'] ) {
                                $ts   = strtotime( $profile['next_appointment'] );
                                $appt = $ts ? date_i18n( 'j M Y', $ts ) : '';
                            }
                            $rows = array(
                                array( 'Weight / Height',   ( $profile['weight'] ? esc_html($profile['weight']) . 'kg' : '—' ) . ' / ' . ( $profile['height'] ? esc_html($profile['height']) . 'cm' : '—' ) . ( $bmi ? ' <span style="color:#008080;">· BMI ' . esc_html($bmi) . '</span>' : '' ), false, true ),
                                array( 'Usual weight',      ( $profile['usual_weight'] ? esc_html($profile['usual_weight']) . 'kg' : '—' ) . ( $wl ? ' <span style="color:#64748B; font-weight:600;">· ' . esc_html($wl) . '</span>' : '' ), false, true ),
                                array( 'Medication',        $profile['medication'],        true,  false ),
                                array( 'Supplements',       $profile['supplements'],       true,  false ),
                                array( 'Allergies',         $profile['allergies'],         true,  false ),
                                array( 'Dietary pattern',   $profile['dietary_pattern'],   false, false ),
                                array( 'Trigger foods',     $profile['trigger_foods'],     true,  false ),
                                array( 'Recent changes',    $profile['lifestyle_changes'], true,  false ),
                                array( 'Flare-up history',  ( $profile['flare_up_freq'] ?: '—' ) . ( $profile['last_flare_up'] ? ' (last: ' . $profile['last_flare_up'] . ')' : '' ), false, false ),
                                array( 'Blood pressure',    $profile['blood_pressure'],    false, false ),
                                array( 'Next appointment',  $appt,                         false, false ),
                                array( 'Questions to ask',  $profile['appointment_questions'], true, false ),
                            );
                            ?>
                            <div class="dash-list">
                                <?php foreach ( $rows as $row ) :
                                    list( $label, $value, $wide, $raw ) = $row;
                                    $display = $raw ? $value : esc_html( (string) $value );
                                    $empty   = trim( wp_strip_all_tags( (string) $value ) ) === '' || trim( (string) $value ) === '—';
                                    ?>
                                    <div class="list-item"<?php echo $wide ? ' style="flex-direction:column; align-items:flex-start; gap:4px;"' : ''; ?>>
                                        <span style="font-size:13px; font-weight:600; color:#64748B;"><?php echo esc_html( $label ); ?></span>
                                        <?php if ( $wide ) : ?>
                                            <p style="font-size:14px; color:<?php echo $empty ? '#94A3B8' : '#334155'; ?>; margin:0; line-height:1.5; white-space:pre-line;"><?php echo $empty ? 'Not recorded' : $display; ?></p>
                                        <?php else : ?>
                                            <span style="font-size:14px; color:<?php echo $empty ? '#94A3B8' : '#0F172A'; ?>; font-weight:700; text-align:right;"><?php echo $empty ? 'Not recorded' : $display; ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div style="margin-top:30px; border-top:1px solid #E2E8F0; padding-top:20px;">
                                <label for="dash-additional-details" style="display:block; font-size:13px; font-weight:700; color:#0A1929; margin-bottom:12px;">Additional notes &amp; observations</label>
                                <form id="dashboard-additional-details-form">
                                    <?php wp_nonce_field( 'vance_dashboard_nonce', 'nonce' ); ?>
                                    <input type="hidden" name="action" value="vance_save_clinical_profile">
                                    <?php
                                    // The hidden copies of every other field that used
                                    // to live here are gone: the save handler now merges
                                    // into the stored profile instead of replacing it,
                                    // so this form only needs to send what it edits.
                                    // (They were also a stale-data hazard — a value
                                    // changed in the modal after page load would have
                                    // been written back from this page's old copy.)
                                    ?>
                                    <textarea id="dash-additional-details" name="additional_details" rows="5" placeholder="Anything else you would like to keep a record of — symptoms, observations, how you have been feeling…" style="width:100%; padding:14px; border:1px solid #E2E8F0; border-radius:0; font-size:14px; background:#F8FAFC; margin-bottom:12px; resize:vertical;"><?php echo esc_textarea($profile['additional_details']); ?></textarea>
                                    <button type="submit" style="width:100%; padding:10px; background:#F1F5F9; border:1px solid #E2E8F0; border-radius:0; font-weight:700; color:#475569; cursor:pointer; transition:all 0.2s;">Save notes</button>
                                    <p id="dashboard-additional-details-msg" role="status" aria-live="polite" style="display:none; margin:10px 0 0; font-size:13px; line-height:1.5;"></p>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Malnutrition screening history -->
                    <div class="dash-card vance-mc-card" style="margin-top:32px;">
                        <div class="card-header">
                            <h3 class="card-title">Malnutrition Screening Results</h3>
                            <a href="/malnutrition-calculator/" class="vance-btn-glass vance-btn--sm" data-vance-tool-open="malnutrition-calculator"><?php echo $malnutrition_history ? 'Screen again' : 'Start screening'; ?></a>
                        </div>
                        <?php if (empty($malnutrition_history)): ?>
                            <div style="text-align:center; padding:40px;">
                                <p style="color:#64748B; margin-bottom:20px;">You haven't saved a malnutrition screening yet. Complete the calculator and choose &ldquo;Save Results&rdquo; to track your score here over time.</p>
                                <a href="/malnutrition-calculator/" class="vance-btn-inverted" data-vance-tool-open="malnutrition-calculator">Open the Calculator</a>
                            </div>
                        <?php else: ?>
                            <?php
                            // View-models for the detail modal, built alongside the rows so the
                            // row markup and the modal can never drift out of sync.
                            $mc_view_data = array();
                            // Per-category maximums come from the calculator's own scoring.
                            $mc_breakdown_map = array(
                                'bmi'          => array( 'BMI', 2 ),
                                'weightLoss'   => array( 'Weight loss', 2 ),
                                'acuteDisease' => array( 'Acute disease', 2 ),
                                'ibdSymptoms'  => array( 'IBD symptoms', 4 ),
                            );
                            ?>
                            <div class="dash-list">
                                <?php foreach ($malnutrition_history as $idx => $entry):
                                    $p     = $entry['payload'];
                                    $ts    = !empty($entry['ts']) ? (int) $entry['ts'] : 0;
                                    $when  = $ts ? date_i18n('j M Y', $ts) : '';
                                    // Structured payloads carry a score; snapshot/placeholder saves don't.
                                    $has_score = isset($p['score']) && is_numeric($p['score']);
                                    $level     = isset($p['riskLevel']) ? strtolower((string) $p['riskLevel']) : '';
                                    $level_col = $level === 'low' ? '#16a34a' : ($level === 'medium' ? '#d97706' : '#008080');
                                    $risk_text = $has_score ? ($p['riskLabel'] ?? ucfirst($level) . ' risk') : '';

                                    $mc_rows = array();
                                    if (isset($p['breakdown']) && is_array($p['breakdown'])) {
                                        foreach ($mc_breakdown_map as $bk => $bm) {
                                            if (isset($p['breakdown'][$bk]) && is_numeric($p['breakdown'][$bk])) {
                                                $mc_rows[] = array('label' => $bm[0], 'value' => (float) $p['breakdown'][$bk], 'max' => $bm[1]);
                                            }
                                        }
                                    }
                                    // Older saves are a DOM snapshot with only a text blob.
                                    $mc_note = '';
                                    if (!$has_score) {
                                        $raw     = isset($p['text']) ? (string) $p['text'] : (isset($p['note']) ? (string) $p['note'] : '');
                                        $mc_note = $raw !== '' ? wp_trim_words($raw, 150) : '';
                                    }
                                    $mc_view_data[] = array(
                                        'when'      => $ts ? date_i18n('j F Y \a\t H:i', $ts) : '',
                                        'hasScore'  => (bool) $has_score,
                                        'score'     => $has_score ? 0 + $p['score'] : null,
                                        'maxScore'  => isset($p['maxScore']) && is_numeric($p['maxScore']) ? (int) $p['maxScore'] : 8,
                                        'riskLabel' => $risk_text,
                                        'riskColor' => $level_col,
                                        'bmi'       => isset($p['bmi']) ? (string) $p['bmi'] : '',
                                        'bmiCat'    => isset($p['bmiCat']) ? (string) $p['bmiCat'] : '',
                                        'ibdType'   => isset($p['ibdType']) ? (string) $p['ibdType'] : '',
                                        'breakdown' => $mc_rows,
                                        'note'      => $mc_note,
                                    );
                                    ?>
                                    <div class="list-item" style="flex-direction:column; align-items:flex-start; gap:8px;">
                                        <div style="display:flex; align-items:center; justify-content:space-between; width:100%; gap:12px; flex-wrap:wrap;">
                                            <span style="font-size:13px; font-weight:600; color:#64748B;"><?php echo esc_html($when); ?></span>
                                            <span style="display:inline-flex; align-items:center; gap:10px; flex-wrap:wrap;">
                                                <?php if ($has_score): ?>
                                                    <span class="vance-mc-pill" style="color:<?php echo esc_attr($level_col); ?>; background:<?php echo esc_attr($level_col); ?>1A;"><?php echo esc_html($risk_text); ?></span>
                                                    <span style="font-size:14px; color:#0F172A; font-weight:700;">Score <?php echo esc_html($p['score']); ?><?php echo isset($p['maxScore']) ? '/' . esc_html($p['maxScore']) : ''; ?></span>
                                                <?php else: ?>
                                                    <span style="font-size:13px; color:#64748B;">Saved result</span>
                                                <?php endif; ?>
                                                <button type="button" class="vance-mc-view"
                                                        onclick="openMalnutritionResult(<?php echo (int) $idx; ?>, this)"
                                                        aria-label="View full screening result from <?php echo esc_attr($when); ?>">
                                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                                                    </svg>
                                                    View
                                                </button>
                                            </span>
                                        </div>
                                        <?php if ($has_score): ?>
                                            <div style="display:flex; gap:20px; flex-wrap:wrap; font-size:13px; color:#475569;">
                                                <?php if (!empty($p['bmi'])): ?>
                                                    <span>BMI <strong style="color:#0F172A;"><?php echo esc_html($p['bmi']); ?></strong><?php echo !empty($p['bmiCat']) ? ' (' . esc_html($p['bmiCat']) . ')' : ''; ?></span>
                                                <?php endif; ?>
                                                <?php if (!empty($p['ibdType'])): ?>
                                                    <span>IBD type <strong style="color:#0F172A;"><?php echo esc_html($p['ibdType']); ?></strong></span>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <p style="margin:20px 0 0; font-size:12px; color:#94A3B8; line-height:1.5;">Screening results are an estimate based on the answers you gave. They are not a diagnosis &mdash; discuss any concerns with your healthcare team.</p>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($malnutrition_history)): ?>
                    <style>
                        /* Glassmorphism screening card (overrides .dash-card here; this
                           scoped block loads after main.css so it wins the cascade). */
                        .vance-mc-card {
                            background: var(--glass-fill) !important;
                            -webkit-backdrop-filter: var(--glass-blur);
                            backdrop-filter: var(--glass-blur);
                            border: var(--glass-border-tint) !important;
                            box-shadow: var(--glass-shadow) !important;
                            border-radius: var(--glass-radius) !important;
                        }
                        .vance-mc-card .list-item {
                            border-bottom: 1px solid rgba(0,128,128,0.10);
                        }
                        .vance-mc-view {
                            display:inline-flex; align-items:center; gap:6px;
                            min-height:38px; padding:8px 16px;
                            font-size:13px; font-weight:700; font-family:inherit;
                            color:#008080;
                            background:rgba(255,255,255,0.55);
                            -webkit-backdrop-filter:var(--glass-blur); backdrop-filter:var(--glass-blur);
                            border:1px solid rgba(0,128,128,0.35); border-radius:0 !important;
                            cursor:pointer; transition:background-color .2s, color .2s, transform .15s;
                        }
                        .vance-mc-view:hover { background:#008080; color:white; transform:translateY(-1px); }
                        .vance-mc-view:focus-visible,
                        .vance-mc-close:focus-visible { outline:3px solid var(--primary-pale); outline-offset:2px; }
                        .vance-mc-pill {
                            font-size:12px; font-weight:700; padding:5px 14px;
                            border-radius:0; border:1px solid currentColor;
                        }
                        .vance-mc-close {
                            position:absolute; top:16px; right:16px;
                            width:40px; height:40px; display:flex; align-items:center; justify-content:center;
                            font-size:24px; line-height:1; color:#0A1929;
                            background:rgba(255,255,255,0.5); border:1px solid rgba(255,255,255,0.6);
                            border-radius:0 !important; cursor:pointer;
                            transition:background-color .2s, transform .15s;
                        }
                        .vance-mc-close:hover { background:rgba(255,255,255,0.85); }
                        .vance-mc-bar-track { height:8px; background:rgba(10,25,41,0.08); width:100%; border-radius:0; overflow:hidden; }
                        .vance-mc-bar-fill  { height:8px; background:linear-gradient(90deg,#00a3a3,#008080); border-radius:0; transition:width .5s ease; }
                        @keyframes vanceMcPop { from { opacity:0; transform:translateY(24px) scale(.98); } to { opacity:1; transform:none; } }
                        #vance-mc-modal-panel { animation:vanceMcPop .38s cubic-bezier(.2,.8,.2,1); }
                        @media (prefers-reduced-motion: reduce) {
                            .vance-mc-bar-fill, .vance-mc-view, .vance-mc-close { transition:none; }
                            #vance-mc-modal-panel { animation:none !important; }
                        }
                    </style>

                    <div id="vance-mc-result-modal" class="vance-modal vance-glass-scrim" role="dialog" aria-modal="true" aria-labelledby="vance-mc-modal-title"
                         style="display:none; position:fixed; inset:0; z-index:10000; overflow-y:auto; padding:20px; align-items:center; justify-content:center;">
                        <div class="vance-glass-panel" id="vance-mc-modal-panel" tabindex="-1"
                             style="max-width:560px; width:100%; padding:40px; position:relative;">
                            <button type="button" class="vance-mc-close" onclick="closeMalnutritionResult()" aria-label="Close screening result">&times;</button>
                            <h3 id="vance-mc-modal-title" class="card-title" style="margin:0 0 4px; font-family:'Outfit'; font-size:24px;">Screening Result</h3>
                            <p id="vance-mc-modal-date" style="margin:0 0 24px; font-size:13px; color:#64748B;"></p>
                            <div id="vance-mc-modal-body"></div>
                            <p style="margin:24px 0 0; padding-top:16px; border-top:1px solid #E2E8F0; font-size:12px; color:#94A3B8; line-height:1.5;">This is a screening estimate, not a diagnosis. Discuss any concerns with your healthcare team.</p>
                        </div>
                    </div>

                    <script>
                    var VANCE_MC_RESULTS = <?php echo json_encode($mc_view_data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
                    (function () {
                        var modal = document.getElementById('vance-mc-result-modal');
                        var panel = document.getElementById('vance-mc-modal-panel');
                        var lastTrigger = null;

                        function esc(v) {
                            return String(v == null ? '' : v).replace(/[&<>"']/g, function (c) {
                                return { '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;' }[c];
                            });
                        }

                        function statRow(label, value) {
                            return '<div style="display:flex; justify-content:space-between; gap:16px; padding:10px 0; border-bottom:1px solid #F1F5F9;">' +
                                   '<span style="font-size:13px; font-weight:600; color:#64748B;">' + esc(label) + '</span>' +
                                   '<span style="font-size:14px; font-weight:700; color:#0F172A; text-align:right;">' + esc(value) + '</span>' +
                                   '</div>';
                        }

                        function buildBody(d) {
                            // Snapshot-only saves have no score to show.
                            if (!d.hasScore) {
                                return '<p style="font-size:14px; color:#475569; line-height:1.6; margin:0;">' +
                                       (d.note ? esc(d.note)
                                               : 'This result was saved before the calculator recorded structured scores, so only the date is available. Run the screening again to capture a full score.') +
                                       '</p>';
                            }

                            var pct = d.maxScore ? Math.round((d.score / d.maxScore) * 100) : 0;
                            var html = '';

                            // Headline score + risk band (frosted, rounded).
                            html += '<div style="text-align:center; padding:28px 24px; background:' + esc(d.riskColor) + '14; border:1px solid ' + esc(d.riskColor) + '33; border-radius:0; margin-bottom:24px; -webkit-backdrop-filter:blur(6px); backdrop-filter:blur(6px);">' +
                                    '<div style="font-family:\'Outfit\',sans-serif; font-size:52px; font-weight:800; line-height:1; color:#0F172A;">' + esc(d.score) +
                                    '<span style="font-size:20px; color:#64748B; font-weight:700;">/' + esc(d.maxScore) + '</span></div>' +
                                    '<div style="margin-top:12px; display:inline-block; font-size:13px; font-weight:700; padding:6px 18px; border-radius:0; color:' + esc(d.riskColor) + '; background:' + esc(d.riskColor) + '1A; border:1px solid ' + esc(d.riskColor) + '55;">' + esc(d.riskLabel) + '</div>' +
                                    '</div>';

                            // Key values.
                            html += '<div style="margin-bottom:24px;">';
                            if (d.bmi)     html += statRow('BMI', d.bmi + (d.bmiCat ? ' (' + d.bmiCat + ')' : ''));
                            if (d.ibdType) html += statRow('IBD type', d.ibdType);
                            html += statRow('Total score', d.score + ' out of ' + d.maxScore + ' (' + pct + '%)');
                            html += '</div>';

                            // Per-category breakdown, mirroring the calculator's own bars.
                            if (d.breakdown && d.breakdown.length) {
                                html += '<h4 style="font-family:\'Outfit\',sans-serif; font-size:14px; font-weight:700; color:#0A1929; margin:0 0 14px; text-transform:uppercase; letter-spacing:0.5px;">Score breakdown</h4>';
                                d.breakdown.forEach(function (b) {
                                    var w = b.max ? Math.round((b.value / b.max) * 100) : 0;
                                    html += '<div style="margin-bottom:14px;">' +
                                            '<div style="display:flex; justify-content:space-between; margin-bottom:6px;">' +
                                            '<span style="font-size:13px; color:#475569;">' + esc(b.label) + '</span>' +
                                            '<span style="font-size:13px; font-weight:700; color:#0F172A;">' + esc(b.value) + '/' + esc(b.max) + '</span>' +
                                            '</div>' +
                                            '<div class="vance-mc-bar-track"><div class="vance-mc-bar-fill" style="width:' + w + '%"></div></div>' +
                                            '</div>';
                                });
                            }
                            return html;
                        }

                        window.openMalnutritionResult = function (i, trigger) {
                            var d = VANCE_MC_RESULTS[i];
                            if (!d) return;
                            lastTrigger = trigger || null;
                            document.getElementById('vance-mc-modal-date').textContent = d.when || '';
                            document.getElementById('vance-mc-modal-body').innerHTML = buildBody(d);
                            modal.style.display = 'flex';
                            document.body.style.overflow = 'hidden';
                            panel.focus();
                        };

                        window.closeMalnutritionResult = function () {
                            modal.style.display = 'none';
                            document.body.style.overflow = '';
                            // Return focus to the View button that opened this.
                            if (lastTrigger && document.contains(lastTrigger)) lastTrigger.focus();
                            lastTrigger = null;
                        };

                        // Click the backdrop (not the panel) to dismiss.
                        modal.addEventListener('click', function (e) {
                            if (e.target === modal) window.closeMalnutritionResult();
                        });

                        document.addEventListener('keydown', function (e) {
                            if (modal.style.display !== 'flex') return;
                            if (e.key === 'Escape') { window.closeMalnutritionResult(); return; }
                            // Keep Tab inside the dialog while it is open.
                            if (e.key === 'Tab') {
                                var f = panel.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
                                if (!f.length) { e.preventDefault(); panel.focus(); return; }
                                var first = f[0], last = f[f.length - 1];
                                if (e.shiftKey && (document.activeElement === first || document.activeElement === panel)) {
                                    e.preventDefault(); last.focus();
                                } else if (!e.shiftKey && document.activeElement === last) {
                                    e.preventDefault(); first.focus();
                                }
                            }
                        });
                    })();
                    </script>
                    <?php endif; ?>

                    <!-- Saved meal plans (IBD Recipes planner) -->
                    <div class="dash-card vance-mp-cardwrap" style="margin-top:32px;">
                        <div class="card-header">
                            <h3 class="card-title">Saved Meal Plans</h3>
                            <a href="/ibd-recipies/" class="vance-btn-glass vance-btn--sm" data-vance-tool-open="ibd-recipes"><?php echo $meal_plan_history ? 'Build another' : 'Build a plan'; ?></a>
                        </div>
                        <?php if (empty($meal_plan_history)): ?>
                            <div style="text-align:center; padding:40px;">
                                <p style="color:#64748B; margin-bottom:20px;">You haven't saved a meal plan yet. Build a week in the planner and choose &ldquo;Save this meal plan&rdquo; to keep it here.</p>
                                <a href="/ibd-recipies/" class="vance-btn-inverted" data-vance-tool-open="ibd-recipes">Open the Planner</a>
                            </div>
                        <?php else: ?>
                            <style>
                                .vance-mp-list { display:flex; flex-direction:column; gap:16px; }
                                .vance-mp-card { padding:0; overflow:hidden; }
                                .vance-mp-head { display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; padding:18px 20px; }
                                .vance-mp-toggle { display:inline-flex; align-items:center; gap:10px; background:none; border:none; cursor:pointer; font-family:inherit; padding:0; text-align:left; }
                                .vance-mp-toggle:focus-visible { outline:3px solid var(--primary-pale); outline-offset:3px; }
                                .vance-mp-title { font-family:'Outfit',sans-serif; font-size:16px; font-weight:700; color:#0F172A; }
                                .vance-mp-meta { font-size:12px; color:#64748B; }
                                .vance-mp-panel-inner { padding:0 20px 20px; }
                                .vance-mp-img { width:100%; max-height:220px; object-fit:cover; border-radius:0 !important; margin-bottom:16px; }
                                .vance-mp-days { display:flex; flex-wrap:wrap; gap:8px; margin-bottom:18px; }
                                .vance-mp-daychip { font-size:12px; color:#475569; background:rgba(0,128,128,0.08); border:1px solid rgba(0,128,128,0.16); padding:6px 12px; border-radius:0; }
                                .vance-mp-daychip strong { color:#0F172A; }
                                .vance-mp-actions { display:flex; flex-wrap:wrap; gap:10px; align-items:center; }
                                .vance-mp-textbtn { background:none; border:none; cursor:pointer; font-family:inherit; font-size:13px; font-weight:600; color:#0EA5E9; padding:8px 6px; }
                                .vance-mp-textbtn.vance-mp-danger { color:#EF4444; }
                                .vance-mp-textbtn:focus-visible { outline:2px solid var(--primary-color); outline-offset:2px; }

                                /* --- Meal plan viewer --- */
                                .vance-mv-day { border:1px solid #E2E8F0; background:#fff; }
                                .vance-mv-dayhead { display:flex; align-items:baseline; justify-content:space-between; gap:8px; padding:12px 16px; background:var(--primary-color, #008080); color:#fff; }
                                .vance-mv-dayhead strong { font-family:'Outfit',sans-serif; font-size:15px; font-weight:700; }
                                .vance-mv-dayhead span { font-size:12px; color:rgba(255,255,255,0.92); }
                                .vance-mv-meal { display:flex; gap:12px; padding:12px 16px; border-top:1px solid #2f4f6f; align-items:flex-start; }
                                .vance-mv-meal:first-of-type { border-top:none; }
                                .vance-mv-thumb { width:72px; height:72px; flex:0 0 72px; object-fit:cover; border:1px solid #E2E8F0; background:#F1F5F9; }
                                .vance-mv-thumb--empty { display:flex; align-items:center; justify-content:center; color:#94A3B8; }
                                .vance-mv-body { flex:1; min-width:0; }
                                .vance-mv-slot { font-size:11px; font-weight:700; letter-spacing:0.6px; text-transform:uppercase; color:#475569; }
                                .vance-mv-name { font-size:14px; font-weight:600; color:#0F172A; line-height:1.35; margin:2px 0 4px; }
                                .vance-mv-facts { font-size:12px; color:#475569; display:flex; flex-wrap:wrap; gap:10px; margin-bottom:8px; }
                                /* 44px min height keeps this a comfortable touch target. */
                                .vance-mv-open { display:inline-flex; align-items:center; gap:6px; min-height:44px; padding:0 14px; font-size:12px; font-weight:700; letter-spacing:0.3px; text-transform:uppercase; color:#fff; background:var(--primary-color, #008080); border:1px solid var(--primary-color, #008080); text-decoration:none; cursor:pointer; transition:background 200ms ease, color 200ms ease; }
                                .vance-mv-open:hover { background:#00696B; color:#fff; }
                                .vance-mv-open:focus-visible { outline:3px solid var(--primary-pale, #AEDBDB); outline-offset:2px; }
                                .vance-mv-open svg { width:13px; height:13px; }
                                /* The theme's .screen-reader-text is scoped to .vance-askai, so
                                   the meal viewer needs its own visually-hidden helper. */
                                .vance-mv-sr { position:absolute; width:1px; height:1px; padding:0; margin:-1px; overflow:hidden; clip:rect(0 0 0 0); clip-path:inset(50%); white-space:nowrap; border:0; }
                                .vance-mv-stats { grid-column:1/-1; display:flex; flex-wrap:wrap; gap:10px; }
                                .vance-mv-stat { flex:1 1 120px; border:1px solid #E2E8F0; background:#F8FAFC; padding:10px 14px; }
                                .vance-mv-stat b { display:block; font-family:'Outfit',sans-serif; font-size:19px; color:#0F172A; }
                                .vance-mv-stat span { font-size:11px; text-transform:uppercase; letter-spacing:0.5px; color:#475569; }
                                @media (prefers-reduced-motion: reduce) {
                                    .vance-mv-open { transition:none; }
                                }
                            </style>
                            <?php
                            // Collected in the loop below, emitted as one JS object after it.
                            $meal_plan_payloads = array();
                            ?>
                            <div class="vance-mp-list">
                                <?php foreach ($meal_plan_history as $mp_i => $entry):
                                    $p    = $entry['payload'];
                                    $ts   = !empty($entry['ts']) ? (int) $entry['ts'] : 0;
                                    $when = $ts ? date_i18n('j M Y', $ts) : '';
                                    // Same handle the rename/delete handlers resolve against, so
                                    // rows saved before ids existed stay addressable by timestamp.
                                    $key  = function_exists('vance_tool_history_key') ? vance_tool_history_key($entry) : '';
                                    // Structured saves carry days/totals. Older saves are a DOM
                                    // snapshot with only a text blob, so degrade to a plain row.
                                    $is_structured = isset($p['kind']) && $p['kind'] === 'meal-plan' && !empty($p['days']) && is_array($p['days']);

                                    // Expand the saved rows against inc/recipe-catalogue.php. This is
                                    // what puts a picture and a full-recipe link on every meal: the
                                    // planner's slot cells render neither, so the saved payload has
                                    // only a name (and, since v3, a slug) to go on.
                                    $plan     = $is_structured ? vance_recipe_expand_plan($p['days']) : array('days' => array(), 'image' => '', 'totals' => array());
                                    $exp_days = $plan['days'];
                                    $totals   = $plan['totals'];
                                    // Every plan reads as named: user label if renamed, else the date.
                                    $plan_name = !empty($p['name'])
                                        ? $p['name']
                                        : 'Meal plan' . ($when ? ', ' . $when : '');
                                    // Meta line: "Saved on 23 Jul 2026 · 7 days, 28 meals · 8,975 kcal"
                                    $meta_bits = array();
                                    if ($when) { $meta_bits[] = 'Saved on ' . $when; }
                                    if ($is_structured) {
                                        $d = isset($totals['days'])  ? (int) $totals['days']  : count($p['days']);
                                        $m = isset($totals['meals']) ? (int) $totals['meals'] : 0;
                                        $meta_bits[] = sprintf(
                                            /* translators: 1: number of days, 2: number of meals */
                                            esc_html__('%1$d days, %2$d meals', 'vance-health-hub'),
                                            $d,
                                            $m
                                        );
                                        if (!empty($totals['calories'])) {
                                            $meta_bits[] = number_format_i18n((int) $totals['calories']) . ' kcal';
                                        }
                                    } else {
                                        $meta_bits[] = 'Saved plan';
                                    }
                                    // Plan hero: first meal that resolves to a picture. The payload's
                                    // own `image` is only a fallback — it is empty on every save made
                                    // to date, because the planner never rendered a thumbnail to scrape.
                                    $plan_image = $plan['image'];
                                    if ('' === $plan_image && !empty($p['image'])) {
                                        $plan_image = (string) $p['image'];
                                    }

                                    // The viewer and the PDF both render from this. It goes into a
                                    // keyed JS object rather than a data-plan attribute: with a
                                    // thumbnail, a link and a nutrition block on all 28 meals plus a
                                    // shopping list, the JSON is far too big to sit in an attribute
                                    // on every card.
                                    $meal_plan_payloads[ $mp_i ] = array(
                                        'name'     => $plan_name,
                                        'when'     => $when,
                                        // ISO stamp for the PDF filename. `when` is already
                                        // localised for display ("23 Jul 2026") and sorts badly
                                        // in a folder listing, so the filename uses this instead.
                                        'date'     => $ts ? date('Y-m-d', $ts) : '',
                                        'image'    => $plan_image,
                                        'days'     => $exp_days,
                                        'totals'   => $totals,
                                        'shopping' => $is_structured ? vance_recipe_shopping_list($exp_days) : array(),
                                        // Ingredients + method for each distinct recipe, keyed by
                                        // slug. The PDF's recipe appendix reads from here; meal
                                        // rows only carry a slug, so nothing is duplicated 28 times.
                                        'recipes'  => $is_structured ? vance_recipe_plan_recipes($exp_days) : array(),
                                    );

                                    // Photo credit for the viewer and the PDF, covering only the
                                    // photographers whose work this plan actually shows. Plain
                                    // text, not linked: it is rasterised into the PDF, where a
                                    // link would be dead weight.
                                    $plan_slugs = array_keys($meal_plan_payloads[ $mp_i ]['recipes']);
                                    $meal_plan_payloads[ $mp_i ]['credit'] = $plan_slugs
                                        ? vance_recipe_credit_line($plan_slugs, false)
                                        : '';
                                    // Accumulated across every plan on the page for the one
                                    // discreet line under the list.
                                    $meal_plan_credit_slugs = array_unique(array_merge(
                                        isset($meal_plan_credit_slugs) ? $meal_plan_credit_slugs : array(),
                                        $plan_slugs
                                    ));

                                    $panel_id = 'vance-mp-panel-' . (int) $mp_i;
                                    ?>
                                    <div class="vance-mp-card vance-glass">
                                        <div class="vance-mp-head">
                                            <button type="button" class="vance-mp-toggle vance-expand-toggle" aria-expanded="false" aria-controls="<?php echo esc_attr($panel_id); ?>">
                                                <svg class="vance-expand-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M6 9l6 6 6-6"/></svg>
                                                <span class="vance-mp-title"><?php echo esc_html($plan_name); ?></span>
                                            </button>
                                            <div class="vance-mp-meta"><?php echo esc_html(implode(' · ', $meta_bits)); ?></div>
                                        </div>
                                        <div id="<?php echo esc_attr($panel_id); ?>" class="vance-expand-panel vance-mp-panel">
                                            <div class="vance-mp-panel-inner">
                                                <?php if ($plan_image): ?>
                                                    <img class="vance-mp-img" src="<?php echo esc_url($plan_image); ?>" alt="<?php echo esc_attr($plan_name); ?>" loading="lazy">
                                                <?php endif; ?>
                                                <?php if ($is_structured):
                                                    $t_days  = isset($totals['days'])  ? (int) $totals['days']  : 0;
                                                    $t_meals = isset($totals['meals']) ? (int) $totals['meals'] : 0;
                                                    $t_kcal  = !empty($totals['calories']) ? (int) $totals['calories'] : 0;
                                                    ?>
                                                    <div class="vance-mv-stats" style="margin-bottom:18px;">
                                                        <div class="vance-mv-stat"><b><?php echo esc_html(number_format_i18n($t_days)); ?></b><span>Days</span></div>
                                                        <div class="vance-mv-stat"><b><?php echo esc_html(number_format_i18n($t_meals)); ?></b><span>Meals</span></div>
                                                        <?php if ($t_kcal): ?>
                                                            <div class="vance-mv-stat"><b><?php echo esc_html(number_format_i18n($t_kcal)); ?></b><span>Total kcal</span></div>
                                                        <?php endif; ?>
                                                        <?php if ($t_kcal && $t_days): ?>
                                                            <div class="vance-mv-stat"><b><?php echo esc_html(number_format_i18n((int) round($t_kcal / $t_days))); ?></b><span>kcal / day</span></div>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if ($is_structured): ?>
                                                    <div class="vance-mp-days">
                                                        <?php foreach ($exp_days as $dd): ?>
                                                            <span class="vance-mp-daychip"><strong><?php echo esc_html($dd['day']); ?></strong> &middot; <?php echo count($dd['meals']); ?> meals<?php echo $dd['calories'] ? ' &middot; ' . esc_html(number_format_i18n($dd['calories'])) . ' kcal' : ''; ?></span>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="vance-mp-actions">
                                                    <?php if ($is_structured): ?>
                                                        <button type="button" class="vance-btn-inverted vance-btn--sm" data-vance-tool-open="ibd-recipes" data-plan-key="<?php echo esc_attr($key); ?>">Edit meal plan</button>
                                                        <button type="button" class="vance-btn-glass vance-btn--sm vance-mp-pdf" data-plan-index="<?php echo (int) $mp_i; ?>">Download PDF</button>
                                                        <button type="button" class="vance-btn-glass vance-btn--sm btn-view-meal-plan" data-plan-index="<?php echo (int) $mp_i; ?>">View full</button>
                                                    <?php endif; ?>
                                                    <button type="button" class="vance-mp-textbtn" onclick="renameMealPlan('<?php echo esc_js($key); ?>', '<?php echo esc_js($plan_name); ?>')">Rename</button>
                                                    <button type="button" class="vance-mp-textbtn vance-mp-danger" onclick="deleteMealPlan('<?php echo esc_js($key); ?>')">Delete</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <script>
                                // Keyed by the card's data-plan-index. Built server-side so the
                                // viewer and the PDF share one already-resolved shape.
                                window.VANCE_MEAL_PLANS = <?php echo wp_json_encode($meal_plan_payloads); ?>;
                            </script>
                            <p style="margin:20px 0 0; font-size:12px; color:#94A3B8; line-height:1.5;">Meal plans are a general guide, not personalised dietary advice. Check any dietary change with your healthcare team.</p>
                            <?php
                            // Unsplash licence: credit the photographer wherever the photo
                            // appears. Deliberately near-invisible — it belongs on the page,
                            // not in the reader's way.
                            $mp_credit = !empty($meal_plan_credit_slugs)
                                ? vance_recipe_credit_line($meal_plan_credit_slugs)
                                : '';
                            if ($mp_credit) : ?>
                                <p style="margin:8px 0 0; font-size:9px; line-height:1.6; color:#94A3B8; letter-spacing:.2px;"><?php echo $mp_credit; // already escaped by vance_recipe_credit_line() ?></p>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Meal Plan Viewer Modal (glass) -->
                    <div id="meal-plan-modal" class="vance-glass-scrim" style="display:none; position:fixed; inset:0; z-index:10001; align-items:center; justify-content:center; padding:20px;">
                        <div class="vance-glass-panel" style="width:100%; max-width:800px; max-height:90vh; display:flex; flex-direction:column; overflow:hidden;">
                            <div style="padding:24px; border-bottom:1px solid rgba(0,128,128,0.16); display:flex; justify-content:space-between; align-items:center;">
                                <div>
                                    <h3 id="modal-meal-plan-title" style="margin:0; font-family:'Outfit'; font-size:20px; color:#0A1929;">Meal plan</h3>
                                    <p id="modal-meal-plan-date" style="margin:4px 0 0 0; font-size:12px; color:#64748B;"></p>
                                </div>
                                <button onclick="closeMealPlanModal()" aria-label="Close" style="font-size:24px; border:1px solid rgba(255,255,255,0.6); background:rgba(255,255,255,0.5); cursor:pointer; color:#0A1929; line-height:1; width:40px; height:40px; border-radius:0; display:flex; align-items:center; justify-content:center;">&times;</button>
                            </div>
                            <!-- minmax(320px) not 220px: each meal row carries a 72px thumbnail
                                 and a Full recipe button, so narrower columns wrap badly. -->
                            <div id="modal-meal-plan-content" style="flex:1; overflow-y:auto; padding:32px; display:grid; grid-template-columns:repeat(auto-fit, minmax(320px, 1fr)); gap:16px; align-content:start;">
                                <!-- Days are rendered here on open -->
                            </div>
                            <div style="padding:20px; border-top:1px solid rgba(0,128,128,0.16); display:flex; justify-content:flex-end; gap:10px; flex-wrap:wrap;">
                                <button type="button" id="modal-meal-plan-pdf" class="vance-btn-glass vance-btn--sm vance-mp-pdf" data-plan-index="">Download PDF</button>
                                <button onclick="closeMealPlanModal()" class="vance-btn-inverted vance-btn--sm">Close</button>
                            </div>
                        </div>
                    </div>
                    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

                    <script>
                    // Saved meal plans: view / rename / delete. Follows the saved-chats
                    // handlers on the VANCE-Ai tab (prompt or confirm, post, reload).
                    (function () {
                        var ajaxUrl = <?php echo wp_json_encode( admin_url('admin-ajax.php') ); ?>;
                        var nonce   = <?php echo wp_json_encode( wp_create_nonce('vance_dashboard_nonce') ); ?>;

                        function esc(s) {
                            return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
                                return { '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;' }[c];
                            });
                        }

                        // Both the viewer and the PDF read from the server-built object.
                        function planFor(btn) {
                            var i = btn.getAttribute('data-plan-index');
                            var plans = window.VANCE_MEAL_PLANS || {};
                            return (i !== null && plans[i]) ? plans[i] : null;
                        }

                        function num(n) {
                            return String(n == null ? '' : n).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                        }

                        // Open-in-new-tab arrow, inline so it inherits currentColor. SVG rather
                        // than a text arrow — the rest of the dashboard uses icons, not glyphs.
                        var ICON_EXTERNAL =
                            '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" ' +
                            'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' +
                            '<path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>' +
                            '<path d="M15 3h6v6"/><path d="M10 14L21 3"/></svg>';

                        /**
                         * One meal row: thumbnail, slot, name, facts, and a button that opens the
                         * full recipe in a new tab. Meals whose recipe the catalogue does not know
                         * still render — they just lose the picture and the link rather than
                         * dropping out of the plan.
                         */
                        function mealRowHtml(m) {
                            var thumb = m.image
                                ? '<img class="vance-mv-thumb" src="' + esc(m.image) + '" alt="' + esc(m.name) + '" loading="lazy" decoding="async">'
                                : '<div class="vance-mv-thumb vance-mv-thumb--empty" aria-hidden="true">' +
                                  '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">' +
                                  '<circle cx="12" cy="12" r="9"/><path d="M8 14h8M9 9h.01M15 9h.01"/></svg></div>';

                            var facts = [];
                            if (m.calories) { facts.push(esc(m.calories) + ' kcal'); }
                            if (m.minutes)  { facts.push(esc(m.minutes) + ' min'); }
                            if (m.nutrition && m.nutrition.protein) { facts.push(esc(m.nutrition.protein) + 'g protein'); }
                            if (m.nutrition && m.nutrition.fibre)   { facts.push(esc(m.nutrition.fibre) + 'g fibre'); }

                            var open = m.url
                                ? '<a class="vance-mv-open" href="' + esc(m.url) + '" target="_blank" rel="noopener noreferrer">' +
                                  'Full recipe' + ICON_EXTERNAL +
                                  '<span class="vance-mv-sr"> for ' + esc(m.name) + ' (opens in a new tab)</span></a>'
                                : '';

                            return '<div class="vance-mv-meal">' + thumb +
                                   '<div class="vance-mv-body">' +
                                   '<div class="vance-mv-slot">' + esc(m.slot) + '</div>' +
                                   '<div class="vance-mv-name">' + esc(m.name) + '</div>' +
                                   (facts.length ? '<div class="vance-mv-facts">' + facts.map(function (f) {
                                       return '<span>' + f + '</span>';
                                   }).join('') + '</div>' : '') +
                                   open + '</div></div>';
                        }

                        window.openMealPlanModal = function (btn) {
                            var plan = planFor(btn);
                            if (!plan) { return; }

                            document.getElementById('modal-meal-plan-title').textContent = plan.name || 'Meal plan';
                            document.getElementById('modal-meal-plan-date').textContent  = plan.when ? ('Saved on ' + plan.when) : '';

                            var t = plan.totals || {};
                            var statsHtml = '<div class="vance-mv-stats">' +
                                '<div class="vance-mv-stat"><b>' + num(t.days || 0) + '</b><span>Days</span></div>' +
                                '<div class="vance-mv-stat"><b>' + num(t.meals || 0) + '</b><span>Meals</span></div>' +
                                (t.calories ? '<div class="vance-mv-stat"><b>' + num(t.calories) + '</b><span>Total kcal</span></div>' : '') +
                                (t.calories && t.days ? '<div class="vance-mv-stat"><b>' + num(Math.round(t.calories / t.days)) + '</b><span>kcal / day</span></div>' : '') +
                                '</div>';

                            var html = (plan.days || []).filter(function (d) {
                                return d && d.meals && d.meals.length;
                            }).map(function (d) {
                                return '<div class="vance-mv-day">' +
                                       '<div class="vance-mv-dayhead"><strong>' + esc(d.day) + '</strong>' +
                                       (d.calories ? '<span>' + num(d.calories) + ' kcal</span>' : '') + '</div>' +
                                       d.meals.map(mealRowHtml).join('') + '</div>';
                            }).join('');

                            var imgHtml = plan.image
                                ? '<img src="' + esc(plan.image) + '" alt="" style="grid-column:1/-1; width:100%; max-height:260px; object-fit:cover; border-radius:0;">'
                                : '';
                            document.getElementById('modal-meal-plan-content').innerHTML =
                                imgHtml + statsHtml +
                                (html || '<p style="color:#475569; font-size:13px;">This plan has no meals saved against it.</p>') +
                                // Unsplash licence: the modal shows a thumbnail per meal, so the
                                // credit belongs here too. Spans the full grid, barely visible.
                                (plan.credit
                                    ? '<p style="grid-column:1/-1; margin:6px 0 0; font-size:9px; line-height:1.6; color:#94A3B8; letter-spacing:.2px;">' + esc(plan.credit) + '</p>'
                                    : '');
                            // Point the modal's own PDF button at whichever plan is open.
                            var modalPdf = document.getElementById('modal-meal-plan-pdf');
                            if (modalPdf) { modalPdf.setAttribute('data-plan-index', btn.getAttribute('data-plan-index')); }

                            document.getElementById('meal-plan-modal').style.display = 'flex';
                            document.body.style.overflow = 'hidden';
                        };

                        window.closeMealPlanModal = function () {
                            document.getElementById('meal-plan-modal').style.display = 'none';
                            document.body.style.overflow = 'auto';
                        };

                        window.renameMealPlan = function (id, currentName) {
                            var name = prompt('Enter a new name for this meal plan:', currentName);
                            if (name === null) { return; }
                            name = name.trim();
                            if (name === '' || name === currentName) { return; }
                            jQuery.post(ajaxUrl, {
                                action: 'vance_rename_tool_entry',
                                tool: 'ibd-recipes', id: id, name: name, nonce: nonce
                            }, function (res) {
                                if (res.success) { location.reload(); } else { alert(res.data); }
                            });
                        };

                        window.deleteMealPlan = function (id) {
                            if (!confirm('Delete this meal plan permanently?')) { return; }
                            jQuery.post(ajaxUrl, {
                                action: 'vance_delete_tool_entry',
                                tool: 'ibd-recipes', id: id, nonce: nonce
                            }, function (res) {
                                if (res.success) { location.reload(); } else { alert(res.data); }
                            });
                        };

                        // Expand / collapse a saved plan card (animated max-height).
                        function toggleMealPlanPanel(toggle) {
                            var id = toggle.getAttribute('aria-controls');
                            var panel = id ? document.getElementById(id) : null;
                            if (!panel) { return; }
                            var isOpen = toggle.getAttribute('aria-expanded') === 'true';
                            if (isOpen) {
                                panel.style.maxHeight = panel.scrollHeight + 'px';
                                requestAnimationFrame(function () {
                                    panel.style.maxHeight = '0px';
                                    panel.classList.remove('is-open');
                                });
                                toggle.setAttribute('aria-expanded', 'false');
                            } else {
                                panel.classList.add('is-open');
                                panel.style.maxHeight = panel.scrollHeight + 'px';
                                toggle.setAttribute('aria-expanded', 'true');
                                panel.addEventListener('transitionend', function te(ev) {
                                    if (ev.propertyName === 'max-height' && toggle.getAttribute('aria-expanded') === 'true') {
                                        panel.style.maxHeight = 'none'; // let it grow (e.g. once the image loads)
                                    }
                                    panel.removeEventListener('transitionend', te);
                                });
                            }
                        }

                        /**
                         * ---------------------------------------------------------------
                         * Meal plan PDF
                         * ---------------------------------------------------------------
                         * A masthead carrying the Vance logo, a per-day schedule with a photo
                         * against every meal, a consolidated shopping list, and a recipe
                         * appendix with the ingredients and method for every dish in the plan
                         * — i.e. something you can actually take to the kitchen and the
                         * supermarket and cook from, rather than the two-column name/kcal
                         * table this used to emit.
                         *
                         * Document order is deliberate and follows how it gets used: plan
                         * overview, then what to eat each day, then what to buy, then how to
                         * cook it.
                         *
                         * Three things make it work that the old version got wrong:
                         *
                         *  1. The render element is attached to the document (off-screen) and
                         *     every image is awaited before html2canvas runs. html2canvas
                         *     paints whatever is decoded at the moment it fires; a detached
                         *     node whose images are still in flight rasterises to blank boxes.
                         *     With a photo on all 28 meals that is the difference between a
                         *     usable document and an empty one.
                         *  2. A4, not US Letter — this is a UK site.
                         *  3. Explicit page-break rules, so a day block or the shopping list
                         *     is never sliced across the fold.
                         */
                        var PDF_TEAL = '#008080', PDF_INK = '#0F172A', PDF_BODY = '#334155', PDF_MUTE = '#475569';

                        // Same-origin, so html2canvas can read it back out of the canvas
                        // without tainting it — no crossorigin attribute needed or wanted.
                        var PDF_LOGO = <?php echo wp_json_encode( get_template_directory_uri() . '/assets/img/logo.png' ); ?>;
                        var PDF_USER = <?php echo wp_json_encode( $current_user->user_login ); ?>;

                        /**
                         * The logo PNG is 1024x576 with a large transparent margin baked in, so
                         * drawing it at its own aspect ratio renders a wordmark about a third
                         * the size of the box it sits in. The site header solves this with a
                         * crop window (see .logo-area in main.css); the same trick is used
                         * here, but with an explicit negative offset rather than flex centring
                         * — box arithmetic rasterises predictably, flex alignment inside an
                         * overflow:hidden parent does not always survive html2canvas.
                         */
                        function pdfLogo(width) {
                            var natural = width * 576 / 1024;        // height if drawn uncropped
                            var band    = Math.round(width * 0.29);  // visible strip, matches the header's crop ratio
                            var offset  = (natural - band) / 2;
                            return '<div style="width:' + width + 'px; height:' + band + 'px; overflow:hidden;">' +
                                   '<img src="' + PDF_LOGO + '" alt="Vance Medical" ' +
                                   'style="width:' + width + 'px; height:' + natural + 'px; display:block; margin-top:-' + offset + 'px;">' +
                                   '</div>';
                        }

                        /**
                         * VANCE_MealPlan_<username>_<plan name>_<date>.pdf
                         *
                         * The date is the plan's *saved* date, not today's, so re-downloading
                         * the same plan produces the same file rather than a folder full of
                         * near-duplicates.
                         *
                         * Each segment keeps only [A-Za-z0-9-] and joins the rest with
                         * underscores. Hyphens survive deliberately: stripping them would turn
                         * the ISO date into 2026_07_23, which no longer reads as a date and no
                         * longer sorts as one next to anything else. Underscore stays the
                         * segment separator, so "A - B" collapses to "A_B" rather than "A_-_B".
                         */
                        function pdfFilename(plan) {
                            var clean = function (v, fallback) {
                                var s = String(v == null ? '' : v)
                                    .replace(/[^A-Za-z0-9-]+/g, '_')
                                    .replace(/_-+_/g, '_')
                                    .replace(/^[_-]+|[_-]+$/g, '');
                                return s || fallback;
                            };
                            return [
                                'VANCE_MealPlan',
                                clean(PDF_USER, 'user'),
                                clean(plan.name, 'meal_plan'),
                                clean(plan.date || plan.when, 'undated')
                            ].join('_') + '.pdf';
                        }

                        /**
                         * A photo cropped to fill a fixed box, for the PDF only.
                         *
                         * NOT an <img style="object-fit:cover"> — html2canvas ignores object-fit
                         * and stretches the image to the box instead, so every photo printed
                         * distorted. It is subtle on a square 62px thumbnail (a 3:2 source
                         * squeezed 33%) and gross on the full-width hero, where a 3:2 photo is
                         * crushed into a 4.8:1 band. background-size:cover IS honoured, and
                         * crops exactly as object-fit would. Verified side by side.
                         *
                         * The URL goes on data-bg too so whenImagesSettled() can preload it —
                         * background images fire no load event of their own.
                         */
                        function pdfPhoto(url, css) {
                            return '<div data-bg="' + esc(url) + '" style="' + css +
                                   ' background-image:url(\'' + esc(url) + '\');' +
                                   ' background-size:cover; background-position:center center;' +
                                   ' background-repeat:no-repeat; background-color:#F1F5F9;"></div>';
                        }

                        // Wait for every photo inside el — both <img> tags and the background
                        // images painted by pdfPhoto() — but never hang the download on one that
                        // will not load. A dead URL resolves rather than rejects.
                        function whenImagesSettled(el) {
                            var jobs = [].slice.call(el.querySelectorAll('img')).map(function (img) {
                                if (img.complete && img.naturalWidth > 0) { return Promise.resolve(); }
                                return new Promise(function (resolve) {
                                    var done = false;
                                    var finish = function () { if (!done) { done = true; resolve(); } };
                                    img.addEventListener('load', finish);
                                    img.addEventListener('error', function () {
                                        // Drop the broken frame so it does not print as a grey box.
                                        if (img.parentNode) { img.parentNode.removeChild(img); }
                                        finish();
                                    });
                                    setTimeout(finish, 8000);
                                });
                            });

                            jobs = jobs.concat([].slice.call(el.querySelectorAll('[data-bg]')).map(function (node) {
                                return new Promise(function (resolve) {
                                    var done = false;
                                    var finish = function () { if (!done) { done = true; resolve(); } };
                                    var probe = new Image();
                                    probe.crossOrigin = 'anonymous';
                                    probe.onload  = finish;
                                    probe.onerror = function () {
                                        // Leave the plain placeholder box rather than a broken tile.
                                        node.style.backgroundImage = 'none';
                                        finish();
                                    };
                                    setTimeout(finish, 8000);
                                    probe.src = node.getAttribute('data-bg');
                                });
                            }));

                            return jobs.length ? Promise.all(jobs) : Promise.resolve();
                        }

                        function pdfStat(value, label) {
                            return '<td style="padding:10px 12px; border:1px solid #E2E8F0; background:#F8FAFC; text-align:center;">' +
                                   '<div style="font-size:19px; font-weight:800; color:' + PDF_INK + '; line-height:1.1;">' + value + '</div>' +
                                   '<div style="font-size:9px; letter-spacing:0.6px; text-transform:uppercase; color:' + PDF_MUTE + '; margin-top:3px;">' + label + '</div></td>';
                        }

                        function pdfMealRow(m) {
                            var facts = [];
                            if (m.calories) { facts.push(esc(m.calories) + ' kcal'); }
                            if (m.minutes)  { facts.push(esc(m.minutes) + ' min'); }
                            if (m.nutrition && m.nutrition.protein) { facts.push(esc(m.nutrition.protein) + 'g protein'); }
                            if (m.nutrition && m.nutrition.fibre)   { facts.push(esc(m.nutrition.fibre) + 'g fibre'); }
                            if (m.servings) { facts.push('serves ' + esc(m.servings)); }

                            var img = m.image
                                ? pdfPhoto(m.image, 'width:62px; height:62px; border:1px solid #E2E8F0;')
                                : '';

                            return '<tr>' +
                                '<td style="width:70px; padding:8px 10px 8px 0; vertical-align:top;">' + img + '</td>' +
                                '<td style="padding:8px 0; vertical-align:top;">' +
                                    '<div style="font-size:8.5px; font-weight:700; letter-spacing:0.7px; text-transform:uppercase; color:' + PDF_MUTE + ';">' + esc(m.slot || '') + '</div>' +
                                    '<div style="font-size:12.5px; font-weight:700; color:' + PDF_INK + '; margin:2px 0 3px;">' + esc(m.name || '') + '</div>' +
                                    (facts.length ? '<div style="font-size:10px; color:' + PDF_BODY + ';">' + facts.join(' &nbsp;·&nbsp; ') + '</div>' : '') +
                                '</td></tr>';
                        }

                        // Section rule shared by the shopping list and the recipe appendix.
                        function pdfHeading(text) {
                            return '<div style="font-size:14px; font-weight:800; color:' + PDF_INK + '; text-transform:uppercase; letter-spacing:0.8px; border-bottom:2px solid ' + PDF_TEAL + '; padding-bottom:5px; margin-bottom:10px;">' + text + '</div>';
                        }

                        /**
                         * One recipe: ingredients on the left, numbered method on the right.
                         *
                         * Ingredients arrive as [{section, items[]}] — most recipes have a
                         * single unnamed section, but the ones that separate (say) a sauce from
                         * the base carry a title that has to be shown or the list reads as one
                         * undifferentiated column.
                         */
                        function pdfRecipeCard(r) {
                            var meta = [];
                            if (r.servings) { meta.push('Serves ' + num(r.servings)); }
                            if (r.prep)     { meta.push(num(r.prep) + ' min prep'); }
                            if (r.cook)     { meta.push(num(r.cook) + ' min cook'); }

                            var ing = (r.ingredients || []).map(function (sec) {
                                var title = sec.section
                                    ? '<div style="font-size:9px; font-weight:800; text-transform:uppercase; letter-spacing:0.6px; color:' + PDF_TEAL + '; margin:8px 0 3px;">' + esc(sec.section) + '</div>'
                                    : '';
                                return title + (sec.items || []).map(function (item) {
                                    return '<div style="font-size:10.5px; color:' + PDF_BODY + '; padding:2.5px 0; border-bottom:1px solid #F1F5F9;">' +
                                           '<span style="display:inline-block; width:8px; height:8px; border:1px solid #94A3B8; margin-right:7px;"></span>' +
                                           esc(item) + '</div>';
                                }).join('');
                            }).join('');

                            var steps = (r.instructions || []).map(function (step, i) {
                                return '<tr>' +
                                    '<td style="width:20px; vertical-align:top; padding:3px 7px 3px 0;">' +
                                        '<span style="display:inline-block; width:15px; height:15px; background:' + PDF_TEAL + '; color:#fff; font-size:9px; font-weight:800; text-align:center; line-height:15px;">' + (i + 1) + '</span>' +
                                    '</td>' +
                                    '<td style="font-size:10.5px; color:' + PDF_BODY + '; line-height:1.45; padding:3px 0;">' + esc(step) + '</td>' +
                                '</tr>';
                            }).join('');

                            return '<div class="pdf-block" style="margin-bottom:18px; border:1px solid #E2E8F0; border-top:3px solid ' + PDF_TEAL + '; padding:12px 14px;">' +
                                '<div style="font-size:13.5px; font-weight:800; color:' + PDF_INK + ';">' + esc(r.name || '') + '</div>' +
                                (meta.length ? '<div style="font-size:9.5px; color:' + PDF_MUTE + '; margin:3px 0 9px;">' + meta.join(' &nbsp;·&nbsp; ') + '</div>' : '<div style="height:8px;"></div>') +
                                '<table style="width:100%; border-collapse:collapse;"><tr>' +
                                    '<td style="width:42%; vertical-align:top; padding-right:16px;">' +
                                        '<div style="font-size:9px; font-weight:800; text-transform:uppercase; letter-spacing:0.8px; color:' + PDF_MUTE + '; margin-bottom:4px;">Ingredients</div>' +
                                        (ing || '<div style="font-size:10px; color:' + PDF_MUTE + ';">Not recorded.</div>') +
                                    '</td>' +
                                    '<td style="vertical-align:top;">' +
                                        '<div style="font-size:9px; font-weight:800; text-transform:uppercase; letter-spacing:0.8px; color:' + PDF_MUTE + '; margin-bottom:4px;">Method</div>' +
                                        (steps ? '<table style="width:100%; border-collapse:collapse;">' + steps + '</table>'
                                               : '<div style="font-size:10px; color:' + PDF_MUTE + ';">Not recorded.</div>') +
                                    '</td>' +
                                '</tr></table></div>';
                        }

                        function buildMealPlanDocument(plan) {
                            var t = plan.totals || {};
                            var days = (plan.days || []).filter(function (d) { return d && d.meals && d.meals.length; });

                            var statsRow =
                                '<table style="width:100%; border-collapse:separate; border-spacing:6px 0; margin:0 0 22px;"><tr>' +
                                pdfStat(num(t.days || days.length), 'Days') +
                                pdfStat(num(t.meals || 0), 'Meals') +
                                (t.calories ? pdfStat(num(t.calories), 'Total kcal') : '') +
                                (t.calories && t.days ? pdfStat(num(Math.round(t.calories / t.days)), 'kcal / day') : '') +
                                (t.minutes ? pdfStat(num(t.minutes) + ' min', 'Kitchen time') : '') +
                                '</tr></table>';

                            var daysHtml = days.map(function (d) {
                                return '<div class="pdf-block" style="margin-bottom:16px;">' +
                                    '<table style="width:100%; border-collapse:collapse; background:' + PDF_TEAL + ';"><tr>' +
                                        '<td style="padding:8px 12px; color:#fff; font-size:13px; font-weight:800; letter-spacing:0.4px;">' + esc(d.day) + '</td>' +
                                        '<td style="padding:8px 12px; color:rgba(255,255,255,0.92); font-size:10px; text-align:right;">' +
                                            (d.calories ? num(d.calories) + ' kcal' : '') + '</td>' +
                                    '</tr></table>' +
                                    '<table style="width:100%; border-collapse:collapse; border:1px solid #E2E8F0; border-top:none; padding:0 12px;">' +
                                        d.meals.map(pdfMealRow).join('') +
                                    '</table></div>';
                            }).join('');

                            // Shopping list: two columns of tick-boxes. Quantities are per recipe
                            // (see vance_recipe_shopping_list) — the ×N says how many times over.
                            var shopping = plan.shopping || [];
                            var shoppingHtml = '';
                            if (shopping.length) {
                                var half  = Math.ceil(shopping.length / 2);
                                var cell  = function (list) {
                                    return '<td style="width:50%; vertical-align:top; padding-right:14px;">' + list.map(function (s) {
                                        return '<div style="font-size:10.5px; color:' + PDF_BODY + '; padding:3px 0; border-bottom:1px solid #F1F5F9;">' +
                                               '<span style="display:inline-block; width:9px; height:9px; border:1px solid #94A3B8; margin-right:7px;"></span>' +
                                               esc(s.item) + (s.count > 1 ? ' <b style="color:' + PDF_TEAL + ';">&times;' + s.count + '</b>' : '') +
                                               '</div>';
                                    }).join('') + '</td>';
                                };
                                shoppingHtml =
                                    '<div class="pdf-block" style="margin-top:22px;">' +
                                    pdfHeading('Shopping list') +
                                    '<div style="font-size:9.5px; color:' + PDF_MUTE + '; margin-bottom:8px;">Quantities are per recipe. &times;2 means that quantity is needed twice across the plan.</div>' +
                                    '<table style="width:100%; border-collapse:collapse;"><tr>' +
                                    cell(shopping.slice(0, half)) + cell(shopping.slice(half)) +
                                    '</tr></table></div>';
                            }

                            // Recipe appendix — ingredients and method for every distinct dish,
                            // in the order the plan first calls for it. `plan.recipes` is a
                            // slug-keyed map; saves made before it existed simply have none, in
                            // which case the section is omitted rather than printed empty.
                            var recipes    = plan.recipes || {};
                            var recipeKeys = Object.keys(recipes);
                            var recipesHtml = '';
                            if (recipeKeys.length) {
                                recipesHtml =
                                    '<div style="margin-top:26px;">' +
                                    '<div class="pdf-block">' +
                                        pdfHeading('Recipes') +
                                        '<div style="font-size:9.5px; color:' + PDF_MUTE + '; margin-bottom:12px;">' +
                                            'Ingredient quantities are for the servings shown against each recipe. Scale them up if you are cooking for more.' +
                                        '</div>' +
                                    '</div>' +
                                    recipeKeys.map(function (slug) { return pdfRecipeCard(recipes[slug]); }).join('') +
                                    '</div>';
                            }

                            var el = document.createElement('div');
                            el.innerHTML =
                                '<div style="padding:34px 38px; font-family:Helvetica,Arial,sans-serif; color:' + PDF_BODY + ';">' +
                                    // Masthead: logo left, save date right, plan name across the
                                    // bottom above the teal rule.
                                    '<table style="width:100%; border-collapse:collapse;"><tr>' +
                                        '<td style="vertical-align:middle;">' + pdfLogo(150) + '</td>' +
                                        '<td style="text-align:right; vertical-align:middle; font-size:10px; color:' + PDF_MUTE + ';">' +
                                            (plan.when ? 'Saved ' + esc(plan.when) : '') +
                                        '</td>' +
                                    '</tr></table>' +
                                    '<div style="border-bottom:3px solid ' + PDF_TEAL + '; padding-bottom:12px; margin-top:16px;">' +
                                        '<div style="font-size:10px; font-weight:800; letter-spacing:2.2px; text-transform:uppercase; color:' + PDF_TEAL + ';">Meal plan</div>' +
                                        '<div style="font-size:25px; font-weight:800; color:' + PDF_INK + '; letter-spacing:-0.4px; margin-top:3px;">' + esc(plan.name || 'Meal plan') + '</div>' +
                                    '</div>' +
                                    (plan.image
                                        ? pdfPhoto(plan.image, 'width:100%; height:150px; margin:18px 0;')
                                        : '<div style="height:18px;"></div>') +
                                    statsRow +
                                    (daysHtml || '<p style="color:' + PDF_MUTE + ';">No meals recorded against this plan.</p>') +
                                    shoppingHtml +
                                    recipesHtml +
                                    // pdf-block so the disclaimer and the photo credit are not
                                    // split across the fold. Without it a real 12-page export put
                                    // the disclaimer at the foot of page 11 and stranded the credit
                                    // alone on a near-empty page 12.
                                    '<div class="pdf-block" style="margin-top:26px; border-top:1px solid #E2E8F0; padding-top:10px; font-size:9px; color:' + PDF_MUTE + '; line-height:1.5;">' +
                                        'Generated from Vance Medical Hub. Meal plans are general guidance, not personalised dietary advice — check any dietary change with your healthcare team.' +
                                        // Unsplash licence: credit travels with the photos.
                                        (plan.credit ? '<div style="margin-top:5px; color:#94A3B8;">' + esc(plan.credit) + '</div>' : '') +
                                    '</div>' +
                                '</div>';
                            return el;
                        }

                        function downloadMealPlanPDF(btn) {
                            var plan = planFor(btn);
                            if (!plan) { return; }
                            if (typeof html2pdf === 'undefined') {
                                alert('The PDF library is still loading, please try again in a moment.');
                                return;
                            }

                            var label = btn.textContent;
                            btn.disabled = true;
                            btn.textContent = 'Building PDF…';

                            // Off-screen but laid out and painted, so the images actually decode —
                            // display:none or a detached node would not load them at all.
                            //
                            // The offset MUST live on a wrapper, not on the element handed to
                            // html2pdf. html2pdf clones the element into an inline-block container
                            // and measures it; a clone that is itself position:fixed contributes
                            // no in-flow height, so the container measures zero and the whole
                            // export rasterises to a 0px-tall canvas — a blank PDF, with no error
                            // thrown. Verified: fixed-on-element gives 1191x0, this gives 794x5135.
                            //
                            // `absolute`, not `fixed`: a fixed wrapper is positioned against the
                            // viewport, so its document-space box moves with the scroll position.
                            // html2canvas captures from the document origin, and the gap between
                            // that origin and the element was rasterised as leading blank pages —
                            // a plan card sits far enough down My Tools that a real export came
                            // back with a blank page 1 and a blank top half of page 2. An
                            // absolute wrapper is anchored to the document, so it is always at 0.
                            var holder = document.createElement('div');
                            holder.style.cssText = 'position:absolute; left:-10000px; top:0; width:794px;'; // 794px ≈ A4 at 96dpi
                            var el = buildMealPlanDocument(plan);
                            holder.appendChild(el);
                            document.body.appendChild(holder);

                            var cleanup = function () {
                                if (holder.parentNode) { holder.parentNode.removeChild(holder); }
                                btn.disabled = false;
                                btn.textContent = label;
                            };

                            whenImagesSettled(el).then(function () {
                                return html2pdf().set({
                                    margin:      [10, 0, 12, 0], // mm — the inner div supplies side padding
                                    filename:    pdfFilename(plan),
                                    image:       { type: 'jpeg', quality: 0.95 },
                                    // scrollX/scrollY default to the page's current scroll offset;
                                    // pinning them to 0 is the second half of the blank-leading-page
                                    // fix above, and covers html2pdf re-parenting the clone into its
                                    // own fixed overlay before html2canvas ever sees it.
                                    html2canvas: { scale: 2, useCORS: true, allowTaint: false, backgroundColor: '#FFFFFF', logging: false, scrollX: 0, scrollY: 0 },
                                    jsPDF:       { unit: 'mm', format: 'a4', orientation: 'portrait', compress: true },
                                    pagebreak:   { mode: ['css', 'legacy'], avoid: '.pdf-block' }
                                }).from(el).toPdf().get('pdf').then(function (pdf) {
                                    // Page numbers, added after layout so the count is known.
                                    var total = pdf.internal.getNumberOfPages();
                                    for (var i = 1; i <= total; i++) {
                                        pdf.setPage(i);
                                        pdf.setFontSize(8);
                                        pdf.setTextColor(148, 163, 184);
                                        pdf.text(
                                            'Page ' + i + ' of ' + total,
                                            pdf.internal.pageSize.getWidth() / 2,
                                            pdf.internal.pageSize.getHeight() - 5,
                                            { align: 'center' }
                                        );
                                    }
                                }).save();
                            }).then(cleanup, function (err) {
                                cleanup();
                                // Say what happened rather than leaving a dead button — the old
                                // version had no failure path at all.
                                alert('Sorry, the PDF could not be generated. Please try again.');
                                if (window.console) { console.error('Meal plan PDF failed', err); }
                            });
                        }

                        document.addEventListener('click', function (e) {
                            if (!e.target.closest) { return; }
                            var viewBtn = e.target.closest('.btn-view-meal-plan');
                            if (viewBtn) { window.openMealPlanModal(viewBtn); return; }
                            var pdfBtn = e.target.closest('.vance-mp-pdf');
                            if (pdfBtn) { downloadMealPlanPDF(pdfBtn); return; }
                            var toggle = e.target.closest('.vance-mp-toggle');
                            if (toggle) { toggleMealPlanPanel(toggle); return; }
                        });
                        // Click the backdrop to dismiss, same as clicking Close.
                        var overlay = document.getElementById('meal-plan-modal');
                        if (overlay) {
                            overlay.addEventListener('click', function (e) {
                                if (e.target === overlay) { window.closeMealPlanModal(); }
                            });
                        }
                    })();

                    // Every outcome resets the button and says what happened. The
                    // previous success-only callback meant a failed request (the
                    // save handler used to 403 on a nonce mismatch) left this
                    // stuck on "Saving..." with nothing shown to the user.
                    jQuery('#dashboard-additional-details-form').on('submit', function(e) {
                        e.preventDefault();
                        const btn = jQuery(this).find('button[type="submit"]');
                        const msg = jQuery('#dashboard-additional-details-msg');
                        const reset = () => btn.prop('disabled', false).text('Save notes').css('background', '#F1F5F9').css('color', '#475569');
                        const say = (text, ok) => msg.text(text).css('color', ok ? '#047857' : '#B91C1C').show();

                        msg.hide();
                        btn.prop('disabled', true).text('Saving...');

                        jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', jQuery(this).serialize())
                            .done(function(res) {
                                if (res && res.success) {
                                    btn.text('Saved ✓').css('background', '#D1FAE5').css('color', '#065F46');
                                    say('Your notes have been saved.', true);
                                    setTimeout(reset, 2200);
                                } else {
                                    say((res && res.data) ? String(res.data) : 'Could not save, please try again.', false);
                                    reset();
                                }
                            })
                            .fail(function(xhr) {
                                say('Could not save (error ' + xhr.status + '). Please refresh the page and try again.', false);
                                reset();
                            });
                    });
                    </script>
                <?php break;

                case 'tools':
                    // Mirror the public /tools-resources/ card grid so logged-in users
                    // see the same tool catalogue with consistent brand styling. Cards
                    // link to the per-tool wrapper pages; saving results from the
                    // wrappers is now logged-in-aware (see vance_save_tool_result).
                    $dash_tools = array(
                        array( 'slug' => 'healthcare-quiz',        'page_url' => '/healthcare-quiz/',         'name' => 'Gastro Health Survey',        'tag' => 'Self-Assessment',  'desc' => 'A short, evidence-based questionnaire covering symptom patterns, dietary triggers, and lifestyle factors. Get an instant summary you can share with your clinician.', 'colors' => array( '#78bfbf', '#aedbdb', '#008080' ), 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.5M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z"/>' ),
                        array( 'slug' => 'ibd-recipes',            'page_url' => '/ibd-recipies/',            'name' => 'IBD Recipes & Meal Planner','tag' => 'Meal Planning', 'desc' => 'Browse EPA-rich, gut-friendly recipes with full nutrition data. Build weekly meal plans freely, saving plans prompts a quick signup.', 'colors' => array( '#def4f4', '#aedbdb', '#008080' ), 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9l9-7 9 7v11a2 2 0 01-2 2h-4a2 2 0 01-2-2v-4a2 2 0 00-2-2H10a2 2 0 00-2 2v4a2 2 0 01-2 2H2V9z" transform="translate(0,-1)"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14h8M8 11h8" />' ),
                        array( 'slug' => 'malnutrition-calculator','page_url' => '/malnutrition-calculator/','name' => 'Malnutrition Calculator','tag' => 'IBD Screening',    'desc' => 'Clinically-grounded 11-step malnutrition risk screener for IBD patients. Combines MUST, IBD-NST, and GLIM criteria into a single, actionable score.', 'colors' => array( '#78bfbf', '#5fa3a3', '#ffffff' ), 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>' ),
                    );
                    ?>
                    <style>
                        .my-tools-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 28px; }
                        .my-tools-grid .tool-card {
                            display: flex; flex-direction: column; padding: 32px; background: white; border-radius: 0;
                            box-shadow: 0 4px 16px rgba(10,25,41,0.06); border-top: 4px solid #008080;
                            text-decoration: none; color: inherit; transition: transform 0.18s, box-shadow 0.18s;
                        }
                        .my-tools-grid .tool-card:hover { transform: translateY(-4px); box-shadow: 0 12px 32px rgba(10,25,41,0.10); }
                        .my-tools-grid .tool-card__head { display: flex; gap: 16px; align-items: flex-start; margin-bottom: 20px; }
                        .my-tools-grid .tool-card__icon { flex-shrink: 0; width: 56px; height: 56px; border-radius: 12px; display: flex; align-items: center; justify-content: center; }
                        .my-tools-grid .tool-card__tag  { display: inline-block; font-size: 11px; font-weight: 700; letter-spacing: 0.5px; text-transform: uppercase; color: #008080; margin-bottom: 4px; }
                        .my-tools-grid .tool-card__title{ font-size: 19px; color: #0F172A; margin: 0; line-height: 1.3; }
                        .my-tools-grid .tool-card__desc { color: #64748B; font-size: 14px; margin: 0 0 20px 0; line-height: 1.6; flex: 1; }
                        .my-tools-grid .tool-card__cta  { font-size: 14px; font-weight: 600; color: #008080; display: inline-flex; align-items: center; gap: 6px; }
                        @media (max-width: 768px) { .my-tools-grid { grid-template-columns: 1fr; } }
                    </style>

                    <div class="my-tools-grid">
                        <?php foreach ( $dash_tools as $tool ) : ?>
                            <a class="tool-card" href="<?php echo esc_url( $tool['page_url'] ); ?>" style="border-top-color: <?php echo esc_attr( $tool['colors'][0] ); ?>;">
                                <div class="tool-card__head">
                                    <div class="tool-card__icon" style="background: linear-gradient(135deg, <?php echo esc_attr( $tool['colors'][0] ); ?>, <?php echo esc_attr( $tool['colors'][1] ); ?>);">
                                        <svg width="28" height="28" fill="none" stroke="<?php echo esc_attr( $tool['colors'][2] ); ?>" viewBox="0 0 24 24"><?php echo $tool['icon']; ?></svg>
                                    </div>
                                    <div>
                                        <span class="tool-card__tag"><?php echo esc_html( $tool['tag'] ); ?></span>
                                        <h3 class="tool-card__title"><?php echo esc_html( $tool['name'] ); ?></h3>
                                    </div>
                                </div>
                                <p class="tool-card__desc"><?php echo esc_html( $tool['desc'] ); ?></p>
                                <span class="tool-card__cta">Open <?php echo esc_html( $tool['name'] ); ?> →</span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php break;

                case 'reading-list': ?>
                    <div class="dash-card">
                         <?php if(empty($bookmarks)): ?>
                            <div style="text-align:center; padding:48px; background:#F8FAFC; border:1px dashed #E2E8F0; border-radius:0;">
                                <p style="color:#64748B;">Your reading list is currently empty.</p>
                                <a href="/" style="color:<?php echo $theme_primary; ?>; font-weight:600;">Browse Articles</a>
                            </div>
                         <?php else: ?>
                            <div class="dash-list">
                                <?php 
                                $b_query = new WP_Query(array('post__in' => array_reverse($bookmarks), 'post_type' => 'any', 'posts_per_page' => -1, 'orderby' => 'post__in'));
                                while($b_query->have_posts()): $b_query->the_post();
                                $p_link = get_permalink();
                                $p_title = get_the_title();
                                ?>
                                <div class="list-item" style="padding:16px 0;">
                                    <div style="display:flex; gap:16px; align-items:center; flex:1;">
                                        <div style="width:64px; height:64px; background:#F1F5F9; border-radius:0; overflow:hidden; flex-shrink:0;">
                                            <?php echo get_the_post_thumbnail(get_the_ID(), 'medium', array('style'=>'width:100%;height:100%;object-fit:cover;')); ?>
                                        </div>
                                        <div>
                                            <?php // Plain text, not a link — reading happens through Read Now. ?>
                                            <div class="item-title"><?php the_title(); ?></div>
                                            <div class="item-meta"><?php echo get_the_date('M j, Y'); ?> • <?php echo get_post_type(); ?></div>
                                        </div>
                                    </div>
                                    <div class="rl-actions">
                                        <button type="button" class="rl-btn rl-btn--primary rl-read"
                                                data-post-id="<?php echo (int) get_the_ID(); ?>">Read Now</button>
                                        <a href="<?php echo esc_url($p_link); ?>" class="rl-btn" target="_blank" rel="noopener">Open in New Tab</a>
                                        <button type="button" class="rl-btn rl-copy" data-url="<?php echo esc_attr($p_link); ?>">Copy Link</button>
                                        <button type="button" class="rl-btn rl-btn--text" onclick="deleteBookmark(<?php echo (int) get_the_ID(); ?>)">Remove</button>
                                    </div>
                                </div>
                                <?php endwhile; wp_reset_postdata(); ?>
                            </div>
                         <?php endif; ?>
                    </div>

                    <!-- Minimalist article reader -->
                    <div id="rl-reader" class="rl-reader" role="dialog" aria-modal="true" aria-labelledby="rl-reader-title">
                        <div class="rl-reader__panel">
                            <div class="rl-reader__head">
                                <div>
                                    <h2 class="rl-reader__title" id="rl-reader-title">Loading…</h2>
                                    <p class="rl-reader__meta" id="rl-reader-meta"></p>
                                </div>
                                <button type="button" class="rl-reader__close" data-rl-close aria-label="Close reader">&times;</button>
                            </div>
                            <div class="rl-reader__body" id="rl-reader-body">
                                <div class="rl-reader__state">Loading article…</div>
                            </div>
                            <div class="rl-reader__foot">
                                <a href="#" class="rl-btn" id="rl-reader-open" target="_blank" rel="noopener">Open in New Tab</a>
                                <button type="button" class="rl-btn rl-btn--primary" data-rl-close>Close</button>
                            </div>
                        </div>
                    </div>

                    <script>
                    (function () {
                        var ajaxUrl = <?php echo wp_json_encode(admin_url('admin-ajax.php')); ?>;
                        var nonce   = <?php echo wp_json_encode(wp_create_nonce('vance_dashboard_nonce')); ?>;

                        var reader  = document.getElementById('rl-reader');
                        if (!reader) { return; }
                        var titleEl = document.getElementById('rl-reader-title');
                        var metaEl  = document.getElementById('rl-reader-meta');
                        var bodyEl  = document.getElementById('rl-reader-body');
                        var openEl  = document.getElementById('rl-reader-open');
                        var lastFocus = null;
                        // Article bodies are fetched once per session — reopening the
                        // same piece is instant and costs no second round trip.
                        var cache = {};

                        function setState(msg) {
                            bodyEl.innerHTML = '';
                            var d = document.createElement('div');
                            d.className = 'rl-reader__state';
                            d.textContent = msg;
                            bodyEl.appendChild(d);
                        }

                        function open() {
                            lastFocus = document.activeElement;
                            reader.classList.add('is-open');
                            // Stop the page behind scrolling while the reader owns the screen.
                            document.body.style.overflow = 'hidden';
                            var close = reader.querySelector('[data-rl-close]');
                            if (close) { close.focus(); }
                        }

                        function close() {
                            reader.classList.remove('is-open');
                            document.body.style.overflow = '';
                            if (lastFocus && lastFocus.focus) { lastFocus.focus(); }
                        }

                        function render(data) {
                            titleEl.textContent = data.title || 'Article';
                            metaEl.textContent  = [data.date, data.type].filter(Boolean).join(' • ');
                            openEl.href = data.url || '#';
                            bodyEl.innerHTML = '';
                            bodyEl.scrollTop = 0;
                            if (data.image) {
                                var img = document.createElement('img');
                                img.className = 'rl-reader__hero';
                                img.src = data.image;
                                img.alt = '';
                                bodyEl.appendChild(img);
                            }
                            var wrap = document.createElement('div');
                            // Server-side wp_kses_post has already stripped scripts and
                            // event handlers; this is the same content single.php prints.
                            wrap.innerHTML = data.content || '';
                            bodyEl.appendChild(wrap);
                        }

                        function load(postId) {
                            titleEl.textContent = 'Loading…';
                            metaEl.textContent  = '';
                            openEl.href = '#';
                            setState('Loading article…');
                            open();

                            if (cache[postId]) { render(cache[postId]); return; }

                            var fd = new FormData();
                            fd.append('action', 'vance_read_article');
                            fd.append('nonce', nonce);
                            fd.append('post_id', postId);

                            fetch(ajaxUrl, { method: 'POST', credentials: 'same-origin', body: fd })
                                .then(function (r) { return r.json(); })
                                .then(function (res) {
                                    if (!res || !res.success) {
                                        var m = (res && res.data && res.data.message) || 'This article could not be loaded.';
                                        titleEl.textContent = 'Unavailable';
                                        setState(m);
                                        return;
                                    }
                                    cache[postId] = res.data;
                                    render(res.data);
                                })
                                .catch(function () {
                                    titleEl.textContent = 'Unavailable';
                                    setState('This article could not be loaded. Please check your connection and try again.');
                                });
                        }

                        document.addEventListener('click', function (e) {
                            if (!e.target.closest) { return; }

                            var readBtn = e.target.closest('.rl-read');
                            if (readBtn) {
                                e.preventDefault();
                                load(readBtn.getAttribute('data-post-id'));
                                return;
                            }

                            var copyBtn = e.target.closest('.rl-copy');
                            if (copyBtn) {
                                e.preventDefault();
                                var url = copyBtn.getAttribute('data-url') || '';
                                var done = function () {
                                    // Feedback in the button beats an alert() that has to be
                                    // dismissed before the next row can be copied.
                                    var was = copyBtn.textContent;
                                    copyBtn.textContent = 'Copied ✓';
                                    setTimeout(function () { copyBtn.textContent = was; }, 1600);
                                };
                                if (navigator.clipboard && navigator.clipboard.writeText) {
                                    navigator.clipboard.writeText(url).then(done, function () { window.prompt('Copy this link:', url); });
                                } else {
                                    window.prompt('Copy this link:', url);
                                }
                                return;
                            }

                            if (e.target.closest('[data-rl-close]')) { e.preventDefault(); close(); return; }
                            if (e.target === reader) { close(); }
                        });

                        document.addEventListener('keydown', function (e) {
                            if (e.key === 'Escape' && reader.classList.contains('is-open')) { close(); }
                        });
                    })();
                    </script>
                <?php break;

                case 'courses': ?>
                     <div class="dash-card">
                        <div style="text-align:center; padding:48px; background:#F8FAFC; border:1px dashed #E2E8F0; border-radius:0;">
                            <h3 style="color:#0F172A; margin:0 0 10px 0;">No active courses</h3>
                            <p style="color:#64748B; margin-bottom:20px;">Subscribe to professional medical courses to see them here.</p>
                            <button class="btn-primary" style="background:<?php echo $theme_primary; ?>; color:white; border:none; padding:10px 24px; border-radius:0; cursor:pointer; font-weight:600;">Browse Catalog</button>
                        </div>
                    </div>
                <?php break;

                case 'searches':
                    $searches      = get_user_meta($current_user->ID, '_sla_saved_searches', true) ?: array();
                    $searches_safe = is_array($searches) ? $searches : array();
                    // The Discovery Suite filter modal, reused verbatim from the
                    // homepage tool widget so the two cannot drift apart. Its form
                    // is given target="_blank" here only: results open in a new tab
                    // so the dashboard stays put behind them.
                    $has_filters_modal = function_exists('vance_tool_widget_modal')
                        && function_exists('vance_tw_render_content_filters_body');
                    if ($has_filters_modal) { vance_tool_widgets_emit_modal_css_once(); }
                    ?>
                    <div class="dash-card">
                        <?php if ($has_filters_modal): ?>
                            <div style="display:flex; justify-content:flex-end; margin-bottom:16px;">
                                <button type="button" class="rl-btn rl-btn--primary" data-vance-tw-open="vance-tw-modal-new-search">+ New Search</button>
                            </div>
                        <?php endif; ?>

                        <?php if(empty($searches_safe)): ?>
                             <div style="text-align:center; padding:48px; background:#F8FAFC; border:1px dashed #E2E8F0; border-radius:0;">
                                <p style="color:#64748B; margin:0 0 16px;">You haven't saved any searches yet.</p>
                                <?php if ($has_filters_modal): ?>
                                    <button type="button" class="rl-btn rl-btn--primary" data-vance-tw-open="vance-tw-modal-new-search">Start a new search</button>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="dash-list">
                                <?php foreach(array_reverse($searches_safe) as $s): ?>
                                <div class="list-item" style="padding:16px 0;">
                                    <div style="flex:1;">
                                        <div class="item-title"><?php echo esc_html($s['name']); ?></div>
                                        <div class="item-meta">Saved on <?php echo date('M j, Y', strtotime($s['date'])); ?></div>
                                    </div>
                                    <div class="rl-actions">
                                        <a href="<?php echo esc_url($s['url']); ?>" class="rl-btn rl-btn--primary" target="_blank" rel="noopener">Run Search</a>
                                        <button type="button" class="rl-btn rl-btn--text" onclick="deleteSearch('<?php echo esc_js($s['id']); ?>')">Delete</button>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php
                    if ($has_filters_modal) {
                        vance_tool_widget_modal(
                            'vance-tw-modal-new-search',
                            'Discovery Suite',
                            function () { vance_tw_render_content_filters_body('_blank'); },
                            'Vance Medical Hub · New Search',
                            'Narrow the knowledge base down to the articles, studies and guides that apply to you. Results open in a new tab.'
                        );
                    }
                    ?>
                <?php break;

                case 'notes': ?>
                    <div class="dash-card">
                         <?php 
                         $my_notes = get_user_meta($current_user->ID, '_sla_user_notes', true) ?: array();
                         if(empty($my_notes)): ?>
                             <div style="text-align:center; padding:48px; background:#F8FAFC; border:1px dashed #E2E8F0; border-radius:0;">
                                <p style="color:#64748B; margin-bottom:16px;">You don't have any notes yet.</p>
                                <a href="/my-notes/?new=1" class="btn-primary" style="background:<?php echo $theme_primary; ?>; color:white; text-decoration:none; padding:10px 20px; border-radius:0; font-weight:600;">Create First Note</a>
                            </div>
                         <?php else: ?>
                            <div class="dash-list">
                                <?php
                                $notes_safe = is_array($my_notes) ? $my_notes : array();

                                // Payload for the read-only View modal. Note bodies are HTML
                                // (the /my-notes/ editor is contenteditable and saves innerHTML,
                                // and "Add to Note" appends blockquote markup), so run each one
                                // through wp_kses_post before it reaches the page — same
                                // treatment the PDF/Print view gives it.
                                $notes_view_data = array();
                                foreach ($notes_safe as $n) {
                                    if (empty($n['id'])) { continue; }
                                    $notes_view_data[(string) $n['id']] = array(
                                        'title' => ($n['title'] !== '' ? $n['title'] : 'Untitled Note'),
                                        'date'  => !empty($n['date']) ? date('M j, Y \a\t H:i', strtotime($n['date'])) : '',
                                        'html'  => wp_kses_post(isset($n['content']) ? $n['content'] : ''),
                                    );
                                }

                                foreach(array_reverse($notes_safe) as $note): ?>
                                <div class="list-item" style="padding:16px 0;">
                                    <div style="flex:1;">
                                        <div class="item-title"><?php echo esc_html($note['title'] ?: 'Untitled Note'); ?></div>
                                        <div class="item-meta">Last edited on <?php echo date('M j, Y', strtotime($note['date'])); ?></div>
                                    </div>
                                    <div style="display:flex; gap:12px; align-items:center;">
                                        <button type="button" class="vance-btn-glass vance-btn--sm vance-note-view"
                                                onclick="openNoteView('<?php echo esc_js($note['id']); ?>', this)"
                                                aria-label="View note <?php echo esc_attr($note['title'] ?: 'Untitled Note'); ?>">
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                                            </svg>
                                            View
                                        </button>
                                        <a href="?print_note=<?php echo $note['id']; ?>" target="_blank" class="card-link" style="color:#0EA5E9;">PDF/Print</a>
                                        <a href="/my-notes/?id=<?php echo $note['id']; ?>" class="card-link">Edit</a>
                                        <button onclick="deleteNote('<?php echo $note['id']; ?>')" style="color:#EF4444; border:none; background:none; cursor:pointer; font-size:13px; font-weight:600;">Delete</button>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>

                            <style>
                                .vance-note-view { display:inline-flex; align-items:center; gap:7px; cursor:pointer; }
                                .vance-note-view svg { flex:0 0 auto; }
                                #vance-note-modal-panel { animation:vanceNotePop .34s cubic-bezier(.2,.8,.2,1); }
                                @keyframes vanceNotePop { from { opacity:0; transform:translateY(14px); } to { opacity:1; transform:none; } }
                                .vance-note-close {
                                    position:absolute; top:18px; right:18px; width:36px; height:36px;
                                    display:flex; align-items:center; justify-content:center;
                                    font-size:26px; line-height:1; color:#475569; cursor:pointer;
                                    background:rgba(255,255,255,0.6); border:1px solid rgba(0,128,128,0.18);
                                    border-radius:0; transition:background .2s ease, color .2s ease;
                                }
                                .vance-note-close:hover { background:#008080; color:#fff; }
                                .vance-note-close:focus-visible { outline:3px solid var(--primary-pale, #B2D8D8); outline-offset:2px; }
                                /* Long free-text bodies: cap the measure for readability and let the
                                   body scroll inside the panel rather than the panel growing off-screen. */
                                #vance-note-modal-body {
                                    font-size:16px; line-height:1.7; color:#334155;
                                    max-width:68ch; overflow-y:auto; overscroll-behavior:contain;
                                    max-height:min(58vh, 620px); padding-right:6px; word-wrap:break-word; overflow-wrap:anywhere;
                                }
                                #vance-note-modal-body > *:first-child { margin-top:0; }
                                #vance-note-modal-body > *:last-child { margin-bottom:0; }
                                #vance-note-modal-body img { max-width:100%; height:auto; }
                                #vance-note-modal-body a { color:#008080; }
                                @media (prefers-reduced-motion: reduce) {
                                    #vance-note-modal-panel { animation:none !important; }
                                    .vance-note-close { transition:none; }
                                }
                            </style>

                            <div id="vance-note-view-modal" class="vance-modal vance-glass-scrim" role="dialog" aria-modal="true" aria-labelledby="vance-note-modal-title"
                                 style="display:none; position:fixed; inset:0; z-index:10000; overflow-y:auto; padding:20px; align-items:center; justify-content:center;">
                                <div class="vance-glass-panel" id="vance-note-modal-panel" tabindex="-1"
                                     style="max-width:720px; width:100%; padding:40px; position:relative;">
                                    <button type="button" class="vance-note-close" onclick="closeNoteView()" aria-label="Close note">&times;</button>
                                    <h3 id="vance-note-modal-title" class="card-title" style="margin:0 0 4px; padding-right:44px; font-family:'Outfit'; font-size:24px;"></h3>
                                    <p id="vance-note-modal-date" style="margin:0 0 24px; font-size:13px; color:#64748B;"></p>
                                    <div id="vance-note-modal-body"></div>
                                    <div style="margin-top:24px; padding-top:20px; border-top:1px solid #E2E8F0; display:flex; gap:12px; flex-wrap:wrap;">
                                        <a id="vance-note-modal-edit" href="#" class="vance-btn-inverted vance-btn--sm">Edit this note</a>
                                        <a id="vance-note-modal-print" href="#" target="_blank" class="vance-btn-glass vance-btn--sm">PDF/Print</a>
                                    </div>
                                </div>
                            </div>

                            <script>
                            var VANCE_NOTES = <?php echo json_encode($notes_view_data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
                            (function () {
                                var modal = document.getElementById('vance-note-view-modal');
                                var panel = document.getElementById('vance-note-modal-panel');
                                var lastTrigger = null;

                                window.openNoteView = function (id, trigger) {
                                    var d = VANCE_NOTES[id];
                                    if (!d) { return; }
                                    lastTrigger = trigger || null;

                                    document.getElementById('vance-note-modal-title').textContent = d.title;
                                    document.getElementById('vance-note-modal-date').textContent = d.date ? 'Last edited on ' + d.date : '';

                                    // Already sanitised with wp_kses_post server-side; the note body is
                                    // genuine HTML so it is injected rather than escaped.
                                    var body = document.getElementById('vance-note-modal-body');
                                    body.innerHTML = d.html && d.html.trim()
                                        ? d.html
                                        : '<p style="margin:0; color:#94A3B8;">This note is empty.</p>';

                                    document.getElementById('vance-note-modal-edit').href  = '/my-notes/?id=' + encodeURIComponent(id);
                                    document.getElementById('vance-note-modal-print').href = '?print_note=' + encodeURIComponent(id);

                                    modal.style.display = 'flex';
                                    modal.classList.add('is-open');
                                    modal.setAttribute('aria-hidden', 'false');
                                    document.body.style.overflow = 'hidden';
                                    // Must come after the panel is displayed — scrollTop is a no-op
                                    // on a display:none element, which would strand a reopened long
                                    // note at the previous reader's scroll position.
                                    body.scrollTop = 0;
                                    panel.focus();
                                };

                                window.closeNoteView = function () {
                                    modal.style.display = 'none';
                                    modal.classList.remove('is-open');
                                    modal.setAttribute('aria-hidden', 'true');
                                    document.body.style.overflow = '';
                                    // Hand focus back to the row the user came from.
                                    if (lastTrigger && document.contains(lastTrigger) && typeof lastTrigger.focus === 'function') {
                                        lastTrigger.focus();
                                    }
                                    lastTrigger = null;
                                };

                                // Click the scrim (not the panel) to dismiss.
                                modal.addEventListener('click', function (e) {
                                    if (e.target === modal) { window.closeNoteView(); }
                                });

                                document.addEventListener('keydown', function (e) {
                                    if (e.key === 'Escape' && modal.style.display === 'flex') { window.closeNoteView(); }
                                });
                            })();
                            </script>
                         <?php endif; ?>
                    </div>
                <?php break;

                case 'ai-chats': ?>
                    <div class="dash-card">
                         <?php 
                         $ai_chats = get_user_meta($current_user->ID, '_sla_saved_chats', true);
                         if (!is_array($ai_chats)) $ai_chats = array();
                         if(empty($ai_chats)): ?>
                             <div style="text-align:center; padding:48px; background:#F8FAFC; border:1px dashed #E2E8F0; border-radius:0;">
                                <p style="color:#64748B; margin-bottom:16px;">No VANCE-Ai conversations yet. Anything you ask is saved here automatically.</p>
                                <?php // Opens the shared VANCE-Ai modal in place rather than navigating away. ?>
                                <button type="button" data-vance-askai-open="new" class="btn-primary" style="display:inline-flex; align-items:center; background:<?php echo $theme_primary; ?>; color:white; border:none; text-decoration:none; padding:10px 20px; min-height:44px; border-radius:0; font-weight:600; font-family:inherit; font-size:inherit; cursor:pointer;">Start New Ai Chat</button>
                            </div>
                         <?php else: ?>
                             <div class="dash-list">
                                <?php foreach(array_reverse($ai_chats) as $chat): 
                                    $chat_json = wp_json_encode($chat);
                                    // Make sure title doesn't overflow incredibly long if it was saved improperly
                                    $display_title = !empty($chat['title']) ? wp_trim_words($chat['title'], 8, '...') : 'VANCE-Ai conversation';
                                ?>
                                <div class="list-item" style="padding:16px 0;">
                                    <div style="flex:1;">
                                        <div class="item-title"><?php echo esc_html($display_title); ?></div>
                                        <div class="item-meta"><?php
                                            // Conversations are auto-saved and updated in place as
                                            // the exchange continues, so show both stamps once they
                                            // differ. Legacy entries have no 'updated' key.
                                            $chat_started = !empty($chat['date'])    ? strtotime($chat['date'])    : 0;
                                            $chat_updated = !empty($chat['updated']) ? strtotime($chat['updated']) : 0;
                                            if ($chat_started && $chat_updated && date('Y-m-d', $chat_started) !== date('Y-m-d', $chat_updated)) {
                                                echo 'Started ' . esc_html(date('M j, Y', $chat_started)) . ' &middot; updated ' . esc_html(date('M j, Y', $chat_updated));
                                            } elseif ($chat_started) {
                                                echo 'Saved on ' . esc_html(date('M j, Y', $chat_started));
                                            }
                                        ?></div>
                                    </div>
                                    <div style="display:flex; gap:12px;">
                                        <button class="card-link btn-view-ai-chat" data-chat="<?php echo esc_attr($chat_json); ?>" style="background:none; border:none; font-family:inherit; cursor:pointer; font-weight:600; color:<?php echo $theme_primary; ?>;">View Conversation</button>
                                        <?php // Saves the whole exchange into one of the user's notes. ?>
                                        <button type="button" class="card-link btn-chat-to-note" data-vn-open data-chat="<?php echo esc_attr($chat_json); ?>" style="background:none; border:none; font-family:inherit; cursor:pointer; font-weight:600; color:#0EA5E9;">Add to Note</button>
                                        <button onclick="renameChat('<?php echo esc_js($chat['id']); ?>', '<?php echo esc_js($display_title); ?>')" style="color:#0EA5E9; border:none; background:none; cursor:pointer; font-size:13px; font-weight:600;">Rename</button>
                                        <button onclick="deleteChat('<?php echo esc_js($chat['id']); ?>')" style="color:#EF4444; border:none; background:none; cursor:pointer; font-size:13px; font-weight:600;">Delete</button>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                             </div>
                             <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    jQuery('.btn-view-ai-chat').on('click', function() {
                                        var chatData = JSON.parse(jQuery(this).attr('data-chat'));
                                        viewChat(chatData);
                                    });
                                });

                                function renameChat(id, currentTitle) {
                                    var newTitle = prompt("Enter a new name for this chat:", currentTitle);
                                    if (newTitle === null || newTitle.trim() === '' || newTitle.trim() === currentTitle) return;
                                    
                                    jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                                        action: 'vance_rename_chat',
                                        id: id,
                                        title: newTitle.trim(),
                                        nonce: '<?php echo wp_create_nonce("vance_dashboard_nonce"); ?>'
                                    }, function(res) {
                                        if(res.success) {
                                            location.reload();
                                        } else {
                                            alert(res.data);
                                        }
                                    });
                                }
                             </script>
                         <?php endif; ?>
                    </div>

                    <!-- Chat Viewer Modal -->
                    <div id="chat-modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:10001; align-items:center; justify-content:center; padding:20px;">
                        <div style="background:white; width:100%; max-width:800px; max-height:90vh; border-radius:0; display:flex; flex-direction:column; overflow:hidden; box-shadow: 0 20px 50px rgba(0,0,0,0.2);">
                            <div style="padding:24px; border-bottom:1px solid #E2E8F0; display:flex; justify-content:space-between; align-items:center; background:white;">
                                <div>
                                    <h3 id="modal-chat-title" style="margin:0; font-family:'Outfit'; font-size:20px; color:#0A1929;">VANCE-Ai Transcript</h3>
                                    <p id="modal-chat-date" style="margin:4px 0 0 0; font-size:12px; color:#64748B;"></p>
                                </div>
                                <button onclick="closeChatModal()" style="font-size:32px; border:none; background:none; cursor:pointer; color:#64748B; line-height:1;">×</button>
                            </div>
                            <div id="modal-chat-content" style="flex:1; overflow-y:auto; padding:32px; background:#F8FAFC; display:flex; flex-direction:column; gap:24px;">
                                <!-- Messages will go here -->
                            </div>
                            <div style="padding:20px; border-top:1px solid #E2E8F0; background:white; display:flex; justify-content:space-between; align-items:center; gap:12px;">
                                <button type="button" id="chat-modal-to-note" class="rl-btn" data-vn-open>Add whole chat to a note</button>
                                <button onclick="closeChatModal()" class="btn-primary" style="background:<?php echo $theme_primary; ?>; color:white; border:none; padding:10px 24px; border-radius:0; cursor:pointer; font-weight:600;">Close</button>
                            </div>
                        </div>
                    </div>

                    <script>
                    /**
                     * "Add to Note" for VANCE-Ai conversations.
                     *
                     * Two entry points, one saver: a button on each session row that
                     * captures the whole exchange, and a small button under every
                     * VANCE-Ai answer inside the transcript modal that captures just
                     * that answer. Both post to the same vance_append_to_note handler
                     * the article-highlight pill already uses, so a chat excerpt and
                     * an article excerpt land in notes looking the same.
                     */
                    window.VanceNoteSaver = (function () {
                        var ajaxUrl = <?php echo wp_json_encode(admin_url('admin-ajax.php')); ?>;
                        var nonce   = <?php echo wp_json_encode(wp_create_nonce('vance_dashboard_nonce')); ?>;
                        var notesUrl = <?php echo wp_json_encode(home_url('/my-notes/')); ?>;

                        var panel = null;
                        var notes = null;   // cached across opens; invalidated after a save

                        function esc(s) {
                            return String(s == null ? '' : s)
                                .replace(/&/g, '&amp;').replace(/</g, '&lt;')
                                .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
                        }

                        // Plain text -> paragraphs. The transcript is stored as text,
                        // so it must be escaped before it becomes note HTML.
                        function toParagraphs(text) {
                            return String(text == null ? '' : text)
                                .split(/\n{2,}/)
                                .map(function (p) { return p.trim(); })
                                .filter(Boolean)
                                .map(function (p) { return '<p>' + esc(p).replace(/\n/g, '<br>') + '</p>'; })
                                .join('');
                        }

                        function close() {
                            if (panel && panel.parentNode) { panel.parentNode.removeChild(panel); }
                            panel = null;
                        }

                        function el(tag, cls, text) {
                            var n = document.createElement(tag);
                            if (cls) { n.className = cls; }
                            if (text != null) { n.textContent = text; }
                            return n;
                        }

                        function position(anchor) {
                            var r = anchor.getBoundingClientRect();
                            var top = r.bottom + window.scrollY + 6;
                            var left = r.left + window.scrollX;
                            // Keep it on screen when the anchor sits near the right edge.
                            var maxLeft = window.scrollX + document.documentElement.clientWidth - panel.offsetWidth - 12;
                            panel.style.top = top + 'px';
                            panel.style.left = Math.max(window.scrollX + 12, Math.min(left, maxLeft)) + 'px';
                        }

                        function open(anchor, payload) {
                            close();

                            panel = el('div', 'vn-pick');
                            var head = el('div', 'vn-pick__head');
                            head.appendChild(el('span', null, 'Add to note'));
                            var x = el('button', 'vn-pick__close', '×');
                            x.type = 'button';
                            x.setAttribute('aria-label', 'Close');
                            x.addEventListener('click', close);
                            head.appendChild(x);
                            panel.appendChild(head);

                            var list = el('div', 'vn-pick__list');
                            list.appendChild(el('div', 'vn-pick__empty', 'Loading your notes…'));
                            panel.appendChild(list);

                            var newWrap = el('div', 'vn-pick__new');
                            var input = document.createElement('input');
                            input.type = 'text';
                            input.placeholder = 'Or start a new note…';
                            input.value = payload.suggestedTitle || '';
                            var create = el('button', null, 'Create');
                            create.type = 'button';
                            newWrap.appendChild(input);
                            newWrap.appendChild(create);
                            panel.appendChild(newWrap);

                            var status = el('div', 'vn-pick__status');
                            status.style.display = 'none';
                            panel.appendChild(status);

                            function say(msg, kind) {
                                status.textContent = msg;
                                status.className = 'vn-pick__status' + (kind ? ' vn-pick__status--' + kind : '');
                                status.style.display = '';
                            }

                            function save(targetId, newTitle) {
                                panel.classList.add('is-busy');
                                say('Saving…');
                                var mine = panel;

                                var fd = new FormData();
                                fd.append('action', 'vance_append_to_note');
                                fd.append('nonce', nonce);
                                fd.append('target_id', targetId || '');
                                fd.append('new_title', newTitle || payload.suggestedTitle || 'VANCE-Ai conversation');
                                fd.append('content', payload.html);

                                fetch(ajaxUrl, { method: 'POST', credentials: 'same-origin', body: fd })
                                    .then(function (r) { return r.json(); })
                                    .then(function (res) {
                                        if (panel !== mine) { return; }
                                        panel.classList.remove('is-busy');
                                        if (!res || !res.success) {
                                            say((res && res.data) || 'Could not save to your note.', 'error');
                                            return;
                                        }
                                        notes = null; // titles and dates have moved on
                                        var url = (res.data && res.data.url) || notesUrl;
                                        status.innerHTML = 'Saved. <a href="' + esc(url) + '" style="color:#008080;font-weight:600;">Open note</a>';
                                        status.className = 'vn-pick__status vn-pick__status--ok';
                                        setTimeout(function () { if (panel === mine) { close(); } }, 2600);
                                    })
                                    .catch(function () {
                                        if (panel !== mine) { return; }
                                        panel.classList.remove('is-busy');
                                        say('Could not save to your note. Please try again.', 'error');
                                    });
                            }

                            create.addEventListener('click', function () { save('', input.value.trim()); });
                            input.addEventListener('keydown', function (e) {
                                if (e.key === 'Enter') { e.preventDefault(); save('', input.value.trim()); }
                            });

                            document.body.appendChild(panel);
                            position(anchor);

                            function renderList() {
                                list.innerHTML = '';
                                if (!notes || !notes.length) {
                                    list.appendChild(el('div', 'vn-pick__empty', 'No notes yet — create one below.'));
                                    return;
                                }
                                notes.forEach(function (n) {
                                    var b = el('button', 'vn-pick__item');
                                    b.type = 'button';
                                    b.appendChild(document.createTextNode(n.title));
                                    if (n.date) {
                                        b.appendChild(el('span', null, new Date(n.date.replace(' ', 'T')).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' })));
                                    }
                                    b.addEventListener('click', function () { save(n.id, ''); });
                                    list.appendChild(b);
                                });
                                position(anchor);
                            }

                            if (notes) { renderList(); return; }

                            var mine = panel;
                            var fd = new FormData();
                            fd.append('action', 'vance_list_notes');
                            fd.append('nonce', nonce);
                            fetch(ajaxUrl, { method: 'POST', credentials: 'same-origin', body: fd })
                                .then(function (r) { return r.json(); })
                                .then(function (res) {
                                    if (panel !== mine) { return; }
                                    notes = (res && res.success && Array.isArray(res.data)) ? res.data : [];
                                    renderList();
                                })
                                .catch(function () {
                                    if (panel !== mine) { return; }
                                    list.innerHTML = '';
                                    list.appendChild(el('div', 'vn-pick__empty', 'Could not load your notes.'));
                                });
                        }

                        // Dismiss on outside click / Escape.
                        document.addEventListener('mousedown', function (e) {
                            if (!panel) { return; }
                            if (panel.contains(e.target)) { return; }
                            if (e.target.closest && e.target.closest('[data-vn-open]')) { return; }
                            close();
                        });
                        document.addEventListener('keydown', function (e) {
                            if (e.key === 'Escape') { close(); }
                        });

                        function chatDateLabel(chat) {
                            if (!chat || !chat.date) { return ''; }
                            var d = new Date(chat.date.replace(' ', 'T'));
                            return isNaN(d) ? '' : d.toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' });
                        }

                        function attribution(chat) {
                            var when = chatDateLabel(chat);
                            return '<p><em>Saved from your VANCE-Ai conversation' + (when ? ' on ' + esc(when) : '') +
                                   '. VANCE-Ai provides general information, not medical advice.</em></p>';
                        }

                        return {
                            close: close,

                            // Whole conversation, both sides, in order.
                            openForChat: function (anchor, chat) {
                                var title = chat.title || 'VANCE-Ai conversation';
                                var body = '';
                                if (Array.isArray(chat.transcript)) {
                                    body = chat.transcript.map(function (m) {
                                        var who = (m.role === 'user') ? 'You' : 'VANCE-Ai';
                                        return '<p><strong>' + who + ':</strong></p>' + toParagraphs(m.content);
                                    }).join('');
                                } else {
                                    body = toParagraphs(chat.transcript || '');
                                }
                                open(anchor, {
                                    suggestedTitle: title,
                                    html: '<h3>' + esc(title) + '</h3>' + attribution(chat) +
                                          '<blockquote>' + body + '</blockquote>'
                                });
                            },

                            // A single VANCE-Ai answer.
                            openForAnswer: function (anchor, chat, text) {
                                var title = chat.title || 'VANCE-Ai conversation';
                                open(anchor, {
                                    suggestedTitle: title,
                                    html: '<h3>' + esc(title) + '</h3>' + attribution(chat) +
                                          '<blockquote>' + toParagraphs(text) + '</blockquote>'
                                });
                            }
                        };
                    })();

                    // Session rows: save the whole exchange.
                    document.addEventListener('click', function (e) {
                        if (!e.target.closest) { return; }
                        var btn = e.target.closest('.btn-chat-to-note');
                        if (!btn) { return; }
                        e.preventDefault();
                        try {
                            window.VanceNoteSaver.openForChat(btn, JSON.parse(btn.getAttribute('data-chat')));
                        } catch (err) {
                            if (window.console) { console.error('Add to Note failed to read the chat', err); }
                        }
                    });
                    </script>
                <?php break;

                case 'messages':
                    // Full message history — both unread and previously read.
                    $all_msgs = function_exists( 'vance_admin_messages_for_user' )
                        ? vance_admin_messages_for_user( $current_user->ID, true )
                        : array();
                    $unread_count = 0;
                    if ( $all_msgs ) {
                        foreach ( $all_msgs as $m ) {
                            $r = (array) get_post_meta( $m->ID, '_sla_msg_read_by', true );
                            if ( ! in_array( (int) $current_user->ID, array_map( 'intval', $r ), true ) ) $unread_count++;
                        }
                    }
                    $rendered_ids = array();
                    ?>
                    <div class="dash-card" style="background: white; border: 1px solid #E2E8F0; padding: 28px;">
                        <header style="display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 20px;">
                            <h2 style="margin: 0; color: #0F172A; font-size: 22px;">My Messages</h2>
                            <div style="display:flex; align-items:center; gap:12px;">
                                <?php if ( $unread_count > 0 ) : ?>
                                    <span style="background: #008080; color: white; font-size: 12px; font-weight: 700; padding: 4px 10px; border-radius: 12px;"><?php echo (int) $unread_count; ?> new</span>
                                <?php endif; ?>
                                <button type="button" class="rl-btn rl-btn--primary" id="msg-compose-toggle">New message</button>
                            </div>
                        </header>

                        <?php // Compose a new thread to the Vance team. ?>
                        <form id="msg-compose" data-nonce="<?php echo esc_attr( wp_create_nonce( 'vance_msg_user_new' ) ); ?>"
                              style="display:none; margin-bottom:24px; padding:20px; background:#F8FAFC; border:1px solid #E2E8F0;">
                            <h3 style="margin:0 0 4px; font-size:16px; color:#0F172A;">Message the Vance team</h3>
                            <p style="margin:0 0 14px; font-size:12.5px; color:#64748B; line-height:1.55;">
                                We usually reply within two working days, and the reply appears here in My Messages.
                                <strong>Please don't use this for anything urgent or clinical</strong> — this is not a
                                medical service and is not monitored around the clock. For medical advice contact your
                                GP or care team; in an emergency call 999 or NHS 111.
                            </p>
                            <label style="display:block; font-size:12px; font-weight:700; color:#475569; margin-bottom:5px;" for="msg-compose-subject">Subject</label>
                            <input type="text" id="msg-compose-subject" required minlength="3" maxlength="150"
                                   placeholder="What is your message about?"
                                   style="width:100%; padding:10px 12px; border:1px solid #CBD5E1; font-size:13.5px; box-sizing:border-box; font-family:inherit; margin-bottom:14px;">
                            <label style="display:block; font-size:12px; font-weight:700; color:#475569; margin-bottom:5px;" for="msg-compose-body">Message</label>
                            <textarea id="msg-compose-body" required minlength="10" maxlength="4000" rows="6"
                                      placeholder="Write your message… plain text, **bold**, *italic*, and URLs work."
                                      style="width:100%; padding:10px 12px; border:1px solid #CBD5E1; font-size:13.5px; line-height:1.55; box-sizing:border-box; resize:vertical; font-family:inherit;"></textarea>
                            <p style="margin:10px 0 14px; font-size:11.5px; color:#94A3B8; line-height:1.5;">
                                Your name and email address are shared with the Vance team so they can reply. Please do
                                not include health information you would not want held on record — see our
                                <a href="<?php echo esc_url( home_url( '/privacy-policy/' ) ); ?>" target="_blank" rel="noopener" style="color:#008080;">privacy policy</a>.
                            </p>
                            <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                                <button type="submit" class="rl-btn rl-btn--primary">Send message</button>
                                <button type="button" class="rl-btn" id="msg-compose-cancel">Cancel</button>
                                <span id="msg-compose-status" style="font-size:12.5px; color:#64748B;"></span>
                            </div>
                        </form>

                        <?php if ( empty( $all_msgs ) ) : ?>
                            <div style="text-align: center; padding: 48px; background: #F8FAFC; border: 1px dashed #E2E8F0;">
                                <p style="color: #64748B; margin: 0;">No messages yet. The team shares updates and announcements here, and you can start a conversation with <strong>New message</strong> above.</p>
                            </div>
                        <?php else : ?>
                            <div class="vance-msg-list">
                                <?php foreach ( $all_msgs as $m ) :
                                    echo vance_admin_messages_render_with_thread( $m, $current_user->ID );
                                    $rendered_ids[] = $m->ID;
                                endforeach; ?>
                            </div>
                            <?php
                            // Viewing the messages tab marks everything as read.
                            if ( $rendered_ids ) {
                                vance_admin_messages_mark_read( $current_user->ID, $rendered_ids );
                            }
                            ?>
                            <script>
                            // Reply + soft-delete handlers for the messages tab.
                            (function () {
                                var ajaxUrl = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;

                                function setStatus(form, msg, isError) {
                                    var s = form.querySelector('.vance-msg-reply-status');
                                    if (!s) return;
                                    s.textContent = msg || '';
                                    s.style.color = isError ? '#b32d2e' : '#64748b';
                                }

                                // Toggle reply form open/close.
                                document.querySelectorAll('.vance-msg-reply-toggle').forEach(function (btn) {
                                    btn.addEventListener('click', function () {
                                        var thread = btn.closest('.vance-msg-thread');
                                        if (!thread) return;
                                        var form = thread.querySelector('.vance-msg-reply-form');
                                        if (!form) return;
                                        var hidden = form.style.display === 'none' || form.style.display === '';
                                        form.style.display = hidden ? 'block' : 'none';
                                        if (hidden) { var ta = form.querySelector('textarea'); if (ta) ta.focus(); }
                                    });
                                });
                                document.querySelectorAll('.vance-msg-reply-cancel').forEach(function (btn) {
                                    btn.addEventListener('click', function () {
                                        var form = btn.closest('.vance-msg-reply-form');
                                        if (form) form.style.display = 'none';
                                    });
                                });

                                // Submit reply via AJAX.
                                document.querySelectorAll('.vance-msg-reply-form').forEach(function (form) {
                                    form.addEventListener('submit', function (e) {
                                        e.preventDefault();
                                        var msgId = form.getAttribute('data-msg-id');
                                        var nonce = form.getAttribute('data-nonce');
                                        var ta = form.querySelector('textarea');
                                        var body = (ta && ta.value || '').trim();
                                        if (body.length < 3) { setStatus(form, 'Please write a few words.', true); return; }

                                        var fd = new FormData();
                                        fd.append('action', 'vance_msg_user_reply');
                                        fd.append('nonce', nonce);
                                        fd.append('message_id', msgId);
                                        fd.append('body', body);
                                        setStatus(form, 'Sending…', false);

                                        fetch(ajaxUrl, { method: 'POST', credentials: 'same-origin', body: fd })
                                            .then(function (r) { return r.json(); })
                                            .then(function (j) {
                                                if (j && j.success) {
                                                    setStatus(form, 'Reply sent, admins will see it shortly. Refresh to see your reply in the thread.', false);
                                                    if (ta) ta.value = '';
                                                    setTimeout(function () { window.location.reload(); }, 1200);
                                                } else {
                                                    setStatus(form, (j && j.data && j.data.message) || 'Could not send.', true);
                                                }
                                            })
                                            .catch(function () { setStatus(form, 'Network error.', true); });
                                    });
                                });

                                // Soft-delete a message from this user's inbox.
                                document.querySelectorAll('.vance-msg-delete').forEach(function (btn) {
                                    btn.addEventListener('click', function () {
                                        if (!window.confirm('Remove this message from your inbox? Admins will still have a copy.')) return;
                                        var msgId = btn.getAttribute('data-msg-id');
                                        var nonce = btn.getAttribute('data-nonce');
                                        var fd = new FormData();
                                        fd.append('action', 'vance_msg_user_delete');
                                        fd.append('nonce', nonce);
                                        fd.append('message_id', msgId);
                                        btn.disabled = true;
                                        btn.textContent = 'Removing…';

                                        fetch(ajaxUrl, { method: 'POST', credentials: 'same-origin', body: fd })
                                            .then(function (r) { return r.json(); })
                                            .then(function (j) {
                                                if (j && j.success) {
                                                    var thread = btn.closest('.vance-msg-thread');
                                                    if (thread) thread.style.transition = 'opacity 0.25s';
                                                    if (thread) thread.style.opacity = '0';
                                                    setTimeout(function () { if (thread) thread.remove(); }, 280);
                                                } else {
                                                    btn.disabled = false;
                                                    btn.textContent = 'Delete from my inbox';
                                                    alert((j && j.data && j.data.message) || 'Could not remove.');
                                                }
                                            })
                                            .catch(function () {
                                                btn.disabled = false;
                                                btn.textContent = 'Delete from my inbox';
                                                alert('Network error.');
                                            });
                                    });
                                });
                            })();
                            </script>
                        <?php endif; ?>
                    </div>

                    <script>
                    // Compose + send a new thread to the Vance team. Lives outside the
                    // "has messages" branch above so it also works on an empty inbox,
                    // which is exactly when someone is most likely to want it.
                    (function () {
                        var form = document.getElementById('msg-compose');
                        if (!form) { return; }

                        var ajaxUrl  = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
                        var toggle   = document.getElementById('msg-compose-toggle');
                        var cancel   = document.getElementById('msg-compose-cancel');
                        var status   = document.getElementById('msg-compose-status');
                        var subject  = document.getElementById('msg-compose-subject');
                        var bodyEl   = document.getElementById('msg-compose-body');
                        var submit   = form.querySelector('button[type="submit"]');

                        function say(msg, kind) {
                            status.textContent = msg || '';
                            status.style.color = kind === 'error' ? '#B91C1C' : (kind === 'ok' ? '#047857' : '#64748B');
                        }

                        function show(on) {
                            form.style.display = on ? 'block' : 'none';
                            if (on) { subject.focus(); }
                        }

                        toggle.addEventListener('click', function () {
                            show(form.style.display === 'none' || form.style.display === '');
                        });
                        cancel.addEventListener('click', function () { say(''); show(false); });

                        form.addEventListener('submit', function (e) {
                            e.preventDefault();
                            var s = subject.value.trim();
                            var b = bodyEl.value.trim();
                            if (s.length < 3)  { say('Please give your message a subject.', 'error'); subject.focus(); return; }
                            if (b.length < 10) { say('Please write a little more.', 'error'); bodyEl.focus(); return; }

                            var fd = new FormData();
                            fd.append('action', 'vance_msg_user_new');
                            fd.append('nonce', form.getAttribute('data-nonce'));
                            fd.append('subject', s);
                            fd.append('body', b);

                            submit.disabled = true;
                            say('Sending…');

                            fetch(ajaxUrl, { method: 'POST', credentials: 'same-origin', body: fd })
                                .then(function (r) { return r.json(); })
                                .then(function (res) {
                                    if (!res || !res.success) {
                                        submit.disabled = false;
                                        say((res && res.data && res.data.message) || 'Could not send your message.', 'error');
                                        return;
                                    }
                                    say(res.data.message || 'Message sent.', 'ok');
                                    // Reload so the new thread appears in the list with
                                    // its reply box, rather than being invisible until
                                    // the next visit.
                                    setTimeout(function () { location.reload(); }, 1200);
                                })
                                .catch(function () {
                                    submit.disabled = false;
                                    say('Could not send your message. Please check your connection and try again.', 'error');
                                });
                        });
                    })();
                    </script>
                <?php break;

                case 'documents':
                    // Documents uploaded before this tab existed have never been
                    // through text extraction. Do it now, once, so they can be
                    // asked about like anything else — see
                    // vance_user_docs_backfill_text().
                    if (function_exists('vance_user_docs_backfill_text')) {
                        vance_user_docs_backfill_text($current_user->ID);
                    }
                    $docs      = function_exists('vance_user_docs_get') ? vance_user_docs_get($current_user->ID) : array();
                    $doc_nonce = wp_create_nonce('vance_dashboard_nonce');
                    $doc_max   = defined('VANCE_DOCS_MAX') ? VANCE_DOCS_MAX : 10;
                    // Payload for the reader and the Ask modal. `text` itself is NOT
                    // sent to the browser — only whether we managed to read any, so a
                    // 40k-character discharge summary is not embedded in the page.
                    $doc_payload = array();
                    foreach ($docs as $d) {
                        $doc_payload[(string) $d['id']] = array(
                            'id'        => $d['id'],
                            'name'      => $d['name'],
                            'mime'      => $d['mime'],
                            'date'      => $d['date'] ? date_i18n('j M Y', strtotime($d['date'])) : '',
                            'readerUrl' => vance_user_docs_reader_url($d['id']),
                            'hasText'   => ('' !== trim($d['text'])),
                            'textStatus'=> $d['text_status'],
                        );
                    }
                    ?>
                    <div class="dash-card">
                        <header style="display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:8px;">
                            <div>
                                <h2 style="margin:0; color:#0F172A; font-size:22px;">My Documents</h2>
                                <p style="margin:4px 0 0; font-size:13px; color:#64748B;">
                                    Letters, test results, care plans and photos — <?php echo count($docs); ?> of <?php echo (int) $doc_max; ?> stored.
                                </p>
                            </div>
                            <?php if (count($docs) < $doc_max): ?>
                                <div>
                                    <button type="button" class="rl-btn rl-btn--primary" id="doc-upload-trigger">+ Upload document</button>
                                    <input type="file" id="doc-upload-input" style="display:none;"
                                           accept=".pdf,.doc,.docx,.txt,.csv,.jpg,.jpeg,.png,.heic">
                                </div>
                            <?php endif; ?>
                        </header>
                        <p id="doc-upload-status" style="margin:0 0 16px; font-size:13px; color:#64748B; display:none;"></p>

                        <?php // ---- Disclaimers: medical first, then data. ---- ?>
                        <div style="border:1px solid #E2E8F0; border-left:4px solid #008080; background:#F8FAFC; padding:16px 18px; margin-bottom:24px;">
                            <p style="margin:0 0 10px; font-size:13px; font-weight:700; color:#0F172A;">Before you upload</p>
                            <p style="margin:0 0 8px; font-size:12.5px; color:#475569; line-height:1.6;">
                                <strong>This is not a medical service and not a medical record.</strong> Anything VANCE-Ai
                                says about a document is general information, not a diagnosis, not a second opinion and
                                not personalised medical advice. It can misread a document or miss something important.
                                Always check anything that matters with the clinician who wrote it. Never rely on this
                                area in an emergency — call 999 or NHS 111.
                            </p>
                            <p style="margin:0 0 8px; font-size:12.5px; color:#475569; line-height:1.6;">
                                <strong>Your documents are special category health data.</strong> They are stored on this
                                site's own server and are visible to you and to site administrators. Files are held in the
                                standard WordPress uploads folder, so anyone who is given a document's direct file link
                                could open it — please do not share those links. Delete anything you no longer want held.
                            </p>
                            <p style="margin:0; font-size:12.5px; color:#475569; line-height:1.6;">
                                <strong>Asking VANCE-Ai sends that document's text to our AI provider</strong> for the
                                length of that conversation. Nothing is sent unless you press "Ask VANCE-Ai" on a
                                document. Consider removing names, addresses and NHS numbers first. See the
                                <a href="<?php echo esc_url(home_url('/privacy-policy/')); ?>" target="_blank" rel="noopener" style="color:#008080;">privacy policy</a>
                                and <a href="<?php echo esc_url(home_url('/medical-disclaimer/')); ?>" target="_blank" rel="noopener" style="color:#008080;">medical disclaimer</a>.
                            </p>
                        </div>

                        <?php if (empty($docs)): ?>
                            <div style="text-align:center; padding:48px; background:#F8FAFC; border:1px dashed #E2E8F0;">
                                <p style="color:#64748B; margin:0 0 16px;">You haven't uploaded any documents yet.</p>
                                <button type="button" class="rl-btn rl-btn--primary" id="doc-upload-trigger-empty">Upload your first document</button>
                            </div>
                        <?php else: ?>
                            <div class="dash-list">
                                <?php foreach (array_reverse($docs) as $d):
                                    $can_ask = ('' !== trim($d['text']));
                                    ?>
                                    <div class="list-item" style="padding:16px 0;">
                                        <div style="flex:1; min-width:0;">
                                            <div class="item-title" style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?php echo esc_html($d['name']); ?></div>
                                            <div class="item-meta">
                                                <?php echo $d['date'] ? esc_html(date_i18n('j M Y', strtotime($d['date']))) : ''; ?>
                                                <?php if ($d['size']): ?> • <?php echo esc_html(size_format($d['size'])); ?><?php endif; ?>
                                                <?php if (!$can_ask): ?>
                                                    • <span style="color:#B45309;">no readable text<?php echo ('unsupported' === $d['text_status']) ? ' (image or scan)' : ''; ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="rl-actions">
                                            <button type="button" class="rl-btn doc-view" data-doc="<?php echo (int) $d['id']; ?>">View</button>
                                            <button type="button" class="rl-btn rl-btn--primary doc-ask" data-doc="<?php echo (int) $d['id']; ?>"
                                                    <?php disabled(!$can_ask); ?>
                                                    title="<?php echo $can_ask ? 'Ask VANCE-Ai about this document' : 'No readable text could be extracted from this file'; ?>">Ask VANCE-Ai</button>
                                            <button type="button" class="rl-btn rl-btn--text doc-delete" data-doc="<?php echo (int) $d['id']; ?>">Delete</button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Document viewer -->
                    <div id="doc-viewer" class="rl-reader" role="dialog" aria-modal="true" aria-labelledby="doc-viewer-title">
                        <div class="rl-reader__panel">
                            <div class="rl-reader__head">
                                <div style="min-width:0;">
                                    <h2 class="rl-reader__title" id="doc-viewer-title">Document</h2>
                                    <p class="rl-reader__meta" id="doc-viewer-meta"></p>
                                </div>
                                <button type="button" class="rl-reader__close" data-doc-close aria-label="Close">&times;</button>
                            </div>
                            <div class="rl-reader__body" id="doc-viewer-body" style="padding:0; background:#F1F5F9;"></div>
                            <div class="rl-reader__foot">
                                <a href="#" class="rl-btn" id="doc-viewer-download" target="_blank" rel="noopener">Open in New Tab</a>
                                <button type="button" class="rl-btn rl-btn--primary" data-doc-close>Close</button>
                            </div>
                        </div>
                    </div>

                    <!-- Ask VANCE-Ai about a document -->
                    <div id="doc-ask" class="rl-reader" role="dialog" aria-modal="true" aria-labelledby="doc-ask-title">
                        <div class="rl-reader__panel" style="max-width:720px;">
                            <div class="rl-reader__head">
                                <div style="min-width:0;">
                                    <h2 class="rl-reader__title" id="doc-ask-title">Ask VANCE-Ai</h2>
                                    <p class="rl-reader__meta" id="doc-ask-meta"></p>
                                </div>
                                <button type="button" class="rl-reader__close" data-doc-close aria-label="Close">&times;</button>
                            </div>
                            <div class="rl-reader__body" id="doc-ask-thread" style="background:#F8FAFC;">
                                <p style="margin:0 0 14px; font-size:12.5px; color:#B45309; background:#FFFBEB; border:1px solid #FDE68A; padding:10px 12px; line-height:1.55;">
                                    This document's text is sent to our AI provider to answer your question. Answers are
                                    general information, not medical advice — check anything that matters with your
                                    clinician. VANCE-Ai will explain what a document says and what its terms mean, but
                                    it will not tell you whether a result is high, low or normal, or what it means for
                                    you. Only the clinician who ordered it can do that.
                                </p>
                                <p id="doc-ask-empty" style="font-size:14px; color:#64748B;">
                                    Ask anything about this document — for example "explain this in plain English",
                                    "what does this abbreviation stand for?", "what is this test for?" or
                                    "what should I ask my consultant about this?".
                                </p>
                            </div>
                            <div class="rl-reader__foot" style="flex-direction:column; align-items:stretch; gap:10px;">
                                <div style="display:flex; gap:8px;">
                                    <input type="text" id="doc-ask-input" placeholder="Ask about this document…"
                                           style="flex:1; min-width:0; padding:10px 12px; border:1px solid #CBD5E1; font-family:inherit; font-size:14px;">
                                    <button type="button" class="rl-btn rl-btn--primary" id="doc-ask-send">Ask</button>
                                </div>
                                <div style="display:flex; justify-content:space-between; align-items:center; gap:8px;">
                                    <span id="doc-ask-status" style="font-size:12px; color:#64748B;"></span>
                                    <button type="button" class="rl-btn" data-doc-close>Close</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <script>
                    (function () {
                        var ajaxUrl = <?php echo wp_json_encode(admin_url('admin-ajax.php')); ?>;
                        var restUrl = <?php echo wp_json_encode(esc_url_raw(rest_url('vance-health/v1/ai-chat'))); ?>;
                        var restNonce = <?php echo wp_json_encode(wp_create_nonce('wp_rest')); ?>;
                        var nonce   = <?php echo wp_json_encode($doc_nonce); ?>;
                        var DOCS    = <?php echo wp_json_encode($doc_payload); ?>;

                        // ---- Upload ----------------------------------------------------
                        var input  = document.getElementById('doc-upload-input');
                        var status = document.getElementById('doc-upload-status');

                        function say(msg, kind) {
                            if (!status) { return; }
                            status.textContent = msg || '';
                            status.style.display = msg ? 'block' : 'none';
                            status.style.color = kind === 'error' ? '#B91C1C' : (kind === 'ok' ? '#047857' : '#64748B');
                        }

                        ['doc-upload-trigger', 'doc-upload-trigger-empty'].forEach(function (id) {
                            var b = document.getElementById(id);
                            if (b && input) { b.addEventListener('click', function () { input.click(); }); }
                        });

                        if (input) {
                            input.addEventListener('change', function () {
                                if (!input.files || !input.files[0]) { return; }
                                var fd = new FormData();
                                fd.append('action', 'vance_doc_upload');
                                fd.append('nonce', nonce);
                                fd.append('doc', input.files[0]);
                                say('Uploading…');
                                fetch(ajaxUrl, { method: 'POST', credentials: 'same-origin', body: fd })
                                    .then(function (r) { return r.json(); })
                                    .then(function (res) {
                                        if (!res || !res.success) {
                                            say((res && res.data && res.data.message) || 'Upload failed.', 'error');
                                            input.value = '';
                                            return;
                                        }
                                        say('Uploaded.', 'ok');
                                        location.reload();
                                    })
                                    .catch(function () { say('Upload failed. Please try again.', 'error'); input.value = ''; });
                            });
                        }

                        // ---- Shared modal plumbing ------------------------------------
                        function openModal(el) { el.classList.add('is-open'); document.body.style.overflow = 'hidden'; }
                        function closeModals() {
                            ['doc-viewer', 'doc-ask'].forEach(function (id) {
                                var m = document.getElementById(id);
                                if (m) { m.classList.remove('is-open'); }
                            });
                            document.body.style.overflow = '';
                        }

                        // ---- Viewer ----------------------------------------------------
                        var viewer = document.getElementById('doc-viewer');
                        var vTitle = document.getElementById('doc-viewer-title');
                        var vMeta  = document.getElementById('doc-viewer-meta');
                        var vBody  = document.getElementById('doc-viewer-body');
                        var vOpen  = document.getElementById('doc-viewer-download');

                        function view(doc) {
                            vTitle.textContent = doc.name;
                            vMeta.textContent  = doc.date;
                            vOpen.href = doc.readerUrl;
                            vBody.innerHTML = '';

                            if (doc.mime.indexOf('image/') === 0) {
                                var img = document.createElement('img');
                                img.src = doc.readerUrl;
                                img.alt = doc.name;
                                img.style.cssText = 'display:block; max-width:100%; margin:0 auto;';
                                vBody.appendChild(img);
                            } else if (doc.mime === 'application/pdf') {
                                // <object> rather than <iframe>: it degrades to the fallback
                                // child when the browser has no PDF viewer, which iframes do not.
                                var obj = document.createElement('object');
                                obj.data = doc.readerUrl;
                                obj.type = 'application/pdf';
                                obj.style.cssText = 'width:100%; height:70vh; display:block;';
                                var fb = document.createElement('div');
                                fb.className = 'rl-reader__state';
                                fb.innerHTML = 'This browser cannot preview PDFs inline. ' +
                                    '<a href="' + doc.readerUrl + '" target="_blank" rel="noopener" style="color:#008080;font-weight:600;">Open it in a new tab</a>.';
                                obj.appendChild(fb);
                                vBody.appendChild(obj);
                            } else {
                                var d = document.createElement('div');
                                d.className = 'rl-reader__state';
                                d.innerHTML = 'This file type cannot be previewed here. ' +
                                    '<a href="' + doc.readerUrl + '" target="_blank" rel="noopener" style="color:#008080;font-weight:600;">Open it in a new tab</a>.';
                                vBody.appendChild(d);
                            }
                            openModal(viewer);
                        }

                        // ---- Ask VANCE-Ai ----------------------------------------------
                        var ask       = document.getElementById('doc-ask');
                        var aMeta     = document.getElementById('doc-ask-meta');
                        var aThread   = document.getElementById('doc-ask-thread');
                        var aEmpty    = document.getElementById('doc-ask-empty');
                        var aInput    = document.getElementById('doc-ask-input');
                        var aSend     = document.getElementById('doc-ask-send');
                        var aStatus   = document.getElementById('doc-ask-status');
                        var askDoc    = null;
                        var messages  = [];

                        function bubble(role, text) {
                            if (aEmpty) { aEmpty.style.display = 'none'; }
                            var wrap = document.createElement('div');
                            wrap.style.cssText = 'margin:0 0 14px; display:flex; justify-content:' + (role === 'user' ? 'flex-end' : 'flex-start') + ';';
                            var b = document.createElement('div');
                            b.style.cssText = 'max-width:85%; padding:12px 16px; font-size:14px; line-height:1.6; ' +
                                (role === 'user'
                                    ? 'background:#0F172A; color:#fff;'
                                    : 'background:#fff; color:#1F2937; border:1px solid #E2E8F0;');
                            b.textContent = text;
                            wrap.appendChild(b);
                            aThread.appendChild(wrap);
                            aThread.scrollTop = aThread.scrollHeight;
                            return b;
                        }

                        function send() {
                            var q = (aInput.value || '').trim();
                            if (!q || !askDoc) { return; }

                            aInput.value = '';
                            bubble('user', q);
                            messages.push({ role: 'user', content: q });

                            aSend.disabled = true;
                            aStatus.textContent = 'VANCE-Ai is reading your document…';
                            var pending = bubble('assistant', '…');

                            fetch(restUrl, {
                                method: 'POST',
                                credentials: 'same-origin',
                                headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': restNonce },
                                body: JSON.stringify({ messages: messages, doc_id: askDoc.id })
                            })
                                .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, body: j }; }); })
                                .then(function (res) {
                                    aSend.disabled = false;
                                    aStatus.textContent = '';
                                    if (!res.ok) {
                                        pending.textContent = (res.body && res.body.message) || 'VANCE-Ai could not answer that. Please try again.';
                                        pending.style.color = '#B91C1C';
                                        return;
                                    }
                                    var answer = (res.body && (res.body.reply || res.body.message || res.body.content)) || '';
                                    pending.textContent = answer || 'No answer was returned.';
                                    if (answer) { messages.push({ role: 'assistant', content: answer }); }
                                })
                                .catch(function () {
                                    aSend.disabled = false;
                                    aStatus.textContent = '';
                                    pending.textContent = 'VANCE-Ai could not be reached. Please check your connection.';
                                    pending.style.color = '#B91C1C';
                                });
                        }

                        aSend.addEventListener('click', send);
                        aInput.addEventListener('keydown', function (e) {
                            if (e.key === 'Enter') { e.preventDefault(); send(); }
                        });

                        function openAsk(doc) {
                            askDoc = doc;
                            messages = [];
                            aMeta.textContent = doc.name + (doc.date ? ' • ' + doc.date : '');
                            // Reset the thread back to the notice + prompt.
                            Array.prototype.slice.call(aThread.children).forEach(function (n) {
                                if (n.id !== 'doc-ask-empty' && n.tagName !== 'P') { aThread.removeChild(n); }
                            });
                            if (aEmpty) { aEmpty.style.display = ''; }
                            aStatus.textContent = '';
                            openModal(ask);
                            aInput.focus();
                        }

                        // ---- Row actions -----------------------------------------------
                        document.addEventListener('click', function (e) {
                            if (!e.target.closest) { return; }

                            var v = e.target.closest('.doc-view');
                            if (v) { e.preventDefault(); var d = DOCS[v.getAttribute('data-doc')]; if (d) { view(d); } return; }

                            var a = e.target.closest('.doc-ask');
                            if (a && !a.disabled) { e.preventDefault(); var d2 = DOCS[a.getAttribute('data-doc')]; if (d2) { openAsk(d2); } return; }

                            var del = e.target.closest('.doc-delete');
                            if (del) {
                                e.preventDefault();
                                if (!confirm('Delete this document permanently? This cannot be undone.')) { return; }
                                var fd = new FormData();
                                fd.append('action', 'vance_doc_delete');
                                fd.append('nonce', nonce);
                                fd.append('id', del.getAttribute('data-doc'));
                                del.disabled = true;
                                fetch(ajaxUrl, { method: 'POST', credentials: 'same-origin', body: fd })
                                    .then(function (r) { return r.json(); })
                                    .then(function (res) {
                                        if (res && res.success) { location.reload(); }
                                        else { del.disabled = false; alert((res && res.data && res.data.message) || 'Could not delete that document.'); }
                                    })
                                    .catch(function () { del.disabled = false; alert('Could not delete that document.'); });
                                return;
                            }

                            if (e.target.closest('[data-doc-close]')) { e.preventDefault(); closeModals(); return; }
                            if (e.target === viewer || e.target === ask) { closeModals(); }
                        });

                        document.addEventListener('keydown', function (e) {
                            if (e.key === 'Escape') { closeModals(); }
                        });
                    })();
                    </script>
                <?php break;

            endswitch; ?>
        </div>
    </main>
</div>

<script>
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('active');
    }

    // Bookmarks
    function deleteBookmark(pid) {
        if(!confirm('Remove bookmark?')) return;
        jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', {
            action: 'vance_toggle_bookmark', post_id: pid, nonce: '<?php echo wp_create_nonce("vance_dashboard_nonce"); ?>'
        }, function(res) { if(res.success) location.reload(); });
    }

    // Notes
    function deleteNote(id) {
        if(!confirm('Delete this note permanently?')) return;
        // Re-using vance_save_note with empty content or similar? 
        // Better to add a proper delete action in functions.php if not exists.
        // For now, let's assume vance_delete_note exists or use vance_save_note with a flag.
        jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', {
            action: 'vance_delete_note', id: id, nonce: '<?php echo wp_create_nonce("vance_dashboard_nonce"); ?>'
        }, function(res) { if(res.success) location.reload(); else alert(res.data); });
    }

    // Searches
    function deleteSearch(id) {
        if(!confirm('Delete this saved search?')) return;
        jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', {
            action: 'vance_delete_search', id: id, nonce: '<?php echo wp_create_nonce("vance_dashboard_nonce"); ?>'
        }, function(res) { if(res.success) location.reload(); else alert(res.data); });
    }

    // Avatar
    function triggerAvatarUpload() { document.getElementById('avatar-input').click(); }
    function uploadAvatar(input) {
        if (input.files[0]) {
            var fd = new FormData();
            fd.append('action', 'vance_upload_avatar');
            fd.append('avatar', input.files[0]);
            fd.append('nonce', '<?php echo wp_create_nonce("vance_dashboard_nonce"); ?>');
            jQuery.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>', type: 'POST', data: fd, processData: false, contentType: false,
                success: function(res) { if(res.success) location.reload(); else alert(res.data); }
            });
        }
    }

    // Profile Form
    jQuery(document).on('submit', '#profile-form-main', function(e) {
        e.preventDefault();
        var data = jQuery(this).serialize() + '&action=vance_save_profile&nonce=<?php echo wp_create_nonce("vance_dashboard_nonce"); ?>';
        var btn = jQuery(this).find('button[type="submit"]');
        btn.text('Saving...').prop('disabled', true);

        jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', data, function(res) {
            btn.text('Update Profile').prop('disabled', false);
            if(res.success) { alert('Profile saved successfully!'); } else alert(res.data);
        });
    });

    // Keeps each save button's icon (checkmark vs. empty circle-slash — see
    // the [data-empty] CSS above) matching its input LIVE as the user types,
    // not just after a save. Delegated so it also covers the icon inserted
    // for a 6th platform... N/A here, but stays correct if the row markup is
    // ever rebuilt client-side.
    jQuery(document).on('input', '.vance-field-save-row input', function () {
        var row = this.closest('.vance-field-save-row');
        var btn = row && row.querySelector('.vance-field-save-btn');
        if (btn) btn.setAttribute('data-empty', this.value.trim() ? '0' : '1');
    });

    // Per-field save (Social Profiles / My Links) — the tick button beside each
    // input. Saves just that field (or, for My Links, all 5 array slots
    // together so the other 4 aren't wiped — the handler overwrites the whole
    // _sla_profile_links meta from whatever profile_links[] it receives) and
    // shows a brief filled-tick "saved" state, no page reload.
    function vanceSaveProfileField(btn) {
        var row = btn.closest('.vance-field-save-row');
        if (!row || btn.disabled) return;
        vancePersistFieldRow(row, btn);
    }

    // Delete button beside each save button (request 2026-08-11) — clears the
    // field and persists that (a no-op if it was already empty: nothing to
    // clear, no confirm needed). Reuses the save button as the feedback
    // target so the same checkmark/circle-slash + brief "saved" flash apply,
    // rather than inventing a second status indicator for the same field.
    function vanceDeleteProfileField(btn) {
        var row = btn.closest('.vance-field-save-row');
        if (!row || btn.disabled) return;
        var input = row.querySelector('input');
        if (!input || !input.value.trim()) return;
        if (!confirm('Clear this link?')) return;
        input.value = '';
        var saveBtn = row.querySelector('.vance-field-save-btn');
        if (saveBtn) saveBtn.setAttribute('data-empty', '1');
        vancePersistFieldRow(row, saveBtn || btn);
    }

    // Shared by both buttons above — builds and sends the save-profile
    // request for one row (a single Social Profile field, or all 5 My Links
    // slots together) and updates the row's feedback button + the relevant
    // live list (icon row / text list) on success.
    function vancePersistFieldRow(row, feedbackBtn) {
        var platform = row.getAttribute('data-platform'); // set for Social Profiles rows only
        var data = {
            action: 'vance_save_profile',
            nonce: '<?php echo wp_create_nonce("vance_dashboard_nonce"); ?>'
        };
        var savedValue = '';
        var myLinksValues = null;

        if (platform) {
            var input = row.querySelector('input');
            savedValue = input.value.trim();
            data[input.name] = savedValue;
        } else {
            // My Links: send every slot in the group, in DOM order, so an
            // untouched link isn't dropped by the handler's array overwrite.
            myLinksValues = [];
            document.querySelectorAll('.vance-profile-links-group input[name="profile_links[]"]').forEach(function (inp, i) {
                data['profile_links[' + i + ']'] = inp.value;
                myLinksValues.push(inp.value.trim());
            });
        }

        feedbackBtn.disabled = true;
        jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', data, function (res) {
            feedbackBtn.disabled = false;
            if (res.success) {
                feedbackBtn.classList.add('is-saved');
                feedbackBtn.setAttribute('aria-label', 'Saved');
                setTimeout(function () {
                    feedbackBtn.classList.remove('is-saved');
                    feedbackBtn.setAttribute('aria-label', platform ? 'Save ' + platform + ' link' : 'Save link');
                }, 2000);
                if (platform) {
                    vanceRefreshSocialIcon(platform, savedValue);
                } else {
                    vanceRefreshMyLinksList(myLinksValues);
                }
            } else {
                alert(res.data || 'Could not save — please try again.');
            }
        }).fail(function () {
            feedbackBtn.disabled = false;
            alert('Could not save — please try again.');
        });
    }

    // Adds/updates/removes one platform's icon in the row above the avatar
    // so a saved (or cleared) social link shows up instantly, without a
    // reload. Social Profiles only — that row is icons-only (no text list,
    // see vanceRefreshMyLinksList() for My Links' equivalent below).
    function vanceRefreshSocialIcon(platform, url) {
        var wrap = document.getElementById('vance-profile-social-icons');
        if (!wrap) return;
        var existing = wrap.querySelector('a[data-platform="' + platform + '"]');

        if (!url) {
            if (existing) existing.remove();
        } else if (existing) {
            existing.href = url;
        } else {
            var tpl = document.getElementById('vance-social-icon-tpl-' + platform);
            if (tpl) {
                var a = document.createElement('a');
                a.href = url;
                a.target = '_blank';
                a.rel = 'noopener';
                a.className = 'vance-profile-social-icon';
                a.setAttribute('data-platform', platform);
                a.setAttribute('title', platform.charAt(0).toUpperCase() + platform.slice(1));
                a.innerHTML = tpl.innerHTML;
                wrap.appendChild(a);
            }
        }

        wrap.style.display = wrap.children.length ? 'grid' : 'none'; // grid, not flex — 3-per-row layout above
    }

    // Rebuilds the protocol-stripped My Links text list under the avatar
    // photo from the 5 current slot values — simpler and safer than diffing
    // against data-platform (My Links have no stable per-row identity the
    // way Social Profiles do), and vanceSaveProfileField() already has all
    // 5 values in hand from building the save request.
    function vanceRefreshMyLinksList(values) {
        var wrap = document.getElementById('vance-profile-my-links-list');
        if (!wrap || !values) return;
        wrap.innerHTML = '';
        values.forEach(function (url) {
            if (!url) return;
            var a = document.createElement('a');
            a.href = url;
            a.target = '_blank';
            a.rel = 'noopener';
            a.className = 'vance-profile-link-text';
            a.textContent = url.replace(/^https?:\/\/(www\.)?/i, '').replace(/\/$/, '');
            wrap.appendChild(a);
        });
        wrap.style.display = wrap.children.length ? 'flex' : 'none';
    }

    // Markdown Formatter Helper
    function parseMarkdown(text) {
        if (!text) return '';
        let html = text;
        
        // Bold
        html = html.replace(/\*\*([^*]+)\*\*/g, '<strong style="color:inherit;">$1</strong>');
        // Italic
        html = html.replace(/\*([^*]+)\*/g, '<em style="color:inherit;">$1</em>');
        
        // Lists (asterisk or dash)
        html = html.replace(/(?:\r?\n|^)\s*[\*-]\s+(.*?)(?=\n|$)/g, '<li style="margin-left: 20px; padding-bottom: 6px;">$1</li>');
        
        // Wrap contiguous list items in a <ul>
        html = html.replace(/(<li[^>]*>.*?<\/li>\s*)+/g, '<ul style="margin: 10px 0; padding: 0;">$&</ul>');
        
        // Newlines to breaks
        html = html.replace(/\n/g, '<br>');
        
        return html;
    }

    // AI Chat History
    function viewChat(chat) {
        const modal = document.getElementById('chat-modal');
        const title = document.getElementById('modal-chat-title');
        const date = document.getElementById('modal-chat-date');
        const content = document.getElementById('modal-chat-content');
        
        title.innerText = chat.title || 'VANCE-Ai conversation';
        date.innerText = 'Consultation date: ' + new Date(chat.date).toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' });
        content.innerHTML = '';
        
        if (chat.transcript && Array.isArray(chat.transcript)) {
            chat.transcript.forEach(msg => {
                const isUser = msg.role === 'user';
                const msgEl = document.createElement('div');
                msgEl.style.display = 'flex';
                msgEl.style.flexDirection = isUser ? 'row-reverse' : 'row';
                msgEl.style.gap = '12px';
                msgEl.style.alignItems = 'flex-start';
                msgEl.style.marginBottom = '20px';
                
                const avatar = document.createElement('div');
                avatar.style.width = '32px';
                avatar.style.height = '32px';
                avatar.style.borderRadius = '50%';
                avatar.style.display = 'flex';
                avatar.style.alignItems = 'center';
                avatar.style.justifyContent = 'center';
                avatar.style.fontSize = '12px';
                avatar.style.fontWeight = '700';
                avatar.style.flexShrink = '0';
                
                if (isUser) {
                    avatar.style.background = '#F1F5F9';
                    avatar.style.color = '#64748B';
                    avatar.innerText = 'USR';
                } else {
                    avatar.style.background = 'white';
                    avatar.style.color = 'var(--dash-primary)';
                    avatar.style.border = '1px solid var(--dash-primary)';
                    avatar.innerText = '🤖';
                }
                
                const bubble = document.createElement('div');
                bubble.style.padding = '16px 20px';
                bubble.style.borderRadius = '12px';
                bubble.style.fontSize = '14.5px';
                bubble.style.lineHeight = '1.65';
                bubble.style.maxWidth = '85%';
                bubble.style.boxShadow = '0 2px 4px rgba(0,0,0,0.02)';
                
                if (isUser) {
                    bubble.style.background = '#0F172A';
                    bubble.style.color = 'white';
                    bubble.style.borderTopRightRadius = '0';
                } else {
                    bubble.style.background = 'white';
                    bubble.style.color = '#1F2937';
                    bubble.style.border = '1px solid #E2E8F0';
                    bubble.style.borderTopLeftRadius = '0';
                }
                
                bubble.innerHTML = parseMarkdown(msg.content);

                // Small save-to-note control under each VANCE-Ai answer. Only on
                // the assistant side: saving your own question to a note is not
                // something anyone asked for, and it would double the clutter.
                if (!isUser && window.VanceNoteSaver) {
                    const save = document.createElement('button');
                    save.type = 'button';
                    save.className = 'vn-save-answer';
                    save.setAttribute('data-vn-open', '');
                    save.setAttribute('aria-label', 'Save this answer to a note');
                    save.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" ' +
                        'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' +
                        '<path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg><span>Add to note</span>';
                    save.addEventListener('click', function (ev) {
                        ev.preventDefault();
                        window.VanceNoteSaver.openForAnswer(save, chat, msg.content);
                    });
                    bubble.appendChild(save);
                }

                msgEl.appendChild(avatar);
                msgEl.appendChild(bubble);
                content.appendChild(msgEl);
            });
        } else if (chat.transcript) {
            // Handle legacy string format
            const wrapper = document.createElement('div');
            wrapper.style.padding = '24px 32px';
            wrapper.style.fontSize = '15px';
            wrapper.style.lineHeight = '1.7';
            wrapper.style.color = '#1F2937';
            wrapper.style.background = 'white';
            wrapper.style.border = '1px solid #E2E8F0';
            wrapper.style.borderRadius = '6px';
            
            let rawContent = chat.transcript;
            
            // Format legacy You: / AI: speakers into styled pills
            rawContent = rawContent.replace(/<strong>You:<\/strong>|You:/gi, '<br><div style="background:#F1F5F9; color:#64748B; padding:4px 12px; border-radius:12px; display:inline-block; font-size:11px; font-weight:700; margin-bottom:8px; margin-top:20px; line-height:1;">USER</div><br>');
            rawContent = rawContent.replace(/<strong>AI:<\/strong>|AI:/gi, '<br><div style="background:#def4f4; color:#008080; border:1px solid #aedbdb; padding:4px 12px; border-radius:12px; display:inline-block; font-size:11px; font-weight:700; margin-bottom:8px; margin-top:20px; line-height:1;">🤖 AI</div><br>');
            
            // Remove lingering empty paragraphs if any
            rawContent = rawContent.replace(/<p>\s*<\/p>/gi, '');

            wrapper.innerHTML = parseMarkdown(rawContent);
            content.appendChild(wrapper);
        }
        
        // Held so the footer's "Add whole chat to a note" knows what is on screen.
        window.__vanceOpenChat = chat;

        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    // Footer button: same whole-conversation save as the one on the list row.
    document.addEventListener('click', function (e) {
        if (!e.target.closest) { return; }
        var btn = e.target.closest('#chat-modal-to-note');
        if (!btn || !window.VanceNoteSaver || !window.__vanceOpenChat) { return; }
        e.preventDefault();
        window.VanceNoteSaver.openForChat(btn, window.__vanceOpenChat);
    });

    function closeChatModal() {
        document.getElementById('chat-modal').style.display = 'none';
        document.body.style.overflow = 'auto';
        if (window.VanceNoteSaver) { window.VanceNoteSaver.close(); }
    }

    function deleteChat(id) {
        if(!confirm('Delete this chat history permanently?')) return;
        jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', {
            action: 'vance_delete_chat', id: id, nonce: '<?php echo wp_create_nonce("vance_dashboard_nonce"); ?>'
        }, function(res) { if(res.success) location.reload(); else alert(res.data); });
    }
</script>

<?php get_footer('dashboard'); ?>
