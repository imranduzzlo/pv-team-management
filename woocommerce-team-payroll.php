<?php
/**
 * Plugin Name: WooCommerce Team Payroll & Commission System
 * Plugin URI: https://github.com/imranduzzlo/pv-team-payroll
 * Description: Manage team-based commission and payroll system with agents and processors
 * Version: 3.1.0
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

define( 'WC_TEAM_PAYROLL_VERSION', '3.1.0' );
define( 'WC_TEAM_PAYROLL_PATH', plugin_dir_path( __FILE__ ) );
define( 'WC_TEAM_PAYROLL_URL', plugin_dir_url( __FILE__ ) );

// Check dependencies
require_once WC_TEAM_PAYROLL_PATH . 'includes/class-dependencies.php';
$dependencies = new WC_Team_Payroll_Dependencies();
if ( ! $dependencies->check() ) {
	return;
}

// Load main plugin class
require_once WC_TEAM_PAYROLL_PATH . 'includes/class-main.php';

// Initialize plugin
add_action( 'plugins_loaded', function() {
	WC_Team_Payroll_Main::get_instance();
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
