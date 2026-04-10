<?php
/**
 * Settings Page
 */

class WC_Team_Payroll_Settings {

	/**
	 * Render settings page
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized', 'wc-team-payroll' ) );
		}

		// Handle form submission
		if ( isset( $_POST['submit'] ) && check_admin_referer( 'wc_team_payroll_settings_nonce' ) ) {
			$settings = isset( $_POST['wc_team_payroll_settings'] ) ? array_map( 'sanitize_text_field', $_POST['wc_team_payroll_settings'] ) : array();
			$checkout_fields = isset( $_POST['wc_team_payroll_checkout_fields'] ) ? array_map( 'sanitize_text_field', $_POST['wc_team_payroll_checkout_fields'] ) : array();
			$acf_fields = isset( $_POST['wc_team_payroll_acf_fields'] ) ? array_map( 'sanitize_text_field', $_POST['wc_team_payroll_acf_fields'] ) : array();

			update_option( 'wc_team_payroll_settings', $settings );
			update_option( 'wc_team_payroll_checkout_fields', $checkout_fields );
			update_option( 'wc_team_payroll_acf_fields', $acf_fields );

			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved successfully!', 'wc-team-payroll' ) . '</p></div>';
		}

		$settings = get_option( 'wc_team_payroll_settings', array() );
		$checkout_fields = get_option( 'wc_team_payroll_checkout_fields', array() );
		$acf_fields = get_option( 'wc_team_payroll_acf_fields', array() );
		
		$agent_percentage = isset( $settings['agent_percentage'] ) ? floatval( $settings['agent_percentage'] ) : 70;
		$processor_percentage = isset( $settings['processor_percentage'] ) ? floatval( $settings['processor_percentage'] ) : 30;
		$extra_earnings_rules = isset( $settings['extra_earnings_rules'] ) ? $settings['extra_earnings_rules'] : array();
		$enable_breakdown = isset( $settings['enable_breakdown'] ) ? $settings['enable_breakdown'] : 1;
		$enable_myaccount = isset( $settings['enable_myaccount'] ) ? $settings['enable_myaccount'] : 1;
		$enable_shortcodes = isset( $settings['enable_shortcodes'] ) ? $settings['enable_shortcodes'] : 1;
		
		$agent_field_name = isset( $checkout_fields['agent_field_name'] ) ? $checkout_fields['agent_field_name'] : 'order_other_agent_or_not';
		$processor_field_name = isset( $checkout_fields['processor_field_name'] ) ? $checkout_fields['processor_field_name'] : '_processor_user_id';
		$agent_user_roles = isset( $checkout_fields['agent_user_roles'] ) ? $checkout_fields['agent_user_roles'] : array( 'shop_employee', 'shop_manager', 'administrator' );
		
		$commission_field_name = isset( $acf_fields['commission_field_name'] ) ? $acf_fields['commission_field_name'] : 'team_commission';
		$agent_dropdown_field = isset( $acf_fields['agent_dropdown_field'] ) ? $acf_fields['agent_dropdown_field'] : 'order_agent_name';

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'WooCommerce Team Payroll Settings', 'wc-team-payroll' ); ?></h1>

			<form method="post" action="">
				<?php wp_nonce_field( 'wc_team_payroll_settings_nonce' ); ?>

				<!-- Commission Settings -->
				<h2><?php esc_html_e( 'Commission Settings', 'wc-team-payroll' ); ?></h2>
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="agent_percentage"><?php esc_html_e( 'Agent Commission %', 'wc-team-payroll' ); ?></label>
						</th>
						<td>
							<input type="number" id="agent_percentage" name="wc_team_payroll_settings[agent_percentage]" value="<?php echo esc_attr( $agent_percentage ); ?>" step="0.01" min="0" max="100" />
							<p class="description"><?php esc_html_e( 'Percentage of commission for agent when different from processor', 'wc-team-payroll' ); ?></p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="processor_percentage"><?php esc_html_e( 'Processor Commission %', 'wc-team-payroll' ); ?></label>
						</th>
						<td>
							<input type="number" id="processor_percentage" name="wc_team_payroll_settings[processor_percentage]" value="<?php echo esc_attr( $processor_percentage ); ?>" step="0.01" min="0" max="100" />
							<p class="description"><?php esc_html_e( 'Percentage of commission for processor when different from agent', 'wc-team-payroll' ); ?></p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label><?php esc_html_e( 'Refunded Order Commission', 'wc-team-payroll' ); ?></label>
						</th>
						<td>
							<select name="wc_team_payroll_settings[refund_commission_type]">
								<option value="none" <?php selected( isset( $settings['refund_commission_type'] ) ? $settings['refund_commission_type'] : 'none', 'none' ); ?>><?php esc_html_e( 'No Commission', 'wc-team-payroll' ); ?></option>
								<option value="percentage" <?php selected( isset( $settings['refund_commission_type'] ) ? $settings['refund_commission_type'] : 'none', 'percentage' ); ?>><?php esc_html_e( 'Percentage of Order', 'wc-team-payroll' ); ?></option>
								<option value="flat" <?php selected( isset( $settings['refund_commission_type'] ) ? $settings['refund_commission_type'] : 'none', 'flat' ); ?>><?php esc_html_e( 'Flat Amount', 'wc-team-payroll' ); ?></option>
							</select>
							<p class="description"><?php esc_html_e( 'Commission type for refunded orders', 'wc-team-payroll' ); ?></p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="refund_commission_value"><?php esc_html_e( 'Refunded Order Commission Value', 'wc-team-payroll' ); ?></label>
						</th>
						<td>
							<input type="number" id="refund_commission_value" name="wc_team_payroll_settings[refund_commission_value]" value="<?php echo esc_attr( isset( $settings['refund_commission_value'] ) ? $settings['refund_commission_value'] : 0 ); ?>" step="0.01" min="0" />
							<p class="description"><?php esc_html_e( 'Percentage (%) or flat amount for refunded orders', 'wc-team-payroll' ); ?></p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="enable_breakdown"><?php esc_html_e( 'Enable Breakdown Table', 'wc-team-payroll' ); ?></label>
						</th>
						<td>
							<input type="checkbox" id="enable_breakdown" name="wc_team_payroll_settings[enable_breakdown]" value="1" <?php checked( $enable_breakdown, 1 ); ?> />
							<p class="description"><?php esc_html_e( 'Show commission breakdown table on order details', 'wc-team-payroll' ); ?></p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="enable_myaccount"><?php esc_html_e( 'Enable My Account Integration', 'wc-team-payroll' ); ?></label>
						</th>
						<td>
							<input type="checkbox" id="enable_myaccount" name="wc_team_payroll_settings[enable_myaccount]" value="1" <?php checked( $enable_myaccount, 1 ); ?> />
							<p class="description"><?php esc_html_e( 'Add earnings tabs to My Account page', 'wc-team-payroll' ); ?></p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="enable_shortcodes"><?php esc_html_e( 'Enable Shortcodes', 'wc-team-payroll' ); ?></label>
						</th>
						<td>
							<input type="checkbox" id="enable_shortcodes" name="wc_team_payroll_settings[enable_shortcodes]" value="1" <?php checked( $enable_shortcodes, 1 ); ?> />
							<p class="description"><?php esc_html_e( 'Enable shortcode system for displaying earnings', 'wc-team-payroll' ); ?></p>
						</td>
					</tr>
				</table>

				<!-- Checkout Field Mapping -->
				<h2><?php esc_html_e( 'Checkout Field Mapping', 'wc-team-payroll' ); ?></h2>
				<p><?php esc_html_e( 'Configure your custom checkout field names (created via ThemeHigh Checkout Field Editor)', 'wc-team-payroll' ); ?></p>
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="agent_field_name"><?php esc_html_e( 'Agent Dropdown Field Name', 'wc-team-payroll' ); ?></label>
						</th>
						<td>
							<input type="text" id="agent_field_name" name="wc_team_payroll_checkout_fields[agent_field_name]" value="<?php echo esc_attr( $agent_field_name ); ?>" />
							<p class="description"><?php esc_html_e( 'The POST field name from your ThemeHigh checkout field (e.g., order_other_agent_or_not)', 'wc-team-payroll' ); ?></p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="agent_user_roles"><?php esc_html_e( 'Agent User Roles', 'wc-team-payroll' ); ?></label>
						</th>
						<td>
							<?php
							global $wp_roles;
							$all_roles = $wp_roles->roles;
							?>
							<select id="agent_user_roles" name="wc_team_payroll_checkout_fields[agent_user_roles][]" multiple="multiple" style="width: 100%; min-height: 100px;">
								<?php foreach ( $all_roles as $role_key => $role_data ) : ?>
									<option value="<?php echo esc_attr( $role_key ); ?>" <?php echo in_array( $role_key, $agent_user_roles ) ? 'selected' : ''; ?>>
										<?php echo esc_html( $role_data['name'] ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<p class="description"><?php esc_html_e( 'Select which user roles can be shown as agents in the checkout dropdown', 'wc-team-payroll' ); ?></p>
						</td>
					</tr>
				</table>

				<!-- ACF Field Mapping -->
				<h2><?php esc_html_e( 'ACF Field Mapping', 'wc-team-payroll' ); ?></h2>
				<p><?php esc_html_e( 'Configure your custom ACF field names (you create these fields in ACF)', 'wc-team-payroll' ); ?></p>
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="commission_field_name"><?php esc_html_e( 'Product Commission Field Name', 'wc-team-payroll' ); ?></label>
						</th>
						<td>
							<input type="text" id="commission_field_name" name="wc_team_payroll_acf_fields[commission_field_name]" value="<?php echo esc_attr( $commission_field_name ); ?>" />
							<p class="description"><?php esc_html_e( 'The ACF field name on products for commission rate (e.g., team_commission). You must create this field in ACF.', 'wc-team-payroll' ); ?></p>
						</td>
					</tr>
				</table>

				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
