<?php
/**
 * Plugin Name: WooCommerce Team Payroll & Commission System
 * Plugin URI: https://github.com/imranduzzlo/pv-team-payroll
 * Description: Manage team-based commission and payroll system with agents and processors
 * Version: 5.0.0
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

define( 'WC_TEAM_PAYROLL_VERSION', '5.0.0' );
define( 'WC_TEAM_PAYROLL_PATH', plugin_dir_path( __FILE__ ) );
define( 'WC_TEAM_PAYROLL_URL', plugin_dir_url( __FILE__ ) );

// Load text domain
add_action( 'plugins_loaded', function() {
	load_plugin_textdomain( 'wc-team-payroll', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
} );

// ============================================================================
// CHECK DEPENDENCIES - ONLY WOOCOMMERCE REQUIRED
// ============================================================================

$wc_active = class_exists( 'WooCommerce' );

if ( ! $wc_active ) {
	add_action( 'admin_notices', function() {
		?>
		<div class="notice notice-error is-dismissible">
			<p><?php esc_html_e( 'WooCommerce Team Payroll requires WooCommerce to be installed and activated.', 'wc-team-payroll' ); ?></p>
		</div>
		<?php
	} );
}

// Load all classes - ONLY IF WOOCOMMERCE IS ACTIVE
if ( $wc_active ) {
	require_once WC_TEAM_PAYROLL_PATH . 'includes/class-core-engine.php';
	require_once WC_TEAM_PAYROLL_PATH . 'includes/class-payroll-engine.php';
	require_once WC_TEAM_PAYROLL_PATH . 'includes/class-settings.php';
	require_once WC_TEAM_PAYROLL_PATH . 'includes/class-dashboard.php';
	require_once WC_TEAM_PAYROLL_PATH . 'includes/class-checkout-integration.php';
	require_once WC_TEAM_PAYROLL_PATH . 'includes/class-employee-management.php';
	require_once WC_TEAM_PAYROLL_PATH . 'includes/class-employee-detail.php';
	require_once WC_TEAM_PAYROLL_PATH . 'includes/class-custom-fields.php';
	require_once WC_TEAM_PAYROLL_PATH . 'includes/class-github-updater.php';

	// Initialize GitHub updater
	new WC_Team_Payroll_GitHub_Updater();

	// Initialize custom fields (creates meta fields)
	new WC_Team_Payroll_Custom_Fields();

	// Initialize core engine (handles commission calculations)
	$core_engine = new WC_Team_Payroll_Core_Engine();

	// Initialize checkout integration (handles agent dropdown)
	$checkout_integration = new WC_Team_Payroll_Checkout_Integration();

	// ============================================================================
	// AJAX HANDLERS
	// ============================================================================

	add_action( 'wp_ajax_wc_tp_update_employee_salary', function() {
		$employees = new WC_Team_Payroll_Employee_Management();
		$employees->ajax_update_employee_salary();
	} );

	add_action( 'wp_ajax_wc_tp_add_payment', function() {
		$employees = new WC_Team_Payroll_Employee_Management();
		$employees->ajax_add_payment();
	} );

	add_action( 'wp_ajax_wc_tp_delete_payment', function() {
		$employees = new WC_Team_Payroll_Employee_Management();
		$employees->ajax_delete_payment();
	} );

	add_action( 'wp_ajax_wc_tp_get_payment_data', function() {
		$employees = new WC_Team_Payroll_Employee_Management();
		$employees->ajax_get_payment_data();
	} );

	add_action( 'wp_ajax_wc_tp_add_order_bonus', function() {
		$employees = new WC_Team_Payroll_Employee_Management();
		$employees->ajax_add_order_bonus();
	} );
}

// ============================================================================
// ADMIN MENU - ALWAYS SHOW
// ============================================================================

add_action( 'admin_menu', function() {
	// Main menu
	add_menu_page(
		__( 'Team Payroll', 'wc-team-payroll' ),
		__( 'Team Payroll', 'wc-team-payroll' ),
		'manage_options',
		'wc-team-payroll',
		function() {
			if ( class_exists( 'WC_Team_Payroll_Dashboard' ) ) {
				$dashboard = new WC_Team_Payroll_Dashboard();
				$dashboard->render_dashboard();
			} else {
				echo '<div class="wrap"><h1>Team Payroll</h1>';
				echo '<div class="notice notice-error"><p>WooCommerce is required.</p></div>';
				echo '</div>';
			}
		},
		'dashicons-money-alt',
		25
	);

	// Dashboard submenu
	add_submenu_page(
		'wc-team-payroll',
		__( 'Dashboard', 'wc-team-payroll' ),
		__( 'Dashboard', 'wc-team-payroll' ),
		'manage_options',
		'wc-team-payroll',
		function() {
			if ( class_exists( 'WC_Team_Payroll_Dashboard' ) ) {
				$dashboard = new WC_Team_Payroll_Dashboard();
				$dashboard->render_dashboard();
			} else {
				echo '<div class="wrap"><h1>Dashboard</h1>';
				echo '<div class="notice notice-error"><p>WooCommerce is required.</p></div>';
				echo '</div>';
			}
		}
	);

	// Payroll submenu
	add_submenu_page(
		'wc-team-payroll',
		__( 'Payroll', 'wc-team-payroll' ),
		__( 'Payroll', 'wc-team-payroll' ),
		'manage_options',
		'wc-team-payroll-payroll',
		function() {
			if ( class_exists( 'WC_Team_Payroll_Dashboard' ) ) {
				$dashboard = new WC_Team_Payroll_Dashboard();
				$dashboard->render_payroll();
			} else {
				echo '<div class="wrap"><h1>Payroll</h1>';
				echo '<div class="notice notice-error"><p>WooCommerce is required.</p></div>';
				echo '</div>';
			}
		}
	);

	// Team Members submenu
	add_submenu_page(
		'wc-team-payroll',
		__( 'Team Members', 'wc-team-payroll' ),
		__( 'Team Members', 'wc-team-payroll' ),
		'manage_options',
		'wc-team-payroll-employees',
		function() {
			if ( class_exists( 'WC_Team_Payroll_Employee_Management' ) ) {
				$employees = new WC_Team_Payroll_Employee_Management();
				$employees->render_employees_page();
			} else {
				echo '<div class="wrap"><h1>Team Members</h1>';
				echo '<div class="notice notice-error"><p>WooCommerce is required.</p></div>';
				echo '</div>';
			}
		}
	);

	// Settings submenu
	add_submenu_page(
		'wc-team-payroll',
		__( 'Settings', 'wc-team-payroll' ),
		__( 'Settings', 'wc-team-payroll' ),
		'manage_options',
		'wc-team-payroll-settings',
		function() {
			if ( class_exists( 'WC_Team_Payroll_Settings' ) ) {
				$settings = new WC_Team_Payroll_Settings();
				$settings->render_settings_page();
			} else {
				echo '<div class="wrap"><h1>Settings</h1>';
				echo '<div class="notice notice-error"><p>WooCommerce is required.</p></div>';
				echo '</div>';
			}
		}
	);

	// Employee Detail (hidden submenu)
	add_submenu_page(
		'wc-team-payroll',
		__( 'Employee Detail', 'wc-team-payroll' ),
		'',
		'manage_options',
		'wc-team-payroll-employee-detail',
		function() {
			if ( class_exists( 'WC_Team_Payroll_Employee_Detail' ) ) {
				$detail = new WC_Team_Payroll_Employee_Detail();
				$detail->render_employee_detail();
			} else {
				echo '<div class="wrap"><h1>Employee Detail</h1>';
				echo '<div class="notice notice-error"><p>WooCommerce is required.</p></div>';
				echo '</div>';
			}
		}
	);
}, 10 );

// ============================================================================
// ENQUEUE ADMIN SCRIPTS AND STYLES
// ============================================================================

add_action( 'admin_enqueue_scripts', function( $hook ) {
	if ( strpos( $hook, 'wc-team-payroll' ) === false ) {
		return;
	}

	wp_enqueue_script( 'jquery-datatables', 'https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js', array( 'jquery' ), '1.13.6', true );
	wp_enqueue_style( 'jquery-datatables', 'https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css', array(), '1.13.6' );

	wp_enqueue_style( 'wc-team-payroll-dashboard', WC_TEAM_PAYROLL_URL . 'assets/css/dashboard.css', array(), WC_TEAM_PAYROLL_VERSION );
	wp_enqueue_script( 'wc-team-payroll-dashboard', WC_TEAM_PAYROLL_URL . 'assets/js/dashboard.js', array( 'jquery', 'jquery-datatables' ), WC_TEAM_PAYROLL_VERSION, true );
} );

// ============================================================================
// PLUGIN ACTION LINKS
// ============================================================================

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), function( $links ) {
	$settings_link = '<a href="' . esc_url( admin_url( 'admin.php?page=wc-team-payroll-settings' ) ) . '">' . esc_html__( 'Settings', 'wc-team-payroll' ) . '</a>';
	$dashboard_link = '<a href="' . esc_url( admin_url( 'admin.php?page=wc-team-payroll' ) ) . '">' . esc_html__( 'Dashboard', 'wc-team-payroll' ) . '</a>';
	array_unshift( $links, $settings_link, $dashboard_link );
	return $links;
} );

// ============================================================================
// ACTIVATION AND DEACTIVATION HOOKS
// ============================================================================

register_activation_hook( __FILE__, function() {
	if ( file_exists( WC_TEAM_PAYROLL_PATH . 'includes/class-installer.php' ) ) {
		require_once WC_TEAM_PAYROLL_PATH . 'includes/class-installer.php';
		WC_Team_Payroll_Installer::install();
	}
} );

register_deactivation_hook( __FILE__, function() {
	// Cleanup if needed
} );
