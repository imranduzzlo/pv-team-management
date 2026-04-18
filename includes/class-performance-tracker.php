<?php
/**
 * Performance Tracker Class
 * Handles Goals, Achievements, and Baselines tracking for employees
 *
 * @package WooCommerce Team Payroll
 * @since 1.2.8
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Team_Payroll_Performance_Tracker {

	/**
	 * Constructor
	 */
	public function __construct() {
		// Initialize hooks
		$this->init_hooks();
	}

	/**
	 * Initialize hooks
	 */
	private function init_hooks() {
		// AJAX handlers
		add_action( 'wp_ajax_wc_tp_get_user_goal_progress', array( $this, 'ajax_get_user_goal_progress' ) );
		add_action( 'wp_ajax_wc_tp_get_user_achievements', array( $this, 'ajax_get_user_achievements' ) );
		add_action( 'wp_ajax_wc_tp_get_user_baselines', array( $this, 'ajax_get_user_baselines' ) );
		add_action( 'wp_ajax_wc_tp_recalculate_performance', array( $this, 'ajax_recalculate_performance' ) );

		// Cron jobs
		add_action( 'wc_tp_daily_baseline_update', array( $this, 'cron_update_baselines' ) );
		add_action( 'wc_tp_check_achievements', array( $this, 'cron_check_achievements' ) );
		add_action( 'wc_tp_finalize_period_goals', array( $this, 'cron_finalize_period_goals' ) );

		// Schedule cron jobs if not scheduled
		if ( ! wp_next_scheduled( 'wc_tp_daily_baseline_update' ) ) {
			wp_schedule_event( time(), 'daily', 'wc_tp_daily_baseline_update' );
		}
		if ( ! wp_next_scheduled( 'wc_tp_check_achievements' ) ) {
			wp_schedule_event( time(), 'hourly', 'wc_tp_check_achievements' );
		}
		if ( ! wp_next_scheduled( 'wc_tp_finalize_period_goals' ) ) {
			wp_schedule_event( time(), 'daily', 'wc_tp_finalize_period_goals' );
		}
	}

	/**
	 * Static method to initialize the class
	 */
	public static function init() {
		return new self();
	}

	// ============================================================================
	// HELPER FUNCTIONS - Data Calculation
	// ============================================================================

	/**
	 * Get attributed order total for user in a date range
	 * Uses same logic as Performance Metrics "Total Order Value"
	 *
	 * @param int $user_id User ID
	 * @param string $start_date Start date (Y-m-d)
	 * @param string $end_date End date (Y-m-d)
	 * @param string $role_filter Role filter (agent, processor, all)
	 * @param string $status_filter Status filter
	 * @return float Attributed order total
	 */
	private function get_attributed_order_total( $user_id, $start_date, $end_date, $role_filter = 'all', $status_filter = 'all' ) {
		// Get commission calculation statuses from settings using the correct method
		$commission_statuses = WC_Team_Payroll_Core_Engine::get_commission_calculation_statuses();

		// Prepare statuses to query
		$statuses_to_query = array();
		foreach ( $commission_statuses as $status ) {
			$statuses_to_query[] = 'wc-' . $status;
		}

		$attributed_total = 0;

		// Query orders where user is agent
		if ( $role_filter === 'all' || $role_filter === 'agent' ) {
			$agent_args = array(
				'limit'        => -1,
				'meta_key'     => '_primary_agent_id',
				'meta_value'   => $user_id,
				'status'       => $statuses_to_query,
				'date_created' => $start_date . ' 00:00:00...' . $end_date . ' 23:59:59',
				'return'       => 'ids',
			);

			$agent_orders = wc_get_orders( $agent_args );

			foreach ( $agent_orders as $order_id ) {
				$order = wc_get_order( $order_id );
				if ( ! $order ) {
					continue;
				}

				$commission_data = $order->get_meta( '_commission_data' );
				if ( $commission_data && isset( $commission_data['agent_order_value'] ) ) {
					$attributed_total += floatval( $commission_data['agent_order_value'] );
				}
			}
		}

		// Query orders where user is processor
		if ( $role_filter === 'all' || $role_filter === 'processor' ) {
			$processor_args = array(
				'limit'        => -1,
				'meta_key'     => '_processor_user_id',
				'meta_value'   => $user_id,
				'status'       => $statuses_to_query,
				'date_created' => $start_date . ' 00:00:00...' . $end_date . ' 23:59:59',
				'return'       => 'ids',
			);

			$processor_orders = wc_get_orders( $processor_args );

			foreach ( $processor_orders as $order_id ) {
				$order = wc_get_order( $order_id );
				if ( ! $order ) {
					continue;
				}

				$commission_data = $order->get_meta( '_commission_data' );
				if ( $commission_data && isset( $commission_data['processor_order_value'] ) ) {
					$attributed_total += floatval( $commission_data['processor_order_value'] );
				}
			}
		}

		return $attributed_total;
	}

	/**
	 * Get order count for user in a date range
	 *
	 * @param int $user_id User ID
	 * @param string $start_date Start date (Y-m-d)
	 * @param string $end_date End date (Y-m-d)
	 * @param string $role_filter Role filter (agent, processor, all)
	 * @return int Order count
	 */
	private function get_order_count( $user_id, $start_date, $end_date, $role_filter = 'all' ) {
		// Get commission calculation statuses from settings using the correct method
		$commission_statuses = WC_Team_Payroll_Core_Engine::get_commission_calculation_statuses();

		// Prepare statuses to query
		$statuses_to_query = array();
		foreach ( $commission_statuses as $status ) {
			$statuses_to_query[] = 'wc-' . $status;
		}

		$order_ids = array();

		// Query orders where user is agent
		if ( $role_filter === 'all' || $role_filter === 'agent' ) {
			$agent_args = array(
				'limit'        => -1,
				'meta_key'     => '_primary_agent_id',
				'meta_value'   => $user_id,
				'status'       => $statuses_to_query,
				'date_created' => $start_date . ' 00:00:00...' . $end_date . ' 23:59:59',
				'return'       => 'ids',
			);

			$agent_orders = wc_get_orders( $agent_args );
			$order_ids = array_merge( $order_ids, $agent_orders );
		}

		// Query orders where user is processor
		if ( $role_filter === 'all' || $role_filter === 'processor' ) {
			$processor_args = array(
				'limit'        => -1,
				'meta_key'     => '_processor_user_id',
				'meta_value'   => $user_id,
				'status'       => $statuses_to_query,
				'date_created' => $start_date . ' 00:00:00...' . $end_date . ' 23:59:59',
				'return'       => 'ids',
			);

			$processor_orders = wc_get_orders( $processor_args );
			$order_ids = array_merge( $order_ids, $processor_orders );
		}

		// Remove duplicates (if user is both agent and processor on same order)
		$order_ids = array_unique( $order_ids );

		return count( $order_ids );
	}

	/**
	 * Get average order value for user in a date range
	 *
	 * @param int $user_id User ID
	 * @param string $start_date Start date (Y-m-d)
	 * @param string $end_date End date (Y-m-d)
	 * @param string $role_filter Role filter (agent, processor, all)
	 * @return float Average order value
	 */
	private function get_average_order_value( $user_id, $start_date, $end_date, $role_filter = 'all' ) {
		$attributed_total = $this->get_attributed_order_total( $user_id, $start_date, $end_date, $role_filter );
		$order_count = $this->get_order_count( $user_id, $start_date, $end_date, $role_filter );

		if ( $order_count === 0 ) {
			return 0;
		}

		return $attributed_total / $order_count;
	}

	/**
	 * Get period dates based on period type
	 *
	 * @param string $period_type Period type (weekly, monthly, quarterly, yearly)
	 * @param string $date Optional date to calculate period for (default: today)
	 * @return array Array with 'start' and 'end' dates
	 */
	private function get_period_dates( $period_type, $date = null ) {
		$timezone = wp_timezone();
		$now = $date ? new DateTime( $date, $timezone ) : new DateTime( 'now', $timezone );

		switch ( $period_type ) {
			case 'weekly':
				$start = clone $now;
				$start->modify( 'monday this week' );
				$end = clone $start;
				$end->modify( '+6 days' );
				break;

			case 'monthly':
				$start = new DateTime( $now->format( 'Y-m-01' ), $timezone );
				$end = clone $start;
				$end->modify( 'last day of this month' );
				break;

			case 'quarterly':
				$month = (int) $now->format( 'n' );
				$quarter_start_month = ( ceil( $month / 3 ) - 1 ) * 3 + 1;
				$start = new DateTime( $now->format( 'Y' ) . '-' . str_pad( $quarter_start_month, 2, '0', STR_PAD_LEFT ) . '-01', $timezone );
				$end = clone $start;
				$end->modify( '+2 months' );
				$end->modify( 'last day of this month' );
				break;

			case 'yearly':
				$start = new DateTime( $now->format( 'Y' ) . '-01-01', $timezone );
				$end = new DateTime( $now->format( 'Y' ) . '-12-31', $timezone );
				break;

			default:
				$start = new DateTime( $now->format( 'Y-m-01' ), $timezone );
				$end = clone $start;
				$end->modify( 'last day of this month' );
		}

		return array(
			'start' => $start->format( 'Y-m-d' ),
			'end'   => $end->format( 'Y-m-d' ),
			'period_id' => $start->format( 'Y-m' ),
		);
	}

	// ============================================================================
	// GOALS TRACKING
	// ============================================================================

	/**
	 * Calculate and update current goal progress for a user
	 *
	 * @param int $user_id User ID
	 * @return array Goal progress data
	 */
	public function update_goal_progress( $user_id ) {
		// Get user role
		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			return array();
		}

		$user_roles = $user->roles;
		$employee_role = '';

		// Find employee role (agent, processor, etc.)
		$all_roles = $this->get_employee_roles();
		
		foreach ( $user_roles as $role ) {
			if ( isset( $all_roles[ $role ] ) ) {
				$employee_role = $role;
				break;
			}
		}

		if ( empty( $employee_role ) ) {
			return array();
		}

		// Get goals configuration
		$goals_config = get_option( 'wc_tp_goals_config', array() );
		
		$period_type = isset( $goals_config['period'] ) ? $goals_config['period'] : 'monthly';
		$role_goals = isset( $goals_config['roles'][ $employee_role ] ) ? $goals_config['roles'][ $employee_role ] : array();

		if ( empty( $role_goals ) ) {
			return array();
		}

		// Get current period dates
		$period_dates = $this->get_period_dates( $period_type );

		// Calculate current values
		$attributed_total = $this->get_attributed_order_total( $user_id, $period_dates['start'], $period_dates['end'] );
		$order_count = $this->get_order_count( $user_id, $period_dates['start'], $period_dates['end'] );
		$aov = $this->get_average_order_value( $user_id, $period_dates['start'], $period_dates['end'] );

		// Build progress data
		$progress_data = array(
			'period' => $period_dates['period_id'],
			'period_type' => $period_type,
			'period_start' => $period_dates['start'],
			'period_end' => $period_dates['end'],
			'order_value' => $this->calculate_goal_status( $attributed_total, $role_goals['earnings'] ?? array() ),
			'orders' => $this->calculate_goal_status( $order_count, $role_goals['orders'] ?? array() ),
			'aov' => $this->calculate_goal_status( $aov, $role_goals['aov'] ?? array() ),
		);

		// Save to user meta
		update_user_meta( $user_id, '_wc_tp_current_goal_progress', $progress_data );

		return $progress_data;
	}

	/**
	 * Calculate goal status for a metric
	 *
	 * @param float $current Current value
	 * @param array $goals Goal thresholds (minimum, target, stretch)
	 * @return array Status data
	 */
	private function calculate_goal_status( $current, $goals ) {
		$minimum = isset( $goals['minimum'] ) ? floatval( $goals['minimum'] ) : 0;
		$target = isset( $goals['target'] ) ? floatval( $goals['target'] ) : 0;
		$stretch = isset( $goals['stretch'] ) ? floatval( $goals['stretch'] ) : 0;

		$percentage = $target > 0 ? ( $current / $target ) * 100 : 0;

		// Determine status
		$status = 'not_started';
		if ( $current >= $stretch ) {
			$status = 'stretch_achieved';
		} elseif ( $current >= $target ) {
			$status = 'achieved';
		} elseif ( $current > 0 ) {
			$status = 'in_progress';
		}

		return array(
			'current' => $current,
			'minimum' => $minimum,
			'target' => $target,
			'stretch' => $stretch,
			'percentage' => round( $percentage, 2 ),
			'status' => $status,
		);
	}

	/**
	 * Finalize period goals and save to history
	 *
	 * @param int $user_id User ID
	 * @return bool Success
	 */
	public function finalize_period_goals( $user_id ) {
		// Get current progress
		$current_progress = get_user_meta( $user_id, '_wc_tp_current_goal_progress', true );

		if ( empty( $current_progress ) ) {
			return false;
		}

		// Get goal history
		$goal_history = get_user_meta( $user_id, '_wc_tp_goal_history', true );
		if ( ! is_array( $goal_history ) ) {
			$goal_history = array();
		}

		// Add achieved date for achieved goals
		$finalized_data = $current_progress;
		$finalized_data['finalized_date'] = current_time( 'Y-m-d H:i:s' );

		// Add to history
		array_unshift( $goal_history, $finalized_data );

		// Keep only last 12 periods
		$goal_history = array_slice( $goal_history, 0, 12 );

		// Save history
		update_user_meta( $user_id, '_wc_tp_goal_history', $goal_history );

		// Check if all performance metrics are achieved and send congratulations email
		$this->check_and_send_congratulations_email( $user_id );

		return true;
	}

	/**
	 * Check if all performance metrics are achieved and send congratulations email
	 *
	 * @param int $user_id User ID
	 * @return bool Success
	 */
	private function check_and_send_congratulations_email( $user_id ) {
		// Check if email was already sent for this period
		$last_email_sent = get_user_meta( $user_id, '_wc_tp_last_congratulations_email', true );
		$current_progress = get_user_meta( $user_id, '_wc_tp_current_goal_progress', true );
		
		if ( empty( $current_progress ) ) {
			return false;
		}

		$current_period = isset( $current_progress['period'] ) ? $current_progress['period'] : '';
		
		// Don't send if already sent for this period
		if ( $last_email_sent === $current_period ) {
			return false;
		}

		// Check if all goals are achieved
		$goals_achieved = false;
		if ( isset( $current_progress['order_value']['status'] ) && 
		     isset( $current_progress['orders']['status'] ) && 
		     isset( $current_progress['aov']['status'] ) ) {
			
			$order_value_achieved = in_array( $current_progress['order_value']['status'], array( 'achieved', 'stretch_achieved' ) );
			$orders_achieved = in_array( $current_progress['orders']['status'], array( 'achieved', 'stretch_achieved' ) );
			$aov_achieved = in_array( $current_progress['aov']['status'], array( 'achieved', 'stretch_achieved' ) );
			
			$goals_achieved = $order_value_achieved && $orders_achieved && $aov_achieved;
		}

		// Check if achievements are unlocked
		$achievement_stats = get_user_meta( $user_id, '_wc_tp_achievement_stats', true );
		$achievements_unlocked = isset( $achievement_stats['total_unlocked'] ) && $achievement_stats['total_unlocked'] > 0;

		// Check if baselines have sufficient data
		$baselines = get_user_meta( $user_id, '_wc_tp_current_baselines', true );
		$baselines_sufficient = ! empty( $baselines ) && isset( $baselines['order_value'] );

		// All three must be achieved
		if ( $goals_achieved && $achievements_unlocked && $baselines_sufficient ) {
			// Send congratulations email
			$this->send_congratulations_email( $user_id, $current_progress, $achievement_stats, $baselines );
			
			// Mark email as sent for this period
			update_user_meta( $user_id, '_wc_tp_last_congratulations_email', $current_period );
			
			return true;
		}

		return false;
	}

	/**
	 * Send congratulations email to employee
	 *
	 * @param int $user_id User ID
	 * @param array $goal_progress Goal progress data
	 * @param array $achievement_stats Achievement statistics
	 * @param array $baselines Baseline data
	 * @return bool Success
	 */
	private function send_congratulations_email( $user_id, $goal_progress, $achievement_stats, $baselines ) {
		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			return false;
		}

		$to = $user->user_email;
		$subject = '🎉 Outstanding Performance Achievement - Congratulations!';

		// Get period information
		$period_type = isset( $goal_progress['period_type'] ) ? $goal_progress['period_type'] : 'monthly';
		$period_start = isset( $goal_progress['period_start'] ) ? date( 'F j, Y', strtotime( $goal_progress['period_start'] ) ) : '';
		$period_end = isset( $goal_progress['period_end'] ) ? date( 'F j, Y', strtotime( $goal_progress['period_end'] ) ) : '';

		// Get currency symbol
		$currency_symbol = html_entity_decode( get_woocommerce_currency_symbol() );

		// Build email content
		$message = $this->get_congratulations_email_template( 
			$user->display_name, 
			$period_type, 
			$period_start, 
			$period_end,
			$goal_progress,
			$achievement_stats,
			$baselines,
			$currency_symbol
		);

		// Email headers
		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: Povaly Group <noreply@povalygroup.com>',
		);

		// Send email
		$sent = wp_mail( $to, $subject, $message, $headers );

		return $sent;
	}

	/**
	 * Get congratulations email HTML template
	 *
	 * @param string $name Employee name
	 * @param string $period_type Period type
	 * @param string $period_start Period start date
	 * @param string $period_end Period end date
	 * @param array $goal_progress Goal progress data
	 * @param array $achievement_stats Achievement statistics
	 * @param array $baselines Baseline data
	 * @param string $currency_symbol Currency symbol
	 * @return string HTML email content
	 */
	private function get_congratulations_email_template( $name, $period_type, $period_start, $period_end, $goal_progress, $achievement_stats, $baselines, $currency_symbol ) {
		ob_start();
		?>
		<!DOCTYPE html>
		<html>
		<head>
			<meta charset="UTF-8">
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
			<title>Outstanding Performance Achievement</title>
		</head>
		<body style="margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f6f8;">
			<table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f6f8; padding: 40px 20px;">
				<tr>
					<td align="center">
						<!-- Main Container -->
						<table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); overflow: hidden;">
							
							<!-- Header with Povaly Group Branding -->
							<tr>
								<td style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); padding: 40px 30px; text-align: center;">
									<h1 style="margin: 0; color: #ffffff; font-size: 32px; font-weight: 700; text-shadow: 0 2px 4px rgba(0,0,0,0.1);">
										🎉 Outstanding Performance!
									</h1>
									<p style="margin: 10px 0 0 0; color: #ffffff; font-size: 16px; opacity: 0.95;">
										Congratulations on Your Achievement
									</p>
								</td>
							</tr>

							<!-- Greeting -->
							<tr>
								<td style="padding: 40px 30px 20px 30px;">
									<p style="margin: 0; font-size: 18px; color: #212B36; line-height: 1.6;">
										Dear <strong><?php echo esc_html( $name ); ?></strong>,
									</p>
								</td>
							</tr>

							<!-- Main Message -->
							<tr>
								<td style="padding: 0 30px 30px 30px;">
									<p style="margin: 0 0 20px 0; font-size: 16px; color: #637381; line-height: 1.8;">
										We are thrilled to inform you that you have achieved <strong style="color: #28a745;">exceptional performance</strong> for the period from <strong><?php echo esc_html( $period_start ); ?></strong> to <strong><?php echo esc_html( $period_end ); ?></strong>!
									</p>
									<p style="margin: 0; font-size: 16px; color: #637381; line-height: 1.8;">
										You have successfully completed <strong>all your goals</strong>, unlocked <strong>achievements</strong>, and maintained <strong>excellent performance baselines</strong>. This is a testament to your dedication, hard work, and commitment to excellence.
									</p>
								</td>
							</tr>

							<!-- Performance Summary -->
							<tr>
								<td style="padding: 0 30px 30px 30px;">
									<table width="100%" cellpadding="0" cellspacing="0" style="background: linear-gradient(135deg, #f0fff4 0%, #e8f5e9 100%); border: 2px solid #28a745; border-radius: 8px; padding: 20px;">
										<tr>
											<td>
												<h2 style="margin: 0 0 20px 0; font-size: 20px; color: #28a745; font-weight: 700;">
													📊 Your Performance Summary
												</h2>

												<!-- Goals Achieved -->
												<div style="margin-bottom: 20px;">
													<h3 style="margin: 0 0 10px 0; font-size: 16px; color: #212B36; font-weight: 600;">
														🎯 Goals Achieved (100%)
													</h3>
													<table width="100%" cellpadding="8" cellspacing="0" style="font-size: 14px; color: #637381;">
														<tr>
															<td style="padding: 8px 0;">Order Value:</td>
															<td align="right" style="padding: 8px 0; font-weight: 600; color: #28a745;">
																<?php echo esc_html( $currency_symbol . number_format( $goal_progress['order_value']['current'], 2 ) ); ?> / <?php echo esc_html( $currency_symbol . number_format( $goal_progress['order_value']['target'], 2 ) ); ?>
															</td>
														</tr>
														<tr>
															<td style="padding: 8px 0;">Orders Count:</td>
															<td align="right" style="padding: 8px 0; font-weight: 600; color: #28a745;">
																<?php echo esc_html( number_format( $goal_progress['orders']['current'], 0 ) ); ?> / <?php echo esc_html( number_format( $goal_progress['orders']['target'], 0 ) ); ?>
															</td>
														</tr>
														<tr>
															<td style="padding: 8px 0;">Avg Order Value:</td>
															<td align="right" style="padding: 8px 0; font-weight: 600; color: #28a745;">
																<?php echo esc_html( $currency_symbol . number_format( $goal_progress['aov']['current'], 2 ) ); ?> / <?php echo esc_html( $currency_symbol . number_format( $goal_progress['aov']['target'], 2 ) ); ?>
															</td>
														</tr>
													</table>
												</div>

												<!-- Achievements -->
												<div style="margin-bottom: 20px;">
													<h3 style="margin: 0 0 10px 0; font-size: 16px; color: #212B36; font-weight: 600;">
														🏆 Achievements Unlocked
													</h3>
													<p style="margin: 0; font-size: 14px; color: #637381;">
														<strong style="color: #28a745;"><?php echo esc_html( $achievement_stats['total_unlocked'] ); ?></strong> Total Achievements
														<span style="margin-left: 10px;">
															🥉 <?php echo esc_html( $achievement_stats['bronze_count'] ); ?>
															🥈 <?php echo esc_html( $achievement_stats['silver_count'] ); ?>
															🥇 <?php echo esc_html( $achievement_stats['gold_count'] ); ?>
														</span>
													</p>
												</div>

												<!-- Baselines -->
												<div>
													<h3 style="margin: 0 0 10px 0; font-size: 16px; color: #212B36; font-weight: 600;">
														📈 Performance Baselines
													</h3>
													<p style="margin: 0; font-size: 14px; color: #637381;">
														Your performance is 
														<strong style="color: #28a745; text-transform: capitalize;">
															<?php echo esc_html( $baselines['order_value']['trend'] ); ?>
														</strong>
														compared to your baseline metrics.
													</p>
												</div>
											</td>
										</tr>
									</table>
								</td>
							</tr>

							<!-- Closing Message -->
							<tr>
								<td style="padding: 0 30px 30px 30px;">
									<p style="margin: 0 0 20px 0; font-size: 16px; color: #637381; line-height: 1.8;">
										Your outstanding performance reflects the values and excellence that <strong>Povaly Group</strong> stands for. As part of the <strong>Vorosa Bajar</strong> family, you continue to set the standard for success.
									</p>
									<p style="margin: 0; font-size: 16px; color: #637381; line-height: 1.8;">
										Keep up the excellent work, and we look forward to celebrating more of your achievements in the future!
									</p>
								</td>
							</tr>

							<!-- Call to Action -->
							<tr>
								<td style="padding: 0 30px 40px 30px;" align="center">
									<a href="<?php echo esc_url( home_url( '/my-account/reports/' ) ); ?>" style="display: inline-block; background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: #ffffff; text-decoration: none; padding: 14px 32px; border-radius: 6px; font-size: 16px; font-weight: 600; box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);">
										View Your Performance Dashboard
									</a>
								</td>
							</tr>

							<!-- Footer -->
							<tr>
								<td style="background-color: #f9fafb; padding: 30px; text-align: center; border-top: 1px solid #e5eaf0;">
									<p style="margin: 0 0 10px 0; font-size: 16px; color: #212B36; font-weight: 600;">
										Best Regards,
									</p>
									<p style="margin: 0 0 5px 0; font-size: 18px; color: #28a745; font-weight: 700;">
										Povaly Group
									</p>
									<p style="margin: 0 0 20px 0; font-size: 14px; color: #637381;">
										<em>Vorosa Bajar - A Povaly Group Product</em>
									</p>
									<p style="margin: 0; font-size: 12px; color: #919EAB; line-height: 1.6;">
										This is an automated message from your performance tracking system.<br>
										Please do not reply to this email.
									</p>
								</td>
							</tr>

						</table>
					</td>
				</tr>
			</table>
		</body>
		</html>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get employee roles from settings
	 *
	 * @return array Employee roles (role_key => role_name)
	 */
	private function get_employee_roles() {
		// Get employee roles from settings (simple array of role keys)
		$employee_role_keys = get_option( 'wc_tp_employee_roles', array( 'shop_employee' ) );
		
		if ( ! is_array( $employee_role_keys ) ) {
			$employee_role_keys = array( 'shop_employee' );
		}

		// Get WordPress roles to get display names
		global $wp_roles;
		$all_roles = isset( $wp_roles ) && isset( $wp_roles->roles ) ? $wp_roles->roles : array();

		$roles = array();
		foreach ( $employee_role_keys as $role_key ) {
			$role_name = isset( $all_roles[ $role_key ]['name'] ) ? $all_roles[ $role_key ]['name'] : ucfirst( str_replace( '_', ' ', $role_key ) );
			$roles[ $role_key ] = $role_name;
		}

		return $roles;
	}

	// ============================================================================
	// ACHIEVEMENTS TRACKING
	// ============================================================================

	/**
	 * Check and update achievements for a user
	 *
	 * @param int $user_id User ID
	 * @return array Updated achievements data
	 */
	public function update_achievements( $user_id ) {
		// Get user role
		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			return array();
		}

		$user_roles = $user->roles;
		$employee_role = '';

		// Find employee role
		$all_roles = $this->get_employee_roles();
		foreach ( $user_roles as $role ) {
			if ( isset( $all_roles[ $role ] ) ) {
				$employee_role = $role;
				break;
			}
		}

		if ( empty( $employee_role ) ) {
			return array();
		}

		// Get achievements configuration
		$achievements_config = get_option( 'wc_tp_achievements_config', array() );
		
		$role_achievements = isset( $achievements_config['roles'][ $employee_role ] ) ? $achievements_config['roles'][ $employee_role ] : array();

		if ( empty( $role_achievements ) ) {
			return array();
		}

		// Get current unlocked achievements
		$unlocked_achievements = get_user_meta( $user_id, '_wc_tp_unlocked_achievements', true );
		if ( ! is_array( $unlocked_achievements ) ) {
			$unlocked_achievements = array();
		}

		// Calculate all-time totals
		$all_time_order_value = $this->get_attributed_order_total( $user_id, '2000-01-01', date( 'Y-m-d' ) );
		$all_time_orders = $this->get_order_count( $user_id, '2000-01-01', date( 'Y-m-d' ) );
		$all_time_aov = $this->get_average_order_value( $user_id, '2000-01-01', date( 'Y-m-d' ) );

		$newly_unlocked = array();

		// Check each achievement
		foreach ( $role_achievements as $achievement_key => $achievement_data ) {
			$threshold = isset( $achievement_data['threshold'] ) ? floatval( $achievement_data['threshold'] ) : 0;
			$tier = isset( $achievement_data['tier'] ) ? $achievement_data['tier'] : 'bronze';

			// Determine which metric to check
			$current_value = 0;
			if ( strpos( $achievement_key, 'earnings' ) !== false || strpos( $achievement_key, 'order_value' ) !== false ) {
				$current_value = $all_time_order_value;
			} elseif ( strpos( $achievement_key, 'orders' ) !== false ) {
				$current_value = $all_time_orders;
			} elseif ( strpos( $achievement_key, 'aov' ) !== false ) {
				$current_value = $all_time_aov;
			}

			// Check if already unlocked
			$is_unlocked = isset( $unlocked_achievements[ $achievement_key ] ) && $unlocked_achievements[ $achievement_key ]['unlocked'] === true;

			if ( ! $is_unlocked && $current_value >= $threshold ) {
				// Achievement unlocked!
				$unlocked_achievements[ $achievement_key ] = array(
					'unlocked' => true,
					'unlocked_date' => current_time( 'Y-m-d H:i:s' ),
					'value_at_unlock' => $current_value,
					'threshold' => $threshold,
					'tier' => $tier,
				);

				$newly_unlocked[] = $achievement_key;
			} elseif ( ! $is_unlocked ) {
				// Not yet unlocked, track progress
				$unlocked_achievements[ $achievement_key ] = array(
					'unlocked' => false,
					'current_progress' => $current_value,
					'threshold' => $threshold,
					'percentage' => $threshold > 0 ? round( ( $current_value / $threshold ) * 100, 2 ) : 0,
					'tier' => $tier,
				);
			}
		}

		// Save updated achievements
		update_user_meta( $user_id, '_wc_tp_unlocked_achievements', $unlocked_achievements );

		// Update achievement statistics
		$this->update_achievement_stats( $user_id, $unlocked_achievements );

		// Send notifications for newly unlocked achievements
		if ( ! empty( $newly_unlocked ) && isset( $achievements_config['notification'] ) && $achievements_config['notification'] ) {
			$this->send_achievement_notifications( $user_id, $newly_unlocked, $role_achievements );
		}

		return $unlocked_achievements;
	}

	/**
	 * Update achievement statistics
	 *
	 * @param int $user_id User ID
	 * @param array $unlocked_achievements Unlocked achievements data
	 */
	private function update_achievement_stats( $user_id, $unlocked_achievements ) {
		$bronze_count = 0;
		$silver_count = 0;
		$gold_count = 0;
		$last_unlocked = null;
		$next_achievement = null;
		$min_percentage = 100;

		foreach ( $unlocked_achievements as $key => $data ) {
			if ( isset( $data['unlocked'] ) && $data['unlocked'] === true ) {
				// Count by tier
				if ( isset( $data['tier'] ) ) {
					switch ( $data['tier'] ) {
						case 'bronze':
							$bronze_count++;
							break;
						case 'silver':
							$silver_count++;
							break;
						case 'gold':
							$gold_count++;
							break;
					}
				}

				// Track last unlocked
				if ( ! $last_unlocked || ( isset( $data['unlocked_date'] ) && $data['unlocked_date'] > $last_unlocked['date'] ) ) {
					$last_unlocked = array(
						'achievement' => $key,
						'date' => $data['unlocked_date'] ?? '',
						'value' => $data['value_at_unlock'] ?? 0,
					);
				}
			} else {
				// Track next achievement (closest to completion)
				$percentage = isset( $data['percentage'] ) ? $data['percentage'] : 0;
				if ( $percentage < 100 && $percentage > 0 && ( ! $next_achievement || $percentage > $min_percentage ) ) {
					$next_achievement = array(
						'achievement' => $key,
						'threshold' => $data['threshold'] ?? 0,
						'current' => $data['current_progress'] ?? 0,
						'remaining' => ( $data['threshold'] ?? 0 ) - ( $data['current_progress'] ?? 0 ),
						'percentage' => $percentage,
					);
					$min_percentage = $percentage;
				}
			}
		}

		$stats = array(
			'total_unlocked' => $bronze_count + $silver_count + $gold_count,
			'bronze_count' => $bronze_count,
			'silver_count' => $silver_count,
			'gold_count' => $gold_count,
			'last_unlocked' => $last_unlocked,
			'next_achievement' => $next_achievement,
		);

		update_user_meta( $user_id, '_wc_tp_achievement_stats', $stats );
	}

	/**
	 * Send achievement unlock notifications
	 *
	 * @param int $user_id User ID
	 * @param array $newly_unlocked Newly unlocked achievement keys
	 * @param array $role_achievements Role achievements configuration
	 */
	private function send_achievement_notifications( $user_id, $newly_unlocked, $role_achievements ) {
		// This is a placeholder for notification system
		// Can be extended to send emails, push notifications, etc.
		
		// For now, just save a transient that frontend can check
		foreach ( $newly_unlocked as $achievement_key ) {
			$achievement_data = isset( $role_achievements[ $achievement_key ] ) ? $role_achievements[ $achievement_key ] : array();
			$notification_data = array(
				'user_id' => $user_id,
				'achievement_key' => $achievement_key,
				'achievement_name' => $achievement_data['name'] ?? '',
				'achievement_description' => $achievement_data['description'] ?? '',
				'tier' => $achievement_data['tier'] ?? 'bronze',
				'timestamp' => current_time( 'timestamp' ),
			);

			set_transient( 'wc_tp_achievement_notification_' . $user_id . '_' . $achievement_key, $notification_data, DAY_IN_SECONDS );
		}
	}


	// ============================================================================
	// BASELINES CALCULATION
	// ============================================================================

	/**
	 * Calculate and update baselines for a user
	 *
	 * @param int $user_id User ID
	 * @return array Baseline data
	 */
	public function update_baselines( $user_id ) {
		// Get baselines configuration
		$baselines_config = get_option( 'wc_tp_baselines_config', array() );
		$method = isset( $baselines_config['method'] ) ? $baselines_config['method'] : 'rolling_average';
		$periods = isset( $baselines_config['periods'] ) ? intval( $baselines_config['periods'] ) : 3;
		$minimum_data = isset( $baselines_config['minimum_data'] ) ? intval( $baselines_config['minimum_data'] ) : 5;

		// Get goals configuration for period type
		$goals_config = get_option( 'wc_tp_goals_config', array() );
		$period_type = isset( $goals_config['period'] ) ? $goals_config['period'] : 'monthly';

		// Get historical data
		$historical_data = $this->get_historical_performance_data( $user_id, $period_type, $periods + 5 ); // Get extra periods for calculation

		// Check if we have enough data
		if ( count( $historical_data ) < $minimum_data ) {
			return array(
				'error' => 'insufficient_data',
				'message' => sprintf( __( 'Need at least %d data points to calculate baseline', 'wc-team-payroll' ), $minimum_data ),
				'current_data_points' => count( $historical_data ),
			);
		}

		// Calculate baselines based on method
		$baseline_data = array(
			'calculated_date' => current_time( 'Y-m-d H:i:s' ),
			'method' => $method,
			'periods_used' => min( $periods, count( $historical_data ) ),
			'order_value' => $this->calculate_baseline_for_metric( $historical_data, 'order_value', $method, $periods ),
			'orders' => $this->calculate_baseline_for_metric( $historical_data, 'orders', $method, $periods ),
			'aov' => $this->calculate_baseline_for_metric( $historical_data, 'aov', $method, $periods ),
		);

		// Save to user meta
		update_user_meta( $user_id, '_wc_tp_current_baselines', $baseline_data );

		// Update baseline history
		$this->update_baseline_history( $user_id, $baseline_data );

		return $baseline_data;
	}

	/**
	 * Get historical performance data for a user
	 *
	 * @param int $user_id User ID
	 * @param string $period_type Period type (weekly, monthly, quarterly, yearly)
	 * @param int $num_periods Number of periods to retrieve
	 * @return array Historical data
	 */
	private function get_historical_performance_data( $user_id, $period_type, $num_periods ) {
		$historical_data = array();
		$timezone = wp_timezone();
		$now = new DateTime( 'now', $timezone );

		for ( $i = 0; $i < $num_periods; $i++ ) {
			// Calculate period dates
			$period_date = clone $now;
			
			switch ( $period_type ) {
				case 'weekly':
					$period_date->modify( '-' . $i . ' weeks' );
					break;
				case 'monthly':
					$period_date->modify( '-' . $i . ' months' );
					break;
				case 'quarterly':
					$period_date->modify( '-' . ( $i * 3 ) . ' months' );
					break;
				case 'yearly':
					$period_date->modify( '-' . $i . ' years' );
					break;
			}

			$period_dates = $this->get_period_dates( $period_type, $period_date->format( 'Y-m-d' ) );

			// Get data for this period
			$order_value = $this->get_attributed_order_total( $user_id, $period_dates['start'], $period_dates['end'] );
			$orders = $this->get_order_count( $user_id, $period_dates['start'], $period_dates['end'] );
			$aov = $this->get_average_order_value( $user_id, $period_dates['start'], $period_dates['end'] );

			// Only include periods with data
			if ( $order_value > 0 || $orders > 0 ) {
				$historical_data[] = array(
					'period' => $period_dates['period_id'],
					'start_date' => $period_dates['start'],
					'end_date' => $period_dates['end'],
					'order_value' => $order_value,
					'orders' => $orders,
					'aov' => $aov,
				);
			}
		}

		return $historical_data;
	}

	/**
	 * Calculate baseline for a specific metric
	 *
	 * @param array $historical_data Historical performance data
	 * @param string $metric Metric name (order_value, orders, aov)
	 * @param string $method Calculation method
	 * @param int $periods Number of periods to use
	 * @return array Baseline data for metric
	 */
	private function calculate_baseline_for_metric( $historical_data, $metric, $method, $periods ) {
		// Extract values for this metric
		$values = array();
		$data_points = array();
		
		$limited_data = array_slice( $historical_data, 0, $periods );
		
		foreach ( $limited_data as $period_data ) {
			if ( isset( $period_data[ $metric ] ) ) {
				$values[] = floatval( $period_data[ $metric ] );
				$data_points[] = floatval( $period_data[ $metric ] );
			}
		}

		if ( empty( $values ) ) {
			return array(
				'baseline' => 0,
				'current' => 0,
				'difference' => 0,
				'percentage' => 0,
				'trend' => 'stable',
				'data_points' => array(),
			);
		}

		// Calculate baseline based on method
		$baseline = 0;
		switch ( $method ) {
			case 'rolling_average':
				$baseline = array_sum( $values ) / count( $values );
				break;

			case 'historical_average':
				$baseline = array_sum( $values ) / count( $values );
				break;

			case 'best_period':
				$baseline = max( $values );
				break;

			case 'median':
				sort( $values );
				$count = count( $values );
				$middle = floor( $count / 2 );
				if ( $count % 2 == 0 ) {
					$baseline = ( $values[ $middle - 1 ] + $values[ $middle ] ) / 2;
				} else {
					$baseline = $values[ $middle ];
				}
				break;

			case 'percentile':
				$baselines_config = get_option( 'wc_tp_baselines_config', array() );
				$percentile = isset( $baselines_config['percentile'] ) ? intval( $baselines_config['percentile'] ) : 75;
				sort( $values );
				$index = ceil( ( $percentile / 100 ) * count( $values ) ) - 1;
				$baseline = $values[ max( 0, $index ) ];
				break;

			default:
				$baseline = array_sum( $values ) / count( $values );
		}

		// Get current value (most recent period)
		$current = isset( $historical_data[0][ $metric ] ) ? floatval( $historical_data[0][ $metric ] ) : 0;

		// Calculate difference and percentage
		$difference = $current - $baseline;
		$percentage = $baseline > 0 ? round( ( $difference / $baseline ) * 100, 2 ) : 0;

		// Determine trend
		$trend = 'stable';
		if ( $percentage > 5 ) {
			$trend = 'improving';
		} elseif ( $percentage < -5 ) {
			$trend = 'declining';
		}

		return array(
			'baseline' => round( $baseline, 2 ),
			'current' => round( $current, 2 ),
			'difference' => round( $difference, 2 ),
			'percentage' => $percentage,
			'trend' => $trend,
			'data_points' => $data_points,
		);
	}

	/**
	 * Update baseline history
	 *
	 * @param int $user_id User ID
	 * @param array $baseline_data Current baseline data
	 */
	private function update_baseline_history( $user_id, $baseline_data ) {
		$baseline_history = get_user_meta( $user_id, '_wc_tp_baseline_history', true );
		if ( ! is_array( $baseline_history ) ) {
			$baseline_history = array();
		}

		// Add current baseline to history
		$history_entry = array(
			'date' => $baseline_data['calculated_date'],
			'order_value_baseline' => $baseline_data['order_value']['baseline'] ?? 0,
			'orders_baseline' => $baseline_data['orders']['baseline'] ?? 0,
			'aov_baseline' => $baseline_data['aov']['baseline'] ?? 0,
			'method' => $baseline_data['method'],
			'periods' => $baseline_data['periods_used'],
		);

		array_unshift( $baseline_history, $history_entry );

		// Keep only last 12 entries
		$baseline_history = array_slice( $baseline_history, 0, 12 );

		update_user_meta( $user_id, '_wc_tp_baseline_history', $baseline_history );
	}


	// ============================================================================
	// AJAX HANDLERS
	// ============================================================================

	/**
	 * AJAX: Get user goal progress
	 */
	public function ajax_get_user_goal_progress() {
		check_ajax_referer( 'wc_team_payroll_nonce', 'nonce' );

		$user_id = isset( $_POST['user_id'] ) ? intval( $_POST['user_id'] ) : get_current_user_id();

		if ( ! $user_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid user ID', 'wc-team-payroll' ) ) );
		}

		// Check permissions
		if ( $user_id !== get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'wc-team-payroll' ) ) );
		}

		// Update and get goal progress
		$progress = $this->update_goal_progress( $user_id );

		if ( empty( $progress ) ) {
			wp_send_json_error( array( 'message' => __( 'No goals configured for this user', 'wc-team-payroll' ) ) );
		}

		wp_send_json_success( array( 'progress' => $progress ) );
	}

	/**
	 * AJAX: Get user achievements
	 */
	public function ajax_get_user_achievements() {
		check_ajax_referer( 'wc_team_payroll_nonce', 'nonce' );

		$user_id = isset( $_POST['user_id'] ) ? intval( $_POST['user_id'] ) : get_current_user_id();

		if ( ! $user_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid user ID', 'wc-team-payroll' ) ) );
		}

		// Check permissions
		if ( $user_id !== get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'wc-team-payroll' ) ) );
		}

		// Update and get achievements
		$achievements = $this->update_achievements( $user_id );
		$stats = get_user_meta( $user_id, '_wc_tp_achievement_stats', true );

		if ( empty( $achievements ) ) {
			wp_send_json_error( array( 'message' => __( 'No achievements configured for this user', 'wc-team-payroll' ) ) );
		}

		wp_send_json_success( array(
			'achievements' => $achievements,
			'stats' => $stats,
		) );
	}

	/**
	 * AJAX: Get user baselines
	 */
	public function ajax_get_user_baselines() {
		check_ajax_referer( 'wc_team_payroll_nonce', 'nonce' );

		$user_id = isset( $_POST['user_id'] ) ? intval( $_POST['user_id'] ) : get_current_user_id();

		if ( ! $user_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid user ID', 'wc-team-payroll' ) ) );
		}

		// Check permissions
		if ( $user_id !== get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'wc-team-payroll' ) ) );
		}

		// Get current baselines (don't recalculate, just return stored data)
		$baselines = get_user_meta( $user_id, '_wc_tp_current_baselines', true );
		$history = get_user_meta( $user_id, '_wc_tp_baseline_history', true );

		if ( empty( $baselines ) ) {
			// No baselines yet, calculate them
			$baselines = $this->update_baselines( $user_id );
		}

		wp_send_json_success( array(
			'baselines' => $baselines,
			'history' => $history,
		) );
	}

	/**
	 * AJAX: Recalculate all performance data for a user
	 */
	public function ajax_recalculate_performance() {
		check_ajax_referer( 'wc_team_payroll_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'wc-team-payroll' ) ) );
		}

		$user_id = isset( $_POST['user_id'] ) ? intval( $_POST['user_id'] ) : 0;

		if ( ! $user_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid user ID', 'wc-team-payroll' ) ) );
		}

		// Recalculate everything
		$goals = $this->update_goal_progress( $user_id );
		$achievements = $this->update_achievements( $user_id );
		$baselines = $this->update_baselines( $user_id );

		wp_send_json_success( array(
			'message' => __( 'Performance data recalculated successfully', 'wc-team-payroll' ),
			'goals' => $goals,
			'achievements' => $achievements,
			'baselines' => $baselines,
		) );
	}


	// ============================================================================
	// CRON JOBS
	// ============================================================================

	/**
	 * Cron: Update baselines for all employees
	 */
	public function cron_update_baselines() {
		// Get baselines configuration
		$baselines_config = get_option( 'wc_tp_baselines_config', array() );
		$update_frequency = isset( $baselines_config['update_frequency'] ) ? $baselines_config['update_frequency'] : 'monthly';

		// Check if it's time to update based on frequency
		$last_update = get_option( 'wc_tp_last_baseline_update', 0 );
		$current_time = current_time( 'timestamp' );

		$should_update = false;
		switch ( $update_frequency ) {
			case 'daily':
				$should_update = ( $current_time - $last_update ) >= DAY_IN_SECONDS;
				break;
			case 'weekly':
				$should_update = ( $current_time - $last_update ) >= ( 7 * DAY_IN_SECONDS );
				break;
			case 'monthly':
				$should_update = ( $current_time - $last_update ) >= ( 30 * DAY_IN_SECONDS );
				break;
			case 'quarterly':
				$should_update = ( $current_time - $last_update ) >= ( 90 * DAY_IN_SECONDS );
				break;
			case 'manual':
				$should_update = false;
				break;
		}

		if ( ! $should_update ) {
			return;
		}

		// Get all employees
		$employees = $this->get_all_employees();

		foreach ( $employees as $employee_id ) {
			$this->update_baselines( $employee_id );
		}

		// Update last update timestamp
		update_option( 'wc_tp_last_baseline_update', $current_time );
	}

	/**
	 * Cron: Check and update achievements for all employees
	 */
	public function cron_check_achievements() {
		// Get all employees
		$employees = $this->get_all_employees();

		foreach ( $employees as $employee_id ) {
			$this->update_achievements( $employee_id );
		}
	}

	/**
	 * Cron: Finalize period goals for all employees
	 */
	public function cron_finalize_period_goals() {
		// Get goals configuration
		$goals_config = get_option( 'wc_tp_goals_config', array() );
		$period_type = isset( $goals_config['period'] ) ? $goals_config['period'] : 'monthly';

		// Get current period dates
		$period_dates = $this->get_period_dates( $period_type );
		$today = current_time( 'Y-m-d' );

		// Check if we're at the end of the period
		if ( $today !== $period_dates['end'] ) {
			return; // Not end of period yet
		}

		// Get all employees
		$employees = $this->get_all_employees();

		foreach ( $employees as $employee_id ) {
			// Update current progress one last time
			$this->update_goal_progress( $employee_id );
			
			// Finalize and save to history
			$this->finalize_period_goals( $employee_id );
		}
	}

	/**
	 * Get all employee user IDs
	 *
	 * @return array Employee user IDs
	 */
	private function get_all_employees() {
		$employee_roles = array_keys( $this->get_employee_roles() );

		if ( empty( $employee_roles ) ) {
			return array();
		}

		$args = array(
			'role__in' => $employee_roles,
			'fields' => 'ID',
			'meta_query' => array(
				array(
					'key' => '_wc_tp_employee_status',
					'value' => 'active',
					'compare' => '=',
				),
			),
		);

		$users = get_users( $args );

		return $users;
	}
}
