<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://www.georgenicolaou.me
 * @since      1.0.0
 *
 * @package    Gn_In_Store_Coupons
 * @subpackage Gn_In_Store_Coupons/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Gn_In_Store_Coupons
 * @subpackage Gn_In_Store_Coupons/includes
 * @author     George Nicolaou <orionas.elite@gmail.com>
 */
class Gn_In_Store_Coupons_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'gn-in-store-coupons',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
