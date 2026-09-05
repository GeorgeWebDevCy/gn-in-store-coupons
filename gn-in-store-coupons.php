<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://www.georgenicolaou.me
 * @since             1.0.0
 * @package           Gn_In_Store_Coupons
 *
 * @wordpress-plugin
 * Plugin Name:       GN In-Store Coupons
 * Plugin URI:        https://www.georgenicolaou.me/plugins/gn-in-store-coupons
 * Description:       This is a plugin that check if a user is already on a Mail Mint list or is a newly register WooCommerce customer and if so checks if they have received a coupon that has Store Branding and a unique code. The Discount Rate can be defined on a stand-alone settings screen in admin
 * Version:           1.4.0
 * Update URI:        https://github.com/GeorgeWebDevCy/gn-in-store-coupons
 * Requires PHP:      7.4
 * Requires at least: 6.5
 * Requires Plugins:  woocommerce, mail-mint
 * Author:            George Nicolaou
 * Author URI:        https://www.georgenicolaou.me/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       gn-in-store-coupons
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( isset( $_GET['gn_store_coupon'] ) && ! defined( 'DONOTCACHEPAGE' ) ) {
	define( 'DONOTCACHEPAGE', true );
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'GN_IN_STORE_COUPONS_VERSION', '1.4.0' );

/**
 * Register GitHub updates in every WordPress context, including cron and WP-CLI.
 */
function gn_in_store_coupons_init_update_checker() {

	require_once plugin_dir_path( __FILE__ ) . 'includes/plugin-update-checker/plugin-update-checker.php';

	$update_checker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
		'https://github.com/GeorgeWebDevCy/gn-in-store-coupons/',
		__FILE__,
		'gn-in-store-coupons'
	);
	$update_checker->setBranch( 'main' );

}
add_action( 'plugins_loaded', 'gn_in_store_coupons_init_update_checker' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-gn-in-store-coupons-activator.php
 */
function activate_gn_in_store_coupons() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-gn-in-store-coupons-activator.php';
	Gn_In_Store_Coupons_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-gn-in-store-coupons-deactivator.php
 */
function deactivate_gn_in_store_coupons() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-gn-in-store-coupons-deactivator.php';
	Gn_In_Store_Coupons_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_gn_in_store_coupons' );
register_deactivation_hook( __FILE__, 'deactivate_gn_in_store_coupons' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-gn-in-store-coupons.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_gn_in_store_coupons() {

	$plugin = new Gn_In_Store_Coupons();
	$plugin->run();

}
run_gn_in_store_coupons();
