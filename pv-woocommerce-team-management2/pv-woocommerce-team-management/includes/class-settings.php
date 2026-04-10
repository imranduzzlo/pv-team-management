<?php
/**
 * Settings Page
 */

class WC_Team_Payroll_Settings {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	public function add_menu() {
		add_submenu_page(
			'wc-team-payroll',
			__( 'Team Payroll Settings', 'wc-team-payroll' ),
			__( 'Settings', 'wc-team-payroll' ),
			'manage_woocommerce',
			'wc-team-payroll-settings',
			array( $this, 'render_settings_page' )
		);
	}

	public function register_settings() {
		register_setting( 'wc_team_payroll_settings_group', 'wc_team_payroll_settings' );
		register_setting( 'wc_team_payroll_settings_group', 'wc_team_payroll_checkout_fields' );
		register_setting( 'wc_team_payroll_settings_group', 'wc_team_payroll_acf_fields' );
	}

	public function render_settings_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Unauthorized', 'wc-team-payroll' ) );
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

			<form method="post" action="options.php">
				<?php settings_fields( 'wc_team_payroll_settings_group' ); ?>

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
				<h2><?php esc_html_e( 'Extra Earnings Rules', 'wc-team-payroll' ); ?></h2>
				<p><?php esc_html_e( 'Define additional earnings rules with conditions (bonuses, delivery fees, etc.)', 'wc-team-payroll' ); ?></p>

				<table class="widefat striped" id="extra-earnings-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Label', 'wc-team-payroll' ); ?></th>
							<th><?php esc_html_e( 'Type', 'wc-team-payroll' ); ?></th>
							<th><?php esc_html_e( 'Value', 'wc-team-payroll' ); ?></th>
							<th><?php esc_html_e( 'Condition Type', 'wc-team-payroll' ); ?></th>
							<th><?php esc_html_e( 'Condition Value', 'wc-team-payroll' ); ?></th>
							<th><?php esc_html_e( 'End Date', 'wc-team-payroll' ); ?></th>
							<th><?php esc_html_e( 'Active', 'wc-team-payroll' ); ?></th>
							<th><?php esc_html_e( 'Action', 'wc-team-payroll' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $extra_earnings_rules as $index => $rule ) : ?>
							<tr class="extra-earnings-row">
								<td>
									<input type="text" name="wc_team_payroll_settings[extra_earnings_rules][<?php echo esc_attr( $index ); ?>][label]" value="<?php echo esc_attr( isset( $rule['label'] ) ? $rule['label'] : '' ); ?>" placeholder="Rule name" />
								</td>
								<td>
									<select name="wc_team_payroll_settings[extra_earnings_rules][<?php echo esc_attr( $index ); ?>][type]">
										<option value="fixed" <?php selected( isset( $rule['type'] ) ? $rule['type'] : '', 'fixed' ); ?>><?php esc_html_e( 'Fixed Amount', 'wc-team-payroll' ); ?></option>
										<option value="percentage_order" <?php selected( isset( $rule['type'] ) ? $rule['type'] : '', 'percentage_order' ); ?>><?php esc_html_e( 'Percentage of Order', 'wc-team-payroll' ); ?></option>
										<option value="percentage_commission" <?php selected( isset( $rule['type'] ) ? $rule['type'] : '', 'percentage_commission' ); ?>><?php esc_html_e( 'Percentage of Commission', 'wc-team-payroll' ); ?></option>
									</select>
								</td>
								<td>
									<input type="number" name="wc_team_payroll_settings[extra_earnings_rules][<?php echo esc_attr( $index ); ?>][value]" value="<?php echo esc_attr( isset( $rule['value'] ) ? $rule['value'] : '' ); ?>" step="0.01" placeholder="Amount or %" />
								</td>
								<td>
									<select name="wc_team_payroll_settings[extra_earnings_rules][<?php echo esc_attr( $index ); ?>][condition_type]" class="condition-type-select">
										<option value="none" <?php selected( isset( $rule['condition_type'] ) ? $rule['condition_type'] : '', 'none' ); ?>><?php esc_html_e( 'No Condition', 'wc-team-payroll' ); ?></option>
										<option value="order_total" <?php selected( isset( $rule['condition_type'] ) ? $rule['condition_type'] : '', 'order_total' ); ?>><?php esc_html_e( 'Order Total >', 'wc-team-payroll' ); ?></option>
										<option value="product_based" <?php selected( isset( $rule['condition_type'] ) ? $rule['condition_type'] : '', 'product_based' ); ?>><?php esc_html_e( 'Specific Products', 'wc-team-payroll' ); ?></option>
										<option value="category_based" <?php selected( isset( $rule['condition_type'] ) ? $rule['condition_type'] : '', 'category_based' ); ?>><?php esc_html_e( 'Product Categories', 'wc-team-payroll' ); ?></option>
										<option value="agent_based" <?php selected( isset( $rule['condition_type'] ) ? $rule['condition_type'] : '', 'agent_based' ); ?>><?php esc_html_e( 'Specific Agent', 'wc-team-payroll' ); ?></option>
									</select>
								</td>
								<td>
									<input type="text" name="wc_team_payroll_settings[extra_earnings_rules][<?php echo esc_attr( $index ); ?>][condition_value]" value="<?php echo esc_attr( isset( $rule['condition_value'] ) ? $rule['condition_value'] : '' ); ?>" placeholder="Value or IDs (comma-separated)" />
								</td>
								<td>
									<input type="date" name="wc_team_payroll_settings[extra_earnings_rules][<?php echo esc_attr( $index ); ?>][end_date]" value="<?php echo esc_attr( isset( $rule['end_date'] ) ? $rule['end_date'] : '' ); ?>" />
								</td>
								<td>
									<input type="checkbox" name="wc_team_payroll_settings[extra_earnings_rules][<?php echo esc_attr( $index ); ?>][active]" value="1" <?php checked( isset( $rule['active'] ) ? $rule['active'] : 1, 1 ); ?> />
								</td>
								<td>
									<button type="button" class="button remove-rule"><?php esc_html_e( 'Remove', 'wc-team-payroll' ); ?></button>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

				<button type="button" class="button" id="add-rule"><?php esc_html_e( 'Add Rule', 'wc-team-payroll' ); ?></button>

				<?php submit_button(); ?>
			</form>
		</div>

		<script>
			jQuery(document).ready(function($) {
				$('#add-rule').on('click', function() {
					const index = $('#extra-earnings-table tbody tr').length;
					const row = `
						<tr class="extra-earnings-row">
							<td><input type="text" name="wc_team_payroll_settings[extra_earnings_rules][${index}][label]" placeholder="Rule name" /></td>
							<td>
								<select name="wc_team_payroll_settings[extra_earnings_rules][${index}][type]">
									<option value="fixed"><?php esc_html_e( 'Fixed Amount', 'wc-team-payroll' ); ?></option>
									<option value="percentage_order"><?php esc_html_e( 'Percentage of Order', 'wc-team-payroll' ); ?></option>
									<option value="percentage_commission"><?php esc_html_e( 'Percentage of Commission', 'wc-team-payroll' ); ?></option>
								</select>
							</td>
							<td><input type="number" name="wc_team_payroll_settings[extra_earnings_rules][${index}][value]" step="0.01" placeholder="Amount or %" /></td>
							<td>
								<select name="wc_team_payroll_settings[extra_earnings_rules][${index}][condition_type]" class="condition-type-select">
									<option value="none"><?php esc_html_e( 'No Condition', 'wc-team-payroll' ); ?></option>
									<option value="order_total"><?php esc_html_e( 'Order Total >', 'wc-team-payroll' ); ?></option>
									<option value="product_based"><?php esc_html_e( 'Specific Products', 'wc-team-payroll' ); ?></option>
									<option value="category_based"><?php esc_html_e( 'Product Categories', 'wc-team-payroll' ); ?></option>
									<option value="agent_based"><?php esc_html_e( 'Specific Agent', 'wc-team-payroll' ); ?></option>
								</select>
							</td>
							<td><input type="text" name="wc_team_payroll_settings[extra_earnings_rules][${index}][condition_value]" placeholder="Value or IDs (comma-separated)" /></td>
							<td><input type="date" name="wc_team_payroll_settings[extra_earnings_rules][${index}][end_date]" /></td>
							<td><input type="checkbox" name="wc_team_payroll_settings[extra_earnings_rules][${index}][active]" value="1" checked /></td>
							<td><button type="button" class="button remove-rule"><?php esc_html_e( 'Remove', 'wc-team-payroll' ); ?></button></td>
						</tr>
					`;
					$('#extra-earnings-table tbody').append(row);
				});

				$(document).on('click', '.remove-rule', function(e) {
					e.preventDefault();
					$(this).closest('tr').remove();
				});
			});
		</script>
		<?php
	}
}
