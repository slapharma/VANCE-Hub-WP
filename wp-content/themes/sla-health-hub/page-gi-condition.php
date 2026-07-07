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
$nav_conditions = [
    ['slug' => 'inflammatory-bowel-disease',  'data' => 'ibd',          'label' => 'Inflammatory Bowel Disease'],
    ['slug' => 'ulcerative-colitis',           'data' => 'uc',           'label' => 'Ulcerative Colitis'],
    ['slug' => 'crohns-disease',               'data' => 'crohns',       'label' => 'Crohn\'s Disease'],
    ['slug' => 'microscopic-colitis',          'data' => 'mc',           'label' => 'Microscopic Colitis'],
    ['slug' => 'irritable-bowel-syndrome',     'data' => 'ibs',          'label' => 'Irritable Bowel Syndrome'],
    ['slug' => 'colorectal-cancer',            'data' => 'crc',          'label' => 'Colorectal Cancer'],
    ['slug' => 'diverticular-disease',         'data' => 'diverticular', 'label' => 'Diverticular Disease'],
];
?>

<main id="main-content">

  <!-- ===== Hero ===== -->
  <?php
  /* Resolve customizer values early so the hero can use them */
  $cond_key_map = [
      'inflammatory-bowel-disease' => 'ibd',
      'ulcerative-colitis'         => 'uc',
      'crohns-disease'             => 'crohns',
      'microscopic-colitis'        => 'mc',
      'irritable-bowel-syndrome'   => 'ibs',
      'colorectal-cancer'          => 'crc',
      'diverticular-disease'       => 'div',
  ];
  $cond_defaults = [
      'ibd'    => [ 'Inflammatory Bowel Disease (IBD)',      'Inflammatory bowel disease (IBD) is a chronic inflammatory condition of the gastrointestinal tract. It is divided mainly into Crohn\'s disease and ulcerative colitis.' ],
      'uc'     => [ 'Ulcerative Colitis',                    'A type of inflammatory bowel disease that causes inflammation and ulcers in the lining of the colon and rectum.' ],
      'crohns' => [ 'Crohn\'s Disease',                     'A type of inflammatory bowel disease that can cause inflammation anywhere in the digestive tract, most often the end of the small intestine.' ],
      'mc'     => [ 'Microscopic Colitis',                   'Inflammation of the colon that can only be seen under a microscope.' ],
      'ibs'    => [ 'Irritable Bowel Syndrome',              'A common, long-term disorder of how the gut functions, causing abdominal pain, bloating and changes in bowel habit, without visible damage to the bowel.' ],
      'crc'    => [ 'Colorectal Cancer',                     'Cancer that develops in the colon or rectum, often growing slowly from small growths called polyps.' ],
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
  ?>
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

          /* ─────────────────────────────────────────
             INFLAMMATORY BOWEL DISEASE
             ───────────────────────────────────────── */
          case 'inflammatory-bowel-disease': ?>

          <section id="what" class="gi-reveal">
            <h2>What is IBD?</h2>
            <p>Inflammatory bowel disease (IBD) is the umbrella term for a group of long-term conditions in which the immune system causes ongoing inflammation of the digestive tract. The three main types are Crohn's disease, ulcerative colitis and microscopic colitis.</p>
            <p>IBD usually follows a relapsing-remitting pattern: <strong>flares</strong>, when symptoms are active, followed by periods of <strong>remission</strong>, when symptoms settle.</p>
            <div class="gi-callout">
              <strong>Key fact:</strong> there isn't a single cure for IBD, but it can be managed well. Treatment can control inflammation, ease symptoms and help many people stay in long, stable remission, it is about finding the right management plan rather than a one-size-fits-all cure.
            </div>
            <div class="gi-keyfacts">
              <div class="gi-keyfact gi-reveal">
                <div class="gi-kf-num">~<span data-count="500">0</span>,000</div>
                <div class="gi-kf-label">People in the UK are estimated to live with IBD</div>
              </div>
              <div class="gi-keyfact gi-reveal" style="--reveal-delay:.08s">
                <div class="gi-kf-num">Any age</div>
                <div class="gi-kf-label">IBD can be diagnosed at any age, and is becoming more common</div>
              </div>
              <div class="gi-keyfact gi-reveal" style="--reveal-delay:.16s">
                <div class="gi-kf-num"><span data-count="3">0</span> main types</div>
                <div class="gi-kf-label">Crohn's, ulcerative colitis and microscopic colitis</div>
              </div>
            </div>
          </section>

          <section id="types" class="gi-reveal">
            <h2>Types of IBD</h2>
            <figure class="gi-diagram-card">
              <figcaption class="gi-diagram-title">Where each type affects the gut</figcaption>
              <svg viewBox="0 0 560 290" role="img" aria-label="Schematic diagram showing where each type of IBD occurs">
                <path d="M300 28 Q330 22 340 44 Q348 66 322 74 Q300 80 290 64 Q282 46 300 28" fill="none" stroke="#94a8b8" stroke-width="5" stroke-linejoin="round"/>
                <path d="M298 76 C 270 110, 330 128, 296 152 C 262 176, 330 196, 292 216 C 268 230, 250 222, 238 212" fill="none" stroke="#94a8b8" stroke-width="5" stroke-linecap="round"/>
                <path d="M170 250 L170 110 Q170 80 200 80 L386 80 Q416 80 416 110 L416 218 Q416 246 388 246 L362 246 Q340 246 346 268 L352 282" fill="none" stroke="#94a8b8" stroke-width="9" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M170 250 L170 110 Q170 80 200 80 L386 80 Q416 80 416 110 L416 218 Q416 246 388 246 L362 246 Q340 246 346 268 L352 282" fill="none" stroke="#008080" stroke-width="9" stroke-linecap="round" stroke-linejoin="round" stroke-opacity="0.55"/>
                <circle cx="238" cy="212" r="11" fill="#006666"/>
                <circle cx="296" cy="152" r="9" fill="#006666"/>
                <circle cx="322" cy="74" r="8" fill="#006666"/>
                <g transform="translate(118,178)" fill="none" stroke="#0A1929" stroke-width="4" stroke-linecap="round">
                  <circle cx="22" cy="22" r="16"/>
                  <line x1="34" y1="34" x2="48" y2="48"/>
                </g>
                <text x="118" y="158" font-family="Inter, sans-serif" font-size="13" fill="#6B7280">Seen only under</text>
                <text x="118" y="173" font-family="Inter, sans-serif" font-size="13" fill="#6B7280">the microscope</text>
              </svg>
              <ul class="gi-diagram-legend">
                <li><span class="gi-swatch" style="background:#006666"></span>Crohn's disease: patches anywhere, mouth to anus</li>
                <li><span class="gi-swatch" style="background:#008080;opacity:.55"></span>Ulcerative colitis: continuous, colon and rectum only</li>
                <li><span class="gi-swatch" style="background:#0A1929"></span>Microscopic colitis: colon looks normal; inflammation seen on biopsy</li>
              </ul>
              <p class="gi-diagram-note">Simplified schematic for illustration, not to anatomical scale.</p>
            </figure>
            <div class="gi-mini-grid">
              <div class="gi-mini-card">
                <h4>Crohn's disease</h4>
                <p>Can affect any part of the gut, from mouth to anus, in patches, most often the end of the small intestine.</p>
              </div>
              <div class="gi-mini-card">
                <h4>Ulcerative colitis</h4>
                <p>Affects only the large bowel (colon) and rectum, causing inflammation and ulcers in the lining.</p>
              </div>
              <div class="gi-mini-card">
                <h4>Microscopic colitis</h4>
                <p>A less widely known form of IBD where inflammation of the colon is only visible under a microscope.</p>
              </div>
            </div>
            <p>Crohn's disease and ulcerative colitis are the two most common forms. In a small number of people the pattern doesn't fit neatly into either and is described as IBD-unclassified.</p>
          </section>

          <section id="symptoms" class="gi-reveal">
            <h2>Symptoms</h2>
            <p>Symptoms vary by type and by which part of the gut is affected. Common ones include:</p>
            <ul class="gi-bullets">
              <li>Persistent diarrhoea, sometimes with blood or mucus</li>
              <li>Tummy (abdominal) pain and cramping</li>
              <li>Tiredness and fatigue</li>
              <li>Unintended weight loss and loss of appetite</li>
              <li>A frequent or urgent need to empty the bowel</li>
            </ul>
            <p>IBD can sometimes cause symptoms outside the gut too, such as joint pain, mouth ulcers or sore eyes. See the individual condition pages for more detail.</p>
          </section>

          <section id="causes" class="gi-reveal">
            <h2>Causes &amp; risk factors</h2>
            <p>The exact cause isn't fully understood, but IBD is thought to develop from a combination of factors:</p>
            <ul class="gi-bullets">
              <li><strong>Genetics and family history</strong>: IBD is more common if a close relative has it</li>
              <li><strong>The immune system</strong>: an over-active immune response in the gut</li>
              <li><strong>The gut microbiome</strong>: the community of bacteria living in the bowel</li>
              <li><strong>Diet, lifestyle and environmental factors</strong></li>
            </ul>
          </section>

          <section id="diagnosis" class="gi-reveal">
            <h2>Diagnosis</h2>
            <ul class="gi-bullets">
              <li>Blood tests to check for inflammation and anaemia</li>
              <li>A stool test for <strong>faecal calprotectin</strong>, a marker of gut inflammation</li>
              <li>Endoscopy (colonoscopy) to look at the bowel lining and take biopsies</li>
              <li>Scans such as MRI or CT to assess the small bowel</li>
            </ul>
          </section>

          <section id="treatment" class="gi-reveal">
            <h2>Treatment &amp; management</h2>
            <p>Treatment has two goals: settling a flare, then maintaining remission. The right approach depends on the type, location and severity of the condition.</p>
            <ul class="gi-bullets">
              <li>Anti-inflammatory medicines to calm the gut lining (for example aminosalicylates such as mesalazine)</li>
              <li>Immune-modifying and biologic therapies for moderate-to-severe disease (for example azathioprine, or biologics such as infliximab, adalimumab or vedolizumab)</li>
              <li>Short courses of steroids to bring a flare under control (for example prednisolone or budesonide)</li>
              <li>Surgery for some people when medicines aren't enough</li>
              <li>Support for diet and overall wellbeing alongside medical treatment</li>
            </ul>
            <p>Most people with IBD are cared for by a specialist team and have regular reviews to keep things under control.</p>
            <div class="gi-callout">
              <strong>Explore the conditions in detail:</strong>
              <a href="<?php echo esc_url( vance_gi_cond_url('crohns-disease') ); ?>" style="color:#006666;font-weight:600">Crohn's disease</a> ·
              <a href="<?php echo esc_url( vance_gi_cond_url('ulcerative-colitis') ); ?>" style="color:#006666;font-weight:600">Ulcerative colitis</a> ·
              <a href="<?php echo esc_url( vance_gi_cond_url('microscopic-colitis') ); ?>" style="color:#006666;font-weight:600">Microscopic colitis</a>
            </div>
          </section>

          <div class="gi-references gi-reveal">
            <h2>References &amp; further reading</h2>
            <ol>
              <li>NHS. <em>Inflammatory bowel disease</em>. nhs.uk</li>
              <li>NICE guidance on Crohn's disease and ulcerative colitis management. nice.org.uk</li>
              <li>Crohn's &amp; Colitis UK. crohnsandcolitis.org.uk</li>
              <li>Guts UK Charity. gutscharity.org.uk</li>
            </ol>
            <p class="gi-disclaimer">This page is for general information and does not replace advice from your doctor or specialist team.</p>
          </div>

          <?php
          $toc = ['what' => 'What is IBD?', 'types' => 'Types of IBD', 'symptoms' => 'Symptoms', 'causes' => 'Causes &amp; risk factors', 'diagnosis' => 'Diagnosis', 'treatment' => 'Treatment &amp; management'];
          break;

          /* ─────────────────────────────────────────
             ULCERATIVE COLITIS
             ───────────────────────────────────────── */
          case 'ulcerative-colitis': ?>

          <section id="what" class="gi-reveal">
            <h2>What is Ulcerative Colitis?</h2>
            <p>Ulcerative colitis (UC) is a long-term condition where the lining of the large bowel, the colon and rectum, becomes inflamed. Small sores (ulcers) can form on the lining, which may bleed and produce mucus.</p>
            <p>UC can occur at any age and is often first diagnosed in younger adults. Most people have times when symptoms flare up, followed by periods of remission when they feel well.</p>
            <div class="gi-callout">
              <strong>Key fact:</strong> ulcerative colitis always begins in the rectum and can spread part or all of the way up the colon. Unlike Crohn's disease, it affects only the colon and rectum, and only the inner lining.
            </div>
            <div class="gi-keyfacts">
              <div class="gi-keyfact gi-reveal">
                <div class="gi-kf-num">~<span data-count="1">0</span> in <span data-count="420">0</span></div>
                <div class="gi-kf-label">People in the UK are estimated to live with UC</div>
              </div>
            </div>
          </section>

          <section id="symptoms" class="gi-reveal">
            <h2>Symptoms</h2>
            <ul class="gi-bullets">
              <li>Diarrhoea, often containing blood or mucus</li>
              <li>An urgent and frequent need to empty the bowel</li>
              <li>Tummy pain and cramping, often eased by passing a stool</li>
              <li>A feeling of needing to go even when the bowel is empty (tenesmus)</li>
              <li>Tiredness, weight loss and reduced appetite during <strong>flares</strong></li>
            </ul>
            <div class="gi-callout"><strong>When to seek help:</strong> it's a good idea to seek urgent advice if you have a severe flare, for example frequent bloody stools, a fever, a racing heart or significant tummy pain.</div>

            <figure class="gi-diagram-card">
              <figcaption class="gi-diagram-title">How far UC extends</figcaption>
              <svg viewBox="0 0 560 230" role="img" aria-label="Diagram showing the three patterns of how far ulcerative colitis extends">
                <g transform="translate(28,16)">
                  <path d="M30 150 L30 50 Q30 22 58 22 L112 22 Q140 22 140 50 L140 118 Q140 142 118 142 L106 142 Q92 142 96 160 L102 176" fill="none" stroke="#c3d0da" stroke-width="8" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M106 142 Q92 142 96 160 L102 176" fill="none" stroke="#008080" stroke-width="9" stroke-linecap="round"/>
                  <text x="85" y="206" text-anchor="middle" font-family="Outfit, sans-serif" font-weight="600" font-size="14" fill="#0A1929">Proctitis</text>
                  <text x="85" y="222" text-anchor="middle" font-family="Inter, sans-serif" font-size="12" fill="#6B7280">rectum only · ~30%</text>
                </g>
                <g transform="translate(212,16)">
                  <path d="M30 150 L30 50 Q30 22 58 22 L112 22 Q140 22 140 50 L140 118 Q140 142 118 142 L106 142 Q92 142 96 160 L102 176" fill="none" stroke="#c3d0da" stroke-width="8" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M140 60 L140 118 Q140 142 118 142 L106 142 Q92 142 96 160 L102 176" fill="none" stroke="#008080" stroke-width="9" stroke-linecap="round" stroke-linejoin="round"/>
                  <text x="85" y="206" text-anchor="middle" font-family="Outfit, sans-serif" font-weight="600" font-size="14" fill="#0A1929">Left-sided colitis</text>
                  <text x="85" y="222" text-anchor="middle" font-family="Inter, sans-serif" font-size="12" fill="#6B7280">up the left colon · ~40%</text>
                </g>
                <g transform="translate(396,16)">
                  <path d="M30 150 L30 50 Q30 22 58 22 L112 22 Q140 22 140 50 L140 118 Q140 142 118 142 L106 142 Q92 142 96 160 L102 176" fill="none" stroke="#008080" stroke-width="9" stroke-linecap="round" stroke-linejoin="round"/>
                  <text x="85" y="206" text-anchor="middle" font-family="Outfit, sans-serif" font-weight="600" font-size="14" fill="#0A1929">Extensive colitis</text>
                  <text x="85" y="222" text-anchor="middle" font-family="Inter, sans-serif" font-size="12" fill="#6B7280">most or all of the colon · ~30%</text>
                </g>
              </svg>
              <p class="gi-diagram-note">Illustrative proportions at diagnosis; the pattern varies between individuals. UC always starts in the rectum.</p>
            </figure>
          </section>

          <section id="causes" class="gi-reveal">
            <h2>Causes &amp; risk factors</h2>
            <p>UC develops from a combination of factors, rather than one single cause:</p>
            <ul class="gi-bullets">
              <li>Genetics and family history of IBD</li>
              <li>The immune system's response in the gut</li>
              <li>The gut microbiome</li>
              <li>Environmental factors</li>
            </ul>
          </section>

          <section id="diagnosis" class="gi-reveal">
            <h2>Diagnosis</h2>
            <ul class="gi-bullets">
              <li>Blood tests for inflammation and anaemia</li>
              <li>Stool tests, including <strong>faecal calprotectin</strong>, and tests to rule out infection</li>
              <li>Colonoscopy with biopsies to confirm the diagnosis and map how much of the colon is involved</li>
            </ul>
          </section>

          <section id="treatment" class="gi-reveal">
            <h2>Treatment &amp; management</h2>
            <p>The aim is to bring <strong>flares</strong> under control, then keep the bowel healed and in <strong>remission</strong>.</p>
            <ul class="gi-bullets">
              <li><strong>Aminosalicylates / 5-ASAs</strong> (for example mesalazine or sulfasalazine): often the first-line treatment, taken by mouth or as an enema/suppository</li>
              <li><strong>Steroids</strong> (for example prednisolone or budesonide): short courses to settle a flare</li>
              <li><strong>Immunosuppressants and biologic therapies</strong> (for example azathioprine, infliximab, adalimumab or vedolizumab): for more active or hard-to-treat disease</li>
              <li><strong>Surgery</strong>: removing the colon can cure UC and is an option for some people</li>
            </ul>
          </section>

          <section id="living" class="gi-reveal">
            <h2>Living with Ulcerative Colitis</h2>
            <p>Many people with UC lead full, active lives. Taking maintenance treatment as prescribed, attending reviews, knowing your personal warning signs and looking after sleep, stress and diet all help keep things stable.</p>
          </section>

          <div class="gi-references gi-reveal">
            <h2>References &amp; further reading</h2>
            <ol>
              <li>NHS. <em>Ulcerative colitis</em>. nhs.uk</li>
              <li>NICE NG130. <em>Ulcerative colitis: management</em>. nice.org.uk</li>
              <li>Crohn's &amp; Colitis UK. <em>Ulcerative colitis</em>. crohnsandcolitis.org.uk</li>
            </ol>
            <p class="gi-disclaimer">This page is for general information and does not replace advice from your doctor or specialist team.</p>
          </div>

          <?php
          $toc = ['what' => 'What is Ulcerative Colitis?', 'symptoms' => 'Symptoms', 'causes' => 'Causes &amp; risk factors', 'diagnosis' => 'Diagnosis', 'treatment' => 'Treatment &amp; management', 'living' => 'Living with Ulcerative Colitis'];
          break;

          /* ─────────────────────────────────────────
             CROHN'S DISEASE
             ───────────────────────────────────────── */
          case 'crohns-disease': ?>

          <section id="what" class="gi-reveal">
            <h2>What is Crohn's Disease?</h2>
            <p>Crohn's disease is a long-term condition where the immune system causes inflammation that can appear anywhere along the gut, from the mouth to the anus. It most commonly affects the last part of the small intestine (the terminal ileum) and the start of the colon.</p>
            <p>A key feature is that the inflammation comes in patches, with healthy stretches of bowel in between, and it can reach through the full thickness of the bowel wall. Over time this can sometimes lead to narrowing (strictures) or small tunnels between loops of bowel (fistulas).</p>
            <div class="gi-callout"><strong>Crohn's vs ulcerative colitis:</strong> Crohn's can affect any part of the gut, in patches, and through the whole bowel wall. Ulcerative colitis affects only the colon and rectum, in a continuous pattern, and only the lining.</div>
            <div class="gi-callout"><strong>Key fact:</strong> Crohn's most commonly affects the end of the small intestine (the terminal ileum), but it can appear anywhere from mouth to anus.</div>
            <div class="gi-keyfacts">
              <div class="gi-keyfact gi-reveal">
                <div class="gi-kf-num">~115,000–250,000</div>
                <div class="gi-kf-label">People in the UK are estimated to live with Crohn's disease</div>
              </div>
            </div>
          </section>

          <section id="symptoms" class="gi-reveal">
            <h2>Symptoms</h2>
            <ul class="gi-bullets">
              <li>Tummy pain and cramping, often in the lower right side</li>
              <li>Persistent or recurring diarrhoea</li>
              <li>Fatigue and feeling generally unwell</li>
              <li>Unintended weight loss</li>
              <li>Blood or mucus in the stool</li>
              <li>Mouth ulcers, and soreness around the anus</li>
            </ul>
            <figure class="gi-diagram-card">
              <figcaption class="gi-diagram-title">Where Crohn's commonly occurs</figcaption>
              <svg viewBox="0 0 560 290" role="img" aria-label="Schematic gut diagram showing where Crohn's disease commonly occurs">
                <path d="M298 76 C 270 110, 330 128, 296 152 C 262 176, 330 196, 292 216 C 268 230, 250 222, 238 212" fill="none" stroke="#94a8b8" stroke-width="5" stroke-linecap="round"/>
                <path d="M170 250 L170 110 Q170 80 200 80 L386 80 Q416 80 416 110 L416 218 Q416 246 388 246 L362 246 Q340 246 346 268 L352 282" fill="none" stroke="#94a8b8" stroke-width="9" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M200 80 L386 80 Q416 80 416 110 L416 190" fill="none" stroke="#78bfbf" stroke-width="9" stroke-linecap="round" stroke-linejoin="round"/>
                <circle cx="238" cy="212" r="14" fill="#006666"/>
                <path d="M170 250 L170 130" fill="none" stroke="#008080" stroke-width="9" stroke-linecap="round"/>
                <circle cx="276" cy="184" r="9" fill="#008080"/>
                <text x="118" y="262" font-family="Inter, sans-serif" font-size="13" fill="#6B7280" text-anchor="end">Terminal</text>
                <text x="118" y="277" font-family="Inter, sans-serif" font-size="13" fill="#6B7280" text-anchor="end">ileum</text>
                <line x1="124" y1="262" x2="224" y2="216" stroke="#cdd6df" stroke-width="2"/>
              </svg>
              <ul class="gi-diagram-legend">
                <li><span class="gi-swatch" style="background:#006666"></span>Terminal ileum: ~30%</li>
                <li><span class="gi-swatch" style="background:#78bfbf"></span>Colon only: ~30%</li>
                <li><span class="gi-swatch" style="background:#008080"></span>Ileum &amp; colon: ~40%</li>
              </ul>
              <p class="gi-diagram-note">Illustrative split of where Crohn's commonly occurs; it can affect any part of the gut.</p>
            </figure>
          </section>

          <section id="causes" class="gi-reveal">
            <h2>Causes &amp; risk factors</h2>
            <p>As with other forms of IBD, Crohn's results from a combination of factors:</p>
            <ul class="gi-bullets">
              <li>Genetics and family history</li>
              <li>The immune system's response in the gut</li>
              <li>The gut microbiome</li>
              <li>Environmental factors, including smoking, which is linked to a higher risk and severity of Crohn's</li>
            </ul>
          </section>

          <section id="diagnosis" class="gi-reveal">
            <h2>Diagnosis</h2>
            <ul class="gi-bullets">
              <li>Blood and stool tests, including <strong>faecal calprotectin</strong></li>
              <li>Colonoscopy with biopsies</li>
              <li>MRI or CT scans to assess the small bowel and check for complications</li>
              <li>Capsule endoscopy in some cases</li>
            </ul>
          </section>

          <section id="treatment" class="gi-reveal">
            <h2>Treatment &amp; management</h2>
            <p>Treatment can bring on and maintain remission and help prevent complications.</p>
            <ul class="gi-bullets">
              <li>Steroids (for example prednisolone or budesonide) or liquid nutrition therapy to settle a flare</li>
              <li>Immunosuppressants and biologic therapies (for example azathioprine, infliximab, adalimumab or ustekinumab) to maintain remission</li>
              <li>Surgery to remove or repair badly affected sections, common in Crohn's, though inflammation can return</li>
            </ul>
          </section>

          <section id="living" class="gi-reveal">
            <h2>Living with Crohn's Disease</h2>
            <p>With modern treatment and regular specialist review, most people with Crohn's manage their symptoms well. Good nutrition, watching for warning signs and looking after mental wellbeing are all important parts of care.</p>
          </section>

          <div class="gi-references gi-reveal">
            <h2>References &amp; further reading</h2>
            <ol>
              <li>NHS. <em>Crohn's disease</em>. nhs.uk</li>
              <li>NICE NG129. <em>Crohn's disease: management</em>. nice.org.uk</li>
              <li>Crohn's &amp; Colitis UK. <em>Crohn's disease</em>. crohnsandcolitis.org.uk</li>
            </ol>
            <p class="gi-disclaimer">This page is for general information and does not replace advice from your doctor or specialist team.</p>
          </div>

          <?php
          $toc = ['what' => 'What is Crohn\'s Disease?', 'symptoms' => 'Symptoms', 'causes' => 'Causes &amp; risk factors', 'diagnosis' => 'Diagnosis', 'treatment' => 'Treatment &amp; management', 'living' => 'Living with Crohn\'s Disease'];
          break;

          /* ─────────────────────────────────────────
             MICROSCOPIC COLITIS
             ───────────────────────────────────────── */
          case 'microscopic-colitis': ?>

          <section id="what" class="gi-reveal">
            <h2>What is Microscopic Colitis?</h2>
            <p>Microscopic colitis is a type of inflammatory bowel disease affecting the large bowel. Its defining feature is that the colon usually looks completely normal during a colonoscopy. The inflammation only shows up when a small tissue sample (a biopsy) is examined under a microscope.</p>
            <p>Because the bowel looks normal, it is often mistaken for irritable bowel syndrome. It is a common and treatable cause of ongoing watery diarrhoea, particularly in older adults.</p>
            <div class="gi-callout">
              <strong>Key fact:</strong> microscopic colitis is more common in women, especially over the age of 60, and is confirmed by taking a biopsy even when the colon looks normal.
            </div>
          </section>

          <section id="subtypes" class="gi-reveal">
            <h2>Subtypes</h2>
            <table class="gi-compare-table">
              <thead>
                <tr><th>Subtype</th><th>What's different under the microscope</th></tr>
              </thead>
              <tbody>
                <tr><td><strong>Collagenous colitis</strong></td><td>A thickened band of collagen forms just beneath the lining of the colon</td></tr>
                <tr><td><strong>Lymphocytic colitis</strong></td><td>An increased number of white blood cells (lymphocytes) in the colon lining</td></tr>
              </tbody>
            </table>
            <p>The two subtypes cause similar symptoms and are generally treated in the same way.</p>
          </section>

          <section id="symptoms" class="gi-reveal">
            <h2>Symptoms</h2>
            <ul class="gi-bullets">
              <li>Chronic watery diarrhoea that is not bloody</li>
              <li>An urgent need to empty the bowel, sometimes at night</li>
              <li>Tummy pain or cramps</li>
              <li>Faecal incontinence in some people</li>
              <li>Tiredness and mild weight loss</li>
            </ul>
          </section>

          <section id="causes" class="gi-reveal">
            <h2>Causes &amp; risk factors</h2>
            <p>The exact trigger isn't fully understood, but it is thought to involve the immune system reacting to something passing through the bowel. Microscopic colitis is more likely in people with these known associations:</p>
            <ul class="gi-bullets">
              <li>Being female and over 60</li>
              <li>Autoimmune conditions such as coeliac disease, thyroid disorders and rheumatoid arthritis</li>
              <li>Certain medicines, including some anti-inflammatory painkillers (NSAIDs), acid-reducing drugs (PPIs) and some antidepressants (SSRIs)</li>
            </ul>
          </section>

          <section id="diagnosis" class="gi-reveal">
            <h2>Diagnosis</h2>
            <p>Diagnosis relies on a colonoscopy <strong>with biopsies</strong>. Even when the bowel looks normal, the laboratory findings confirm the type. Blood and stool tests help rule out other causes such as coeliac disease or infection.</p>
            <div class="gi-callout"><strong>Why it can be missed:</strong> a normal-looking colonoscopy can be falsely reassuring. In anyone with persistent watery diarrhoea, taking biopsies is the key step that confirms the diagnosis.</div>
          </section>

          <section id="treatment" class="gi-reveal">
            <h2>Treatment &amp; management</h2>
            <ul class="gi-bullets">
              <li><strong>Budesonide</strong>, a targeted steroid, is usually the first-line treatment and works well for most people</li>
              <li>Reviewing any medicines that may be contributing</li>
              <li>Anti-diarrhoeal medicines for milder symptoms</li>
              <li>Simple dietary adjustments</li>
            </ul>
            <p>Symptoms often settle with treatment, though they can come back and may need a further course.</p>
          </section>

          <div class="gi-references gi-reveal">
            <h2>References &amp; further reading</h2>
            <ol>
              <li>Guts UK Charity. <em>Microscopic colitis</em>. gutscharity.org.uk</li>
              <li>British Society of Gastroenterology guidelines on microscopic colitis. bsg.org.uk</li>
              <li>NHS. <em>Colitis</em>. nhs.uk</li>
            </ol>
            <p class="gi-disclaimer">This page is for general information and does not replace advice from your doctor or specialist team.</p>
          </div>

          <?php
          $toc = ['what' => 'What is Microscopic Colitis?', 'subtypes' => 'Subtypes', 'symptoms' => 'Symptoms', 'causes' => 'Causes &amp; risk factors', 'diagnosis' => 'Diagnosis', 'treatment' => 'Treatment &amp; management'];
          break;

          /* ─────────────────────────────────────────
             IRRITABLE BOWEL SYNDROME
             ───────────────────────────────────────── */
          case 'irritable-bowel-syndrome': ?>

          <section id="what" class="gi-reveal">
            <h2>What is Irritable Bowel Syndrome?</h2>
            <p>Irritable bowel syndrome (IBS) is one of the most common digestive conditions. It is a <strong>functional</strong> disorder, which means the gut doesn't work as it should even though it looks normal and isn't damaged. This is what sets it apart from inflammatory bowel disease.</p>
            <p>IBS is closely linked to how the gut and the brain communicate; this is often referred to as the <strong>gut-brain axis</strong>. It can be uncomfortable and disruptive, but it does not damage the bowel or raise the risk of bowel cancer.</p>
            <div class="gi-keyfacts">
              <div class="gi-keyfact gi-reveal">
                <div class="gi-kf-num"><span data-count="1">0</span> in <span data-count="7">0</span></div>
                <div class="gi-kf-label">Adults are thought to have IBS symptoms</div>
              </div>
              <div class="gi-keyfact gi-reveal" style="--reveal-delay:.08s">
                <div class="gi-kf-num">~<span data-count="2">0</span>× more common</div>
                <div class="gi-kf-label">In women than in men</div>
              </div>
              <div class="gi-keyfact gi-reveal" style="--reveal-delay:.16s">
                <div class="gi-kf-num"><span data-count="3">0</span> main types</div>
                <div class="gi-kf-label">IBS-D, IBS-C and IBS-M</div>
              </div>
            </div>
          </section>

          <section id="subtypes" class="gi-reveal">
            <h2>Subtypes</h2>
            <p>IBS is grouped by the main change in bowel habit, which helps guide treatment:</p>
            <div class="gi-mini-grid">
              <div class="gi-mini-card">
                <h4>IBS-D</h4>
                <p>Diarrhoea-predominant: loose or frequent stools are the main problem.</p>
              </div>
              <div class="gi-mini-card">
                <h4>IBS-C</h4>
                <p>Constipation-predominant: infrequent, hard or difficult-to-pass stools.</p>
              </div>
              <div class="gi-mini-card">
                <h4>IBS-M</h4>
                <p>Mixed: alternating between diarrhoea and constipation over time.</p>
              </div>
            </div>
            <p>Many people move between subtypes over time.</p>
          </section>

          <section id="symptoms" class="gi-reveal">
            <h2>Symptoms</h2>
            <ul class="gi-bullets">
              <li>Abdominal pain or cramping, often relieved by passing a stool</li>
              <li>Bloating and a swollen tummy</li>
              <li>Diarrhoea, constipation, or both at different times</li>
              <li>Excess wind</li>
              <li>A feeling of not having fully emptied the bowel</li>
              <li>Mucus in the stool</li>
            </ul>
            <div class="gi-callout"><strong>When to get checked:</strong> some symptoms are not typical of IBS and should always be checked by a doctor: blood in the stool, unexplained weight loss, a persistent change in bowel habit over the age of 50, or symptoms that wake you at night.</div>
          </section>

          <section id="causes" class="gi-reveal">
            <h2>Causes &amp; triggers</h2>
            <p>There is no single cause. IBS is thought to involve a sensitive gut, changes in how quickly the bowel moves, and altered gut-brain signalling. Common triggers that can worsen symptoms include:</p>
            <ul class="gi-bullets">
              <li><strong>Diet</strong>: certain foods, including some high in fermentable carbohydrates (FODMAPs)</li>
              <li><strong>Stress and anxiety</strong>, through the gut-brain connection</li>
              <li><strong>A previous bout of gastroenteritis</strong> (post-infectious IBS)</li>
              <li><strong>Hormonal changes</strong>, such as around the menstrual cycle</li>
            </ul>
          </section>

          <section id="diagnosis" class="gi-reveal">
            <h2>Diagnosis</h2>
            <p>IBS is usually diagnosed from the typical pattern of symptoms (often using the <strong>Rome criteria</strong>) once other conditions have been ruled out. A doctor may arrange blood tests, a coeliac disease test and a stool test for <strong>faecal calprotectin</strong> to help exclude inflammation or infection.</p>
          </section>

          <section id="treatment" class="gi-reveal">
            <h2>Treatment &amp; management</h2>
            <p>IBS cannot be cured, but symptoms can usually be well controlled with a combination of approaches tailored to the individual:</p>
            <ul class="gi-bullets">
              <li><strong>Diet and lifestyle</strong>: regular meals, adjusting fibre, and limiting caffeine and alcohol; a dietitian-supervised low-FODMAP diet helps many people</li>
              <li><strong>Medicines</strong>: antispasmodics for pain, laxatives for constipation, or anti-diarrhoeals for loose stools</li>
              <li><strong>Probiotics</strong>: worth a trial for some people</li>
              <li><strong>Psychological therapies</strong>: cognitive behavioural therapy (CBT) or gut-directed hypnotherapy can be very effective</li>
            </ul>
          </section>

          <section id="living" class="gi-reveal">
            <h2>Living with IBS</h2>
            <p>Identifying your personal triggers, often with a food and symptom diary, managing stress, staying active and keeping a regular routine can all make a big difference. IBS tends to come and go, but most people find a management plan that lets them get on with daily life.</p>
          </section>

          <div class="gi-references gi-reveal">
            <h2>References &amp; further reading</h2>
            <ol>
              <li>NHS. <em>Irritable bowel syndrome (IBS)</em>. nhs.uk</li>
              <li>NICE CG61. <em>Irritable bowel syndrome in adults</em>. nice.org.uk</li>
              <li>Guts UK Charity. gutscharity.org.uk</li>
              <li>The IBS Network. theibsnetwork.org</li>
            </ol>
            <p class="gi-disclaimer">This page is for general information and does not replace advice from your doctor or specialist team.</p>
          </div>

          <?php
          $toc = ['what' => 'What is Irritable Bowel Syndrome?', 'subtypes' => 'Subtypes', 'symptoms' => 'Symptoms', 'causes' => 'Causes &amp; triggers', 'diagnosis' => 'Diagnosis', 'treatment' => 'Treatment &amp; management', 'living' => 'Living with IBS'];
          break;

          /* ─────────────────────────────────────────
             COLORECTAL CANCER
             ───────────────────────────────────────── */
          case 'colorectal-cancer': ?>

          <section id="what" class="gi-reveal">
            <h2>What is Colorectal Cancer?</h2>
            <p>Colorectal cancer, also called bowel cancer, starts in the large bowel (colon) or back passage (rectum). Most bowel cancers develop slowly from small growths called polyps on the bowel lining. Not all polyps become cancer, and finding and removing them, often during screening, can stop a cancer from ever forming.</p>
            <div class="gi-keyfacts">
              <div class="gi-keyfact gi-reveal">
                <div class="gi-kf-num"><span data-count="9">0</span> in <span data-count="10">0</span></div>
                <div class="gi-kf-label">Survive bowel cancer when it is found at the earliest stage</div>
              </div>
              <div class="gi-keyfact gi-reveal" style="--reveal-delay:.08s">
                <div class="gi-kf-num">4th</div>
                <div class="gi-kf-label">Most common cancer in the UK</div>
              </div>
            </div>
          </section>

          <section id="symptoms" class="gi-reveal">
            <h2>Symptoms</h2>
            <ul class="gi-bullets">
              <li>Bleeding from the back passage, or blood in the stool</li>
              <li>A lasting change in bowel habit, looser stools, or going more often</li>
              <li>Tummy pain, bloating or discomfort, especially after eating</li>
              <li>Unintended weight loss</li>
              <li>Tiredness or breathlessness from unexplained anaemia</li>
            </ul>
            <div class="gi-callout"><strong>Don't panic:</strong> most of the time these symptoms are caused by something far less serious than cancer. If they last more than a few weeks, see your GP.</div>
          </section>

          <section id="risk" class="gi-reveal">
            <h2>Risk factors</h2>
            <ul class="gi-bullets">
              <li><strong>Age</strong>: risk rises from around the age of 50</li>
              <li>A family history of bowel cancer, or certain inherited conditions</li>
              <li>A long history of inflammatory bowel disease</li>
              <li><strong>Lifestyle</strong>: diets high in red and processed meat and low in fibre, being overweight, smoking and drinking a lot of alcohol</li>
            </ul>
          </section>

          <section id="screening" class="gi-reveal">
            <h2>Screening</h2>
            <p>Screening looks for early signs of cancer in people who have no symptoms. This is the single most effective way to catch bowel cancer early.</p>
            <figure class="gi-diagram-card">
              <figcaption class="gi-diagram-title">How NHS bowel cancer screening works</figcaption>
              <div class="gi-steps">
                <div class="gi-step">
                  <h4>A kit arrives by post</h4>
                  <p>An NHS bowel screening kit is posted to eligible adults within the screening age range.</p>
                </div>
                <div class="gi-step">
                  <h4>Take the FIT test at home</h4>
                  <p>The faecal immunochemical test takes a few minutes and detects tiny traces of blood in the stool.</p>
                </div>
                <div class="gi-step">
                  <h4>Colonoscopy if needed</h4>
                  <p>If the test is positive, a colonoscopy looks at the bowel lining and can remove any polyps found.</p>
                </div>
              </div>
            </figure>
            <div class="gi-callout"><strong>If you receive a screening kit, use it.</strong> Using it takes only a few minutes at home and can detect changes long before any symptoms appear.</div>
          </section>

          <section id="diagnosis" class="gi-reveal">
            <h2>Diagnosis</h2>
            <p>If bowel cancer is suspected, the main test is a <strong>colonoscopy</strong>, which lets the bowel be examined and biopsies taken. Scans such as CT then check the size of the cancer and whether it has spread (its stage), which guides treatment.</p>
          </section>

          <section id="treatment" class="gi-reveal">
            <h2>Treatment</h2>
            <p>Treatment depends on where the cancer is, its stage and a person's general health. The main options, often used in combination, are:</p>
            <ul class="gi-bullets">
              <li>Surgery to remove the affected part of the bowel, the most common treatment</li>
              <li>Chemotherapy</li>
              <li>Radiotherapy, particularly for rectal cancer</li>
              <li>Targeted and immunotherapy medicines for some cancers</li>
            </ul>
          </section>

          <section id="prevention" class="gi-reveal">
            <h2>Prevention</h2>
            <p>You can lower your risk by eating plenty of fibre, fruit and vegetables, limiting red and processed meat and alcohol, keeping to a healthy weight, staying active and not smoking, alongside taking part in screening when invited.</p>
          </section>

          <div class="gi-references gi-reveal">
            <h2>References &amp; further reading</h2>
            <ol>
              <li>NHS. <em>Bowel cancer</em>. nhs.uk</li>
              <li>NHS. <em>Bowel cancer screening</em>. nhs.uk</li>
              <li>Bowel Cancer UK. bowelcanceruk.org.uk</li>
              <li>Cancer Research UK. <em>Bowel cancer</em>. cancerresearchuk.org</li>
            </ol>
            <p class="gi-disclaimer">This page is for general information and does not replace advice from your doctor or specialist team.</p>
          </div>

          <?php
          $toc = ['what' => 'What is Colorectal Cancer?', 'symptoms' => 'Symptoms', 'risk' => 'Risk factors', 'screening' => 'Screening', 'diagnosis' => 'Diagnosis', 'treatment' => 'Treatment', 'prevention' => 'Prevention'];
          break;

          /* ─────────────────────────────────────────
             DIVERTICULAR DISEASE
             ───────────────────────────────────────── */
          case 'diverticular-disease': ?>

          <section id="what" class="gi-reveal">
            <h2>What is diverticular disease and diverticulitis?</h2>
            <p>Diverticular disease and diverticulitis affect the large bowel (colon). Over time, small pouches called <strong>diverticula</strong> can develop in the bowel wall. Having these pouches is extremely common as we age and is known as <strong>diverticulosis</strong>.</p>
            <p>Most people with diverticula never know they have them. When the pouches cause symptoms such as tummy pain, it is called <strong>diverticular disease</strong>. If a pouch becomes inflamed or infected, this is <strong>diverticulitis</strong>.</p>
            <div class="gi-callout">
              <strong>Key fact:</strong> diverticula are a normal part of getting older for many people and usually cause no problems at all. Simple steps, including eating enough fibre, can help ease symptoms and keep them away.
            </div>
          </section>

          <section id="terms" class="gi-reveal">
            <h2>Three terms explained</h2>
            <figure class="gi-diagram-card">
              <figcaption class="gi-diagram-title">A section of the colon wall</figcaption>
              <svg viewBox="0 0 560 200" role="img" aria-label="Cross-section diagram of the colon showing healthy bowel, pouches and an inflamed pouch">
                <rect x="30" y="55" width="500" height="70" rx="35" fill="#def4f4" stroke="#94a8b8" stroke-width="5"/>
                <text x="105" y="45" text-anchor="middle" font-family="Inter, sans-serif" font-size="13" fill="#6B7280">Healthy bowel wall</text>
                <path d="M250 124 q0 26 22 26 q22 0 22 -26" fill="#def4f4" stroke="#008080" stroke-width="5"/>
                <path d="M330 124 q0 22 18 22 q18 0 18 -22" fill="#def4f4" stroke="#008080" stroke-width="5"/>
                <text x="300" y="184" text-anchor="middle" font-family="Inter, sans-serif" font-size="13" fill="#6B7280">Pouches (diverticula)</text>
                <path d="M430 124 q0 28 24 28 q24 0 24 -28" fill="#f6ddda" stroke="#c0564b" stroke-width="5"/>
                <text x="478" y="184" text-anchor="middle" font-family="Inter, sans-serif" font-size="13" fill="#6B7280">Inflamed (diverticulitis)</text>
              </svg>
              <p class="gi-diagram-note">Simplified cross-section for illustration.</p>
            </figure>
            <table class="gi-compare-table">
              <thead>
                <tr><th>Term</th><th>What it means</th></tr>
              </thead>
              <tbody>
                <tr><td><strong>Diverticulosis</strong></td><td>Pouches are present in the bowel, but cause no symptoms</td></tr>
                <tr><td><strong>Diverticular disease</strong></td><td>The pouches cause symptoms such as tummy pain</td></tr>
                <tr><td><strong>Diverticulitis</strong></td><td>One or more pouches become inflamed or infected</td></tr>
              </tbody>
            </table>
          </section>

          <section id="symptoms" class="gi-reveal">
            <h2>Symptoms</h2>
            <h3>Diverticular disease</h3>
            <ul class="gi-bullets">
              <li>Tummy pain, usually in the lower left side (a small number of people feel it on the right)</li>
              <li>Pain that often eases after passing wind or having a poo</li>
              <li>Bloating</li>
              <li>Constipation, diarrhoea, or both</li>
              <li>Occasionally, blood in the stool</li>
            </ul>
            <h3>Diverticulitis (more serious)</h3>
            <ul class="gi-bullets">
              <li>More severe, constant tummy pain (usually on the left)</li>
              <li>A high temperature (fever)</li>
              <li>Feeling sick or being sick</li>
              <li>A change in bowel habit, sometimes with blood or mucus</li>
              <li>Feeling generally tired and unwell</li>
            </ul>
            <div class="gi-callout"><strong>When to seek help:</strong> see your GP if you have ongoing tummy pain or a change in bowel habit. Seek urgent medical advice if you have severe tummy pain with a fever, as diverticulitis sometimes needs prompt treatment.</div>
          </section>

          <section id="causes" class="gi-reveal">
            <h2>Causes &amp; risk factors</h2>
            <p>Diverticula are thought to form when weak spots in the bowel wall give way under pressure, for example from straining with constipation. Factors linked to a higher risk include:</p>
            <ul class="gi-bullets">
              <li>A low-fibre diet</li>
              <li>Getting older, as the bowel wall naturally weakens</li>
              <li>Constipation and straining</li>
              <li>Being overweight</li>
              <li>Not being very physically active</li>
              <li>Some painkillers, such as NSAIDs (anti-inflammatories)</li>
              <li>Smoking</li>
            </ul>
          </section>

          <section id="diagnosis" class="gi-reveal">
            <h2>Diagnosis</h2>
            <ul class="gi-bullets">
              <li>Diverticulosis is often found by chance during tests done for another reason</li>
              <li>A CT scan is the main test if diverticulitis is suspected</li>
              <li>A colonoscopy or CT colonography may be used to look at the bowel lining</li>
              <li>Blood tests can check for signs of infection</li>
            </ul>
          </section>

          <section id="treatment" class="gi-reveal">
            <h2>Treatment &amp; management</h2>
            <h3>Diverticular disease</h3>
            <ul class="gi-bullets">
              <li>A high-fibre diet and plenty of fluids, increasing fibre gradually</li>
              <li>Managing constipation</li>
              <li>Simple pain relief such as paracetamol; avoid NSAIDs and opioid painkillers, which can upset the bowel</li>
            </ul>
            <h3>Diverticulitis</h3>
            <ul class="gi-bullets">
              <li>Mild cases can often be managed at home, sometimes with antibiotics</li>
              <li>More serious cases may need hospital treatment, and occasionally surgery, to treat or prevent complications</li>
            </ul>
          </section>

          <section id="living" class="gi-reveal">
            <h2>Living with it &amp; prevention</h2>
            <p>Most people with diverticular disease manage well with simple changes. Eating plenty of fibre, drinking enough fluids, staying active and keeping to a healthy weight can all help reduce symptoms and lower the chance of future flare-ups.</p>
          </section>

          <div class="gi-references gi-reveal">
            <h2>References &amp; further reading</h2>
            <ol>
              <li>NHS. <em>Diverticular disease and diverticulitis</em>. nhs.uk</li>
              <li>Guts UK Charity. <em>Diverticular disease</em>. gutscharity.org.uk</li>
              <li>NICE NG147. <em>Diverticular disease: diagnosis and management</em>. nice.org.uk</li>
            </ol>
            <p class="gi-disclaimer">This page is for general information and does not replace advice from your doctor or specialist team.</p>
          </div>

          <?php
          $toc = ['what' => 'What is diverticular disease?', 'terms' => 'Three terms explained', 'symptoms' => 'Symptoms', 'causes' => 'Causes &amp; risk factors', 'diagnosis' => 'Diagnosis', 'treatment' => 'Treatment &amp; management', 'living' => 'Living with it &amp; prevention'];
          break;

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
