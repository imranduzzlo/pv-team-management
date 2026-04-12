<?php
/**
 * Payments Management Page
 */

class WC_Team_Payroll_Payments_Page {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_payments_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Add payments menu
	 */
	public function add_payments_menu() {
		add_submenu_page(
			'wc-team-payroll',
			__( 'Payments', 'wc-team-payroll' ),
			__( 'Payments', 'wc-team-payroll' ),
			'manage_woocommerce',
			'wc-team-payroll-payments',
			array( $this, 'render_payments_page' )
		);
	}

	/**
	 * Enqueue scripts and styles
	 */
	public function enqueue_scripts( $hook ) {
		if ( 'wc-team-payroll_page_wc-team-payroll-payments' !== $hook ) {
			return;
		}

		// Enqueue Select2
		wp_enqueue_script( 'select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array( 'jquery' ), '4.1.0', true );
		wp_enqueue_style( 'select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', array(), '4.1.0' );

		wp_enqueue_script(
			'wc-tp-payments',
			WC_TEAM_PAYROLL_URL . 'assets/js/payments.js',
			array( 'jquery', 'wp-util', 'select2' ),
			WC_TEAM_PAYROLL_VERSION,
			true
		);

		wp_localize_script( 'wc-tp-payments', 'wcTpPayments', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'wc_tp_payments_nonce' ),
			'strings' => array(
				'confirmDelete' => __( 'Are you sure you want to delete this payment?', 'wc-team-payroll' ),
				'paymentAdded' => __( 'Payment added successfully', 'wc-team-payroll' ),
				'paymentDeleted' => __( 'Payment deleted successfully', 'wc-team-payroll' ),
				'error' => __( 'An error occurred', 'wc-team-payroll' ),
			),
		) );

		wp_enqueue_style(
			'wc-tp-payments',
			WC_TEAM_PAYROLL_URL . 'assets/css/payments.css',
			array(),
			WC_TEAM_PAYROLL_VERSION
		);
	}

	/**
	 * Render payments page
	 */
	public function render_payments_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Unauthorized', 'wc-team-payroll' ) );
		}

		$current_datetime = current_time( 'Y-m-d\TH:i' );
		?>
		<div class="wrap wc-team-payroll-payments">
			<h1><?php esc_html_e( 'Payments', 'wc-team-payroll' ); ?></h1>

			<!-- Payment Entry Form -->
			<div class="wc-tp-payment-form-section">
				<h2><?php esc_html_e( 'Add Payment', 'wc-team-payroll' ); ?></h2>
				<form id="wc-tp-payment-form" class="wc-tp-form">
					<div class="wc-tp-form-row">
						<div class="wc-tp-form-group">
							<label for="wc-tp-employee-select"><?php esc_html_e( 'Employee Name', 'wc-team-payroll' ); ?> <span class="required">*</span></label>
							<select id="wc-tp-employee-select" name="employee_id" required>
								<option value=""><?php esc_html_e( 'Select an employee...', 'wc-team-payroll' ); ?></option>
							</select>
						</div>

						<div class="wc-tp-form-group">
							<label for="wc-tp-payment-amount"><?php esc_html_e( 'Amount', 'wc-team-payroll' ); ?> <span class="required">*</span></label>
							<input type="number" id="wc-tp-payment-amount" name="amount" step="0.01" min="0" required placeholder="0.00" />
						</div>

						<div class="wc-tp-form-group">
							<label for="wc-tp-payment-datetime"><?php esc_html_e( 'Date & Time', 'wc-team-payroll' ); ?> <span class="required">*</span></label>
							<input type="datetime-local" id="wc-tp-payment-datetime" name="payment_date" value="<?php echo esc_attr( $current_datetime ); ?>" required />
						</div>
					</div>

					<div class="wc-tp-form-row">
						<div class="wc-tp-form-group">
							<label for="wc-tp-payment-method"><?php esc_html_e( 'Payment Method', 'wc-team-payroll' ); ?> <span class="required">*</span></label>
							<select id="wc-tp-payment-method" name="payment_method" required>
								<option value=""><?php esc_html_e( 'Select payment method...', 'wc-team-payroll' ); ?></option>
							</select>
							<small id="wc-tp-method-details" style="display: none; color: #666; margin-top: 5px;"></small>
						</div>

						<div class="wc-tp-form-group">
							<label for="wc-tp-payment-note"><?php esc_html_e( 'Note', 'wc-team-payroll' ); ?></label>
							<textarea id="wc-tp-payment-note" name="note" rows="3" placeholder="<?php esc_attr_e( 'Optional notes about this payment...', 'wc-team-payroll' ); ?>"></textarea>
						</div>
					</div>

					<div class="wc-tp-form-actions">
						<button type="submit" class="button button-primary"><?php esc_html_e( 'Add Payment', 'wc-team-payroll' ); ?></button>
						<button type="reset" class="button button-secondary"><?php esc_html_e( 'Clear', 'wc-team-payroll' ); ?></button>
					</div>
				</form>
			</div>

			<!-- Payments History Table -->
			<div class="wc-tp-table-section">
				<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
					<h2 style="margin: 0;"><?php esc_html_e( 'Payment History', 'wc-team-payroll' ); ?></h2>
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

				<!-- Filters -->
				<div class="wc-tp-unified-filter">
					<div class="wc-tp-filter-row">
						<div class="wc-tp-filter-group">
							<input type="text" id="wc-tp-payments-search" placeholder="<?php esc_attr_e( 'Search by employee name...', 'wc-team-payroll' ); ?>" />
						</div>

						<div class="wc-tp-filter-group">
							<label><?php esc_html_e( 'Date Range:', 'wc-team-payroll' ); ?></label>
							<select id="wc-tp-payments-date-preset">
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

						<div class="wc-tp-filter-group wc-tp-custom-date-range" id="wc-tp-payments-custom-date-range" style="display: none;">
							<input type="date" id="wc-tp-payments-start-date" />
							<span class="wc-tp-date-separator">to</span>
							<input type="date" id="wc-tp-payments-end-date" />
						</div>

						<div class="wc-tp-filter-group">
							<label><?php esc_html_e( 'Payment Method:', 'wc-team-payroll' ); ?></label>
							<select id="wc-tp-payments-method-filter">
								<option value=""><?php esc_html_e( 'All Methods', 'wc-team-payroll' ); ?></option>
							</select>
						</div>

						<div class="wc-tp-filter-group">
							<button type="button" class="button button-primary" id="wc-tp-payments-filter-btn"><?php esc_html_e( 'Filter', 'wc-team-payroll' ); ?></button>
							<button type="button" class="button button-secondary" id="wc-tp-payments-reset-btn"><?php esc_html_e( 'Reset', 'wc-team-payroll' ); ?></button>
						</div>
					</div>
				</div>

				<!-- Table -->
				<div id="wc-tp-payments-table-container">
					<!-- Content will be loaded via AJAX -->
				</div>

				<!-- Pagination -->
				<div id="wc-tp-payments-pagination" style="margin-top: 20px; text-align: center;"></div>
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
				--color-accent-success: #388E3C;
				--color-accent-muted: #F4F4F4;
				--text-main: #212B36;
				--text-body: #454F5B;
				--text-muted: #919EAB;
			}

			.wc-team-payroll-payments {
				background-color: var(--color-site-bg);
				padding: 20px;
			}

			.wc-tp-payment-form-section {
				background: var(--color-card-bg);
				border: 1px solid var(--color-border-light);
				border-radius: 8px;
				padding: 20px;
				margin-bottom: 30px;
				box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
			}

			.wc-tp-payment-form-section h2 {
				margin-top: 0;
				color: var(--text-main);
				font-size: 1.25rem;
				margin-bottom: 20px;
			}

			.wc-tp-form {
				display: flex;
				flex-direction: column;
				gap: 20px;
			}

			.wc-tp-form-row {
				display: grid;
				grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
				gap: 20px;
			}

			.wc-tp-form-group {
				display: flex;
				flex-direction: column;
			}

			.wc-tp-form-group label {
				font-weight: 600;
				color: var(--text-main);
				margin-bottom: 8px;
				font-size: 0.95rem;
			}

			.wc-tp-form-group .required {
				color: var(--color-accent-alert);
			}

			.wc-tp-form-group input,
			.wc-tp-form-group select,
			.wc-tp-form-group textarea {
				padding: 10px 12px;
				border: 1px solid var(--color-border-light);
				border-radius: 6px;
				font-size: 0.95rem;
				font-family: inherit;
				transition: border-color 0.2s;
			}

			.wc-tp-form-group input:focus,
			.wc-tp-form-group select:focus,
			.wc-tp-form-group textarea:focus {
				outline: none;
				border-color: var(--color-primary);
				box-shadow: 0 0 0 3px var(--color-primary-subtle);
			}

			.wc-tp-form-group textarea {
				resize: vertical;
				min-height: 80px;
			}

			.wc-tp-form-actions {
				display: flex;
				gap: 10px;
				margin-top: 10px;
			}

			.wc-tp-form-actions button {
				padding: 10px 20px;
				font-weight: 600;
			}

			.wc-tp-table-section {
				background: var(--color-card-bg);
				border: 1px solid var(--color-border-light);
				border-radius: 8px;
				padding: 20px;
				box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
			}

			.wc-tp-unified-filter {
				background: var(--color-accent-muted);
				border: 1px solid var(--color-border-light);
				border-radius: 6px;
				padding: 15px;
				margin-bottom: 20px;
			}

			.wc-tp-filter-row {
				display: grid;
				grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
				gap: 15px;
				align-items: flex-end;
			}

			.wc-tp-filter-group {
				display: flex;
				flex-direction: column;
				gap: 5px;
			}

			.wc-tp-filter-group label {
				font-weight: 600;
				color: var(--text-main);
				font-size: 0.9rem;
			}

			.wc-tp-filter-group input,
			.wc-tp-filter-group select {
				padding: 8px 10px;
				border: 1px solid var(--color-border-light);
				border-radius: 4px;
				font-size: 0.9rem;
			}

			.wc-tp-filter-group input:focus,
			.wc-tp-filter-group select:focus {
				outline: none;
				border-color: var(--color-primary);
			}

			.wc-tp-date-separator {
				color: var(--text-muted);
				font-size: 0.9rem;
				padding: 0 5px;
			}

			.wc-tp-custom-date-range {
				display: flex;
				align-items: center;
				gap: 10px;
			}

			.wc-tp-custom-date-range input {
				flex: 1;
			}

			table.wc-tp-payments-table {
				width: 100%;
				border-collapse: collapse;
				margin-bottom: 20px;
			}

			table.wc-tp-payments-table thead {
				background-color: var(--color-accent-muted);
				border-bottom: 2px solid var(--color-border-light);
			}

			table.wc-tp-payments-table th {
				padding: 12px;
				text-align: left;
				font-weight: 600;
				color: var(--text-main);
				font-size: 0.9rem;
				cursor: pointer;
				user-select: none;
			}

			table.wc-tp-payments-table th:hover {
				background-color: #E8E8E8;
			}

			table.wc-tp-payments-table th.sortable::after {
				content: ' ⇅';
				opacity: 0.5;
			}

			table.wc-tp-payments-table th.sorted-asc::after {
				content: ' ↑';
				opacity: 1;
			}

			table.wc-tp-payments-table th.sorted-desc::after {
				content: ' ↓';
				opacity: 1;
			}

			table.wc-tp-payments-table td {
				padding: 12px;
				border-bottom: 1px solid var(--color-border-light);
				color: var(--text-body);
				font-size: 0.9rem;
			}

			table.wc-tp-payments-table tbody tr:hover {
				background-color: var(--color-primary-subtle);
			}

			.wc-tp-amount {
				font-weight: 600;
				color: var(--color-accent-success);
			}

			.wc-tp-action-buttons {
				display: flex;
				gap: 8px;
			}

			.wc-tp-action-buttons button {
				padding: 6px 12px;
				font-size: 0.85rem;
				border: none;
				border-radius: 4px;
				cursor: pointer;
				transition: all 0.2s;
			}

			.wc-tp-delete-btn {
				background-color: var(--color-accent-alert);
				color: white;
			}

			.wc-tp-delete-btn:hover {
				background-color: var(--color-accent-alert-hover);
			}

			.wc-tp-pagination {
				display: flex;
				justify-content: center;
				gap: 5px;
				flex-wrap: wrap;
			}

			.wc-tp-pagination a,
			.wc-tp-pagination span {
				padding: 8px 12px;
				border: 1px solid var(--color-border-light);
				border-radius: 4px;
				text-decoration: none;
				color: var(--text-main);
				cursor: pointer;
				transition: all 0.2s;
			}

			.wc-tp-pagination a:hover {
				background-color: var(--color-primary);
				color: white;
				border-color: var(--color-primary);
			}

			.wc-tp-pagination .current {
				background-color: var(--color-primary);
				color: white;
				border-color: var(--color-primary);
			}

			.wc-tp-pagination .disabled {
				opacity: 0.5;
				cursor: not-allowed;
			}

			.wc-tp-no-data {
				text-align: center;
				padding: 40px 20px;
				color: var(--text-muted);
			}

			.wc-tp-loading {
				text-align: center;
				padding: 20px;
				color: var(--text-muted);
			}

			.wc-tp-success-message {
				background-color: #D4EDDA;
				border: 1px solid #C3E6CB;
				color: #155724;
				padding: 12px;
				border-radius: 4px;
				margin-bottom: 15px;
			}

			.wc-tp-error-message {
				background-color: #F8D7DA;
				border: 1px solid #F5C6CB;
				color: #721C24;
				padding: 12px;
				border-radius: 4px;
				margin-bottom: 15px;
			}
		</style>
		<?php
	}
}
