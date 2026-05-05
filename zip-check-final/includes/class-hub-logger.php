<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Superadmin_Hub_Logger {

	/**
	 * Create the custom table for logging
	 */
	public static function create_table() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'wc_superadmin_hub_logs';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			agent_id bigint(20) NOT NULL,
			agent_username varchar(60) NOT NULL,
			client_url varchar(255) NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}

	/**
	 * Log a generated link event
	 */
	public static function log_event( $client_url ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'wc_superadmin_hub_logs';
		$current_user = wp_get_current_user();

		$wpdb->insert(
			$table_name,
			array(
				'time' => current_time( 'mysql' ),
				'agent_id' => $current_user->ID,
				'agent_username' => $current_user->user_login,
				'client_url' => esc_url_raw( $client_url ),
			)
		);
	}

	/**
	 * Get logs
	 */
	public static function get_logs( $limit = 50 ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'wc_superadmin_hub_logs';
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table_name ORDER BY time DESC LIMIT %d", $limit ) );
	}
}
