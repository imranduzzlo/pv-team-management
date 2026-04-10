<?php
/**
 * Core Commission Calculation Engine
 */

class WC_Team_Payroll_Core_Engine {

	public static function init() {
		add_action( 'woocommerce_order_status_completed', array( __CLASS__, 'calculate_order_commission' ) );
		add_action( 'woocommerce_order_status_processing', array( __CLASS__, 'calculate_order_commission' ) );
		add_action( 'woocommerce_order_item_added', array( __CLASS__, 'on_order_updated' ), 10, 3 );
		add_action( 'woocommerce_order_item_changed', array( __CLASS__, 'on_order_updated' ), 10, 3 );
		add_action( 'woocommerce_order_item_removed', array( __CLASS__, 'on_order_updated' ), 10, 2 );
		add_action( 'woocommerce_order_refunded', array( __CLASS__, 'on_order_updated' ), 10, 2 );
		add_action( 'woocommerce_order_partially_refunded', array( __CLASS__, 'on_order_updated' ), 10, 2 );
		add_action( 'woocommerce_order_status_cancelled', array( __CLASS__, 'on_order_cancelled' ) );
		add_action( 'woocommerce_order_status_failed', array( __CLASS__, 'on_order_cancelled' ) );
		add_action( 'woocommerce_order_status_refunded', array( __CLASS__, 'on_order_cancelled' ) );
		add_action( 'before_delete_post', array( __CLASS__, 'on_order_trashed' ) );
	}

	/**
	 * Calculate commission for an order
	 */
	public static function calculate_order_commission( $order_id ) {
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
	public static function calculate_commission( $order, $agent_id, $processor_id ) {
		$settings = get_option( 'wc_team_payroll_settings', array() );
		$acf_fields = get_option( 'wc_team_payroll_acf_fields', array() );
		
		$agent_percentage = isset( $settings['agent_percentage'] ) ? floatval( $settings['agent_percentage'] ) : 70;
		$processor_percentage = isset( $settings['processor_percentage'] ) ? floatval( $settings['processor_percentage'] ) : 30;
		$commission_field_name = isset( $acf_fields['commission_field_name'] ) ? $acf_fields['commission_field_name'] : 'team_commission';

		$commission_data = array(
			'order_id'       => $order->get_id(),
			'agent_id'       => $agent_id,
			'processor_id'   => $processor_id,
			'items'          => array(),
			'total_commission' => 0,
			'agent_earnings' => 0,
			'processor_earnings' => 0,
			'extra_earnings' => array(),
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

		// Apply high value bonus (over 10000 tk)
		$order_total = $order->get_total();
		if ( $order_total > 10000 ) {
			$commission_data['total_commission'] += 120;
		}

		// Apply split logic with fixed salary consideration
		if ( $agent_id === $processor_id || ! $processor_id ) {
			// Same user or no processor
			$agent_is_fixed = WC_Team_Payroll_Employee_Management::is_fixed_salary( $agent_id );
			$agent_is_combined = WC_Team_Payroll_Employee_Management::is_combined_salary( $agent_id );
			
			if ( $agent_is_fixed ) {
				// Fixed salary user gets 0 commission
				$commission_data['agent_earnings'] = 0;
				$commission_data['processor_earnings'] = 0;
			} elseif ( $agent_is_combined ) {
				// Combined salary: gets commission + base salary
				$commission_data['agent_earnings'] = $commission_data['total_commission'];
				$commission_data['processor_earnings'] = 0;
			} else {
				// Commission based user gets 100%
				$commission_data['agent_earnings'] = $commission_data['total_commission'];
				$commission_data['processor_earnings'] = 0;
			}
		} else {
			// Different users
			$agent_is_fixed = WC_Team_Payroll_Employee_Management::is_fixed_salary( $agent_id );
			$agent_is_combined = WC_Team_Payroll_Employee_Management::is_combined_salary( $agent_id );
			$processor_is_fixed = WC_Team_Payroll_Employee_Management::is_fixed_salary( $processor_id );
			$processor_is_combined = WC_Team_Payroll_Employee_Management::is_combined_salary( $processor_id );
			
			if ( $agent_is_fixed && $processor_is_fixed ) {
				// Both fixed salary - no commission
				$commission_data['agent_earnings'] = 0;
				$commission_data['processor_earnings'] = 0;
			} elseif ( $agent_is_fixed ) {
				// Agent is fixed, processor gets all
				$commission_data['agent_earnings'] = 0;
				$commission_data['processor_earnings'] = $processor_is_combined ? $commission_data['total_commission'] : $commission_data['total_commission'];
			} elseif ( $processor_is_fixed ) {
				// Processor is fixed, agent gets all
				$commission_data['agent_earnings'] = $agent_is_combined ? $commission_data['total_commission'] : $commission_data['total_commission'];
				$commission_data['processor_earnings'] = 0;
			} else {
				// Both commission or combined based - split
				$commission_data['agent_earnings'] = ( $commission_data['total_commission'] * $agent_percentage ) / 100;
				$commission_data['processor_earnings'] = ( $commission_data['total_commission'] * $processor_percentage ) / 100;
			}
		}

		// Apply extra earnings rules
		$extra_earnings = $this->apply_extra_earnings( $order, $commission_data );
		$commission_data['extra_earnings'] = $extra_earnings;

		return $commission_data;
	}

	/**
	 * Apply extra earnings rules
	 */
	private static function apply_extra_earnings( $order, $commission_data ) {
		$settings = get_option( 'wc_team_payroll_settings', array() );
		$extra_rules = isset( $settings['extra_earnings_rules'] ) ? $settings['extra_earnings_rules'] : array();

		$extra_earnings = array();
		$today = date( 'Y-m-d' );

		foreach ( $extra_rules as $rule ) {
			// Check if rule is active
			if ( ! isset( $rule['active'] ) || ! $rule['active'] ) {
				continue;
			}

			// Check if rule has expired
			if ( isset( $rule['end_date'] ) && ! empty( $rule['end_date'] ) ) {
				if ( $today > $rule['end_date'] ) {
					continue; // Rule expired
				}
			}

			// Check condition
			if ( ! $this->check_rule_condition( $order, $rule, $commission_data ) ) {
				continue;
			}

			$label = isset( $rule['label'] ) ? sanitize_text_field( $rule['label'] ) : '';
			$type = isset( $rule['type'] ) ? sanitize_text_field( $rule['type'] ) : 'fixed';
			$value = isset( $rule['value'] ) ? floatval( $rule['value'] ) : 0;

			$amount = 0;

			if ( 'fixed' === $type ) {
				$amount = $value;
			} elseif ( 'percentage_order' === $type ) {
				$amount = ( $order->get_total() * $value ) / 100;
			} elseif ( 'percentage_commission' === $type ) {
				$amount = ( $commission_data['total_commission'] * $value ) / 100;
			}

			if ( $amount > 0 ) {
				$extra_earnings[] = array(
					'label'  => $label,
					'type'   => $type,
					'value'  => $value,
					'amount' => $amount,
				);
			}
		}

		return $extra_earnings;
	}

	/**
	 * Check if rule condition is met
	 */
	private static function check_rule_condition( $order, $rule, $commission_data ) {
		$condition_type = isset( $rule['condition_type'] ) ? $rule['condition_type'] : 'none';
		$condition_value = isset( $rule['condition_value'] ) ? $rule['condition_value'] : '';

		if ( 'none' === $condition_type ) {
			return true; // No condition, always apply
		}

		if ( 'order_total' === $condition_type ) {
			$threshold = floatval( $condition_value );
			return $order->get_total() > $threshold;
		}

		if ( 'product_based' === $condition_type ) {
			$product_ids = array_map( 'intval', explode( ',', $condition_value ) );
			foreach ( $order->get_items() as $item ) {
				if ( in_array( $item->get_product_id(), $product_ids ) ) {
					return true;
				}
			}
			return false;
		}

		if ( 'category_based' === $condition_type ) {
			$categories = array_map( 'trim', explode( ',', $condition_value ) );
			foreach ( $order->get_items() as $item ) {
				$product_id = $item->get_product_id();
				$product_categories = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'names' ) );
				foreach ( $product_categories as $cat ) {
					if ( in_array( $cat, $categories ) ) {
						return true;
					}
				}
			}
			return false;
		}

		if ( 'agent_based' === $condition_type ) {
			$agent_ids = array_map( 'intval', explode( ',', $condition_value ) );
			return in_array( intval( $commission_data['agent_id'] ), $agent_ids );
		}

		return false;
	}

	/**
	 * Get commission data for order
	 */
	public static function get_order_commission( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return null;
		}

		return $order->get_meta( '_commission_data' );
	}

	/**
	 * Handle order updates (items added/removed/changed)
	 */
	public static function on_order_updated( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		// Only recalculate if order is in processing or completed status
		if ( ! in_array( $order->get_status(), array( 'completed', 'processing' ) ) ) {
			return;
		}

		// Get old commission data
		$old_commission_data = $order->get_meta( '_commission_data' );

		// Recalculate commission
		$this->calculate_order_commission( $order_id );

		// Get new commission data
		$new_commission_data = $order->get_meta( '_commission_data' );

		// Log the change
		$this->log_order_change( $order_id, $old_commission_data, $new_commission_data );
	}

	/**
	 * Log order changes
	 */
	private static function log_order_change( $order_id, $old_data, $new_data ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		$change_log = $order->get_meta( '_wc_tp_change_log' );
		if ( ! is_array( $change_log ) ) {
			$change_log = array();
		}

		$change_log[] = array(
			'timestamp'           => current_time( 'mysql' ),
			'changed_by'          => get_current_user_id(),
			'old_total_commission' => $old_data ? $old_data['total_commission'] : 0,
			'new_total_commission' => $new_data ? $new_data['total_commission'] : 0,
			'old_agent_earnings'   => $old_data ? $old_data['agent_earnings'] : 0,
			'new_agent_earnings'   => $new_data ? $new_data['agent_earnings'] : 0,
			'old_processor_earnings' => $old_data ? $old_data['processor_earnings'] : 0,
			'new_processor_earnings' => $new_data ? $new_data['processor_earnings'] : 0,
			'old_order_total'      => $old_data ? $order->get_total() : 0,
			'new_order_total'      => $order->get_total(),
		);

		$order->update_meta_data( '_wc_tp_change_log', $change_log );
		$order->save();

		do_action( 'wc_team_payroll_order_updated', $order_id, $old_data, $new_data );
	}

	/**
	 * Handle order cancellation/failure/refund
	 */
	public static function on_order_cancelled( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		// Get old commission data
		$old_commission_data = $order->get_meta( '_commission_data' );

		// Check if this is a refund and if we should calculate refund commission
		if ( 'refunded' === $order->get_status() ) {
			// Calculate refund commission
			$agent_id = $order->get_meta( '_primary_agent_id' );
			$processor_id = $order->get_meta( '_processor_user_id' );

			if ( $agent_id || $processor_id ) {
				$refund_commission_data = $this->calculate_refund_commission( $order, $agent_id, $processor_id );
				$order->update_meta_data( '_commission_data', $refund_commission_data );
				$order->save();

				// Log the refund
				$this->log_order_change( $order_id, $old_commission_data, $refund_commission_data );
				return;
			}
		}

		// For cancelled/failed orders, clear commission
		$order->delete_meta_data( '_commission_data' );
		$order->save();

		// Log the cancellation
		$this->log_order_cancellation( $order_id, $old_commission_data, $order->get_status() );
	}

	/**
	 * Calculate commission for refunded orders
	 */
	private static function calculate_refund_commission( $order, $agent_id, $processor_id ) {
		$settings = get_option( 'wc_team_payroll_settings', array() );
		
		$refund_type = isset( $settings['refund_commission_type'] ) ? $settings['refund_commission_type'] : 'none';
		$refund_value = isset( $settings['refund_commission_value'] ) ? floatval( $settings['refund_commission_value'] ) : 0;
		$agent_percentage = isset( $settings['agent_percentage'] ) ? floatval( $settings['agent_percentage'] ) : 70;
		$processor_percentage = isset( $settings['processor_percentage'] ) ? floatval( $settings['processor_percentage'] ) : 30;

		$commission_data = array(
			'order_id'       => $order->get_id(),
			'agent_id'       => $agent_id,
			'processor_id'   => $processor_id,
			'items'          => array(),
			'total_commission' => 0,
			'agent_earnings' => 0,
			'processor_earnings' => 0,
			'is_refund'      => true,
			'calculated_at'  => current_time( 'mysql' ),
		);

		if ( 'none' === $refund_type ) {
			return $commission_data; // No commission for refunded orders
		}

		$total_commission = 0;

		if ( 'percentage' === $refund_type ) {
			$total_commission = ( $order->get_total() * $refund_value ) / 100;
		} elseif ( 'flat' === $refund_type ) {
			$total_commission = $refund_value;
		}

		$commission_data['total_commission'] = $total_commission;

		// Apply split logic (same as regular commission)
		$agent_is_fixed = WC_Team_Payroll_Employee_Management::is_fixed_salary( $agent_id );
		$processor_is_fixed = WC_Team_Payroll_Employee_Management::is_fixed_salary( $processor_id );

		if ( $agent_id === $processor_id || ! $processor_id ) {
			if ( $agent_is_fixed ) {
				$commission_data['agent_earnings'] = 0;
				$commission_data['processor_earnings'] = 0;
			} else {
				$commission_data['agent_earnings'] = $total_commission;
				$commission_data['processor_earnings'] = 0;
			}
		} else {
			if ( $agent_is_fixed && $processor_is_fixed ) {
				$commission_data['agent_earnings'] = 0;
				$commission_data['processor_earnings'] = 0;
			} elseif ( $agent_is_fixed ) {
				$commission_data['agent_earnings'] = 0;
				$commission_data['processor_earnings'] = $total_commission;
			} elseif ( $processor_is_fixed ) {
				$commission_data['agent_earnings'] = $total_commission;
				$commission_data['processor_earnings'] = 0;
			} else {
				$commission_data['agent_earnings'] = ( $total_commission * $agent_percentage ) / 100;
				$commission_data['processor_earnings'] = ( $total_commission * $processor_percentage ) / 100;
			}
		}

		return $commission_data;
	}

	/**
	 * Handle order trashing/deletion
	 */
	public static function on_order_trashed( $post_id ) {
		// Check if this is a shop_order post type
		if ( get_post_type( $post_id ) !== 'shop_order' ) {
			return;
		}

		$order = wc_get_order( $post_id );
		if ( ! $order ) {
			return;
		}

		// Get old commission data
		$old_commission_data = $order->get_meta( '_commission_data' );

		// Log the deletion
		$this->log_order_cancellation( $post_id, $old_commission_data, 'trashed' );
	}

	/**
	 * Log order cancellation/deletion
	 */
	private static function log_order_cancellation( $order_id, $old_data, $reason ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		$cancellation_log = $order->get_meta( '_wc_tp_cancellation_log' );
		if ( ! is_array( $cancellation_log ) ) {
			$cancellation_log = array();
		}

		$cancellation_log[] = array(
			'timestamp'           => current_time( 'mysql' ),
			'reason'              => $reason,
			'cancelled_by'        => get_current_user_id(),
			'old_total_commission' => $old_data ? $old_data['total_commission'] : 0,
			'old_agent_earnings'   => $old_data ? $old_data['agent_earnings'] : 0,
			'old_processor_earnings' => $old_data ? $old_data['processor_earnings'] : 0,
		);

		$order->update_meta_data( '_wc_tp_cancellation_log', $cancellation_log );
		$order->save();

		do_action( 'wc_team_payroll_order_cancelled', $order_id, $reason, $old_data );
	}

	/**
	 * Get order change log
	 */
	public static function get_order_change_log( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return array();
		}

		$change_log = $order->get_meta( '_wc_tp_change_log' );
		return is_array( $change_log ) ? $change_log : array();
	}

	/**
	 * Get user earnings for date range
	 */
	public static function get_user_earnings( $user_id, $start_date = null, $end_date = null ) {
		if ( ! $start_date ) {
			$start_date = date( 'Y-m-01' );
		}
		if ( ! $end_date ) {
			$end_date = date( 'Y-m-t' );
		}

		$args = array(
			'limit'  => -1,
			'status' => array( 'completed', 'processing' ),
			'date_created' => array(
				'>=' => strtotime( $start_date ),
				'<=' => strtotime( $end_date . ' 23:59:59' ),
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
