<?php
/**
 * Admin Dashboard
 */

class WC_Team_Payroll_Dashboard {

	public function __construct() {
		// Enqueue common CSS and JS on admin pages
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue common CSS and JS
	 */
	public function enqueue_assets( $hook ) {
		// Only load on dashboard page
		if ( strpos( $hook, 'wc-team-payroll-dashboard' ) === false ) {
			return;
		}

		wp_enqueue_style( 'wc-tp-common-css', WC_TEAM_PAYROLL_URL . 'assets/css/common.css', array(), WC_TEAM_PAYROLL_VERSION );
		wp_enqueue_style( 'wc-tp-dashboard-css', WC_TEAM_PAYROLL_URL . 'assets/css/dashboard.css', array(), WC_TEAM_PAYROLL_VERSION );
		wp_enqueue_script( 'wc-tp-common-js', WC_TEAM_PAYROLL_URL . 'assets/js/common.js', array( 'jquery' ), WC_TEAM_PAYROLL_VERSION, true );
		wp_enqueue_script( 'wc-tp-dashboard-js', WC_TEAM_PAYROLL_URL . 'assets/js/dashboard.js', array( 'jquery', 'wc-tp-common-js' ), WC_TEAM_PAYROLL_VERSION, true );
	}

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
		<div class="wc-tp-page-wrapper">
			<h1><?php esc_html_e( 'Dashboard', 'wc-team-payroll' ); ?></h1>

			<!-- Unified Date Range Filter -->
			<div class="wc-tp-unified-filter">
				<div class="wc-tp-filter-row">
					<div class="wc-tp-filter-group">
						<label><?php esc_html_e( 'Date Range:', 'wc-team-payroll' ); ?></label>
						<select id="wc-tp-date-preset">
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

					<div class="wc-tp-filter-group" id="wc-tp-custom-dates" style="display: none;">
						<label><?php esc_html_e( 'From:', 'wc-team-payroll' ); ?></label>
						<input type="date" id="wc-tp-start-date" value="<?php echo esc_attr( $start_date ); ?>" />
					</div>

					<div class="wc-tp-filter-group" id="wc-tp-custom-dates-end" style="display: none;">
						<label><?php esc_html_e( 'To:', 'wc-team-payroll' ); ?></label>
						<input type="date" id="wc-tp-end-date" value="<?php echo esc_attr( $end_date ); ?>" />
					</div>

					<button type="button" class="button button-primary" id="wc-tp-filter-btn" style="align-self: flex-end;"><?php esc_html_e( 'Filter', 'wc-team-payroll' ); ?></button>
				</div>
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
		<?php
	}
}
