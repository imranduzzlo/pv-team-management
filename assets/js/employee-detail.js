/* WooCommerce Team Payroll - Employee Detail Page */

jQuery(document).ready(function($) {
	// Get data from page
	const userId = parseInt($('.wc-team-payroll-employee-detail').data('user-id') || 0);
	const wcCurrencySymbol = $('.wc-team-payroll-employee-detail').data('currency-symbol') || '$';
	const wcCurrencyPos = $('.wc-team-payroll-employee-detail').data('currency-pos') || 'left';

	// Initialize employee detail page
	if (userId > 0) {
		initializeEmployeeDetailPage(userId, wcCurrencySymbol, wcCurrencyPos);
	}
});
