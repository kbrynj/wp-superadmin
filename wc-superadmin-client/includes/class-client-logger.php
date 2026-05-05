<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Superadmin_Client_Logger {

	/**
	 * Create the custom table for logging
	 */
	public static function create_table() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'wc_superadmin_client_logs';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			agent_login varchar(60) NOT NULL,
			ip_address varchar(45) NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}

	/**
	 * Log a login event
	 */
	public static function log_event( $agent_login ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'wc_superadmin_client_logs';

		$wpdb->insert(
			$table_name,
			array(
				'time' => current_time( 'mysql' ),
				'agent_login' => sanitize_text_field( $agent_login ),
				'ip_address' => sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? '' ),
			)
		);
	}

	/**
	 * Admin page to view logs
	 */
	public static function init_log_page() {
		add_submenu_page(
			'options-general.php',
			'WC Superadmin Logs',
			'Superadmin Logs',
			'manage_options',
			'wc-superadmin-client-logs',
			array( __CLASS__, 'render_logs_page' )
		);
	}

	public static function render_logs_page() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'wc_superadmin_client_logs';
		$logs = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY time DESC LIMIT 100" );

		?>
		<div class="wrap">
			<h1>WC Superadmin Client Logs</h1>
			<p>Recent logins via the Central Hub.</p>
			<?php if ( empty( $logs ) ) : ?>
				<p>No logins recorded yet.</p>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th>Time</th>
							<th>Agent</th>
							<th>IP Address</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $logs as $log ) : ?>
							<tr>
								<td><?php echo esc_html( $log->time ); ?></td>
								<td><?php echo esc_html( $log->agent_login ); ?></td>
								<td><?php echo esc_html( $log->ip_address ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}
}

add_action( 'admin_menu', array( 'WC_Superadmin_Client_Logger', 'init_log_page' ) );
