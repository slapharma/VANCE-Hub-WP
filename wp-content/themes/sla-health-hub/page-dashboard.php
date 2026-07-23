<?php
/**
 * Template Name: User Dashboard
 */

// 1. Session & Auth Check
$current_user = wp_get_current_user();
$is_logged_in = is_user_logged_in();

// Redirect if not logged in? (User requested inline login previously, preserving that logic)
// But for the "Admin" design, it usually requires full screen. Refactoring to show Login Hero if not logged in.

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

<?php if ( ! $is_logged_in ) : ?>
    <!-- LOGIN VIEW (Retained from previous version) -->
    <div class="container" style="padding-top: 100px;">
        <div class="login-hero" style="max-width: 500px; margin: 0 auto; background: white; padding: 40px; border-radius: 0; box-shadow: 0 10px 25px rgba(0,0,0,0.05); text-align: center;">
            <div style="width: 64px; height: 64px; background: #008080; border-radius: 0; margin: 0 auto 24px; display: flex; align-items: center; justify-content: center;">
                <svg width="32" height="32" fill="white" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/></svg>
            </div>
            <h1 style="margin-bottom: 12px; color: #0f172a;">Welcome to Your Hub</h1>
            <p style="color: #64748b; margin-bottom: 32px;">Access your personalized readings, courses, and health records.</p>
            
            <?php echo do_shortcode('[google_login]'); ?>
            
            <div style="margin-top: 24px; border-top: 1px solid #e2e8f0; padding-top: 24px;">
                <p style="font-size: 14px; color: #94a3b8;">Don't have an account? <a href="<?php echo esc_url( home_url( '/register/' ) ); ?>" style="color: #008080; font-weight: 600;">Register Now</a></p>
            </div>
        </div>
    </div>

<?php else : 
    // DATA PREP
    $first_name = get_user_meta( $current_user->ID, 'first_name', true ) ?: $current_user->display_name;
    $job_title = get_user_meta( $current_user->ID, '_sla_job_title', true ) ?: 'Add Job Title';
    $org = get_user_meta( $current_user->ID, '_sla_organization', true ) ?: 'Add Organization';
    $bookmarks = get_user_meta( $current_user->ID, '_sla_reading_list', true ) ?: array();
    $profile_img = get_avatar_url( $current_user->ID, array('size' => 128) );
    
    // Role Logic
    $user_roles = (array) $current_user->roles;
    $is_practitioner = in_array( 'practitioner', $user_roles );
    
    // Theme Vars based on Role
    $theme_primary = $is_practitioner ? '#0A1929' : '#008080'; // Navy vs Orange
    $theme_sidebar = $is_practitioner ? '#0A1929' : '#FFFFFF';
    $theme_sidebar_text = $is_practitioner ? '#94a3b8' : '#64748B';
    // Lighter visual weight for the Member sidebar: less-bold labels/items than
    // Practitioner's, while keeping the same (accessible-contrast) text color.
    $nav_label_weight = $is_practitioner ? '700' : '600';
    $nav_item_weight = $is_practitioner ? '500' : '400';
    $sidebar_logo_color = $is_practitioner ? '#FFFFFF' : '#0A1929';
    $nav_hover_bg = $is_practitioner ? 'rgba(255,255,255,0.1)' : '#F1F5F9';
    $nav_active_color = $is_practitioner ? '#008080' : '#008080';
    $nav_active_bg = $is_practitioner ? 'rgba(0,128,128,0.1)' : '#def4f4';

    // Navigation Configuration (Global)
    $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'home';
    // Back-compat: legacy ?tab=calculators links resolve to the new tools tab.
    if ( $current_tab === 'calculators' ) { $current_tab = 'tools'; }
    // Back-compat: legacy ?tab=clinical-profile links resolve to the renamed Health Profile tab.
    if ( $current_tab === 'clinical-profile' ) { $current_tab = 'health-profile'; }
    $nav_items = [
        'main' => [
            'home'        => ['label' => 'Dashboard', 'icon' => '📊'],
            'profile'     => ['label' => 'My Profile', 'icon' => '👤'],
            'health-profile' => ['label' => 'Health Profile', 'icon' => '🩺'],
            'tools' => ['label' => 'My Tools', 'icon' => '🧮'],
        ],
        'learning' => [
            'reading-list' => ['label' => 'My Reading List', 'icon' => '📚'],
            'courses'      => ['label' => 'My Courses', 'icon' => '🎓'],
            'searches'     => ['label' => 'My Searches', 'icon' => '🔍'],
        ],
        'communication' => [
            'notes'    => ['label' => 'My Notes', 'icon' => '📝'],
            'ai-chats' => ['label' => 'My VANCE-Ai', 'icon' => '🤖'],
            'messages' => ['label' => 'My Messages', 'icon' => '💬'],
        ],
    ];
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
.dash-logo { font-family: 'Outfit', sans-serif; font-weight: 800; font-size: 20px; color: <?php echo $sidebar_logo_color; ?>; display: flex; align-items: center; gap: 8px; text-decoration: none; }
.dash-nav { padding: 20px 12px; flex: 1; }
.nav-section { margin-bottom: 24px; }
.nav-label { font-size: 11px; font-weight: <?php echo $nav_label_weight; ?>; color: <?php echo $theme_sidebar_text; ?>; text-transform: uppercase; margin: 0 0 8px 12px; letter-spacing: 0.5px; opacity: <?php echo $is_practitioner ? '0.8' : '0.7'; ?>; }
.nav-item { display: flex; align-items: center; gap: 12px; padding: 10px 12px; color: <?php echo $theme_sidebar_text; ?>; text-decoration: none; border-radius: 0; font-size: 14px; font-weight: <?php echo $nav_item_weight; ?>; transition: all 0.2s; margin-bottom: 2px; }
.nav-item:hover { background: <?php echo $nav_hover_bg; ?>; color: <?php echo $is_practitioner ? 'white' : 'var(--dash-primary)'; ?>; }
.nav-item.active { background: <?php echo $nav_active_bg; ?>; color: <?php echo $nav_active_color; ?>; }

/* Header */
.dash-header { height: 64px; background: white; border-bottom: 1px solid var(--dash-border); display: flex; align-items: center; justify-content: space-between; padding: 0 32px; position: sticky; top: 0; z-index: 998; }
.page-title { font-family: 'Outfit', sans-serif; font-size: 18px; font-weight: 600; color: #0A1929; display: flex; align-items: center; gap: 8px; }
.user-profile { display: flex; align-items: center; gap: 12px; cursor: pointer; }
.profile-avatar { width: 32px; height: 32px; border-radius: 0; object-fit: cover; border: 1px solid #E2E8F0; }

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
.card-link { font-size: 13px; font-weight: 600; color: <?php echo $is_practitioner ? '#0369A1' : '#008080'; ?>; text-decoration: none; cursor: pointer; border: none; background: none; }

/* List Items */
.dash-list { display: flex; flex-direction: column; gap: 0; }
.list-item { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid #F1F5F9; }
.list-item:last-child { border-bottom: none; }
.item-title { font-size: 14px; font-weight: 600; color: #0F172A; margin-bottom: 2px; }
.item-meta { font-size: 12px; color: #64748B; }

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
            <a href="/" class="dash-logo" style="display: flex; align-items: center; gap: 0; text-decoration: none;">
                <img src="<?php echo get_template_directory_uri(); ?>/assets/img/logo.png" alt="Vance Medical" style="height: 50px; width: auto; object-fit: contain;">
            </a>
            <button class="mobile-toggle" style="margin-left: auto; color: <?php echo $is_practitioner ? 'white' : '#0A1929'; ?>;" onclick="toggleSidebar()">✕</button>
        </div>

        <!-- Nav Items Loop -->

        <nav class="dash-nav">
            <?php foreach($nav_items as $section => $items): ?>
                <div class="nav-section">
                    <?php if($section !== 'main'): ?>
                        <div class="nav-label"><?php echo ucfirst($section === 'misc' ? '' : $section); ?></div>
                    <?php endif; ?>
                    <?php foreach($items as $slug => $data): ?>
                        <a href="?tab=<?php echo $slug; ?>" class="nav-item <?php echo $current_tab === $slug ? 'active' : ''; ?>">
                            <span style="width:20px;text-align:center;"><?php echo $data['icon']; ?></span> <?php echo $data['label']; ?>
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
                <div class="page-title">
                    <?php 
                    $tab_label = 'Overview';
                    foreach($nav_items as $sec => $its) {
                        if(isset($its[$current_tab])) {
                            $tab_label = $its[$current_tab]['label'];
                            break;
                        }
                    }
                    echo $tab_label;
                    ?>
                </div>
            </div>

            <div style="display: flex; align-items: center; gap: 20px;">
                <div class="user-profile">
                    <div class="dash-user-meta">
                        <div style="font-size: 14px; font-weight: 600; color: #0F172A;"><?php echo esc_html($first_name); ?></div>
                        <div style="font-size: 11px; color: #64748B;"><?php echo esc_html($is_practitioner ? 'MD, ' . $org : 'Member'); ?></div>
                    </div>
                    <img src="<?php echo esc_url($profile_img); ?>" class="profile-avatar">
                </div>
                <a href="<?php echo wp_logout_url(home_url()); ?>" title="Log Out" aria-label="Log Out" style="display:flex; align-items:center; justify-content:center; width:36px; height:36px; border:1px solid #E2E8F0; border-radius:0; color:#64748B; text-decoration:none; font-size:16px; transition:all 0.2s;" onmouseover="this.style.background='#F1F5F9'; this.style.color='#EF4444';" onmouseout="this.style.background=''; this.style.color='#64748B';">🚪</a>
            </div>
        </header>

        <div class="dash-content">
            <?php 
            $tab_label = 'Overview';
            foreach($nav_items as $sec => $its) {
                if(isset($its[$current_tab])) {
                    $tab_label = $its[$current_tab]['label'];
                    break;
                }
            }
            ?>
            <div style="margin-bottom: 32px; display: flex; justify-content: space-between; align-items: flex-end;">
                <div>
                    <h1 style="font-family:'Outfit'; font-size:28px; color:#0F172A; margin:0 0 8px 0;"><?php echo $tab_label; ?></h1>
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

                    <div class="dash-grid-v2">
                        <!-- 1. READING LIST (Wide) -->
                        <div class="d-card d-col-8">
                             <div class="d-card-header">
                                <div class="d-card-title"><span class="d-icon-box">📚</span> Reading List</div>
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

                        <!-- 2. MESSAGES (Tall/Side) — admin-broadcast messages live here -->
                        <div class="d-card d-col-4">
                            <div class="d-card-header">
                                <div class="d-card-title"><span class="d-icon-box">💬</span> Messages</div>
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

                        <!-- 3. NOTES -->
                        <div class="d-card d-col-4">
                            <div class="d-card-header">
                                <div class="d-card-title"><span class="d-icon-box">📝</span> My Notes</div>
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

                        <!-- 4. MY VANCE-AI -->
                        <div class="d-card d-col-4">
                            <div class="d-card-header">
                                <div class="d-card-title"><span class="d-icon-box">🤖</span> My VANCE-Ai</div>
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

                        <!-- 5. HEALTH PROFILE PROMO -->
                        <?php $has_health_profile = (bool) get_user_meta( $current_user->ID, '_sla_clinical_profile', true ); ?>
                        <div class="d-card d-col-4" style="background: linear-gradient(135deg, #008080, #0A1929); color: white; border: none;">
                            <div class="d-card-header">
                                <div class="d-card-title" style="color: white;"><span class="d-icon-box" style="background: rgba(255,255,255,0.15);">🩺</span> Health Profile</div>
                            </div>
                            <p style="font-size:13px; color:rgba(255,255,255,0.85); line-height:1.5; margin: 0 0 16px 0;">
                                <?php echo $has_health_profile
                                    ? 'Keep your Health Profile up to date so your care stays personalised.'
                                    : 'Complete your Health Profile to get personalised content and tools.'; ?>
                            </p>
                            <a href="?tab=health-profile" class="card-link" style="color:white; font-weight:700;"><?php echo $has_health_profile ? 'Update Health Profile →' : 'Complete Health Profile →'; ?></a>
                        </div>
                    </div>
                <?php break;

                case 'profile': 
                    // Prepare data
                    $socials = array(
                        'website' => get_user_meta($current_user->ID, '_sla_website', true),
                        'twitter' => get_user_meta($current_user->ID, '_sla_twitter', true), // X
                        'linkedin' => get_user_meta($current_user->ID, '_sla_linkedin', true),
                        'instagram' => get_user_meta($current_user->ID, '_sla_instagram', true),
                        'facebook' => get_user_meta($current_user->ID, '_sla_facebook', true),
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
                                    
                                    <!-- Documents Section -->
                                    <div style="margin-top: 30px; border-top: 1px solid #E2E8F0; padding-top: 20px;">
                                        <label style="display:block; font-size:13px; font-weight:600; margin-bottom:10px;">My Documents (Max 5)</label>
                                        <div id="doc-list" style="margin-bottom: 12px;">
                                            <?php foreach($profile_docs as $doc): ?>
                                                <div class="doc-item" style="display:flex; justify-content:space-between; font-size:12px; background:#F8FAFC; padding:8px; border-radius:0; margin-bottom:4px;">
                                                    <a href="<?php echo esc_url($doc['url']); ?>" target="_blank" style="text-decoration:none; color:#334155; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:140px;"><?php echo esc_html($doc['name']); ?></a>
                                                    <span onclick="deleteProfileDoc(<?php echo $doc['id']; ?>)" style="color:#EF4444; cursor:pointer; font-weight:700;">×</span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php if(count($profile_docs) < 5): ?>
                                            <button type="button" onclick="document.getElementById('profile-doc-up').click()" style="font-size:12px; width:100%; padding:8px; border:1px dashed #CBD5E1; background:white; color:#64748B; border-radius:0; cursor:pointer;">+ Upload Document</button>
                                            <input type="file" id="profile-doc-up" style="display:none;" onchange="uploadProfileDoc(this)">
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Right Col: Info & Links -->
                                <div>
                                    <h3 style="margin:0 0 20px 0; font-size:18px; color:#0F172A;">Personal Information</h3>
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px;">
                                        <div>
                                            <label style="display:block; margin-bottom:8px; font-size:13px; font-weight:600;">First Name</label>
                                            <input type="text" name="first_name" value="<?php echo esc_attr($first_name); ?>" style="width:100%; padding:10px; border:1px solid #E2E8F0; border-radius:0;">
                                        </div>
                                        <div>
                                            <label style="display:block; margin-bottom:8px; font-size:13px; font-weight:600;">Last Name</label>
                                            <input type="text" name="last_name" value="<?php echo esc_attr($current_user->last_name); ?>" style="width:100%; padding:10px; border:1px solid #E2E8F0; border-radius:0;">
                                        </div>
                                    </div>

                                    <div style="margin-bottom: 24px;">
                                        <label style="display:block; margin-bottom:8px; font-size:13px; font-weight:600;">Email Address</label>
                                        <input type="email" name="user_email" value="<?php echo esc_attr($current_user->user_email); ?>" style="width:100%; padding:10px; border:1px solid #E2E8F0; border-radius:0;">
                                    </div>

                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px;">
                                        <div>
                                            <label style="display:block; margin-bottom:8px; font-size:13px; font-weight:600;">Job Title</label>
                                            <input type="text" name="vance_job_title" value="<?php echo esc_attr($job_title); ?>" style="width:100%; padding:10px; border:1px solid #E2E8F0; border-radius:0;">
                                        </div>
                                        <div>
                                            <label style="display:block; margin-bottom:8px; font-size:13px; font-weight:600;">Organization</label>
                                            <input type="text" name="vance_organization" value="<?php echo esc_attr($org); ?>" style="width:100%; padding:10px; border:1px solid #E2E8F0; border-radius:0;">
                                        </div>
                                    </div>

                                    <div style="margin-bottom: 24px;">
                                        <label style="display:block; margin-bottom:8px; font-size:13px; font-weight:600;">Biography</label>
                                        <textarea name="description" rows="4" style="width:100%; padding:10px; border:1px solid #E2E8F0; border-radius:0; font-family:inherit; resize:none;"><?php echo esc_textarea($current_user->description); ?></textarea>
                                    </div>

                                    <!-- Social Links -->
                                    <h3 style="margin:30px 0 20px 0; font-size:16px; color:#0F172A; border-top:1px solid #E2E8F0; padding-top:20px;">Social Profiles</h3>
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px;">
                                        <div><input type="url" name="vance_website" placeholder="Website URL" value="<?php echo esc_attr($socials['website']); ?>" style="width:100%; padding:8px; border:1px solid #E2E8F0; border-radius:0; font-size:13px;"></div>
                                        <div><input type="url" name="vance_twitter" placeholder="X (Twitter) URL" value="<?php echo esc_attr($socials['twitter']); ?>" style="width:100%; padding:8px; border:1px solid #E2E8F0; border-radius:0; font-size:13px;"></div>
                                        <div><input type="url" name="vance_linkedin" placeholder="LinkedIn URL" value="<?php echo esc_attr($socials['linkedin']); ?>" style="width:100%; padding:8px; border:1px solid #E2E8F0; border-radius:0; font-size:13px;"></div>
                                        <div><input type="url" name="vance_instagram" placeholder="Instagram URL" value="<?php echo esc_attr($socials['instagram']); ?>" style="width:100%; padding:8px; border:1px solid #E2E8F0; border-radius:0; font-size:13px;"></div>
                                        <div><input type="url" name="vance_facebook" placeholder="Facebook URL" value="<?php echo esc_attr($socials['facebook']); ?>" style="width:100%; padding:8px; border:1px solid #E2E8F0; border-radius:0; font-size:13px;"></div>
                                    </div>

                                    <!-- External Links -->
                                    <h3 style="margin:30px 0 20px 0; font-size:16px; color:#0F172A; border-top:1px solid #E2E8F0; padding-top:20px;">My Links (Up to 5)</h3>
                                    <div style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 30px;">
                                        <?php foreach($profile_links as $i => $link): ?>
                                            <input type="url" name="profile_links[]" placeholder="https://" value="<?php echo esc_attr($link); ?>" style="width:100%; padding:8px; border:1px solid #E2E8F0; border-radius:0; font-size:13px;">
                                        <?php endforeach; ?>
                                    </div>

                                    <div style="display: flex; justify-content: flex-end;">
                                        <button type="submit" class="btn-primary" style="background:<?php echo $theme_primary; ?>; color:white; border:none; padding:12px 32px; border-radius:0; font-weight:600; cursor:pointer;">Update Profile</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <!-- Upload Scripts -->
                    <script>
                    function uploadProfileDoc(input) {
                        if (input.files[0]) {
                            var fd = new FormData();
                            fd.append('action', 'vance_upload_profile_doc');
                            fd.append('doc', input.files[0]);
                            fd.append('nonce', '<?php echo wp_create_nonce("vance_dashboard_nonce"); ?>');
                            jQuery.ajax({
                                url: '<?php echo admin_url('admin-ajax.php'); ?>', type: 'POST', data: fd, processData: false, contentType: false,
                                success: function(res) { if(res.success) location.reload(); else alert(res.data); }
                            });
                        }
                    }
                    function deleteProfileDoc(id) {
                        if(!confirm('Delete this document?')) return;
                        jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                            action: 'vance_delete_profile_doc', id: id, nonce: '<?php echo wp_create_nonce("vance_dashboard_nonce"); ?>'
                        }, function(res) { if(res.success) location.reload(); else alert(res.data); });
                    }
                    </script>
                <?php break;

                case 'health-profile':
                    $quiz_results = get_user_meta($current_user->ID, '_sla_healthcare_quiz_results', true) ?: array();
                    $clinical_profile = get_user_meta($current_user->ID, '_sla_clinical_profile', true) ?: array();
                    
                    // Defaults for form
                    $defaults = array(
                        'digital_apps' => '', 'medication' => '', 'supplements' => '', 
                        'lifestyle_changes' => '', 'flare_up_freq' => '', 'last_flare_up' => '',
                        'weight' => '', 'height' => '', 'blood_pressure' => '', 'additional_details' => ''
                    );
                    $profile = array_merge($defaults, $clinical_profile);
                    ?>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 32px;">
                        <!-- Quiz Results Section -->
                        <div class="dash-card">
                            <div class="card-header">
                                <h3 class="card-title">My Health Profile Responses</h3>
                                <?php if($quiz_results): ?>
                                    <button onclick="openQuizModal(1)" class="card-link" style="font-size:12px; border:1px solid #E2E8F0; padding:4px 10px; border-radius:0; background:white;">Edit Answers</button>
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
                                    $labels = array(
                                        'age' => 'Age Range', 'gender' => 'Gender',
                                        'gastro_condition' => 'Gastro Condition', 'condition_type' => 'Primary Concern',
                                        'looking_for' => 'Searching For', 'duration' => 'Duration/Interest',
                                        'seeing_specialist' => 'Seeing Specialist', 'current_tools' => 'Digital Tool Use',
                                        'learning_pref' => 'Learning Style'
                                    );
                                    foreach($quiz_results as $key => $val): if(isset($labels[$key])): ?>
                                        <div class="list-item" style="cursor:pointer;" onclick="openQuizModal(<?php echo array_search($key, array_keys($labels)) + 1; ?>, true)">
                                            <span style="font-size:13px; font-weight:600; color:#64748B;"><?php echo $labels[$key]; ?></span>
                                            <div style="display:flex; align-items:center; gap:8px;">
                                                <span style="font-size:14px; color:#0F172A; font-weight:700;"><?php echo esc_html(ucfirst($val)); ?></span>
                                                <span style="font-size:12px; color:#008080; opacity:0; transition:opacity 0.2s;" class="edit-hint">Edit &rarr;</span>
                                            </div>
                                        </div>
                                    <?php endif; endforeach; ?>
                                </div>
                                <div style="margin-top:24px; text-align:center; display:flex; flex-direction:column; gap:12px;">
                                    <button onclick="openQuizModal()" style="font-size:12px; color:#008080; font-weight:600; background:none; border:none; cursor:pointer;">Retake Entire Quiz &rarr;</button>
                                    <div style="display:flex; gap:12px;">
                                        <a href="/ask-ai/?context=clinical_eval" class="btn-primary" style="flex: 1; background:#0A1929; color:white; text-decoration:none; padding:12px; border-radius:0; font-weight:700; display:flex; align-items:center; justify-content:center; gap:8px;">
                                            <span>🔍</span> Ask VANCE-Ai
                                        </a>
                                        <a href="/ask-ai/?context=suggest_content" class="btn-primary" style="flex: 1; background:#008080; color:white; text-decoration:none; padding:12px; border-radius:0; font-weight:700; display:flex; align-items:center; justify-content:center; gap:8px;">
                                            <span>💡</span> Suggest Content
                                        </a>
                                    </div>
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
                                <button onclick="openClinicalInfoModal()" class="card-link" style="font-size:12px; border:1px solid #E2E8F0; padding:4px 10px; border-radius:0; background:white;">Update Details</button>
                            </div>
                            
                            <div class="dash-list">
                                <div class="list-item">
                                    <span style="font-size:13px; font-weight:600; color:#64748B;">Weight / Height</span>
                                    <span style="font-size:14px; color:#0F172A; font-weight:700;"><?php echo $profile['weight'] ? esc_html($profile['weight']) . 'kg' : '---'; ?> / <?php echo $profile['height'] ? esc_html($profile['height']) . 'cm' : '---'; ?></span>
                                </div>
                                <div class="list-item">
                                    <span style="font-size:13px; font-weight:600; color:#64748B;">Medication</span>
                                    <span style="font-size:14px; color:#0F172A; font-weight:700;"><?php echo $profile['medication'] ?: 'None listed'; ?></span>
                                </div>
                                <div class="list-item">
                                    <span style="font-size:13px; font-weight:600; color:#64748B;">Supplements</span>
                                    <span style="font-size:14px; color:#0F172A; font-weight:700;"><?php echo $profile['supplements'] ?: 'None listed'; ?></span>
                                </div>
                                <div class="list-item" style="flex-direction:column; align-items:flex-start; gap:4px;">
                                    <span style="font-size:13px; font-weight:600; color:#64748B;">Lifestyle Changes</span>
                                    <p style="font-size:14px; color:#334155; margin:0; line-height:1.4;"><?php echo $profile['lifestyle_changes'] ?: 'No lifestyle updates yet.'; ?></p>
                                </div>
                                <div class="list-item">
                                    <span style="font-size:13px; font-weight:600; color:#64748B;">Flare-up History</span>
                                    <span style="font-size:14px; color:#0F172A; font-weight:700;"><?php echo $profile['flare_up_freq'] ?: '---'; ?> (Last: <?php echo $profile['last_flare_up'] ?: 'N/A'; ?>)</span>
                                </div>
                            </div>

                            <div style="margin-top:30px; border-top:1px solid #E2E8F0; padding-top:20px;">
                                <label style="display:block; font-size:13px; font-weight:700; color:#0A1929; margin-bottom:12px;">Additional Discovery Details</label>
                                <form id="dashboard-additional-details-form">
                                    <?php wp_nonce_field( 'vance_dashboard_nonce', 'nonce' ); ?>
                                    <input type="hidden" name="action" value="vance_save_clinical_profile">
                                    <input type="hidden" name="weight" value="<?php echo esc_attr($profile['weight']); ?>">
                                    <input type="hidden" name="height" value="<?php echo esc_attr($profile['height']); ?>">
                                    <input type="hidden" name="medication" value="<?php echo esc_attr($profile['medication']); ?>">
                                    <input type="hidden" name="supplements" value="<?php echo esc_attr($profile['supplements']); ?>">
                                    <input type="hidden" name="digital_apps" value="<?php echo esc_attr($profile['digital_apps']); ?>">
                                    <input type="hidden" name="lifestyle_changes" value="<?php echo esc_attr($profile['lifestyle_changes']); ?>">
                                    <input type="hidden" name="flare_up_freq" value="<?php echo esc_attr($profile['flare_up_freq']); ?>">
                                    <input type="hidden" name="last_flare_up" value="<?php echo esc_attr($profile['last_flare_up']); ?>">
                                    <input type="hidden" name="blood_pressure" value="<?php echo esc_attr($profile['blood_pressure']); ?>">
                                    
                                    <textarea name="additional_details" rows="5" placeholder="Add any other symptoms, observations or clinical notes you would like to track..." style="width:100%; padding:14px; border:1px solid #E2E8F0; border-radius:0; font-size:14px; background:#F8FAFC; margin-bottom:12px; resize:none;"><?php echo esc_textarea($profile['additional_details']); ?></textarea>
                                    <button type="submit" style="width:100%; padding:10px; background:#F1F5F9; border:1px solid #E2E8F0; border-radius:0; font-weight:700; color:#475569; cursor:pointer; transition:all 0.2s;">Save Additional Details</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <script>
                    jQuery('#dashboard-additional-details-form').on('submit', function(e) {
                        e.preventDefault();
                        const btn = jQuery(this).find('button');
                        btn.prop('disabled', true).text('Saving...');
                        jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', jQuery(this).serialize(), function(res) {
                            if(res.success) {
                                btn.text('Details Saved!').css('background', '#D1FAE5').css('color', '#065F46');
                                setTimeout(() => btn.prop('disabled', false).text('Save Additional Details').css('background', '#F1F5F9').css('color', '#475569'), 2000);
                            }
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
                        array( 'slug' => 'healthcare-quiz',        'page_url' => '/healthcare-quiz/',         'name' => 'IBD Health Quiz',        'tag' => 'Self-Assessment',  'desc' => 'A short, evidence-based questionnaire covering symptom patterns, dietary triggers, and lifestyle factors. Get an instant summary you can share with your clinician.', 'colors' => array( '#78bfbf', '#aedbdb', '#008080' ), 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.5M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z"/>' ),
                        array( 'slug' => 'ibd-recipes',            'page_url' => '/ibd-recipies/',            'name' => 'IBD Recipes & Meal Planner','tag' => 'Meal Planning', 'desc' => 'Browse EPA-rich, gut-friendly recipes with full nutrition data. Build weekly meal plans freely — saving plans prompts a quick signup.', 'colors' => array( '#def4f4', '#aedbdb', '#008080' ), 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9l9-7 9 7v11a2 2 0 01-2 2h-4a2 2 0 01-2-2v-4a2 2 0 00-2-2H10a2 2 0 00-2 2v4a2 2 0 01-2 2H2V9z" transform="translate(0,-1)"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14h8M8 11h8" />' ),
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
                                            <div class="item-title"><a href="<?php the_permalink(); ?>" style="text-decoration:none; color:inherit;"><?php the_title(); ?></a></div>
                                            <div class="item-meta"><?php echo get_the_date('M j, Y'); ?> • <?php echo get_post_type(); ?></div>
                                        </div>
                                    </div>
                                    <div style="display:flex; gap:8px;">
                                        <a href="<?php echo $p_link; ?>" class="card-link" style="border:1px solid #e2e8f0; padding:6px 12px; border-radius:0; color:#475569; font-weight:500;">View</a>
                                        <a href="mailto:?subject=<?php echo rawurlencode($p_title); ?>&body=<?php echo rawurlencode($p_link); ?>" class="card-link" style="border:1px solid #e2e8f0; padding:6px 12px; border-radius:0; color:#475569; font-weight:500;">Share</a>
                                        <button onclick="navigator.clipboard.writeText('<?php echo esc_js($p_link); ?>'); alert('Link copied!');" style="background:white; border:1px solid #e2e8f0; padding:6px 12px; border-radius:0; color:#475569; font-weight:500; cursor:pointer;">Copy Link</button>
                                        
                                        <button onclick="deleteBookmark(<?php echo get_the_ID(); ?>)" style="color:#EF4444; border:none; background:none; cursor:pointer; font-size:13px; font-weight:600; margin-left:8px;">Remove</button>
                                    </div>
                                </div>
                                <?php endwhile; wp_reset_postdata(); ?>
                            </div>
                         <?php endif; ?>
                    </div>
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

                case 'searches': ?>
                    <div class="dash-card">
                        <?php 
                        $searches = get_user_meta($current_user->ID, '_sla_saved_searches', true) ?: array();
                        if(empty($searches)): ?>
                             <div style="text-align:center; padding:48px; background:#F8FAFC; border:1px dashed #E2E8F0; border-radius:0;">
                                <p style="color:#64748B;">You haven't saved any searches yet.</p>
                            </div>
                        <?php else: ?>
                            <div class="dash-list">
                                <?php 
                                $searches_safe = is_array($searches) ? $searches : array();
                                foreach(array_reverse($searches_safe) as $s): ?>
                                <div class="list-item" style="padding:16px 0;">
                                    <div style="flex:1;">
                                        <div class="item-title"><a href="<?php echo esc_url($s['url']); ?>" style="text-decoration:none; color:inherit;"><?php echo esc_html($s['name']); ?></a></div>
                                        <div class="item-meta">Saved on <?php echo date('M j, Y', strtotime($s['date'])); ?></div>
                                    </div>
                                    <div style="display:flex; gap:12px;">
                                        <a href="<?php echo esc_url($s['url']); ?>" class="card-link">Run Search</a>
                                        <button onclick="deleteSearch('<?php echo $s['id']; ?>')" style="color:#EF4444; border:none; background:none; cursor:pointer; font-size:13px; font-weight:600;">Delete</button>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
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
                                foreach(array_reverse($notes_safe) as $note): ?>
                                <div class="list-item" style="padding:16px 0;">
                                    <div style="flex:1;">
                                        <div class="item-title"><?php echo esc_html($note['title'] ?: 'Untitled Note'); ?></div>
                                        <div class="item-meta">Last edited on <?php echo date('M j, Y', strtotime($note['date'])); ?></div>
                                    </div>
                                    <div style="display:flex; gap:12px;">
                                        <a href="?print_note=<?php echo $note['id']; ?>" target="_blank" class="card-link" style="color:#0EA5E9;">PDF/Print</a>
                                        <a href="/my-notes/?id=<?php echo $note['id']; ?>" class="card-link">Edit</a>
                                        <button onclick="deleteNote('<?php echo $note['id']; ?>')" style="color:#EF4444; border:none; background:none; cursor:pointer; font-size:13px; font-weight:600;">Delete</button>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
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
                                <a href="/ask-ai/" class="btn-primary" style="background:<?php echo $theme_primary; ?>; color:white; text-decoration:none; padding:10px 20px; border-radius:0; font-weight:600;">Start New Consultation</a>
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
                            <div style="padding:20px; border-top:1px solid #E2E8F0; background:white; display:flex; justify-content:flex-end;">
                                <button onclick="closeChatModal()" class="btn-primary" style="background:<?php echo $theme_primary; ?>; color:white; border:none; padding:10px 24px; border-radius:0; cursor:pointer; font-weight:600;">Close</button>
                            </div>
                        </div>
                    </div>
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
                            <?php if ( $unread_count > 0 ) : ?>
                                <span style="background: #008080; color: white; font-size: 12px; font-weight: 700; padding: 4px 10px; border-radius: 12px;"><?php echo (int) $unread_count; ?> new</span>
                            <?php endif; ?>
                        </header>

                        <?php if ( empty( $all_msgs ) ) : ?>
                            <div style="text-align: center; padding: 48px; background: #F8FAFC; border: 1px dashed #E2E8F0;">
                                <p style="color: #64748B; margin: 0;">No messages yet — the team will share updates and announcements here.</p>
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
                                                    setStatus(form, 'Reply sent — admins will see it shortly. Refresh to see your reply in the thread.', false);
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
        
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeChatModal() {
        document.getElementById('chat-modal').style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    function deleteChat(id) {
        if(!confirm('Delete this chat history permanently?')) return;
        jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', {
            action: 'vance_delete_chat', id: id, nonce: '<?php echo wp_create_nonce("vance_dashboard_nonce"); ?>'
        }, function(res) { if(res.success) location.reload(); else alert(res.data); });
    }
</script>

<?php endif; ?>
<?php get_footer('dashboard'); ?>
