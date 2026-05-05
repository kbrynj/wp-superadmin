<?php
/**
 * Plugin Name: WC Superadmin Hub
 * Description: Central hub for generating magic login links to client sites.
 * Version: 1.1.3
 * Author: KimB
 * License: GPL-2.0+
 */

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

define('WC_SUPERADMIN_HUB_VERSION', '1.1.3');
define('WC_SUPERADMIN_HUB_PATH', plugin_dir_path(__FILE__));
define('WC_SUPERADMIN_HUB_URL', plugin_dir_url(__FILE__));

// Plugin Update Checker
require_once WC_SUPERADMIN_HUB_PATH . 'vendor/plugin-update-checker/plugin-update-checker.php';
$wc_superadmin_hub_updater = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
	'https://raw.githubusercontent.com/kbrynj/wp-superadmin/main/hub-update.json',
	__FILE__,
	'wc-superadmin-hub'
);

/**
 * Main WC Superadmin Hub Class
 */
class WC_Superadmin_Hub
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
		require_once WC_SUPERADMIN_HUB_PATH . 'includes/class-hub-keys.php';
		require_once WC_SUPERADMIN_HUB_PATH . 'includes/class-hub-logger.php';
		require_once WC_SUPERADMIN_HUB_PATH . 'includes/class-hub-dashboard.php';
		require_once WC_SUPERADMIN_HUB_PATH . 'includes/class-hub-api.php';
	}

	/**
	 * Initialize hooks
	 */
	private function init_hooks()
	{
		register_activation_hook(__FILE__, array('WC_Superadmin_Hub_Keys', 'generate_keys_on_activation'));
		register_activation_hook(__FILE__, array('WC_Superadmin_Hub_Logger', 'create_table'));
	}
}

// Initialize the plugin
function run_wc_superadmin_hub()
{
	WC_Superadmin_Hub::get_instance();
}
run_wc_superadmin_hub();
