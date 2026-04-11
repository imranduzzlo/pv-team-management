<?php
/**
 * Employee Management - Salary, Bonuses, History
 */

class WC_Team_Payroll_Employee_Management {

	public function __construct() {
		// Enqueue common CSS and JS on admin pages
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue common CSS and JS
	 */
	public function enqueue_assets( $hook ) {
		// Only load on employee management page
		if ( strpos( $hook, 'wc-team-payroll-employees' ) === false ) {
			return;
		}

		wp_enqueue_style( 'wc-tp-common-css', WC_TEAM_PAYROLL_URL . 'assets/css/common.css', array(), WC_TEAM_PAYROLL_VERSION );
		wp_enqueue_script( 'wc-tp-common-js', WC_TEAM_PAYROLL_URL . 'assets/js/common.js', array( 'jquery' ), WC_TEAM_PAYROLL_VERSION, true );
		wp_enqueue_script( 'wc-tp-employees-js', WC_TEAM_PAYROLL_URL . 'assets/js/employees.js', array( 'jquery', 'wc-tp-common-js' ), WC_TEAM_PAYROLL_VERSION, true );
	}

	public function render_employees_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized', 'wc-team-payroll' ) );
		}

		?>
		<div class="wc-tp-page-wrapper">
			<h1><?php esc_html_e( 'Team Members', 'wc-team-payroll' ); ?></h1>

			<!-- Search Filter -->
			<div class="wc-tp-search-filter">
				<input type="text" id="wc-tp-employees-search" placeholder="<?php esc_attr_e( 'Search by name, email, or vb_user_id...', 'wc-team-payroll' ); ?>" />
				<button type="button" class="button button-secondary" id="wc-tp-employees-search-clear"><?php esc_html_e( 'Clear', 'wc-team-payroll' ); ?></button>
			</div>

			<!-- Unified Filter Section -->
			<div class="wc-tp-unified-filter">
				<div class="wc-tp-filter-row">
					<!-- Date Range Preset -->
					<div class="wc-tp-filter-group">
						<label><?php esc_html_e( 'Employee Created:', 'wc-team-payroll' ); ?></label>
						<select id="wc-tp-employees-date-preset">
							<option value="all-time"><?php esc_html_e( 'All Time', 'wc-team-payroll' ); ?></option>
							<option value="today"><?php esc_html_e( 'Today', 'wc-team-payroll' ); ?></option>
							<option value="this-week"><?php esc_html_e( 'This Week', 'wc-team-payroll' ); ?></option>
							<option value="this-month" selected><?php esc_html_e( 'This Month', 'wc-team-payroll' ); ?></option>
							<option value="this-year"><?php esc_html_e( 'This Year', 'wc-team-payroll' ); ?></option>
							<option value="last-week"><?php esc_html_e( 'Last Week', 'wc-team-payroll' ); ?></option>
							<option value="last-month"><?php esc_html_e( 'Last Month', 'wc-team-payroll' ); ?></option>
							<option value="last-year"><?php esc_html_e( 'Last Year', 'wc-team-payroll' ); ?></option>
							<option value="last-6-months"><?php esc_html_e( 'Last 6 Months', 'wc-team-payroll' ); ?></option>
							<option value="custom"><?php esc_html_e( 'Custom', 'wc-team-payroll' ); ?></option>
						</select>
					</div>

					<!-- Custom Date Range (Hidden by default) -->
					<div class="wc-tp-filter-group" id="wc-tp-employees-custom-dates" style="display: none;">
						<label><?php esc_html_e( 'From:', 'wc-team-payroll' ); ?></label>
						<input type="date" id="wc-tp-employees-start-date" />
					</div>

					<div class="wc-tp-filter-group" id="wc-tp-employees-custom-dates-end" style="display: none;">
						<label><?php esc_html_e( 'To:', 'wc-team-payroll' ); ?></label>
						<input type="date" id="wc-tp-employees-end-date" />
					</div>

					<!-- Salary Type Filter -->
					<div class="wc-tp-filter-group">
						<label><?php esc_html_e( 'Salary Type:', 'wc-team-payroll' ); ?></label>
						<select id="wc-tp-salary-type-filter">
							<option value=""><?php esc_html_e( 'All Types', 'wc-team-payroll' ); ?></option>
							<option value="commission"><?php esc_html_e( 'Commission Based', 'wc-team-payroll' ); ?></option>
							<option value="fixed"><?php esc_html_e( 'Fixed Salary', 'wc-team-payroll' ); ?></option>
							<option value="combined"><?php esc_html_e( 'Combined (Base + Commission)', 'wc-team-payroll' ); ?></option>
						</select>
					</div>

					<!-- Filter Button -->
					<button type="button" class="button button-primary" id="wc-tp-employees-filter-btn"><?php esc_html_e( 'Filter', 'wc-team-payroll' ); ?></button>
				</div>
			</div>

			<!-- Employees Table Section -->
			<div class="wc-tp-table-section" id="wc-tp-employees-table-section">
				<div class="wc-tp-table-header">
					<h2><?php esc_html_e( 'Team Members', 'wc-team-payroll' ); ?></h2>
					<div class="wc-tp-items-per-page">
						<label for="wc-tp-employees-per-page"><?php esc_html_e( 'Items per page:', 'wc-team-payroll' ); ?></label>
						<select id="wc-tp-employees-per-page">
							<option value="10">10</option>
							<option value="20" selected>20</option>
							<option value="30">30</option>
							<option value="50">50</option>
							<option value="100">100</option>
						</select>
					</div>
				</div>
				<div id="wc-tp-employees-table-container">
					<!-- Content will be loaded via AJAX -->
				</div>
				<!-- Pagination -->
				<div id="wc-tp-employees-pagination" style="margin-top: 20px;"></div>
			</div>
		</div>
		<?php
	}

	public function ajax_update_employee_salary() {
		check_ajax_referer( 'wc_team_payroll_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'wc-team-payroll' ) );
		}

		$user_id = intval( $_POST['user_id'] );
		$salary_type = sanitize_text_field( $_POST['salary_type'] );
		$salary_amount = floatval( $_POST['salary_amount'] );
		$salary_frequency = sanitize_text_field( $_POST['salary_frequency'] );

		$old_type = get_user_meta( $user_id, '_wc_tp_fixed_salary', true );
		$old_combined = get_user_meta( $user_id, '_wc_tp_combined_salary', true );
		$old_amount = get_user_meta( $user_id, '_wc_tp_salary_amount', true );
		$old_frequency = get_user_meta( $user_id, '_wc_tp_salary_frequency', true );

		$old_salary_type = 'commission';
		if ( $old_type ) {
			$old_salary_type = 'fixed';
		} elseif ( $old_combined ) {
			$old_salary_type = 'combined';
		}

		if ( 'fixed' === $salary_type ) {
			update_user_meta( $user_id, '_wc_tp_fixed_salary', 1 );
			update_user_meta( $user_id, '_wc_tp_combined_salary', 0 );
			update_user_meta( $user_id, '_wc_tp_salary_amount', $salary_amount );
			update_user_meta( $user_id, '_wc_tp_salary_frequency', $salary_frequency );
		} elseif ( 'combined' === $salary_type ) {
			update_user_meta( $user_id, '_wc_tp_fixed_salary', 0 );
			update_user_meta( $user_id, '_wc_tp_combined_salary', 1 );
			update_user_meta( $user_id, '_wc_tp_salary_amount', $salary_amount );
			update_user_meta( $user_id, '_wc_tp_salary_frequency', $salary_frequency );
		} else {
			update_user_meta( $user_id, '_wc_tp_fixed_salary', 0 );
			update_user_meta( $user_id, '_wc_tp_combined_salary', 0 );
			delete_user_meta( $user_id, '_wc_tp_salary_amount' );
			delete_user_meta( $user_id, '_wc_tp_salary_frequency' );
		}

		$this->add_salary_history( $user_id, $old_salary_type, $old_amount, $old_frequency, $salary_type, $salary_amount, $salary_frequency );

		wp_send_json_success( array(
			'message' => __( 'Employee salary updated', 'wc-team-payroll' ),
		) );
	}

	private function add_salary_history( $user_id, $old_type, $old_amount, $old_frequency, $new_type, $new_amount, $new_frequency ) {
		$history = get_user_meta( $user_id, '_wc_tp_salary_history', true );
		if ( ! is_array( $history ) ) {
			$history = array();
		}

		// Only add to history if the type actually changed
		if ( $old_type !== $new_type ) {
			$history[] = array(
				'date'           => current_time( 'mysql' ),
				'old_type'       => $old_type,
				'old_amount'     => $old_amount,
				'old_frequency'  => $old_frequency,
				'new_type'       => $new_type,
				'new_amount'     => $new_amount,
				'new_frequency'  => $new_frequency,
				'changed_by'     => get_current_user_id(),
			);

			update_user_meta( $user_id, '_wc_tp_salary_history', $history );

			// Recalculate commissions for all orders involving this user
			$this->recalculate_user_commissions( $user_id );
		}
	}

	/**
	 * Recalculate commissions for all orders involving a user
	 */
	private function recalculate_user_commissions( $user_id ) {
		$core_engine = new WC_Team_Payroll_Core_Engine();

		// Get all orders where this user is agent or processor
		$args = array(
			'limit'  => -1,
			'status' => array( 'completed', 'processing', 'refunded' ),
		);

		$orders = wc_get_orders( $args );

		foreach ( $orders as $order ) {
			$agent_id = $order->get_meta( '_primary_agent_id' );
			$processor_id = $order->get_meta( '_processor_user_id' );

			// Check if this user is involved in this order
			if ( intval( $agent_id ) === intval( $user_id ) || intval( $processor_id ) === intval( $user_id ) ) {
				// Recalculate and update the commission
				$commission_data = $core_engine->calculate_commission( $order, $agent_id, $processor_id );
				$order->update_meta_data( '_commission_data', $commission_data );
				$order->save();
			}
		}
	}

	public function ajax_add_payment() {
		check_ajax_referer( 'wc_team_payroll_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'wc-team-payroll' ) );
		}

		$user_id = intval( $_POST['user_id'] );
		$amount = floatval( $_POST['amount'] );
		$payment_date = sanitize_text_field( $_POST['payment_date'] );

		$payments = get_user_meta( $user_id, '_wc_tp_payments', true );
		if ( ! is_array( $payments ) ) {
			$payments = array();
		}

		$payments[] = array(
			'id'             => uniqid(),
			'amount'         => $amount,
			'date'           => $payment_date,
			'created_at'     => current_time( 'mysql' ),
			'created_by'     => get_current_user_id(),
			'status'         => 'completed',
		);

		update_user_meta( $user_id, '_wc_tp_payments', $payments );

		wp_send_json_success( array(
			'message' => __( 'Payment added', 'wc-team-payroll' ),
			'payments' => $payments,
		) );
	}

	public function ajax_delete_payment() {
		check_ajax_referer( 'wc_team_payroll_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'wc-team-payroll' ) );
		}

		$user_id = intval( $_POST['user_id'] );
		$payment_id = sanitize_text_field( $_POST['payment_id'] );

		$payments = get_user_meta( $user_id, '_wc_tp_payments', true );
		if ( ! is_array( $payments ) ) {
			wp_send_json_error( __( 'No payments found', 'wc-team-payroll' ) );
			return;
		}

		// Find and remove the payment
		foreach ( $payments as $key => $payment ) {
			if ( $payment['id'] === $payment_id ) {
				unset( $payments[ $key ] );
				break;
			}
		}

		// Re-index array
		$payments = array_values( $payments );

		update_user_meta( $user_id, '_wc_tp_payments', $payments );

		wp_send_json_success( array(
			'message' => __( 'Payment deleted', 'wc-team-payroll' ),
		) );
	}

	public function ajax_get_payment_data() {
		check_ajax_referer( 'wc_team_payroll_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'wc-team-payroll' ) );
		}

		$user_id = intval( $_POST['user_id'] );

		$payments = get_user_meta( $user_id, '_wc_tp_payments', true );
		if ( ! is_array( $payments ) ) {
			$payments = array();
		}

		wp_send_json_success( array(
			'payments' => $payments,
		) );
	}

	public function ajax_add_order_bonus() {
		check_ajax_referer( 'wc_team_payroll_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'wc-team-payroll' ) );
		}

		$order_id = intval( $_POST['order_id'] );
		$user_id = intval( $_POST['user_id'] );
		$bonus_amount = floatval( $_POST['bonus_amount'] );

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			wp_send_json_error( __( 'Order not found', 'wc-team-payroll' ) );
		}

		$bonuses = $order->get_meta( '_wc_tp_bonuses' );
		if ( ! is_array( $bonuses ) ) {
			$bonuses = array();
		}

		$bonuses[ $user_id ] = $bonus_amount;

		$order->update_meta_data( '_wc_tp_bonuses', $bonuses );
		$order->save();

		wp_send_json_success( array(
			'message' => __( 'Bonus added', 'wc-team-payroll' ),
		) );
	}

	public function get_user_total_paid( $user_id, $year = null, $month = null ) {
		if ( ! $year ) {
			$year = date( 'Y' );
		}

		if ( ! $month ) {
			$month = date( 'm' );
		}

		$payments = get_user_meta( $user_id, '_wc_tp_payments', true );
		if ( ! is_array( $payments ) ) {
			return 0;
		}

		$total_paid = 0;

		foreach ( $payments as $payment ) {
			$payment_date = new DateTime( $payment['date'] );
			$payment_year = $payment_date->format( 'Y' );
			$payment_month = $payment_date->format( 'm' );

			if ( $payment_year == $year && $payment_month == $month ) {
				$total_paid += $payment['amount'];
			}
		}

		return $total_paid;
	}

	public function get_salary_history( $user_id ) {
		$history = get_user_meta( $user_id, '_wc_tp_salary_history', true );
		return is_array( $history ) ? $history : array();
	}

	public function is_fixed_salary( $user_id ) {
		return (bool) get_user_meta( $user_id, '_wc_tp_fixed_salary', true );
	}

	public function is_combined_salary( $user_id ) {
		return (bool) get_user_meta( $user_id, '_wc_tp_combined_salary', true );
	}

	public function get_user_salary( $user_id ) {
		$is_fixed = $this->is_fixed_salary( $user_id );
		$is_combined = $this->is_combined_salary( $user_id );

		if ( ! $is_fixed && ! $is_combined ) {
			return null;
		}

		return array(
			'amount'    => floatval( get_user_meta( $user_id, '_wc_tp_salary_amount', true ) ),
			'frequency' => sanitize_text_field( get_user_meta( $user_id, '_wc_tp_salary_frequency', true ) ),
		);
	}
}
