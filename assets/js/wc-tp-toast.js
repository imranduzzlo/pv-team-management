/**
 * WooCommerce Team Payroll - Global Toast Notification System
 * 
 * Usage: wcTPToast('Message', 'success|error')
 */

(function($) {
    'use strict';

    // Global Toast Notification System
    window.wcTPToast = function(message, type = 'success') {
        const toastId = 'wc-tp-toast-' + Date.now();
        const toast = $(`
            <div id="${toastId}" class="wc-tp-toast wc-tp-toast-${type}">
                <span>${message}</span>
                <button class="wc-tp-toast-close" data-toast-id="${toastId}">×</button>
            </div>
        `);

        $('body').append(toast);

        // Auto hide after 4 seconds
        setTimeout(() => {
            toast.fadeOut(300, function() {
                $(this).remove();
            });
        }, 4000);

        // Manual close handler (use event delegation to avoid multiple bindings)
        $(document).off('click.wcTPToast').on('click.wcTPToast', '.wc-tp-toast-close', function() {
            const id = $(this).data('toast-id');
            $('#' + id).fadeOut(300, function() {
                $(this).remove();
            });
        });
    };

    // Add Toast CSS (only once)
    function addToastStyles() {
        if ($('#wc-tp-toast-styles').length) {
            return; // Already added
        }

        $('head').append(`
            <style id="wc-tp-toast-styles">
                .wc-tp-toast {
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: #388E3C;
                    color: white;
                    padding: 16px 20px;
                    border-radius: 6px;
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                    display: flex;
                    align-items: center;
                    gap: 12px;
                    z-index: 9999;
                    font-size: 14px;
                    font-weight: 500;
                    animation: wcTPSlideIn 0.3s ease;
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                }

                .wc-tp-toast-error {
                    background: #dc3545;
                }

                .wc-tp-toast-warning {
                    background: #ffc107;
                    color: #212529;
                }

                .wc-tp-toast-info {
                    background: #17a2b8;
                }

                .wc-tp-toast-close {
                    background: none;
                    border: none;
                    color: inherit;
                    font-size: 24px;
                    cursor: pointer;
                    padding: 0;
                    line-height: 1;
                    transition: all 0.2s ease;
                    margin-left: auto;
                }

                .wc-tp-toast-close:hover {
                    opacity: 0.8;
                    transform: scale(1.1);
                }

                @keyframes wcTPSlideIn {
                    from {
                        transform: translateX(400px);
                        opacity: 0;
                    }
                    to {
                        transform: translateX(0);
                        opacity: 1;
                    }
                }

                /* Stack multiple toasts */
                .wc-tp-toast:nth-child(n+2) {
                    top: calc(20px + (80px * var(--toast-index, 0)));
                }

                /* Mobile responsive */
                @media (max-width: 768px) {
                    .wc-tp-toast {
                        top: 10px;
                        right: 10px;
                        left: 10px;
                        width: auto;
                        font-size: 13px;
                        padding: 12px 16px;
                    }

                    .wc-tp-toast:nth-child(n+2) {
                        top: calc(10px + (70px * var(--toast-index, 0)));
                    }
                }

                @media (max-width: 480px) {
                    .wc-tp-toast {
                        font-size: 12px;
                        padding: 10px 14px;
                    }

                    .wc-tp-toast:nth-child(n+2) {
                        top: calc(10px + (60px * var(--toast-index, 0)));
                    }
                }
            </style>
        `);
    }

    // Initialize when document is ready
    $(document).ready(function() {
        addToastStyles();
        
        // Update toast positions when new ones are added
        $(document).on('DOMNodeInserted', '.wc-tp-toast', function() {
            updateToastPositions();
        });
    });

    // Update positions for stacked toasts
    function updateToastPositions() {
        $('.wc-tp-toast').each(function(index) {
            $(this).css('--toast-index', index);
        });
    }

    // Expose utility functions
    window.wcTPToast.success = function(message) {
        wcTPToast(message, 'success');
    };

    window.wcTPToast.error = function(message) {
        wcTPToast(message, 'error');
    };

    window.wcTPToast.warning = function(message) {
        wcTPToast(message, 'warning');
    };

    window.wcTPToast.info = function(message) {
        wcTPToast(message, 'info');
    };

})(jQuery);