<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Superadmin_Client_Auth {

	public function __construct() {
		add_action( 'init', array( $this, 'intercept_login' ) );
	}

	/**
	 * Intercept the custom auth parameter
	 */
	public function intercept_login() {
		if ( ! isset( $_GET['wc_superadmin_auth'] ) ) {
			return;
		}

		$token = sanitize_text_field( $_GET['wc_superadmin_auth'] );
		$public_key = get_option( 'wc_superadmin_public_key' );

		if ( empty( $public_key ) ) {
			wp_die( 'WC Superadmin: Public key not configured on this client site.' );
		}

		// Split the token into payload and signature
		$parts = explode( '.', $token );
		if ( count( $parts ) !== 2 ) {
			wp_die( 'WC Superadmin: Invalid token format.' );
		}

		$payload_encoded = $parts[0];
		$signature_encoded = $parts[1];

		$signature = base64_decode( $signature_encoded );

		// Verify the signature using the Public Key
		$verified = openssl_verify( $payload_encoded, $signature, $public_key, OPENSSL_ALGO_SHA256 );

		if ( $verified !== 1 ) {
			wp_die( 'WC Superadmin: Invalid or forged token signature.' );
		}

		// Decode the payload
		$payload_json = base64_decode( $payload_encoded );
		$payload = json_decode( $payload_json, true );

		if ( ! is_array( $payload ) || ! isset( $payload['exp'] ) || ! isset( $payload['agent_login'] ) || ! isset( $payload['agent_email'] ) ) {
			wp_die( 'WC Superadmin: Invalid token payload.' );
		}

		// Check expiration
		if ( time() > $payload['exp'] ) {
			wp_die( 'WC Superadmin: Token has expired. Please generate a new link from the Hub.' );
		}

		// Check audience (ensure the token was meant for this specific site)
		$current_site_url = site_url();
		if ( isset( $payload['aud'] ) && rtrim( $payload['aud'], '/' ) !== rtrim( $current_site_url, '/' ) ) {
			// This prevents a token generated for Site A from being used on Site B,
			// even if they share the same public key.
			wp_die( 'WC Superadmin: Token audience mismatch. This token is not valid for this site.' );
		}

		// Token is valid! Let's log them in.
		$agent_login = $payload['agent_login'];
		$agent_email = $payload['agent_email'];

		$user = WC_Superadmin_Client_User_Manager::get_or_create_support_user( $agent_login, $agent_email );

		if ( is_wp_error( $user ) ) {
			wp_die( 'WC Superadmin: Failed to provision support user: ' . $user->get_error_message() );
		}

		// Log the access
		WC_Superadmin_Client_Logger::log_event( $agent_login );

		// Log the user into WordPress
		wp_set_current_user( $user->ID, $user->user_login );
		wp_set_auth_cookie( $user->ID, false );
		do_action( 'wp_login', $user->user_login, $user );

		// Redirect to dashboard
		wp_safe_redirect( admin_url() );
		exit;
	}
}

new WC_Superadmin_Client_Auth();
