<?php
/**
 * Main plugin class
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Team_Payroll_Main {

	/**
	 * Initialize plugin
	 */
	public static function init() {
		// Check dependencies
		if ( ! self::check_dependencies() ) {
			return;
		}

		// Load text domain
		load_plugin_textdomain( 'wc-team-payroll', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

		// Include GitHub updater
		require_once WC_TEAM_PAYROLL_PATH . 'includes/class-github-updater.php';

		// Include all required files
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

		// Initialize all classes
		WC_Team_Payroll_Core_Engine::init();
		WC_Team_Payroll_Settings::init();
		WC_Team_Payroll_Dashboard::init();
		WC_Team_Payroll_Shortcodes::init();
		WC_Team_Payroll_MyAccount::init();
		WC_Team_Payroll_ACF_Fields::init();
		WC_Team_Payroll_Checkout_Integration::init();
		WC_Team_Payroll_Employee_Management::init();
		WC_Team_Payroll_Employee_Detail::init();
	}

	/**
	 * Check dependencies
	 */
	private static function check_dependencies() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action( 'admin_notices', array( __CLASS__, 'woocommerce_missing_notice' ) );
			return false;
		}

		if ( ! class_exists( 'ACF' ) && ! class_exists( 'SCF' ) ) {
			add_action( 'admin_notices', array( __CLASS__, 'acf_missing_notice' ) );
			return false;
		}

		return true;
	}

	/**
	 * WooCommerce missing notice
	 */
	public static function woocommerce_missing_notice() {
		?>
		<div class="notice notice-error is-dismissible">
			<p><?php esc_html_e( 'WooCommerce Team Payroll requires WooCommerce to be installed and activated.', 'wc-team-payroll' ); ?></p>
		</div>
		<?php
	}

	/**
	 * ACF/SCF missing notice
	 */
	public static function acf_missing_notice() {
		?>
		<div class="notice notice-error is-dismissible">
			<p><?php esc_html_e( 'WooCommerce Team Payroll requires Advanced Custom Fields (ACF) or Smart Custom Fields (SCF) to be installed and activated.', 'wc-team-payroll' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Plugin activation
	 */
	public static function activate() {
		require_once WC_TEAM_PAYROLL_PATH . 'includes/class-installer.php';
		WC_Team_Payroll_Installer::install();
	}

	/**
	 * Plugin deactivation
	 */
	public static function deactivate() {
		// Cleanup if needed
	}
}
