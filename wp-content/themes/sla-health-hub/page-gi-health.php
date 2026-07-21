<?php
/**
 * Template Name: GI Health Hub
 *
 * Landing page for the GI Health section: 7 condition cards, gut-statistics band,
 * and a dashboard CTA. Condition child pages use the "GI Health Condition" template.
 *
 * All copy and colours are editable via Appearance → Customize → Page — GI Health.
 *
 * Requires child pages with slugs:
 *   inflammatory-bowel-disease, ulcerative-colitis, crohns-disease,
 *   microscopic-colitis, irritable-bowel-syndrome, colorectal-cancer,
 *   diverticular-disease
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

get_header();

$tmpl = get_template_directory_uri();

/* Customizer helper shorthand */
$cm = fn( string $key, string $fb = '' ) => vance_get_theme_mod( $key, $fb );

/* ── Hero ── */
$hero_eyebrow   = $cm( 'vance_gi_hub_hero_eyebrow', 'GI Health' );
$hero_heading   = $cm( 'vance_gi_hub_hero_heading', 'Gastro Conditions Explained' );
$hero_lede      = $cm( 'vance_gi_hub_hero_lede', 'Expert reviewed information. Written in plain language to help you understand, prepare and manage.' );
$hero_btn1_txt  = $cm( 'vance_gi_hub_hero_btn1_text', 'Explore conditions' );
$hero_btn1_url  = $cm( 'vance_gi_hub_hero_btn1_url', '#conditions' );
$hero_btn2_txt  = $cm( 'vance_gi_hub_hero_btn2_text', 'My Dashboard' );
$hero_btn2_url  = $cm( 'vance_gi_hub_hero_btn2_url', home_url( '/dashboard/' ) );
$hero_bg        = $cm( 'vance_gi_hub_hero_bg_image', '' );
$hero_overlay   = absint( $cm( 'vance_gi_hub_hero_bg_overlay', '70' ) );

/* ── Grid ── */
$grid_heading   = $cm( 'vance_gi_hub_grid_heading', 'Learn more about common GI conditions' );
$grid_subtitle  = $cm( 'vance_gi_hub_grid_subtitle', 'Understanding your digestive health, one condition at a time' );

/* ── Stats ── */
$stats_heading  = $cm( 'vance_gi_hub_stats_heading', "You're not alone" );
$stats_desc     = $cm( 'vance_gi_hub_stats_desc', "Digestive conditions are more common than you might think. You're in good company." );
$stat1_num      = $cm( 'vance_gi_hub_stat1_num', '1 in 7' );
$stat1_lbl      = $cm( 'vance_gi_hub_stat1_label', 'UK adults live with IBS symptoms' );
$stat2_num      = $cm( 'vance_gi_hub_stat2_num', '500,000' );
$stat2_lbl      = $cm( 'vance_gi_hub_stat2_label', 'People in the UK live with inflammatory bowel disease' );
$stat3_num      = $cm( 'vance_gi_hub_stat3_num', '9 in 10' );
$stat3_lbl      = $cm( 'vance_gi_hub_stat3_label', 'Survive bowel cancer when it is found at the earliest stage' );

/* ── CTA ── */
$cta_heading    = $cm( 'vance_gi_hub_cta_heading', 'Track your symptoms and learn what works for you' );
$cta_desc       = $cm( 'vance_gi_hub_cta_desc', 'The Vance Health Hub dashboard brings together symptom trackers, evidence-based tools and clinician-reviewed resources to help you manage your gut health day to day.' );
$cta_btn_txt    = $cm( 'vance_gi_hub_cta_btn_text', 'Go to My Dashboard' );
$cta_btn_url    = $cm( 'vance_gi_hub_cta_btn_url', home_url( '/dashboard/' ) );

/* Helper: resolve a child-page URL by slug. Falls back to home_url() path. */
function vance_gi_page_url( string $slug ): string {
    $page = get_page_by_path( 'gi-health/' . $slug );
    if ( $page ) {
        return get_permalink( $page );
    }
    return home_url( '/gi-health/' . $slug . '/' );
}

$conditions = [
    [
        'slug'  => 'inflammatory-bowel-disease',
        'image' => 'ibd-ai.jpg',
        'title' => 'Inflammatory Bowel Disease (IBD)',
        'desc'  => "The umbrella term for long-term conditions, mainly Crohn\u{2019}s disease and ulcerative colitis, that cause ongoing inflammation of the digestive tract.",
    ],
    [
        'slug'  => 'ulcerative-colitis',
        'image' => 'ulcerative-colitis-ai.jpg',
        'title' => 'Ulcerative Colitis (UC)',
        'desc'  => 'A form of IBD causing inflammation and ulcers in the lining of the colon and rectum.',
    ],
    [
        'slug'  => 'crohns-disease',
        'image' => 'crohns-ai.jpg',
        'title' => "Crohn\u{2019}s Disease",
        'desc'  => 'A form of IBD that can inflame any part of the gut, from mouth to anus, often the small intestine.',
    ],
    [
        'slug'  => 'microscopic-colitis',
        'image' => 'microscopic-colitis-ai.jpg',
        'title' => 'Microscopic Colitis',
        'desc'  => 'Inflammation of the colon visible only under a microscope, causing chronic watery diarrhoea.',
    ],
    [
        'slug'  => 'irritable-bowel-syndrome',
        'image' => 'ibs-ai.jpg',
        'title' => 'Irritable Bowel Syndrome (IBS)',
        'desc'  => 'A common, long-term condition affecting how the gut works, causing abdominal pain, bloating, and bouts of diarrhoea, constipation or both.',
    ],
    [
        'slug'  => 'colorectal-cancer',
        'image' => 'colorectal-cancer-ai.jpg',
        'title' => 'Colorectal Cancer',
        'desc'  => 'Cancer that develops in the colon or rectum, often growing slowly from small growths called polyps.',
    ],
    [
        'slug'  => 'diverticular-disease',
        'image' => 'diverticular-disease-ai.jpg',
        'title' => 'Diverticular Disease &amp; Diverticulitis',
        'desc'  => 'Small pouches that form in the wall of the colon, which can sometimes cause pain or become inflamed.',
    ],
];
?>

<main id="main-content">

  <!-- ===== Hero ===== -->
  <?php
  $ov   = max( 0, min( 100, absint( $hero_overlay ) ) );
  $ov1  = round( $ov / 100, 2 );
  $ov2  = min( 1, $ov1 + 0.10 );
  if ( $hero_bg ) {
      $hero_bg_css = "linear-gradient(rgba(10,25,41,{$ov1}),rgba(0,50,50,{$ov2})),url('" . esc_url( $hero_bg ) . "')";
  } else {
      $hero_bg_css = 'linear-gradient(135deg,#003d3d 0%,#006666 45%,#008080 100%)';
  }
  ?>
  <section class="hero gi-hub-hero" style="height:420px;min-height:0;display:flex;align-items:center;padding:0;position:relative;overflow:hidden;">
    <div style="position:absolute;inset:0;background-image:<?php echo $hero_bg_css; ?>;background-position:center center;background-size:cover;background-repeat:no-repeat;z-index:1;"></div>
    <div class="container" style="position:relative;z-index:2;width:100%;">
      <div class="hero-content" style="max-width:800px;">
        <span class="eyebrow" style="color:#aedbdb;text-transform:uppercase;letter-spacing:1px;font-weight:600;font-size:14px;display:block;margin-bottom:10px;"><?php echo esc_html( $hero_eyebrow ); ?></span>
        <h1 class="entry-title" style="font-size:clamp(32px,5vw,56px);color:#ffffff;font-weight:700;margin:0 0 16px;line-height:1.1;"><?php echo wp_kses_post( $hero_heading ); ?></h1>
        <p style="color:rgba(255,255,255,0.88);font-size:clamp(15px,2vw,18px);line-height:1.6;margin:0 0 28px;max-width:60ch;"><?php echo esc_html( $hero_lede ); ?></p>
      </div>
    </div>
  </section>

  <!-- ===== "You're not alone" — stats card ===== -->
  <section class="section-padding" style="padding-bottom:0" id="understanding">
    <div class="container">
      <div class="gi-stats-card gi-reveal">
        <h2><?php echo esc_html( $stats_heading ); ?></h2>
        <p class="gi-stats-card-subtitle"><?php echo esc_html( $stats_desc ); ?></p>

        <div class="gi-stats-card-grid">
          <div class="gi-stats-card-item">
            <div class="gi-stats-card-num"><?php echo esc_html( $stat1_num ); ?></div>
            <div class="gi-stats-card-label"><?php echo esc_html( $stat1_lbl ); ?></div>
          </div>
          <div class="gi-stats-card-item">
            <div class="gi-stats-card-num"><?php echo esc_html( $stat2_num ); ?></div>
            <div class="gi-stats-card-label"><?php echo esc_html( $stat2_lbl ); ?></div>
          </div>
          <div class="gi-stats-card-item">
            <div class="gi-stats-card-num"><?php echo esc_html( $stat3_num ); ?></div>
            <div class="gi-stats-card-label"><?php echo esc_html( $stat3_lbl ); ?></div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ===== Conditions list ===== -->
  <section class="section-padding" id="conditions">
    <div class="container">

      <div class="gi-section-head gi-reveal">
        <h2 style="font-size:clamp(26px,4vw,36px)"><?php echo esc_html( $grid_heading ); ?></h2>
        <?php if ( $grid_subtitle ) : ?>
          <p style="margin-top:10px"><?php echo esc_html( $grid_subtitle ); ?></p>
        <?php endif; ?>
      </div>

      <div class="gi-conditions-list">
        <?php foreach ( $conditions as $i => $c ) :
          $delay = ( $i % 3 === 0 ) ? '0s' : ( $i % 3 === 1 ? '.08s' : '.16s' );
        ?>
        <a href="<?php echo esc_url( vance_gi_page_url( $c['slug'] ) ); ?>"
           class="gi-condition-row gi-reveal"
           style="--reveal-delay:<?php echo esc_attr( $delay ); ?>">
          <div class="gi-condition-row-image">
            <img src="<?php echo esc_url( $tmpl . '/assets/img/gi-health/' . $c['image'] ); ?>"
                 loading="lazy" alt="<?php echo esc_attr( wp_strip_all_tags( $c['title'] ) ); ?> illustration">
          </div>
          <div class="gi-condition-row-content">
            <h3><?php echo wp_kses_post( $c['title'] ); ?></h3>
            <p><?php echo esc_html( $c['desc'] ); ?></p>
            <span class="gi-card-link">Learn more <span class="gi-arrow">→</span></span>
          </div>
        </a>
        <?php endforeach; ?>
      </div>

    </div>
  </section>

  <!-- ===== Dashboard CTA ===== -->
  <section class="gi-cta-band">
    <div class="container gi-reveal">
      <h2><?php echo esc_html( $cta_heading ); ?></h2>
      <p><?php echo esc_html( $cta_desc ); ?></p>
      <a href="<?php echo esc_url( $cta_btn_url ); ?>" class="btn btn-outline"><?php echo esc_html( $cta_btn_txt ); ?></a>
    </div>
  </section>

</main>

<script>
(function () {
  'use strict';
  var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function initReveal() {
    var items = document.querySelectorAll('.gi-reveal');
    if (!items.length) return;
    if (reduceMotion || !('IntersectionObserver' in window)) {
      items.forEach(function (el) { el.classList.add('is-visible'); });
      return;
    }
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (e.isIntersecting) { e.target.classList.add('is-visible'); io.unobserve(e.target); }
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
    items.forEach(function (el) { io.observe(el); });
  }

  document.addEventListener('DOMContentLoaded', function () { initReveal(); });
})();
</script>

<?php get_footer(); ?>
