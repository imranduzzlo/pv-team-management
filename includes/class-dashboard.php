<?php
/**
 * Admin Dashboard
 */

class WC_Team_Payroll_Dashboard {

	/**
	 * Initialize dashboard
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
	}

	/**
	 * Add menu items
	 */
	public static function add_menu() {
		// Create custom parent menu - points to Dashboard
		add_menu_page(
			__( 'Team Payroll', 'wc-team-payroll' ),
			__( 'Team Payroll', 'wc-team-payroll' ),
			'manage_woocommerce',
			'wc-team-payroll',
			array( __CLASS__, 'render_dashboard' ),
			'dashicons-money-alt',
			56
		);

		// Submenu: Payroll
		add_submenu_page(
			'wc-team-payroll',
			__( 'Payroll', 'wc-team-payroll' ),
			__( 'Payroll', 'wc-team-payroll' ),
			'manage_woocommerce',
			'wc-team-payroll-payroll',
			array( __CLASS__, 'render_payroll' )
		);
	}

	/**
	 * Enqueue scripts and styles
	 */
	public static function enqueue_scripts( $hook ) {
		if ( strpos( $hook, 'wc-team-payroll' ) === false ) {
			return;
		}

		wp_enqueue_script( 'jquery-datatables', 'https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js', array( 'jquery' ), '1.13.6', true );
		wp_enqueue_style( 'jquery-datatables', 'https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css', array(), '1.13.6' );

		wp_enqueue_style( 'wc-team-payroll-dashboard', WC_TEAM_PAYROLL_URL . 'assets/css/dashboard.css', array(), WC_TEAM_PAYROLL_VERSION );
		wp_enqueue_script( 'wc-team-payroll-dashboard', WC_TEAM_PAYROLL_URL . 'assets/js/dashboard.js', array( 'jquery', 'jquery-datatables' ), WC_TEAM_PAYROLL_VERSION, true );
	}

	/**
	 * Render dashboard page
	 */
	public static function render_dashboard() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Unauthorized', 'wc-team-payroll' ) );
		}

		$year = isset( $_GET['year'] ) ? intval( $_GET['year'] ) : date( 'Y' );
		$month = isset( $_GET['month'] ) ? intval( $_GET['month'] ) : date( 'm' );

		// Get payroll data safely
		$payroll = array();
		if ( class_exists( 'WC_Team_Payroll_Payroll_Engine' ) ) {
			$payroll = WC_Team_Payroll_Payroll_Engine::get_monthly_payroll( $year, $month );
		}

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Team Payroll Dashboard', 'wc-team-payroll' ); ?></h1>

			<div class="wc-team-payroll-filters">
				<form method="get">
					<input type="hidden" name="page" value="wc-team-payroll" />
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
								<td><?php echo esc_html( $data['user'] ? $data['user']->display_name : 'Unknown' ); ?></td>
								<td><?php echo esc_html( $data['user'] ? $data['user']->user_email : 'N/A' ); ?></td>
								<td><?php echo esc_html( $data['orders'] ); ?></td>
								<td><?php echo wp_kses_post( wc_price( $data['total'] ) ); ?></td>
								<td><?php echo wp_kses_post( wc_price( $data['paid'] ) ); ?></td>
								<td><?php echo wp_kses_post( wc_price( $data['due'] ) ); ?></td>
								<td>
									<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'wc-team-payroll-employees', 'user_id' => $data['user_id'] ), admin_url( 'admin.php' ) ) ); ?>" class="button button-small"><?php esc_html_e( 'View', 'wc-team-payroll' ); ?></a>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render payroll page
	 */
	public static function render_payroll() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Unauthorized', 'wc-team-payroll' ) );
		}

		$year = isset( $_GET['year'] ) ? intval( $_GET['year'] ) : date( 'Y' );
		$month = isset( $_GET['month'] ) ? intval( $_GET['month'] ) : date( 'm' );

		// Get payroll data safely
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
									<button class="button mark-paid" data-user-id="<?php echo esc_attr( $data['user_id'] ); ?>" data-year="<?php echo esc_attr( $year ); ?>" data-month="<?php echo esc_attr( $month ); ?>" data-amount="<?php echo esc_attr( $data['due'] ); ?>"><?php esc_html_e( 'Mark Paid', 'wc-team-payroll' ); ?></button>
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
