<?php
/**
 * Employee Management - Salary, Bonuses, History
 */

class WC_Team_Payroll_Employee_Management {

	public function render_employees_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized', 'wc-team-payroll' ) );
		}

		?>
		<div class="wrap wc-team-payroll-employees">
			<h1><?php esc_html_e( 'Team Members', 'wc-team-payroll' ); ?></h1>

			<!-- Search Filter -->
			<div class="wc-tp-search-filter">
				<input type="text" id="wc-tp-employees-search" placeholder="<?php esc_attr_e( 'Search by name, email, or vb_user_id...', 'wc-team-payroll' ); ?>" />
				<button type="button" class="button button-secondary" id="wc-tp-employees-search-clear"><?php esc_html_e( 'Clear', 'wc-team-payroll' ); ?></button>
			</div>

			<!-- Unified Filter Section -->
			<div class="wc-tp-unified-filter">
				<div class="wc-tp-filter-row">
					<!-- Date Range Preset -->
					<div class="wc-tp-filter-group">
						<label><?php esc_html_e( 'Employee Created:', 'wc-team-payroll' ); ?></label>
						<select id="wc-tp-employees-date-preset">
							<option value="this-month"><?php esc_html_e( 'This Month', 'wc-team-payroll' ); ?></option>
							<option value="all-time"><?php esc_html_e( 'All Time', 'wc-team-payroll' ); ?></option>
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
						<input type="date" id="wc-tp-employees-start-date" />
						<span class="wc-tp-date-separator">to</span>
						<input type="date" id="wc-tp-employees-end-date" />
					</div>

					<!-- Salary Type Filter -->
					<div class="wc-tp-filter-group">
						<label><?php esc_html_e( 'Salary Type:', 'wc-team-payroll' ); ?></label>
						<select id="wc-tp-salary-type-filter">
							<option value=""><?php esc_html_e( 'All Types', 'wc-team-payroll' ); ?></option>
							<option value="commission"><?php esc_html_e( 'Commission Based', 'wc-team-payroll' ); ?></option>
							<option value="fixed"><?php esc_html_e( 'Fixed Salary', 'wc-team-payroll' ); ?></option>
							<option value="combined"><?php esc_html_e( 'Combined (Base + Commission)', 'wc-team-payroll' ); ?></option>
						</select>
					</div>

					<!-- Filter Button -->
					<div class="wc-tp-filter-group">
						<button type="button" class="button button-primary" id="wc-tp-employees-filter-btn"><?php esc_html_e( 'Filter', 'wc-team-payroll' ); ?></button>
					</div>
				</div>
			</div>

			<!-- Employees Table Section -->
			<div class="wc-tp-table-section" id="wc-tp-employees-table-section">
				<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
					<h2 style="margin: 0;"><?php esc_html_e( 'Team Members', 'wc-team-payroll' ); ?></h2>
					<div style="display: flex; gap: 10px; align-items: center;">
						<label for="wc-tp-employees-per-page" style="margin: 0; font-weight: 600; color: #212B36;"><?php esc_html_e( 'Items per page:', 'wc-team-payroll' ); ?></label>
						<select id="wc-tp-employees-per-page" style="padding: 6px 10px; border: 1px solid #E5EAF0; border-radius: 6px; font-size: 14px;">
							<option value="10">10</option>
							<option value="20" selected>20</option>
							<option value="30">30</option>
							<option value="50">50</option>
							<option value="100">100</option>
						</select>
					</div>
				</div>
				<div id="wc-tp-employees-table-container">
					<!-- Content will be loaded via AJAX -->
				</div>
				<!-- Pagination -->
				<div id="wc-tp-employees-pagination" style="margin-top: 20px; text-align: center;"></div>
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

			.wc-team-payroll-employees {
				background: var(--color-site-bg);
				padding: 24px;
				font-family: var(--font-family);
				color: var(--text-main);
			}

			.wc-team-payroll-employees h1 {
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
			}

			.wc-tp-data-table thead {
				background: var(--color-accent-muted);
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
				.wc-team-payroll-employees {
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
				let currentPage = 1;
				let allEmployeesData = [];
				let searchQuery = '';
				let salaryTypeFilter = '';
				let itemsPerPage = 20; // Default
				let lastPresetRange = { start: '', end: '' }; // Store last preset range

				// Load saved items per page from localStorage
				const savedItemsPerPage = localStorage.getItem('wc_tp_employees_items_per_page');
				if (savedItemsPerPage) {
					itemsPerPage = parseInt(savedItemsPerPage);
					$('#wc-tp-employees-per-page').val(itemsPerPage);
				}

				// Initialize with default preset (This Month)
				updateDateRangeFromPreset('this-month');
				loadEmployeesData();

				// Date preset change
				$('#wc-tp-employees-date-preset').on('change', function() {
					const preset = $(this).val();
					
					if (preset === 'custom') {
						// Show custom date inputs with last preset values
						$('#wc-tp-custom-date-range').slideDown(200);
					} else {
						// Hide custom date inputs and update dates
						$('#wc-tp-custom-date-range').slideUp(200);
						updateDateRangeFromPreset(preset);
						currentPage = 1;
						loadEmployeesData();
					}
				});

				// Custom date range change
				$('#wc-tp-employees-start-date, #wc-tp-employees-end-date').on('change', function() {
					currentPage = 1;
					loadEmployeesData();
				});

				// Items per page change
				$('#wc-tp-employees-per-page').on('change', function() {
					itemsPerPage = parseInt($(this).val());
					localStorage.setItem('wc_tp_employees_items_per_page', itemsPerPage);
					currentPage = 1;
					renderEmployeesTable(allEmployeesData);
					renderPagination(allEmployeesData);
				});

				$('#wc-tp-employees-filter-btn').on('click', function() {
					currentPage = 1;
					loadEmployeesData();
				});

				$('#wc-tp-employees-search').on('keyup', function() {
					currentPage = 1;
					searchQuery = $(this).val();
					loadEmployeesData();
				});

				$('#wc-tp-employees-search-clear').on('click', function() {
					$('#wc-tp-employees-search').val('');
					searchQuery = '';
					currentPage = 1;
					loadEmployeesData();
				});

				$('#wc-tp-salary-type-filter').on('change', function() {
					currentPage = 1;
					salaryTypeFilter = $(this).val();
					loadEmployeesData();
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
					$('#wc-tp-employees-start-date').val(range.start);
					$('#wc-tp-employees-end-date').val(range.end);
				}

				function loadEmployeesData() {
					const startDate = $('#wc-tp-employees-start-date').val();
					const endDate = $('#wc-tp-employees-end-date').val();

					if (!startDate || !endDate) {
						return;
					}

					$.ajax({
						url: ajaxurl,
						type: 'POST',
						data: {
							action: 'wc_tp_get_employees_data',
							search_query: searchQuery,
							salary_type: salaryTypeFilter,
							start_date: startDate,
							end_date: endDate
						},
						success: function(response) {
							if (response.success) {
								const data = response.data;
								allEmployeesData = data.employees;
								currentPage = 1;
								
								renderEmployeesTable(allEmployeesData);
								renderPagination(allEmployeesData);
							}
						},
						error: function() {
							// Silent error handling
						}
					});
				}

				function renderEmployeesTable(employees) {
					const container = $('#wc-tp-employees-table-container');
					
					if (!employees || employees.length === 0) {
						container.html('<div class="wc-tp-empty-state"><div class="wc-tp-empty-icon">👥</div><p>No team members found</p></div>');
						return;
					}

					const startIndex = (currentPage - 1) * itemsPerPage;
					const endIndex = startIndex + itemsPerPage;
					const pageData = employees.slice(startIndex, endIndex);

					let html = '<table class="wc-tp-data-table wc-tp-sortable"><thead><tr>';
					html += '<th class="wc-tp-sortable-header" data-sort="display_name">Name</th>';
					html += '<th class="wc-tp-sortable-header" data-sort="user_email">Email</th>';
					html += '<th class="wc-tp-sortable-header" data-sort="type">Type</th>';
					html += '<th>Salary/Commission</th>';
					html += '<th>Action</th>';
					html += '</tr></thead><tbody>';

					$.each(pageData, function(i, emp) {
						html += '<tr>';
						html += '<td><strong>' + emp.display_name + '</strong></td>';
						html += '<td>' + emp.user_email + '</td>';
						html += '<td>' + emp.type + '</td>';
						html += '<td>' + emp.salary_info + '</td>';
						html += '<td><a href="' + emp.manage_url + '" class="button button-small button-primary">Manage</a></td>';
						html += '</tr>';
					});

					html += '</tbody></table>';
					container.html(html);
					
					// Store employees array for sorting
					container.data('employeesArray', employees);
					attachEmployeesSortHandlers(container, employees);
				}

				function attachEmployeesSortHandlers(container, employeesArray) {
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
						let sortedData = [...employeesArray].sort((a, b) => {
							let aVal = a[sortField];
							let bVal = b[sortField];
							
							if (aVal === undefined || aVal === null) aVal = '';
							if (bVal === undefined || bVal === null) bVal = '';
							
							aVal = String(aVal).toLowerCase();
							bVal = String(bVal).toLowerCase();
							return currentSort.direction === 'asc' ? aVal.localeCompare(bVal) : bVal.localeCompare(aVal);
						});
						
						// Reset to first page and update global data
						currentPage = 1;
						allEmployeesData = sortedData;
						
						renderEmployeesTable(allEmployeesData);
						renderPagination(allEmployeesData);
						
						// Re-attach handlers to new headers with updated sort state
						setTimeout(function() {
							attachEmployeesSortHandlers(container, sortedData);
						}, 10);
					});
				}

				function renderPagination(employees) {
					const container = $('#wc-tp-employees-pagination');
					const totalPages = Math.ceil(employees.length / itemsPerPage);

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
						renderEmployeesTable(allEmployeesData);
						renderPagination(allEmployeesData);
						$('html, body').animate({ scrollTop: $('#wc-tp-employees-table-section').offset().top - 100 }, 300);
					});
				}
			});
		</script>
		<?php
	}

	public function ajax_update_employee_salary() {
		check_ajax_referer( 'wc_team_payroll_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'wc-team-payroll' ) );
		}

		$user_id = intval( $_POST['user_id'] );
		$salary_type = sanitize_text_field( $_POST['salary_type'] );
		$salary_amount = floatval( $_POST['salary_amount'] );
		$salary_frequency = sanitize_text_field( $_POST['salary_frequency'] );

		$old_type = get_user_meta( $user_id, '_wc_tp_fixed_salary', true );
		$old_combined = get_user_meta( $user_id, '_wc_tp_combined_salary', true );
		$old_amount = get_user_meta( $user_id, '_wc_tp_salary_amount', true );
		$old_frequency = get_user_meta( $user_id, '_wc_tp_salary_frequency', true );

		$old_salary_type = 'commission';
		if ( $old_type ) {
			$old_salary_type = 'fixed';
		} elseif ( $old_combined ) {
			$old_salary_type = 'combined';
		}

		if ( 'fixed' === $salary_type ) {
			update_user_meta( $user_id, '_wc_tp_fixed_salary', 1 );
			update_user_meta( $user_id, '_wc_tp_combined_salary', 0 );
			update_user_meta( $user_id, '_wc_tp_salary_amount', $salary_amount );
			update_user_meta( $user_id, '_wc_tp_salary_frequency', $salary_frequency );
		} elseif ( 'combined' === $salary_type ) {
			update_user_meta( $user_id, '_wc_tp_fixed_salary', 0 );
			update_user_meta( $user_id, '_wc_tp_combined_salary', 1 );
			update_user_meta( $user_id, '_wc_tp_salary_amount', $salary_amount );
			update_user_meta( $user_id, '_wc_tp_salary_frequency', $salary_frequency );
		} else {
			update_user_meta( $user_id, '_wc_tp_fixed_salary', 0 );
			update_user_meta( $user_id, '_wc_tp_combined_salary', 0 );
			delete_user_meta( $user_id, '_wc_tp_salary_amount' );
			delete_user_meta( $user_id, '_wc_tp_salary_frequency' );
		}

		$this->add_salary_history( $user_id, $old_salary_type, $old_amount, $old_frequency, $salary_type, $salary_amount, $salary_frequency );

		wp_send_json_success( array(
			'message' => __( 'Employee salary updated', 'wc-team-payroll' ),
		) );
	}

	private function add_salary_history( $user_id, $old_type, $old_amount, $old_frequency, $new_type, $new_amount, $new_frequency ) {
		$history = get_user_meta( $user_id, '_wc_tp_salary_history', true );
		if ( ! is_array( $history ) ) {
			$history = array();
		}

		// Only add to history if the type actually changed
		if ( $old_type !== $new_type ) {
			$history[] = array(
				'date'           => current_time( 'mysql' ),
				'old_type'       => $old_type,
				'old_amount'     => $old_amount,
				'old_frequency'  => $old_frequency,
				'new_type'       => $new_type,
				'new_amount'     => $new_amount,
				'new_frequency'  => $new_frequency,
				'changed_by'     => get_current_user_id(),
			);

			update_user_meta( $user_id, '_wc_tp_salary_history', $history );

			// Recalculate commissions for all orders involving this user
			$this->recalculate_user_commissions( $user_id );
		}
	}

	/**
	 * Recalculate commissions for all orders involving a user
	 */
	private function recalculate_user_commissions( $user_id ) {
		$core_engine = new WC_Team_Payroll_Core_Engine();

		// Get all orders where this user is agent or processor
		$args = array(
			'limit'  => -1,
			'status' => array( 'completed', 'processing', 'refunded' ),
		);

		$orders = wc_get_orders( $args );

		foreach ( $orders as $order ) {
			$agent_id = $order->get_meta( '_primary_agent_id' );
			$processor_id = $order->get_meta( '_processor_user_id' );

			// Check if this user is involved in this order
			if ( intval( $agent_id ) === intval( $user_id ) || intval( $processor_id ) === intval( $user_id ) ) {
				// Recalculate and update the commission
				$commission_data = $core_engine->calculate_commission( $order, $agent_id, $processor_id );
				$order->update_meta_data( '_commission_data', $commission_data );
				$order->save();
			}
		}
	}

	public function ajax_add_payment() {
		check_ajax_referer( 'wc_team_payroll_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'wc-team-payroll' ) );
		}

		$user_id = intval( $_POST['user_id'] );
		$amount = floatval( $_POST['amount'] );
		$payment_date = sanitize_text_field( $_POST['payment_date'] );

		$payments = get_user_meta( $user_id, '_wc_tp_payments', true );
		if ( ! is_array( $payments ) ) {
			$payments = array();
		}

		$payments[] = array(
			'id'             => uniqid(),
			'amount'         => $amount,
			'date'           => $payment_date,
			'created_at'     => current_time( 'mysql' ),
			'created_by'     => get_current_user_id(),
			'status'         => 'completed',
		);

		update_user_meta( $user_id, '_wc_tp_payments', $payments );

		wp_send_json_success( array(
			'message' => __( 'Payment added', 'wc-team-payroll' ),
			'payments' => $payments,
		) );
	}

	public function ajax_delete_payment() {
		check_ajax_referer( 'wc_team_payroll_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'wc-team-payroll' ) );
		}

		$user_id = intval( $_POST['user_id'] );
		$payment_id = sanitize_text_field( $_POST['payment_id'] );

		$payments = get_user_meta( $user_id, '_wc_tp_payments', true );
		if ( ! is_array( $payments ) ) {
			wp_send_json_error( __( 'No payments found', 'wc-team-payroll' ) );
		}

		$payments = array_filter( $payments, function( $p ) use ( $payment_id ) {
			return $p['id'] !== $payment_id;
		} );

		update_user_meta( $user_id, '_wc_tp_payments', array_values( $payments ) );

		wp_send_json_success( array(
			'message' => __( 'Payment deleted', 'wc-team-payroll' ),
		) );
	}

	public function ajax_get_payment_data() {
		check_ajax_referer( 'wc_team_payroll_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'wc-team-payroll' ) );
		}

		$user_id = intval( $_POST['user_id'] );
		$year = isset( $_POST['year'] ) ? intval( $_POST['year'] ) : date( 'Y' );
		$month = isset( $_POST['month'] ) ? intval( $_POST['month'] ) : date( 'm' );

		$payments = get_user_meta( $user_id, '_wc_tp_payments', true );
		if ( ! is_array( $payments ) ) {
			$payments = array();
		}

		$total_paid = $this->get_user_total_paid( $user_id, $year, $month );

		wp_send_json_success( array(
			'payments'   => $payments,
			'total_paid' => $total_paid,
		) );
	}

	public function ajax_add_order_bonus() {
		check_ajax_referer( 'wc_team_payroll_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'wc-team-payroll' ) );
		}

		$order_id = intval( $_POST['order_id'] );
		$user_id = intval( $_POST['user_id'] );
		$bonus_amount = floatval( $_POST['bonus_amount'] );
		$bonus_reason = sanitize_text_field( $_POST['bonus_reason'] );

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			wp_send_json_error( __( 'Order not found', 'wc-team-payroll' ) );
		}

		$order_bonuses = $order->get_meta( '_wc_tp_order_bonuses' );
		if ( ! is_array( $order_bonuses ) ) {
			$order_bonuses = array();
		}

		$bonus_exists = false;
		foreach ( $order_bonuses as $key => $bonus ) {
			if ( $bonus['user_id'] == $user_id ) {
				$order_bonuses[ $key ] = array(
					'user_id'    => $user_id,
					'amount'     => $bonus_amount,
					'reason'     => $bonus_reason,
					'created_at' => current_time( 'mysql' ),
					'created_by' => get_current_user_id(),
				);
				$bonus_exists = true;
				break;
			}
		}

		if ( ! $bonus_exists ) {
			$order_bonuses[] = array(
				'user_id'    => $user_id,
				'amount'     => $bonus_amount,
				'reason'     => $bonus_reason,
				'created_at' => current_time( 'mysql' ),
				'created_by' => get_current_user_id(),
			);
		}

		$order->update_meta_data( '_wc_tp_order_bonuses', $order_bonuses );
		$order->save();

		wp_send_json_success( array(
			'message' => __( 'Order bonus added for this employee only', 'wc-team-payroll' ),
		) );
	}

	public function get_user_total_paid( $user_id, $year = null, $month = null ) {
		if ( ! $year ) {
			$year = date( 'Y' );
		}
		if ( ! $month ) {
			$month = date( 'm' );
		}

		$payments = get_user_meta( $user_id, '_wc_tp_payments', true );
		if ( ! is_array( $payments ) ) {
			return 0;
		}

		$total_paid = 0;

		foreach ( $payments as $payment ) {
			$payment_date = new DateTime( $payment['date'] );
			$payment_year = $payment_date->format( 'Y' );
			$payment_month = $payment_date->format( 'm' );

			if ( $payment_year == $year && $payment_month == $month ) {
				$total_paid += $payment['amount'];
			}
		}

		return $total_paid;
	}

	public function get_salary_history( $user_id ) {
		$history = get_user_meta( $user_id, '_wc_tp_salary_history', true );
		return is_array( $history ) ? $history : array();
	}

	public function is_fixed_salary( $user_id ) {
		return (bool) get_user_meta( $user_id, '_wc_tp_fixed_salary', true );
	}

	public function is_combined_salary( $user_id ) {
		return (bool) get_user_meta( $user_id, '_wc_tp_combined_salary', true );
	}

	public function get_user_salary( $user_id ) {
		$is_fixed = $this->is_fixed_salary( $user_id );
		$is_combined = $this->is_combined_salary( $user_id );

		if ( ! $is_fixed && ! $is_combined ) {
			return null;
		}

		return array(
			'amount'    => floatval( get_user_meta( $user_id, '_wc_tp_salary_amount', true ) ),
			'frequency' => sanitize_text_field( get_user_meta( $user_id, '_wc_tp_salary_frequency', true ) ),
		);
	}
}
