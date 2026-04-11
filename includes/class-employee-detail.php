<?php
/**
 * Employee Detail Page
 */

class WC_Team_Payroll_Employee_Detail {

	public function render_employee_detail() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized', 'wc-team-payroll' ) );
		}

		$user_id = isset( $_GET['user_id'] ) ? intval( $_GET['user_id'] ) : 0;
		if ( ! $user_id ) {
			wp_die( esc_html__( 'Invalid user ID', 'wc-team-payroll' ) );
		}

		$user = get_user_by( 'ID', $user_id );
		if ( ! $user ) {
			wp_die( esc_html__( 'User not found', 'wc-team-payroll' ) );
		}

		// Get user meta
		$profile_picture = get_user_meta( $user_id, 'profile_picture', true );
		$phone = get_user_meta( $user_id, 'billing_phone', true );
		$bio = get_user_meta( $user_id, 'description', true );
		$vb_user_id = get_user_meta( $user_id, 'vb_user_id', true );

		// Get stats
		$core_engine = new WC_Team_Payroll_Core_Engine();
		$total_orders = $core_engine->get_user_total_orders( $user_id );
		$total_earnings = $core_engine->get_user_total_earnings( $user_id );
		$total_paid = $core_engine->get_user_total_paid( $user_id );
		$total_due = $total_earnings - $total_paid;

		// Add styles
		$this->add_styles();
		// Add scripts
		$this->add_scripts();

		?>
		<div class="wrap wc-tp-employee-detail">
			<h1><?php esc_html_e( 'Employee Details', 'wc-team-payroll' ); ?></h1>

			<!-- Profile Section -->
			<div class="wc-tp-profile-section">
				<div class="wc-tp-profile-header">
					<div class="wc-tp-profile-left">
						<div class="wc-tp-profile-picture">
							<?php if ( $profile_picture ) : ?>
								<img src="<?php echo esc_url( $profile_picture ); ?>" alt="<?php echo esc_attr( $user->display_name ); ?>" />
							<?php else : ?>
								<div class="wc-tp-profile-placeholder">
									<span class="dashicons dashicons-admin-users"></span>
								</div>
							<?php endif; ?>
						</div>
						<div class="wc-tp-profile-info">
							<h2><?php echo esc_html( $user->display_name ); ?></h2>
							<?php if ( $vb_user_id ) : ?>
								<p class="wc-tp-user-id"><strong><?php esc_html_e( 'ID:', 'wc-team-payroll' ); ?></strong> <?php echo esc_html( $vb_user_id ); ?></p>
							<?php endif; ?>
							<p class="wc-tp-email"><strong><?php esc_html_e( 'Email:', 'wc-team-payroll' ); ?></strong> <?php echo esc_html( $user->user_email ); ?></p>
							<?php if ( $phone ) : ?>
								<p class="wc-tp-phone"><strong><?php esc_html_e( 'Phone:', 'wc-team-payroll' ); ?></strong> <?php echo esc_html( $phone ); ?></p>
							<?php endif; ?>
							<?php if ( $bio ) : ?>
								<p class="wc-tp-bio"><strong><?php esc_html_e( 'Bio:', 'wc-team-payroll' ); ?></strong> <?php echo esc_html( $bio ); ?></p>
							<?php endif; ?>
						</div>
					</div>
					<div class="wc-tp-profile-right">
						<a href="<?php echo esc_url( get_edit_user_link( $user_id ) ); ?>" class="button button-primary">
							<span class="dashicons dashicons-edit"></span> <?php esc_html_e( 'Edit Profile', 'wc-team-payroll' ); ?>
						</a>
					</div>
				</div>
			</div>

			<!-- Stats Cards -->
			<div class="wc-tp-stats-grid">
				<div class="wc-tp-stat-card">
					<div class="wc-tp-stat-icon">📦</div>
					<div class="wc-tp-stat-content">
						<div class="wc-tp-stat-value"><?php echo esc_html( $total_orders ); ?></div>
						<div class="wc-tp-stat-label"><?php esc_html_e( 'Total Orders', 'wc-team-payroll' ); ?></div>
					</div>
				</div>
				<div class="wc-tp-stat-card">
					<div class="wc-tp-stat-icon">💰</div>
					<div class="wc-tp-stat-content">
						<div class="wc-tp-stat-value"><?php echo wp_kses_post( wc_price( $total_earnings ) ); ?></div>
						<div class="wc-tp-stat-label"><?php esc_html_e( 'Total Earnings', 'wc-team-payroll' ); ?></div>
					</div>
				</div>
				<div class="wc-tp-stat-card">
					<div class="wc-tp-stat-icon">✅</div>
					<div class="wc-tp-stat-content">
						<div class="wc-tp-stat-value"><?php echo wp_kses_post( wc_price( $total_paid ) ); ?></div>
						<div class="wc-tp-stat-label"><?php esc_html_e( 'Total Paid', 'wc-team-payroll' ); ?></div>
					</div>
				</div>
				<div class="wc-tp-stat-card">
					<div class="wc-tp-stat-icon">⏳</div>
					<div class="wc-tp-stat-content">
						<div class="wc-tp-stat-value"><?php echo wp_kses_post( wc_price( $total_due ) ); ?></div>
						<div class="wc-tp-stat-label"><?php esc_html_e( 'Total Due', 'wc-team-payroll' ); ?></div>
					</div>
				</div>
			</div>

			<!-- Tabs Navigation -->
			<nav class="nav-tab-wrapper wc-tp-tabs">
				<a href="?page=wc-team-payroll-employee-detail&user_id=<?php echo esc_attr( $user_id ); ?>&tab=orders" class="nav-tab <?php echo ( ! isset( $_GET['tab'] ) || $_GET['tab'] === 'orders' ) ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Orders', 'wc-team-payroll' ); ?>
				</a>
				<a href="?page=wc-team-payroll-employee-detail&user_id=<?php echo esc_attr( $user_id ); ?>&tab=payments" class="nav-tab <?php echo ( isset( $_GET['tab'] ) && $_GET['tab'] === 'payments' ) ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Payments', 'wc-team-payroll' ); ?>
				</a>
				<a href="?page=wc-team-payroll-employee-detail&user_id=<?php echo esc_attr( $user_id ); ?>&tab=salary" class="nav-tab <?php echo ( isset( $_GET['tab'] ) && $_GET['tab'] === 'salary' ) ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Salary Management', 'wc-team-payroll' ); ?>
				</a>
			</nav>

			<!-- Tab Content -->
			<div class="wc-tp-tab-content">
				<?php
				$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'orders';

				if ( $current_tab === 'orders' ) {
					$this->render_orders_tab( $user_id );
				} elseif ( $current_tab === 'payments' ) {
					$this->render_payments_tab( $user_id );
				} elseif ( $current_tab === 'salary' ) {
					$this->render_salary_tab( $user_id );
				}
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Orders Tab
	 */
	private function render_orders_tab( $user_id ) {
		?>
		<div class="wc-tp-orders-tab">
			<!-- Unified Filter Section -->
			<div class="wc-tp-unified-filter">
				<div class="wc-tp-filter-row">
					<!-- Date Range Preset -->
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
					<div class="wc-tp-filter-group wc-tp-custom-date-range" id="wc-tp-orders-custom-date-range" style="display: none;">
						<input type="date" id="wc-tp-orders-start-date" />
						<span class="wc-tp-date-separator">to</span>
						<input type="date" id="wc-tp-orders-end-date" />
					</div>

					<!-- Status Filter -->
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

					<!-- Flag Filter -->
					<div class="wc-tp-filter-group">
						<label><?php esc_html_e( 'Flag:', 'wc-team-payroll' ); ?></label>
						<select id="wc-tp-orders-flag-filter">
							<option value=""><?php esc_html_e( 'All Flags', 'wc-team-payroll' ); ?></option>
							<option value="owner"><?php esc_html_e( 'Owner', 'wc-team-payroll' ); ?></option>
							<option value="affiliate_to"><?php esc_html_e( 'Affiliate To', 'wc-team-payroll' ); ?></option>
							<option value="affiliate_from"><?php esc_html_e( 'Affiliate From', 'wc-team-payroll' ); ?></option>
						</select>
					</div>

					<!-- Search -->
					<div class="wc-tp-filter-group">
						<label><?php esc_html_e( 'Search:', 'wc-team-payroll' ); ?></label>
						<input type="text" id="wc-tp-orders-search" placeholder="<?php esc_attr_e( 'Order ID, Customer...', 'wc-team-payroll' ); ?>" />
					</div>

					<!-- Filter Button -->
					<div class="wc-tp-filter-group">
						<button type="button" class="button button-primary" id="wc-tp-orders-filter-btn"><?php esc_html_e( 'Filter', 'wc-team-payroll' ); ?></button>
					</div>
				</div>
			</div>

			<!-- Orders Table -->
			<div class="wc-tp-table-section">
				<h2><?php esc_html_e( 'Order History', 'wc-team-payroll' ); ?></h2>
				<div id="wc-tp-orders-table-container">
					<!-- Content will be loaded via AJAX -->
				</div>
			</div>
		</div>

		<input type="hidden" id="wc-tp-current-user-id" value="<?php echo esc_attr( $user_id ); ?>" />
		<?php
		wp_nonce_field( 'wc_team_payroll_nonce', 'wc_team_payroll_nonce' );
	}

	/**
	 * Render Payments Tab
	 */
	private function render_payments_tab( $user_id ) {
		?>
		<div class="wc-tp-payments-tab">
			<!-- Add Payment Form -->
			<div class="wc-tp-table-section">
				<h2><?php esc_html_e( 'Add Payment', 'wc-team-payroll' ); ?></h2>
				<form id="wc-tp-add-payment-form" class="wc-tp-form">
					<div class="wc-tp-form-row">
						<div class="wc-tp-form-group">
							<label for="wc-tp-payment-amount"><?php esc_html_e( 'Amount', 'wc-team-payroll' ); ?></label>
							<input type="number" id="wc-tp-payment-amount" step="0.01" min="0" required />
						</div>
						<div class="wc-tp-form-group">
							<label for="wc-tp-payment-date"><?php esc_html_e( 'Date', 'wc-team-payroll' ); ?></label>
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
							<button type="submit" class="button button-primary"><?php esc_html_e( 'Add Payment', 'wc-team-payroll' ); ?></button>
						</div>
					</div>
				</form>
			</div>

			<!-- Payment History -->
			<div class="wc-tp-table-section">
				<h2><?php esc_html_e( 'Payment History', 'wc-team-payroll' ); ?></h2>
				<div id="wc-tp-payment-history-container">
					<!-- Content will be loaded via AJAX -->
				</div>
			</div>

			<!-- Add Payment Method Form -->
			<div class="wc-tp-table-section">
				<h2><?php esc_html_e( 'Add Payment Method', 'wc-team-payroll' ); ?></h2>
				<form id="wc-tp-add-payment-method-form" class="wc-tp-form">
					<div class="wc-tp-form-row">
						<div class="wc-tp-form-group">
							<label for="wc-tp-method-name"><?php esc_html_e( 'Payment Method', 'wc-team-payroll' ); ?></label>
							<input type="text" id="wc-tp-method-name" placeholder="<?php esc_attr_e( 'e.g., bKash Personal', 'wc-team-payroll' ); ?>" required />
						</div>
						<div class="wc-tp-form-group">
							<label for="wc-tp-method-account"><?php esc_html_e( 'Account/Details', 'wc-team-payroll' ); ?></label>
							<input type="text" id="wc-tp-method-account" placeholder="<?php esc_attr_e( 'e.g., 01712345678', 'wc-team-payroll' ); ?>" required />
						</div>
						<div class="wc-tp-form-group">
							<label for="wc-tp-method-note"><?php esc_html_e( 'Note (Optional)', 'wc-team-payroll' ); ?></label>
							<input type="text" id="wc-tp-method-note" />
						</div>
						<div class="wc-tp-form-group">
							<button type="submit" class="button button-primary"><?php esc_html_e( 'Add Method', 'wc-team-payroll' ); ?></button>
						</div>
					</div>
				</form>
			</div>

			<!-- Payment Methods List -->
			<div class="wc-tp-table-section">
				<h2><?php esc_html_e( 'Payment Methods', 'wc-team-payroll' ); ?></h2>
				<div id="wc-tp-payment-methods-container">
					<!-- Content will be loaded via AJAX -->
				</div>
			</div>
		</div>

		<input type="hidden" id="wc-tp-current-user-id" value="<?php echo esc_attr( $user_id ); ?>" />
		<?php
		wp_nonce_field( 'wc_team_payroll_nonce', 'wc_team_payroll_nonce' );
	}

	/**
	 * Render Salary Tab
	 */
	private function render_salary_tab( $user_id ) {
		$is_fixed = get_user_meta( $user_id, '_wc_tp_fixed_salary', true );
		$is_combined = get_user_meta( $user_id, '_wc_tp_combined_salary', true );
		$salary_amount = get_user_meta( $user_id, '_wc_tp_salary_amount', true );
		$salary_frequency = get_user_meta( $user_id, '_wc_tp_salary_frequency', true );

		$salary_type = 'commission';
		if ( $is_fixed ) {
			$salary_type = 'fixed';
		} elseif ( $is_combined ) {
			$salary_type = 'combined';
		}
		?>
		<div class="wc-tp-salary-tab">
			<!-- Salary Management Form -->
			<div class="wc-tp-table-section">
				<h2><?php esc_html_e( 'Salary Management', 'wc-team-payroll' ); ?></h2>
				<form id="wc-tp-salary-form" class="wc-tp-form">
					<div class="wc-tp-form-row">
						<div class="wc-tp-form-group">
							<label for="wc-tp-salary-type"><?php esc_html_e( 'Salary Type', 'wc-team-payroll' ); ?></label>
							<select id="wc-tp-salary-type">
								<option value="commission" <?php selected( $salary_type, 'commission' ); ?>><?php esc_html_e( 'Commission Based', 'wc-team-payroll' ); ?></option>
								<option value="fixed" <?php selected( $salary_type, 'fixed' ); ?>><?php esc_html_e( 'Fixed Salary', 'wc-team-payroll' ); ?></option>
								<option value="combined" <?php selected( $salary_type, 'combined' ); ?>><?php esc_html_e( 'Combined (Base + Commission)', 'wc-team-payroll' ); ?></option>
							</select>
						</div>
						<div class="wc-tp-form-group wc-tp-salary-amount-group" style="<?php echo ( $salary_type === 'commission' ) ? 'display: none;' : ''; ?>">
							<label for="wc-tp-salary-amount"><?php esc_html_e( 'Salary Amount', 'wc-team-payroll' ); ?></label>
							<input type="number" id="wc-tp-salary-amount" step="0.01" min="0" value="<?php echo esc_attr( $salary_amount ); ?>" />
						</div>
						<div class="wc-tp-form-group wc-tp-salary-frequency-group" style="<?php echo ( $salary_type === 'commission' ) ? 'display: none;' : ''; ?>">
							<label for="wc-tp-salary-frequency"><?php esc_html_e( 'Frequency', 'wc-team-payroll' ); ?></label>
							<select id="wc-tp-salary-frequency">
								<option value="monthly" <?php selected( $salary_frequency, 'monthly' ); ?>><?php esc_html_e( 'Monthly', 'wc-team-payroll' ); ?></option>
								<option value="weekly" <?php selected( $salary_frequency, 'weekly' ); ?>><?php esc_html_e( 'Weekly', 'wc-team-payroll' ); ?></option>
								<option value="daily" <?php selected( $salary_frequency, 'daily' ); ?>><?php esc_html_e( 'Daily', 'wc-team-payroll' ); ?></option>
							</select>
						</div>
						<div class="wc-tp-form-group">
							<button type="submit" class="button button-primary"><?php esc_html_e( 'Update Salary', 'wc-team-payroll' ); ?></button>
						</div>
					</div>
				</form>
			</div>

			<!-- Salary History -->
			<div class="wc-tp-table-section">
				<h2><?php esc_html_e( 'Salary History', 'wc-team-payroll' ); ?></h2>
				<div id="wc-tp-salary-history-container">
					<!-- Content will be loaded via AJAX -->
				</div>
			</div>
		</div>

		<input type="hidden" id="wc-tp-current-user-id" value="<?php echo esc_attr( $user_id ); ?>" />
		<?php
		wp_nonce_field( 'wc_team_payroll_nonce', 'wc_team_payroll_nonce' );
	}

	/**
	 * Add inline styles
	 */
	public function add_styles() {
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

			.wc-tp-employee-detail {
				background: var(--color-site-bg);
				padding: 24px;
				font-family: var(--font-family);
				color: var(--text-main);
			}

			.wc-tp-employee-detail h1 {
				font-size: var(--fs-h1);
				font-weight: var(--fw-bold);
				color: var(--text-main);
				margin-bottom: 24px;
			}

			/* Profile Section */
			.wc-tp-profile-section {
				background: var(--color-card-bg);
				border: 1px solid var(--color-border-light);
				border-radius: 8px;
				padding: 24px;
				margin-bottom: 24px;
			}

			.wc-tp-profile-header {
				display: flex;
				justify-content: space-between;
				align-items: flex-start;
				gap: 20px;
			}

			.wc-tp-profile-left {
				display: flex;
				gap: 20px;
				flex: 1;
			}

			.wc-tp-profile-picture {
				width: 120px;
				height: 120px;
				border-radius: 8px;
				overflow: hidden;
				border: 2px solid var(--color-border-light);
				flex-shrink: 0;
			}

			.wc-tp-profile-picture img {
				width: 100%;
				height: 100%;
				object-fit: cover;
			}

			.wc-tp-profile-placeholder {
				width: 100%;
				height: 100%;
				background: var(--color-accent-muted);
				display: flex;
				align-items: center;
				justify-content: center;
			}

			.wc-tp-profile-placeholder .dashicons {
				font-size: 60px;
				width: 60px;
				height: 60px;
				color: var(--text-muted);
			}

			.wc-tp-profile-info {
				flex: 1;
			}

			.wc-tp-profile-info h2 {
				margin: 0 0 12px 0;
				font-size: 1.75rem;
				font-weight: var(--fw-bold);
				color: var(--text-main);
			}

			.wc-tp-profile-info p {
				margin: 8px 0;
				font-size: var(--fs-body);
				color: var(--text-body);
				line-height: var(--lh-body);
			}

			.wc-tp-profile-info p strong {
				color: var(--text-main);
				font-weight: var(--fw-semibold);
			}

			.wc-tp-user-id {
				color: var(--color-primary);
				font-weight: var(--fw-semibold);
			}

			.wc-tp-profile-right {
				flex-shrink: 0;
			}

			.wc-tp-profile-right .button-primary {
				display: inline-flex;
				align-items: center;
				gap: 8px;
				background: var(--color-primary);
				border-color: var(--color-primary);
				color: white;
				font-weight: var(--fw-semibold);
				border-radius: 6px;
				padding: 10px 20px;
				font-size: var(--fs-body);
				transition: all 0.2s ease;
			}

			.wc-tp-profile-right .button-primary:hover {
				background: var(--color-primary-hover);
				border-color: var(--color-primary-hover);
			}

			.wc-tp-profile-right .button-primary .dashicons {
				font-size: 18px;
				width: 18px;
				height: 18px;
			}

			/* Stats Cards */
			.wc-tp-stats-grid {
				display: grid;
				grid-template-columns: repeat(4, 1fr);
				gap: 16px;
				margin-bottom: 24px;
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
			}

			.wc-tp-stat-label {
				font-size: var(--fs-meta);
				color: var(--text-muted);
				text-transform: uppercase;
				letter-spacing: 0.5px;
				font-weight: var(--fw-medium);
			}

			/* Tabs */
			.wc-tp-tabs {
				margin-bottom: 0;
				border-bottom: 1px solid var(--color-border-light);
			}

			.wc-tp-tabs .nav-tab {
				font-size: var(--fs-body);
				font-weight: var(--fw-semibold);
				color: var(--text-body);
				border: none;
				border-bottom: 3px solid transparent;
				background: transparent;
				padding: 12px 24px;
				margin: 0;
				transition: all 0.2s ease;
			}

			.wc-tp-tabs .nav-tab:hover {
				color: var(--color-primary);
				background: var(--color-primary-subtle);
			}

			.wc-tp-tabs .nav-tab-active {
				color: var(--color-primary);
				border-bottom-color: var(--color-primary);
				background: transparent;
			}

			/* Tab Content */
			.wc-tp-tab-content {
				background: var(--color-card-bg);
				border: 1px solid var(--color-border-light);
				border-top: none;
				border-radius: 0 0 8px 8px;
				padding: 24px;
			}

			/* Unified Filter */
			.wc-tp-unified-filter {
				background: var(--color-accent-muted);
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
			.wc-tp-filter-group input[type="date"],
			.wc-tp-filter-group input[type="text"] {
				padding: 8px 12px;
				border: 1px solid var(--color-border-light);
				border-radius: 6px;
				font-size: var(--fs-body);
				font-family: var(--font-family);
				color: var(--text-main);
				background: var(--color-card-bg);
				min-width: 150px;
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

			/* Table Section */
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

			/* Forms */
			.wc-tp-form {
				background: var(--color-accent-muted);
				padding: 20px;
				border-radius: 8px;
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

			.wc-tp-form-group input[type="text"],
			.wc-tp-form-group input[type="number"],
			.wc-tp-form-group input[type="datetime-local"],
			.wc-tp-form-group select {
				padding: 10px 12px;
				border: 1px solid var(--color-border-light);
				border-radius: 6px;
				font-size: var(--fs-body);
				font-family: var(--font-family);
				color: var(--text-main);
				background: var(--color-card-bg);
			}

			.wc-tp-form-group input:focus,
			.wc-tp-form-group select:focus {
				outline: none;
				border-color: var(--color-primary);
				box-shadow: 0 0 0 3px var(--color-primary-subtle);
			}

			/* Buttons */
			.button-primary {
				background: var(--color-primary);
				border-color: var(--color-primary);
				color: white;
				font-weight: var(--fw-semibold);
				border-radius: 6px;
				padding: 10px 20px;
				font-size: var(--fs-body);
				transition: all 0.2s ease;
				cursor: pointer;
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
				transition: all 0.2s ease;
			}

			.button-secondary:hover {
				background: var(--color-border-light);
				border-color: var(--color-border-light);
			}

			.button-small {
				padding: 6px 12px;
				font-size: var(--fs-small);
			}

			/* Action Icons */
			.wc-tp-action-icons {
				display: flex;
				gap: 8px;
			}

			.wc-tp-action-icon {
				display: inline-flex;
				align-items: center;
				justify-content: center;
				width: 32px;
				height: 32px;
				border-radius: 4px;
				border: 1px solid var(--color-border-light);
				background: var(--color-card-bg);
				color: var(--text-body);
				transition: all 0.2s ease;
				text-decoration: none;
			}

			.wc-tp-action-icon:hover {
				background: var(--color-primary);
				border-color: var(--color-primary);
				color: white;
			}

			.wc-tp-action-icon .dashicons {
				font-size: 16px;
				width: 16px;
				height: 16px;
			}

			/* Badges */
			.wc-tp-badge {
				display: inline-block;
				padding: 4px 8px;
				border-radius: 4px;
				font-size: var(--fs-small);
				font-weight: var(--fw-semibold);
			}

			.wc-tp-badge-owner {
				background: #E3F2FD;
				color: #1976D2;
			}

			.wc-tp-badge-affiliate-to {
				background: #FFF3E0;
				color: #F57C00;
			}

			.wc-tp-badge-affiliate-from {
				background: #F3E5F5;
				color: #7B1FA2;
			}

			.wc-tp-status-completed {
				background: #D4EDDA;
				color: #155724;
			}

			.wc-tp-status-processing {
				background: #D1ECF1;
				color: #0C5460;
			}

			.wc-tp-status-pending {
				background: #FFF3CD;
				color: #856404;
			}

			.wc-tp-status-cancelled {
				background: #F8D7DA;
				color: #721C24;
			}

			.wc-tp-status-refunded {
				background: #E2E3E5;
				color: #383D41;
			}

			/* Payment Methods List */
			.wc-tp-payment-methods-list {
				display: grid;
				gap: 12px;
			}

			.wc-tp-payment-method-item {
				display: flex;
				justify-content: space-between;
				align-items: center;
				padding: 16px;
				background: var(--color-accent-muted);
				border: 1px solid var(--color-border-light);
				border-radius: 6px;
			}

			.wc-tp-payment-method-info {
				flex: 1;
			}

			.wc-tp-payment-method-name {
				font-weight: var(--fw-semibold);
				color: var(--text-main);
				margin-bottom: 4px;
			}

			.wc-tp-payment-method-details {
				font-size: var(--fs-meta);
				color: var(--text-body);
			}

			.wc-tp-payment-method-actions {
				display: flex;
				gap: 8px;
			}

			.wc-tp-delete-btn {
				background: #dc3545;
				border-color: #dc3545;
				color: white;
				padding: 6px 12px;
				border-radius: 4px;
				font-size: var(--fs-small);
				cursor: pointer;
				transition: all 0.2s ease;
			}

			.wc-tp-delete-btn:hover {
				background: #c82333;
				border-color: #c82333;
			}

			/* Responsive Design */
			@media (max-width: 1024px) {
				.wc-tp-stats-grid {
					grid-template-columns: repeat(2, 1fr);
				}

				.wc-tp-filter-row {
					flex-direction: column;
					align-items: stretch;
				}

				.wc-tp-filter-group {
					width: 100%;
				}
			}

			@media (max-width: 768px) {
				.wc-tp-employee-detail {
					padding: 12px;
				}

				.wc-tp-profile-header {
					flex-direction: column;
				}

				.wc-tp-profile-left {
					flex-direction: column;
					align-items: center;
					text-align: center;
				}

				.wc-tp-profile-right {
					width: 100%;
				}

				.wc-tp-profile-right .button-primary {
					width: 100%;
					justify-content: center;
				}

				.wc-tp-stats-grid {
					grid-template-columns: 1fr;
				}

				.wc-tp-stat-card {
					flex-direction: column;
					text-align: center;
				}

				.wc-tp-form-row {
					flex-direction: column;
				}

				.wc-tp-form-group {
					width: 100%;
					min-width: unset;
				}

				.wc-tp-data-table {
					font-size: var(--fs-small);
				}

				.wc-tp-data-table th,
				.wc-tp-data-table td {
					padding: 8px 6px;
				}
			}
		</style>
		<?php
	}

	/**
	 * Add inline scripts
	 */
	public function add_scripts() {
		?>
		<script>
			jQuery(document).ready(function($) {
				const userId = $('#wc-tp-current-user-id').val();
				const nonce = $('#wc_team_payroll_nonce').val();

				// ============================================================================
				// GLOBAL TOAST NOTIFICATION SYSTEM
				// ============================================================================
				window.wcTPToast = function(message, type = 'success') {
					const toastId = 'wc-tp-toast-' + Date.now();
					const toast = $(`
						<div id="${toastId}" class="wc-tp-toast wc-tp-toast-${type}">
							<span>${message}</span>
							<button class="wc-tp-toast-close" data-toast-id="${toastId}">×</button>
						</div>
					`);

					$('body').append(toast);

					// Auto hide after 4 seconds
					setTimeout(() => {
						toast.fadeOut(300, function() {
							$(this).remove();
						});
					}, 4000);

					// Manual close
					$(document).on('click', '.wc-tp-toast-close', function() {
						const id = $(this).data('toast-id');
						$('#' + id).fadeOut(300, function() {
							$(this).remove();
						});
					});
				};

				// Toast CSS (add once)
				if (!$('#wc-tp-toast-styles').length) {
					$('head').append(`
						<style id="wc-tp-toast-styles">
							.wc-tp-toast {
								position: fixed;
								top: 20px;
								right: 20px;
								background: #388E3C;
								color: white;
								padding: 16px 20px;
								border-radius: 6px;
								box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
								display: flex;
								align-items: center;
								gap: 12px;
								z-index: 9999;
								font-size: 14px;
								font-weight: 500;
								animation: wcTPSlideIn 0.3s ease;
							}

							.wc-tp-toast-error {
								background: #dc3545;
							}

							.wc-tp-toast-close {
								background: none;
								border: none;
								color: white;
								font-size: 24px;
								cursor: pointer;
								padding: 0;
								line-height: 1;
								transition: all 0.2s ease;
							}

							.wc-tp-toast-close:hover {
								opacity: 0.8;
							}

							@keyframes wcTPSlideIn {
								from {
									transform: translateX(400px);
									opacity: 0;
								}
								to {
									transform: translateX(0);
									opacity: 1;
								}
							}

							@media (max-width: 768px) {
								.wc-tp-toast {
									top: 10px;
									right: 10px;
									left: 10px;
									width: auto;
								}
							}

							.wc-tp-inline-edit-form {
								padding: 16px;
								background: #f9f9f9;
								border-radius: 6px;
							}

							.wc-tp-inline-edit-form .wc-tp-form-row {
								display: flex;
								gap: 12px;
								align-items: flex-end;
								flex-wrap: wrap;
							}

							.wc-tp-inline-edit-form .wc-tp-form-group {
								display: flex;
								flex-direction: column;
								gap: 6px;
								flex: 1;
								min-width: 150px;
							}

							.wc-tp-inline-edit-form .wc-tp-form-group label {
								font-weight: 600;
								font-size: 12px;
								color: #212B36;
							}

							.wc-tp-inline-edit-form .wc-tp-form-group input {
								padding: 8px 12px;
								border: 1px solid #E5EAF0;
								border-radius: 6px;
								font-size: 14px;
							}
						</style>
					`);
				}

				// ============================================================================
				// ORDERS TAB
				// ============================================================================
				if ($('.wc-tp-orders-tab').length) {
					let currentStartDate = '';
					let currentEndDate = '';

					// Initialize with default preset (This Month)
					updateDateRangeFromPreset('this-month');
					loadOrdersData();

					// Date preset change
					$('#wc-tp-orders-date-preset').on('change', function() {
						const preset = $(this).val();
						
						if (preset === 'custom') {
							$('#wc-tp-orders-custom-date-range').slideDown(200);
						} else {
							$('#wc-tp-orders-custom-date-range').slideUp(200);
							updateDateRangeFromPreset(preset);
						}
					});

					// Filter button click
					$('#wc-tp-orders-filter-btn').on('click', function() {
						loadOrdersData();
					});

					// Search on enter key
					$('#wc-tp-orders-search').on('keypress', function(e) {
						if (e.which === 13) {
							loadOrdersData();
						}
					});

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
						currentStartDate = range.start;
						currentEndDate = range.end;
						$('#wc-tp-orders-start-date').val(range.start);
						$('#wc-tp-orders-end-date').val(range.end);
					}

					function loadOrdersData() {
						const preset = $('#wc-tp-orders-date-preset').val();
						
						if (preset === 'custom') {
							currentStartDate = $('#wc-tp-orders-start-date').val();
							currentEndDate = $('#wc-tp-orders-end-date').val();
						}

						const status = $('#wc-tp-orders-status-filter').val();
						const flag = $('#wc-tp-orders-flag-filter').val();
						const search = $('#wc-tp-orders-search').val();

						if (!currentStartDate || !currentEndDate) {
							return;
						}

						$('#wc-tp-orders-filter-btn').prop('disabled', true).text('Loading...');

						$.ajax({
							url: ajaxurl,
							type: 'POST',
							data: {
								action: 'wc_tp_get_employee_orders',
								user_id: userId,
								start_date: currentStartDate,
								end_date: currentEndDate,
								status: status,
								flag: flag,
								search: search,
								nonce: nonce
							},
							success: function(response) {
								if (response.success) {
									renderOrdersTable(response.data.orders);
								} else {
									$('#wc-tp-orders-table-container').html('<div class="wc-tp-empty-state"><div class="wc-tp-empty-icon">📦</div><p>Failed to load orders</p></div>');
								}
							},
							error: function() {
								$('#wc-tp-orders-table-container').html('<div class="wc-tp-empty-state"><div class="wc-tp-empty-icon">❌</div><p>Error loading orders</p></div>');
							},
							complete: function() {
								$('#wc-tp-orders-filter-btn').prop('disabled', false).text('Filter');
							}
						});
					}

					function renderOrdersTable(orders) {
						const container = $('#wc-tp-orders-table-container');
						
						if (!orders || orders.length === 0) {
							container.html('<div class="wc-tp-empty-state"><div class="wc-tp-empty-icon">📦</div><p>No orders found</p></div>');
							return;
						}

						let html = '<table class="wc-tp-data-table"><thead><tr>';
						html += '<th>Order ID</th>';
						html += '<th>Customer</th>';
						html += '<th>Total</th>';
						html += '<th>Status</th>';
						html += '<th>Commission</th>';
						html += '<th>Your Earnings</th>';
						html += '<th>Flag</th>';
						html += '<th>Date</th>';
						html += '<th>Actions</th>';
						html += '</tr></thead><tbody>';

						$.each(orders, function(i, order) {
							const statusClass = 'wc-tp-status-' + order.status.toLowerCase();
							const flagClass = 'wc-tp-badge-' + order.flag.replace('_', '-');
							const viewUrl = '<?php echo admin_url("post.php"); ?>?post=' + order.order_id + '&action=edit';
							const editUrl = viewUrl;

							html += '<tr>';
							html += '<td><strong>#' + order.order_id + '</strong></td>';
							html += '<td>' + order.customer_name + '</td>';
							html += '<td>' + formatCurrency(order.total) + '</td>';
							html += '<td><span class="wc-tp-badge ' + statusClass + '">' + order.status + '</span></td>';
							html += '<td>' + formatCurrency(order.commission) + '</td>';
							html += '<td><strong>' + formatCurrency(order.user_earnings) + '</strong></td>';
							html += '<td><span class="wc-tp-badge ' + flagClass + '">' + order.flag_label + '</span></td>';
							html += '<td>' + order.date + '</td>';
							html += '<td><div class="wc-tp-action-icons">';
							html += '<a href="' + viewUrl + '" class="wc-tp-action-icon" title="View Order"><span class="dashicons dashicons-visibility"></span></a>';
							html += '<a href="' + editUrl + '" class="wc-tp-action-icon" title="Edit Order"><span class="dashicons dashicons-edit"></span></a>';
							html += '</div></td>';
							html += '</tr>';
						});

						html += '</tbody></table>';
						container.html(html);
					}

					function formatCurrency(value) {
						return '<?php echo get_woocommerce_currency_symbol(); ?>' + parseFloat(value).toFixed(2);
					}
				}

				// ============================================================================
				// PAYMENTS TAB
				// ============================================================================
				if ($('.wc-tp-payments-tab').length) {
					loadPaymentMethods();
					loadPaymentHistory();

					// Add Payment Form Submit
					$('#wc-tp-add-payment-form').on('submit', function(e) {
						e.preventDefault();

						const amount = $('#wc-tp-payment-amount').val();
						const date = $('#wc-tp-payment-date').val();
						const method = $('#wc-tp-payment-method').val();
						const note = $('#wc-tp-payment-note').val();

						if (!amount || !date) {
							wcTPToast('Please fill in all required fields', 'error');
							return;
						}

						$.ajax({
							url: ajaxurl,
							type: 'POST',
							data: {
								action: 'wc_tp_add_payment',
								user_id: userId,
								amount: amount,
								payment_date: date,
								payment_method: method,
								note: note,
								nonce: nonce
							},
							success: function(response) {
								if (response.success) {
									wcTPToast('Payment added successfully');
									$('#wc-tp-add-payment-form')[0].reset();
									$('#wc-tp-payment-date').val('<?php echo date("Y-m-d\TH:i"); ?>');
									loadPaymentHistory();
									updateStatsCards();
								} else {
									wcTPToast('Failed to add payment: ' + response.data, 'error');
								}
							},
							error: function() {
								wcTPToast('Error adding payment', 'error');
							}
						});
					});

					// Add Payment Method Form Submit
					$('#wc-tp-add-payment-method-form').on('submit', function(e) {
						e.preventDefault();

						const methodName = $('#wc-tp-method-name').val();
						const methodAccount = $('#wc-tp-method-account').val();
						const methodNote = $('#wc-tp-method-note').val();

						if (!methodName || !methodAccount) {
							wcTPToast('Please fill in all required fields', 'error');
							return;
						}

						const methodDetails = methodAccount + (methodNote ? ' - ' + methodNote : '');

						$.ajax({
							url: ajaxurl,
							type: 'POST',
							data: {
								action: 'wc_tp_add_payment_method',
								user_id: userId,
								method_name: methodName,
								method_details: methodDetails,
								nonce: nonce
							},
							success: function(response) {
								if (response.success) {
									wcTPToast('Payment method added successfully');
									$('#wc-tp-add-payment-method-form')[0].reset();
									loadPaymentMethods();
								} else {
									wcTPToast('Failed to add payment method: ' + response.data, 'error');
								}
							},
							error: function() {
								wcTPToast('Error adding payment method', 'error');
							}
						});
					});

					// Delete Payment
					$(document).on('click', '.wc-tp-delete-payment', function() {
						if (!confirm('Are you sure you want to delete this payment?')) {
							return;
						}

						const paymentId = $(this).data('payment-id');

						$.ajax({
							url: ajaxurl,
							type: 'POST',
							data: {
								action: 'wc_tp_delete_payment',
								user_id: userId,
								payment_id: paymentId,
								nonce: nonce
							},
							success: function(response) {
								if (response.success) {
									wcTPToast('Payment deleted successfully');
									loadPaymentHistory();
									updateStatsCards();
								} else {
									wcTPToast('Failed to delete payment: ' + response.data, 'error');
								}
							},
							error: function() {
								wcTPToast('Error deleting payment', 'error');
							}
						});
					});

					// Edit Payment
					$(document).on('click', '.wc-tp-edit-payment', function() {
						const row = $(this).closest('tr');
						const paymentId = $(this).data('payment-id');
						const amount = row.find('.wc-tp-payment-amount').data('amount');
						const date = row.find('.wc-tp-payment-date').data('date');

						// Convert date format for datetime-local input
						const dateObj = new Date(date);
						const formattedDate = dateObj.toISOString().slice(0, 16);

						// Show edit form
						const editForm = $(`
							<tr class="wc-tp-edit-payment-row">
								<td colspan="5">
									<div class="wc-tp-inline-edit-form">
										<div class="wc-tp-form-row">
											<div class="wc-tp-form-group">
												<label>Amount</label>
												<input type="number" class="wc-tp-edit-amount" step="0.01" min="0" value="${amount}" />
											</div>
											<div class="wc-tp-form-group">
												<label>Date</label>
												<input type="datetime-local" class="wc-tp-edit-date" value="${formattedDate}" />
											</div>
											<div class="wc-tp-form-group">
												<button type="button" class="button button-primary wc-tp-save-payment-edit" data-payment-id="${paymentId}">Save</button>
												<button type="button" class="button wc-tp-cancel-payment-edit">Cancel</button>
											</div>
										</div>
									</div>
								</td>
							</tr>
						`);

						row.hide();
						row.after(editForm);
					});

					// Save Payment Edit
					$(document).on('click', '.wc-tp-save-payment-edit', function() {
						const paymentId = $(this).data('payment-id');
						const amount = $('.wc-tp-edit-amount').val();
						const date = $('.wc-tp-edit-date').val();

						if (!amount || !date) {
							wcTPToast('Please fill in all fields', 'error');
							return;
						}

						$.ajax({
							url: ajaxurl,
							type: 'POST',
							data: {
								action: 'wc_tp_update_payment',
								user_id: userId,
								payment_id: paymentId,
								amount: amount,
								date: date,
								nonce: nonce
							},
							success: function(response) {
								if (response.success) {
									wcTPToast('Payment updated successfully');
									loadPaymentHistory();
									updateStatsCards();
								} else {
									wcTPToast('Failed to update payment: ' + response.data, 'error');
								}
							},
							error: function() {
								wcTPToast('Error updating payment', 'error');
							}
						});
					});

					// Cancel Payment Edit
					$(document).on('click', '.wc-tp-cancel-payment-edit', function() {
						$('.wc-tp-edit-payment-row').remove();
						$('tr').show();
					});

					function loadPaymentMethods() {
						$.ajax({
							url: ajaxurl,
							type: 'POST',
							data: {
								action: 'wc_tp_get_payment_methods',
								user_id: userId,
								nonce: nonce
							},
							success: function(response) {
								if (response.success) {
									renderPaymentMethods(response.data.methods);
									populatePaymentMethodDropdown(response.data.methods);
								}
							}
						});
					}

					function renderPaymentMethods(methods) {
						const container = $('#wc-tp-payment-methods-container');
						
						if (!methods || methods.length === 0) {
							container.html('<div class="wc-tp-empty-state"><div class="wc-tp-empty-icon">💳</div><p>No payment methods added yet</p></div>');
							return;
						}

						let html = '<div class="wc-tp-payment-methods-list">';
						
						$.each(methods, function(i, method) {
							html += '<div class="wc-tp-payment-method-item">';
							html += '<div class="wc-tp-payment-method-info">';
							html += '<div class="wc-tp-payment-method-name">' + method.method_name + '</div>';
							html += '<div class="wc-tp-payment-method-details">' + method.method_details + '</div>';
							html += '</div>';
							html += '<div class="wc-tp-payment-method-actions">';
							html += '<button class="button wc-tp-delete-btn wc-tp-delete-method" data-method-id="' + method.id + '">Delete</button>';
							html += '</div>';
							html += '</div>';
						});

						html += '</div>';
						container.html(html);
					}

					function populatePaymentMethodDropdown(methods) {
						const dropdown = $('#wc-tp-payment-method');
						dropdown.find('option:not(:first)').remove();

						if (methods && methods.length > 0) {
							$.each(methods, function(i, method) {
								dropdown.append('<option value="' + method.method_name + '">' + method.method_name + '</option>');
							});
						}
					}

					function loadPaymentHistory() {
						$.ajax({
							url: ajaxurl,
							type: 'POST',
							data: {
								action: 'wc_tp_get_employee_payments',
								user_id: userId,
								nonce: nonce
							},
							success: function(response) {
								if (response.success) {
									renderPaymentHistory(response.data.payments);
								}
							}
						});
					}

					function renderPaymentHistory(payments) {
						const container = $('#wc-tp-payment-history-container');
						
						if (!payments || payments.length === 0) {
							container.html('<div class="wc-tp-empty-state"><div class="wc-tp-empty-icon">💰</div><p>No payments yet</p></div>');
							return;
						}

						let html = '<table class="wc-tp-data-table"><thead><tr>';
						html += '<th>Amount</th>';
						html += '<th>Date</th>';
						html += '<th>Method</th>';
						html += '<th>Added By</th>';
						html += '<th>Actions</th>';
						html += '</tr></thead><tbody>';

						$.each(payments, function(i, payment) {
							html += '<tr>';
							html += '<td class="wc-tp-payment-amount" data-amount="' + payment.amount + '"><strong>' + formatCurrency(payment.amount) + '</strong></td>';
							html += '<td class="wc-tp-payment-date" data-date="' + payment.date + '">' + payment.date + '</td>';
							html += '<td>' + (payment.payment_method || '-') + '</td>';
							html += '<td>' + (payment.added_by || 'System') + '</td>';
							html += '<td><div class="wc-tp-action-icons">';
							html += '<button class="wc-tp-action-icon wc-tp-edit-payment" data-payment-id="' + payment.id + '" title="Edit Payment"><span class="dashicons dashicons-edit"></span></button>';
							html += '<button class="wc-tp-action-icon wc-tp-delete-payment" data-payment-id="' + payment.id + '" title="Delete Payment"><span class="dashicons dashicons-trash"></span></button>';
							html += '</div></td>';
							html += '</tr>';
						});

						html += '</tbody></table>';
						container.html(html);
					}

					function updateStatsCards() {
						// Reload stats from server
						$.ajax({
							url: ajaxurl,
							type: 'POST',
							data: {
								action: 'wc_tp_get_employee_stats',
								user_id: userId,
								nonce: nonce
							},
							success: function(response) {
								if (response.success) {
									const stats = response.data;
									$('.wc-tp-stat-card').eq(1).find('.wc-tp-stat-value').text(formatCurrency(stats.total_earnings));
									$('.wc-tp-stat-card').eq(2).find('.wc-tp-stat-value').text(formatCurrency(stats.total_paid));
									$('.wc-tp-stat-card').eq(3).find('.wc-tp-stat-value').text(formatCurrency(stats.total_due));
								}
							}
						});
					}

					function formatCurrency(value) {
						return '<?php echo get_woocommerce_currency_symbol(); ?>' + parseFloat(value).toFixed(2);
					}
				}

				// ============================================================================
				// SALARY TAB
				// ============================================================================
				if ($('.wc-tp-salary-tab').length) {
					loadSalaryHistory();

					// Salary Type Change - Show/Hide Amount and Frequency
					$('#wc-tp-salary-type').on('change', function() {
						const salaryType = $(this).val();
						
						if (salaryType === 'commission') {
							$('.wc-tp-salary-amount-group, .wc-tp-salary-frequency-group').slideUp(200);
						} else {
							$('.wc-tp-salary-amount-group, .wc-tp-salary-frequency-group').slideDown(200);
						}
					});

					// Salary Form Submit
					$('#wc-tp-salary-form').on('submit', function(e) {
						e.preventDefault();

						const salaryType = $('#wc-tp-salary-type').val();
						const salaryAmount = $('#wc-tp-salary-amount').val();
						const salaryFrequency = $('#wc-tp-salary-frequency').val();

						if (salaryType !== 'commission' && (!salaryAmount || salaryAmount <= 0)) {
							alert('Please enter a valid salary amount');
							return;
						}

						$.ajax({
							url: ajaxurl,
							type: 'POST',
							data: {
								action: 'wc_tp_update_employee_salary',
								user_id: userId,
								salary_type: salaryType,
								salary_amount: salaryAmount,
								salary_frequency: salaryFrequency,
								nonce: nonce
							},
							success: function(response) {
								if (response.success) {
									alert('Salary updated successfully');
									loadSalaryHistory();
								} else {
									alert('Failed to update salary: ' + response.data);
								}
							},
							error: function() {
								alert('Error updating salary');
							}
						});
					});

					function loadSalaryHistory() {
						$.ajax({
							url: ajaxurl,
							type: 'POST',
							data: {
								action: 'wc_tp_get_salary_history',
								user_id: userId,
								nonce: nonce
							},
							success: function(response) {
								if (response.success) {
									renderSalaryHistory(response.data.history);
								}
							}
						});
					}

					function renderSalaryHistory(history) {
						const container = $('#wc-tp-salary-history-container');
						
						if (!history || history.length === 0) {
							container.html('<div class="wc-tp-empty-state"><div class="wc-tp-empty-icon">📊</div><p>No salary history yet</p></div>');
							return;
						}

						let html = '<table class="wc-tp-data-table"><thead><tr>';
						html += '<th>Date</th>';
						html += '<th>Old Type</th>';
						html += '<th>New Type</th>';
						html += '<th>Old Amount</th>';
						html += '<th>New Amount</th>';
						html += '<th>Frequency</th>';
						html += '<th>Changed By</th>';
						html += '</tr></thead><tbody>';

						$.each(history, function(i, entry) {
							const changedByUser = entry.changed_by ? 'User #' + entry.changed_by : 'System';
							
							html += '<tr>';
							html += '<td>' + entry.date + '</td>';
							html += '<td>' + formatSalaryType(entry.old_type) + '</td>';
							html += '<td><strong>' + formatSalaryType(entry.new_type) + '</strong></td>';
							html += '<td>' + (entry.old_amount ? formatCurrency(entry.old_amount) : '-') + '</td>';
							html += '<td><strong>' + (entry.new_amount ? formatCurrency(entry.new_amount) : '-') + '</strong></td>';
							html += '<td>' + (entry.new_frequency ? entry.new_frequency : '-') + '</td>';
							html += '<td>' + changedByUser + '</td>';
							html += '</tr>';
						});

						html += '</tbody></table>';
						container.html(html);
					}

					function formatSalaryType(type) {
						const types = {
							'commission': 'Commission Based',
							'fixed': 'Fixed Salary',
							'combined': 'Combined (Base + Commission)'
						};
						return types[type] || type;
					}

					function formatCurrency(value) {
						return '<?php echo get_woocommerce_currency_symbol(); ?>' + parseFloat(value).toFixed(2);
					}
				}
			});
		</script>
		<?php
	}
}
