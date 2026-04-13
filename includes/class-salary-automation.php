<?php
/**
 * Salary Automation System
 * Handles automatic base salary addition for fixed and combined salary types
 */

class WC_Team_Payroll_Salary_Automation {

	/**
	 * Initialize the salary automation system
	 */
	public static function init() {
		// Register cron hooks
		add_action( 'wc_tp_daily_salary_accumulation', array( __CLASS__, 'process_daily_salary_batch' ), 10, 1 );
		
		// Hook into salary changes
		add_action( 'wc_tp_salary_changed', array( __CLASS__, 'handle_salary_change' ), 10, 2 );
		
		// Schedule cron jobs on plugin activation
		add_action( 'init', array( __CLASS__, 'schedule_cron_jobs' ) );
		
		// Clear cron jobs on plugin deactivation
		register_deactivation_hook( WC_TEAM_PAYROLL_PATH . 'woocommerce-team-payroll.php', array( __CLASS__, 'clear_cron_jobs' ) );
	}

	/**
	 * Schedule staggered cron jobs (11:50 PM - 11:59 PM)
	 * Process employees in batches to avoid timeout
	 */
	public static function schedule_cron_jobs() {
		// Check if already scheduled
		if ( wp_next_scheduled( 'wc_tp_daily_salary_accumulation', array( 0 ) ) ) {
			return;
		}

		// Schedule 10 cron jobs, one per minute from 11:50 PM to 11:59 PM
		// Each processes a batch of 50 employees
		for ( $i = 0; $i < 10; $i++ ) {
			$time = strtotime( 'today 23:50:00' ) + ( $i * 60 ); // Every minute
			
			// If time has passed today, schedule for tomorrow
			if ( $time < time() ) {
				$time = strtotime( 'tomorrow 23:50:00' ) + ( $i * 60 );
			}
			
			wp_schedule_event( $time, 'daily', 'wc_tp_daily_salary_accumulation', array( $i ) );
		}
	}

	/**
	 * Clear all scheduled cron jobs
	 */
	public static function clear_cron_jobs() {
		for ( $i = 0; $i < 10; $i++ ) {
			$timestamp = wp_next_scheduled( 'wc_tp_daily_salary_accumulation', array( $i ) );
			if ( $timestamp ) {
				wp_unschedule_event( $timestamp, 'wc_tp_daily_salary_accumulation', array( $i ) );
			}
		}
	}

	/**
	 * Process daily salary accumulation for a batch of employees
	 * 
	 * @param int $batch_number Batch number (0-9)
	 */
	public static function process_daily_salary_batch( $batch_number ) {
		global $wpdb;

		$offset = $batch_number * 50;

		// Get employees with fixed or combined salary (OPTIMIZED: Direct SQL query)
		$employees = $wpdb->get_results( $wpdb->prepare( "
			SELECT u.ID as user_id,
			       u.user_registered,
			       MAX(CASE WHEN um.meta_key = '_wc_tp_fixed_salary' THEN um.meta_value END) as is_fixed,
			       MAX(CASE WHEN um.meta_key = '_wc_tp_combined_salary' THEN um.meta_value END) as is_combined,
			       MAX(CASE WHEN um.meta_key = '_wc_tp_salary_amount' THEN um.meta_value END) as salary_amount,
			       MAX(CASE WHEN um.meta_key = '_wc_tp_salary_frequency' THEN um.meta_value END) as salary_frequency,
			       MAX(CASE WHEN um.meta_key = '_wc_tp_employee_status' THEN um.meta_value END) as employee_status
			FROM {$wpdb->users} u
			INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
			WHERE um.meta_key IN ('_wc_tp_fixed_salary', '_wc_tp_combined_salary', '_wc_tp_salary_amount', '_wc_tp_salary_frequency', '_wc_tp_employee_status')
			GROUP BY u.ID
			HAVING (is_fixed = '1' OR is_combined = '1')
			   AND salary_amount > 0
			   AND salary_frequency IS NOT NULL
			   AND (employee_status IS NULL OR employee_status = 'active')
			LIMIT 50 OFFSET %d
		", $offset ) );

		foreach ( $employees as $employee ) {
			self::accumulate_daily_salary( $employee );
		}
	}

	/**
	 * Accumulate daily salary for a single employee
	 * 
	 * @param object $employee Employee data from database
	 */
	private static function accumulate_daily_salary( $employee ) {
		$user_id = $employee->user_id;
		$salary_amount = floatval( $employee->salary_amount );
		$salary_frequency = $employee->salary_frequency;
		$salary_type = $employee->is_fixed ? 'fixed' : 'combined';

		// Calculate daily rate based on frequency
		$daily_rate = self::calculate_daily_rate( $salary_amount, $salary_frequency );

		if ( $daily_rate <= 0 ) {
			return; // Invalid rate
		}

		// Handle based on frequency
		if ( 'daily' === $salary_frequency ) {
			// Daily frequency: Add directly to total earnings (no DB storage)
			self::add_to_total_earnings( $user_id, $daily_rate, 'daily_salary' );
			self::log_salary_transaction( $user_id, $daily_rate, 'daily_transfer', 'Daily salary added directly to earnings' );
		} elseif ( 'weekly' === $salary_frequency ) {
			// Weekly frequency: Accumulate in DB, transfer at week end
			self::accumulate_weekly_salary( $user_id, $daily_rate, $salary_amount, $salary_type );
		} elseif ( 'monthly' === $salary_frequency ) {
			// Monthly frequency: Accumulate in DB, transfer at month end
			self::accumulate_monthly_salary( $user_id, $daily_rate, $salary_amount, $salary_type );
		}
	}

	/**
	 * Calculate daily rate based on salary amount and frequency
	 * 
	 * @param float $salary_amount Salary amount
	 * @param string $salary_frequency Frequency (daily/weekly/monthly)
	 * @return float Daily rate
	 */
	private static function calculate_daily_rate( $salary_amount, $salary_frequency ) {
		if ( 'daily' === $salary_frequency ) {
			return $salary_amount;
		} elseif ( 'weekly' === $salary_frequency ) {
			return $salary_amount / 7;
		} elseif ( 'monthly' === $salary_frequency ) {
			$days_in_month = date( 't' ); // Auto-detect days in current month
			return $salary_amount / $days_in_month;
		}

		return 0;
	}

	/**
	 * Accumulate weekly salary and transfer at week end
	 * 
	 * @param int $user_id User ID
	 * @param float $daily_rate Daily rate
	 * @param float $salary_amount Full salary amount
	 * @param string $salary_type Salary type (fixed/combined)
	 */
	private static function accumulate_weekly_salary( $user_id, $daily_rate, $salary_amount, $salary_type ) {
		// Get current accumulation
		$accumulation = get_user_meta( $user_id, '_wc_tp_daily_accumulation', true );
		
		if ( ! is_array( $accumulation ) ) {
			// Initialize new accumulation
			$week_start_day = get_option( 'start_of_week', 0 ); // 0 = Sunday, 1 = Monday, etc.
			$today = new DateTime();
			$days_since_week_start = ( $today->format( 'w' ) - $week_start_day + 7 ) % 7;
			$period_start = clone $today;
			$period_start->modify( "-{$days_since_week_start} days" );
			$period_end = clone $period_start;
			$period_end->modify( '+6 days' );

			$accumulation = array(
				'user_id'           => $user_id,
				'salary_type'       => $salary_type,
				'salary_amount'     => $salary_amount,
				'salary_frequency'  => 'weekly',
				'daily_rate'        => $daily_rate,
				'accumulated_total' => 0,
				'period_start'      => $period_start->format( 'Y-m-d' ),
				'period_end'        => $period_end->format( 'Y-m-d' ),
				'days_accumulated'  => 0,
				'last_updated'      => current_time( 'mysql' ),
				'status'            => 'active',
			);
		}

		// Add today's accumulation
		$accumulation['accumulated_total'] += $daily_rate;
		$accumulation['days_accumulated']++;
		$accumulation['last_updated'] = current_time( 'mysql' );

		// Check if week end (Saturday or configured end day)
		$week_start_day = get_option( 'start_of_week', 0 );
		$week_end_day = ( $week_start_day + 6 ) % 7;
		$today_day = date( 'w' );

		if ( $today_day == $week_end_day ) {
			// Transfer accumulated total to earnings
			self::add_to_total_earnings( $user_id, $accumulation['accumulated_total'], 'weekly_salary' );
			self::log_salary_transaction( $user_id, $accumulation['accumulated_total'], 'weekly_transfer', 'Weekly salary transferred to earnings' );

			// Clear accumulation and start new period
			delete_user_meta( $user_id, '_wc_tp_daily_accumulation' );
		} else {
			// Save updated accumulation
			update_user_meta( $user_id, '_wc_tp_daily_accumulation', $accumulation );
		}
	}

	/**
	 * Accumulate monthly salary and transfer at month end
	 * 
	 * @param int $user_id User ID
	 * @param float $daily_rate Daily rate
	 * @param float $salary_amount Full salary amount
	 * @param string $salary_type Salary type (fixed/combined)
	 */
	private static function accumulate_monthly_salary( $user_id, $daily_rate, $salary_amount, $salary_type ) {
		// Get current accumulation
		$accumulation = get_user_meta( $user_id, '_wc_tp_daily_accumulation', true );
		
		if ( ! is_array( $accumulation ) ) {
			// Initialize new accumulation
			$period_start = date( 'Y-m-01' );
			$period_end = date( 'Y-m-t' );

			$accumulation = array(
				'user_id'           => $user_id,
				'salary_type'       => $salary_type,
				'salary_amount'     => $salary_amount,
				'salary_frequency'  => 'monthly',
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

		// Check if month end
		$today = date( 'Y-m-d' );
		$month_end = date( 'Y-m-t' );

		if ( $today === $month_end ) {
			// Transfer accumulated total to earnings
			self::add_to_total_earnings( $user_id, $accumulation['accumulated_total'], 'monthly_salary' );
			self::log_salary_transaction( $user_id, $accumulation['accumulated_total'], 'monthly_transfer', 'Monthly salary transferred to earnings' );

			// Clear accumulation and start new period
			delete_user_meta( $user_id, '_wc_tp_daily_accumulation' );
		} else {
			// Save updated accumulation
			update_user_meta( $user_id, '_wc_tp_daily_accumulation', $accumulation );
		}
	}

	/**
	 * Handle salary change (type, amount, or frequency)
	 * 
	 * @param int $user_id User ID
	 * @param array $change_data Change data (old and new values)
	 */
	public static function handle_salary_change( $user_id, $change_data ) {
		// Get current accumulation
		$accumulation = get_user_meta( $user_id, '_wc_tp_daily_accumulation', true );

		// Step 1: Transfer accumulated total immediately (if any)
		if ( is_array( $accumulation ) && isset( $accumulation['accumulated_total'] ) && $accumulation['accumulated_total'] > 0 ) {
			self::add_to_total_earnings( $user_id, $accumulation['accumulated_total'], 'partial_period_salary' );
			self::log_salary_transaction( $user_id, $accumulation['accumulated_total'], 'partial_transfer', 'Partial period salary transferred due to salary change' );
		}

		// Step 2: Clear accumulation
		delete_user_meta( $user_id, '_wc_tp_daily_accumulation' );

		// Step 3: Today's accumulation will be added at 11:59 PM with NEW rate
		// (Handled by daily cron job)
	}

	/**
	 * Add amount to user's total earnings
	 * 
	 * @param int $user_id User ID
	 * @param float $amount Amount to add
	 * @param string $source Source of earnings (daily_salary/weekly_salary/monthly_salary/partial_period_salary)
	 */
	private static function add_to_total_earnings( $user_id, $amount, $source = 'salary' ) {
		// Get current total earnings
		$total_earnings = get_user_meta( $user_id, '_wc_tp_total_earnings', true );
		if ( ! $total_earnings ) {
			$total_earnings = 0;
		}

		// Add amount
		$total_earnings += $amount;

		// Update total earnings
		update_user_meta( $user_id, '_wc_tp_total_earnings', $total_earnings );
	}

	/**
	 * Log salary transaction for audit trail
	 * 
	 * @param int $user_id User ID
	 * @param float $amount Amount
	 * @param string $type Transaction type
	 * @param string $note Note
	 */
	private static function log_salary_transaction( $user_id, $amount, $type, $note = '' ) {
		$log = get_user_meta( $user_id, '_wc_tp_salary_transactions', true );
		if ( ! is_array( $log ) ) {
			$log = array();
		}

		$log[] = array(
			'date'   => current_time( 'mysql' ),
			'amount' => $amount,
			'type'   => $type,
			'note'   => $note,
		);

		// Keep only last 100 transactions to avoid bloat
		if ( count( $log ) > 100 ) {
			$log = array_slice( $log, -100 );
		}

		update_user_meta( $user_id, '_wc_tp_salary_transactions', $log );
	}

	/**
	 * Get user's total earnings (commission + base salary)
	 * 
	 * @param int $user_id User ID
	 * @return float Total earnings
	 */
	public static function get_user_total_earnings( $user_id ) {
		// Get commission earnings from orders
		$core_engine = new WC_Team_Payroll_Core_Engine();
		$commission_earnings = $core_engine->get_user_total_earnings( $user_id );

		// Get base salary earnings
		$salary_earnings = get_user_meta( $user_id, '_wc_tp_total_earnings', true );
		if ( ! $salary_earnings ) {
			$salary_earnings = 0;
		}

		return $commission_earnings + $salary_earnings;
	}

	/**
	 * Get user's pending accumulation (not yet transferred)
	 * 
	 * @param int $user_id User ID
	 * @return array Accumulation data
	 */
	public static function get_user_pending_accumulation( $user_id ) {
		$accumulation = get_user_meta( $user_id, '_wc_tp_daily_accumulation', true );
		
		if ( ! is_array( $accumulation ) ) {
			return array(
				'accumulated_total' => 0,
				'days_accumulated'  => 0,
				'period_start'      => null,
				'period_end'        => null,
				'next_transfer'     => null,
			);
		}

		// Calculate next transfer date
		$next_transfer = null;
		if ( isset( $accumulation['salary_frequency'] ) ) {
			if ( 'weekly' === $accumulation['salary_frequency'] ) {
				$next_transfer = $accumulation['period_end'];
			} elseif ( 'monthly' === $accumulation['salary_frequency'] ) {
				$next_transfer = date( 'Y-m-t' );
			}
		}

		return array(
			'accumulated_total' => $accumulation['accumulated_total'],
			'days_accumulated'  => $accumulation['days_accumulated'],
			'period_start'      => $accumulation['period_start'],
			'period_end'        => $accumulation['period_end'],
			'next_transfer'     => $next_transfer,
		);
	}

	/**
	 * Get user's salary transaction log
	 * 
	 * @param int $user_id User ID
	 * @param int $limit Limit number of transactions
	 * @return array Transaction log
	 */
	public static function get_user_salary_transactions( $user_id, $limit = 20 ) {
		$log = get_user_meta( $user_id, '_wc_tp_salary_transactions', true );
		if ( ! is_array( $log ) ) {
			return array();
		}

		// Return latest transactions
		return array_slice( array_reverse( $log ), 0, $limit );
	}
}

// Initialize the salary automation system
WC_Team_Payroll_Salary_Automation::init();
