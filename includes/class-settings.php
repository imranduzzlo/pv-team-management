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
		// Get styling settings
		$styling_settings = get_option( 'wc_team_payroll_styling', array() );
		?>
		<div class="wrap wc-tp-settings-wrap">
			<h1><?php esc_html_e( 'WooCommerce Team Payroll Settings', 'wc-team-payroll' ); ?></h1>

			<nav class="nav-tab-wrapper">
				<a href="?page=wc-team-payroll-settings&tab=general" class="nav-tab <?php echo $current_tab === 'general' ? 'nav-tab-active' : ''; ?>">General</a>
				<a href="?page=wc-team-payroll-settings&tab=commission" class="nav-tab <?php echo $current_tab === 'commission' ? 'nav-tab-active' : ''; ?>">Commission</a>
				<a href="?page=wc-team-payroll-settings&tab=styling" class="nav-tab <?php echo $current_tab === 'styling' ? 'nav-tab-active' : ''; ?>">Frontend Styling</a>
				<a href="?page=wc-team-payroll-settings&tab=roles" class="nav-tab <?php echo $current_tab === 'roles' ? 'nav-tab-active' : ''; ?>">Employee Roles</a>
				<a href="?page=wc-team-payroll-settings&tab=checkout" class="nav-tab <?php echo $current_tab === 'checkout' ? 'nav-tab-active' : ''; ?>">Checkout</a>
				<a href="?page=wc-team-payroll-settings&tab=advanced" class="nav-tab <?php echo $current_tab === 'advanced' ? 'nav-tab-active' : ''; ?>">Advanced</a>
				<a href="?page=wc-team-payroll-settings&tab=debug" class="nav-tab <?php echo $current_tab === 'debug' ? 'nav-tab-active' : ''; ?>">Debug</a>
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

					<h3>Contact Information for Inactive Employees</h3>
					<p>These contact details will be shown to inactive employees when they try to login.</p>
					<table class="form-table">
						<tr>
							<th><label for="contact_whatsapp">WhatsApp Number</label></th>
							<td>
								<input type="text" id="contact_whatsapp" name="contact_whatsapp" value="<?php echo esc_attr( get_option( 'wc_team_payroll_contact_whatsapp', '' ) ); ?>" placeholder="1234567890" />
								<p class="description">WhatsApp number (without + or country code, e.g., 1234567890)</p>
							</td>
						</tr>
						<tr>
							<th><label for="contact_email">Contact Email</label></th>
							<td>
								<input type="email" id="contact_email" name="contact_email" value="<?php echo esc_attr( get_option( 'wc_team_payroll_contact_email', '' ) ); ?>" placeholder="support@example.com" />
								<p class="description">Email address for employee support</p>
							</td>
						</tr>
						<tr>
							<th><label for="contact_telegram">Telegram Username</label></th>
							<td>
								<input type="text" id="contact_telegram" name="contact_telegram" value="<?php echo esc_attr( get_option( 'wc_team_payroll_contact_telegram', '' ) ); ?>" placeholder="username" />
								<p class="description">Telegram username (without @, e.g., username)</p>
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

				<?php if ( $current_tab === 'styling' ) : ?>
					<h2>Frontend Styling Settings</h2>
					<p>Customize the appearance of My Account pages and frontend elements. These settings will override the default styling.</p>
					
					<h3>Color Scheme</h3>
					<table class="form-table">
						<tr>
							<th><label for="primary_color">Primary Color</label></th>
							<td>
								<input type="color" id="primary_color" name="wc_team_payroll_styling[primary_color]" value="<?php echo esc_attr( isset( $styling_settings['primary_color'] ) ? $styling_settings['primary_color'] : '#0073aa' ); ?>" />
								<p class="description">Main brand color used for buttons, links, and accents</p>
							</td>
						</tr>
						<tr>
							<th><label for="secondary_color">Secondary Color</label></th>
							<td>
								<input type="color" id="secondary_color" name="wc_team_payroll_styling[secondary_color]" value="<?php echo esc_attr( isset( $styling_settings['secondary_color'] ) ? $styling_settings['secondary_color'] : '#28a745' ); ?>" />
								<p class="description">Secondary color for success states and positive amounts</p>
							</td>
						</tr>
						<tr>
							<th><label for="heading_color">Heading Color</label></th>
							<td>
								<input type="color" id="heading_color" name="wc_team_payroll_styling[heading_color]" value="<?php echo esc_attr( isset( $styling_settings['heading_color'] ) ? $styling_settings['heading_color'] : '#333333' ); ?>" />
								<p class="description">Color for headings (h1, h2, h3, etc.)</p>
							</td>
						</tr>
						<tr>
							<th><label for="text_color">Text Color</label></th>
							<td>
								<input type="color" id="text_color" name="wc_team_payroll_styling[text_color]" value="<?php echo esc_attr( isset( $styling_settings['text_color'] ) ? $styling_settings['text_color'] : '#495057' ); ?>" />
								<p class="description">Main text color for paragraphs and content</p>
							</td>
						</tr>
						<tr>
							<th><label for="link_color">Link Color</label></th>
							<td>
								<input type="color" id="link_color" name="wc_team_payroll_styling[link_color]" value="<?php echo esc_attr( isset( $styling_settings['link_color'] ) ? $styling_settings['link_color'] : '#0073aa' ); ?>" />
								<p class="description">Color for links in normal state</p>
							</td>
						</tr>
						<tr>
							<th><label for="link_hover_color">Link Hover Color</label></th>
							<td>
								<input type="color" id="link_hover_color" name="wc_team_payroll_styling[link_hover_color]" value="<?php echo esc_attr( isset( $styling_settings['link_hover_color'] ) ? $styling_settings['link_hover_color'] : '#005a87' ); ?>" />
								<p class="description">Color for links when hovered</p>
							</td>
						</tr>
					</table>

					<h3>Background Colors</h3>
					<table class="form-table">
						<tr>
							<th><label for="background_color">Main Background</label></th>
							<td>
								<input type="color" id="background_color" name="wc_team_payroll_styling[background_color]" value="<?php echo esc_attr( isset( $styling_settings['background_color'] ) ? $styling_settings['background_color'] : '#ffffff' ); ?>" />
								<p class="description">Background color for main content areas</p>
							</td>
						</tr>
						<tr>
							<th><label for="header_background">Header Background</label></th>
							<td>
								<input type="color" id="header_background" name="wc_team_payroll_styling[header_background]" value="<?php echo esc_attr( isset( $styling_settings['header_background'] ) ? $styling_settings['header_background'] : '#f8f9fa' ); ?>" />
								<p class="description">Background color for employee header sections</p>
							</td>
						</tr>
						<tr>
							<th><label for="header_border_color">Header Border/Line Color</label></th>
							<td>
								<input type="color" id="header_border_color" name="wc_team_payroll_styling[header_border_color]" value="<?php echo esc_attr( isset( $styling_settings['header_border_color'] ) ? $styling_settings['header_border_color'] : '#0073aa' ); ?>" />
								<p class="description">Color for connecting lines and borders in employee header</p>
							</td>
						</tr>
						<tr>
							<th><label for="card_background">Card Background</label></th>
							<td>
								<input type="color" id="card_background" name="wc_team_payroll_styling[card_background]" value="<?php echo esc_attr( isset( $styling_settings['card_background'] ) ? $styling_settings['card_background'] : '#f8f9fa' ); ?>" />
								<p class="description">Background color for cards and info boxes</p>
							</td>
						</tr>
						<tr>
							<th><label for="border_color">Border Color</label></th>
							<td>
								<input type="color" id="border_color" name="wc_team_payroll_styling[border_color]" value="<?php echo esc_attr( isset( $styling_settings['border_color'] ) ? $styling_settings['border_color'] : '#e9ecef' ); ?>" />
								<p class="description">Color for borders and dividers</p>
							</td>
						</tr>
					</table>

					<h3>Table Styling</h3>
					<table class="form-table">
						<tr>
							<th><label for="table_header_background">Table Header Background</label></th>
							<td>
								<input type="color" id="table_header_background" name="wc_team_payroll_styling[table_header_background]" value="<?php echo esc_attr( isset( $styling_settings['table_header_background'] ) ? $styling_settings['table_header_background'] : '#f8f9fa' ); ?>" />
								<p class="description">Background color for table headers</p>
							</td>
						</tr>
						<tr>
							<th><label for="table_row_hover">Table Row Hover</label></th>
							<td>
								<input type="color" id="table_row_hover" name="wc_team_payroll_styling[table_row_hover]" value="<?php echo esc_attr( isset( $styling_settings['table_row_hover'] ) ? $styling_settings['table_row_hover'] : '#f5f5f5' ); ?>" />
								<p class="description">Background color for table rows on hover</p>
							</td>
						</tr>
						<tr>
							<th><label for="table_border_color">Table Border Color</label></th>
							<td>
								<input type="color" id="table_border_color" name="wc_team_payroll_styling[table_border_color]" value="<?php echo esc_attr( isset( $styling_settings['table_border_color'] ) ? $styling_settings['table_border_color'] : '#dee2e6' ); ?>" />
								<p class="description">Color for table borders and dividers</p>
							</td>
						</tr>
					</table>

					<h3>Typography</h3>
					<table class="form-table">
						<tr>
							<th><label for="font_family">Font Family</label></th>
							<td>
								<select id="font_family" name="wc_team_payroll_styling[font_family]">
									<?php
									$font_family = isset( $styling_settings['font_family'] ) ? $styling_settings['font_family'] : 'inherit';
									$font_options = array(
										'inherit' => 'Inherit from theme',
										'Arial, sans-serif' => 'Arial',
										'Helvetica, Arial, sans-serif' => 'Helvetica',
										'"Segoe UI", Tahoma, Geneva, Verdana, sans-serif' => 'Segoe UI',
										'"Roboto", sans-serif' => 'Roboto',
										'"Open Sans", sans-serif' => 'Open Sans',
										'"Lato", sans-serif' => 'Lato',
										'"Poppins", sans-serif' => 'Poppins',
										'Georgia, serif' => 'Georgia',
										'"Times New Roman", serif' => 'Times New Roman',
									);
									foreach ( $font_options as $value => $label ) {
										echo '<option value="' . esc_attr( $value ) . '"' . selected( $font_family, $value, false ) . '>' . esc_html( $label ) . '</option>';
									}
									?>
								</select>
								<p class="description">Font family for all text elements</p>
							</td>
						</tr>
						<tr>
							<th><label for="base_font_size">Base Font Size</label></th>
							<td>
								<input type="number" id="base_font_size" name="wc_team_payroll_styling[base_font_size]" value="<?php echo esc_attr( isset( $styling_settings['base_font_size'] ) ? $styling_settings['base_font_size'] : 14 ); ?>" min="10" max="24" step="1" />
								<span>px</span>
								<p class="description">Base font size for body text (10-24px)</p>
							</td>
						</tr>
						<tr>
							<th><label for="heading_font_size">Heading Font Size</label></th>
							<td>
								<input type="number" id="heading_font_size" name="wc_team_payroll_styling[heading_font_size]" value="<?php echo esc_attr( isset( $styling_settings['heading_font_size'] ) ? $styling_settings['heading_font_size'] : 24 ); ?>" min="16" max="48" step="1" />
								<span>px</span>
								<p class="description">Font size for main headings (16-48px)</p>
							</td>
						</tr>
					</table>

					<h3>Button Styling</h3>
					<table class="form-table">
						<tr>
							<th><label for="button_background">Button Background</label></th>
							<td>
								<input type="color" id="button_background" name="wc_team_payroll_styling[button_background]" value="<?php echo esc_attr( isset( $styling_settings['button_background'] ) ? $styling_settings['button_background'] : '#0073aa' ); ?>" />
								<p class="description">Background color for primary buttons</p>
							</td>
						</tr>
						<tr>
							<th><label for="button_text_color">Button Text Color</label></th>
							<td>
								<input type="color" id="button_text_color" name="wc_team_payroll_styling[button_text_color]" value="<?php echo esc_attr( isset( $styling_settings['button_text_color'] ) ? $styling_settings['button_text_color'] : '#ffffff' ); ?>" />
								<p class="description">Text color for buttons</p>
							</td>
						</tr>
						<tr>
							<th><label for="button_hover_background">Button Hover Background</label></th>
							<td>
								<input type="color" id="button_hover_background" name="wc_team_payroll_styling[button_hover_background]" value="<?php echo esc_attr( isset( $styling_settings['button_hover_background'] ) ? $styling_settings['button_hover_background'] : '#005a87' ); ?>" />
								<p class="description">Background color for buttons when hovered</p>
							</td>
						</tr>
						<tr>
							<th><label for="button_border_radius">Button Border Radius</label></th>
							<td>
								<input type="number" id="button_border_radius" name="wc_team_payroll_styling[button_border_radius]" value="<?php echo esc_attr( isset( $styling_settings['button_border_radius'] ) ? $styling_settings['button_border_radius'] : 4 ); ?>" min="0" max="20" step="1" />
								<span>px</span>
								<p class="description">Border radius for buttons (0-20px)</p>
							</td>
						</tr>
					</table>

					<h3>Layout Settings</h3>
					<table class="form-table">
						<tr>
							<th><label for="card_border_radius">Card Border Radius</label></th>
							<td>
								<input type="number" id="card_border_radius" name="wc_team_payroll_styling[card_border_radius]" value="<?php echo esc_attr( isset( $styling_settings['card_border_radius'] ) ? $styling_settings['card_border_radius'] : 8 ); ?>" min="0" max="20" step="1" />
								<span>px</span>
								<p class="description">Border radius for cards and info boxes (0-20px)</p>
							</td>
						</tr>
						<tr>
							<th><label for="card_shadow">Card Shadow</label></th>
							<td>
								<select id="card_shadow" name="wc_team_payroll_styling[card_shadow]">
									<?php
									$card_shadow = isset( $styling_settings['card_shadow'] ) ? $styling_settings['card_shadow'] : 'medium';
									$shadow_options = array(
										'none' => 'No Shadow',
										'light' => 'Light Shadow',
										'medium' => 'Medium Shadow',
										'heavy' => 'Heavy Shadow',
									);
									foreach ( $shadow_options as $value => $label ) {
										echo '<option value="' . esc_attr( $value ) . '"' . selected( $card_shadow, $value, false ) . '>' . esc_html( $label ) . '</option>';
									}
									?>
								</select>
								<p class="description">Shadow depth for cards and elements</p>
							</td>
						</tr>
					</table>

					<h3>Preview</h3>
					<div style="background: <?php echo esc_attr( isset( $styling_settings['background_color'] ) ? $styling_settings['background_color'] : '#ffffff' ); ?>; padding: 20px; border: 1px solid <?php echo esc_attr( isset( $styling_settings['border_color'] ) ? $styling_settings['border_color'] : '#e9ecef' ); ?>; border-radius: <?php echo esc_attr( isset( $styling_settings['card_border_radius'] ) ? $styling_settings['card_border_radius'] : 8 ); ?>px; margin: 15px 0;">
						<h3 style="color: <?php echo esc_attr( isset( $styling_settings['heading_color'] ) ? $styling_settings['heading_color'] : '#333333' ); ?>; font-size: <?php echo esc_attr( isset( $styling_settings['heading_font_size'] ) ? $styling_settings['heading_font_size'] : 24 ); ?>px; font-family: <?php echo esc_attr( isset( $styling_settings['font_family'] ) && $styling_settings['font_family'] !== 'inherit' ? $styling_settings['font_family'] : 'inherit' ); ?>;">Sample Heading</h3>
						<p style="color: <?php echo esc_attr( isset( $styling_settings['text_color'] ) ? $styling_settings['text_color'] : '#495057' ); ?>; font-size: <?php echo esc_attr( isset( $styling_settings['base_font_size'] ) ? $styling_settings['base_font_size'] : 14 ); ?>px; font-family: <?php echo esc_attr( isset( $styling_settings['font_family'] ) && $styling_settings['font_family'] !== 'inherit' ? $styling_settings['font_family'] : 'inherit' ); ?>;">This is sample text content. <a href="#" style="color: <?php echo esc_attr( isset( $styling_settings['link_color'] ) ? $styling_settings['link_color'] : '#0073aa' ); ?>;">This is a sample link</a> within the text.</p>
						<div style="background: <?php echo esc_attr( isset( $styling_settings['card_background'] ) ? $styling_settings['card_background'] : '#f8f9fa' ); ?>; padding: 15px; border: 1px solid <?php echo esc_attr( isset( $styling_settings['border_color'] ) ? $styling_settings['border_color'] : '#e9ecef' ); ?>; border-radius: <?php echo esc_attr( isset( $styling_settings['card_border_radius'] ) ? $styling_settings['card_border_radius'] : 8 ); ?>px; margin: 10px 0;">
							<strong>Sample Card Content</strong><br>
							<span style="color: <?php echo esc_attr( isset( $styling_settings['secondary_color'] ) ? $styling_settings['secondary_color'] : '#28a745' ); ?>; font-weight: 600;">$1,250.00</span>
						</div>
						<button type="button" style="background: <?php echo esc_attr( isset( $styling_settings['button_background'] ) ? $styling_settings['button_background'] : '#0073aa' ); ?>; color: <?php echo esc_attr( isset( $styling_settings['button_text_color'] ) ? $styling_settings['button_text_color'] : '#ffffff' ); ?>; border: none; padding: 8px 16px; border-radius: <?php echo esc_attr( isset( $styling_settings['button_border_radius'] ) ? $styling_settings['button_border_radius'] : 4 ); ?>px; cursor: pointer; font-family: <?php echo esc_attr( isset( $styling_settings['font_family'] ) && $styling_settings['font_family'] !== 'inherit' ? $styling_settings['font_family'] : 'inherit' ); ?>;">Sample Button</button>
					</div>

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

				<?php if ( $current_tab === 'debug' ) : ?>
					<h2>Debug Information</h2>
					<p>Use this section to troubleshoot plugin issues and manage debug tools.</p>
					
					<h3>Salary Management Debug</h3>
					<table class="form-table">
						<tr>
							<th><label for="enable_salary_debug">Enable Salary Debug Tools</label></th>
							<td>
								<input type="checkbox" id="enable_salary_debug" name="wc_team_payroll_settings[enable_salary_debug]" value="1" <?php checked( isset( $settings['enable_salary_debug'] ) ? $settings['enable_salary_debug'] : 0, 1 ); ?> />
								<p class="description">Enable advanced debugging tools for salary accumulation and testing</p>
								
								<?php if ( isset( $settings['enable_salary_debug'] ) && $settings['enable_salary_debug'] ) : ?>
								<div style="background: #e8f5e9; border: 1px solid #4caf50; border-radius: 4px; padding: 12px; margin-top: 10px;">
									<strong>✅ Salary Debug Enabled</strong>
									<p style="margin: 8px 0 0 0; font-size: 13px;">
										<strong>Access Debug Tools:</strong> Go to <strong>Team Payroll → Salary Debug</strong> in the admin menu
									</p>
									<p style="margin: 8px 0 0 0; font-size: 13px;">
										<strong>How It Works:</strong>
									</p>
									<ul style="margin: 5px 0 0 20px; font-size: 13px;">
										<li><strong>Test Accumulation:</strong> Simulate daily salary accumulation without waiting for cron jobs</li>
										<li><strong>Get Status:</strong> View current salary configuration, pending accumulation, and earnings</li>
										<li><strong>Manual Cron:</strong> Trigger salary transfer immediately for testing</li>
										<li><strong>Reset Data:</strong> Clear all salary data for fresh testing</li>
									</ul>
									<p style="margin: 8px 0 0 0; font-size: 13px;">
										<strong>Testing Scenarios:</strong>
									</p>
									<ul style="margin: 5px 0 0 20px; font-size: 13px;">
										<li><strong>Daily:</strong> 1 click = salary added immediately</li>
										<li><strong>Weekly:</strong> Clicks accumulate until week end (Saturday by default)</li>
										<li><strong>Monthly:</strong> Clicks accumulate until month end</li>
										<li><strong>Partial Periods:</strong> Debug tool calculates remaining days from today</li>
									</ul>
								</div>
								<?php else : ?>
								<div style="background: #fff3cd; border: 1px solid #ffc107; border-radius: 4px; padding: 12px; margin-top: 10px;">
									<strong>⚠️ Salary Debug Disabled</strong>
									<p style="margin: 8px 0 0 0; font-size: 13px;">Enable this option to access advanced salary debugging and testing tools.</p>
								</div>
								<?php endif; ?>
							</td>
						</tr>
					</table>
					
					<h3>GitHub Update Status</h3>
					<div id="wc-tp-update-status" style="background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px; padding: 15px; margin: 15px 0;">
						<p><strong>Checking GitHub for updates...</strong></p>
						<p style="color: #666; font-size: 12px;">This may take a few seconds.</p>
					</div>
					<button type="button" class="button button-primary" id="wc-tp-check-update-btn">Check for Updates Now</button>
					<button type="button" class="button" id="wc-tp-clear-cache-btn">Clear Update Cache</button>

					<h3>Plugin Information</h3>
					<table class="form-table">
						<tr>
							<th>Current Version</th>
							<td id="wc-tp-current-version">Loading...</td>
						</tr>
						<tr>
							<th>GitHub Repository</th>
							<td><a href="https://github.com/imranduzzlo/pv-team-payroll" target="_blank">imranduzzlo/pv-team-payroll</a></td>
						</tr>
						<tr>
							<th>Latest Release</th>
							<td id="wc-tp-latest-version">Loading...</td>
						</tr>
						<tr>
							<th>Update Available</th>
							<td id="wc-tp-update-available">Loading...</td>
						</tr>
					</table>

					<?php $nonce = wp_create_nonce( 'wc_team_payroll_nonce' ); ?>
					<script>
						jQuery(document).ready(function($) {
							const nonce = '<?php echo esc_js( $nonce ); ?>';
							
							function checkGitHubUpdate() {
								const statusDiv = $('#wc-tp-update-status');
								statusDiv.html('<p><strong>Checking GitHub for updates...</strong></p><p style="color: #666; font-size: 12px;">This may take a few seconds.</p>');

								$.ajax({
									url: ajaxurl,
									type: 'POST',
									data: {
										action: 'wc_tp_check_github_update',
										nonce: nonce
									},
									success: function(response) {
										if (response.success) {
											const data = response.data;
											let html = '<table style="width: 100%; border-collapse: collapse;">';
											html += '<tr style="background: #f0f0f0;"><td style="padding: 8px; border: 1px solid #ddd;"><strong>Current Version:</strong></td><td style="padding: 8px; border: 1px solid #ddd;">' + data.current_version + '</td></tr>';
											html += '<tr><td style="padding: 8px; border: 1px solid #ddd;"><strong>Latest Version:</strong></td><td style="padding: 8px; border: 1px solid #ddd;">' + data.latest_version + '</td></tr>';
											html += '<tr style="background: #f0f0f0;"><td style="padding: 8px; border: 1px solid #ddd;"><strong>GitHub Tag:</strong></td><td style="padding: 8px; border: 1px solid #ddd;"><a href="' + data.github_url + '" target="_blank">' + data.github_tag + '</a></td></tr>';
											html += '<tr><td style="padding: 8px; border: 1px solid #ddd;"><strong>Published:</strong></td><td style="padding: 8px; border: 1px solid #ddd;">' + new Date(data.published_at).toLocaleString() + '</td></tr>';
											
											if (data.update_available) {
												html += '<tr style="background: #fff3cd;"><td style="padding: 8px; border: 1px solid #ddd;"><strong>Status:</strong></td><td style="padding: 8px; border: 1px solid #ddd; color: #856404;"><strong>✅ Update Available!</strong></td></tr>';
											} else {
												html += '<tr style="background: #d4edda;"><td style="padding: 8px; border: 1px solid #ddd;"><strong>Status:</strong></td><td style="padding: 8px; border: 1px solid #ddd; color: #155724;"><strong>✅ You are up to date</strong></td></tr>';
											}
											
											html += '</table>';
											statusDiv.html(html);

											// Update info table
											$('#wc-tp-current-version').text(data.current_version);
											$('#wc-tp-latest-version').text(data.latest_version);
											$('#wc-tp-update-available').html(data.update_available ? '<span style="color: #d9534f;"><strong>Yes - Update Available</strong></span>' : '<span style="color: #5cb85c;"><strong>No - Up to Date</strong></span>');
										} else {
											statusDiv.html('<div style="background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; padding: 12px; color: #721c24;"><strong>Error:</strong> ' + response.data.message + '</div>');
										}
									},
									error: function() {
										statusDiv.html('<div style="background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; padding: 12px; color: #721c24;"><strong>Error:</strong> Failed to check GitHub API. Please try again.</div>');
									}
								});
							}

							// Check on page load
							checkGitHubUpdate();

							// Check button click
							$('#wc-tp-check-update-btn').on('click', function(e) {
								e.preventDefault();
								checkGitHubUpdate();
							});

							// Clear cache button
							$('#wc-tp-clear-cache-btn').on('click', function(e) {
								e.preventDefault();
								const btn = $(this);
								btn.prop('disabled', true).text('Clearing...');

								$.ajax({
									url: ajaxurl,
									type: 'POST',
									data: {
										action: 'wc_tp_check_github_update',
										nonce: nonce
									},
									complete: function() {
										btn.prop('disabled', false).text('Clear Update Cache');
										checkGitHubUpdate();
									}
								});
							});
						});
					</script>
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
					const timestamp = Date.now();
					const html = `
						<div class="wc-tp-role-item">
							<div class="wc-tp-role-item-header">
								<div style="flex: 1;">
									<input type="text" name="wc_tp_employee_roles[new_${timestamp}][name]" placeholder="Role name (e.g., shop_employee)" value="" style="font-weight: bold; padding: 6px; border: 1px solid #ddd; border-radius: 3px; width: 100%; max-width: 300px;" />
								</div>
								<button type="button" class="wc-tp-role-remove" style="margin-left: 10px;">Remove</button>
							</div>

							<div class="wc-tp-role-capabilities" style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #eee;">
								<label style="display: block; font-weight: bold; margin-bottom: 8px; font-size: 12px;">Capabilities:</label>
								<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px;">
									<label style="display: flex; align-items: center; font-size: 12px;">
										<input type="checkbox" name="wc_tp_employee_roles[new_${timestamp}][capabilities][read]" value="1" />
										<span style="margin-left: 5px;">Read</span>
									</label>
									<label style="display: flex; align-items: center; font-size: 12px;">
										<input type="checkbox" name="wc_tp_employee_roles[new_${timestamp}][capabilities][edit_posts]" value="1" />
										<span style="margin-left: 5px;">Edit Posts</span>
									</label>
									<label style="display: flex; align-items: center; font-size: 12px;">
										<input type="checkbox" name="wc_tp_employee_roles[new_${timestamp}][capabilities][delete_posts]" value="1" />
										<span style="margin-left: 5px;">Delete Posts</span>
									</label>
									<label style="display: flex; align-items: center; font-size: 12px;">
										<input type="checkbox" name="wc_tp_employee_roles[new_${timestamp}][capabilities][publish_posts]" value="1" />
										<span style="margin-left: 5px;">Publish Posts</span>
									</label>
									<label style="display: flex; align-items: center; font-size: 12px;">
										<input type="checkbox" name="wc_tp_employee_roles[new_${timestamp}][capabilities][manage_options]" value="1" />
										<span style="margin-left: 5px;">Manage Options</span>
									</label>
									<label style="display: flex; align-items: center; font-size: 12px;">
										<input type="checkbox" name="wc_tp_employee_roles[new_${timestamp}][capabilities][manage_woocommerce]" value="1" />
										<span style="margin-left: 5px;">Manage WooCommerce</span>
									</label>
								</div>
							</div>

							<input type="hidden" name="wc_tp_employee_roles[new_${timestamp}][role_key]" value="new_${timestamp}" />
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
		
		// Save styling settings
		$styling_settings = isset( $_POST['wc_team_payroll_styling'] ) ? array_map( 'sanitize_text_field', $_POST['wc_team_payroll_styling'] ) : array();

		update_option( 'wc_team_payroll_settings', $settings );
		update_option( 'wc_team_payroll_checkout_fields', $checkout_fields );
		update_option( 'wc_team_payroll_acf_fields', $acf_fields );
		update_option( 'wc_team_payroll_styling', $styling_settings );

		if ( isset( $_POST['wc_tp_user_id_prefix'] ) ) {
			$prefix = sanitize_text_field( $_POST['wc_tp_user_id_prefix'] );
			update_option( 'wc_tp_user_id_prefix', $prefix );
		}

		// Save contact information
		if ( isset( $_POST['contact_whatsapp'] ) ) {
			$whatsapp = sanitize_text_field( $_POST['contact_whatsapp'] );
			update_option( 'wc_team_payroll_contact_whatsapp', $whatsapp );
		}

		if ( isset( $_POST['contact_email'] ) ) {
			$email = sanitize_email( $_POST['contact_email'] );
			update_option( 'wc_team_payroll_contact_email', $email );
		}

		if ( isset( $_POST['contact_telegram'] ) ) {
			$telegram = sanitize_text_field( $_POST['contact_telegram'] );
			update_option( 'wc_team_payroll_contact_telegram', $telegram );
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
