<?php
/**
 * Salary Display Helper
 * Helper functions to display salary information in dashboard and employee pages
 */

class WC_Team_Payroll_Salary_Display_Helper {

	/**
	 * Get formatted earnings breakdown for an employee
	 * 
	 * @param int $user_id User ID
	 * @return array Earnings breakdown
	 */
	public static function get_earnings_breakdown( $user_id ) {
		$core_engine = new WC_Team_Payroll_Core_Engine();

		// Get commission earnings
		$commission_earnings = $core_engine->get_user_commission_earnings( $user_id );

		// Get base salary earnings
		$salary_earnings = get_user_meta( $user_id, '_wc_tp_total_earnings', true );
		if ( ! $salary_earnings ) {
			$salary_earnings = 0;
		}

		// Get pending accumulation
		$pending = WC_Team_Payroll_Salary_Automation::get_user_pending_accumulation( $user_id );

		// Total earnings
		$total_earnings = $commission_earnings + $salary_earnings;

		return array(
			'commission_earnings' => $commission_earnings,
			'salary_earnings'     => $salary_earnings,
			'pending_accumulation' => $pending['accumulated_total'],
			'total_earnings'      => $total_earnings,
			'total_with_pending'  => $total_earnings + $pending['accumulated_total'],
			'pending_info'        => $pending,
		);
	}

	/**
	 * Display earnings breakdown HTML
	 * 
	 * @param int $user_id User ID
	 * @return string HTML output
	 */
	public static function display_earnings_breakdown( $user_id ) {
		$breakdown = self::get_earnings_breakdown( $user_id );

		ob_start();
		?>
		<div class="wc-tp-earnings-breakdown">
			<div class="wc-tp-earnings-card">
				<h3><?php esc_html_e( 'Earnings Breakdown', 'wc-team-payroll' ); ?></h3>
				
				<div class="wc-tp-earnings-row">
					<span class="wc-tp-earnings-label"><?php esc_html_e( 'Commission Earnings:', 'wc-team-payroll' ); ?></span>
					<span class="wc-tp-earnings-value"><?php echo wc_price( $breakdown['commission_earnings'] ); ?></span>
				</div>

				<div class="wc-tp-earnings-row">
					<span class="wc-tp-earnings-label"><?php esc_html_e( 'Base Salary Earnings:', 'wc-team-payroll' ); ?></span>
					<span class="wc-tp-earnings-value"><?php echo wc_price( $breakdown['salary_earnings'] ); ?></span>
				</div>

				<?php if ( $breakdown['pending_accumulation'] > 0 ) : ?>
				<div class="wc-tp-earnings-row wc-tp-pending">
					<span class="wc-tp-earnings-label">
						<?php esc_html_e( 'Pending Accumulation:', 'wc-team-payroll' ); ?>
						<small>(<?php echo esc_html( $breakdown['pending_info']['days_accumulated'] ); ?> days)</small>
					</span>
					<span class="wc-tp-earnings-value"><?php echo wc_price( $breakdown['pending_accumulation'] ); ?></span>
				</div>

				<?php if ( $breakdown['pending_info']['next_transfer'] ) : ?>
				<div class="wc-tp-earnings-note">
					<small>
						<?php
						printf(
							esc_html__( 'Will be transferred on %s', 'wc-team-payroll' ),
							esc_html( date_i18n( get_option( 'date_format' ), strtotime( $breakdown['pending_info']['next_transfer'] ) ) )
						);
						?>
					</small>
				</div>
				<?php endif; ?>
				<?php endif; ?>

				<div class="wc-tp-earnings-row wc-tp-total">
					<span class="wc-tp-earnings-label"><strong><?php esc_html_e( 'Total Earnings:', 'wc-team-payroll' ); ?></strong></span>
					<span class="wc-tp-earnings-value"><strong><?php echo wc_price( $breakdown['total_earnings'] ); ?></strong></span>
				</div>

				<?php if ( $breakdown['pending_accumulation'] > 0 ) : ?>
				<div class="wc-tp-earnings-row wc-tp-projected">
					<span class="wc-tp-earnings-label"><?php esc_html_e( 'Projected Total:', 'wc-team-payroll' ); ?></span>
					<span class="wc-tp-earnings-value"><?php echo wc_price( $breakdown['total_with_pending'] ); ?></span>
				</div>
				<?php endif; ?>
			</div>
		</div>

		<style>
			.wc-tp-earnings-breakdown {
				margin: 20px 0;
			}

			.wc-tp-earnings-card {
				background: #fff;
				border: 1px solid #E5EAF0;
				border-radius: 8px;
				padding: 20px;
			}

			.wc-tp-earnings-card h3 {
				margin-top: 0;
				margin-bottom: 15px;
				color: #212B36;
				font-size: 1.2rem;
				border-bottom: 2px solid #FF9900;
				padding-bottom: 10px;
			}

			.wc-tp-earnings-row {
				display: flex;
				justify-content: space-between;
				align-items: center;
				padding: 10px 0;
				border-bottom: 1px solid #F4F4F4;
			}

			.wc-tp-earnings-row:last-child {
				border-bottom: none;
			}

			.wc-tp-earnings-label {
				color: #454F5B;
				font-size: 0.95rem;
			}

			.wc-tp-earnings-label small {
				color: #919EAB;
				font-size: 0.85rem;
			}

			.wc-tp-earnings-value {
				color: #212B36;
				font-size: 1rem;
				font-weight: 600;
			}

			.wc-tp-earnings-row.wc-tp-pending {
				background: #FFF4E5;
				margin: 0 -10px;
				padding: 10px;
				border-radius: 4px;
			}

			.wc-tp-earnings-row.wc-tp-total {
				background: #F4F4F4;
				margin: 10px -10px 0;
				padding: 15px 10px;
				border-radius: 4px;
			}

			.wc-tp-earnings-row.wc-tp-total .wc-tp-earnings-label,
			.wc-tp-earnings-row.wc-tp-total .wc-tp-earnings-value {
				font-size: 1.1rem;
			}

			.wc-tp-earnings-row.wc-tp-projected {
				background: #E8F5E9;
				margin: 5px -10px 0;
				padding: 10px;
				border-radius: 4px;
			}

			.wc-tp-earnings-note {
				margin-top: 10px;
				padding: 8px;
				background: #FFF9E6;
				border-left: 3px solid #FF9900;
				border-radius: 4px;
			}

			.wc-tp-earnings-note small {
				color: #666;
			}
		</style>
		<?php
		return ob_get_clean();
	}

	/**
	 * Display salary transaction log
	 * 
	 * @param int $user_id User ID
	 * @param int $limit Number of transactions to display
	 * @return string HTML output
	 */
	public static function display_salary_transactions( $user_id, $limit = 20 ) {
		$transactions = WC_Team_Payroll_Salary_Automation::get_user_salary_transactions( $user_id, $limit );

		if ( empty( $transactions ) ) {
			return '<p>' . esc_html__( 'No salary transactions yet.', 'wc-team-payroll' ) . '</p>';
		}

		ob_start();
		?>
		<div class="wc-tp-salary-transactions">
			<h3><?php esc_html_e( 'Recent Salary Transactions', 'wc-team-payroll' ); ?></h3>
			<table class="wc-tp-data-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Date', 'wc-team-payroll' ); ?></th>
						<th><?php esc_html_e( 'Type', 'wc-team-payroll' ); ?></th>
						<th><?php esc_html_e( 'Amount', 'wc-team-payroll' ); ?></th>
						<th><?php esc_html_e( 'Note', 'wc-team-payroll' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $transactions as $transaction ) : ?>
					<tr>
						<td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $transaction['date'] ) ) ); ?></td>
						<td><?php echo esc_html( ucwords( str_replace( '_', ' ', $transaction['type'] ) ) ); ?></td>
						<td><?php echo wc_price( $transaction['amount'] ); ?></td>
						<td><?php echo esc_html( $transaction['note'] ); ?></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<style>
			.wc-tp-salary-transactions {
				margin: 20px 0;
			}

			.wc-tp-salary-transactions h3 {
				margin-bottom: 15px;
				color: #212B36;
				font-size: 1.2rem;
			}

			.wc-tp-data-table {
				width: 100%;
				border-collapse: collapse;
				background: #fff;
				border: 1px solid #E5EAF0;
				border-radius: 8px;
				overflow: hidden;
			}

			.wc-tp-data-table thead {
				background: #F4F4F4;
			}

			.wc-tp-data-table th {
				padding: 12px;
				text-align: left;
				font-weight: 600;
				color: #212B36;
				border-bottom: 2px solid #E5EAF0;
			}

			.wc-tp-data-table td {
				padding: 10px 12px;
				border-bottom: 1px solid #F4F4F4;
				color: #454F5B;
			}

			.wc-tp-data-table tbody tr:hover {
				background: #FFF4E5;
			}

			.wc-tp-data-table tbody tr:last-child td {
				border-bottom: none;
			}
		</style>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get salary status badge HTML
	 * 
	 * @param int $user_id User ID
	 * @return string HTML badge
	 */
	public static function get_salary_status_badge( $user_id ) {
		$is_fixed = get_user_meta( $user_id, '_wc_tp_fixed_salary', true );
		$is_combined = get_user_meta( $user_id, '_wc_tp_combined_salary', true );
		$salary_amount = get_user_meta( $user_id, '_wc_tp_salary_amount', true );
		$salary_frequency = get_user_meta( $user_id, '_wc_tp_salary_frequency', true );

		if ( $is_fixed ) {
			$type = __( 'Fixed', 'wc-team-payroll' );
			$color = '#2196F3';
		} elseif ( $is_combined ) {
			$type = __( 'Combined', 'wc-team-payroll' );
			$color = '#4CAF50';
		} else {
			$type = __( 'Commission', 'wc-team-payroll' );
			$color = '#FF9900';
		}

		$badge = '<span class="wc-tp-salary-badge" style="background: ' . esc_attr( $color ) . '; color: white; padding: 4px 8px; border-radius: 4px; font-size: 0.85rem; font-weight: 600;">';
		$badge .= esc_html( $type );
		
		if ( $salary_amount && $salary_frequency ) {
			$badge .= ' - ' . wc_price( $salary_amount ) . '/' . esc_html( $salary_frequency );
		}
		
		$badge .= '</span>';

		return $badge;
	}
}
