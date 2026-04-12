<?php
/**
 * My Account Integration
 */

class WC_Team_Payroll_MyAccount {

	/**
	 * Initialize My Account
	 */
	public static function init() {
		// Add menu items
		add_filter( 'woocommerce_account_menu_items', array( __CLASS__, 'add_menu_items' ), 10 );
		
		// Add endpoint content hooks
		add_action( 'woocommerce_account_my_salary_details_endpoint', array( __CLASS__, 'render_salary_details_tab' ), 10 );
		add_action( 'woocommerce_account_my_earnings_endpoint', array( __CLASS__, 'render_earnings_tab' ), 10 );
		add_action( 'woocommerce_account_my_orders_commission_endpoint', array( __CLASS__, 'render_orders_tab' ), 10 );
		add_action( 'woocommerce_account_my_reports_endpoint', array( __CLASS__, 'render_reports_tab' ), 10 );
		
		// AJAX handlers
		add_action( 'wp_ajax_wc_tp_get_orders_data', array( __CLASS__, 'ajax_get_orders_data' ), 10 );
		add_action( 'wp_ajax_wc_tp_get_order_details', array( __CLASS__, 'ajax_get_order_details' ), 10 );
		
		// Enqueue Phosphor icons
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_icons' ), 10 );
		
		// Add icons via CSS and JavaScript
		add_action( 'wp_head', array( __CLASS__, 'add_menu_icons_css' ), 10 );
		add_action( 'wp_footer', array( __CLASS__, 'add_menu_icons_js' ), 10 );
	}

	/**
	 * Add query variables
	 */
	public static function add_query_vars( $vars ) {
		$vars[] = 'my-salary-details';
		$vars[] = 'my-earnings';
		$vars[] = 'my-orders-commission';
		$vars[] = 'my-reports';
		return $vars;
	}

	/**
	 * Add menu icons via JavaScript
	 */
	public static function add_menu_icons_js() {
		?>
		<script>
			document.addEventListener('DOMContentLoaded', function() {
				// Add icons to menu items
				const menuItems = {
					'my-salary-details': '<i class="ph ph-briefcase"></i> ',
					'my-earnings': '<i class="ph ph-wallet"></i> ',
					'my-orders-commission': '<i class="ph ph-shopping-bag"></i> ',
					'my-reports': '<i class="ph ph-chart-bar"></i> '
				};

				Object.keys(menuItems).forEach(function(key) {
					const links = document.querySelectorAll('a[href*="' + key + '"]');
					links.forEach(function(link) {
						// Only add icon if it doesn't already have one
						if (!link.querySelector('i')) {
							const icon = document.createElement('span');
							icon.innerHTML = menuItems[key];
							link.insertBefore(icon.firstChild, link.firstChild);
						}
					});
				});
			});
		</script>
		<?php
	}

	/**
	 * Enqueue Phosphor icons
	 */
	public static function enqueue_icons() {
		// Enqueue Phosphor icons
		wp_enqueue_script( 'phosphor-icons', 'https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.2', array(), '2.1.2', false );
		wp_enqueue_style( 'wc-team-payroll-myaccount', WC_TEAM_PAYROLL_URL . 'assets/css/myaccount.css', array(), WC_TEAM_PAYROLL_VERSION );
	}

	/**
	 * Add menu icons via CSS
	 */
	public static function add_menu_icons_css() {
		?>
		<style>
			.woocommerce-MyAccount-navigation ul li a i {
				margin-right: 8px;
				display: inline-block;
				font-size: 20px;
			}
		</style>
		<?php
	}

	/**
	 * Add menu items
	 */
	public static function add_menu_items( $items ) {
		// For testing - always show the tabs
		$new_items = array();
		foreach ( $items as $key => $item ) {
			$new_items[ $key ] = $item;
			if ( 'orders' === $key ) {
				$new_items['my-salary-details'] = __( 'Salary Details', 'wc-team-payroll' );
				$new_items['my-earnings'] = __( 'My Earnings', 'wc-team-payroll' );
				$new_items['my-orders-commission'] = __( 'My Orders (Commission)', 'wc-team-payroll' );
				$new_items['my-reports'] = __( 'Reports', 'wc-team-payroll' );
			}
		}

		return $new_items;
	}

	/**
	 * Render salary details tab
	 */
	public static function render_salary_details_tab() {
		echo '<div class="wc-team-payroll-myaccount-salary">';
		echo '<h2>Salary Details</h2>';
		echo '<p>This is the Salary Details page content. User ID: ' . get_current_user_id() . '</p>';
		echo '</div>';
	}

	/**
	 * Render earnings tab
	 */
	public static function render_earnings_tab() {
		echo '<div class="wc-team-payroll-myaccount-earnings">';
		echo '<h2>My Earnings</h2>';
		echo '<p>This is the My Earnings page content. User ID: ' . get_current_user_id() . '</p>';
		echo '</div>';
	}

	/**
	 * Render orders tab
	 */
	public static function render_orders_tab() {
		echo '<div class="wc-team-payroll-myaccount-orders">';
		echo '<h2>My Orders (Commission)</h2>';
		echo '<p>This is the My Orders (Commission) page content. User ID: ' . get_current_user_id() . '</p>';
		echo '</div>';
	}

	/**
	 * Render reports tab
	 */
	public static function render_reports_tab() {
		echo '<div class="wc-team-payroll-myaccount-reports">';
		echo '<h2>Reports</h2>';
		echo '<p>This is the Reports page content. User ID: ' . get_current_user_id() . '</p>';
		echo '</div>';
	}

	/**
	 * Check if user has one of the selected agent roles
	 */
	/**
	 * Check if user has agent role
	 */
	private static function user_has_agent_role( $user_id ) {
		$checkout_fields = get_option( 'wc_team_payroll_checkout_fields', array() );
		$agent_roles = isset( $checkout_fields['agent_user_roles'] ) && is_array( $checkout_fields['agent_user_roles'] ) 
			? $checkout_fields['agent_user_roles'] 
			: array( 'shop_employee', 'shop_manager', 'administrator' );

		$user = get_user_by( 'ID', $user_id );
		if ( ! $user ) {
			return false;
		}

		foreach ( $agent_roles as $role ) {
			if ( in_array( $role, $user->roles ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * AJAX: Get orders data with filters and sorting
	 */
	/**
	 * AJAX: Get orders data
	 */
	public static function ajax_get_orders_data() {
		check_ajax_referer( 'wc_team_payroll_nonce', 'nonce' );

		$user_id = get_current_user_id();
		if ( ! $user_id || ! self::user_has_agent_role( $user_id ) ) {
			wp_send_json_error( __( 'Unauthorized', 'wc-team-payroll' ) );
		}

		$role_filter = isset( $_POST['role_filter'] ) ? sanitize_text_field( $_POST['role_filter'] ) : 'all';
		$date_from = isset( $_POST['date_from'] ) ? sanitize_text_field( $_POST['date_from'] ) : '';
		$date_to = isset( $_POST['date_to'] ) ? sanitize_text_field( $_POST['date_to'] ) : '';
		$status_filter = isset( $_POST['status_filter'] ) ? sanitize_text_field( $_POST['status_filter'] ) : 'all';
		$sort_by = isset( $_POST['sort_by'] ) ? sanitize_text_field( $_POST['sort_by'] ) : 'date-desc';

		$args = array(
			'limit'  => -1,
			'status' => array( 'completed', 'processing', 'refunded' ),
		);

		$orders = wc_get_orders( $args );
		$filtered_orders = array();

		foreach ( $orders as $order ) {
			$agent_id = $order->get_meta( '_primary_agent_id' );
			$processor_id = $order->get_meta( '_processor_user_id' );
			$commission_data = $order->get_meta( '_commission_data' );

			if ( ! $commission_data ) {
				continue;
			}

			// Check role filter
			$include = false;
			$role = null;

			if ( 'all' === $role_filter ) {
				$include = ( intval( $agent_id ) === intval( $user_id ) || intval( $processor_id ) === intval( $user_id ) );
			} elseif ( 'agent' === $role_filter ) {
				$include = intval( $agent_id ) === intval( $user_id );
				$role = 'agent';
			} elseif ( 'processor' === $role_filter ) {
				$include = intval( $processor_id ) === intval( $user_id );
				$role = 'processor';
			}

			if ( ! $include ) {
				continue;
			}

			if ( ! $role ) {
				$role = intval( $agent_id ) === intval( $user_id ) ? 'agent' : 'processor';
			}

			// Check date filter
			$order_date = $order->get_date_created()->format( 'Y-m-d' );
			if ( ! empty( $date_from ) && $order_date < $date_from ) {
				continue;
			}
			if ( ! empty( $date_to ) && $order_date > $date_to ) {
				continue;
			}

			// Check status filter
			if ( 'all' !== $status_filter && $order->get_status() !== $status_filter ) {
				continue;
			}

			// Get "Made By" name
			$made_by = self::get_made_by_name( $user_id, $agent_id, $processor_id );

			$earnings = 'agent' === $role ? $commission_data['agent_earnings'] : $commission_data['processor_earnings'];

			$filtered_orders[] = array(
				'order_id'   => $order->get_id(),
				'date'       => $order_date,
				'made_by'    => $made_by,
				'total'      => wc_price( $order->get_total() ),
				'commission' => wc_price( $commission_data['total_commission'] ),
				'earning'    => wc_price( $earnings ),
				'role'       => ucfirst( $role ),
				'total_raw'  => $order->get_total(),
				'earning_raw' => $earnings,
			);
		}

		// Sort
		usort( $filtered_orders, function( $a, $b ) use ( $sort_by ) {
			switch ( $sort_by ) {
				case 'date-asc':
					return strtotime( $a['date'] ) - strtotime( $b['date'] );
				case 'date-desc':
					return strtotime( $b['date'] ) - strtotime( $a['date'] );
				case 'total-asc':
					return $a['total_raw'] - $b['total_raw'];
				case 'total-desc':
					return $b['total_raw'] - $a['total_raw'];
				case 'earning-asc':
					return $a['earning_raw'] - $b['earning_raw'];
				case 'earning-desc':
					return $b['earning_raw'] - $a['earning_raw'];
				default:
					return 0;
			}
		} );

		wp_send_json_success( array(
			'orders' => $filtered_orders,
		) );
	}

	/**
	 * Get "Made By" name
	 */
	/**
	 * Get made by name
	 */
	private static function get_made_by_name( $current_user_id, $agent_id, $processor_id ) {
		// If current user is both agent and processor
		if ( intval( $agent_id ) === intval( $processor_id ) && intval( $agent_id ) === intval( $current_user_id ) ) {
			return __( 'Me (Agent & Processor)', 'wc-team-payroll' );
		}

		// If current user is agent
		if ( intval( $agent_id ) === intval( $current_user_id ) ) {
			$processor = get_user_by( 'ID', $processor_id );
			return __( 'Me (Agent)', 'wc-team-payroll' ) . ' + ' . ( $processor ? $processor->display_name : __( 'Unknown', 'wc-team-payroll' ) );
		}

		// If current user is processor
		if ( intval( $processor_id ) === intval( $current_user_id ) ) {
			$agent = get_user_by( 'ID', $agent_id );
			return ( $agent ? $agent->display_name : __( 'Unknown', 'wc-team-payroll' ) ) . ' + Me (Processor)';
		}

		return __( 'Unknown', 'wc-team-payroll' );
	}

	/**
	 * AJAX: Get order details
	 */
	/**
	 * AJAX: Get order details
	 */
	public static function ajax_get_order_details() {
		check_ajax_referer( 'wc_team_payroll_nonce', 'nonce' );

		$user_id = get_current_user_id();
		if ( ! $user_id || ! self::user_has_agent_role( $user_id ) ) {
			wp_send_json_error( __( 'Unauthorized', 'wc-team-payroll' ) );
		}

		$order_id = intval( $_POST['order_id'] );
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			wp_send_json_error( __( 'Order not found', 'wc-team-payroll' ) );
		}

		$agent_id = $order->get_meta( '_primary_agent_id' );
		$processor_id = $order->get_meta( '_processor_user_id' );
		$commission_data = $order->get_meta( '_commission_data' );

		// Check if user is involved in this order
		if ( intval( $agent_id ) !== intval( $user_id ) && intval( $processor_id ) !== intval( $user_id ) ) {
			wp_send_json_error( __( 'Unauthorized', 'wc-team-payroll' ) );
		}

		$agent = get_user_by( 'ID', $agent_id );
		$processor = get_user_by( 'ID', $processor_id );

		ob_start();
		?>
		<h2><?php esc_html_e( 'Order Details', 'wc-team-payroll' ); ?> #<?php echo esc_html( $order_id ); ?></h2>

		<div style="margin-bottom: 20px;">
			<h3><?php esc_html_e( 'Order Information', 'wc-team-payroll' ); ?></h3>
			<table style="width: 100%; border-collapse: collapse;">
				<tr style="border-bottom: 1px solid #ddd;">
					<td style="padding: 10px; font-weight: bold;"><?php esc_html_e( 'Order Date', 'wc-team-payroll' ); ?></td>
					<td style="padding: 10px;"><?php echo esc_html( $order->get_date_created()->format( 'Y-m-d H:i:s' ) ); ?></td>
				</tr>
				<tr style="border-bottom: 1px solid #ddd;">
					<td style="padding: 10px; font-weight: bold;"><?php esc_html_e( 'Order Total', 'wc-team-payroll' ); ?></td>
					<td style="padding: 10px;"><?php echo wp_kses_post( wc_price( $order->get_total() ) ); ?></td>
				</tr>
				<tr style="border-bottom: 1px solid #ddd;">
					<td style="padding: 10px; font-weight: bold;"><?php esc_html_e( 'Order Status', 'wc-team-payroll' ); ?></td>
					<td style="padding: 10px;"><?php echo esc_html( wc_get_order_status_name( $order->get_status() ) ); ?></td>
				</tr>
			</table>
		</div>

		<div style="margin-bottom: 20px;">
			<h3><?php esc_html_e( 'Team Assignment', 'wc-team-payroll' ); ?></h3>
			<table style="width: 100%; border-collapse: collapse;">
				<tr style="border-bottom: 1px solid #ddd;">
					<td style="padding: 10px; font-weight: bold;"><?php esc_html_e( 'Agent', 'wc-team-payroll' ); ?></td>
					<td style="padding: 10px;">
						<?php
						if ( $agent ) {
							echo esc_html( $agent->display_name );
							$agent_is_fixed = WC_Team_Payroll_Employee_Management::is_fixed_salary( $agent->ID );
							$agent_is_combined = WC_Team_Payroll_Employee_Management::is_combined_salary( $agent->ID );
							if ( $agent_is_fixed ) {
								echo ' <span style="color: #666; font-size: 12px;">(Fixed Salary)</span>';
							} elseif ( $agent_is_combined ) {
								echo ' <span style="color: #666; font-size: 12px;">(Combined)</span>';
							} else {
								echo ' <span style="color: #666; font-size: 12px;">(Commission Based)</span>';
							}
						} else {
							echo esc_html__( 'None', 'wc-team-payroll' );
						}
						?>
					</td>
				</tr>
				<tr style="border-bottom: 1px solid #ddd;">
					<td style="padding: 10px; font-weight: bold;"><?php esc_html_e( 'Processor', 'wc-team-payroll' ); ?></td>
					<td style="padding: 10px;">
						<?php
						if ( $processor ) {
							echo esc_html( $processor->display_name );
							$processor_is_fixed = WC_Team_Payroll_Employee_Management::is_fixed_salary( $processor->ID );
							$processor_is_combined = WC_Team_Payroll_Employee_Management::is_combined_salary( $processor->ID );
							if ( $processor_is_fixed ) {
								echo ' <span style="color: #666; font-size: 12px;">(Fixed Salary)</span>';
							} elseif ( $processor_is_combined ) {
								echo ' <span style="color: #666; font-size: 12px;">(Combined)</span>';
							} else {
								echo ' <span style="color: #666; font-size: 12px;">(Commission Based)</span>';
							}
						} else {
							echo esc_html__( 'None', 'wc-team-payroll' );
						}
						?>
					</td>
				</tr>
			</table>
		</div>

		<div style="margin-bottom: 20px;">
			<h3><?php esc_html_e( 'Commission Breakdown', 'wc-team-payroll' ); ?></h3>
			<table style="width: 100%; border-collapse: collapse;">
				<tr style="border-bottom: 1px solid #ddd;">
					<td style="padding: 10px; font-weight: bold;"><?php esc_html_e( 'Total Commission', 'wc-team-payroll' ); ?></td>
					<td style="padding: 10px;"><?php echo wp_kses_post( wc_price( $commission_data['total_commission'] ) ); ?></td>
				</tr>
				<tr style="border-bottom: 1px solid #ddd;">
					<td style="padding: 10px; font-weight: bold;"><?php esc_html_e( 'Agent Earnings', 'wc-team-payroll' ); ?></td>
					<td style="padding: 10px;"><?php echo wp_kses_post( wc_price( $commission_data['agent_earnings'] ) ); ?></td>
				</tr>
				<tr style="border-bottom: 1px solid #ddd;">
					<td style="padding: 10px; font-weight: bold;"><?php esc_html_e( 'Processor Earnings', 'wc-team-payroll' ); ?></td>
					<td style="padding: 10px;"><?php echo wp_kses_post( wc_price( $commission_data['processor_earnings'] ) ); ?></td>
				</tr>
				<?php if ( ! empty( $commission_data['extra_earnings'] ) ) : ?>
					<tr style="border-bottom: 1px solid #ddd;">
						<td style="padding: 10px; font-weight: bold;"><?php esc_html_e( 'Extra Earnings', 'wc-team-payroll' ); ?></td>
						<td style="padding: 10px;">
							<?php foreach ( $commission_data['extra_earnings'] as $extra ) : ?>
								<div><?php echo esc_html( $extra['label'] ); ?>: <?php echo wp_kses_post( wc_price( $extra['amount'] ) ); ?></div>
							<?php endforeach; ?>
						</td>
					</tr>
				<?php endif; ?>
			</table>
		</div>

		<div style="margin-bottom: 20px;">
			<h3><?php esc_html_e( 'Order Items', 'wc-team-payroll' ); ?></h3>
			<table style="width: 100%; border-collapse: collapse;">
				<thead>
					<tr style="background: #f5f5f5; border-bottom: 2px solid #ddd;">
						<th style="padding: 10px; text-align: left;"><?php esc_html_e( 'Product', 'wc-team-payroll' ); ?></th>
						<th style="padding: 10px; text-align: right;"><?php esc_html_e( 'Qty', 'wc-team-payroll' ); ?></th>
						<th style="padding: 10px; text-align: right;"><?php esc_html_e( 'Total', 'wc-team-payroll' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $order->get_items() as $item ) : ?>
						<tr style="border-bottom: 1px solid #ddd;">
							<td style="padding: 10px;"><?php echo esc_html( $item->get_name() ); ?></td>
							<td style="padding: 10px; text-align: right;"><?php echo esc_html( $item->get_quantity() ); ?></td>
							<td style="padding: 10px; text-align: right;"><?php echo wp_kses_post( wc_price( $item->get_total() ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<?php
		// Show change log if exists
		$change_log = WC_Team_Payroll_Core_Engine::get_order_change_log( $order_id );

		if ( ! empty( $change_log ) ) :
		?>
		<div style="margin-bottom: 20px;">
			<h3><?php esc_html_e( 'Change History', 'wc-team-payroll' ); ?></h3>
			<table style="width: 100%; border-collapse: collapse;">
				<thead>
					<tr style="background: #f5f5f5; border-bottom: 2px solid #ddd;">
						<th style="padding: 10px; text-align: left;"><?php esc_html_e( 'Date', 'wc-team-payroll' ); ?></th>
						<th style="padding: 10px; text-align: left;"><?php esc_html_e( 'Changed By', 'wc-team-payroll' ); ?></th>
						<th style="padding: 10px; text-align: right;"><?php esc_html_e( 'Old Total', 'wc-team-payroll' ); ?></th>
						<th style="padding: 10px; text-align: right;"><?php esc_html_e( 'New Total', 'wc-team-payroll' ); ?></th>
						<th style="padding: 10px; text-align: right;"><?php esc_html_e( 'Old Commission', 'wc-team-payroll' ); ?></th>
						<th style="padding: 10px; text-align: right;"><?php esc_html_e( 'New Commission', 'wc-team-payroll' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $change_log as $change ) : ?>
						<tr style="border-bottom: 1px solid #ddd;">
							<td style="padding: 10px;"><?php echo esc_html( $change['timestamp'] ); ?></td>
							<td style="padding: 10px;">
								<?php
								$changed_by = get_user_by( 'ID', $change['changed_by'] );
								echo esc_html( $changed_by ? $changed_by->display_name : __( 'Unknown', 'wc-team-payroll' ) );
								?>
							</td>
							<td style="padding: 10px; text-align: right;"><?php echo wp_kses_post( wc_price( $change['old_order_total'] ) ); ?></td>
							<td style="padding: 10px; text-align: right;"><?php echo wp_kses_post( wc_price( $change['new_order_total'] ) ); ?></td>
							<td style="padding: 10px; text-align: right;"><?php echo wp_kses_post( wc_price( $change['old_total_commission'] ) ); ?></td>
							<td style="padding: 10px; text-align: right;"><?php echo wp_kses_post( wc_price( $change['new_total_commission'] ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php endif; ?>

		<?php
		$html = ob_get_clean();

		wp_send_json_success( array(
			'html' => $html,
		) );
	}
}
