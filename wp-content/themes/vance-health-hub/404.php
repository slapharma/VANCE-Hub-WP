<?php
/**
 * 404 template.
 *
 * Before this existed, a bad URL (including the two dead-tool pages removed
 * 2026-08-07) fell through to index.php's generic "No content found." with no
 * heading, no branding, and a <title> identical to the homepage's. The first
 * version of this file fixed that with a centred block of text and a single
 * "Home" button.
 *
 * It now renders the spotlight hero the rest of the site runs, from
 * inc/page-hero-spotlight.php, under the page key 'e404'. Three things are
 * deliberate and are explained at that config entry rather than repeated here:
 *
 *   - NO TOGGLE. Every other page carrying this hero can switch back to a dark
 *     one; the 404 has never had a hero at all, so there is nothing to switch
 *     back to. vance_page_hero_spotlight_active( 'e404' ) is always true.
 *   - NO CUSTOMIZER COPY. The eyebrow, headline and intro are literals in the
 *     config, the same call inc/legal-hero.php made for the five policy
 *     documents. Colours, buttons, the card and the photograph ARE editable,
 *     under Appearance -> Customize -> Page - Not Found (404).
 *   - THE START PAGE IS THE KNOWLEDGEBASE, not the homepage. It is the one door
 *     on this site that leads to every other -- collections, conditions, the
 *     free tools, the newest articles and a search field are all on it, and it
 *     resolves by slug so it survives being moved. The homepage is button 2.
 *
 * The band under the buttons carries four more destinations. A 404 whose only
 * exit is "Home" makes the visitor start their search again from nothing; the
 * whole point of this page is to be a junction rather than a dead end.
 *
 * The hero also renders its own <h1>, which this page needs and index.php never
 * gave it. If the hero ever stops rendering, the fallback below keeps that true
 * -- a 404 with no heading is the bug this file was written to fix.
 */

get_header();

$vance_404_hero = function_exists( 'vance_page_hero_spotlight_active' )
	&& function_exists( 'vance_render_page_hero_spotlight' )
	&& vance_page_hero_spotlight_active( 'e404' );
?>

<main id="main-content">

	<?php if ( $vance_404_hero ) : ?>

		<?php vance_render_page_hero_spotlight( 'e404' ); ?>

	<?php else : ?>

		<?php
		/*
		 * Fallback: the pre-2026-08-31 block, kept because this template runs
		 * on requests that reached WordPress but matched nothing, which is
		 * exactly the situation in which a half-deployed theme shows up. It is
		 * unreachable while inc/page-hero-spotlight.php is loaded.
		 */
		?>
		<div class="container" style="padding: 100px 20px; text-align: center;">
			<p style="font-size: 15px; font-weight: 700; letter-spacing: 1.2px; text-transform: uppercase; color: var(--primary-color); margin: 0 0 12px;"><?php esc_html_e( '404 error', 'vance-health-hub' ); ?></p>
			<?php /* A literal ’, not &rsquo;: esc_html_e() would escape the ampersand
			         and print the entity. */ ?>
			<h1 style="font-size: 40px; font-weight: 800; color: var(--secondary-color); margin: 0 0 16px;"><?php esc_html_e( 'We can’t find that page', 'vance-health-hub' ); ?></h1>
			<p style="font-size: 17px; color: var(--text-light); max-width: 560px; margin: 0 auto 36px; line-height: 1.7;">
				<?php esc_html_e( 'The address may have changed, or the page may have been retired. Everything the Hub publishes is reachable from the Knowledgebase.', 'vance-health-hub' ); ?>
			</p>
			<a href="<?php echo esc_url( home_url( '/knowledgebase/' ) ); ?>" class="btn btn-primary" style="display: inline-block; padding: 14px 32px; font-size: 15px; font-weight: 700; border-radius: var(--radius-control, 10px); text-decoration: none;"><?php esc_html_e( 'Start at the Knowledgebase', 'vance-health-hub' ); ?></a>
		</div>

	<?php endif; ?>

</main>

<?php get_footer(); ?>
