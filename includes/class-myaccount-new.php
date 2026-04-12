<?php
/**
 * My Account Integration - Clean Implementation
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Team_Payroll_MyAccount_New {

	/**
	 * Custom endpoint names
	 */
	public static $endpoints = array(
		'salary-details'     => 'salary-details',
		'my-earnings'        => 'my-earnings', 
		'orders-commission'  => 'orders-commission',
		'reports'            => 'reports'
	);

	/**
	 * Initialize the class
	 */
	public static function init() {
		// Register endpoints
		add_action( 'init', array( __CLASS__, 'add_endpoints' ) );
		add_filter( 'query_vars', array( __CLASS__, 'add_query_vars' ), 0 );

		// Add menu items
		add_filter( 'woocommerce_account_menu_items', array( __CLASS__, 'add_menu_items' ) );

		// Add endpoint content
		add_action( 'woocommerce_account_salary-details_endpoint', array( __CLASS__, 'salary_details_content' ) );
		add_action( 'woocommerce_account_my-earnings_endpoint', array( __CLASS__, 'my_earnings_content' ) );
		add_action( 'woocommerce_account_orders-commission_endpoint', array( __CLASS__, 'orders_commission_content' ) );
		add_action( 'woocommerce_account_reports_endpoint', array( __CLASS__, 'reports_content' ) );

		// Enqueue assets
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	/**
	 * Register new endpoints
	 */
	public static function add_endpoints() {
		foreach ( self::$endpoints as $endpoint ) {
			add_rewrite_endpoint( $endpoint, EP_ROOT | EP_PAGES );
		}
	}

	/**
	 * Add query vars
	 */
	public static function add_query_vars( $vars ) {
		foreach ( self::$endpoints as $endpoint ) {
			$vars[] = $endpoint;
		}
		return $vars;
	}

	/**
	 * Add menu items
	 */
	public static function add_menu_items( $items ) {
		// Remove logout temporarily
		$logout = $items['customer-logout'];
		unset( $items['customer-logout'] );

		// Add our custom items (plain text only - icons added via JavaScript)
		$items['salary-details'] = __( 'Salary Details', 'wc-team-payroll' );
		$items['my-earnings'] = __( 'My Earnings', 'wc-team-payroll' );
		$items['orders-commission'] = __( 'My Orders (Commission)', 'wc-team-payroll' );
		$items['reports'] = __( 'Reports', 'wc-team-payroll' );

		// Add logout back
		$items['customer-logout'] = $logout;

		return $items;
	}

	/**
	 * Salary Details content
	 */
	public static function salary_details_content() {
		$user_id = get_current_user_id();
		$vb_user_id = get_user_meta( $user_id, 'vb_user_id', true );
		$user = get_user_by( 'ID', $user_id );
		
		// Get salary information
		$salary_type = get_user_meta( $user_id, '_wc_tp_salary_type', true ) ?: 'commission';
		$salary_amount = get_user_meta( $user_id, '_wc_tp_salary_amount', true ) ?: 0;
		$salary_frequency = get_user_meta( $user_id, '_wc_tp_salary_frequency', true ) ?: 'monthly';
		$employee_status = get_user_meta( $user_id, '_wc_tp_employee_status', true ) ?: 'active';
		
		// Get salary history
		$salary_history = get_user_meta( $user_id, '_wc_tp_salary_history', true );
		if ( ! is_array( $salary_history ) ) {
			$salary_history = array();
		}
		
		// Get payment methods
		$payment_methods = get_user_meta( $user_id, '_wc_tp_payment_methods', true );
		if ( ! is_array( $payment_methods ) ) {
			$payment_methods = array();
		}
		
		// Determine salary type labels
		$is_fixed = ( $salary_type === 'fixed' );
		$is_combined = ( $salary_type === 'combined' );
		$is_commission = ( $salary_type === 'commission' );
		
		?>
		<div class="wc-team-payroll-salary-details">
			<h2><i class="fas fa-briefcase"></i> <?php esc_html_e( 'Salary Details', 'wc-team-payroll' ); ?></h2>
			
			<!-- Employee Information -->
			<div class="employee-info-section">
				<h3><i class="fas fa-user"></i> <?php esc_html_e( 'Employee Information', 'wc-team-payroll' ); ?></h3>
				<div class="info-grid">
					<div class="info-card">
						<label><?php esc_html_e( 'Employee ID', 'wc-team-payroll' ); ?></label>
						<span class="value"><?php echo esc_html( $vb_user_id ?: 'Not Set' ); ?></span>
					</div>
					<div class="info-card">
						<label><?php esc_html_e( 'Full Name', 'wc-team-payroll' ); ?></label>
						<span class="value"><?php echo esc_html( $user->display_name ); ?></span>
					</div>
					<div class="info-card">
						<label><?php esc_html_e( 'Email', 'wc-team-payroll' ); ?></label>
						<span class="value"><?php echo esc_html( $user->user_email ); ?></span>
					</div>
					<div class="info-card">
						<label><?php esc_html_e( 'Status', 'wc-team-payroll' ); ?></label>
						<span class="value status-<?php echo esc_attr( $employee_status ); ?>">
							<i class="fas fa-circle"></i> <?php echo esc_html( ucfirst( $employee_status ) ); ?>
						</span>
					</div>
				</div>
			</div>

			<!-- Salary Information -->
			<div class="salary-info-section">
				<h3><i class="fas fa-money-bill-wave"></i> <?php esc_html_e( 'Salary Information', 'wc-team-payroll' ); ?></h3>
				<div class="salary-info-card">
					<div class="salary-type-badge salary-type-<?php echo esc_attr( $salary_type ); ?>">
						<?php
						if ( $is_fixed ) {
							echo '<i class="fas fa-coins"></i> ' . esc_html__( 'Fixed Salary', 'wc-team-payroll' );
						} elseif ( $is_combined ) {
							echo '<i class="fas fa-chart-line"></i> ' . esc_html__( 'Combined (Base + Commission)', 'wc-team-payroll' );
						} else {
							echo '<i class="fas fa-percentage"></i> ' . esc_html__( 'Commission Based', 'wc-team-payroll' );
						}
						?>
					</div>
					
					<?php if ( $is_fixed || $is_combined ) : ?>
						<div class="salary-details-grid">
							<div class="salary-detail">
								<label><?php esc_html_e( 'Base Salary Amount', 'wc-team-payroll' ); ?></label>
								<span class="amount"><?php echo wp_kses_post( wc_price( $salary_amount ) ); ?></span>
							</div>
							<div class="salary-detail">
								<label><?php esc_html_e( 'Payment Frequency', 'wc-team-payroll' ); ?></label>
								<span class="frequency">
									<?php
									$frequency_labels = array(
										'daily'   => __( 'Daily', 'wc-team-payroll' ),
										'weekly'  => __( 'Weekly', 'wc-team-payroll' ),
										'monthly' => __( 'Monthly', 'wc-team-payroll' ),
										'yearly'  => __( 'Yearly', 'wc-team-payroll' ),
									);
									echo esc_html( $frequency_labels[ $salary_frequency ] ?? ucfirst( $salary_frequency ) );
									?>
								</span>
							</div>
						</div>
					<?php endif; ?>
					
					<?php if ( $is_combined ) : ?>
						<div class="commission-note">
							<i class="fas fa-info-circle"></i>
							<span><?php esc_html_e( 'You also earn commission from orders in addition to your base salary.', 'wc-team-payroll' ); ?></span>
						</div>
					<?php elseif ( $is_commission ) : ?>
						<div class="commission-note">
							<i class="fas fa-info-circle"></i>
							<span><?php esc_html_e( 'Your earnings are based entirely on commission from orders you process.', 'wc-team-payroll' ); ?></span>
						</div>
					<?php endif; ?>
				</div>
			</div>

			<!-- Payment Methods -->
			<?php if ( ! empty( $payment_methods ) ) : ?>
				<div class="payment-methods-section">
					<h3><i class="fas fa-credit-card"></i> <?php esc_html_e( 'Payment Methods', 'wc-team-payroll' ); ?></h3>
					<div class="payment-methods-grid">
						<?php foreach ( $payment_methods as $method ) : ?>
							<div class="payment-method-card">
								<div class="method-header">
									<i class="fas fa-university"></i>
									<span class="method-name"><?php echo esc_html( $method['method_name'] ); ?></span>
								</div>
								<div class="method-details">
									<?php echo esc_html( $method['method_details'] ); ?>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>

			<!-- Salary History -->
			<?php if ( ! empty( $salary_history ) ) : ?>
				<div class="salary-history-section">
					<h3><i class="fas fa-history"></i> <?php esc_html_e( 'Salary Change History', 'wc-team-payroll' ); ?></h3>
					<div class="table-container">
						<table class="woocommerce-table woocommerce-table--salary-history">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Date', 'wc-team-payroll' ); ?></th>
									<th><?php esc_html_e( 'Previous', 'wc-team-payroll' ); ?></th>
									<th><?php esc_html_e( 'New', 'wc-team-payroll' ); ?></th>
									<th><?php esc_html_e( 'Changed By', 'wc-team-payroll' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( array_reverse( $salary_history ) as $history ) : 
									$changed_by_id = isset( $history['changed_by'] ) ? $history['changed_by'] : 0;
									$changed_by_user = $changed_by_id ? get_user_by( 'id', $changed_by_id ) : null;
								?>
									<tr>
										<td>
											<span class="date"><?php echo esc_html( date( 'M j, Y', strtotime( $history['date'] ) ) ); ?></span>
											<small><?php echo esc_html( date( 'g:i A', strtotime( $history['date'] ) ) ); ?></small>
										</td>
										<td>
											<div class="salary-change">
												<span class="type"><?php echo esc_html( ucfirst( $history['old_type'] ) ); ?></span>
												<?php if ( isset( $history['old_amount'] ) && $history['old_amount'] > 0 ) : ?>
													<span class="amount"><?php echo wp_kses_post( wc_price( $history['old_amount'] ) ); ?></span>
												<?php endif; ?>
											</div>
										</td>
										<td>
											<div class="salary-change">
												<span class="type"><?php echo esc_html( ucfirst( $history['new_type'] ) ); ?></span>
												<?php if ( isset( $history['new_amount'] ) && $history['new_amount'] > 0 ) : ?>
													<span class="amount"><?php echo wp_kses_post( wc_price( $history['new_amount'] ) ); ?></span>
												<?php endif; ?>
											</div>
										</td>
										<td>
											<?php if ( $changed_by_user ) : ?>
												<span class="changed-by"><?php echo esc_html( $changed_by_user->display_name ); ?></span>
												<small><?php echo esc_html( $changed_by_user->user_email ); ?></small>
											<?php else : ?>
												<span class="changed-by"><?php esc_html_e( 'System', 'wc-team-payroll' ); ?></span>
											<?php endif; ?>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				</div>
			<?php else : ?>
				<div class="no-history-message">
					<i class="fas fa-info-circle"></i>
					<p><?php esc_html_e( 'No salary change history available.', 'wc-team-payroll' ); ?></p>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * My Earnings content
	 */
	public static function my_earnings_content() {
		$user_id = get_current_user_id();
		$vb_user_id = get_user_meta( $user_id, 'vb_user_id', true );
		
		// Get current month earnings
		$current_month_start = date( 'Y-m-01' );
		$current_month_end = date( 'Y-m-t' );
		
		// Get earnings data (you'll need to implement this based on your payroll engine)
		$current_month_earnings = self::get_user_earnings_for_period( $user_id, $current_month_start, $current_month_end );
		$total_earnings = self::get_user_total_earnings( $user_id );
		$total_paid = self::get_user_total_paid( $user_id );
		$total_due = $total_earnings - $total_paid;
		
		// Get monthly history (last 12 months)
		$monthly_history = self::get_user_monthly_history( $user_id, 12 );
		
		?>
		<div class="wc-team-payroll-earnings">
			<h2><i class="fas fa-wallet"></i> <?php esc_html_e( 'My Earnings', 'wc-team-payroll' ); ?></h2>
			
			<!-- Employee Info -->
			<div class="employee-header">
				<div class="employee-id">
					<i class="fas fa-id-badge"></i>
					<span><?php esc_html_e( 'Employee ID:', 'wc-team-payroll' ); ?> <strong><?php echo esc_html( $vb_user_id ?: 'Not Set' ); ?></strong></span>
				</div>
			</div>

			<!-- Earnings Summary Cards -->
			<div class="earnings-summary">
				<div class="earning-card current-month">
					<div class="card-header">
						<i class="fas fa-calendar-alt"></i>
						<h4><?php esc_html_e( 'This Month', 'wc-team-payroll' ); ?></h4>
					</div>
					<p class="amount"><?php echo wp_kses_post( wc_price( $current_month_earnings ) ); ?></p>
					<p class="label"><?php echo esc_html( date( 'F Y' ) ); ?></p>
				</div>
				
				<div class="earning-card total-earnings">
					<div class="card-header">
						<i class="fas fa-chart-line"></i>
						<h4><?php esc_html_e( 'Total Earnings', 'wc-team-payroll' ); ?></h4>
					</div>
					<p class="amount"><?php echo wp_kses_post( wc_price( $total_earnings ) ); ?></p>
					<p class="label"><?php esc_html_e( 'All Time', 'wc-team-payroll' ); ?></p>
				</div>
				
				<div class="earning-card total-paid">
					<div class="card-header">
						<i class="fas fa-check-circle"></i>
						<h4><?php esc_html_e( 'Total Paid', 'wc-team-payroll' ); ?></h4>
					</div>
					<p class="amount paid"><?php echo wp_kses_post( wc_price( $total_paid ) ); ?></p>
					<p class="label"><?php esc_html_e( 'Received', 'wc-team-payroll' ); ?></p>
				</div>
				
				<div class="earning-card total-due">
					<div class="card-header">
						<i class="fas fa-clock"></i>
						<h4><?php esc_html_e( 'Amount Due', 'wc-team-payroll' ); ?></h4>
					</div>
					<p class="amount due"><?php echo wp_kses_post( wc_price( $total_due ) ); ?></p>
					<p class="label"><?php esc_html_e( 'Pending', 'wc-team-payroll' ); ?></p>
				</div>
			</div>

			<!-- Monthly History -->
			<?php if ( ! empty( $monthly_history ) ) : ?>
				<div class="earnings-history-section">
					<h3><i class="fas fa-history"></i> <?php esc_html_e( 'Monthly Earnings History', 'wc-team-payroll' ); ?></h3>
					<div class="table-container">
						<table class="woocommerce-table woocommerce-table--earnings">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Month', 'wc-team-payroll' ); ?></th>
									<th><?php esc_html_e( 'Orders', 'wc-team-payroll' ); ?></th>
									<th><?php esc_html_e( 'Total Earned', 'wc-team-payroll' ); ?></th>
									<th><?php esc_html_e( 'Paid', 'wc-team-payroll' ); ?></th>
									<th><?php esc_html_e( 'Due', 'wc-team-payroll' ); ?></th>
									<th><?php esc_html_e( 'Status', 'wc-team-payroll' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $monthly_history as $month_data ) : 
									$due_amount = $month_data['total'] - $month_data['paid'];
									$status = $due_amount <= 0 ? 'paid' : 'pending';
								?>
									<tr>
										<td>
											<span class="month-name"><?php echo esc_html( date( 'F Y', strtotime( $month_data['date'] . '-01' ) ) ); ?></span>
										</td>
										<td>
											<span class="orders-count"><?php echo esc_html( $month_data['orders_count'] ?? 0 ); ?></span>
										</td>
										<td>
											<span class="amount-earned"><?php echo wp_kses_post( wc_price( $month_data['total'] ) ); ?></span>
										</td>
										<td>
											<span class="amount-paid"><?php echo wp_kses_post( wc_price( $month_data['paid'] ) ); ?></span>
										</td>
										<td>
											<span class="amount-due"><?php echo wp_kses_post( wc_price( $due_amount ) ); ?></span>
										</td>
										<td>
											<span class="status-badge status-<?php echo esc_attr( $status ); ?>">
												<?php if ( $status === 'paid' ) : ?>
													<i class="fas fa-check-circle"></i> <?php esc_html_e( 'Paid', 'wc-team-payroll' ); ?>
												<?php else : ?>
													<i class="fas fa-clock"></i> <?php esc_html_e( 'Pending', 'wc-team-payroll' ); ?>
												<?php endif; ?>
											</span>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				</div>
			<?php else : ?>
				<div class="no-earnings-message">
					<i class="fas fa-chart-line"></i>
					<h4><?php esc_html_e( 'No Earnings Yet', 'wc-team-payroll' ); ?></h4>
					<p><?php esc_html_e( 'Start processing orders to see your earnings here.', 'wc-team-payroll' ); ?></p>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Orders Commission content
	 */
	public static function orders_commission_content() {
		$user_id = get_current_user_id();
		$vb_user_id = get_user_meta( $user_id, 'vb_user_id', true );
		
		?>
		<div class="wc-team-payroll-orders">
			<h2><i class="fas fa-shopping-bag"></i> <?php esc_html_e( 'My Orders (Commission)', 'wc-team-payroll' ); ?></h2>
			
			<!-- Employee Info -->
			<div class="employee-header">
				<div class="employee-id">
					<i class="fas fa-id-badge"></i>
					<span><?php esc_html_e( 'Employee ID:', 'wc-team-payroll' ); ?> <strong><?php echo esc_html( $vb_user_id ?: 'Not Set' ); ?></strong></span>
				</div>
			</div>

			<!-- Filters and Controls -->
			<div class="orders-controls">
				<div class="filters-section">
					<h3><i class="fas fa-filter"></i> <?php esc_html_e( 'Filters & Search', 'wc-team-payroll' ); ?></h3>
					<div class="filters-grid">
						<div class="filter-group">
							<label for="role-filter"><?php esc_html_e( 'My Role', 'wc-team-payroll' ); ?></label>
							<select id="role-filter" onchange="loadOrdersData()">
								<option value="all"><?php esc_html_e( 'All Orders', 'wc-team-payroll' ); ?></option>
								<option value="agent"><?php esc_html_e( 'As Agent', 'wc-team-payroll' ); ?></option>
								<option value="processor"><?php esc_html_e( 'As Processor', 'wc-team-payroll' ); ?></option>
							</select>
						</div>

						<div class="filter-group">
							<label for="date-from"><?php esc_html_e( 'Date From', 'wc-team-payroll' ); ?></label>
							<input type="date" id="date-from" onchange="loadOrdersData()" />
						</div>

						<div class="filter-group">
							<label for="date-to"><?php esc_html_e( 'Date To', 'wc-team-payroll' ); ?></label>
							<input type="date" id="date-to" onchange="loadOrdersData()" />
						</div>

						<div class="filter-group">
							<label for="status-filter"><?php esc_html_e( 'Order Status', 'wc-team-payroll' ); ?></label>
							<select id="status-filter" onchange="loadOrdersData()">
								<option value="all"><?php esc_html_e( 'All Status', 'wc-team-payroll' ); ?></option>
								<option value="completed"><?php esc_html_e( 'Completed', 'wc-team-payroll' ); ?></option>
								<option value="processing"><?php esc_html_e( 'Processing', 'wc-team-payroll' ); ?></option>
								<option value="refunded"><?php esc_html_e( 'Refunded', 'wc-team-payroll' ); ?></option>
							</select>
						</div>

						<div class="filter-group">
							<label for="sort-by"><?php esc_html_e( 'Sort By', 'wc-team-payroll' ); ?></label>
							<select id="sort-by" onchange="loadOrdersData()">
								<option value="date-desc"><?php esc_html_e( 'Date (Newest First)', 'wc-team-payroll' ); ?></option>
								<option value="date-asc"><?php esc_html_e( 'Date (Oldest First)', 'wc-team-payroll' ); ?></option>
								<option value="total-desc"><?php esc_html_e( 'Total (High to Low)', 'wc-team-payroll' ); ?></option>
								<option value="total-asc"><?php esc_html_e( 'Total (Low to High)', 'wc-team-payroll' ); ?></option>
								<option value="earning-desc"><?php esc_html_e( 'My Earning (High to Low)', 'wc-team-payroll' ); ?></option>
								<option value="earning-asc"><?php esc_html_e( 'My Earning (Low to High)', 'wc-team-payroll' ); ?></option>
							</select>
						</div>

						<div class="filter-group">
							<label for="search-orders"><?php esc_html_e( 'Search Orders', 'wc-team-payroll' ); ?></label>
							<input type="text" id="search-orders" placeholder="<?php esc_attr_e( 'Order ID, Customer...', 'wc-team-payroll' ); ?>" onkeyup="debounceSearch()" />
						</div>
					</div>
					
					<div class="filter-actions">
						<button type="button" onclick="clearFilters()" class="btn-clear">
							<i class="fas fa-times"></i> <?php esc_html_e( 'Clear Filters', 'wc-team-payroll' ); ?>
						</button>
						<button type="button" onclick="exportOrders()" class="btn-export">
							<i class="fas fa-download"></i> <?php esc_html_e( 'Export Data', 'wc-team-payroll' ); ?>
						</button>
					</div>
				</div>
			</div>

			<!-- Orders Summary -->
			<div class="orders-summary" id="orders-summary">
				<div class="summary-card">
					<i class="fas fa-shopping-cart"></i>
					<div class="summary-content">
						<span class="summary-number" id="total-orders">0</span>
						<span class="summary-label"><?php esc_html_e( 'Total Orders', 'wc-team-payroll' ); ?></span>
					</div>
				</div>
				<div class="summary-card">
					<i class="fas fa-dollar-sign"></i>
					<div class="summary-content">
						<span class="summary-number" id="total-commission">$0.00</span>
						<span class="summary-label"><?php esc_html_e( 'Total Commission', 'wc-team-payroll' ); ?></span>
					</div>
				</div>
				<div class="summary-card">
					<i class="fas fa-wallet"></i>
					<div class="summary-content">
						<span class="summary-number" id="my-earnings">$0.00</span>
						<span class="summary-label"><?php esc_html_e( 'My Earnings', 'wc-team-payroll' ); ?></span>
					</div>
				</div>
			</div>

			<!-- Orders Table -->
			<div class="orders-table-section">
				<div class="table-header">
					<h3><i class="fas fa-table"></i> <?php esc_html_e( 'Orders List', 'wc-team-payroll' ); ?></h3>
					<div class="table-controls">
						<div class="per-page-control">
							<label for="per-page"><?php esc_html_e( 'Show:', 'wc-team-payroll' ); ?></label>
							<select id="per-page" onchange="changePerPage()">
								<option value="10">10</option>
								<option value="25" selected>25</option>
								<option value="50">50</option>
								<option value="100">100</option>
							</select>
							<span><?php esc_html_e( 'per page', 'wc-team-payroll' ); ?></span>
						</div>
					</div>
				</div>

				<div class="table-container">
					<table class="woocommerce-table woocommerce-table--orders" id="orders-table">
						<thead>
							<tr>
								<th class="sortable" data-sort="order_id">
									<?php esc_html_e( 'Order ID', 'wc-team-payroll' ); ?>
									<i class="fas fa-sort"></i>
								</th>
								<th class="sortable" data-sort="date">
									<?php esc_html_e( 'Date', 'wc-team-payroll' ); ?>
									<i class="fas fa-sort"></i>
								</th>
								<th><?php esc_html_e( 'Customer', 'wc-team-payroll' ); ?></th>
								<th><?php esc_html_e( 'My Role', 'wc-team-payroll' ); ?></th>
								<th class="sortable" data-sort="total">
									<?php esc_html_e( 'Order Total', 'wc-team-payroll' ); ?>
									<i class="fas fa-sort"></i>
								</th>
								<th><?php esc_html_e( 'Commission', 'wc-team-payroll' ); ?></th>
								<th class="sortable" data-sort="earning">
									<?php esc_html_e( 'My Earning', 'wc-team-payroll' ); ?>
									<i class="fas fa-sort"></i>
								</th>
								<th><?php esc_html_e( 'Status', 'wc-team-payroll' ); ?></th>
								<th><?php esc_html_e( 'Actions', 'wc-team-payroll' ); ?></th>
							</tr>
						</thead>
						<tbody id="orders-tbody">
							<tr>
								<td colspan="9" class="loading-row">
									<div class="loading-spinner">
										<i class="fas fa-spinner fa-spin"></i>
										<?php esc_html_e( 'Loading orders...', 'wc-team-payroll' ); ?>
									</div>
								</td>
							</tr>
						</tbody>
					</table>
				</div>

				<!-- Pagination -->
				<div class="pagination-container" id="pagination-container">
					<!-- Pagination will be inserted here by JavaScript -->
				</div>
			</div>

			<!-- Order Details Modal -->
			<div id="order-details-modal" class="modal-overlay" style="display: none;">
				<div class="modal-content">
					<div class="modal-header">
						<h3><i class="fas fa-receipt"></i> <?php esc_html_e( 'Order Details', 'wc-team-payroll' ); ?></h3>
						<button type="button" class="modal-close" onclick="closeOrderDetails()">
							<i class="fas fa-times"></i>
						</button>
					</div>
					<div class="modal-body" id="order-details-content">
						<!-- Order details will be loaded here -->
					</div>
				</div>
			</div>
		</div>

		<script>
			let currentPage = 1;
			let perPage = 25;
			let searchTimeout;

			// Load orders data
			function loadOrdersData() {
				const roleFilter = document.getElementById('role-filter').value;
				const dateFrom = document.getElementById('date-from').value;
				const dateTo = document.getElementById('date-to').value;
				const statusFilter = document.getElementById('status-filter').value;
				const sortBy = document.getElementById('sort-by').value;
				const search = document.getElementById('search-orders').value;

				// Show loading
				document.getElementById('orders-tbody').innerHTML = `
					<tr>
						<td colspan="9" class="loading-row">
							<div class="loading-spinner">
								<i class="fas fa-spinner fa-spin"></i>
								<?php esc_html_e( 'Loading orders...', 'wc-team-payroll' ); ?>
							</div>
						</td>
					</tr>
				`;

				jQuery.ajax({
					url: '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>',
					type: 'POST',
					data: {
						action: 'wc_tp_get_myaccount_orders',
						role_filter: roleFilter,
						date_from: dateFrom,
						date_to: dateTo,
						status_filter: statusFilter,
						sort_by: sortBy,
						search: search,
						page: currentPage,
						per_page: perPage,
						nonce: '<?php echo esc_js( wp_create_nonce( 'wc_team_payroll_nonce' ) ); ?>'
					},
					success: function(response) {
						if (response.success) {
							updateOrdersTable(response.data.orders);
							updateSummary(response.data.summary);
							updatePagination(response.data.pagination);
						} else {
							showError(response.data.message || 'Failed to load orders');
						}
					},
					error: function() {
						showError('Network error occurred');
					}
				});
			}

			// Update orders table
			function updateOrdersTable(orders) {
				const tbody = document.getElementById('orders-tbody');
				
				if (orders.length === 0) {
					tbody.innerHTML = `
						<tr>
							<td colspan="9" class="no-orders-row">
								<div class="no-orders-message">
									<i class="fas fa-shopping-bag"></i>
									<p><?php esc_html_e( 'No orders found matching your criteria.', 'wc-team-payroll' ); ?></p>
								</div>
							</td>
						</tr>
					`;
					return;
				}

				tbody.innerHTML = '';
				orders.forEach(order => {
					const row = document.createElement('tr');
					row.innerHTML = `
						<td>
							<a href="#" onclick="showOrderDetails(${order.order_id}); return false;" class="order-link">
								#${order.order_id}
							</a>
						</td>
						<td>
							<span class="order-date">${order.date}</span>
							<small class="order-time">${order.time}</small>
						</td>
						<td>
							<span class="customer-name">${order.customer_name}</span>
							<small class="customer-email">${order.customer_email}</small>
						</td>
						<td>
							<span class="role-badge role-${order.my_role}">
								<i class="fas ${order.my_role === 'agent' ? 'fa-user-tie' : 'fa-cogs'}"></i>
								${order.my_role_label}
							</span>
						</td>
						<td class="amount-cell">${order.total}</td>
						<td class="amount-cell">${order.commission}</td>
						<td class="amount-cell earning-amount">${order.earning}</td>
						<td>
							<span class="status-badge status-${order.status}">
								${order.status_label}
							</span>
						</td>
						<td>
							<div class="action-buttons">
								<button onclick="showOrderDetails(${order.order_id})" class="btn-action btn-view" title="<?php esc_attr_e( 'View Details', 'wc-team-payroll' ); ?>">
									<i class="fas fa-eye"></i>
								</button>
								<a href="${order.edit_url}" class="btn-action btn-edit" title="<?php esc_attr_e( 'Edit Order', 'wc-team-payroll' ); ?>" target="_blank">
									<i class="fas fa-edit"></i>
								</a>
							</div>
						</td>
					`;
					tbody.appendChild(row);
				});
			}

			// Update summary cards
			function updateSummary(summary) {
				document.getElementById('total-orders').textContent = summary.total_orders;
				document.getElementById('total-commission').textContent = summary.total_commission;
				document.getElementById('my-earnings').textContent = summary.my_earnings;
			}

			// Update pagination
			function updatePagination(pagination) {
				const container = document.getElementById('pagination-container');
				if (pagination.total_pages <= 1) {
					container.innerHTML = '';
					return;
				}

				let paginationHTML = '<div class="pagination">';
				
				// Previous button
				if (pagination.current_page > 1) {
					paginationHTML += `<button onclick="goToPage(${pagination.current_page - 1})" class="page-btn prev-btn"><i class="fas fa-chevron-left"></i></button>`;
				}

				// Page numbers
				for (let i = 1; i <= pagination.total_pages; i++) {
					if (i === pagination.current_page) {
						paginationHTML += `<button class="page-btn current-page">${i}</button>`;
					} else if (i === 1 || i === pagination.total_pages || (i >= pagination.current_page - 2 && i <= pagination.current_page + 2)) {
						paginationHTML += `<button onclick="goToPage(${i})" class="page-btn">${i}</button>`;
					} else if (i === pagination.current_page - 3 || i === pagination.current_page + 3) {
						paginationHTML += '<span class="page-ellipsis">...</span>';
					}
				}

				// Next button
				if (pagination.current_page < pagination.total_pages) {
					paginationHTML += `<button onclick="goToPage(${pagination.current_page + 1})" class="page-btn next-btn"><i class="fas fa-chevron-right"></i></button>`;
				}

				paginationHTML += '</div>';
				paginationHTML += `<div class="pagination-info">Showing ${pagination.start} to ${pagination.end} of ${pagination.total} orders</div>`;
				
				container.innerHTML = paginationHTML;
			}

			// Go to specific page
			function goToPage(page) {
				currentPage = page;
				loadOrdersData();
			}

			// Change per page
			function changePerPage() {
				perPage = parseInt(document.getElementById('per-page').value);
				currentPage = 1;
				loadOrdersData();
			}

			// Debounced search
			function debounceSearch() {
				clearTimeout(searchTimeout);
				searchTimeout = setTimeout(() => {
					currentPage = 1;
					loadOrdersData();
				}, 500);
			}

			// Clear filters
			function clearFilters() {
				document.getElementById('role-filter').value = 'all';
				document.getElementById('date-from').value = '';
				document.getElementById('date-to').value = '';
				document.getElementById('status-filter').value = 'all';
				document.getElementById('sort-by').value = 'date-desc';
				document.getElementById('search-orders').value = '';
				currentPage = 1;
				loadOrdersData();
			}

			// Show order details
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
							document.getElementById('order-details-modal').style.display = 'flex';
						}
					}
				});
			}

			// Close order details
			function closeOrderDetails() {
				document.getElementById('order-details-modal').style.display = 'none';
			}

			// Export orders
			function exportOrders() {
				// Implementation for exporting orders
				alert('Export functionality will be implemented');
			}

			// Show error message
			function showError(message) {
				document.getElementById('orders-tbody').innerHTML = `
					<tr>
						<td colspan="9" class="error-row">
							<div class="error-message">
								<i class="fas fa-exclamation-triangle"></i>
								<p>${message}</p>
							</div>
						</td>
					</tr>
				`;
			}

			// Load data on page load
			jQuery(document).ready(function() {
				loadOrdersData();
			});

			// Close modal when clicking outside
			document.getElementById('order-details-modal').addEventListener('click', function(e) {
				if (e.target === this) {
					closeOrderDetails();
				}
			});
		</script>
		<?php
	}

	/**
	 * Reports content
	 */
	public static function reports_content() {
		$user_id = get_current_user_id();
		$vb_user_id = get_user_meta( $user_id, 'vb_user_id', true );
		?>
		<div class="wc-team-payroll-reports">
			<h2><i class="fas fa-chart-bar"></i> <?php esc_html_e( 'Reports', 'wc-team-payroll' ); ?></h2>
			
			<!-- Employee Info -->
			<div class="employee-header">
				<div class="employee-id">
					<i class="fas fa-id-badge"></i>
					<span><?php esc_html_e( 'Employee ID:', 'wc-team-payroll' ); ?> <strong><?php echo esc_html( $vb_user_id ?: 'Not Set' ); ?></strong></span>
				</div>
			</div>
			
			<div class="reports-grid">
				<div class="report-card">
					<h4><?php esc_html_e( 'Monthly Summary', 'wc-team-payroll' ); ?></h4>
					<p><?php esc_html_e( 'View your monthly earnings and performance.', 'wc-team-payroll' ); ?></p>
				</div>
				<div class="report-card">
					<h4><?php esc_html_e( 'Commission Breakdown', 'wc-team-payroll' ); ?></h4>
					<p><?php esc_html_e( 'Detailed breakdown of your commissions.', 'wc-team-payroll' ); ?></p>
				</div>
				<div class="report-card">
					<h4><?php esc_html_e( 'Performance Analytics', 'wc-team-payroll' ); ?></h4>
					<p><?php esc_html_e( 'Track your performance over time.', 'wc-team-payroll' ); ?></p>
				</div>
				<div class="report-card">
					<h4><?php esc_html_e( 'Payment History', 'wc-team-payroll' ); ?></h4>
					<p><?php esc_html_e( 'View all your payment records.', 'wc-team-payroll' ); ?></p>
				</div>
			</div>
			
			<!-- Debug Info -->
			<div style="margin-top: 30px; padding: 15px; background: #f0f0f1; border-radius: 5px; font-size: 12px; color: #666;">
				<strong>Debug Info:</strong><br>
				User ID: <?php echo esc_html( $user_id ); ?><br>
				VB User ID: <?php echo esc_html( $vb_user_id ?: 'Not Set' ); ?><br>
				CSS Version: <?php echo esc_html( WC_TEAM_PAYROLL_VERSION ); ?><br>
				CSS File: <?php echo esc_html( WC_TEAM_PAYROLL_URL . 'assets/css/myaccount.css' ); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Enqueue assets
	 */
	public static function enqueue_assets() {
		if ( is_account_page() ) {
			// Enqueue Font Awesome
			wp_enqueue_style( 
				'font-awesome', 
				'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', 
				array(), 
				'6.4.0' 
			);
			
			// Enqueue our CSS with version-based cache busting
			wp_enqueue_style( 
				'wc-team-payroll-myaccount', 
				WC_TEAM_PAYROLL_URL . 'assets/css/myaccount.css', 
				array(), 
				WC_TEAM_PAYROLL_VERSION . '-' . filemtime( WC_TEAM_PAYROLL_PATH . 'assets/css/myaccount.css' )
			);

			// Add inline CSS to ensure styles are applied
			wp_add_inline_style( 'wc-team-payroll-myaccount', '
				/* Force CSS to load and apply */
				.woocommerce-account .woocommerce-MyAccount-content .wc-team-payroll-salary-details,
				.woocommerce-account .woocommerce-MyAccount-content .wc-team-payroll-earnings,
				.woocommerce-account .woocommerce-MyAccount-content .wc-team-payroll-orders,
				.woocommerce-account .woocommerce-MyAccount-content .wc-team-payroll-reports {
					padding: 20px 0 !important;
					font-family: inherit !important;
					background: #fff !important;
					color: #333 !important;
				}
				
				/* Menu icons styling */
				.woocommerce-MyAccount-navigation ul li a i {
					margin-right: 8px !important;
					display: inline-block !important;
					width: 20px !important;
					text-align: center !important;
					font-size: 20px !important;
				}
				
				/* Grid layouts */
				.wc-team-payroll-salary-details .info-grid,
				.wc-team-payroll-earnings .earnings-summary {
					display: grid !important;
					grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)) !important;
					gap: 20px !important;
					margin-bottom: 20px !important;
				}
				
				/* Cards styling */
				.wc-team-payroll-salary-details .info-card,
				.wc-team-payroll-earnings .earning-card {
					background: #f8f9fa !important;
					border: 1px solid #e9ecef !important;
					border-radius: 8px !important;
					padding: 20px !important;
				}
				
				/* Salary info card */
				.wc-team-payroll-salary-details .salary-info-card {
					background: #fff !important;
					border: 1px solid #e9ecef !important;
					border-radius: 12px !important;
					padding: 30px !important;
					box-shadow: 0 2px 8px rgba(0,0,0,0.1) !important;
					margin-bottom: 20px !important;
				}
				
				/* Tables */
				.wc-team-payroll-salary-details .table-container,
				.wc-team-payroll-earnings .table-container {
					overflow-x: auto !important;
					border-radius: 8px !important;
					border: 1px solid #e9ecef !important;
				}
				
				/* Ensure wc_price output is styled */
				.wc-team-payroll-salary-details .amount,
				.wc-team-payroll-earnings .amount {
					color: #28a745 !important;
					font-weight: 600 !important;
				}
				
				/* Debug: Add border to main containers */
				.wc-team-payroll-salary-details,
				.wc-team-payroll-earnings,
				.wc-team-payroll-orders,
				.wc-team-payroll-reports {
					border: 2px solid #007cba !important;
					margin: 10px 0 !important;
				}
			' );

			// Enqueue jQuery for AJAX and icon injection
			wp_enqueue_script( 'jquery' );
			
			// Add inline script to inject icons
			wp_add_inline_script( 'jquery', '
				jQuery(document).ready(function($) {
					// Function to add icons to menu items
					function addMyAccountIcons() {
						// Remove any existing icons first
						$(".woocommerce-MyAccount-navigation a i").remove();
						
						// Add icons to menu items
						$(".woocommerce-MyAccount-navigation a[href*=\'salary-details\']").each(function() {
							if (!$(this).find("i").length) {
								$(this).prepend("<i class=\'fas fa-briefcase\' style=\'margin-right: 8px; font-size: 20px; width: 20px; text-align: center; display: inline-block;\'></i>");
							}
						});
						
						$(".woocommerce-MyAccount-navigation a[href*=\'my-earnings\']").each(function() {
							if (!$(this).find("i").length) {
								$(this).prepend("<i class=\'fas fa-wallet\' style=\'margin-right: 8px; font-size: 20px; width: 20px; text-align: center; display: inline-block;\'></i>");
							}
						});
						
						$(".woocommerce-MyAccount-navigation a[href*=\'orders-commission\']").each(function() {
							if (!$(this).find("i").length) {
								$(this).prepend("<i class=\'fas fa-shopping-bag\' style=\'margin-right: 8px; font-size: 20px; width: 20px; text-align: center; display: inline-block;\'></i>");
							}
						});
						
						$(".woocommerce-MyAccount-navigation a[href*=\'reports\']").each(function() {
							if (!$(this).find("i").length) {
								$(this).prepend("<i class=\'fas fa-chart-bar\' style=\'margin-right: 8px; font-size: 20px; width: 20px; text-align: center; display: inline-block;\'></i>");
							}
						});
					}
					
					// Add icons on page load
					addMyAccountIcons();
					
					// Re-add icons after any AJAX updates (in case menu is refreshed)
					$(document).ajaxComplete(function() {
						setTimeout(addMyAccountIcons, 100);
					});
				});
			' );
		}
	}

	/**
	 * Flush rewrite rules (call this on activation)
	 */
	public static function flush_rewrite_rules() {
		self::add_endpoints();
		flush_rewrite_rules();
	}

	/**
	 * Helper: Get user earnings for a specific period
	 */
	private static function get_user_earnings_for_period( $user_id, $start_date, $end_date ) {
		// This would integrate with your payroll engine
		// For now, return sample data
		return 1250.00;
	}

	/**
	 * Helper: Get user total earnings
	 */
	private static function get_user_total_earnings( $user_id ) {
		// This would integrate with your payroll engine
		// For now, return sample data
		return 15750.00;
	}

	/**
	 * Helper: Get user total paid amount
	 */
	private static function get_user_total_paid( $user_id ) {
		// This would integrate with your payroll engine
		// For now, return sample data
		return 12500.00;
	}

	/**
	 * Helper: Get user monthly history
	 */
	private static function get_user_monthly_history( $user_id, $months = 12 ) {
		// This would integrate with your payroll engine
		// For now, return sample data
		$history = array();
		for ( $i = 0; $i < $months; $i++ ) {
			$date = date( 'Y-m', strtotime( "-{$i} months" ) );
			$history[] = array(
				'date' => $date,
				'total' => rand( 800, 2000 ),
				'paid' => rand( 600, 1800 ),
				'orders_count' => rand( 5, 25 ),
			);
		}
		return array_reverse( $history );
	}

	/**
	 * AJAX: Get orders data for My Account
	 */
	public static function ajax_get_orders() {
		check_ajax_referer( 'wc_team_payroll_nonce', 'nonce' );

		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			wp_send_json_error( __( 'Unauthorized', 'wc-team-payroll' ) );
		}

		// Get filters
		$role_filter = isset( $_POST['role_filter'] ) ? sanitize_text_field( $_POST['role_filter'] ) : 'all';
		$date_from = isset( $_POST['date_from'] ) ? sanitize_text_field( $_POST['date_from'] ) : '';
		$date_to = isset( $_POST['date_to'] ) ? sanitize_text_field( $_POST['date_to'] ) : '';
		$status_filter = isset( $_POST['status_filter'] ) ? sanitize_text_field( $_POST['status_filter'] ) : 'all';
		$sort_by = isset( $_POST['sort_by'] ) ? sanitize_text_field( $_POST['sort_by'] ) : 'date-desc';
		$search = isset( $_POST['search'] ) ? sanitize_text_field( $_POST['search'] ) : '';
		$page = isset( $_POST['page'] ) ? intval( $_POST['page'] ) : 1;
		$per_page = isset( $_POST['per_page'] ) ? intval( $_POST['per_page'] ) : 25;

		// Get orders
		$args = array(
			'limit'  => -1,
			'status' => array( 'completed', 'processing', 'refunded' ),
		);

		if ( $date_from ) {
			$args['date_created'] = '>=' . $date_from;
		}
		if ( $date_to ) {
			$args['date_created'] = '<=' . $date_to . ' 23:59:59';
		}

		$orders = wc_get_orders( $args );
		$filtered_orders = array();
		$total_commission = 0;
		$my_total_earnings = 0;

		foreach ( $orders as $order ) {
			$agent_id = $order->get_meta( '_primary_agent_id' );
			$processor_id = $order->get_meta( '_processor_user_id' );
			$commission_data = $order->get_meta( '_commission_data' );

			if ( ! $commission_data ) {
				continue;
			}

			// Check if user is involved in this order
			$user_role = null;
			if ( intval( $agent_id ) === intval( $user_id ) ) {
				$user_role = 'agent';
			} elseif ( intval( $processor_id ) === intval( $user_id ) ) {
				$user_role = 'processor';
			}

			if ( ! $user_role ) {
				continue;
			}

			// Apply role filter
			if ( $role_filter !== 'all' && $role_filter !== $user_role ) {
				continue;
			}

			// Apply status filter
			if ( $status_filter !== 'all' && $order->get_status() !== $status_filter ) {
				continue;
			}

			// Apply search filter
			if ( $search ) {
				$search_fields = array(
					$order->get_id(),
					$order->get_billing_first_name(),
					$order->get_billing_last_name(),
					$order->get_billing_email(),
				);
				$search_match = false;
				foreach ( $search_fields as $field ) {
					if ( stripos( $field, $search ) !== false ) {
						$search_match = true;
						break;
					}
				}
				if ( ! $search_match ) {
					continue;
				}
			}

			$my_earning = $user_role === 'agent' ? $commission_data['agent_earnings'] : $commission_data['processor_earnings'];
			$total_commission += $commission_data['total_commission'];
			$my_total_earnings += $my_earning;

			$filtered_orders[] = array(
				'order_id' => $order->get_id(),
				'date' => $order->get_date_created()->format( 'M j, Y' ),
				'time' => $order->get_date_created()->format( 'g:i A' ),
				'customer_name' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
				'customer_email' => $order->get_billing_email(),
				'my_role' => $user_role,
				'my_role_label' => $user_role === 'agent' ? __( 'Agent', 'wc-team-payroll' ) : __( 'Processor', 'wc-team-payroll' ),
				'total' => wp_kses_post( wc_price( $order->get_total() ) ),
				'commission' => wp_kses_post( wc_price( $commission_data['total_commission'] ) ),
				'earning' => wp_kses_post( wc_price( $my_earning ) ),
				'status' => $order->get_status(),
				'status_label' => wc_get_order_status_name( $order->get_status() ),
				'edit_url' => admin_url( 'post.php?post=' . $order->get_id() . '&action=edit' ),
				'total_raw' => $order->get_total(),
				'earning_raw' => $my_earning,
				'date_raw' => $order->get_date_created()->getTimestamp(),
			);
		}

		// Sort orders
		usort( $filtered_orders, function( $a, $b ) use ( $sort_by ) {
			switch ( $sort_by ) {
				case 'date-asc':
					return $a['date_raw'] - $b['date_raw'];
				case 'date-desc':
					return $b['date_raw'] - $a['date_raw'];
				case 'total-asc':
					return $a['total_raw'] - $b['total_raw'];
				case 'total-desc':
					return $b['total_raw'] - $a['total_raw'];
				case 'earning-asc':
					return $a['earning_raw'] - $b['earning_raw'];
				case 'earning-desc':
					return $b['earning_raw'] - $a['earning_raw'];
				default:
					return $b['date_raw'] - $a['date_raw'];
			}
		} );

		// Pagination
		$total_orders = count( $filtered_orders );
		$total_pages = ceil( $total_orders / $per_page );
		$offset = ( $page - 1 ) * $per_page;
		$paged_orders = array_slice( $filtered_orders, $offset, $per_page );

		wp_send_json_success( array(
			'orders' => $paged_orders,
			'summary' => array(
				'total_orders' => $total_orders,
				'total_commission' => wp_kses_post( wc_price( $total_commission ) ),
				'my_earnings' => wp_kses_post( wc_price( $my_total_earnings ) ),
			),
			'pagination' => array(
				'current_page' => $page,
				'total_pages' => $total_pages,
				'per_page' => $per_page,
				'total' => $total_orders,
				'start' => $offset + 1,
				'end' => min( $offset + $per_page, $total_orders ),
			),
		) );
	}

	/**
	 * AJAX: Get order details
	 */
	public static function ajax_get_order_details() {
		check_ajax_referer( 'wc_team_payroll_nonce', 'nonce' );

		$user_id = get_current_user_id();
		if ( ! $user_id ) {
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
		<div class="order-details-content">
			<div class="order-header">
				<h4><?php esc_html_e( 'Order Information', 'wc-team-payroll' ); ?></h4>
				<div class="order-basic-info">
					<div class="info-row">
						<span class="label"><?php esc_html_e( 'Order ID:', 'wc-team-payroll' ); ?></span>
						<span class="value">#<?php echo esc_html( $order_id ); ?></span>
					</div>
					<div class="info-row">
						<span class="label"><?php esc_html_e( 'Date:', 'wc-team-payroll' ); ?></span>
						<span class="value"><?php echo esc_html( $order->get_date_created()->format( 'F j, Y g:i A' ) ); ?></span>
					</div>
					<div class="info-row">
						<span class="label"><?php esc_html_e( 'Status:', 'wc-team-payroll' ); ?></span>
						<span class="value">
							<span class="status-badge status-<?php echo esc_attr( $order->get_status() ); ?>">
								<?php echo esc_html( wc_get_order_status_name( $order->get_status() ) ); ?>
							</span>
						</span>
					</div>
					<div class="info-row">
						<span class="label"><?php esc_html_e( 'Total:', 'wc-team-payroll' ); ?></span>
						<span class="value amount"><?php echo wp_kses_post( wc_price( $order->get_total() ) ); ?></span>
					</div>
				</div>
			</div>

			<div class="customer-info">
				<h4><?php esc_html_e( 'Customer Information', 'wc-team-payroll' ); ?></h4>
				<div class="customer-details">
					<div class="info-row">
						<span class="label"><?php esc_html_e( 'Name:', 'wc-team-payroll' ); ?></span>
						<span class="value"><?php echo esc_html( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ); ?></span>
					</div>
					<div class="info-row">
						<span class="label"><?php esc_html_e( 'Email:', 'wc-team-payroll' ); ?></span>
						<span class="value"><?php echo esc_html( $order->get_billing_email() ); ?></span>
					</div>
					<?php if ( $order->get_billing_phone() ) : ?>
						<div class="info-row">
							<span class="label"><?php esc_html_e( 'Phone:', 'wc-team-payroll' ); ?></span>
							<span class="value"><?php echo esc_html( $order->get_billing_phone() ); ?></span>
						</div>
					<?php endif; ?>
				</div>
			</div>

			<div class="team-assignment">
				<h4><?php esc_html_e( 'Team Assignment', 'wc-team-payroll' ); ?></h4>
				<div class="team-details">
					<div class="team-member">
						<span class="role-label"><?php esc_html_e( 'Agent:', 'wc-team-payroll' ); ?></span>
						<span class="member-info">
							<?php if ( $agent ) : ?>
								<?php echo esc_html( $agent->display_name ); ?>
								<?php if ( intval( $agent_id ) === intval( $user_id ) ) : ?>
									<span class="you-badge"><?php esc_html_e( '(You)', 'wc-team-payroll' ); ?></span>
								<?php endif; ?>
							<?php else : ?>
								<?php esc_html_e( 'Not assigned', 'wc-team-payroll' ); ?>
							<?php endif; ?>
						</span>
					</div>
					<div class="team-member">
						<span class="role-label"><?php esc_html_e( 'Processor:', 'wc-team-payroll' ); ?></span>
						<span class="member-info">
							<?php if ( $processor ) : ?>
								<?php echo esc_html( $processor->display_name ); ?>
								<?php if ( intval( $processor_id ) === intval( $user_id ) ) : ?>
									<span class="you-badge"><?php esc_html_e( '(You)', 'wc-team-payroll' ); ?></span>
								<?php endif; ?>
							<?php else : ?>
								<?php esc_html_e( 'Not assigned', 'wc-team-payroll' ); ?>
							<?php endif; ?>
						</span>
					</div>
				</div>
			</div>

			<div class="commission-breakdown">
				<h4><?php esc_html_e( 'Commission Breakdown', 'wc-team-payroll' ); ?></h4>
				<div class="commission-details">
					<div class="commission-row total">
						<span class="label"><?php esc_html_e( 'Total Commission:', 'wc-team-payroll' ); ?></span>
						<span class="value"><?php echo wp_kses_post( wc_price( $commission_data['total_commission'] ) ); ?></span>
					</div>
					<div class="commission-row">
						<span class="label"><?php esc_html_e( 'Agent Earnings:', 'wc-team-payroll' ); ?></span>
						<span class="value"><?php echo wp_kses_post( wc_price( $commission_data['agent_earnings'] ) ); ?></span>
					</div>
					<div class="commission-row">
						<span class="label"><?php esc_html_e( 'Processor Earnings:', 'wc-team-payroll' ); ?></span>
						<span class="value"><?php echo wp_kses_post( wc_price( $commission_data['processor_earnings'] ) ); ?></span>
					</div>
					<?php if ( ! empty( $commission_data['extra_earnings'] ) ) : ?>
						<div class="extra-earnings">
							<h5><?php esc_html_e( 'Extra Earnings:', 'wc-team-payroll' ); ?></h5>
							<?php foreach ( $commission_data['extra_earnings'] as $extra ) : ?>
								<div class="commission-row">
									<span class="label"><?php echo esc_html( $extra['label'] ); ?>:</span>
									<span class="value"><?php echo wp_kses_post( wc_price( $extra['amount'] ) ); ?></span>
								</div>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<?php
		$html = ob_get_clean();

		wp_send_json_success( array( 'html' => $html ) );
	}
}