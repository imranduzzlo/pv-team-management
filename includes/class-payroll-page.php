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
				<input type="text" id="wc-tp-payroll-search" placeholder="<?php esc_attr_e( 'Search by name, vb_user_id, user ID, email, or phone...', 'wc-team-payroll' ); ?>" />
				<button type="button" class="button button-secondary" id="wc-tp-payroll-search-clear"><?php esc_html_e( 'Clear', 'wc-team-payroll' ); ?></button>
			</div>

			<!-- Date Range Filter -->
			<div class="wc-tp-date-filter">
				<label><?php esc_html_e( 'Date Range:', 'wc-team-payroll' ); ?></label>
				<input type="date" id="wc-tp-payroll-start-date" value="<?php echo esc_attr( $start_date ); ?>" />
				<span class="wc-tp-date-separator">to</span>
				<input type="date" id="wc-tp-payroll-end-date" value="<?php echo esc_attr( $end_date ); ?>" />
				<button type="button" class="button button-primary" id="wc-tp-payroll-filter-btn"><?php esc_html_e( 'Filter', 'wc-team-payroll' ); ?></button>
			</div>

			<!-- Payroll Table Section -->
			<div class="wc-tp-table-section" id="wc-tp-payroll-table-section">
				<h2><?php esc_html_e( 'Payroll Details', 'wc-team-payroll' ); ?></h2>
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

				.wc-tp-date-filter {
					flex-direction: column;
					gap: 8px;
				}

				.wc-tp-date-filter input[type="date"] {
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
		</style>

		<script>
			jQuery(document).ready(function($) {
				let wcCurrency = 'USD';
				let wcCurrencySymbol = '$';
				let wcCurrencyPos = 'left';
				let currentPage = 1;
				let allPayrollData = [];
				let searchQuery = '';

				loadPayrollData();

				$('#wc-tp-payroll-filter-btn').on('click', function() {
					currentPage = 1;
					loadPayrollData();
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
					const startDate = $('#wc-tp-payroll-start-date').val();
					const endDate = $('#wc-tp-payroll-end-date').val();

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
							search_query: searchQuery
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

				function renderPayrollTable(payroll) {
					const container = $('#wc-tp-payroll-table-container');
					
					if (!payroll || Object.keys(payroll).length === 0) {
						container.html('<div class="wc-tp-empty-state"><div class="wc-tp-empty-icon">📊</div><p>No payroll data for this period</p></div>');
						return;
					}

					const itemsPerPage = 30;
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
				}

				function renderPagination(payroll) {
					const container = $('#wc-tp-payroll-pagination');
					const itemsPerPage = 30;
					let payrollArray = Object.keys(payroll).length;
					const totalPages = Math.ceil(payrollArray / itemsPerPage);

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
