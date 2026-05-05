<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Superadmin_Hub_Keys {

	/**
	 * Generate and store RSA keys on plugin activation
	 */
	public static function generate_keys_on_activation() {
		if ( ! get_option( 'wc_superadmin_private_key' ) || ! get_option( 'wc_superadmin_public_key' ) ) {
			self::generate_and_store_keys();
		}
	}

	/**
	 * Generate a new RSA keypair
	 */
	public static function generate_and_store_keys() {
		// Check for OpenSSL extension
		if ( ! function_exists( 'openssl_pkey_new' ) ) {
			add_action( 'admin_notices', function() {
				echo '<div class="notice notice-error"><p><strong>WC Superadmin Hub Error:</strong> The PHP OpenSSL extension is not enabled on your server. This extension is required to generate secure RSA keys. Please contact your hosting provider.</p></div>';
			} );
			return false;
		}

		// Set up key generation parameters
		$config = array(
			'digest_alg'       => 'sha256',
			'private_key_bits' => 2048,
			'private_key_type' => OPENSSL_KEYTYPE_RSA,
		);

		// Create the private and public key
		$res = openssl_pkey_new( $config );

		if ( ! $res ) {
			// Log error if openssl is not configured properly
			error_log( 'WC Superadmin Hub: Failed to generate RSA keys. Check OpenSSL configuration.' );
			return false;
		}

		// Extract the private key
		openssl_pkey_export( $res, $private_key );

		// Extract the public key
		$public_key_pem = openssl_pkey_get_details( $res );
		$public_key = $public_key_pem['key'];

		// Store keys securely in WordPress options
		update_option( 'wc_superadmin_private_key', $private_key );
		update_option( 'wc_superadmin_public_key', $public_key );

		return true;
	}

	/**
	 * Get the private key
	 */
	public static function get_private_key() {
		return get_option( 'wc_superadmin_private_key' );
	}

	/**
	 * Get the public key
	 */
	public static function get_public_key() {
		return get_option( 'wc_superadmin_public_key' );
	}
}
