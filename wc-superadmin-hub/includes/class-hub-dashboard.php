<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Superadmin_Hub_Dashboard {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
		add_action( 'admin_init', array( $this, 'handle_actions' ) );
	}

	/**
	 * Add menu page
	 */
	public function add_menu_page() {
		add_menu_page(
			'WC Superadmin Hub',
			'Superadmin Hub',
			'manage_options',
			'wc-superadmin-hub',
			array( $this, 'render_dashboard' ),
			'dashicons-admin-network',
			2
		);
	}

	/**
	 * Handle form actions (add client, generate link)
	 */
	public function handle_actions() {
		if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'wc-superadmin-hub' ) {
			return;
		}

		if ( isset( $_POST['action'] ) && $_POST['action'] === 'add_client' && check_admin_referer( 'add_client_action' ) ) {
			$clients = get_option( 'wc_superadmin_clients', array() );
			$new_url = esc_url_raw( $_POST['client_url'] );
			if ( $new_url && ! in_array( $new_url, $clients ) ) {
				$clients[] = $new_url;
				update_option( 'wc_superadmin_clients', $clients );
			}
			wp_redirect( admin_url( 'admin.php?page=wc-superadmin-hub' ) );
			exit;
		}

		if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete_client' && isset( $_GET['client'] ) && check_admin_referer( 'delete_client_action' ) ) {
			$clients = get_option( 'wc_superadmin_clients', array() );
			$url_to_delete = base64_decode( $_GET['client'] );
			$clients = array_filter( $clients, function($c) use ($url_to_delete) { return $c !== $url_to_delete; } );
			update_option( 'wc_superadmin_clients', $clients );
			wp_redirect( admin_url( 'admin.php?page=wc-superadmin-hub' ) );
			exit;
		}
	}

	/**
	 * Generate JWT-like token signed with RSA
	 */
	private function generate_token( $client_url ) {
		$private_key = WC_Superadmin_Hub_Keys::get_private_key();
		if ( ! $private_key ) {
			return false;
		}

		$current_user = wp_get_current_user();

		$payload = array(
			'agent_login' => $current_user->user_login,
			'agent_email' => $current_user->user_email,
			'exp'         => time() + 60, // Token valid for 60 seconds
			'iss'         => site_url(),
			'aud'         => $client_url,
		);

		$payload_encoded = base64_encode( wp_json_encode( $payload ) );
		
		// Sign the payload
		openssl_sign( $payload_encoded, $signature, $private_key, OPENSSL_ALGO_SHA256 );
		$signature_encoded = base64_encode( $signature );

		return $payload_encoded . '.' . $signature_encoded;
	}

	/**
	 * Render the dashboard HTML
	 */
	public function render_dashboard() {
		$clients = get_option( 'wc_superadmin_clients', array() );
		$public_key = WC_Superadmin_Hub_Keys::get_public_key();
		
		// Handle generation request for UI rendering
		$generated_link = '';
		if ( isset( $_GET['action'] ) && $_GET['action'] === 'generate' && isset( $_GET['client'] ) && check_admin_referer( 'generate_link_action' ) ) {
			$client_url = base64_decode( $_GET['client'] );
			$token = $this->generate_token( $client_url );
			if ( $token ) {
				WC_Superadmin_Hub_Logger::log_event( $client_url );
				$generated_link = rtrim( $client_url, '/' ) . '/?wc_superadmin_auth=' . urlencode( $token );
			}
		}

		?>
		<div class="wrap">
			<h1>WC Superadmin Hub</h1>

			<div style="background: #fff; padding: 20px; border: 1px solid #ccc; margin-top: 20px;">
				<h2>Your Public Key</h2>
				<p>Copy and paste this public key into the settings of every client site.</p>
				<textarea readonly style="width: 100%; height: 150px; font-family: monospace;"><?php echo esc_textarea( $public_key ); ?></textarea>
			</div>

			<?php if ( $generated_link ) : ?>
				<div style="background: #eefee6; padding: 20px; border: 1px solid #46b450; margin-top: 20px;">
					<h2>Generated Login Link</h2>
					<p>This link is valid for <strong>60 seconds</strong>. Click it to log in as an administrator on the client site.</p>
					<p><a href="<?php echo esc_url( $generated_link ); ?>" target="_blank" class="button button-primary button-large">Login to Client Site</a></p>
					<p>Or copy the link manually:</p>
					<input type="text" readonly value="<?php echo esc_attr( $generated_link ); ?>" style="width: 100%;" onfocus="this.select();">
				</div>
			<?php endif; ?>

			<div style="display: flex; gap: 20px; margin-top: 20px;">
				<div style="flex: 1; background: #fff; padding: 20px; border: 1px solid #ccc;">
					<h2>Client Sites</h2>
					<?php if ( empty( $clients ) ) : ?>
						<p>No client sites added yet.</p>
					<?php else : ?>
						<table class="wp-list-table widefat fixed striped">
							<thead>
								<tr>
									<th>Site URL</th>
									<th style="width: 250px;">Actions</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $clients as $client ) : ?>
									<tr>
										<td><strong><?php echo esc_html( $client ); ?></strong></td>
										<td>
											<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=wc-superadmin-hub&action=generate&client=' . base64_encode( $client ) ), 'generate_link_action' ) ); ?>" class="button button-primary">Generate Login Link</a>
											<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=wc-superadmin-hub&action=delete_client&client=' . base64_encode( $client ) ), 'delete_client_action' ) ); ?>" class="button" onclick="return confirm('Are you sure?');" style="color: #a00;">Remove</a>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php endif; ?>

					<h3 style="margin-top: 30px;">Add New Client Site</h3>
					<form method="post" action="">
						<?php wp_nonce_field( 'add_client_action' ); ?>
						<input type="hidden" name="action" value="add_client">
						<input type="url" name="client_url" placeholder="https://clientsite.com" required style="width: 60%;">
						<input type="submit" class="button" value="Add Client">
					</form>
				</div>

				<div style="flex: 1; background: #fff; padding: 20px; border: 1px solid #ccc;">
					<h2>Recent Access Logs</h2>
					<?php 
					$logs = WC_Superadmin_Hub_Logger::get_logs( 10 );
					if ( empty( $logs ) ) : ?>
						<p>No access logs yet.</p>
					<?php else : ?>
						<table class="wp-list-table widefat fixed striped">
							<thead>
								<tr>
									<th>Time</th>
									<th>Agent</th>
									<th>Target Site</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $logs as $log ) : ?>
									<tr>
										<td><?php echo esc_html( $log->time ); ?></td>
										<td><?php echo esc_html( $log->agent_username ); ?></td>
										<td><?php echo esc_html( $log->client_url ); ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<?php
	}
}

new WC_Superadmin_Hub_Dashboard();
