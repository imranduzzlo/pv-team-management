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
			<h1><?php esc_html_e( 'Dashboard', 'wc-team-payroll' ); ?></h1>

			<!-- Unified Filter Section -->
			<div class="wc-tp-unified-filter">
				<div class="wc-tp-filter-row">
					<!-- Date Range Preset -->
					<div class="wc-tp-filter-group">
						<label><?php esc_html_e( 'Date Range:', 'wc-team-payroll' ); ?></label>
						<select id="wc-tp-dashboard-date-preset">
							<option value="all-time"><?php esc_html_e( 'All Time', 'wc-team-payroll' ); ?></option>
							<option value="today"><?php esc_html_e( 'Today', 'wc-team-payroll' ); ?></option>
							<option value="this-week"><?php esc_html_e( 'This Week', 'wc-team-payroll' ); ?></option>
							<option value="this-month"><?php esc_html_e( 'This Month', 'wc-team-payroll' ); ?></option>
							<option value="this-year"><?php esc_html_e( 'This Year', 'wc-team-payroll' ); ?></option>
							<option value="last-week"><?php esc_html_e( 'Last Week', 'wc-team-payroll' ); ?></option>
							<option value="last-month"><?php esc_html_e( 'Last Month', 'wc-team-payroll' ); ?></option>
							<option value="last-year"><?php esc_html_e( 'Last Year', 'wc-team-payroll' ); ?></option>
							<option value="last-6-months"><?php esc_html_e( 'Last 6 Months', 'wc-team-payroll' ); ?></option>
							<option value="custom"><?php esc_html_e( 'Custom', 'wc-team-payroll' ); ?></option>
						</select>
					</div>

					<!-- Custom Date Range (Hidden by default) -->
					<div class="wc-tp-filter-group wc-tp-custom-date-range" id="wc-tp-dashboard-custom-date-range" style="display: none;">
						<input type="date" id="wc-tp-dashboard-start-date" value="<?php echo esc_attr( $start_date ); ?>" />
						<span class="wc-tp-date-separator">to</span>
						<input type="date" id="wc-tp-dashboard-end-date" value="<?php echo esc_attr( $end_date ); ?>" />
					</div>

					<!-- Filter Button -->
					<div class="wc-tp-filter-group">
						<button type="button" class="button button-primary" id="wc-tp-dashboard-filter-btn"><?php esc_html_e( 'Filter', 'wc-team-payroll' ); ?></button>
					</div>
				</div>
			</div>

			<!-- Stats Cards -->
			<div class="wc-tp-stats-grid" id="wc-tp-stats-container">
				<!-- Stats will be loaded via AJAX -->
			</div>

			<!-- Employee Payroll Details (TOP) -->
			<div class="wc-tp-table-section" id="wc-tp-payroll-section">
				<div class="wc-tp-section-header">
					<h2><?php esc_html_e( 'Employee Payroll Details', 'wc-team-payroll' ); ?></h2>
					<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'wc-team-payroll-payroll' ), admin_url( 'admin.php' ) ) ); ?>" class="wc-tp-view-all-btn">
						<?php esc_html_e( 'View All', 'wc-team-payroll' ); ?>
						<span class="dashicons dashicons-arrow-right-alt2"></span>
					</a>
				</div>
				<div id="wc-tp-payroll-container">
					<!-- Content will be loaded via AJAX -->
				</div>
			</div>

			<!-- Two Column Layout -->
			<div class="wc-tp-dashboard-grid">
				<!-- Top Earners -->
				<div class="wc-tp-table-section" id="wc-tp-earners-section">
					<div class="wc-tp-section-header">
						<h2><?php esc_html_e( 'Top Earners', 'wc-team-payroll' ); ?></h2>
					</div>
					<div id="wc-tp-top-earners-container">
						<!-- Content will be loaded via AJAX -->
					</div>
				</div>

				<!-- Recent Payments -->
				<div class="wc-tp-table-section" id="wc-tp-payments-section">
					<div class="wc-tp-section-header">
						<h2><?php esc_html_e( 'Recent Payments', 'wc-team-payroll' ); ?></h2>
						<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'wc-team-payroll-payments' ), admin_url( 'admin.php' ) ) ); ?>" class="wc-tp-view-all-btn">
							<?php esc_html_e( 'View All', 'wc-team-payroll' ); ?>
							<span class="dashicons dashicons-arrow-right-alt2"></span>
						</a>
					</div>
					<div id="wc-tp-recent-payments-container">
						<!-- Content will be loaded via AJAX -->
					</div>
				</div>
			</div>

			<!-- Latest Employees (10) - BOTTOM -->
			<div class="wc-tp-table-section" id="wc-tp-employees-section">
				<div class="wc-tp-section-header">
					<h2><?php esc_html_e( 'Latest Employees', 'wc-team-payroll' ); ?></h2>
					<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'wc-team-payroll-employees' ), admin_url( 'admin.php' ) ) ); ?>" class="wc-tp-view-all-btn">
						<?php esc_html_e( 'View All', 'wc-team-payroll' ); ?>
						<span class="dashicons dashicons-arrow-right-alt2"></span>
					</a>
				</div>
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

			.wc-tp-stats-grid {
				display: grid;
				grid-template-columns: repeat(5, 1fr);
				gap: 16px;
				margin-bottom: 32px;
			}

			.wc-tp-stat-card {
				background: var(--color-card-bg);
				padding: 16px;
				border-radius: 8px;
				border: 1px solid var(--color-border-light);
				display: flex;
				flex-direction: column;
				align-items: flex-start;
				justify-content: flex-end;
				gap: 12px;
				transition: all 0.3s ease;
				cursor: pointer;
				min-height: 100px;
				text-align: left;
				position: relative;
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
				font-size: 24px;
				position: absolute;
				top: 12px;
				right: 12px;
				text-align: center;
				flex-shrink: 0;
			}

			.wc-tp-stat-content {
				flex: 1;
				width: 100%;
				padding-right: 30px;
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
				margin: 0;
				color: var(--text-main);
				border-left: 4px solid var(--color-primary);
				padding-left: 12px;
				font-size: var(--fs-h2);
				font-weight: var(--fw-bold);
			}

			/* Section header with view all button styling */
			.wc-tp-section-header {
				display: flex;
				justify-content: space-between;
				align-items: center;
				margin-bottom: 20px;
			}

			.wc-tp-section-header h2 {
				margin: 0;
				color: var(--text-main);
				border-left: 4px solid var(--color-primary);
				padding-left: 12px;
				font-size: var(--fs-h2);
				font-weight: var(--fw-bold);
			}

			.wc-tp-view-all-btn {
				background: transparent;
				border: none;
				color: var(--color-primary);
				font-size: var(--fs-meta);
				font-weight: var(--fw-medium);
				text-decoration: none;
				padding: 0;
				margin: 0;
				display: flex;
				align-items: center;
				gap: 4px;
				transition: all 0.2s ease;
				cursor: pointer;
				line-height: 1;
			}

			.wc-tp-view-all-btn:hover {
				color: var(--color-primary-hover);
				text-decoration: none;
			}

			.wc-tp-view-all-btn .dashicons {
				font-size: 14px;
				transition: transform 0.2s ease;
				width: 14px;
				height: 14px;
			}

			.wc-tp-view-all-btn:hover .dashicons {
				transform: translateX(2px);
			}

			/* Sections without view all button - ensure consistent spacing */
			.wc-tp-table-section h2:only-child {
				margin-bottom: 20px;
			}

			/* Action icons - remove underlines and borders */
			.wc-tp-action-icon {
				text-decoration: none !important;
				border: none !important;
				box-shadow: none !important;
				background: transparent !important;
				padding: 4px;
				display: inline-flex;
				align-items: center;
				justify-content: center;
				border-radius: 3px;
				transition: background 0.2s ease;
			}

			.wc-tp-action-icon:hover {
				text-decoration: none !important;
				background: #f0f0f0 !important;
			}

			.wc-tp-action-icon .dashicons {
				color: #666;
				font-size: 16px;
				width: 16px;
				height: 16px;
			}

			.wc-tp-action-icon:hover .dashicons {
				color: #333;
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
				transition: all 0.2s ease;
				white-space: nowrap;
			}

			.wc-tp-sortable-header:hover {
				background: var(--color-primary-subtle);
				color: var(--color-primary);
			}

			.wc-tp-data-table td {
				padding: 12px;
				border-bottom: 1px solid var(--color-border-light);
				font-size: var(--fs-body);
				color: var(--text-body);
			}

			.wc-tp-no-action td {
				padding: 10px 12px !important;
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

			.wc-tp-status-active {
				background: #D4EDDA;
				color: #155724;
			}

			.wc-tp-status-inactive {
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

			.wc-tp-quick-edit {
				background: var(--color-accent-link);
				border-color: var(--color-accent-link);
				color: white;
				padding: 4px 8px;
				font-size: 12px;
				height: auto;
				line-height: 1.5;
			}

			.wc-tp-quick-edit:hover {
				background: #0066cc;
				border-color: #0066cc;
			}

			.wc-tp-edit-input {
				width: 100%;
				padding: 6px;
				border: 1px solid var(--color-primary);
				border-radius: 4px;
				font-size: 14px;
			}

			.wc-tp-save-edit {
				background: var(--color-accent-success);
				border-color: var(--color-accent-success);
				color: white;
			}

			.wc-tp-cancel-edit {
				background: #dc3545;
				border-color: #dc3545;
				color: white;
			}

			/* Mobile Responsive */
			@media (max-width: 1024px) {
				.wc-tp-stats-grid {
					grid-template-columns: repeat(3, 1fr);
				}

				.wc-tp-dashboard-grid {
					grid-template-columns: 1fr;
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
				.wc-team-payroll-dashboard {
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

				.wc-tp-edit-input {
					padding: 4px;
					font-size: 12px;
				}
			}

			@media (max-width: 480px) {
				.wc-team-payroll-dashboard {
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
				let wcCurrencySymbol = '$';
				let wcCurrencyPos = 'left';
				let lastDatePreset = 'all-time';

				// Load dashboard data on page load
				loadDashboardData();

				// Date preset change - show/hide custom date range
				$('#wc-tp-dashboard-date-preset').on('change', function() {
					const preset = $(this).val();
					lastDatePreset = preset;
					
					if (preset === 'custom') {
						$('#wc-tp-dashboard-custom-date-range').show();
					} else {
						$('#wc-tp-dashboard-custom-date-range').hide();
					}
				});

				// Filter button click
				$('#wc-tp-dashboard-filter-btn').on('click', function() {
					loadDashboardData();
				});

				// Calculate date range based on preset
				function getDateRange(preset) {
					const today = new Date();
					let startDate, endDate;

					switch(preset) {
						case 'today':
							startDate = new Date(today);
							endDate = new Date(today);
							break;
						case 'this-week':
							const firstDay = new Date(today.setDate(today.getDate() - today.getDay()));
							startDate = new Date(firstDay);
							endDate = new Date();
							break;
						case 'this-month':
							startDate = new Date(today.getFullYear(), today.getMonth(), 1);
							endDate = new Date();
							break;
						case 'this-year':
							startDate = new Date(today.getFullYear(), 0, 1);
							endDate = new Date();
							break;
						case 'last-week':
							const today2 = new Date();
							const lastWeekEnd = new Date(today2.setDate(today2.getDate() - today2.getDay() - 1));
							const lastWeekStart = new Date(lastWeekEnd.setDate(lastWeekEnd.getDate() - 6));
							startDate = new Date(lastWeekStart);
							endDate = new Date(lastWeekEnd);
							break;
						case 'last-month':
							const lastMonthEnd = new Date(today.getFullYear(), today.getMonth(), 0);
							const lastMonthStart = new Date(today.getFullYear(), today.getMonth() - 1, 1);
							startDate = new Date(lastMonthStart);
							endDate = new Date(lastMonthEnd);
							break;
						case 'last-year':
							startDate = new Date(today.getFullYear() - 1, 0, 1);
							endDate = new Date(today.getFullYear() - 1, 11, 31);
							break;
						case 'last-6-months':
							const sixMonthsAgo = new Date(today);
							sixMonthsAgo.setMonth(sixMonthsAgo.getMonth() - 6);
							startDate = new Date(sixMonthsAgo);
							endDate = new Date();
							break;
						case 'custom':
							const customStart = $('#wc-tp-dashboard-start-date').val();
							const customEnd = $('#wc-tp-dashboard-end-date').val();
							if (!customStart || !customEnd) {
								alert('Please select both start and end dates');
								return null;
							}
							return { start: customStart, end: customEnd };
						case 'all-time':
						default:
							return { start: '2000-01-01', end: new Date().toISOString().split('T')[0] };
					}

					return {
						start: startDate.toISOString().split('T')[0],
						end: endDate.toISOString().split('T')[0]
					};
				}

				// Load all dashboard data via AJAX
				function loadDashboardData() {
					const preset = $('#wc-tp-dashboard-date-preset').val();
					const dateRange = getDateRange(preset);

					if (!dateRange) {
						return;
					}

					// Show loading state
					$('#wc-tp-dashboard-filter-btn').prop('disabled', true).text('Loading...');

					// AJAX request
					$.ajax({
						url: ajaxurl,
						type: 'POST',
						data: {
							action: 'wc_tp_get_dashboard_data',
							date_preset: preset,
							start_date: dateRange.start,
							end_date: dateRange.end
						},
						success: function(response) {
							if (response.success) {
								const data = response.data;
								
								// Store currency
								wcCurrency = data.currency || 'USD';
								wcCurrencySymbol = data.currency_symbol || '$';
								wcCurrencyPos = data.currency_pos || 'left';
								
								// Update stat cards
								renderStatCards(data);
								
								// Update all tables
								renderLatestEmployees(data.latest_employees);
								renderTopEarners(data.top_earners);
								renderRecentPayments(data.recent_payments);
								renderPayrollTable(data.payroll);
							}
						},
						error: function() {
							// Silent error handling
						},
						complete: function() {
							$('#wc-tp-dashboard-filter-btn').prop('disabled', false).text('Filter');
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
					html += '<th class="wc-tp-sortable-header" data-sort="display_name">Name' + getSortIconDashboard('display_name', container) + '</th>';
					html += '<th class="wc-tp-sortable-header" data-sort="type">Type' + getSortIconDashboard('type', container) + '</th>';
					html += '<th class="wc-tp-sortable-header" data-sort="salary_info">Salary/Commission' + getSortIconDashboard('salary_info', container) + '</th>';
					html += '<th class="wc-tp-sortable-header" data-sort="status">Status' + getSortIconDashboard('status', container) + '</th>';
					html += '<th style="width: 50px; text-align: center;">Action</th>';
					html += '</tr></thead><tbody>';

					$.each(employees, function(i, emp) {
						const profileImg = emp.profile_picture ? '<img src="' + emp.profile_picture + '" alt="' + emp.display_name + '" style="width: 32px; height: 32px; border-radius: 50%; margin-right: 8px; vertical-align: middle;" />' : '<span style="display: inline-block; width: 32px; height: 32px; border-radius: 50%; background: #E5EAF0; margin-right: 8px; vertical-align: middle;"></span>';
						const tooltip = 'Name: ' + emp.display_name + '\nEmail: ' + emp.user_email + '\nPhone: ' + (emp.phone || 'N/A') + '\nRole: ' + emp.user_role;
						const userEditUrl = 'user-edit.php?user_id=' + emp.user_id;
						const nameHtml = '<a href="' + userEditUrl + '" title="' + tooltip + '" style="text-decoration: none; color: #0073aa; display: flex; align-items: center;">' + profileImg + '<span>' + emp.vb_user_id + ' ' + emp.display_name.split(' ').slice(0, 1).join(' ') + '</span></a>';
						
						// Status badge - using actual employee status instead of hardcoded "Active"
						const statusClass = emp.status === 'active' ? 'wc-tp-status-active' : 'wc-tp-status-inactive';
						const statusText = emp.status === 'active' ? 'Active' : 'Inactive';
						const statusBadge = '<span class="wc-tp-status ' + statusClass + '">' + statusText + '</span>';
						
						html += '<tr>';
						html += '<td>' + nameHtml + '</td>';
						html += '<td>' + emp.type + '</td>';
						html += '<td>' + emp.salary_info + '</td>';
						html += '<td>' + statusBadge + '</td>';
						html += '<td style="text-align: center;"><a href="' + emp.manage_url + '" class="wc-tp-action-icon" title="Manage Employee"><span class="dashicons dashicons-edit"></span></a></td>';
						html += '</tr>';
					});

					html += '</tbody></table>';
					container.html(html);
					attachSortHandlers(container, employees, ['display_name', 'type', 'salary_info', 'status']);
				}

				// Render top earners table
				function renderTopEarners(earners) {
					const container = $('#wc-tp-top-earners-container');
					
					if (!earners || earners.length === 0) {
						container.html('<div class="wc-tp-empty-state"><div class="wc-tp-empty-icon">💰</div><p>No earnings data</p></div>');
						return;
					}

					let html = '<table class="wc-tp-data-table wc-tp-sortable wc-tp-no-action"><thead><tr>';
					html += '<th class="wc-tp-sortable-header" data-sort="name">Employee' + getSortIconDashboard('name', container) + '</th>';
					html += '<th class="wc-tp-sortable-header" data-sort="orders">Orders' + getSortIconDashboard('orders', container) + '</th>';
					html += '<th class="wc-tp-sortable-header" data-sort="earnings">Total Earnings' + getSortIconDashboard('earnings', container) + '</th>';
					html += '</tr></thead><tbody>';

					$.each(earners, function(i, earner) {
						const profileImg = earner.profile_picture ? '<img src="' + earner.profile_picture + '" alt="' + earner.name + '" style="width: 32px; height: 32px; border-radius: 50%; margin-right: 8px; vertical-align: middle;" />' : '<span style="display: inline-block; width: 32px; height: 32px; border-radius: 50%; background: #E5EAF0; margin-right: 8px; vertical-align: middle;"></span>';
						const tooltip = 'Name: ' + earner.name + '\nEmail: ' + earner.user_email + '\nPhone: ' + (earner.phone || 'N/A') + '\nRole: ' + earner.user_role;
						const userEditUrl = 'user-edit.php?user_id=' + earner.user_id;
						const nameHtml = '<a href="' + userEditUrl + '" title="' + tooltip + '" style="text-decoration: none; color: #0073aa; display: flex; align-items: center;">' + profileImg + '<span>' + earner.name + '</span></a>';
						
						html += '<tr>';
						html += '<td>' + nameHtml + '</td>';
						html += '<td><span class="wc-tp-badge">' + earner.orders + '</span></td>';
						html += '<td><strong>' + formatCurrency(earner.earnings) + '</strong></td>';
						html += '</tr>';
					});

					html += '</tbody></table>';
					container.html(html);
					attachSortHandlers(container, earners, ['name', 'orders', 'earnings']);
				}

				// Render recent payments table
				function renderRecentPayments(payments) {
					const container = $('#wc-tp-recent-payments-container');
					
					if (!payments || payments.length === 0) {
						container.html('<div class="wc-tp-empty-state"><div class="wc-tp-empty-icon">💳</div><p>No payments yet</p></div>');
						return;
					}

					let html = '<table class="wc-tp-data-table wc-tp-sortable wc-tp-no-action"><thead><tr>';
					html += '<th class="wc-tp-sortable-header" data-sort="employee_name">Employee' + getSortIconDashboard('employee_name', container) + '</th>';
					html += '<th class="wc-tp-sortable-header" data-sort="date">Date' + getSortIconDashboard('date', container) + '</th>';
					html += '<th class="wc-tp-sortable-header" data-sort="amount">Amount' + getSortIconDashboard('amount', container) + '</th>';
					html += '</tr></thead><tbody>';

					$.each(payments, function(i, payment) {
						const profileImg = payment.profile_picture ? '<img src="' + payment.profile_picture + '" alt="' + payment.employee_name + '" style="width: 32px; height: 32px; border-radius: 50%; margin-right: 8px; vertical-align: middle;" />' : '<span style="display: inline-block; width: 32px; height: 32px; border-radius: 50%; background: #E5EAF0; margin-right: 8px; vertical-align: middle;"></span>';
						const tooltip = 'Name: ' + payment.employee_name + '\nEmail: ' + payment.user_email + '\nPhone: ' + (payment.phone || 'N/A') + '\nRole: ' + payment.user_role;
						const userEditUrl = 'user-edit.php?user_id=' + payment.user_id;
						const nameHtml = '<a href="' + userEditUrl + '" title="' + tooltip + '" style="text-decoration: none; color: #0073aa; display: flex; align-items: center;">' + profileImg + '<span>' + payment.employee_name + '</span></a>';
						
						html += '<tr data-payment-user-id="' + payment.user_id + '">';
						html += '<td>' + nameHtml + '</td>';
						html += '<td class="wc-tp-payment-date" data-date="' + payment.date + '">' + payment.date + '</td>';
						html += '<td class="wc-tp-payment-amount" data-amount="' + payment.amount + '"><strong>' + formatCurrency(payment.amount) + '</strong></td>';
						html += '</tr>';
					});

					html += '</tbody></table>';
					container.html(html);
					attachSortHandlers(container, payments, ['employee_name', 'date', 'amount']);
				}

				// Render payroll table
				function renderPayrollTable(payroll) {
					const container = $('#wc-tp-payroll-container');
					
					if (!payroll || Object.keys(payroll).length === 0) {
						container.html('<div class="wc-tp-empty-state"><div class="wc-tp-empty-icon">📊</div><p>No payroll data for this period</p></div>');
						return;
					}

					let html = '<table class="wc-tp-data-table wc-tp-sortable"><thead><tr>';
					html += '<th class="wc-tp-sortable-header" data-sort="name">Employee' + getSortIconDashboard('name', container) + '</th>';
					html += '<th class="wc-tp-sortable-header" data-sort="orders">Orders' + getSortIconDashboard('orders', container) + '</th>';
					html += '<th class="wc-tp-sortable-header" data-sort="total">Total Earnings' + getSortIconDashboard('total', container) + '</th>';
					html += '<th class="wc-tp-sortable-header" data-sort="paid">Paid' + getSortIconDashboard('paid', container) + '</th>';
					html += '<th class="wc-tp-sortable-header" data-sort="due">Due' + getSortIconDashboard('due', container) + '</th>';
					html += '<th style="width: 50px; text-align: center;">Action</th>';
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
							user: data.user,
							profile_picture: data.profile_picture,
							phone: data.phone,
							user_role: data.user_role,
							manage_url: data.manage_url
						});
					});

					$.each(payrollArray, function(i, data) {
						const profileImg = data.profile_picture ? '<img src="' + data.profile_picture + '" alt="' + data.name + '" style="width: 32px; height: 32px; border-radius: 50%; margin-right: 8px; vertical-align: middle;" />' : '<span style="display: inline-block; width: 32px; height: 32px; border-radius: 50%; background: #E5EAF0; margin-right: 8px; vertical-align: middle;"></span>';
						const tooltip = 'Name: ' + data.name + '\nEmail: ' + data.user_email + '\nPhone: ' + (data.phone || 'N/A') + '\nRole: ' + data.user_role;
						const userEditUrl = 'user-edit.php?user_id=' + data.userId;
						const nameHtml = '<a href="' + userEditUrl + '" title="' + tooltip + '" style="text-decoration: none; color: #0073aa; display: flex; align-items: center;">' + profileImg + '<span>' + data.name + '</span></a>';
						
						html += '<tr data-user-id="' + data.userId + '">';
						html += '<td>' + nameHtml + '</td>';
						html += '<td><span class="wc-tp-badge">' + data.orders + '</span></td>';
						html += '<td>' + formatCurrency(data.total) + '</td>';
						html += '<td class="wc-tp-paid-cell" data-paid="' + data.paid + '">' + formatCurrency(data.paid) + '</td>';
						html += '<td class="wc-tp-due-cell" data-due="' + data.due + '">' + formatCurrency(data.due) + '</td>';
						html += '<td style="text-align: center;"><a href="' + data.manage_url + '" class="wc-tp-action-icon" title="View Details"><span class="dashicons dashicons-visibility"></span></a></td>';
						html += '</tr>';
					});

					html += '</tbody></table>';
					container.html(html);
					attachSortHandlers(container, payrollArray, ['name', 'orders', 'total', 'paid', 'due']);
				}

				// Attach sort handlers to table headers
				function getSortIconDashboard(column, container) {
					const sortState = container.data('sortState') || { field: null, direction: 'asc' };
					if (sortState.field !== column) {
						return '';
					}
					
					const icon = sortState.direction === 'asc' ? 'arrow-up' : 'arrow-down';
					return ' <span class="dashicons dashicons-' + icon + '" style="font-size: 14px; margin-left: 4px;"></span>';
				}

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
						
						// Re-attach handlers to new headers with updated sort state
						setTimeout(function() {
							attachSortHandlers(container, sortedData, sortableFields);
						}, 10);
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
