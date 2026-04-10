<?php
/**
 * ACF Fields Registration
 * 
 * NOTE: This class does NOT create any fields.
 * User creates their own fields:
 * - Product field: team_commission (ACF number field)
 * - Checkout field: order_other_agent_or_not (ThemeHigh Checkout Field Editor)
 * 
 * All other data is stored as order meta:
 * - _primary_agent_id (order meta)
 * - _processor_user_id (order meta)
 * - _commission_data (order meta)
 * - _wc_tp_order_bonuses (order meta)
 * - _wc_tp_payments (user meta)
 * - _wc_tp_fixed_salary (user meta)
 * - _wc_tp_salary_amount (user meta)
 * - _wc_tp_salary_frequency (user meta)
 * - _wc_tp_salary_history (user meta)
 */

class WC_Team_Payroll_ACF_Fields {

	public static function init() {
		// No fields to register - user creates their own
	}
}
