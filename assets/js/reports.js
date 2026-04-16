/**
 * Enterprise Reports Page - Master Filter System
 * WooCommerce Team Payroll
 */

jQuery(document).ready(function($) {
	'use strict';

	// Master filter state
	let masterFilters = {
		dateRange: 'this_month',
		customStartDate: null,
		customEndDate: null,
		orderStatus: 'all',
		role: 'all',
		commissionRange: 'all',
		timePeriod: 'monthly',
		sortBy: 'date',
		sortOrder: 'desc'
	};

	// Table state management
	let tableState = {
		'commission-table': {
			currentPage: 1,
			perPage: 10,
			sortColumn: null,
			sortOrder: 'asc',
			searchTerm: ''
		},
		'orders-table': {
			currentPage: 1,
			perPage: 10,
			sortColumn: null,
			sortOrder: 'asc',
			searchTerm: ''
		}
	};

	// Auto-refresh interval (in milliseconds)
	let autoRefreshInterval = null;
	const AUTO_REFRESH_ENABLED = true;
	const AUTO_REFRESH_DELAY = 30000; // 30 seconds

	// Initialize reports on page load
	initializeReports();

	/**
	 * Initialize reports
	 */
	function initializeReports() {
		// Load filters from localStorage
		loadFilterState();

		// Load initial data
		loadAllReportData();

		// Bind filter events
		bindFilterEvents();

		// Setup auto-refresh
		if (AUTO_REFRESH_ENABLED) {
			setupAutoRefresh();
		}

		// Bind drill-down events
		bindDrillDownEvents();
	}

	/**
	 * Load filter state from localStorage
	 */
	function loadFilterState() {
		const savedFilters = localStorage.getItem('wc_tp_reports_filters');
		if (savedFilters) {
			try {
				const filters = JSON.parse(savedFilters);
				masterFilters = { ...masterFilters, ...filters };

				// Update form inputs with saved values
				$('#reports-date-range').val(masterFilters.dateRange);
				$('#reports-order-status').val(masterFilters.orderStatus);
				$('#reports-role').val(masterFilters.role);
				$('#reports-commission-range').val(masterFilters.commissionRange);
				$('#reports-time-period').val(masterFilters.timePeriod);
				$('#reports-sort-by').val(masterFilters.sortBy);
				$('#reports-sort-order').val(masterFilters.sortOrder);

				// Show custom date inputs if needed
				if (masterFilters.dateRange === 'custom') {
					$('#reports-custom-date-range, #reports-custom-date-range-end').show();
					$('#reports-start-date').val(masterFilters.customStartDate);
					$('#reports-end-date').val(masterFilters.customEndDate);
				}
			} catch (e) {
				console.log('Could not load saved filters');
			}
		}
	}

	/**
	 * Save filter state to localStorage
	 */
	function saveFilterState() {
		localStorage.setItem('wc_tp_reports_filters', JSON.stringify(masterFilters));
	}

	/**
	 * Setup auto-refresh
	 */
	function setupAutoRefresh() {
		// Refresh data every 30 seconds
		autoRefreshInterval = setInterval(function() {
			loadAllReportData();
		}, AUTO_REFRESH_DELAY);
	}

	/**
	 * Bind drill-down events
	 */
	function bindDrillDownEvents() {
		// KPI card click for drill-down
		$(document).on('click', '.reports-kpi-card', function() {
			const cardType = $(this).data('card-type');
			showDrillDownModal(cardType);
		});

		// Goal card click for drill-down
		$(document).on('click', '.reports-goal-card', function() {
			const goalType = $(this).data('goal-type');
			showGoalDetailsModal(goalType);
		});

		// Close modal on background click
		$(document).on('click', '.reports-modal-overlay', function(e) {
			if (e.target === this) {
				closeDrillDownModal();
			}
		});

		// Close modal on close button
		$(document).on('click', '.reports-modal-close', function() {
			closeDrillDownModal();
		});
	}

	/**
	 * Show drill-down modal
	 */
	function showDrillDownModal(cardType) {
		const modal = $(`
			<div class="reports-modal-overlay">
				<div class="reports-modal">
					<div class="reports-modal-header">
						<h3>${cardType.replace(/_/g, ' ').toUpperCase()}</h3>
						<button class="reports-modal-close">
							<i class="ph ph-x"></i>
						</button>
					</div>
					<div class="reports-modal-body">
						<div class="reports-loading">
							<i class="ph ph-spinner"></i>
							<p>Loading details...</p>
						</div>
					</div>
				</div>
			</div>
		`);

		$('body').append(modal);

		// Load drill-down data
		loadDrillDownData(cardType, modal);
	}

	/**
	 * Load drill-down data
	 */
	function loadDrillDownData(cardType, modal) {
		// Simulate drill-down data based on card type
		let content = '';

		switch (cardType) {
			case 'my_earnings':
				content = `
					<div class="drill-down-content">
						<div class="drill-down-section">
							<h4>Earnings Breakdown</h4>
							<div class="breakdown-items">
								<div class="breakdown-item">
									<span class="breakdown-label">Commission Earned</span>
									<span class="breakdown-value">$${(Math.random() * 5000).toFixed(2)}</span>
								</div>
								<div class="breakdown-item">
									<span class="breakdown-label">Salary Portion</span>
									<span class="breakdown-value">$${(Math.random() * 3000).toFixed(2)}</span>
								</div>
								<div class="breakdown-item">
									<span class="breakdown-label">Bonuses</span>
									<span class="breakdown-value">$${(Math.random() * 1000).toFixed(2)}</span>
								</div>
							</div>
						</div>
						<div class="drill-down-section">
							<h4>Top Earning Days</h4>
							<div class="top-items">
								<div class="top-item">
									<span class="item-date">Mar 15, 2024</span>
									<span class="item-amount">$450.00</span>
								</div>
								<div class="top-item">
									<span class="item-date">Mar 12, 2024</span>
									<span class="item-amount">$380.00</span>
								</div>
								<div class="top-item">
									<span class="item-date">Mar 08, 2024</span>
									<span class="item-amount">$320.00</span>
								</div>
							</div>
						</div>
					</div>
				`;
				break;

			case 'my_commission':
				content = `
					<div class="drill-down-content">
						<div class="drill-down-section">
							<h4>Commission Details</h4>
							<div class="breakdown-items">
								<div class="breakdown-item">
									<span class="breakdown-label">Total Commission</span>
									<span class="breakdown-value">$${(Math.random() * 5000).toFixed(2)}</span>
								</div>
								<div class="breakdown-item">
									<span class="breakdown-label">Commission Rate</span>
									<span class="breakdown-value">${(Math.random() * 15).toFixed(1)}%</span>
								</div>
								<div class="breakdown-item">
									<span class="breakdown-label">Orders Counted</span>
									<span class="breakdown-value">${Math.floor(Math.random() * 50)}</span>
								</div>
							</div>
						</div>
						<div class="drill-down-section">
							<h4>Commission by Role</h4>
							<div class="breakdown-items">
								<div class="breakdown-item">
									<span class="breakdown-label">Agent Commission</span>
									<span class="breakdown-value">$${(Math.random() * 3000).toFixed(2)}</span>
								</div>
								<div class="breakdown-item">
									<span class="breakdown-label">Processor Commission</span>
									<span class="breakdown-value">$${(Math.random() * 2000).toFixed(2)}</span>
								</div>
							</div>
						</div>
					</div>
				`;
				break;

			case 'my_orders':
				content = `
					<div class="drill-down-content">
						<div class="drill-down-section">
							<h4>Order Statistics</h4>
							<div class="breakdown-items">
								<div class="breakdown-item">
									<span class="breakdown-label">Total Orders</span>
									<span class="breakdown-value">${Math.floor(Math.random() * 100)}</span>
								</div>
								<div class="breakdown-item">
									<span class="breakdown-label">Completed Orders</span>
									<span class="breakdown-value">${Math.floor(Math.random() * 80)}</span>
								</div>
								<div class="breakdown-item">
									<span class="breakdown-label">Processing Orders</span>
									<span class="breakdown-value">${Math.floor(Math.random() * 20)}</span>
								</div>
							</div>
						</div>
						<div class="drill-down-section">
							<h4>Order Status Distribution</h4>
							<div class="breakdown-items">
								<div class="breakdown-item">
									<span class="breakdown-label">Completed</span>
									<span class="breakdown-value">${Math.floor(Math.random() * 80)}</span>
								</div>
								<div class="breakdown-item">
									<span class="breakdown-label">Pending</span>
									<span class="breakdown-value">${Math.floor(Math.random() * 10)}</span>
								</div>
								<div class="breakdown-item">
									<span class="breakdown-label">Refunded</span>
									<span class="breakdown-value">${Math.floor(Math.random() * 5)}</span>
								</div>
							</div>
						</div>
					</div>
				`;
				break;

			case 'my_average_order_value':
				content = `
					<div class="drill-down-content">
						<div class="drill-down-section">
							<h4>Average Order Value Analysis</h4>
							<div class="breakdown-items">
								<div class="breakdown-item">
									<span class="breakdown-label">Average Order Value</span>
									<span class="breakdown-value">$${(Math.random() * 500).toFixed(2)}</span>
								</div>
								<div class="breakdown-item">
									<span class="breakdown-label">Highest Order</span>
									<span class="breakdown-value">$${(Math.random() * 2000 + 500).toFixed(2)}</span>
								</div>
								<div class="breakdown-item">
									<span class="breakdown-label">Lowest Order</span>
									<span class="breakdown-value">$${(Math.random() * 100).toFixed(2)}</span>
								</div>
							</div>
						</div>
						<div class="drill-down-section">
							<h4>Order Value Distribution</h4>
							<div class="breakdown-items">
								<div class="breakdown-item">
									<span class="breakdown-label">$0 - $100</span>
									<span class="breakdown-value">${Math.floor(Math.random() * 30)}</span>
								</div>
								<div class="breakdown-item">
									<span class="breakdown-label">$100 - $500</span>
									<span class="breakdown-value">${Math.floor(Math.random() * 40)}</span>
								</div>
								<div class="breakdown-item">
									<span class="breakdown-label">$500+</span>
									<span class="breakdown-value">${Math.floor(Math.random() * 20)}</span>
								</div>
							</div>
						</div>
					</div>
				`;
				break;

			case 'my_performance_score':
				content = `
					<div class="drill-down-content">
						<div class="drill-down-section">
							<h4>Performance Score Breakdown</h4>
							<div class="breakdown-items">
								<div class="breakdown-item">
									<span class="breakdown-label">Base Score</span>
									<span class="breakdown-value">5.0</span>
								</div>
								<div class="breakdown-item">
									<span class="breakdown-label">Order Bonus</span>
									<span class="breakdown-value">+${(Math.random() * 2).toFixed(1)}</span>
								</div>
								<div class="breakdown-item">
									<span class="breakdown-label">Earnings Bonus</span>
									<span class="breakdown-value">+${(Math.random() * 2).toFixed(1)}</span>
								</div>
								<div class="breakdown-item">
									<span class="breakdown-label">AOV Bonus</span>
									<span class="breakdown-value">+${(Math.random() * 1).toFixed(1)}</span>
								</div>
							</div>
						</div>
						<div class="drill-down-section">
							<h4>Performance Rating</h4>
							<div class="breakdown-items">
								<div class="breakdown-item">
									<span class="breakdown-label">Current Rating</span>
									<span class="breakdown-value">Excellent</span>
								</div>
								<div class="breakdown-item">
									<span class="breakdown-label">Trend</span>
									<span class="breakdown-value"><i class="ph ph-trend-up"></i> Improving</span>
								</div>
							</div>
						</div>
					</div>
				`;
				break;

			default:
				content = '<p>No details available</p>';
		}

		modal.find('.reports-modal-body').html(content);
	}

	/**
	 * Show goal details modal
	 */
	function showGoalDetailsModal(goalType) {
		const modal = $(`
			<div class="reports-modal-overlay">
				<div class="reports-modal">
					<div class="reports-modal-header">
						<h3>${goalType.replace(/_/g, ' ').toUpperCase()}</h3>
						<button class="reports-modal-close">
							<i class="ph ph-x"></i>
						</button>
					</div>
					<div class="reports-modal-body">
						<div class="goal-details-content">
							<div class="goal-detail-section">
								<h4>Goal Progress</h4>
								<div class="progress-detail">
									<div class="progress-bar-large">
										<div class="progress-fill" style="width: ${Math.random() * 100}%"></div>
									</div>
									<div class="progress-info">
										<span class="progress-actual">Actual: $${(Math.random() * 5000).toFixed(2)}</span>
										<span class="progress-target">Target: $5,000</span>
									</div>
								</div>
							</div>
							<div class="goal-detail-section">
								<h4>Historical Performance</h4>
								<div class="history-items">
									<div class="history-item">
										<span class="history-period">This Month</span>
										<span class="history-value">$${(Math.random() * 5000).toFixed(2)}</span>
									</div>
									<div class="history-item">
										<span class="history-period">Last Month</span>
										<span class="history-value">$${(Math.random() * 5000).toFixed(2)}</span>
									</div>
									<div class="history-item">
										<span class="history-period">2 Months Ago</span>
										<span class="history-value">$${(Math.random() * 5000).toFixed(2)}</span>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		`);

		$('body').append(modal);
	}

	/**
	 * Close drill-down modal
	 */
	function closeDrillDownModal() {
		$('.reports-modal-overlay').fadeOut(200, function() {
			$(this).remove();
		});
	}

	/**
	 * Bind filter change events
	 */
	function bindFilterEvents() {
		// Date range change
		$('#reports-date-range').on('change', function() {
			const value = $(this).val();
			if (value === 'custom') {
				$('#reports-custom-date-range, #reports-custom-date-range-end').show();
			} else {
				$('#reports-custom-date-range, #reports-custom-date-range-end').hide();
				masterFilters.dateRange = value;
			}
		});

		// Apply filters button
		$('#reports-apply-filters').on('click', function() {
			updateMasterFilters();
			saveFilterState();
			loadAllReportData();
			updateFilterSummary();
		});

		// Clear filters button
		$('#reports-clear-filters').on('click', function() {
			resetFilters();
			saveFilterState();
			loadAllReportData();
		});
	}

	/**
	 * Update master filters from form inputs
	 */
	function updateMasterFilters() {
		const dateRange = $('#reports-date-range').val();

		if (dateRange === 'custom') {
			masterFilters.customStartDate = $('#reports-start-date').val();
			masterFilters.customEndDate = $('#reports-end-date').val();
			masterFilters.dateRange = 'custom';
		} else {
			masterFilters.dateRange = dateRange;
			masterFilters.customStartDate = null;
			masterFilters.customEndDate = null;
		}

		masterFilters.orderStatus = $('#reports-order-status').val();
		masterFilters.role = $('#reports-role').val();
		masterFilters.commissionRange = $('#reports-commission-range').val();
		masterFilters.timePeriod = $('#reports-time-period').val();
		masterFilters.sortBy = $('#reports-sort-by').val();
		masterFilters.sortOrder = $('#reports-sort-order').val();
	}

	/**
	 * Reset all filters to defaults
	 */
	function resetFilters() {
		masterFilters = {
			dateRange: 'this_month',
			customStartDate: null,
			customEndDate: null,
			orderStatus: 'all',
			role: 'all',
			commissionRange: 'all',
			timePeriod: 'monthly',
			sortBy: 'date',
			sortOrder: 'desc'
		};

		// Reset form inputs
		$('#reports-date-range').val('this_month');
		$('#reports-order-status').val('all');
		$('#reports-role').val('all');
		$('#reports-commission-range').val('all');
		$('#reports-time-period').val('monthly');
		$('#reports-sort-by').val('date');
		$('#reports-sort-order').val('desc');
		$('#reports-custom-date-range, #reports-custom-date-range-end').hide();
		$('#reports-filter-summary').hide();

		// Update clear button state
		$('#reports-clear-filters').removeClass('active');

		// Reset table states
		tableState = {
			'commission-table': {
				currentPage: 1,
				perPage: 10,
				sortColumn: null,
				sortOrder: 'asc',
				searchTerm: ''
			},
			'orders-table': {
				currentPage: 1,
				perPage: 10,
				sortColumn: null,
				sortOrder: 'asc',
				searchTerm: ''
			}
		};
	}

	/**
	 * Update filter summary display
	 */
	function updateFilterSummary() {
		const activeFilters = [];

		if (masterFilters.dateRange !== 'this_month') {
			activeFilters.push(masterFilters.dateRange.replace(/_/g, ' '));
		}
		if (masterFilters.orderStatus !== 'all') {
			activeFilters.push('Status: ' + masterFilters.orderStatus);
		}
		if (masterFilters.role !== 'all') {
			activeFilters.push('Role: ' + masterFilters.role);
		}
		if (masterFilters.commissionRange !== 'all') {
			activeFilters.push('Commission: ' + masterFilters.commissionRange);
		}

		if (activeFilters.length > 0) {
			$('#reports-active-filters-text').text(activeFilters.join(', '));
			$('#reports-filter-summary').show();
			$('#reports-clear-filters').addClass('active');
		} else {
			$('#reports-filter-summary').hide();
			$('#reports-clear-filters').removeClass('active');
		}
	}

	/**
	 * Load all report data with current filters
	 */
	function loadAllReportData() {
		loadDashboardData();
		loadAnalyticsData();
		loadPerformanceMetrics();
		loadTableData();
		loadGoalsData();
	}

	/**
	 * Load KPI dashboard data
	 */
	function loadDashboardData() {
		$.ajax({
			url: wc_tp_reports.ajax_url,
			type: 'POST',
			data: {
				action: 'wc_tp_get_filtered_dashboard_data',
				nonce: wc_tp_reports.nonce,
				filters: masterFilters
			},
			success: function(response) {
				if (response.success) {
					// Fade out old content
					$('#reports-kpi-container').fadeOut(200, function() {
						$(this).html(response.data.html).fadeIn(300);
					});
				}
			},
			error: function() {
				$('#reports-kpi-container').html('<div class="reports-no-data"><i class="ph ph-warning"></i><p>Error loading dashboard data</p></div>');
			}
		});
	}

	/**
	 * Load analytics charts data
	 */
	function loadAnalyticsData() {
		$.ajax({
			url: wc_tp_reports.ajax_url,
			type: 'POST',
			data: {
				action: 'wc_tp_get_filtered_analytics_data',
				nonce: wc_tp_reports.nonce,
				filters: masterFilters
			},
			success: function(response) {
				if (response.success) {
					// Fade out old content
					$('#reports-charts-container').fadeOut(200, function() {
						$(this).html(response.data.html).fadeIn(300);
					});
				}
			},
			error: function() {
				$('#reports-charts-container').html('<div class="reports-no-data"><i class="ph ph-warning"></i><p>Error loading analytics data</p></div>');
			}
		});
	}

	/**
	 * Load performance metrics
	 */
	function loadPerformanceMetrics() {
		$.ajax({
			url: wc_tp_reports.ajax_url,
			type: 'POST',
			data: {
				action: 'wc_tp_get_filtered_performance_data',
				nonce: wc_tp_reports.nonce,
				filters: masterFilters
			},
			success: function(response) {
				if (response.success) {
					// Fade out old content
					$('#reports-metrics-container').fadeOut(200, function() {
						$(this).html(response.data.html).fadeIn(300);
					});
				}
			},
			error: function() {
				$('#reports-metrics-container').html('<div class="reports-no-data"><i class="ph ph-warning"></i><p>Error loading metrics</p></div>');
			}
		});
	}

	/**
	 * Load data tables
	 */
	function loadTableData() {
		$.ajax({
			url: wc_tp_reports.ajax_url,
			type: 'POST',
			data: {
				action: 'wc_tp_get_filtered_table_data',
				nonce: wc_tp_reports.nonce,
				filters: masterFilters
			},
			success: function(response) {
				if (response.success) {
					// Fade out old content
					$('#reports-tables-container').fadeOut(200, function() {
						$(this).html(response.data.html).fadeIn(300);
						// Bind table events after content loads
						bindTableEvents();
						// Initialize pagination for all tables
						initializeTablePagination();
					});
				}
			},
			error: function() {
				$('#reports-tables-container').html('<div class="reports-no-data"><i class="ph ph-warning"></i><p>Error loading tables</p></div>');
			}
		});
	}

	/**
	 * Load goals and achievements data
	 */
	function loadGoalsData() {
		$.ajax({
			url: wc_tp_reports.ajax_url,
			type: 'POST',
			data: {
				action: 'wc_tp_get_filtered_goals_data',
				nonce: wc_tp_reports.nonce,
				filters: masterFilters
			},
			success: function(response) {
				if (response.success) {
					// Fade out old content
					$('#reports-goals-container').fadeOut(200, function() {
						$(this).html(response.data.html).fadeIn(300);
					});
				}
			},
			error: function() {
				$('#reports-goals-container').html('<div class="reports-no-data"><i class="ph ph-warning"></i><p>Error loading goals</p></div>');
			}
		});
	}

	/**
	 * Bind table events (search, sort, pagination)
	 */
	function bindTableEvents() {
		// Search functionality
		$(document).on('keyup', '.table-search-input', function() {
			const tableId = $(this).data('table');
			const searchTerm = $(this).val().toLowerCase();
			tableState[tableId].searchTerm = searchTerm;
			tableState[tableId].currentPage = 1;
			filterAndDisplayTable(tableId);
		});

		// Per-page selection
		$(document).on('change', '.table-per-page-select', function() {
			const tableId = $(this).data('table');
			tableState[tableId].perPage = parseInt($(this).val());
			tableState[tableId].currentPage = 1;
			filterAndDisplayTable(tableId);
		});

		// Column sorting
		$(document).on('click', '.reports-table th.sortable', function() {
			const tableId = $(this).closest('.reports-table').attr('id');
			const sortColumn = $(this).data('sort');
			
			// Toggle sort order if clicking same column
			if (tableState[tableId].sortColumn === sortColumn) {
				tableState[tableId].sortOrder = tableState[tableId].sortOrder === 'asc' ? 'desc' : 'asc';
			} else {
				tableState[tableId].sortColumn = sortColumn;
				tableState[tableId].sortOrder = 'asc';
			}
			
			tableState[tableId].currentPage = 1;
			filterAndDisplayTable(tableId);
		});

		// Pagination
		$(document).on('click', '.reports-page-btn', function(e) {
			e.preventDefault();
			const page = parseInt($(this).data('page'));
			const tableId = $(this).closest('.reports-pagination').data('table');
			tableState[tableId].currentPage = page;
			filterAndDisplayTable(tableId);
		});
	}

	/**
	 * Filter and display table data
	 */
	function filterAndDisplayTable(tableId) {
		const $table = $('#' + tableId);
		const $tbody = $table.find('tbody');
		let rows = $tbody.find('tr').not(':has(.reports-no-data)').toArray();

		// Apply search filter
		if (tableState[tableId].searchTerm) {
			rows = rows.filter(function() {
				const text = $(this).text().toLowerCase();
				return text.includes(tableState[tableId].searchTerm);
			});
		}

		// Apply sorting
		if (tableState[tableId].sortColumn) {
			const sortColumn = tableState[tableId].sortColumn;
			const sortOrder = tableState[tableId].sortOrder;
			
			rows.sort(function(a, b) {
				let aVal, bVal;
				
				// Find the column index
				const $headers = $table.find('th');
				let colIndex = 0;
				$headers.each(function(i) {
					if ($(this).data('sort') === sortColumn) {
						colIndex = i;
						return false;
					}
				});
				
				aVal = $(a).find('td').eq(colIndex).text().trim();
				bVal = $(b).find('td').eq(colIndex).text().trim();
				
				// Try to parse as numbers
				const aNum = parseFloat(aVal.replace(/[^0-9.-]/g, ''));
				const bNum = parseFloat(bVal.replace(/[^0-9.-]/g, ''));
				
				if (!isNaN(aNum) && !isNaN(bNum)) {
					return sortOrder === 'asc' ? aNum - bNum : bNum - aNum;
				}
				
				// String comparison
				if (sortOrder === 'asc') {
					return aVal.localeCompare(bVal);
				} else {
					return bVal.localeCompare(aVal);
				}
			});
		}

		// Calculate pagination
		const totalRows = rows.length;
		const perPage = tableState[tableId].perPage;
		const totalPages = Math.ceil(totalRows / perPage);
		const currentPage = Math.min(tableState[tableId].currentPage, totalPages) || 1;
		const startIndex = (currentPage - 1) * perPage;
		const endIndex = startIndex + perPage;

		// Hide all rows
		$tbody.find('tr').hide();

		// Show filtered and paginated rows
		if (rows.length === 0) {
			$tbody.find('tr:has(.reports-no-data)').show();
		} else {
			rows.slice(startIndex, endIndex).forEach(function(row) {
				$(row).show();
			});
		}

		// Update pagination info
		const $pagination = $('[data-table="' + tableId + '"]');
		$pagination.find('.pagination-start').text(rows.length === 0 ? 0 : startIndex + 1);
		$pagination.find('.pagination-end').text(Math.min(endIndex, totalRows));
		$pagination.find('.pagination-total').text(totalRows);

		// Generate pagination buttons
		generatePaginationButtons(tableId, totalPages, currentPage);
	}

	/**
	 * Generate pagination buttons
	 */
	function generatePaginationButtons(tableId, totalPages, currentPage) {
		const $controls = $('[data-table="' + tableId + '"] .reports-pagination-controls');
		$controls.empty();

		if (totalPages <= 1) {
			return;
		}

		// Previous button
		if (currentPage > 1) {
			$controls.append('<a href="#" class="reports-page-btn" data-page="' + (currentPage - 1) + '"><i class="ph ph-caret-left"></i></a>');
		}

		// Page numbers
		let startPage = Math.max(1, currentPage - 2);
		let endPage = Math.min(totalPages, currentPage + 2);

		if (startPage > 1) {
			$controls.append('<a href="#" class="reports-page-btn" data-page="1">1</a>');
			if (startPage > 2) {
				$controls.append('<span class="reports-page-ellipsis">...</span>');
			}
		}

		for (let i = startPage; i <= endPage; i++) {
			const activeClass = i === currentPage ? 'current' : '';
			$controls.append('<a href="#" class="reports-page-btn ' + activeClass + '" data-page="' + i + '">' + i + '</a>');
		}

		if (endPage < totalPages) {
			if (endPage < totalPages - 1) {
				$controls.append('<span class="reports-page-ellipsis">...</span>');
			}
			$controls.append('<a href="#" class="reports-page-btn" data-page="' + totalPages + '">' + totalPages + '</a>');
		}

		// Next button
		if (currentPage < totalPages) {
			$controls.append('<a href="#" class="reports-page-btn" data-page="' + (currentPage + 1) + '"><i class="ph ph-caret-right"></i></a>');
		}
	}

	/**
	 * Initialize table pagination on load
	 */
	function initializeTablePagination() {
		Object.keys(tableState).forEach(function(tableId) {
			filterAndDisplayTable(tableId);
		});
	}

	// Export functions
	$('#reports-export-csv').on('click', function() {
		exportReportData('csv');
	});

	$('#reports-export-pdf').on('click', function() {
		exportReportData('pdf');
	});

	$('#reports-print-report').on('click', function() {
		window.print();
	});

	/**
	 * Export report data
	 */
	function exportReportData(format) {
		$.ajax({
			url: wc_tp_reports.ajax_url,
			type: 'POST',
			data: {
				action: 'wc_tp_export_filtered_report',
				nonce: wc_tp_reports.nonce,
				filters: masterFilters,
				format: format
			},
			success: function(response) {
				if (response.success) {
					// Trigger download
					window.location.href = response.data.download_url;
				}
			}
		});
	}
});
