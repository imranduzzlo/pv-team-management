<?php
/**
 * Employee Detail Page - Salary, Payments, Bonuses, History
 */

class WC_Team_Payroll_Employee_Detail {

	public function render_employee_detail() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized', 'wc-team-payroll' ) );
		}

		$user_id = isset( $_GET['user_id'] ) ? intval( $_GET['user_id'] ) : 0;
		if ( ! $user_id ) {
			wp_die( esc_html__( 'User not found', 'wc-team-payroll' ) );
		}

		$user = get_user_by( 'ID', $user_id );
		if ( ! $user ) {
			wp_die( esc_html__( 'User not found', 'wc-team-payroll' ) );
		}

		$employee_mgmt = new WC_Team_Payroll_Employee_Management();
		$is_fixed_salary = $employee_mgmt->is_fixed_salary( $user_id );
		$is_combined_salary = $employee_mgmt->is_combined_salary( $user_id );
		$salary_info = $employee_mgmt->get_user_salary( $user_id );
		$salary_history = $employee_mgmt->get_salary_history( $user_id );
		$payments = get_user_meta( $user_id, '_wc_tp_payments', true );
		if ( ! is_array( $payments ) ) {
			$payments = array();
		}

		$year = isset( $_GET['year'] ) ? intval( $_GET['year'] ) : date( 'Y' );
		$month = isset( $_GET['month'] ) ? intval( $_GET['month'] ) : date( 'm' );

		$total_paid = $employee_mgmt->get_user_total_paid( $user_id, $year, $month );

		?>
		<div class="wrap">
			<h1><?php echo esc_html( $user->display_name ); ?> - <?php esc_html_e( 'Employee Detail', 'wc-team-payroll' ); ?></h1>

			<!-- Salary Section -->
			<h2><?php esc_html_e( 'Salary Management', 'wc-team-payroll' ); ?></h2>
			<table class="form-table">
				<tr>
					<th scope="row">
						<label><?php esc_html_e( 'Salary Type', 'wc-team-payroll' ); ?></label>
					</th>
					<td>
						<select id="salary_type" onchange="toggleSalaryFields()">
							<option value="commission" <?php selected( ! $is_fixed_salary && ! $is_combined_salary, true ); ?>><?php esc_html_e( 'Commission Based', 'wc-team-payroll' ); ?></option>
							<option value="fixed" <?php selected( $is_fixed_salary, true ); ?>><?php esc_html_e( 'Fixed Salary', 'wc-team-payroll' ); ?></option>
							<option value="combined" <?php selected( $is_combined_salary, true ); ?>><?php esc_html_e( 'Combined (Base Salary + Commission)', 'wc-team-payroll' ); ?></option>
						</select>
					</td>
				</tr>

				<tr id="salary_amount_row" style="<?php echo ( $is_fixed_salary || $is_combined_salary ) ? '' : 'display:none;'; ?>">
					<th scope="row">
						<label for="salary_amount"><?php esc_html_e( 'Salary Amount', 'wc-team-payroll' ); ?></label>
					</th>
					<td>
						<input type="number" id="salary_amount" value="<?php echo esc_attr( $salary_info ? $salary_info['amount'] : 0 ); ?>" step="0.01" min="0" />
					</td>
				</tr>

				<tr id="salary_frequency_row" style="<?php echo ( $is_fixed_salary || $is_combined_salary ) ? '' : 'display:none;'; ?>">
					<th scope="row">
						<label for="salary_frequency"><?php esc_html_e( 'Salary Frequency', 'wc-team-payroll' ); ?></label>
					</th>
					<td>
						<select id="salary_frequency">
							<option value="daily" <?php selected( $salary_info ? $salary_info['frequency'] : '', 'daily' ); ?>><?php esc_html_e( 'Daily', 'wc-team-payroll' ); ?></option>
							<option value="weekly" <?php selected( $salary_info ? $salary_info['frequency'] : '', 'weekly' ); ?>><?php esc_html_e( 'Weekly', 'wc-team-payroll' ); ?></option>
							<option value="monthly" <?php selected( $salary_info ? $salary_info['frequency'] : '', 'monthly' ); ?>><?php esc_html_e( 'Monthly', 'wc-team-payroll' ); ?></option>
							<option value="yearly" <?php selected( $salary_info ? $salary_info['frequency'] : '', 'yearly' ); ?>><?php esc_html_e( 'Yearly', 'wc-team-payroll' ); ?></option>
						</select>
					</td>
				</tr>

				<tr>
					<td colspan="2">
						<button type="button" class="button button-primary" onclick="updateEmployeeSalary(<?php echo esc_attr( $user_id ); ?>)"><?php esc_html_e( 'Update Salary', 'wc-team-payroll' ); ?></button>
					</td>
				</tr>
			</table>

			<!-- Salary History -->
			<h2><?php esc_html_e( 'Salary History', 'wc-team-payroll' ); ?></h2>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Date', 'wc-team-payroll' ); ?></th>
						<th><?php esc_html_e( 'Old Type', 'wc-team-payroll' ); ?></th>
						<th><?php esc_html_e( 'Old Amount', 'wc-team-payroll' ); ?></th>
						<th><?php esc_html_e( 'New Type', 'wc-team-payroll' ); ?></th>
						<th><?php esc_html_e( 'New Amount', 'wc-team-payroll' ); ?></th>
						<th><?php esc_html_e( 'Changed By', 'wc-team-payroll' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $salary_history as $history ) : ?>
						<tr>
							<td><?php echo esc_html( $history['date'] ); ?></td>
							<td><?php echo esc_html( ucfirst( $history['old_type'] ) ); ?></td>
							<td><?php echo wp_kses_post( wc_price( $history['old_amount'] ) ); ?></td>
							<td><?php echo esc_html( ucfirst( $history['new_type'] ) ); ?></td>
							<td><?php echo wp_kses_post( wc_price( $history['new_amount'] ) ); ?></td>
							<td><?php echo esc_html( get_user_by( 'ID', $history['changed_by'] )->display_name ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<!-- Payments Section -->
			<h2><?php esc_html_e( 'Payments', 'wc-team-payroll' ); ?></h2>
			<div class="wc-tp-filters">
				<form method="get">
					<input type="hidden" name="page" value="wc-team-payroll-employee-detail" />
					<input type="hidden" name="user_id" value="<?php echo esc_attr( $user_id ); ?>" />
					<select name="month">
						<?php for ( $m = 1; $m <= 12; $m++ ) : ?>
							<option value="<?php echo esc_attr( $m ); ?>" <?php selected( $month, $m ); ?>><?php echo esc_html( date( 'F', mktime( 0, 0, 0, $m, 1 ) ) ); ?></option>
						<?php endfor; ?>
					</select>
					<input type="number" name="year" value="<?php echo esc_attr( $year ); ?>" min="2020" max="2099" />
					<button type="submit" class="button"><?php esc_html_e( 'Filter', 'wc-team-payroll' ); ?></button>
				</form>
			</div>

			<div class="wc-tp-payment-summary">
				<div class="payment-card">
					<span class="label"><?php esc_html_e( 'Total Paid', 'wc-team-payroll' ); ?></span>
					<span class="amount"><?php echo wp_kses_post( wc_price( $total_paid ) ); ?></span>
				</div>
			</div>

			<!-- Add Payment Form -->
			<h3><?php esc_html_e( 'Add Payment', 'wc-team-payroll' ); ?></h3>
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="payment_amount"><?php esc_html_e( 'Amount', 'wc-team-payroll' ); ?></label>
					</th>
					<td>
						<input type="number" id="payment_amount" step="0.01" min="0" />
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="payment_date"><?php esc_html_e( 'Payment Date', 'wc-team-payroll' ); ?></label>
					</th>
					<td>
						<input type="datetime-local" id="payment_date" value="<?php echo esc_attr( date( 'Y-m-d\TH:i' ) ); ?>" />
					</td>
				</tr>

				<tr>
					<td colspan="2">
						<button type="button" class="button button-primary" onclick="addPayment(<?php echo esc_attr( $user_id ); ?>)"><?php esc_html_e( 'Add Payment', 'wc-team-payroll' ); ?></button>
					</td>
				</tr>
			</table>

			<!-- Payments List -->
			<h3><?php esc_html_e( 'Payment History', 'wc-team-payroll' ); ?></h3>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Date', 'wc-team-payroll' ); ?></th>
						<th><?php esc_html_e( 'Amount', 'wc-team-payroll' ); ?></th>
						<th><?php esc_html_e( 'Added By', 'wc-team-payroll' ); ?></th>
						<th><?php esc_html_e( 'Action', 'wc-team-payroll' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $payments as $payment ) : ?>
						<tr>
							<td><?php echo esc_html( $payment['date'] ); ?></td>
							<td><?php echo wp_kses_post( wc_price( $payment['amount'] ) ); ?></td>
							<td><?php echo esc_html( get_user_by( 'ID', $payment['created_by'] )->display_name ); ?></td>
							<td>
								<button type="button" class="button button-small" onclick="deletePayment(<?php echo esc_attr( $user_id ); ?>, '<?php echo esc_attr( $payment['id'] ); ?>')"><?php esc_html_e( 'Delete', 'wc-team-payroll' ); ?></button>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<script>
			function toggleSalaryFields() {
				const type = document.getElementById('salary_type').value;
				document.getElementById('salary_amount_row').style.display = type === 'fixed' || type === 'combined' ? '' : 'none';
				document.getElementById('salary_frequency_row').style.display = type === 'fixed' || type === 'combined' ? '' : 'none';
			}

			function updateEmployeeSalary(userId) {
				const type = document.getElementById('salary_type').value;
				const amount = document.getElementById('salary_amount').value;
				const frequency = document.getElementById('salary_frequency').value;

				jQuery.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'wc_tp_update_employee_salary',
						user_id: userId,
						salary_type: type,
						salary_amount: amount,
						salary_frequency: frequency,
						nonce: '<?php echo esc_js( wp_create_nonce( 'wc_team_payroll_nonce' ) ); ?>'
					},
					success: function(response) {
						if (response.success) {
							alert('<?php esc_html_e( 'Salary updated', 'wc-team-payroll' ); ?>');
							location.reload();
						} else {
							alert('Error: ' + response.data);
						}
					},
					error: function() {
						alert('<?php esc_html_e( 'Error updating salary', 'wc-team-payroll' ); ?>');
					}
				});
			}

			function addPayment(userId) {
				const amount = document.getElementById('payment_amount').value;
				const date = document.getElementById('payment_date').value;

				if (!amount || !date) {
					alert('<?php esc_html_e( 'Please fill all fields', 'wc-team-payroll' ); ?>');
					return;
				}

				jQuery.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'wc_tp_add_payment',
						user_id: userId,
						amount: amount,
						payment_date: date,
						nonce: '<?php echo esc_js( wp_create_nonce( 'wc_team_payroll_nonce' ) ); ?>'
					},
					success: function(response) {
						if (response.success) {
							document.getElementById('payment_amount').value = '';
							document.getElementById('payment_date').value = new Date().toISOString().slice(0, 16);
							refreshPaymentList(userId);
							alert('<?php esc_html_e( 'Payment added', 'wc-team-payroll' ); ?>');
						} else {
							alert('Error: ' + response.data);
						}
					},
					error: function() {
						alert('<?php esc_html_e( 'Error adding payment', 'wc-team-payroll' ); ?>');
					}
				});
			}

			function deletePayment(userId, paymentId) {
				if (!confirm('<?php esc_html_e( 'Delete this payment?', 'wc-team-payroll' ); ?>')) {
					return;
				}

				jQuery.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'wc_tp_delete_payment',
						user_id: userId,
						payment_id: paymentId,
						nonce: '<?php echo esc_js( wp_create_nonce( 'wc_team_payroll_nonce' ) ); ?>'
					},
					success: function(response) {
						if (response.success) {
							refreshPaymentList(userId);
							alert('<?php esc_html_e( 'Payment deleted', 'wc-team-payroll' ); ?>');
						} else {
							alert('Error: ' + response.data);
						}
					},
					error: function() {
						alert('<?php esc_html_e( 'Error deleting payment', 'wc-team-payroll' ); ?>');
					}
				});
			}

			function refreshPaymentList(userId) {
				const year = new URLSearchParams(window.location.search).get('year') || new Date().getFullYear();
				const month = new URLSearchParams(window.location.search).get('month') || (new Date().getMonth() + 1);

				jQuery.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'wc_tp_get_payment_data',
						user_id: userId,
						year: year,
						month: month,
						nonce: '<?php echo esc_js( wp_create_nonce( 'wc_team_payroll_nonce' ) ); ?>'
					},
					success: function(response) {
						if (response.success) {
							const totalPaid = response.data.total_paid;
							const totalPaidElement = document.querySelector('.payment-card .amount');
							if (totalPaidElement) {
								totalPaidElement.textContent = new Intl.NumberFormat('en-US', {
									style: 'currency',
									currency: 'USD'
								}).format(totalPaid);
							}

							const tbody = document.querySelector('table.widefat tbody');
							if (tbody && response.data.payments) {
								tbody.innerHTML = '';
								response.data.payments.forEach(payment => {
									const row = document.createElement('tr');
									row.innerHTML = `
										<td>${payment.date}</td>
										<td>${new Intl.NumberFormat('en-US', {style: 'currency', currency: 'USD'}).format(payment.amount)}</td>
										<td><button type="button" class="button button-small" onclick="deletePayment(${userId}, '${payment.id}')"><?php esc_html_e( 'Delete', 'wc-team-payroll' ); ?></button></td>
									`;
									tbody.appendChild(row);
								});
							}
						}
					}
				});
			}
		</script>

		<style>
			.wc-tp-filters {
				margin: 20px 0;
				padding: 15px;
				background: #f5f5f5;
				border-radius: 4px;
			}

			.wc-tp-filters form {
				display: flex;
				gap: 10px;
				align-items: center;
			}

			.wc-tp-payment-summary {
				display: grid;
				grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
				gap: 15px;
				margin: 20px 0;
			}

			.payment-card {
				background: #fff;
				border: 1px solid #ddd;
				border-radius: 4px;
				padding: 20px;
				text-align: center;
			}

			.payment-card .label {
				display: block;
				color: #666;
				font-size: 14px;
				margin-bottom: 10px;
			}

			.payment-card .amount {
				display: block;
				font-size: 24px;
				font-weight: 600;
				color: #0073aa;
			}
		</style>
		<?php
	}
}
