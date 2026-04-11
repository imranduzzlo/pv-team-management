/**
 * Global Delete Confirmation Modal for WooCommerce Team Payroll
 */
(function($) {
	'use strict';

	// Create modal HTML if it doesn't exist
	function initModal() {
		if ($('#wc-tp-delete-modal').length === 0) {
			const modalHTML = `
				<div id="wc-tp-delete-modal" class="wc-tp-modal" style="display: none;">
					<div class="wc-tp-modal-overlay"></div>
					<div class="wc-tp-modal-content">
						<h2>Confirm Deletion</h2>
						<p id="wc-tp-delete-message">This action will permanently delete the selected item(s). This cannot be undone.</p>
						<p><strong>Type "DELETE" to confirm:</strong></p>
						<input type="text" id="wc-tp-delete-confirm-input" class="regular-text" placeholder="DELETE" autocomplete="off" />
						<div class="wc-tp-modal-actions">
							<button type="button" class="button button-primary button-large wc-tp-confirm-delete" disabled>Delete Permanently</button>
							<button type="button" class="button button-large wc-tp-cancel-delete">Cancel</button>
						</div>
					</div>
				</div>
			`;
			$('body').append(modalHTML);

			// Add modal styles
			if ($('#wc-tp-modal-styles').length === 0) {
				const styles = `
					<style id="wc-tp-modal-styles">
						.wc-tp-modal {
							position: fixed;
							top: 0;
							left: 0;
							width: 100%;
							height: 100%;
							z-index: 999999;
							display: flex;
							align-items: center;
							justify-content: center;
						}

						.wc-tp-modal-overlay {
							position: absolute;
							top: 0;
							left: 0;
							width: 100%;
							height: 100%;
							background: rgba(0, 0, 0, 0.7);
							backdrop-filter: blur(2px);
						}

						.wc-tp-modal-content {
							position: relative;
							background: #fff;
							border-radius: 8px;
							padding: 32px;
							max-width: 500px;
							width: 90%;
							box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
							animation: wc-tp-modal-slide-in 0.3s ease-out;
						}

						@keyframes wc-tp-modal-slide-in {
							from {
								opacity: 0;
								transform: translateY(-20px);
							}
							to {
								opacity: 1;
								transform: translateY(0);
							}
						}

						.wc-tp-modal-content h2 {
							margin: 0 0 16px 0;
							font-size: 24px;
							font-weight: 600;
							color: #dc3545;
						}

						.wc-tp-modal-content p {
							margin: 0 0 16px 0;
							font-size: 14px;
							line-height: 1.6;
							color: #454F5B;
						}

						.wc-tp-modal-content p strong {
							color: #212B36;
						}

						.wc-tp-modal-content input[type="text"] {
							width: 100%;
							padding: 10px 12px;
							border: 2px solid #E5EAF0;
							border-radius: 6px;
							font-size: 14px;
							margin-bottom: 24px;
							transition: border-color 0.2s;
						}

						.wc-tp-modal-content input[type="text"]:focus {
							outline: none;
							border-color: #dc3545;
							box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1);
						}

						.wc-tp-modal-actions {
							display: flex;
							gap: 12px;
							justify-content: flex-end;
						}

						.wc-tp-modal-actions .button {
							padding: 10px 24px;
							font-size: 14px;
							font-weight: 600;
							border-radius: 6px;
							cursor: pointer;
							transition: all 0.2s;
						}

						.wc-tp-modal-actions .button-primary {
							background: #dc3545;
							border-color: #dc3545;
							color: white;
						}

						.wc-tp-modal-actions .button-primary:hover:not(:disabled) {
							background: #c82333;
							border-color: #c82333;
						}

						.wc-tp-modal-actions .button-primary:disabled {
							opacity: 0.5;
							cursor: not-allowed;
						}

						.wc-tp-modal-actions .button:not(.button-primary) {
							background: #f4f4f4;
							border-color: #E5EAF0;
							color: #454F5B;
						}

						.wc-tp-modal-actions .button:not(.button-primary):hover {
							background: #e0e0e0;
							border-color: #d0d0d0;
						}

						@media (max-width: 600px) {
							.wc-tp-modal-content {
								padding: 24px;
								max-width: 95%;
							}

							.wc-tp-modal-actions {
								flex-direction: column-reverse;
							}

							.wc-tp-modal-actions .button {
								width: 100%;
								justify-content: center;
							}
						}
					</style>
				`;
				$('head').append(styles);
			}

			// Attach event handlers
			attachModalHandlers();
		}
	}

	// Attach event handlers to modal
	function attachModalHandlers() {
		// Enable/disable delete button based on input
		$(document).on('input', '#wc-tp-delete-confirm-input', function() {
			const value = $(this).val().trim();
			$('.wc-tp-confirm-delete').prop('disabled', value !== 'DELETE');
		});

		// Close modal on overlay click
		$(document).on('click', '.wc-tp-modal-overlay', function() {
			closeModal();
		});

		// Close modal on cancel button
		$(document).on('click', '.wc-tp-cancel-delete', function() {
			closeModal();
		});

		// Close modal on ESC key
		$(document).on('keydown', function(e) {
			if (e.key === 'Escape' && $('#wc-tp-delete-modal').is(':visible')) {
				closeModal();
			}
		});
	}

	// Show delete confirmation modal
	function showDeleteModal(options) {
		initModal();

		const defaults = {
			message: 'This action will permanently delete the selected item(s). This cannot be undone.',
			onConfirm: function() {},
			onCancel: function() {}
		};

		const settings = $.extend({}, defaults, options);

		// Set message
		$('#wc-tp-delete-message').text(settings.message);

		// Clear input
		$('#wc-tp-delete-confirm-input').val('');
		$('.wc-tp-confirm-delete').prop('disabled', true);

		// Show modal
		$('#wc-tp-delete-modal').fadeIn(200);

		// Focus input
		setTimeout(function() {
			$('#wc-tp-delete-confirm-input').focus();
		}, 250);

		// Store callbacks
		$('#wc-tp-delete-modal').data('onConfirm', settings.onConfirm);
		$('#wc-tp-delete-modal').data('onCancel', settings.onCancel);

		// Attach confirm handler (remove previous handlers first)
		$('.wc-tp-confirm-delete').off('click').on('click', function() {
			const callback = $('#wc-tp-delete-modal').data('onConfirm');
			if (typeof callback === 'function') {
				callback();
			}
			closeModal();
		});
	}

	// Close modal
	function closeModal() {
		const callback = $('#wc-tp-delete-modal').data('onCancel');
		$('#wc-tp-delete-modal').fadeOut(200);
		$('#wc-tp-delete-confirm-input').val('');
		$('.wc-tp-confirm-delete').prop('disabled', true);
		
		if (typeof callback === 'function') {
			callback();
		}
	}

	// Expose global function
	window.wcTPDeleteModal = showDeleteModal;

	// Initialize on document ready
	$(document).ready(function() {
		initModal();
	});

})(jQuery);
