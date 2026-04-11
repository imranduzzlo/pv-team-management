/**
 * WooCommerce Team Payroll - Payroll Page JavaScript
 * Handles payroll data loading, filtering, sorting, and pagination
 */

jQuery(document).ready(function($) {
	let wcCurrency = 'USD';
	let wcCurrencySymbol = '$';
	let wcCurrencyPos = 'left';
	let currentPage = 1;
	let allPayrollData = [];
	let searchQuery = '';
	let salaryTypeFilter = '';
	let itemsPerPage = 20; // Default

	// Load saved items per page from localStorage
	const savedItemsPerPage = localStorage.getItem('wc_tp_payroll_items_per_page');
	if (savedItemsPerPage) {
		itemsPerPage = parseInt(savedItemsPerPage);
		$('#wc-tp-payroll-per-page').val(itemsPerPage);
	}

	// Set default dates if not already set
	if (!$('#wc-tp-payroll-start-date').val()) {
		const today = new Date();
		const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
		const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
		
		$('#wc-tp-payroll-start-date').val(firstDay.toISOString().split('T')[0]);
		$('#wc-tp-payroll-end-date').val(lastDay.toISOString().split('T')[0]);
	}

	loadPayrollData();

	// Items per page change
	$('#wc-tp-payroll-per-page').on('change', function() {
		itemsPerPage = parseInt($(this).val());
		localStorage.setItem('wc_tp_payroll_items_per_page', itemsPerPage);
		currentPage = 1;
		renderPayrollTable(allPayrollData);
		renderPagination(allPayrollData);
	});

	$('#wc-tp-payroll-filter-btn').on('click', function() {
		currentPage = 1;
		loadPayrollData();
	});

	$('#wc-tp-payroll-salary-type-filter').on('change', function() {
		salaryTypeFilter = $(this).val();
		currentPage = 1;
		loadPayrollData();
	});

	$('#wc-tp-payroll-search').on('keyup', function() {
		currentPage = 1;
		searchQuery = $(this).val();
		loadPayrollData();
	});

	$('#wc-tp-payroll-search-clear').on('click', function() {
		$('#wc-tp-payroll-search').val('');
		searchQuery = '';
		currentPage = 1;
		loadPayrollData();
	});

	function loadPayrollData() {
		const startDate = $('#wc-tp-payroll-start-date').val();
		const endDate = $('#wc-tp-payroll-end-date').val();

		// If dates are not set, set them to current month
		if (!startDate || !endDate) {
			const today = new Date();
			const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
			const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
			
			$('#wc-tp-payroll-start-date').val(firstDay.toISOString().split('T')[0]);
			$('#wc-tp-payroll-end-date').val(lastDay.toISOString().split('T')[0]);
			
			// Retry with new dates
			setTimeout(function() {
				loadPayrollData();
			}, 100);
			return;
		}

		$('#wc-tp-payroll-filter-btn').prop('disabled', true).text('Loading...');

		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'wc_tp_get_payroll_data_range',
				start_date: startDate,
				end_date: endDate,
				search_query: searchQuery,
				salary_type: salaryTypeFilter
			},
			success: function(response) {
				if (response.success) {
					const data = response.data;
					
					wcCurrency = data.currency || 'USD';
					wcCurrencySymbol = data.currency_symbol || '$';
					wcCurrencyPos = data.currency_pos || 'left';
					allPayrollData = data.payroll;
					currentPage = 1;
					
					renderPayrollTable(allPayrollData);
					renderPagination(allPayrollData);
				}
			},
			error: function() {
				// Silent error handling
			},
			complete: function() {
				$('#wc-tp-payroll-filter-btn').prop('disabled', false).text('Filter');
			}
		});
	}

	function renderPayrollTable(payroll) {
		const container = $('#wc-tp-payroll-table-container');
		
		if (!payroll || payroll.length === 0) {
			container.html('<div class="wc-tp-empty-state"><div class="wc-tp-empty-icon">📊</div><p>No payroll data for this period</p></div>');
			return;
		}

		let payrollArray = payroll;
		
		// If payroll is an object (from AJAX), convert to array
		if (!Array.isArray(payroll)) {
			payrollArray = [];
			$.each(payroll, function(userId, data) {
				payrollArray.push({
					userId: userId,
					name: data.name,
					orders: data.orders,
					total: data.total,
					paid: data.paid,
					due: data.due
				});
			});
		}

		const startIndex = (currentPage - 1) * itemsPerPage;
		const endIndex = startIndex + itemsPerPage;
		const pageData = payrollArray.slice(startIndex, endIndex);

		let html = '<table class="wc-tp-data-table wc-tp-sortable"><thead><tr>';
		html += '<th class="wc-tp-sortable-header" data-sort="name">Employee</th>';
		html += '<th class="wc-tp-sortable-header" data-sort="orders">Orders</th>';
		html += '<th class="wc-tp-sortable-header" data-sort="total">Total Earnings</th>';
		html += '<th class="wc-tp-sortable-header" data-sort="paid">Paid</th>';
		html += '<th class="wc-tp-sortable-header" data-sort="due">Due</th>';
		html += '<th>Action</th>';
		html += '</tr></thead><tbody>';

		$.each(pageData, function(i, data) {
			html += '<tr data-user-id="' + data.userId + '">';
			html += '<td><strong>' + data.name + '</strong></td>';
			html += '<td><span class="wc-tp-badge">' + data.orders + '</span></td>';
			html += '<td>' + formatCurrency(data.total) + '</td>';
			html += '<td>' + formatCurrency(data.paid) + '</td>';
			html += '<td>' + formatCurrency(data.due) + '</td>';
			html += '<td><a href="' + ajaxurl.replace('admin-ajax.php', 'admin.php?page=wc-team-payroll-employee-detail&user_id=' + data.userId) + '" class="button button-small button-primary">View</a></td>';
			html += '</tr>';
		});

		html += '</tbody></table>';
		container.html(html);
		
		// Store payroll array for sorting
		container.data('payrollArray', payrollArray);
		attachPayrollSortHandlers(container, payrollArray);
	}

	function attachPayrollSortHandlers(container, payrollArray) {
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
			
			const isNumeric = ['orders', 'total', 'paid', 'due'].includes(sortField);
			
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
			let sortedData = [...payrollArray].sort((a, b) => {
				let aVal = a[sortField];
				let bVal = b[sortField];
				
				if (aVal === undefined || aVal === null) aVal = '';
				if (bVal === undefined || bVal === null) bVal = '';
				
				if (isNumeric) {
					aVal = parseFloat(aVal) || 0;
					bVal = parseFloat(bVal) || 0;
					return currentSort.direction === 'asc' ? aVal - bVal : bVal - aVal;
				} else {
					aVal = String(aVal).toLowerCase();
					bVal = String(bVal).toLowerCase();
					return currentSort.direction === 'asc' ? aVal.localeCompare(bVal) : bVal.localeCompare(aVal);
				}
			});
			
			// Reset to first page and update global data
			currentPage = 1;
			allPayrollData = sortedData;
			
			renderPayrollTable(allPayrollData);
			renderPagination(allPayrollData);
			
			// Re-attach handlers to new headers with updated sort state
			setTimeout(function() {
				attachPayrollSortHandlers(container, sortedData);
			}, 10);
		});
	}

	function renderPagination(payroll) {
		const container = $('#wc-tp-payroll-pagination');
		
		// Handle both array and object formats
		let totalItems = 0;
		if (Array.isArray(payroll)) {
			totalItems = payroll.length;
		} else {
			totalItems = Object.keys(payroll).length;
		}
		
		const totalPages = Math.ceil(totalItems / itemsPerPage);

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
			renderPayrollTable(allPayrollData);
			renderPagination(allPayrollData);
			$('html, body').animate({ scrollTop: $('#wc-tp-payroll-table-section').offset().top - 100 }, 300);
		});
	}

	function formatCurrency(value) {
		const amount = parseFloat(value).toFixed(2);
		if (wcCurrencyPos === 'right') {
			return amount + ' ' + wcCurrencySymbol;
		} else {
			return wcCurrencySymbol + ' ' + amount;
		}
	}
});
