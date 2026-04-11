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
