<?php
/**
 * My Account Integration
 */

class WC_Team_Payroll_MyAccount {

	/**
	 * Initialize My Account
	 */
	public static function init() {
		// Register endpoints
		add_action( 'init', array( __CLASS__, 'register_endpoints' ), 5 );
		
		// Register query variables
		add_filter( 'query_vars', array( __CLASS__, 'add_query_vars' ), 10 );
		
		add_filter( 'woocommerce_account_menu_items', array( __CLASS__, 'add_menu_items' ), 10 );
		add_action( 'woocommerce_account_my-salary-details_endpoint', array( __CLASS__, 'render_salary_details_tab' ), 10 );
		add_action( 'woocommerce_account_my-earnings_endpoint', array( __CLASS__, 'render_earnings_tab' ), 10 );
		add_action( 'woocommerce_account_my-orders-commission_endpoint', array( __CLASS__, 'render_orders_tab' ), 10 );
		add_action( 'woocommerce_account_my-reports_endpoint', array( __CLASS__, 'render_reports_tab' ), 10 );
		add_action( 'wp_ajax_wc_tp_get_orders_data', array( __CLASS__, 'ajax_get_orders_data' ), 10 );
		add_action( 'wp_ajax_wc_tp_get_order_details', array( __CLASS__, 'ajax_get_order_details' ), 10 );
		
		// Enqueue Phosphor icons
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_icons' ), 10 );
		
		// Add icons via CSS
		add_action( 'wp_head', array( __CLASS__, 'add_menu_icons_css' ), 10 );
		
		// Allow HTML in menu items
		add_filter( 'woocommerce_account_menu_items', array( __CLASS__, 'allow_html_in_menu_items' ), 20 );
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
	 * Allow HTML in menu items
	 */
	public static function allow_html_in_menu_items( $items ) {
		foreach ( $items as $key => $item ) {
			$items[ $key ] = wp_kses_post( $item );
		}
		return $items;
	}

	/**
	 * Register WooCommerce endpoints
	 */
	public static function register_endpoints() {
		// Endpoints are now registered in the main plugin file with higher priority
		// This method is kept for backward compatibility
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
				font-size: 16px;
			}
		</style>
		<?php
	}

	/**
	 * Add menu items
	 */
	public static function add_menu_items( $items ) {
		$settings = get_option( 'wc_team_payroll_settings', array() );
		if ( ! isset( $settings['enable_myaccount'] ) || ! $settings['enable_myaccount'] ) {
			return $items;
		}

		// Check if current user has one of the selected agent roles
		$user_id = get_current_user_id();
		if ( ! $user_id || ! self::user_has_agent_role( $user_id ) ) {
			return $items;
		}

		$new_items = array();
		foreach ( $items as $key => $item ) {
			$new_items[ $key ] = $item;
			if ( 'orders' === $key ) {
				$new_items['my-salary-details'] = '<i class="ph ph-briefcase"></i> ' . __( 'Salary Details', 'wc-team-payroll' );
				$new_items['my-earnings'] = '<i class="ph ph-wallet"></i> ' . __( 'My Earnings', 'wc-team-payroll' );
				$new_items['my-orders-commission'] = '<i class="ph ph-shopping-bag"></i> ' . __( 'My Orders (Commission)', 'wc-team-payroll' );
				$new_items['my-reports'] = '<i class="ph ph-chart-bar"></i> ' . __( 'Reports', 'wc-team-payroll' );
			}
		}

		return $new_items;
	}

	/**
	 * Render salary details tab
	 */
	public static function render_salary_details_tab() {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return;
		}

		// Check if user has one of the selected agent roles
		if ( ! self::user_has_agent_role( $user_id ) ) {
			echo '<p>' . esc_html__( 'You do not have permission to view this page', 'wc-team-payroll' ) . '</p>';
			return;
		}

		$is_fixed = WC_Team_Payroll_Employee_Management::is_fixed_salary( $user_id );
		$is_combined = WC_Team_Payroll_Employee_Management::is_combined_salary( $user_id );
		$salary_info = WC_Team_Payroll_Employee_Management::get_user_salary( $user_id );
		$salary_history = WC_Team_Payroll_Employee_Management::get_salary_history( $user_id );

		?>
		<div class="wc-team-payroll-myaccount-salary">
			<h2><?php esc_html_e( 'Salary Details', 'wc-team-payroll' ); ?></h2>

			<div style="background: #f5f5f5; padding: 20px; border-radius: 4px; margin-bottom: 20px;">
				<h3><?php esc_html_e( 'Your Salary Type', 'wc-team-payroll' ); ?></h3>
				<table style="width: 100%; border-collapse: collapse;">
					<tr style="border-bottom: 1px solid #ddd;">
						<td style="padding: 10px; font-weight: bold;"><?php esc_html_e( 'Salary Type', 'wc-team-payroll' ); ?></td>
						<td style="padding: 10px;">
							<?php
							if ( $is_fixed ) {
								echo esc_html__( 'Fixed Salary', 'wc-team-payroll' );
							} elseif ( $is_combined ) {
								echo esc_html__( 'Combined (Base Salary + Commission)', 'wc-team-payroll' );
							} else {
								echo esc_html__( 'Commission Based', 'wc-team-payroll' );
							}
							?>
						</td>
					</tr>
					<?php if ( $is_fixed || $is_combined ) : ?>
						<tr style="border-bottom: 1px solid #ddd;">
							<td style="padding: 10px; font-weight: bold;"><?php esc_html_e( 'Base Salary Amount', 'wc-team-payroll' ); ?></td>
							<td style="padding: 10px;"><?php echo wp_kses_post( wc_price( $salary_info['amount'] ) ); ?></td>
						</tr>
						<tr style="border-bottom: 1px solid #ddd;">
							<td style="padding: 10px; font-weight: bold;"><?php esc_html_e( 'Salary Frequency', 'wc-team-payroll' ); ?></td>
							<td style="padding: 10px;">
								<?php
								$frequency_labels = array(
									'daily'   => __( 'Daily', 'wc-team-payroll' ),
									'weekly'  => __( 'Weekly', 'wc-team-payroll' ),
									'monthly' => __( 'Monthly', 'wc-team-payroll' ),
									'yearly'  => __( 'Yearly', 'wc-team-payroll' ),
								);
								echo esc_html( $frequency_labels[ $salary_info['frequency'] ] ?? $salary_info['frequency'] );
								?>
							</td>
						</tr>
					<?php endif; ?>
					<?php if ( $is_combined ) : ?>
						<tr style="border-bottom: 1px solid #ddd;">
							<td style="padding: 10px; font-weight: bold;"><?php esc_html_e( 'Commission', 'wc-team-payroll' ); ?></td>
							<td style="padding: 10px;"><?php esc_html_e( 'Yes - You also earn commission from orders', 'wc-team-payroll' ); ?></td>
						</tr>
					<?php endif; ?>
				</table>
			</div>

			<?php if ( ! empty( $salary_history ) ) : ?>
				<h3><?php esc_html_e( 'Salary Change History', 'wc-team-payroll' ); ?></h3>
				<table class="woocommerce-table woocommerce-table--orders" style="width: 100%; border-collapse: collapse;">
					<thead>
						<tr style="background: #f5f5f5; border-bottom: 2px solid #ddd;">
							<th style="padding: 10px; text-align: left;"><?php esc_html_e( 'Date', 'wc-team-payroll' ); ?></th>
							<th style="padding: 10px; text-align: left;"><?php esc_html_e( 'Old Type', 'wc-team-payroll' ); ?></th>
							<th style="padding: 10px; text-align: right;"><?php esc_html_e( 'Old Amount', 'wc-team-payroll' ); ?></th>
							<th style="padding: 10px; text-align: left;"><?php esc_html_e( 'New Type', 'wc-team-payroll' ); ?></th>
							<th style="padding: 10px; text-align: right;"><?php esc_html_e( 'New Amount', 'wc-team-payroll' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $salary_history as $history ) : ?>
							<tr style="border-bottom: 1px solid #ddd;">
								<td style="padding: 10px;"><?php echo esc_html( $history['date'] ); ?></td>
								<td style="padding: 10px;"><?php echo esc_html( ucfirst( $history['old_type'] ) ); ?></td>
								<td style="padding: 10px; text-align: right;"><?php echo wp_kses_post( wc_price( $history['old_amount'] ) ); ?></td>
								<td style="padding: 10px;"><?php echo esc_html( ucfirst( $history['new_type'] ) ); ?></td>
								<td style="padding: 10px; text-align: right;"><?php echo wp_kses_post( wc_price( $history['new_amount'] ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render earnings tab
	 */
	public static function render_earnings_tab() {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return;
		}

		// Check if user has one of the selected agent roles
		if ( ! self::user_has_agent_role( $user_id ) ) {
			echo '<p>' . esc_html__( 'You do not have permission to view this page', 'wc-team-payroll' ) . '</p>';
			return;
		}

		$history = WC_Team_Payroll_Payroll_Engine::get_user_payroll_history( $user_id, 12 );

		?>
		<div class="wc-team-payroll-myaccount-earnings">
			<h2><?php esc_html_e( 'My Earnings', 'wc-team-payroll' ); ?></h2>

			<table class="woocommerce-table woocommerce-table--orders">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Month', 'wc-team-payroll' ); ?></th>
						<th><?php esc_html_e( 'Total', 'wc-team-payroll' ); ?></th>
						<th><?php esc_html_e( 'Paid', 'wc-team-payroll' ); ?></th>
						<th><?php esc_html_e( 'Due', 'wc-team-payroll' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $history as $month_data ) : ?>
						<tr>
							<td><?php echo esc_html( date( 'F Y', strtotime( $month_data['date'] ) ) ); ?></td>
							<td><?php echo wp_kses_post( wc_price( $month_data['total'] ) ); ?></td>
							<td><?php echo wp_kses_post( wc_price( $month_data['paid'] ) ); ?></td>
							<td><?php echo wp_kses_post( wc_price( $month_data['due'] ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Render orders tab
	 */
	public static function render_orders_tab() {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return;
		}

		// Check if user has one of the selected agent roles
		if ( ! self::user_has_agent_role( $user_id ) ) {
			echo '<p>' . esc_html__( 'You do not have permission to view this page', 'wc-team-payroll' ) . '</p>';
			return;
		}

		?>
		<div class="wc-team-payroll-myaccount-orders">
			<h2><?php esc_html_e( 'My Orders (Commission)', 'wc-team-payroll' ); ?></h2>

			<!-- Filters -->
			<div class="wc-team-payroll-filters" style="margin-bottom: 20px; padding: 15px; background: #f5f5f5; border-radius: 4px;">
				<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
					<div>
						<label><?php esc_html_e( 'Role Filter', 'wc-team-payroll' ); ?></label>
						<select id="role-filter" onchange="loadOrdersData()">
							<option value="all"><?php esc_html_e( 'All Orders', 'wc-team-payroll' ); ?></option>
							<option value="agent"><?php esc_html_e( 'Assigned to Me (Agent)', 'wc-team-payroll' ); ?></option>
							<option value="processor"><?php esc_html_e( 'Processed by Me', 'wc-team-payroll' ); ?></option>
						</select>
					</div>

					<div>
						<label><?php esc_html_e( 'Date From', 'wc-team-payroll' ); ?></label>
						<input type="date" id="date-from" onchange="loadOrdersData()" />
					</div>

					<div>
						<label><?php esc_html_e( 'Date To', 'wc-team-payroll' ); ?></label>
						<input type="date" id="date-to" onchange="loadOrdersData()" />
					</div>

					<div>
						<label><?php esc_html_e( 'Order Status', 'wc-team-payroll' ); ?></label>
						<select id="status-filter" onchange="loadOrdersData()">
							<option value="all"><?php esc_html_e( 'All Status', 'wc-team-payroll' ); ?></option>
							<option value="completed"><?php esc_html_e( 'Completed', 'wc-team-payroll' ); ?></option>
							<option value="processing"><?php esc_html_e( 'Processing', 'wc-team-payroll' ); ?></option>
						</select>
					</div>

					<div>
						<label><?php esc_html_e( 'Sort By', 'wc-team-payroll' ); ?></label>
						<select id="sort-by" onchange="loadOrdersData()">
							<option value="date-desc"><?php esc_html_e( 'Date (Newest)', 'wc-team-payroll' ); ?></option>
							<option value="date-asc"><?php esc_html_e( 'Date (Oldest)', 'wc-team-payroll' ); ?></option>
							<option value="total-desc"><?php esc_html_e( 'Total (High to Low)', 'wc-team-payroll' ); ?></option>
							<option value="total-asc"><?php esc_html_e( 'Total (Low to High)', 'wc-team-payroll' ); ?></option>
							<option value="earning-desc"><?php esc_html_e( 'My Earning (High to Low)', 'wc-team-payroll' ); ?></option>
							<option value="earning-asc"><?php esc_html_e( 'My Earning (Low to High)', 'wc-team-payroll' ); ?></option>
						</select>
					</div>
				</div>
			</div>

			<!-- Orders Table -->
			<table class="woocommerce-table woocommerce-table--orders" id="orders-table" style="width: 100%; border-collapse: collapse;">
				<thead>
					<tr>
						<th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;"><?php esc_html_e( 'Order ID', 'wc-team-payroll' ); ?></th>
						<th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;"><?php esc_html_e( 'Date', 'wc-team-payroll' ); ?></th>
						<th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;"><?php esc_html_e( 'Made By', 'wc-team-payroll' ); ?></th>
						<th style="padding: 10px; text-align: right; border-bottom: 2px solid #ddd;"><?php esc_html_e( 'Total', 'wc-team-payroll' ); ?></th>
						<th style="padding: 10px; text-align: right; border-bottom: 2px solid #ddd;"><?php esc_html_e( 'Commission', 'wc-team-payroll' ); ?></th>
						<th style="padding: 10px; text-align: right; border-bottom: 2px solid #ddd;"><?php esc_html_e( 'My Earning', 'wc-team-payroll' ); ?></th>
						<th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;"><?php esc_html_e( 'Role', 'wc-team-payroll' ); ?></th>
						<th style="padding: 10px; text-align: center; border-bottom: 2px solid #ddd;"><?php esc_html_e( 'Action', 'wc-team-payroll' ); ?></th>
					</tr>
				</thead>
				<tbody id="orders-tbody">
					<tr><td colspan="8" style="text-align: center; padding: 20px;"><?php esc_html_e( 'Loading...', 'wc-team-payroll' ); ?></td></tr>
				</tbody>
			</table>

			<!-- Order Details Modal -->
			<div id="order-details-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; overflow-y: auto;">
				<div style="background: white; margin: 50px auto; padding: 30px; max-width: 800px; border-radius: 8px;">
					<button onclick="closeOrderDetails()" style="float: right; background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
					<div id="order-details-content"></div>
				</div>
			</div>
		</div>

		<script>
			function loadOrdersData() {
				const roleFilter = document.getElementById('role-filter').value;
				const dateFrom = document.getElementById('date-from').value;
				const dateTo = document.getElementById('date-to').value;
				const statusFilter = document.getElementById('status-filter').value;
				const sortBy = document.getElementById('sort-by').value;

				jQuery.ajax({
					url: '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>',
					type: 'POST',
					data: {
						action: 'wc_tp_get_orders_data',
						role_filter: roleFilter,
						date_from: dateFrom,
						date_to: dateTo,
						status_filter: statusFilter,
						sort_by: sortBy,
						nonce: '<?php echo esc_js( wp_create_nonce( 'wc_team_payroll_nonce' ) ); ?>'
					},
					success: function(response) {
						if (response.success) {
							const tbody = document.getElementById('orders-tbody');
							tbody.innerHTML = '';

							if (response.data.orders.length === 0) {
								tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 20px;"><?php esc_html_e( 'No orders found', 'wc-team-payroll' ); ?></td></tr>';
								return;
							}

							response.data.orders.forEach(order => {
								const row = document.createElement('tr');
								row.style.borderBottom = '1px solid #eee';
								row.innerHTML = `
									<td style="padding: 10px;"><a href="#" onclick="showOrderDetails(${order.order_id}); return false;">#${order.order_id}</a></td>
									<td style="padding: 10px;">${order.date}</td>
									<td style="padding: 10px;">${order.made_by}</td>
									<td style="padding: 10px; text-align: right;">${order.total}</td>
									<td style="padding: 10px; text-align: right;">${order.commission}</td>
									<td style="padding: 10px; text-align: right; font-weight: bold; color: #0073aa;">${order.earning}</td>
									<td style="padding: 10px;">${order.role}</td>
									<td style="padding: 10px; text-align: center;">
										<button onclick="showOrderDetails(${order.order_id})" style="background: #0073aa; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer;"><?php esc_html_e( 'Details', 'wc-team-payroll' ); ?></button>
									</td>
								`;
								tbody.appendChild(row);
							});
						}
					}
				});
			}

			function showOrderDetails(orderId) {
				jQuery.ajax({
					url: '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>',
					type: 'POST',
					data: {
						action: 'wc_tp_get_order_details',
						order_id: orderId,
						nonce: '<?php echo esc_js( wp_create_nonce( 'wc_team_payroll_nonce' ) ); ?>'
					},
					success: function(response) {
						if (response.success) {
							document.getElementById('order-details-content').innerHTML = response.data.html;
							document.getElementById('order-details-modal').style.display = 'block';
						}
					}
				});
			}

			function closeOrderDetails() {
				document.getElementById('order-details-modal').style.display = 'none';
			}

			// Load on page load
			jQuery(document).ready(function() {
				loadOrdersData();
			});
		</script>
		<?php
	}

	/**
	 * Render reports tab
	 */
	public static function render_reports_tab() {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return;
		}

		// Check if user has one of the selected agent roles
		if ( ! self::user_has_agent_role( $user_id ) ) {
			echo '<p>' . esc_html__( 'You do not have permission to view this page', 'wc-team-payroll' ) . '</p>';
			return;
		}

		$current_month = WC_Team_Payroll_Core_Engine::get_user_earnings( $user_id );

		?>
		<div class="wc-team-payroll-myaccount-reports">
			<h2><?php esc_html_e( 'Reports', 'wc-team-payroll' ); ?></h2>

			<div class="wc-team-payroll-report-cards">
				<div class="report-card">
					<h3><?php esc_html_e( 'This Month', 'wc-team-payroll' ); ?></h3>
					<p class="amount"><?php echo wp_kses_post( wc_price( $current_month['total_earnings'] ) ); ?></p>
					<p class="label"><?php esc_html_e( 'Total Earnings', 'wc-team-payroll' ); ?></p>
				</div>

				<div class="report-card">
					<h3><?php esc_html_e( 'Orders', 'wc-team-payroll' ); ?></h3>
					<p class="amount"><?php echo esc_html( count( $current_month['orders'] ) ); ?></p>
					<p class="label"><?php esc_html_e( 'Total Orders', 'wc-team-payroll' ); ?></p>
				</div>
			</div>
		</div>
		<?php
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
