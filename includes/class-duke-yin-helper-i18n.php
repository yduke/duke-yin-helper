<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://www.dukeyin.com
 * @since      1.0.0
 *
 * @package    Duke_Yin_Helper
 * @subpackage Duke_Yin_Helper/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Duke_Yin_Helper
 * @subpackage Duke_Yin_Helper/includes
 * @author     Duke Yin <duke@dukeyin.com>
 */
class Duke_Yin_Helper_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'duke-yin-helper',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
