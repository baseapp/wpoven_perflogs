<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://www.wpoven.com
 * @since      1.0.0
 *
 * @package    Wpoven_Performance_Logs
 * @subpackage Wpoven_Performance_Logs/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Wpoven_Performance_Logs
 * @subpackage Wpoven_Performance_Logs/includes
 * @author     WPOven <contact@wpoven.com>
 */
class Wpoven_Performance_Logs_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'wpoven-performance-logs',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
