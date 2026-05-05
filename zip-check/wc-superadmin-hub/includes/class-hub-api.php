<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Superadmin_Hub_API {

	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	public function register_routes() {
		register_rest_route( 'wc-superadmin-hub/v1', '/register', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'handle_registration' ),
			'permission_callback' => '__return_true', // Open endpoint for clients to ping
		) );
	}

	public function handle_registration( WP_REST_Request $request ) {
		$client_url = esc_url_raw( $request->get_param( 'client_url' ) );

		if ( empty( $client_url ) ) {
			return new WP_Error( 'missing_url', 'Client URL is required', array( 'status' => 400 ) );
		}

		$clients = get_option( 'wc_superadmin_clients', array() );

		if ( ! in_array( $client_url, $clients ) ) {
			$clients[] = $client_url;
			update_option( 'wc_superadmin_clients', $clients );
		}

		return rest_ensure_response( array(
			'success' => true,
			'message' => 'Client registered successfully',
		) );
	}
}

new WC_Superadmin_Hub_API();
