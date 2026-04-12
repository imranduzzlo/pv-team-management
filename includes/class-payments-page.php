<?php
/**
 * Payments Page - All Payments with Filtering and Sorting
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Team_Payroll_Payments_Page {

	/**
	 * Render payments page
	 */
	public function render_payments() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized', 'wc-team-payroll' ) );
		}

		?>
		<div class="wrap wc-team-payroll-payments">
			<h1><?php esc_html_e( 'All Payments', 'wc-team-payroll' ); ?></h1>

			<!-- Add Payment Section -->
			<div class="wc-tp-add-payment-section">
				<h2><?php esc_html_e( 'Add New Payment', 'wc-team-payroll' ); ?></h2>
				<form id="wc-tp-add-payment-form" class="wc-tp-add-payment-form">
					<div class="wc-tp-form-row">
						<div class="wc-tp-form-group">
							<label for="wc-tp-payment-employee"><?php esc_html_e( 'Employee', 'wc-team-payroll' ); ?></label>
							<select id="wc-tp-payment-employee" required>
								<option value=""><?php esc_html_e( 'Select Employee', 'wc-team-payroll' ); ?></option>
								<?php
								$employees = get_users( array(
									'role__in' => array( 'shop_employee', 'shop_manager', 'administrator' ),
									'orderby'  => 'display_name',
									'order'    => 'ASC',
								) );
								foreach ( $employees as $employee ) {
									$vb_user_id = get_user_meta( $employee->ID, 'vb_user_id', true );
									$display_name = $vb_user_id ? esc_html( $vb_user_id ) . ' ' . esc_html( $employee->display_name ) : esc_html( $employee->display_name );
									echo '<option value="' . esc_attr( $employee->ID ) . '">' . $display_name . '</option>';
								}
								?>
							</select>
						</div>

						<div class="wc-tp-form-group">
							<label for="wc-tp-payment-amount"><?php esc_html_e( 'Amount', 'wc-team-payroll' ); ?></label>
							<input type="number" id="wc-tp-payment-amount" placeholder="0.00" step="0.01" min="0" required />
						</div>

						<div class="wc-tp-form-group">
							<label for="wc-tp-payment-date"><?php esc_html_e( 'Payment Date', 'wc-team-payroll' ); ?></label>
							<input type="datetime-local" id="wc-tp-payment-date" value="<?php echo esc_attr( date( 'Y-m-d\TH:i' ) ); ?>" required />
						</div>

						<div class="wc-tp-form-group">
							<label for="wc-tp-payment-method"><?php esc_html_e( 'Payment Method', 'wc-team-payroll' ); ?></label>
							<select id="wc-tp-payment-method">
								<option value=""><?php esc_html_e( 'Select Method', 'wc-team-payroll' ); ?></option>
								<!-- Will be populated via AJAX -->
							</select>
						</div>

						<div class="wc-tp-form-group">
							<label for="wc-tp-payment-note"><?php esc_html_e( 'Note (Optional)', 'wc-team-payroll' ); ?></label>
							<input type="text" id="wc-tp-payment-note" />
						</div>

						<div class="wc-tp-form-group">
							<button type="submit" class="button button-primary" id="wc-tp-add-payment-btn"><?php esc_html_e( 'Add Payment', 'wc-team-payroll' ); ?></button>
						</div>
					</div>
				</form>
				<?php wp_nonce_field( 'wc_team_payroll_nonce', 'wc_team_payroll_nonce' ); ?>
			</div>

			<!-- Search Filter -->
			<div class="wc-tp-search-filter">
				<input type="text" id="wc-tp-payments-search" placeholder="<?php esc_attr_e( 'Search by Employee Name, ID, Email, Phone...', 'wc-team-payroll' ); ?>" />
				<button type="button" class="button button-secondary" id="wc-tp-payments-search-clear"><?php esc_html_e( 'Clear', 'wc-team-payroll' ); ?></button>
			</div>

			<!-- Unified Filter Section -->
			<div class="wc-tp-unified-filter">
				<div class="wc-tp-filter-row">
					<!-- Date Range Preset -->
					<div class="wc-tp-filter-group">
						<label><?php esc_html_e( 'Date Range:', 'wc-team-payroll' ); ?></label>
						<select id="wc-tp-payments-date-preset">
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
						<input type="date" id="wc-tp-payments-start-date" value="<?php echo esc_attr( date( 'Y-m-01' ) ); ?>" />
						<span class="wc-tp-date-separator">to</span>
						<input type="date" id="wc-tp-payments-end-date" value="<?php echo esc_attr( date( 'Y-m-t' ) ); ?>" />
					</div>

					<!-- Salary Type Filter -->
					<div class="wc-tp-filter-group">
						<label><?php esc_html_e( 'Employee Type:', 'wc-team-payroll' ); ?></label>
						<select id="wc-tp-payments-salary-type-filter">
							<option value=""><?php esc_html_e( 'All Types', 'wc-team-payroll' ); ?></option>
							<option value="commission"><?php esc_html_e( 'Commission Based', 'wc-team-payroll' ); ?></option>
							<option value="fixed"><?php esc_html_e( 'Fixed Salary', 'wc-team-payroll' ); ?></option>
							<option value="combined"><?php esc_html_e( 'Combined (Base + Commission)', 'wc-team-payroll' ); ?></option>
						</select>
					</div>

					<!-- Filter Button -->
					<div class="wc-tp-filter-group">
						<button type="button" class="button button-primary" id="wc-tp-payments-filter-btn"><?php esc_html_e( 'Filter', 'wc-team-payroll' ); ?></button>
					</div>
				</div>
			</div>

			<!-- Payments Table Section -->
			<div class="wc-tp-table-section" id="wc-tp-payments-table-section">
				<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
					<h2 style="margin: 0;"><?php esc_html_e( 'Payment Records', 'wc-team-payroll' ); ?></h2>
					<div style="display: flex; gap: 10px; align-items: center;">
						<label for="wc-tp-payments-per-page" style="margin: 0; font-weight: 600; color: #212B36;"><?php esc_html_e( 'Items per page:', 'wc-team-payroll' ); ?></label>
						<select id="wc-tp-payments-per-page" style="padding: 6px 10px; border: 1px solid #E5EAF0; border-radius: 6px; font-size: 14px;">
							<option value="10">10</option>
							<option value="20" selected>20</option>
							<option value="30">30</option>
							<option value="50">50</option>
							<option value="100">100</option>
						</select>
					</div>
				</div>
				<div id="wc-tp-payments-table-container">
					<!-- Content will be loaded via AJAX -->
				</div>
				<!-- Pagination -->
				<div id="wc-tp-payments-pagination" style="margin-top: 20px; text-align: center;"></div>
			</div>
		</div>

		<?php
		$this->render_styles();
		$this->render_scripts();
	}

	/**
	 * Render styles
	 */
	private function render_styles() {
		?>
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

			.wc-team-payroll-payments {
				background: var(--color-site-bg);
				padding: 24px;
				font-family: var(--font-family);
				color: var(--text-main);
			}

			.wc-team-payroll-payments h1 {
				font-size: var(--fs-h1);
				font-weight: var(--fw-bold);
				color: var(--text-main);
				margin-bottom: 24px;
			}

			.wc-tp-add-payment-section {
				background: var(--color-card-bg);
				padding: 20px;
				border-radius: 8px;
				margin-bottom: 24px;
				border: 1px solid var(--color-border-light);
			}

			.wc-tp-add-payment-section h2 {
				margin-top: 0;
				margin-bottom: 16px;
				color: var(--text-main);
				border-left: 4px solid var(--color-primary);
				padding-left: 12px;
				font-size: var(--fs-h2);
				font-weight: var(--fw-bold);
			}

			.wc-tp-form-row {
				display: flex;
				gap: 16px;
				align-items: flex-end;
				flex-wrap: wrap;
			}

			.wc-tp-form-group {
				display: flex;
				flex-direction: column;
				gap: 6px;
				flex: 1;
				min-width: 200px;
			}

			.wc-tp-form-group label {
				font-weight: var(--fw-semibold);
				color: var(--text-main);
				font-size: var(--fs-meta);
			}

			.wc-tp-form-group select,
			.wc-tp-form-group input[type="number"],
			.wc-tp-form-group input[type="datetime-local"] {
				padding: 8px 12px;
				border: 1px solid var(--color-border-light);
				border-radius: 6px;
				font-size: var(--fs-body);
				font-family: var(--font-family);
				color: var(--text-main);
				background: var(--color-card-bg);
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

			.wc-tp-custom-date-range {
				display: flex;
				gap: 8px;
				align-items: center;
				flex-wrap: wrap;
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
			}

			.wc-tp-sortable-header::after {
				content: '⇅';
				position: absolute;
				right: 8px;
				opacity: 0.3;
			}

			.wc-tp-sortable-header:hover {
				background-color: var(--color-primary-subtle);
			}

			.wc-tp-sortable-header.wc-tp-sort-active {
				background-color: var(--color-primary-subtle);
				color: var(--color-primary);
			}

			.wc-tp-sortable-header.wc-tp-sort-active::after {
				opacity: 1;
				color: var(--color-primary);
			}

			.wc-tp-sortable-header.wc-tp-sort-active.wc-tp-sort-asc::after {
				content: '↑';
			}

			.wc-tp-sortable-header.wc-tp-sort-active.wc-tp-sort-desc::after {
				content: '↓';
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

			.button-primary {
				background: var(--color-primary);
				border-color: var(--color-primary);
				color: white;
				font-weight: var(--fw-semibold);
				border-radius: 6px;
				padding: 8px 16px;
				font-size: var(--fs-meta);
			}

			.button-primary:hover {
				background: var(--color-primary-hover);
				border-color: var(--color-primary-hover);
			}

			.button-secondary {
				background: var(--color-accent-muted);
				border-color: var(--color-border-light);
				color: var(--text-main);
				font-weight: var(--fw-semibold);
				border-radius: 6px;
				padding: 8px 16px;
				font-size: var(--fs-meta);
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
		</style>
		<?php
	}

	/**
	 * Render scripts
	 */
	private function render_scripts() {
		?>
		<script>
			jQuery(document).ready(function($) {
				let currentPage = 1;
				let allPaymentsData = [];
				let itemsPerPage = 20;
				let currentSort = { field: null, direction: 'asc' };
				let lastPresetRange = { start: '', end: '' };

				const wcCurrency = '<?php echo esc_js( get_woocommerce_currency() ); ?>';
				const wcCurrencySymbol = '<?php echo esc_js( get_woocommerce_currency_symbol() ); ?>';
				const wcCurrencyPos = '<?php echo esc_js( get_option( 'woocommerce_currency_pos', 'left' ) ); ?>';

				// Initialize with default preset (This Month)
				updateDateRangeFromPreset('this-month');

				// Date preset change
				$('#wc-tp-payments-date-preset').on('change', function() {
					const preset = $(this).val();
					
					if (preset === 'custom') {
						$('#wc-tp-custom-date-range').slideDown(200);
					} else {
						$('#wc-tp-custom-date-range').slideUp(200);
						updateDateRangeFromPreset(preset);
					}
				});

				// Filter button click
				$('#wc-tp-payments-filter-btn').on('click', function() {
					currentPage = 1;
					loadPaymentsData();
				});

				// Search on keyup
				$('#wc-tp-payments-search').on('keyup', function() {
					currentPage = 1;
					loadPaymentsData();
				});

				// Clear search
				$('#wc-tp-payments-search-clear').on('click', function() {
					$('#wc-tp-payments-search').val('');
					currentPage = 1;
					loadPaymentsData();
				});

				// Items per page change
				$('#wc-tp-payments-per-page').on('change', function() {
					itemsPerPage = parseInt($(this).val());
					currentPage = 1;
					renderPaymentsTable(allPaymentsData);
					renderPagination(allPaymentsData);
				});

				// Add payment form submit
				$('#wc-tp-add-payment-form').on('submit', function(e) {
					e.preventDefault();

					const employeeId = $('#wc-tp-payment-employee').val();
					const amount = $('#wc-tp-payment-amount').val();
					const date = $('#wc-tp-payment-date').val();
					const method = $('#wc-tp-payment-method').val();
					const note = $('#wc-tp-payment-note').val();
					const nonce = $('#wc_team_payroll_nonce').val();

					if (!employeeId || !amount || !date) {
						wcTPToast('<?php esc_js_e( 'Please fill all required fields', 'wc-team-payroll' ); ?>', 'error');
						return;
					}

					$('#wc-tp-add-payment-btn').prop('disabled', true).text('<?php esc_js_e( 'Adding...', 'wc-team-payroll' ); ?>');

					$.ajax({
						url: ajaxurl,
						type: 'POST',
						data: {
							action: 'wc_tp_add_payment',
							user_id: employeeId,
							amount: amount,
							payment_date: date,
							payment_method: method || '',
							note: note,
							nonce: nonce
						},
						success: function(response) {
							if (response.success) {
								wcTPToast('<?php esc_js_e( 'Payment added successfully', 'wc-team-payroll' ); ?>');
								$('#wc-tp-add-payment-form')[0].reset();
								$('#wc-tp-payment-date').val(new Date().toISOString().slice(0, 16));
								loadPaymentsData();
							} else {
								wcTPToast('<?php esc_js_e( 'Error adding payment', 'wc-team-payroll' ); ?>' + (response.data ? ': ' + response.data : ''), 'error');
							}
						},
						error: function() {
							wcTPToast('<?php esc_js_e( 'Error adding payment', 'wc-team-payroll' ); ?>', 'error');
						},
						complete: function() {
							$('#wc-tp-add-payment-btn').prop('disabled', false).text('<?php esc_js_e( 'Add Payment', 'wc-team-payroll' ); ?>');
						}
					});
				});

				// Load payment methods when employee is selected
				$('#wc-tp-payment-employee').on('change', function() {
					const employeeId = $(this).val();
					if (!employeeId) {
						$('#wc-tp-payment-method').html('<option value=""><?php esc_js_e( 'Select Method', 'wc-team-payroll' ); ?></option>');
						return;
					}

					$.ajax({
						url: ajaxurl,
						type: 'POST',
						data: {
							action: 'wc_tp_get_payment_methods',
							user_id: employeeId
						},
						success: function(response) {
							if (response.success && response.data.methods) {
								let html = '<option value=""><?php esc_js_e( 'Select Method', 'wc-team-payroll' ); ?></option>';
								$.each(response.data.methods, function(i, method) {
									html += '<option value="' + method.id + '">' + method.name + '</option>';
								});
								$('#wc-tp-payment-method').html(html);
							}
						}
					});
				});

				// Load payments data
				function loadPaymentsData() {
					const startDate = $('#wc-tp-payments-start-date').val();
					const endDate = $('#wc-tp-payments-end-date').val();
					const salaryType = $('#wc-tp-payments-salary-type-filter').val();
					const searchQuery = $('#wc-tp-payments-search').val();

					if (!startDate || !endDate) {
						return;
					}

					$('#wc-tp-payments-filter-btn').prop('disabled', true).text('<?php esc_js_e( 'Loading...', 'wc-team-payroll' ); ?>');

					$.ajax({
						url: ajaxurl,
						type: 'POST',
						data: {
							action: 'wc_tp_get_all_payments',
							start_date: startDate,
							end_date: endDate,
							salary_type: salaryType,
							search: searchQuery
						},
						success: function(response) {
							if (response.success) {
								allPaymentsData = response.data.payments || [];
								renderPaymentsTable(allPaymentsData);
								renderPagination(allPaymentsData);
							}
						},
						complete: function() {
							$('#wc-tp-payments-filter-btn').prop('disabled', false).text('<?php esc_js_e( 'Filter', 'wc-team-payroll' ); ?>');
						}
					});
				}

				// Calculate date range from preset
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
					lastPresetRange = range;
					$('#wc-tp-payments-start-date').val(range.start);
					$('#wc-tp-payments-end-date').val(range.end);
				}

				// Load payments on page load
				loadPaymentsData();

				// Render payments table (continued in next part)
				function renderPaymentsTable(payments) {
					const container = $('#wc-tp-payments-table-container');
					
					if (!payments || payments.length === 0) {
						container.html('<div class="wc-tp-empty-state"><div class="wc-tp-empty-icon">💳</div><p><?php esc_js_e( 'No payments found', 'wc-team-payroll' ); ?></p></div>');
						return;
					}

					const startIndex = (currentPage - 1) * itemsPerPage;
					const endIndex = startIndex + itemsPerPage;
					const pageData = payments.slice(startIndex, endIndex);

					let html = '<table class="wc-tp-data-table"><thead><tr>';
					html += '<th class="wc-tp-sortable-header" data-sort="employee_name"><?php esc_js_e( 'Employee', 'wc-team-payroll' ); ?></th>';
					html += '<th class="wc-tp-sortable-header" data-sort="vb_user_id"><?php esc_js_e( 'Employee ID', 'wc-team-payroll' ); ?></th>';
					html += '<th class="wc-tp-sortable-header" data-sort="amount"><?php esc_js_e( 'Amount', 'wc-team-payroll' ); ?></th>';
					html += '<th class="wc-tp-sortable-header" data-sort="date"><?php esc_js_e( 'Payment Date', 'wc-team-payroll' ); ?></th>';
					html += '<th class="wc-tp-sortable-header" data-sort="added_by"><?php esc_js_e( 'Added By', 'wc-team-payroll' ); ?></th>';
					html += '<th class="wc-tp-sortable-header" data-sort="salary_type"><?php esc_js_e( 'Employee Type', 'wc-team-payroll' ); ?></th>';
					html += '<th><?php esc_js_e( 'Action', 'wc-team-payroll' ); ?></th>';
					html += '</tr></thead><tbody>';

					$.each(pageData, function(i, payment) {
						html += '<tr>';
						html += '<td><strong>' + payment.employee_name + '</strong></td>';
						html += '<td>' + payment.vb_user_id + '</td>';
						html += '<td><strong>' + formatCurrency(payment.amount) + '</strong></td>';
						html += '<td>' + payment.date + '</td>';
						html += '<td>' + payment.added_by + '</td>';
						html += '<td>' + payment.salary_type + '</td>';
						html += '<td><a href="' + ajaxurl.replace('admin-ajax.php', 'admin.php?page=wc-team-payroll-employee-detail&user_id=' + payment.user_id) + '" class="button button-small button-primary"><?php esc_js_e( 'View', 'wc-team-payroll' ); ?></a></td>';
						html += '</tr>';
					});

					html += '</tbody></table>';
					container.html(html);

					// Attach sort handlers
					attachSortHandlers();
				}

				// Attach sort handlers
				function attachSortHandlers() {
					$('.wc-tp-sortable-header').off('click').on('click', function() {
						const sortField = $(this).data('sort');
						if (!sortField) return;

						if (currentSort.field === sortField) {
							currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
						} else {
							currentSort.field = sortField;
							currentSort.direction = 'asc';
						}

						// Update header classes
						$('.wc-tp-sortable-header').removeClass('wc-tp-sort-active wc-tp-sort-asc wc-tp-sort-desc');
						$(this).addClass('wc-tp-sort-active wc-tp-sort-' + currentSort.direction);

						// Sort data
						const isNumeric = ['amount'].includes(sortField);
						const sortedData = [...allPaymentsData].sort((a, b) => {
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

						allPaymentsData = sortedData;
						renderPaymentsTable(allPaymentsData);
						renderPagination(allPaymentsData);
					});
				}

				// Render pagination
				function renderPagination(payments) {
					const container = $('#wc-tp-payments-pagination');
					const totalPages = Math.ceil(payments.length / itemsPerPage);

					if (totalPages <= 1) {
						container.html('');
						return;
					}

					let html = '<div class="wc-tp-pagination">';

					if (currentPage > 1) {
						html += '<a href="#" data-page="' + (currentPage - 1) + '">← <?php esc_js_e( 'Previous', 'wc-team-payroll' ); ?></a>';
					}

					for (let i = 1; i <= totalPages; i++) {
						if (i === currentPage) {
							html += '<span class="current">' + i + '</span>';
						} else {
							html += '<a href="#" data-page="' + i + '">' + i + '</a>';
						}
					}

					if (currentPage < totalPages) {
						html += '<a href="#" data-page="' + (currentPage + 1) + '"><?php esc_js_e( 'Next', 'wc-team-payroll' ); ?> →</a>';
					}

					html += '</div>';
					container.html(html);

					container.find('a').on('click', function(e) {
						e.preventDefault();
						currentPage = parseInt($(this).data('page'));
						renderPaymentsTable(allPaymentsData);
						renderPagination(allPaymentsData);
						$('html, body').animate({ scrollTop: $('#wc-tp-payments-table-section').offset().top - 100 }, 300);
					});
				}

				// Format currency
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
