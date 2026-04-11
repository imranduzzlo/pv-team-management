<?php
/**
 * Employee Detail Page - Profile, Orders, Salary, Payments
 */

class WC_Team_Payroll_Employee_Detail {

	public function __construct() {
		// Enqueue common CSS and JS on admin pages
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue common CSS and JS
	 */
	public function enqueue_assets( $hook ) {
		// Only load on employee detail page
		if ( strpos( $hook, 'wc-team-payroll-employee-detail' ) === false ) {
			return;
		}

		wp_enqueue_style( 'wc-tp-common-css', WC_TEAM_PAYROLL_URL . 'assets/css/common.css', array(), WC_TEAM_PAYROLL_VERSION );
		wp_enqueue_script( 'wc-tp-common-js', WC_TEAM_PAYROLL_URL . 'assets/js/common.js', array( 'jquery' ), WC_TEAM_PAYROLL_VERSION, true );
	}

	public function render_employee_detail() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized', 'wc-team-payroll' ) );
		}

		$user_id = isset( $_GET['user_id'] ) ? intval( $_GET['user_id'] ) : 0;
		if ( ! $user_id ) {
			wp_die( esc_html__( 'User not found', 'wc-team-payroll' ) );
		}

		$user = get_user_by( 'ID', $user_id );
		if ( ! $user ) {
			wp_die( esc_html__( 'User not found', 'wc-team-payroll' ) );
		}

		$vb_user_id = get_user_meta( $user_id, 'vb_user_id', true );
		$profile_picture_id = get_user_meta( $user_id, '_wc_tp_profile_picture', true );
		$profile_picture_url = '';
		
		if ( $profile_picture_id ) {
			$profile_picture_url = wp_get_attachment_url( $profile_picture_id );
		}

		$employee_mgmt = new WC_Team_Payroll_Employee_Management();
		$user_phone = get_user_meta( $user_id, 'billing_phone', true );
		$user_address = get_user_meta( $user_id, 'billing_address_1', true );
		$user_bio = get_user_meta( $user_id, 'description', true );

		// Get dashboard stats
		$core_engine = new WC_Team_Payroll_Core_Engine();
		$total_orders = $core_engine->get_user_total_orders( $user_id );
		$total_earnings = $core_engine->get_user_total_earnings( $user_id );
		$total_paid = $core_engine->get_user_total_paid( $user_id );
		$total_due = $total_earnings - $total_paid;

		$wc_currency = get_woocommerce_currency();
		$wc_currency_symbol = get_woocommerce_currency_symbol( $wc_currency );
		$wc_currency_pos = get_option( 'woocommerce_currency_pos', 'left' );

		?>
		<div class="wrap wc-team-payroll-employee-detail">
			<!-- Back Button -->
			<div style="margin-bottom: 20px;">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-team-payroll-employees' ) ); ?>" class="button button-secondary">← <?php esc_html_e( 'Back to Team Members', 'wc-team-payroll' ); ?></a>
			</div>

			<!-- Profile Header Section -->
			<div class="wc-tp-profile-header">
				<div class="wc-tp-profile-picture-container">
					<?php if ( $profile_picture_url ) : ?>
						<img src="<?php echo esc_url( $profile_picture_url ); ?>" alt="<?php echo esc_attr( $user->display_name ); ?>" class="wc-tp-profile-picture" />
					<?php else : ?>
						<div class="wc-tp-profile-picture-placeholder">
							<span><?php echo esc_html( substr( $user->display_name, 0, 1 ) ); ?></span>
						</div>
					<?php endif; ?>
				</div>

				<div class="wc-tp-profile-info">
					<div class="wc-tp-profile-name-section">
						<h1><?php echo esc_html( $user->display_name ); ?></h1>
						<span class="wc-tp-vb-user-id"><?php echo esc_html( $vb_user_id ); ?></span>
					</div>

					<div class="wc-tp-profile-details">
						<div class="wc-tp-detail-item">
							<span class="label">Email:</span>
							<span class="value"><?php echo esc_html( $user->user_email ); ?></span>
						</div>
						<?php if ( $user_phone ) : ?>
							<div class="wc-tp-detail-item">
								<span class="label">Phone:</span>
								<span class="value"><?php echo esc_html( $user_phone ); ?></span>
							</div>
						<?php endif; ?>
						<?php if ( $user_address ) : ?>
							<div class="wc-tp-detail-item">
								<span class="label">Address:</span>
								<span class="value"><?php echo esc_html( $user_address ); ?></span>
							</div>
						<?php endif; ?>
						<?php if ( $user_bio ) : ?>
							<div class="wc-tp-detail-item">
								<span class="label">Bio:</span>
								<span class="value"><?php echo esc_html( $user_bio ); ?></span>
							</div>
						<?php endif; ?>
					</div>
				</div>

				<div class="wc-tp-profile-actions">
					<a href="<?php echo esc_url( admin_url( 'user-edit.php?user_id=' . $user_id ) ); ?>" class="button button-primary">
						<?php esc_html_e( 'Edit Profile', 'wc-team-payroll' ); ?>
					</a>
				</div>
			</div>

			<!-- Dashboard Cards Section -->
			<div class="wc-tp-stats-cards">
				<div class="wc-tp-stat-card">
					<div class="wc-tp-stat-icon">📦</div>
					<div class="wc-tp-stat-content">
						<span class="wc-tp-stat-label"><?php esc_html_e( 'Total Orders', 'wc-team-payroll' ); ?></span>
						<span class="wc-tp-stat-value"><?php echo esc_html( $total_orders ); ?></span>
					</div>
				</div>

				<div class="wc-tp-stat-card">
					<div class="wc-tp-stat-icon">💰</div>
					<div class="wc-tp-stat-content">
						<span class="wc-tp-stat-label"><?php esc_html_e( 'Total Earnings', 'wc-team-payroll' ); ?></span>
						<span class="wc-tp-stat-value"><?php echo esc_html( $this->format_currency( $total_earnings, $wc_currency_symbol, $wc_currency_pos ) ); ?></span>
					</div>
				</div>

				<div class="wc-tp-stat-card">
					<div class="wc-tp-stat-icon">✅</div>
					<div class="wc-tp-stat-content">
						<span class="wc-tp-stat-label"><?php esc_html_e( 'Total Paid', 'wc-team-payroll' ); ?></span>
						<span class="wc-tp-stat-value"><?php echo esc_html( $this->format_currency( $total_paid, $wc_currency_symbol, $wc_currency_pos ) ); ?></span>
					</div>
				</div>

				<div class="wc-tp-stat-card">
					<div class="wc-tp-stat-icon">⏳</div>
					<div class="wc-tp-stat-content">
						<span class="wc-tp-stat-label"><?php esc_html_e( 'Total Due', 'wc-team-payroll' ); ?></span>
						<span class="wc-tp-stat-value"><?php echo esc_html( $this->format_currency( $total_due, $wc_currency_symbol, $wc_currency_pos ) ); ?></span>
					</div>
				</div>
			</div>

			<!-- Tabs Section -->
			<div class="wc-tp-tabs-container">
				<div class="wc-tp-tabs-nav">
					<button class="wc-tp-tab-button wc-tp-tab-active" data-tab="orders">
						<?php esc_html_e( 'Orders', 'wc-team-payroll' ); ?>
					</button>
					<button class="wc-tp-tab-button" data-tab="salary">
						<?php esc_html_e( 'Salary Management', 'wc-team-payroll' ); ?>
					</button>
					<button class="wc-tp-tab-button" data-tab="payments">
						<?php esc_html_e( 'Payments', 'wc-team-payroll' ); ?>
					</button>
				</div>

				<!-- Orders Tab -->
				<div class="wc-tp-tab-content wc-tp-tab-active" id="orders-tab">
					<!-- Search Filter -->
				<div class="wc-tp-orders-search-filter">
					<input type="text" id="wc-tp-orders-search" placeholder="<?php esc_attr_e( 'Search by Order ID, Customer Name, Email, Phone...', 'wc-team-payroll' ); ?>" />
					<button type="button" class="button button-secondary" id="wc-tp-orders-search-clear"><?php esc_html_e( 'Clear', 'wc-team-payroll' ); ?></button>
				</div>

				<!-- Filters Row -->
				<div class="wc-tp-orders-filters">
					<div class="wc-tp-filter-group">
						<label><?php esc_html_e( 'Date Range:', 'wc-team-payroll' ); ?></label>
						<input type="date" id="wc-tp-orders-start-date" value="<?php echo esc_attr( date( 'Y-m-01' ) ); ?>" />
						<span class="wc-tp-date-separator">to</span>
						<input type="date" id="wc-tp-orders-end-date" value="<?php echo esc_attr( date( 'Y-m-t' ) ); ?>" />
					</div>

					<div class="wc-tp-filter-group">
						<label><?php esc_html_e( 'Status:', 'wc-team-payroll' ); ?></label>
						<select id="wc-tp-orders-status-filter">
							<option value=""><?php esc_html_e( 'All Statuses', 'wc-team-payroll' ); ?></option>
							<option value="completed"><?php esc_html_e( 'Completed', 'wc-team-payroll' ); ?></option>
							<option value="processing"><?php esc_html_e( 'Processing', 'wc-team-payroll' ); ?></option>
							<option value="pending"><?php esc_html_e( 'Pending', 'wc-team-payroll' ); ?></option>
							<option value="cancelled"><?php esc_html_e( 'Cancelled', 'wc-team-payroll' ); ?></option>
							<option value="refunded"><?php esc_html_e( 'Refunded', 'wc-team-payroll' ); ?></option>
						</select>
					</div>

					<div class="wc-tp-filter-group">
						<label><?php esc_html_e( 'Flag:', 'wc-team-payroll' ); ?></label>
						<select id="wc-tp-orders-flag-filter">
							<option value=""><?php esc_html_e( 'All Flags', 'wc-team-payroll' ); ?></option>
							<option value="owner"><?php esc_html_e( 'Order Owner', 'wc-team-payroll' ); ?></option>
							<option value="affiliate_to"><?php esc_html_e( 'Affiliate To', 'wc-team-payroll' ); ?></option>
							<option value="affiliate_from"><?php esc_html_e( 'Affiliate From', 'wc-team-payroll' ); ?></option>
						</select>
					</div>

					<button type="button" class="button button-primary" id="wc-tp-orders-filter-btn"><?php esc_html_e( 'Filter', 'wc-team-payroll' ); ?></button>
				</div>

				<!-- Orders Table -->
				<div id="wc-tp-orders-table-container" style="margin-top: 20px;">
					<!-- Content will be loaded via AJAX -->
				</div>

				<!-- Pagination -->
				<div id="wc-tp-orders-pagination" style="margin-top: 20px; text-align: center;"></div>
				</div>

				<!-- Salary Tab -->
				<div class="wc-tp-tab-content" id="salary-tab">
					<p><?php esc_html_e( 'Salary management tab content coming soon...', 'wc-team-payroll' ); ?></p>
				</div>

				<!-- Payments Tab -->
				<div class="wc-tp-tab-content" id="payments-tab">
					<p><?php esc_html_e( 'Payments tab content coming soon...', 'wc-team-payroll' ); ?></p>
				</div>
			</div>
		</div>

		<?php
	}

	/**
	 * Format currency
	 */
	private function format_currency( $amount, $symbol, $position ) {
		$formatted = number_format( $amount, 2 );
		if ( $position === 'right' ) {
			return $formatted . ' ' . $symbol;
		} else {
			return $symbol . ' ' . $formatted;
		}
	}
}

