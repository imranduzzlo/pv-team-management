<?php
/**
 * Salary System Debug & Testing Helper
 * Allows manual testing and debugging of salary accumulation
 */

class WC_Team_Payroll_Salary_Debug {

	/**
	 * Initialize debug helpers
	 */
	public static function init() {
		// Check if salary debug is enabled
		$settings = get_option( 'wc_team_payroll_settings', array() );
		$debug_enabled = isset( $settings['enable_salary_debug'] ) ? $settings['enable_salary_debug'] : 0;

		if ( ! $debug_enabled ) {
			return; // Debug disabled, don't register anything
		}

		// Enqueue toast notification script
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
		
		// Register AJAX handlers for testing
		add_action( 'wp_ajax_wc_tp_test_salary_accumulation', array( __CLASS__, 'ajax_test_salary_accumulation' ) );
		add_action( 'wp_ajax_wc_tp_get_employee_salary_status', array( __CLASS__, 'ajax_get_employee_salary_status' ) );
		add_action( 'wp_ajax_wc_tp_manual_trigger_cron', array( __CLASS__, 'ajax_manual_trigger_cron' ) );
		add_action( 'wp_ajax_wc_tp_reset_employee_salary', array( __CLASS__, 'ajax_reset_employee_salary' ) );
	}

	/**
	 * Enqueue scripts for debug page
	 */
	public static function enqueue_scripts( $hook ) {
		// Check if we're on the salary debug page
		if ( strpos( $hook, 'wc-tp-salary-debug' ) === false ) {
			return;
		}

		// Toast script should already be enqueued by main plugin file
		// Just verify it's loaded
		wp_enqueue_script( 'wc-team-payroll-toast' );
	}

	/**
	 * Render debug page
	 */
	public static function render_debug_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Unauthorized' );
		}

		?>
		<div class="wrap">
			<h1>💰 Salary System Debug & Testing</h1>
			
			<div style="background: #fff; padding: 20px; border-radius: 8px; margin-top: 20px;">
				<h2>🧪 Quick Test</h2>
				<p>Select an employee and test salary accumulation immediately:</p>
				
				<div style="margin: 20px 0;">
					<label for="test-employee-id">Select Employee:</label>
					<select id="test-employee-id" style="padding: 8px; margin-left: 10px;">
						<option value="">-- Select Employee --</option>
						<?php
						$employees = get_users( array(
							'role__in' => array( 'shop_employee', 'shop_manager', 'administrator' ),
							'number'   => -1,
						) );

						foreach ( $employees as $employee ) {
							$is_fixed = get_user_meta( $employee->ID, '_wc_tp_fixed_salary', true );
							$is_combined = get_user_meta( $employee->ID, '_wc_tp_combined_salary', true );
							
							if ( $is_fixed ) {
								$salary_type = 'fixed';
							} elseif ( $is_combined ) {
								$salary_type = 'combined';
							} else {
								$salary_type = 'commission';
							}
							
							$salary_amount = get_user_meta( $employee->ID, '_wc_tp_salary_amount', true );
							$salary_frequency = get_user_meta( $employee->ID, '_wc_tp_salary_frequency', true );
							
							$label = $employee->display_name . ' (' . $salary_type;
							if ( $salary_amount ) {
								// Format price without HTML tags
								$currency_symbol = get_woocommerce_currency_symbol();
								$formatted_price = wc_price( $salary_amount, array( 'echo' => false ) );
								// Strip HTML tags from price
								$price_text = wp_strip_all_tags( $formatted_price );
								$label .= ' - ' . $price_text . '/' . $salary_frequency;
							}
							$label .= ')';
							
							echo '<option value="' . esc_attr( $employee->ID ) . '">' . esc_html( $label ) . '</option>';
						}
						?>
					</select>
				</div>

				<div style="margin: 20px 0;">
					<button class="button button-primary" id="btn-test-accumulation">Test Salary Accumulation</button>
					<button class="button button-secondary" id="btn-get-status">Get Current Status</button>
					<button class="button button-secondary" id="btn-manual-cron">Manually Trigger Cron</button>
					<button class="button button-danger" id="btn-reset-salary" style="background: #dc3545; color: white;">Reset Employee Demo Salary</button>
				</div>

				<div id="test-results" style="margin-top: 20px; padding: 15px; background: #f5f5f5; border-radius: 4px; display: none;">
					<h3>📊 Test Results:</h3>
					<pre id="results-content" style="background: #fff; padding: 10px; border-radius: 4px; overflow-x: auto;"></pre>
				</div>
			</div>

			<div style="background: #fff; padding: 20px; border-radius: 8px; margin-top: 20px;">
				<h2>📋 How to Use This Tool</h2>
				<ol>
					<li><strong>Pick an employee</strong> from the dropdown list</li>
					<li><strong>Click "Test Salary Accumulation"</strong> to simulate one day of work
						<ul style="margin-top: 5px;">
							<li>Each click = 1 day of salary building up</li>
							<li>For daily pay: salary is added right away</li>
							<li>For weekly/monthly pay: salary builds up until the end of the week/month</li>
						</ul>
					</li>
					<li><strong>Click "Get Current Status"</strong> to see:
						<ul style="margin-top: 5px;">
							<li>How much salary has built up so far</li>
							<li>How many more days until it gets added to their earnings</li>
							<li>Their total earnings</li>
							<li>History of all salary changes</li>
						</ul>
					</li>
					<li><strong>Click "Manually Trigger Cron"</strong> to force the system to process all employees right now (normally happens automatically at 11:59 PM)</li>
					<li><strong>Click "Reset Employee Demo Salary"</strong> to clear all test data and start fresh</li>
				</ol>
			</div>

			<div style="background: #fff; padding: 20px; border-radius: 8px; margin-top: 20px;">
				<h2>🔍 What to Look For</h2>
				<table style="width: 100%; border-collapse: collapse;">
					<tr style="background: #f5f5f5;">
						<th style="padding: 10px; text-align: left; border: 1px solid #ddd;">What You'll See</th>
						<th style="padding: 10px; text-align: left; border: 1px solid #ddd;">What It Means</th>
					</tr>
					<tr>
						<td style="padding: 10px; border: 1px solid #ddd;"><strong>Daily Rate</strong></td>
						<td style="padding: 10px; border: 1px solid #ddd;">How much salary is earned per day (salary ÷ number of days)</td>
					</tr>
					<tr>
						<td style="padding: 10px; border: 1px solid #ddd;"><strong>Accumulated Total</strong></td>
						<td style="padding: 10px; border: 1px solid #ddd;">How much salary has built up so far (waiting to be added)</td>
					</tr>
					<tr>
						<td style="padding: 10px; border: 1px solid #ddd;"><strong>Days Accumulated</strong></td>
						<td style="padding: 10px; border: 1px solid #ddd;">How many days of work have been counted</td>
					</tr>
					<tr>
						<td style="padding: 10px; border: 1px solid #ddd;"><strong>Days Remaining</strong></td>
						<td style="padding: 10px; border: 1px solid #ddd;">How many more days until salary is added to their earnings</td>
					</tr>
					<tr>
						<td style="padding: 10px; border: 1px solid #ddd;"><strong>Total Earnings</strong></td>
						<td style="padding: 10px; border: 1px solid #ddd;">All money the employee has earned (salary + commissions)</td>
					</tr>
				</table>
			</div>

			<div style="background: #fff; padding: 20px; border-radius: 8px; margin-top: 20px;">
				<h2>⚙️ Testing Examples</h2>
				
				<h3>Daily Pay Test</h3>
				<ol>
					<li>Set employee to earn $100 per day</li>
					<li>Click "Test Salary Accumulation" once</li>
					<li>Check: Their earnings should go up by $100 right away</li>
					<li>Result: ✅ Daily pay works correctly</li>
				</ol>

				<h3>Weekly Pay Test</h3>
				<ol>
					<li>Set employee to earn $700 per week</li>
					<li>Click "Test Salary Accumulation" multiple times (each click = 1 day)</li>
					<li>Check: You'll see the salary building up day by day</li>
					<li>When you reach the end of the week (Saturday by default), the salary automatically gets added to their earnings</li>
					<li>Result: ✅ Weekly pay builds up and transfers correctly</li>
				</ol>

				<h3>Monthly Pay Test</h3>
				<ol>
					<li>Set employee to earn $3,000 per month</li>
					<li>Click "Test Salary Accumulation" multiple times (each click = 1 day)</li>
					<li>Check: You'll see the salary building up day by day</li>
					<li>When you reach the last day of the month, the salary automatically gets added to their earnings</li>
					<li>Result: ✅ Monthly pay builds up and transfers correctly</li>
				</ol>

				<h3>What Happens When You Reset Demo Salary</h3>
				<ol>
					<li>Click "Reset Employee Demo Salary"</li>
					<li>All test data for that employee is deleted</li>
					<li>Their earnings stay the same (only test data is cleared)</li>
					<li>You can start fresh testing again</li>
					<li>Result: ✅ Clean slate for new tests</li>
				</ol>

				<h3>Important Notes</h3>
				<ul>
					<li><strong>Weekly Pay:</strong> Salary is added on the last day of the week (Saturday by default, based on your WordPress settings)</li>
					<li><strong>Monthly Pay:</strong> Salary is added on the last day of the month</li>
					<li><strong>Days Remaining:</strong> Shows how many more days until the salary gets added</li>
					<li><strong>Real Calendar:</strong> This tool uses the actual calendar dates, not simulated dates</li>
					<li><strong>Demo Data:</strong> All test data is separate from real employee earnings</li>
				</ul>
			</div>
		</div>

		<style>
			.button.button-danger {
				background: #dc3545 !important;
				border-color: #dc3545 !important;
				color: white !important;
			}
			.button.button-danger:hover {
				background: #c82333 !important;
				border-color: #c82333 !important;
			}
			#test-results {
				border-left: 4px solid #FF9900;
			}
			#results-content {
				font-family: 'Courier New', monospace;
				font-size: 12px;
				line-height: 1.5;
				max-height: 500px;
			}
		</style>

		<script>
			jQuery(document).ready(function($) {
				const nonce = '<?php echo wp_create_nonce( 'wc_team_payroll_nonce' ); ?>';

				function showResults(data) {
					$('#test-results').show();
					$('#results-content').text(JSON.stringify(data, null, 2));
				}

				$('#btn-test-accumulation').on('click', function() {
					const employeeId = $('#test-employee-id').val();
					if (!employeeId) {
						wcTPToast.error('Please select an employee');
						return;
					}

					$.ajax({
						url: ajaxurl,
						type: 'POST',
						data: {
							action: 'wc_tp_test_salary_accumulation',
							nonce: nonce,
							user_id: employeeId
						},
						success: function(response) {
							if (response.success) {
								showResults(response.data);
								wcTPToast.success(response.data.message);
							} else {
								wcTPToast.error('Error: ' + response.data.message);
							}
						},
						error: function() {
							wcTPToast.error('Connection error. Please try again.');
						}
					});
				});

				$('#btn-get-status').on('click', function() {
					const employeeId = $('#test-employee-id').val();
					if (!employeeId) {
						wcTPToast.error('Please select an employee');
						return;
					}

					$.ajax({
						url: ajaxurl,
						type: 'POST',
						data: {
							action: 'wc_tp_get_employee_salary_status',
							nonce: nonce,
							user_id: employeeId
						},
						success: function(response) {
							if (response.success) {
								showResults(response.data);
								wcTPToast.success('Status loaded successfully');
							} else {
								wcTPToast.error('Error: ' + response.data.message);
							}
						},
						error: function() {
							wcTPToast.error('Connection error. Please try again.');
						}
					});
				});

				$('#btn-manual-cron').on('click', function() {
					if (!confirm('Process all employees now? This will transfer any pending salaries.')) {
						return;
					}

					$.ajax({
						url: ajaxurl,
						type: 'POST',
						data: {
							action: 'wc_tp_manual_trigger_cron',
							nonce: nonce
						},
						success: function(response) {
							if (response.success) {
								showResults(response.data);
								wcTPToast.success('All employees processed successfully');
							} else {
								wcTPToast.error('Error: ' + response.data.message);
							}
						},
						error: function() {
							wcTPToast.error('Connection error. Please try again.');
						}
					});
				});

				$('#btn-reset-salary').on('click', function() {
					const employeeId = $('#test-employee-id').val();
					if (!employeeId) {
						wcTPToast.error('Please select an employee');
						return;
					}

					if (!confirm('Clear all test data for this employee? Their real earnings will not be affected.')) {
						return;
					}

					$.ajax({
						url: ajaxurl,
						type: 'POST',
						data: {
							action: 'wc_tp_reset_employee_salary',
							nonce: nonce,
							user_id: employeeId
						},
						success: function(response) {
							if (response.success) {
								showResults(response.data);
								wcTPToast.success('Test data cleared. Ready for fresh testing.');
							} else {
								wcTPToast.error('Error: ' + response.data.message);
							}
						},
						error: function() {
							wcTPToast.error('Connection error. Please try again.');
						}
					});
				});
			});
		</script>
		<?php
	}

	/**
	 * Test salary accumulation for an employee
	 */
	public static function ajax_test_salary_accumulation() {
		check_ajax_referer( 'wc_team_payroll_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		}

		$user_id = intval( $_POST['user_id'] );

		// Get employee salary info
		$is_fixed = get_user_meta( $user_id, '_wc_tp_fixed_salary', true );
		$is_combined = get_user_meta( $user_id, '_wc_tp_combined_salary', true );
		$salary_amount = floatval( get_user_meta( $user_id, '_wc_tp_salary_amount', true ) );
		$salary_frequency = get_user_meta( $user_id, '_wc_tp_salary_frequency', true );

		if ( ! $salary_amount || ! $salary_frequency ) {
			wp_send_json_error( array( 'message' => 'Employee has no salary configured' ) );
		}

		if ( $is_fixed ) {
			$salary_type = 'fixed';
		} elseif ( $is_combined ) {
			$salary_type = 'combined';
		} else {
			$salary_type = 'commission';
		}

		if ( 'commission' === $salary_type ) {
			wp_send_json_error( array( 'message' => 'Employee is commission-based, no salary to accumulate' ) );
		}

		// Manually trigger accumulation
		$daily_rate = self::calculate_daily_rate( $salary_amount, $salary_frequency );

		if ( 'daily' === $salary_frequency ) {
			// Add directly to earnings
			$current_earnings = get_user_meta( $user_id, '_wc_tp_total_earnings', true );
			if ( ! $current_earnings ) {
				$current_earnings = 0;
			}
			$current_earnings += $daily_rate;
			update_user_meta( $user_id, '_wc_tp_total_earnings', $current_earnings );

			$result = array(
				'message' => 'Daily salary added to earnings',
				'salary_type' => $salary_type,
				'salary_amount' => $salary_amount,
				'salary_frequency' => $salary_frequency,
				'daily_rate' => $daily_rate,
				'total_earnings' => $current_earnings,
				'action' => 'direct_transfer',
			);
		} else {
			// Accumulate in database
			$accumulation = get_user_meta( $user_id, '_wc_tp_daily_accumulation', true );

			if ( ! is_array( $accumulation ) ) {
				// Initialize new accumulation
				$period_start = date( 'Y-m-01' );
				$period_end = date( 'Y-m-t' );

				if ( 'weekly' === $salary_frequency ) {
					$week_start_day = get_option( 'start_of_week', 0 );
					$today = new DateTime();
					$days_since_week_start = ( $today->format( 'w' ) - $week_start_day + 7 ) % 7;
					$period_start_obj = clone $today;
					$period_start_obj->modify( "-{$days_since_week_start} days" );
					$period_start = $period_start_obj->format( 'Y-m-d' );
					$period_end_obj = clone $period_start_obj;
					$period_end_obj->modify( '+6 days' );
					$period_end = $period_end_obj->format( 'Y-m-d' );
				}

				$accumulation = array(
					'user_id'           => $user_id,
					'salary_type'       => $salary_type,
					'salary_amount'     => $salary_amount,
					'salary_frequency'  => $salary_frequency,
					'daily_rate'        => $daily_rate,
					'accumulated_total' => 0,
					'period_start'      => $period_start,
					'period_end'        => $period_end,
					'days_accumulated'  => 0,
					'last_updated'      => current_time( 'mysql' ),
					'status'            => 'active',
				);
			}

			// Add today's accumulation
			$accumulation['accumulated_total'] += $daily_rate;
			$accumulation['days_accumulated']++;
			$accumulation['last_updated'] = current_time( 'mysql' );

			// Check if period end (match actual cron job logic)
			$period_end_reached = false;
			$expected_days = 0;
			$transferred_amount = 0;
			$days_remaining = 0;

			if ( 'weekly' === $salary_frequency ) {
				$week_start_day = get_option( 'start_of_week', 0 );
				$week_end_day = ( $week_start_day + 6 ) % 7;
				$today_day = date( 'w' );
				
				// Calculate days remaining until week end (from today)
				$days_until_end = ( $week_end_day - $today_day + 7 ) % 7;
				if ( $days_until_end === 0 ) {
					// Today is the week end day
					$days_until_end = 0;
					$period_end_reached = true;
				} else {
					// Days remaining in current week (including today)
					$days_until_end = $days_until_end + 1;
				}
				
				$expected_days = $days_until_end;
				$days_remaining = $days_until_end - $accumulation['days_accumulated'];
				
				// Check if we've accumulated enough days to reach week end
				if ( $accumulation['days_accumulated'] >= $days_until_end ) {
					$period_end_reached = true;
				}
			} elseif ( 'monthly' === $salary_frequency ) {
				$today = date( 'Y-m-d' );
				$month_end = date( 'Y-m-t' );
				$days_in_month = (int) date( 't' );
				$current_day = (int) date( 'd' );
				
				// Days remaining in current month (including today)
				$days_until_end = $days_in_month - $current_day + 1;
				
				$expected_days = $days_until_end;
				$days_remaining = $days_until_end - $accumulation['days_accumulated'];
				
				// Check if today is month end or we've accumulated enough days
				if ( $today === $month_end || $accumulation['days_accumulated'] >= $days_until_end ) {
					$period_end_reached = true;
				}
			}

			// If period end reached, transfer to earnings
			if ( $period_end_reached ) {
				$current_earnings = get_user_meta( $user_id, '_wc_tp_total_earnings', true );
				if ( ! $current_earnings ) {
					$current_earnings = 0;
				}
				$current_earnings += $accumulation['accumulated_total'];
				update_user_meta( $user_id, '_wc_tp_total_earnings', $current_earnings );
				$transferred_amount = $accumulation['accumulated_total'];

				// Log transaction
				$log = get_user_meta( $user_id, '_wc_tp_salary_transactions', true );
				if ( ! is_array( $log ) ) {
					$log = array();
				}
				$log[] = array(
					'date'   => current_time( 'mysql' ),
					'amount' => $transferred_amount,
					'type'   => $salary_frequency . '_transfer',
					'note'   => 'Period end transfer (test)',
				);
				if ( count( $log ) > 100 ) {
					$log = array_slice( $log, -100 );
				}
				update_user_meta( $user_id, '_wc_tp_salary_transactions', $log );

				// Clear accumulation
				delete_user_meta( $user_id, '_wc_tp_daily_accumulation' );

				$result = array(
					'message' => 'Period end reached! Salary transferred to earnings',
					'salary_type' => $salary_type,
					'salary_amount' => $salary_amount,
					'salary_frequency' => $salary_frequency,
					'daily_rate' => $daily_rate,
					'accumulated_total' => $transferred_amount,
					'days_accumulated' => $accumulation['days_accumulated'],
					'transferred_to_earnings' => $transferred_amount,
					'action' => 'period_end_transfer',
				);
			} else {
				update_user_meta( $user_id, '_wc_tp_daily_accumulation', $accumulation );

				$result = array(
					'message' => 'Salary accumulated (test) - ' . max( 0, $days_remaining ) . ' more days until period end',
					'salary_type' => $salary_type,
					'salary_amount' => $salary_amount,
					'salary_frequency' => $salary_frequency,
					'daily_rate' => $daily_rate,
					'accumulated_total' => $accumulation['accumulated_total'],
					'days_accumulated' => $accumulation['days_accumulated'],
					'expected_days_for_period' => $expected_days,
					'days_remaining' => max( 0, $days_remaining ),
					'period_start' => $accumulation['period_start'],
					'period_end' => $accumulation['period_end'],
					'action' => 'accumulation',
				);
			}
		}

		wp_send_json_success( $result );
	}

	/**
	 * Get employee salary status
	 */
	public static function ajax_get_employee_salary_status() {
		check_ajax_referer( 'wc_team_payroll_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		}

		$user_id = intval( $_POST['user_id'] );
		$user = get_user_by( 'id', $user_id );

		if ( ! $user ) {
			wp_send_json_error( array( 'message' => 'Employee not found' ) );
		}

		// Get salary configuration
		$is_fixed = get_user_meta( $user_id, '_wc_tp_fixed_salary', true );
		$is_combined = get_user_meta( $user_id, '_wc_tp_combined_salary', true );
		$salary_amount = floatval( get_user_meta( $user_id, '_wc_tp_salary_amount', true ) );
		$salary_frequency = get_user_meta( $user_id, '_wc_tp_salary_frequency', true );

		if ( $is_fixed ) {
			$salary_type = 'fixed';
		} elseif ( $is_combined ) {
			$salary_type = 'combined';
		} else {
			$salary_type = 'commission';
		}

		// Get accumulation
		$accumulation = get_user_meta( $user_id, '_wc_tp_daily_accumulation', true );
		if ( ! is_array( $accumulation ) ) {
			$accumulation = array(
				'accumulated_total' => 0,
				'days_accumulated'  => 0,
				'period_start'      => null,
				'period_end'        => null,
			);
		}

		// Get earnings
		$commission_earnings = 0;
		$core_engine = new WC_Team_Payroll_Core_Engine();
		if ( method_exists( $core_engine, 'get_user_commission_earnings' ) ) {
			$commission_earnings = $core_engine->get_user_commission_earnings( $user_id );
		}

		$salary_earnings = floatval( get_user_meta( $user_id, '_wc_tp_total_earnings', true ) );
		if ( ! $salary_earnings ) {
			$salary_earnings = 0;
		}

		$total_earnings = $commission_earnings + $salary_earnings;

		// Get recent transactions
		$transactions = get_user_meta( $user_id, '_wc_tp_salary_transactions', true );
		if ( ! is_array( $transactions ) ) {
			$transactions = array();
		}
		$recent_transactions = array_slice( array_reverse( $transactions ), 0, 5 );

		$result = array(
			'employee' => array(
				'id' => $user_id,
				'name' => $user->display_name,
				'email' => $user->user_email,
				'registered' => $user->user_registered,
			),
			'salary_configuration' => array(
				'type' => $salary_type,
				'amount' => $salary_amount,
				'frequency' => $salary_frequency,
				'daily_rate' => $salary_amount ? self::calculate_daily_rate( $salary_amount, $salary_frequency ) : 0,
			),
			'pending_accumulation' => $accumulation,
			'earnings' => array(
				'commission' => $commission_earnings,
				'salary' => $salary_earnings,
				'total' => $total_earnings,
			),
			'recent_transactions' => $recent_transactions,
		);

		wp_send_json_success( $result );
	}

	/**
	 * Manually trigger cron job
	 */
	public static function ajax_manual_trigger_cron() {
		check_ajax_referer( 'wc_team_payroll_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		}

		$processed = 0;
		$transferred = 0;

		// Process all batches
		for ( $i = 0; $i < 10; $i++ ) {
			do_action( 'wc_tp_daily_salary_accumulation', $i );
			$processed += 50; // Approximate
		}

		wp_send_json_success( array(
			'message' => 'Cron job triggered manually',
			'batches_processed' => 10,
			'employees_processed' => $processed,
			'timestamp' => current_time( 'mysql' ),
		) );
	}

	/**
	 * Reset employee salary data
	 */
	public static function ajax_reset_employee_salary() {
		check_ajax_referer( 'wc_team_payroll_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		}

		$user_id = intval( $_POST['user_id'] );

		// Delete salary data
		delete_user_meta( $user_id, '_wc_tp_daily_accumulation' );
		delete_user_meta( $user_id, '_wc_tp_total_earnings' );
		delete_user_meta( $user_id, '_wc_tp_salary_transactions' );

		wp_send_json_success( array(
			'message' => 'Employee salary data reset',
			'user_id' => $user_id,
		) );
	}

	/**
	 * Calculate daily rate
	 */
	private static function calculate_daily_rate( $salary_amount, $salary_frequency ) {
		if ( 'daily' === $salary_frequency ) {
			return $salary_amount;
		} elseif ( 'weekly' === $salary_frequency ) {
			return $salary_amount / 7;
		} elseif ( 'monthly' === $salary_frequency ) {
			$days_in_month = date( 't' );
			return $salary_amount / $days_in_month;
		}

		return 0;
	}
}

// Initialize debug helpers
if ( is_admin() ) {
	WC_Team_Payroll_Salary_Debug::init();
}
