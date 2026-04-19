<?php
/**
 * Performance Tracker AJAX Handlers
 * Handles frontend AJAX requests for Goals, Achievements, and Baselines
 *
 * @package WooCommerce Team Payroll
 * @since 1.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Team_Payroll_Performance_Tracker_AJAX {

	/**
	 * Initialize AJAX handlers
	 */
	public static function init() {
		add_action( 'wp_ajax_wc_tp_get_performance_tracker_data', array( __CLASS__, 'ajax_get_performance_tracker_data' ) );
	}

	/**
	 * AJAX: Get Performance Tracker Data
	 * Handles Goals, Achievements, and Baselines
	 */
	public static function ajax_get_performance_tracker_data() {
		check_ajax_referer( 'wc_team_payroll_nonce', 'nonce' );

		// Check if user_id is provided (for admin viewing employee performance)
		$requested_user_id = isset( $_POST['user_id'] ) ? intval( $_POST['user_id'] ) : 0;
		
		// If user_id is provided and current user is admin, use that user_id
		if ( $requested_user_id && current_user_can( 'manage_options' ) ) {
			$user_id = $requested_user_id;
		} else {
			// Otherwise use current logged-in user
			$user_id = get_current_user_id();
		}

		if ( ! $user_id ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'wc-team-payroll' ) ) );
		}

		$section = isset( $_POST['section'] ) ? sanitize_text_field( $_POST['section'] ) : 'overview';
		$view_mode = isset( $_POST['view_mode'] ) ? sanitize_text_field( $_POST['view_mode'] ) : 'current';

		// Initialize Performance Tracker
		$tracker = new WC_Team_Payroll_Performance_Tracker();

		$data = array();

		switch ( $section ) {
			case 'config':
				// Get admin-configured period type
				$goals_config = get_option( 'wc_tp_goals_config', array() );
				$data['period_type'] = isset( $goals_config['period'] ) ? $goals_config['period'] : 'monthly';
				break;

			case 'overview':
				// Get overview data
				$goals = $tracker->update_goal_progress( $user_id );
				$achievements_stats = get_user_meta( $user_id, '_wc_tp_achievement_stats', true );
				$baselines = get_user_meta( $user_id, '_wc_tp_current_baselines', true );

				$data['goals_summary'] = array(
					'html' => self::render_goals_summary( $goals )
				);
				$data['achievements_summary'] = array(
					'html' => self::render_achievements_summary( $achievements_stats )
				);
				$data['baselines_summary'] = array(
					'html' => self::render_baselines_summary( $baselines )
				);
				$data['quick_stats'] = self::get_quick_stats( $goals, $achievements_stats, $baselines );
				break;

			case 'goals':
				// Get goals data
				$data['goals'] = $tracker->update_goal_progress( $user_id );
				$data['history'] = get_user_meta( $user_id, '_wc_tp_goal_history', true );
				break;

			case 'achievements':
				// Get achievements data
				$data['achievements'] = $tracker->update_achievements( $user_id );
				$data['stats'] = get_user_meta( $user_id, '_wc_tp_achievement_stats', true );
				break;

			case 'baselines':
				// Get baselines data
				$baselines = get_user_meta( $user_id, '_wc_tp_current_baselines', true );
				
				// If no baselines exist, calculate them
				if ( empty( $baselines ) ) {
					$baselines = $tracker->update_baselines( $user_id );
				}
				
				$data['baselines'] = $baselines;
				$data['history'] = get_user_meta( $user_id, '_wc_tp_baseline_history', true );
				break;

			default:
				wp_send_json_error( array( 'message' => __( 'Invalid section', 'wc-team-payroll' ) ) );
		}

		wp_send_json_success( $data );
	}

	/**
	 * Render goals summary for overview
	 */
	private static function render_goals_summary( $goals ) {
		if ( empty( $goals ) ) {
			return '<p>No goals configured</p>';
		}

		$achieved_count = 0;
		$total_count = 0;

		foreach ( array( 'order_value', 'orders', 'aov' ) as $metric ) {
			if ( isset( $goals[ $metric ] ) ) {
				$total_count++;
				if ( $goals[ $metric ]['status'] === 'achieved' || $goals[ $metric ]['status'] === 'stretch_achieved' ) {
					$achieved_count++;
				}
			}
		}

		$percentage = $total_count > 0 ? ( $achieved_count / $total_count ) * 100 : 0;

		return sprintf(
			'<div class="summary-stat"><strong>%d/%d</strong> Goals Achieved</div><div class="summary-progress">%.0f%%</div>',
			$achieved_count,
			$total_count,
			$percentage
		);
	}

	/**
	 * Render achievements summary for overview
	 */
	private static function render_achievements_summary( $stats ) {
		if ( empty( $stats ) ) {
			return '<p>No achievements yet</p>';
		}

		return sprintf(
			'<div class="summary-stat"><strong>%d</strong> Total Unlocked</div><div class="summary-badges">🥉 %d  🥈 %d  🥇 %d</div>',
			isset( $stats['total_unlocked'] ) ? $stats['total_unlocked'] : 0,
			isset( $stats['bronze_count'] ) ? $stats['bronze_count'] : 0,
			isset( $stats['silver_count'] ) ? $stats['silver_count'] : 0,
			isset( $stats['gold_count'] ) ? $stats['gold_count'] : 0
		);
	}

	/**
	 * Render baselines summary for overview
	 */
	private static function render_baselines_summary( $baselines ) {
		if ( empty( $baselines ) || isset( $baselines['error'] ) ) {
			return '<p>Insufficient data</p>';
		}

		$trends = array();
		foreach ( array( 'order_value', 'orders', 'aov' ) as $metric ) {
			if ( isset( $baselines[ $metric ]['trend'] ) ) {
				$trends[] = $baselines[ $metric ]['trend'];
			}
		}

		$improving = count( array_filter( $trends, function( $t ) { return $t === 'improving'; } ) );
		$total = count( $trends );

		$trend_icon = $improving > $total / 2 ? '↗' : ( $improving < $total / 2 ? '↘' : '→' );
		$trend_text = $improving > $total / 2 ? 'Improving' : ( $improving < $total / 2 ? 'Declining' : 'Stable' );

		return sprintf(
			'<div class="summary-stat"><strong>%s</strong> %s</div><div class="summary-trend">%d/%d metrics improving</div>',
			$trend_icon,
			$trend_text,
			$improving,
			$total
		);
	}

	/**
	 * Get quick stats for overview
	 */
	private static function get_quick_stats( $goals, $achievements_stats, $baselines ) {
		$stats = array();

		// Order Value stat
		if ( isset( $goals['order_value'] ) ) {
			$stats[] = array(
				'label' => 'Order Value Progress',
				'value' => number_format( $goals['order_value']['percentage'], 0 ) . '%'
			);
		}

		// Orders stat
		if ( isset( $goals['orders'] ) ) {
			$stats[] = array(
				'label' => 'Orders Progress',
				'value' => number_format( $goals['orders']['percentage'], 0 ) . '%'
			);
		}

		// AOV stat
		if ( isset( $goals['aov'] ) ) {
			$stats[] = array(
				'label' => 'AOV Progress',
				'value' => number_format( $goals['aov']['percentage'], 0 ) . '%'
			);
		}

		// Next achievement
		if ( isset( $achievements_stats['next_achievement'] ) ) {
			$next = $achievements_stats['next_achievement'];
			$stats[] = array(
				'label' => 'Next Achievement',
				'value' => number_format( $next['percentage'], 0 ) . '%'
			);
		}

		return $stats;
	}
}

// Initialize AJAX handlers
WC_Team_Payroll_Performance_Tracker_AJAX::init();
