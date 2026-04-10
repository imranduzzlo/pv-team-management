<?php
/**
 * Employee Management - Salary, Bonuses, History
 */

class WC_Team_Payroll_Employee_Management {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_employee_menu' ) );
		add_action( 'admin_init', array( $this, 'register_employee_settings' ) );
		add_action( 'wp_ajax_wc_tp_update_employee_salary', array( $this, 'ajax_update_employee_salary' ) );
		add_action( 'wp_ajax_wc_tp_add_payment', array( $this, 'ajax_add_payment' ) );
		add_action( 'wp_ajax_wc_tp_delete_payment', array( $this, 'ajax_delete_payment' ) );
		add_action( 'wp_ajax_wc_tp_add_order_bonus', array( $this, 'ajax_add_order_bonus' ) );
		add_action( 'wp_ajax_wc_tp_get_payment_data', array( $this, 'ajax_get_payment_data' ) );
	}

	public function add_employee_menu() {
		add_submenu_page(
			'wc-team-payroll',
			__( 'Team Members', 'wc-team-payroll' ),
			__( 'Team Members', 'wc-team-payroll' ),
			'manage_woocommerce',
			'wc-team-payroll-employees',
			array( $this, 'render_employees_page' )
		);
	}

	public function register_employee_settings() {
		register_setting( 'wc_team_payroll_settings_group', 'wc_team_payroll_employees' );
	}

	public function render_employees_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Unauthorized', 'wc-team-payroll' ) );
		}

		$employees = get_users( array(
			'role__in' => array( 'shop_employee', 'shop_manager', 'administrator' ),
			'orderby'  => 'display_name',
			'order'    => 'ASC',
		) );

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Team Members Management', 'wc-team-payroll' ); ?></h1>

			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Name', 'wc-team-payroll' ); ?></th>
						<th><?php esc_html_e( 'Email', 'wc-team-payroll' ); ?></th>
						<th><?php esc_html_e( 'Type', 'wc-team-payroll' ); ?></th>
						<th><?php esc_html_e( 'Salary/Commission', 'wc-team-payroll' ); ?></th>
						<th><?php esc_html_e( 'Action', 'wc-team-payroll' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $employees as $employee ) : ?>
						<tr>
							<td><?php echo esc_html( $employee->display_name ); ?></td>
							<td><?php echo esc_html( $employee->user_email ); ?></td>
							<td>
								<?php
								$is_fixed_salary = get_user_meta( $employee->ID, '_wc_tp_fixed_salary', true );
								$is_combined_salary = get_user_meta( $employee->ID, '_wc_tp_combined_salary', true );
								
								if ( $is_fixed_salary ) {
									echo esc_html__( 'Fixed Salary', 'wc-team-payroll' );
								} elseif ( $is_combined_salary ) {
									echo esc_html__( 'Combined (Base + Commission)', 'wc-team-payroll' );
								} else {
									echo esc_html__( 'Commission Based', 'wc-team-payroll' );
								}
								?>
							</td>
							<td>
								<?php
								if ( $is_fixed_salary || $is_combined_salary ) {
									$salary = get_user_meta( $employee->ID, '_wc_tp_salary_amount', true );
									$frequency = get_user_meta( $employee->ID, '_wc_tp_salary_frequency', true );
									echo wp_kses_post( wc_price( $salary ) . ' / ' . esc_html( $frequency ) );
								} else {
									echo esc_html__( 'Commission Based', 'wc-team-payroll' );
								}
								?>
							</td>
							<td>
								<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'wc-team-payroll-employee-detail', 'user_id' => $employee->ID ), admin_url( 'admin.php' ) ) ); ?>" class="button button-small"><?php esc_html_e( 'Manage', 'wc-team-payroll' ); ?></a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	public function ajax_update_employee_salary() {
		check_ajax_referer( 'wc_team_payroll_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'wc-team-payroll' ) );
		}

		$user_id = intval( $_POST['user_id'] );
		$salary_type = sanitize_text_field( $_POST['salary_type'] ); // 'fixed', 'commission', or 'combined'
		$salary_amount = floatval( $_POST['salary_amount'] );
		$salary_frequency = sanitize_text_field( $_POST['salary_frequency'] ); // 'daily', 'weekly', 'monthly', 'yearly'

		// Store old values for history
		$old_type = get_user_meta( $user_id, '_wc_tp_fixed_salary', true );
		$old_combined = get_user_meta( $user_id, '_wc_tp_combined_salary', true );
		$old_amount = get_user_meta( $user_id, '_wc_tp_salary_amount', true );
		$old_frequency = get_user_meta( $user_id, '_wc_tp_salary_frequency', true );

		// Determine old type
		$old_salary_type = 'commission';
		if ( $old_type ) {
			$old_salary_type = 'fixed';
		} elseif ( $old_combined ) {
			$old_salary_type = 'combined';
		}

		// Update user meta based on type
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
			// Commission based
			update_user_meta( $user_id, '_wc_tp_fixed_salary', 0 );
			update_user_meta( $user_id, '_wc_tp_combined_salary', 0 );
			delete_user_meta( $user_id, '_wc_tp_salary_amount' );
			delete_user_meta( $user_id, '_wc_tp_salary_frequency' );
		}

		// Add to history
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

		$history[] = array(
			'date'           => current_time( 'mysql' ),
			'old_type'       => $old_type ? 'fixed' : 'commission',
			'old_amount'     => $old_amount,
			'old_frequency'  => $old_frequency,
			'new_type'       => $new_type,
			'new_amount'     => $new_amount,
			'new_frequency'  => $new_frequency,
			'changed_by'     => get_current_user_id(),
		);

		update_user_meta( $user_id, '_wc_tp_salary_history', $history );
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

		$total_paid = self::get_user_total_paid( $user_id, $year, $month );

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

		// Store bonus per user (not shared with other employees)
		$order_bonuses = $order->get_meta( '_wc_tp_order_bonuses' );
		if ( ! is_array( $order_bonuses ) ) {
			$order_bonuses = array();
		}

		// Check if bonus already exists for this user on this order
		$bonus_exists = false;
		foreach ( $order_bonuses as $key => $bonus ) {
			if ( $bonus['user_id'] == $user_id ) {
				// Update existing bonus for this user
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

		// Add new bonus if doesn't exist for this user
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

	/**
	 * Get total paid for user
	 */
	public static function get_user_total_paid( $user_id, $year = null, $month = null ) {
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

	/**
	 * Get salary history for user
	 */
	public static function get_salary_history( $user_id ) {
		$history = get_user_meta( $user_id, '_wc_tp_salary_history', true );
		return is_array( $history ) ? $history : array();
	}

	/**
	 * Check if user is fixed salary
	 */
	public static function is_fixed_salary( $user_id ) {
		return (bool) get_user_meta( $user_id, '_wc_tp_fixed_salary', true );
	}

	/**
	 * Check if user is combined salary
	 */
	public static function is_combined_salary( $user_id ) {
		return (bool) get_user_meta( $user_id, '_wc_tp_combined_salary', true );
	}

	/**
	 * Get user salary info
	 */
	public static function get_user_salary( $user_id ) {
		$is_fixed = self::is_fixed_salary( $user_id );
		$is_combined = self::is_combined_salary( $user_id );

		if ( ! $is_fixed && ! $is_combined ) {
			return null;
		}

		return array(
			'amount'    => floatval( get_user_meta( $user_id, '_wc_tp_salary_amount', true ) ),
			'frequency' => sanitize_text_field( get_user_meta( $user_id, '_wc_tp_salary_frequency', true ) ),
		);
	}
}
