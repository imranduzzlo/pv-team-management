/**
 * WooCommerce Team Payroll Dashboard JS
 */

jQuery(document).ready(function($) {
	// Initialize DataTables if available
	if ($.fn.dataTable) {
		$('#employees-table').DataTable({
			paging: true,
			searching: true,
			ordering: true,
			info: true,
			pageLength: 25,
			columnDefs: [
				{
					targets: -1,
					orderable: false,
				},
			],
		});
	}

	// Mark payroll as paid
	$('.mark-paid').on('click', function(e) {
		e.preventDefault();

		const userId = $(this).data('user-id');
		const year = $(this).data('year');
		const month = $(this).data('month');
		const amount = $(this).data('amount');

		if (!confirm('Mark this payroll as paid?')) {
			return;
		}

		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'wc_team_payroll_mark_paid',
				user_id: userId,
				year: year,
				month: month,
				amount: amount,
				nonce: wcTeamPayrollNonce,
			},
			success: function(response) {
				if (response.success) {
					alert('Payroll marked as paid');
					location.reload();
				} else {
					alert('Error: ' + response.data);
				}
			},
			error: function() {
				alert('Error marking payroll as paid');
			},
		});
	});

	// Column sorting
	$('.widefat thead th').on('click', function() {
		const table = $(this).closest('table');
		const columnIndex = $(this).index();
		const isAscending = $(this).hasClass('sorted-asc');

		// Remove sorting classes from all headers
		table.find('thead th').removeClass('sorted-asc sorted-desc');

		// Add sorting class to current header
		if (isAscending) {
			$(this).addClass('sorted-desc');
		} else {
			$(this).addClass('sorted-asc');
		}

		// Sort table rows
		const rows = table.find('tbody tr').get();
		rows.sort(function(a, b) {
			const aValue = $(a).find('td').eq(columnIndex).text();
			const bValue = $(b).find('td').eq(columnIndex).text();

			// Try to parse as numbers
			const aNum = parseFloat(aValue.replace(/[^0-9.-]/g, ''));
			const bNum = parseFloat(bValue.replace(/[^0-9.-]/g, ''));

			if (!isNaN(aNum) && !isNaN(bNum)) {
				return isAscending ? bNum - aNum : aNum - bNum;
			}

			// String comparison
			return isAscending ? bValue.localeCompare(aValue) : aValue.localeCompare(bValue);
		});

		// Reorder table
		$.each(rows, function(index, row) {
			table.find('tbody').append(row);
		});
	});
});
