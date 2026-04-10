<?php
/**
 * Admin Dashboard
 */

class WC_Team_Payroll_Dashboard {

	/**
	 * Render dashboard page
	 */
	public function render_dashboard() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized', 'wc-team-payroll' ) );
		}

		// Get date range from request
		$start_date = isset( $_GET['start_date'] ) ? sanitize_text_field( $_GET['start_date'] ) : date( 'Y-m-01' );
		$end_date = isset( $_GET['end_date'] ) ? sanitize_text_field( $_GET['end_date'] ) : date( 'Y-m-t' );

		// Get all employees
		$all_employees = $this->get_all_employees();

		// Get latest employees (10)
		$latest_employees = $this->get_latest_employees( 10 );

		// Get payroll data for date range (only those with commission)
		$payroll = array();
		if ( class_exists( 'WC_Team_Payroll_Payroll_Engine' ) ) {
			$payroll = WC_Team_Payroll_Payroll_Engine::get_payroll_by_date_range( $start_date, $end_date );
		}

		// Calculate stats
		$total_employees = count( $all_employees );
		$total_earnings = 0;
		$total_paid = 0;
		$total_due = 0;
		$total_orders = 0;

		foreach ( $payroll as $data ) {
			$total_earnings += $data['total'];
			$total_paid += $data['paid'];
			$total_due += $data['due'];
			$total_orders += $data['orders'];
		}

		// Get recent payments
		$recent_payments = $this->get_recent_payments( 10 );

		// Get top earners
		$top_earners = $this->get_top_earners( 5, $start_date, $end_date );

		?>
		<div class="wrap wc-team-payroll-dashboard">
			<h1><?php esc_html_e( 'Team Payroll Dashboard', 'wc-team-payroll' ); ?></h1>

			<!-- Date Range Filter -->
			<div class="wc-tp-date-filter">
				<label><?php esc_html_e( 'Date Range:', 'wc-team-payroll' ); ?></label>
				<input type="date" id="wc-tp-start-date" value="<?php echo esc_attr( $start_date ); ?>" />
				<span class="wc-tp-date-separator">to</span>
				<input type="date" id="wc-tp-end-date" value="<?php echo esc_attr( $end_date ); ?>" />
				<button type="button" class="button button-primary" id="wc-tp-filter-btn"><?php esc_html_e( 'Filter', 'wc-team-payroll' ); ?></button>
			</div>

			<!-- Stats Cards -->
			<div class="wc-tp-stats-grid">
				<div class="wc-tp-stat-card">
					<div class="wc-tp-stat-icon">👥</div>
					<div class="wc-tp-stat-content">
						<div class="wc-tp-stat-value" id="total-employees"><?php echo esc_html( $total_employees ); ?></div>
						<div class="wc-tp-stat-label"><?php esc_html_e( 'Total Employees', 'wc-team-payroll' ); ?></div>
					</div>
				</div>

				<div class="wc-tp-stat-card">
					<div class="wc-tp-stat-icon">📦</div>
					<div class="wc-tp-stat-content">
						<div class="wc-tp-stat-value" id="total-orders"><?php echo esc_html( $total_orders ); ?></div>
						<div class="wc-tp-stat-label"><?php esc_html_e( 'Total Orders', 'wc-team-payroll' ); ?></div>
					</div>
				</div>

				<div class="wc-tp-stat-card">
					<div class="wc-tp-stat-icon">💰</div>
					<div class="wc-tp-stat-content">
						<div class="wc-tp-stat-value" id="total-earnings"><?php echo wp_kses_post( wc_price( $total_earnings ) ); ?></div>
						<div class="wc-tp-stat-label"><?php esc_html_e( 'Total Earnings', 'wc-team-payroll' ); ?></div>
					</div>
				</div>

				<div class="wc-tp-stat-card">
					<div class="wc-tp-stat-icon">✅</div>
					<div class="wc-tp-stat-content">
						<div class="wc-tp-stat-value" id="total-paid"><?php echo wp_kses_post( wc_price( $total_paid ) ); ?></div>
						<div class="wc-tp-stat-label"><?php esc_html_e( 'Total Paid', 'wc-team-payroll' ); ?></div>
					</div>
				</div>

				<div class="wc-tp-stat-card">
					<div class="wc-tp-stat-icon">⏳</div>
					<div class="wc-tp-stat-content">
						<div class="wc-tp-stat-value" id="total-due"><?php echo wp_kses_post( wc_price( $total_due ) ); ?></div>
						<div class="wc-tp-stat-label"><?php esc_html_e( 'Total Due', 'wc-team-payroll' ); ?></div>
					</div>
				</div>
			</div>

			<!-- Latest Employees (10) -->
			<div class="wc-tp-table-section">
				<h2><?php esc_html_e( 'Latest Employees', 'wc-team-payroll' ); ?></h2>
				<?php if ( empty( $latest_employees ) ) : ?>
					<div class="wc-tp-empty-state">
						<div class="wc-tp-empty-icon">👥</div>
						<p><?php esc_html_e( 'No employees yet', 'wc-team-payroll' ); ?></p>
					</div>
				<?php else : ?>
					<table class="wc-tp-data-table">
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
							<?php foreach ( $latest_employees as $employee ) : ?>
								<?php
									$is_fixed_salary = get_user_meta( $employee->ID, '_wc_tp_fixed_salary', true );
									$is_combined_salary = get_user_meta( $employee->ID, '_wc_tp_combined_salary', true );
									$salary = get_user_meta( $employee->ID, '_wc_tp_salary_amount', true );
									$frequency = get_user_meta( $employee->ID, '_wc_tp_salary_frequency', true );
								?>
								<tr>
									<td><strong><?php echo esc_html( $employee->display_name ); ?></strong></td>
									<td><?php echo esc_html( $employee->user_email ); ?></td>
									<td>
										<?php
										if ( $is_fixed_salary ) {
											echo esc_html__( 'Fixed Salary', 'wc-team-payroll' );
										} elseif ( $is_combined_salary ) {
											echo esc_html__( 'Combined', 'wc-team-payroll' );
										} else {
											echo esc_html__( 'Commission', 'wc-team-payroll' );
										}
										?>
									</td>
									<td>
										<?php
										if ( $is_fixed_salary || $is_combined_salary ) {
											echo wp_kses_post( wc_price( $salary ) . ' / ' . esc_html( $frequency ) );
										} else {
											echo esc_html__( 'Commission Based', 'wc-team-payroll' );
										}
										?>
									</td>
									<td>
										<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'wc-team-payroll-employee-detail', 'user_id' => $employee->ID ), admin_url( 'admin.php' ) ) ); ?>" class="button button-small button-primary"><?php esc_html_e( 'Manage', 'wc-team-payroll' ); ?></a>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>

			<!-- Two Column Layout -->
			<div class="wc-tp-dashboard-grid">
				<!-- Top Earners -->
				<div class="wc-tp-table-section">
					<h2><?php esc_html_e( 'Top Earners', 'wc-team-payroll' ); ?></h2>
					<?php if ( empty( $top_earners ) ) : ?>
						<div class="wc-tp-empty-state">
							<div class="wc-tp-empty-icon">💰</div>
							<p><?php esc_html_e( 'No earnings data', 'wc-team-payroll' ); ?></p>
						</div>
					<?php else : ?>
						<table class="wc-tp-data-table">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Employee', 'wc-team-payroll' ); ?></th>
									<th><?php esc_html_e( 'Earnings', 'wc-team-payroll' ); ?></th>
									<th><?php esc_html_e( 'Orders', 'wc-team-payroll' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $top_earners as $earner ) : ?>
									<tr>
										<td><strong><?php echo esc_html( $earner['name'] ); ?></strong></td>
										<td><?php echo wp_kses_post( wc_price( $earner['earnings'] ) ); ?></td>
										<td><span class="wc-tp-badge"><?php echo esc_html( $earner['orders'] ); ?></span></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php endif; ?>
				</div>

				<!-- Recent Payments -->
				<div class="wc-tp-table-section">
					<h2><?php esc_html_e( 'Recent Payments', 'wc-team-payroll' ); ?></h2>
					<?php if ( empty( $recent_payments ) ) : ?>
						<div class="wc-tp-empty-state">
							<div class="wc-tp-empty-icon">💳</div>
							<p><?php esc_html_e( 'No payments yet', 'wc-team-payroll' ); ?></p>
						</div>
					<?php else : ?>
						<table class="wc-tp-data-table">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Employee', 'wc-team-payroll' ); ?></th>
									<th><?php esc_html_e( 'Amount', 'wc-team-payroll' ); ?></th>
									<th><?php esc_html_e( 'Date', 'wc-team-payroll' ); ?></th>
									<th><?php esc_html_e( 'Status', 'wc-team-payroll' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $recent_payments as $payment ) : ?>
									<tr>
										<td><strong><?php echo esc_html( $payment['employee_name'] ); ?></strong></td>
										<td><?php echo wp_kses_post( wc_price( $payment['amount'] ) ); ?></td>
										<td><?php echo esc_html( date( 'M d, Y', strtotime( $payment['date'] ) ) ); ?></td>
										<td><span class="wc-tp-status wc-tp-status-<?php echo esc_attr( $payment['status'] ); ?>"><?php echo esc_html( ucfirst( $payment['status'] ) ); ?></span></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php endif; ?>
				</div>
			</div>

			<!-- Employee Payroll Details -->
			<div class="wc-tp-table-section">
				<h2><?php esc_html_e( 'Employee Payroll Details', 'wc-team-payroll' ); ?></h2>
				<?php if ( empty( $payroll ) ) : ?>
					<div class="wc-tp-empty-state">
						<div class="wc-tp-empty-icon">📊</div>
						<p><?php esc_html_e( 'No payroll data for this period', 'wc-team-payroll' ); ?></p>
					</div>
				<?php else : ?>
					<table class="wc-tp-data-table" id="payroll-table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Employee', 'wc-team-payroll' ); ?></th>
								<th><?php esc_html_e( 'Email', 'wc-team-payroll' ); ?></th>
								<th><?php esc_html_e( 'Orders', 'wc-team-payroll' ); ?></th>
								<th><?php esc_html_e( 'Total Earnings', 'wc-team-payroll' ); ?></th>
								<th><?php esc_html_e( 'Paid', 'wc-team-payroll' ); ?></th>
								<th><?php esc_html_e( 'Due', 'wc-team-payroll' ); ?></th>
								<th><?php esc_html_e( 'Action', 'wc-team-payroll' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $payroll as $data ) : ?>
								<tr>
									<td><strong><?php echo esc_html( $data['user'] ? $data['user']->display_name : 'Unknown' ); ?></strong></td>
									<td><?php echo esc_html( $data['user'] ? $data['user']->user_email : 'N/A' ); ?></td>
									<td><span class="wc-tp-badge"><?php echo esc_html( $data['orders'] ); ?></span></td>
									<td><?php echo wp_kses_post( wc_price( $data['total'] ) ); ?></td>
									<td><span class="wc-tp-paid"><?php echo wp_kses_post( wc_price( $data['paid'] ) ); ?></span></td>
									<td><span class="wc-tp-due"><?php echo wp_kses_post( wc_price( $data['due'] ) ); ?></span></td>
									<td>
										<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'wc-team-payroll-employee-detail', 'user_id' => $data['user_id'] ), admin_url( 'admin.php' ) ) ); ?>" class="button button-small button-primary"><?php esc_html_e( 'View', 'wc-team-payroll' ); ?></a>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>
		</div>

		<style>
			.wc-team-payroll-dashboard {
				background: #f5f5f5;
				padding: 20px;
			}

			.wc-tp-date-filter {
				background: white;
				padding: 15px;
				border-radius: 8px;
				margin-bottom: 20px;
				box-shadow: 0 2px 8px rgba(0,0,0,0.1);
				display: flex;
				gap: 10px;
				align-items: center;
				flex-wrap: wrap;
			}

			.wc-tp-date-filter label {
				font-weight: 600;
				color: #333;
			}

			.wc-tp-date-filter input[type="date"] {
				padding: 8px 12px;
				border: 1px solid #ddd;
				border-radius: 4px;
				font-size: 14px;
			}

			.wc-tp-date-separator {
				color: #999;
				font-weight: 500;
			}

			.wc-tp-stats-grid {
				display: grid;
				grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
				gap: 15px;
				margin-bottom: 30px;
			}

			.wc-tp-stat-card {
				background: white;
				padding: 20px;
				border-radius: 8px;
				box-shadow: 0 2px 8px rgba(0,0,0,0.1);
				display: flex;
				align-items: center;
				gap: 15px;
				transition: transform 0.2s, box-shadow 0.2s;
			}

			.wc-tp-stat-card:hover {
				transform: translateY(-2px);
				box-shadow: 0 4px 12px rgba(0,0,0,0.15);
			}

			.wc-tp-stat-icon {
				font-size: 32px;
				min-width: 50px;
				text-align: center;
			}

			.wc-tp-stat-content {
				flex: 1;
			}

			.wc-tp-stat-value {
				font-size: 24px;
				font-weight: bold;
				color: #0073aa;
				margin-bottom: 5px;
			}

			.wc-tp-stat-label {
				font-size: 13px;
				color: #666;
				text-transform: uppercase;
				letter-spacing: 0.5px;
			}

			.wc-tp-dashboard-grid {
				display: grid;
				grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
				gap: 20px;
				margin-bottom: 20px;
			}

			.wc-tp-table-section {
				background: white;
				padding: 20px;
				border-radius: 8px;
				box-shadow: 0 2px 8px rgba(0,0,0,0.1);
				margin-bottom: 20px;
			}

			.wc-tp-table-section h2 {
				margin-top: 0;
				margin-bottom: 20px;
				color: #333;
				border-bottom: 2px solid #0073aa;
				padding-bottom: 10px;
				font-size: 18px;
			}

			.wc-tp-empty-state {
				text-align: center;
				padding: 40px 20px;
				color: #999;
			}

			.wc-tp-empty-icon {
				font-size: 48px;
				margin-bottom: 15px;
				display: block;
				opacity: 0.5;
			}

			.wc-tp-empty-state p {
				margin: 0;
				font-size: 14px;
				color: #999;
			}

			.wc-tp-data-table {
				width: 100%;
				border-collapse: collapse;
			}

			.wc-tp-data-table thead {
				background: #f9f9f9;
				border-bottom: 2px solid #0073aa;
			}

			.wc-tp-data-table th {
				padding: 12px;
				text-align: left;
				font-weight: 600;
				color: #333;
				font-size: 13px;
			}

			.wc-tp-data-table td {
				padding: 12px;
				border-bottom: 1px solid #eee;
			}

			.wc-tp-data-table tbody tr:hover {
				background: #f5f5f5;
			}

			.wc-tp-badge {
				background: #0073aa;
				color: white;
				padding: 4px 8px;
				border-radius: 3px;
				font-size: 12px;
				font-weight: 600;
			}

			.wc-tp-paid {
				color: #28a745;
				font-weight: 600;
			}

			.wc-tp-due {
				color: #dc3545;
				font-weight: 600;
			}

			.wc-tp-status {
				padding: 4px 8px;
				border-radius: 3px;
				font-size: 12px;
				font-weight: 600;
				display: inline-block;
			}

			.wc-tp-status-paid {
				background: #d4edda;
				color: #155724;
			}

			.wc-tp-status-pending {
				background: #fff3cd;
				color: #856404;
			}

			.wc-tp-status-failed {
				background: #f8d7da;
				color: #721c24;
			}

			.button-primary {
				background: #0073aa;
				border-color: #0073aa;
				color: white;
			}

			.button-primary:hover {
				background: #005a87;
				border-color: #005a87;
			}

			@media (max-width: 768px) {
				.wc-tp-date-filter {
					flex-direction: column;
					align-items: flex-start;
				}

				.wc-tp-dashboard-grid {
					grid-template-columns: 1fr;
				}

				.wc-tp-data-table {
					font-size: 13px;
				}

				.wc-tp-data-table th,
				.wc-tp-data-table td {
					padding: 8px;
				}
			}
		</style>

		<script>
			jQuery(document).ready(function($) {
				// Filter button click
				$('#wc-tp-filter-btn').on('click', function() {
					const startDate = $('#wc-tp-start-date').val();
					const endDate = $('#wc-tp-end-date').val();

					if (!startDate || !endDate) {
						alert('Please select both start and end dates');
						return;
					}

					// Show loading state
					$('#wc-tp-filter-btn').prop('disabled', true).text('Loading...');

					// AJAX request
					$.ajax({
						url: ajaxurl,
						type: 'POST',
						data: {
							action: 'wc_tp_get_dashboard_data',
							start_date: startDate,
							end_date: endDate
						},
						success: function(response) {
							if (response.success) {
								const data = response.data;
								
								// Update stat cards
								updateStatCard('total-employees', data.total_employees);
								updateStatCard('total-orders', data.total_orders);
								updateStatCard('total-earnings', data.total_earnings);
								updateStatCard('total-paid', data.total_paid);
								updateStatCard('total-due', data.total_due);
								
								// Update payroll table
								updatePayrollTable(data.payroll);
								
								// Show success message
								showNotice('Dashboard updated successfully', 'success');
							} else {
								showNotice('Error: ' + response.data, 'error');
							}
						},
						error: function() {
							showNotice('Error loading dashboard data', 'error');
						},
						complete: function() {
							$('#wc-tp-filter-btn').prop('disabled', false).text('Filter');
						}
					});
				});

				// Update stat card value
				function updateStatCard(id, value) {
					const element = $('#' + id);
					if (element.length) {
						// Format as currency if it's a monetary value
						if (id.includes('earnings') || id.includes('paid') || id.includes('due')) {
							const formatted = new Intl.NumberFormat('en-US', {
								style: 'currency',
								currency: 'USD'
							}).format(value);
							element.text(formatted);
						} else {
							element.text(value);
						}
					}
				}

				// Update payroll table
				function updatePayrollTable(payroll) {
					const tbody = $('#payroll-table tbody');
					tbody.empty();

					if (Object.keys(payroll).length === 0) {
						tbody.html('<tr><td colspan="7" style="text-align: center; padding: 20px;">No payroll data for this period</td></tr>');
						return;
					}

					$.each(payroll, function(userId, data) {
						const row = $('<tr>');
						row.append($('<td>').html('<strong>' + (data.user ? data.user.display_name : 'Unknown') + '</strong>'));
						row.append($('<td>').text(data.user ? data.user.user_email : 'N/A'));
						row.append($('<td>').html('<span class="wc-tp-badge">' + data.orders + '</span>'));
						row.append($('<td>').text(formatCurrency(data.total)));
						row.append($('<td>').html('<span class="wc-tp-paid">' + formatCurrency(data.paid) + '</span>'));
						row.append($('<td>').html('<span class="wc-tp-due">' + formatCurrency(data.due) + '</span>'));
						row.append($('<td>').html('<a href="' + ajaxurl.replace('admin-ajax.php', 'admin.php?page=wc-team-payroll-employee-detail&user_id=' + userId) + '" class="button button-small button-primary">View</a>'));
						tbody.append(row);
					});
				}

				// Format currency
				function formatCurrency(value) {
					return new Intl.NumberFormat('en-US', {
						style: 'currency',
						currency: 'USD'
					}).format(value);
				}

				// Show notice
				function showNotice(message, type) {
					const noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
					const notice = $('<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>');
					$('.wrap').prepend(notice);
					setTimeout(function() {
						notice.fadeOut(function() { $(this).remove(); });
					}, 3000);
				}
			});
		</script>
		<?php
	}

	/**
	 * Get all employees (shop_employee, shop_manager and administrator roles)
	 */
	private function get_all_employees() {
		$args = array(
			'role__in' => array( 'shop_employee', 'shop_manager', 'administrator' ),
			'orderby'  => 'display_name',
			'order'    => 'ASC',
			'number'   => -1,
		);

		return get_users( $args );
	}

	/**
	 * Get latest employees (last 10)
	 */
	private function get_latest_employees( $limit = 10 ) {
		$args = array(
			'role__in' => array( 'shop_employee', 'shop_manager', 'administrator' ),
			'orderby'  => 'user_registered',
			'order'    => 'DESC',
			'number'   => $limit,
		);

		return get_users( $args );
	}

	/**
	 * Get recent payments
	 */
	private function get_recent_payments( $limit = 10 ) {
		global $wpdb;

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}postmeta 
				WHERE meta_key = 'wc_tp_payment' 
				ORDER BY meta_id DESC 
				LIMIT %d",
				$limit
			),
			ARRAY_A
		);

		$payments = array();
		foreach ( $results as $result ) {
			$payment_data = maybe_unserialize( $result['meta_value'] );
			if ( is_array( $payment_data ) ) {
				$user = get_user_by( 'id', $payment_data['user_id'] ?? 0 );
				$payments[] = array(
					'user_id'       => $payment_data['user_id'] ?? 0,
					'employee_name' => $user ? $user->display_name : 'Unknown',
					'amount'        => $payment_data['amount'] ?? 0,
					'date'          => $payment_data['date'] ?? current_time( 'mysql' ),
					'status'        => $payment_data['status'] ?? 'pending',
				);
			}
		}

		return $payments;
	}

	/**
	 * Get top earners
	 */
	private function get_top_earners( $limit = 5, $start_date = '', $end_date = '' ) {
		$payroll = array();
		if ( class_exists( 'WC_Team_Payroll_Payroll_Engine' ) ) {
			$payroll = WC_Team_Payroll_Payroll_Engine::get_payroll_by_date_range( $start_date, $end_date );
		}

		// Sort by earnings
		usort( $payroll, function( $a, $b ) {
			return $b['total'] - $a['total'];
		} );

		$top_earners = array();
		$count = 0;
		foreach ( $payroll as $data ) {
			if ( $count >= $limit ) {
				break;
			}
			$top_earners[] = array(
				'name'     => $data['user'] ? $data['user']->display_name : 'Unknown',
				'earnings' => $data['total'],
				'orders'   => $data['orders'],
			);
			$count++;
		}

		return $top_earners;
	}

	/**
	 * Render payroll page
	 */
	public function render_payroll() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized', 'wc-team-payroll' ) );
		}

		$year = isset( $_GET['year'] ) ? intval( $_GET['year'] ) : date( 'Y' );
		$month = isset( $_GET['month'] ) ? intval( $_GET['month'] ) : date( 'm' );

		// Get payroll data
		$payroll = array();
		if ( class_exists( 'WC_Team_Payroll_Payroll_Engine' ) ) {
			$payroll = WC_Team_Payroll_Payroll_Engine::get_monthly_payroll( $year, $month );
		}

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Monthly Payroll', 'wc-team-payroll' ); ?></h1>

			<div class="wc-team-payroll-filters">
				<form method="get">
					<input type="hidden" name="page" value="wc-team-payroll-payroll" />
					<select name="month">
						<?php for ( $m = 1; $m <= 12; $m++ ) : ?>
							<option value="<?php echo esc_attr( $m ); ?>" <?php selected( $month, $m ); ?>><?php echo esc_html( date( 'F', mktime( 0, 0, 0, $m, 1 ) ) ); ?></option>
						<?php endfor; ?>
					</select>
					<input type="number" name="year" value="<?php echo esc_attr( $year ); ?>" min="2020" max="2099" />
					<button type="submit" class="button"><?php esc_html_e( 'Filter', 'wc-team-payroll' ); ?></button>
				</form>
			</div>

			<?php if ( empty( $payroll ) ) : ?>
				<div class="notice notice-info"><p><?php esc_html_e( 'No payroll data for this period.', 'wc-team-payroll' ); ?></p></div>
			<?php else : ?>
				<table class="widefat striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Employee', 'wc-team-payroll' ); ?></th>
							<th><?php esc_html_e( 'Total Earnings', 'wc-team-payroll' ); ?></th>
							<th><?php esc_html_e( 'Paid', 'wc-team-payroll' ); ?></th>
							<th><?php esc_html_e( 'Due', 'wc-team-payroll' ); ?></th>
							<th><?php esc_html_e( 'Action', 'wc-team-payroll' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $payroll as $data ) : ?>
							<tr>
								<td><?php echo esc_html( $data['user'] ? $data['user']->display_name : 'Unknown' ); ?></td>
								<td><?php echo wp_kses_post( wc_price( $data['total'] ) ); ?></td>
								<td><?php echo wp_kses_post( wc_price( $data['paid'] ) ); ?></td>
								<td><?php echo wp_kses_post( wc_price( $data['due'] ) ); ?></td>
								<td>
									<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'wc-team-payroll-employee-detail', 'user_id' => $data['user_id'] ), admin_url( 'admin.php' ) ) ); ?>" class="button button-small"><?php esc_html_e( 'View', 'wc-team-payroll' ); ?></a>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}
}
