<?php
/**
 * Employee Management - Salary, Bonuses, History
 */

class WC_Team_Payroll_Employee_Management {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue assets for employee management page
	 */
	public function enqueue_assets() {
		$screen = get_current_screen();
		if ( ! $screen || 'woocommerce_page_wc-team-payroll-employees' !== $screen->id ) {
			return;
		}

		// Enqueue common CSS
		wp_enqueue_style( 'wc-tp-common', plugin_dir_url( __FILE__ ) . '../../assets/css/common.css', array(), '5.6.2' );

		// Enqueue employees-specific CSS
		wp_enqueue_style( 'wc-tp-employees', plugin_dir_url( __FILE__ ) . '../../assets/css/employees.css', array( 'wc-tp-common' ), '5.6.2' );

		// Enqueue common JS
		wp_enqueue_script( 'wc-tp-common', plugin_dir_url( __FILE__ ) . '../../assets/js/common.js', array( 'jquery' ), '5.6.2', true );

		// Enqueue employees-specific JS
		wp_enqueue_script( 'wc-tp-employees', plugin_dir_url( __FILE__ ) . '../../assets/js/employees.js', array( 'jquery', 'wc-tp-common' ), '5.6.2', true );
	}

	public function render_employees_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized', 'wc-team-payroll' ) );
		}

		?>
		<div class="wrap wc-team-payroll-employees">
			<h1><?php esc_html_e( 'Team Members', 'wc-team-payroll' ); ?></h1>

			<!-- Search Filter -->
			<div class="wc-tp-search-filter">
				<input type="text" id="wc-tp-employees-search" placeholder="<?php esc_attr_e( 'Search by name, email, or vb_user_id...', 'wc-team-payroll' ); ?>" />
				<button type="button" class="button button-secondary" id="wc-tp-employees-search-clear"><?php esc_html_e( 'Clear', 'wc-team-payroll' ); ?></button>
			</div>

			<!-- Salary Type Filter -->
			<div class="wc-tp-salary-filter">
				<label><?php esc_html_e( 'Salary Type:', 'wc-team-payroll' ); ?></label>
				<select id="wc-tp-salary-type-filter">
					<option value=""><?php esc_html_e( 'All Types', 'wc-team-payroll' ); ?></option>
					<option value="commission"><?php esc_html_e( 'Commission Based', 'wc-team-payroll' ); ?></option>
					<option value="fixed"><?php esc_html_e( 'Fixed Salary', 'wc-team-payroll' ); ?></option>
					<option value="combined"><?php esc_html_e( 'Combined (Base + Commission)', 'wc-team-payroll' ); ?></option>
				</select>
			</div>

			<!-- Employee Creation Date Filter -->
			<div class="wc-tp-date-filter">
				<label><?php esc_html_e( 'Employee Created:', 'wc-team-payroll' ); ?></label>
				<input type="date" id="wc-tp-employees-start-date" />
				<span class="wc-tp-date-separator">to</span>
				<input type="date" id="wc-tp-employees-end-date" />
				<button type="button" class="button button-secondary" id="wc-tp-employees-date-clear"><?php esc_html_e( 'Clear Dates', 'wc-team-payroll' ); ?></button>
			</div>

			<!-- Employees Table Section -->
			<div class="wc-tp-table-section" id="wc-tp-employees-table-section">
				<div class="wc-tp-items-per-page">
					<h2><?php esc_html_e( 'Team Members', 'wc-team-payroll' ); ?></h2>
					<div>
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
				<div id="wc-tp-employees-pagination"></div>
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
		}

		$payments = array_filter( $payments, function( $p ) use ( $payment_id ) {
			return $p['id'] !== $payment_id;
		} );

		update_user_meta( $user_id, '_wc_tp_payments', array_values( $payments ) );

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
		$year = isset( $_POST['year'] ) ? intval( $_POST['year'] ) : date( 'Y' );
		$month = isset( $_POST['month'] ) ? intval( $_POST['month'] ) : date( 'm' );

		$payments = get_user_meta( $user_id, '_wc_tp_payments', true );
		if ( ! is_array( $payments ) ) {
			$payments = array();
		}

		$total_paid = $this->get_user_total_paid( $user_id, $year, $month );

		wp_send_json_success( array(
			'payments'   => $payments,
			'total_paid' => $total_paid,
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
		$bonus_reason = sanitize_text_field( $_POST['bonus_reason'] );

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			wp_send_json_error( __( 'Order not found', 'wc-team-payroll' ) );
		}

		$order_bonuses = $order->get_meta( '_wc_tp_order_bonuses' );
		if ( ! is_array( $order_bonuses ) ) {
			$order_bonuses = array();
		}

		$bonus_exists = false;
		foreach ( $order_bonuses as $key => $bonus ) {
			if ( $bonus['user_id'] == $user_id ) {
				$order_bonuses[ $key ] = array(
					'user_id'    => $user_id,
					'amount'     => $bonus_amount,
					'reason'     => $bonus_reason,
					'created_at' => current_time( 'mysql' ),
					'created_by' => get_current_user_id(),
				);
				$bonus_exists = true;
				break;
			}
		}

		if ( ! $bonus_exists ) {
			$order_bonuses[] = array(
				'user_id'    => $user_id,
				'amount'     => $bonus_amount,
				'reason'     => $bonus_reason,
				'created_at' => current_time( 'mysql' ),
				'created_by' => get_current_user_id(),
			);
		}

		$order->update_meta_data( '_wc_tp_order_bonuses', $order_bonuses );
		$order->save();

		wp_send_json_success( array(
			'message' => __( 'Order bonus added for this employee only', 'wc-team-payroll' ),
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
