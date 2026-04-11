/* WooCommerce Team Payroll - Dashboard Page JavaScript */

jQuery(document).ready(function($) {
	// Store currency globally
	let wcCurrency = 'USD';
	let wcCurrencySymbol = '$';
	let wcCurrencyPos = 'left';

	// Load dashboard data on page load
	loadDashboardData();

	// Handle date preset change
	$('#wc-tp-date-preset').on('change', function() {
		const preset = $(this).val();
		const customDatesDiv = $('#wc-tp-custom-dates');
		const customDatesEndDiv = $('#wc-tp-custom-dates-end');

		if (preset === 'custom') {
			customDatesDiv.show();
			customDatesEndDiv.show();
		} else {
			customDatesDiv.hide();
			customDatesEndDiv.hide();
			const range = getDateRange(preset);
			$('#wc-tp-start-date').val(range.start);
			$('#wc-tp-end-date').val(range.end);
		}
	});

	// Filter button click
	$('#wc-tp-filter-btn').on('click', function() {
		loadDashboardData();
	});

	// Load all dashboard data via AJAX
	function loadDashboardData() {
		const startDate = $('#wc-tp-start-date').val();
		const endDate = $('#wc-tp-end-date').val();

		if (!startDate || !endDate) {
			alert('Please select both start and end dates');
			return;
		}

		// Show loading state
		$('#wc-tp-filter-btn').prop('disabled', true).text('Loading...');

		// AJAX request
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'wc_tp_get_dashboard_data',
				start_date: startDate,
				end_date: endDate
			},
			success: function(response) {
				if (response.success) {
					const data = response.data;
					
					// Store currency
					wcCurrency = data.currency || 'USD';
					wcCurrencySymbol = data.currency_symbol || '$';
					wcCurrencyPos = data.currency_pos || 'left';
					
					// Update stat cards
					renderStatCards(data);
					
					// Update all tables
					renderLatestEmployees(data.latest_employees);
					renderTopEarners(data.top_earners);
					renderRecentPayments(data.recent_payments);
					renderPayrollTable(data.payroll);
				}
			},
			error: function() {
				// Silent error handling
			},
			complete: function() {
				$('#wc-tp-filter-btn').prop('disabled', false).text('Filter');
			}
		});
	}

	// Render stat cards
	function renderStatCards(data) {
		const container = $('#wc-tp-stats-container');
		let html = '';

		// Total Employees - Link to Employees Section
		html += '<a href="#wc-tp-employees-section" class="wc-tp-stat-card wc-tp-stat-link">';
		html += '<div class="wc-tp-stat-icon">👥</div>';
		html += '<div class="wc-tp-stat-content">';
		html += '<div class="wc-tp-stat-value">' + data.total_employees + '</div>';
		html += '<div class="wc-tp-stat-label">Total Employees</div>';
		html += '</div></a>';

		// Total Orders - Link to Payroll Section
		html += '<a href="#wc-tp-payroll-section" class="wc-tp-stat-card wc-tp-stat-link">';
		html += '<div class="wc-tp-stat-icon">📦</div>';
		html += '<div class="wc-tp-stat-content">';
		html += '<div class="wc-tp-stat-value">' + data.total_orders + '</div>';
		html += '<div class="wc-tp-stat-label">Total Orders</div>';
		html += '</div></a>';

		// Total Earnings - Link to Earners Section
		html += '<a href="#wc-tp-earners-section" class="wc-tp-stat-card wc-tp-stat-link">';
		html += '<div class="wc-tp-stat-icon">💰</div>';
		html += '<div class="wc-tp-stat-content">';
		html += '<div class="wc-tp-stat-value">' + formatCurrencyLocal(data.total_earnings) + '</div>';
		html += '<div class="wc-tp-stat-label">Total Earnings</div>';
		html += '</div></a>';

		// Total Paid - Link to Payments Section
		html += '<a href="#wc-tp-payments-section" class="wc-tp-stat-card wc-tp-stat-link">';
		html += '<div class="wc-tp-stat-icon">✅</div>';
		html += '<div class="wc-tp-stat-content">';
		html += '<div class="wc-tp-stat-value">' + formatCurrencyLocal(data.total_paid) + '</div>';
		html += '<div class="wc-tp-stat-label">Total Paid</div>';
		html += '</div></a>';

		// Total Due - Link to Payroll Section
		html += '<a href="#wc-tp-payroll-section" class="wc-tp-stat-card wc-tp-stat-link">';
		html += '<div class="wc-tp-stat-icon">⏳</div>';
		html += '<div class="wc-tp-stat-content">';
		html += '<div class="wc-tp-stat-value">' + formatCurrencyLocal(data.total_due) + '</div>';
		html += '<div class="wc-tp-stat-label">Total Due</div>';
		html += '</div></a>';

		container.html(html);
	}

	// Render latest employees table
	function renderLatestEmployees(employees) {
		const container = $('#wc-tp-latest-employees-container');
		
		if (!employees || employees.length === 0) {
			container.html('<div class="wc-tp-empty-state"><div class="wc-tp-empty-icon">👥</div><p>No employees yet</p></div>');
			return;
		}

		let html = '<table class="wc-tp-data-table wc-tp-sortable"><thead><tr>';
		html += '<th class="wc-tp-sortable-header" data-sort="display_name">Name</th>';
		html += '<th class="wc-tp-sortable-header" data-sort="user_email">Email</th>';
		html += '<th class="wc-tp-sortable-header" data-sort="type">Type</th>';
		html += '<th>Salary/Commission</th>';
		html += '<th>Action</th>';
		html += '</tr></thead><tbody>';

		$.each(employees, function(i, emp) {
			html += '<tr>';
			html += '<td><strong>' + emp.display_name + '</strong></td>';
			html += '<td>' + emp.user_email + '</td>';
			html += '<td>' + emp.type + '</td>';
			html += '<td>' + emp.salary_info + '</td>';
			html += '<td><a href="' + emp.manage_url + '" class="button button-small button-primary">Manage</a></td>';
			html += '</tr>';
		});

		html += '</tbody></table>';
		container.html(html);
		attachSortHandlers(container, employees, function(sortedData) {
			renderLatestEmployees(sortedData);
		});
	}

	// Render top earners table
	function renderTopEarners(earners) {
		const container = $('#wc-tp-top-earners-container');
		
		if (!earners || earners.length === 0) {
			container.html('<div class="wc-tp-empty-state"><div class="wc-tp-empty-icon">💰</div><p>No earnings data</p></div>');
			return;
		}

		let html = '<table class="wc-tp-data-table wc-tp-sortable wc-tp-no-action"><thead><tr>';
		html += '<th class="wc-tp-sortable-header" data-sort="name">Employee</th>';
		html += '<th class="wc-tp-sortable-header" data-sort="earnings">Earnings</th>';
		html += '<th class="wc-tp-sortable-header" data-sort="orders">Orders</th>';
		html += '</tr></thead><tbody>';

		$.each(earners, function(i, earner) {
			html += '<tr>';
			html += '<td><strong>' + earner.name + '</strong></td>';
			html += '<td>' + formatCurrencyLocal(earner.earnings) + '</td>';
			html += '<td><span class="wc-tp-badge">' + earner.orders + '</span></td>';
			html += '</tr>';
		});

		html += '</tbody></table>';
		container.html(html);
		attachSortHandlers(container, earners, function(sortedData) {
			renderTopEarners(sortedData);
		});
	}

	// Render recent payments table
	function renderRecentPayments(payments) {
		const container = $('#wc-tp-recent-payments-container');
		
		if (!payments || payments.length === 0) {
			container.html('<div class="wc-tp-empty-state"><div class="wc-tp-empty-icon">💳</div><p>No payments yet</p></div>');
			return;
		}

		let html = '<table class="wc-tp-data-table wc-tp-sortable wc-tp-no-action"><thead><tr>';
		html += '<th class="wc-tp-sortable-header" data-sort="employee_name">Employee</th>';
		html += '<th class="wc-tp-sortable-header" data-sort="amount">Amount</th>';
		html += '<th class="wc-tp-sortable-header" data-sort="date">Date</th>';
		html += '</tr></thead><tbody>';

		$.each(payments, function(i, payment) {
			html += '<tr data-payment-user-id="' + payment.user_id + '">';
			html += '<td><strong>' + payment.employee_name + '</strong></td>';
			html += '<td class="wc-tp-payment-amount" data-amount="' + payment.amount + '">' + formatCurrencyLocal(payment.amount) + '</td>';
			html += '<td class="wc-tp-payment-date" data-date="' + payment.date + '">' + payment.date + '</td>';
			html += '</tr>';
		});

		html += '</tbody></table>';
		container.html(html);
		attachSortHandlers(container, payments, function(sortedData) {
			renderRecentPayments(sortedData);
		});
	}

	// Render payroll table
	function renderPayrollTable(payroll) {
		const container = $('#wc-tp-payroll-container');
		
		if (!payroll || Object.keys(payroll).length === 0) {
			container.html('<div class="wc-tp-empty-state"><div class="wc-tp-empty-icon">📊</div><p>No payroll data for this period</p></div>');
			return;
		}

		let html = '<table class="wc-tp-data-table wc-tp-sortable"><thead><tr>';
		html += '<th class="wc-tp-sortable-header" data-sort="name">Employee</th>';
		html += '<th class="wc-tp-sortable-header" data-sort="user_email">Email</th>';
		html += '<th class="wc-tp-sortable-header" data-sort="orders">Orders</th>';
		html += '<th class="wc-tp-sortable-header" data-sort="total">Total Earnings</th>';
		html += '<th class="wc-tp-sortable-header" data-sort="paid">Paid</th>';
		html += '<th class="wc-tp-sortable-header" data-sort="due">Due</th>';
		html += '<th>Action</th>';
		html += '</tr></thead><tbody>';

		let payrollArray = [];
		$.each(payroll, function(userId, data) {
			payrollArray.push({
				userId: userId,
				name: data.name,
				user_email: data.user_email || 'N/A',
				orders: data.orders,
				total: data.total,
				paid: data.paid,
				due: data.due,
				user: data.user
			});
		});

		$.each(payrollArray, function(i, data) {
			html += '<tr data-user-id="' + data.userId + '">';
			html += '<td><strong>' + data.name + '</strong></td>';
			html += '<td>' + data.user_email + '</td>';
			html += '<td><span class="wc-tp-badge">' + data.orders + '</span></td>';
			html += '<td>' + formatCurrencyLocal(data.total) + '</td>';
			html += '<td class="wc-tp-paid-cell" data-paid="' + data.paid + '">' + formatCurrencyLocal(data.paid) + '</td>';
			html += '<td class="wc-tp-due-cell" data-due="' + data.due + '">' + formatCurrencyLocal(data.due) + '</td>';
			html += '<td><a href="' + ajaxurl.replace('admin-ajax.php', 'admin.php?page=wc-team-payroll-employee-detail&user_id=' + data.userId) + '" class="button button-small button-primary">View</a></td>';
			html += '</tr>';
		});

		html += '</tbody></table>';
		container.html(html);
		attachSortHandlers(container, payrollArray, function(sortedData) {
			renderPayrollTable(sortedData);
		});
	}

	// Format currency with local settings
	function formatCurrencyLocal(value) {
		const amount = parseFloat(value).toFixed(2);
		if (wcCurrencyPos === 'right') {
			return amount + ' ' + wcCurrencySymbol;
		} else {
			return wcCurrencySymbol + ' ' + amount;
		}
	}
});
