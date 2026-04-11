<?php
/**
 * Shortcodes System
 */

class WC_Team_Payroll_Shortcodes {

	public static function init() {
		add_shortcode( 'team_earnings', array( __CLASS__, 'shortcode_earnings' ) );
		add_shortcode( 'team_orders', array( __CLASS__, 'shortcode_orders' ) );
		add_action( 'admin_menu', array( __CLASS__, 'add_shortcode_builder' ) );
		
		// Enqueue admin CSS
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue admin CSS
	 */
	public static function enqueue_assets( $hook ) {
		// Only load on shortcode builder page
		if ( strpos( $hook, 'wc-team-payroll-shortcodes' ) === false ) {
			return;
		}

		wp_enqueue_style( 'wc-tp-common-css', WC_TEAM_PAYROLL_URL . 'assets/css/common.css', array(), WC_TEAM_PAYROLL_VERSION );
	}

	public static function add_shortcode_builder() {
		add_submenu_page(
			'wc-team-payroll',
			__( 'Shortcode Builder', 'wc-team-payroll' ),
			__( 'Shortcode Builder', 'wc-team-payroll' ),
			'manage_woocommerce',
			'wc-team-payroll-shortcodes',
			array( __CLASS__, 'render_shortcode_builder' )
		);
	}

	public static function render_shortcode_builder() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Unauthorized', 'wc-team-payroll' ) );
		}

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Shortcode Builder', 'wc-team-payroll' ); ?></h1>

			<div class="wc-team-payroll-shortcode-builder">
				<h2><?php esc_html_e( 'Team Earnings', 'wc-team-payroll' ); ?></h2>
				<p><?php esc_html_e( 'Display user earnings summary', 'wc-team-payroll' ); ?></p>
				<div class="shortcode-output">
					<code>[team_earnings user="current"]</code>
					<button class="button" onclick="copyToClipboard('[team_earnings user=\"current\"]')"><?php esc_html_e( 'Copy', 'wc-team-payroll' ); ?></button>
				</div>

				<h2><?php esc_html_e( 'Team Orders', 'wc-team-payroll' ); ?></h2>
				<p><?php esc_html_e( 'Display orders with commission breakdown', 'wc-team-payroll' ); ?></p>

				<div class="shortcode-output">
					<label><?php esc_html_e( 'Type:', 'wc-team-payroll' ); ?></label>
					<select id="orders-type">
						<option value="agent"><?php esc_html_e( 'Agent Orders', 'wc-team-payroll' ); ?></option>
						<option value="processor"><?php esc_html_e( 'Processor Orders', 'wc-team-payroll' ); ?></option>
						<option value="all"><?php esc_html_e( 'All Orders', 'wc-team-payroll' ); ?></option>
					</select>
					<code id="orders-shortcode">[team_orders type="agent"]</code>
					<button class="button" onclick="copyToClipboard(document.getElementById('orders-shortcode').textContent)"><?php esc_html_e( 'Copy', 'wc-team-payroll' ); ?></button>
				</div>
			</div>
		</div>

		<?php
	}

		<?php
	}

	public static function shortcode_earnings( $atts ) {
		$atts = shortcode_atts( array(
			'user' => 'current',
		), $atts );

		if ( 'current' === $atts['user'] ) {
			$user_id = get_current_user_id();
		} else {
			$user_id = intval( $atts['user'] );
		}

		if ( ! $user_id ) {
			return '<p>' . esc_html__( 'Please log in to view earnings', 'wc-team-payroll' ) . '</p>';
		}

		// Check if user has one of the selected agent roles
		if ( ! self::user_has_agent_role( $user_id ) ) {
			return '<p>' . esc_html__( 'You do not have permission to view earnings', 'wc-team-payroll' ) . '</p>';
		}

		$earnings = WC_Team_Payroll_Core_Engine::get_user_earnings( $user_id );

		ob_start();
		?>
		<div class="wc-team-payroll-earnings">
			<h3><?php esc_html_e( 'Your Earnings', 'wc-team-payroll' ); ?></h3>
			<div class="earnings-summary">
				<div class="earnings-card">
					<span class="label"><?php esc_html_e( 'Total Earnings', 'wc-team-payroll' ); ?></span>
					<span class="amount"><?php echo wp_kses_post( wc_price( $earnings['total_earnings'] ) ); ?></span>
				</div>
				<div class="earnings-card">
					<span class="label"><?php esc_html_e( 'Orders', 'wc-team-payroll' ); ?></span>
					<span class="amount"><?php echo esc_html( count( $earnings['orders'] ) ); ?></span>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	public static function shortcode_orders( $atts ) {
		$atts = shortcode_atts( array(
			'type'   => 'agent',
			'filter' => 'all',
		), $atts );

		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return '<p>' . esc_html__( 'Please log in to view orders', 'wc-team-payroll' ) . '</p>';
		}

		// Check if user has one of the selected agent roles
		if ( ! self::user_has_agent_role( $user_id ) ) {
			return '<p>' . esc_html__( 'You do not have permission to view orders', 'wc-team-payroll' ) . '</p>';
		}

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

			if ( 'agent' === $atts['type'] && intval( $agent_id ) === intval( $user_id ) ) {
				$filtered_orders[] = array(
					'order' => $order,
					'commission_data' => $commission_data,
					'role' => 'agent',
				);
			} elseif ( 'processor' === $atts['type'] && intval( $processor_id ) === intval( $user_id ) ) {
				$filtered_orders[] = array(
					'order' => $order,
					'commission_data' => $commission_data,
					'role' => 'processor',
				);
			}
		}

		ob_start();
		?>
		<div class="wc-team-payroll-orders">
			<table class="wc-team-payroll-orders-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Order ID', 'wc-team-payroll' ); ?></th>
						<th><?php esc_html_e( 'Date', 'wc-team-payroll' ); ?></th>
						<th><?php esc_html_e( 'Total', 'wc-team-payroll' ); ?></th>
						<th><?php esc_html_e( 'Commission', 'wc-team-payroll' ); ?></th>
						<th><?php esc_html_e( 'Your Earnings', 'wc-team-payroll' ); ?></th>
						<th><?php esc_html_e( 'Role', 'wc-team-payroll' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $filtered_orders as $item ) : ?>
						<tr>
							<td><?php echo esc_html( '#' . $item['order']->get_id() ); ?></td>
							<td><?php echo esc_html( $item['order']->get_date_created()->format( 'Y-m-d' ) ); ?></td>
							<td><?php echo wp_kses_post( wc_price( $item['order']->get_total() ) ); ?></td>
							<td><?php echo wp_kses_post( wc_price( $item['commission_data']['total_commission'] ) ); ?></td>
							<td>
								<?php
								$earnings = 'agent' === $item['role'] ? $item['commission_data']['agent_earnings'] : $item['commission_data']['processor_earnings'];
								echo wp_kses_post( wc_price( $earnings ) );
								?>
							</td>
							<td><?php echo esc_html( ucfirst( $item['role'] ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Check if user has one of the selected agent roles
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
}

