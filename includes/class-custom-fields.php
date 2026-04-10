<?php
/**
 * Custom Fields - Create meta fields without ACF dependency
 */

class WC_Team_Payroll_Custom_Fields {

	public function __construct() {
		// Add product meta box for commission
		add_action( 'add_meta_boxes', array( $this, 'add_product_meta_box' ) );
		add_action( 'save_post_product', array( $this, 'save_product_meta' ) );

		// Add user meta box for vb_user_id
		add_action( 'show_user_profile', array( $this, 'add_user_meta_box' ) );
		add_action( 'edit_user_profile', array( $this, 'add_user_meta_box' ) );
		add_action( 'personal_options_update', array( $this, 'save_user_meta' ) );
		add_action( 'edit_user_profile_update', array( $this, 'save_user_meta' ) );
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
	 * Add user meta box for vb_user_id
	 */
	public function add_user_meta_box( $user ) {
		$vb_user_id = get_user_meta( $user->ID, 'vb_user_id', true );
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
					<p class="description"><?php esc_html_e( 'Enter the VB User ID for this employee', 'wc-team-payroll' ); ?></p>
				</td>
			</tr>
		</table>
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
	}
}
