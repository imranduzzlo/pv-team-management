<?php
/**
 * Plugin Name: WooCommerce Team Payroll & Commission System
 * Plugin URI: https://github.com/imranduzzlo/pv-team-payroll
 * Description: Manage team-based commission and payroll system with agents and processors
 * Version: 5.3.21
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

define( 'WC_TEAM_PAYROLL_VERSION', '5.3.21' );
define( 'WC_TEAM_PAYROLL_PATH', plugin_dir_path( __FILE__ ) );
define( 'WC_TEAM_PAYROLL_URL', plugin_dir_url( __FILE__ ) );

// ============================================================================
// LOAD ON PLUGINS_LOADED - AFTER WOOCOMMERCE IS LOADED
// ============================================================================

add_action( 'plugins_loaded', function() {
	// Load text domain
	load_plugin_textdomain( 'wc-team-payroll', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

	// Check if WooCommerce is active
	if ( ! class_exists( 'WooCommerce' ) && ! function_exists( 'WC' ) && ! function_exists( 'wc_get_orders' ) ) {
		add_action( 'admin_notices', function() {
			?>
			<div class="notice notice-error is-dismissible">
				<p><?php esc_html_e( 'WooCommerce Team Payroll requires WooCommerce to be installed and activated.', 'wc-team-payroll' ); ?></p>
			</div>
			<?php
		} );
		return;
	}

	// Load all classes
	require_once WC_TEAM_PAYROLL_PATH . 'includes/class-core-engine.php';
	require_once WC_TEAM_PAYROLL_PATH . 'includes/class-payroll-engine.php';
	require_once WC_TEAM_PAYROLL_PATH . 'includes/class-settings.php';
	require_once WC_TEAM_PAYROLL_PATH . 'includes/class-dashboard.php';
	require_once WC_TEAM_PAYROLL_PATH . 'includes/class-checkout-integration.php';
	require_once WC_TEAM_PAYROLL_PATH . 'includes/class-employee-management.php';
	require_once WC_TEAM_PAYROLL_PATH . 'includes/class-employee-detail.php';
	require_once WC_TEAM_PAYROLL_PATH . 'includes/class-custom-fields.php';
	require_once WC_TEAM_PAYROLL_PATH . 'includes/class-github-updater.php';

	// Initialize custom fields (creates meta fields)
	new WC_Team_Payroll_Custom_Fields();

	// Initialize core engine (handles commission calculations)
	new WC_Team_Payroll_Core_Engine();

	// Initialize checkout integration (handles agent dropdown)
	new WC_Team_Payroll_Checkout_Integration();

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

	add_action( 'wp_ajax_wc_tp_update_payment', function() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'wc-team-payroll' ) );
		}

		$user_id = intval( $_POST['user_id'] );
		$amount = floatval( $_POST['amount'] );
		$date = sanitize_text_field( $_POST['date'] );

		if ( ! $user_id || ! $amount || ! $date ) {
			wp_send_json_error( __( 'Invalid parameters', 'wc-team-payroll' ) );
		}

		$payments = get_user_meta( $user_id, '_wc_tp_payments', true );
		if ( ! is_array( $payments ) || empty( $payments ) ) {
			wp_send_json_error( __( 'No payments found', 'wc-team-payroll' ) );
		}

		// Update the most recent payment (last in array)
		$last_index = count( $payments ) - 1;
		$payments[ $last_index ]['amount'] = $amount;
		$payments[ $last_index ]['date'] = $date;
		
		// Ensure status is set
		if ( ! isset( $payments[ $last_index ]['status'] ) ) {
			$payments[ $last_index ]['status'] = 'completed';
		}

		// Save updated payments
		if ( update_user_meta( $user_id, '_wc_tp_payments', $payments ) !== false ) {
			wp_send_json_success( array( 'message' => __( 'Payment updated successfully', 'wc-team-payroll' ) ) );
		} else {
			wp_send_json_error( __( 'Failed to update payment', 'wc-team-payroll' ) );
		}
	} );

	add_action( 'wp_ajax_wc_tp_get_payment_data', function() {
		$employees = new WC_Team_Payroll_Employee_Management();
		$employees->ajax_get_payment_data();
	} );

	add_action( 'wp_ajax_wc_tp_add_order_bonus', function() {
		$employees = new WC_Team_Payroll_Employee_Management();
		$employees->ajax_add_order_bonus();
	} );

	add_action( 'wp_ajax_wc_tp_get_dashboard_data', function() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'wc-team-payroll' ) );
		}

		$start_date = isset( $_POST['start_date'] ) ? sanitize_text_field( $_POST['start_date'] ) : date( 'Y-m-01' );
		$end_date = isset( $_POST['end_date'] ) ? sanitize_text_field( $_POST['end_date'] ) : date( 'Y-m-t' );

		// Get payroll data
		$payroll = array();
		if ( class_exists( 'WC_Team_Payroll_Payroll_Engine' ) ) {
			$payroll = WC_Team_Payroll_Payroll_Engine::get_payroll_by_date_range( $start_date, $end_date );
		}

		// Process payroll data to include vb_user_id and formatted names
		$processed_payroll = array();
		foreach ( $payroll as $user_id => $data ) {
			$vb_user_id = $data['user'] ? get_user_meta( $data['user']->ID, 'vb_user_id', true ) : '';
			$employee_name = $vb_user_id ? '(' . esc_html( $vb_user_id ) . ') ' . esc_html( $data['user']->display_name ) : ( $data['user'] ? esc_html( $data['user']->display_name ) : 'Unknown' );
			
			$processed_payroll[ $user_id ] = array(
				'user_id'    => $data['user_id'],
				'user'       => $data['user'],
				'user_email' => $data['user'] ? $data['user']->user_email : 'N/A',
				'vb_user_id' => $vb_user_id,
				'name'       => $employee_name,
				'total'      => $data['total'],
				'orders'     => $data['orders'],
				'paid'       => $data['paid'],
				'due'        => $data['due'],
			);
		}
		$payroll = $processed_payroll;

		// Calculate stats
		$total_employees = 0;
		$total_earnings = 0;
		$total_paid = 0;
		$total_due = 0;
		$total_orders = 0;

		foreach ( $payroll as $data ) {
			$total_employees++;
			$total_earnings += $data['total'];
			$total_paid += $data['paid'];
			$total_due += $data['due'];
			$total_orders += $data['orders'];
		}

		// Get latest employees
		$latest_employees_data = array();
		$latest_employees = get_users( array(
			'role__in' => array( 'shop_employee', 'shop_manager', 'administrator' ),
			'orderby'  => 'user_registered',
			'order'    => 'DESC',
			'number'   => 10,
		) );

		foreach ( $latest_employees as $employee ) {
			$is_fixed_salary = get_user_meta( $employee->ID, '_wc_tp_fixed_salary', true );
			$is_combined_salary = get_user_meta( $employee->ID, '_wc_tp_combined_salary', true );
			$salary = get_user_meta( $employee->ID, '_wc_tp_salary_amount', true );
			$frequency = get_user_meta( $employee->ID, '_wc_tp_salary_frequency', true );
			$vb_user_id = get_user_meta( $employee->ID, 'vb_user_id', true );

			if ( $is_fixed_salary ) {
				$type = __( 'Fixed Salary', 'wc-team-payroll' );
				$salary_info = wc_price( $salary ) . ' / ' . esc_html( $frequency );
			} elseif ( $is_combined_salary ) {
				$type = __( 'Combined', 'wc-team-payroll' );
				$salary_info = wc_price( $salary ) . ' / ' . esc_html( $frequency );
			} else {
				$type = __( 'Commission', 'wc-team-payroll' );
				$salary_info = __( 'Commission Based', 'wc-team-payroll' );
			}

			$employee_name = $vb_user_id ? '(' . esc_html( $vb_user_id ) . ') ' . esc_html( $employee->display_name ) : esc_html( $employee->display_name );

			$latest_employees_data[] = array(
				'user_id'      => $employee->ID,
				'vb_user_id'   => $vb_user_id,
				'display_name' => $employee_name,
				'user_email'   => $employee->user_email,
				'type'         => $type,
				'salary_info'  => $salary_info,
				'manage_url'   => add_query_arg( array( 'page' => 'wc-team-payroll-employee-detail', 'user_id' => $employee->ID ), admin_url( 'admin.php' ) ),
			);
		}

		// Get top earners (only those with at least 1 order, up to 10)
		$top_earners_data = array();
		$sorted_payroll = $payroll;
		usort( $sorted_payroll, function( $a, $b ) {
			return $b['total'] - $a['total'];
		} );

		$count = 0;
		foreach ( $sorted_payroll as $data ) {
			if ( $count >= 10 ) {
				break;
			}
			// Only include if they have at least 1 order
			if ( $data['orders'] < 1 ) {
				continue;
			}
			$vb_user_id = $data['user'] ? get_user_meta( $data['user']->ID, 'vb_user_id', true ) : '';
			$employee_name = $vb_user_id ? '(' . esc_html( $vb_user_id ) . ') ' . esc_html( $data['user']->display_name ) : ( $data['user'] ? esc_html( $data['user']->display_name ) : 'Unknown' );
			
			$top_earners_data[] = array(
				'user_id'   => $data['user_id'],
				'name'      => $employee_name,
				'earnings'  => $data['total'],
				'orders'    => $data['orders'],
			);
			$count++;
		}

		// Get recent payments (from user meta, filtered by date range)
		global $wpdb;
		$recent_payments_data = array();
		
		// Get all employees
		$all_employees = get_users( array(
			'role__in' => array( 'shop_employee', 'shop_manager', 'administrator' ),
			'number'   => -1,
		) );

		$all_payments = array();
		foreach ( $all_employees as $employee ) {
			$payments = get_user_meta( $employee->ID, '_wc_tp_payments', true );
			if ( is_array( $payments ) ) {
				foreach ( $payments as $payment ) {
					$payment_date_str = $payment['date'] ?? current_time( 'mysql' );
					
					// Convert datetime-local format
					if ( strpos( $payment_date_str, 'T' ) !== false ) {
						$payment_date_str = str_replace( 'T', ' ', $payment_date_str );
					}
					
					$payment_timestamp = strtotime( $payment_date_str );
					$start_timestamp = strtotime( $start_date . ' 00:00:00' );
					$end_timestamp = strtotime( $end_date . ' 23:59:59' );
					
					// Only include payments within date range
					if ( $payment_timestamp !== false && $payment_timestamp >= $start_timestamp && $payment_timestamp <= $end_timestamp ) {
						$vb_user_id = get_user_meta( $employee->ID, 'vb_user_id', true );
						$employee_name = $vb_user_id ? '(' . esc_html( $vb_user_id ) . ') ' . esc_html( $employee->display_name ) : esc_html( $employee->display_name );
						
						$all_payments[] = array(
							'user_id'       => $employee->ID,
							'employee_name' => $employee_name,
							'amount'        => $payment['amount'] ?? 0,
							'date'          => date( 'M d, Y', $payment_timestamp ),
							'timestamp'     => $payment_timestamp,
							'status'        => $payment['status'] ?? 'pending',
						);
					}
				}
			}
		}

		// Sort by date descending and get latest 10
		usort( $all_payments, function( $a, $b ) {
			return $b['timestamp'] - $a['timestamp'];
		} );

		$recent_payments_data = array_slice( $all_payments, 0, 10 );
		
		// Keep timestamp for sorting but rename it
		foreach ( $recent_payments_data as &$payment ) {
			$payment['date_timestamp'] = $payment['timestamp'];
			unset( $payment['timestamp'] );
		}

		wp_send_json_success( array(
			'total_employees'   => count( $latest_employees ),
			'total_orders'      => $total_orders,
			'total_earnings'    => $total_earnings,
			'total_paid'        => $total_paid,
			'total_due'         => $total_due,
			'payroll'           => $payroll,
			'latest_employees'  => $latest_employees_data,
			'top_earners'       => $top_earners_data,
			'recent_payments'   => $recent_payments_data,
			'currency'          => get_woocommerce_currency(),
			'currency_symbol'   => get_woocommerce_currency_symbol(),
			'currency_pos'      => get_option( 'woocommerce_currency_pos', 'left' ),
		) );
	} );
}, 20 ); // Priority 20 - after WooCommerce loads (priority 10)

// ============================================================================
// ADMIN MENU - REGISTER EARLY (before plugins_loaded)
// ============================================================================

add_action( 'admin_menu', function() {
	// Main menu
	add_menu_page(
		__( 'Team Payroll', 'wc-team-payroll' ),
		__( 'Team Payroll', 'wc-team-payroll' ),
		'manage_options',
		'wc-team-payroll',
		'__return_null',
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
				echo '<div class="notice notice-error"><p>Plugin not fully loaded.</p></div>';
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
				echo '<div class="notice notice-error"><p>Plugin not fully loaded.</p></div>';
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
				echo '<div class="notice notice-error"><p>Plugin not fully loaded.</p></div>';
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
				echo '<div class="notice notice-error"><p>Plugin not fully loaded.</p></div>';
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
				echo '<div class="notice notice-error"><p>Plugin not fully loaded.</p></div>';
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
