<?php
/**
 * Settings Page with Tabs
 */

class WC_Team_Payroll_Settings {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue assets for settings page
	 */
	public function enqueue_assets() {
		$screen = get_current_screen();
		if ( ! $screen || 'woocommerce_page_wc-team-payroll-settings' !== $screen->id ) {
			return;
		}

		// Enqueue common CSS
		wp_enqueue_style( 'wc-tp-common', plugin_dir_url( __FILE__ ) . '../../assets/css/common.css', array(), '5.6.2' );

		// Enqueue settings-specific CSS
		wp_enqueue_style( 'wc-tp-settings', plugin_dir_url( __FILE__ ) . '../../assets/css/settings.css', array( 'wc-tp-common' ), '5.6.2' );

		// Enqueue common JS
		wp_enqueue_script( 'wc-tp-common', plugin_dir_url( __FILE__ ) . '../../assets/js/common.js', array( 'jquery' ), '5.6.2', true );

		// Enqueue settings-specific JS
		wp_enqueue_script( 'wc-tp-settings', plugin_dir_url( __FILE__ ) . '../../assets/js/settings.js', array( 'jquery', 'wc-tp-common' ), '5.6.2', true );
	}

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
			$is_admin = $role === 'administrator';
			$role_obj = get_role( $role );
			$capabilities = $role_obj ? $role_obj->capabilities : array();
			?>
			<div class="wc-tp-role-item">
				<?php if ( $is_admin ) : ?>
					<div class="wc-tp-role-warning">
						⚠️ Warning: Modifying administrator role permissions can affect site security. Proceed with caution.
					</div>
				<?php endif; ?>

				<div class="wc-tp-role-item-header">
					<div style="flex: 1;">
						<input type="text" class="wc-tp-role-name-input" name="wc_tp_employee_roles[<?php echo esc_attr( $role ); ?>][name]" value="<?php echo esc_attr( $role_data['name'] ); ?>" style="font-weight: bold; padding: 6px; border: 1px solid #ddd; border-radius: 3px; width: 100%; max-width: 300px;" />
					</div>
					<button type="button" class="wc-tp-role-remove" data-role="<?php echo esc_attr( $role ); ?>">
						Remove
					</button>
				</div>

				<div class="wc-tp-role-capabilities" style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #eee;">
					<label style="display: block; font-weight: bold; margin-bottom: 8px; font-size: 12px;">Capabilities:</label>
					<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px;">
						<?php
						$all_capabilities = array(
							'read' => 'Read',
							'edit_posts' => 'Edit Posts',
							'delete_posts' => 'Delete Posts',
							'publish_posts' => 'Publish Posts',
							'manage_options' => 'Manage Options',
							'manage_woocommerce' => 'Manage WooCommerce',
						);

						foreach ( $all_capabilities as $cap_key => $cap_label ) :
							$is_checked = isset( $capabilities[ $cap_key ] ) && $capabilities[ $cap_key ];
							?>
							<label style="display: flex; align-items: center; font-size: 12px;">
								<input type="checkbox" name="wc_tp_employee_roles[<?php echo esc_attr( $role ); ?>][capabilities][<?php echo esc_attr( $cap_key ); ?>]" value="1" <?php checked( $is_checked, true ); ?> />
								<span style="margin-left: 5px;"><?php echo esc_html( $cap_label ); ?></span>
							</label>
							<?php
						endforeach;
						?>
					</div>
				</div>

				<input type="hidden" name="wc_tp_employee_roles[<?php echo esc_attr( $role ); ?>][role_key]" value="<?php echo esc_attr( $role ); ?>" />
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

		// Save employee roles with capabilities
		if ( isset( $_POST['wc_tp_employee_roles'] ) && is_array( $_POST['wc_tp_employee_roles'] ) ) {
			$employee_roles = array();
			
			foreach ( $_POST['wc_tp_employee_roles'] as $role_key => $role_data ) {
				$role_key = sanitize_text_field( $role_key );
				$role_name = isset( $role_data['name'] ) ? sanitize_text_field( $role_data['name'] ) : $role_key;
				$capabilities = isset( $role_data['capabilities'] ) ? array_keys( $role_data['capabilities'] ) : array();

				$employee_roles[] = $role_key;

				// Update role capabilities
				$role_obj = get_role( $role_key );
				if ( $role_obj ) {
					// Remove all capabilities first
					$all_capabilities = array( 'read', 'edit_posts', 'delete_posts', 'publish_posts', 'manage_options', 'manage_woocommerce' );
					foreach ( $all_capabilities as $cap ) {
						$role_obj->remove_cap( $cap );
					}

					// Add selected capabilities
					foreach ( $capabilities as $cap ) {
						$cap = sanitize_text_field( $cap );
						$role_obj->add_cap( $cap );
					}

					// Update role name
					if ( $role_name !== $role_key ) {
						global $wp_roles;
						$wp_roles->roles[ $role_key ]['name'] = $role_name;
						update_option( $wp_roles->role_key, $wp_roles->roles );
					}
				}
			}

			update_option( 'wc_tp_employee_roles', $employee_roles );
		}
	}
}
