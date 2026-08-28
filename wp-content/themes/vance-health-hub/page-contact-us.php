<?php
/**
 * Template Name: Contact Us
 */

// ── Form processing (before headers are sent) ─────────────────────────────
// vance_contact_recaptcha_verify() and vance_contact_process_submission() live
// in functions.php (loaded on every request, including admin-ajax.php) rather
// than here — this file is a page TEMPLATE, only included when WordPress
// actually renders /contact-us/. Defining the AJAX action's add_action() call
// here meant it never registered on admin-ajax.php requests at all, since
// admin-ajax.php never loads page templates — every AJAX submit attempt hit
// WordPress's generic "unknown action" fallback instead of this form's logic.
$contact_sent  = false;
$contact_error = '';

// Native-POST fallback for no-JS visitors, or if the AJAX path (functions.php)
// is ever unreachable (fetch blocked, JS disabled, etc.) — the form's
// method="post" action stays pointed at this same page for exactly that reason.
if ( isset( $_POST['vance_contact_submit'] ) && wp_verify_nonce( $_POST['vance_contact_nonce'], 'vance_contact_form' ) ) {
    $result = vance_contact_process_submission(
        sanitize_text_field( wp_unslash( $_POST['contact_name'] ?? '' ) ),
        sanitize_email( wp_unslash( $_POST['contact_email'] ?? '' ) ),
        sanitize_text_field( wp_unslash( $_POST['contact_subject'] ?? '' ) ),
        sanitize_textarea_field( wp_unslash( $_POST['contact_message'] ?? '' ) ),
        sanitize_text_field( wp_unslash( $_POST['vance_recaptcha_token'] ?? '' ) )
    );
    $contact_sent  = $result['success'];
    $contact_error = $result['error'];
}

get_header(); ?>

<main id="main-content">

    <?php
    // ── Style helper (unique name to avoid collision with other templates) ──
    function vance_get_style_contact( $prefix, $default_bg = '' ) {
        $bg       = get_theme_mod( $prefix . '_bg', $default_bg );
        $t_color  = get_theme_mod( $prefix . '_title_color' );
        $t_size   = get_theme_mod( $prefix . '_title_size' );
        $tx_color = get_theme_mod( $prefix . '_text_color' );
        $tx_size  = get_theme_mod( $prefix . '_text_size' );
        $tag_bg   = get_theme_mod( $prefix . '_tag_bg' );
        $tag_col  = get_theme_mod( $prefix . '_tag_color' );

        $section = $bg ? "background:$bg;" : '';

        $title = '';
        if ( $t_color ) $title .= "color:$t_color !important;";
        if ( $t_size )  $title .= 'font-size:' . ( is_numeric( $t_size ) ? $t_size . 'px' : $t_size ) . ' !important;';

        $text = '';
        if ( $tx_color ) $text .= "color:$tx_color !important;";
        if ( $tx_size )  $text .= 'font-size:' . ( is_numeric( $tx_size ) ? $tx_size . 'px' : $tx_size ) . ' !important;';

        $tag = '';
        if ( $tag_bg  ) $tag .= "background:$tag_bg !important;";
        if ( $tag_col ) $tag .= "color:$tag_col !important;";

        return compact( 'section', 'title', 'text', 'tag' );
    }
    ?>

    <!-- ══ HERO SECTION ══════════════════════════════════════════════════ -->
    <?php
    // Two hero designs share this slot, chosen in Customize → Page - Contact Us
    // → Hero Section → "Contact hero design":
    //   classic   — the dark navy band below (default; nothing changes on deploy).
    //   spotlight — the light, action-led hero, inc/page-hero-spotlight.php.
    // The spotlight reads the same tag/title/description settings the classic
    // hero uses, so switching is a change of layout only — no copy moves and
    // nothing has to be re-typed to switch back.
    if ( function_exists( 'vance_page_hero_spotlight_active' ) && vance_page_hero_spotlight_active( 'contact' ) ) :
        vance_render_page_hero_spotlight( 'contact' );
    else :

    $hero_img      = vance_get_theme_mod( 'vance_contact_hero_img',   get_template_directory_uri() . '/assets/img/hcp_hero.png' );
    $hero_bg_color = vance_get_theme_mod( 'vance_contact_hero_bg_color' );
    $hero_tag      = vance_get_theme_mod( 'vance_contact_hero_tag',   'Get in Touch' );
    $hero_title    = vance_get_theme_mod( 'vance_contact_hero_title', 'We\'d Love to <span class="highlight">Hear From You</span>' );
    $hero_desc     = vance_get_theme_mod( 'vance_contact_hero_desc',  'Whether you\'re a patient, healthcare professional, researcher, or media contact, our team is here to help. Reach out and we\'ll respond within one business day.' );

    $hero_styles   = vance_get_style_contact( 'vance_contact_hero' );
    $hero_overlay  = max(0, min(100, absint(vance_get_theme_mod('vance_contact_hero_overlay', 78)))) / 100;
    $hero_overlay_bottom = min(1, $hero_overlay + 0.15);
    $hero_bg_style = "background: linear-gradient(rgba(10,25,41,{$hero_overlay}), rgba(10,25,41,{$hero_overlay_bottom})), url('" . esc_url( $hero_img ) . "') no-repeat center center; background-size: cover;";
    if ( $hero_bg_color ) {
        $hero_bg_style = "background: {$hero_bg_color};";
    }
    ?>
    <section class="vance-contact-hero" style="padding: 72px 0 116px; min-height: 332px; display: flex; align-items: flex-start; <?php echo $hero_bg_style; ?> position: relative; overflow: hidden;">
        <div class="container" style="position: relative; z-index: 1;">
            <div style="max-width: 800px;">
                <span class="tag-label" style="<?php echo $hero_styles['tag']; ?>"><?php echo esc_html( $hero_tag ); ?></span>
                <h1 style="font-weight: 900; margin: 16px 0 20px; font-family: 'Outfit', sans-serif; line-height: 1.1;
                    <?php echo strpos( $hero_styles['title'], 'font-size' ) === false ? 'font-size: clamp(36px,5vw,60px);' : ''; ?>
                    <?php echo strpos( $hero_styles['title'], 'color' ) === false    ? 'color: white;' : ''; ?>
                    <?php echo $hero_styles['title']; ?>">
                    <?php echo wp_kses_post( $hero_title ); ?>
                </h1>
                <p style="max-width:600px;line-height:1.7;margin:0 0 32px; <?php if(strpos($hero_styles['text'],'font-size')===false) echo 'font-size:20px;'; ?> <?php if(strpos($hero_styles['text'],'color')===false) echo 'color:rgba(255,255,255,.82);'; ?> <?php echo $hero_styles['text']; ?>">
                    <?php echo esc_html( $hero_desc ); ?>
                </p>
            </div>
        </div>
    </section>
    <?php endif; // end classic / spotlight hero ?>

    <!-- ══ MAIN CONTENT: DETAILS + FORM ═════════════════════════════════ -->
    <?php
    $intro_title  = vance_get_theme_mod( 'vance_contact_intro_title', 'How Can We Help?' );
    $intro_text   = vance_get_theme_mod( 'vance_contact_intro_text',  'Vance Medical is committed to providing exceptional support to every member of our community. Use the form to send us a message, or reach us directly through any of the channels below.' );
    $detail_email = vance_get_theme_mod( 'vance_contact_email',   'team@vancemedicalfoods.co.uk' );
    $detail_phone = vance_get_theme_mod( 'vance_contact_phone',   '+44 (0)1628 526 005' );
    $detail_hours = vance_get_theme_mod( 'vance_contact_hours',   'Monday – Friday, 9:00 am – 5:00 pm GMT' );

    $social_linkedin  = vance_get_theme_mod( 'vance_social_linkedin' );
    $social_facebook  = vance_get_theme_mod( 'vance_social_facebook' );
    $social_twitter   = vance_get_theme_mod( 'vance_social_twitter' );
    $social_instagram = vance_get_theme_mod( 'vance_social_instagram' );
    ?>
    <section class="section-padding" style="background: var(--accent-color, #f8fafc);">
        <div class="container">
            <div class="vcontact-grid" style="display: grid; grid-template-columns: minmax(0,1fr) minmax(0,1.4fr); gap: 60px; align-items: start;">

                <!-- ─ Left column: details ───────────────────────────── -->
                <div>
                    <h2 style="color: var(--secondary-color); font-size: clamp(26px,3vw,36px); font-weight: 800; margin-bottom: 16px; line-height: 1.2;">
                        <?php echo esc_html( $intro_title ); ?>
                    </h2>
                    <p style="color: var(--text-light); font-size: 16px; line-height: 1.75; margin-bottom: 40px;">
                        <?php echo esc_html( $intro_text ); ?>
                    </p>

                    <!-- Contact detail cards -->
                    <div style="display: flex; flex-direction: column; gap: 20px; margin-bottom: 40px;">

                        <?php
                        // 'color' MUST be a literal hex: the tile background
                        // below is built as "<color>1a" to get a 10% tint, and
                        // that only works on a hex. Email used
                        // var(--primary-color) here, so the tile resolved to
                        // "var(--primary-color)1a" -- invalid, dropped by the
                        // parser, and the icon sat on no background at all
                        // while Phone and Office Hours (both hex) rendered
                        // theirs correctly.
                        $details = array(
                            array(
                                'label' => 'Email',
                                'value' => $detail_email,
                                'href'  => 'mailto:' . antispambot( $detail_email ),
                                'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>',
                                'color' => '#8b5cf6',
                            ),
                            array(
                                'label' => 'Phone',
                                'value' => $detail_phone,
                                'href'  => 'tel:' . preg_replace( '/[^+0-9]/', '', $detail_phone ),
                                'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>',
                                'color' => '#0ea5e9',
                            ),
                            array(
                                'label' => 'Office Hours',
                                'value' => $detail_hours,
                                'href'  => false,
                                'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                                'color' => '#10b981',
                            ),
                        );
                        foreach ( $details as $d ) : ?>
                        <div style="display: flex; gap: 16px; align-items: flex-start; background: white; padding: 20px 24px; border-radius: var(--radius-surface, 14px); box-shadow: 0 1px 6px rgba(0,0,0,.06);">
                            <div style="flex-shrink: 0; width: 44px; height: 44px; border-radius: var(--radius-control, 6px); background: <?php echo $d['color']; ?>1a; display: flex; align-items: center; justify-content: center;">
                                <svg width="22" height="22" fill="none" stroke="<?php echo $d['color']; ?>" viewBox="0 0 24 24"><?php echo $d['icon']; ?></svg>
                            </div>
                            <div>
                                <p style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: var(--text-light); margin: 0 0 4px;"><?php echo esc_html( $d['label'] ); ?></p>
                                <?php if ( $d['href'] ) : ?>
                                    <a href="<?php echo esc_url( $d['href'] ); ?>" style="font-size: 15px; color: var(--secondary-color); text-decoration: none; font-weight: 500; line-height: 1.5;"><?php echo esc_html( $d['value'] ); ?></a>
                                <?php else : ?>
                                    <p style="font-size: 15px; color: var(--secondary-color); margin: 0; font-weight: 500; line-height: 1.5;"><?php echo esc_html( $d['value'] ); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Social links -->
                    <?php
                    $socials = array(
                        'linkedin'  => array(
                            'url'  => $social_linkedin,
                            'name' => 'LinkedIn',
                            'icon' => '<path d="M16 8a6 6 0 016 6v7h-4v-7a2 2 0 00-2-2 2 2 0 00-2 2v7h-4v-7a6 6 0 016-6zM2 9h4v12H2z"/><circle cx="4" cy="4" r="2"/>',
                            'fill' => true,
                        ),
                        'facebook'  => array(
                            'url'  => $social_facebook,
                            'name' => 'Facebook',
                            'icon' => '<path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"/>',
                            'fill' => true,
                        ),
                        'twitter'   => array(
                            'url'  => $social_twitter,
                            'name' => 'X',
                            'icon' => '<path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>',
                            'fill' => true,
                        ),
                        'instagram' => array(
                            'url'  => $social_instagram,
                            'name' => 'Instagram',
                            'icon' => '<rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1112.63 8 4 4 0 0116 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/>',
                            'fill' => false,
                        ),
                    );

                    // Only show social block if at least one is configured
                    $has_social = array_filter( array_column( $socials, 'url' ) );
                    if ( $has_social ) : ?>
                    <div>
                        <p style="font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: var(--text-light); margin-bottom: 14px;">Follow Us</p>
                        <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                            <?php foreach ( $socials as $key => $s ) :
                                if ( empty( $s['url'] ) ) continue; ?>
                            <a href="<?php echo esc_url( $s['url'] ); ?>" target="_blank" rel="noopener noreferrer"
                               title="<?php echo esc_attr( $s['name'] ); ?>"
                               style="display: inline-flex; align-items: center; justify-content: center; width: 44px; height: 44px; border-radius: var(--radius-control, 6px); background: white; box-shadow: 0 1px 6px rgba(0,0,0,.1); color: var(--secondary-color); transition: transform .15s, box-shadow .15s;"
                               onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 4px 14px rgba(0,0,0,.15)';"
                               onmouseout="this.style.transform='';this.style.boxShadow='0 1px 6px rgba(0,0,0,.1)';">
                                <svg width="20" height="20" fill="<?php echo $s['fill'] ? 'currentColor' : 'none'; ?>" stroke="<?php echo $s['fill'] ? 'none' : 'currentColor'; ?>" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><?php echo $s['icon']; ?></svg>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- ─ Right column: contact form ─────────────────────── -->
                <div style="background: white; border-radius: var(--radius-surface, 14px); padding: 48px 44px; box-shadow: 0 4px 32px rgba(10,25,41,.1);">

                    <!-- Rendered server-side for the no-JS fallback (native POST below actually
                         reached this page) AND used as the JS AJAX success target — JS toggles
                         these two containers directly instead of relying on a page reload. -->
                    <div id="contact-form-success" style="<?php echo $contact_sent ? '' : 'display:none;'; ?> text-align: center; padding: 40px 0;">
                        <div style="width: 72px; height: 72px; background: #10b981; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px;">
                            <svg width="36" height="36" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                <path d="M20 6L9 17l-5-5"/>
                            </svg>
                        </div>
                        <h3 style="font-size: 24px; font-weight: 800; color: var(--secondary-color); margin-bottom: 12px;">Message Sent!</h3>
                        <p style="color: var(--text-light); font-size: 16px; line-height: 1.7;">Thank you for reaching out. A member of our team will get back to you within one business day.</p>
                    </div>

                    <div id="contact-form-wrapper" style="<?php echo $contact_sent ? 'display:none;' : ''; ?>">

                    <h3 style="font-size: 24px; font-weight: 800; color: var(--secondary-color); margin: 0 0 8px;">Send Us a Message</h3>
                    <p style="color: var(--text-light); font-size: 15px; margin: 0 0 32px;">Fields marked <span style="color: var(--primary-color);">*</span> are required.</p>

                    <?php if ( $contact_error ) : ?>
                    <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: var(--radius-surface, 14px); padding: 14px 18px; margin-bottom: 24px; color: #dc2626; font-size: 14px;">
                        <?php echo esc_html( $contact_error ); ?>
                    </div>
                    <?php endif; ?>

                    <form method="post" action="<?php echo esc_url( get_permalink() ); ?>#contact-form" id="contact-form">
                        <?php wp_nonce_field( 'vance_contact_form', 'vance_contact_nonce' ); ?>
                        <input type="hidden" name="vance_recaptcha_token" id="vance_recaptcha_token" value="">
                        <div id="contact-form-client-error" style="display:none; background: #fef2f2; border: 1px solid #fecaca; border-radius: var(--radius-surface, 14px); padding: 14px 18px; margin-bottom: 24px; color: #dc2626; font-size: 14px;"></div>

                        <div class="vcontact-name-email-grid" style="display: grid; grid-template-columns: minmax(0,1fr) minmax(0,1fr); gap: 20px; margin-bottom: 20px;">
                            <div>
                                <label for="contact_name" style="display: block; font-size: 13px; font-weight: 700; color: var(--secondary-color); margin-bottom: 8px; letter-spacing: 0.3px;">
                                    Full Name <span style="color: var(--primary-color);">*</span>
                                </label>
                                <input type="text" id="contact_name" name="contact_name" required
                                       value="<?php echo esc_attr( $_POST['contact_name'] ?? '' ); ?>"
                                       placeholder="Your full name"
                                       style="width: 100%; padding: 13px 16px; border: 1.5px solid #e2e8f0; border-radius: var(--radius-field, 10px); font-size: 15px; color: var(--secondary-color); background: #f8fafc; transition: border-color .2s; outline: none; box-sizing: border-box;"
                                       onfocus="this.style.borderColor='var(--primary-color)';this.style.background='white';"
                                       onblur="this.style.borderColor='#e2e8f0';this.style.background='#f8fafc';">
                            </div>
                            <div>
                                <label for="contact_email" style="display: block; font-size: 13px; font-weight: 700; color: var(--secondary-color); margin-bottom: 8px; letter-spacing: 0.3px;">
                                    Email Address <span style="color: var(--primary-color);">*</span>
                                </label>
                                <input type="email" id="contact_email" name="contact_email" required
                                       value="<?php echo esc_attr( $_POST['contact_email'] ?? '' ); ?>"
                                       placeholder="your@email.com"
                                       style="width: 100%; padding: 13px 16px; border: 1.5px solid #e2e8f0; border-radius: var(--radius-field, 10px); font-size: 15px; color: var(--secondary-color); background: #f8fafc; transition: border-color .2s; outline: none; box-sizing: border-box;"
                                       onfocus="this.style.borderColor='var(--primary-color)';this.style.background='white';"
                                       onblur="this.style.borderColor='#e2e8f0';this.style.background='#f8fafc';">
                            </div>
                        </div>

                        <div style="margin-bottom: 20px;">
                            <label for="contact_subject" style="display: block; font-size: 13px; font-weight: 700; color: var(--secondary-color); margin-bottom: 8px; letter-spacing: 0.3px;">
                                Subject
                            </label>
                            <select id="contact_subject" name="contact_subject"
                                    style="width: 100%; padding: 13px 16px; border: 1.5px solid #e2e8f0; border-radius: var(--radius-field, 10px); font-size: 15px; color: var(--secondary-color); background: #f8fafc; transition: border-color .2s; outline: none; box-sizing: border-box; appearance: none; cursor: pointer;"
                                    onfocus="this.style.borderColor='var(--primary-color)';this.style.background='white';"
                                    onblur="this.style.borderColor='#e2e8f0';this.style.background='#f8fafc';">
                                <option value="">Select a topic…</option>
                                <option value="Patient Enquiry"      <?php selected( ( $_POST['contact_subject'] ?? '' ), 'Patient Enquiry' ); ?>>Patient Enquiry</option>
                                <option value="Healthcare Professional" <?php selected( ( $_POST['contact_subject'] ?? '' ), 'Healthcare Professional' ); ?>>Healthcare Professional</option>
                                <option value="Media & Press"        <?php selected( ( $_POST['contact_subject'] ?? '' ), 'Media & Press' ); ?>>Media &amp; Press</option>
                                <option value="Research Collaboration" <?php selected( ( $_POST['contact_subject'] ?? '' ), 'Research Collaboration' ); ?>>Research Collaboration</option>
                                <option value="Partnership"          <?php selected( ( $_POST['contact_subject'] ?? '' ), 'Partnership' ); ?>>Partnership</option>
                                <option value="Other"                <?php selected( ( $_POST['contact_subject'] ?? '' ), 'Other' ); ?>>Other</option>
                            </select>
                        </div>

                        <div style="margin-bottom: 28px;">
                            <label for="contact_message" style="display: block; font-size: 13px; font-weight: 700; color: var(--secondary-color); margin-bottom: 8px; letter-spacing: 0.3px;">
                                Message <span style="color: var(--primary-color);">*</span>
                            </label>
                            <textarea id="contact_message" name="contact_message" required rows="6"
                                      placeholder="How can we help you?"
                                      style="width: 100%; padding: 13px 16px; border: 1.5px solid #e2e8f0; border-radius: var(--radius-field, 10px); font-size: 15px; color: var(--secondary-color); background: #f8fafc; transition: border-color .2s; outline: none; box-sizing: border-box; resize: vertical; font-family: inherit; line-height: 1.6;"
                                      onfocus="this.style.borderColor='var(--primary-color)';this.style.background='white';"
                                      onblur="this.style.borderColor='#e2e8f0';this.style.background='#f8fafc';"><?php echo esc_textarea( $_POST['contact_message'] ?? '' ); ?></textarea>
                        </div>

                        <button type="submit" name="vance_contact_submit" value="1" class="btn btn-primary"
                                style="width: 100%; padding: 16px; font-size: 16px; font-weight: 700; border: none; border-radius: var(--radius-control, 6px); cursor: pointer; letter-spacing: 0.3px;">
                            Send Message
                            <svg style="display:inline-block;vertical-align:middle;margin-left:8px;" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                <line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>
                            </svg>
                        </button>

                        <p style="font-size: 12px; color: var(--text-light); text-align: center; margin-top: 16px; line-height: 1.6;">
                            By submitting this form you agree to our <a href="/privacy-policy" style="color: var(--primary-color);">Privacy Policy</a>. We never share your data.
                        </p>
                    </form>

                    </div><!-- / #contact-form-wrapper -->

                    <?php $recaptcha_site_key = vance_get_theme_mod( 'vance_recaptcha_site_key', '' ); ?>
                    <?php if ( $recaptcha_site_key ) : ?>
                    <script src="https://www.google.com/recaptcha/api.js?render=<?php echo esc_attr( $recaptcha_site_key ); ?>"></script>
                    <?php endif; ?>
                    <script>
                    (function () {
                        var form     = document.getElementById( 'contact-form' );
                        var wrapper  = document.getElementById( 'contact-form-wrapper' );
                        var success  = document.getElementById( 'contact-form-success' );
                        var errBox   = document.getElementById( 'contact-form-client-error' );
                        var siteKey  = <?php echo wp_json_encode( $recaptcha_site_key ); ?>;
                        var tokenEl  = document.getElementById( 'vance_recaptcha_token' );
                        var submitBtn = form ? form.querySelector( 'button[type="submit"]' ) : null;
                        var ajaxUrl  = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
                        if ( ! form ) {
                            return;
                        }

                        // Submits via fetch()/admin-ajax.php instead of a native form POST +
                        // page reload. The native-POST path (form's method/action attributes)
                        // stays in the markup purely as a no-JS fallback — with JS active this
                        // handler always wins by calling preventDefault() unconditionally.
                        //
                        // Reasons for the switch, found while chasing a "form resets with no
                        // feedback" report: (1) this page can be served from LiteSpeed's
                        // full-page cache, freezing the nonce at whatever moment the cache last
                        // regenerated, so a stale nonce reaching the server via a real page POST
                        // showed neither an error nor a success state; (2) in some browser/
                        // automation contexts form.requestSubmit() silently produced no network
                        // request at all — a full-page-navigation submission has no way to
                        // recover or even report that. An AJAX POST sidesteps both: the nonce is
                        // fetched fresh moments before use via this same request, and success/
                        // failure is read directly from the fetch() promise rather than inferred
                        // from whether a navigation happened to occur.
                        //
                        // The nonce still needs an explicit refresh here: hitting an uncached
                        // endpoint doesn't make the nonce *value* fresh if that value was read
                        // out of the page's own (possibly cached) HTML. Fetched via the same
                        // never-page-cached admin-ajax.php action used for the login/signup
                        // forms elsewhere on this site; on failure the original page-rendered
                        // value is kept as a fallback rather than blocking the submission.
                        var nonceReady = fetch( ajaxUrl + '?action=vance_refresh_auth_nonces', { credentials: 'same-origin' } )
                            .then( function ( r ) { return r.json(); } )
                            .then( function ( data ) { return ( data.success && data.data.contact ) ? data.data.contact : form.vance_contact_nonce.value; } )
                            .catch( function () { return form.vance_contact_nonce.value; } );

                        function showClientError( msg ) {
                            errBox.textContent = msg;
                            errBox.style.display = 'block';
                        }

                        function fieldErrors() {
                            var name    = form.contact_name.value.trim();
                            var email   = form.contact_email.value.trim();
                            var message = form.contact_message.value.trim();
                            if ( ! name || ! email || ! message ) {
                                return 'Please fill in all required fields.';
                            }
                            if ( ! /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test( email ) ) {
                                return 'Please enter a valid email address.';
                            }
                            return '';
                        }

                        function getRecaptchaToken() {
                            if ( ! siteKey || ! window.grecaptcha ) {
                                return Promise.resolve( '' );
                            }
                            // grecaptcha.ready()/.execute() can fail to ever call back at all —
                            // not reject, just never settle (seen with certain domain/site-key
                            // mismatches and some automation contexts). The existing .catch()
                            // only covers a genuine rejection; a hang here would otherwise leave
                            // the submission permanently stuck. A hard timeout guarantees this
                            // always resolves either way, with a real or an empty token.
                            return new Promise( function ( resolve ) {
                                var settled = false;
                                var settle  = function ( token ) {
                                    if ( settled ) { return; }
                                    settled = true;
                                    resolve( token || '' );
                                };
                                setTimeout( function () { settle( '' ); }, 10000 );
                                grecaptcha.ready( function () {
                                    grecaptcha.execute( siteKey, { action: 'contact_form' } )
                                        .then( function ( token ) { tokenEl.value = token; settle( token ); } )
                                        .catch( function () { settle( '' ); } );
                                } );
                            } );
                        }

                        function submitViaAjax( nonce, token ) {
                            var body = new URLSearchParams();
                            body.set( 'action', 'vance_contact_submit' );
                            body.set( 'nonce', nonce );
                            body.set( 'contact_name', form.contact_name.value.trim() );
                            body.set( 'contact_email', form.contact_email.value.trim() );
                            body.set( 'contact_subject', form.contact_subject.value );
                            body.set( 'contact_message', form.contact_message.value.trim() );
                            body.set( 'vance_recaptcha_token', token );

                            return fetch( ajaxUrl, {
                                method: 'POST',
                                credentials: 'same-origin',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                body: body.toString()
                            } )
                                .then( function ( r ) { return r.json(); } )
                                .then( function ( data ) {
                                    if ( data.success ) {
                                        wrapper.style.display = 'none';
                                        success.style.display = '';
                                        success.scrollIntoView( { behavior: 'smooth', block: 'center' } );
                                    } else {
                                        showClientError( ( data.data && data.data.message ) || 'Something went wrong, please try again.' );
                                    }
                                } )
                                .catch( function () {
                                    showClientError( 'Network error, please try again or email us directly.' );
                                } );
                        }

                        form.addEventListener( 'submit', function ( e ) {
                            e.preventDefault();
                            errBox.style.display = 'none';
                            var msg = fieldErrors();
                            if ( msg ) {
                                showClientError( msg );
                                return;
                            }
                            if ( submitBtn ) { submitBtn.disabled = true; }
                            Promise.all( [ nonceReady, getRecaptchaToken() ] )
                                .then( function ( results ) { return submitViaAjax( results[0], results[1] ); } )
                                .finally( function () {
                                    if ( submitBtn ) { submitBtn.disabled = false; }
                                } );
                        } );
                    })();
                    </script>
                </div><!-- / form card -->

            </div><!-- / grid -->
        </div>
    </section>

</main>

<style>
/* The details/form layout and the Name/Email row are built with inline-style
   CSS Grid (1fr tracks), which have no explicit mobile breakpoint. 1fr alone
   sizes a track to at least its content's min-content width, so on narrow
   screens the columns refused to shrink and forced the whole page to render
   zoomed out. minmax(0,1fr) on the inline styles stops that blowout; these
   breakpoints additionally stack the columns so fields stay full-width and
   readable instead of being squeezed side by side.
*/
@media (max-width: 900px) {
    .vcontact-grid { grid-template-columns: 1fr !important; }
}
@media (max-width: 520px) {
    .vcontact-name-email-grid { grid-template-columns: 1fr !important; }
}
</style>

<?php get_footer(); ?>
