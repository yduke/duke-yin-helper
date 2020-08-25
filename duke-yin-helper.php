<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://www.dukeyin.com
 * @since             1.0.0
 * @package           Duke_Yin_Helper
 *
 * @wordpress-plugin
 * Plugin Name:       Duke Yin Helper
 * Plugin URI:        https://www.dukeyin.com
 * Description:       For supporting the wordpress theme dukeyin, add more post types and shortcodes.
 * Version:           1.0.5
 * Author:            Duke Yin
 * Author URI:        https://www.dukeyin.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       duke-yin-helper
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'DUKE_YIN_HELPER_VERSION', '1.0.5' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-duke-yin-helper-activator.php
 */
function activate_duke_yin_helper() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-duke-yin-helper-activator.php';
	Duke_Yin_Helper_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-duke-yin-helper-deactivator.php
 */
function deactivate_duke_yin_helper() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-duke-yin-helper-deactivator.php';
	Duke_Yin_Helper_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_duke_yin_helper' );
register_deactivation_hook( __FILE__, 'deactivate_duke_yin_helper' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-duke-yin-helper.php';
require plugin_dir_path( __FILE__ ) . 'includes/post-type.php';
require plugin_dir_path( __FILE__ ) . 'includes/helpers.php';

/**
* updater
*/
require plugin_dir_path( __FILE__ ) . 'includes//plugin-update-checker/plugin-update-checker.php';
$MyUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
	'https://update.dukeyin.com/?action=get_metadata&slug=duke-yin-helper', //Metadata URL.
	__FILE__, //Full path to the main plugin file.
	'duke-yin-helper' //Plugin slug. Usually it's the same as the name of the directory.
);


/**
 * Short codes only work with DukeYin theme.
 */
if( function_exists('amts_checkMobile') AND amts_checkMobile()['amts_mobile_browser'] == 'Now' ){}else{
	require plugin_dir_path( __FILE__ ) . 'includes/short-code.php';
}

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_duke_yin_helper() {

	$plugin = new Duke_Yin_Helper();
	$plugin->run();

}
run_duke_yin_helper();
