<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://www.wpoven.com
 * @since             1.0.0
 * @package           Wpoven_Performance_Logs
 *
 * @wordpress-plugin
 * Plugin Name:       WPOven Performance Logs
 * Plugin URI:        https://github.com/shyambaseapp/wpoven-performance-logs
 * Description:       WPOven Performance Logs monitoring page generation, memory, database queries, and API calls
 * Version:           1.0.1
 * Author:            WPOven
 * Author URI:        https://www.wpoven.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wpoven-performance-logs
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (! defined('WPINC')) {
	die;
}

if (! defined('SAVEQUERIES')) {
	define('SAVEQUERIES', true);
}


if (SAVEQUERIES && property_exists($GLOBALS['wpdb'], 'save_queries')) {
	$GLOBALS['wpdb']->save_queries = true;
}



/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define('WPOVEN_PERFORMANCE_LOGS_VERSION', '1.0.1');
if (!defined('WPOVEN_PERFORMANCE_LOGS_SLUG'))
	define('WPOVEN_PERFORMANCE_LOGS_SLUG', 'wpoven-performance-logs');

define('WPOVEN_PERFORMANCE_LOGS', 'WPOven Performance Logs');
define('WPOVEN_PERFORMANCE_LOGS_ROOT_PL', __FILE__);
define('WPOVEN_PERFORMANCE_LOGS_ROOT_URL', plugins_url('', WPOVEN_PERFORMANCE_LOGS_ROOT_PL));
define('WPOVEN_PERFORMANCE_LOGS_ROOT_DIR', dirname(WPOVEN_PERFORMANCE_LOGS_ROOT_PL));
define('WPOVEN_PERFORMANCE_LOGS_PLUGIN_DIR', plugin_dir_path(__DIR__));
define('WPOVEN_PERFORMANCE_LOGS_PLUGIN_BASE', plugin_basename(WPOVEN_PERFORMANCE_LOGS_ROOT_PL));
define('WPOVEN_PERFORMANCE_LOGS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WPOVEN_PERFORMANCE_PATH', realpath(plugin_dir_path(WPOVEN_PERFORMANCE_LOGS_ROOT_PL)) . '/');


require_once plugin_dir_path(__FILE__) . 'includes/libraries/plugin-update-checker/plugin-update-checker.php';

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker(
	'https://github.com/baseapp/wpoven_perflogs/',
	__FILE__,
	'wpoven-performance-logs'
);
$myUpdateChecker->getVcsApi()->enableReleaseAssets();


/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-wpoven-performance-logs-activator.php
 */
function wpoven_performance_logs_activate()
{
	require_once plugin_dir_path(__FILE__) . 'includes/class-wpoven-performance-logs-activator.php';
	Wpoven_Performance_Logs_Activator::activate();

	require_once plugin_dir_path(__FILE__) . 'admin/class-wpoven-performance-logs-admin.php';
	$wpoven_performance_logs = new Wpoven_Performance_Logs_Admin('wpoven-performance-logs', '1.0.1');
	$wpoven_performance_logs->create_database_table();
	update_option('wpoven_log_current_date', gmdate('Y-m-d'));
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-wpoven-performance-logs-deactivator.php
 */
function wpoven_performance_logs_deactivate()
{
	require_once plugin_dir_path(__FILE__) . 'includes/class-wpoven-performance-logs-deactivator.php';
	Wpoven_Performance_Logs_Deactivator::deactivate();

	global $wpdb;
	$table_name = $wpdb->prefix . 'performance_logs';
	$wpdb->query("DROP TABLE IF EXISTS $table_name");
}

register_activation_hook(__FILE__, 'wpoven_performance_logs_activate');
register_deactivation_hook(__FILE__, 'wpoven_performance_logs_deactivate');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-wpoven-performance-logs.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */

add_action('plugins_loaded', function () {
	$plugin = new Wpoven_Performance_Logs();
	$plugin->run();
});

function wpoven_performance_logs_plugin_settings_link($links)
{
	$settings_link = '<a href="' . admin_url('admin.php?page=' . WPOVEN_PERFORMANCE_LOGS_SLUG) . '">Settings</a>';

	array_push($links, $settings_link);
	return $links;
}
add_filter('plugin_action_links_' . WPOVEN_PERFORMANCE_LOGS_PLUGIN_BASE, 'wpoven_performance_logs_plugin_settings_link');
