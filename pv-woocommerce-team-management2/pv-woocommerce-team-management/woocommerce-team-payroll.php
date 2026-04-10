<?php
/**
 * Plugin Name: WooCommerce Team Payroll & Commission System
 * Plugin URI: https://example.com/woocommerce-team-payroll
 * Description: Manage team-based commission and payroll system with agents and processors
 * Version: 1.0.0
 * Author: Imran
 * Author URI: https://imranhossain.me/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 * Text Domain: wc-team-payroll
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WC_TEAM_PAYROLL_VERSION', '1.0.0' );
define( 'WC_TEAM_PAYROLL_PATH', plugin_dir_path( __FILE__ ) );
define( 'WC_TEAM_PAYROLL_URL', plugin_dir_url( __FILE__ ) );

// Check dependencies
require_once WC_TEAM_PAYROLL_PATH . 'includes/class-dependencies.php';
$dependencies = new WC_Team_Payroll_Dependencies();
if ( ! $dependencies->check() ) {
	return;
}

// Load plugin classes
require_once WC_TEAM_PAYROLL_PATH . 'includes/class-core-engine.php';
require_once WC_TEAM_PAYROLL_PATH . 'includes/class-payroll-engine.php';
require_once WC_TEAM_PAYROLL_PATH . 'includes/class-settings.php';
require_once WC_TEAM_PAYROLL_PATH . 'includes/class-dashboard.php';
require_once WC_TEAM_PAYROLL_PATH . 'includes/class-shortcodes.php';
require_once WC_TEAM_PAYROLL_PATH . 'includes/class-myaccount.php';
require_once WC_TEAM_PAYROLL_PATH . 'includes/class-acf-fields.php';
require_once WC_TEAM_PAYROLL_PATH . 'includes/class-ajax-handlers.php';
require_once WC_TEAM_PAYROLL_PATH . 'includes/class-checkout-integration.php';
require_once WC_TEAM_PAYROLL_PATH . 'includes/class-employee-management.php';
require_once WC_TEAM_PAYROLL_PATH . 'includes/class-employee-detail.php';

// Initialize plugin
add_action( 'plugins_loaded', function() {
	new WC_Team_Payroll_Core_Engine();
	new WC_Team_Payroll_Settings();
	new WC_Team_Payroll_Dashboard();
	new WC_Team_Payroll_Shortcodes();
	new WC_Team_Payroll_MyAccount();
	new WC_Team_Payroll_ACF_Fields();
	new WC_Team_Payroll_Checkout_Integration();
	new WC_Team_Payroll_Employee_Management();
	new WC_Team_Payroll_Employee_Detail();
} );

// Activation hook
register_activation_hook( __FILE__, function() {
	require_once WC_TEAM_PAYROLL_PATH . 'includes/class-installer.php';
	WC_Team_Payroll_Installer::install();
} );

// Deactivation hook
register_deactivation_hook( __FILE__, function() {
	// Cleanup if needed
} );
