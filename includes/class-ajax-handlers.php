<?php
/**
 * AJAX Handlers
 */

class WC_Team_Payroll_AJAX_Handlers {

	public static function init() {
		add_action( 'wp_ajax_wc_team_payroll_mark_paid', array( __CLASS__, 'mark_payroll_paid' ) );
		add_action( 'wp_ajax_wc_tp_get_employee_orders', array( __CLASS__, 'get_employee_orders' ) );
		add_action( 'wp_ajax_wc_tp_get_employee_salary', array( __CLASS__, 'get_employee_salary' ) );
		add_action( 'wp_ajax_wc_tp_get_salary_history', array( __CLASS__, 'get_salary_history' ) );
		add_action( 'wp_ajax_wc_tp_update_employee_salary', array( __CLASS__, 'update_employee_salary' ) );
		add_action( 'wp_ajax_wc_tp_get_employee_payments', array( __CLASS__, 'get_employee_payments' ) );
		add_action( 'wp_ajax_wc_tp_add_payment', array( __CLASS__, 'add_payment' ) );
		add_action( 'wp_ajax_wc_tp_delete_payment', array( __CLASS__, 'delete_payment' ) );
		add_action( 'wp_ajax_wc_tp_get_payment_methods', array( __CLASS__, 'get_payment_methods' ) );
		add_action( 'wp_ajax_wc_tp_add_payment_method', array( __CLASS__, 'add_payment_method' ) );
		add_action( 'wp_ajax_wc_tp_delete_payment_method', array( __CLASS__, 'delete_payment_method' ) );
		add_action( 'wp_ajax_wc_tp_get_all_employees', array( __CLASS__, 'get_all_employees' ) );
		add_action( 'wp_ajax_wc_tp_get_payments_table', array( __CLASS__, 'get_payments_table' ) );
		add_action( 'wp_ajax_wc_tp_get_all_payment_methods', array( __CLASS__, 'get_all_payment_methods' ) );
	}

	public static function mark_payroll_paid() {
		check_ajax_referer( 'wc_team_payroll_nonce', 'wc_team_payroll_nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'wc-team-payroll' ) );
		}

		$user_id = intval( $_POST['user_id'] );
		$year = intval( $_POST['year'] );
		$month = intval( $_POST['month'] );
		$amount = floatval( $_POST['amount'] );

		WC_Team_Payroll_Payroll_Engine::mark_payroll_paid( $user_id, $year, $month, $amount );

		wp_send_json_success( array(
			'message' => __( 'Payroll marked as paid', 'wc-team-payroll' ),
		) );
	}

	public static function get_employee_orders() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'wc-team-payroll' ) );
		}

		$user_id = intval( $_POST['user_id'] );
		$start_date = isset( $_POST['start_date'] ) ? sanitize_text_field( $_POST['start_date'] ) : '';
		$end_date = isset( $_POST['end_date'] ) ? sanitize_text_field( $_POST['end_date'] ) : '';
		$status = isset( $_POST['status'] ) ? sanitize_text_field( $_POST['status'] ) : '';
		$flag = isset( $_POST['flag'] ) ? sanitize_text_field( $_POST['flag'] ) : '';
		$search = isset( $_POST['search'] ) ? sanitize_text_field( $_POST['search'] ) : '';

		$earnings = WC_Team_Payroll_Core_Engine::get_user_earnings( $user_id );
		$orders = $earnings['orders'] ?? array();

		// Filter by date range
		if ( $start_date && $end_date ) {
			$start_timestamp = strtotime( $start_date );
			$end_timestamp = strtotime( $end_date . ' 23:59:59' );
			$orders = array_filter( $orders, function( $order ) use ( $start_timestamp, $end_timestamp ) {
				$order_time = strtotime( $order['date'] ?? '' );
				return $order_time >= $start_timestamp && $order_time <= $end_timestamp;
			} );
		}

		// Filter by status
		if ( $status ) {
			$orders = array_filter( $orders, function( $order ) use ( $status ) {
				return ( $order['status'] ?? '' ) === $status;
			} );
		}

		// Filter by flag
		if ( $flag ) {
			$orders = array_filter( $orders, function( $order ) use ( $flag ) {
				return ( $order['flag'] ?? '' ) === $flag;
			} );
		}

		// Search
		if ( $search ) {
			$orders = array_filter( $orders, function( $order ) use ( $search ) {
				$search_lower = strtolower( $search );
				return strpos( strtolower( $order['order_id'] ?? '' ), $search_lower ) !== false ||
					   strpos( strtolower( $order['customer_name'] ?? '' ), $search_lower ) !== false ||
					   strpos( strtolower( $order['customer_email'] ?? '' ), $search_lower ) !== false ||
					   strpos( strtolower( $order['customer_phone'] ?? '' ), $search_lower ) !== false;
			} );
		}

		wp_send_json_success( array(
			'orders' => array_values( $orders ),
		) );
	}

	public static function get_employee_salary() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'wc-team-payroll' ) );
		}

		$user_id = intval( $_POST['user_id'] );

		$salary_type = get_user_meta( $user_id, '_wc_tp_salary_type', true ) ?: 'commission';
		$salary_amount = get_user_meta( $user_id, '_wc_tp_salary_amount', true ) ?: 0;
		$salary_frequency = get_user_meta( $user_id, '_wc_tp_salary_frequency', true ) ?: 'monthly';

		wp_send_json_success( array(
			'type' => $salary_type,
			'amount' => $salary_amount,
			'frequency' => $salary_frequency,
		) );
	}

	public static function get_salary_history() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'wc-team-payroll' ) );
		}

		$user_id = intval( $_POST['user_id'] );
		$start_date = isset( $_POST['start_date'] ) ? sanitize_text_field( $_POST['start_date'] ) : '';
		$end_date = isset( $_POST['end_date'] ) ? sanitize_text_field( $_POST['end_date'] ) : '';

		$history = get_user_meta( $user_id, '_wc_tp_salary_history', true );
		if ( ! is_array( $history ) ) {
			$history = array();
		}

		// Filter by date range
		if ( $start_date && $end_date ) {
			$start_timestamp = strtotime( $start_date );
			$end_timestamp = strtotime( $end_date . ' 23:59:59' );
			$history = array_filter( $history, function( $entry ) use ( $start_timestamp, $end_timestamp ) {
				$entry_time = strtotime( $entry['date'] ?? '' );
				return $entry_time >= $start_timestamp && $entry_time <= $end_timestamp;
			} );
		}

		wp_send_json_success( array(
			'history' => array_values( $history ),
		) );
	}

	public static function update_employee_salary() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'wc-team-payroll' ) );
		}

		$user_id = intval( $_POST['user_id'] );
		$salary_type = sanitize_text_field( $_POST['salary_type'] ?? '' );
		$salary_amount = floatval( $_POST['salary_amount'] ?? 0 );
		$salary_frequency = sanitize_text_field( $_POST['salary_frequency'] ?? 'monthly' );

		// Get existing history
		$history = get_user_meta( $user_id, '_wc_tp_salary_history', true );
		if ( ! is_array( $history ) ) {
			$history = array();
		}

		// Add new entry
		$history[] = array(
			'date' => current_time( 'Y-m-d H:i:s' ),
			'new_type' => $salary_type,
			'new_amount' => $salary_amount,
			'new_frequency' => $salary_frequency,
		);

		update_user_meta( $user_id, '_wc_tp_salary_history', $history );
		update_user_meta( $user_id, '_wc_tp_salary_type', $salary_type );
		update_user_meta( $user_id, '_wc_tp_salary_amount', $salary_amount );
		update_user_meta( $user_id, '_wc_tp_salary_frequency', $salary_frequency );

		wp_send_json_success( array(
			'message' => __( 'Salary updated', 'wc-team-payroll' ),
		) );
	}

	public static function get_employee_payments() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'wc-team-payroll' ) );
		}

		$user_id = intval( $_POST['user_id'] );
		$start_date = isset( $_POST['start_date'] ) ? sanitize_text_field( $_POST['start_date'] ) : '';
		$end_date = isset( $_POST['end_date'] ) ? sanitize_text_field( $_POST['end_date'] ) : '';

		$payments = get_user_meta( $user_id, '_wc_tp_payments', true );
		if ( ! is_array( $payments ) ) {
			$payments = array();
		}

		// Filter by date range
		if ( $start_date && $end_date ) {
			$start_timestamp = strtotime( $start_date );
			$end_timestamp = strtotime( $end_date . ' 23:59:59' );
			$payments = array_filter( $payments, function( $payment ) use ( $start_timestamp, $end_timestamp ) {
				$payment_time = strtotime( $payment['date'] ?? '' );
				return $payment_time >= $start_timestamp && $payment_time <= $end_timestamp;
			} );
		}

		wp_send_json_success( array(
			'payments' => array_values( $payments ),
		) );
	}

	public static function add_payment() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'wc-team-payroll' ) );
		}

		$user_id = intval( $_POST['user_id'] );
		$amount = floatval( $_POST['amount'] ?? 0 );
		$payment_date = sanitize_text_field( $_POST['payment_date'] ?? '' );
		$payment_method_id = sanitize_text_field( $_POST['payment_method_id'] ?? '' );

		// Get payment method name
		$payment_method_name = '';
		if ( $payment_method_id ) {
			$methods = get_user_meta( $user_id, '_wc_tp_payment_methods', true );
			if ( is_array( $methods ) ) {
				foreach ( $methods as $method ) {
					if ( $method['id'] === $payment_method_id ) {
						$payment_method_name = $method['method_name'] . ' - ' . $method['method_details'];
						break;
					}
				}
			}
		}

		$payments = get_user_meta( $user_id, '_wc_tp_payments', true );
		if ( ! is_array( $payments ) ) {
			$payments = array();
		}

		$payments[] = array(
			'id' => uniqid(),
			'date' => $payment_date,
			'amount' => $amount,
			'payment_method' => $payment_method_name,
			'added_by' => wp_get_current_user()->display_name,
		);

		update_user_meta( $user_id, '_wc_tp_payments', $payments );

		wp_send_json_success( array(
			'message' => __( 'Payment added', 'wc-team-payroll' ),
		) );
	}

	public static function delete_payment() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'wc-team-payroll' ) );
		}

		$user_id = intval( $_POST['user_id'] );
		$payment_id = sanitize_text_field( $_POST['payment_id'] ?? '' );

		$payments = get_user_meta( $user_id, '_wc_tp_payments', true );
		if ( ! is_array( $payments ) ) {
			$payments = array();
		}

		$payments = array_filter( $payments, function( $payment ) use ( $payment_id ) {
			return ( $payment['id'] ?? '' ) !== $payment_id;
		} );

		update_user_meta( $user_id, '_wc_tp_payments', array_values( $payments ) );

		wp_send_json_success( array(
			'message' => __( 'Payment deleted', 'wc-team-payroll' ),
		) );
	}

	public static function get_payment_methods() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'wc-team-payroll' ) );
		}

		$user_id = intval( $_POST['user_id'] );

		$methods = get_user_meta( $user_id, '_wc_tp_payment_methods', true );
		if ( ! is_array( $methods ) ) {
			$methods = array();
		}

		wp_send_json_success( array(
			'methods' => $methods,
		) );
	}

	public static function add_payment_method() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'wc-team-payroll' ) );
		}

		$user_id = intval( $_POST['user_id'] );
		$method_name = sanitize_text_field( $_POST['method_name'] ?? '' );
		$method_details = sanitize_text_field( $_POST['method_details'] ?? '' );
		$note = sanitize_text_field( $_POST['note'] ?? '' );

		$methods = get_user_meta( $user_id, '_wc_tp_payment_methods', true );
		if ( ! is_array( $methods ) ) {
			$methods = array();
		}

		$methods[] = array(
			'id' => uniqid(),
			'method_name' => $method_name,
			'method_details' => $method_details,
			'note' => $note,
			'added_date' => current_time( 'Y-m-d H:i:s' ),
		);

		update_user_meta( $user_id, '_wc_tp_payment_methods', $methods );

		wp_send_json_success( array(
			'message' => __( 'Payment method added', 'wc-team-payroll' ),
		) );
	}

	public static function delete_payment_method() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'wc-team-payroll' ) );
		}

		$user_id = intval( $_POST['user_id'] );
		$method_id = sanitize_text_field( $_POST['method_id'] ?? '' );

		$methods = get_user_meta( $user_id, '_wc_tp_payment_methods', true );
		if ( ! is_array( $methods ) ) {
			$methods = array();
		}

		$methods = array_filter( $methods, function( $method ) use ( $method_id ) {
			return ( $method['id'] ?? '' ) !== $method_id;
		} );

		update_user_meta( $user_id, '_wc_tp_payment_methods', array_values( $methods ) );

		wp_send_json_success( array(
			'message' => __( 'Payment method deleted', 'wc-team-payroll' ),
		) );
	}

	/**
	 * Get all employees for dropdown
	 */
	public static function get_all_employees() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'wc-team-payroll' ) );
		}

		$args = array(
			'role' => array( 'shop_manager', 'woocommerce_manager', 'administrator' ),
			'orderby' => 'display_name',
			'order' => 'ASC',
		);

		$users = get_users( $args );
		$employees = array();

		foreach ( $users as $user ) {
			$employees[] = array(
				'id' => $user->ID,
				'name' => $user->display_name,
				'email' => $user->user_email,
			);
		}

		wp_send_json_success( array(
			'employees' => $employees,
		) );
	}

	/**
	 * Get payments table with filtering and pagination
	 */
	public static function get_payments_table() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'wc-team-payroll' ) );
		}

		$page = intval( $_POST['page'] ?? 1 );
		$per_page = intval( $_POST['per_page'] ?? 20 );
		$search = sanitize_text_field( $_POST['search'] ?? '' );
		$start_date = sanitize_text_field( $_POST['start_date'] ?? '' );
		$end_date = sanitize_text_field( $_POST['end_date'] ?? '' );
		$payment_method = sanitize_text_field( $_POST['payment_method'] ?? '' );
		$sort_by = sanitize_text_field( $_POST['sort_by'] ?? 'date' );
		$sort_order = sanitize_text_field( $_POST['sort_order'] ?? 'desc' );

		// Get all users
		$args = array(
			'role' => array( 'shop_manager', 'woocommerce_manager', 'administrator' ),
			'orderby' => 'display_name',
			'order' => 'ASC',
			'number' => -1,
		);

		$users = get_users( $args );
		$all_payments = array();

		// Collect all payments from all users
		foreach ( $users as $user ) {
			$payments = get_user_meta( $user->ID, '_wc_tp_payments', true );
			if ( ! is_array( $payments ) ) {
				continue;
			}

			foreach ( $payments as $payment ) {
				$payment['user_id'] = $user->ID;
				$payment['user_name'] = $user->display_name;
				$payment['user_email'] = $user->user_email;
				$all_payments[] = $payment;
			}
		}

		// Filter by search
		if ( $search ) {
			$search_lower = strtolower( $search );
			$all_payments = array_filter( $all_payments, function( $payment ) use ( $search_lower ) {
				return strpos( strtolower( $payment['user_name'] ?? '' ), $search_lower ) !== false ||
					   strpos( strtolower( $payment['user_email'] ?? '' ), $search_lower ) !== false;
			} );
		}

		// Filter by date range
		if ( $start_date && $end_date ) {
			$start_timestamp = strtotime( $start_date );
			$end_timestamp = strtotime( $end_date . ' 23:59:59' );
			$all_payments = array_filter( $all_payments, function( $payment ) use ( $start_timestamp, $end_timestamp ) {
				$payment_time = strtotime( $payment['date'] ?? '' );
				return $payment_time >= $start_timestamp && $payment_time <= $end_timestamp;
			} );
		}

		// Filter by payment method
		if ( $payment_method ) {
			$all_payments = array_filter( $all_payments, function( $payment ) use ( $payment_method ) {
				return strpos( $payment['payment_method'] ?? '', $payment_method ) !== false;
			} );
		}

		// Sort
		usort( $all_payments, function( $a, $b ) use ( $sort_by, $sort_order ) {
			$a_val = $a[ $sort_by ] ?? '';
			$b_val = $b[ $sort_by ] ?? '';

			if ( $sort_by === 'date' || $sort_by === 'amount' ) {
				$a_val = $sort_by === 'date' ? strtotime( $a_val ) : floatval( $a_val );
				$b_val = $sort_by === 'date' ? strtotime( $b_val ) : floatval( $b_val );
				$cmp = $a_val <=> $b_val;
			} else {
				$cmp = strcasecmp( $a_val, $b_val );
			}

			return $sort_order === 'asc' ? $cmp : -$cmp;
		} );

		// Pagination
		$total = count( $all_payments );
		$offset = ( $page - 1 ) * $per_page;
		$payments_page = array_slice( $all_payments, $offset, $per_page );

		// Format for display
		$currency_symbol = get_woocommerce_currency_symbol();
		$formatted_payments = array();

		foreach ( $payments_page as $payment ) {
			$formatted_payments[] = array(
				'id' => $payment['id'] ?? '',
				'user_id' => $payment['user_id'] ?? '',
				'user_name' => $payment['user_name'] ?? '',
				'user_email' => $payment['user_email'] ?? '',
				'date' => $payment['date'] ?? '',
				'date_formatted' => wp_date( 'M d, Y H:i', strtotime( $payment['date'] ?? '' ) ),
				'amount' => $payment['amount'] ?? 0,
				'amount_formatted' => $currency_symbol . number_format( floatval( $payment['amount'] ?? 0 ), 2 ),
				'payment_method' => $payment['payment_method'] ?? '',
				'added_by' => $payment['added_by'] ?? '',
			);
		}

		wp_send_json_success( array(
			'payments' => $formatted_payments,
			'total' => $total,
			'page' => $page,
			'per_page' => $per_page,
			'total_pages' => ceil( $total / $per_page ),
		) );
	}

	/**
	 * Get all unique payment methods across all employees
	 */
	public static function get_all_payment_methods() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'wc-team-payroll' ) );
		}

		$args = array(
			'role' => array( 'shop_manager', 'woocommerce_manager', 'administrator' ),
			'number' => -1,
		);

		$users = get_users( $args );
		$all_methods = array();
		$method_names = array();

		foreach ( $users as $user ) {
			$methods = get_user_meta( $user->ID, '_wc_tp_payment_methods', true );
			if ( ! is_array( $methods ) ) {
				continue;
			}

			foreach ( $methods as $method ) {
				$method_name = $method['method_name'] ?? '';
				if ( $method_name && ! in_array( $method_name, $method_names, true ) ) {
					$method_names[] = $method_name;
					$all_methods[] = array(
						'name' => $method_name,
					);
				}
			}
		}

		wp_send_json_success( array(
			'methods' => $all_methods,
		) );
	}
}

// Initialize AJAX handlers
add_action( 'plugins_loaded', function() {
	WC_Team_Payroll_AJAX_Handlers::init();
} );
