<?php
/**
 * Payroll Page with Modern UI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Team_Payroll_Page {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue assets for payroll page
	 */
	public function enqueue_assets() {
		$screen = get_current_screen();
		if ( ! $screen || ( 'woocommerce_page_wc-team-payroll-details' !== $screen->id && 'woocommerce_page_wc-team-payroll' !== $screen->id ) ) {
			return;
		}

		// Enqueue common CSS
		wp_enqueue_style( 'wc-tp-common', plugin_dir_url( __FILE__ ) . '../../assets/css/common.css', array(), '5.6.2' );

		// Enqueue payroll-specific CSS
		wp_enqueue_style( 'wc-tp-payroll', plugin_dir_url( __FILE__ ) . '../../assets/css/payroll.css', array( 'wc-tp-common' ), '5.6.2' );

		// Enqueue common JS
		wp_enqueue_script( 'wc-tp-common', plugin_dir_url( __FILE__ ) . '../../assets/js/common.js', array( 'jquery' ), '5.6.2', true );

		// Enqueue payroll-specific JS
		wp_enqueue_script( 'wc-tp-payroll', plugin_dir_url( __FILE__ ) . '../../assets/js/payroll.js', array( 'jquery', 'wc-tp-common' ), '5.6.2', true );
	}

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

			<!-- Date Range Filter -->
			<div class="wc-tp-date-filter">
				<label><?php esc_html_e( 'Date Range:', 'wc-team-payroll' ); ?></label>
				<input type="date" id="wc-tp-payroll-start-date" value="<?php echo esc_attr( $start_date ); ?>" />
				<span class="wc-tp-date-separator">to</span>
				<input type="date" id="wc-tp-payroll-end-date" value="<?php echo esc_attr( $end_date ); ?>" />
				<button type="button" class="button button-primary" id="wc-tp-payroll-filter-btn"><?php esc_html_e( 'Filter', 'wc-team-payroll' ); ?></button>
			</div>

			<!-- Salary Type Filter -->
			<div class="wc-tp-salary-filter">
				<label><?php esc_html_e( 'Salary Type:', 'wc-team-payroll' ); ?></label>
				<select id="wc-tp-payroll-salary-type-filter">
					<option value=""><?php esc_html_e( 'All Types', 'wc-team-payroll' ); ?></option>
					<option value="commission"><?php esc_html_e( 'Commission Based', 'wc-team-payroll' ); ?></option>
					<option value="fixed"><?php esc_html_e( 'Fixed Salary', 'wc-team-payroll' ); ?></option>
					<option value="combined"><?php esc_html_e( 'Combined (Base + Commission)', 'wc-team-payroll' ); ?></option>
				</select>
			</div>

			<!-- Payroll Table Section -->
			<div class="wc-tp-table-section" id="wc-tp-payroll-table-section">
				<div class="wc-tp-items-per-page">
					<h2><?php esc_html_e( 'Payroll Details', 'wc-team-payroll' ); ?></h2>
					<div>
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
				<div id="wc-tp-payroll-pagination"></div>
			</div>
		</div>
		<?php
	}
}
