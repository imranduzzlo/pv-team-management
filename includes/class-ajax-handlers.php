<?php
/**
 * AJAX Handlers
 */

class WC_Team_Payroll_AJAX_Handlers {

	public static function init() {
		add_action( 'wp_ajax_wc_team_payroll_mark_paid', array( __CLASS__, 'mark_payroll_paid' ) );
		add_action( 'wp_ajax_wc_team_payroll_get_employee_orders', array( __CLASS__, 'get_employee_orders' ) );
	}

	public static function mark_payroll_paid() {
		check_ajax_referer( 'wc_team_payroll_nonce', 'nonce' );

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
		check_ajax_referer( 'wc_team_payroll_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'wc-team-payroll' ) );
		}

		$user_id = intval( $_POST['user_id'] );
		$page = isset( $_POST['page'] ) ? intval( $_POST['page'] ) : 1;
		$per_page = 20;

		$earnings = WC_Team_Payroll_Core_Engine::get_user_earnings( $user_id );

		$total = count( $earnings['orders'] );
		$orders = array_slice( $earnings['orders'], ( $page - 1 ) * $per_page, $per_page );

		wp_send_json_success( array(
			'orders' => $orders,
			'total' => $total,
			'pages' => ceil( $total / $per_page ),
			'current_page' => $page,
		) );
	}
}

// Initialize AJAX handlers
add_action( 'plugins_loaded', function() {
	WC_Team_Payroll_AJAX_Handlers::init();
} );
