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

/* ── Hero ──
   Resolved inside vance_render_gi_hub_hero() (inc/gi-hero.php), not here. The
   eight $hero_* locals this block used to declare were read by the dark band
   that the spotlight hero replaced; their DEFAULTS now live in
   vance_gi_hero_hub_defaults(), which inc/customizer-gi-health.php also reads
   for its controls, so the renderer and the Customizer cannot disagree about
   what an unsaved setting says. That disagreement is a real bug this theme has
   had before — see tests/README.md on the toggle default. */

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

/* The condition cards (photo, alt, title, description) and the slug → URL
   helper both live in functions.php now — the homepage "Gastro Conditions"
   section renders the same seven conditions and must not carry a second copy
   of the list. See vance_gi_condition_cards() / vance_gi_page_url(). */
$conditions = vance_gi_condition_cards();
?>

<main id="main-content">

  <!-- ===== Hero ===== -->
  <?php
  /*
   * The spotlight hero, from inc/gi-hero.php — the same section the homepage,
   * the page heroes and the five policy documents render, and now shared with
   * the seven condition pages so the whole GI section reads as one set.
   *
   * It replaces a 420px dark band that put a 70% navy veil over a photograph
   * and set white type on it. Every value that band read is still read: the
   * eyebrow, heading, lede and both buttons come from the same Customizer
   * settings, and vance_gi_hub_hero_bg_image now supplies the photograph that
   * dissolves into the right edge instead of the backdrop it hid behind.
   *
   * The renderer reads those settings itself, so this template no longer
   * resolves any of them — the $hero_* locals that used to sit at the top of
   * the file have gone with the markup that used them.
   */
  vance_render_gi_hub_hero();
  ?>

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

          /* The row photos are swapped in place from time to time, keeping the
             filename. Hostinger serves them with a long max-age, so a browser
             that already has one will never re-ask and would keep showing the
             old picture. Stamp the file's mtime on the URL: it changes when the
             file is rewritten, and is stable across deploys (tar -p preserves
             mtimes), so re-deploying does not needlessly re-bust the cache.
             CAVEAT: if you swap two photos with a copy that preserves mtime
             (cp -p, shutil.copy2, rsync -t), the stamp travels with the old
             bytes and the URL will not change. `touch` the files afterwards. */
          $img_rel  = '/assets/img/gi-health/' . $c['image'];
          $img_src  = $tmpl . $img_rel;
          $img_file = get_template_directory() . $img_rel;
          if ( file_exists( $img_file ) ) {
              $img_src = add_query_arg( 'v', filemtime( $img_file ), $img_src );
          }
        ?>
        <a href="<?php echo esc_url( vance_gi_page_url( $c['slug'] ) ); ?>"
           class="gi-condition-row gi-reveal"
           style="--reveal-delay:<?php echo esc_attr( $delay ); ?>">
          <div class="gi-condition-row-image">
            <img src="<?php echo esc_url( $img_src ); ?>"
                 loading="lazy" alt="<?php echo esc_attr( $c['alt'] ); ?>">
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

<?php /* .gi-reveal starts at opacity 0 in gi-health.css so the page never flashes
         its content before the animation begins. That is only safe while
         something is guaranteed to reveal it again -- with scripting off,
         nothing is, and the page would render permanently blank. */ ?>
<noscript><style>.gi-reveal { opacity: 1 !important; transform: none !important; }</style></noscript>

<script>
(function () {
  'use strict';
  var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  // The Customizer preview renders inside an iframe it moves and scales between
  // device sizes, and IntersectionObserver reports unreliably through that: the
  // entries never arrive and an admin is left editing a blank page. Same reason
  // as page-about.php. Keep this in step with page-gi-condition.php, which
  // carries the same block.
  var inCustomizer = <?php echo is_customize_preview() ? 'true' : 'false'; ?>;

  function initReveal() {
    var items = document.querySelectorAll('.gi-reveal');
    if (!items.length) return;

    function showAll() {
      Array.prototype.forEach.call(items, function (el) { el.classList.add('is-visible'); });
    }

    if (inCustomizer || reduceMotion || !('IntersectionObserver' in window)) { showAll(); return; }

    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (e.isIntersecting) { e.target.classList.add('is-visible'); io.unobserve(e.target); }
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
    Array.prototype.forEach.call(items, function (el) { io.observe(el); });

    // Content must never be left invisible. Anything already on screen is
    // revealed directly after 1.2s if the observer has not reported on it, and
    // after 5s nothing may still be hidden for any reason.
    setTimeout(function () {
      Array.prototype.forEach.call(items, function (el) {
        var r = el.getBoundingClientRect();
        if (r.top < (window.innerHeight || 0) && r.bottom > 0) {
          el.classList.add('is-visible');
          io.unobserve(el);
        }
      });
    }, 1200);
    setTimeout(showAll, 5000);
  }

  // Not a plain DOMContentLoaded listener: this script is concatenated and
  // deferred by Jetpack Boost on the live site, so by the time it runs the
  // event has usually already fired -- and a listener added afterwards never
  // calls initReveal at all, leaving the whole page at opacity 0.
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initReveal);
  } else {
    initReveal();
  }
})();
</script>

<?php get_footer(); ?>
