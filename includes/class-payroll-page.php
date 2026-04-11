<?php
/**
 * Payroll Page with Modern UI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Team_Payroll_Page {

	/**
	 * Render payroll page
	 */
	public function render_payroll() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized', 'wc-team-payroll' ) );
		}

		// Enqueue common CSS and JS
		wp_enqueue_style( 'wc-tp-common', WC_TEAM_PAYROLL_URL . 'assets/css/common.css', array(), WC_TEAM_PAYROLL_VERSION );
		wp_enqueue_script( 'wc-tp-common', WC_TEAM_PAYROLL_URL . 'assets/js/common.js', array( 'jquery' ), WC_TEAM_PAYROLL_VERSION, true );

		$start_date = isset( $_GET['start_date'] ) ? sanitize_text_field( $_GET['start_date'] ) : date( 'Y-m-01' );
		$end_date = isset( $_GET['end_date'] ) ? sanitize_text_field( $_GET['end_date'] ) : date( 'Y-m-t' );

		?>
		<div class="wrap wc-tp-page-wrapper">
			<h1><?php esc_html_e( 'Payroll', 'wc-team-payroll' ); ?></h1>

			<!-- Search Filter -->
			<div class="wc-tp-search-filter">
				<input type="text" id="wc-tp-payroll-search" placeholder="<?php esc_attr_e( 'Search by Name, Email, User ID, or Phone...', 'wc-team-payroll' ); ?>" />
				<button type="button" class="button button-secondary" id="wc-tp-payroll-search-clear"><?php esc_html_e( 'Clear', 'wc-team-payroll' ); ?></button>
			</div>

			<!-- Unified Filter Section -->
			<div class="wc-tp-unified-filter">
				<div class="wc-tp-filter-row">
					<!-- Date Range Preset -->
					<div class="wc-tp-filter-group">
						<label><?php esc_html_e( 'Date Range:', 'wc-team-payroll' ); ?></label>
						<select id="wc-tp-payroll-date-preset">
							<option value="all-time"><?php esc_html_e( 'All Time', 'wc-team-payroll' ); ?></option>
							<option value="today"><?php esc_html_e( 'Today', 'wc-team-payroll' ); ?></option>
							<option value="this-week"><?php esc_html_e( 'This Week', 'wc-team-payroll' ); ?></option>
							<option value="this-month" selected><?php esc_html_e( 'This Month', 'wc-team-payroll' ); ?></option>
							<option value="this-year"><?php esc_html_e( 'This Year', 'wc-team-payroll' ); ?></option>
							<option value="last-week"><?php esc_html_e( 'Last Week', 'wc-team-payroll' ); ?></option>
							<option value="last-month"><?php esc_html_e( 'Last Month', 'wc-team-payroll' ); ?></option>
							<option value="last-year"><?php esc_html_e( 'Last Year', 'wc-team-payroll' ); ?></option>
							<option value="last-6-months"><?php esc_html_e( 'Last 6 Months', 'wc-team-payroll' ); ?></option>
							<option value="custom"><?php esc_html_e( 'Custom', 'wc-team-payroll' ); ?></option>
						</select>
					</div>

					<!-- Custom Date Range (Hidden by default) -->
					<div class="wc-tp-filter-group" id="wc-tp-payroll-custom-dates" style="display: none;">
						<input type="date" id="wc-tp-payroll-start-date" />
						<span class="wc-tp-date-separator">to</span>
						<input type="date" id="wc-tp-payroll-end-date" />
					</div>

					<!-- Salary Type Filter -->
					<div class="wc-tp-filter-group">
						<label><?php esc_html_e( 'Salary Type:', 'wc-team-payroll' ); ?></label>
						<select id="wc-tp-payroll-salary-type-filter">
							<option value=""><?php esc_html_e( 'All Types', 'wc-team-payroll' ); ?></option>
							<option value="commission"><?php esc_html_e( 'Commission Based', 'wc-team-payroll' ); ?></option>
							<option value="fixed"><?php esc_html_e( 'Fixed Salary', 'wc-team-payroll' ); ?></option>
							<option value="combined"><?php esc_html_e( 'Combined (Base + Commission)', 'wc-team-payroll' ); ?></option>
						</select>
					</div>

					<!-- Filter Button -->
					<div class="wc-tp-filter-group">
						<button type="button" class="button button-primary" id="wc-tp-payroll-filter-btn"><?php esc_html_e( 'Filter', 'wc-team-payroll' ); ?></button>
					</div>
				</div>
			</div>

			<!-- Payroll Table Section -->
			<div class="wc-tp-table-section" id="wc-tp-payroll-table-section">
				<div class="wc-tp-table-header">
					<h2><?php esc_html_e( 'Payroll Details', 'wc-team-payroll' ); ?></h2>
					<div class="wc-tp-items-per-page">
						<label for="wc-tp-payroll-per-page"><?php esc_html_e( 'Items per page:', 'wc-team-payroll' ); ?></label>
						<select id="wc-tp-payroll-per-page">
							<option value="10">10</option>
							<option value="20" selected>20</option>
							<option value="30">30</option>
							<option value="50">50</option>
							<option value="100">100</option>
						</select>
					</div>
				</div>
				<div id="wc-tp-payroll-table-container">
					<!-- Content will be loaded via AJAX -->
				</div>
				<!-- Pagination -->
				<div id="wc-tp-payroll-pagination" style="margin-top: 20px; text-align: center;"></div>
			</div>
		</div>

		<style>
			/* Payroll page specific styles */
		</style>

		<script>
			jQuery(document).ready(function($) {
				let wcCurrency = 'USD';
				let wcCurrencySymbol = '$';
				let wcCurrencyPos = 'left';
				let currentPage = 1;
				let allPayrollData = [];
				let searchQuery = '';
				let salaryTypeFilter = '';
				let startDate = '';
				let endDate = '';
				let itemsPerPage = 20;

				const savedItemsPerPage = localStorage.getItem('wc_tp_payroll_items_per_page');
				if (savedItemsPerPage) {
					itemsPerPage = parseInt(savedItemsPerPage);
					$('#wc-tp-payroll-per-page').val(itemsPerPage);
				}

				// Initialize with default date range (This Month)
				const defaultRange = getDateRange('this-month');
				startDate = defaultRange.start;
				endDate = defaultRange.end;
				$('#wc-tp-payroll-start-date').val(startDate);
				$('#wc-tp-payroll-end-date').val(endDate);

				loadPayrollData();

				// Date preset change
				handleDatePresetChange(
					'#wc-tp-payroll-date-preset',
					$('#wc-tp-payroll-custom-dates'),
					'#wc-tp-payroll-start-date',
					'#wc-tp-payroll-end-date',
					function(start, end) {
						startDate = start;
						endDate = end;
					}
				);

				// Custom date inputs
				$('#wc-tp-payroll-start-date').on('change', function() {
					startDate = $(this).val();
				});

				$('#wc-tp-payroll-end-date').on('change', function() {
					endDate = $(this).val();
				});

				$('#wc-tp-payroll-per-page').on('change', function() {
					itemsPerPage = parseInt($(this).val());
					localStorage.setItem('wc_tp_payroll_items_per_page', itemsPerPage);
					currentPage = 1;
					renderPayrollTable(allPayrollData);
					renderPagination(allPayrollData);
				});

				$('#wc-tp-payroll-filter-btn').on('click', function() {
					currentPage = 1;
					loadPayrollData();
				});

				$('#wc-tp-payroll-salary-type-filter').on('change', function() {
					salaryTypeFilter = $(this).val();
				});

				$('#wc-tp-payroll-search').on('keyup', function() {
					currentPage = 1;
					searchQuery = $(this).val();
					loadPayrollData();
				});

				$('#wc-tp-payroll-search-clear').on('click', function() {
					$('#wc-tp-payroll-search').val('');
					searchQuery = '';
					currentPage = 1;
					loadPayrollData();
				});

				function loadPayrollData() {
					if (!startDate || !endDate) {
						alert('Please select both start and end dates');
						return;
					}

					$('#wc-tp-payroll-filter-btn').prop('disabled', true).text('Loading...');

					$.ajax({
						url: ajaxurl,
						type: 'POST',
						data: {
							action: 'wc_tp_get_payroll_data_range',
							start_date: startDate,
							end_date: endDate,
							search_query: searchQuery,
							salary_type: salaryTypeFilter
						},
						success: function(response) {
							if (response.success) {
								const data = response.data;
								
								wcCurrency = data.currency || 'USD';
								wcCurrencySymbol = data.currency_symbol || '$';
								wcCurrencyPos = data.currency_pos || 'left';
								allPayrollData = data.payroll;
								currentPage = 1;
								
								renderPayrollTable(allPayrollData);
								renderPagination(allPayrollData);
							}
						},
						error: function() {
							// Silent error handling
						},
						complete: function() {
							$('#wc-tp-payroll-filter-btn').prop('disabled', false).text('Filter');
						}
					});
				}

				function renderPayrollTable(payroll) {
					const container = $('#wc-tp-payroll-table-container');
					
					if (!payroll || payroll.length === 0) {
						container.html('<div class="wc-tp-empty-state"><div class="wc-tp-empty-icon">📊</div><p>No payroll data for this period</p></div>');
						return;
					}

					let payrollArray = payroll;
					
					// If payroll is an object (from AJAX), convert to array
					if (!Array.isArray(payroll)) {
						payrollArray = [];
						$.each(payroll, function(userId, data) {
							payrollArray.push({
								userId: userId,
								name: data.name,
								orders: data.orders,
								total: data.total,
								paid: data.paid,
								due: data.due
							});
						});
					}

					const startIndex = (currentPage - 1) * itemsPerPage;
					const endIndex = startIndex + itemsPerPage;
					const pageData = payrollArray.slice(startIndex, endIndex);

					let html = '<table class="wc-tp-data-table wc-tp-sortable"><thead><tr>';
					html += '<th class="wc-tp-sortable-header" data-sort="name">Employee</th>';
					html += '<th class="wc-tp-sortable-header" data-sort="orders">Orders</th>';
					html += '<th class="wc-tp-sortable-header" data-sort="total">Total Earnings</th>';
					html += '<th class="wc-tp-sortable-header" data-sort="paid">Paid</th>';
					html += '<th class="wc-tp-sortable-header" data-sort="due">Due</th>';
					html += '<th>Action</th>';
					html += '</tr></thead><tbody>';

					$.each(pageData, function(i, data) {
						html += '<tr data-user-id="' + data.userId + '">';
						html += '<td><strong>' + data.name + '</strong></td>';
						html += '<td><span class="wc-tp-badge">' + data.orders + '</span></td>';
						html += '<td>' + formatCurrency(data.total) + '</td>';
						html += '<td>' + formatCurrency(data.paid) + '</td>';
						html += '<td>' + formatCurrency(data.due) + '</td>';
						html += '<td><a href="' + ajaxurl.replace('admin-ajax.php', 'admin.php?page=wc-team-payroll-employee-detail&user_id=' + data.userId) + '" class="button button-small button-primary">View</a></td>';
						html += '</tr>';
					});

					html += '</tbody></table>';
					container.html(html);
					
					// Store payroll array for sorting
					container.data('payrollArray', payrollArray);
					attachPayrollSortHandlers(container, payrollArray);
				}

				function attachPayrollSortHandlers(container, payrollArray) {
					attachSortHandlers(container, payrollArray, function(sortedData, sortState) {
						currentPage = 1;
						allPayrollData = sortedData;
						renderPayrollTable(allPayrollData);
						renderPagination(allPayrollData);
					});
				}

				function renderPagination(payroll) {
					const container = $('#wc-tp-payroll-pagination');
					
					let totalItems = 0;
					if (Array.isArray(payroll)) {
						totalItems = payroll.length;
					} else {
						totalItems = Object.keys(payroll).length;
					}

					renderPagination(container, totalItems, currentPage, itemsPerPage, function(page) {
						currentPage = page;
						renderPayrollTable(allPayrollData);
						renderPagination(allPayrollData);
						$('html, body').animate({ scrollTop: $('#wc-tp-payroll-table-section').offset().top - 100 }, 300);
					});
				}

				function formatCurrency(value) {
					return formatCurrency(value, wcCurrency, wcCurrencySymbol, wcCurrencyPos);
				}
			});
		</script>
		<?php
	}
}
