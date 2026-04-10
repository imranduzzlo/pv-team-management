<?php
/**
 * Plugin Name: WooCommerce Team Payroll & Commission System
 * Plugin URI: https://github.com/imranduzzlo/pv-team-payroll
 * Description: Manage team-based commission and payroll system with agents and processors
 * Version: 3.0.3
 * Author: Imran
 * Author URI: https://imranhossain.me/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 * Text Domain: wc-team-payroll
 * Domain Path: /languages
 * GitHub Plugin URI: imranduzzlo/pv-team-payroll
 * GitHub Branch: main
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WC_TEAM_PAYROLL_VERSION', '3.0.3' );
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
require_once WC_TEAM_PAYROLL_PATH . 'includes/class-checkout-integration.php';
require_once WC_TEAM_PAYROLL_PATH . 'includes/class-employee-management.php';
require_once WC_TEAM_PAYROLL_PATH . 'includes/class-employee-detail.php';
require_once WC_TEAM_PAYROLL_PATH . 'includes/class-github-updater.php';

// Initialize plugin - instantiate on plugins_loaded, they hook into admin_menu
add_action( 'plugins_loaded', function() {
	new WC_Team_Payroll_Core_Engine();
	new WC_Team_Payroll_Settings();
	new WC_Team_Payroll_Dashboard();
	new WC_Team_Payroll_Checkout_Integration();
	new WC_Team_Payroll_Employee_Management();
	new WC_Team_Payroll_Employee_Detail();
} );

// Also initialize on frontend
add_action( 'wp_loaded', function() {
	if ( ! is_admin() ) {
		new WC_Team_Payroll_Core_Engine();
		new WC_Team_Payroll_Checkout_Integration();
	}
} );

// Add plugin action links
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), function( $links ) {
	$settings_link = '<a href="' . esc_url( admin_url( 'admin.php?page=wc-team-payroll-settings' ) ) . '">' . esc_html__( 'Settings', 'wc-team-payroll' ) . '</a>';
	array_unshift( $links, $settings_link );
	return $links;
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
