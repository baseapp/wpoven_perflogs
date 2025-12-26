<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://www.wpoven.com
 * @since      1.0.0
 *
 * @package    Wpoven_Performance_Logs
 * @subpackage Wpoven_Performance_Logs/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Wpoven_Performance_Logs
 * @subpackage Wpoven_Performance_Logs/admin
 * @author     WPOven <contact@wpoven.com>
 */
class Wpoven_Performance_Logs_Admin
{

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;
	private $start_time;
	private $queries = array();
	private $total_query_time = 0;
	private $start_memory;
	private $_wpoven_performance_logs;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct($plugin_name, $version)
	{

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->start_memory = memory_get_usage();


		if (!class_exists('ReduxFramework') && file_exists(require_once plugin_dir_path(dirname(__FILE__)) . 'includes/libraries/redux-framework/redux-core/framework.php')) {
			require_once plugin_dir_path(dirname(__FILE__)) . 'includes/libraries/redux-framework/redux-core/framework.php';
		}

		if (!class_exists('WPOven_Performance_Logs_List_Table')) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
			include_once WPOVEN_PERFORMANCE_PATH . 'includes/class-wpoven-performance-logs-list-table.php';
		}

		$this->action();
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles()
	{

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Wpoven_Performance_Logs_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Wpoven_Performance_Logs_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/wpoven-performance-logs-admin.css', array(), $this->version, 'all');
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts()
	{

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Wpoven_Performance_Logs_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Wpoven_Performance_Logs_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/wpoven-performance-logs-admin.js', array('jquery'), $this->version, false);
	}

	function action()
	{
		$this->start_time = microtime(true);
		add_action('init', array($this, 'update_current_date'));
		//add_action('shutdown', array($this, 'log_performance_data'));

		add_action('admin_footer', array($this, 'add_ajax_nonce_to_admin_footer'));
		add_action('wp_footer', array($this, 'add_ajax_nonce_to_admin_footer'));

		add_action('wp_ajax_wpoven_purge_all_logs', array($this, 'wpoven_purge_all_logs'));
		add_action('wp_ajax_nopriv_wpoven_purge_all_logs',  array($this, 'wpoven_purge_all_logs'));

		add_action('admin_menu', array($this, 'register_view_performance_log_page'));
		add_action('admin_enqueue_scripts', array($this, 'custom_wp_list_table_styles'));

		//add_filter('query', array($this, 'log_query'), 0);
		register_shutdown_function(array($this, 'log_performance_data'));
		add_action('admin_bar_menu', array($this, 'add_latest_info_to_admin_bar'), 1000);

		// Add AJAX action for updating admin bar
		add_action('wp_ajax_update_admin_bar_data', array($this, 'get_latest_admin_bar_data'));
		add_action('wp_ajax_nopriv_update_admin_bar_data', array($this, 'get_latest_admin_bar_data'));
	}

	function custom_wp_list_table_styles()
	{
		echo '<style>
			.wp-list-table .column-url {
				width: 800px; 
			}
			.wp-list-table .column-execution_time {
				width: 200px;
			}
			
			@media screen and (max-width: 768px) {

			.wp-list-table .column-url {
				width: 220px; 
			}

            .wp-list-table .column-execution_time,
			.wp-list-table .column-execution_time, 
			.wp-list-table .column-post_type,
			.wp-list-table .column-ip_address,
			.wp-list-table .column-total_queries,
			.wp-list-table .column-timestamp{
                display: none;
            }
			

            .wp-list-table .is-expanded .column-execution_time,
			.wp-list-table .is-expanded .column-execution_time, 
			.wp-list-table .is-expanded .column-post_type,
			.wp-list-table .is-expanded .column-ip_address,
			.wp-list-table .is-expanded .column-total_queries,
			.wp-list-table .is-expanded .column-timestamp{
                 display: table-cell;
            }

			.log-json-output {
				background: #f7f7f7;
				border: 1px solid #ddd;
				padding: 12px;
				border-radius: 6px;
				font-size: 13px;
				line-height: 1.4;
				width: 100%;
				max-height: 220px; /* define area height */
				overflow: auto; /* scroll if large JSON */
				white-space: pre-wrap; /* wrap long lines */
			}

		</style>';
	}

	function register_view_performance_log_page()
	{
		add_submenu_page(
			null,
			'View Performance Log',
			'View Performance Log',
			'manage_options',
			'view-performance-log',
			array($this, 'render_view_performance_log_page')
		);
	}

	function render_view_performance_log_page()
	{
		if (!current_user_can('manage_options')) {
			return;
		}

		$log_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
		if (!$log_id) {
			return;
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'performance_logs';
		$log = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $log_id), ARRAY_A);

		if (!$log) {
			wp_die(esc_html__('Log not found.', 'wpoven-performance-logs'));
		}

		$server_metrics = $this->get_server_metrics();

?>
		<div class="wrap">
			<header>
				<div>
					<strong>
						<h1>Performance Logs Details</h1>
					</strong><br>
				</div>
			</header>
			<div>
				<div class="wp-list-table">
					<div class="wp-list-row">
						<div class="wp-list-cell">
							<h4>URL</h4>
						</div>

						<div class="wp-list-cell" style="margin-bottom: 10px;">
							<span><?php echo esc_html($log['url']); ?></span>
							<p>Status : <?php echo esc_html($log['status_code']); ?></p>
							<p>Post Arguments :</p>
							<div style="max-height: 300px; overflow: auto; border: 1px solid #ccc; padding: 10px; background: #f7f7f7;">
								<pre style="white-space: pre-wrap; word-wrap: break-word;">
<?php
		$post_args = $log['post_arguments'];
		if (is_string($post_args) && json_decode($post_args, true)) {
			echo esc_html(json_encode(json_decode($post_args, true), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
		} elseif (is_array($post_args)) {
			echo esc_html(print_r($post_args, true));
		} else {
			echo esc_html("No POST arguments logged.");
		}
?>
        </pre>
							</div>
						</div>

					</div>
					<div class="wp-list-row">
						<div class="wp-list-cell">
							<h4>Execution Time</h4>
						</div>
						<div class="wp-list-cell">
							<p>PHP Execution Time : <?php echo esc_html(number_format($log['execution_time'], 2)) . 's'; ?></p>
							<p>Max Execution Time : <?php echo esc_html($server_metrics['max_execution_time']) . 's'; ?></p>
							<p>CPU Load : <?php echo esc_html(number_format($log['cpu_load'], 2)) ?></p>
						</div>
					</div>
					<div class="wp-list-row">
						<div class="wp-list-cell">
							<h4>Type</h4>
						</div>
						<div class="wp-list-cell"><?php echo esc_html($log['post_type']); ?></div>
					</div>
					<div class="wp-list-row">
						<div class="wp-list-cell">
							<h4>IP Address</h4>
						</div>
						<div class="wp-list-cell">
							<a href="https://ipinfo.io/<?php echo esc_html($log['ip_address']); ?>" target="_blank">
								<?php echo esc_html($log['ip_address']); ?>
							</a>
						</div>
					</div>
					<div class="wp-list-row">
						<div class="wp-list-cell">
							<h4>Memory Usage</h4>
						</div>
						<div class="wp-list-cell"><?php
													$peak_memory_usage_bytes = $log['peak_memory_usage'];
													$peak_memory_usage_mb = $peak_memory_usage_bytes / 1048576;
													?>
							<p>Memory Usage: <?php echo esc_html(number_format($log['memory_usage'] / 1048576, 2)); ?>M</p>
							<p>Peak Memory Usage : <?php echo esc_html($peak_memory_usage_mb); ?>M</p>
							<p>Memory Limit : <?php echo esc_html($server_metrics['memory_limit']); ?></p>
						</div>
					</div>
					<div class="wp-list-row">
						<div class="wp-list-cell">
							<h4>Date</h4>
						</div>
						<div class="wp-list-cell"><?php echo esc_html($log['timestamp']); ?></div>
					</div>
					<div class="wp-list-row">
						<div class="wp-list-cell">
							<h4>Total Queries</h4>
						</div>
						<div class="wp-list-cell"><?php echo esc_html($log['total_queries']); ?></div>
					</div>
					<div class="wp-list-row">
						<div class="wp-list-cell">
							<h4>Total Queries Time</h4>
						</div>
						<div class="wp-list-cell"><?php echo esc_html(number_format($log['total_query_time'], 2)) . 's'; ?></div>
					</div>
					<div class="wp-list-row">
						<div class="wp-list-cell" style="width: 200px;">
							<h4>All Queries</h4>
						</div>
						<div class="wp-list-cell">
							<div style="height: 300px;  overflow-y: scroll;  overflow-x: scroll; border: 1px solid #ccc;">

								<div class="wp-list-table">
									<div class="wp-list-row">
										<div class="wp-list-cell">
											<h4>Query</h4>
										</div>
										<div class="wp-list-cell">
											<h4>Time</h4>
										</div>
									</div>
									<?php
									$queries = json_decode($log['queries'], true);
									if (is_array($queries)) {
										foreach ($queries as $num => $query) {
											// Output the query number, query string, and time inside HTML structure
											echo '<div class="wp-list-row">
													<div class="wp-list-cell">' . esc_html($num + 1) . '. <code>' . esc_html($query['query']) . '</code></div>
													<div class="wp-list-cell">' . esc_html(number_format($query['time'], 4)) . 's</div>
												  </div><br>';
										}
									} else {
										echo '<div class="wp-list-row"><div class="wp-list-cell"><p>No queries found.</p></div>
										<div class="wp-list-cell"></div></div>';
									}
									?>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	<?php
	}

	/**
	 * Adds an AJAX nonce to the admin footer for security in AJAX requests.
	 */
	function add_ajax_nonce_to_admin_footer()
	{
	?>
		<script type="text/javascript">
			var ajax_nonce = '<?php echo esc_html(wp_create_nonce('wpoven_ajax_nonce')); ?>';
			var ajax_url = '<?php echo esc_html(admin_url('admin-ajax.php')); ?>';
			document.write('<div id="wpoven-ajax-nonce" style="display:none;">' + ajax_nonce + '</div>');
			document.write('<div id="wpoven-ajax-url" style="display:none;">' + ajax_url + '</div>');
		</script>
<?php
	}

	function update_current_date()
	{
		// Get the current date in 'Y-m-d' format
		$current_date = gmdate('Y-m-d');
		$stored_date = get_option('wpoven_log_current_date');

		// Check if the stored date exists and if it doesn't match the current date
		if ($stored_date !== $current_date) {
			update_option('wpoven_log_current_date', $current_date);
		}
	}

	/**
	 * Get server limits and resource usage
	 */
	private function get_server_metrics()
	{
		$metrics = array(
			'memory_limit' => ini_get('memory_limit'),
			'max_execution_time' => ini_get('max_execution_time'),
			'memory_usage' => memory_get_usage(true),
			'peak_memory_usage' => memory_get_peak_usage(true),
		);

		// Get CPU usage if possible
		if (function_exists('sys_getloadavg') && is_callable('sys_getloadavg')) {
			$load = sys_getloadavg();
			$metrics['cpu_load'] = $load[0]; // 1 minute average
		} else {
			$metrics['cpu_load'] = 'N/A';
		}

		return $metrics;
	}

	public function add_latest_info_to_admin_bar($wp_admin_bar)
	{
		$options = get_option(WPOVEN_PERFORMANCE_LOGS_SLUG);
		$enable_admin_page_logging = isset($options['log-retention'][1]) ? $options['log-retention'][1] : false;
		if ($enable_admin_page_logging) {
			echo '<style>
					#wp-admin-bar-latest-info > .ab-item {
						background: #2790c4 !important; /* Red background */
						color: #ffffff !important; /* White text */
					}
					#wp-admin-bar-latest-info:hover > .ab-item {
						background: #206c91 !important; /* Darker red on hover */
					}
				</style>';

			$wp_admin_bar->add_node(array(
				'id' => 'latest-info',
				'title' => 'Loading latest logs...',
				'href' => '#',
			));
		}
	}

	public function get_latest_admin_bar_data()
	{
		$return_array = array();

		global $wpdb;
		$latest_entry = $wpdb->get_row("SELECT * FROM wp_performance_logs ORDER BY timestamp DESC LIMIT 1");

		if ($latest_entry) {
			$display_text = sprintf(
				'%ss &nbsp;%sMB &nbsp;%ss &nbsp;%sQ',
				esc_html(number_format($latest_entry->execution_time, 2)),
				esc_html(number_format($latest_entry->memory_usage / 1048576, 2)),
				esc_html(number_format($latest_entry->total_query_time, 2)),
				esc_html($latest_entry->total_queries)
			);
			$return_array['url'] =  esc_url(admin_url("admin.php?page=view-performance-log&id={$latest_entry->id}"));;
			$return_array['display_text'] =  $display_text;
		}
		$return_array['status'] = 'ok';
		die(wp_json_encode($return_array));
	}

	/**
	 * Sanitize and prepare POST arguments for storage
	 */
	private function sanitize_post_arguments($post_data)
	{
		$sanitized = array();

		foreach ($post_data as $key => $value) {
			// Skip sensitive data
			$sensitive_keys = array('password', 'pwd', 'secret', 'token', 'credit_card', 'cc');
			$is_sensitive = false;

			foreach ($sensitive_keys as $sensitive_key) {
				if (stripos($key, $sensitive_key) !== false) {
					$is_sensitive = true;
					break;
				}
			}

			if ($is_sensitive) {
				$sanitized[$key] = '[REDACTED]';
			} else {
				if (is_array($value)) {
					$sanitized[$key] = $this->sanitize_post_arguments($value);
				} else {
					$sanitized[$key] = sanitize_text_field($value);
				}
			}
		}

		return wp_json_encode($sanitized);
	}

	/**
	 * Log the performance data (URL, execution time, post type, IP address).
	 */
	public function log_performance_data()
	{
		$options = get_option(WPOVEN_PERFORMANCE_LOGS_SLUG);
		global $wpdb;
		$queries = array();
		$queries_time = 0;

		$enable_admin_page_logging = isset($options['log-retention'][1]) ? $options['log-retention'][1] : false;

		$enable_database_query_logging = isset($options['query-retention'][1]) ? $options['query-retention'][1] : null;

		if (empty($enable_admin_page_logging)) {
			if (is_admin() && is_user_logged_in()) {
				return;
			}
		}


		$filter_request = isset($options['filter-request']) ? $options['filter-request'] : null;

		//Check if the current request is for admin-ajax.php
		if (!empty($filter_request)) {
			$array = preg_split('/[\n,]+/', $filter_request);
			$array = array_map('trim', $array);
			$array = array_filter($array);

			foreach ($array as $item) {

				if (isset($_SERVER['REQUEST_URI']) && strpos(wp_unslash($_SERVER['REQUEST_URI']), $item)) {
					return;
				}
			}
		}

		if (defined('SAVEQUERIES') && SAVEQUERIES && ! empty($wpdb->queries)) {

			foreach ($wpdb->queries as $q) {
				$queries[] = array(
					'query' => $q[0],
					'time'  => $q[1]
				);
				$queries_time += $q[1];
			}
		}


		// Get the current URL
		$url = esc_url($_SERVER['REQUEST_URI']);

		// Calculate execution time
		$execution_time = microtime(true) - $this->start_time;

		// Get post type if it's a single post or page
		$post_type = null;

		$post_type = $_SERVER['REQUEST_METHOD'];

		// // Get the user's IP address
		$ip_address = !empty($_SERVER['HTTP_CLIENT_IP']) ? $_SERVER['HTTP_CLIENT_IP'] : (!empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] :
			$_SERVER['REMOTE_ADDR']);


		// Get server metrics
		$server_metrics = $this->get_server_metrics();

		// Calculate memory usage
		$memory_usage = memory_get_usage();

		if ($enable_database_query_logging) {
			$database_queries = $queries;
		} else {
			$database_queries = null;
		}

		// Capture POST arguments
		$post_arguments = null;
		if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST)) {
			$post_arguments = $this->sanitize_post_arguments($_POST);
		}


		// Prepare log data
		$data = array(
			'url'               => home_url() . $url,
			'status_code'       => http_response_code(),
			'execution_time'    => $execution_time,
			'post_type'         => $post_type,
			'ip_address'        => $ip_address,
			'timestamp'         => current_time('mysql'),
			'total_queries'     => count($queries),
			'total_query_time'  => $queries_time,
			'queries'           => wp_json_encode($database_queries),
			'memory_limit'      => $server_metrics['memory_limit'],
			'memory_usage'      => $memory_usage,
			'peak_memory_usage' => $server_metrics['peak_memory_usage'],
			'cpu_load'          => $server_metrics['cpu_load'],
			'post_arguments'    => $post_arguments,
		);

		if (isset($options['query_slow_threshold']) && is_numeric($options['query_slow_threshold'])) {
			$threshold_seconds = floatval($options['query_slow_threshold']);
			if ($execution_time < $threshold_seconds) {
				return; // Skip logging if execution time is below the threshold
			}
		}

		// Store the log in the database
		$this->store_data_in_database($data);
	}

	/**
	 * Store the log data in the database.
	 */
	private function store_data_in_database($data)
	{
		global $wpdb;
		$table_name = $wpdb->prefix . 'performance_logs';
		$options = get_option(WPOVEN_PERFORMANCE_LOGS_SLUG);

		if (isset($options['delete-logs'])) {
			if ($options['delete-logs'] == 1) {
				$wpdb->query(
					$wpdb->prepare(
						"DELETE FROM $table_name WHERE timestamp < %s",
						gmdate('Y-m-d H:i:s', strtotime('-7 days'))
					)
				);
			}
		}

		// Insert log into database
		$wpdb->insert(
			$table_name,
			array(
				'url'               => $data['url'],
				'status_code'       => $data['status_code'],
				'execution_time'    => $data['execution_time'],
				'post_type'         => $data['post_type'],
				'ip_address'        => $data['ip_address'],
				'timestamp'         => $data['timestamp'],
				'total_queries'     => $data['total_queries'],
				'total_query_time'  => $data['total_query_time'],
				'queries'           => $data['queries'],
				'memory_usage'      => $data['memory_usage'],
				'peak_memory_usage' => $data['peak_memory_usage'],
				'cpu_load'          => $data['cpu_load'],
				'post_arguments'    => isset($data['post_arguments']) ? $data['post_arguments'] : null,
			),
			array(
				'%s', // URL
				'%s', // status_code
				'%f', // Execution time (float)
				'%s', // Post type
				'%s', // IP address
				'%s', // Timestamp
				'%d', // Total queries (integer)
				'%f', // Total query time (float)
				'%s', // Queries (JSON string)
				'%d', // Memory usage
				'%d', // Peak memory usage
				'%s',  // CPU load
				'%s'  // Post arguments
			)
		);
	}

	/**
	 * Create the database table for storing performance logs.
	 */
	public function create_database_table()
	{
		global $wpdb;
		$table_name = $wpdb->prefix . 'performance_logs';
		$charset_collate = $wpdb->get_charset_collate();


		$sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            url text NOT NULL,
			status_code text NOT NULL,
            execution_time float NOT NULL,
            post_type varchar(255) DEFAULT NULL,
            ip_address varchar(255) DEFAULT NULL,
            timestamp datetime DEFAULT NULL,
            total_queries int(11) DEFAULT 0,
            total_query_time float DEFAULT 0,
            queries longtext DEFAULT NULL,
			memory_usage bigint(20) DEFAULT NULL,
            peak_memory_usage bigint(20) DEFAULT NULL,
            cpu_load varchar(10) DEFAULT NULL,
			post_arguments longtext DEFAULT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
	}

	function wpoven_purge_all_logs()
	{
		//check_ajax_referer('ajax_nonce', 'nonce');

		global $wpdb;
		$return_array = array();

		$table_name = $wpdb->prefix . 'performance_logs'; // Use the correct table name with the WordPress prefix
		$deleted_rows = $wpdb->query("DELETE FROM {$table_name}");

		if ($deleted_rows !== false) {
			// If the deletion was successful
			$return_array['status'] = 'ok';
			$return_array['success_msg'] = __('All logs have been deleted successfully.', 'wpoven-performance-logs');
		} else {
			// If there was an error during deletion
			$return_array['status'] = 'error';
			$return_array['error_msg'] = __('Failed to delete logs.', 'wpoven-performance-logs');
		}

		die(wp_json_encode($return_array));
	}


	function wpoven_performance_logs_settings()
	{
		$fields = array();

		$log_retention = array(
			'id'       => 'log-retention',
			'type'     => 'checkbox',
			'title'    => esc_html__('Log Retention', 'wpoven-performance-logs'),
			'options'  => array(
				'1' => 'Enable admin page logging.'
			),

		);

		$query_retention = array(
			'id'       => 'query-retention',
			'type'     => 'checkbox',
			'title'    => esc_html__(' ', 'wpoven-performance-logs'),
			'options'  => array(
				'1' => 'Enable database queries logging.'
			),

		);

		$query_slow_threshold = array(
			'id'       => 'query_slow_threshold',
			'type'     => 'text',
			'title'    => esc_html__('Log only when request exceeds', 'wpoven-performance-logs'),
			'desc'     => 'Enter a value in <strong>second(s)</strong>.',
			'validate' => 'numeric',
			'placeholder' => 'Ex : 0.2 second',
		);



		$delete_logs = array(
			'id'       => 'delete-logs',
			'type'     => 'select',
			'title'    => ' ',
			'options'  => array(
				'0' => 'Unlimited',
				'1' => '7 days only',
			),
			'default'  => '1',
			'desc'     => esc_html__('Choose log retention period: unlimited or 7 days (default).', 'wpoven-performance-logs'),
		);

		$purge_all_logs = array(
			'id'       => 'purge-all-logs',
			'type'     => 'button_set',
			'title'    => ' ',
			'options' => array(
				'1' => 'Purge All Logs',
			),
			'desc'     => esc_html__('Click to instantly purge all logs from the database.', 'wpoven-performance-logs'),
		);

		$filter_request = array(
			'id'       => 'filter-request',
			'type'     => 'textarea',
			'title'    => esc_html__('Filter Request', 'wpoven-performance-logs'),
			'desc'     => esc_html__('Input URLs to filter specific performance logs for review.', 'wpoven-performance-logs'),
		);

		$fields[] = $log_retention;
		$fields[] = $query_retention;
		$fields[] = $query_slow_threshold;
		$fields[] = $delete_logs;
		$fields[] = $purge_all_logs;
		$fields[] = $filter_request;

		return $fields;
	}

	function wpoven_performance_logs_table()
	{
		$fields = array();
		$table = new WPOven_Performance_Logs_List_Table();
		$table_output = ' <form method="post">
            ?>
            $table->prepare_items();
            $table->search_box("search", "search_id");
            $table->display();
            <?php
        </form>';

		$log_table = array(
			'id'         => 'log-table',
			'type'       => 'raw',
			'full_width' => false,
			'content'    =>   wp_kses_post($table_output)
		);

		$fields[] = $log_table;

		return $fields;
	}

	/**
	 * Create performance logs pages.
	 */
	function performance_logs()
	{
		echo '<div class="wrap"><h1><strong>Performance Logs</strong></h1>';
		echo '<form method="post">';

		$table = new WPOven_Performance_Logs_List_Table();
		$table->prepare_items();
		$table->search_box('search', 'search_id');
		$table->display();

		echo '</div></form>';
	}

	function setup_gui()
	{
		if (!class_exists('Redux')) {
			return;
		}

		$options = get_option(WPOVEN_PERFORMANCE_LOGS_SLUG);
		$opt_name = WPOVEN_PERFORMANCE_LOGS_SLUG;

		Redux::disable_demo();

		$args = array(
			'opt_name'                  => $opt_name,
			'display_name'              => 'WPOven Performance Logs',
			'display_version'           => ' ',
			//'menu_type'                 => 'menu',
			'allow_sub_menu'            => true,
			//	'menu_title'                => esc_html__('WPOven Plugins', 'wpoven-performance-logs'),
			'page_title'                => esc_html__('WPOven Performance Logs', 'wpoven-performance-logs'),
			'disable_google_fonts_link' => false,
			'admin_bar'                 => false,
			'admin_bar_icon'            => 'dashicons-portfolio',
			'admin_bar_priority'        => 90,
			'global_variable'           => $opt_name,
			'dev_mode'                  => false,
			'customizer'                => false,
			'open_expanded'             => false,
			'disable_save_warn'         => false,
			'page_priority'             => 90,
			'page_parent'               => 'themes.php',
			'page_permissions'          => 'manage_options',
			'menu_icon'                 => plugin_dir_url(__FILE__) . '/image/logo.png',
			'last_tab'                  => '',
			'page_icon'                 => 'icon-themes',
			'page_slug'                 => $opt_name,
			'save_defaults'             => false,
			'default_show'              => false,
			'default_mark'              => '',
			'show_import_export'        => false,
			'transient_time'            => 60 * MINUTE_IN_SECONDS,
			'output'                    => false,
			'output_tag'                => false,
			// 'footer_credit'             => 'Please rate WPOven Performance Logs ★★★★★ on WordPress.org to support us. Thank you!',
			'footer_credit'             => ' ',
			'use_cdn'                   => false,
			'admin_theme'               => 'wp',
			'flyout_submenus'           => true,
			'font_display'              => 'swap',
			'hide_reset'                => true,
			'database'                  => '',
			'network_admin'           => '',
			'search'                    => false,
			'hide_expand'            => true,
		);

		Redux::set_args($opt_name, $args);

		Redux::set_section(
			$opt_name,
			array(
				'title'      => esc_html__('Settings', 'wpoven-performance-logs'),
				'id'         => 'performance-logs-settings',
				'subsection' => false,
				'heading'    => 'Performance Logs Settings',
				'fields' => $this->wpoven_performance_logs_settings(),
			)
		);

		Redux::set_section(
			$opt_name,
			array(
				'title'      => '<a href="admin.php?page=view-performance-logs" class="view-performance-logs">
                            <span class="dashicons dashicons-media-text" style="margin-right:6px;"></span>
                           <strong> <span class="group_title">View Logs</span></strong>
                        </a>',
				'id'         => 'performance-logs',
				'class'      => 'performance-logs',
				'parent'     => 'performance-logs-settings',
				'subsection' => false,
				'icon'       => '', //el el-list

			)
		);
	}

	/**
	 * Add a admin menu.
	 */
	function wpoven_performance_logs_menu()
	{
		add_menu_page('WPOven Plugins', 'WPOven Plugins', '', 'wpoven', 'manage_options', plugin_dir_url(__FILE__) . '/image/logo.png');
		add_submenu_page('wpoven', 'Performance Logs', 'Performance Logs', 'manage_options', 'admin.php?page=wpoven-performance-logs&tab=1');
		add_submenu_page('admin.php?page=wpoven-performance-logs&tab=1', 'View Logs', 'View Logs', 'manage_options', 'view-performance-logs', array($this, 'performance_logs'));
	}

	/**
	 * Hook to add the admin menu.
	 */
	public function admin_main(Wpoven_Performance_Logs $wpoven_performance_logs)
	{
		$this->_wpoven_performance_logs = $wpoven_performance_logs;
		add_action('admin_menu', array($this, 'wpoven_performance_logs_menu'));
		$this->setup_gui();
	}
}
