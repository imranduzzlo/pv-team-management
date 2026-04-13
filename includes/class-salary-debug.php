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
		// Add debug page to admin menu
		add_action( 'admin_menu', array( __CLASS__, 'add_debug_menu' ) );
		
		// Register AJAX handlers for testing
		add_action( 'wp_ajax_wc_tp_test_salary_accumulation', array( __CLASS__, 'ajax_test_salary_accumulation' ) );
		add_action( 'wp_ajax_wc_tp_get_employee_salary_status', array( __CLASS__, 'ajax_get_employee_salary_status' ) );
		add_action( 'wp_ajax_wc_tp_manual_trigger_cron', array( __CLASS__, 'ajax_manual_trigger_cron' ) );
		add_action( 'wp_ajax_wc_tp_reset_employee_salary', array( __CLASS__, 'ajax_reset_employee_salary' ) );
	}

	/**
	 * Add debug menu to admin
	 */
	public static function add_debug_menu() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		add_submenu_page(
			'woocommerce',
			'Salary Debug',
			'Salary Debug',
			'manage_options',
			'wc-tp-salary-debug',
			array( __CLASS__, 'render_debug_page' )
		);
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
							$salary_type = get_user_meta( $employee->ID, '_wc_tp_fixed_salary', true ) ? 'fixed' : 
										   get_user_meta( $employee->ID, '_wc_tp_combined_salary', true ) ? 'combined' : 'commission';
							$salary_amount = get_user_meta( $employee->ID, '_wc_tp_salary_amount', true );
							$salary_frequency = get_user_meta( $employee->ID, '_wc_tp_salary_frequency', true );
							
							$label = $employee->display_name . ' (' . $salary_type;
							if ( $salary_amount ) {
								$label .= ' - ' . wc_price( $salary_amount ) . '/' . $salary_frequency;
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
					<button class="button button-danger" id="btn-reset-salary" style="background: #dc3545; color: white;">Reset Employee Salary</button>
				</div>

				<div id="test-results" style="margin-top: 20px; padding: 15px; background: #f5f5f5; border-radius: 4px; display: none;">
					<h3>📊 Test Results:</h3>
					<pre id="results-content" style="background: #fff; padding: 10px; border-radius: 4px; overflow-x: auto;"></pre>
				</div>
			</div>

			<div style="background: #fff; padding: 20px; border-radius: 8px; margin-top: 20px;">
				<h2>📋 How to Test</h2>
				<ol>
					<li><strong>Select an employee</strong> with fixed or combined salary</li>
					<li><strong>Click "Test Salary Accumulation"</strong> to simulate daily accumulation</li>
					<li><strong>Click "Get Current Status"</strong> to see:
						<ul>
							<li>Current salary configuration</li>
							<li>Pending accumulation</li>
							<li>Total earnings</li>
							<li>Recent transactions</li>
						</ul>
					</li>
					<li><strong>Click "Manually Trigger Cron"</strong> to run the cron job immediately</li>
					<li><strong>Check results</strong> to verify accumulation is working</li>
				</ol>
			</div>

			<div style="background: #fff; padding: 20px; border-radius: 8px; margin-top: 20px;">
				<h2>🔍 What to Look For</h2>
				<table style="width: 100%; border-collapse: collapse;">
					<tr style="background: #f5f5f5;">
						<th style="padding: 10px; text-align: left; border: 1px solid #ddd;">Check</th>
						<th style="padding: 10px; text-align: left; border: 1px solid #ddd;">Expected Result</th>
					</tr>
					<tr>
						<td style="padding: 10px; border: 1px solid #ddd;"><strong>Daily Rate</strong></td>
						<td style="padding: 10px; border: 1px solid #ddd;">Salary ÷ frequency days (e.g., 700/7 = 100 for weekly)</td>
					</tr>
					<tr>
						<td style="padding: 10px; border: 1px solid #ddd;"><strong>Accumulated Total</strong></td>
						<td style="padding: 10px; border: 1px solid #ddd;">Increases by daily rate each test</td>
					</tr>
					<tr>
						<td style="padding: 10px; border: 1px solid #ddd;"><strong>Days Accumulated</strong></td>
						<td style="padding: 10px; border: 1px solid #ddd;">Increases by 1 each test</td>
					</tr>
					<tr>
						<td style="padding: 10px; border: 1px solid #ddd;"><strong>Total Earnings</strong></td>
						<td style="padding: 10px; border: 1px solid #ddd;">Increases when period ends (week/month)</td>
					</tr>
					<tr>
						<td style="padding: 10px; border: 1px solid #ddd;"><strong>Transactions</strong></td>
						<td style="padding: 10px; border: 1px solid #ddd;">New entries for each accumulation/transfer</td>
					</tr>
				</table>
			</div>

			<div style="background: #fff; padding: 20px; border-radius: 8px; margin-top: 20px;">
				<h2>⚙️ Testing Scenarios</h2>
				
				<h3>Daily Frequency Test</h3>
				<ol>
					<li>Set employee salary: $100/day (Fixed)</li>
					<li>Click "Test Salary Accumulation" once</li>
					<li>Check: Total Earnings should increase by $100</li>
					<li>Result: ✅ Daily salary added directly to earnings</li>
				</ol>

				<h3>Weekly Frequency Test</h3>
				<ol>
					<li>Set employee salary: $700/week (Fixed)</li>
					<li>Click "Test Salary Accumulation" 7 times</li>
					<li>Check: Accumulated Total should be $700 after 7 tests</li>
					<li>Click "Manually Trigger Cron" on day 7</li>
					<li>Check: Total Earnings should increase by $700</li>
					<li>Result: ✅ Weekly salary accumulated and transferred</li>
				</ol>

				<h3>Monthly Frequency Test</h3>
				<ol>
					<li>Set employee salary: $3,000/month (Fixed)</li>
					<li>Click "Test Salary Accumulation" 28 times</li>
					<li>Check: Accumulated Total should be ~$3,000 after 28 tests</li>
					<li>Click "Manually Trigger Cron" on day 28</li>
					<li>Check: Total Earnings should increase by ~$3,000</li>
					<li>Result: ✅ Monthly salary accumulated and transferred</li>
				</ol>

				<h3>Salary Change Test</h3>
				<ol>
					<li>Set employee salary: $700/week (Fixed)</li>
					<li>Click "Test Salary Accumulation" 3 times</li>
					<li>Check: Accumulated Total should be $300</li>
					<li>Change salary to: $1,000/week</li>
					<li>Click "Get Current Status"</li>
					<li>Check: Previous $300 transferred to Total Earnings, new accumulation started</li>
					<li>Result: ✅ Salary change handled correctly</li>
				</ol>
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
						alert('Please select an employee');
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
							} else {
								alert('Error: ' + response.data.message);
							}
						},
						error: function() {
							alert('AJAX error');
						}
					});
				});

				$('#btn-get-status').on('click', function() {
					const employeeId = $('#test-employee-id').val();
					if (!employeeId) {
						alert('Please select an employee');
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
							} else {
								alert('Error: ' + response.data.message);
							}
						},
						error: function() {
							alert('AJAX error');
						}
					});
				});

				$('#btn-manual-cron').on('click', function() {
					if (!confirm('Manually trigger cron job? This will process all employees.')) {
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
							} else {
								alert('Error: ' + response.data.message);
							}
						},
						error: function() {
							alert('AJAX error');
						}
					});
				});

				$('#btn-reset-salary').on('click', function() {
					const employeeId = $('#test-employee-id').val();
					if (!employeeId) {
						alert('Please select an employee');
						return;
					}

					if (!confirm('Reset all salary data for this employee? This cannot be undone.')) {
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
								alert('Employee salary data reset successfully');
							} else {
								alert('Error: ' + response.data.message);
							}
						},
						error: function() {
							alert('AJAX error');
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

		$salary_type = $is_fixed ? 'fixed' : ( $is_combined ? 'combined' : 'commission' );

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

			update_user_meta( $user_id, '_wc_tp_daily_accumulation', $accumulation );

			$result = array(
				'message' => 'Salary accumulated (test)',
				'salary_type' => $salary_type,
				'salary_amount' => $salary_amount,
				'salary_frequency' => $salary_frequency,
				'daily_rate' => $daily_rate,
				'accumulated_total' => $accumulation['accumulated_total'],
				'days_accumulated' => $accumulation['days_accumulated'],
				'period_start' => $accumulation['period_start'],
				'period_end' => $accumulation['period_end'],
				'action' => 'accumulation',
			);
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

		$salary_type = $is_fixed ? 'fixed' : ( $is_combined ? 'combined' : 'commission' );

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
