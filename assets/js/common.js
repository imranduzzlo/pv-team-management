/* WooCommerce Team Payroll - Common JavaScript Functions */

/**
 * Calculate date range based on preset
 */
function getDateRange(preset) {
	const today = new Date();
	let start, end;

	switch(preset) {
		case 'today':
			start = new Date(today);
			end = new Date(today);
			break;
		case 'this-week':
			start = new Date(today);
			start.setDate(today.getDate() - today.getDay());
			end = new Date(today);
			break;
		case 'this-month':
			start = new Date(today.getFullYear(), today.getMonth(), 1);
			end = new Date(today);
			break;
		case 'this-year':
			start = new Date(today.getFullYear(), 0, 1);
			end = new Date(today);
			break;
		case 'last-week':
			end = new Date(today);
			end.setDate(today.getDate() - today.getDay() - 1);
			start = new Date(end);
			start.setDate(end.getDate() - 6);
			break;
		case 'last-month':
			start = new Date(today.getFullYear(), today.getMonth() - 1, 1);
			end = new Date(today.getFullYear(), today.getMonth(), 0);
			break;
		case 'last-year':
			start = new Date(today.getFullYear() - 1, 0, 1);
			end = new Date(today.getFullYear() - 1, 11, 31);
			break;
		case 'last-6-months':
			start = new Date(today);
			start.setMonth(today.getMonth() - 6);
			end = new Date(today);
			break;
		case 'all-time':
		default:
			start = new Date('2000-01-01');
			end = new Date(today);
	}

	return {
		start: start.toISOString().split('T')[0],
		end: end.toISOString().split('T')[0]
	};
}

/**
 * Format currency based on WooCommerce settings
 */
function formatCurrency(value, currency = 'USD', symbol = '$', position = 'left') {
	const amount = parseFloat(value).toFixed(2);
	if (position === 'right') {
		return amount + ' ' + symbol;
	} else {
		return symbol + ' ' + amount;
	}
}

/**
 * Handle date preset change
 */
function handleDatePresetChange(presetSelector, customDatesDiv, startDateInput, endDateInput, onChangeCallback) {
	jQuery(presetSelector).on('change', function() {
		const preset = jQuery(this).val();

		if (preset === 'custom') {
			customDatesDiv.show();
		} else {
			customDatesDiv.hide();
			const range = getDateRange(preset);
			jQuery(startDateInput).val(range.start);
			jQuery(endDateInput).val(range.end);
			if (onChangeCallback) {
				onChangeCallback(range.start, range.end);
			}
		}
	});
}

/**
 * Render pagination
 */
function renderPagination(container, totalItems, currentPage, itemsPerPage, onPageChange) {
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
		const page = parseInt(jQuery(this).data('page'));
		if (onPageChange) {
			onPageChange(page);
		}
	});
}

/**
 * Attach sort handlers to table headers
 */
function attachSortHandlers(container, dataArray, onSortChange) {
	let currentSort = container.data('sortState') || { field: null, direction: 'asc' };

	// Remove old event handlers
	container.find('.wc-tp-sortable-header').off('click');

	// Restore sort state classes
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
		const sortField = jQuery(this).data('sort');
		if (!sortField) return;

		const isNumeric = ['orders', 'total', 'paid', 'due', 'earnings'].includes(sortField);

		// Check if clicking the same field
		if (currentSort.field === sortField) {
			// Toggle direction
			currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
		} else {
			// New field, start with ascending
			currentSort.field = sortField;
			currentSort.direction = 'asc';
		}

		// Save sort state
		container.data('sortState', currentSort);

		// Sort data
		let sortedData = [...dataArray].sort((a, b) => {
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

		if (onSortChange) {
			onSortChange(sortedData, currentSort);
		}

		// Re-attach handlers
		setTimeout(function() {
			attachSortHandlers(container, sortedData, onSortChange);
		}, 10);
	});
}

/**
 * Employee Detail Page - Orders Tab Functions
 */

function initializeEmployeeDetailPage(userId, wcCurrencySymbol, wcCurrencyPos) {
	let currentPage = 1;
	let allOrdersData = [];
	const itemsPerPage = 20;

	// Load orders data function
	function loadOrdersData() {
		const startDate = jQuery('#wc-tp-orders-start-date').val();
		const endDate = jQuery('#wc-tp-orders-end-date').val();
		const statusFilter = jQuery('#wc-tp-orders-status-filter').val();
		const flagFilter = jQuery('#wc-tp-orders-flag-filter').val();
		const searchQuery = jQuery('#wc-tp-orders-search').val();

		jQuery.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'wc_tp_get_employee_orders',
				user_id: userId,
				start_date: startDate,
				end_date: endDate,
				status: statusFilter,
				flag: flagFilter,
				search: searchQuery
			},
			success: function(response) {
				if (response.success) {
					allOrdersData = response.data.orders || [];
					renderOrdersTable(allOrdersData);
					renderOrdersPagination(allOrdersData);
				}
			},
			error: function() {
				// Silent error handling
			}
		});
	}

	// Load orders on page load (since orders tab is active by default)
	loadOrdersData();

	// Tab switching
	jQuery('.wc-tp-tab-button').on('click', function() {
		const tabName = jQuery(this).data('tab');
		
		// Remove active class from all buttons and contents
		jQuery('.wc-tp-tab-button').removeClass('wc-tp-tab-active');
		jQuery('.wc-tp-tab-content').removeClass('wc-tp-tab-active');
		
		// Add active class to clicked button and corresponding content
		jQuery(this).addClass('wc-tp-tab-active');
		jQuery('#' + tabName + '-tab').addClass('wc-tp-tab-active');

		// Load orders data when orders tab is clicked
		if (tabName === 'orders') {
			loadOrdersData();
		}
	});

	// Orders Tab Functionality
	jQuery('#wc-tp-orders-filter-btn').on('click', function() {
		currentPage = 1;
		loadOrdersData();
	});

	jQuery('#wc-tp-orders-search').on('keyup', function() {
		currentPage = 1;
		loadOrdersData();
	});

	jQuery('#wc-tp-orders-search-clear').on('click', function() {
		jQuery('#wc-tp-orders-search').val('');
		currentPage = 1;
		loadOrdersData();
	});

	function renderOrdersTable(orders) {
		const container = jQuery('#wc-tp-orders-table-container');
		
		if (!orders || orders.length === 0) {
			container.html('<div class="wc-tp-empty-state"><div class="wc-tp-empty-icon">📭</div><p>No orders found</p></div>');
			return;
		}

		const startIndex = (currentPage - 1) * itemsPerPage;
		const endIndex = startIndex + itemsPerPage;
		const pageData = orders.slice(startIndex, endIndex);

		let html = '<table class="wc-tp-data-table"><thead><tr>';
		html += '<th>Order ID</th>';
		html += '<th>Customer</th>';
		html += '<th>Total</th>';
		html += '<th>Status</th>';
		html += '<th>Commission</th>';
		html += '<th>Your Earnings</th>';
		html += '<th>Flag</th>';
		html += '<th>Date</th>';
		html += '<th>Action</th>';
		html += '</tr></thead><tbody>';

		jQuery.each(pageData, function(i, order) {
			html += '<tr>';
			html += '<td><strong>#' + order.order_id + '</strong></td>';
			html += '<td>' + order.customer_name + '</td>';
			html += '<td>' + formatCurrencyValue(order.total, wcCurrencySymbol, wcCurrencyPos) + '</td>';
			html += '<td><span class="wc-tp-badge">' + order.status + '</span></td>';
			html += '<td>' + formatCurrencyValue(order.commission, wcCurrencySymbol, wcCurrencyPos) + '</td>';
			html += '<td><strong>' + formatCurrencyValue(order.user_earnings, wcCurrencySymbol, wcCurrencyPos) + '</strong></td>';
			html += '<td><span class="wc-tp-flag-badge wc-tp-flag-' + order.flag.toLowerCase().replace(/_/g, '-') + '">' + order.flag_label + '</span></td>';
			html += '<td>' + order.date + '</td>';
			html += '<td>';
			html += '<a href="' + ajaxurl.replace('admin-ajax.php', 'admin.php?page=wc-team-payroll-order-detail&order_id=' + order.order_id) + '" class="button button-small" title="View">👁️</a> ';
			html += '<a href="' + ajaxurl.replace('admin-ajax.php', 'post.php?post=' + order.order_id + '&action=edit') + '" class="button button-small" title="Edit">✏️</a>';
			html += '</td>';
			html += '</tr>';
		});

		html += '</tbody></table>';
		container.html(html);
	}

	function renderOrdersPagination(orders) {
		const container = jQuery('#wc-tp-orders-pagination');
		const totalPages = Math.ceil(orders.length / itemsPerPage);

		if (totalPages <= 1) {
			container.html('');
			return;
		}

		let html = '<div class="wc-tp-pagination">';

		if (currentPage > 1) {
			html += '<a href="#" data-page="' + (currentPage - 1) + '">← Previous</a>';
		}

		for (let i = 1; i <= totalPages; i++) {
			if (i === currentPage) {
				html += '<span class="current">' + i + '</span>';
			} else {
				html += '<a href="#" data-page="' + i + '">' + i + '</a>';
			}
		}

		if (currentPage < totalPages) {
			html += '<a href="#" data-page="' + (currentPage + 1) + '">Next →</a>';
		}

		html += '</div>';
		container.html(html);

		container.find('a').on('click', function(e) {
			e.preventDefault();
			currentPage = parseInt(jQuery(this).data('page'));
			renderOrdersTable(allOrdersData);
			renderOrdersPagination(allOrdersData);
			jQuery('html, body').animate({ scrollTop: jQuery('#wc-tp-orders-table-container').offset().top - 100 }, 300);
		});
	}

	function formatCurrencyValue(value, symbol, position) {
		const amount = parseFloat(value).toFixed(2);
		if (position === 'right') {
			return amount + ' ' + symbol;
		} else {
			return symbol + ' ' + amount;
		}
	}
}
