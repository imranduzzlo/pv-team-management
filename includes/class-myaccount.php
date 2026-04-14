<?php
/**
 * My Account Integration - Clean Implementation
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Team_Payroll_MyAccount {

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

		// AJAX handlers
		add_action( 'wp_ajax_wc_tp_get_earnings_data', array( __CLASS__, 'ajax_get_earnings_data' ) );

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
		
		// Get salary information - check for fixed/combined flags first
		$is_fixed_salary = get_user_meta( $user_id, '_wc_tp_fixed_salary', true );
		$is_combined_salary = get_user_meta( $user_id, '_wc_tp_combined_salary', true );
		
		if ( $is_fixed_salary ) {
			$salary_type = 'fixed';
		} elseif ( $is_combined_salary ) {
			$salary_type = 'combined';
		} else {
			$salary_type = get_user_meta( $user_id, '_wc_tp_salary_type', true ) ?: 'commission';
		}
		
		$salary_amount = get_user_meta( $user_id, '_wc_tp_salary_amount', true ) ?: 0;
		$salary_frequency = get_user_meta( $user_id, '_wc_tp_salary_frequency', true ) ?: 'monthly';
		
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
			<!-- Employee Header -->
			<?php echo self::get_employee_header( $user_id ); ?>

			<!-- Salary Information -->
			<div class="salary-info-section">
				<h3><?php esc_html_e( 'Salary Information', 'wc-team-payroll' ); ?></h3>
				<div class="salary-info-card">
					<div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 25px;">
						<div class="salary-type-badge salary-type-<?php echo esc_attr( $salary_type ); ?>">
							<?php
							if ( $is_fixed ) {
								echo '<i class="ph ph-coins"></i> ' . esc_html__( 'Fixed Salary', 'wc-team-payroll' );
							} elseif ( $is_combined ) {
								echo '<i class="ph ph-chart-line-up"></i> ' . esc_html__( 'Combined (Base + Commission)', 'wc-team-payroll' );
							} else {
								echo '<i class="ph ph-percent"></i> ' . esc_html__( 'Commission Based', 'wc-team-payroll' );
							}
							?>
						</div>
						
						<!-- Salary Display in Top Right -->
						<div style="text-align: right;">
							<?php if ( $is_fixed || $is_combined ) : ?>
								<div style="font-size: 24px; font-weight: 700; color: #28a745; margin-bottom: 5px;">
									<?php echo wp_kses_post( wc_price( $salary_amount ) ); ?>
								</div>
								<div style="font-size: 13px; color: #6c757d; text-transform: uppercase; letter-spacing: 0.5px;">
									<?php
									$frequency_labels = array(
										'daily'   => __( 'Per Day', 'wc-team-payroll' ),
										'weekly'  => __( 'Per Week', 'wc-team-payroll' ),
										'monthly' => __( 'Per Month', 'wc-team-payroll' ),
										'yearly'  => __( 'Per Year', 'wc-team-payroll' ),
									);
									echo esc_html( $frequency_labels[ $salary_frequency ] ?? ucfirst( $salary_frequency ) );
									?>
								</div>
							<?php elseif ( $is_commission ) : ?>
								<div style="font-size: 13px; color: #6c757d; text-transform: uppercase; letter-spacing: 0.5px;">
									<?php esc_html_e( 'Percentage/Order', 'wc-team-payroll' ); ?>
								</div>
							<?php endif; ?>
						</div>
					</div>
					
					<?php if ( $is_combined ) : ?>
						<div class="salary-type-note">
							<i class="ph ph-info"></i>
							<span><?php esc_html_e( 'You also earn commission from orders in addition to your base salary.', 'wc-team-payroll' ); ?></span>
						</div>
					<?php elseif ( $is_commission ) : ?>
						<div class="salary-type-note">
							<i class="ph ph-info"></i>
							<span><?php esc_html_e( 'Your earnings are based entirely on commission from orders you process.', 'wc-team-payroll' ); ?></span>
						</div>
					<?php elseif ( $is_fixed ) : ?>
						<div class="salary-type-note">
							<i class="ph ph-info"></i>
							<span><?php esc_html_e( 'You receive a fixed salary as shown above.', 'wc-team-payroll' ); ?></span>
						</div>
					<?php endif; ?>
				</div>
			</div>

			<!-- Payment Methods -->
			<?php if ( ! empty( $payment_methods ) ) : ?>
				<div class="payment-methods-section">
					<h3><i class="ph ph-credit-card"></i> <?php esc_html_e( 'Payment Methods', 'wc-team-payroll' ); ?></h3>
					<div class="payment-methods-grid">
						<?php foreach ( $payment_methods as $method ) : ?>
							<div class="payment-method-card">
								<div class="method-header">
									<i class="ph ph-bank"></i>
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
					<h3><?php esc_html_e( 'Salary Change History', 'wc-team-payroll' ); ?></h3>
					<div class="table-wrapper">
						<div class="section-header">
							<div class="table-controls">
								<div class="search-control">
									<input type="text" id="salary-history-search" placeholder="<?php esc_attr_e( 'Search history...', 'wc-team-payroll' ); ?>" />
									<i class="ph ph-magnifying-glass"></i>
								</div>
								<div class="per-page-control">
									<label for="salary-history-per-page"><?php esc_html_e( 'Show:', 'wc-team-payroll' ); ?></label>
									<select id="salary-history-per-page">
										<option value="5">5</option>
										<option value="10" selected>10</option>
										<option value="25">25</option>
										<option value="50">50</option>
									</select>
									<span><?php esc_html_e( 'per page', 'wc-team-payroll' ); ?></span>
								</div>
							</div>
						</div>
						
						<div class="table-container">
							<table class="woocommerce-table woocommerce-table--salary-history" id="salary-history-table">
								<thead>
									<tr>
										<th class="sortable" data-sort="date">
											<?php esc_html_e( 'Date', 'wc-team-payroll' ); ?>
											<i class="ph ph-caret-up-down sort-icon"></i>
										</th>
										<th class="sortable" data-sort="old_type">
											<?php esc_html_e( 'Previous', 'wc-team-payroll' ); ?>
											<i class="ph ph-caret-up-down sort-icon"></i>
										</th>
										<th class="sortable" data-sort="new_type">
											<?php esc_html_e( 'New', 'wc-team-payroll' ); ?>
											<i class="ph ph-caret-up-down sort-icon"></i>
										</th>
										<th class="sortable" data-sort="changed_by">
											<?php esc_html_e( 'Changed By', 'wc-team-payroll' ); ?>
											<i class="ph ph-caret-up-down sort-icon"></i>
										</th>
									</tr>
								</thead>
								<tbody id="salary-history-tbody">
									<?php foreach ( array_reverse( $salary_history ) as $index => $history ) : 
										$changed_by_id = isset( $history['changed_by'] ) ? $history['changed_by'] : 0;
										$changed_by_user = $changed_by_id ? get_user_by( 'id', $changed_by_id ) : null;
										
										// Get frequency abbreviations
										$frequency_abbr = array(
											'daily'   => 'dy',
											'weekly'  => 'wk',
											'monthly' => 'mn',
											'yearly'  => 'yr',
										);
									?>
										<tr data-index="<?php echo esc_attr( $index ); ?>">
											<td data-sort-value="<?php echo esc_attr( strtotime( $history['date'] ) ); ?>">
												<span class="date"><?php echo esc_html( date( 'M j, Y', strtotime( $history['date'] ) ) ); ?></span>
												<small><?php echo esc_html( date( 'g:i A', strtotime( $history['date'] ) ) ); ?></small>
											</td>
											<td data-sort-value="<?php echo esc_attr( $history['old_type'] ); ?>">
												<div class="salary-change">
													<span class="type"><?php echo esc_html( ucfirst( $history['old_type'] ) ); ?></span>
													<?php if ( $history['old_type'] === 'commission' ) : ?>
														<span class="amount"><?php esc_html_e( '%/order', 'wc-team-payroll' ); ?></span>
													<?php elseif ( isset( $history['old_amount'] ) && $history['old_amount'] > 0 ) : ?>
														<?php 
															$old_freq = isset( $history['old_frequency'] ) ? $history['old_frequency'] : 'monthly';
															$abbr = $frequency_abbr[ $old_freq ] ?? 'mn';
														?>
														<span class="amount"><?php echo wp_kses_post( wc_price( $history['old_amount'] ) ); ?>/<span style="font-size: 12px;"><?php echo esc_html( $abbr ); ?></span></span>
													<?php endif; ?>
												</div>
											</td>
											<td data-sort-value="<?php echo esc_attr( $history['new_type'] ); ?>">
												<div class="salary-change">
													<span class="type"><?php echo esc_html( ucfirst( $history['new_type'] ) ); ?></span>
													<?php if ( $history['new_type'] === 'commission' ) : ?>
														<span class="amount"><?php esc_html_e( '%/order', 'wc-team-payroll' ); ?></span>
													<?php elseif ( isset( $history['new_amount'] ) && $history['new_amount'] > 0 ) : ?>
														<?php 
															$new_freq = isset( $history['new_frequency'] ) ? $history['new_frequency'] : 'monthly';
															$abbr = $frequency_abbr[ $new_freq ] ?? 'mn';
														?>
														<span class="amount"><?php echo wp_kses_post( wc_price( $history['new_amount'] ) ); ?>/<span style="font-size: 12px;"><?php echo esc_html( $abbr ); ?></span></span>
													<?php endif; ?>
												</div>
											</td>
											<td data-sort-value="<?php echo esc_attr( $changed_by_user ? $changed_by_user->display_name : 'System' ); ?>">
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
					
					<!-- Pagination -->
					<div class="pagination-container" id="salary-history-pagination">
						<!-- Pagination will be inserted here by JavaScript -->
					</div>
				</div>
				
				<script>
					jQuery(document).ready(function($) {
						// Salary History Table Management
						let currentPage = 1;
						let perPage = 10;
						let currentSort = { column: 'date', direction: 'desc' };
						let searchTerm = '';
						let allRows = [];
						
						// Initialize table
						function initSalaryHistoryTable() {
							// Store all rows
							$('#salary-history-tbody tr').each(function() {
								allRows.push({
									element: $(this).clone(),
									data: {
										date: $(this).find('td').eq(0).data('sort-value'),
										old_type: $(this).find('td').eq(1).data('sort-value'),
										new_type: $(this).find('td').eq(2).data('sort-value'),
										changed_by: $(this).find('td').eq(3).data('sort-value'),
										text: $(this).text().toLowerCase()
									}
								});
							});
							
							updateTable();
						}
						
						// Update table display
						function updateTable() {
							let filteredRows = allRows.slice();
							
							// Apply search filter
							if (searchTerm) {
								filteredRows = filteredRows.filter(row => 
									row.data.text.includes(searchTerm.toLowerCase())
								);
							}
							
							// Apply sorting
							filteredRows.sort((a, b) => {
								let aVal = a.data[currentSort.column];
								let bVal = b.data[currentSort.column];
								
								if (typeof aVal === 'string') {
									aVal = aVal.toLowerCase();
									bVal = bVal.toLowerCase();
								}
								
								if (currentSort.direction === 'asc') {
									return aVal > bVal ? 1 : -1;
								} else {
									return aVal < bVal ? 1 : -1;
								}
							});
							
							// Calculate pagination
							const totalRows = filteredRows.length;
							const totalPages = Math.ceil(totalRows / perPage);
							const startIndex = (currentPage - 1) * perPage;
							const endIndex = startIndex + perPage;
							const pageRows = filteredRows.slice(startIndex, endIndex);
							
							// Update table body
							const tbody = $('#salary-history-tbody');
							tbody.empty();
							
							if (pageRows.length === 0) {
								tbody.append(`
									<tr>
										<td colspan="4" class="no-results">
											<div class="no-results-message">
												<i class="ph ph-magnifying-glass"></i>
												<p><?php esc_html_e( 'No salary history found matching your search.', 'wc-team-payroll' ); ?></p>
											</div>
										</td>
									</tr>
								`);
							} else {
								pageRows.forEach(row => {
									tbody.append(row.element);
								});
							}
							
							// Update pagination
							updatePagination(totalPages, totalRows, startIndex + 1, Math.min(endIndex, totalRows));
							
							// Update sort icons
							updateSortIcons();
						}
						
						// Update pagination
						function updatePagination(totalPages, totalRows, start, end) {
							const container = $('#salary-history-pagination');
							
							if (totalPages <= 1) {
								container.html('');
								return;
							}
							
							let paginationHTML = '<div class="pagination-wrapper">';
							paginationHTML += '<div class="pagination-info">';
							paginationHTML += `<?php esc_html_e( 'Showing', 'wc-team-payroll' ); ?> ${start} <?php esc_html_e( 'to', 'wc-team-payroll' ); ?> ${end} <?php esc_html_e( 'of', 'wc-team-payroll' ); ?> ${totalRows} <?php esc_html_e( 'entries', 'wc-team-payroll' ); ?>`;
							paginationHTML += '</div>';
							paginationHTML += '<div class="pagination-controls">';
							
							// Previous button
							if (currentPage > 1) {
								paginationHTML += `<a href="#" class="page-btn prev-btn" data-page="${currentPage - 1}"><i class="ph ph-caret-left"></i></a>`;
							}
							
							// Page numbers
							for (let i = 1; i <= totalPages; i++) {
								if (i === currentPage) {
									paginationHTML += `<a href="#" class="page-btn current-page">${i}</a>`;
								} else if (i === 1 || i === totalPages || (i >= currentPage - 2 && i <= currentPage + 2)) {
									paginationHTML += `<a href="#" class="page-btn" data-page="${i}">${i}</a>`;
								} else if (i === currentPage - 3 || i === currentPage + 3) {
									paginationHTML += '<span class="page-ellipsis">...</span>';
								}
							}
							
							// Next button
							if (currentPage < totalPages) {
								paginationHTML += `<a href="#" class="page-btn next-btn" data-page="${currentPage + 1}"><i class="ph ph-caret-right"></i></a>`;
							}
							
							paginationHTML += '</div></div>';
							container.html(paginationHTML);
						}
						
						// Update sort icons
						function updateSortIcons() {
							$('.sortable .sort-icon').removeClass('ph-caret-up ph-caret-down').addClass('ph-caret-up-down');
							$(`.sortable[data-sort="${currentSort.column}"] .sort-icon`)
								.removeClass('ph-caret-up-down')
								.addClass(currentSort.direction === 'asc' ? 'ph-caret-up' : 'ph-caret-down');
						}
						
						// Event handlers
						$('.sortable').on('click', function() {
							const column = $(this).data('sort');
							if (currentSort.column === column) {
								currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
							} else {
								currentSort.column = column;
								currentSort.direction = 'desc';
							}
							currentPage = 1;
							updateTable();
						});
						
						$('#salary-history-search').on('input', function() {
							searchTerm = $(this).val();
							currentPage = 1;
							
							// Toggle search icon
							var $icon = $(this).siblings('i');
							if (searchTerm.length > 0) {
								$icon.removeClass('ph-magnifying-glass').addClass('ph-x');
							} else {
								$icon.removeClass('ph-x').addClass('ph-magnifying-glass');
							}
							
							updateTable();
						});
						
						// Clear search on icon click
						$(document).on('click', '.search-control i.ph-x', function() {
							$('#salary-history-search').val('').trigger('input');
						});
						
						$('#salary-history-per-page').on('change', function() {
							perPage = parseInt($(this).val());
							currentPage = 1;
							updateTable();
						});
						
						$(document).on('click', '.page-btn[data-page]', function(e) {
							e.preventDefault();
							currentPage = parseInt($(this).data('page'));
							updateTable();
							
							// Scroll to table header smoothly
							$('html, body').animate({
								scrollTop: $('#salary-history-table').offset().top - 100
							}, 300);
						});
						
						// Initialize
						initSalaryHistoryTable();
					});
				</script>
			<?php else : ?>
				<div class="no-history-message">
					<i class="ph ph-info"></i>
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
		
		?>
		<div class="wc-team-payroll-earnings">
			<!-- Employee Header -->
			<?php echo self::get_employee_header( $user_id ); ?>

			<!-- Earnings Summary Cards -->
			<div class="earnings-summary" id="earnings-summary">
				<div class="earning-card current-month">
					<div class="card-header">
						<i class="ph ph-calendar"></i>
						<h4><?php esc_html_e( 'This Month', 'wc-team-payroll' ); ?></h4>
					</div>
					<p class="amount" id="current-month-earnings">$0.00</p>
					<p class="label"><?php echo esc_html( date( 'F Y' ) ); ?></p>
				</div>
				
				<div class="earning-card total-earnings">
					<div class="card-header">
						<i class="ph ph-chart-line-up"></i>
						<h4><?php esc_html_e( 'Total Earnings', 'wc-team-payroll' ); ?></h4>
					</div>
					<p class="amount" id="total-earnings">$0.00</p>
					<p class="label"><?php esc_html_e( 'All Time', 'wc-team-payroll' ); ?></p>
				</div>
				
				<div class="earning-card total-paid">
					<div class="card-header">
						<i class="ph ph-check-circle"></i>
						<h4><?php esc_html_e( 'Total Paid', 'wc-team-payroll' ); ?></h4>
					</div>
					<p class="amount paid" id="total-paid">$0.00</p>
					<p class="label"><?php esc_html_e( 'Received', 'wc-team-payroll' ); ?></p>
				</div>
				
				<div class="earning-card total-due">
					<div class="card-header">
						<i class="ph ph-clock"></i>
						<h4><?php esc_html_e( 'Amount Due', 'wc-team-payroll' ); ?></h4>
					</div>
					<p class="amount due" id="total-due">$0.00</p>
					<p class="label"><?php esc_html_e( 'Pending', 'wc-team-payroll' ); ?></p>
				</div>
			</div>

			<!-- Monthly History Section -->
			<div class="earnings-history-section">
				<div class="section-header">
					<h3><?php esc_html_e( 'Monthly Earnings History', 'wc-team-payroll' ); ?></h3>
					<div class="table-controls">
						<div class="search-control">
							<input type="text" id="earnings-search" placeholder="<?php esc_attr_e( 'Search history...', 'wc-team-payroll' ); ?>" />
							<i class="ph ph-magnifying-glass"></i>
						</div>
						<div class="per-page-control">
							<label for="earnings-per-page"><?php esc_html_e( 'Show:', 'wc-team-payroll' ); ?></label>
							<select id="earnings-per-page">
								<option value="5">5</option>
								<option value="10" selected>10</option>
								<option value="25">25</option>
								<option value="50">50</option>
							</select>
							<span><?php esc_html_e( 'per page', 'wc-team-payroll' ); ?></span>
						</div>
					</div>
				</div>

				<div class="table-wrapper">
					<div class="table-container">
						<table class="woocommerce-table woocommerce-table--earnings" id="earnings-table">
							<thead>
								<tr>
									<th class="sortable" data-sort="month">
										<?php esc_html_e( 'Month', 'wc-team-payroll' ); ?>
										<i class="ph ph-caret-up-down sort-icon"></i>
									</th>
									<th class="sortable" data-sort="orders">
										<?php esc_html_e( 'Orders', 'wc-team-payroll' ); ?>
										<i class="ph ph-caret-up-down sort-icon"></i>
									</th>
									<th class="sortable" data-sort="earned">
										<?php esc_html_e( 'Total Earned', 'wc-team-payroll' ); ?>
										<i class="ph ph-caret-up-down sort-icon"></i>
									</th>
									<th class="sortable" data-sort="paid">
										<?php esc_html_e( 'Paid', 'wc-team-payroll' ); ?>
										<i class="ph ph-caret-up-down sort-icon"></i>
									</th>
									<th class="sortable" data-sort="due">
										<?php esc_html_e( 'Due', 'wc-team-payroll' ); ?>
										<i class="ph ph-caret-up-down sort-icon"></i>
									</th>
									<th class="sortable" data-sort="status">
										<?php esc_html_e( 'Status', 'wc-team-payroll' ); ?>
										<i class="ph ph-caret-up-down sort-icon"></i>
									</th>
								</tr>
							</thead>
							<tbody id="earnings-tbody">
								<tr>
									<td colspan="6" style="text-align: center; padding: 40px 20px;">
										<i class="ph ph-spinner" style="font-size: 32px; animation: spin 1s linear infinite;"></i>
										<p><?php esc_html_e( 'Loading earnings data...', 'wc-team-payroll' ); ?></p>
									</td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>

				<!-- Pagination -->
				<div class="pagination-container" id="earnings-pagination">
					<!-- Pagination will be inserted here by JavaScript -->
				</div>
			</div>

			<!-- No Earnings Message (hidden by default) -->
			<div class="no-earnings-message" id="no-earnings-message" style="display: none;">
				<i class="ph ph-chart-line-up"></i>
				<h4><?php esc_html_e( 'No Earnings Yet', 'wc-team-payroll' ); ?></h4>
				<p><?php esc_html_e( 'Start processing orders to see your earnings here.', 'wc-team-payroll' ); ?></p>
			</div>
		</div>

		<style>
			@keyframes spin {
				from { transform: rotate(0deg); }
				to { transform: rotate(360deg); }
			}
		</style>

		<script>
			jQuery(document).ready(function($) {
				let currentPage = 1;
				let perPage = 10;
				let currentSort = { column: 'month', direction: 'desc' };
				let searchTerm = '';
				let allRows = [];

				// Load earnings data on page load
				loadEarningsData();

				function loadEarningsData() {
					$.ajax({
						url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
						type: 'POST',
						data: {
							action: 'wc_tp_get_earnings_data',
							nonce: '<?php echo esc_attr( wp_create_nonce( 'wc_team_payroll_nonce' ) ); ?>'
						},
						success: function(response) {
							if (response.success) {
								const data = response.data;
								
								// Update summary cards
								$('#current-month-earnings').html(data.current_month_earnings);
								$('#total-earnings').html(data.total_earnings);
								$('#total-paid').html(data.total_paid);
								$('#total-due').html(data.total_due);

								// Populate table rows
								allRows = [];
								if (data.monthly_history && data.monthly_history.length > 0) {
									data.monthly_history.forEach(function(month) {
										allRows.push({
											element: createTableRow(month),
											data: {
												month: month.date,
												orders: month.orders_count,
												earned: month.total,
												paid: month.paid,
												due: month.due,
												status: month.status,
												text: (month.date + ' ' + month.status).toLowerCase()
											}
										});
									});
									$('#no-earnings-message').hide();
									$('#earnings-history-section').show();
									updateTable();
								} else {
									$('#no-earnings-message').show();
									$('#earnings-history-section').hide();
								}
							}
						},
						error: function() {
							$('#earnings-tbody').html('<tr><td colspan="6" style="text-align: center; padding: 20px;"><p style="color: #dc3545;"><?php esc_html_e( 'Error loading earnings data', 'wc-team-payroll' ); ?></p></td></tr>');
						}
					});
				}

				function createTableRow(month) {
					const dueAmount = month.total - month.paid;
					const status = month.status; // Use status from server
					let statusLabel = '<?php esc_html_e( 'Pending', 'wc-team-payroll' ); ?>';
					let statusIcon = 'ph-clock';
					
					if (status === 'paid') {
						statusLabel = '<?php esc_html_e( 'Paid', 'wc-team-payroll' ); ?>';
						statusIcon = 'ph-check-circle';
					} else if (status === 'partially_paid') {
						statusLabel = '<?php esc_html_e( 'Partially Paid', 'wc-team-payroll' ); ?>';
						statusIcon = 'ph-warning';
					}

					const row = $('<tr></tr>');
					row.attr('data-sort-month', month.date);
					row.attr('data-sort-orders', month.orders_count);
					row.attr('data-sort-earned', month.total);
					row.attr('data-sort-paid', month.paid);
					row.attr('data-sort-due', dueAmount);
					row.attr('data-sort-status', status);

					// Create cells with proper HTML handling
					const monthCell = $('<td></td>').attr('data-sort-value', month.date)
						.append($('<span class="month-name"></span>').text(month.date));
					
					const ordersCell = $('<td></td>').attr('data-sort-value', month.orders_count)
						.append($('<span class="orders-count"></span>').text(month.orders_count));
					
					const earnedCell = $('<td></td>').attr('data-sort-value', month.total)
						.append($('<span class="amount-earned"></span>').html(month.total_formatted));
					
					const paidCell = $('<td></td>').attr('data-sort-value', month.paid)
						.append($('<span class="amount-paid"></span>').html(month.paid_formatted));
					
					const dueCell = $('<td></td>').attr('data-sort-value', dueAmount)
						.append($('<span class="amount-due"></span>').html(month.due_formatted));
					
					const statusCell = $('<td></td>').attr('data-sort-value', status)
						.append($('<span class="status-badge status-' + status + '"></span>')
							.append($('<i class="ph ' + statusIcon + '"></i>'))
							.append(' ' + statusLabel));

					row.append(monthCell, ordersCell, earnedCell, paidCell, dueCell, statusCell);
					return row;
				}

				function updateTable() {
					let filteredRows = allRows.slice();

					// Apply search filter
					if (searchTerm) {
						filteredRows = filteredRows.filter(row => 
							row.data.text.includes(searchTerm.toLowerCase())
						);
					}

					// Apply sorting
					filteredRows.sort((a, b) => {
						let aVal = a.data[currentSort.column];
						let bVal = b.data[currentSort.column];

						if (typeof aVal === 'string') {
							aVal = aVal.toLowerCase();
							bVal = bVal.toLowerCase();
						}

						if (currentSort.direction === 'asc') {
							return aVal > bVal ? 1 : -1;
						} else {
							return aVal < bVal ? 1 : -1;
						}
					});

					// Calculate pagination
					const totalRows = filteredRows.length;
					const totalPages = Math.ceil(totalRows / perPage);
					const startIndex = (currentPage - 1) * perPage;
					const endIndex = startIndex + perPage;
					const pageRows = filteredRows.slice(startIndex, endIndex);

					// Update table body
					const tbody = $('#earnings-tbody');
					tbody.empty();

					if (pageRows.length === 0) {
						tbody.append(`
							<tr>
								<td colspan="6" class="no-results">
									<div class="no-results-message">
										<i class="ph ph-magnifying-glass"></i>
										<p><?php esc_html_e( 'No earnings history found matching your search.', 'wc-team-payroll' ); ?></p>
									</div>
								</td>
							</tr>
						`);
					} else {
						pageRows.forEach(row => {
							tbody.append(row.element);
						});
					}

					// Update pagination
					updatePagination(totalPages, totalRows, startIndex + 1, Math.min(endIndex, totalRows));

					// Update sort icons
					updateSortIcons();
				}

				function updatePagination(totalPages, totalRows, start, end) {
					const container = $('#earnings-pagination');

					if (totalPages <= 1) {
						container.html('');
						return;
					}

					let paginationHTML = '<div class="pagination-wrapper">';
					paginationHTML += '<div class="pagination-info">';
					paginationHTML += `<?php esc_html_e( 'Showing', 'wc-team-payroll' ); ?> ${start} <?php esc_html_e( 'to', 'wc-team-payroll' ); ?> ${end} <?php esc_html_e( 'of', 'wc-team-payroll' ); ?> ${totalRows} <?php esc_html_e( 'entries', 'wc-team-payroll' ); ?>`;
					paginationHTML += '</div>';
					paginationHTML += '<div class="pagination-controls">';

					// Previous button
					if (currentPage > 1) {
						paginationHTML += `<a href="#" class="page-btn prev-btn" data-page="${currentPage - 1}"><i class="ph ph-caret-left"></i></a>`;
					}

					// Page numbers
					for (let i = 1; i <= totalPages; i++) {
						if (i === currentPage) {
							paginationHTML += `<a href="#" class="page-btn current-page">${i}</a>`;
						} else if (i === 1 || i === totalPages || (i >= currentPage - 2 && i <= currentPage + 2)) {
							paginationHTML += `<a href="#" class="page-btn" data-page="${i}">${i}</a>`;
						} else if (i === currentPage - 3 || i === currentPage + 3) {
							paginationHTML += '<span class="page-ellipsis">...</span>';
						}
					}

					// Next button
					if (currentPage < totalPages) {
						paginationHTML += `<a href="#" class="page-btn next-btn" data-page="${currentPage + 1}"><i class="ph ph-caret-right"></i></a>`;
					}

					paginationHTML += '</div></div>';
					container.html(paginationHTML);
				}

				function updateSortIcons() {
					$('.sortable .sort-icon').removeClass('ph-caret-up ph-caret-down').addClass('ph-caret-up-down');
					$(`.sortable[data-sort="${currentSort.column}"] .sort-icon`)
						.removeClass('ph-caret-up-down')
						.addClass(currentSort.direction === 'asc' ? 'ph-caret-up' : 'ph-caret-down');
				}

				// Event handlers
				$('.sortable').on('click', function() {
					const column = $(this).data('sort');
					if (currentSort.column === column) {
						currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
					} else {
						currentSort.column = column;
						currentSort.direction = 'desc';
					}
					currentPage = 1;
					updateTable();
				});

				$('#earnings-search').on('input', function() {
					searchTerm = $(this).val();
					currentPage = 1;

					// Toggle search icon
					var $icon = $(this).siblings('i');
					if (searchTerm.length > 0) {
						$icon.removeClass('ph-magnifying-glass').addClass('ph-x');
					} else {
						$icon.removeClass('ph-x').addClass('ph-magnifying-glass');
					}

					updateTable();
				});

				// Clear search on icon click
				$(document).on('click', '.search-control i.ph-x', function() {
					$('#earnings-search').val('').trigger('input');
				});

				$('#earnings-per-page').on('change', function() {
					perPage = parseInt($(this).val());
					currentPage = 1;
					updateTable();
				});

				$(document).on('click', '.page-btn[data-page]', function(e) {
					e.preventDefault();
					currentPage = parseInt($(this).data('page'));
					updateTable();

					// Scroll to table header smoothly
					$('html, body').animate({
						scrollTop: $('#earnings-table').offset().top - 100
					}, 300);
				});
			});
		</script>
		<?php
	}

	/**
	 * Orders Commission content
	 */
	public static function orders_commission_content() {
		$user_id = get_current_user_id();
		
		?>
		<div class="wc-team-payroll-orders">
			<!-- Employee Header -->
			<?php echo self::get_employee_header( $user_id ); ?>

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
		?>
		<div class="wc-team-payroll-reports">
			<!-- Employee Header -->
			<?php echo self::get_employee_header( $user_id ); ?>
			
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
			// Get styling settings
			$styling_settings = get_option( 'wc_team_payroll_styling', array() );
			
			// Set default values
			$primary_color = isset( $styling_settings['primary_color'] ) ? $styling_settings['primary_color'] : '#0073aa';
			$secondary_color = isset( $styling_settings['secondary_color'] ) ? $styling_settings['secondary_color'] : '#28a745';
			$heading_color = isset( $styling_settings['heading_color'] ) ? $styling_settings['heading_color'] : '#333333';
			$text_color = isset( $styling_settings['text_color'] ) ? $styling_settings['text_color'] : '#495057';
			$link_color = isset( $styling_settings['link_color'] ) ? $styling_settings['link_color'] : '#0073aa';
			$link_hover_color = isset( $styling_settings['link_hover_color'] ) ? $styling_settings['link_hover_color'] : '#005a87';
			$background_color = isset( $styling_settings['background_color'] ) ? $styling_settings['background_color'] : '#ffffff';
			$header_background = isset( $styling_settings['header_background'] ) ? $styling_settings['header_background'] : '#f8f9fa';
			$header_border_color = isset( $styling_settings['header_border_color'] ) ? $styling_settings['header_border_color'] : '#0073aa';
			$card_background = isset( $styling_settings['card_background'] ) ? $styling_settings['card_background'] : '#f8f9fa';
			$border_color = isset( $styling_settings['border_color'] ) ? $styling_settings['border_color'] : '#e9ecef';
			$table_header_background = isset( $styling_settings['table_header_background'] ) ? $styling_settings['table_header_background'] : '#f8f9fa';
			$table_row_hover = isset( $styling_settings['table_row_hover'] ) ? $styling_settings['table_row_hover'] : '#f5f5f5';
			$table_border_color = isset( $styling_settings['table_border_color'] ) ? $styling_settings['table_border_color'] : '#dee2e6';
			$font_family = isset( $styling_settings['font_family'] ) && $styling_settings['font_family'] !== 'inherit' ? $styling_settings['font_family'] : 'inherit';
			$base_font_size = isset( $styling_settings['base_font_size'] ) ? $styling_settings['base_font_size'] : 14;
			$heading_font_size = isset( $styling_settings['heading_font_size'] ) ? $styling_settings['heading_font_size'] : 24;
			$button_background = isset( $styling_settings['button_background'] ) ? $styling_settings['button_background'] : '#0073aa';
			$button_text_color = isset( $styling_settings['button_text_color'] ) ? $styling_settings['button_text_color'] : '#ffffff';
			$button_hover_background = isset( $styling_settings['button_hover_background'] ) ? $styling_settings['button_hover_background'] : '#005a87';
			$button_border_radius = isset( $styling_settings['button_border_radius'] ) ? $styling_settings['button_border_radius'] : 4;
			$card_border_radius = isset( $styling_settings['card_border_radius'] ) ? $styling_settings['card_border_radius'] : 8;
			$card_shadow = isset( $styling_settings['card_shadow'] ) ? $styling_settings['card_shadow'] : 'medium';
			$remove_debug_border = isset( $styling_settings['remove_debug_border'] ) ? $styling_settings['remove_debug_border'] : 0;
			
			// Generate shadow CSS
			$shadow_css = '';
			switch ( $card_shadow ) {
				case 'none':
					$shadow_css = 'none';
					break;
				case 'light':
					$shadow_css = '0 1px 3px rgba(0,0,0,0.1)';
					break;
				case 'medium':
					$shadow_css = '0 2px 8px rgba(0,0,0,0.1)';
					break;
				case 'heavy':
					$shadow_css = '0 4px 16px rgba(0,0,0,0.15)';
					break;
				default:
					$shadow_css = '0 2px 8px rgba(0,0,0,0.1)';
			}
			
			// Enqueue Phosphor Icons
			wp_enqueue_script(
				'phosphor-icons',
				'https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.2',
				array(),
				'2.1.2',
				true
			);
			
			// Enqueue Font Awesome (keeping as fallback)
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

			// Generate dynamic CSS based on settings
			$dynamic_css = "
				/* Dynamic Styling from Settings */
				.woocommerce-account .woocommerce-MyAccount-content .wc-team-payroll-salary-details,
				.woocommerce-account .woocommerce-MyAccount-content .wc-team-payroll-earnings,
				.woocommerce-account .woocommerce-MyAccount-content .wc-team-payroll-orders,
				.woocommerce-account .woocommerce-MyAccount-content .wc-team-payroll-reports {
					padding: 20px 0 !important;
					font-family: {$font_family} !important;
					background: {$background_color} !important;
					color: {$text_color} !important;
					font-size: {$base_font_size}px !important;
					border: none !important;
					margin: 0 !important;
				}
				
				/* Employee Header */
				.wc-tp-employee-header-new {
					background: {$header_background} !important;
					border-radius: {$card_border_radius}px !important;
					color: {$text_color} !important;
					font-family: {$font_family} !important;
					box-shadow: {$shadow_css} !important;
				}
				
				.wc-tp-employee-header-new .profile-picture-container {
					border-color: {$header_border_color} !important;
					background: {$background_color} !important;
				}
				
				.wc-tp-employee-header-new .profile-picture-placeholder {
					background: linear-gradient(135deg, {$header_border_color} 0%, {$link_hover_color} 100%) !important;
				}
				
				.wc-tp-employee-header-new {
					background: {$background_color} !important;
					border-color: {$border_color} !important;
				}
				
				.wc-tp-employee-header-new .profile-picture-container {
					border-color: {$primary_color} !important;
					background: {$background_color} !important;
				}
				
				.wc-tp-employee-header-new .profile-picture-placeholder {
					background: linear-gradient(135deg, {$primary_color} 0%, {$link_hover_color} 100%) !important;
				}
				
				.wc-tp-employee-header-new .profile-name {
					color: {$heading_color} !important;
					font-family: {$font_family} !important;
				}
				
				.wc-tp-employee-header-new .profile-role {
					background: rgba(" . implode(',', sscanf($primary_color, "#%02x%02x%02x")) . ", 0.1) !important;
					color: {$primary_color} !important;
					font-family: {$font_family} !important;
				}
				
				.wc-tp-employee-header-new .info-item {
					color: {$text_color} !important;
					font-family: {$font_family} !important;
				}
				
				.wc-tp-employee-header-new .info-item i {
					color: {$primary_color} !important;
				}
				
				.wc-tp-employee-header-new .info-value:hover {
					color: {$primary_color} !important;
				}
				
				.wc-tp-employee-header-new .social-icon {
					color: {$primary_color} !important;
				}
				
				.wc-tp-employee-header-new .social-icon:hover {
					color: {$secondary_color} !important;
				}
				
				.wc-tp-employee-header-new .profile-bio {
					color: {$text_color} !important;
					font-family: {$font_family} !important;
					border-top-color: {$header_border_color} !important;
				}
				
				.wc-tp-employee-header-new .header-row-2 {
					border-top-color: {$border_color} !important;
					border-bottom-color: {$border_color} !important;
				}
				
				/* Headings */
				.wc-team-payroll-salary-details h2,
				.wc-team-payroll-earnings h2,
				.wc-team-payroll-orders h2,
				.wc-team-payroll-reports h2,
				.wc-team-payroll-salary-details h3,
				.wc-team-payroll-earnings h3,
				.wc-team-payroll-orders h3,
				.wc-team-payroll-reports h3 {
					color: {$heading_color} !important;
					font-family: {$font_family} !important;
				}
				
				.wc-team-payroll-salary-details h2,
				.wc-team-payroll-earnings h2,
				.wc-team-payroll-orders h2,
				.wc-team-payroll-reports h2 {
					font-size: {$heading_font_size}px !important;
				}
				
				/* Links */
				.wc-team-payroll-salary-details a,
				.wc-team-payroll-earnings a,
				.wc-team-payroll-orders a,
				.wc-team-payroll-reports a {
					color: {$link_color} !important;
				}
				
				.wc-team-payroll-salary-details a:hover,
				.wc-team-payroll-earnings a:hover,
				.wc-team-payroll-orders a:hover,
				.wc-team-payroll-reports a:hover {
					color: {$link_hover_color} !important;
				}
				
				/* Menu icons styling */
				.woocommerce-MyAccount-navigation ul li a i {
					margin-right: 8px !important;
					display: inline-block !important;
					width: 20px !important;
					text-align: center !important;
					font-size: 20px !important;
					color: {$primary_color} !important;
				}
				
				/* Phosphor icons in content */
				.wc-team-payroll-salary-details .ph,
				.wc-team-payroll-earnings .ph,
				.wc-team-payroll-orders .ph,
				.wc-team-payroll-reports .ph {
					color: {$primary_color} !important;
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
					background: {$card_background} !important;
					border: 1px solid {$border_color} !important;
					border-radius: {$card_border_radius}px !important;
					padding: 20px !important;
					box-shadow: {$shadow_css} !important;
					color: {$text_color} !important;
					font-family: {$font_family} !important;
				}
				
				/* Salary info card */
				.wc-team-payroll-salary-details .salary-info-card {
					background: {$background_color} !important;
					border: 1px solid {$border_color} !important;
					border-radius: {$card_border_radius}px !important;
					padding: 30px !important;
					box-shadow: {$shadow_css} !important;
					margin-bottom: 20px !important;
					color: {$text_color} !important;
					font-family: {$font_family} !important;
				}
				
				/* Tables */
				.wc-team-payroll-salary-details .table-container,
				.wc-team-payroll-earnings .table-container {
					overflow-x: auto !important;
					border: none !important;
					border-radius: 0 !important;
					background: transparent !important;
				}
				
				.wc-team-payroll-salary-details .woocommerce-table,
				.wc-team-payroll-earnings .woocommerce-table {
					color: {$text_color} !important;
					font-family: {$font_family} !important;
				}
				
				.wc-team-payroll-salary-details .woocommerce-table th,
				.wc-team-payroll-earnings .woocommerce-table th {
					background: {$table_header_background} !important;
					color: {$heading_color} !important;
					border: none !important;
					border-bottom: 1px solid {$table_border_color} !important;
					padding: 14px 5px !important;
					font-family: {$font_family} !important;
				}
				
				.wc-team-payroll-salary-details .woocommerce-table td,
				.wc-team-payroll-earnings .woocommerce-table td {
					border: none !important;
					border-bottom: 1px solid {$table_border_color} !important;
					padding: 12px 5px !important;
					background: transparent !important;
					font-family: {$font_family} !important;
				}
				
				.wc-team-payroll-salary-details .woocommerce-table tbody tr:hover,
				.wc-team-payroll-earnings .woocommerce-table tbody tr:hover {
					background: {$table_row_hover} !important;
				}
				
				/* Search and Controls */
				.search-control input {
					border: 1px solid {$border_color} !important;
					color: {$text_color} !important;
					font-family: {$font_family} !important;
				}
				
				.search-control input:focus {
					border-color: {$primary_color} !important;
					outline: none !important;
					box-shadow: 0 0 0 2px rgba(" . implode(',', sscanf($primary_color, "#%02x%02x%02x")) . ", 0.2) !important;
				}
				
				.per-page-control select {
					border: 1px solid {$border_color} !important;
					color: {$text_color} !important;
					font-family: {$font_family} !important;
				}
				
				/* Pagination */
				.page-btn {
					border: 1px solid {$button_background} !important;
					background: transparent !important;
					color: {$button_background} !important;
					font-family: {$font_family} !important;
					border-radius: {$button_border_radius}px !important;
				}
				
				.page-btn i {
					color: {$button_background} !important;
				}
				
				.page-btn:hover {
					background: rgba(" . implode(',', sscanf($button_background, "#%02x%02x%02x")) . ", 0.1) !important;
					border-color: {$button_background} !important;
				}
				
				.page-btn.current-page {
					background: {$button_background} !important;
					color: {$button_text_color} !important;
					border-color: {$button_background} !important;
				}
				
				.page-btn.current-page i {
					color: {$button_text_color} !important;
				}
				
				/* Amount styling */
				.wc-team-payroll-salary-details .amount,
				.wc-team-payroll-earnings .amount,
				.wc-team-payroll-salary-details .amount-earned,
				.wc-team-payroll-earnings .amount-earned {
					color: {$secondary_color} !important;
					font-weight: 600 !important;
				}
				
				/* Buttons */
				.wc-team-payroll-salary-details button,
				.wc-team-payroll-earnings button,
				.wc-team-payroll-orders button,
				.wc-team-payroll-reports button,
				.wc-team-payroll-salary-details .btn,
				.wc-team-payroll-earnings .btn,
				.wc-team-payroll-orders .btn,
				.wc-team-payroll-reports .btn {
					background: {$button_background} !important;
					color: {$button_text_color} !important;
					border: none !important;
					border-radius: {$button_border_radius}px !important;
					font-family: {$font_family} !important;
					cursor: pointer !important;
					transition: background-color 0.2s ease !important;
				}
				
				.wc-team-payroll-salary-details button:hover,
				.wc-team-payroll-earnings button:hover,
				.wc-team-payroll-orders button:hover,
				.wc-team-payroll-reports button:hover,
				.wc-team-payroll-salary-details .btn:hover,
				.wc-team-payroll-earnings .btn:hover,
				.wc-team-payroll-orders .btn:hover,
				.wc-team-payroll-reports .btn:hover {
					background: {$button_hover_background} !important;
				}
				
				/* Status badges */
				.wc-team-payroll-salary-details .status-badge,
				.wc-team-payroll-earnings .status-badge {
					border-radius: {$button_border_radius}px !important;
					font-family: {$font_family} !important;
				}
				
				/* Payment method cards */
				.wc-team-payroll-salary-details .payment-method-card {
					background: {$background_color} !important;
					border: 1px solid {$border_color} !important;
					border-radius: {$card_border_radius}px !important;
					box-shadow: {$shadow_css} !important;
					color: {$text_color} !important;
					font-family: {$font_family} !important;
				}
				
				/* Salary type badges */
				.wc-team-payroll-salary-details .salary-type-badge {
					border-radius: {$button_border_radius}px !important;
					font-family: {$font_family} !important;
				}
				
				/* Commission note */
				.wc-team-payroll-salary-details .commission-note {
					background: {$card_background} !important;
					border: 1px solid {$border_color} !important;
					border-radius: {$card_border_radius}px !important;
					color: {$primary_color} !important;
					font-family: {$font_family} !important;
				}
				
				/* Salary type note */
				.wc-team-payroll-salary-details .salary-type-note {
					background: {$card_background} !important;
					border: 1px solid {$border_color} !important;
					border-radius: {$card_border_radius}px !important;
					color: {$primary_color} !important;
					font-family: {$font_family} !important;
				}
				
				/* Section Headings - Salary Info and Salary History */
				.salary-info-section h3,
				.salary-history-section h3 {
					border-bottom-color: {$border_color} !important;
				}
				
				.salary-info-section h3::after,
				.salary-history-section h3::after {
					background: {$primary_color} !important;
				}
				
				/* Table Wrapper Card */
				.wc-team-payroll-salary-details .table-wrapper {
					background: {$background_color} !important;
					border: 1px solid {$border_color} !important;
					border-radius: 0 !important;
					box-shadow: {$shadow_css} !important;
					padding: 20px !important;
					font-family: {$font_family} !important;
				}
			";

			// Add the dynamic CSS
			wp_add_inline_style( 'wc-team-payroll-myaccount', $dynamic_css );

			// Enqueue jQuery for AJAX and icon injection
			wp_enqueue_script( 'jquery' );
			
			// Add inline script to inject Phosphor icons
			wp_add_inline_script( 'phosphor-icons', '
				jQuery(document).ready(function($) {
					// Function to add Phosphor icons to menu items
					function addMyAccountIcons() {
						// Remove any existing icons first
						$(".woocommerce-MyAccount-navigation a i").remove();
						
						// Add Phosphor icons to menu items
						$(".woocommerce-MyAccount-navigation a[href*=\'salary-details\']").each(function() {
							if (!$(this).find("i").length) {
								$(this).prepend("<i class=\'ph ph-briefcase\' style=\'margin-right: 8px; font-size: 20px; width: 20px; text-align: center; display: inline-block; color: ' . esc_js( $primary_color ) . ';\'></i>");
							}
						});
						
						$(".woocommerce-MyAccount-navigation a[href*=\'my-earnings\']").each(function() {
							if (!$(this).find("i").length) {
								$(this).prepend("<i class=\'ph ph-wallet\' style=\'margin-right: 8px; font-size: 20px; width: 20px; text-align: center; display: inline-block; color: ' . esc_js( $primary_color ) . ';\'></i>");
							}
						});
						
						$(".woocommerce-MyAccount-navigation a[href*=\'orders-commission\']").each(function() {
							if (!$(this).find("i").length) {
								$(this).prepend("<i class=\'ph ph-shopping-bag\' style=\'margin-right: 8px; font-size: 20px; width: 20px; text-align: center; display: inline-block; color: ' . esc_js( $primary_color ) . ';\'></i>");
							}
						});
						
						$(".woocommerce-MyAccount-navigation a[href*=\'reports\']").each(function() {
							if (!$(this).find("i").length) {
								$(this).prepend("<i class=\'ph ph-chart-bar\' style=\'margin-right: 8px; font-size: 20px; width: 20px; text-align: center; display: inline-block; color: ' . esc_js( $primary_color ) . ';\'></i>");
							}
						});
					}
					
					// Add icons on page load
					addMyAccountIcons();
					
					// Re-add icons after any AJAX updates (in case menu is refreshed)
					$(document).ajaxComplete(function() {
						setTimeout(addMyAccountIcons, 100);
					});
					
					// Copy to clipboard functionality for header info
					$(document).on("click", ".wc-tp-employee-header-new [data-copy], .wc-tp-employee-header-new .info-item i[data-copy]", function(e) {
						e.preventDefault();
						var textToCopy = $(this).data("copy");
						
						if (!textToCopy || textToCopy === "---") {
							return;
						}
						
						// Copy to clipboard
						var tempInput = $("<input>");
						$("body").append(tempInput);
						tempInput.val(textToCopy).select();
						document.execCommand("copy");
						tempInput.remove();
						
						// Show notification
						var notification = $("<div class=\"copy-notification\">Copied!</div>");
						$("body").append(notification);
						
						// Auto-hide after 3 seconds
						setTimeout(function() {
							notification.fadeOut(300, function() {
								$(this).remove();
							});
						}, 3000);
					});
				});
			' );
		}
	}

	/**
	 * Generate employee header HTML
	 */
	private static function get_employee_header( $user_id ) {
		$user = get_user_by( 'ID', $user_id );
		if ( ! $user ) {
			return '';
		}

		$vb_user_id = get_user_meta( $user_id, 'vb_user_id', true ) ?: '---';
		$profile_picture_id = get_user_meta( $user_id, '_wc_tp_profile_picture', true );
		$profile_picture_url = $profile_picture_id ? wp_get_attachment_url( $profile_picture_id ) : '';
		$phone = get_user_meta( $user_id, 'billing_phone', true ) ?: '---';
		$email = $user->user_email ?: '---';
		$bio = get_user_meta( $user_id, 'description', true ) ?: '---';
		$employee_status = get_user_meta( $user_id, '_wc_tp_employee_status', true ) ?: 'active';
		
		// Get salary information - check for fixed/combined flags first
		$is_fixed_salary = get_user_meta( $user_id, '_wc_tp_fixed_salary', true );
		$is_combined_salary = get_user_meta( $user_id, '_wc_tp_combined_salary', true );
		
		if ( $is_fixed_salary ) {
			$salary_type = 'fixed';
		} elseif ( $is_combined_salary ) {
			$salary_type = 'combined';
		} else {
			$salary_type = get_user_meta( $user_id, '_wc_tp_salary_type', true ) ?: 'commission';
		}
		
		// Get user role with proper labeling
		$user_roles = $user->roles;
		$role_label = 'Employee';
		if ( in_array( 'administrator', $user_roles ) ) {
			$role_label = 'Administrator';
		} elseif ( in_array( 'shop_manager', $user_roles ) ) {
			$role_label = 'Manager';
		} elseif ( in_array( 'shop_employee', $user_roles ) ) {
			$role_label = 'Employee';
		}
		
		// Generate initials for placeholder
		$name_parts = explode( ' ', $user->display_name );
		$initials = '';
		foreach ( $name_parts as $part ) {
			$initials .= strtoupper( substr( $part, 0, 1 ) );
		}
		$initials = substr( $initials, 0, 2 );

		// Get current page URL to check if on salary-details page
		$current_url = home_url( add_query_arg( array() ) );
		$is_salary_details_page = strpos( $current_url, 'salary-details' ) !== false;

		ob_start();
		?>
		<div class="wc-tp-employee-header-new">
			<!-- Main Container: 2 Columns (Image + Right Content) -->
			<div class="header-main-grid">
				<!-- Left Column: Profile Picture -->
				<div class="header-left-column">
					<div class="profile-picture-container">
						<?php if ( $profile_picture_url ) : ?>
							<img src="<?php echo esc_url( $profile_picture_url ); ?>" alt="<?php echo esc_attr( $user->display_name ); ?>" class="profile-picture" />
						<?php else : ?>
							<div class="profile-picture-placeholder">
								<span class="initials"><?php echo esc_html( $initials ); ?></span>
							</div>
						<?php endif; ?>
					</div>
				</div>
				
				<!-- Right Column: 3 Row Containers -->
				<div class="header-right-column">
					<!-- Row 1: Name & Role -->
					<div class="header-row-1">
						<h2 class="profile-name"><?php echo esc_html( $user->display_name ); ?></h2>
						<span class="profile-role"><?php echo esc_html( $role_label ); ?></span>
					</div>
					
					<!-- Row 2: Info Items (2 Columns) -->
					<div class="header-row-2">
						<!-- Left Column: ID, Phone, Email -->
						<div class="info-column-left">
							<!-- ID (Clickable to copy) -->
							<div class="info-item">
								<i class="ph ph-identification-badge" style="cursor: pointer;" data-copy="<?php echo esc_attr( $vb_user_id ); ?>" title="Click to copy"></i>
								<span class="info-value" data-copy="<?php echo esc_attr( $vb_user_id ); ?>" title="Click to copy"><?php echo esc_html( $vb_user_id ); ?></span>
							</div>
							<!-- Phone (Clickable to call) -->
							<div class="info-item">
								<i class="ph ph-phone"></i>
								<?php if ( $phone !== '---' ) : ?>
									<a href="tel:<?php echo esc_attr( $phone ); ?>" class="info-value" style="text-decoration: none; color: inherit;"><?php echo esc_html( $phone ); ?></a>
								<?php else : ?>
									<span class="info-value"><?php echo esc_html( $phone ); ?></span>
								<?php endif; ?>
							</div>
							<!-- Email (Clickable to email) -->
							<div class="info-item">
								<i class="ph ph-envelope"></i>
								<?php if ( $email !== '---' ) : ?>
									<a href="mailto:<?php echo esc_attr( $email ); ?>" class="info-value" style="text-decoration: none; color: inherit;"><?php echo esc_html( $email ); ?></a>
								<?php else : ?>
									<span class="info-value"><?php echo esc_html( $email ); ?></span>
								<?php endif; ?>
							</div>
						</div>
						
						<!-- Right Column: Salary Type, Status, Social Icons -->
						<div class="info-column-right">
							<!-- Salary Type (Clickable to salary-details page) -->
							<div class="info-item">
								<i class="ph ph-briefcase"></i>
								<?php
								$salary_type_labels = array(
									'fixed' => __( 'Fixed Salary', 'wc-team-payroll' ),
									'commission' => __( 'Commission Based', 'wc-team-payroll' ),
									'combined' => __( 'Combined', 'wc-team-payroll' ),
								);
								$salary_label = $salary_type_labels[ $salary_type ] ?? ucfirst( $salary_type );
								$salary_details_url = wc_get_account_endpoint_url( 'salary-details' );
								?>
								<?php if ( ! $is_salary_details_page ) : ?>
									<a href="<?php echo esc_url( $salary_details_url ); ?>" class="info-value" style="text-decoration: none; color: inherit;"><?php echo esc_html( $salary_label ); ?></a>
								<?php else : ?>
									<span class="info-value"><?php echo esc_html( $salary_label ); ?></span>
								<?php endif; ?>
							</div>
							
							<!-- Status -->
							<div class="info-item">
								<i class="ph ph-check-square-offset"></i>
								<span class="info-value status-<?php echo esc_attr( $employee_status ); ?>"><?php echo esc_html( ucfirst( $employee_status ) ); ?></span>
							</div>
							
							<!-- Social Icons -->
							<div class="social-icons-row">
								<a href="#" class="social-icon facebook" title="<?php esc_attr_e( 'Facebook', 'wc-team-payroll' ); ?>">
									<i class="ph ph-facebook-logo"></i>
								</a>
								<a href="#" class="social-icon whatsapp" title="<?php esc_attr_e( 'WhatsApp', 'wc-team-payroll' ); ?>">
									<i class="ph ph-whatsapp-logo"></i>
								</a>
								<a href="#" class="social-icon instagram" title="<?php esc_attr_e( 'Instagram', 'wc-team-payroll' ); ?>">
									<i class="ph ph-instagram-logo"></i>
								</a>
								<a href="#" class="social-icon linkedin" title="<?php esc_attr_e( 'LinkedIn', 'wc-team-payroll' ); ?>">
									<i class="ph ph-linkedin-logo"></i>
								</a>
							</div>
						</div>
					</div>
					
					<!-- Row 3: Bio -->
					<div class="header-row-3">
						<p class="profile-bio"><?php echo esc_html( $bio ); ?></p>
					</div>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Flush rewrite rules (call this on activation)
	 */
	public static function flush_rewrite_rules() {
		self::add_endpoints();
		flush_rewrite_rules();
	}
	private static function get_user_earnings_for_period( $user_id, $start_date, $end_date ) {
		$args = array(
			'limit'  => -1,
			'status' => array( 'completed', 'processing', 'refunded' ),
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

		foreach ( $orders as $order ) {
			$agent_id = $order->get_meta( '_primary_agent_id' );
			$processor_id = $order->get_meta( '_processor_user_id' );
			$commission_data = $order->get_meta( '_commission_data' );

			if ( ! $commission_data ) {
				continue;
			}

			if ( intval( $agent_id ) === intval( $user_id ) ) {
				$total_earnings += $commission_data['agent_earnings'];
			} elseif ( intval( $processor_id ) === intval( $user_id ) ) {
				$total_earnings += $commission_data['processor_earnings'];
			}
		}

		return $total_earnings;
	}

	/**
	 * Helper: Get user total earnings
	 */
	private static function get_user_total_earnings( $user_id ) {
		$args = array(
			'limit'  => -1,
			'status' => array( 'completed', 'processing', 'refunded' ),
		);

		$orders = wc_get_orders( $args );
		$total_earnings = 0;

		foreach ( $orders as $order ) {
			$agent_id = $order->get_meta( '_primary_agent_id' );
			$processor_id = $order->get_meta( '_processor_user_id' );
			$commission_data = $order->get_meta( '_commission_data' );

			if ( ! $commission_data ) {
				continue;
			}

			if ( intval( $agent_id ) === intval( $user_id ) ) {
				$total_earnings += $commission_data['agent_earnings'];
			} elseif ( intval( $processor_id ) === intval( $user_id ) ) {
				$total_earnings += $commission_data['processor_earnings'];
			}
		}

		return $total_earnings;
	}

	/**
	 * Helper: Get user total paid amount
	 */
	private static function get_user_total_paid( $user_id ) {
		$payments = get_user_meta( $user_id, '_wc_tp_payments', true );
		if ( ! is_array( $payments ) ) {
			return 0;
		}

		$total_paid = 0;
		foreach ( $payments as $payment ) {
			$total_paid += floatval( $payment['amount'] );
		}

		return $total_paid;
	}

	/**
	 * Helper: Get user monthly history with real data
	 */
	private static function get_user_monthly_history( $user_id, $months = 12 ) {
		$history = array();

		for ( $i = 0; $i < $months; $i++ ) {
			$date = date( 'Y-m', strtotime( "-{$i} months" ) );
			$start_date = $date . '-01';
			$end_date = date( 'Y-m-t', strtotime( $start_date ) );

			// Get earnings for this month
			$args = array(
				'limit'  => -1,
				'status' => array( 'completed', 'processing', 'refunded' ),
				'date_query' => array(
					array(
						'after'     => $start_date,
						'before'    => $end_date,
						'inclusive' => true,
					),
				),
			);

			$orders = wc_get_orders( $args );
			$total_earned = 0;
			$orders_count = 0;

			foreach ( $orders as $order ) {
				$agent_id = $order->get_meta( '_primary_agent_id' );
				$processor_id = $order->get_meta( '_processor_user_id' );
				$commission_data = $order->get_meta( '_commission_data' );

				if ( ! $commission_data ) {
					continue;
				}

				if ( intval( $agent_id ) === intval( $user_id ) ) {
					$total_earned += $commission_data['agent_earnings'];
					$orders_count++;
				} elseif ( intval( $processor_id ) === intval( $user_id ) ) {
					$total_earned += $commission_data['processor_earnings'];
					$orders_count++;
				}
			}

			// Get payments for this month
			$payments = get_user_meta( $user_id, '_wc_tp_payments', true );
			$total_paid = 0;

			if ( is_array( $payments ) ) {
				$start_timestamp = strtotime( $start_date . ' 00:00:00' );
				$end_timestamp = strtotime( $end_date . ' 23:59:59' );

				foreach ( $payments as $payment ) {
					$payment_date_str = $payment['date'];
					if ( strpos( $payment_date_str, 'T' ) !== false ) {
						$payment_date_str = str_replace( 'T', ' ', $payment_date_str );
					}

					$payment_timestamp = strtotime( $payment_date_str );
					if ( $payment_timestamp !== false && $payment_timestamp >= $start_timestamp && $payment_timestamp <= $end_timestamp ) {
						$total_paid += floatval( $payment['amount'] );
					}
				}
			}

			// Only add months with earnings or payments
			if ( $total_earned > 0 || $total_paid > 0 ) {
				$history[] = array(
					'date' => $date,
					'total' => $total_earned,
					'paid' => $total_paid,
					'orders_count' => $orders_count,
				);
			}
		}

		return array_reverse( $history );
	}

	/**
	 * AJAX: Get earnings data for My Earnings page
	 */
	public static function ajax_get_earnings_data() {
		check_ajax_referer( 'wc_team_payroll_nonce', 'nonce' );

		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			wp_send_json_error( __( 'Unauthorized', 'wc-team-payroll' ) );
		}

		// Get current month earnings
		$current_month_start = date( 'Y-m-01' );
		$current_month_end = date( 'Y-m-t' );
		$current_month_earnings = self::get_user_earnings_for_period( $user_id, $current_month_start, $current_month_end );
		$total_earnings = self::get_user_total_earnings( $user_id );
		$total_paid = self::get_user_total_paid( $user_id );
		$total_due = $total_earnings - $total_paid;

		// Get monthly history
		$monthly_history = self::get_user_monthly_history( $user_id, 12 );

		// Get all payments for this user
		$all_payments = get_user_meta( $user_id, '_wc_tp_payments', true );
		if ( ! is_array( $all_payments ) ) {
			$all_payments = array();
		}

		// Format monthly history for frontend
		$formatted_history = array();
		foreach ( $monthly_history as $month_data ) {
			$due_amount = $month_data['total'] - $month_data['paid'];
			
			// Determine status based on actual payment records
			$status = self::get_payment_status_for_period( $user_id, $month_data['date'], $month_data['total'], $month_data['paid'], $all_payments );
			
			$formatted_history[] = array(
				'date' => date( 'F Y', strtotime( $month_data['date'] . '-01' ) ),
				'orders_count' => $month_data['orders_count'],
				'total' => $month_data['total'],
				'total_formatted' => wp_kses_post( wc_price( $month_data['total'] ) ),
				'paid' => $month_data['paid'],
				'paid_formatted' => wp_kses_post( wc_price( $month_data['paid'] ) ),
				'due' => $due_amount,
				'due_formatted' => wp_kses_post( wc_price( $due_amount ) ),
				'status' => $status,
			);
		}

		wp_send_json_success( array(
			'current_month_earnings' => wp_kses_post( wc_price( $current_month_earnings ) ),
			'total_earnings' => wp_kses_post( wc_price( $total_earnings ) ),
			'total_paid' => wp_kses_post( wc_price( $total_paid ) ),
			'total_due' => wp_kses_post( wc_price( $total_due ) ),
			'monthly_history' => $formatted_history,
		) );
	}

	/**
	 * Helper: Determine payment status for a period
	 * 
	 * Status logic:
	 * - 'paid': Total earned amount is fully covered by payments
	 * - 'partially_paid': Some payments exist but don't cover full amount
	 * - 'pending': No payments recorded for this period
	 */
	private static function get_payment_status_for_period( $user_id, $month_date, $total_earned, $total_paid, $all_payments = array() ) {
		// If no earnings, mark as paid
		if ( $total_earned <= 0 ) {
			return 'paid';
		}

		// If no payments recorded, it's pending
		if ( empty( $all_payments ) ) {
			return 'pending';
		}

		// Extract year and month from date string (format: 'Y-m')
		$date_parts = explode( '-', $month_date );
		$year = $date_parts[0];
		$month = $date_parts[1];
		$start_date = sprintf( '%s-%s-01', $year, $month );
		$end_date = date( 'Y-m-t', strtotime( $start_date ) );
		$start_timestamp = strtotime( $start_date . ' 00:00:00' );
		$end_timestamp = strtotime( $end_date . ' 23:59:59' );

		// Calculate payments for this period
		$period_paid = 0;
		foreach ( $all_payments as $payment ) {
			$payment_date_str = $payment['date'];
			
			// Handle datetime-local format (2026-04-11T14:30)
			if ( strpos( $payment_date_str, 'T' ) !== false ) {
				$payment_date_str = str_replace( 'T', ' ', $payment_date_str );
			}
			
			$payment_timestamp = strtotime( $payment_date_str );
			
			// Only count payments within this period
			if ( $payment_timestamp !== false && $payment_timestamp >= $start_timestamp && $payment_timestamp <= $end_timestamp ) {
				$period_paid += floatval( $payment['amount'] );
			}
		}

		// Determine status based on payment coverage
		if ( $period_paid >= $total_earned ) {
			return 'paid';
		} elseif ( $period_paid > 0 ) {
			return 'partially_paid';
		} else {
			return 'pending';
		}
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
