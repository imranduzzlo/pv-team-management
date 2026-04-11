/**
 * WooCommerce Team Payroll - Settings Page JavaScript
 * Handles role management and dynamic form interactions
 */

jQuery(document).ready(function($) {
	$('#wc-tp-add-role-btn').on('click', function() {
		const container = $('#wc-tp-roles-container');
		const timestamp = Date.now();
		const html = `
			<div class="wc-tp-role-item">
				<div class="wc-tp-role-item-header">
					<div style="flex: 1;">
						<input type="text" name="wc_tp_employee_roles[new_${timestamp}][name]" placeholder="Role name (e.g., shop_employee)" value="" style="font-weight: bold; padding: 6px; border: 1px solid #ddd; border-radius: 3px; width: 100%; max-width: 300px;" />
					</div>
					<button type="button" class="wc-tp-role-remove" style="margin-left: 10px;">Remove</button>
				</div>

				<div class="wc-tp-role-capabilities" style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #eee;">
					<label style="display: block; font-weight: bold; margin-bottom: 8px; font-size: 12px;">Capabilities:</label>
					<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px;">
						<label style="display: flex; align-items: center; font-size: 12px;">
							<input type="checkbox" name="wc_tp_employee_roles[new_${timestamp}][capabilities][read]" value="1" />
							<span style="margin-left: 5px;">Read</span>
						</label>
						<label style="display: flex; align-items: center; font-size: 12px;">
							<input type="checkbox" name="wc_tp_employee_roles[new_${timestamp}][capabilities][edit_posts]" value="1" />
							<span style="margin-left: 5px;">Edit Posts</span>
						</label>
						<label style="display: flex; align-items: center; font-size: 12px;">
							<input type="checkbox" name="wc_tp_employee_roles[new_${timestamp}][capabilities][delete_posts]" value="1" />
							<span style="margin-left: 5px;">Delete Posts</span>
						</label>
						<label style="display: flex; align-items: center; font-size: 12px;">
							<input type="checkbox" name="wc_tp_employee_roles[new_${timestamp}][capabilities][publish_posts]" value="1" />
							<span style="margin-left: 5px;">Publish Posts</span>
						</label>
						<label style="display: flex; align-items: center; font-size: 12px;">
							<input type="checkbox" name="wc_tp_employee_roles[new_${timestamp}][capabilities][manage_options]" value="1" />
							<span style="margin-left: 5px;">Manage Options</span>
						</label>
						<label style="display: flex; align-items: center; font-size: 12px;">
							<input type="checkbox" name="wc_tp_employee_roles[new_${timestamp}][capabilities][manage_woocommerce]" value="1" />
							<span style="margin-left: 5px;">Manage WooCommerce</span>
						</label>
					</div>
				</div>

				<input type="hidden" name="wc_tp_employee_roles[new_${timestamp}][role_key]" value="new_${timestamp}" />
			</div>
		`;
		container.append(html);
	});

	$(document).on('click', '.wc-tp-role-remove', function(e) {
		e.preventDefault();
		if ($(this).prop('disabled')) return;
		$(this).closest('.wc-tp-role-item').remove();
	});
});
