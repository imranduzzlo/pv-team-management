/**
 * Global Search for WooCommerce Team Payroll
 */

jQuery(document).ready(function($) {
	const searchInput = $('#wc-tp-global-search');
	const searchResults = $('#wc-tp-search-results');
	let searchTimeout;
	let currentPage = 1;
	let currentQuery = '';
	let allResults = [];

	// Search input handler
	searchInput.on('keyup', function() {
		const query = $(this).val().trim();

		if (query.length < 2) {
			searchResults.removeClass('show').html('');
			return;
		}

		clearTimeout(searchTimeout);
		currentQuery = query;
		currentPage = 1;

		searchTimeout = setTimeout(function() {
			performSearch(query);
		}, 300);
	});

	// Close search results when clicking outside
	$(document).on('click', function(e) {
		if (!$(e.target).closest('.wc-tp-global-search-container').length) {
			searchResults.removeClass('show');
		}
	});

	function performSearch(query) {
		searchResults.html('<div class="wc-tp-search-loading">Searching...</div>').addClass('show');

		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'wc_tp_global_search',
				query: query,
				nonce: wc_tp_search_nonce
			},
			success: function(response) {
				if (response.success) {
					allResults = response.data.results;
					renderSearchResults(allResults, currentPage);
				} else {
					searchResults.html('<div class="wc-tp-search-empty">No results found</div>');
				}
			},
			error: function() {
				searchResults.html('<div class="wc-tp-search-empty">Error performing search</div>');
			}
		});
	}

	function renderSearchResults(results, page) {
		if (!results || results.length === 0) {
			searchResults.html('<div class="wc-tp-search-empty">No results found for "' + currentQuery + '"</div>');
			return;
		}

		const itemsPerPage = 10;
		const totalPages = Math.ceil(results.length / itemsPerPage);
		const startIndex = (page - 1) * itemsPerPage;
		const endIndex = startIndex + itemsPerPage;
		const paginatedResults = results.slice(startIndex, endIndex);

		let html = '';

		$.each(paginatedResults, function(i, result) {
			html += renderResultItem(result);
		});

		// Add pagination if needed
		if (totalPages > 1) {
			html += '<div class="wc-tp-search-pagination">';
			for (let i = 1; i <= totalPages; i++) {
				const activeClass = i === page ? 'active' : '';
				html += '<button type="button" class="wc-tp-search-page-btn ' + activeClass + '" data-page="' + i + '">' + i + '</button>';
			}
			html += '</div>';
		}

		searchResults.html(html).addClass('show');

		// Attach pagination handlers
		$('.wc-tp-search-page-btn').on('click', function() {
			currentPage = parseInt($(this).data('page'));
			renderSearchResults(allResults, currentPage);
		});

		// Attach result item click handlers
		$('.wc-tp-search-result-item').on('click', function(e) {
			e.preventDefault();
			const url = $(this).data('url');
			if (url) {
				window.location.href = url;
			}
		});
	}

	function renderResultItem(result) {
		let badgeClass = 'order';
		let badgeText = 'Order';

		if (result.type === 'employee') {
			badgeClass = 'employee';
			badgeText = 'Employee';
		} else if (result.type === 'customer') {
			badgeClass = 'customer';
			badgeText = 'Customer';
		} else if (result.type === 'payment') {
			badgeClass = 'payment';
			badgeText = 'Payment';
		}

		let metaHtml = '';
		if (result.meta && result.meta.length > 0) {
			$.each(result.meta, function(i, meta) {
				metaHtml += '<span>' + meta + '</span>';
			});
		}

		return `
			<a href="${result.url}" class="wc-tp-search-result-item" data-url="${result.url}">
				<div class="wc-tp-search-result-content">
					<div class="wc-tp-search-result-title">${result.title}</div>
					<div class="wc-tp-search-result-meta">
						${metaHtml}
					</div>
				</div>
				<span class="wc-tp-search-result-badge ${badgeClass}">${badgeText}</span>
			</a>
		`;
	}
});
