<?php
/**
 * Plugin Name:       INC Stats Tracker
 * Plugin URI:        https://example.com/inc-stats-tracker
 * Description:       Tracks TYFCB, referrals, connects, and member activity for INC reporting.
 * Version:           0.2.13
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            Your Name
 * Author URI:        https://example.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       inc-stats-tracker
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants.
define( 'IST_VERSION', '0.2.13' );
define( 'IST_PLUGIN_FILE', __FILE__ );
define( 'IST_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'IST_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'IST_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Bootstrap — load the infrastructure classes first.
require_once IST_PLUGIN_DIR . 'includes/class-ist-activator.php';
require_once IST_PLUGIN_DIR . 'includes/class-ist-deactivator.php';
require_once IST_PLUGIN_DIR . 'includes/class-ist-loader.php';
require_once IST_PLUGIN_DIR . 'includes/ist-functions.php';
require_once IST_PLUGIN_DIR . 'includes/ist-notifications.php';

// Activation / deactivation hooks.
register_activation_hook( __FILE__, array( 'IST_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'IST_Deactivator', 'deactivate' ) );

/**
 * Bootstraps the plugin.
 *
 * IST_Loader::load_dependencies() requires all plugin class files.
 * ist-hooks.php is loaded afterward so every class it instantiates is
 * guaranteed to exist by the time the file runs.
 */
function ist_run(): void {
	$loader = new IST_Loader();
	$loader->run();
}
ist_run();

// Hooks are registered after ist_run() so all class files are already loaded.
require_once IST_PLUGIN_DIR . 'includes/ist-hooks.php';
