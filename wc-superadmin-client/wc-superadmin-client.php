<?php
/**
 * Plugin Name: WC Superadmin Client
 * Description: Client plugin to accept magic login links from the central Hub.
 * Version: 1.0.0
 * Author: KimB
 * License: GPL-2.0+
 */

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

define('WC_SUPERADMIN_CLIENT_VERSION', '1.0.0');
define('WC_SUPERADMIN_CLIENT_PATH', plugin_dir_path(__FILE__));
define('WC_SUPERADMIN_CLIENT_URL', plugin_dir_url(__FILE__));

/**
 * Main WC Superadmin Client Class
 */
class WC_Superadmin_Client
{

	/**
	 * Single instance of the class
	 */
	private static $instance = null;

	/**
	 * Get instance
	 */
	public static function get_instance()
	{
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct()
	{
		$this->includes();
		$this->init_hooks();
	}

	/**
	 * Include required files
	 */
	private function includes()
	{
		require_once WC_SUPERADMIN_CLIENT_PATH . 'includes/class-client-settings.php';
		require_once WC_SUPERADMIN_CLIENT_PATH . 'includes/class-client-logger.php';
		require_once WC_SUPERADMIN_CLIENT_PATH . 'includes/class-client-user-manager.php';
		require_once WC_SUPERADMIN_CLIENT_PATH . 'includes/class-client-auth.php';
	}

	/**
	 * Initialize hooks
	 */
	private function init_hooks()
	{
		register_activation_hook(__FILE__, array('WC_Superadmin_Client_Logger', 'create_table'));
	}
}

// Initialize the plugin
function run_wc_superadmin_client()
{
	WC_Superadmin_Client::get_instance();
}
run_wc_superadmin_client();
