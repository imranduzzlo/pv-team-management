<?php
/**
 * Payments Page - All Payments with Filtering and Sorting
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Team_Payroll_Payments_Page {

	/**
	 * Render payments page
	 */
	public function render_payments() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized', 'wc-team-payroll' ) );
		}

		?>
		<div class="wrap wc-team-payroll-payments">
			<h1><?php esc_html_e( 'All Payments', 'wc-team-payroll' ); ?></h1>

			<!-- Add Payment Section -->
			<div class="wc-tp-add-payment-section" style="background: #fff; padding: 20px; border-radius: 8px; margin-bottom: 30px; border: 1px solid #e5eaf0;">
				<h2><?php esc_html_e( 'Add New Payment', 'wc-team-payroll' ); ?></h2>
				<form id="wc-tp-add-payment-form" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
					<div>
						<label for="wc-tp-payment-employee" style="display: block; margin-bottom: 5px; font-weight: 600;"><?php esc_html_e( 'Employee', 'wc-team-payroll' ); ?> *</label>
						<select id="wc-tp-payment-employee" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
							<option value=""><?php esc_html_e( 'Select Employee', 'wc-team-payroll' ); ?></option>
							<?php
							$employees = get_users( array(
								'role__in' => array( 'shop_employee', 'shop_manager', 'administrator' ),
								'orderby'  => 'display_name',
								'order'    => 'ASC',
							) );
							foreach ( $employees as $employee ) {
								$vb_user_id = get_user_meta( $employee->ID, 'vb_user_id', true );
								$display_name = $vb_user_id ? esc_html( $vb_user_id ) . ' ' . esc_html( $employee->display_name ) : esc_html( $employee->display_name );
								echo '<option value="' . esc_attr( $employee->ID ) . '">' . $display_name . '</option>';
							}
							?>
						</select>
					</div>

					<div>
						<label for="wc-tp-payment-amount" style="display: block; margin-bottom: 5px; font-weight: 600;"><?php esc_html_e( 'Amount', 'wc-team-payroll' ); ?> *</label>
						<input type="number" id="wc-tp-payment-amount" placeholder="0.00" step="0.01" min="0" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" />
					</div>

					<div>
						<label for="wc-tp-payment-date" style="display: block; margin-bottom: 5px; font-weight: 600;"><?php esc_html_e( 'Payment Date', 'wc-team-payroll' ); ?> *</label>
						<input type="datetime-local" id="wc-tp-payment-date" value="<?php echo esc_attr( date( 'Y-m-d\TH:i' ) ); ?>" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" />
					</div>

					<div>
						<label for="wc-tp-payment-method" style="display: block; margin-bottom: 5px; font-weight: 600;"><?php esc_html_e( 'Payment Method', 'wc-team-payroll' ); ?></label>
						<select id="wc-tp-payment-method" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
							<option value=""><?php esc_html_e( 'Select Method', 'wc-team-payroll' ); ?></option>
						</select>
					</div>

					<div>
						<label for="wc-tp-payment-note" style="display: block; margin-bottom: 5px; font-weight: 600;"><?php esc_html_e( 'Note (Optional)', 'wc-team-payroll' ); ?></label>
						<input type="text" id="wc-tp-payment-note" placeholder="Add a note..." style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" />
					</div>

					<div style="display: flex; align-items: flex-end;">
						<button type="submit" class="button button-primary" id="wc-tp-add-payment-btn" style="width: 100%;"><?php esc_html_e( 'Add Payment', 'wc-team-payroll' ); ?></button>
					</div>
				</form>
				<?php wp_nonce_field( 'wc_team_payroll_nonce', 'wc_team_payroll_nonce' ); ?>
			</div>

			<!-- Search Filter -->
			<div class="wc-tp-search-filter">
				<input type="text" id="wc-tp-payments-search" placeholder="<?php esc_attr_e( 'Search by Employee Name, ID, Email, Phone...', 'wc-team-payroll' ); ?>" />
				<button type="button" class="button button-secondary" id="wc-tp-payments-search-clear"><?php esc_html_e( 'Clear', 'wc-team-payroll' ); ?></button>
			</div>

			<!-- Unified Filter Section -->
			<div class="wc-tp-unified-filter">
				<div class="wc-tp-filter-row">
					<!-- Date Range Preset -->
					<div class="wc-tp-filter-group">
						<label><?php esc_html_e( 'Date Range:', 'wc-team-payroll' ); ?></label>
						<select id="wc-tp-payments-date-preset">
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
						<input type="date" id="wc-tp-payments-start-date" value="<?php echo esc_attr( date( 'Y-m-01' ) ); ?>" />
						<span class="wc-tp-date-separator">to</span>
						<input type="date" id="wc-tp-payments-end-date" value="<?php echo esc_attr( date( 'Y-m-t' ) ); ?>" />
					</div>

					<!-- Salary Type Filter -->
					<div class="wc-tp-filter-group">
						<label><?php esc_html_e( 'Employee Type:', 'wc-team-payroll' ); ?></label>
						<select id="wc-tp-payments-salary-type-filter">
							<option value=""><?php esc_html_e( 'All Types', 'wc-team-payroll' ); ?></option>
							<option value="commission"><?php esc_html_e( 'Commission Based', 'wc-team-payroll' ); ?></option>
							<option value="fixed"><?php esc_html_e( 'Fixed Salary', 'wc-team-payroll' ); ?></option>
							<option value="combined"><?php esc_html_e( 'Combined (Base + Commission)', 'wc-team-payroll' ); ?></option>
						</select>
					</div>

					<!-- Filter Button -->
					<div class="wc-tp-filter-group">
						<button type="button" class="button button-primary" id="wc-tp-payments-filter-btn"><?php esc_html_e( 'Filter', 'wc-team-payroll' ); ?></button>
					</div>
				</div>
			</div>

			<!-- Payments Table Section -->
			<div class="wc-tp-table-section" id="wc-tp-payments-table-section">
				<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
					<h2 style="margin: 0;"><?php esc_html_e( 'Payment Records', 'wc-team-payroll' ); ?></h2>
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
				<div id="wc-tp-payments-table-container">
					<!-- Content will be loaded via AJAX -->
				</div>
				<!-- Pagination -->
				<div id="wc-tp-payments-pagination" style="margin-top: 20px; text-align: center;"></div>
			</div>
		</div>

		<script>
		(function($) {
			'use strict';
			
			$(document).ready(function() {
				// Load payment methods when employee is selected
				$('#wc-tp-payment-employee').on('change', function() {
					var employeeId = $(this).val();
					var nonce = $('#wc_team_payroll_nonce').val();
					
					if (!employeeId) {
						$('#wc-tp-payment-method').html('<option value=""><?php esc_js_e( 'Select Method', 'wc-team-payroll' ); ?></option>');
						return;
					}
					
					$.ajax({
						url: ajaxurl,
						type: 'POST',
						data: {
							action: 'wc_tp_get_payment_methods',
							user_id: employeeId,
							nonce: nonce
						},
						success: function(response) {
							if (response.success && response.data.methods && response.data.methods.length > 0) {
								var html = '<option value=""><?php esc_js_e( 'Select Method', 'wc-team-payroll' ); ?></option>';
								$.each(response.data.methods, function(i, method) {
									html += '<option value="' + method.method_name + '">' + method.method_name + '</option>';
								});
								$('#wc-tp-payment-method').html(html);
							} else {
								$('#wc-tp-payment-method').html('<option value=""><?php esc_js_e( 'No payment methods', 'wc-team-payroll' ); ?></option>');
							}
						},
						error: function() {
							$('#wc-tp-payment-method').html('<option value=""><?php esc_js_e( 'Error loading methods', 'wc-team-payroll' ); ?></option>');
						}
					});
				});
				
				// Add payment form submit
				$('#wc-tp-add-payment-form').on('submit', function(e) {
					e.preventDefault();
					
					var employeeId = $('#wc-tp-payment-employee').val();
					var amount = $('#wc-tp-payment-amount').val();
					var date = $('#wc-tp-payment-date').val();
					var method = $('#wc-tp-payment-method').val();
					var note = $('#wc-tp-payment-note').val();
					var nonce = $('#wc_team_payroll_nonce').val();
					
					if (!employeeId || !amount || !date) {
						wcTPToast('<?php esc_js_e( 'Please fill all required fields', 'wc-team-payroll' ); ?>', 'error');
						return;
					}
					
					$('#wc-tp-add-payment-btn').prop('disabled', true).text('<?php esc_js_e( 'Adding...', 'wc-team-payroll' ); ?>');
					
					$.ajax({
						url: ajaxurl,
						type: 'POST',
						data: {
							action: 'wc_tp_add_payment',
							user_id: employeeId,
							amount: amount,
							payment_date: date,
							payment_method: method || '',
							note: note,
							nonce: nonce
						},
						success: function(response) {
							if (response.success) {
								wcTPToast('<?php esc_js_e( 'Payment added successfully', 'wc-team-payroll' ); ?>');
								$('#wc-tp-add-payment-form')[0].reset();
								$('#wc-tp-payment-date').val(new Date().toISOString().slice(0, 16));
								$('#wc-tp-payment-method').html('<option value=""><?php esc_js_e( 'Select Method', 'wc-team-payroll' ); ?></option>');
							} else {
								wcTPToast('<?php esc_js_e( 'Error adding payment', 'wc-team-payroll' ); ?>', 'error');
							}
						},
						error: function() {
							wcTPToast('<?php esc_js_e( 'Error adding payment', 'wc-team-payroll' ); ?>', 'error');
						},
						complete: function() {
							$('#wc-tp-add-payment-btn').prop('disabled', false).text('<?php esc_js_e( 'Add Payment', 'wc-team-payroll' ); ?>');
						}
					});
				});
			});
		})(jQuery);
		</script>
		<?php
	}
}
