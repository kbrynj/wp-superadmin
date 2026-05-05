<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Superadmin_Client_User_Manager {

	/**
	 * Provision or retrieve a support user based on the central hub agent login
	 *
	 * @param string $agent_login The username from the central hub.
	 * @param string $agent_email The email from the central hub.
	 * @return WP_User|WP_Error The user object or an error.
	 */
	public static function get_or_create_support_user( $agent_login, $agent_email ) {
		// Prefix the username to clearly identify it as a remote support user
		$local_username = 'support_' . sanitize_user( $agent_login );
		$local_email    = sanitize_email( $agent_email );

		$user = get_user_by( 'login', $local_username );

		// We use a highly random, unguessable password. 
		// The agent never knows this password; they only log in via the magic link.
		$random_password = wp_generate_password( 64, true, true );

		if ( ! $user ) {
			// If a user with the same email already exists (maybe from a different agent login name),
			// we should probably just use a modified email to avoid conflicts, or use the existing user.
			// To be safe and ensure isolation, we'll prefix the email too if it already exists.
			if ( email_exists( $local_email ) ) {
				$local_email = 'support_' . uniqid() . '_' . $local_email;
			}

			$user_id = wp_create_user( $local_username, $random_password, $local_email );

			if ( is_wp_error( $user_id ) ) {
				return $user_id;
			}

			$user = get_user_by( 'id', $user_id );
			
			// Grant administrator role
			$user->set_role( 'administrator' );
		} else {
			// User exists, reset their password to a new random string to ensure it remains inaccessible via standard login forms.
			wp_set_password( $random_password, $user->ID );
			
			// Ensure they are still an admin (in case someone demoted them)
			if ( ! in_array( 'administrator', (array) $user->roles ) ) {
				$user->set_role( 'administrator' );
			}
		}

		return $user;
	}
}
