<?php
/**
 * Custom Fields - Create meta fields without ACF dependency
 */

class WC_Team_Payroll_Custom_Fields {

	public function __construct() {
		// Create shop_employee role
		$this->create_shop_employee_role();

		// Add product meta box for commission
		add_action( 'add_meta_boxes', array( $this, 'add_product_meta_box' ) );
		add_action( 'save_post_product', array( $this, 'save_product_meta' ) );

		// Add user meta box for vb_user_id
		add_action( 'show_user_profile', array( $this, 'add_user_meta_box' ) );
		add_action( 'edit_user_profile', array( $this, 'add_user_meta_box' ) );
		add_action( 'personal_options_update', array( $this, 'save_user_meta' ) );
		add_action( 'edit_user_profile_update', array( $this, 'save_user_meta' ) );

		// Auto-generate vb_user_id on user register and profile update
		add_action( 'user_register', array( $this, 'auto_set_user_custom_id' ) );
		add_action( 'profile_update', array( $this, 'auto_set_user_custom_id' ) );

		// Restrict admin access for Shop Employee
		add_action( 'admin_init', array( $this, 'restrict_shop_employee_admin_access' ) );
	}

	/**
	 * Create shop_employee role
	 */
	private function create_shop_employee_role() {
		if ( ! get_role( 'shop_employee' ) ) {
			add_role(
				'shop_employee',
				__( 'Shop employee', 'wc-team-payroll' ),
				array(
					'read' => true,
				)
			);
		}
	}

	/**
	 * Restrict admin access for Shop Employee
	 */
	public function restrict_shop_employee_admin_access() {
		if ( current_user_can( 'shop_employee' ) && ! defined( 'DOING_AJAX' ) ) {
			wp_redirect( home_url() );
			exit;
		}
	}

	/**
	 * Auto-generate vb_user_id for employees
	 */
	public function auto_set_user_custom_id( $user_id ) {
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return;
		}

		$allowed_roles = array( 'shop_employee', 'shop_manager', 'administrator' );
		if ( ! array_intersect( $allowed_roles, $user->roles ) ) {
			return;
		}

		// Check if vb_user_id already exists
		$existing_id = get_user_meta( $user_id, 'vb_user_id', true );
		if ( ! empty( $existing_id ) ) {
			return; // Don't overwrite existing ID
		}

		// Get prefix from settings
		$prefix = get_option( 'wc_tp_user_id_prefix', 'PVVB-EMID' );

		// Generate custom ID
		$custom_id = $prefix . $user_id;
		update_user_meta( $user_id, 'vb_user_id', $custom_id );

		// Also update ACF field if available
		if ( function_exists( 'update_field' ) ) {
			update_field( 'vb_user_id', $custom_id, 'user_' . $user_id );
		}
	}

	/**
	 * Add product meta box for commission
	 */
	public function add_product_meta_box() {
		add_meta_box(
			'wc_tp_product_commission',
			__( 'Team Commission Rate', 'wc-team-payroll' ),
			array( $this, 'render_product_meta_box' ),
			'product',
			'normal',
			'high'
		);
	}

	/**
	 * Render product meta box
	 */
	public function render_product_meta_box( $post ) {
		$commission = get_post_meta( $post->ID, 'team_commission', true );
		wp_nonce_field( 'wc_tp_product_nonce', 'wc_tp_product_nonce' );
		?>
		<p>
			<label for="team_commission"><?php esc_html_e( 'Commission Rate (%)', 'wc-team-payroll' ); ?></label>
			<input type="number" id="team_commission" name="team_commission" value="<?php echo esc_attr( $commission ); ?>" step="0.01" min="0" max="100" />
			<small><?php esc_html_e( 'Enter the commission percentage for this product (e.g., 5 for 5%)', 'wc-team-payroll' ); ?></small>
		</p>
		<?php
	}

	/**
	 * Save product meta
	 */
	public function save_product_meta( $post_id ) {
		if ( ! isset( $_POST['wc_tp_product_nonce'] ) || ! wp_verify_nonce( $_POST['wc_tp_product_nonce'], 'wc_tp_product_nonce' ) ) {
			return;
		}

		if ( isset( $_POST['team_commission'] ) ) {
			$commission = floatval( $_POST['team_commission'] );
			update_post_meta( $post_id, 'team_commission', $commission );
		}
	}

	/**
	 * Add user meta box for vb_user_id and profile picture
	 */
	public function add_user_meta_box( $user ) {
		$vb_user_id = get_user_meta( $user->ID, 'vb_user_id', true );
		$profile_picture_id = get_user_meta( $user->ID, '_wc_tp_profile_picture', true );
		$profile_picture_url = '';
		
		if ( $profile_picture_id ) {
			$profile_picture_url = wp_get_attachment_url( $profile_picture_id );
		}
		
		wp_nonce_field( 'wc_tp_user_nonce', 'wc_tp_user_nonce' );
		?>
		<h3><?php esc_html_e( 'Team Payroll', 'wc-team-payroll' ); ?></h3>
		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="vb_user_id"><?php esc_html_e( 'VB User ID', 'wc-team-payroll' ); ?></label>
				</th>
				<td>
					<input type="text" id="vb_user_id" name="vb_user_id" value="<?php echo esc_attr( $vb_user_id ); ?>" class="regular-text" />
					<p class="description"><?php esc_html_e( 'Auto-generated employee ID. Edit to customize.', 'wc-team-payroll' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="wc_tp_profile_picture"><?php esc_html_e( 'Profile Picture', 'wc-team-payroll' ); ?></label>
				</th>
				<td>
					<div id="wc-tp-profile-picture-preview" style="margin-bottom: 10px;">
						<?php if ( $profile_picture_url ) : ?>
							<img src="<?php echo esc_url( $profile_picture_url ); ?>" style="max-width: 150px; height: auto; border-radius: 8px;" />
						<?php endif; ?>
					</div>
					<input type="hidden" id="wc_tp_profile_picture" name="wc_tp_profile_picture" value="<?php echo esc_attr( $profile_picture_id ); ?>" />
					<button type="button" class="button" id="wc-tp-upload-profile-picture"><?php esc_html_e( 'Upload Picture', 'wc-team-payroll' ); ?></button>
					<?php if ( $profile_picture_id ) : ?>
						<button type="button" class="button" id="wc-tp-remove-profile-picture" style="margin-left: 5px;"><?php esc_html_e( 'Remove Picture', 'wc-team-payroll' ); ?></button>
					<?php endif; ?>
					<p class="description"><?php esc_html_e( 'Upload a profile picture for this employee.', 'wc-team-payroll' ); ?></p>
				</td>
			</tr>
		</table>
		<script>
			jQuery(document).ready(function($) {
				let mediaUploader;

				$('#wc-tp-upload-profile-picture').on('click', function(e) {
					e.preventDefault();

					if (mediaUploader) {
						mediaUploader.open();
						return;
					}

					mediaUploader = wp.media.frames.file_frame = wp.media({
						title: '<?php esc_js_e( 'Select Profile Picture', 'wc-team-payroll' ); ?>',
						button: {
							text: '<?php esc_js_e( 'Use this image', 'wc-team-payroll' ); ?>'
						},
						multiple: false,
						library: {
							type: 'image'
						}
					});

					mediaUploader.on('select', function() {
						const attachment = mediaUploader.state().get('selection').first().toJSON();
						$('#wc_tp_profile_picture').val(attachment.id);
						$('#wc-tp-profile-picture-preview').html('<img src="' + attachment.url + '" style="max-width: 150px; height: auto; border-radius: 8px;" />');
						
						// Show remove button
						if (!$('#wc-tp-remove-profile-picture').length) {
							$('#wc-tp-upload-profile-picture').after('<button type="button" class="button" id="wc-tp-remove-profile-picture" style="margin-left: 5px;"><?php esc_js_e( 'Remove Picture', 'wc-team-payroll' ); ?></button>');
						}
					});

					mediaUploader.open();
				});

				$('#wc-tp-profile-picture-preview').on('click', '#wc-tp-remove-profile-picture', function(e) {
					e.preventDefault();
					$('#wc_tp_profile_picture').val('');
					$('#wc-tp-profile-picture-preview').html('');
					$(this).remove();
				});
			});
		</script>
		<?php
	}

	/**
	 * Save user meta
	 */
	public function save_user_meta( $user_id ) {
		if ( ! isset( $_POST['wc_tp_user_nonce'] ) || ! wp_verify_nonce( $_POST['wc_tp_user_nonce'], 'wc_tp_user_nonce' ) ) {
			return;
		}

		if ( isset( $_POST['vb_user_id'] ) ) {
			$vb_user_id = sanitize_text_field( $_POST['vb_user_id'] );
			update_user_meta( $user_id, 'vb_user_id', $vb_user_id );
		}

		if ( isset( $_POST['wc_tp_profile_picture'] ) ) {
			$profile_picture_id = intval( $_POST['wc_tp_profile_picture'] );
			if ( $profile_picture_id ) {
				update_user_meta( $user_id, '_wc_tp_profile_picture', $profile_picture_id );
			} else {
				delete_user_meta( $user_id, '_wc_tp_profile_picture' );
			}
		}
	}
}

