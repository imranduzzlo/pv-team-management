<?php
/**
 * Check plugin dependencies
 */

class WC_Team_Payroll_Dependencies {

	public function check() {
		if ( ! $this->is_woocommerce_active() ) {
			add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
			return false;
		}

		if ( ! $this->is_acf_active() ) {
			add_action( 'admin_notices', array( $this, 'acf_missing_notice' ) );
			return false;
		}

		return true;
	}

	private function is_woocommerce_active() {
		return class_exists( 'WooCommerce' );
	}

	private function is_acf_active() {
		// Check for ACF or SCF (Smart Custom Fields)
		return class_exists( 'ACF' ) || class_exists( 'SCF' );
	}

	public function woocommerce_missing_notice() {
		?>
		<div class="notice notice-error is-dismissible">
			<p><?php esc_html_e( 'WooCommerce Team Payroll requires WooCommerce to be installed and activated.', 'wc-team-payroll' ); ?></p>
		</div>
		<?php
	}

	public function acf_missing_notice() {
		?>
		<div class="notice notice-error is-dismissible">
			<p><?php esc_html_e( 'WooCommerce Team Payroll requires Advanced Custom Fields (ACF) or Smart Custom Fields (SCF) to be installed and activated.', 'wc-team-payroll' ); ?></p>
		</div>
		<?php
	}
}
