<?php
/**
 * Employee Detail Page - Profile, Orders, Salary, Payments
 */

class WC_Team_Payroll_Employee_Detail {

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
					<button class="wc-tp-tab-button" data-tab="payments">
						<?php esc_html_e( 'Payments', 'wc-team-payroll' ); ?>
					</button>
					<button class="wc-tp-tab-button" data-tab="salary">
						<?php esc_html_e( 'Salary Management', 'wc-team-payroll' ); ?>
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
						<select id="wc-tp-orders-date-preset">
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
					<!-- Salary Management Form -->
					<div class="wc-tp-salary-form-section" style="background: var(--color-card-bg); border: 1px solid var(--color-border-light); border-radius: 8px; padding: 24px; margin-bottom: 30px;">
						<h3 style="margin-top: 0; margin-bottom: 20px; color: var(--text-main); border-left: 4px solid var(--color-primary); padding-left: 12px;"><?php esc_html_e( 'Salary Management', 'wc-team-payroll' ); ?></h3>
						
						<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px; margin-bottom: 20px;">
							<div class="wc-tp-form-group">
								<label for="wc-tp-salary-type" style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-main);"><?php esc_html_e( 'Salary Type', 'wc-team-payroll' ); ?></label>
								<select id="wc-tp-salary-type" style="width: 100%; padding: 8px 12px; border: 1px solid var(--color-border-light); border-radius: 6px; font-size: 14px;">
									<option value="commission"><?php esc_html_e( 'Commission Based', 'wc-team-payroll' ); ?></option>
									<option value="fixed"><?php esc_html_e( 'Fixed Salary', 'wc-team-payroll' ); ?></option>
									<option value="combined"><?php esc_html_e( 'Combined (Base + Commission)', 'wc-team-payroll' ); ?></option>
								</select>
							</div>

							<div class="wc-tp-form-group" id="wc-tp-salary-amount-group" style="display: none;">
								<label for="wc-tp-salary-amount" style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-main);"><?php esc_html_e( 'Salary Amount', 'wc-team-payroll' ); ?></label>
								<input type="number" id="wc-tp-salary-amount" placeholder="0.00" step="0.01" min="0" style="width: 100%; padding: 8px 12px; border: 1px solid var(--color-border-light); border-radius: 6px; font-size: 14px;" />
							</div>

							<div class="wc-tp-form-group" id="wc-tp-salary-frequency-group" style="display: none;">
								<label for="wc-tp-salary-frequency" style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-main);"><?php esc_html_e( 'Salary Frequency', 'wc-team-payroll' ); ?></label>
								<select id="wc-tp-salary-frequency" style="width: 100%; padding: 8px 12px; border: 1px solid var(--color-border-light); border-radius: 6px; font-size: 14px;">
									<option value="daily"><?php esc_html_e( 'Daily', 'wc-team-payroll' ); ?></option>
									<option value="weekly"><?php esc_html_e( 'Weekly', 'wc-team-payroll' ); ?></option>
									<option value="monthly" selected><?php esc_html_e( 'Monthly', 'wc-team-payroll' ); ?></option>
									<option value="yearly"><?php esc_html_e( 'Yearly', 'wc-team-payroll' ); ?></option>
								</select>
							</div>
						</div>

						<button type="button" class="button button-primary" id="wc-tp-update-salary-btn"><?php esc_html_e( 'Update Salary', 'wc-team-payroll' ); ?></button>
					</div>

					<!-- Salary History Section -->
					<div class="wc-tp-salary-history-section" style="background: var(--color-card-bg); border: 1px solid var(--color-border-light); border-radius: 8px; padding: 24px;">
						<h3 style="margin-top: 0; margin-bottom: 20px; color: var(--text-main); border-left: 4px solid var(--color-primary); padding-left: 12px;"><?php esc_html_e( 'Salary History', 'wc-team-payroll' ); ?></h3>
						
						<div id="wc-tp-salary-history-container">
							<!-- Salary history will be loaded via AJAX -->
						</div>
					</div>
				</div>

				<!-- Payments Tab -->
				<div class="wc-tp-tab-content" id="payments-tab">
					<!-- Payment Stats Cards -->
					<div class="wc-tp-stats-cards" style="margin-bottom: 30px;">
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

					<!-- Payment Methods Section -->
					<div class="wc-tp-payment-methods-section" style="background: var(--color-card-bg); border: 1px solid var(--color-border-light); border-radius: 8px; padding: 24px; margin-bottom: 30px;">
						<h3 style="margin-top: 0; margin-bottom: 20px; color: var(--text-main); border-left: 4px solid var(--color-primary); padding-left: 12px;"><?php esc_html_e( 'Payment Methods', 'wc-team-payroll' ); ?></h3>
						
						<div id="wc-tp-payment-methods-list" class="wc-tp-payment-methods-list">
							<!-- Payment methods will be loaded via AJAX -->
						</div>

						<button type="button" class="button button-primary" id="wc-tp-add-payment-method-btn" style="margin-top: 20px;">
							<?php esc_html_e( '+ Add Payment Method', 'wc-team-payroll' ); ?>
						</button>
					</div>

					<!-- Add Payment Form -->
					<div class="wc-tp-add-payment-section" style="background: var(--color-card-bg); border: 1px solid var(--color-border-light); border-radius: 8px; padding: 24px; margin-bottom: 30px;">
						<h3 style="margin-top: 0; margin-bottom: 20px; color: var(--text-main); border-left: 4px solid var(--color-primary); padding-left: 12px;"><?php esc_html_e( 'Add Payment', 'wc-team-payroll' ); ?></h3>
						
						<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px; margin-bottom: 20px;">
							<div class="wc-tp-form-group">
								<label for="wc-tp-payment-amount" style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-main);"><?php esc_html_e( 'Amount', 'wc-team-payroll' ); ?></label>
								<input type="number" id="wc-tp-payment-amount" placeholder="0.00" step="0.01" min="0" style="width: 100%; padding: 8px 12px; border: 1px solid var(--color-border-light); border-radius: 6px; font-size: 14px;" />
							</div>

							<div class="wc-tp-form-group">
								<label for="wc-tp-payment-date" style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-main);"><?php esc_html_e( 'Payment Date', 'wc-team-payroll' ); ?></label>
								<input type="datetime-local" id="wc-tp-payment-date" value="<?php echo esc_attr( date( 'Y-m-d\TH:i' ) ); ?>" style="width: 100%; padding: 8px 12px; border: 1px solid var(--color-border-light); border-radius: 6px; font-size: 14px;" />
							</div>
						</div>

						<button type="button" class="button button-primary" id="wc-tp-add-payment-btn"><?php esc_html_e( 'Add Payment', 'wc-team-payroll' ); ?></button>
					</div>

					<!-- Payment History Section -->
					<div class="wc-tp-payment-history-section" style="background: var(--color-card-bg); border: 1px solid var(--color-border-light); border-radius: 8px; padding: 24px;">
						<h3 style="margin-top: 0; margin-bottom: 20px; color: var(--text-main); border-left: 4px solid var(--color-primary); padding-left: 12px;"><?php esc_html_e( 'Payment History', 'wc-team-payroll' ); ?></h3>
						
						<div id="wc-tp-payment-history-container">
							<!-- Payment history will be loaded via AJAX -->
						</div>
					</div>
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

			.wc-team-payroll-employee-detail {
				background: var(--color-site-bg);
				padding: 24px;
				font-family: var(--font-family);
				color: var(--text-main);
			}

			.wc-tp-profile-header {
				background: var(--color-card-bg);
				border: 1px solid var(--color-border-light);
				border-radius: 8px;
				padding: 24px;
				margin-bottom: 24px;
				display: flex;
				gap: 24px;
				align-items: flex-start;
			}

			.wc-tp-profile-picture-container {
				flex-shrink: 0;
			}

			.wc-tp-profile-picture {
				width: 120px;
				height: 120px;
				border-radius: 8px;
				object-fit: cover;
				border: 2px solid var(--color-border-light);
			}

			.wc-tp-profile-picture-placeholder {
				width: 120px;
				height: 120px;
				border-radius: 8px;
				background: var(--color-primary-subtle);
				border: 2px solid var(--color-border-light);
				display: flex;
				align-items: center;
				justify-content: center;
				font-size: 48px;
				font-weight: var(--fw-bold);
				color: var(--color-primary);
			}

			.wc-tp-profile-info {
				flex: 1;
			}

			.wc-tp-profile-name-section {
				margin-bottom: 16px;
			}

			.wc-tp-profile-name-section h1 {
				margin: 0 0 8px 0;
				font-size: var(--fs-h1);
				font-weight: var(--fw-bold);
				color: var(--text-main);
			}

			.wc-tp-vb-user-id {
				display: inline-block;
				background: var(--color-primary-subtle);
				color: var(--color-primary);
				padding: 4px 12px;
				border-radius: 4px;
				font-size: var(--fs-meta);
				font-weight: var(--fw-semibold);
			}

			.wc-tp-profile-details {
				display: grid;
				grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
				gap: 12px;
			}

			.wc-tp-detail-item {
				display: flex;
				flex-direction: column;
				gap: 4px;
			}

			.wc-tp-detail-item .label {
				font-size: var(--fs-meta);
				font-weight: var(--fw-semibold);
				color: var(--text-muted);
				text-transform: uppercase;
				letter-spacing: 0.5px;
			}

			.wc-tp-detail-item .value {
				font-size: var(--fs-body);
				color: var(--text-body);
			}

			.wc-tp-profile-actions {
				flex-shrink: 0;
			}

			.wc-tp-stats-cards {
				display: grid;
				grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
				gap: 16px;
				margin-bottom: 24px;
			}

			.wc-tp-stat-card {
				background: var(--color-card-bg);
				border: 1px solid var(--color-border-light);
				border-radius: 8px;
				padding: 20px;
				display: flex;
				gap: 16px;
				align-items: center;
			}

			.wc-tp-stat-icon {
				font-size: 32px;
				flex-shrink: 0;
			}

			.wc-tp-stat-content {
				display: flex;
				flex-direction: column;
				gap: 4px;
			}

			.wc-tp-stat-label {
				font-size: var(--fs-meta);
				color: var(--text-muted);
				font-weight: var(--fw-medium);
			}

			.wc-tp-stat-value {
				font-size: 1.5rem;
				font-weight: var(--fw-bold);
				color: var(--text-main);
			}

			.wc-tp-tabs-container {
				background: var(--color-card-bg);
				border: 1px solid var(--color-border-light);
				border-radius: 8px;
				overflow: hidden;
			}

			.wc-tp-tabs-nav {
				display: flex;
				border-bottom: 1px solid var(--color-border-light);
				background: var(--color-accent-muted);
			}

			.wc-tp-tab-button {
				flex: 1;
				padding: 16px;
				border: none;
				background: transparent;
				color: var(--text-body);
				font-size: var(--fs-body);
				font-weight: var(--fw-semibold);
				cursor: pointer;
				transition: all 0.2s ease;
				border-bottom: 3px solid transparent;
				margin-bottom: -1px;
			}

			.wc-tp-tab-button:hover {
				background: var(--color-primary-subtle);
				color: var(--color-primary);
			}

			.wc-tp-tab-button.wc-tp-tab-active {
				background: var(--color-primary-subtle);
				color: var(--color-primary);
				border-bottom-color: var(--color-primary);
			}

			.wc-tp-tab-content {
				display: none;
				padding: 24px;
			}

			.wc-tp-tab-content.wc-tp-tab-active {
				display: block;
			}

			/* Orders Tab Styles */
			.wc-tp-orders-search-filter {
				background: var(--color-accent-muted);
				padding: 16px;
				border-radius: 8px;
				margin-bottom: 16px;
				display: flex;
				gap: 12px;
				align-items: center;
				flex-wrap: wrap;
			}

			.wc-tp-orders-search-filter input[type="text"] {
				flex: 1;
				min-width: 250px;
				padding: 8px 12px;
				border: 1px solid var(--color-border-light);
				border-radius: 6px;
				font-size: var(--fs-body);
				font-family: var(--font-family);
				color: var(--text-main);
			}

			.wc-tp-orders-search-filter input[type="text"]::placeholder {
				color: var(--text-muted);
			}

			.wc-tp-orders-search-filter .button-secondary {
				background: var(--color-card-bg);
				border-color: var(--color-border-light);
				color: var(--text-main);
				font-weight: var(--fw-semibold);
				border-radius: 6px;
				padding: 8px 16px;
				font-size: var(--fs-meta);
				transition: all 0.2s ease;
			}

			.wc-tp-orders-search-filter .button-secondary:hover {
				background: var(--color-border-light);
				border-color: var(--color-border-light);
			}

			.wc-tp-orders-filters {
				background: var(--color-accent-muted);
				padding: 16px;
				border-radius: 8px;
				margin-bottom: 20px;
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
				font-size: var(--fs-meta);
				font-weight: var(--fw-semibold);
				color: var(--text-main);
			}

			.wc-tp-filter-group input[type="date"],
			.wc-tp-filter-group select {
				padding: 8px 12px;
				border: 1px solid var(--color-border-light);
				border-radius: 6px;
				font-size: var(--fs-body);
				font-family: var(--font-family);
				color: var(--text-main);
				background: var(--color-card-bg);
			}

			.wc-tp-date-separator {
				color: var(--text-muted);
				font-weight: var(--fw-medium);
				padding: 0 8px;
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

			.wc-tp-data-table td {
				padding: 12px;
				border-bottom: 1px solid var(--color-border-light);
				font-size: var(--fs-body);
				color: var(--text-body);
			}

			.wc-tp-data-table tbody tr:hover {
				background: var(--color-primary-subtle);
			}

			.wc-tp-flag-badge {
				display: inline-block;
				padding: 4px 8px;
				border-radius: 4px;
				font-size: var(--fs-small);
				font-weight: var(--fw-semibold);
				white-space: nowrap;
			}

			.wc-tp-flag-owner {
				background: #E3F2FD;
				color: #1976D2;
			}

			.wc-tp-flag-affiliate-to {
				background: #F3E5F5;
				color: #7B1FA2;
			}

			.wc-tp-flag-affiliate-from {
				background: #E8F5E9;
				color: #388E3C;
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

			@media (max-width: 768px) {
				.wc-team-payroll-employee-detail {
					padding: 12px;
				}

				.wc-tp-profile-header {
					flex-direction: column;
					padding: 16px;
					gap: 16px;
				}

				.wc-tp-profile-picture {
					width: 100px;
					height: 100px;
				}

				.wc-tp-profile-picture-placeholder {
					width: 100px;
					height: 100px;
					font-size: 40px;
				}

				.wc-tp-profile-details {
					grid-template-columns: 1fr;
				}

				.wc-tp-stats-cards {
					grid-template-columns: repeat(2, 1fr);
					gap: 12px;
				}

				.wc-tp-stat-card {
					padding: 16px;
				}

				.wc-tp-stat-icon {
					font-size: 24px;
				}

				.wc-tp-stat-value {
					font-size: 1.25rem;
				}

				.wc-tp-tabs-nav {
					flex-wrap: wrap;
				}

				.wc-tp-tab-button {
					flex: 1;
					min-width: 100px;
					padding: 12px;
					font-size: var(--fs-meta);
				}

				.wc-tp-tab-content {
					padding: 16px;
				}

				.wc-tp-orders-search-filter {
					flex-direction: column;
					gap: 8px;
				}

				.wc-tp-orders-search-filter input[type="text"] {
					width: 100%;
					min-width: unset;
				}

				.wc-tp-orders-search-filter .button-secondary {
					width: 100%;
				}

				.wc-tp-orders-filters {
					flex-direction: column;
					gap: 8px;
				}

				.wc-tp-filter-group input[type="date"],
				.wc-tp-filter-group select {
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
			}

			@media (max-width: 480px) {
				.wc-tp-stats-cards {
					grid-template-columns: 1fr;
				}

				.wc-tp-stat-card {
					flex-direction: column;
					text-align: center;
				}

				.wc-tp-stat-icon {
					font-size: 28px;
				}
			}
		</style>

		<script>
			jQuery(document).ready(function($) {
				const userId = <?php echo intval( $user_id ); ?>;
				const wcCurrencySymbol = '<?php echo esc_js( $wc_currency_symbol ); ?>';
				const wcCurrencyPos = '<?php echo esc_js( $wc_currency_pos ); ?>';
				let currentPage = 1;
				let allOrdersData = [];
				let itemsPerPage = 20;
				let lastPresetRange = { start: '', end: '' }; // Store last preset range

				// Helper functions first
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
					$('#wc-tp-orders-start-date').val(range.start);
					$('#wc-tp-orders-end-date').val(range.end);
				}

				function formatCurrency(value) {
					const amount = parseFloat(value).toFixed(2);
					if (wcCurrencyPos === 'right') {
						return amount + ' ' + wcCurrencySymbol;
					} else {
						return wcCurrencySymbol + ' ' + amount;
					}
				}

				// Initialize with default preset (This Month)
				updateDateRangeFromPreset('this-month');

				// Date preset change
				$('#wc-tp-orders-date-preset').on('change', function() {
					const preset = $(this).val();
					
					if (preset === 'custom') {
						// Show custom date inputs with last preset values
						$('#wc-tp-custom-date-range').slideDown(200);
					} else {
						// Hide custom date inputs and update dates
						$('#wc-tp-custom-date-range').slideUp(200);
						updateDateRangeFromPreset(preset);
						// Don't load here, wait for Filter button click
					}
				});

				// Custom date range change - just update values, don't load
				$('#wc-tp-orders-start-date, #wc-tp-orders-end-date').on('change', function() {
					// Just update the values, don't trigger load
				});

				// Load orders data function
				function loadOrdersData() {
					const startDate = $('#wc-tp-orders-start-date').val();
					const endDate = $('#wc-tp-orders-end-date').val();
					const statusFilter = $('#wc-tp-orders-status-filter').val();
					const flagFilter = $('#wc-tp-orders-flag-filter').val();
					const searchQuery = $('#wc-tp-orders-search').val();

					if (!startDate || !endDate) {
						return;
					}

					$.ajax({
						url: ajaxurl,
						type: 'POST',
						data: {
							action: 'wc_tp_get_employee_orders',
							user_id: userId,
							start_date: startDate,
							end_date: endDate,
							status: statusFilter,
							flag: flagFilter,
							search: searchQuery
						},
						success: function(response) {
							if (response.success) {
								allOrdersData = response.data.orders || [];
								renderOrdersTable(allOrdersData);
								renderOrdersPagination(allOrdersData);
							}
						},
						error: function() {
							// Silent error handling
						}
					});
				}

				// Load orders on page load (since orders tab is active by default)
				loadOrdersData();

				// Tab switching
				$('.wc-tp-tab-button').on('click', function() {
					const tabName = $(this).data('tab');
					
					// Remove active class from all buttons and contents
					$('.wc-tp-tab-button').removeClass('wc-tp-tab-active');
					$('.wc-tp-tab-content').removeClass('wc-tp-tab-active');
					
					// Add active class to clicked button and corresponding content
					$(this).addClass('wc-tp-tab-active');
					$('#' + tabName + '-tab').addClass('wc-tp-tab-active');

					// Load tab-specific data
					if (tabName === 'orders') {
						loadOrdersData();
					} else if (tabName === 'payments') {
						loadPaymentMethods();
						loadPaymentHistory();
					} else if (tabName === 'salary') {
						loadSalaryData();
						loadSalaryHistory();
					}
				});

				// Orders Tab Functionality
				$('#wc-tp-orders-filter-btn').on('click', function() {
					currentPage = 1;
					loadOrdersData();
				});

				$('#wc-tp-orders-search').on('keyup', function() {
					currentPage = 1;
					loadOrdersData();
				});

				$('#wc-tp-orders-search-clear').on('click', function() {
					$('#wc-tp-orders-search').val('');
					currentPage = 1;
					loadOrdersData();
				});

				function renderOrdersTable(orders) {
					const container = $('#wc-tp-orders-table-container');
					
					if (!orders || orders.length === 0) {
						container.html('<div class="wc-tp-empty-state"><div class="wc-tp-empty-icon">📭</div><p>No orders found</p></div>');
						return;
					}

					const startIndex = (currentPage - 1) * itemsPerPage;
					const endIndex = startIndex + itemsPerPage;
					const pageData = orders.slice(startIndex, endIndex);

					let html = '<table class="wc-tp-data-table"><thead><tr>';
					html += '<th>Order ID</th>';
					html += '<th>Customer</th>';
					html += '<th>Total</th>';
					html += '<th>Status</th>';
					html += '<th>Commission</th>';
					html += '<th>Your Earnings</th>';
					html += '<th>Flag</th>';
					html += '<th>Date</th>';
					html += '<th>Action</th>';
					html += '</tr></thead><tbody>';

					$.each(pageData, function(i, order) {
						html += '<tr>';
						html += '<td><strong>#' + order.order_id + '</strong></td>';
						html += '<td>' + order.customer_name + '</td>';
						html += '<td>' + formatCurrency(order.total) + '</td>';
						html += '<td><span class="wc-tp-badge">' + order.status + '</span></td>';
						html += '<td>' + formatCurrency(order.commission) + '</td>';
						html += '<td><strong>' + formatCurrency(order.user_earnings) + '</strong></td>';
						html += '<td><span class="wc-tp-flag-badge wc-tp-flag-' + order.flag.toLowerCase().replace(/_/g, '-') + '">' + order.flag_label + '</span></td>';
						html += '<td>' + order.date + '</td>';
						html += '<td>';
						html += '<a href="' + ajaxurl.replace('admin-ajax.php', 'admin.php?page=wc-team-payroll-order-detail&order_id=' + order.order_id) + '" class="button button-small" title="View">👁️</a> ';
						html += '<a href="' + ajaxurl.replace('admin-ajax.php', 'post.php?post=' + order.order_id + '&action=edit') + '" class="button button-small" title="Edit">✏️</a>';
						html += '</td>';
						html += '</tr>';
					});

					html += '</tbody></table>';
					container.html(html);
				}

				function renderOrdersPagination(orders) {
					const container = $('#wc-tp-orders-pagination');
					const totalPages = Math.ceil(orders.length / itemsPerPage);

					if (totalPages <= 1) {
						container.html('');
						return;
					}

					let html = '<div class="wc-tp-pagination">';

					if (currentPage > 1) {
						html += '<a href="#" data-page="' + (currentPage - 1) + '">← Previous</a>';
					}

					for (let i = 1; i <= totalPages; i++) {
						if (i === currentPage) {
							html += '<span class="current">' + i + '</span>';
						} else {
							html += '<a href="#" data-page="' + i + '">' + i + '</a>';
						}
					}

					if (currentPage < totalPages) {
						html += '<a href="#" data-page="' + (currentPage + 1) + '">Next →</a>';
					}

					html += '</div>';
					container.html(html);

					container.find('a').on('click', function(e) {
						e.preventDefault();
						currentPage = parseInt($(this).data('page'));
						renderOrdersTable(allOrdersData);
						renderOrdersPagination(allOrdersData);
						$('html, body').animate({ scrollTop: $('#wc-tp-orders-table-container').offset().top - 100 }, 300);
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

				// Load payment methods
				function loadPaymentMethods() {
					$.ajax({
						url: ajaxurl,
						type: 'POST',
						data: {
							action: 'wc_tp_get_payment_methods',
							user_id: userId
						},
						success: function(response) {
							if (response.success) {
								renderPaymentMethods(response.data.methods || []);
							}
						}
					});
				}

				// Render payment methods
				function renderPaymentMethods(methods) {
					const container = $('#wc-tp-payment-methods-list');
					
					if (!methods || methods.length === 0) {
						container.html('<p style="color: var(--text-muted); text-align: center; padding: 20px;">No payment methods added yet</p>');
						return;
					}

					let html = '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 16px;">';
					
					$.each(methods, function(i, method) {
						html += '<div style="background: var(--color-accent-muted); border: 1px solid var(--color-border-light); border-radius: 8px; padding: 16px;">';
						html += '<div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 12px;">';
						html += '<div>';
						html += '<strong style="display: block; margin-bottom: 4px; color: var(--text-main);">' + method.method_name + '</strong>';
						html += '<span style="font-size: 13px; color: var(--text-muted);">' + method.method_details + '</span>';
						html += '</div>';
						html += '<button type="button" class="button button-small" onclick="deletePaymentMethod(' + userId + ', ' + method.id + ')" style="background: #dc3545; border-color: #dc3545; color: white;">Delete</button>';
						html += '</div>';
						html += '</div>';
					});

					html += '</div>';
					container.html(html);
				}

				// Add payment method
				$('#wc-tp-add-payment-method-btn').on('click', function() {
					const methodName = prompt('<?php esc_js_e( 'Enter payment method name (e.g., bKash Personal)', 'wc-team-payroll' ); ?>');
					if (!methodName) return;

					const methodDetails = prompt('<?php esc_js_e( 'Enter payment details (e.g., bKash number, account info)', 'wc-team-payroll' ); ?>');
					if (!methodDetails) return;

					$.ajax({
						url: ajaxurl,
						type: 'POST',
						data: {
							action: 'wc_tp_add_payment_method',
							user_id: userId,
							method_name: methodName,
							method_details: methodDetails
						},
						success: function(response) {
							if (response.success) {
								loadPaymentMethods();
								alert('<?php esc_js_e( 'Payment method added', 'wc-team-payroll' ); ?>');
							}
						}
					});
				});

				// Delete payment method
				window.deletePaymentMethod = function(userId, methodId) {
					if (!confirm('<?php esc_js_e( 'Delete this payment method?', 'wc-team-payroll' ); ?>')) return;

					$.ajax({
						url: ajaxurl,
						type: 'POST',
						data: {
							action: 'wc_tp_delete_payment_method',
							user_id: userId,
							method_id: methodId
						},
						success: function(response) {
							if (response.success) {
								loadPaymentMethods();
								alert('<?php esc_js_e( 'Payment method deleted', 'wc-team-payroll' ); ?>');
							}
						}
					});
				};

				// Load payment history
				function loadPaymentHistory() {
					$.ajax({
						url: ajaxurl,
						type: 'POST',
						data: {
							action: 'wc_tp_get_employee_payments',
							user_id: userId
						},
						success: function(response) {
							if (response.success) {
								renderPaymentHistory(response.data.payments || []);
							}
						}
					});
				}

				// Render payment history
				function renderPaymentHistory(payments) {
					const container = $('#wc-tp-payment-history-container');
					
					if (!payments || payments.length === 0) {
						container.html('<div class="wc-tp-empty-state"><div class="wc-tp-empty-icon">💳</div><p>No payments recorded</p></div>');
						return;
					}

					let html = '<table class="wc-tp-data-table"><thead><tr>';
					html += '<th>Date</th>';
					html += '<th>Amount</th>';
					html += '<th>Added By</th>';
					html += '<th>Action</th>';
					html += '</tr></thead><tbody>';

					$.each(payments, function(i, payment) {
						html += '<tr>';
						html += '<td>' + payment.date + '</td>';
						html += '<td><strong>' + formatCurrency(payment.amount) + '</strong></td>';
						html += '<td>' + payment.added_by + '</td>';
						html += '<td><button type="button" class="button button-small" onclick="deletePayment(' + userId + ', \'' + payment.id + '\')" style="background: #dc3545; border-color: #dc3545; color: white;">Delete</button></td>';
						html += '</tr>';
					});

					html += '</tbody></table>';
					container.html(html);
				}

				// Add payment
				$('#wc-tp-add-payment-btn').on('click', function() {
					const amount = $('#wc-tp-payment-amount').val();
					const date = $('#wc-tp-payment-date').val();

					if (!amount || !date) {
						alert('<?php esc_js_e( 'Please fill all fields', 'wc-team-payroll' ); ?>');
						return;
					}

					$.ajax({
						url: ajaxurl,
						type: 'POST',
						data: {
							action: 'wc_tp_add_payment',
							user_id: userId,
							amount: amount,
							payment_date: date
						},
						success: function(response) {
							if (response.success) {
								$('#wc-tp-payment-amount').val('');
								$('#wc-tp-payment-date').val(new Date().toISOString().slice(0, 16));
								loadPaymentHistory();
								alert('<?php esc_js_e( 'Payment added', 'wc-team-payroll' ); ?>');
							}
						}
					});
				});

				// Delete payment
				window.deletePayment = function(userId, paymentId) {
					if (!confirm('<?php esc_js_e( 'Delete this payment?', 'wc-team-payroll' ); ?>')) return;

					$.ajax({
						url: ajaxurl,
						type: 'POST',
						data: {
							action: 'wc_tp_delete_payment',
							user_id: userId,
							payment_id: paymentId
						},
						success: function(response) {
							if (response.success) {
								loadPaymentHistory();
								alert('<?php esc_js_e( 'Payment deleted', 'wc-team-payroll' ); ?>');
							}
						}
					});
				};

				// Load salary data
				function loadSalaryData() {
					$.ajax({
						url: ajaxurl,
						type: 'POST',
						data: {
							action: 'wc_tp_get_employee_salary',
							user_id: userId
						},
						success: function(response) {
							if (response.success) {
								const salary = response.data;
								$('#wc-tp-salary-type').val(salary.type);
								$('#wc-tp-salary-amount').val(salary.amount || '');
								$('#wc-tp-salary-frequency').val(salary.frequency || 'monthly');
								toggleSalaryFields();
							}
						}
					});
				}

				// Toggle salary fields based on type
				$('#wc-tp-salary-type').on('change', function() {
					toggleSalaryFields();
				});

				function toggleSalaryFields() {
					const type = $('#wc-tp-salary-type').val();
					if (type === 'fixed' || type === 'combined') {
						$('#wc-tp-salary-amount-group').slideDown(200);
						$('#wc-tp-salary-frequency-group').slideDown(200);
					} else {
						$('#wc-tp-salary-amount-group').slideUp(200);
						$('#wc-tp-salary-frequency-group').slideUp(200);
					}
				}

				// Update salary
				$('#wc-tp-update-salary-btn').on('click', function() {
					const type = $('#wc-tp-salary-type').val();
					const amount = $('#wc-tp-salary-amount').val();
					const frequency = $('#wc-tp-salary-frequency').val();

					if ((type === 'fixed' || type === 'combined') && (!amount || amount <= 0)) {
						alert('<?php esc_js_e( 'Please enter a valid salary amount', 'wc-team-payroll' ); ?>');
						return;
					}

					$(this).prop('disabled', true).text('<?php esc_js_e( 'Updating...', 'wc-team-payroll' ); ?>');

					$.ajax({
						url: ajaxurl,
						type: 'POST',
						data: {
							action: 'wc_tp_update_employee_salary',
							user_id: userId,
							salary_type: type,
							salary_amount: amount || 0,
							salary_frequency: frequency
						},
						success: function(response) {
							if (response.success) {
								loadSalaryHistory();
								alert('<?php esc_js_e( 'Salary updated successfully', 'wc-team-payroll' ); ?>');
							} else {
								alert('<?php esc_js_e( 'Error updating salary', 'wc-team-payroll' ); ?>');
							}
						},
						error: function() {
							alert('<?php esc_js_e( 'Error updating salary', 'wc-team-payroll' ); ?>');
						},
						complete: function() {
							$('#wc-tp-update-salary-btn').prop('disabled', false).text('<?php esc_js_e( 'Update Salary', 'wc-team-payroll' ); ?>');
						}
					});
				});

				// Load salary history
				function loadSalaryHistory() {
					$.ajax({
						url: ajaxurl,
						type: 'POST',
						data: {
							action: 'wc_tp_get_salary_history',
							user_id: userId
						},
						success: function(response) {
							if (response.success) {
								renderSalaryHistory(response.data.history || []);
							}
						}
					});
				}

				// Render salary history
				function renderSalaryHistory(history) {
					const container = $('#wc-tp-salary-history-container');
					
					if (!history || history.length === 0) {
						container.html('<div class="wc-tp-empty-state"><div class="wc-tp-empty-icon">📋</div><p>No salary history</p></div>');
						return;
					}

					let html = '<table class="wc-tp-data-table"><thead><tr>';
					html += '<th>Date</th>';
					html += '<th>Old Type</th>';
					html += '<th>Old Amount</th>';
					html += '<th>New Type</th>';
					html += '<th>New Amount</th>';
					html += '<th>Changed By</th>';
					html += '</tr></thead><tbody>';

					$.each(history, function(i, entry) {
						html += '<tr>';
						html += '<td>' + entry.date + '</td>';
						html += '<td>' + (entry.old_type ? entry.old_type.charAt(0).toUpperCase() + entry.old_type.slice(1) : '-') + '</td>';
						html += '<td>' + (entry.old_amount ? formatCurrency(entry.old_amount) : '-') + '</td>';
						html += '<td>' + (entry.new_type ? entry.new_type.charAt(0).toUpperCase() + entry.new_type.slice(1) : '-') + '</td>';
						html += '<td>' + (entry.new_amount ? formatCurrency(entry.new_amount) : '-') + '</td>';
						html += '<td>' + entry.changed_by + '</td>';
						html += '</tr>';
					});

					html += '</tbody></table>';
					container.html(html);
				}
			});
		</script>
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
