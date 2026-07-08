<?php
/**
 * Plugin Name:       VHH Annotations
 * Description:       Highlight-and-comment annotations for Vance Medical Hub articles — text highlights, image-region comments, email review links, and a Claude Code export/to-do workflow.
 * Version:           0.1.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Vance Medical
 * License:           GPL-2.0-or-later
 * Text Domain:       vhh-annotations
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'VHH_ANN_VERSION', '0.1.0' );
define( 'VHH_ANN_FILE', __FILE__ );
define( 'VHH_ANN_DIR', plugin_dir_path( __FILE__ ) );
define( 'VHH_ANN_URL', plugin_dir_url( __FILE__ ) );

require_once VHH_ANN_DIR . 'includes/class-vhh-capabilities.php';
require_once VHH_ANN_DIR . 'includes/class-vhh-selector.php';
require_once VHH_ANN_DIR . 'includes/class-vhh-annotation-store.php';
require_once VHH_ANN_DIR . 'includes/class-vhh-rate-limiter.php';
require_once VHH_ANN_DIR . 'includes/class-vhh-rest-annotations.php';
require_once VHH_ANN_DIR . 'includes/class-vhh-frontend.php';
require_once VHH_ANN_DIR . 'includes/class-vhh-customizer.php';
require_once VHH_ANN_DIR . 'includes/class-vhh-todos-cpt.php';
require_once VHH_ANN_DIR . 'includes/class-vhh-apply.php';
require_once VHH_ANN_DIR . 'includes/class-vhh-rest-todos.php';
require_once VHH_ANN_DIR . 'includes/class-vhh-rest-review.php';
require_once VHH_ANN_DIR . 'includes/class-vhh-review-emails.php';
require_once VHH_ANN_DIR . 'includes/class-vhh-privacy.php';
require_once VHH_ANN_DIR . 'includes/class-vhh-plugin.php';

register_activation_hook( __FILE__, array( 'VHH_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'VHH_Plugin', 'deactivate' ) );

add_action( 'plugins_loaded', array( 'VHH_Plugin', 'instance' ) );
