/**
 * WooCommerce Team Payroll - Employee Management (Team Members) Page JavaScript
 * Handles employee data loading, filtering, sorting, and pagination
 */

jQuery(document).ready(function($) {
	let currentPage = 1;
	let allEmployeesData = [];
	let searchQuery = '';
	let salaryTypeFilter = '';
	let startDate = '';
	let endDate = '';
	let itemsPerPage = 20; // Default

	// Load saved items per page from localStorage
	const savedItemsPerPage = localStorage.getItem('wc_tp_employees_items_per_page');
	if (savedItemsPerPage) {
		itemsPerPage = parseInt(savedItemsPerPage);
		$('#wc-tp-employees-per-page').val(itemsPerPage);
	}

	loadEmployeesData();

	// Items per page change
	$('#wc-tp-employees-per-page').on('change', function() {
		itemsPerPage = parseInt($(this).val());
		localStorage.setItem('wc_tp_employees_items_per_page', itemsPerPage);
		currentPage = 1;
		renderEmployeesTable(allEmployeesData);
		renderPagination(allEmployeesData);
	});

	$('#wc-tp-employees-search').on('keyup', function() {
		currentPage = 1;
		searchQuery = $(this).val();
		loadEmployeesData();
	});

	$('#wc-tp-employees-search-clear').on('click', function() {
		$('#wc-tp-employees-search').val('');
		searchQuery = '';
		currentPage = 1;
		loadEmployeesData();
	});

	$('#wc-tp-salary-type-filter').on('change', function() {
		currentPage = 1;
		salaryTypeFilter = $(this).val();
		loadEmployeesData();
	});

	$('#wc-tp-employees-start-date').on('change', function() {
		currentPage = 1;
		startDate = $(this).val();
		loadEmployeesData();
	});

	$('#wc-tp-employees-end-date').on('change', function() {
		currentPage = 1;
		endDate = $(this).val();
		loadEmployeesData();
	});

	$('#wc-tp-employees-date-clear').on('click', function() {
		$('#wc-tp-employees-start-date').val('');
		$('#wc-tp-employees-end-date').val('');
		startDate = '';
		endDate = '';
		currentPage = 1;
		loadEmployeesData();
	});

	function loadEmployeesData() {
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'wc_tp_get_employees_data',
				search_query: searchQuery,
				salary_type: salaryTypeFilter,
				start_date: startDate,
				end_date: endDate
			},
			success: function(response) {
				if (response.success) {
					const data = response.data;
					allEmployeesData = data.employees;
					currentPage = 1;
					
					renderEmployeesTable(allEmployeesData);
					renderPagination(allEmployeesData);
				}
			},
			error: function() {
				// Silent error handling
			}
		});
	}

	function renderEmployeesTable(employees) {
		const container = $('#wc-tp-employees-table-container');
		
		if (!employees || employees.length === 0) {
			container.html('<div class="wc-tp-empty-state"><div class="wc-tp-empty-icon">👥</div><p>No team members found</p></div>');
			return;
		}

		const startIndex = (currentPage - 1) * itemsPerPage;
		const endIndex = startIndex + itemsPerPage;
		const pageData = employees.slice(startIndex, endIndex);

		let html = '<table class="wc-tp-data-table wc-tp-sortable"><thead><tr>';
		html += '<th class="wc-tp-sortable-header" data-sort="display_name">Name</th>';
		html += '<th class="wc-tp-sortable-header" data-sort="user_email">Email</th>';
		html += '<th class="wc-tp-sortable-header" data-sort="type">Type</th>';
		html += '<th>Salary/Commission</th>';
		html += '<th>Action</th>';
		html += '</tr></thead><tbody>';

		$.each(pageData, function(i, emp) {
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
		
		// Store employees array for sorting
		container.data('employeesArray', employees);
		attachEmployeesSortHandlers(container, employees);
	}

	function attachEmployeesSortHandlers(container, employeesArray) {
		let currentSort = container.data('sortState') || { field: null, direction: 'asc' };
		
		// Remove old event handlers to prevent duplicates
		container.find('.wc-tp-sortable-header').off('click');
		
		// Restore sort state classes if they exist
		if (currentSort.field) {
			const header = container.find('.wc-tp-sortable-header[data-sort="' + currentSort.field + '"]');
			if (header.length) {
				header.addClass('wc-tp-sort-active');
				if (currentSort.direction === 'asc') {
					header.addClass('wc-tp-sort-asc');
				} else {
					header.addClass('wc-tp-sort-desc');
				}
			}
		}
		
		container.find('.wc-tp-sortable-header').on('click', function() {
			const sortField = $(this).data('sort');
			if (!sortField) return;
			
			// Check if clicking the same field
			if (currentSort.field === sortField) {
				// Toggle direction
				currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
			} else {
				// New field, start with ascending
				currentSort.field = sortField;
				currentSort.direction = 'asc';
			}
			
			// Save sort state to container
			container.data('sortState', currentSort);
			
			// Sort data
			let sortedData = [...employeesArray].sort((a, b) => {
				let aVal = a[sortField];
				let bVal = b[sortField];
				
				if (aVal === undefined || aVal === null) aVal = '';
				if (bVal === undefined || bVal === null) bVal = '';
				
				aVal = String(aVal).toLowerCase();
				bVal = String(bVal).toLowerCase();
				return currentSort.direction === 'asc' ? aVal.localeCompare(bVal) : bVal.localeCompare(aVal);
			});
			
			// Reset to first page and update global data
			currentPage = 1;
			allEmployeesData = sortedData;
			
			renderEmployeesTable(allEmployeesData);
			renderPagination(allEmployeesData);
			
			// Re-attach handlers to new headers with updated sort state
			setTimeout(function() {
				attachEmployeesSortHandlers(container, sortedData);
			}, 10);
		});
	}

	function renderPagination(employees) {
		const container = $('#wc-tp-employees-pagination');
		const totalPages = Math.ceil(employees.length / itemsPerPage);

		if (totalPages <= 1) {
			container.html('');
			return;
		}

		let html = '<div class="wc-tp-pagination">';

		// Previous button
		if (currentPage > 1) {
			html += '<a href="#" data-page="' + (currentPage - 1) + '">← Previous</a>';
		}

		// Page numbers
		for (let i = 1; i <= totalPages; i++) {
			if (i === currentPage) {
				html += '<span class="current">' + i + '</span>';
			} else {
				html += '<a href="#" data-page="' + i + '">' + i + '</a>';
			}
		}

		// Next button
		if (currentPage < totalPages) {
			html += '<a href="#" data-page="' + (currentPage + 1) + '">Next →</a>';
		}

		html += '</div>';
		container.html(html);

		// Pagination click handler
		container.find('a').on('click', function(e) {
			e.preventDefault();
			currentPage = parseInt($(this).data('page'));
			renderEmployeesTable(allEmployeesData);
			renderPagination(allEmployeesData);
			$('html, body').animate({ scrollTop: $('#wc-tp-employees-table-section').offset().top - 100 }, 300);
		});
	}
});
