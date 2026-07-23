<?php
/**
 * Template Name: GI Health Condition
 *
 * Generic condition-detail template for all seven GI Health child pages.
 * Reads the page slug to select content and set the active sidebar link.
 *
 * Expected child page slugs (under /gi-health/):
 *   inflammatory-bowel-disease, ulcerative-colitis, crohns-disease,
 *   microscopic-colitis, irritable-bowel-syndrome, colorectal-cancer,
 *   diverticular-disease
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

get_header();

$tmpl = get_template_directory_uri();
$slug = get_post_field( 'post_name', get_queried_object_id() );

/* Hub page URL */
$hub_url = home_url( '/gi-health/' );
$hub_page = get_page_by_path( 'gi-health' );
if ( $hub_page ) { $hub_url = get_permalink( $hub_page ); }

/* Helper: condition child-page URL */
function vance_gi_cond_url( string $cond_slug ): string {
    $page = get_page_by_path( 'gi-health/' . $cond_slug );
    if ( $page ) { return get_permalink( $page ); }
    return home_url( '/gi-health/' . $cond_slug . '/' );
}

/* Conditions for the sidebar nav */
$gi_conditions  = vance_gi_conditions();
$nav_conditions = [];
foreach ( $gi_conditions as $cond_slug => $cond ) {
    $nav_conditions[] = [ 'slug' => $cond_slug, 'data' => $cond['nav'], 'label' => $cond['label'] ];
}

/* Image-led redesign (v2): single-column layout with in-page jump nav
   instead of the sidebar + table-of-contents shell used by the rest
   of the GI Health condition pages. */
$redesigned_conditions = [ 'inflammatory-bowel-disease', 'crohns-disease', 'ulcerative-colitis', 'microscopic-colitis', 'irritable-bowel-syndrome', 'colorectal-cancer', 'diverticular-disease' ];
$is_redesigned = in_array( $slug, $redesigned_conditions, true );
?>

<main id="main-content">

  <!-- ===== Hero ===== -->
  <?php
  /* Resolve customizer values early so the hero can use them */
  $cond_key_map = wp_list_pluck( $gi_conditions, 'key' );
  $cond_defaults = [
      'ibd'    => [ 'Inflammatory Bowel Disease (IBD)',      'A chronic condition of the digestive tract. There is no single cure, but with the right plan, many people live well in long, stable remission.' ],
      'uc'     => [ 'Ulcerative Colitis',                    'A type of inflammatory bowel disease that causes inflammation and ulcers in the lining of the colon and rectum. Many people with UC lead full, active lives with the right treatment.' ],
      'crohns' => [ 'Crohn\'s Disease',                     'A type of inflammatory bowel disease that can cause inflammation anywhere in the digestive tract, most often the end of the small intestine. With modern treatment, most people manage their symptoms well.' ],
      'mc'     => [ 'Microscopic Colitis',                   'Inflammation of the colon that can only be seen under a microscope. A common and treatable cause of ongoing watery diarrhoea, particularly in older adults.' ],
      'ibs'    => [ 'Irritable Bowel Syndrome',              'A common, long-term disorder of how the gut functions, causing abdominal pain, bloating and changes in bowel habit, without visible damage to the bowel.' ],
      'crc'    => [ 'Colorectal Cancer',                     'Cancer that develops in the colon or rectum, often growing slowly from small growths called polyps. Early detection through screening saves lives.' ],
      'div'    => [ 'Diverticular Disease &amp; Diverticulitis', 'Small pouches called diverticula can form in the wall of the colon as we get older. They are very common and usually harmless, but can sometimes cause symptoms or become inflamed.' ],
  ];
  $cond_key     = $cond_key_map[ $slug ] ?? '';
  $cm_sec       = $cond_key ? "vance_gi_cond_{$cond_key}" : '';
  $def_title    = $cond_key ? $cond_defaults[ $cond_key ][0] : get_the_title();
  $def_lede     = $cond_key ? $cond_defaults[ $cond_key ][1] : '';
  $cond_title   = $cm_sec ? vance_get_theme_mod( "{$cm_sec}_title",         $def_title ) : $def_title;
  $cond_lede    = $cm_sec ? vance_get_theme_mod( "{$cm_sec}_lede",          $def_lede  ) : $def_lede;
  $cond_img     = $cm_sec ? vance_get_theme_mod( "{$cm_sec}_image",         ''         ) : '';
  $cond_img_cap = $cm_sec ? vance_get_theme_mod( "{$cm_sec}_image_caption", ''         ) : '';

  if ( $cond_img ) {
      $cond_hero_bg = "linear-gradient(rgba(10,25,41,0.72),rgba(0,50,50,0.82)),url('" . esc_url( $cond_img ) . "')";
  } else {
      $cond_hero_bg = 'linear-gradient(135deg,#003d3d 0%,#006666 45%,#008080 100%)';
  }

  /* v2 hero banner images (bundled with the redesign, not customizer-managed) */
  $cond_hero_images = [
      'inflammatory-bowel-disease' => 'hero-ibd.jpg',
      'crohns-disease'           => 'hero-crohns.jpg',
      'ulcerative-colitis'       => 'hero-uc.jpg',
      'microscopic-colitis'      => 'hero-mc.jpg',
      'irritable-bowel-syndrome' => 'hero-ibs.jpg',
      'colorectal-cancer'        => 'hero-crc.jpg',
      'diverticular-disease'     => 'hero-dd.jpg',
  ];
  ?>
  <?php if ( $is_redesigned ) : ?>
  <section class="gi-cp-hero">
    <p class="gi-cp-breadcrumb"><a href="<?php echo esc_url( $hub_url ); ?>">GI Health</a> &nbsp;&rarr;&nbsp; <?php echo wp_kses_post( $cond_title ); ?></p>
    <h1><?php echo wp_kses_post( $cond_title ); ?></h1>
    <?php if ( $cond_lede ) : ?>
    <p class="gi-cp-subtitle"><?php echo esc_html( $cond_lede ); ?></p>
    <?php endif; ?>
    <?php if ( ! empty( $cond_hero_images[ $slug ] ) ) : ?>
    <div class="gi-cp-hero-image">
      <img src="<?php echo esc_url( $tmpl . '/assets/img/gi-health/conditions/' . $cond_hero_images[ $slug ] ); ?>"
           alt="<?php echo esc_attr( wp_strip_all_tags( $cond_title ) ); ?> illustration">
    </div>
    <?php endif; ?>
  </section>
  <?php else : ?>
  <section class="hero gi-cond-hero" style="height:350px;min-height:0;display:flex;align-items:center;padding:0;position:relative;overflow:hidden;">
    <div style="position:absolute;inset:0;background-image:<?php echo $cond_hero_bg; ?>;background-position:center center;background-size:cover;background-repeat:no-repeat;z-index:1;"></div>
    <div class="container" style="position:relative;z-index:2;width:100%;">
      <div class="hero-content" style="max-width:800px;">
        <span class="eyebrow" style="color:#aedbdb;text-transform:uppercase;letter-spacing:1px;font-weight:600;font-size:14px;display:block;margin-bottom:10px;">GI Health</span>
        <h1 class="entry-title" style="font-size:clamp(28px,4vw,52px);color:#ffffff;font-weight:700;margin:0 0 12px;line-height:1.1;"><?php echo wp_kses_post( $cond_title ); ?></h1>
        <?php if ( $cond_lede ) : ?>
        <p style="color:rgba(255,255,255,0.88);font-size:clamp(14px,1.8vw,17px);line-height:1.6;margin:0;max-width:60ch;"><?php echo esc_html( $cond_lede ); ?></p>
        <?php endif; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <?php if ( ! $is_redesigned ) : ?>
  <section class="section-padding">
    <div class="container gi-cond-layout">

      <!-- ===== Left sidebar ===== -->
      <aside class="gi-cond-sidebar gi-rail-left">
        <a href="<?php echo esc_url( $hub_url ); ?>" class="gi-sidebar-home">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
               stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M3 10.5 12 3l9 7.5"/>
            <path d="M5 9.5V21h14V9.5"/>
          </svg>
          <span>GI Health Home</span>
          <span class="gi-arrow">→</span>
        </a>

        <div class="gi-sidebar-block">
          <h4>GI Conditions</h4>
          <nav class="gi-cond-nav" aria-label="GI conditions">
            <?php foreach ( $nav_conditions as $cond ) :
              $active = ( $cond['slug'] === $slug ) ? ' active' : '';
              $aria   = $active ? ' aria-current="page"' : '';
            ?>
            <a href="<?php echo esc_url( vance_gi_cond_url( $cond['slug'] ) ); ?>"
               class="<?php echo esc_attr( trim( $active ) ); ?>"
               data-cond="<?php echo esc_attr( $cond['data'] ); ?>"
               <?php echo $aria; ?>>
              <?php echo esc_html( $cond['label'] ); ?>
            </a>
            <?php endforeach; ?>
          </nav>
        </div>
      </aside>

      <!-- ===== Main content ===== -->
      <div class="gi-cond-main">
        <?php switch ( $slug ) {

          default:
            /* Fallback: display the page's WordPress content */
            $toc = [];
            while ( have_posts() ) : the_post(); ?>
            <div class="gi-page-title">
              <h1><?php the_title(); ?></h1>
            </div>
            <div class="gi-cond-main"><?php the_content(); ?></div>
            <?php endwhile;
        } // end switch

        ?>
      </div><!-- .gi-cond-main -->

      <!-- ===== Right sidebar — Table of contents ===== -->
      <?php if ( ! empty( $toc ) ) : ?>
      <aside class="gi-cond-sidebar gi-rail-right">
        <div class="gi-sidebar-block">
          <h4>On this page</h4>
          <nav class="gi-toc" aria-label="On this page">
            <?php foreach ( $toc as $anchor => $label ) : ?>
            <a href="#<?php echo esc_attr( $anchor ); ?>"><?php echo wp_kses_post( $label ); ?></a>
            <?php endforeach; ?>
          </nav>
        </div>
      </aside>
      <?php endif; ?>

    </div><!-- .gi-cond-layout -->
  </section>
  <?php endif; // ! $is_redesigned ?>

  <?php if ( $is_redesigned ) :

    $cp_facts = [
        'inflammatory-bowel-disease' => [ 3, [ [ '500,000', 'People in the UK are estimated to live with IBD' ], [ 'Any age', 'IBD can be diagnosed at any age and is becoming more common' ], [ '3 types', 'Crohn\'s, ulcerative colitis and microscopic colitis' ] ] ],
        'crohns-disease'           => [ 3, [ [ '115,000–250,000', 'People in the UK are estimated to live with Crohn\'s disease' ], [ 'Any part', 'Can affect anywhere from mouth to anus, most commonly the terminal ileum' ] ] ],
        'ulcerative-colitis'       => [ 2, [ [ '1 in 420', 'People in the UK are estimated to live with UC' ], [ 'Any age', 'Often first diagnosed in younger adults, but can occur at any age' ] ] ],
        'microscopic-colitis'      => [ 3, [ [ 'Women 60+', 'Most commonly affects women over the age of 60' ], [ 'Normal scope', 'Colon looks completely normal during colonoscopy' ], [ 'Treatable', 'Symptoms often settle well with targeted treatment' ] ] ],
        'irritable-bowel-syndrome' => [ 3, [ [ '1 in 7', 'Adults are thought to have IBS symptoms' ], [ '~2x more', 'Common in women than in men' ], [ '3 types', 'IBS-D, IBS-C and IBS-M' ] ] ],
        'colorectal-cancer'        => [ 2, [ [ '9 in 10', 'Survive bowel cancer when it is found at the earliest stage' ], [ '4th', 'Most common cancer in the UK' ] ] ],
        'diverticular-disease'     => [ 3, [ [ '50%+', 'Of people over 50 have diverticula' ], [ '~25%', 'Will develop symptoms at some point' ], [ 'Treatable', 'Most cases managed with diet and lifestyle changes' ] ] ],
    ];
    $cp_nav = [
        'inflammatory-bowel-disease' => [ 'what-is-ibd' => 'What is IBD?', 'types' => 'Types', 'symptoms' => 'Symptoms', 'causes' => 'Causes', 'diagnosis' => 'Diagnosis', 'treatment' => 'Treatment' ],
        'crohns-disease'           => [ 'what-is-crohns' => 'What is Crohn\'s?', 'location' => 'Location', 'symptoms' => 'Symptoms', 'causes' => 'Causes', 'diagnosis' => 'Diagnosis', 'treatment' => 'Treatment', 'living' => 'Living with Crohn\'s' ],
        'ulcerative-colitis'       => [ 'what-is-uc' => 'What is UC?', 'extent' => 'Extent', 'symptoms' => 'Symptoms', 'causes' => 'Causes', 'diagnosis' => 'Diagnosis', 'treatment' => 'Treatment', 'living' => 'Living with UC' ],
        'microscopic-colitis'      => [ 'what-is-mc' => 'What is MC?', 'subtypes' => 'Subtypes', 'symptoms' => 'Symptoms', 'causes' => 'Causes', 'diagnosis' => 'Diagnosis', 'treatment' => 'Treatment' ],
        'irritable-bowel-syndrome' => [ 'what-is-ibs' => 'What is IBS?', 'subtypes' => 'Subtypes', 'symptoms' => 'Symptoms', 'causes' => 'Causes', 'diagnosis' => 'Diagnosis', 'treatment' => 'Treatment', 'living' => 'Living with IBS' ],
        'colorectal-cancer'        => [ 'what-is-crc' => 'What is it?', 'symptoms' => 'Symptoms', 'risk-factors' => 'Risk factors', 'screening' => 'Screening', 'diagnosis' => 'Diagnosis', 'treatment' => 'Treatment', 'prevention' => 'Prevention' ],
        'diverticular-disease'     => [ 'what-is-dd' => 'What is it?', 'terms' => 'Three terms', 'symptoms' => 'Symptoms', 'causes' => 'Causes', 'diagnosis' => 'Diagnosis', 'treatment' => 'Treatment', 'living' => 'Living with it' ],
    ];
    $cp_explore = [
        'inflammatory-bowel-disease' => [ [ 'crohns-disease', "Crohn's Disease" ], [ 'ulcerative-colitis', 'Ulcerative Colitis' ], [ 'microscopic-colitis', 'Microscopic Colitis' ] ],
        'crohns-disease'           => [ [ 'inflammatory-bowel-disease', 'IBD Overview' ], [ 'ulcerative-colitis', 'Ulcerative Colitis' ], [ 'microscopic-colitis', 'Microscopic Colitis' ] ],
        'ulcerative-colitis'       => [ [ 'inflammatory-bowel-disease', 'IBD Overview' ], [ 'crohns-disease', "Crohn's Disease" ], [ 'microscopic-colitis', 'Microscopic Colitis' ] ],
        'microscopic-colitis'      => [ [ 'inflammatory-bowel-disease', 'IBD Overview' ], [ 'ulcerative-colitis', 'Ulcerative Colitis' ], [ 'crohns-disease', "Crohn's Disease" ] ],
        'irritable-bowel-syndrome' => [ [ 'inflammatory-bowel-disease', 'IBD Overview' ], [ 'microscopic-colitis', 'Microscopic Colitis' ], [ 'colorectal-cancer', 'Colorectal Cancer' ] ],
        'colorectal-cancer'        => [ [ 'inflammatory-bowel-disease', 'IBD Overview' ], [ 'diverticular-disease', 'Diverticular Disease' ], [ 'irritable-bowel-syndrome', 'IBS' ] ],
        'diverticular-disease'     => [ [ 'colorectal-cancer', 'Colorectal Cancer' ], [ 'irritable-bowel-syndrome', 'IBS' ], [ 'inflammatory-bowel-disease', 'IBD Overview' ] ],
    ];
    $cp_explore_copy = [
        'inflammatory-bowel-disease' => [ 'Explore the conditions in detail', 'Learn more about each type of IBD and how it is managed' ],
        'colorectal-cancer'          => [ 'Explore related conditions', 'Learn more about other GI conditions' ],
        'diverticular-disease'       => [ 'Explore related conditions', 'Learn more about other GI conditions' ],
    ];
    list( $cp_explore_h2, $cp_explore_p ) = $cp_explore_copy[ $slug ] ?? [ 'Explore related conditions', 'Learn more about IBD and other related conditions' ];
    list( $cp_fact_cols, $cp_fact_items ) = $cp_facts[ $slug ];
  ?>
  <div class="gi-cp-container">

    <!-- ===== Key facts ===== -->
    <section class="gi-cp-facts<?php echo ( 2 === $cp_fact_cols ) ? ' is-cols-2' : ''; ?>">
      <?php foreach ( $cp_fact_items as $fact ) : ?>
      <div class="gi-cp-fact">
        <div class="gi-cp-fact-num"><?php echo esc_html( $fact[0] ); ?></div>
        <div class="gi-cp-fact-label"><?php echo esc_html( $fact[1] ); ?></div>
      </div>
      <?php endforeach; ?>
    </section>

    <!-- ===== In-page jump nav ===== -->
    <nav class="gi-cp-nav" aria-label="On this page">
      <ul>
        <?php foreach ( $cp_nav[ $slug ] as $anchor => $label ) : ?>
        <li><a href="#<?php echo esc_attr( $anchor ); ?>"><?php echo esc_html( $label ); ?></a></li>
        <?php endforeach; ?>
      </ul>
    </nav>

    <?php switch ( $slug ) {

      /* ─────────────────────────────────────────
         INFLAMMATORY BOWEL DISEASE (v2)
         ───────────────────────────────────────── */
      case 'inflammatory-bowel-disease': ?>

      <section class="gi-cp-section" id="what-is-ibd">
        <div class="gi-cp-section-head"><div class="gi-cp-section-icon">📖</div><h2>What is IBD?</h2></div>
        <div class="gi-cp-card">
          <p>Inflammatory bowel disease (IBD) is the umbrella term for a group of long-term conditions in which the immune system causes ongoing inflammation of the digestive tract. The three main types are Crohn's disease, ulcerative colitis and microscopic colitis.</p>
          <p>IBD usually follows a <strong>relapsing-remitting pattern</strong>: flares, when symptoms are active, followed by periods of remission, when symptoms settle.</p>
          <div class="gi-cp-highlight"><p><strong>Key fact:</strong> There isn't a single cure for IBD, but it can be managed well. Treatment can control inflammation, ease symptoms and help many people stay in long, stable remission. It is about finding the right management plan rather than a one-size-fits-all cure.</p></div>
        </div>
      </section>

      <section class="gi-cp-section" id="types">
        <div class="gi-cp-section-head"><div class="gi-cp-section-icon">🔍</div><h2>Types of IBD</h2></div>
        <div class="gi-cp-card">
          <p>Where each type affects the gut:</p>
          <div class="gi-cp-row">
            <div class="gi-cp-row-image"><img src="<?php echo esc_url( $tmpl . '/assets/img/gi-health/conditions/types-ibd.jpg' ); ?>" loading="lazy" alt="Comparison of the three types of IBD showing where each affects the gut"></div>
            <div class="gi-cp-row-text">
              <div class="gi-cp-grid is-cols-1">
                <div class="gi-cp-tile is-centered"><h4>Crohn's Disease</h4><p>Can affect any part of the gut, from mouth to anus, in patches. Most often the end of the small intestine.</p></div>
                <div class="gi-cp-tile is-centered"><h4>Ulcerative Colitis</h4><p>Affects only the large bowel (colon) and rectum, causing inflammation and ulcers in the lining.</p></div>
                <div class="gi-cp-tile is-centered"><h4>Microscopic Colitis</h4><p>A less widely known form where inflammation of the colon is only visible under a microscope.</p></div>
              </div>
            </div>
          </div>
          <p style="margin-top:20px">Crohn's disease and ulcerative colitis are the two most common forms. In a small number of people the pattern doesn't fit neatly into either and is described as IBD-unclassified.</p>
        </div>
      </section>

      <section class="gi-cp-section" id="symptoms">
        <div class="gi-cp-section-head"><div class="gi-cp-section-icon">⚠️</div><h2>Symptoms</h2></div>
        <div class="gi-cp-card">
          <p>Symptoms vary by type and by which part of the gut is affected. Common ones include:</p>
          <div class="gi-cp-row">
            <div class="gi-cp-row-image"><img src="<?php echo esc_url( $tmpl . '/assets/img/gi-health/conditions/symptoms-ibd.jpg' ); ?>" loading="lazy" alt="Illustration showing common IBD symptoms on a body silhouette"></div>
            <div class="gi-cp-row-text">
              <ul class="gi-cp-list">
                <li>Persistent diarrhoea, sometimes with blood or mucus</li>
                <li>Tummy (abdominal) pain and cramping</li>
                <li>Tiredness and fatigue</li>
                <li>Unintended weight loss and loss of appetite</li>
                <li>A frequent or urgent need to empty the bowel</li>
              </ul>
            </div>
          </div>
          <div class="gi-cp-highlight" style="margin-top:25px"><p>IBD can sometimes cause symptoms outside the gut too, such as joint pain, mouth ulcers or sore eyes. See the individual condition pages for more detail.</p></div>
        </div>
      </section>

      <section class="gi-cp-section" id="causes">
        <div class="gi-cp-section-head"><div class="gi-cp-section-icon">🧬</div><h2>Causes &amp; risk factors</h2></div>
        <div class="gi-cp-card">
          <p>The exact cause isn't fully understood, but IBD is thought to develop from a combination of factors:</p>
          <div class="gi-cp-row">
            <div class="gi-cp-row-image"><img src="<?php echo esc_url( $tmpl . '/assets/img/gi-health/conditions/causes-ibd.jpg' ); ?>" loading="lazy" alt="Illustration showing the four main factors contributing to IBD"></div>
            <div class="gi-cp-row-text">
              <div class="gi-cp-grid is-cols-1">
                <div class="gi-cp-tile"><h4>Genetics &amp; family history</h4><p>IBD is more common if a close relative has it</p></div>
                <div class="gi-cp-tile"><h4>The immune system</h4><p>An over-active immune response in the gut</p></div>
                <div class="gi-cp-tile"><h4>The gut microbiome</h4><p>The community of bacteria living in the bowel</p></div>
                <div class="gi-cp-tile"><h4>Diet &amp; environment</h4><p>Lifestyle and environmental factors may play a role</p></div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="gi-cp-section" id="diagnosis">
        <div class="gi-cp-section-head"><div class="gi-cp-section-icon">🩺</div><h2>Diagnosis</h2></div>
        <div class="gi-cp-card">
          <p>Your doctor may use several tests to diagnose IBD:</p>
          <div class="gi-cp-row">
            <div class="gi-cp-row-image"><img src="<?php echo esc_url( $tmpl . '/assets/img/gi-health/conditions/diagnosis-ibd.jpg' ); ?>" loading="lazy" alt="Illustration showing IBD diagnostic tools including blood tests, stool tests, colonoscopy and MRI"></div>
            <div class="gi-cp-row-text">
              <div class="gi-cp-steps">
                <div class="gi-cp-step"><div class="gi-cp-step-num">1</div><div class="gi-cp-step-text">Blood tests to check for inflammation and anaemia</div></div>
                <div class="gi-cp-step"><div class="gi-cp-step-num">2</div><div class="gi-cp-step-text">A stool test for <strong>faecal calprotectin</strong>, a marker of gut inflammation</div></div>
                <div class="gi-cp-step"><div class="gi-cp-step-num">3</div><div class="gi-cp-step-text">Endoscopy (colonoscopy) to look at the bowel lining and take biopsies</div></div>
                <div class="gi-cp-step"><div class="gi-cp-step-num">4</div><div class="gi-cp-step-text">Scans such as MRI or CT to assess the small bowel</div></div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="gi-cp-section" id="treatment">
        <div class="gi-cp-section-head"><div class="gi-cp-section-icon">💊</div><h2>Treatment &amp; management</h2></div>
        <div class="gi-cp-card">
          <p>Treatment has two goals: <strong>settling a flare</strong>, then <strong>maintaining remission</strong>. The right approach depends on the type, location and severity of the condition.</p>
          <div class="gi-cp-row">
            <div class="gi-cp-row-image"><img src="<?php echo esc_url( $tmpl . '/assets/img/gi-health/conditions/treatment-ibd.jpg' ); ?>" loading="lazy" alt="Illustration showing IBD treatment approaches including medication, biologics, diet and wellbeing support"></div>
            <div class="gi-cp-row-text">
              <div class="gi-cp-grid is-cols-1">
                <div class="gi-cp-tile"><h4>Anti-inflammatory medicines</h4><p>To calm the gut lining, for example aminosalicylates such as mesalazine</p></div>
                <div class="gi-cp-tile"><h4>Immune-modifying &amp; biologic therapies</h4><p>For moderate-to-severe disease, for example azathioprine, infliximab, adalimumab or vedolizumab</p></div>
                <div class="gi-cp-tile"><h4>Short courses of steroids</h4><p>To bring a flare under control, for example prednisolone or budesonide</p></div>
                <div class="gi-cp-tile"><h4>Surgery</h4><p>For some people when medicines aren't enough</p></div>
                <div class="gi-cp-tile"><h4>Diet &amp; wellbeing support</h4><p>Support for nutrition and overall wellbeing alongside medical treatment</p></div>
              </div>
            </div>
          </div>
          <div class="gi-cp-highlight" style="margin-top:25px"><p>Most people with IBD are cared for by a specialist team and have regular reviews to keep things under control.</p></div>
        </div>
      </section>

      <div class="gi-references">
        <h2>References &amp; further reading</h2>
        <ol>
          <li>NHS. <em>Inflammatory bowel disease</em>. nhs.uk</li>
          <li>NICE guidance on Crohn's disease and ulcerative colitis management. nice.org.uk</li>
          <li>Crohn's &amp; Colitis UK. crohnsandcolitis.org.uk</li>
          <li>Guts UK Charity. gutscharity.org.uk</li>
        </ol>
        <p class="gi-disclaimer">This page is for general information and does not replace advice from your doctor or specialist team.</p>
      </div>

      <?php break;

      /* ─────────────────────────────────────────
         CROHN'S DISEASE (v2)
         ───────────────────────────────────────── */
      case 'crohns-disease': ?>

      <section class="gi-cp-section" id="what-is-crohns">
        <div class="gi-cp-section-head"><div class="gi-cp-section-icon">📖</div><h2>What is Crohn's Disease?</h2></div>
        <div class="gi-cp-card">
          <p>Crohn's disease is a long-term condition where the immune system causes inflammation that can appear anywhere along the gut, from the mouth to the anus. It most commonly affects the last part of the small intestine (the terminal ileum) and the start of the colon.</p>
          <p>A key feature is that the inflammation comes in <strong>patches</strong>, with healthy stretches of bowel in between, and it can reach through the <strong>full thickness</strong> of the bowel wall. Over time this can sometimes lead to narrowing (strictures) or small tunnels between loops of bowel (fistulas).</p>
          <div class="gi-cp-highlight"><p><strong>Crohn's vs Ulcerative Colitis:</strong> Crohn's can affect any part of the gut, in patches, and through the whole bowel wall. Ulcerative colitis affects only the colon and rectum, in a continuous pattern, and only the lining.</p></div>
          <div class="gi-cp-highlight"><p><strong>Key fact:</strong> Crohn's most commonly affects the end of the small intestine (the terminal ileum), but it can appear anywhere from mouth to anus.</p></div>
        </div>
      </section>

      <section class="gi-cp-section" id="location">
        <div class="gi-cp-section-head"><div class="gi-cp-section-icon">🔍</div><h2>Where Crohn's commonly occurs</h2></div>
        <div class="gi-cp-card">
          <p>Crohn's can affect any part of the digestive tract. Here's how it typically distributes at diagnosis:</p>
          <div class="gi-cp-row">
            <div class="gi-cp-row-image"><img src="<?php echo esc_url( $tmpl . '/assets/img/gi-health/conditions/location-crohns.jpg' ); ?>" loading="lazy" alt="Diagram showing where Crohn's commonly occurs"></div>
            <div class="gi-cp-row-text">
              <div class="gi-cp-grid is-cols-1">
                <div class="gi-cp-tile is-centered"><div class="gi-cp-tile-big">~30%</div><h4>Terminal ileum only</h4><p>End of the small intestine</p></div>
                <div class="gi-cp-tile is-centered"><div class="gi-cp-tile-big">~30%</div><h4>Colon only</h4><p>Large intestine (colonic Crohn's)</p></div>
                <div class="gi-cp-tile is-centered"><div class="gi-cp-tile-big">~40%</div><h4>Ileum &amp; colon</h4><p>Both small and large intestine</p></div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="gi-cp-section" id="symptoms">
        <div class="gi-cp-section-head"><div class="gi-cp-section-icon">⚠️</div><h2>Symptoms</h2></div>
        <div class="gi-cp-card">
          <p>Symptoms can vary depending on which part of the gut is affected:</p>
          <div class="gi-cp-row">
            <div class="gi-cp-row-image"><img src="<?php echo esc_url( $tmpl . '/assets/img/gi-health/conditions/symptoms-crohns.jpg' ); ?>" loading="lazy" alt="Body silhouette showing common Crohn's symptoms"></div>
            <div class="gi-cp-row-text">
              <ul class="gi-cp-list">
                <li>Tummy pain and cramping, often in the lower right side</li>
                <li>Persistent or recurring diarrhoea</li>
                <li>Fatigue and feeling generally unwell</li>
                <li>Unintended weight loss</li>
                <li>Blood or mucus in the stool</li>
                <li>Mouth ulcers, and soreness around the anus</li>
              </ul>
            </div>
          </div>
        </div>
      </section>

      <section class="gi-cp-section" id="causes">
        <div class="gi-cp-section-head"><div class="gi-cp-section-icon">🧬</div><h2>Causes &amp; risk factors</h2></div>
        <div class="gi-cp-card">
          <p>As with other forms of IBD, Crohn's results from a combination of factors:</p>
          <div class="gi-cp-grid">
            <div class="gi-cp-tile"><h4>Genetics &amp; family history</h4><p>More common if a close relative has IBD</p></div>
            <div class="gi-cp-tile"><h4>The immune system</h4><p>An over-active immune response in the gut</p></div>
            <div class="gi-cp-tile"><h4>The gut microbiome</h4><p>The community of bacteria living in the bowel</p></div>
            <div class="gi-cp-tile"><h4>Environmental factors</h4><p>Including smoking, which is linked to higher risk and severity of Crohn's</p></div>
          </div>
        </div>
      </section>

      <section class="gi-cp-section" id="diagnosis">
        <div class="gi-cp-section-head"><div class="gi-cp-section-icon">🩺</div><h2>Diagnosis</h2></div>
        <div class="gi-cp-card">
          <p>Your doctor may use several tests to diagnose Crohn's and understand which parts of the gut are affected:</p>
          <div class="gi-cp-row">
            <div class="gi-cp-row-image"><img src="<?php echo esc_url( $tmpl . '/assets/img/gi-health/conditions/diagnosis-crohns.jpg' ); ?>" loading="lazy" alt="Illustration showing Crohn's diagnostic tools"></div>
            <div class="gi-cp-row-text">
              <div class="gi-cp-steps">
                <div class="gi-cp-step"><div class="gi-cp-step-num">1</div><div class="gi-cp-step-text">Blood and stool tests, including <strong>faecal calprotectin</strong></div></div>
                <div class="gi-cp-step"><div class="gi-cp-step-num">2</div><div class="gi-cp-step-text">Colonoscopy with biopsies</div></div>
                <div class="gi-cp-step"><div class="gi-cp-step-num">3</div><div class="gi-cp-step-text">MRI or CT scans to assess the small bowel and check for complications</div></div>
                <div class="gi-cp-step"><div class="gi-cp-step-num">4</div><div class="gi-cp-step-text">Capsule endoscopy in some cases</div></div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="gi-cp-section" id="treatment">
        <div class="gi-cp-section-head"><div class="gi-cp-section-icon">💊</div><h2>Treatment &amp; management</h2></div>
        <div class="gi-cp-card">
          <p>Treatment can bring on and maintain <strong>remission</strong> and help prevent complications.</p>
          <div class="gi-cp-row">
            <div class="gi-cp-row-image"><img src="<?php echo esc_url( $tmpl . '/assets/img/gi-health/conditions/treatment-crohns.jpg' ); ?>" loading="lazy" alt="Illustration showing Crohn's treatment approaches"></div>
            <div class="gi-cp-row-text">
              <div class="gi-cp-grid is-cols-1">
                <div class="gi-cp-tile"><h4>Steroids or liquid nutrition therapy</h4><p>To settle a flare (e.g. prednisolone or budesonide, or exclusive enteral nutrition)</p></div>
                <div class="gi-cp-tile"><h4>Immunosuppressants &amp; biologics</h4><p>To maintain remission (e.g. azathioprine, infliximab, adalimumab or ustekinumab)</p></div>
                <div class="gi-cp-tile"><h4>Surgery</h4><p>To remove or repair badly affected sections; common in Crohn's, though inflammation can return</p></div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="gi-cp-section" id="living">
        <div class="gi-cp-section-head"><div class="gi-cp-section-icon">🌟</div><h2>Living with Crohn's Disease</h2></div>
        <div class="gi-cp-card">
          <p>With modern treatment and regular specialist review, most people with Crohn's manage their symptoms well:</p>
          <div class="gi-cp-row">
            <div class="gi-cp-row-image"><img src="<?php echo esc_url( $tmpl . '/assets/img/gi-health/conditions/living-crohns.jpg' ); ?>" loading="lazy" alt="Illustration showing a person living well with Crohn's"></div>
            <div class="gi-cp-row-text">
              <div class="gi-cp-tips is-cols-1">
                <div class="gi-cp-tip"><div class="gi-cp-tip-icon">🍎</div><p><strong>Good nutrition</strong> — eating well supports healing and energy levels</p></div>
                <div class="gi-cp-tip"><div class="gi-cp-tip-icon">⚠️</div><p><strong>Know your warning signs</strong> — recognise when a flare may be starting</p></div>
                <div class="gi-cp-tip"><div class="gi-cp-tip-icon">🧠</div><p><strong>Mental wellbeing</strong> — looking after your emotional health is an important part of care</p></div>
                <div class="gi-cp-tip"><div class="gi-cp-tip-icon">📅</div><p><strong>Regular specialist review</strong> — stay in touch with your IBD team</p></div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <div class="gi-references">
        <h2>References &amp; further reading</h2>
        <ol>
          <li>NHS. <em>Crohn's disease</em>. nhs.uk</li>
          <li>NICE NG129. <em>Crohn's disease: management</em>. nice.org.uk</li>
          <li>Crohn's &amp; Colitis UK. <em>Crohn's disease</em>. crohnsandcolitis.org.uk</li>
        </ol>
        <p class="gi-disclaimer">This page is for general information and does not replace advice from your doctor or specialist team.</p>
      </div>

      <?php break;

      /* ─────────────────────────────────────────
         ULCERATIVE COLITIS (v2)
         ───────────────────────────────────────── */
      case 'ulcerative-colitis': ?>

      <section class="gi-cp-section" id="what-is-uc">
        <div class="gi-cp-section-head"><div class="gi-cp-section-icon">📖</div><h2>What is Ulcerative Colitis?</h2></div>
        <div class="gi-cp-card">
          <p>Ulcerative colitis (UC) is a long-term condition where the lining of the large bowel — the colon and rectum — becomes inflamed. Small sores (ulcers) can form on the lining, which may bleed and produce mucus.</p>
          <p>UC can occur at any age and is often first diagnosed in younger adults. Most people have times when symptoms <strong>flare up</strong>, followed by periods of <strong>remission</strong> when they feel well.</p>
          <div class="gi-cp-highlight"><p><strong>Key fact:</strong> Ulcerative colitis always begins in the rectum and can spread part or all of the way up the colon. Unlike Crohn's disease, it affects only the colon and rectum, and only the inner lining.</p></div>
        </div>
      </section>

      <section class="gi-cp-section" id="extent">
        <div class="gi-cp-section-head"><div class="gi-cp-section-icon">🔍</div><h2>How far UC extends</h2></div>
        <div class="gi-cp-card">
          <p>UC always starts in the rectum. How far it spreads up the colon varies between individuals:</p>
          <div class="gi-cp-row">
            <div class="gi-cp-row-image"><img src="<?php echo esc_url( $tmpl . '/assets/img/gi-health/conditions/extent-uc.jpg' ); ?>" loading="lazy" alt="Three diagrams showing proctitis, left-sided colitis, and pancolitis"></div>
            <div class="gi-cp-row-text">
              <div class="gi-cp-grid is-cols-1">
                <div class="gi-cp-tile is-centered"><h4>Proctitis</h4><p>Inflammation limited to the rectum only</p></div>
                <div class="gi-cp-tile is-centered"><h4>Left-sided colitis</h4><p>Inflammation extends from the rectum up the left side of the colon</p></div>
                <div class="gi-cp-tile is-centered"><h4>Extensive / Pancolitis</h4><p>Inflammation affects most or all of the colon</p></div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="gi-cp-section" id="symptoms">
        <div class="gi-cp-section-head"><div class="gi-cp-section-icon">⚠️</div><h2>Symptoms</h2></div>
        <div class="gi-cp-card">
          <p>Symptoms can vary depending on how much of the colon is affected and how active the inflammation is:</p>
          <div class="gi-cp-row">
            <div class="gi-cp-row-image"><img src="<?php echo esc_url( $tmpl . '/assets/img/gi-health/conditions/symptoms-uc.jpg' ); ?>" loading="lazy" alt="Body silhouette showing common UC symptoms"></div>
            <div class="gi-cp-row-text">
              <ul class="gi-cp-list">
                <li>Diarrhoea, often containing blood or mucus</li>
                <li>An urgent and frequent need to empty the bowel</li>
                <li>Tummy pain and cramping, often eased by passing a stool</li>
                <li>A feeling of needing to go even when the bowel is empty (tenesmus)</li>
                <li>Tiredness, weight loss and reduced appetite during flares</li>
              </ul>
            </div>
          </div>
          <div class="gi-cp-warning-urgent" style="margin-top:25px"><p><strong>When to seek help:</strong> Seek urgent advice if you have a severe flare — for example frequent bloody stools, a fever, a racing heart or significant tummy pain.</p></div>
        </div>
      </section>

      <section class="gi-cp-section" id="causes">
        <div class="gi-cp-section-head"><div class="gi-cp-section-icon">🧬</div><h2>Causes &amp; risk factors</h2></div>
        <div class="gi-cp-card">
          <p>UC develops from a combination of factors, rather than one single cause:</p>
          <div class="gi-cp-grid">
            <div class="gi-cp-tile"><h4>Genetics &amp; family history</h4><p>More common if a close relative has IBD</p></div>
            <div class="gi-cp-tile"><h4>The immune system</h4><p>An over-active immune response in the gut lining</p></div>
            <div class="gi-cp-tile"><h4>The gut microbiome</h4><p>The community of bacteria living in the bowel</p></div>
            <div class="gi-cp-tile"><h4>Environmental factors</h4><p>Lifestyle and environmental triggers may play a role</p></div>
          </div>
        </div>
      </section>

      <section class="gi-cp-section" id="diagnosis">
        <div class="gi-cp-section-head"><div class="gi-cp-section-icon">🩺</div><h2>Diagnosis</h2></div>
        <div class="gi-cp-card">
          <p>Your doctor may use several tests to diagnose UC and understand how much of the colon is affected:</p>
          <div class="gi-cp-row">
            <div class="gi-cp-row-image"><img src="<?php echo esc_url( $tmpl . '/assets/img/gi-health/conditions/diagnosis-uc.jpg' ); ?>" loading="lazy" alt="Illustration showing UC diagnostic tools"></div>
            <div class="gi-cp-row-text">
              <div class="gi-cp-steps">
                <div class="gi-cp-step"><div class="gi-cp-step-num">1</div><div class="gi-cp-step-text">Blood tests for inflammation and anaemia</div></div>
                <div class="gi-cp-step"><div class="gi-cp-step-num">2</div><div class="gi-cp-step-text">Stool tests, including <strong>faecal calprotectin</strong>, and tests to rule out infection</div></div>
                <div class="gi-cp-step"><div class="gi-cp-step-num">3</div><div class="gi-cp-step-text">Colonoscopy with biopsies to confirm the diagnosis and map how much of the colon is involved</div></div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="gi-cp-section" id="treatment">
        <div class="gi-cp-section-head"><div class="gi-cp-section-icon">💊</div><h2>Treatment &amp; management</h2></div>
        <div class="gi-cp-card">
          <p>The aim is to bring <strong>flares</strong> under control, then keep the bowel healed and in <strong>remission</strong>.</p>
          <div class="gi-cp-row">
            <div class="gi-cp-row-image"><img src="<?php echo esc_url( $tmpl . '/assets/img/gi-health/conditions/treatment-uc.jpg' ); ?>" loading="lazy" alt="Illustration showing UC treatment approaches"></div>
            <div class="gi-cp-row-text">
              <div class="gi-cp-grid is-cols-1">
                <div class="gi-cp-tile"><h4>Aminosalicylates / 5-ASAs</h4><p>Often the first-line treatment (e.g. mesalazine or sulfasalazine), taken by mouth or as an enema/suppository</p></div>
                <div class="gi-cp-tile"><h4>Steroids</h4><p>Short courses to settle a flare (e.g. prednisolone or budesonide)</p></div>
                <div class="gi-cp-tile"><h4>Immunosuppressants &amp; biologics</h4><p>For more active or hard-to-treat disease (e.g. azathioprine, infliximab, adalimumab or vedolizumab)</p></div>
                <div class="gi-cp-tile"><h4>Surgery</h4><p>Removing the colon can cure UC and is an option for some people</p></div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="gi-cp-section" id="living">
        <div class="gi-cp-section-head"><div class="gi-cp-section-icon">🌟</div><h2>Living with Ulcerative Colitis</h2></div>
        <div class="gi-cp-card">
          <p>Many people with UC lead full, active lives. Here are some things that can help keep things stable:</p>
          <div class="gi-cp-row">
            <div class="gi-cp-row-image"><img src="<?php echo esc_url( $tmpl . '/assets/img/gi-health/conditions/living-uc.jpg' ); ?>" loading="lazy" alt="Illustration showing a person living well with UC"></div>
            <div class="gi-cp-row-text">
              <div class="gi-cp-tips is-cols-1">
                <div class="gi-cp-tip"><div class="gi-cp-tip-icon">💊</div><p><strong>Take maintenance treatment as prescribed</strong> — even when you feel well</p></div>
                <div class="gi-cp-tip"><div class="gi-cp-tip-icon">📅</div><p><strong>Attend regular reviews</strong> — stay in touch with your specialist team</p></div>
                <div class="gi-cp-tip"><div class="gi-cp-tip-icon">⚠️</div><p><strong>Know your warning signs</strong> — recognise when a flare may be starting</p></div>
                <div class="gi-cp-tip"><div class="gi-cp-tip-icon">🍎</div><p><strong>Look after sleep, stress and diet</strong> — these all help keep things stable</p></div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <div class="gi-references">
        <h2>References &amp; further reading</h2>
        <ol>
          <li>NHS. <em>Ulcerative colitis</em>. nhs.uk</li>
          <li>NICE NG130. <em>Ulcerative colitis: management</em>. nice.org.uk</li>
          <li>Crohn's &amp; Colitis UK. <em>Ulcerative colitis</em>. crohnsandcolitis.org.uk</li>
        </ol>
        <p class="gi-disclaimer">This page is for general information and does not replace advice from your doctor or specialist team.</p>
      </div>

      <?php break;

      /* ─────────────────────────────────────────
         MICROSCOPIC COLITIS (v2)
         ───────────────────────────────────────── */
      case 'microscopic-colitis': ?>

      <section class="gi-cp-section" id="what-is-mc">
        <div class="gi-cp-section-head"><div class="gi-cp-section-icon">🔬</div><h2>What is Microscopic Colitis?</h2></div>
        <div class="gi-cp-card">
          <p>Microscopic colitis is a type of inflammatory bowel disease affecting the large bowel. Its defining feature is that the colon usually looks <strong>completely normal</strong> during a colonoscopy. The inflammation only shows up when a small tissue sample (a biopsy) is examined under a microscope.</p>
          <p>Because the bowel looks normal, it is often mistaken for irritable bowel syndrome. It is a common and treatable cause of ongoing watery diarrhoea, particularly in older adults.</p>
          <div class="gi-cp-highlight"><p><strong>Key fact:</strong> Microscopic colitis is more common in women, especially over the age of 60, and is confirmed by taking a biopsy even when the colon looks normal.</p></div>
        </div>
      </section>

      <section class="gi-cp-section" id="subtypes">
        <div class="gi-cp-section-head"><div class="gi-cp-section-icon">🧪</div><h2>Subtypes</h2></div>
        <div class="gi-cp-card">
          <p>There are two main subtypes of microscopic colitis. The two subtypes cause similar symptoms and are generally treated in the same way.</p>
          <div class="gi-cp-row">
            <div class="gi-cp-row-image"><img src="<?php echo esc_url( $tmpl . '/assets/img/gi-health/conditions/subtypes-mc.jpg' ); ?>" loading="lazy" alt="Diagram showing the two subtypes of microscopic colitis"></div>
            <div class="gi-cp-row-text">
              <table class="gi-compare-table">
                <thead><tr><th>Subtype</th><th>What's different under the microscope</th></tr></thead>
                <tbody>
                  <tr><td><strong>Collagenous colitis</strong></td><td>A thickened band of collagen forms just beneath the lining of the colon</td></tr>
                  <tr><td><strong>Lymphocytic colitis</strong></td><td>An increased number of white blood cells (lymphocytes) in the colon lining</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </section>

      <section class="gi-cp-section" id="symptoms">
        <div class="gi-cp-section-head"><div class="gi-cp-section-icon">⚠️</div><h2>Symptoms</h2></div>
        <div class="gi-cp-card">
          <p>The main symptom is chronic watery diarrhoea that is not bloody:</p>
          <div class="gi-cp-row">
            <div class="gi-cp-row-image"><img src="<?php echo esc_url( $tmpl . '/assets/img/gi-health/conditions/symptoms-mc.jpg' ); ?>" loading="lazy" alt="Body silhouette showing common Microscopic Colitis symptoms"></div>
            <div class="gi-cp-row-text">
              <ul class="gi-cp-list">
                <li>Chronic watery diarrhoea that is not bloody</li>
                <li>An urgent need to empty the bowel, sometimes at night</li>
                <li>Tummy pain or cramps</li>
                <li>Faecal incontinence in some people</li>
                <li>Tiredness and mild weight loss</li>
              </ul>
            </div>
          </div>
        </div>
      </section>

      <section class="gi-cp-section" id="causes">
        <div class="gi-cp-section-head"><div class="gi-cp-section-icon">🧬</div><h2>Causes &amp; risk factors</h2></div>
        <div class="gi-cp-card">
          <p>The exact trigger isn't fully understood, but it is thought to involve the immune system reacting to something passing through the bowel. Microscopic colitis is more likely in people with these known associations:</p>
          <div class="gi-cp-grid is-cols-1">
            <div class="gi-cp-tile"><h4>Being female and over 60</h4><p>The condition is significantly more common in women, particularly after the age of 60</p></div>
            <div class="gi-cp-tile"><h4>Autoimmune conditions</h4><p>Such as coeliac disease, thyroid disorders and rheumatoid arthritis</p></div>
            <div class="gi-cp-tile"><h4>Certain medicines</h4><p>Including some anti-inflammatory painkillers (NSAIDs), acid-reducing drugs (PPIs) and some antidepressants (SSRIs)</p></div>
          </div>
        </div>
      </section>

      <section class="gi-cp-section" id="diagnosis">
        <div class="gi-cp-section-head"><div class="gi-cp-section-icon">🩺</div><h2>Diagnosis</h2></div>
        <div class="gi-cp-card">
          <p>Diagnosis relies on a colonoscopy <strong>with biopsies</strong>. Even when the bowel looks normal, the laboratory findings confirm the type. Blood and stool tests help rule out other causes such as coeliac disease or infection.</p>
          <div class="gi-cp-row">
            <div class="gi-cp-row-image"><img src="<?php echo esc_url( $tmpl . '/assets/img/gi-health/conditions/diagnosis-mc.jpg' ); ?>" loading="lazy" alt="Illustration showing the diagnostic process for Microscopic Colitis"></div>
            <div class="gi-cp-row-text">
              <div class="gi-cp-warning"><p><strong>Why it can be missed:</strong> A normal-looking colonoscopy can be falsely reassuring. In anyone with persistent watery diarrhoea, taking biopsies is the key step that confirms the diagnosis.</p></div>
            </div>
          </div>
        </div>
      </section>

      <section class="gi-cp-section" id="treatment">
        <div class="gi-cp-section-head"><div class="gi-cp-section-icon">💊</div><h2>Treatment &amp; management</h2></div>
        <div class="gi-cp-card">
          <p>Symptoms often settle with treatment, though they can come back and may need a further course:</p>
          <div class="gi-cp-row">
            <div class="gi-cp-row-image"><img src="<?php echo esc_url( $tmpl . '/assets/img/gi-health/conditions/treatment-mc.jpg' ); ?>" loading="lazy" alt="Illustration showing Microscopic Colitis treatment approaches"></div>
            <div class="gi-cp-row-text">
              <div class="gi-cp-grid is-cols-1">
                <div class="gi-cp-tile"><h4>Budesonide</h4><p>A targeted steroid, usually the first-line treatment and works well for most people</p></div>
                <div class="gi-cp-tile"><h4>Medicine review</h4><p>Reviewing any medicines that may be contributing to symptoms</p></div>
                <div class="gi-cp-tile"><h4>Anti-diarrhoeal medicines</h4><p>For milder symptoms, such as loperamide</p></div>
                <div class="gi-cp-tile"><h4>Dietary adjustments</h4><p>Simple changes to diet that may help manage symptoms</p></div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <div class="gi-references">
        <h2>References &amp; further reading</h2>
        <ol>
          <li>Guts UK Charity. <em>Microscopic colitis</em>. gutscharity.org.uk</li>
          <li>British Society of Gastroenterology guidelines on microscopic colitis. bsg.org.uk</li>
          <li>NHS. <em>Colitis</em>. nhs.uk</li>
        </ol>
        <p class="gi-disclaimer">This page is for general information and does not replace advice from your doctor or specialist team.</p>
      </div>

      <?php break;

      /* ─────────────────────────────────────────
         IRRITABLE BOWEL SYNDROME (v2)
         ───────────────────────────────────────── */
      case 'irritable-bowel-syndrome': ?>

      <section class="gi-cp-section" id="what-is-ibs">
        <div class="gi-cp-section-head"><div class="gi-cp-section-icon">🧠</div><h2>What is Irritable Bowel Syndrome?</h2></div>
        <div class="gi-cp-card">
          <div class="gi-cp-row">
            <div class="gi-cp-row-image"><img src="<?php echo esc_url( $tmpl . '/assets/img/gi-health/conditions/gutbrain-ibs.jpg' ); ?>" loading="lazy" alt="Gut-brain axis illustration"></div>
            <div class="gi-cp-row-text">
              <p>Irritable bowel syndrome (IBS) is one of the most common digestive conditions. It is a <strong>functional</strong> disorder, which means the gut doesn't work as it should even though it looks normal and isn't damaged. This is what sets it apart from inflammatory bowel disease.</p>
              <p>IBS is closely linked to how the gut and the brain communicate; this is often referred to as the <strong>gut-brain axis</strong>. It can be uncomfortable and disruptive, but it does not damage the bowel or raise the risk of bowel cancer.</p>
              <div class="gi-cp-highlight"><p><strong>Key fact:</strong> IBS is a functional disorder — the gut looks normal but doesn't work as it should. It is closely linked to the gut-brain axis.</p></div>
            </div>
          </div>
        </div>
      </section>

      <section class="gi-cp-section" id="subtypes">
        <div class="gi-cp-section-head"><div class="gi-cp-section-icon">📊</div><h2>Subtypes</h2></div>
        <div class="gi-cp-card">
          <p>IBS is grouped by the main change in bowel habit, which helps guide treatment:</p>
          <div class="gi-cp-row">
            <div class="gi-cp-row-image"><img src="<?php echo esc_url( $tmpl . '/assets/img/gi-health/conditions/subtypes-ibs.jpg' ); ?>" loading="lazy" alt="Three IBS subtypes illustration"></div>
            <div class="gi-cp-row-text">
              <div class="gi-cp-grid is-cols-3">
                <div class="gi-cp-tile is-centered"><div class="gi-cp-tile-eyebrow">Diarrhoea-predominant</div><h4>IBS-D</h4><p>Loose or frequent stools are the main problem</p></div>
                <div class="gi-cp-tile is-centered"><div class="gi-cp-tile-eyebrow">Constipation-predominant</div><h4>IBS-C</h4><p>Infrequent, hard or difficult-to-pass stools</p></div>
                <div class="gi-cp-tile is-centered"><div class="gi-cp-tile-eyebrow">Mixed</div><h4>IBS-M</h4><p>Alternating between diarrhoea and constipation over time</p></div>
              </div>
              <div class="gi-cp-highlight" style="margin-top:20px"><p>Many people move between subtypes over time.</p></div>
            </div>
          </div>
        </div>
      </section>

      <section class="gi-cp-section" id="symptoms">
        <div class="gi-cp-section-head"><div class="gi-cp-section-icon">⚠️</div><h2>Symptoms</h2></div>
        <div class="gi-cp-card">
          <div class="gi-cp-row">
            <div class="gi-cp-row-image"><img src="<?php echo esc_url( $tmpl . '/assets/img/gi-health/conditions/symptoms-ibs.jpg' ); ?>" loading="lazy" alt="Body silhouette showing common IBS symptoms"></div>
            <div class="gi-cp-row-text">
              <ul class="gi-cp-list">
                <li>Abdominal pain or cramping, often relieved by passing a stool</li>
                <li>Bloating and a swollen tummy</li>
                <li>Diarrhoea, constipation, or both at different times</li>
                <li>Excess wind</li>
                <li>A feeling of not having fully emptied the bowel</li>
                <li>Mucus in the stool</li>
              </ul>
              <div class="gi-cp-warning"><p><strong>When to get checked:</strong> Some symptoms are not typical of IBS and should always be checked by a doctor: blood in the stool, unexplained weight loss, a persistent change in bowel habit over the age of 50, or symptoms that wake you at night.</p></div>
            </div>
          </div>
        </div>
      </section>

      <section class="gi-cp-section" id="causes">
        <div class="gi-cp-section-head"><div class="gi-cp-section-icon">🧬</div><h2>Causes &amp; triggers</h2></div>
        <div class="gi-cp-card">
          <p>There is no single cause. IBS is thought to involve a sensitive gut, changes in how quickly the bowel moves, and altered gut-brain signalling. Common triggers that can worsen symptoms include:</p>
          <div class="gi-cp-grid">
            <div class="gi-cp-tile"><h4>Diet</h4><p>Certain foods, including some high in fermentable carbohydrates (FODMAPs)</p></div>
            <div class="gi-cp-tile"><h4>Stress and anxiety</h4><p>Through the gut-brain connection, stress can directly worsen symptoms</p></div>
            <div class="gi-cp-tile"><h4>Previous gastroenteritis</h4><p>A bout of food poisoning or stomach bug can trigger post-infectious IBS</p></div>
            <div class="gi-cp-tile"><h4>Hormonal changes</h4><p>Such as around the menstrual cycle, which may explain higher prevalence in women</p></div>
          </div>
        </div>
      </section>

      <section class="gi-cp-section" id="diagnosis">
        <div class="gi-cp-section-head"><div class="gi-cp-section-icon">🩺</div><h2>Diagnosis</h2></div>
        <div class="gi-cp-card">
          <p>IBS is usually diagnosed from the typical pattern of symptoms (often using the <strong>Rome criteria</strong>) once other conditions have been ruled out.</p>
          <p>A doctor may arrange:</p>
          <ul class="gi-cp-list">
            <li>Blood tests to check for anaemia and inflammation</li>
            <li>A coeliac disease test</li>
            <li>A stool test for <strong>faecal calprotectin</strong> to help exclude inflammation or infection</li>
          </ul>
          <div class="gi-cp-highlight"><p><strong>Important:</strong> IBS is a diagnosis of exclusion — tests are done to rule out other conditions rather than to confirm IBS itself.</p></div>
        </div>
      </section>

      <section class="gi-cp-section" id="treatment">
        <div class="gi-cp-section-head"><div class="gi-cp-section-icon">💊</div><h2>Treatment &amp; management</h2></div>
        <div class="gi-cp-card">
          <p>IBS cannot be cured, but symptoms can usually be well controlled with a combination of approaches tailored to the individual:</p>
          <div class="gi-cp-row">
            <div class="gi-cp-row-image"><img src="<?php echo esc_url( $tmpl . '/assets/img/gi-health/conditions/treatment-ibs.jpg' ); ?>" loading="lazy" alt="Illustration showing IBS treatment approaches"></div>
            <div class="gi-cp-row-text">
              <div class="gi-cp-grid is-cols-1">
                <div class="gi-cp-tile"><h4>Diet and lifestyle</h4><p>Regular meals, adjusting fibre, and limiting caffeine and alcohol; a dietitian-supervised low-FODMAP diet helps many people</p></div>
                <div class="gi-cp-tile"><h4>Medicines</h4><p>Antispasmodics for pain, laxatives for constipation, or anti-diarrhoeals for loose stools</p></div>
                <div class="gi-cp-tile"><h4>Probiotics</h4><p>Worth a trial for some people to help balance gut bacteria</p></div>
                <div class="gi-cp-tile"><h4>Psychological therapies</h4><p>Cognitive behavioural therapy (CBT) or gut-directed hypnotherapy can be very effective</p></div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="gi-cp-section" id="living">
        <div class="gi-cp-section-head"><div class="gi-cp-section-icon">🌱</div><h2>Living with IBS</h2></div>
        <div class="gi-cp-card">
          <div class="gi-cp-row">
            <div class="gi-cp-row-image"><img src="<?php echo esc_url( $tmpl . '/assets/img/gi-health/conditions/living-ibs.jpg' ); ?>" loading="lazy" alt="Illustration showing positive daily life with IBS management"></div>
            <div class="gi-cp-row-text">
              <p>Identifying your personal triggers, often with a food and symptom diary, managing stress, staying active and keeping a regular routine can all make a big difference.</p>
              <p>IBS tends to come and go, but most people find a management plan that lets them get on with daily life.</p>
              <div class="gi-cp-highlight"><p><strong>Tip:</strong> Keeping a food and symptom diary for a few weeks can help you and your healthcare team identify patterns and triggers specific to you.</p></div>
            </div>
          </div>
        </div>
      </section>

      <div class="gi-references">
        <h2>References &amp; further reading</h2>
        <ol>
          <li>NHS. <em>Irritable bowel syndrome (IBS)</em>. nhs.uk</li>
          <li>NICE CG61. <em>Irritable bowel syndrome in adults</em>. nice.org.uk</li>
          <li>Guts UK Charity. gutscharity.org.uk</li>
          <li>The IBS Network. theibsnetwork.org</li>
        </ol>
        <p class="gi-disclaimer">This page is for general information and does not replace advice from your doctor or specialist team.</p>
      </div>

      <?php break;

      /* ─────────────────────────────────────────
         COLORECTAL CANCER (v2)
         ───────────────────────────────────────── */
      case 'colorectal-cancer': ?>

      <section class="gi-cp-section" id="what-is-crc">
        <div class="gi-cp-section-head"><div class="gi-cp-section-icon">🎯</div><h2>What is Colorectal Cancer?</h2></div>
        <div class="gi-cp-card">
          <p>Colorectal cancer, also called bowel cancer, starts in the large bowel (colon) or back passage (rectum). Most bowel cancers develop slowly from small growths called <strong>polyps</strong> on the bowel lining.</p>
          <p>Not all polyps become cancer, and finding and removing them — often during screening — can stop a cancer from ever forming.</p>
          <div class="gi-cp-highlight"><p><strong>Key fact:</strong> Most bowel cancers grow slowly from polyps. Finding and removing polyps during screening can prevent cancer from developing.</p></div>
        </div>
      </section>

      <section class="gi-cp-section" id="symptoms">
        <div class="gi-cp-section-head"><div class="gi-cp-section-icon">⚠️</div><h2>Symptoms</h2></div>
        <div class="gi-cp-card">
          <div class="gi-cp-row">
            <div class="gi-cp-row-image"><img src="<?php echo esc_url( $tmpl . '/assets/img/gi-health/conditions/symptoms-crc.jpg' ); ?>" loading="lazy" alt="Body silhouette showing common Colorectal Cancer symptoms"></div>
            <div class="gi-cp-row-text">
              <ul class="gi-cp-list">
                <li>Bleeding from the back passage, or blood in the stool</li>
                <li>A lasting change in bowel habit, looser stools, or going more often</li>
                <li>Tummy pain, bloating or discomfort, especially after eating</li>
                <li>Unintended weight loss</li>
                <li>Tiredness or breathlessness from unexplained anaemia</li>
              </ul>
              <div class="gi-cp-reassurance"><p><strong>Don't panic:</strong> Most of the time these symptoms are caused by something far less serious than cancer. If they last more than a few weeks, see your GP.</p></div>
            </div>
          </div>
        </div>
      </section>

      <section class="gi-cp-section" id="risk-factors">
        <div class="gi-cp-section-head"><div class="gi-cp-section-icon">🧬</div><h2>Risk factors</h2></div>
        <div class="gi-cp-card">
          <p>Several factors can increase the risk of developing bowel cancer:</p>
          <div class="gi-cp-grid">
            <div class="gi-cp-tile"><h4>Age</h4><p>Risk rises from around the age of 50</p></div>
            <div class="gi-cp-tile"><h4>Family history</h4><p>A family history of bowel cancer, or certain inherited conditions</p></div>
            <div class="gi-cp-tile"><h4>Inflammatory bowel disease</h4><p>A long history of IBD increases the risk over time</p></div>
            <div class="gi-cp-tile"><h4>Lifestyle</h4><p>Diets high in red and processed meat and low in fibre, being overweight, smoking and drinking a lot of alcohol</p></div>
          </div>
        </div>
      </section>

      <section class="gi-cp-section" id="screening">
        <div class="gi-cp-section-head"><div class="gi-cp-section-icon">📨</div><h2>Screening</h2></div>
        <div class="gi-cp-card">
          <p>Screening looks for early signs of cancer in people who have no symptoms. This is the <strong>single most effective way</strong> to catch bowel cancer early.</p>
          <div class="gi-cp-row">
            <div class="gi-cp-row-image"><img src="<?php echo esc_url( $tmpl . '/assets/img/gi-health/conditions/screening-crc.jpg' ); ?>" loading="lazy" alt="Illustration showing the bowel cancer screening process"></div>
            <div class="gi-cp-row-text">
              <div class="gi-cp-step-cards">
                <div class="gi-cp-step-card"><div class="gi-cp-step-num">1</div><h4>Kit arrives by post</h4><p>An NHS bowel screening kit is posted to eligible adults within the screening age range</p></div>
                <div class="gi-cp-step-card"><div class="gi-cp-step-num">2</div><h4>FIT test at home</h4><p>The faecal immunochemical test takes a few minutes and detects tiny traces of blood</p></div>
                <div class="gi-cp-step-card"><div class="gi-cp-step-num">3</div><h4>Colonoscopy if needed</h4><p>If the test is positive, a colonoscopy examines the bowel and can remove polyps</p></div>
              </div>
              <div class="gi-cp-highlight" style="margin-top:20px"><p><strong>If you receive a screening kit, use it.</strong> It takes only a few minutes at home and can detect changes long before any symptoms appear.</p></div>
            </div>
          </div>
        </div>
      </section>

      <section class="gi-cp-section" id="diagnosis">
        <div class="gi-cp-section-head"><div class="gi-cp-section-icon">🩺</div><h2>Diagnosis</h2></div>
        <div class="gi-cp-card">
          <p>If bowel cancer is suspected, the main test is a <strong>colonoscopy</strong>, which lets the bowel be examined and biopsies taken.</p>
          <p>Scans such as CT then check the size of the cancer and whether it has spread (its stage), which guides treatment.</p>
        </div>
      </section>

      <section class="gi-cp-section" id="treatment">
        <div class="gi-cp-section-head"><div class="gi-cp-section-icon">💊</div><h2>Treatment</h2></div>
        <div class="gi-cp-card">
          <p>Treatment depends on where the cancer is, its stage and a person's general health. The main options, often used in combination, are:</p>
          <div class="gi-cp-row">
            <div class="gi-cp-row-image"><img src="<?php echo esc_url( $tmpl . '/assets/img/gi-health/conditions/treatment-crc.jpg' ); ?>" loading="lazy" alt="Illustration showing Colorectal Cancer treatment options"></div>
            <div class="gi-cp-row-text">
              <div class="gi-cp-grid">
                <div class="gi-cp-tile"><h4>Surgery</h4><p>To remove the affected part of the bowel — the most common treatment</p></div>
                <div class="gi-cp-tile"><h4>Chemotherapy</h4><p>Medicines that kill cancer cells, often given after surgery</p></div>
                <div class="gi-cp-tile"><h4>Radiotherapy</h4><p>Particularly for rectal cancer, using radiation to target cancer cells</p></div>
                <div class="gi-cp-tile"><h4>Targeted &amp; immunotherapy</h4><p>Newer medicines for some cancers that target specific cell changes</p></div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="gi-cp-section" id="prevention">
        <div class="gi-cp-section-head"><div class="gi-cp-section-icon">🛡️</div><h2>Prevention</h2></div>
        <div class="gi-cp-card">
          <p>You can lower your risk with healthy lifestyle choices:</p>
          <div class="gi-cp-row">
            <div class="gi-cp-row-image"><img src="<?php echo esc_url( $tmpl . '/assets/img/gi-health/conditions/prevention-crc.jpg' ); ?>" loading="lazy" alt="Illustration showing bowel cancer prevention through healthy lifestyle"></div>
            <div class="gi-cp-row-text">
              <ul class="gi-cp-list">
                <li>Eating plenty of fibre, fruit and vegetables</li>
                <li>Limiting red and processed meat and alcohol</li>
                <li>Keeping to a healthy weight</li>
                <li>Staying physically active</li>
                <li>Not smoking</li>
                <li>Taking part in screening when invited</li>
              </ul>
            </div>
          </div>
        </div>
      </section>

      <div class="gi-references">
        <h2>References &amp; further reading</h2>
        <ol>
          <li>NHS. <em>Bowel cancer</em>. nhs.uk</li>
          <li>NHS. <em>Bowel cancer screening</em>. nhs.uk</li>
          <li>Bowel Cancer UK. bowelcanceruk.org.uk</li>
          <li>Cancer Research UK. <em>Bowel cancer</em>. cancerresearchuk.org</li>
        </ol>
        <p class="gi-disclaimer">This page is for general information and does not replace advice from your doctor or specialist team.</p>
      </div>

      <?php break;

      /* ─────────────────────────────────────────
         DIVERTICULAR DISEASE (v2)
         ───────────────────────────────────────── */
      case 'diverticular-disease': ?>

      <section class="gi-cp-section" id="what-is-dd">
        <div class="gi-cp-section-head"><div class="gi-cp-section-icon">💡</div><h2>What is diverticular disease?</h2></div>
        <div class="gi-cp-card">
          <p>Diverticular disease and diverticulitis affect the large bowel (colon). Over time, small pouches called <strong>diverticula</strong> can develop in the bowel wall. Having these pouches is extremely common as we age and is known as <strong>diverticulosis</strong>.</p>
          <p>Most people with diverticula never know they have them. When the pouches cause symptoms such as tummy pain, it is called <strong>diverticular disease</strong>. If a pouch becomes inflamed or infected, this is <strong>diverticulitis</strong>.</p>
          <div class="gi-cp-highlight"><p><strong>Key fact:</strong> Diverticula are a normal part of getting older for many people and usually cause no problems at all. Simple steps, including eating enough fibre, can help ease symptoms and keep them away.</p></div>
        </div>
      </section>

      <section class="gi-cp-section" id="terms">
        <div class="gi-cp-section-head"><div class="gi-cp-section-icon">📋</div><h2>Three terms explained</h2></div>
        <div class="gi-cp-card">
          <div class="gi-cp-row">
            <div class="gi-cp-row-image"><img src="<?php echo esc_url( $tmpl . '/assets/img/gi-health/conditions/terms-dd.jpg' ); ?>" loading="lazy" alt="Illustration showing the three stages: diverticulosis, diverticular disease, diverticulitis"></div>
            <div class="gi-cp-row-text">
              <table class="gi-compare-table">
                <thead><tr><th>Term</th><th>What it means</th></tr></thead>
                <tbody>
                  <tr><td><strong>Diverticulosis</strong></td><td>Pouches are present in the bowel, but cause no symptoms</td></tr>
                  <tr><td><strong>Diverticular disease</strong></td><td>The pouches cause symptoms such as tummy pain</td></tr>
                  <tr><td><strong>Diverticulitis</strong></td><td>One or more pouches become inflamed or infected</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </section>

      <section class="gi-cp-section" id="symptoms">
        <div class="gi-cp-section-head"><div class="gi-cp-section-icon">⚠️</div><h2>Symptoms</h2></div>
        <div class="gi-cp-card">
          <div class="gi-cp-row">
            <div class="gi-cp-row-image"><img src="<?php echo esc_url( $tmpl . '/assets/img/gi-health/conditions/symptoms-dd.jpg' ); ?>" loading="lazy" alt="Body silhouette showing common Diverticular Disease symptoms"></div>
            <div class="gi-cp-row-text">
              <h3>Diverticular disease</h3>
              <ul class="gi-cp-list">
                <li>Tummy pain, usually in the lower left side</li>
                <li>Pain that often eases after passing wind or having a poo</li>
                <li>Bloating</li>
                <li>Constipation, diarrhoea, or both</li>
                <li>Occasionally, blood in the stool</li>
              </ul>
              <h3>Diverticulitis (more serious)</h3>
              <ul class="gi-cp-list">
                <li>More severe, constant tummy pain (usually on the left)</li>
                <li>A high temperature (fever)</li>
                <li>Feeling sick or being sick</li>
                <li>A change in bowel habit, sometimes with blood or mucus</li>
                <li>Feeling generally tired and unwell</li>
              </ul>
              <div class="gi-cp-warning"><p><strong>When to seek help:</strong> See your GP if you have ongoing tummy pain or a change in bowel habit. Seek urgent medical advice if you have severe tummy pain with a fever, as diverticulitis sometimes needs prompt treatment.</p></div>
            </div>
          </div>
        </div>
      </section>

      <section class="gi-cp-section" id="causes">
        <div class="gi-cp-section-head"><div class="gi-cp-section-icon">🧬</div><h2>Causes &amp; risk factors</h2></div>
        <div class="gi-cp-card">
          <p>Diverticula are thought to form when weak spots in the bowel wall give way under pressure, for example from straining with constipation. Factors linked to a higher risk include:</p>
          <div class="gi-cp-grid">
            <div class="gi-cp-tile"><h4>Low-fibre diet</h4><p>Not eating enough fibre increases pressure in the bowel</p></div>
            <div class="gi-cp-tile"><h4>Getting older</h4><p>The bowel wall naturally weakens with age</p></div>
            <div class="gi-cp-tile"><h4>Constipation &amp; straining</h4><p>Increases pressure on the bowel wall</p></div>
            <div class="gi-cp-tile"><h4>Being overweight</h4><p>Excess weight is linked to higher risk</p></div>
            <div class="gi-cp-tile"><h4>Lack of physical activity</h4><p>Not being very physically active contributes to risk</p></div>
            <div class="gi-cp-tile"><h4>Smoking &amp; NSAIDs</h4><p>Smoking and some painkillers (anti-inflammatories) increase risk</p></div>
          </div>
        </div>
      </section>

      <section class="gi-cp-section" id="diagnosis">
        <div class="gi-cp-section-head"><div class="gi-cp-section-icon">🩺</div><h2>Diagnosis</h2></div>
        <div class="gi-cp-card">
          <ul class="gi-cp-list">
            <li>Diverticulosis is often found by chance during tests done for another reason</li>
            <li>A <strong>CT scan</strong> is the main test if diverticulitis is suspected</li>
            <li>A colonoscopy or CT colonography may be used to look at the bowel lining</li>
            <li>Blood tests can check for signs of infection</li>
          </ul>
        </div>
      </section>

      <section class="gi-cp-section" id="treatment">
        <div class="gi-cp-section-head"><div class="gi-cp-section-icon">💊</div><h2>Treatment &amp; management</h2></div>
        <div class="gi-cp-card">
          <div class="gi-cp-row">
            <div class="gi-cp-row-image"><img src="<?php echo esc_url( $tmpl . '/assets/img/gi-health/conditions/treatment-dd.jpg' ); ?>" loading="lazy" alt="Illustration showing Diverticular Disease treatment approaches"></div>
            <div class="gi-cp-row-text">
              <div class="gi-cp-split">
                <div class="gi-cp-split-col">
                  <h4>Diverticular disease</h4>
                  <ul>
                    <li>A high-fibre diet and plenty of fluids, increasing fibre gradually</li>
                    <li>Managing constipation</li>
                    <li>Simple pain relief such as paracetamol</li>
                    <li>Avoid NSAIDs and opioid painkillers, which can upset the bowel</li>
                  </ul>
                </div>
                <div class="gi-cp-split-col">
                  <h4>Diverticulitis</h4>
                  <ul>
                    <li>Mild cases can often be managed at home, sometimes with antibiotics</li>
                    <li>More serious cases may need hospital treatment</li>
                    <li>Occasionally surgery to treat or prevent complications</li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="gi-cp-section" id="living">
        <div class="gi-cp-section-head"><div class="gi-cp-section-icon">🌱</div><h2>Living with it &amp; prevention</h2></div>
        <div class="gi-cp-card">
          <div class="gi-cp-row">
            <div class="gi-cp-row-image"><img src="<?php echo esc_url( $tmpl . '/assets/img/gi-health/conditions/living-dd.jpg' ); ?>" loading="lazy" alt="Illustration showing positive lifestyle for managing Diverticular Disease"></div>
            <div class="gi-cp-row-text">
              <p>Most people with diverticular disease manage well with simple changes:</p>
              <ul class="gi-cp-list">
                <li>Eating plenty of fibre (fruit, vegetables, wholegrains)</li>
                <li>Drinking enough fluids</li>
                <li>Staying physically active</li>
                <li>Keeping to a healthy weight</li>
              </ul>
              <div class="gi-cp-highlight"><p><strong>Tip:</strong> These simple lifestyle changes can reduce symptoms and lower the chance of future flare-ups. Increase fibre gradually to avoid bloating.</p></div>
            </div>
          </div>
        </div>
      </section>

      <div class="gi-references">
        <h2>References &amp; further reading</h2>
        <ol>
          <li>NHS. <em>Diverticular disease and diverticulitis</em>. nhs.uk</li>
          <li>Guts UK Charity. <em>Diverticular disease</em>. gutscharity.org.uk</li>
          <li>NICE NG147. <em>Diverticular disease: diagnosis and management</em>. nice.org.uk</li>
        </ol>
        <p class="gi-disclaimer">This page is for general information and does not replace advice from your doctor or specialist team.</p>
      </div>

      <?php break;
    } // end switch ?>

    <!-- ===== Latest articles on this condition ===== -->
    <?php
    /* Articles are linked to a condition by a post_tag whose slug matches this
       page's slug — see vance_gi_conditions(), which is what keeps the page slug,
       the tag and the Discovery Suite's condition[] value identical. Renders
       nothing at all until at least one article carries the tag. */
    $cond_articles = new WP_Query( array(
        'post_type'           => vance_discovery_post_types(),
        'post_status'         => 'publish',
        'posts_per_page'      => 4,
        'ignore_sticky_posts' => true,
        'no_found_rows'       => true,
        'tax_query'           => array( array(
            'taxonomy' => 'post_tag',
            'field'    => 'slug',
            'terms'    => $slug,
        ) ),
    ) );
    $cond_label = isset( $gi_conditions[ $slug ] ) ? $gi_conditions[ $slug ]['label'] : get_the_title();
    if ( $cond_articles->have_posts() ) : ?>
    <style>
      .gi-cp-articles { margin: 48px 0 0; }
      .gi-cp-articles h2 { font-family: 'Outfit', sans-serif; font-size: 26px; font-weight: 800; color: #0F172A; margin: 0 0 20px; }
      .gi-cp-article-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; }
      .gi-cp-article { display: flex; flex-direction: column; gap: 8px; padding: 18px 20px; background: #fff; border: 1px solid #E2E8F0; border-left: 3px solid #008080; text-decoration: none; transition: box-shadow .2s, transform .2s; }
      .gi-cp-article:hover { box-shadow: 0 6px 18px rgba(0,0,0,.08); transform: translateY(-2px); }
      .gi-cp-article-meta { font-size: 12px; color: #94A3B8; }
      .gi-cp-article-title { font-family: 'Outfit', sans-serif; font-size: 16px; font-weight: 700; color: #0F172A; line-height: 1.35; }
      .gi-cp-article:hover .gi-cp-article-title { color: #008080; }
      .gi-cp-article-all { display: inline-block; margin-top: 18px; font-size: 14px; font-weight: 700; color: #008080; text-decoration: none; }
      .gi-cp-article-all:hover { text-decoration: underline; }
    </style>
    <section class="gi-cp-articles">
      <h2>Latest articles on <?php echo esc_html( $cond_label ); ?></h2>
      <div class="gi-cp-article-grid">
        <?php while ( $cond_articles->have_posts() ) : $cond_articles->the_post(); ?>
        <a class="gi-cp-article" href="<?php the_permalink(); ?>">
          <span class="gi-cp-article-meta"><?php echo esc_html( get_the_date() ); ?> &middot; <?php echo (int) vance_get_read_time( get_the_ID() ); ?> min read</span>
          <span class="gi-cp-article-title"><?php the_title(); ?></span>
        </a>
        <?php endwhile; ?>
      </div>
      <a class="gi-cp-article-all" href="<?php echo esc_url( home_url( '/discovery-results/?condition%5B%5D=' . rawurlencode( $slug ) ) ); ?>">See all articles on this condition &rarr;</a>
    </section>
    <?php endif; wp_reset_postdata(); ?>

    <!-- ===== Explore related conditions ===== -->
    <section class="gi-cp-explore">
      <h2><?php echo esc_html( $cp_explore_h2 ); ?></h2>
      <p><?php echo esc_html( $cp_explore_p ); ?></p>
      <div class="gi-cp-explore-links">
        <?php foreach ( $cp_explore[ $slug ] as $link ) : ?>
        <a href="<?php echo esc_url( vance_gi_cond_url( $link[0] ) ); ?>"><?php echo esc_html( $link[1] ); ?></a>
        <?php endforeach; ?>
      </div>
    </section>

  </div><!-- .gi-cp-container -->
  <?php endif; // $is_redesigned ?>
</main>

<script>
(function () {
  'use strict';
  var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* Reveal on scroll */
  (function () {
    var items = document.querySelectorAll('.gi-reveal');
    if (!items.length) return;
    if (reduceMotion || !('IntersectionObserver' in window)) {
      items.forEach(function (el) { el.classList.add('is-visible'); }); return;
    }
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (e.isIntersecting) { e.target.classList.add('is-visible'); io.unobserve(e.target); }
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
    items.forEach(function (el) { io.observe(el); });
  })();

  /* Counter animation */
  function animateCount(el) {
    var target = parseFloat(el.getAttribute('data-count'));
    if (reduceMotion) { el.textContent = target; return; }
    var dur = 1400, start = null;
    function step(ts) {
      if (!start) start = ts;
      var p = Math.min((ts - start) / dur, 1);
      var eased = 1 - Math.pow(1 - p, 3);
      el.textContent = Math.round(target * eased);
      if (p < 1) requestAnimationFrame(step); else el.textContent = target;
    }
    requestAnimationFrame(step);
  }
  (function () {
    var nums = document.querySelectorAll('[data-count]');
    if (!nums.length || !('IntersectionObserver' in window)) { nums.forEach(animateCount); return; }
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) { if (e.isIntersecting) { animateCount(e.target); io.unobserve(e.target); } });
    }, { threshold: 0.6 });
    nums.forEach(function (el) { io.observe(el); });
  })();

  /* TOC scrollspy */
  (function () {
    var links = document.querySelectorAll('.gi-toc a[href^="#"]');
    if (!links.length || !('IntersectionObserver' in window)) return;
    var map = {};
    links.forEach(function (a) {
      var id = a.getAttribute('href').slice(1);
      var sec = document.getElementById(id);
      if (sec) map[id] = a;
    });
    var sections = Object.keys(map).map(function (id) { return document.getElementById(id); });
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (e.isIntersecting) {
          links.forEach(function (l) { l.classList.remove('active'); });
          if (map[e.target.id]) map[e.target.id].classList.add('active');
        }
      });
    }, { rootMargin: '-30% 0px -60% 0px', threshold: 0 });
    sections.forEach(function (s) { if (s) io.observe(s); });
  })();
})();
</script>

<?php get_footer(); ?>
