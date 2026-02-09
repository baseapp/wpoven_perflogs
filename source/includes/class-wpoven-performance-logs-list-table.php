<?php

if (! defined('ABSPATH')) {
    exit;
}
//namespace WPOVEN\LIST\TABLE;

//use WP_List_Table;

class WPOven_Performance_Logs_List_Table extends WP_List_Table
{
    private $table_data;

    // Define table columns
    function get_columns()
    {
        $columns = array(
            'cb'                 => '<input type="checkbox" />',
            'url'                => __('URL', 'wpoven-performance-logs'),
            'execution_time'     => __('Total Execution Time', 'wpoven-performance-logs'),
            'post_type'          => __('Type', 'wpoven-performance-logs'),
            'ip_address'         => __('IP Address', 'wpoven-performance-logs'),
            //'total_queries'      => __('Total Queries', 'wpoven-performance-logs'),
            //'total_query_time'   => __('Total Query Time', 'wpoven-performance-logs'),
            //'peak_memory_usage'  => __('Peak Memory Usage', 'wpoven-performance-logs'),
            //'max_execution_time' => __('Max Execution Time', 'wpoven-performance-logs'),
            'timestamp'          => __('Timestamp', 'wpoven-performance-logs'),
            'action'             => ''
        );
        return $columns;
    }

    // Output the content of the "Actions" column
    protected function column_action($item)
    {
        $view_url = add_query_arg(
            array(
                'page' => 'view-performance-log',
                'id' => $item['id']
            ),
            admin_url('admin.php')
        );

        $view = sprintf(
            '<a href="%s" title="View Log" class="button" target="_blank">
                <svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                    <path d="M8 10C9.10457 10 10 9.10457 10 8C10 6.89543 9.10457 6 8 6C6.89543 6 6 6.89543 6 8C6 9.10457 6.89543 10 8 10Z" fill="#50575E" fill-opacity="0.8"/>
                    <path d="M15.47 7.83C14.882 6.30882 13.861 4.99331 12.5334 4.04604C11.2058 3.09878 9.62977 2.56129 8.00003 2.5C6.37029 2.56129 4.79423 3.09878 3.46663 4.04604C2.13904 4.99331 1.11811 6.30882 0.530031 7.83C0.490315 7.93985 0.490315 8.06015 0.530031 8.17C1.11811 9.69118 2.13904 11.0067 3.46663 11.954C4.79423 12.9012 6.37029 13.4387 8.00003 13.5C9.62977 13.4387 11.2058 12.9012 12.5334 11.954C13.861 11.0067 14.882 9.69118 15.47 8.17C15.5098 8.06015 15.5098 7.93985 15.47 7.83ZM8.00003 11.25C7.35724 11.25 6.72889 11.0594 6.19443 10.7023C5.65997 10.3452 5.24341 9.83758 4.99742 9.24372C4.75144 8.64986 4.68708 7.99639 4.81248 7.36596C4.93788 6.73552 5.24741 6.15642 5.70193 5.7019C6.15646 5.24738 6.73555 4.93785 7.36599 4.81245C7.99643 4.68705 8.64989 4.75141 9.24375 4.99739C9.83761 5.24338 10.3452 5.65994 10.7023 6.1944C11.0594 6.72886 11.25 7.35721 11.25 8C11.2487 8.86155 10.9059 9.68743 10.2967 10.2966C9.68746 10.9058 8.86158 11.2487 8.00003 11.25Z" fill="currentColor"/>
                </svg>
            </a>&nbsp;&nbsp;',
            esc_url($view_url)
        );

        $delete = sprintf(
            '<button type="submit" title="Delete Log" class="button" value="%s" id="delete" name="delete" style="color:red;">
            <svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                <path d="M4 4.66683H3.33333V13.3335C3.33333 13.6871 3.47381 14.0263 3.72386 14.2763C3.97391 14.5264 4.31304 14.6668 4.66667 14.6668H11.3333C11.687 14.6668 12.0261 14.5264 12.2761 14.2763C12.5262 14.0263 12.6667 13.6871 12.6667 13.3335V4.66683H4ZM6.66667 12.6668H5.33333V6.66683H6.66667V12.6668ZM10.6667 12.6668H9.33333V6.66683H10.6667V12.6668ZM11.0787 2.66683L10 1.3335H6L4.92133 2.66683H2V4.00016H14V2.66683H11.0787Z" fill="currentColor"/>
            </svg>
        </button>',
            $item['id']
        );
        //    $modal = $this->data_modal($item);
        $actions = array(
            '<div class="alignright">' . $view . $delete . '</div>'
        );

        return $this->row_actions($actions, true);
    }

    // Bind table with columns, data and all
    function prepare_items()
    {
        global $wpdb;
        $table_name = esc_sql($wpdb->prefix . 'performance_logs');

        $this->table_data = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table_name}"), ARRAY_A);

        if (isset($_POST['s']) && !empty($_POST['s'])) {
            $search_term = sanitize_text_field($_POST['s']);
            $search_columns = array('url', 'execution_time', 'post_type', 'ip_address', 'total_queries', 'total_query_time', 'peak_memory_usage', 'timestamp');

            // Escape the search term and create wildcards for the LIKE condition
            $search_wildcards = '%' . $wpdb->esc_like($search_term) . '%';

            $conditions = [];
            $args = []; // Prepare arguments for the placeholders

            foreach ($search_columns as $column) {
                // Prepare each condition with the appropriate placeholder
                $conditions[] = "$column LIKE %s";
                $args[] = $search_wildcards; // Append the escaped search term for each column
            }
            // Combine conditions with "OR"
            $where_clause = implode(' OR ', $conditions);
            $this->table_data = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table_name} WHERE $where_clause", ...$args), ARRAY_A);
        }

        if (isset($_POST['action']) == 'delete_all' || isset($_POST['delete'])) {
            if (isset($_POST['element']) && $_POST['action'] == 'delete_all') {
                $selectedLogIds = array_map('absint', $_POST['element']);
                foreach ($selectedLogIds as $logId) {
                    $wpdb->delete($table_name, array('id' => $logId), array('%d'));
                }
                echo '<div class="updated notice"><p>' . count($selectedLogIds) . '&nbsp;Rows deleted successfully!</p></div>';
            }
            if (isset($_POST['delete'])) {
                $id =  $_POST['delete'];
                $wpdb->delete($table_name, array('id' => $id), array('%d'));
                echo '<div class="updated notice"><p>1&nbsp;Rows deleted successfully!</p></div>';
            }
        }

        $columns = $this->get_columns();
        $subsubsub = $this->views();
        $hidden = (is_array(get_user_meta(get_current_user_id(), 'aaa', true))) ? get_user_meta(get_current_user_id(), 'dff', true) : array();
        $sortable = $this->get_sortable_columns();
        $primary  = 'id';
        $this->_column_headers = array($columns, $hidden, $sortable, $primary);

        usort($this->table_data, array(&$this, 'usort_reorder'));

        /* pagination */
        $per_page = $this->get_items_per_page('elements_per_page', 15);
        $current_page = $this->get_pagenum();
        $total_items = count($this->table_data);

        $this->table_data = array_slice($this->table_data, (($current_page - 1) * $per_page), $per_page);

        $this->set_pagination_args(array(
            'total_items' => $total_items, // total number of items
            'per_page'    => $per_page, // items to show on a page
            'total_pages' => ceil($total_items / $per_page) // use ceil to round up
        ));

        $this->items = $this->table_data;
    }

    //Get column default
    function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'id':
                return $item['id'];
            case 'url':
                return '<a href="' . $item["url"] . '" target="_blank">' . $item["url"] . '</a>';
            case 'execution_time':
                $total_execution_time = $item['execution_time'] + $item['total_query_time'];
                return  number_format($total_execution_time, 2) . 's';
            case 'post_type':
                return $item['post_type'];
            case 'ip_address':
                return '<a href="https://ipinfo.io/' . $item["ip_address"] . '" target="_blank">' . $item["ip_address"] . '</a>';
            case 'total_queries':
                return $item['total_queries'];
            case 'total_query_time':
                return $item['total_query_time'];
            case 'peak_memory_usage':
                $peak_memory_usage_bytes = $item['peak_memory_usage'];
                $peak_memory_usage_mb = round($peak_memory_usage_bytes / 1048576, 2);
                return $peak_memory_usage_mb . 'MB';
            case 'timestamp':
                return $item['timestamp'];
            default:
                return $item[$column_name];
        }
    }
    // Get checkbox
    function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="element[]" value="%s" />',
            $item['id']
        );
    }

    // Sorting columns
    function get_sortable_columns()
    {
        $sortable_columns = array(
            'url'  => array('url', true),
            'execution_time' => array('execution_time', true),
            'post_type' => array('post_type', true),
            'ip_address' => array('ip_address', true),
            'total_queries'   => array('total_queries', true),
            //'total_query_time' => array('total_query_time', true),
            //'peak_memory_usage' => array('peak_memory_usage', true),
            //'max_execution_time' => array('max_execution_time', true),
            'timestamp'   => array('timestamp', true)

        );
        return $sortable_columns;
    }

    function get_bulk_actions()
    {
        $actions = array(
            'delete_all'    => __('Delete', 'wpoven-performance-logs'),
        );
        return $actions;
    }

    // Sorting function
    function usort_reorder($a, $b)
    {
        $time = (!empty($_GET['timestamp'])) ? $_GET['timestamp'] : 'timestamp';
        $order = (!empty($_GET['order'])) ? $_GET['order'] : 'desc';
        $result = strcmp($a[$time], $b[$time]);


        return ($order === 'asc') ? $result : -$result;
    }

    function views()
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'performance_logs';

        $row_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            $row_count = 0;
        } else {
            $row_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        }

        echo '<ul class="subsubsub">';
        echo sprintf(
            '<a type="button" style="color:#0073aa;" href="?page=view-performance-logs">All&nbsp;(%s)</a>',
            esc_html($row_count)
        );
        echo '</ul>';
    }


    /**
     * HTML for review log modal.
     */
    // function data_modal($item)
    // {
    //     return '<div id="modal-' . $item['id'] . '" class="hidden disable-outside-clicks">
    // 		<div class="modal modal-content" >
    // 			<header>
    // 				<div><h1>Performance Logs</h1></div>
    // 				<div style="color:red; border-color:red;" class="button close rounded alignright"><strong >Close</strong></div>
    // 			</header>
    // 			<div>
    // 				<div class="wp-list-table">
    // 					<div class="wp-list-row">
    // 						<div class="wp-list-cell"><h4>URL</h4></div>
    // 						<div class="wp-list-cell"><span>' . $item['url'] . '</span></div>
    // 					</div>
    // 					<div class="wp-list-row">
    // 						<div class="wp-list-cell"><h4>Execution Time</h4></div>
    // 						<div class="wp-list-cell"><span>' . $item['execution_time'] . '</span></div>
    // 					</div>
    // 					<div class="wp-list-row">
    // 						<div class="wp-list-cell"><h4>Post Type</h4></div>
    // 						<div class="wp-list-cell">' . $item['post_type'] . '</div>
    // 					</div>
    //                     <div class="wp-list-row">
    // 						<div class="wp-list-cell"><h4>IP Address</h4></div>
    // 						<div class="wp-list-cell">' . $item['ip_address'] . '</div>
    // 					</div>
    // 					<div class="wp-list-row">
    // 						<div class="wp-list-cell"><h4>Total Queries</h4></div>
    // 						<div class="wp-list-cell">' . $item['total_queries'] . '</div>
    // 					</div>
    //                     	<div class="wp-list-row">
    // 						<div class="wp-list-cell"><h4>Total Queries Time</h4></div>
    // 						<div class="wp-list-cell">' . $item['total_query_time'] . '</div>
    // 					</div>
    // 				</div>
    // 			</div>
    // 		</div>
    // 	</div>';
    // }
}
