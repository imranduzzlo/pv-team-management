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

		$year = isset( $_GET['year'] ) ? intval( $_GET['year'] ) : date( 'Y' );
		$month = isset( $_GET['month'] ) ? intval( $_GET['month'] ) : date( 'm' );

		?>
		<div class="wrap wc-team-payroll-payroll">
			<h1><?php esc_html_e( 'Payroll', 'wc-team-payroll' ); ?></h1>

			<!-- Date Range Filter -->
			<div class="wc-tp-date-filter">
				<label><?php esc_html_e( 'Month:', 'wc-team-payroll' ); ?></label>
				<select id="wc-tp-payroll-month">
					<?php for ( $m = 1; $m <= 12; $m++ ) : ?>
						<option value="<?php echo esc_attr( $m ); ?>" <?php selected( $month, $m ); ?>><?php echo esc_html( date( 'F', mktime( 0, 0, 0, $m, 1 ) ) ); ?></option>
					<?php endfor; ?>
				</select>
				<label><?php esc_html_e( 'Year:', 'wc-team-payroll' ); ?></label>
				<input type="number" id="wc-tp-payroll-year" value="<?php echo esc_attr( $year ); ?>" min="2020" max="2099" />
				<button type="button" class="button button-primary" id="wc-tp-payroll-filter-btn"><?php esc_html_e( 'Filter', 'wc-team-payroll' ); ?></button>
			</div>

			<!-- Stats Cards -->
			<div class="wc-tp-stats-grid" id="wc-tp-payroll-stats-container">
				<!-- Stats will be loaded via AJAX -->
			</div>

			<!-- Payroll Table Section -->
			<div class="wc-tp-table-section" id="wc-tp-payroll-table-section">
				<h2><?php esc_html_e( 'Payroll Details', 'wc-team-payroll' ); ?></h2>
				<div id="wc-tp-payroll-table-container">
					<!-- Content will be loaded via AJAX -->
				</div>
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
				--color-accent-alert-hover: #D94800;
				--color-accent-link: #0077EE;
				--color-accent-success: #388E3C;
				--color-accent-muted: #F4F4F4;
				--text-main: #212B36;
				--text-body: #454F5B;
				--text-muted: #919EAB;
				--color-link-subtle: #EBF4FF;
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

			.wc-tp-date-filter input[type="number"],
			.wc-tp-date-filter select {
				padding: 8px 12px;
				border: 1px solid var(--color-border-light);
				border-radius: 6px;
				font-size: var(--fs-body);
				font-family: var(--font-family);
				color: var(--text-main);
			}

			.wc-tp-stats-grid {
				display: grid;
				grid-template-columns: repeat(4, 1fr);
				gap: 16px;
				margin-bottom: 32px;
			}

			.wc-tp-stat-card {
				background: var(--color-card-bg);
				padding: 20px;
				border-radius: 8px;
				border: 1px solid var(--color-border-light);
				display: flex;
				flex-direction: row;
				align-items: center;
				justify-content: flex-start;
				gap: 16px;
				transition: all 0.3s ease;
				cursor: pointer;
				min-height: 120px;
				text-align: left;
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
				flex-shrink: 0;
			}

			.wc-tp-stat-content {
				flex: 1;
			}

			.wc-tp-stat-value {
				font-size: 1.5rem;
				font-weight: var(--fw-bold);
				color: var(--color-primary);
				margin-bottom: 4px;
				line-height: 1.3;
				word-break: keep-all;
				white-space: nowrap;
				overflow: hidden;
				text-overflow: ellipsis;
			}

			.wc-tp-stat-label {
				font-size: var(--fs-meta);
				color: var(--text-muted);
				text-transform: uppercase;
				letter-spacing: 0.5px;
				font-weight: var(--fw-medium);
				word-break: keep-all;
				white-space: normal;
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

			@media (max-width: 1024px) {
				.wc-tp-stats-grid {
					grid-template-columns: repeat(2, 1fr);
				}

				.wc-tp-data-table {
					font-size: 13px;
				}

				.wc-tp-data-table th,
				.wc-tp-data-table td {
					padding: 8px;
				}

				.button-small {
					padding: 4px 8px;
					font-size: 11px;
				}
			}

			@media (max-width: 768px) {
				.wc-team-payroll-payroll {
					padding: 12px;
				}

				.wc-tp-stats-grid {
					grid-template-columns: 1fr;
				}

				.wc-tp-stat-card {
					flex-direction: column;
					text-align: center;
					align-items: center;
					justify-content: center;
					padding: 15px;
					gap: 8px;
					min-height: 100px;
				}

				.wc-tp-stat-icon {
					font-size: 28px;
					min-width: auto;
				}

				.wc-tp-stat-value {
					font-size: 1.25rem;
				}

				.wc-tp-date-filter {
					flex-direction: column;
					gap: 8px;
				}

				.wc-tp-date-filter input[type="number"],
				.wc-tp-date-filter select {
					width: 100%;
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

			@media (max-width: 480px) {
				.wc-team-payroll-payroll {
					padding: 8px;
				}

				.wc-tp-date-filter {
					gap: 6px;
				}

				.wc-tp-stats-grid {
					gap: 8px;
				}

				.wc-tp-stat-card {
					padding: 10px;
					gap: 6px;
					min-height: 90px;
				}

				.wc-tp-stat-icon {
					font-size: 24px;
				}

				.wc-tp-stat-value {
					font-size: 1.1rem;
				}

				.wc-tp-stat-label {
					font-size: 0.7rem;
				}

				.wc-tp-table-section {
					padding: 8px;
					margin-bottom: 8px;
				}

				.wc-tp-table-section h2 {
					font-size: 1rem;
					margin-bottom: 8px;
				}

				.wc-tp-data-table {
					font-size: 11px;
				}

				.wc-tp-data-table th,
				.wc-tp-data-table td {
					padding: 4px;
				}

				.button,
				.button-small {
					padding: 3px 5px;
					font-size: 9px;
				}
			}
		</style>

		<script>
			jQuery(document).ready(function($) {
				let wcCurrency = 'USD';
				let wcCurrencySymbol = '$';
				let wcCurrencyPos = 'left';

				loadPayrollData();

				$('#wc-tp-payroll-filter-btn').on('click', function() {
					loadPayrollData();
				});

				function loadPayrollData() {
					const month = $('#wc-tp-payroll-month').val();
					const year = $('#wc-tp-payroll-year').val();

					if (!month || !year) {
						alert('Please select month and year');
						return;
					}

					$('#wc-tp-payroll-filter-btn').prop('disabled', true).text('Loading...');

					$.ajax({
						url: ajaxurl,
						type: 'POST',
						data: {
							action: 'wc_tp_get_payroll_data',
							month: month,
							year: year
						},
						success: function(response) {
							if (response.success) {
								const data = response.data;
								
								wcCurrency = data.currency || 'USD';
								wcCurrencySymbol = data.currency_symbol || '$';
								wcCurrencyPos = data.currency_pos || 'left';
								
								renderPayrollStats(data);
								renderPayrollTableData(data.payroll);
								
								showNotice('Payroll data loaded successfully', 'success');
							} else {
								showNotice('Error: ' + response.data, 'error');
							}
						},
						error: function() {
							showNotice('Error loading payroll data', 'error');
						},
						complete: function() {
							$('#wc-tp-payroll-filter-btn').prop('disabled', false).text('Filter');
						}
					});
				}

				function renderPayrollStats(data) {
					const container = $('#wc-tp-payroll-stats-container');
					let html = '';

					html += '<div class="wc-tp-stat-card"><div class="wc-tp-stat-icon">👥</div><div class="wc-tp-stat-content"><div class="wc-tp-stat-value">' + data.total_employees + '</div><div class="wc-tp-stat-label">Total Employees</div></div></div>';
					html += '<div class="wc-tp-stat-card"><div class="wc-tp-stat-icon">📦</div><div class="wc-tp-stat-content"><div class="wc-tp-stat-value">' + data.total_orders + '</div><div class="wc-tp-stat-label">Total Orders</div></div></div>';
					html += '<div class="wc-tp-stat-card"><div class="wc-tp-stat-icon">💰</div><div class="wc-tp-stat-content"><div class="wc-tp-stat-value">' + formatCurrency(data.total_earnings) + '</div><div class="wc-tp-stat-label">Total Revenue</div></div></div>';
					html += '<div class="wc-tp-stat-card"><div class="wc-tp-stat-icon">⏳</div><div class="wc-tp-stat-content"><div class="wc-tp-stat-value">' + formatCurrency(data.total_due) + '</div><div class="wc-tp-stat-label">Total Due</div></div></div>';

					container.html(html);
				}

				function renderPayrollTableData(payroll) {
					const container = $('#wc-tp-payroll-table-container');
					
					if (!payroll || Object.keys(payroll).length === 0) {
						container.html('<div class="wc-tp-empty-state"><div class="wc-tp-empty-icon">📊</div><p>No payroll data for this period</p></div>');
						return;
					}

					let html = '<table class="wc-tp-data-table wc-tp-sortable"><thead><tr>';
					html += '<th class="wc-tp-sortable-header" data-sort="name">Employee</th>';
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
							orders: data.orders,
							total: data.total,
							paid: data.paid,
							due: data.due
						});
					});

					$.each(payrollArray, function(i, data) {
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
					attachSortHandlers(container, payrollArray, ['name', 'orders', 'total', 'paid', 'due']);
				}

				function attachSortHandlers(container, data, sortableFields) {
					let currentSort = container.data('sortState') || { field: null, direction: 'asc' };
					
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
						
						if (currentSort.field === sortField) {
							currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
						} else {
							currentSort.field = sortField;
							currentSort.direction = 'asc';
						}
						
						container.data('sortState', currentSort);
						
						container.find('.wc-tp-sortable-header').removeClass('wc-tp-sort-active wc-tp-sort-asc wc-tp-sort-desc');
						
						$(this).addClass('wc-tp-sort-active');
						if (currentSort.direction === 'asc') {
							$(this).addClass('wc-tp-sort-asc');
						} else {
							$(this).addClass('wc-tp-sort-desc');
						}
						
						let sortedData = [...data].sort((a, b) => {
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
						
						renderPayrollTableData(sortedData);
						setTimeout(function() {
							attachSortHandlers(container, sortedData, sortableFields);
						}, 10);
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

				function showNotice(message, type) {
					const noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
					const notice = $('<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>');
					$('.wrap').prepend(notice);
					setTimeout(function() {
						notice.fadeOut(function() { $(this).remove(); });
					}, 3000);
				}
			});
		</script>
		<?php
	}
}
