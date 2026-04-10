<?php
/**
 * Main plugin class - Singleton pattern
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Team_Payroll_Main {

	private static $instance = null;

	/**
	 * Get singleton instance
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		$this->init();
	}

	/**
	 * Initialize plugin
	 */
	private function init() {
		// Load text domain
		load_plugin_textdomain( 'wc-team-payroll', false, dirname( plugin_basename( WC_TEAM_PAYROLL_PATH . 'woocommerce-team-payroll.php' ) ) . '/languages' );

		// Include GitHub updater
		require_once WC_TEAM_PAYROLL_PATH . 'includes/class-github-updater.php';

		// Include all required files
		require_once WC_TEAM_PAYROLL_PATH . 'includes/class-core-engine.php';
		require_once WC_TEAM_PAYROLL_PATH . 'includes/class-payroll-engine.php';
		require_once WC_TEAM_PAYROLL_PATH . 'includes/class-settings.php';
		require_once WC_TEAM_PAYROLL_PATH . 'includes/class-dashboard.php';
		require_once WC_TEAM_PAYROLL_PATH . 'includes/class-checkout-integration.php';
		require_once WC_TEAM_PAYROLL_PATH . 'includes/class-employee-management.php';
		require_once WC_TEAM_PAYROLL_PATH . 'includes/class-employee-detail.php';

		// Initialize all classes - they hook into admin_menu in their constructors
		new WC_Team_Payroll_Core_Engine();
		new WC_Team_Payroll_Settings();
		new WC_Team_Payroll_Dashboard();
		new WC_Team_Payroll_Checkout_Integration();
		new WC_Team_Payroll_Employee_Management();
		new WC_Team_Payroll_Employee_Detail();

		// Add plugin action links
		add_filter( 'plugin_action_links_' . plugin_basename( WC_TEAM_PAYROLL_PATH . 'woocommerce-team-payroll.php' ), array( $this, 'add_action_links' ) );
	}

	/**
	 * Add plugin action links
	 */
	public function add_action_links( $links ) {
		$settings_link = '<a href="' . esc_url( admin_url( 'admin.php?page=wc-team-payroll-settings' ) ) . '">' . esc_html__( 'Settings', 'wc-team-payroll' ) . '</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}
}
