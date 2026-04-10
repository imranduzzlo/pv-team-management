<?php
/**
 * Settings Page with Tabs
 */

class WC_Team_Payroll_Settings {

	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized', 'wc-team-payroll' ) );
		}

		if ( isset( $_POST['submit'] ) && check_admin_referer( 'wc_team_payroll_settings_nonce' ) ) {
			$this->save_settings();
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved successfully!', 'wc-team-payroll' ) . '</p></div>';
		}

		$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'general';
		$settings = get_option( 'wc_team_payroll_settings', array() );
		$checkout_fields = get_option( 'wc_team_payroll_checkout_fields', array() );
		$acf_fields = get_option( 'wc_team_payroll_acf_fields', array() );
		$employee_roles = get_option( 'wc_tp_employee_roles', array( 'shop_employee' ) );
		$user_id_prefix = get_option( 'wc_tp_user_id_prefix', 'PVVB-EMID' );

		$this->render_tabs( $current_tab, $settings, $checkout_fields, $acf_fields, $employee_roles, $user_id_prefix );
	}

	private function render_tabs( $current_tab, $settings, $checkout_fields, $acf_fields, $employee_roles, $user_id_prefix ) {
		?>
		<div class="wrap wc-tp-settings-wrap">
			<h1><?php esc_html_e( 'WooCommerce Team Payroll Settings', 'wc-team-payroll' ); ?></h1>

			<nav class="nav-tab-wrapper">
				<a href="?page=wc-team-payroll-settings&tab=general" class="nav-tab <?php echo $current_tab === 'general' ? 'nav-tab-active' : ''; ?>">General</a>
				<a href="?page=wc-team-payroll-settings&tab=commission" class="nav-tab <?php echo $current_tab === 'commission' ? 'nav-tab-active' : ''; ?>">Commission</a>
				<a href="?page=wc-team-payroll-settings&tab=roles" class="nav-tab <?php echo $current_tab === 'roles' ? 'nav-tab-active' : ''; ?>">Employee Roles</a>
				<a href="?page=wc-team-payroll-settings&tab=checkout" class="nav-tab <?php echo $current_tab === 'checkout' ? 'nav-tab-active' : ''; ?>">Checkout</a>
				<a href="?page=wc-team-payroll-settings&tab=advanced" class="nav-tab <?php echo $current_tab === 'advanced' ? 'nav-tab-active' : ''; ?>">Advanced</a>
			</nav>

			<form method="post" action="">
				<?php wp_nonce_field( 'wc_team_payroll_settings_nonce' ); ?>

				<?php if ( $current_tab === 'general' ) : ?>
					<h2>General Settings</h2>
					<table class="form-table">
						<tr>
							<th><label for="wc_tp_user_id_prefix">Employee ID Prefix</label></th>
							<td>
								<input type="text" id="wc_tp_user_id_prefix" name="wc_tp_user_id_prefix" value="<?php echo esc_attr( $user_id_prefix ); ?>" />
								<p class="description">Prefix for auto-generated employee IDs (e.g., PVVB-EMID)</p>
							</td>
						</tr>
						<tr>
							<th><label for="enable_breakdown">Enable Breakdown Table</label></th>
							<td>
								<input type="checkbox" id="enable_breakdown" name="wc_team_payroll_settings[enable_breakdown]" value="1" <?php checked( isset( $settings['enable_breakdown'] ) ? $settings['enable_breakdown'] : 1, 1 ); ?> />
								<p class="description">Show commission breakdown table on order details</p>
							</td>
						</tr>
						<tr>
							<th><label for="enable_myaccount">Enable My Account Integration</label></th>
							<td>
								<input type="checkbox" id="enable_myaccount" name="wc_team_payroll_settings[enable_myaccount]" value="1" <?php checked( isset( $settings['enable_myaccount'] ) ? $settings['enable_myaccount'] : 1, 1 ); ?> />
								<p class="description">Add earnings tabs to My Account page</p>
							</td>
						</tr>
						<tr>
							<th><label for="enable_shortcodes">Enable Shortcodes</label></th>
							<td>
								<input type="checkbox" id="enable_shortcodes" name="wc_team_payroll_settings[enable_shortcodes]" value="1" <?php checked( isset( $settings['enable_shortcodes'] ) ? $settings['enable_shortcodes'] : 1, 1 ); ?> />
								<p class="description">Enable shortcode system for displaying earnings</p>
							</td>
						</tr>
					</table>
				<?php endif; ?>

				<?php if ( $current_tab === 'commission' ) : ?>
					<h2>Commission Settings</h2>
					<table class="form-table">
						<tr>
							<th><label for="agent_percentage">Agent Commission %</label></th>
							<td>
								<input type="number" id="agent_percentage" name="wc_team_payroll_settings[agent_percentage]" value="<?php echo esc_attr( isset( $settings['agent_percentage'] ) ? $settings['agent_percentage'] : 70 ); ?>" step="0.01" min="0" max="100" />
								<p class="description">Percentage of commission for agent</p>
							</td>
						</tr>
						<tr>
							<th><label for="processor_percentage">Processor Commission %</label></th>
							<td>
								<input type="number" id="processor_percentage" name="wc_team_payroll_settings[processor_percentage]" value="<?php echo esc_attr( isset( $settings['processor_percentage'] ) ? $settings['processor_percentage'] : 30 ); ?>" step="0.01" min="0" max="100" />
								<p class="description">Percentage of commission for processor</p>
							</td>
						</tr>
						<tr>
							<th><label>Refunded Order Commission</label></th>
							<td>
								<select name="wc_team_payroll_settings[refund_commission_type]">
									<option value="none" <?php selected( isset( $settings['refund_commission_type'] ) ? $settings['refund_commission_type'] : 'none', 'none' ); ?>>No Commission</option>
									<option value="percentage" <?php selected( isset( $settings['refund_commission_type'] ) ? $settings['refund_commission_type'] : 'none', 'percentage' ); ?>>Percentage of Order</option>
									<option value="flat" <?php selected( isset( $settings['refund_commission_type'] ) ? $settings['refund_commission_type'] : 'none', 'flat' ); ?>>Flat Amount</option>
								</select>
								<p class="description">Commission type for refunded orders</p>
							</td>
						</tr>
						<tr>
							<th><label for="refund_commission_value">Refunded Order Commission Value</label></th>
							<td>
								<input type="number" id="refund_commission_value" name="wc_team_payroll_settings[refund_commission_value]" value="<?php echo esc_attr( isset( $settings['refund_commission_value'] ) ? $settings['refund_commission_value'] : 0 ); ?>" step="0.01" min="0" />
								<p class="description">Percentage (%) or flat amount for refunded orders</p>
							</td>
						</tr>
					</table>
				<?php endif; ?>

				<?php if ( $current_tab === 'roles' ) : ?>
					<h2>Employee Roles Management</h2>
					<p>Manage which user roles are considered employees. Add new roles or customize existing ones.</p>
					<div class="wc-tp-roles-container" id="wc-tp-roles-container">
						<?php $this->render_roles_repeater( $employee_roles ); ?>
					</div>
					<button type="button" class="button button-secondary" id="wc-tp-add-role-btn">+ Add New Role</button>
				<?php endif; ?>

				<?php if ( $current_tab === 'checkout' ) : ?>
					<h2>Checkout Field Mapping</h2>
					<p>Configure your custom checkout field names (created via ThemeHigh Checkout Field Editor)</p>
					<table class="form-table">
						<tr>
							<th><label for="agent_field_name">Agent Dropdown Field Name</label></th>
							<td>
								<input type="text" id="agent_field_name" name="wc_team_payroll_checkout_fields[agent_field_name]" value="<?php echo esc_attr( isset( $checkout_fields['agent_field_name'] ) ? $checkout_fields['agent_field_name'] : 'order_other_agent_or_not' ); ?>" />
								<p class="description">The POST field name from your ThemeHigh checkout field</p>
							</td>
						</tr>
						<tr>
							<th><label for="agent_user_roles">Agent User Roles</label></th>
							<td>
								<?php
								global $wp_roles;
								$all_roles = $wp_roles->roles;
								$agent_user_roles = isset( $checkout_fields['agent_user_roles'] ) ? $checkout_fields['agent_user_roles'] : array( 'shop_employee', 'shop_manager', 'administrator' );
								?>
								<select id="agent_user_roles" name="wc_team_payroll_checkout_fields[agent_user_roles][]" multiple="multiple" style="width: 100%; min-height: 100px;">
									<?php foreach ( $all_roles as $role_key => $role_data ) : ?>
										<option value="<?php echo esc_attr( $role_key ); ?>" <?php echo in_array( $role_key, $agent_user_roles ) ? 'selected' : ''; ?>>
											<?php echo esc_html( $role_data['name'] ); ?>
										</option>
									<?php endforeach; ?>
								</select>
								<p class="description">Select which user roles can be shown as agents in the checkout dropdown</p>
							</td>
						</tr>
					</table>
				<?php endif; ?>

				<?php if ( $current_tab === 'advanced' ) : ?>
					<h2>Advanced Settings</h2>
					<table class="form-table">
						<tr>
							<th><label for="commission_field_name">Product Commission Field Name</label></th>
							<td>
								<input type="text" id="commission_field_name" name="wc_team_payroll_acf_fields[commission_field_name]" value="<?php echo esc_attr( isset( $acf_fields['commission_field_name'] ) ? $acf_fields['commission_field_name'] : 'team_commission' ); ?>" />
								<p class="description">The field name on products for commission rate (e.g., team_commission)</p>
							</td>
						</tr>
					</table>
				<?php endif; ?>

				<?php submit_button(); ?>
			</form>
		</div>

		<style>
			.wc-tp-roles-container { margin: 20px 0; }
			.wc-tp-role-item {
				background: #f9f9f9;
				border: 1px solid #ddd;
				border-radius: 4px;
				padding: 15px;
				margin-bottom: 15px;
				position: relative;
			}
			.wc-tp-role-item.wc-tp-default-role {
				background: #fff9e6;
				border-color: #ffc107;
			}
			.wc-tp-role-item-header {
				display: flex;
				justify-content: space-between;
				align-items: center;
				margin-bottom: 10px;
			}
			.wc-tp-role-name {
				font-weight: bold;
				color: #333;
				flex: 1;
			}
			.wc-tp-role-badge {
				display: inline-block;
				background: #ffc107;
				color: #333;
				padding: 3px 8px;
				border-radius: 3px;
				font-size: 11px;
				font-weight: bold;
				margin-right: 10px;
			}
			.wc-tp-role-remove {
				background: #dc3545;
				color: white;
				border: none;
				padding: 5px 10px;
				border-radius: 3px;
				cursor: pointer;
				font-size: 12px;
			}
			.wc-tp-role-remove:hover {
				background: #c82333;
			}
			.wc-tp-role-remove:disabled {
				background: #ccc;
				cursor: not-allowed;
			}
			.wc-tp-role-warning {
				background: #fff3cd;
				border: 1px solid #ffc107;
				border-radius: 3px;
				padding: 10px;
				margin-bottom: 10px;
				font-size: 12px;
				color: #856404;
			}
		</style>

		<script>
			jQuery(document).ready(function($) {
				$('#wc-tp-add-role-btn').on('click', function() {
					const container = $('#wc-tp-roles-container');
					const html = `
						<div class="wc-tp-role-item">
							<div class="wc-tp-role-item-header">
								<input type="text" name="wc_tp_employee_roles[]" placeholder="Role name (e.g., shop_employee)" value="" style="flex: 1; padding: 6px; border: 1px solid #ddd; border-radius: 3px;" />
								<button type="button" class="wc-tp-role-remove" style="margin-left: 10px;">Remove</button>
							</div>
						</div>
					`;
					container.append(html);
				});

				$(document).on('click', '.wc-tp-role-remove', function(e) {
					e.preventDefault();
					if ($(this).prop('disabled')) return;
					$(this).closest('.wc-tp-role-item').remove();
				});
			});
		</script>
		<?php
	}

	private function render_roles_repeater( $employee_roles ) {
		global $wp_roles;
		$all_roles = $wp_roles->roles;
		$all_role_keys = array_keys( $all_roles );

		$detected_roles = array();
		foreach ( $all_role_keys as $role_key ) {
			if ( ! in_array( $role_key, $employee_roles ) ) {
				$detected_roles[] = $role_key;
			}
		}

		$all_employee_roles = array_unique( array_merge( $employee_roles, $detected_roles ) );

		foreach ( $all_employee_roles as $role ) :
			$role_data = isset( $all_roles[ $role ] ) ? $all_roles[ $role ] : array( 'name' => $role );
			$is_default = $role === 'shop_employee';
			$is_admin = $role === 'administrator';
			?>
			<div class="wc-tp-role-item <?php echo $is_default ? 'wc-tp-default-role' : ''; ?>">
				<?php if ( $is_admin ) : ?>
					<div class="wc-tp-role-warning">
						⚠️ Warning: Modifying administrator role permissions can affect site security. Proceed with caution.
					</div>
				<?php endif; ?>

				<div class="wc-tp-role-item-header">
					<div>
						<span class="wc-tp-role-name"><?php echo esc_html( $role_data['name'] ); ?></span>
						<?php if ( $is_default ) : ?>
							<span class="wc-tp-role-badge">Default</span>
						<?php endif; ?>
					</div>
					<button type="button" class="wc-tp-role-remove" data-role="<?php echo esc_attr( $role ); ?>" <?php echo $is_default ? 'disabled' : ''; ?>>
						Remove
					</button>
				</div>

				<input type="hidden" name="wc_tp_employee_roles[]" value="<?php echo esc_attr( $role ); ?>" />
			</div>
			<?php
		endforeach;
	}

	private function save_settings() {
		$settings = isset( $_POST['wc_team_payroll_settings'] ) ? array_map( 'sanitize_text_field', $_POST['wc_team_payroll_settings'] ) : array();
		$checkout_fields = isset( $_POST['wc_team_payroll_checkout_fields'] ) ? array_map( 'sanitize_text_field', $_POST['wc_team_payroll_checkout_fields'] ) : array();
		$acf_fields = isset( $_POST['wc_team_payroll_acf_fields'] ) ? array_map( 'sanitize_text_field', $_POST['wc_team_payroll_acf_fields'] ) : array();

		update_option( 'wc_team_payroll_settings', $settings );
		update_option( 'wc_team_payroll_checkout_fields', $checkout_fields );
		update_option( 'wc_team_payroll_acf_fields', $acf_fields );

		if ( isset( $_POST['wc_tp_user_id_prefix'] ) ) {
			$prefix = sanitize_text_field( $_POST['wc_tp_user_id_prefix'] );
			update_option( 'wc_tp_user_id_prefix', $prefix );
		}

		if ( isset( $_POST['wc_tp_employee_roles'] ) ) {
			$roles = array_map( 'sanitize_text_field', $_POST['wc_tp_employee_roles'] );
			update_option( 'wc_tp_employee_roles', $roles );
		}
	}
}
