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

		$start_date = isset( $_GET['start_date'] ) ? sanitize_text_field( $_GET['start_date'] ) : date( 'Y-m-01' );
		$end_date = isset( $_GET['end_date'] ) ? sanitize_text_field( $_GET['end_date'] ) : date( 'Y-m-t' );

		?>
		<div class="wrap wc-team-payroll-payroll">
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
							<option value="this-month"><?php esc_html_e( 'This Month', 'wc-team-payroll' ); ?></option>
							<option value="all-time" selected><?php esc_html_e( 'All Time', 'wc-team-payroll' ); ?></option>
							<option value="today"><?php esc_html_e( 'Today', 'wc-team-payroll' ); ?></option>
							<option value="this-week"><?php esc_html_e( 'This Week', 'wc-team-payroll' ); ?></option>
							<option value="this-year"><?php esc_html_e( 'This Year', 'wc-team-payroll' ); ?></option>
							<option value="last-week"><?php esc_html_e( 'Last Week', 'wc-team-payroll' ); ?></option>
							<option value="last-month"><?php esc_html_e( 'Last Month', 'wc-team-payroll' ); ?></option>
							<option value="last-year"><?php esc_html_e( 'Last Year', 'wc-team-payroll' ); ?></option>
							<option value="last-6-months"><?php esc_html_e( 'Last 6 Months', 'wc-team-payroll' ); ?></option>
							<option value="custom"><?php esc_html_e( 'Custom', 'wc-team-payroll' ); ?></option>
						</select>
					</div>

					<!-- Custom Date Range (Hidden by default) -->
					<div class="wc-tp-filter-group wc-tp-custom-date-range" id="wc-tp-custom-date-range" style="display: none;">
						<input type="date" id="wc-tp-payroll-start-date" value="<?php echo esc_attr( $start_date ); ?>" />
						<span class="wc-tp-date-separator">to</span>
						<input type="date" id="wc-tp-payroll-end-date" value="<?php echo esc_attr( $end_date ); ?>" />
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
				<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
					<h2 style="margin: 0;"><?php esc_html_e( 'Payroll Details', 'wc-team-payroll' ); ?></h2>
					<div style="display: flex; gap: 10px; align-items: center;">
						<label for="wc-tp-payroll-per-page" style="margin: 0; font-weight: 600; color: #212B36;"><?php esc_html_e( 'Items per page:', 'wc-team-payroll' ); ?></label>
						<select id="wc-tp-payroll-per-page" style="padding: 6px 10px; border: 1px solid #E5EAF0; border-radius: 6px; font-size: 14px;">
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
			:root {
				--color-primary: #FF9900;
				--color-primary-hover: #E68A00;
				--color-primary-subtle: #FFF4E5;
				--color-secondary: #212B36;
				--color-site-bg: #FDFBF8;
				--color-card-bg: #FFFFFF;
				--color-border-light: #E5EAF0;
				--color-accent-alert: #FF5500;
				--color-accent-link: #0077EE;
				--color-accent-success: #388E3C;
				--color-accent-muted: #F4F4F4;
				--text-main: #212B36;
				--text-body: #454F5B;
				--text-muted: #919EAB;
				--font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
				--fs-h1: 2rem;
				--fs-h2: 1.5rem;
				--fs-body: 1rem;
				--fs-meta: 0.875rem;
				--fs-small: 0.75rem;
				--fw-bold: 700;
				--fw-semibold: 600;
				--fw-medium: 500;
				--lh-body: 1.5;
			}

			.wc-team-payroll-payroll {
				background: var(--color-site-bg);
				padding: 24px;
				font-family: var(--font-family);
				color: var(--text-main);
			}

			.wc-team-payroll-payroll h1 {
				font-size: var(--fs-h1);
				font-weight: var(--fw-bold);
				color: var(--text-main);
				margin-bottom: 24px;
			}

			.wc-tp-search-filter {
				background: var(--color-card-bg);
				padding: 16px;
				border-radius: 8px;
				margin-bottom: 16px;
				border: 1px solid var(--color-border-light);
				display: flex;
				gap: 12px;
				align-items: center;
				flex-wrap: wrap;
			}

			.wc-tp-search-filter input[type="text"] {
				flex: 1;
				min-width: 250px;
				padding: 8px 12px;
				border: 1px solid var(--color-border-light);
				border-radius: 6px;
				font-size: var(--fs-body);
				font-family: var(--font-family);
				color: var(--text-main);
			}

			.wc-tp-search-filter input[type="text"]::placeholder {
				color: var(--text-muted);
			}

			.wc-tp-search-filter .button-secondary {
				background: var(--color-accent-muted);
				border-color: var(--color-border-light);
				color: var(--text-main);
				font-weight: var(--fw-semibold);
				border-radius: 6px;
				padding: 8px 16px;
				font-size: var(--fs-meta);
				transition: all 0.2s ease;
			}

			.wc-tp-search-filter .button-secondary:hover {
				background: var(--color-border-light);
				border-color: var(--color-border-light);
			}

			.wc-tp-unified-filter {
				background: var(--color-card-bg);
				padding: 16px;
				border-radius: 8px;
				margin-bottom: 24px;
				border: 1px solid var(--color-border-light);
			}

			.wc-tp-filter-row {
				display: flex;
				gap: 12px;
				align-items: flex-end;
				flex-wrap: wrap;
			}

			.wc-tp-filter-group {
				display: flex;
				flex-direction: column;
				gap: 6px;
			}

			.wc-tp-filter-group label {
				font-weight: var(--fw-semibold);
				color: var(--text-main);
				font-size: var(--fs-meta);
			}

			.wc-tp-filter-group select,
			.wc-tp-filter-group input[type="date"] {
				padding: 8px 12px;
				border: 1px solid var(--color-border-light);
				border-radius: 6px;
				font-size: var(--fs-body);
				font-family: var(--font-family);
				color: var(--text-main);
				background: var(--color-card-bg);
				cursor: pointer;
			}

			.wc-tp-custom-date-range {
				display: flex;
				gap: 8px;
				align-items: center;
				flex-wrap: wrap;
			}

			.wc-tp-custom-date-range input[type="date"] {
				flex: 1;
				min-width: 150px;
			}

			.wc-tp-date-separator {
				color: var(--text-muted);
				font-weight: var(--fw-medium);
				font-size: var(--fs-meta);
			}

			.wc-tp-table-section {
				background: var(--color-card-bg);
				padding: 20px;
				border-radius: 8px;
				border: 1px solid var(--color-border-light);
				margin-bottom: 20px;
			}

			.wc-tp-table-section h2 {
				margin-top: 0;
				margin-bottom: 20px;
				color: var(--text-main);
				border-left: 4px solid var(--color-primary);
				padding-left: 12px;
				font-size: var(--fs-h2);
				font-weight: var(--fw-bold);
			}

			.wc-tp-empty-state {
				text-align: center;
				padding: 40px 20px;
				color: var(--text-muted);
			}

			.wc-tp-empty-icon {
				font-size: 48px;
				margin-bottom: 15px;
				display: block;
				opacity: 0.5;
			}

			.wc-tp-empty-state p {
				margin: 0;
				font-size: var(--fs-body);
				color: var(--text-muted);
			}

			.wc-tp-data-table {
				width: 100%;
				border-collapse: collapse;
				display: table !important;
			}

			.wc-tp-data-table thead {
				background: var(--color-accent-muted);
				display: table-header-group !important;
			}

			.wc-tp-data-table tbody {
				display: table-row-group !important;
			}

			.wc-tp-data-table tr {
				display: table-row !important;
			}

			.wc-tp-data-table th,
			.wc-tp-data-table td {
				display: table-cell !important;
			}

			.wc-tp-data-table th {
				padding: 14px 12px;
				text-align: left;
				font-weight: var(--fw-semibold);
				color: var(--text-main);
				font-size: var(--fs-meta);
				border-bottom: 1px solid var(--color-border-light);
			}

			.wc-tp-sortable-header {
				cursor: pointer;
				user-select: none;
				position: relative;
				padding-right: 24px;
				transition: all 0.2s ease;
			}

			.wc-tp-sortable-header::after {
				content: '⇅';
				position: absolute;
				right: 8px;
				opacity: 0.3;
				font-size: 12px;
				transition: all 0.2s ease;
			}

			.wc-tp-sortable-header:hover {
				background-color: var(--color-primary-subtle);
			}

			.wc-tp-sortable-header.wc-tp-sort-active {
				background-color: var(--color-primary-subtle) !important;
				color: var(--color-primary) !important;
			}

			.wc-tp-sortable-header.wc-tp-sort-active::after {
				opacity: 1 !important;
				color: var(--color-primary) !important;
			}

			.wc-tp-sortable-header.wc-tp-sort-active.wc-tp-sort-asc::after {
				content: '↑' !important;
			}

			.wc-tp-sortable-header.wc-tp-sort-active.wc-tp-sort-desc::after {
				content: '↓' !important;
			}

			.wc-tp-data-table td {
				padding: 12px;
				border-bottom: 1px solid var(--color-border-light);
				font-size: var(--fs-body);
				color: var(--text-body);
			}

			.wc-tp-data-table tbody tr:hover {
				background: var(--color-primary-subtle);
			}

			.wc-tp-badge {
				background: var(--color-primary);
				color: white;
				padding: 4px 8px;
				border-radius: 4px;
				font-size: var(--fs-small);
				font-weight: var(--fw-semibold);
			}

			.button-primary {
				background: var(--color-primary);
				border-color: var(--color-primary);
				color: white;
				font-weight: var(--fw-semibold);
				border-radius: 6px;
				padding: 8px 16px;
				font-size: var(--fs-meta);
				transition: all 0.2s ease;
			}

			.button-primary:hover {
				background: var(--color-primary-hover);
				border-color: var(--color-primary-hover);
			}

			.wc-tp-pagination {
				display: flex;
				gap: 8px;
				justify-content: center;
				align-items: center;
				flex-wrap: wrap;
			}

			.wc-tp-pagination a,
			.wc-tp-pagination span {
				padding: 8px 12px;
				border: 1px solid var(--color-border-light);
				border-radius: 4px;
				text-decoration: none;
				color: var(--text-main);
				transition: all 0.2s ease;
			}

			.wc-tp-pagination a:hover {
				background: var(--color-primary-subtle);
				border-color: var(--color-primary);
				color: var(--color-primary);
			}

			.wc-tp-pagination .current {
				background: var(--color-primary);
				color: white;
				border-color: var(--color-primary);
				font-weight: var(--fw-semibold);
			}

			@media (max-width: 768px) {
				.wc-team-payroll-payroll {
					padding: 12px;
				}

				.wc-tp-search-filter {
					flex-direction: column;
					gap: 8px;
					margin-bottom: 12px;
				}

				.wc-tp-search-filter input[type="text"] {
					width: 100%;
					min-width: unset;
				}

				.wc-tp-search-filter .button-secondary {
					width: 100%;
				}

				.wc-tp-unified-filter {
					margin-bottom: 12px;
				}

				.wc-tp-filter-row {
					flex-direction: column;
					gap: 8px;
				}

				.wc-tp-filter-group {
					width: 100%;
				}

				.wc-tp-filter-group select,
				.wc-tp-filter-group input[type="date"] {
					width: 100%;
				}

				.wc-tp-custom-date-range {
					flex-direction: column;
					gap: 8px;
				}

				.wc-tp-custom-date-range input[type="date"] {
					width: 100%;
					min-width: unset;
				}

				.wc-tp-table-section {
					padding: 12px;
					margin-bottom: 12px;
				}

				.wc-tp-table-section h2 {
					font-size: 1.25rem;
					margin-bottom: 12px;
					padding-left: 8px;
				}

				.wc-tp-data-table {
					font-size: 12px;
				}

				.wc-tp-data-table th,
				.wc-tp-data-table td {
					padding: 6px;
				}

				.button,
				.button-small {
					padding: 4px 6px;
					font-size: 10px;
				}

				.wc-tp-badge {
					padding: 2px 4px;
					font-size: 10px;
				}
			}
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
				let itemsPerPage = 20; // Default
				let lastPresetRange = { start: '', end: '' }; // Store last preset range

				// Load saved items per page from localStorage
				const savedItemsPerPage = localStorage.getItem('wc_tp_payroll_items_per_page');
				if (savedItemsPerPage) {
					itemsPerPage = parseInt(savedItemsPerPage);
					$('#wc-tp-payroll-per-page').val(itemsPerPage);
				}

				// Initialize with default preset (All Time)
				updateDateRangeFromPreset('all-time');
				loadPayrollData();

				// Date preset change
				$('#wc-tp-payroll-date-preset').on('change', function() {
					const preset = $(this).val();
					
					if (preset === 'custom') {
						// Show custom date inputs with last preset values
						$('#wc-tp-custom-date-range').slideDown(200);
					} else {
						// Hide custom date inputs and update dates
						$('#wc-tp-custom-date-range').slideUp(200);
						updateDateRangeFromPreset(preset);
					}
				});

				// Custom date range change - just update values, don't load
				$('#wc-tp-payroll-start-date, #wc-tp-payroll-end-date').on('change', function() {
					// Just update the values, don't trigger load
				});

				// Items per page change
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
					// Don't load here, wait for Filter button click
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

				function getDateRangeFromPreset(preset) {
					const today = new Date();
					const year = today.getFullYear();
					const month = String(today.getMonth() + 1).padStart(2, '0');
					const date = String(today.getDate()).padStart(2, '0');
					const todayStr = `${year}-${month}-${date}`;

					let startDate, endDate;

					switch (preset) {
						case 'today':
							startDate = todayStr;
							endDate = todayStr;
							break;

						case 'this-week':
							const firstDay = new Date(today);
							firstDay.setDate(today.getDate() - today.getDay());
							startDate = formatDateForInput(firstDay);
							endDate = todayStr;
							break;

						case 'this-month':
							startDate = `${year}-${month}-01`;
							endDate = todayStr;
							break;

						case 'this-year':
							startDate = `${year}-01-01`;
							endDate = todayStr;
							break;

						case 'last-week':
							const lastWeekEnd = new Date(today);
							lastWeekEnd.setDate(today.getDate() - today.getDay() - 1);
							const lastWeekStart = new Date(lastWeekEnd);
							lastWeekStart.setDate(lastWeekEnd.getDate() - 6);
							startDate = formatDateForInput(lastWeekStart);
							endDate = formatDateForInput(lastWeekEnd);
							break;

						case 'last-month':
							const lastMonthDate = new Date(year, parseInt(month) - 2, 1);
							const lastMonthYear = lastMonthDate.getFullYear();
							const lastMonthMonth = String(lastMonthDate.getMonth() + 1).padStart(2, '0');
							startDate = `${lastMonthYear}-${lastMonthMonth}-01`;
							const lastMonthLastDay = new Date(lastMonthYear, parseInt(lastMonthMonth), 0);
							endDate = `${lastMonthYear}-${lastMonthMonth}-${String(lastMonthLastDay.getDate()).padStart(2, '0')}`;
							break;

						case 'last-year':
							const lastYear = year - 1;
							startDate = `${lastYear}-01-01`;
							endDate = `${lastYear}-12-31`;
							break;

						case 'last-6-months':
							const sixMonthsAgo = new Date(today);
							sixMonthsAgo.setMonth(today.getMonth() - 6);
							startDate = formatDateForInput(sixMonthsAgo);
							endDate = todayStr;
							break;

						case 'all-time':
							startDate = '2000-01-01';
							endDate = todayStr;
							break;

						default:
							startDate = `${year}-${month}-01`;
							endDate = todayStr;
					}

					return { start: startDate, end: endDate };
				}

				function formatDateForInput(date) {
					const year = date.getFullYear();
					const month = String(date.getMonth() + 1).padStart(2, '0');
					const day = String(date.getDate()).padStart(2, '0');
					return `${year}-${month}-${day}`;
				}

				function updateDateRangeFromPreset(preset) {
					const range = getDateRangeFromPreset(preset);
					lastPresetRange = range; // Store for custom mode
					$('#wc-tp-payroll-start-date').val(range.start);
					$('#wc-tp-payroll-end-date').val(range.end);
				}

				function loadPayrollData() {
					const startDate = $('#wc-tp-payroll-start-date').val();
					const endDate = $('#wc-tp-payroll-end-date').val();

					if (!startDate || !endDate) {
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
								due: data.due,
								profile_picture: data.profile_picture,
								user_email: data.user_email,
								phone: data.phone,
								user_role: data.user_role
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
						const profileImg = data.profile_picture ? '<img src="' + data.profile_picture + '" alt="' + data.name + '" style="width: 32px; height: 32px; border-radius: 50%; margin-right: 8px; vertical-align: middle;" />' : '<span style="display: inline-block; width: 32px; height: 32px; border-radius: 50%; background: #E5EAF0; margin-right: 8px; vertical-align: middle;"></span>';
						const tooltip = 'Name: ' + data.name + '\nEmail: ' + (data.user_email || 'N/A') + '\nPhone: ' + (data.phone || 'N/A') + '\nRole: ' + (data.user_role || 'N/A');
						const userEditUrl = 'user-edit.php?user_id=' + data.userId;
						const nameHtml = '<a href="' + userEditUrl + '" title="' + tooltip + '" style="text-decoration: none; color: #0073aa; display: flex; align-items: center;">' + profileImg + '<span>' + data.name + '</span></a>';
						
						html += '<tr data-user-id="' + data.userId + '">';
						html += '<td><strong>' + nameHtml + '</strong></td>';
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
					let currentSort = container.data('sortState') || { field: null, direction: 'asc' };
					
					// Remove old event handlers to prevent duplicates
					container.find('.wc-tp-sortable-header').off('click');
					
					// Restore sort state classes if they exist
					if (currentSort.field) {
						const header = container.find('.wc-tp-sortable-header[data-sort="' + currentSort.field + '"]');
						if (header.length) {
							header.addClass('wc-tp-sort-active');
							if (currentSort.direction === 'asc') {
								header.addClass('wc-tp-sort-asc');
							} else {
								header.addClass('wc-tp-sort-desc');
							}
						}
					}
					
					container.find('.wc-tp-sortable-header').on('click', function() {
						const sortField = $(this).data('sort');
						if (!sortField) return;
						
						const isNumeric = ['orders', 'total', 'paid', 'due'].includes(sortField);
						
						// Check if clicking the same field
						if (currentSort.field === sortField) {
							// Toggle direction
							currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
						} else {
							// New field, start with ascending
							currentSort.field = sortField;
							currentSort.direction = 'asc';
						}
						
						// Save sort state to container
						container.data('sortState', currentSort);
						
						// Sort data
						let sortedData = [...payrollArray].sort((a, b) => {
							let aVal = a[sortField];
							let bVal = b[sortField];
							
							if (aVal === undefined || aVal === null) aVal = '';
							if (bVal === undefined || bVal === null) bVal = '';
							
							if (isNumeric) {
								aVal = parseFloat(aVal) || 0;
								bVal = parseFloat(bVal) || 0;
								return currentSort.direction === 'asc' ? aVal - bVal : bVal - aVal;
							} else {
								aVal = String(aVal).toLowerCase();
								bVal = String(bVal).toLowerCase();
								return currentSort.direction === 'asc' ? aVal.localeCompare(bVal) : bVal.localeCompare(aVal);
							}
						});
						
						// Reset to first page and update global data
						currentPage = 1;
						allPayrollData = sortedData;
						
						renderPayrollTable(allPayrollData);
						renderPagination(allPayrollData);
						
						// Re-attach handlers to new headers with updated sort state
						setTimeout(function() {
							attachPayrollSortHandlers(container, sortedData);
						}, 10);
					});
				}

				function renderPagination(payroll) {
					const container = $('#wc-tp-payroll-pagination');
					
					// Handle both array and object formats
					let totalItems = 0;
					if (Array.isArray(payroll)) {
						totalItems = payroll.length;
					} else {
						totalItems = Object.keys(payroll).length;
					}
					
					const totalPages = Math.ceil(totalItems / itemsPerPage);

					if (totalPages <= 1) {
						container.html('');
						return;
					}

					let html = '<div class="wc-tp-pagination">';

					// Previous button
					if (currentPage > 1) {
						html += '<a href="#" data-page="' + (currentPage - 1) + '">← Previous</a>';
					}

					// Page numbers
					for (let i = 1; i <= totalPages; i++) {
						if (i === currentPage) {
							html += '<span class="current">' + i + '</span>';
						} else {
							html += '<a href="#" data-page="' + i + '">' + i + '</a>';
						}
					}

					// Next button
					if (currentPage < totalPages) {
						html += '<a href="#" data-page="' + (currentPage + 1) + '">Next →</a>';
					}

					html += '</div>';
					container.html(html);

					// Pagination click handler
					container.find('a').on('click', function(e) {
						e.preventDefault();
						currentPage = parseInt($(this).data('page'));
						renderPayrollTable(allPayrollData);
						renderPagination(allPayrollData);
						$('html, body').animate({ scrollTop: $('#wc-tp-payroll-table-section').offset().top - 100 }, 300);
					});
				}

				function formatCurrency(value) {
					const amount = parseFloat(value).toFixed(2);
					if (wcCurrencyPos === 'right') {
						return amount + ' ' + wcCurrencySymbol;
					} else {
						return wcCurrencySymbol + ' ' + amount;
					}
				}
			});
		</script>
		<?php
	}
}
