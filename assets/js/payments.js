jQuery(document).ready(function($) {
	'use strict';

	const wcTpPayments = window.wcTpPayments || {};
	let currentPage = 1;
	let currentPerPage = 20;
	let currentSort = { by: 'date', order: 'desc' };

	// Default strings if not provided
	const strings = {
		selectPaymentMethod: 'Select payment method...',
		noPaymentMethods: 'No payment methods available',
		loading: 'Loading...',
		error: 'An error occurred',
		confirmDelete: 'Are you sure you want to delete this payment?',
		paymentAdded: 'Payment added successfully',
		paymentDeleted: 'Payment deleted successfully',
		fillAllFields: 'Please fill all required fields',
		noPayments: 'No payments found',
		employee: 'Employee',
		date: 'Date',
		amount: 'Amount',
		paymentMethod: 'Payment Method',
		addedBy: 'Added By',
		actions: 'Actions',
		delete: 'Delete',
		previous: 'Previous',
		next: 'Next',
		...wcTpPayments.strings
	};

	/**
	 * Initialize page
	 */
	function init() {
		loadEmployees();
		loadPaymentMethods();
		loadPaymentsTable();
		bindEvents();
	}

	/**
	 * Load employees dropdown
	 */
	function loadEmployees() {
		$.ajax({
			url: wcTpPayments.ajaxUrl,
			type: 'POST',
			data: {
				action: 'wc_tp_get_all_employees',
				nonce: wcTpPayments.nonce,
			},
			success: function(response) {
				if (response.success) {
					const $select = $('#wc-tp-employee-select');
					const employees = response.data.employees || [];

					employees.forEach(function(employee) {
						$select.append(
							$('<option></option>')
								.val(employee.id)
								.text(employee.name + ' (' + employee.email + ')')
						);
					});
				}
			},
		});
	}

	/**
	 * Load payment methods for selected employee
	 */
	function loadEmployeePaymentMethods(employeeId) {
		if (!employeeId) {
			$('#wc-tp-payment-method').html('<option value="">' + strings.selectPaymentMethod + '</option>');
			$('#wc-tp-method-details').hide();
			return;
		}

		$.ajax({
			url: wcTpPayments.ajaxUrl,
			type: 'POST',
			data: {
				action: 'wc_tp_get_payment_methods',
				user_id: employeeId,
				nonce: wcTpPayments.nonce,
			},
			success: function(response) {
				if (response.success) {
					const $select = $('#wc-tp-payment-method');
					const methods = response.data.methods || [];

					$select.html('<option value="">' + strings.selectPaymentMethod + '</option>');

					if (methods.length === 0) {
						$select.html('<option value="" disabled>' + strings.noPaymentMethods + '</option>');
						$('#wc-tp-method-details').hide();
						return;
					}

					methods.forEach(function(method) {
						$select.append(
							$('<option></option>')
								.val(method.id)
								.text(method.method_name + ' - ' + method.method_details)
								.data('details', method.method_details)
								.data('note', method.note || '')
						);
					});

					$select.on('change', function() {
						const $option = $(this).find('option:selected');
						const details = $option.data('details');
						const note = $option.data('note');

						if (details) {
							$('#wc-tp-method-details').text(details + (note ? ' (' + note + ')' : '')).show();
						} else {
							$('#wc-tp-method-details').hide();
						}
					});
				}
			},
		});
	}

	/**
	 * Load all unique payment methods for filter
	 */
	function loadPaymentMethods() {
		$.ajax({
			url: wcTpPayments.ajaxUrl,
			type: 'POST',
			data: {
				action: 'wc_tp_get_all_payment_methods',
				nonce: wcTpPayments.nonce,
			},
			success: function(response) {
				if (response.success) {
					const $select = $('#wc-tp-payments-method-filter');
					const methods = response.data.methods || [];

					methods.forEach(function(method) {
						$select.append(
							$('<option></option>')
								.val(method.name)
								.text(method.name)
						);
					});
				}
			},
		});
	}

	/**
	 * Load payments table
	 */
	function loadPaymentsTable() {
		const filters = getFilters();

		$('#wc-tp-payments-table-container').html('<div class="wc-tp-loading">' + strings.loading + '</div>');

		$.ajax({
			url: wcTpPayments.ajaxUrl,
			type: 'POST',
			data: {
				action: 'wc_tp_get_payments_table',
				page: currentPage,
				per_page: currentPerPage,
				search: filters.search,
				start_date: filters.startDate,
				end_date: filters.endDate,
				payment_method: filters.paymentMethod,
				sort_by: currentSort.by,
				sort_order: currentSort.order,
				nonce: wcTpPayments.nonce,
			},
			success: function(response) {
				if (response.success) {
					renderPaymentsTable(response.data);
					renderPagination(response.data);
				} else {
					$('#wc-tp-payments-table-container').html(
						'<div class="wc-tp-error-message">' + (response.data.message || strings.error) + '</div>'
					);
				}
			},
			error: function() {
				$('#wc-tp-payments-table-container').html(
					'<div class="wc-tp-error-message">' + strings.error + '</div>'
				);
			},
		});
	}

	/**
	 * Render payments table
	 */
	function renderPaymentsTable(data) {
		const payments = data.payments || [];

		if (payments.length === 0) {
			$('#wc-tp-payments-table-container').html(
				'<div class="wc-tp-no-data">' + strings.noPayments + '</div>'
			);
			return;
		}

		let html = '<table class="wc-tp-payments-table"><thead><tr>';
		html += '<th class="sortable" data-sort="user_name">' + strings.employee + '</th>';
		html += '<th class="sortable" data-sort="date">' + strings.date + '</th>';
		html += '<th class="sortable" data-sort="amount">' + strings.amount + '</th>';
		html += '<th>' + strings.paymentMethod + '</th>';
		html += '<th>' + strings.addedBy + '</th>';
		html += '<th>' + strings.actions + '</th>';
		html += '</tr></thead><tbody>';

		payments.forEach(function(payment) {
			html += '<tr>';
			html += '<td><strong>' + escapeHtml(payment.user_name) + '</strong><br><small>' + escapeHtml(payment.user_email) + '</small></td>';
			html += '<td>' + escapeHtml(payment.date_formatted) + '</td>';
			html += '<td class="wc-tp-amount">' + escapeHtml(payment.amount_formatted) + '</td>';
			html += '<td>' + escapeHtml(payment.payment_method) + '</td>';
			html += '<td>' + escapeHtml(payment.added_by) + '</td>';
			html += '<td class="wc-tp-action-buttons">';
			html += '<button type="button" class="button button-small wc-tp-delete-btn" data-payment-id="' + escapeHtml(payment.id) + '" data-user-id="' + escapeHtml(payment.user_id) + '">' + strings.delete + '</button>';
			html += '</td>';
			html += '</tr>';
		});

		html += '</tbody></table>';

		$('#wc-tp-payments-table-container').html(html);

		// Bind delete buttons
		$('.wc-tp-delete-btn').on('click', function() {
			deletePayment($(this));
		});

		// Bind sort headers
		$('th.sortable').on('click', function() {
			const sortBy = $(this).data('sort');
			if (currentSort.by === sortBy) {
				currentSort.order = currentSort.order === 'asc' ? 'desc' : 'asc';
			} else {
				currentSort.by = sortBy;
				currentSort.order = 'asc';
			}
			currentPage = 1;
			loadPaymentsTable();
		});
	}

	/**
	 * Render pagination
	 */
	function renderPagination(data) {
		const totalPages = data.total_pages || 1;
		const page = data.page || 1;

		if (totalPages <= 1) {
			$('#wc-tp-payments-pagination').html('');
			return;
		}

		let html = '<div class="wc-tp-pagination">';

		// Previous button
		if (page > 1) {
			html += '<a href="#" data-page="' + (page - 1) + '">&laquo; ' + strings.previous + '</a>';
		} else {
			html += '<span class="disabled">&laquo; ' + strings.previous + '</span>';
		}

		// Page numbers
		const startPage = Math.max(1, page - 2);
		const endPage = Math.min(totalPages, page + 2);

		if (startPage > 1) {
			html += '<a href="#" data-page="1">1</a>';
			if (startPage > 2) {
				html += '<span>...</span>';
			}
		}

		for (let i = startPage; i <= endPage; i++) {
			if (i === page) {
				html += '<span class="current">' + i + '</span>';
			} else {
				html += '<a href="#" data-page="' + i + '">' + i + '</a>';
			}
		}

		if (endPage < totalPages) {
			if (endPage < totalPages - 1) {
				html += '<span>...</span>';
			}
			html += '<a href="#" data-page="' + totalPages + '">' + totalPages + '</a>';
		}

		// Next button
		if (page < totalPages) {
			html += '<a href="#" data-page="' + (page + 1) + '">' + strings.next + ' &raquo;</a>';
		} else {
			html += '<span class="disabled">' + strings.next + ' &raquo;</span>';
		}

		html += '</div>';

		$('#wc-tp-payments-pagination').html(html);

		// Bind pagination links
		$('#wc-tp-payments-pagination a').on('click', function(e) {
			e.preventDefault();
			currentPage = parseInt($(this).data('page'));
			loadPaymentsTable();
		});
	}

	/**
	 * Delete payment
	 */
	function deletePayment($btn) {
		if (!confirm(strings.confirmDelete)) {
			return;
		}

		const paymentId = $btn.data('payment-id');
		const userId = $btn.data('user-id');

		$.ajax({
			url: wcTpPayments.ajaxUrl,
			type: 'POST',
			data: {
				action: 'wc_tp_delete_payment',
				user_id: userId,
				payment_id: paymentId,
				nonce: wcTpPayments.nonce,
			},
			success: function(response) {
				if (response.success) {
					showMessage(strings.paymentDeleted, 'success');
					loadPaymentsTable();
				} else {
					showMessage(response.data.message || strings.error, 'error');
				}
			},
			error: function() {
				showMessage(strings.error, 'error');
			},
		});
	}

	/**
	 * Get filter values
	 */
	function getFilters() {
		const datePreset = $('#wc-tp-payments-date-preset').val();
		let startDate = '';
		let endDate = '';

		if (datePreset === 'custom') {
			startDate = $('#wc-tp-payments-start-date').val();
			endDate = $('#wc-tp-payments-end-date').val();
		} else if (datePreset !== 'all-time') {
			const dates = getDateRange(datePreset);
			startDate = dates.start;
			endDate = dates.end;
		}

		return {
			search: $('#wc-tp-payments-search').val(),
			startDate: startDate,
			endDate: endDate,
			paymentMethod: $('#wc-tp-payments-method-filter').val(),
		};
	}

	/**
	 * Get date range from preset
	 */
	function getDateRange(preset) {
		const today = new Date();
		let start, end;

		switch (preset) {
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
				end.setDate(today.getDate() - today.getDay());
				start = new Date(end);
				start.setDate(end.getDate() - 7);
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
			default:
				start = new Date(today);
				end = new Date(today);
		}

		return {
			start: formatDate(start),
			end: formatDate(end),
		};
	}

	/**
	 * Format date to YYYY-MM-DD
	 */
	function formatDate(date) {
		const year = date.getFullYear();
		const month = String(date.getMonth() + 1).padStart(2, '0');
		const day = String(date.getDate()).padStart(2, '0');
		return year + '-' + month + '-' + day;
	}

	/**
	 * Show message
	 */
	function showMessage(message, type) {
		const className = type === 'success' ? 'wc-tp-success-message' : 'wc-tp-error-message';
		const $message = $('<div class="' + className + '">' + message + '</div>');

		$('#wc-tp-payment-form').before($message);

		setTimeout(function() {
			$message.fadeOut(function() {
				$(this).remove();
			});
		}, 5000);
	}

	/**
	 * Escape HTML
	 */
	function escapeHtml(text) {
		const map = {
			'&': '&amp;',
			'<': '&lt;',
			'>': '&gt;',
			'"': '&quot;',
			"'": '&#039;',
		};
		return text.replace(/[&<>"']/g, function(m) {
			return map[m];
		});
	}

	/**
	 * Bind events
	 */
	function bindEvents() {
		// Employee select change
		$('#wc-tp-employee-select').on('change', function() {
			loadEmployeePaymentMethods($(this).val());
		});

		// Date preset change
		$('#wc-tp-payments-date-preset').on('change', function() {
			if ($(this).val() === 'custom') {
				$('#wc-tp-payments-custom-date-range').show();
			} else {
				$('#wc-tp-payments-custom-date-range').hide();
			}
		});

		// Filter button
		$('#wc-tp-payments-filter-btn').on('click', function() {
			currentPage = 1;
			loadPaymentsTable();
		});

		// Reset button
		$('#wc-tp-payments-reset-btn').on('click', function() {
			$('#wc-tp-payments-search').val('');
			$('#wc-tp-payments-date-preset').val('this-month');
			$('#wc-tp-payments-method-filter').val('');
			$('#wc-tp-payments-custom-date-range').hide();
			currentPage = 1;
			loadPaymentsTable();
		});

		// Per page change
		$('#wc-tp-payments-per-page').on('change', function() {
			currentPerPage = parseInt($(this).val());
			currentPage = 1;
			loadPaymentsTable();
		});

		// Payment form submit
		$('#wc-tp-payment-form').on('submit', function(e) {
			e.preventDefault();

			const employeeId = $('#wc-tp-employee-select').val();
			const amount = $('#wc-tp-payment-amount').val();
			const paymentDate = $('#wc-tp-payment-datetime').val();
			const paymentMethodId = $('#wc-tp-payment-method').val();
			const note = $('#wc-tp-payment-note').val();

			if (!employeeId || !amount || !paymentDate || !paymentMethodId) {
				showMessage(strings.fillAllFields, 'error');
				return;
			}

			$.ajax({
				url: wcTpPayments.ajaxUrl,
				type: 'POST',
				data: {
					action: 'wc_tp_add_payment',
					user_id: employeeId,
					amount: amount,
					payment_date: paymentDate,
					payment_method_id: paymentMethodId,
					note: note,
					nonce: wcTpPayments.nonce,
				},
				success: function(response) {
					if (response.success) {
						showMessage(strings.paymentAdded, 'success');
						$('#wc-tp-payment-form')[0].reset();
						$('#wc-tp-payment-method').html('<option value="">' + strings.selectPaymentMethod + '</option>');
						$('#wc-tp-method-details').hide();
						currentPage = 1;
						loadPaymentsTable();
					} else {
						showMessage(response.data.message || strings.error, 'error');
					}
				},
				error: function() {
					showMessage(strings.error, 'error');
				},
			});
		});
	}

	// Initialize
	init();
});
