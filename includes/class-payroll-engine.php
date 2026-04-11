<?php
/**
 * Payroll Calculation Engine
 */

class WC_Team_Payroll_Payroll_Engine {

	/**
	 * Get monthly payroll summary - FIXED DATE QUERY
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

		// Use proper WooCommerce date query format
		$args = array(
			'limit'  => -1,
			'status' => array( 'completed', 'processing' ),
			'date_query' => array(
				array(
					'after'     => $start_date,
					'before'    => $end_date,
					'inclusive' => true,
				),
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

		// Calculate paid/due from payments array
		foreach ( $payroll as $user_id => $data ) {
			$payments = get_user_meta( $user_id, '_wc_tp_payments', true );
			$paid = 0;
			
			if ( is_array( $payments ) ) {
				foreach ( $payments as $payment ) {
					// Parse payment date - handle both formats
					$payment_date_str = $payment['date'];
					
					// Convert datetime-local format (2026-04-11T14:30) to timestamp
					if ( strpos( $payment_date_str, 'T' ) !== false ) {
						$payment_date_str = str_replace( 'T', ' ', $payment_date_str );
					}
					
					$payment_timestamp = strtotime( $payment_date_str );
					$start_timestamp = strtotime( $start_date . ' 00:00:00' );
					$end_timestamp = strtotime( $end_date . ' 23:59:59' );
					
					// Only count payments within the date range
					if ( $payment_timestamp !== false && $payment_timestamp >= $start_timestamp && $payment_timestamp <= $end_timestamp ) {
						$paid += floatval( $payment['amount'] );
					}
				}
			}
			
			$payroll[ $user_id ]['paid'] = $paid;
			$payroll[ $user_id ]['due'] = $data['total'] - $paid;
		}

		return $payroll;
	}

	/**
	 * Get payroll by date range
	 */
	public static function get_payroll_by_date_range( $start_date = '', $end_date = '' ) {
		if ( ! $start_date ) {
			$start_date = date( 'Y-m-01' );
		}
		if ( ! $end_date ) {
			$end_date = date( 'Y-m-t' );
		}

		// Use proper WooCommerce date query format
		$args = array(
			'limit'  => -1,
			'status' => array( 'completed', 'processing' ),
			'date_query' => array(
				array(
					'after'     => $start_date,
					'before'    => $end_date,
					'inclusive' => true,
				),
			),
		);

		$orders = wc_get_orders( $args );
		$payroll = array();
		$core_engine = new WC_Team_Payroll_Core_Engine();

		foreach ( $orders as $order ) {
			$agent_id = $order->get_meta( '_primary_agent_id' );
			$processor_id = $order->get_meta( '_processor_user_id' );
			$commission_data = $order->get_meta( '_commission_data' );

			// Recalculate commission based on current salary types
			if ( $commission_data ) {
				$commission_data = $core_engine->calculate_commission( $order, $agent_id, $processor_id );
			}

			// Add agent earnings
			if ( $agent_id && $commission_data && $commission_data['agent_earnings'] > 0 ) {
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
			if ( $processor_id && $commission_data && $commission_data['processor_earnings'] > 0 ) {
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

		// Get all employees and add them to payroll if they have payments
		$all_users = get_users( array(
			'role__in' => array( 'shop_employee', 'shop_manager', 'administrator' ),
			'number'   => -1,
		) );

		foreach ( $all_users as $user ) {
			$payments = get_user_meta( $user->ID, '_wc_tp_payments', true );
			
			if ( is_array( $payments ) && ! empty( $payments ) ) {
				// Check if user has payments in this date range
				$has_payment_in_range = false;
				
				foreach ( $payments as $payment ) {
					$payment_date_str = $payment['date'];
					
					// Convert datetime-local format
					if ( strpos( $payment_date_str, 'T' ) !== false ) {
						$payment_date_str = str_replace( 'T', ' ', $payment_date_str );
					}
					
					$payment_timestamp = strtotime( $payment_date_str );
					$start_timestamp = strtotime( $start_date . ' 00:00:00' );
					$end_timestamp = strtotime( $end_date . ' 23:59:59' );
					
					if ( $payment_timestamp !== false && $payment_timestamp >= $start_timestamp && $payment_timestamp <= $end_timestamp ) {
						$has_payment_in_range = true;
						break;
					}
				}
				
				// If user has payments in range but no earnings, add them to payroll
				if ( $has_payment_in_range && ! isset( $payroll[ $user->ID ] ) ) {
					$payroll[ $user->ID ] = array(
						'user_id'    => $user->ID,
						'user'       => $user,
						'total'      => 0,
						'orders'     => 0,
						'paid'       => 0,
						'due'        => 0,
					);
				}
			}
		}

		// Calculate paid/due from payments array
		foreach ( $payroll as $user_id => $data ) {
			$payments = get_user_meta( $user_id, '_wc_tp_payments', true );
			$paid = 0;
			
			if ( is_array( $payments ) ) {
				foreach ( $payments as $payment ) {
					// Parse payment date - handle both formats
					$payment_date_str = $payment['date'];
					
					// Convert datetime-local format (2026-04-11T14:30) to timestamp
					if ( strpos( $payment_date_str, 'T' ) !== false ) {
						$payment_date_str = str_replace( 'T', ' ', $payment_date_str );
					}
					
					$payment_timestamp = strtotime( $payment_date_str );
					$start_timestamp = strtotime( $start_date . ' 00:00:00' );
					$end_timestamp = strtotime( $end_date . ' 23:59:59' );
					
					// Only count payments within the date range
					if ( $payment_timestamp !== false && $payment_timestamp >= $start_timestamp && $payment_timestamp <= $end_timestamp ) {
						$paid += floatval( $payment['amount'] );
					}
				}
			}
			
			$payroll[ $user_id ]['paid'] = $paid;
			$payroll[ $user_id ]['due'] = $data['total'] - $paid;
		}

		return $payroll;
	}

	/**
	 * Mark payroll as paid
	 */
	public static function mark_payroll_paid( $user_id, $year, $month, $amount ) {
		$key = sprintf( '_payroll_paid_%d_%02d', $year, $month );
		update_user_meta( $user_id, $key, floatval( $amount ) );
	}
}
