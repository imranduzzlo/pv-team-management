/**
 * WooCommerce Team Payroll Employees Management JS
 */

jQuery(document).ready(function($) {
	let currentPage = 1;
	let itemsPerPage = 20;
	let allEmployees = [];
	let filteredEmployees = [];

	// Initialize on page load
	loadEmployees();

	// Handle date preset change
	$('#wc-tp-employees-date-preset').on('change', function() {
		const preset = $(this).val();
		const customDatesDiv = $('#wc-tp-employees-custom-dates');
		const customDatesEndDiv = $('#wc-tp-employees-custom-dates-end');

		if (preset === 'custom') {
			customDatesDiv.show();
			customDatesEndDiv.show();
		} else {
			customDatesDiv.hide();
			customDatesEndDiv.hide();
			const range = getDateRange(preset);
			$('#wc-tp-employees-start-date').val(range.start);
			$('#wc-tp-employees-end-date').val(range.end);
		}
	});

	// Items per page change
	$('#wc-tp-employees-per-page').on('change', function() {
		itemsPerPage = parseInt($(this).val());
		currentPage = 1;
		renderEmployeesTable();
	});

	// Search functionality
	$('#wc-tp-employees-search').on('keyup', function() {
		const searchTerm = $(this).val().toLowerCase();
		if (searchTerm === '') {
			filteredEmployees = [...allEmployees];
		} else {
			filteredEmployees = allEmployees.filter(emp => {
				return emp.display_name.toLowerCase().includes(searchTerm) ||
					   emp.user_email.toLowerCase().includes(searchTerm) ||
					   (emp.vb_user_id && emp.vb_user_id.toLowerCase().includes(searchTerm));
			});
		}
		currentPage = 1;
		renderEmployeesTable();
	});

	// Clear search
	$('#wc-tp-employees-search-clear').on('click', function() {
		$('#wc-tp-employees-search').val('');
		filteredEmployees = [...allEmployees];
		currentPage = 1;
		renderEmployeesTable();
	});

	// Filter button
	$('#wc-tp-employees-filter-btn').on('click', function() {
		loadEmployees();
	});

	// Load employees via AJAX
	function loadEmployees() {
		const startDate = $('#wc-tp-employees-start-date').val();
		const endDate = $('#wc-tp-employees-end-date').val();
		const salaryType = $('#wc-tp-salary-type-filter').val();

		if (!startDate || !endDate) {
			alert('Please select both start and end dates');
			return;
		}

		$('#wc-tp-employees-filter-btn').prop('disabled', true).text('Loading...');

		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'wc_tp_get_employees_list',
				start_date: startDate,
				end_date: endDate,
				salary_type: salaryType
			},
			success: function(response) {
				if (response.success) {
					allEmployees = response.data || [];
					filteredEmployees = [...allEmployees];
					currentPage = 1;
					renderEmployeesTable();
				}
			},
			error: function() {
				// Silent error handling
			},
			complete: function() {
				$('#wc-tp-employees-filter-btn').prop('disabled', false).text('Filter');
			}
		});
	}

	// Render employees table
	function renderEmployeesTable() {
		const container = $('#wc-tp-employees-table-container');
		
		if (!filteredEmployees || filteredEmployees.length === 0) {
			container.html('<div class="wc-tp-empty-state"><div class="wc-tp-empty-icon">👥</div><p>No employees found</p></div>');
			$('#wc-tp-employees-pagination').html('');
			return;
		}

		// Calculate pagination
		const totalPages = Math.ceil(filteredEmployees.length / itemsPerPage);
		const startIndex = (currentPage - 1) * itemsPerPage;
		const endIndex = Math.min(startIndex + itemsPerPage, filteredEmployees.length);
		const pageEmployees = filteredEmployees.slice(startIndex, endIndex);

		// Build table
		let html = '<table class="wc-tp-data-table wc-tp-sortable"><thead><tr>';
		html += '<th class="wc-tp-sortable-header" data-sort="display_name">Name</th>';
		html += '<th class="wc-tp-sortable-header" data-sort="user_email">Email</th>';
		html += '<th class="wc-tp-sortable-header" data-sort="vb_user_id">Employee ID</th>';
		html += '<th class="wc-tp-sortable-header" data-sort="salary_type">Salary Type</th>';
		html += '<th>Action</th>';
		html += '</tr></thead><tbody>';

		$.each(pageEmployees, function(i, emp) {
			html += '<tr>';
			html += '<td><strong>' + emp.display_name + '</strong></td>';
			html += '<td>' + emp.user_email + '</td>';
			html += '<td>' + (emp.vb_user_id || 'N/A') + '</td>';
			html += '<td><span class="wc-tp-badge">' + emp.salary_type + '</span></td>';
			html += '<td><a href="' + emp.manage_url + '" class="button button-small button-primary">Manage</a></td>';
			html += '</tr>';
		});

		html += '</tbody></table>';
		container.html(html);

		// Render pagination
		renderPagination($('#wc-tp-employees-pagination'), filteredEmployees.length, currentPage, itemsPerPage, function(page) {
			currentPage = page;
			renderEmployeesTable();
		});

		// Attach sort handlers
		attachSortHandlers(container, filteredEmployees, function(sortedData) {
			filteredEmployees = sortedData;
			renderEmployeesTable();
		});
	}
});
