<?php
/**
 * Plugin Installer
 */

class WC_Team_Payroll_Installer {

	public static function install() {
		// Create default settings
		$settings = get_option( 'wc_team_payroll_settings', array() );

		if ( empty( $settings ) ) {
			$settings = array(
				'agent_percentage' => 70,
				'processor_percentage' => 30,
				'enable_breakdown' => 1,
				'enable_myaccount' => 1,
				'enable_shortcodes' => 1,
				'extra_earnings_rules' => array(),
			);

			update_option( 'wc_team_payroll_settings', $settings );
		}

		// Flush rewrite rules for My Account endpoints
		flush_rewrite_rules();
	}
}
