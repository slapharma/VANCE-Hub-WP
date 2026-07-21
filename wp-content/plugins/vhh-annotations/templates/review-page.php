<?php
/**
 * Standalone reviewer page (email review flow).
 *
 * Served by VHH_Review_Emails::maybe_review_template() when a valid
 * ?vhh_review=TOKEN is present. GET renders only — every mutation goes
 * through the token-authed vhh/v1 REST routes via fetch.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$vhh_payload = $GLOBALS['vhh_review_payload'];
$vhh_token   = $GLOBALS['vhh_review_token'];
$vhh_post    = get_queried_object();
$vhh_approve = ( 'approve' === $vhh_payload['a'] );

$vhh_asset = function ( $rel ) {
	return esc_url( VHH_ANN_URL . $rel . '?ver=' . ( @filemtime( VHH_ANN_DIR . $rel ) ?: VHH_ANN_VERSION ) );
};

// Shared config builder (single source of truth with the logged-in view),
// overridden for the anonymous token reviewer.
$vhh_cfg = VHH_Frontend::client_config(
	$vhh_post,
	array(
		'nonce'       => '', // api.js omits the header when empty
		'reviewToken' => $vhh_token,
		'settings'    => array( 'sidebarPosition' => 'right' ),
		'user'        => array(
			'id'          => -1,
			'name'        => (string) $vhh_payload['n'],
			'canModerate' => false,
		),
	)
);
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex, nofollow">
	<title><?php echo esc_html( sprintf( __( 'Review: %s', 'vhh-annotations' ), $vhh_post->post_title ) ); ?></title>
	<link rel="stylesheet" href="<?php echo $vhh_asset( 'assets/css/annotations.css' ); ?>">
	<style>
		:root { --primary-color: #008080; }
		body {
			margin: 0;
			font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
			color: #1e293b;
			background: #f8fafc;
			line-height: 1.7;
		}
		.vhh-review-bar {
			position: sticky;
			top: 0;
			z-index: 100;
			background: var(--primary-color);
			color: #fff;
			padding: 12px 20px;
			display: flex;
			align-items: center;
			gap: 14px;
			flex-wrap: wrap;
			font-size: 14px;
		}
		.vhh-review-bar strong { font-size: 15px; }
		.vhh-review-main {
			max-width: 760px;
			margin: 0 auto;
			padding: 32px 20px 120px;
		}
		.vhh-review-main h1 { line-height: 1.3; }
		.vhh-review-content img { max-width: 100%; height: auto; }
		.vhh-approve-box {
			max-width: 560px;
			margin: 60px auto;
			background: #fff;
			border: 1px solid #e2e8f0;
			border-radius: 12px;
			padding: 28px;
			text-align: center;
		}
		.vhh-approve-box .vhh-btn--primary { font-size: 15px; padding: 10px 22px; }
	</style>
</head>
<body class="vhh-review-body<?php echo 'hide' === VHH_Plugin::get( 'resolved_style' ) ? ' vhh-resolved-hide' : ''; ?>">
	<div class="vhh-review-bar vhh-ui">
		<strong><?php echo esc_html( get_bloginfo( 'name' ) ); ?></strong>
		<span>
			<?php
			echo esc_html(
				$vhh_approve
					? sprintf( __( 'Approval requested from %s', 'vhh-annotations' ), $vhh_payload['n'] )
					: sprintf( __( 'Reviewing as %s — select text or drag on images to comment', 'vhh-annotations' ), $vhh_payload['n'] )
			);
			?>
		</span>
	</div>

	<main class="vhh-review-main">
		<?php if ( $vhh_approve ) : ?>
			<div class="vhh-approve-box vhh-ui">
				<h1><?php echo esc_html( $vhh_post->post_title ); ?></h1>
				<p><?php esc_html_e( 'Clicking approve records your sign-off on this article. Nothing happens until you press the button.', 'vhh-annotations' ); ?></p>
				<p><a href="<?php echo esc_url( get_permalink( $vhh_post ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Read the article first ↗', 'vhh-annotations' ); ?></a></p>
				<button type="button" class="vhh-btn vhh-btn--primary" id="vhh-approve-btn"><?php esc_html_e( '✓ Approve this article', 'vhh-annotations' ); ?></button>
				<p id="vhh-approve-result" style="display:none; color:#0f766e; font-weight:700;"><?php esc_html_e( 'Approval recorded — thank you!', 'vhh-annotations' ); ?></p>
			</div>
		<?php else : ?>
			<article>
				<h1><?php echo esc_html( $vhh_post->post_title ); ?></h1>
				<div class="vhh-review-content" data-vhh-annotatable="1">
					<?php echo apply_filters( 'the_content', $vhh_post->post_content ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</div>
			</article>
		<?php endif; ?>
	</main>

	<script>window.VHH_CFG = <?php echo wp_json_encode( $vhh_cfg ); ?>;</script>
	<script src="<?php echo $vhh_asset( 'assets/js/api.js' ); ?>"></script>
	<?php if ( $vhh_approve ) : ?>
	<script>
		document.getElementById( 'vhh-approve-btn' ).addEventListener( 'click', function () {
			var btn = this;
			btn.disabled = true;
			window.VHH.api.approve().then( function () {
				btn.style.display = 'none';
				document.getElementById( 'vhh-approve-result' ).style.display = 'block';
			} ).catch( function ( err ) {
				btn.disabled = false;
				alert( err.message || 'Could not record approval.' );
			} );
		} );
	</script>
	<?php else : ?>
	<script src="<?php echo $vhh_asset( 'assets/js/anchoring.js' ); ?>"></script>
	<script src="<?php echo $vhh_asset( 'assets/js/annotator.js' ); ?>"></script>
	<script src="<?php echo $vhh_asset( 'assets/js/sidebar.js' ); ?>"></script>
	<?php if ( VHH_Plugin::get( 'image_annotation' ) ) : ?>
	<script src="<?php echo $vhh_asset( 'assets/js/image-annotator.js' ); ?>"></script>
	<?php endif; ?>
	<?php if ( VHH_Plugin::get( 'insertion_annotation' ) ) : ?>
	<script src="<?php echo $vhh_asset( 'assets/js/insertion-annotator.js' ); ?>"></script>
	<?php endif; ?>
	<script>
		// Reviewers start in commenting mode with the panel open.
		window.addEventListener( 'load', function () {
			var fab = document.querySelector( '.vhh-fab' );
			if ( fab ) { fab.click(); }
		} );
	</script>
	<?php endif; ?>
</body>
</html>
