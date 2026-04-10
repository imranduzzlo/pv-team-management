<?php
/**
 * Core Commission Calculation Engine
 */

class WC_Team_Payroll_Core_Engine {

	public function __construct() {
		add_action( 'woocommerce_order_status_completed', array( $this, 'calculate_order_commission' ) );
		add_action( 'woocommerce_order_status_processing', array( $this, 'calculate_order_commission' ) );
		add_action( 'woocommerce_order_item_added', array( $this, 'on_order_updated' ), 10, 3 );
		add_action( 'woocommerce_order_item_changed', array( $this, 'on_order_updated' ), 10, 3 );
		add_action( 'woocommerce_order_item_removed', array( $this, 'on_order_updated' ), 10, 2 );
		add_action( 'woocommerce_order_refunded', array( $this, 'on_order_updated' ), 10, 2 );
		add_action( 'woocommerce_order_partially_refunded', array( $this, 'on_order_updated' ), 10, 2 );
		add_action( 'woocommerce_order_status_cancelled', array( $this, 'on_order_cancelled' ) );
		add_action( 'woocommerce_order_status_failed', array( $this, 'on_order_cancelled' ) );
		add_action( 'woocommerce_order_status_refunded', array( $this, 'on_order_cancelled' ) );
	}

	/**
	 * Calculate commission for an order
	 */
	public function calculate_order_commission( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		$agent_id = $order->get_meta( '_primary_agent_id' );
		$processor_id = $order->get_meta( '_processor_user_id' );

		if ( ! $agent_id && ! $processor_id ) {
			return;
		}

		$commission_data = $this->calculate_commission( $order, $agent_id, $processor_id );
		$order->update_meta_data( '_commission_data', $commission_data );
		$order->save();

		do_action( 'wc_team_payroll_commission_calculated', $order_id, $commission_data );
	}

	/**
	 * Calculate commission breakdown for order
	 */
	public function calculate_commission( $order, $agent_id, $processor_id ) {
		$settings = get_option( 'wc_team_payroll_settings', array() );
		
		$agent_percentage = isset( $settings['agent_percentage'] ) ? floatval( $settings['agent_percentage'] ) : 70;
		$processor_percentage = isset( $settings['processor_percentage'] ) ? floatval( $settings['processor_percentage'] ) : 30;
		$commission_field_name = isset( $settings['commission_field_name'] ) ? $settings['commission_field_name'] : 'team_commission';

		$commission_data = array(
			'order_id'       => $order->get_id(),
			'agent_id'       => $agent_id,
			'processor_id'   => $processor_id,
			'items'          => array(),
			'total_commission' => 0,
			'agent_earnings' => 0,
			'processor_earnings' => 0,
			'calculated_at'  => current_time( 'mysql' ),
		);

		// Calculate item commissions
		foreach ( $order->get_items() as $item ) {
			$product_id = $item->get_product_id();
			$commission_rate = get_field( $commission_field_name, $product_id );

			if ( ! $commission_rate ) {
				continue;
			}

			$line_total = $item->get_total();
			$item_commission = ( $line_total * $commission_rate ) / 100;

			$commission_data['items'][] = array(
				'product_id'      => $product_id,
				'product_name'    => $item->get_name(),
				'line_total'      => $line_total,
				'commission_rate' => $commission_rate,
				'commission'      => $item_commission,
			);

			$commission_data['total_commission'] += $item_commission;
		}

		// Apply split logic
		if ( $agent_id === $processor_id || ! $processor_id ) {
			// Same user or no processor - gets 100%
			$commission_data['agent_earnings'] = $commission_data['total_commission'];
			$commission_data['processor_earnings'] = 0;
		} else {
			// Different users - split
			$commission_data['agent_earnings'] = ( $commission_data['total_commission'] * $agent_percentage ) / 100;
			$commission_data['processor_earnings'] = ( $commission_data['total_commission'] * $processor_percentage ) / 100;
		}

		return $commission_data;
	}

	/**
	 * Handle order updates
	 */
	public function on_order_updated( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		if ( ! in_array( $order->get_status(), array( 'completed', 'processing' ) ) ) {
			return;
		}

		$this->calculate_order_commission( $order_id );
	}

	/**
	 * Handle order cancellation
	 */
	public function on_order_cancelled( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		$order->delete_meta_data( '_commission_data' );
		$order->save();
	}

	/**
	 * Get commission data for order
	 */
	public function get_order_commission( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return null;
		}

		return $order->get_meta( '_commission_data' );
	}

	/**
	 * Get user earnings for date range - FIXED DATE QUERY
	 */
	public function get_user_earnings( $user_id, $start_date = null, $end_date = null ) {
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

		$total_earnings = 0;
		$orders_data = array();

		foreach ( $orders as $order ) {
			$agent_id = $order->get_meta( '_primary_agent_id' );
			$processor_id = $order->get_meta( '_processor_user_id' );
			$commission_data = $order->get_meta( '_commission_data' );

			if ( ! $commission_data ) {
				continue;
			}

			$user_earnings = 0;
			$role = null;

			if ( intval( $agent_id ) === intval( $user_id ) ) {
				$user_earnings = $commission_data['agent_earnings'];
				$role = 'agent';
			} elseif ( intval( $processor_id ) === intval( $user_id ) ) {
				$user_earnings = $commission_data['processor_earnings'];
				$role = 'processor';
			}

			if ( $user_earnings > 0 ) {
				$total_earnings += $user_earnings;
				$orders_data[] = array(
					'order_id'  => $order->get_id(),
					'date'      => $order->get_date_created()->format( 'Y-m-d' ),
					'total'     => $order->get_total(),
					'commission' => $commission_data['total_commission'],
					'earnings'  => $user_earnings,
					'role'      => $role,
				);
			}
		}

		return array(
			'total_earnings' => $total_earnings,
			'orders' => $orders_data,
		);
	}
}
