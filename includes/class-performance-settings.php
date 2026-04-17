<?php
/**
 * Performance Settings Class
 * Handles Reports & Performance admin tab configuration
 *
 * @package WooCommerce Team Payroll
 * @since 1.0.52
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Team_Payroll_Performance_Settings {

	/**
	 * Constructor
	 */
	public function __construct() {
		// Add performance tab to settings
		add_filter( 'wc_team_payroll_settings_tabs', array( $this, 'add_performance_tab' ) );
		
		// Enqueue admin assets
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		
		// AJAX handlers - Performance Scoring
		add_action( 'wp_ajax_wc_tp_save_performance_config', array( $this, 'ajax_save_performance_config' ) );
		add_action( 'wp_ajax_wc_tp_get_role_config', array( $this, 'ajax_get_role_config' ) );
		add_action( 'wp_ajax_wc_tp_clone_role_config', array( $this, 'ajax_clone_role_config' ) );
		add_action( 'wp_ajax_wc_tp_preview_calculation', array( $this, 'ajax_preview_calculation' ) );
		
		// AJAX handlers - Goals & Targets
		add_action( 'wp_ajax_wc_tp_get_role_goals', array( $this, 'ajax_get_role_goals' ) );
		add_action( 'wp_ajax_wc_tp_save_goals_config', array( $this, 'ajax_save_goals_config' ) );
		add_action( 'wp_ajax_wc_tp_clone_role_goals', array( $this, 'ajax_clone_role_goals' ) );
		
		// AJAX handlers - Achievements
		add_action( 'wp_ajax_wc_tp_get_role_achievements', array( $this, 'ajax_get_role_achievements' ) );
		add_action( 'wp_ajax_wc_tp_save_achievements_config', array( $this, 'ajax_save_achievements_config' ) );
		add_action( 'wp_ajax_wc_tp_clone_role_achievements', array( $this, 'ajax_clone_role_achievements' ) );
		
		// AJAX handlers - Baselines
		add_action( 'wp_ajax_wc_tp_save_baselines_config', array( $this, 'ajax_save_baselines_config' ) );
		add_action( 'wp_ajax_wc_tp_calculate_baseline_preview', array( $this, 'ajax_calculate_baseline_preview' ) );
		
		// AJAX handlers - Calculation Engine
		add_action( 'wp_ajax_wc_tp_save_calculation_config', array( $this, 'ajax_save_calculation_config' ) );
		add_action( 'wp_ajax_wc_tp_test_formula', array( $this, 'ajax_test_formula' ) );
		
		// AJAX handlers - System Configuration
		add_action( 'wp_ajax_wc_tp_save_system_config', array( $this, 'ajax_save_system_config' ) );
		add_action( 'wp_ajax_wc_tp_reset_all_data', array( $this, 'ajax_reset_all_data' ) );
	}

	/**
	 * Add performance tab to settings
	 */
	public function add_performance_tab( $tabs ) {
		$tabs['performance'] = __( 'Reports & Performance', 'wc-team-payroll' );
		return $tabs;
	}

	/**
	 * Enqueue admin assets
	 */
	public function enqueue_admin_assets( $hook ) {
		// Only load on our settings page
		if ( strpos( $hook, 'wc-team-payroll' ) === false ) {
			return;
		}

		// Check if we're on the performance tab
		$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : '';
		if ( $current_tab !== 'performance' ) {
			return;
		}

		// Enqueue CSS
		wp_enqueue_style(
			'wc-tp-performance-settings',
			WC_TEAM_PAYROLL_URL . 'assets/css/performance-settings.css',
			array(),
			WC_TEAM_PAYROLL_VERSION
		);

		// Enqueue JavaScript
		wp_enqueue_script(
			'wc-tp-performance-settings',
			WC_TEAM_PAYROLL_URL . 'assets/js/performance-settings.js',
			array( 'jquery' ),
			WC_TEAM_PAYROLL_VERSION,
			true
		);

		// Localize script
		wp_localize_script(
			'wc-tp-performance-settings',
			'wcTpPerformance',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'wc_tp_performance_nonce' ),
				'currency_symbol' => get_woocommerce_currency_symbol(),
				'strings'  => array(
					'save_success' => __( 'Configuration saved successfully!', 'wc-team-payroll' ),
					'save_error'   => __( 'Error saving configuration.', 'wc-team-payroll' ),
					'confirm_reset' => __( 'Are you sure you want to reset all configurations? This cannot be undone.', 'wc-team-payroll' ),
				),
			)
		);
	}

	/**
	 * Render performance settings tab content
	 */
	public function render_performance_tab() {
		?>
		<div class="wc-tp-performance-settings-wrapper">
			<h2><?php esc_html_e( 'Reports & Performance Configuration', 'wc-team-payroll' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'Configure role-based performance scoring, goals, achievements, and baselines for your team members.', 'wc-team-payroll' ); ?>
			</p>

			<!-- Navigation Tabs -->
			<div class="wc-tp-perf-nav-tabs">
				<button type="button" class="wc-tp-perf-nav-tab active" data-section="scoring">
					<i class="dashicons dashicons-star-filled"></i>
					<?php esc_html_e( 'Performance Scoring', 'wc-team-payroll' ); ?>
				</button>
				<button type="button" class="wc-tp-perf-nav-tab" data-section="goals">
					<i class="dashicons dashicons-flag"></i>
					<?php esc_html_e( 'Goals & Targets', 'wc-team-payroll' ); ?>
				</button>
				<button type="button" class="wc-tp-perf-nav-tab" data-section="achievements">
					<i class="dashicons dashicons-awards"></i>
					<?php esc_html_e( 'Achievements', 'wc-team-payroll' ); ?>
				</button>
				<button type="button" class="wc-tp-perf-nav-tab" data-section="baselines">
					<i class="dashicons dashicons-chart-line"></i>
					<?php esc_html_e( 'Baselines', 'wc-team-payroll' ); ?>
				</button>
				<button type="button" class="wc-tp-perf-nav-tab" data-section="calculation">
					<i class="dashicons dashicons-calculator"></i>
					<?php esc_html_e( 'Calculation Engine', 'wc-team-payroll' ); ?>
				</button>
				<button type="button" class="wc-tp-perf-nav-tab" data-section="system">
					<i class="dashicons dashicons-admin-settings"></i>
					<?php esc_html_e( 'System Config', 'wc-team-payroll' ); ?>
				</button>
			</div>

			<!-- Section: Performance Scoring -->
			<div class="wc-tp-perf-section active" id="wc-tp-perf-scoring">
				<?php $this->render_scoring_section(); ?>
			</div>

			<!-- Section: Goals & Targets -->
			<div class="wc-tp-perf-section" id="wc-tp-perf-goals">
				<?php $this->render_goals_section(); ?>
			</div>

			<!-- Section: Achievements -->
			<div class="wc-tp-perf-section" id="wc-tp-perf-achievements">
				<?php $this->render_achievements_section(); ?>
			</div>

			<!-- Section: Baselines -->
			<div class="wc-tp-perf-section" id="wc-tp-perf-baselines">
				<?php $this->render_baselines_section(); ?>
			</div>

			<!-- Section: Calculation Engine -->
			<div class="wc-tp-perf-section" id="wc-tp-perf-calculation">
				<?php $this->render_calculation_section(); ?>
			</div>

			<!-- Section: System Config -->
			<div class="wc-tp-perf-section" id="wc-tp-perf-system">
				<?php $this->render_system_section(); ?>
			</div>

			<!-- Save Button -->
			<div class="wc-tp-perf-save-wrapper">
				<button type="button" class="button button-primary button-large" id="wc-tp-save-performance">
					<span class="dashicons dashicons-saved"></span>
					<?php esc_html_e( 'Save All Configurations', 'wc-team-payroll' ); ?>
				</button>
				<button type="button" class="button button-secondary" id="wc-tp-export-performance">
					<span class="dashicons dashicons-download"></span>
					<?php esc_html_e( 'Export Settings', 'wc-team-payroll' ); ?>
				</button>
				<button type="button" class="button button-secondary" id="wc-tp-import-performance">
					<span class="dashicons dashicons-upload"></span>
					<?php esc_html_e( 'Import Settings', 'wc-team-payroll' ); ?>
				</button>
				<button type="button" class="button button-link-delete" id="wc-tp-reset-performance">
					<span class="dashicons dashicons-undo"></span>
					<?php esc_html_e( 'Reset All', 'wc-team-payroll' ); ?>
				</button>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Performance Scoring Section
	 */
	private function render_scoring_section() {
		// Get employee roles from settings (only configured employee roles)
		$all_roles = $this->get_all_roles();
		$performance_config = get_option( 'wc_tp_performance_config', array() );
		?>
		<div class="wc-tp-perf-scoring-wrapper">
			<h3><?php esc_html_e( 'Performance Scoring Matrix - Role-Based Configuration', 'wc-team-payroll' ); ?></h3>
			
			<!-- Base Score -->
			<div class="wc-tp-perf-card">
				<h4><?php esc_html_e( 'Base Score Configuration', 'wc-team-payroll' ); ?></h4>
				<p class="description"><?php esc_html_e( 'Universal starting score for all employees before applying performance factors.', 'wc-team-payroll' ); ?></p>
				<table class="form-table">
					<tr>
						<th><label for="base_score"><?php esc_html_e( 'Base Score', 'wc-team-payroll' ); ?></label></th>
						<td>
							<input type="number" id="base_score" name="base_score" value="<?php echo esc_attr( isset( $performance_config['base_score'] ) ? $performance_config['base_score'] : 5 ); ?>" step="0.1" min="0" max="10" class="small-text" />
							<span class="description"><?php esc_html_e( 'points (0-10)', 'wc-team-payroll' ); ?></span>
						</td>
					</tr>
				</table>
			</div>

			<!-- Role Selector -->
			<div class="wc-tp-perf-card">
				<h4><?php esc_html_e( 'Configure Performance Factors by Role', 'wc-team-payroll' ); ?></h4>
				<p class="description">
					<?php esc_html_e( 'Configure performance scoring for each employee role. Only roles configured in Settings → WooCommerce → Employee User Roles are shown here.', 'wc-team-payroll' ); ?>
					<?php if ( empty( $all_roles ) ) : ?>
						<br><strong style="color: #d63638;"><?php esc_html_e( 'No employee roles configured. Please configure employee roles in Settings → WooCommerce → Employee User Roles first.', 'wc-team-payroll' ); ?></strong>
					<?php endif; ?>
				</p>
				<div class="wc-tp-role-selector-wrapper">
					<label for="wc-tp-role-selector"><?php esc_html_e( 'Select Employee Role to Configure:', 'wc-team-payroll' ); ?></label>
					
					<select id="wc-tp-role-selector" class="wc-tp-role-selector" <?php echo empty( $all_roles ) ? 'disabled' : ''; ?>>
						<option value=""><?php esc_html_e( '-- Select an Employee Role --', 'wc-team-payroll' ); ?></option>
						<?php foreach ( $all_roles as $role_key => $role_name ) : ?>
							<option value="<?php echo esc_attr( $role_key ); ?>"><?php echo esc_html( $role_name ); ?></option>
						<?php endforeach; ?>
					</select>
					
					<div class="wc-tp-role-actions">
						<button type="button" class="button" id="wc-tp-clone-role">
							<span class="dashicons dashicons-admin-page"></span>
							<?php esc_html_e( 'Clone from Another Role', 'wc-team-payroll' ); ?>
						</button>
						<button type="button" class="button" id="wc-tp-reset-role">
							<span class="dashicons dashicons-undo"></span>
							<?php esc_html_e( 'Reset to Default', 'wc-team-payroll' ); ?>
						</button>
					</div>
				</div>
			</div>

			<!-- Role Configuration Container (Loaded via AJAX) -->
			<div id="wc-tp-role-config-container">
				<div class="wc-tp-empty-state">
					<span class="dashicons dashicons-admin-users"></span>
					<p><?php esc_html_e( 'Select an employee role above to configure performance scoring factors.', 'wc-team-payroll' ); ?></p>
					<?php if ( empty( $all_roles ) ) : ?>
						<p><a href="?page=wc-team-payroll-settings&tab=woocommerce" class="button button-secondary"><?php esc_html_e( 'Configure Employee Roles', 'wc-team-payroll' ); ?></a></p>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Goals & Targets Section
	 */
	private function render_goals_section() {
		// Get employee roles from settings (only configured employee roles)
		$all_roles = $this->get_all_roles();
		$goals_config = get_option( 'wc_tp_goals_config', array() );
		?>
		<div class="wc-tp-perf-goals-wrapper">
			<h3><?php esc_html_e( 'Goals & Targets Configuration - Role-Based Matrix', 'wc-team-payroll' ); ?></h3>
			<p class="description"><?php esc_html_e( 'Configure different goal targets for each role based on their responsibilities and expectations. Set minimum, target, and stretch goals for earnings, orders, and AOV.', 'wc-team-payroll' ); ?></p>
			
			<!-- Global Settings -->
			<div class="wc-tp-perf-card">
				<h4><?php esc_html_e( 'Global Goal Settings', 'wc-team-payroll' ); ?></h4>
				<p class="description"><?php esc_html_e( 'Configure how goals are displayed and calculated across the system.', 'wc-team-payroll' ); ?></p>
				<table class="form-table">
					<tr>
						<th><label for="goals_period"><?php esc_html_e( 'Default Goal Period', 'wc-team-payroll' ); ?></label></th>
						<td>
							<select id="goals_period" name="goals_period" class="wc-tp-goals-setting">
								<?php
								$period = isset( $goals_config['period'] ) ? $goals_config['period'] : 'monthly';
								$periods = array(
									'weekly' => __( 'Weekly', 'wc-team-payroll' ),
									'monthly' => __( 'Monthly', 'wc-team-payroll' ),
									'quarterly' => __( 'Quarterly', 'wc-team-payroll' ),
									'yearly' => __( 'Yearly', 'wc-team-payroll' ),
								);
								foreach ( $periods as $value => $label ) {
									echo '<option value="' . esc_attr( $value ) . '"' . selected( $period, $value, false ) . '>' . esc_html( $label ) . '</option>';
								}
								?>
							</select>
							<p class="description"><?php esc_html_e( 'Default time period for goal tracking', 'wc-team-payroll' ); ?></p>
						</td>
					</tr>
					<tr>
						<th><label for="goals_display_mode"><?php esc_html_e( 'Display Mode', 'wc-team-payroll' ); ?></label></th>
						<td>
							<select id="goals_display_mode" name="goals_display_mode" class="wc-tp-goals-setting">
								<?php
								$display_mode = isset( $goals_config['display_mode'] ) ? $goals_config['display_mode'] : 'percentage';
								$modes = array(
									'percentage' => __( 'Percentage Progress', 'wc-team-payroll' ),
									'absolute' => __( 'Absolute Values', 'wc-team-payroll' ),
									'both' => __( 'Both', 'wc-team-payroll' ),
								);
								foreach ( $modes as $value => $label ) {
									echo '<option value="' . esc_attr( $value ) . '"' . selected( $display_mode, $value, false ) . '>' . esc_html( $label ) . '</option>';
								}
								?>
							</select>
							<p class="description"><?php esc_html_e( 'How to display goal progress on reports page', 'wc-team-payroll' ); ?></p>
						</td>
					</tr>
					<tr>
						<th><label for="goals_show_stretch"><?php esc_html_e( 'Show Stretch Goals', 'wc-team-payroll' ); ?></label></th>
						<td>
							<input type="checkbox" id="goals_show_stretch" name="goals_show_stretch" value="1" class="wc-tp-goals-setting" <?php checked( isset( $goals_config['show_stretch'] ) ? $goals_config['show_stretch'] : 1, 1 ); ?> />
							<label for="goals_show_stretch"><?php esc_html_e( 'Display stretch goals to employees', 'wc-team-payroll' ); ?></label>
						</td>
					</tr>
				</table>
			</div>

			<!-- Role Selector -->
			<div class="wc-tp-perf-card">
				<h4><?php esc_html_e( 'Configure Goals by Role', 'wc-team-payroll' ); ?></h4>
				<p class="description">
					<?php esc_html_e( 'Set different goal targets for each employee role. Only roles configured in Settings → WooCommerce → Employee User Roles are shown here.', 'wc-team-payroll' ); ?>
					<?php if ( empty( $all_roles ) ) : ?>
						<br><strong style="color: #d63638;"><?php esc_html_e( 'No employee roles configured. Please configure employee roles in Settings → WooCommerce → Employee User Roles first.', 'wc-team-payroll' ); ?></strong>
					<?php endif; ?>
				</p>
				<div class="wc-tp-role-selector-wrapper">
					<label for="wc-tp-goals-role-selector"><?php esc_html_e( 'Select Employee Role to Configure:', 'wc-team-payroll' ); ?></label>
					<select id="wc-tp-goals-role-selector" class="wc-tp-role-selector" <?php echo empty( $all_roles ) ? 'disabled' : ''; ?>>
						<option value=""><?php esc_html_e( '-- Select an Employee Role --', 'wc-team-payroll' ); ?></option>
						<?php foreach ( $all_roles as $role_key => $role_name ) : ?>
							<option value="<?php echo esc_attr( $role_key ); ?>"><?php echo esc_html( $role_name ); ?></option>
						<?php endforeach; ?>
					</select>
					
					<div class="wc-tp-role-actions">
						<button type="button" class="button" id="wc-tp-clone-goals-role">
							<span class="dashicons dashicons-admin-page"></span>
							<?php esc_html_e( 'Clone from Another Role', 'wc-team-payroll' ); ?>
						</button>
						<button type="button" class="button" id="wc-tp-reset-goals-role">
							<span class="dashicons dashicons-undo"></span>
							<?php esc_html_e( 'Reset to Default', 'wc-team-payroll' ); ?>
						</button>
					</div>
				</div>
			</div>

			<!-- Role Goals Configuration Container (Loaded via AJAX) -->
			<div id="wc-tp-goals-config-container">
				<div class="wc-tp-empty-state">
					<span class="dashicons dashicons-flag"></span>
					<p><?php esc_html_e( 'Select an employee role above to configure goals and targets.', 'wc-team-payroll' ); ?></p>
					<?php if ( empty( $all_roles ) ) : ?>
						<p><a href="?page=wc-team-payroll-settings&tab=woocommerce" class="button button-secondary"><?php esc_html_e( 'Configure Employee Roles', 'wc-team-payroll' ); ?></a></p>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Achievements Section
	 */
	private function render_achievements_section() {
		// Get employee roles from settings (only configured employee roles)
		$all_roles = $this->get_all_roles();
		$achievements_config = get_option( 'wc_tp_achievements_config', array() );
		?>
		<div class="wc-tp-perf-achievements-wrapper">
			<h3><?php esc_html_e( 'Achievements System - Role-Aware Configuration', 'wc-team-payroll' ); ?></h3>
			<p class="description"><?php esc_html_e( 'Create role-specific achievements with different criteria and rewards for each role. Employees earn Bronze, Silver, and Gold badges based on their performance.', 'wc-team-payroll' ); ?></p>
			
			<!-- Global Achievement Settings -->
			<div class="wc-tp-perf-card">
				<h4><?php esc_html_e( 'Global Achievement Settings', 'wc-team-payroll' ); ?></h4>
				<p class="description"><?php esc_html_e( 'Configure how achievements are displayed and awarded across the system.', 'wc-team-payroll' ); ?></p>
				<table class="form-table">
					<tr>
						<th><label for="achievements_enabled"><?php esc_html_e( 'Enable Achievements', 'wc-team-payroll' ); ?></label></th>
						<td>
							<input type="checkbox" id="achievements_enabled" name="achievements_enabled" value="1" class="wc-tp-achievements-setting" <?php checked( isset( $achievements_config['enabled'] ) ? $achievements_config['enabled'] : 1, 1 ); ?> />
							<label for="achievements_enabled"><?php esc_html_e( 'Display achievements on employee reports page', 'wc-team-payroll' ); ?></label>
						</td>
					</tr>
					<tr>
						<th><label for="achievements_display_style"><?php esc_html_e( 'Display Style', 'wc-team-payroll' ); ?></label></th>
						<td>
							<select id="achievements_display_style" name="achievements_display_style" class="wc-tp-achievements-setting">
								<?php
								$display_style = isset( $achievements_config['display_style'] ) ? $achievements_config['display_style'] : 'badges';
								$styles = array(
									'badges' => __( 'Badge Icons', 'wc-team-payroll' ),
									'cards' => __( 'Achievement Cards', 'wc-team-payroll' ),
									'list' => __( 'Simple List', 'wc-team-payroll' ),
								);
								foreach ( $styles as $value => $label ) {
									echo '<option value="' . esc_attr( $value ) . '"' . selected( $display_style, $value, false ) . '>' . esc_html( $label ) . '</option>';
								}
								?>
							</select>
							<p class="description"><?php esc_html_e( 'How achievements are displayed to employees', 'wc-team-payroll' ); ?></p>
						</td>
					</tr>
					<tr>
						<th><label for="achievements_show_locked"><?php esc_html_e( 'Show Locked Achievements', 'wc-team-payroll' ); ?></label></th>
						<td>
							<input type="checkbox" id="achievements_show_locked" name="achievements_show_locked" value="1" class="wc-tp-achievements-setting" <?php checked( isset( $achievements_config['show_locked'] ) ? $achievements_config['show_locked'] : 1, 1 ); ?> />
							<label for="achievements_show_locked"><?php esc_html_e( 'Show achievements that haven\'t been earned yet (grayed out)', 'wc-team-payroll' ); ?></label>
						</td>
					</tr>
					<tr>
						<th><label for="achievements_notification"><?php esc_html_e( 'Achievement Notifications', 'wc-team-payroll' ); ?></label></th>
						<td>
							<input type="checkbox" id="achievements_notification" name="achievements_notification" value="1" class="wc-tp-achievements-setting" <?php checked( isset( $achievements_config['notification'] ) ? $achievements_config['notification'] : 1, 1 ); ?> />
							<label for="achievements_notification"><?php esc_html_e( 'Show notification when employee earns a new achievement', 'wc-team-payroll' ); ?></label>
						</td>
					</tr>
				</table>
			</div>

			<!-- Role Selector -->
			<div class="wc-tp-perf-card">
				<h4><?php esc_html_e( 'Configure Achievements by Role', 'wc-team-payroll' ); ?></h4>
				<p class="description">
					<?php esc_html_e( 'Create role-specific achievements with different criteria for each employee role. Only roles configured in Settings → WooCommerce → Employee User Roles are shown here.', 'wc-team-payroll' ); ?>
					<?php if ( empty( $all_roles ) ) : ?>
						<br><strong style="color: #d63638;"><?php esc_html_e( 'No employee roles configured. Please configure employee roles in Settings → WooCommerce → Employee User Roles first.', 'wc-team-payroll' ); ?></strong>
					<?php endif; ?>
				</p>
				<div class="wc-tp-role-selector-wrapper">
					<label for="wc-tp-achievements-role-selector"><?php esc_html_e( 'Select Employee Role to Configure:', 'wc-team-payroll' ); ?></label>
					<select id="wc-tp-achievements-role-selector" class="wc-tp-role-selector" <?php echo empty( $all_roles ) ? 'disabled' : ''; ?>>
						<option value=""><?php esc_html_e( '-- Select an Employee Role --', 'wc-team-payroll' ); ?></option>
						<?php foreach ( $all_roles as $role_key => $role_name ) : ?>
							<option value="<?php echo esc_attr( $role_key ); ?>"><?php echo esc_html( $role_name ); ?></option>
						<?php endforeach; ?>
					</select>
					
					<div class="wc-tp-role-actions">
						<button type="button" class="button" id="wc-tp-clone-achievements-role">
							<span class="dashicons dashicons-admin-page"></span>
							<?php esc_html_e( 'Clone from Another Role', 'wc-team-payroll' ); ?>
						</button>
						<button type="button" class="button" id="wc-tp-reset-achievements-role">
							<span class="dashicons dashicons-undo"></span>
							<?php esc_html_e( 'Reset to Default', 'wc-team-payroll' ); ?>
						</button>
					</div>
				</div>
			</div>

			<!-- Role Achievements Configuration Container (Loaded via AJAX) -->
			<div id="wc-tp-achievements-config-container">
				<div class="wc-tp-empty-state">
					<span class="dashicons dashicons-awards"></span>
					<p><?php esc_html_e( 'Select an employee role above to configure achievements and badges.', 'wc-team-payroll' ); ?></p>
					<?php if ( empty( $all_roles ) ) : ?>
						<p><a href="?page=wc-team-payroll-settings&tab=woocommerce" class="button button-secondary"><?php esc_html_e( 'Configure Employee Roles', 'wc-team-payroll' ); ?></a></p>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Baselines Section
	 */
	private function render_baselines_section() {
		$baselines_config = get_option( 'wc_tp_baselines_config', array() );
		?>
		<div class="wc-tp-perf-baselines-wrapper">
			<h3><?php esc_html_e( 'Performance Baselines & Benchmarks', 'wc-team-payroll' ); ?></h3>
			<p class="description"><?php esc_html_e( 'Configure how performance baselines are calculated and updated over time. Baselines help track improvement and set realistic expectations.', 'wc-team-payroll' ); ?></p>
			
			<!-- Baseline Calculation Method -->
			<div class="wc-tp-perf-card">
				<h4><?php esc_html_e( 'Baseline Calculation Method', 'wc-team-payroll' ); ?></h4>
				<p class="description"><?php esc_html_e( 'Choose how employee baselines are calculated. This affects how performance is measured and compared.', 'wc-team-payroll' ); ?></p>
				
				<table class="form-table">
					<tr>
						<th><label for="baseline_method"><?php esc_html_e( 'Calculation Method', 'wc-team-payroll' ); ?></label></th>
						<td>
							<select id="baseline_method" name="baseline_method" class="wc-tp-baseline-setting">
								<?php
								$method = isset( $baselines_config['method'] ) ? $baselines_config['method'] : 'rolling_average';
								$methods = array(
									'rolling_average' => __( 'Rolling Average (Last N periods)', 'wc-team-payroll' ),
									'historical_average' => __( 'Historical Average (All time)', 'wc-team-payroll' ),
									'best_period' => __( 'Best Period Performance', 'wc-team-payroll' ),
									'median' => __( 'Median Performance', 'wc-team-payroll' ),
									'percentile' => __( 'Percentile-Based (e.g., 75th percentile)', 'wc-team-payroll' ),
									'manual' => __( 'Manual Entry (Admin sets baseline)', 'wc-team-payroll' ),
								);
								foreach ( $methods as $value => $label ) {
									echo '<option value="' . esc_attr( $value ) . '"' . selected( $method, $value, false ) . '>' . esc_html( $label ) . '</option>';
								}
								?>
							</select>
							<p class="description"><?php esc_html_e( 'How baseline values are calculated from historical data', 'wc-team-payroll' ); ?></p>
						</td>
					</tr>
					
					<tr class="wc-tp-baseline-option" data-show-for="rolling_average">
						<th><label for="baseline_periods"><?php esc_html_e( 'Number of Periods', 'wc-team-payroll' ); ?></label></th>
						<td>
							<input type="number" 
								   id="baseline_periods" 
								   name="baseline_periods" 
								   value="<?php echo esc_attr( isset( $baselines_config['periods'] ) ? $baselines_config['periods'] : 3 ); ?>" 
								   min="1" 
								   max="12" 
								   step="1"
								   class="wc-tp-baseline-setting small-text" />
							<span class="description"><?php esc_html_e( 'periods (e.g., last 3 months)', 'wc-team-payroll' ); ?></span>
						</td>
					</tr>
					
					<tr class="wc-tp-baseline-option" data-show-for="percentile">
						<th><label for="baseline_percentile"><?php esc_html_e( 'Percentile', 'wc-team-payroll' ); ?></label></th>
						<td>
							<input type="number" 
								   id="baseline_percentile" 
								   name="baseline_percentile" 
								   value="<?php echo esc_attr( isset( $baselines_config['percentile'] ) ? $baselines_config['percentile'] : 75 ); ?>" 
								   min="1" 
								   max="99" 
								   step="1"
								   class="wc-tp-baseline-setting small-text" />
							<span class="description"><?php esc_html_e( 'th percentile (1-99)', 'wc-team-payroll' ); ?></span>
						</td>
					</tr>
					
					<tr>
						<th><label for="baseline_update_frequency"><?php esc_html_e( 'Update Frequency', 'wc-team-payroll' ); ?></label></th>
						<td>
							<select id="baseline_update_frequency" name="baseline_update_frequency" class="wc-tp-baseline-setting">
								<?php
								$frequency = isset( $baselines_config['update_frequency'] ) ? $baselines_config['update_frequency'] : 'monthly';
								$frequencies = array(
									'daily' => __( 'Daily', 'wc-team-payroll' ),
									'weekly' => __( 'Weekly', 'wc-team-payroll' ),
									'monthly' => __( 'Monthly', 'wc-team-payroll' ),
									'quarterly' => __( 'Quarterly', 'wc-team-payroll' ),
									'manual' => __( 'Manual Only', 'wc-team-payroll' ),
								);
								foreach ( $frequencies as $value => $label ) {
									echo '<option value="' . esc_attr( $value ) . '"' . selected( $frequency, $value, false ) . '>' . esc_html( $label ) . '</option>';
								}
								?>
							</select>
							<p class="description"><?php esc_html_e( 'How often baselines are recalculated', 'wc-team-payroll' ); ?></p>
						</td>
					</tr>
					
					<tr>
						<th><label for="baseline_minimum_data"><?php esc_html_e( 'Minimum Data Points', 'wc-team-payroll' ); ?></label></th>
						<td>
							<input type="number" 
								   id="baseline_minimum_data" 
								   name="baseline_minimum_data" 
								   value="<?php echo esc_attr( isset( $baselines_config['minimum_data'] ) ? $baselines_config['minimum_data'] : 5 ); ?>" 
								   min="1" 
								   max="50" 
								   step="1"
								   class="wc-tp-baseline-setting small-text" />
							<span class="description"><?php esc_html_e( 'data points required before calculating baseline', 'wc-team-payroll' ); ?></span>
							<p class="description"><?php esc_html_e( 'Prevents unreliable baselines from insufficient data', 'wc-team-payroll' ); ?></p>
						</td>
					</tr>
				</table>
			</div>

			<!-- Baseline Display Settings -->
			<div class="wc-tp-perf-card">
				<h4><?php esc_html_e( 'Baseline Display Settings', 'wc-team-payroll' ); ?></h4>
				<p class="description"><?php esc_html_e( 'Configure how baselines are displayed to employees on the reports page.', 'wc-team-payroll' ); ?></p>
				
				<table class="form-table">
					<tr>
						<th><label for="baseline_show_comparison"><?php esc_html_e( 'Show Comparison', 'wc-team-payroll' ); ?></label></th>
						<td>
							<input type="checkbox" 
								   id="baseline_show_comparison" 
								   name="baseline_show_comparison" 
								   value="1" 
								   class="wc-tp-baseline-setting" 
								   <?php checked( isset( $baselines_config['show_comparison'] ) ? $baselines_config['show_comparison'] : 1, 1 ); ?> />
							<label for="baseline_show_comparison"><?php esc_html_e( 'Show current performance vs baseline comparison', 'wc-team-payroll' ); ?></label>
						</td>
					</tr>
					
					<tr>
						<th><label for="baseline_show_trend"><?php esc_html_e( 'Show Trend', 'wc-team-payroll' ); ?></label></th>
						<td>
							<input type="checkbox" 
								   id="baseline_show_trend" 
								   name="baseline_show_trend" 
								   value="1" 
								   class="wc-tp-baseline-setting" 
								   <?php checked( isset( $baselines_config['show_trend'] ) ? $baselines_config['show_trend'] : 1, 1 ); ?> />
							<label for="baseline_show_trend"><?php esc_html_e( 'Show improvement/decline trend indicators', 'wc-team-payroll' ); ?></label>
						</td>
					</tr>
					
					<tr>
						<th><label for="baseline_show_history"><?php esc_html_e( 'Show History', 'wc-team-payroll' ); ?></label></th>
						<td>
							<input type="checkbox" 
								   id="baseline_show_history" 
								   name="baseline_show_history" 
								   value="1" 
								   class="wc-tp-baseline-setting" 
								   <?php checked( isset( $baselines_config['show_history'] ) ? $baselines_config['show_history'] : 0, 1 ); ?> />
							<label for="baseline_show_history"><?php esc_html_e( 'Show historical baseline changes', 'wc-team-payroll' ); ?></label>
						</td>
					</tr>
					
					<tr>
						<th><label for="baseline_comparison_format"><?php esc_html_e( 'Comparison Format', 'wc-team-payroll' ); ?></label></th>
						<td>
							<select id="baseline_comparison_format" name="baseline_comparison_format" class="wc-tp-baseline-setting">
								<?php
								$format = isset( $baselines_config['comparison_format'] ) ? $baselines_config['comparison_format'] : 'percentage';
								$formats = array(
									'percentage' => __( 'Percentage (e.g., +15%)', 'wc-team-payroll' ),
									'absolute' => __( 'Absolute Value (e.g., +$500)', 'wc-team-payroll' ),
									'both' => __( 'Both (e.g., +$500 / +15%)', 'wc-team-payroll' ),
								);
								foreach ( $formats as $value => $label ) {
									echo '<option value="' . esc_attr( $value ) . '"' . selected( $format, $value, false ) . '>' . esc_html( $label ) . '</option>';
								}
								?>
							</select>
							<p class="description"><?php esc_html_e( 'How to display performance vs baseline', 'wc-team-payroll' ); ?></p>
						</td>
					</tr>
				</table>
			</div>

			<!-- Baseline Preview Calculator -->
			<div class="wc-tp-perf-card wc-tp-baseline-preview-card">
				<h4><?php esc_html_e( 'Baseline Preview Calculator', 'wc-team-payroll' ); ?></h4>
				<p class="description"><?php esc_html_e( 'Test how baselines are calculated with sample historical data.', 'wc-team-payroll' ); ?></p>
				
				<div class="wc-tp-baseline-preview-section">
					<div class="wc-tp-baseline-input-section">
						<h5><?php esc_html_e( 'Sample Historical Data', 'wc-team-payroll' ); ?></h5>
						<p class="description"><?php esc_html_e( 'Enter sample performance data for the last few periods:', 'wc-team-payroll' ); ?></p>
						
						<div class="wc-tp-baseline-data-inputs">
							<div class="wc-tp-baseline-data-column">
								<label><?php esc_html_e( 'Earnings History', 'wc-team-payroll' ); ?></label>
								<input type="text" class="wc-tp-baseline-sample" id="sample_earnings" placeholder="3000, 3500, 4000, 3800, 4200" />
								<span class="description"><?php esc_html_e( 'Comma-separated values', 'wc-team-payroll' ); ?></span>
							</div>
							
							<div class="wc-tp-baseline-data-column">
								<label><?php esc_html_e( 'Orders History', 'wc-team-payroll' ); ?></label>
								<input type="text" class="wc-tp-baseline-sample" id="sample_orders" placeholder="25, 30, 28, 32, 35" />
								<span class="description"><?php esc_html_e( 'Comma-separated values', 'wc-team-payroll' ); ?></span>
							</div>
							
							<div class="wc-tp-baseline-data-column">
								<label><?php esc_html_e( 'AOV History', 'wc-team-payroll' ); ?></label>
								<input type="text" class="wc-tp-baseline-sample" id="sample_aov" placeholder="200, 220, 210, 230, 240" />
								<span class="description"><?php esc_html_e( 'Comma-separated values', 'wc-team-payroll' ); ?></span>
							</div>
						</div>
						
						<button type="button" class="button button-primary" id="wc-tp-calculate-baseline">
							<span class="dashicons dashicons-calculator"></span>
							<?php esc_html_e( 'Calculate Baseline', 'wc-team-payroll' ); ?>
						</button>
					</div>
					
					<div class="wc-tp-baseline-result-section" style="display: none;">
						<h5><?php esc_html_e( 'Calculated Baselines', 'wc-team-payroll' ); ?></h5>
						
						<div class="wc-tp-baseline-results">
							<div class="wc-tp-baseline-result-item">
								<div class="wc-tp-baseline-result-icon">
									<span class="dashicons dashicons-money-alt"></span>
								</div>
								<div class="wc-tp-baseline-result-content">
									<h6><?php esc_html_e( 'Earnings Baseline', 'wc-team-payroll' ); ?></h6>
									<p class="wc-tp-baseline-value" id="baseline_earnings_value">$0</p>
									<p class="wc-tp-baseline-method" id="baseline_earnings_method"></p>
								</div>
							</div>
							
							<div class="wc-tp-baseline-result-item">
								<div class="wc-tp-baseline-result-icon">
									<span class="dashicons dashicons-cart"></span>
								</div>
								<div class="wc-tp-baseline-result-content">
									<h6><?php esc_html_e( 'Orders Baseline', 'wc-team-payroll' ); ?></h6>
									<p class="wc-tp-baseline-value" id="baseline_orders_value">0</p>
									<p class="wc-tp-baseline-method" id="baseline_orders_method"></p>
								</div>
							</div>
							
							<div class="wc-tp-baseline-result-item">
								<div class="wc-tp-baseline-result-icon">
									<span class="dashicons dashicons-chart-line"></span>
								</div>
								<div class="wc-tp-baseline-result-content">
									<h6><?php esc_html_e( 'AOV Baseline', 'wc-team-payroll' ); ?></h6>
									<p class="wc-tp-baseline-value" id="baseline_aov_value">$0</p>
									<p class="wc-tp-baseline-method" id="baseline_aov_method"></p>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Calculation Engine Section
	 */
	private function render_calculation_section() {
		$calculation_config = get_option( 'wc_tp_calculation_config', array() );
		?>
		<div class="wc-tp-perf-calculation-wrapper">
			<h3><?php esc_html_e( 'Performance Calculation Engine', 'wc-team-payroll' ); ?></h3>
			<p class="description"><?php esc_html_e( 'Configure how performance scores and metrics are calculated. Customize formulas, weights, and calculation rules.', 'wc-team-payroll' ); ?></p>
			
			<!-- Score Calculation Formula -->
			<div class="wc-tp-perf-card">
				<h4><?php esc_html_e( 'Performance Score Formula', 'wc-team-payroll' ); ?></h4>
				<p class="description"><?php esc_html_e( 'Configure how the final performance score is calculated from individual factors.', 'wc-team-payroll' ); ?></p>
				
				<table class="form-table">
					<tr>
						<th><label for="calc_score_method"><?php esc_html_e( 'Calculation Method', 'wc-team-payroll' ); ?></label></th>
						<td>
							<select id="calc_score_method" name="calc_score_method" class="wc-tp-calc-setting">
								<?php
								$method = isset( $calculation_config['score_method'] ) ? $calculation_config['score_method'] : 'additive';
								$methods = array(
									'additive' => __( 'Additive (Base + Earnings + Orders + AOV)', 'wc-team-payroll' ),
									'weighted' => __( 'Weighted Average (Custom weights per factor)', 'wc-team-payroll' ),
									'multiplicative' => __( 'Multiplicative (Base × Factor multipliers)', 'wc-team-payroll' ),
									'custom' => __( 'Custom Formula (Advanced)', 'wc-team-payroll' ),
								);
								foreach ( $methods as $value => $label ) {
									echo '<option value="' . esc_attr( $value ) . '"' . selected( $method, $value, false ) . '>' . esc_html( $label ) . '</option>';
								}
								?>
							</select>
							<p class="description"><?php esc_html_e( 'How individual factors combine to create final score', 'wc-team-payroll' ); ?></p>
						</td>
					</tr>
					
					<tr class="wc-tp-calc-option" data-show-for="custom">
						<th><label for="calc_custom_formula"><?php esc_html_e( 'Custom Formula', 'wc-team-payroll' ); ?></label></th>
						<td>
							<textarea id="calc_custom_formula" 
									  name="calc_custom_formula" 
									  rows="4" 
									  class="wc-tp-calc-setting"
									  placeholder="Example: (base * 0.5) + (earnings * 0.4) + (orders * 0.08) + (aov * 0.02)"
									  style="width: 100%; font-family: monospace;"><?php echo esc_textarea( isset( $calculation_config['custom_formula'] ) ? $calculation_config['custom_formula'] : '' ); ?></textarea>
							
							<div class="wc-tp-formula-guide" style="margin-top: 12px; padding: 12px; background: #f0f6fc; border: 1px solid #c3dcf0; border-radius: 4px;">
								<strong><?php esc_html_e( '📝 Formula Guide:', 'wc-team-payroll' ); ?></strong><br><br>
								
								<strong><?php esc_html_e( 'Available Variables:', 'wc-team-payroll' ); ?></strong>
								<ul style="margin: 8px 0; padding-left: 20px;">
									<li><code>base</code> - <?php esc_html_e( 'Base score (usually 5)', 'wc-team-payroll' ); ?></li>
									<li><code>earnings</code> - <?php esc_html_e( 'Earnings performance points', 'wc-team-payroll' ); ?></li>
									<li><code>orders</code> - <?php esc_html_e( 'Orders performance points', 'wc-team-payroll' ); ?></li>
									<li><code>aov</code> - <?php esc_html_e( 'Average Order Value performance points', 'wc-team-payroll' ); ?></li>
								</ul>
								
								<strong><?php esc_html_e( 'Supported Operators:', 'wc-team-payroll' ); ?></strong>
								<ul style="margin: 8px 0; padding-left: 20px;">
									<li><code>+</code> - <?php esc_html_e( 'Addition', 'wc-team-payroll' ); ?></li>
									<li><code>-</code> - <?php esc_html_e( 'Subtraction', 'wc-team-payroll' ); ?></li>
									<li><code>*</code> - <?php esc_html_e( 'Multiplication', 'wc-team-payroll' ); ?></li>
									<li><code>/</code> - <?php esc_html_e( 'Division', 'wc-team-payroll' ); ?></li>
									<li><code>( )</code> - <?php esc_html_e( 'Parentheses for grouping', 'wc-team-payroll' ); ?></li>
								</ul>
								
								<strong><?php esc_html_e( 'Examples:', 'wc-team-payroll' ); ?></strong>
								<ul style="margin: 8px 0; padding-left: 20px;">
									<li><code>base + earnings + orders + aov</code> - <?php esc_html_e( 'Simple addition', 'wc-team-payroll' ); ?></li>
									<li><code>(earnings * 0.4) + (orders * 0.35) + (aov * 0.25)</code> - <?php esc_html_e( 'Weighted average', 'wc-team-payroll' ); ?></li>
									<li><code>base * (1 + earnings * 0.1) * (1 + orders * 0.05)</code> - <?php esc_html_e( 'Multiplicative', 'wc-team-payroll' ); ?></li>
									<li><code>(earnings * 0.6) + (orders * 0.3) + (aov * 0.1)</code> - <?php esc_html_e( 'Sales-focused', 'wc-team-payroll' ); ?></li>
								</ul>
								
								<strong style="color: #d63638;"><?php esc_html_e( '⚠️ Important:', 'wc-team-payroll' ); ?></strong>
								<ul style="margin: 8px 0; padding-left: 20px; color: #d63638;">
									<li><?php esc_html_e( 'Formula is case-sensitive (use lowercase variable names)', 'wc-team-payroll' ); ?></li>
									<li><?php esc_html_e( 'Use * for multiplication (not x)', 'wc-team-payroll' ); ?></li>
									<li><?php esc_html_e( 'Final score will be capped at the Score Cap value', 'wc-team-payroll' ); ?></li>
									<li><?php esc_html_e( 'Invalid formulas will fall back to additive method', 'wc-team-payroll' ); ?></li>
								</ul>
							</div>
						</td>
					</tr>
					
					<tr class="wc-tp-calc-option" data-show-for="weighted">
						<th><label><?php esc_html_e( 'Factor Weights', 'wc-team-payroll' ); ?></label></th>
						<td>
							<div class="wc-tp-weight-inputs">
								<div class="wc-tp-weight-item">
									<label><?php esc_html_e( 'Earnings Weight:', 'wc-team-payroll' ); ?></label>
									<input type="number" 
										   id="calc_weight_earnings" 
										   name="calc_weight_earnings" 
										   value="<?php echo esc_attr( isset( $calculation_config['weight_earnings'] ) ? $calculation_config['weight_earnings'] : 40 ); ?>" 
										   min="0" 
										   max="100" 
										   step="1"
										   class="wc-tp-calc-setting small-text" />
									<span>%</span>
								</div>
								<div class="wc-tp-weight-item">
									<label><?php esc_html_e( 'Orders Weight:', 'wc-team-payroll' ); ?></label>
									<input type="number" 
										   id="calc_weight_orders" 
										   name="calc_weight_orders" 
										   value="<?php echo esc_attr( isset( $calculation_config['weight_orders'] ) ? $calculation_config['weight_orders'] : 35 ); ?>" 
										   min="0" 
										   max="100" 
										   step="1"
										   class="wc-tp-calc-setting small-text" />
									<span>%</span>
								</div>
								<div class="wc-tp-weight-item">
									<label><?php esc_html_e( 'AOV Weight:', 'wc-team-payroll' ); ?></label>
									<input type="number" 
										   id="calc_weight_aov" 
										   name="calc_weight_aov" 
										   value="<?php echo esc_attr( isset( $calculation_config['weight_aov'] ) ? $calculation_config['weight_aov'] : 25 ); ?>" 
										   min="0" 
										   max="100" 
										   step="1"
										   class="wc-tp-calc-setting small-text" />
									<span>%</span>
								</div>
								<div class="wc-tp-weight-total">
									<strong><?php esc_html_e( 'Total:', 'wc-team-payroll' ); ?></strong>
									<span id="calc_weight_total">100</span>%
								</div>
							</div>
							<p class="description"><?php esc_html_e( 'Weights must total 100%. Adjust to emphasize different factors.', 'wc-team-payroll' ); ?></p>
						</td>
					</tr>
					
					<tr>
						<th><label for="calc_score_cap"><?php esc_html_e( 'Score Cap', 'wc-team-payroll' ); ?></label></th>
						<td>
							<input type="number" 
								   id="calc_score_cap" 
								   name="calc_score_cap" 
								   value="<?php echo esc_attr( isset( $calculation_config['score_cap'] ) ? $calculation_config['score_cap'] : 10 ); ?>" 
								   min="1" 
								   max="100" 
								   step="0.1"
								   class="wc-tp-calc-setting small-text" />
							<span class="description"><?php esc_html_e( 'Maximum possible score', 'wc-team-payroll' ); ?></span>
						</td>
					</tr>
					
					<tr>
						<th><label for="calc_rounding"><?php esc_html_e( 'Score Rounding', 'wc-team-payroll' ); ?></label></th>
						<td>
							<select id="calc_rounding" name="calc_rounding" class="wc-tp-calc-setting">
								<?php
								$rounding = isset( $calculation_config['rounding'] ) ? $calculation_config['rounding'] : 'one_decimal';
								$rounding_options = array(
									'none' => __( 'No Rounding (e.g., 7.456)', 'wc-team-payroll' ),
									'one_decimal' => __( 'One Decimal (e.g., 7.5)', 'wc-team-payroll' ),
									'two_decimals' => __( 'Two Decimals (e.g., 7.46)', 'wc-team-payroll' ),
									'whole' => __( 'Whole Number (e.g., 7)', 'wc-team-payroll' ),
								);
								foreach ( $rounding_options as $value => $label ) {
									echo '<option value="' . esc_attr( $value ) . '"' . selected( $rounding, $value, false ) . '>' . esc_html( $label ) . '</option>';
								}
								?>
							</select>
						</td>
					</tr>
				</table>
			</div>

			<!-- Metric Calculation Rules -->
			<div class="wc-tp-perf-card">
				<h4><?php esc_html_e( 'Metric Calculation Rules', 'wc-team-payroll' ); ?></h4>
				<p class="description"><?php esc_html_e( 'Configure how individual metrics (earnings, orders, AOV) are calculated.', 'wc-team-payroll' ); ?></p>
				
				<table class="form-table">
					<tr>
						<th><label for="calc_period_type"><?php esc_html_e( 'Calculation Period', 'wc-team-payroll' ); ?></label></th>
						<td>
							<select id="calc_period_type" name="calc_period_type" class="wc-tp-calc-setting">
								<?php
								$period = isset( $calculation_config['period_type'] ) ? $calculation_config['period_type'] : 'calendar_month';
								$periods = array(
									'calendar_month' => __( 'Calendar Month (1st to last day)', 'wc-team-payroll' ),
									'rolling_30' => __( 'Rolling 30 Days', 'wc-team-payroll' ),
									'custom_range' => __( 'Custom Date Range', 'wc-team-payroll' ),
								);
								foreach ( $periods as $value => $label ) {
									echo '<option value="' . esc_attr( $value ) . '"' . selected( $period, $value, false ) . '>' . esc_html( $label ) . '</option>';
								}
								?>
							</select>
							<p class="description"><?php esc_html_e( 'Time period for calculating metrics', 'wc-team-payroll' ); ?></p>
						</td>
					</tr>
					
					<tr class="wc-tp-calc-option" data-show-for="custom_range" style="<?php echo $period === 'custom_range' ? '' : 'display: none;'; ?>">
						<th><label><?php esc_html_e( 'Custom Date Range', 'wc-team-payroll' ); ?></label></th>
						<td>
							<div class="wc-tp-date-range-inputs" style="display: flex; gap: 15px; align-items: center;">
								<div>
									<label for="calc_custom_start_date" style="display: block; margin-bottom: 5px;"><?php esc_html_e( 'Start Date:', 'wc-team-payroll' ); ?></label>
									<input type="date" 
										   id="calc_custom_start_date" 
										   name="calc_custom_start_date" 
										   value="<?php echo esc_attr( isset( $calculation_config['custom_start_date'] ) ? $calculation_config['custom_start_date'] : '' ); ?>" 
										   class="wc-tp-calc-setting" />
								</div>
								<div>
									<label for="calc_custom_end_date" style="display: block; margin-bottom: 5px;"><?php esc_html_e( 'End Date:', 'wc-team-payroll' ); ?></label>
									<input type="date" 
										   id="calc_custom_end_date" 
										   name="calc_custom_end_date" 
										   value="<?php echo esc_attr( isset( $calculation_config['custom_end_date'] ) ? $calculation_config['custom_end_date'] : '' ); ?>" 
										   class="wc-tp-calc-setting" />
								</div>
							</div>
							<p class="description"><?php esc_html_e( 'Select the start and end dates for the custom calculation period.', 'wc-team-payroll' ); ?></p>
						</td>
					</tr>
					
					<tr>
						<th><label for="calc_revenue_attribution"><?php esc_html_e( 'Revenue Attribution Method', 'wc-team-payroll' ); ?></label></th>
						<td>
							<select id="calc_revenue_attribution" name="calc_revenue_attribution" class="wc-tp-calc-setting">
								<?php
								$attribution_method = isset( $calculation_config['revenue_attribution'] ) ? $calculation_config['revenue_attribution'] : 'full';
								$attribution_methods = array(
									'full' => __( 'Full Order Value (No Split)', 'wc-team-payroll' ),
									'commission_split' => __( 'Use Commission Split Percentages', 'wc-team-payroll' ),
								);
								foreach ( $attribution_methods as $value => $label ) {
									echo '<option value="' . esc_attr( $value ) . '"' . selected( $attribution_method, $value, false ) . '>' . esc_html( $label ) . '</option>';
								}
								?>
							</select>
							<p class="description"><?php esc_html_e( 'How to attribute order values when multiple roles (Agent + Processor) are assigned to the same order.', 'wc-team-payroll' ); ?></p>
							
							<div class="wc-tp-attribution-explanation" style="margin-top: 10px; padding: 12px; background: #f0f6fc; border: 1px solid #c3dcf0; border-radius: 4px;">
								<div class="wc-tp-attribution-option" data-method="full">
									<strong><?php esc_html_e( '📊 Full Order Value:', 'wc-team-payroll' ); ?></strong><br>
									<span style="color: #d63638;"><?php esc_html_e( '⚠️ Warning: May cause double-counting if same order has Agent + Processor', 'wc-team-payroll' ); ?></span><br>
									<em><?php esc_html_e( 'Example: $1,000 order → Agent gets $1,000 credit + Processor gets $1,000 credit = $2,000 total (inflated)', 'wc-team-payroll' ); ?></em>
								</div>
								
								<div class="wc-tp-attribution-option" data-method="commission_split" style="display: none;">
									<strong><?php esc_html_e( '🎯 Commission Split Method:', 'wc-team-payroll' ); ?></strong><br>
									<span style="color: #00a32a;"><?php esc_html_e( '✅ Prevents double-counting by using commission percentages', 'wc-team-payroll' ); ?></span><br>
									<?php esc_html_e( 'Uses the same percentage splits configured in', 'wc-team-payroll' ); ?> 
									<a href="?page=wc-team-payroll-settings&tab=commission" target="_blank" style="text-decoration: none;">
										<strong><?php esc_html_e( 'Settings → Commission', 'wc-team-payroll' ); ?></strong>
									</a><br>
									<em><?php esc_html_e( 'Example: If Agent gets 70% commission and Processor gets 30%, then for $1,000 order → Agent gets $700 credit + Processor gets $300 credit = $1,000 total (accurate)', 'wc-team-payroll' ); ?></em>
								</div>
							</div>
						</td>
					</tr>
					
					<tr>
						<th><label><?php esc_html_e( 'Order Statuses', 'wc-team-payroll' ); ?></label></th>
						<td>
							<p class="description">
								<?php esc_html_e( 'Order statuses are configured in', 'wc-team-payroll' ); ?> 
								<a href="?page=wc-team-payroll-settings&tab=woocommerce" target="_blank">
									<?php esc_html_e( 'Settings → WooCommerce → Order Status Configuration', 'wc-team-payroll' ); ?>
								</a>
							</p>
							<p class="description">
								<?php esc_html_e( 'Only orders with the statuses selected in WooCommerce settings will count toward performance metrics.', 'wc-team-payroll' ); ?>
							</p>
						</td>
					</tr>
					
					<tr>
						<th><label for="calc_exclude_refunds"><?php esc_html_e( 'Exclude Refunds', 'wc-team-payroll' ); ?></label></th>
						<td>
							<input type="checkbox" 
								   id="calc_exclude_refunds" 
								   name="calc_exclude_refunds" 
								   value="1" 
								   class="wc-tp-calc-setting" 
								   <?php checked( isset( $calculation_config['exclude_refunds'] ) ? $calculation_config['exclude_refunds'] : 1, 1 ); ?> />
							<label for="calc_exclude_refunds"><?php esc_html_e( 'Subtract refunded amounts from earnings', 'wc-team-payroll' ); ?></label>
						</td>
					</tr>
					
					<tr>
						<th><label for="calc_aov_method"><?php esc_html_e( 'AOV Calculation', 'wc-team-payroll' ); ?></label></th>
						<td>
							<select id="calc_aov_method" name="calc_aov_method" class="wc-tp-calc-setting">
								<?php
								$aov_method = isset( $calculation_config['aov_method'] ) ? $calculation_config['aov_method'] : 'total_divided_orders';
								$aov_methods = array(
									'total_divided_orders' => __( 'Total Earnings ÷ Total Orders', 'wc-team-payroll' ),
									'average_of_orders' => __( 'Average of Individual Order Values', 'wc-team-payroll' ),
								);
								foreach ( $aov_methods as $value => $label ) {
									echo '<option value="' . esc_attr( $value ) . '"' . selected( $aov_method, $value, false ) . '>' . esc_html( $label ) . '</option>';
								}
								?>
							</select>
							<p class="description"><?php esc_html_e( 'How average order value is calculated', 'wc-team-payroll' ); ?></p>
						</td>
					</tr>
				</table>
			</div>

			<!-- Formula Tester -->
			<div class="wc-tp-perf-card wc-tp-formula-tester-card">
				<h4><?php esc_html_e( 'Formula Tester', 'wc-team-payroll' ); ?></h4>
				<p class="description"><?php esc_html_e( 'Test how your calculation settings affect the final performance score.', 'wc-team-payroll' ); ?></p>
				
				<div class="wc-tp-formula-tester">
					<div class="wc-tp-formula-inputs">
						<div class="wc-tp-formula-input-group">
							<label><?php esc_html_e( 'Base Score:', 'wc-team-payroll' ); ?></label>
							<input type="number" id="test_base_score" value="5" step="0.1" min="0" max="10" />
						</div>
						<div class="wc-tp-formula-input-group">
							<label><?php esc_html_e( 'Earnings Points:', 'wc-team-payroll' ); ?></label>
							<input type="number" id="test_earnings_points" value="2.0" step="0.1" min="0" max="10" />
						</div>
						<div class="wc-tp-formula-input-group">
							<label><?php esc_html_e( 'Orders Points:', 'wc-team-payroll' ); ?></label>
							<input type="number" id="test_orders_points" value="1.5" step="0.1" min="0" max="10" />
						</div>
						<div class="wc-tp-formula-input-group">
							<label><?php esc_html_e( 'AOV Points:', 'wc-team-payroll' ); ?></label>
							<input type="number" id="test_aov_points" value="0.5" step="0.1" min="0" max="10" />
						</div>
						<button type="button" class="button button-primary" id="wc-tp-test-formula">
							<span class="dashicons dashicons-calculator"></span>
							<?php esc_html_e( 'Calculate Score', 'wc-team-payroll' ); ?>
						</button>
					</div>
					
					<div class="wc-tp-formula-result" style="display: none;">
						<div class="wc-tp-formula-breakdown">
							<h5><?php esc_html_e( 'Calculation Breakdown', 'wc-team-payroll' ); ?></h5>
							<div class="wc-tp-formula-steps" id="formula_steps"></div>
							<div class="wc-tp-formula-final">
								<strong><?php esc_html_e( 'Final Score:', 'wc-team-payroll' ); ?></strong>
								<span id="formula_final_score">0</span>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render System Configuration Section
	 */
	private function render_system_section() {
		$system_config = get_option( 'wc_tp_system_config', array() );
		?>
		<div class="wc-tp-perf-system-wrapper">
			<h3><?php esc_html_e( 'System Configuration & Preferences', 'wc-team-payroll' ); ?></h3>
			<p class="description"><?php esc_html_e( 'Global settings and preferences for the performance system. These settings affect all roles and employees.', 'wc-team-payroll' ); ?></p>
			
			<!-- General System Settings -->
			<div class="wc-tp-perf-card">
				<h4><?php esc_html_e( 'General System Settings', 'wc-team-payroll' ); ?></h4>
				
				<table class="form-table">
					<tr>
						<th><label for="system_enable_performance"><?php esc_html_e( 'Enable Performance System', 'wc-team-payroll' ); ?></label></th>
						<td>
							<input type="checkbox" 
								   id="system_enable_performance" 
								   name="system_enable_performance" 
								   value="1" 
								   class="wc-tp-system-setting" 
								   <?php checked( isset( $system_config['enable_performance'] ) ? $system_config['enable_performance'] : 1, 1 ); ?> />
							<label for="system_enable_performance"><?php esc_html_e( 'Enable the entire performance tracking system', 'wc-team-payroll' ); ?></label>
							<p class="description"><?php esc_html_e( 'Turn off to disable all performance features site-wide', 'wc-team-payroll' ); ?></p>
						</td>
					</tr>
				</table>
			</div>

			<!-- Reports Page Settings -->
			<div class="wc-tp-perf-card">
				<h4><?php esc_html_e( 'Reports Page Settings', 'wc-team-payroll' ); ?></h4>
				<p class="description"><?php esc_html_e( 'Configure what employees see on their reports page.', 'wc-team-payroll' ); ?></p>
				
				<table class="form-table">
					<tr>
						<th><label for="system_show_score"><?php esc_html_e( 'Show Performance Score', 'wc-team-payroll' ); ?></label></th>
						<td>
							<input type="checkbox" 
								   id="system_show_score" 
								   name="system_show_score" 
								   value="1" 
								   class="wc-tp-system-setting" 
								   <?php checked( isset( $system_config['show_score'] ) ? $system_config['show_score'] : 1, 1 ); ?> />
							<label for="system_show_score"><?php esc_html_e( 'Display performance score on reports page', 'wc-team-payroll' ); ?></label>
						</td>
					</tr>
					
					<tr>
						<th><label for="system_show_goals"><?php esc_html_e( 'Show Goals', 'wc-team-payroll' ); ?></label></th>
						<td>
							<input type="checkbox" 
								   id="system_show_goals" 
								   name="system_show_goals" 
								   value="1" 
								   class="wc-tp-system-setting" 
								   <?php checked( isset( $system_config['show_goals'] ) ? $system_config['show_goals'] : 1, 1 ); ?> />
							<label for="system_show_goals"><?php esc_html_e( 'Display goals and progress', 'wc-team-payroll' ); ?></label>
						</td>
					</tr>
					
					<tr>
						<th><label for="system_show_achievements"><?php esc_html_e( 'Show Achievements', 'wc-team-payroll' ); ?></label></th>
						<td>
							<input type="checkbox" 
								   id="system_show_achievements" 
								   name="system_show_achievements" 
								   value="1" 
								   class="wc-tp-system-setting" 
								   <?php checked( isset( $system_config['show_achievements'] ) ? $system_config['show_achievements'] : 1, 1 ); ?> />
							<label for="system_show_achievements"><?php esc_html_e( 'Display earned achievements and badges', 'wc-team-payroll' ); ?></label>
						</td>
					</tr>
					
					<tr>
						<th><label for="system_show_baselines"><?php esc_html_e( 'Show Baselines', 'wc-team-payroll' ); ?></label></th>
						<td>
							<input type="checkbox" 
								   id="system_show_baselines" 
								   name="system_show_baselines" 
								   value="1" 
								   class="wc-tp-system-setting" 
								   <?php checked( isset( $system_config['show_baselines'] ) ? $system_config['show_baselines'] : 1, 1 ); ?> />
							<label for="system_show_baselines"><?php esc_html_e( 'Display baseline comparisons', 'wc-team-payroll' ); ?></label>
						</td>
					</tr>
					
					<tr>
						<th><label for="system_show_leaderboard"><?php esc_html_e( 'Show Leaderboard', 'wc-team-payroll' ); ?></label></th>
						<td>
							<input type="checkbox" 
								   id="system_show_leaderboard" 
								   name="system_show_leaderboard" 
								   value="1" 
								   class="wc-tp-system-setting" 
								   <?php checked( isset( $system_config['show_leaderboard'] ) ? $system_config['show_leaderboard'] : 0, 1 ); ?> />
							<label for="system_show_leaderboard"><?php esc_html_e( 'Display team leaderboard (rankings)', 'wc-team-payroll' ); ?></label>
							<p class="description"><?php esc_html_e( 'Shows how employees rank compared to teammates', 'wc-team-payroll' ); ?></p>
						</td>
					</tr>
					
					<tr>
						<th><label for="system_refresh_interval"><?php esc_html_e( 'Auto-Refresh Interval', 'wc-team-payroll' ); ?></label></th>
						<td>
							<input type="number" 
								   id="system_refresh_interval" 
								   name="system_refresh_interval" 
								   value="<?php echo esc_attr( isset( $system_config['refresh_interval'] ) ? $system_config['refresh_interval'] : 30 ); ?>" 
								   min="0" 
								   max="300" 
								   step="5"
								   class="wc-tp-system-setting small-text" />
							<span class="description"><?php esc_html_e( 'seconds (0 = disabled)', 'wc-team-payroll' ); ?></span>
							<p class="description"><?php esc_html_e( 'How often the reports page auto-refreshes data', 'wc-team-payroll' ); ?></p>
						</td>
					</tr>
				</table>
			</div>

			<!-- Data & Privacy Settings -->
			<div class="wc-tp-perf-card">
				<h4><?php esc_html_e( 'Data & Privacy Settings', 'wc-team-payroll' ); ?></h4>
				
				<table class="form-table">
					<tr>
						<th><label for="system_data_retention"><?php esc_html_e( 'Data Retention Period', 'wc-team-payroll' ); ?></label></th>
						<td>
							<select id="system_data_retention" name="system_data_retention" class="wc-tp-system-setting">
								<?php
								$retention = isset( $system_config['data_retention'] ) ? $system_config['data_retention'] : 'forever';
								$retention_options = array(
									'30_days' => __( '30 Days', 'wc-team-payroll' ),
									'90_days' => __( '90 Days', 'wc-team-payroll' ),
									'6_months' => __( '6 Months', 'wc-team-payroll' ),
									'1_year' => __( '1 Year', 'wc-team-payroll' ),
									'2_years' => __( '2 Years', 'wc-team-payroll' ),
									'forever' => __( 'Forever', 'wc-team-payroll' ),
								);
								foreach ( $retention_options as $value => $label ) {
									echo '<option value="' . esc_attr( $value ) . '"' . selected( $retention, $value, false ) . '>' . esc_html( $label ) . '</option>';
								}
								?>
							</select>
							<p class="description"><?php esc_html_e( 'How long to keep historical performance data', 'wc-team-payroll' ); ?></p>
						</td>
					</tr>
					
					<tr>
						<th><label for="system_anonymize_data"><?php esc_html_e( 'Anonymize Leaderboard', 'wc-team-payroll' ); ?></label></th>
						<td>
							<input type="checkbox" 
								   id="system_anonymize_data" 
								   name="system_anonymize_data" 
								   value="1" 
								   class="wc-tp-system-setting" 
								   <?php checked( isset( $system_config['anonymize_data'] ) ? $system_config['anonymize_data'] : 0, 1 ); ?> />
							<label for="system_anonymize_data"><?php esc_html_e( 'Show initials instead of full names on leaderboard', 'wc-team-payroll' ); ?></label>
						</td>
					</tr>
					
					<tr>
						<th><label for="system_cache_duration"><?php esc_html_e( 'Cache Duration', 'wc-team-payroll' ); ?></label></th>
						<td>
							<input type="number" 
								   id="system_cache_duration" 
								   name="system_cache_duration" 
								   value="<?php echo esc_attr( isset( $system_config['cache_duration'] ) ? $system_config['cache_duration'] : 300 ); ?>" 
								   min="0" 
								   max="3600" 
								   step="60"
								   class="wc-tp-system-setting small-text" />
							<span class="description"><?php esc_html_e( 'seconds (0 = no cache)', 'wc-team-payroll' ); ?></span>
							<p class="description"><?php esc_html_e( 'How long to cache performance calculations for better performance', 'wc-team-payroll' ); ?></p>
						</td>
					</tr>
				</table>
			</div>

			<!-- Advanced Settings -->
			<div class="wc-tp-perf-card">
				<h4><?php esc_html_e( 'Advanced Settings', 'wc-team-payroll' ); ?></h4>
				
				<table class="form-table">
					<tr>
						<th><label for="system_debug_mode"><?php esc_html_e( 'Debug Mode', 'wc-team-payroll' ); ?></label></th>
						<td>
							<input type="checkbox" 
								   id="system_debug_mode" 
								   name="system_debug_mode" 
								   value="1" 
								   class="wc-tp-system-setting" 
								   <?php checked( isset( $system_config['debug_mode'] ) ? $system_config['debug_mode'] : 0, 1 ); ?> />
							<label for="system_debug_mode"><?php esc_html_e( 'Enable debug logging for troubleshooting', 'wc-team-payroll' ); ?></label>
							<p class="description"><?php esc_html_e( 'Logs performance calculations to WordPress debug log', 'wc-team-payroll' ); ?></p>
						</td>
					</tr>
					
					<tr>
						<th><label><?php esc_html_e( 'Reset All Data', 'wc-team-payroll' ); ?></label></th>
						<td>
							<button type="button" class="button button-secondary" id="wc-tp-reset-all-data">
								<span class="dashicons dashicons-warning"></span>
								<?php esc_html_e( 'Reset All Performance Data', 'wc-team-payroll' ); ?>
							</button>
							<p class="description" style="color: #d63638;"><?php esc_html_e( 'WARNING: This will delete all performance configurations, scores, goals, achievements, and baselines. This action cannot be undone!', 'wc-team-payroll' ); ?></p>
						</td>
					</tr>
				</table>
			</div>

			<!-- System Status -->
			<div class="wc-tp-perf-card wc-tp-system-status-card">
				<h4><?php esc_html_e( 'System Status', 'wc-team-payroll' ); ?></h4>
				
				<div class="wc-tp-system-status">
					<div class="wc-tp-status-item">
						<span class="wc-tp-status-label"><?php esc_html_e( 'Performance System:', 'wc-team-payroll' ); ?></span>
						<span class="wc-tp-status-value wc-tp-status-active"><?php esc_html_e( 'Active', 'wc-team-payroll' ); ?></span>
					</div>
					<div class="wc-tp-status-item">
						<span class="wc-tp-status-label"><?php esc_html_e( 'Total Employees:', 'wc-team-payroll' ); ?></span>
						<span class="wc-tp-status-value"><?php echo esc_html( count( get_users( array( 'role__in' => array( 'shop_employee', 'shop_manager' ) ) ) ) ); ?></span>
					</div>
					<div class="wc-tp-status-item">
						<span class="wc-tp-status-label"><?php esc_html_e( 'Configured Roles:', 'wc-team-payroll' ); ?></span>
						<span class="wc-tp-status-value"><?php echo esc_html( count( get_option( 'wc_tp_performance_config', array() ) ) ); ?></span>
					</div>
					<div class="wc-tp-status-item">
						<span class="wc-tp-status-label"><?php esc_html_e( 'Last Updated:', 'wc-team-payroll' ); ?></span>
						<span class="wc-tp-status-value"><?php echo esc_html( date( 'Y-m-d H:i:s' ) ); ?></span>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Get employee roles from settings (not all WordPress roles)
	 */
	private function get_all_roles() {
		// Get employee roles from WooCommerce settings (agent_user_roles)
		$checkout_fields = get_option( 'wc_team_payroll_checkout_fields', array() );
		$employee_roles = isset( $checkout_fields['agent_user_roles'] ) && is_array( $checkout_fields['agent_user_roles'] ) 
			? $checkout_fields['agent_user_roles'] 
			: array( 'shop_employee', 'shop_manager', 'administrator' );
		
		// Get WordPress roles for display names
		global $wp_roles;
		if ( ! isset( $wp_roles ) ) {
			$wp_roles = wp_roles();
		}
		
		$all_wp_roles = $wp_roles->get_names();
		$filtered_roles = array();
		
		// Only include roles that are configured as employee roles
		foreach ( $employee_roles as $role_key ) {
			if ( isset( $all_wp_roles[ $role_key ] ) ) {
				$filtered_roles[ $role_key ] = $all_wp_roles[ $role_key ];
			} else {
				// If role doesn't exist in WordPress, use the role key as display name
				$filtered_roles[ $role_key ] = ucwords( str_replace( '_', ' ', $role_key ) );
			}
		}
		
		return $filtered_roles;
	}

	/**
	 * AJAX: Save performance configuration
	 */
	public function ajax_save_performance_config() {
		check_ajax_referer( 'wc_tp_performance_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'wc-team-payroll' ) ) );
		}

		$config = isset( $_POST['config'] ) ? $_POST['config'] : array();
		
		// Get existing configuration
		$existing_config = get_option( 'wc_tp_performance_config', array() );
		
		// Merge with new data
		if ( isset( $config['base_score'] ) ) {
			$existing_config['base_score'] = floatval( $config['base_score'] );
		}
		
		if ( isset( $config['roles'] ) && is_array( $config['roles'] ) ) {
			if ( ! isset( $existing_config['roles'] ) ) {
				$existing_config['roles'] = array();
			}
			
			foreach ( $config['roles'] as $role => $role_config ) {
				$existing_config['roles'][ $role ] = $this->sanitize_role_config( $role_config );
			}
		}
		
		// Save configuration
		update_option( 'wc_tp_performance_config', $existing_config );

		wp_send_json_success( array( 'message' => __( 'Configuration saved successfully!', 'wc-team-payroll' ) ) );
	}

	/**
	 * Sanitize role configuration
	 */
	private function sanitize_role_config( $role_config ) {
		$sanitized = array();
		
		// Sanitize earnings ranges
		if ( isset( $role_config['earnings_ranges'] ) && is_array( $role_config['earnings_ranges'] ) ) {
			$sanitized['earnings_ranges'] = array();
			foreach ( $role_config['earnings_ranges'] as $range ) {
				if ( is_array( $range ) ) {
					$sanitized['earnings_ranges'][] = array(
						'min' => floatval( $range['min'] ),
						'max' => floatval( $range['max'] ),
						'points' => floatval( $range['points'] ),
					);
				}
			}
		}
		
		// Sanitize orders ranges
		if ( isset( $role_config['orders_ranges'] ) && is_array( $role_config['orders_ranges'] ) ) {
			$sanitized['orders_ranges'] = array();
			foreach ( $role_config['orders_ranges'] as $range ) {
				if ( is_array( $range ) ) {
					$sanitized['orders_ranges'][] = array(
						'min' => intval( $range['min'] ),
						'max' => intval( $range['max'] ),
						'points' => floatval( $range['points'] ),
					);
				}
			}
		}
		
		// Sanitize AOV ranges
		if ( isset( $role_config['aov_ranges'] ) && is_array( $role_config['aov_ranges'] ) ) {
			$sanitized['aov_ranges'] = array();
			foreach ( $role_config['aov_ranges'] as $range ) {
				if ( is_array( $range ) ) {
					$sanitized['aov_ranges'][] = array(
						'min' => floatval( $range['min'] ),
						'max' => floatval( $range['max'] ),
						'points' => floatval( $range['points'] ),
					);
				}
			}
		}
		
		return $sanitized;
	}

	/**
	 * AJAX: Get role configuration
	 */
	public function ajax_get_role_config() {
		check_ajax_referer( 'wc_tp_performance_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'wc-team-payroll' ) ) );
		}

		$role = isset( $_POST['role'] ) ? sanitize_text_field( $_POST['role'] ) : '';
		
		if ( empty( $role ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid role', 'wc-team-payroll' ) ) );
		}

		$performance_config = get_option( 'wc_tp_performance_config', array() );
		$role_config = isset( $performance_config['roles'][ $role ] ) ? $performance_config['roles'][ $role ] : $this->get_default_role_config();

		ob_start();
		$this->render_role_config_form( $role, $role_config );
		$html = ob_get_clean();

		wp_send_json_success( array( 'html' => $html ) );
	}

	/**
	 * AJAX: Get role goals configuration
	 */
	public function ajax_get_role_goals() {
		check_ajax_referer( 'wc_tp_performance_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'wc-team-payroll' ) ) );
		}

		$role = isset( $_POST['role'] ) ? sanitize_text_field( $_POST['role'] ) : '';
		
		if ( empty( $role ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid role', 'wc-team-payroll' ) ) );
		}

		$goals_config = get_option( 'wc_tp_goals_config', array() );
		$role_goals = isset( $goals_config['roles'][ $role ] ) ? $goals_config['roles'][ $role ] : $this->get_default_role_goals();

		ob_start();
		$this->render_role_goals_form( $role, $role_goals );
		$html = ob_get_clean();

		wp_send_json_success( array( 'html' => $html ) );
	}

	/**
	 * AJAX: Save goals configuration
	 */
	public function ajax_save_goals_config() {
		check_ajax_referer( 'wc_tp_performance_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'wc-team-payroll' ) ) );
		}

		$config = isset( $_POST['config'] ) ? $_POST['config'] : array();
		
		// Get existing configuration
		$existing_config = get_option( 'wc_tp_goals_config', array() );
		
		// Update global settings
		if ( isset( $config['period'] ) ) {
			$existing_config['period'] = sanitize_text_field( $config['period'] );
		}
		if ( isset( $config['display_mode'] ) ) {
			$existing_config['display_mode'] = sanitize_text_field( $config['display_mode'] );
		}
		if ( isset( $config['show_stretch'] ) ) {
			$existing_config['show_stretch'] = intval( $config['show_stretch'] );
		}
		
		// Update role-specific goals
		if ( isset( $config['roles'] ) && is_array( $config['roles'] ) ) {
			if ( ! isset( $existing_config['roles'] ) ) {
				$existing_config['roles'] = array();
			}
			
			foreach ( $config['roles'] as $role => $role_goals ) {
				$existing_config['roles'][ $role ] = $this->sanitize_role_goals( $role_goals );
			}
		}
		
		// Save configuration
		update_option( 'wc_tp_goals_config', $existing_config );

		wp_send_json_success( array( 'message' => __( 'Goals configuration saved successfully!', 'wc-team-payroll' ) ) );
	}

	/**
	 * AJAX: Clone role goals configuration
	 */
	public function ajax_clone_role_goals() {
		check_ajax_referer( 'wc_tp_performance_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'wc-team-payroll' ) ) );
		}

		$from_role = isset( $_POST['from_role'] ) ? sanitize_text_field( $_POST['from_role'] ) : '';
		$to_role = isset( $_POST['to_role'] ) ? sanitize_text_field( $_POST['to_role'] ) : '';

		if ( empty( $from_role ) || empty( $to_role ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid roles', 'wc-team-payroll' ) ) );
		}

		$goals_config = get_option( 'wc_tp_goals_config', array() );
		
		if ( isset( $goals_config['roles'][ $from_role ] ) ) {
			$goals_config['roles'][ $to_role ] = $goals_config['roles'][ $from_role ];
			update_option( 'wc_tp_goals_config', $goals_config );
			wp_send_json_success( array( 'message' => __( 'Goals configuration cloned successfully!', 'wc-team-payroll' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Source role goals not found', 'wc-team-payroll' ) ) );
		}
	}

	/**
	 * AJAX: Get role achievements configuration
	 */
	public function ajax_get_role_achievements() {
		check_ajax_referer( 'wc_tp_performance_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'wc-team-payroll' ) ) );
		}

		$role = isset( $_POST['role'] ) ? sanitize_text_field( $_POST['role'] ) : '';
		
		if ( empty( $role ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid role', 'wc-team-payroll' ) ) );
		}

		$achievements_config = get_option( 'wc_tp_achievements_config', array() );
		$role_achievements = isset( $achievements_config['roles'][ $role ] ) ? $achievements_config['roles'][ $role ] : $this->get_default_role_achievements();

		ob_start();
		$this->render_role_achievements_form( $role, $role_achievements );
		$html = ob_get_clean();

		wp_send_json_success( array( 'html' => $html ) );
	}

	/**
	 * AJAX: Save achievements configuration
	 */
	public function ajax_save_achievements_config() {
		check_ajax_referer( 'wc_tp_performance_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'wc-team-payroll' ) ) );
		}

		$config = isset( $_POST['config'] ) ? $_POST['config'] : array();
		
		// Get existing configuration
		$existing_config = get_option( 'wc_tp_achievements_config', array() );
		
		// Update global settings
		if ( isset( $config['enabled'] ) ) {
			$existing_config['enabled'] = intval( $config['enabled'] );
		}
		if ( isset( $config['display_style'] ) ) {
			$existing_config['display_style'] = sanitize_text_field( $config['display_style'] );
		}
		if ( isset( $config['show_locked'] ) ) {
			$existing_config['show_locked'] = intval( $config['show_locked'] );
		}
		if ( isset( $config['notification'] ) ) {
			$existing_config['notification'] = intval( $config['notification'] );
		}
		
		// Update role-specific achievements
		if ( isset( $config['roles'] ) && is_array( $config['roles'] ) ) {
			if ( ! isset( $existing_config['roles'] ) ) {
				$existing_config['roles'] = array();
			}
			
			foreach ( $config['roles'] as $role => $role_achievements ) {
				$existing_config['roles'][ $role ] = $this->sanitize_role_achievements( $role_achievements );
			}
		}
		
		// Save configuration
		update_option( 'wc_tp_achievements_config', $existing_config );

		wp_send_json_success( array( 'message' => __( 'Achievements configuration saved successfully!', 'wc-team-payroll' ) ) );
	}

	/**
	 * AJAX: Clone role achievements configuration
	 */
	public function ajax_clone_role_achievements() {
		check_ajax_referer( 'wc_tp_performance_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'wc-team-payroll' ) ) );
		}

		$from_role = isset( $_POST['from_role'] ) ? sanitize_text_field( $_POST['from_role'] ) : '';
		$to_role = isset( $_POST['to_role'] ) ? sanitize_text_field( $_POST['to_role'] ) : '';

		if ( empty( $from_role ) || empty( $to_role ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid roles', 'wc-team-payroll' ) ) );
		}

		$achievements_config = get_option( 'wc_tp_achievements_config', array() );
		
		if ( isset( $achievements_config['roles'][ $from_role ] ) ) {
			$achievements_config['roles'][ $to_role ] = $achievements_config['roles'][ $from_role ];
			update_option( 'wc_tp_achievements_config', $achievements_config );
			wp_send_json_success( array( 'message' => __( 'Achievements configuration cloned successfully!', 'wc-team-payroll' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Source role achievements not found', 'wc-team-payroll' ) ) );
		}
	}

	/**
	 * AJAX: Save baselines configuration
	 */
	public function ajax_save_baselines_config() {
		check_ajax_referer( 'wc_tp_performance_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'wc-team-payroll' ) ) );
		}

		$config = isset( $_POST['config'] ) ? $_POST['config'] : array();
		
		// Sanitize configuration
		$sanitized_config = array(
			'method' => isset( $config['method'] ) ? sanitize_text_field( $config['method'] ) : 'rolling_average',
			'periods' => isset( $config['periods'] ) ? intval( $config['periods'] ) : 3,
			'percentile' => isset( $config['percentile'] ) ? intval( $config['percentile'] ) : 75,
			'update_frequency' => isset( $config['update_frequency'] ) ? sanitize_text_field( $config['update_frequency'] ) : 'monthly',
			'minimum_data' => isset( $config['minimum_data'] ) ? intval( $config['minimum_data'] ) : 5,
			'show_comparison' => isset( $config['show_comparison'] ) ? intval( $config['show_comparison'] ) : 1,
			'show_trend' => isset( $config['show_trend'] ) ? intval( $config['show_trend'] ) : 1,
			'show_history' => isset( $config['show_history'] ) ? intval( $config['show_history'] ) : 0,
			'comparison_format' => isset( $config['comparison_format'] ) ? sanitize_text_field( $config['comparison_format'] ) : 'percentage',
		);
		
		// Save configuration
		update_option( 'wc_tp_baselines_config', $sanitized_config );

		wp_send_json_success( array( 'message' => __( 'Baselines configuration saved successfully!', 'wc-team-payroll' ) ) );
	}

	/**
	 * AJAX: Calculate baseline preview
	 */
	public function ajax_calculate_baseline_preview() {
		check_ajax_referer( 'wc_tp_performance_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'wc-team-payroll' ) ) );
		}

		$method = isset( $_POST['method'] ) ? sanitize_text_field( $_POST['method'] ) : 'rolling_average';
		$periods = isset( $_POST['periods'] ) ? intval( $_POST['periods'] ) : 3;
		$percentile = isset( $_POST['percentile'] ) ? intval( $_POST['percentile'] ) : 75;
		
		$earnings_data = isset( $_POST['earnings_data'] ) ? array_map( 'floatval', $_POST['earnings_data'] ) : array();
		$orders_data = isset( $_POST['orders_data'] ) ? array_map( 'intval', $_POST['orders_data'] ) : array();
		$aov_data = isset( $_POST['aov_data'] ) ? array_map( 'floatval', $_POST['aov_data'] ) : array();

		// Calculate baselines
		$earnings_baseline = $this->calculate_baseline( $earnings_data, $method, $periods, $percentile );
		$orders_baseline = $this->calculate_baseline( $orders_data, $method, $periods, $percentile );
		$aov_baseline = $this->calculate_baseline( $aov_data, $method, $periods, $percentile );

		wp_send_json_success( array(
			'earnings' => $earnings_baseline,
			'orders' => $orders_baseline,
			'aov' => $aov_baseline,
		) );
	}

	/**
	 * Calculate baseline from data array
	 */
	private function calculate_baseline( $data, $method, $periods, $percentile ) {
		if ( empty( $data ) ) {
			return 0;
		}

		switch ( $method ) {
			case 'rolling_average':
				$slice = array_slice( $data, -$periods );
				return array_sum( $slice ) / count( $slice );

			case 'historical_average':
				return array_sum( $data ) / count( $data );

			case 'best_period':
				return max( $data );

			case 'median':
				sort( $data );
				$count = count( $data );
				$middle = floor( ( $count - 1 ) / 2 );
				if ( $count % 2 ) {
					return $data[ $middle ];
				} else {
					return ( $data[ $middle ] + $data[ $middle + 1 ] ) / 2;
				}

			case 'percentile':
				sort( $data );
				$index = ( $percentile / 100 ) * ( count( $data ) - 1 );
				$lower = floor( $index );
				$upper = ceil( $index );
				if ( $lower == $upper ) {
					return $data[ $lower ];
				} else {
					return $data[ $lower ] * ( $upper - $index ) + $data[ $upper ] * ( $index - $lower );
				}

			case 'manual':
			default:
				return array_sum( $data ) / count( $data );
		}
	}

	/**
	 * Calculate employee earnings with revenue attribution
	 * 
	 * @param int $employee_id Employee user ID
	 * @param string $start_date Start date (Y-m-d format)
	 * @param string $end_date End date (Y-m-d format)
	 * @return float Total attributed earnings
	 */
	public function calculate_employee_earnings_with_attribution( $employee_id, $start_date, $end_date ) {
		$calculation_config = get_option( 'wc_tp_calculation_config', array() );
		$revenue_attribution = isset( $calculation_config['revenue_attribution'] ) ? $calculation_config['revenue_attribution'] : 'full';
		
		if ( $revenue_attribution === 'commission_split' ) {
			return $this->calculate_earnings_with_commission_split( $employee_id, $start_date, $end_date );
		} else {
			return $this->calculate_earnings_full_value( $employee_id, $start_date, $end_date );
		}
	}

	/**
	 * Calculate earnings using commission split percentages
	 * 
	 * @param int $employee_id Employee user ID
	 * @param string $start_date Start date (Y-m-d format)
	 * @param string $end_date End date (Y-m-d format)
	 * @return float Total attributed earnings
	 */
	private function calculate_earnings_with_commission_split( $employee_id, $start_date, $end_date ) {
		// Get commission calculation statuses
		$commission_statuses = WC_Team_Payroll_Core_Engine::get_commission_calculation_statuses();
		
		// Get orders for the period
		$args = array(
			'limit'        => -1,
			'date_created' => $start_date . '...' . $end_date,
			'status'       => $commission_statuses,
			'meta_query'   => array(
				'relation' => 'OR',
				array(
					'key'     => '_wc_tp_agent_id',
					'value'   => $employee_id,
					'compare' => '='
				),
				array(
					'key'     => '_wc_tp_processor_id',
					'value'   => $employee_id,
					'compare' => '='
				)
			)
		);
		
		$orders = wc_get_orders( $args );
		$total_attributed_earnings = 0;
		
		foreach ( $orders as $order ) {
			$order_total = $order->get_total();
			$agent_id = get_post_meta( $order->get_id(), '_wc_tp_agent_id', true );
			$processor_id = get_post_meta( $order->get_id(), '_wc_tp_processor_id', true );
			
			// Get employee's attribution percentage for this order
			$attribution_percentage = $this->get_employee_attribution_percentage( 
				$order->get_id(), 
				$employee_id, 
				$agent_id, 
				$processor_id 
			);
			
			// Apply attribution percentage to order total
			$attributed_amount = $order_total * ( $attribution_percentage / 100 );
			$total_attributed_earnings += $attributed_amount;
		}
		
		return $total_attributed_earnings;
	}

	/**
	 * Calculate earnings using full order value (original method)
	 * 
	 * @param int $employee_id Employee user ID
	 * @param string $start_date Start date (Y-m-d format)
	 * @param string $end_date End date (Y-m-d format)
	 * @return float Total earnings
	 */
	private function calculate_earnings_full_value( $employee_id, $start_date, $end_date ) {
		// Get commission calculation statuses
		$commission_statuses = WC_Team_Payroll_Core_Engine::get_commission_calculation_statuses();
		
		// Get orders for the period
		$args = array(
			'limit'        => -1,
			'date_created' => $start_date . '...' . $end_date,
			'status'       => $commission_statuses,
			'meta_query'   => array(
				'relation' => 'OR',
				array(
					'key'     => '_wc_tp_agent_id',
					'value'   => $employee_id,
					'compare' => '='
				),
				array(
					'key'     => '_wc_tp_processor_id',
					'value'   => $employee_id,
					'compare' => '='
				)
			)
		);
		
		$orders = wc_get_orders( $args );
		$total_earnings = 0;
		
		foreach ( $orders as $order ) {
			$total_earnings += $order->get_total();
		}
		
		return $total_earnings;
	}

	/**
	 * Get employee's attribution percentage for a specific order
	 * 
	 * @param int $order_id Order ID
	 * @param int $employee_id Employee user ID
	 * @param int $agent_id Agent ID from order
	 * @param int $processor_id Processor ID from order
	 * @return float Attribution percentage (0-100)
	 */
	private function get_employee_attribution_percentage( $order_id, $employee_id, $agent_id, $processor_id ) {
		// Get commission configuration
		$commission_config = get_option( 'wc_team_payroll_commission_config', array() );
		
		// Default percentages if not configured
		$agent_percentage = isset( $commission_config['agent_percentage'] ) ? floatval( $commission_config['agent_percentage'] ) : 70;
		$processor_percentage = isset( $commission_config['processor_percentage'] ) ? floatval( $commission_config['processor_percentage'] ) : 30;
		
		// Determine employee's role and return appropriate percentage
		if ( $employee_id == $agent_id && $employee_id == $processor_id ) {
			// Same person is both agent and processor - gets 100%
			return 100;
		} elseif ( $employee_id == $agent_id ) {
			// Employee is the agent
			return $agent_percentage;
		} elseif ( $employee_id == $processor_id ) {
			// Employee is the processor
			return $processor_percentage;
		}
		
		// Employee is not assigned to this order
		return 0;
	}
}