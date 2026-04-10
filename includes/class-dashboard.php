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

		$year = isset( $_GET['year'] ) ? intval( $_GET['year'] ) : date( 'Y' );
		$month = isset( $_GET['month'] ) ? intval( $_GET['month'] ) : date( 'm' );

		// Get payroll data
		$payroll = array();
		if ( class_exists( 'WC_Team_Payroll_Payroll_Engine' ) ) {
			$payroll = WC_Team_Payroll_Payroll_Engine::get_monthly_payroll( $year, $month );
		}

		// Calculate stats
		$total_employees = count( $payroll );
		$total_earnings = 0;
		$total_paid = 0;
		$total_due = 0;
		$total_orders = 0;

		foreach ( $payroll as $data ) {
			$total_earnings += $data['total'];
			$total_paid += $data['paid'];
			$total_due += $data['due'];
			$total_orders += $data['orders'];
		}

		?>
		<div class="wrap wc-team-payroll-dashboard">
			<h1><?php esc_html_e( 'Team Payroll Dashboard', 'wc-team-payroll' ); ?></h1>

			<!-- Filter Section -->
			<div class="wc-team-payroll-filters">
				<form method="get">
					<input type="hidden" name="page" value="wc-team-payroll" />
					<select name="month">
						<?php for ( $m = 1; $m <= 12; $m++ ) : ?>
							<option value="<?php echo esc_attr( $m ); ?>" <?php selected( $month, $m ); ?>><?php echo esc_html( date( 'F', mktime( 0, 0, 0, $m, 1 ) ) ); ?></option>
						<?php endfor; ?>
					</select>
					<input type="number" name="year" value="<?php echo esc_attr( $year ); ?>" min="2020" max="2099" />
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Filter', 'wc-team-payroll' ); ?></button>
				</form>
			</div>

			<!-- Stats Cards -->
			<div class="wc-tp-stats-grid">
				<div class="wc-tp-stat-card">
					<div class="wc-tp-stat-icon">👥</div>
					<div class="wc-tp-stat-content">
						<div class="wc-tp-stat-value"><?php echo esc_html( $total_employees ); ?></div>
						<div class="wc-tp-stat-label"><?php esc_html_e( 'Total Employees', 'wc-team-payroll' ); ?></div>
					</div>
				</div>

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

			<!-- Data Table -->
			<div class="wc-tp-table-section">
				<h2><?php esc_html_e( 'Employee Payroll Details', 'wc-team-payroll' ); ?></h2>

				<?php if ( empty( $payroll ) ) : ?>
					<div class="notice notice-info"><p><?php esc_html_e( 'No payroll data for this period.', 'wc-team-payroll' ); ?></p></div>
				<?php else : ?>
					<table class="wc-tp-data-table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Employee', 'wc-team-payroll' ); ?></th>
								<th><?php esc_html_e( 'Email', 'wc-team-payroll' ); ?></th>
								<th><?php esc_html_e( 'Orders', 'wc-team-payroll' ); ?></th>
								<th><?php esc_html_e( 'Total Earnings', 'wc-team-payroll' ); ?></th>
								<th><?php esc_html_e( 'Paid', 'wc-team-payroll' ); ?></th>
								<th><?php esc_html_e( 'Due', 'wc-team-payroll' ); ?></th>
								<th><?php esc_html_e( 'Action', 'wc-team-payroll' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $payroll as $data ) : ?>
								<tr>
									<td><strong><?php echo esc_html( $data['user'] ? $data['user']->display_name : 'Unknown' ); ?></strong></td>
									<td><?php echo esc_html( $data['user'] ? $data['user']->user_email : 'N/A' ); ?></td>
									<td><span class="wc-tp-badge"><?php echo esc_html( $data['orders'] ); ?></span></td>
									<td><?php echo wp_kses_post( wc_price( $data['total'] ) ); ?></td>
									<td><span class="wc-tp-paid"><?php echo wp_kses_post( wc_price( $data['paid'] ) ); ?></span></td>
									<td><span class="wc-tp-due"><?php echo wp_kses_post( wc_price( $data['due'] ) ); ?></span></td>
									<td>
										<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'wc-team-payroll-employee-detail', 'user_id' => $data['user_id'] ), admin_url( 'admin.php' ) ) ); ?>" class="button button-small button-primary"><?php esc_html_e( 'View', 'wc-team-payroll' ); ?></a>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>
		</div>

		<style>
			.wc-team-payroll-dashboard {
				background: #f5f5f5;
				padding: 20px;
			}

			.wc-team-payroll-filters {
				background: white;
				padding: 15px;
				border-radius: 5px;
				margin-bottom: 20px;
				box-shadow: 0 1px 3px rgba(0,0,0,0.1);
			}

			.wc-team-payroll-filters form {
				display: flex;
				gap: 10px;
				align-items: center;
			}

			.wc-team-payroll-filters select,
			.wc-team-payroll-filters input {
				padding: 8px 12px;
				border: 1px solid #ddd;
				border-radius: 4px;
			}

			.wc-tp-stats-grid {
				display: grid;
				grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
				gap: 15px;
				margin-bottom: 30px;
			}

			.wc-tp-stat-card {
				background: white;
				padding: 20px;
				border-radius: 8px;
				box-shadow: 0 2px 8px rgba(0,0,0,0.1);
				display: flex;
				align-items: center;
				gap: 15px;
				transition: transform 0.2s, box-shadow 0.2s;
			}

			.wc-tp-stat-card:hover {
				transform: translateY(-2px);
				box-shadow: 0 4px 12px rgba(0,0,0,0.15);
			}

			.wc-tp-stat-icon {
				font-size: 32px;
				min-width: 50px;
				text-align: center;
			}

			.wc-tp-stat-content {
				flex: 1;
			}

			.wc-tp-stat-value {
				font-size: 24px;
				font-weight: bold;
				color: #0073aa;
				margin-bottom: 5px;
			}

			.wc-tp-stat-label {
				font-size: 13px;
				color: #666;
				text-transform: uppercase;
				letter-spacing: 0.5px;
			}

			.wc-tp-table-section {
				background: white;
				padding: 20px;
				border-radius: 8px;
				box-shadow: 0 2px 8px rgba(0,0,0,0.1);
			}

			.wc-tp-table-section h2 {
				margin-top: 0;
				margin-bottom: 20px;
				color: #333;
				border-bottom: 2px solid #0073aa;
				padding-bottom: 10px;
			}

			.wc-tp-data-table {
				width: 100%;
				border-collapse: collapse;
			}

			.wc-tp-data-table thead {
				background: #f9f9f9;
				border-bottom: 2px solid #0073aa;
			}

			.wc-tp-data-table th {
				padding: 12px;
				text-align: left;
				font-weight: 600;
				color: #333;
			}

			.wc-tp-data-table td {
				padding: 12px;
				border-bottom: 1px solid #eee;
			}

			.wc-tp-data-table tbody tr:hover {
				background: #f5f5f5;
			}

			.wc-tp-badge {
				background: #0073aa;
				color: white;
				padding: 4px 8px;
				border-radius: 3px;
				font-size: 12px;
				font-weight: 600;
			}

			.wc-tp-paid {
				color: #28a745;
				font-weight: 600;
			}

			.wc-tp-due {
				color: #dc3545;
				font-weight: 600;
			}

			.button-primary {
				background: #0073aa;
				border-color: #0073aa;
				color: white;
			}

			.button-primary:hover {
				background: #005a87;
				border-color: #005a87;
			}
		</style>
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
