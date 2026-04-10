<?php
/**
 * Payroll Calculation Engine
 */

class WC_Team_Payroll_Payroll_Engine {

	/**
	 * Get monthly payroll summary
	 */
	public static function get_monthly_payroll( $year = null, $month = null ) {
		if ( ! $year ) {
			$year = date( 'Y' );
		}
		if ( ! $month ) {
			$month = date( 'm' );
		}

		$start_date = sprintf( '%d-%02d-01', $year, $month );
		$end_date = date( 'Y-m-t', strtotime( $start_date ) );

		$args = array(
			'limit'  => -1,
			'status' => array( 'completed', 'processing' ),
			'date_created' => array(
				'>=' => strtotime( $start_date ),
				'<=' => strtotime( $end_date . ' 23:59:59' ),
			),
		);

		$orders = wc_get_orders( $args );
		$payroll = array();

		foreach ( $orders as $order ) {
			$agent_id = $order->get_meta( '_primary_agent_id' );
			$processor_id = $order->get_meta( '_processor_user_id' );
			$commission_data = $order->get_meta( '_commission_data' );

			if ( ! $commission_data ) {
				continue;
			}

			// Add agent earnings
			if ( $agent_id && $commission_data['agent_earnings'] > 0 ) {
				if ( ! isset( $payroll[ $agent_id ] ) ) {
					$payroll[ $agent_id ] = array(
						'user_id'    => $agent_id,
						'user'       => get_user_by( 'ID', $agent_id ),
						'total'      => 0,
						'orders'     => 0,
						'paid'       => 0,
						'due'        => 0,
					);
				}
				$payroll[ $agent_id ]['total'] += $commission_data['agent_earnings'];
				$payroll[ $agent_id ]['orders']++;
			}

			// Add processor earnings
			if ( $processor_id && $commission_data['processor_earnings'] > 0 ) {
				if ( ! isset( $payroll[ $processor_id ] ) ) {
					$payroll[ $processor_id ] = array(
						'user_id'    => $processor_id,
						'user'       => get_user_by( 'ID', $processor_id ),
						'total'      => 0,
						'orders'     => 0,
						'paid'       => 0,
						'due'        => 0,
					);
				}
				$payroll[ $processor_id ]['total'] += $commission_data['processor_earnings'];
				$payroll[ $processor_id ]['orders']++;
			}
		}

		// Calculate paid/due
		foreach ( $payroll as $user_id => $data ) {
			$paid = get_user_meta( $user_id, sprintf( '_payroll_paid_%d_%02d', $year, $month ), true );
			$payroll[ $user_id ]['paid'] = $paid ? floatval( $paid ) : 0;
			$payroll[ $user_id ]['due'] = $data['total'] - $payroll[ $user_id ]['paid'];
		}

		return $payroll;
	}

	/**
	 * Mark payroll as paid
	 */
	public static function mark_payroll_paid( $user_id, $year, $month, $amount ) {
		$key = sprintf( '_payroll_paid_%d_%02d', $year, $month );
		update_user_meta( $user_id, $key, floatval( $amount ) );

		do_action( 'wc_team_payroll_marked_paid', $user_id, $year, $month, $amount );
	}

	/**
	 * Get user payroll history
	 */
	public static function get_user_payroll_history( $user_id, $months = 12 ) {
		$history = array();

		for ( $i = 0; $i < $months; $i++ ) {
			$date = date( 'Y-m-01', strtotime( "-$i months" ) );
			$year = date( 'Y', strtotime( $date ) );
			$month = date( 'm', strtotime( $date ) );

			$engine = new WC_Team_Payroll_Core_Engine();
			$earnings = $engine->get_user_earnings( $user_id, $date, date( 'Y-m-t', strtotime( $date ) ) );

			$paid = get_user_meta( $user_id, sprintf( '_payroll_paid_%d_%02d', $year, $month ), true );
			$paid = $paid ? floatval( $paid ) : 0;

			$history[] = array(
				'date'     => $date,
				'year'     => $year,
				'month'    => $month,
				'total'    => $earnings['total_earnings'],
				'paid'     => $paid,
				'due'      => $earnings['total_earnings'] - $paid,
			);
		}

		return $history;
	}
}
