<?php
/**
 * Plugin Name: WooCommerce Team Payroll & Commission System
 * Plugin URI: https://github.com/imranduzzlo/pv-team-payroll
 * Description: Manage team-based commission and payroll system with agents and processors
 * Version: 5.6.0
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

define( 'WC_TEAM_PAYROLL_VERSION', '5.6.0' );
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
	// Core & Engine
	require_once WC_TEAM_PAYROLL_PATH . 'includes/class-core-engine.php';
	require_once WC_TEAM_PAYROLL_PATH . 'includes/class-payroll-engine.php';
	require_once WC_TEAM_PAYROLL_PATH . 'includes/class-custom-fields.php';
	require_once WC_TEAM_PAYROLL_PATH . 'includes/class-checkout-integration.php';
	require_once WC_TEAM_PAYROLL_PATH . 'includes/class-github-updater.php';

	// Backend (Admin Pages)
	require_once WC_TEAM_PAYROLL_PATH . 'includes/backend/class-settings.php';
	require_once WC_TEAM_PAYROLL_PATH . 'includes/backend/class-dashboard.php';
	require_once WC_TEAM_PAYROLL_PATH . 'includes/backend/class-payroll-page.php';
	require_once WC_TEAM_PAYROLL_PATH . 'includes/backend/class-employee-management.php';
	require_once WC_TEAM_PAYROLL_PATH . 'includes/backend/class-employee-detail.php';

	// Frontend (Customer-Facing)
	require_once WC_TEAM_PAYROLL_PATH . 'includes/frontend/class-myaccount.php';
	require_once WC_TEAM_PAYROLL_PATH . 'includes/frontend/class-shortcodes.php';

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
		}

		// Count unique orders (not summing employee orders, as same order can be counted twice)
		$unique_orders = array();
		$args = array(
			'limit'  => -1,
			'status' => array( 'completed', 'processing', 'refunded' ),
			'date_query' => array(
				array(
					'after'     => $start_date,
					'before'    => $end_date,
					'inclusive' => true,
				),
			),
		);
		$orders = wc_get_orders( $args );
		foreach ( $orders as $order ) {
			$commission_data = $order->get_meta( '_commission_data' );
			
			// Only count orders that have commission data
			if ( $commission_data ) {
				$unique_orders[ $order->get_id() ] = true;
			}
		}
		$total_orders = count( $unique_orders );

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

	add_action( 'wp_ajax_wc_tp_get_payroll_data', function() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'wc-team-payroll' ) );
		}

		$month = intval( $_POST['month'] );
		$year = intval( $_POST['year'] );

		// Get payroll data
		$payroll = array();
		if ( class_exists( 'WC_Team_Payroll_Payroll_Engine' ) ) {
			$payroll = WC_Team_Payroll_Payroll_Engine::get_monthly_payroll( $year, $month );
		}

		// Process payroll data to include vb_user_id and formatted names
		$processed_payroll = array();
		foreach ( $payroll as $user_id => $data ) {
			$vb_user_id = $data['user'] ? get_user_meta( $data['user']->ID, 'vb_user_id', true ) : '';
			$employee_name = $vb_user_id ? '(' . esc_html( $vb_user_id ) . ') ' . esc_html( $data['user']->display_name ) : ( $data['user'] ? esc_html( $data['user']->display_name ) : 'Unknown' );
			
			$processed_payroll[ $user_id ] = array(
				'user_id'    => $data['user_id'],
				'user'       => $data['user'],
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

		wp_send_json_success( array(
			'total_employees'   => $total_employees,
			'total_orders'      => $total_orders,
			'total_earnings'    => $total_earnings,
			'total_paid'        => $total_paid,
			'total_due'         => $total_due,
			'payroll'           => $payroll,
			'currency'          => get_woocommerce_currency(),
			'currency_symbol'   => get_woocommerce_currency_symbol(),
			'currency_pos'      => get_option( 'woocommerce_currency_pos', 'left' ),
		) );
	} );

	add_action( 'wp_ajax_wc_tp_get_payroll_data_range', function() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'wc-team-payroll' ) );
		}

		$start_date = isset( $_POST['start_date'] ) ? sanitize_text_field( $_POST['start_date'] ) : date( 'Y-m-01' );
		$end_date = isset( $_POST['end_date'] ) ? sanitize_text_field( $_POST['end_date'] ) : date( 'Y-m-t' );
		$search_query = isset( $_POST['search_query'] ) ? sanitize_text_field( $_POST['search_query'] ) : '';
		$salary_type = isset( $_POST['salary_type'] ) ? sanitize_text_field( $_POST['salary_type'] ) : '';

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
			
			// Determine salary type
			$is_fixed_salary = $data['user'] ? get_user_meta( $data['user']->ID, '_wc_tp_fixed_salary', true ) : false;
			$is_combined_salary = $data['user'] ? get_user_meta( $data['user']->ID, '_wc_tp_combined_salary', true ) : false;
			
			if ( $is_fixed_salary ) {
				$type_key = 'fixed';
			} elseif ( $is_combined_salary ) {
				$type_key = 'combined';
			} else {
				$type_key = 'commission';
			}
			
			$processed_payroll[ $user_id ] = array(
				'user_id'    => $data['user_id'],
				'user'       => $data['user'],
				'vb_user_id' => $vb_user_id,
				'name'       => $employee_name,
				'total'      => $data['total'],
				'orders'     => $data['orders'],
				'paid'       => $data['paid'],
				'due'        => $data['due'],
				'salary_type' => $type_key,
			);
		}
		$payroll = $processed_payroll;

		// Apply salary type filter if provided
		if ( ! empty( $salary_type ) ) {
			$filtered_payroll = array();
			foreach ( $payroll as $user_id => $data ) {
				if ( $data['salary_type'] === $salary_type ) {
					$filtered_payroll[ $user_id ] = $data;
				}
			}
			$payroll = $filtered_payroll;
		}

		// Apply search filter if search query is provided
		if ( ! empty( $search_query ) ) {
			$search_query_lower = strtolower( $search_query );
			$filtered_payroll = array();

			foreach ( $payroll as $user_id => $data ) {
				$user = $data['user'];
				$vb_user_id = $data['vb_user_id'];
				$email = $user ? $user->user_email : '';
				$phone = $user ? get_user_meta( $user->ID, 'billing_phone', true ) : '';
				$display_name = $user ? $user->display_name : '';

				// Check if search query matches any field
				$matches = false;
				if ( stripos( $display_name, $search_query ) !== false ) {
					$matches = true;
				} elseif ( stripos( $vb_user_id, $search_query ) !== false ) {
					$matches = true;
				} elseif ( stripos( (string) $user_id, $search_query ) !== false ) {
					$matches = true;
				} elseif ( stripos( $email, $search_query ) !== false ) {
					$matches = true;
				} elseif ( stripos( $phone, $search_query ) !== false ) {
					$matches = true;
				}

				if ( $matches ) {
					$filtered_payroll[ $user_id ] = $data;
				}
			}

			$payroll = $filtered_payroll;
		}

		wp_send_json_success( array(
			'payroll'           => $payroll,
			'currency'          => get_woocommerce_currency(),
			'currency_symbol'   => get_woocommerce_currency_symbol(),
			'currency_pos'      => get_option( 'woocommerce_currency_pos', 'left' ),
		) );
	} );

	add_action( 'wp_ajax_wc_tp_get_employees_data', function() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'wc-team-payroll' ) );
		}

		$search_query = isset( $_POST['search_query'] ) ? sanitize_text_field( $_POST['search_query'] ) : '';
		$salary_type = isset( $_POST['salary_type'] ) ? sanitize_text_field( $_POST['salary_type'] ) : '';
		$start_date = isset( $_POST['start_date'] ) ? sanitize_text_field( $_POST['start_date'] ) : '';
		$end_date = isset( $_POST['end_date'] ) ? sanitize_text_field( $_POST['end_date'] ) : '';

		// Get all employees
		$employees = get_users( array(
			'role__in' => array( 'shop_employee', 'shop_manager', 'administrator' ),
			'number'   => -1,
		) );

		$employees_data = array();

		foreach ( $employees as $employee ) {
			$is_fixed_salary = get_user_meta( $employee->ID, '_wc_tp_fixed_salary', true );
			$is_combined_salary = get_user_meta( $employee->ID, '_wc_tp_combined_salary', true );
			$salary = get_user_meta( $employee->ID, '_wc_tp_salary_amount', true );
			$frequency = get_user_meta( $employee->ID, '_wc_tp_salary_frequency', true );
			$vb_user_id = get_user_meta( $employee->ID, 'vb_user_id', true );

			// Determine salary type
			if ( $is_fixed_salary ) {
				$type = __( 'Fixed Salary', 'wc-team-payroll' );
				$type_key = 'fixed';
				$salary_info = wc_price( $salary ) . ' / ' . esc_html( $frequency );
			} elseif ( $is_combined_salary ) {
				$type = __( 'Combined (Base + Commission)', 'wc-team-payroll' );
				$type_key = 'combined';
				$salary_info = wc_price( $salary ) . ' / ' . esc_html( $frequency );
			} else {
				$type = __( 'Commission Based', 'wc-team-payroll' );
				$type_key = 'commission';
				$salary_info = __( 'Commission Based', 'wc-team-payroll' );
			}

			// Apply salary type filter
			if ( ! empty( $salary_type ) && $salary_type !== $type_key ) {
				continue;
			}

			// Apply date range filter (employee creation date)
			if ( ! empty( $start_date ) || ! empty( $end_date ) ) {
				$employee_created = strtotime( $employee->user_registered );
				$filter_start = ! empty( $start_date ) ? strtotime( $start_date ) : 0;
				$filter_end = ! empty( $end_date ) ? strtotime( $end_date . ' 23:59:59' ) : PHP_INT_MAX;

				if ( $employee_created < $filter_start || $employee_created > $filter_end ) {
					continue;
				}
			}

			// Apply search filter
			if ( ! empty( $search_query ) ) {
				$matches = false;
				if ( stripos( $employee->display_name, $search_query ) !== false ) {
					$matches = true;
				} elseif ( stripos( $employee->user_email, $search_query ) !== false ) {
					$matches = true;
				} elseif ( stripos( $vb_user_id, $search_query ) !== false ) {
					$matches = true;
				}

				if ( ! $matches ) {
					continue;
				}
			}

			$employee_name = $vb_user_id ? '(' . esc_html( $vb_user_id ) . ') ' . esc_html( $employee->display_name ) : esc_html( $employee->display_name );

			$employees_data[] = array(
				'user_id'      => $employee->ID,
				'vb_user_id'   => $vb_user_id,
				'display_name' => $employee_name,
				'user_email'   => $employee->user_email,
				'type'         => $type,
				'salary_info'  => $salary_info,
				'manage_url'   => add_query_arg( array( 'page' => 'wc-team-payroll-employee-detail', 'user_id' => $employee->ID ), admin_url( 'admin.php' ) ),
			);
		}

		wp_send_json_success( array(
			'employees' => $employees_data,
		) );
	} );

	add_action( 'wp_ajax_wc_tp_get_employee_orders', function() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'wc-team-payroll' ) );
		}

		$user_id = isset( $_POST['user_id'] ) ? intval( $_POST['user_id'] ) : 0;
		$start_date = isset( $_POST['start_date'] ) ? sanitize_text_field( $_POST['start_date'] ) : date( 'Y-m-01' );
		$end_date = isset( $_POST['end_date'] ) ? sanitize_text_field( $_POST['end_date'] ) : date( 'Y-m-t' );
		$status_filter = isset( $_POST['status'] ) ? sanitize_text_field( $_POST['status'] ) : '';
		$flag_filter = isset( $_POST['flag'] ) ? sanitize_text_field( $_POST['flag'] ) : '';
		$search_query = isset( $_POST['search'] ) ? sanitize_text_field( $_POST['search'] ) : '';

		if ( ! $user_id ) {
			wp_send_json_error( __( 'Invalid user ID', 'wc-team-payroll' ) );
		}

		// Get core engine for commission recalculation
		$core_engine = new WC_Team_Payroll_Core_Engine();

		// Get all orders
		$args = array(
			'limit'  => -1,
			'status' => array( 'completed', 'processing', 'pending', 'cancelled', 'refunded' ),
		);

		$orders = wc_get_orders( $args );
		$orders_data = array();

		foreach ( $orders as $order ) {
			$agent_id = $order->get_meta( '_primary_agent_id' );
			$processor_id = $order->get_meta( '_processor_user_id' );
			$commission_data = $order->get_meta( '_commission_data' );

			// Check if user is involved in this order
			$user_role = null;
			if ( intval( $agent_id ) === intval( $user_id ) ) {
				$user_role = 'agent';
			} elseif ( intval( $processor_id ) === intval( $user_id ) ) {
				$user_role = 'processor';
			}

			if ( ! $user_role ) {
				continue; // User not involved in this order
			}

			// Apply status filter
			if ( ! empty( $status_filter ) && $order->get_status() !== $status_filter ) {
				continue;
			}

			// Recalculate commission based on current salary types
			if ( $commission_data ) {
				$recalculated_commission = $core_engine->calculate_commission( $order, $agent_id, $processor_id );
				$commission_data = $recalculated_commission;
			}

			// Determine flag
			$flag = 'owner';
			$flag_label = __( 'Order Owner', 'wc-team-payroll' );

			if ( $agent_id && $processor_id && intval( $agent_id ) !== intval( $processor_id ) ) {
				if ( $user_role === 'agent' ) {
					$flag = 'affiliate_to';
					$flag_label = __( 'Affiliate To', 'wc-team-payroll' );
				} else {
					$flag = 'affiliate_from';
					$flag_label = __( 'Affiliate From', 'wc-team-payroll' );
				}
			}

			// Apply flag filter
			if ( ! empty( $flag_filter ) && $flag !== $flag_filter ) {
				continue;
			}

			// Calculate user earnings from recalculated commission
			$user_earnings = 0;
			if ( $commission_data ) {
				if ( $user_role === 'agent' ) {
					$user_earnings = $commission_data['agent_earnings'];
				} else {
					$user_earnings = $commission_data['processor_earnings'];
				}
			}

			// Get customer info
			$customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
			$customer_email = $order->get_billing_email();
			$customer_phone = $order->get_billing_phone();

			// Apply search filter
			if ( ! empty( $search_query ) ) {
				$matches = false;
				if ( stripos( $order->get_order_number(), $search_query ) !== false ) {
					$matches = true;
				} elseif ( stripos( $customer_name, $search_query ) !== false ) {
					$matches = true;
				} elseif ( stripos( $customer_email, $search_query ) !== false ) {
					$matches = true;
				} elseif ( stripos( $customer_phone, $search_query ) !== false ) {
					$matches = true;
				}

				if ( ! $matches ) {
					continue;
				}
			}

			// Apply date range filter
			$order_date = $order->get_date_created();
			if ( $order_date ) {
				$order_date_str = $order_date->format( 'Y-m-d' );
				if ( $order_date_str < $start_date || $order_date_str > $end_date ) {
					continue;
				}
			}

			$orders_data[] = array(
				'order_id'        => $order->get_id(),
				'customer_name'   => $customer_name,
				'customer_email'  => $customer_email,
				'customer_phone'  => $customer_phone,
				'total'           => $order->get_total(),
				'status'          => ucfirst( $order->get_status() ),
				'commission'      => $commission_data ? $commission_data['total_commission'] : 0,
				'user_earnings'   => $user_earnings,
				'flag'            => $flag,
				'flag_label'      => $flag_label,
				'date'            => $order->get_date_created()->format( 'Y-m-d' ),
			);
		}

		wp_send_json_success( array(
			'orders' => $orders_data,
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
		'wc-team-payroll-details',
		function() {
			if ( class_exists( 'WC_Team_Payroll_Page' ) ) {
				$payroll_page = new WC_Team_Payroll_Page(); // Constructor will enqueue assets
				$payroll_page->render_payroll();
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
				$employees = new WC_Team_Payroll_Employee_Management(); // Constructor will enqueue assets
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
				$settings = new WC_Team_Payroll_Settings(); // Constructor will enqueue assets
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
