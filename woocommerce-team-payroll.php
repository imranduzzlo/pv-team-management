<?php
/**
 * Plugin Name: WooCommerce Team Payroll & Commission System
 * Plugin URI: https://github.com/imranduzzlo/pv-team-payroll
 * Description: Manage team-based commission and payroll system with agents and processors
 * Version: 2.0.0
 * Author: Imran
 * Author URI: https://imranhossain.me/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 * Text Domain: wc-team-payroll
 * Domain Path: /languages
 * Requires Plugins: woocommerce
 * GitHub Plugin URI: imranduzzlo/pv-team-payroll
 * GitHub Branch: main
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WC_TEAM_PAYROLL_VERSION', '2.0.0' );
define( 'WC_TEAM_PAYROLL_PATH', plugin_dir_path( __FILE__ ) );
define( 'WC_TEAM_PAYROLL_URL', plugin_dir_url( __FILE__ ) );

// Include main plugin class
require_once WC_TEAM_PAYROLL_PATH . 'includes/class-main.php';

// Add plugin action links
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), function( $links ) {
	$settings_link = '<a href="' . esc_url( admin_url( 'admin.php?page=wc-team-payroll-settings' ) ) . '">' . esc_html__( 'Settings', 'wc-team-payroll' ) . '</a>';
	array_unshift( $links, $settings_link );
	return $links;
} );

// Initialize plugin
add_action( 'plugins_loaded', array( 'WC_Team_Payroll_Main', 'init' ) );

// Activation hook
register_activation_hook( __FILE__, array( 'WC_Team_Payroll_Main', 'activate' ) );

// Deactivation hook
register_deactivation_hook( __FILE__, array( 'WC_Team_Payroll_Main', 'deactivate' ) );
