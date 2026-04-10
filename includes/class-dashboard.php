<?php
/**
 * Admin Dashboard
 */

class WC_Team_Payroll_Dashboard {

	/**
	 * Render dashboard page
	 */
	public function render_dashboard() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized', 'wc-team-payroll' ) );
		}

		// Get date range from request
		$start_date = isset( $_GET['start_date'] ) ? sanitize_text_field( $_GET['start_date'] ) : date( 'Y-m-01' );
		$end_date = isset( $_GET['end_date'] ) ? sanitize_text_field( $_GET['end_date'] ) : date( 'Y-m-t' );

		?>
		<div class="wrap wc-team-payroll-dashboard">
			<h1><?php esc_html_e( 'Team Payroll Dashboard', 'wc-team-payroll' ); ?></h1>

			<!-- Date Range Filter -->
			<div class="wc-tp-date-filter">
				<label><?php esc_html_e( 'Date Range:', 'wc-team-payroll' ); ?></label>
				<input type="date" id="wc-tp-start-date" value="<?php echo esc_attr( $start_date ); ?>" />
				<span class="wc-tp-date-separator">to</span>
				<input type="date" id="wc-tp-end-date" value="<?php echo esc_attr( $end_date ); ?>" />
				<button type="button" class="button button-primary" id="wc-tp-filter-btn"><?php esc_html_e( 'Filter', 'wc-team-payroll' ); ?></button>
			</div>

			<!-- Stats Cards -->
			<div class="wc-tp-stats-grid" id="wc-tp-stats-container">
				<!-- Stats will be loaded via AJAX -->
			</div>

			<!-- Employee Payroll Details (TOP) -->
			<div class="wc-tp-table-section" id="wc-tp-payroll-section">
				<h2><?php esc_html_e( 'Employee Payroll Details', 'wc-team-payroll' ); ?></h2>
				<div id="wc-tp-payroll-container">
					<!-- Content will be loaded via AJAX -->
				</div>
			</div>

			<!-- Two Column Layout -->
			<div class="wc-tp-dashboard-grid">
				<!-- Top Earners -->
				<div class="wc-tp-table-section" id="wc-tp-earners-section">
					<h2><?php esc_html_e( 'Top Earners', 'wc-team-payroll' ); ?></h2>
					<div id="wc-tp-top-earners-container">
						<!-- Content will be loaded via AJAX -->
					</div>
				</div>

				<!-- Recent Payments -->
				<div class="wc-tp-table-section" id="wc-tp-payments-section">
					<h2><?php esc_html_e( 'Recent Payments', 'wc-team-payroll' ); ?></h2>
					<div id="wc-tp-recent-payments-container">
						<!-- Content will be loaded via AJAX -->
					</div>
				</div>
			</div>

			<!-- Latest Employees (10) - BOTTOM -->
			<div class="wc-tp-table-section" id="wc-tp-employees-section">
				<h2><?php esc_html_e( 'Latest Employees', 'wc-team-payroll' ); ?></h2>
				<div id="wc-tp-latest-employees-container">
					<!-- Content will be loaded via AJAX -->
				</div>
			</div>
		</div>

		<style>
			:root {
				/* --- Color Palette --- */
				--color-primary: #FF9900;
				--color-primary-hover: #E68A00;
				--color-primary-subtle: #FFF4E5;
				--color-secondary: #212B36;
				--color-site-bg: #FDFBF8;
				--color-card-bg: #FFFFFF;
				--color-border-light: #E5EAF0;
				--color-accent-alert: #FF5500;
				--color-accent-alert-hover: #D94800;
				--color-accent-link: #0077EE;
				--color-accent-success: #388E3C;
				--color-accent-muted: #F4F4F4;
				--text-main: #212B36;
				--text-body: #454F5B;
				--text-muted: #919EAB;
				--color-link-subtle: #EBF4FF;
				/* --- Typography System --- */
				--font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
				--fs-h1: 2rem;
				--fs-h2: 1.5rem;
				--fs-h3: 1.25rem;
				--fs-body: 1rem;
				--fs-meta: 0.875rem;
				--fs-small: 0.75rem;
				--fw-bold: 700;
				--fw-semibold: 600;
				--fw-medium: 500;
				--fw-regular: 400;
				--lh-body: 1.5;
				--lh-heading: 1.2;
			}

			.wc-team-payroll-dashboard {
				background: var(--color-site-bg);
				padding: 24px;
				font-family: var(--font-family);
				color: var(--text-main);
			}

			.wc-team-payroll-dashboard h1 {
				font-size: var(--fs-h1);
				font-weight: var(--fw-bold);
				color: var(--text-main);
				margin-bottom: 24px;
			}

			.wc-tp-date-filter {
				background: var(--color-card-bg);
				padding: 16px;
				border-radius: 8px;
				margin-bottom: 24px;
				border: 1px solid var(--color-border-light);
				display: flex;
				gap: 12px;
				align-items: center;
				flex-wrap: wrap;
			}

			.wc-tp-date-filter label {
				font-weight: var(--fw-semibold);
				color: var(--text-main);
				font-size: var(--fs-body);
			}

			.wc-tp-date-filter input[type="date"] {
				padding: 8px 12px;
				border: 1px solid var(--color-border-light);
				border-radius: 6px;
				font-size: var(--fs-body);
				font-family: var(--font-family);
				color: var(--text-main);
			}

			.wc-tp-date-separator {
				color: var(--text-muted);
				font-weight: var(--fw-medium);
			}

			.wc-tp-stats-grid {
				display: grid;
				grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
				gap: 16px;
				margin-bottom: 32px;
			}

			.wc-tp-stat-card {
				background: var(--color-card-bg);
				padding: 20px;
				border-radius: 8px;
				border: 1px solid var(--color-border-light);
				display: flex;
				align-items: center;
				gap: 16px;
				transition: all 0.3s ease;
				cursor: pointer;
			}

			.wc-tp-stat-link {
				text-decoration: none;
				color: inherit;
			}

			.wc-tp-stat-link:hover {
				text-decoration: none;
				color: inherit;
			}

			.wc-tp-stat-card:hover {
				border-color: var(--color-primary);
				box-shadow: 0 4px 12px rgba(255, 153, 0, 0.1);
				transform: translateY(-2px);
			}

			.wc-tp-stat-icon {
				font-size: 32px;
				min-width: 50px;
				text-align: center;
			}

			.wc-tp-stat-content {
				flex: 1;
			}

			.wc-tp-stat-value {
				font-size: var(--fs-h2);
				font-weight: var(--fw-bold);
				color: var(--color-primary);
				margin-bottom: 4px;
				line-height: var(--lh-heading);
			}

			.wc-tp-stat-label {
				font-size: var(--fs-meta);
				color: var(--text-muted);
				text-transform: uppercase;
				letter-spacing: 0.5px;
				font-weight: var(--fw-medium);
			}

			.wc-tp-dashboard-grid {
				display: grid;
				grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
				gap: 20px;
				margin-bottom: 20px;
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
				padding: 12px;
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

			.wc-tp-paid {
				color: var(--color-accent-success);
				font-weight: var(--fw-semibold);
			}

			.wc-tp-due {
				color: var(--color-accent-alert);
				font-weight: var(--fw-semibold);
			}

			.wc-tp-status {
				padding: 4px 8px;
				border-radius: 4px;
				font-size: var(--fs-small);
				font-weight: var(--fw-semibold);
				display: inline-block;
			}

			.wc-tp-status-paid {
				background: #D4EDDA;
				color: #155724;
			}

			.wc-tp-status-pending {
				background: #FFF3CD;
				color: #856404;
			}

			.wc-tp-status-failed {
				background: #F8D7DA;
				color: #721C24;
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

			.wc-tp-quick-edit,
			.wc-tp-edit-payment {
				background: var(--color-accent-link);
				border-color: var(--color-accent-link);
				color: white;
				padding: 4px 8px;
				font-size: 12px;
				height: auto;
				line-height: 1.5;
			}

			.wc-tp-quick-edit:hover,
			.wc-tp-edit-payment:hover {
				background: #0066cc;
				border-color: #0066cc;
			}

			@media (max-width: 768px) {
				.wc-tp-date-filter {
					flex-direction: column;
					align-items: flex-start;
				}

				.wc-tp-dashboard-grid {
					grid-template-columns: 1fr;
				}

				.wc-tp-data-table {
					font-size: var(--fs-meta);
				}

				.wc-tp-data-table th,
				.wc-tp-data-table td {
					padding: 8px;
				}
			}
		</style>

		<script>
			jQuery(document).ready(function($) {
				// Store currency globally
				let wcCurrency = 'USD';

				// Load dashboard data on page load
				loadDashboardData();

				// Filter button click
				$('#wc-tp-filter-btn').on('click', function() {
					loadDashboardData();
				});

				// Load all dashboard data via AJAX
				function loadDashboardData() {
					const startDate = $('#wc-tp-start-date').val();
					const endDate = $('#wc-tp-end-date').val();

					if (!startDate || !endDate) {
						alert('Please select both start and end dates');
						return;
					}

					// Show loading state
					$('#wc-tp-filter-btn').prop('disabled', true).text('Loading...');

					// AJAX request
					$.ajax({
						url: ajaxurl,
						type: 'POST',
						data: {
							action: 'wc_tp_get_dashboard_data',
							start_date: startDate,
							end_date: endDate
						},
						success: function(response) {
							if (response.success) {
								const data = response.data;
								
								// Store currency
								wcCurrency = data.currency || 'USD';
								
								// Update stat cards
								renderStatCards(data);
								
								// Update all tables
								renderLatestEmployees(data.latest_employees);
								renderTopEarners(data.top_earners);
								renderRecentPayments(data.recent_payments);
								renderPayrollTable(data.payroll);
								
								// Show success message
								showNotice('Dashboard updated successfully', 'success');
							} else {
								showNotice('Error: ' + response.data, 'error');
							}
						},
						error: function() {
							showNotice('Error loading dashboard data', 'error');
						},
						complete: function() {
							$('#wc-tp-filter-btn').prop('disabled', false).text('Filter');
						}
					});
				}

				// Render stat cards
				function renderStatCards(data) {
					const container = $('#wc-tp-stats-container');
					let html = '';

					// Total Employees - Link to Employees Section
					html += '<a href="#wc-tp-employees-section" class="wc-tp-stat-card wc-tp-stat-link">';
					html += '<div class="wc-tp-stat-icon">👥</div>';
					html += '<div class="wc-tp-stat-content">';
					html += '<div class="wc-tp-stat-value">' + data.total_employees + '</div>';
					html += '<div class="wc-tp-stat-label">Total Employees</div>';
					html += '</div></a>';

					// Total Orders - Link to Payroll Section
					html += '<a href="#wc-tp-payroll-section" class="wc-tp-stat-card wc-tp-stat-link">';
					html += '<div class="wc-tp-stat-icon">📦</div>';
					html += '<div class="wc-tp-stat-content">';
					html += '<div class="wc-tp-stat-value">' + data.total_orders + '</div>';
					html += '<div class="wc-tp-stat-label">Total Orders</div>';
					html += '</div></a>';

					// Total Earnings - Link to Earners Section
					html += '<a href="#wc-tp-earners-section" class="wc-tp-stat-card wc-tp-stat-link">';
					html += '<div class="wc-tp-stat-icon">💰</div>';
					html += '<div class="wc-tp-stat-content">';
					html += '<div class="wc-tp-stat-value">' + formatCurrency(data.total_earnings) + '</div>';
					html += '<div class="wc-tp-stat-label">Total Earnings</div>';
					html += '</div></a>';

					// Total Paid - Link to Payments Section
					html += '<a href="#wc-tp-payments-section" class="wc-tp-stat-card wc-tp-stat-link">';
					html += '<div class="wc-tp-stat-icon">✅</div>';
					html += '<div class="wc-tp-stat-content">';
					html += '<div class="wc-tp-stat-value">' + formatCurrency(data.total_paid) + '</div>';
					html += '<div class="wc-tp-stat-label">Total Paid</div>';
					html += '</div></a>';

					// Total Due - Link to Payroll Section
					html += '<a href="#wc-tp-payroll-section" class="wc-tp-stat-card wc-tp-stat-link">';
					html += '<div class="wc-tp-stat-icon">⏳</div>';
					html += '<div class="wc-tp-stat-content">';
					html += '<div class="wc-tp-stat-value">' + formatCurrency(data.total_due) + '</div>';
					html += '<div class="wc-tp-stat-label">Total Due</div>';
					html += '</div></a>';

					container.html(html);
				}

				// Render latest employees table
				function renderLatestEmployees(employees) {
					const container = $('#wc-tp-latest-employees-container');
					
					if (!employees || employees.length === 0) {
						container.html('<div class="wc-tp-empty-state"><div class="wc-tp-empty-icon">👥</div><p>No employees yet</p></div>');
						return;
					}

					let html = '<table class="wc-tp-data-table wc-tp-sortable"><thead><tr>';
					html += '<th class="wc-tp-sortable-header" data-sort="display_name">Name</th>';
					html += '<th class="wc-tp-sortable-header" data-sort="user_email">Email</th>';
					html += '<th class="wc-tp-sortable-header" data-sort="type">Type</th>';
					html += '<th>Salary/Commission</th>';
					html += '<th>Action</th>';
					html += '</tr></thead><tbody>';

					$.each(employees, function(i, emp) {
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
					attachSortHandlers(container, employees, ['display_name', 'user_email', 'type']);
				}

				// Render top earners table
				function renderTopEarners(earners) {
					const container = $('#wc-tp-top-earners-container');
					
					if (!earners || earners.length === 0) {
						container.html('<div class="wc-tp-empty-state"><div class="wc-tp-empty-icon">💰</div><p>No earnings data</p></div>');
						return;
					}

					let html = '<table class="wc-tp-data-table wc-tp-sortable"><thead><tr>';
					html += '<th class="wc-tp-sortable-header" data-sort="name">Employee</th>';
					html += '<th class="wc-tp-sortable-header" data-sort="earnings">Earnings</th>';
					html += '<th class="wc-tp-sortable-header" data-sort="orders">Orders</th>';
					html += '</tr></thead><tbody>';

					$.each(earners, function(i, earner) {
						html += '<tr>';
						html += '<td><strong>' + earner.name + '</strong></td>';
						html += '<td>' + formatCurrency(earner.earnings) + '</td>';
						html += '<td><span class="wc-tp-badge">' + earner.orders + '</span></td>';
						html += '</tr>';
					});

					html += '</tbody></table>';
					container.html(html);
					attachSortHandlers(container, earners, ['name', 'earnings', 'orders']);
				}

				// Render recent payments table
				function renderRecentPayments(payments) {
					const container = $('#wc-tp-recent-payments-container');
					
					if (!payments || payments.length === 0) {
						container.html('<div class="wc-tp-empty-state"><div class="wc-tp-empty-icon">💳</div><p>No payments yet</p></div>');
						return;
					}

					let html = '<table class="wc-tp-data-table wc-tp-sortable"><thead><tr>';
					html += '<th class="wc-tp-sortable-header" data-sort="employee_name">Employee</th>';
					html += '<th class="wc-tp-sortable-header" data-sort="amount">Amount</th>';
					html += '<th class="wc-tp-sortable-header" data-sort="date">Date</th>';
					html += '<th class="wc-tp-sortable-header" data-sort="status">Status</th>';
					html += '<th>Action</th>';
					html += '</tr></thead><tbody>';

					$.each(payments, function(i, payment) {
						const statusClass = 'wc-tp-status-' + payment.status;
						html += '<tr>';
						html += '<td><strong>' + payment.employee_name + '</strong></td>';
						html += '<td>' + formatCurrency(payment.amount) + '</td>';
						html += '<td>' + payment.date + '</td>';
						html += '<td><span class="wc-tp-status ' + statusClass + '">' + payment.status.charAt(0).toUpperCase() + payment.status.slice(1) + '</span></td>';
						html += '<td><button class="button button-small wc-tp-edit-payment" data-payment-id="' + payment.user_id + '" data-amount="' + payment.amount + '" data-date="' + payment.date + '" title="Edit Payment">✎</button></td>';
						html += '</tr>';
					});

					html += '</tbody></table>';
					container.html(html);
					attachSortHandlers(container, payments, ['employee_name', 'amount', 'date', 'status']);
				}

				// Render payroll table
				function renderPayrollTable(payroll) {
					const container = $('#wc-tp-payroll-container');
					
					if (!payroll || Object.keys(payroll).length === 0) {
						container.html('<div class="wc-tp-empty-state"><div class="wc-tp-empty-icon">📊</div><p>No payroll data for this period</p></div>');
						return;
					}

					let html = '<table class="wc-tp-data-table wc-tp-sortable"><thead><tr>';
					html += '<th class="wc-tp-sortable-header" data-sort="name">Employee</th>';
					html += '<th class="wc-tp-sortable-header" data-sort="user_email">Email</th>';
					html += '<th class="wc-tp-sortable-header" data-sort="orders">Orders</th>';
					html += '<th class="wc-tp-sortable-header" data-sort="total">Total Earnings</th>';
					html += '<th class="wc-tp-sortable-header" data-sort="paid">Paid</th>';
					html += '<th class="wc-tp-sortable-header" data-sort="due">Due</th>';
					html += '<th>Action</th>';
					html += '</tr></thead><tbody>';

					let payrollArray = [];
					$.each(payroll, function(userId, data) {
						payrollArray.push({
							userId: userId,
							name: data.name,
							user_email: data.user_email || 'N/A',
							orders: data.orders,
							total: data.total,
							paid: data.paid,
							due: data.due,
							user: data.user
						});
					});

					$.each(payrollArray, function(i, data) {
						html += '<tr>';
						html += '<td><strong>' + data.name + '</strong></td>';
						html += '<td>' + data.user_email + '</td>';
						html += '<td><span class="wc-tp-badge">' + data.orders + '</span></td>';
						html += '<td>' + formatCurrency(data.total) + '</td>';
						html += '<td><span class="wc-tp-paid">' + formatCurrency(data.paid) + '</span></td>';
						html += '<td><span class="wc-tp-due">' + formatCurrency(data.due) + '</span></td>';
						html += '<td><a href="' + ajaxurl.replace('admin-ajax.php', 'admin.php?page=wc-team-payroll-employee-detail&user_id=' + data.userId) + '" class="button button-small button-primary">View</a> <button class="button button-small wc-tp-quick-edit" data-user-id="' + data.userId + '" title="Quick Edit">✎</button></td>';
						html += '</tr>';
					});

					html += '</tbody></table>';
					container.html(html);
					attachSortHandlers(container, payrollArray, ['name', 'user_email', 'orders', 'total', 'paid', 'due']);
				}

				// Attach sort handlers to table headers
				function attachSortHandlers(container, data, sortableFields) {
					// Get or initialize sort state from container data
					let currentSort = container.data('sortState') || { field: null, direction: 'asc' };
					
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
						
						const isNumeric = ['orders', 'total', 'paid', 'due', 'earnings', 'amount'].includes(sortField);
						const isDate = ['date'].includes(sortField);
						
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
						
						// Remove active class from all headers in this container
						container.find('.wc-tp-sortable-header').removeClass('wc-tp-sort-active wc-tp-sort-asc wc-tp-sort-desc');
						
						// Add active class and direction class to clicked header
						$(this).addClass('wc-tp-sort-active');
						if (currentSort.direction === 'asc') {
							$(this).addClass('wc-tp-sort-asc');
						} else {
							$(this).addClass('wc-tp-sort-desc');
						}
						
						// Sort data
						let sortedData = [...data].sort((a, b) => {
							let aVal = a[sortField];
							let bVal = b[sortField];
							
							if (aVal === undefined || aVal === null) aVal = '';
							if (bVal === undefined || bVal === null) bVal = '';
							
							if (isDate) {
								// Parse date/time values
								let aTime = 0;
								let bTime = 0;
								
								// Try to parse as timestamp if available
								if (a[sortField + '_timestamp']) {
									aTime = a[sortField + '_timestamp'];
								} else if (a[sortField]) {
									// Try to parse date string
									aTime = new Date(a[sortField]).getTime() || 0;
								}
								
								if (b[sortField + '_timestamp']) {
									bTime = b[sortField + '_timestamp'];
								} else if (b[sortField]) {
									// Try to parse date string
									bTime = new Date(b[sortField]).getTime() || 0;
								}
								
								return currentSort.direction === 'asc' ? aTime - bTime : bTime - aTime;
							} else if (isNumeric) {
								aVal = parseFloat(aVal) || 0;
								bVal = parseFloat(bVal) || 0;
								return currentSort.direction === 'asc' ? aVal - bVal : bVal - aVal;
							} else {
								aVal = String(aVal).toLowerCase();
								bVal = String(bVal).toLowerCase();
								return currentSort.direction === 'asc' ? aVal.localeCompare(bVal) : bVal.localeCompare(aVal);
							}
						});
						
						// Re-render table with sorted data based on container ID
						if (container.attr('id') === 'wc-tp-payroll-container') {
							renderPayrollTable(sortedData);
						} else if (container.attr('id') === 'wc-tp-top-earners-container') {
							renderTopEarners(sortedData);
						} else if (container.attr('id') === 'wc-tp-recent-payments-container') {
							renderRecentPayments(sortedData);
						} else if (container.attr('id') === 'wc-tp-latest-employees-container') {
							renderLatestEmployees(sortedData);
						}
					});
				}

				// Format currency
				function formatCurrency(value) {
					return new Intl.NumberFormat('en-US', {
						style: 'currency',
						currency: wcCurrency
					}).format(value);
				}

				// Show notice
				function showNotice(message, type) {
					const noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
					const notice = $('<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>');
					$('.wrap').prepend(notice);
					setTimeout(function() {
						notice.fadeOut(function() { $(this).remove(); });
					}, 3000);
				}

				// Quick edit payroll
				$(document).on('click', '.wc-tp-quick-edit', function() {
					const userId = $(this).data('user-id');
					const row = $(this).closest('tr');
					const paidCell = row.find('td:eq(4)');
					const dueCell = row.find('td:eq(5)');
					
					const currentPaid = parseFloat(paidCell.text().replace(/[^0-9.-]/g, ''));
					const currentDue = parseFloat(dueCell.text().replace(/[^0-9.-]/g, ''));
					
					const newPaid = prompt('Enter new Paid amount:', currentPaid);
					if (newPaid === null) return;
					
					const newDue = prompt('Enter new Due amount:', currentDue);
					if (newDue === null) return;
					
					// Update via AJAX
					$.ajax({
						url: ajaxurl,
						type: 'POST',
						data: {
							action: 'wc_tp_update_payroll',
							user_id: userId,
							paid: parseFloat(newPaid),
							due: parseFloat(newDue),
							nonce: wcTeamPayrollNonce
						},
						success: function(response) {
							if (response.success) {
								showNotice('Payroll updated successfully', 'success');
								loadDashboardData();
							} else {
								showNotice('Error: ' + response.data, 'error');
							}
						},
						error: function() {
							showNotice('Error updating payroll', 'error');
						}
					});
				});

				// Quick edit payment
				$(document).on('click', '.wc-tp-edit-payment', function() {
					const userId = $(this).data('payment-id');
					const amount = $(this).data('amount');
					const date = $(this).data('date');
					
					const newAmount = prompt('Enter new amount:', amount);
					if (newAmount === null) return;
					
					const newDate = prompt('Enter new date (YYYY-MM-DD):', date);
					if (newDate === null) return;
					
					// Update via AJAX
					$.ajax({
						url: ajaxurl,
						type: 'POST',
						data: {
							action: 'wc_tp_update_payment',
							user_id: userId,
							amount: parseFloat(newAmount),
							date: newDate,
							nonce: wcTeamPayrollNonce
						},
						success: function(response) {
							if (response.success) {
								showNotice('Payment updated successfully', 'success');
								loadDashboardData();
							} else {
								showNotice('Error: ' + response.data, 'error');
							}
						},
						error: function() {
							showNotice('Error updating payment', 'error');
						}
					});
				});
			});
		</script>
		<?php
	}

	/**
	 * Render payroll page
	 */
	public function render_payroll() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized', 'wc-team-payroll' ) );
		}

		$year = isset( $_GET['year'] ) ? intval( $_GET['year'] ) : date( 'Y' );
		$month = isset( $_GET['month'] ) ? intval( $_GET['month'] ) : date( 'm' );

		// Get payroll data
		$payroll = array();
		if ( class_exists( 'WC_Team_Payroll_Payroll_Engine' ) ) {
			$payroll = WC_Team_Payroll_Payroll_Engine::get_monthly_payroll( $year, $month );
		}

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Monthly Payroll', 'wc-team-payroll' ); ?></h1>

			<div class="wc-team-payroll-filters">
				<form method="get">
					<input type="hidden" name="page" value="wc-team-payroll-payroll" />
					<select name="month">
						<?php for ( $m = 1; $m <= 12; $m++ ) : ?>
							<option value="<?php echo esc_attr( $m ); ?>" <?php selected( $month, $m ); ?>><?php echo esc_html( date( 'F', mktime( 0, 0, 0, $m, 1 ) ) ); ?></option>
						<?php endfor; ?>
					</select>
					<input type="number" name="year" value="<?php echo esc_attr( $year ); ?>" min="2020" max="2099" />
					<button type="submit" class="button"><?php esc_html_e( 'Filter', 'wc-team-payroll' ); ?></button>
				</form>
			</div>

			<?php if ( empty( $payroll ) ) : ?>
				<div class="notice notice-info"><p><?php esc_html_e( 'No payroll data for this period.', 'wc-team-payroll' ); ?></p></div>
			<?php else : ?>
				<table class="widefat striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Employee', 'wc-team-payroll' ); ?></th>
							<th><?php esc_html_e( 'Total Earnings', 'wc-team-payroll' ); ?></th>
							<th><?php esc_html_e( 'Paid', 'wc-team-payroll' ); ?></th>
							<th><?php esc_html_e( 'Due', 'wc-team-payroll' ); ?></th>
							<th><?php esc_html_e( 'Action', 'wc-team-payroll' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $payroll as $data ) : ?>
							<tr>
								<td><?php echo esc_html( $data['user'] ? $data['user']->display_name : 'Unknown' ); ?></td>
								<td><?php echo wp_kses_post( wc_price( $data['total'] ) ); ?></td>
								<td><?php echo wp_kses_post( wc_price( $data['paid'] ) ); ?></td>
								<td><?php echo wp_kses_post( wc_price( $data['due'] ) ); ?></td>
								<td>
									<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'wc-team-payroll-employee-detail', 'user_id' => $data['user_id'] ), admin_url( 'admin.php' ) ) ); ?>" class="button button-small"><?php esc_html_e( 'View', 'wc-team-payroll' ); ?></a>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}
}
