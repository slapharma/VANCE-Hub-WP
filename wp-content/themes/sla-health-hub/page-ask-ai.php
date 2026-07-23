<?php
/**
 * Template Name: Ask AI Page
 * Simplified with single clinical assistant and save functionality
 */

get_header();

// Get customizer settings
$hero_bg = vance_get_theme_mod('vance_askai_hero_bg', get_template_directory_uri() . '/assets/img/about_hero.png');
$hero_title = vance_get_theme_mod('vance_askai_hero_title', 'Ask AI');
$hero_subtitle = vance_get_theme_mod('vance_askai_hero_subtitle', 'Ask anything about IBD, clinical nutrition and gastrointestinal health. Every answer is drawn from articles published on the Vance Medical Hub, with links to the sources used.');
$hero_badge = vance_get_theme_mod('vance_askai_hero_badge', 'Information Assistant');
$askai_overlay = max(0, min(100, absint(vance_get_theme_mod('vance_askai_hero_overlay', 85)))) / 100;
$askai_overlay_bottom = min(1, $askai_overlay + 0.05);

// Define Single AI Agent
$agent_data = array(
    'name' => 'AI Information Assistant',
    'icon' => '<svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>',
    'color' => '#008080',
    'description' => 'Your Vance Medical Hub assistant. Ask anything about IBD, gut health, or clinical nutrition.',
    'training' => 'Answers are grounded in the articles published on this hub.',
    'abilities' => 'Evidence-based analysis, research synthesis, and clinical protocol insights.',
    'limitations' => 'Informational only. Not medical advice.',
);
?>

<style>
.askai-page {
    background: #F8FAFC;
    min-height: 100vh;
}

.askai-hero {
    background: linear-gradient(rgba(10, 25, 41, <?php echo esc_attr($askai_overlay); ?>), rgba(10, 25, 41, <?php echo esc_attr($askai_overlay_bottom); ?>)), url('<?php echo esc_url($hero_bg); ?>') center/cover;
    padding: 80px 0;
    color: white;
    text-align: center;
    border-bottom: 3px solid var(--primary-color);
    position: relative;
}

.askai-hero h1 {
    font-family: 'Outfit', sans-serif;
    font-size: 56px;
    font-weight: 800;
    margin: 0 0 16px 0;
    letter-spacing: -1px;
    text-transform: uppercase;
}

.askai-hero p {
    font-size: 20px;
    color: #CBD5E1;
    max-width: 800px;
    margin: 0 auto;
    font-weight: 500;
}

.hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(255,255,255,0.1);
    padding: 6px 16px;
    border-radius: 0;
    margin-bottom: 24px;
    border: 1px solid rgba(255,255,255,0.2);
}

.status-dot {
    width: 8px;
    height: 8px;
    background: #22C55E;
    border-radius: 0;
    box-shadow: 0 0 10px #22C55E;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}

.askai-container {
    max-width: 1000px;
    margin: -40px auto 0;
    padding: 0 20px 60px;
    position: relative;
    z-index: 10;
}

.chat-main {
    background: white;
    border-radius: 0;
    box-shadow: 0 20px 40px -10px rgba(0,0,0,0.1);
    border: 2px solid var(--primary-color);
    overflow: hidden;
}

.agent-profile {
    padding: 24px 32px;
    background: #F8FAFC;
    border-bottom: 1px solid #E2E8F0;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.profile-left {
    display: flex;
    align-items: center;
    gap: 16px;
}

.agent-avatar {
    width: 48px;
    height: 48px;
    background: var(--primary-color);
    border-radius: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: white;
}

.agent-info h2 {
    font-family: 'Outfit', sans-serif;
    font-size: 18px;
    font-weight: 800;
    color: #0F172A;
    margin: 0;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.agent-status {
    font-size: 12px;
    color: #22C55E;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 6px;
}

.chat-autosave-note {
    margin: 0;
    font-size: 13px;
    color: #4B5563;
    text-align: right;
}

.chat-autosave-note a {
    color: var(--primary-color);
    font-weight: 600;
    text-decoration: underline;
}

.chat-container {
    padding: 0;
}

@media (max-width: 640px) {
    .agent-profile { flex-direction: column; align-items: flex-start; gap: 12px; }
    .chat-autosave-note { text-align: left; }
}
</style>

<div class="askai-page">
    <section class="askai-hero">
        <div class="container">
            <div class="hero-badge">
                <div class="status-dot"></div>
                <span style="color: #cbd5e1; font-size: 12px; font-weight: 800; letter-spacing: 1px; text-transform: uppercase;">
                    <?php echo esc_html($hero_badge); ?>
                </span>
            </div>
            
            <h1><?php echo esc_html($hero_title); ?></h1>
            <p><?php echo esc_html($hero_subtitle); ?></p>
        </div>
    </section>

    <div class="askai-container">
        <main class="chat-main">
            <div class="agent-profile">
                <div class="profile-left">
                    <div class="agent-avatar">
                        <?php echo $agent_data['icon']; ?>
                    </div>
                    <div class="agent-info">
                        <h2>AI INFORMATION ASSISTANT</h2>
                        <div class="agent-status"><span style="width: 6px; height: 6px; background: #22C55E; border-radius: 0;"></span> ONLINE & READY</div>
                    </div>
                </div>
                
                <p class="chat-autosave-note">
                    <?php
                    if ( is_user_logged_in() ) {
                        printf(
                            /* translators: %s: dashboard URL */
                            esc_html__( 'Conversations save automatically to %s.', 'sla-health-hub' ),
                            '<a href="' . esc_url( home_url( '/dashboard/?section=ai-chats' ) ) . '">' . esc_html__( 'your dashboard', 'sla-health-hub' ) . '</a>'
                        );
                    } else {
                        printf(
                            /* translators: %s: login URL */
                            esc_html__( '%s to save your conversations.', 'sla-health-hub' ),
                            '<a href="' . esc_url( home_url( '/login/' ) ) . '">' . esc_html__( 'Log in', 'sla-health-hub' ) . '</a>'
                        );
                    }
                    ?>
                </p>
            </div>

            <div class="chat-container">
                <!-- Mount point for assets/js/vance-askai.js — the same engine
                     powers the site-wide modal and highlight-to-ask. -->
                <div id="vance-askai-inline"></div>
            </div>
        </main>

        <div style="text-align: left; margin-top: 32px; color: #64748b; font-size: 13px; max-width: 800px; margin-left: auto; margin-right: auto; padding: 20px; background: white; border-radius: 0; border: 1px solid #E2E8F0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
            <p style="margin: 0; line-height: 1.65;"><strong>How to use this tool.</strong> Vance AI gives general information about gastrointestinal health, IBD and clinical nutrition. It is an automated assistant: it can be wrong or out of date, it does not know your personal medical history, and it does not provide a diagnosis, prescription or treatment plan. It is not a substitute for advice from your own healthcare team and must not be used for urgent or emergency needs. If you feel unwell or think you may have a medical emergency, call 999 or NHS 111 now. Please do not enter information that identifies you, conversations may be processed by a third-party AI provider and stored to improve the service. By using this tool you accept that it is for general information only.</p>
        </div>
    </div>
</div>


<?php get_footer(); ?>
