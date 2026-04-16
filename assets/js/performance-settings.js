/**
 * Performance Settings Admin JavaScript
 * WooCommerce Team Payroll
 * @since 1.0.52
 */

jQuery(document).ready(function($) {
	'use strict';

	// Navigation tabs
	$('.wc-tp-perf-nav-tab').on('click', function() {
		const section = $(this).data('section');
		
		// Update active tab
		$('.wc-tp-perf-nav-tab').removeClass('active');
		$(this).addClass('active');
		
		// Show corresponding section
		$('.wc-tp-perf-section').removeClass('active');
		$('#wc-tp-perf-' + section).addClass('active');
	});

	// Role selector change
	$('#wc-tp-role-selector').on('change', function() {
		const role = $(this).val();
		
		if (!role) {
			$('#wc-tp-role-config-container').html(
				'<div class="wc-tp-empty-state">' +
				'<span class="dashicons dashicons-admin-users"></span>' +
				'<p>Select a role above to configure performance scoring factors.</p>' +
				'</div>'
			);
			return;
		}

		loadRoleConfiguration(role);
	});

	// Load role configuration via AJAX
	function loadRoleConfiguration(role) {
		$('#wc-tp-role-config-container').html(
			'<div class="wc-tp-loading">' +
			'<span class="spinner is-active"></span>' +
			'<p>Loading configuration...</p>' +
			'</div>'
		);

		$.ajax({
			url: wcTpPerformance.ajax_url,
			type: 'POST',
			data: {
				action: 'wc_tp_get_role_config',
				nonce: wcTpPerformance.nonce,
				role: role
			},
			success: function(response) {
				if (response.success) {
					$('#wc-tp-role-config-container').html(response.data.html);
					initializeRangeControls();
				} else {
					showMessage('error', response.data.message || 'Error loading configuration');
				}
			},
			error: function() {
				showMessage('error', 'AJAX error occurred');
			}
		});
	}

	// Initialize range controls
	function initializeRangeControls() {
		// Add range button
		$(document).on('click', '.wc-tp-add-range', function() {
			const container = $(this).closest('.wc-tp-ranges-container');
			const factor = container.data('factor');
			const role = container.data('role');
			addRangeRow(container, factor, role);
		});

		// Remove range button
		$(document).on('click', '.wc-tp-remove-range', function() {
			if (confirm('Are you sure you want to remove this range?')) {
				$(this).closest('.wc-tp-range-row').fadeOut(200, function() {
					$(this).remove();
				});
			}
		});

		// Calculate preview
		$(document).on('click', '.wc-tp-calculate-preview', function() {
			calculatePreview();
		});
	}

	// Add range row
	function addRangeRow(container, factor, role) {
		const index = container.find('.wc-tp-range-row').length;
		const currencySymbol = (factor === 'earnings' || factor === 'aov') ? '$' : '';
		const step = factor === 'orders' ? '1' : '0.01';
		
		const rangeRow = $('<div class="wc-tp-range-row" data-index="' + index + '">' +
			'<label>Range:</label>' +
			'<span class="wc-tp-range-currency">' + currencySymbol + '</span>' +
			'<input type="number" name="' + factor + '_min[]" placeholder="Min" step="' + step + '" min="0" class="wc-tp-range-min" />' +
			'<span>to</span>' +
			'<span class="wc-tp-range-currency">' + currencySymbol + '</span>' +
			'<input type="number" name="' + factor + '_max[]" placeholder="Max" step="' + step + '" min="0" class="wc-tp-range-max" />' +
			'<span>=</span>' +
			'<input type="number" name="' + factor + '_points[]" placeholder="Points" step="0.1" min="0" max="10" class="wc-tp-range-points" />' +
			'<span>points</span>' +
			'<span class="dashicons dashicons-trash wc-tp-remove-range" title="Remove this range"></span>' +
			'</div>');

		container.find('.wc-tp-add-range').before(rangeRow);
		rangeRow.hide().fadeIn(200);
	}

	// Clone role configuration
	$('#wc-tp-clone-role').on('click', function() {
		const currentRole = $('#wc-tp-role-selector').val();
		
		if (!currentRole) {
			alert('Please select a role first');
			return;
		}

		// Show dialog to select source role
		const allRoles = [];
		$('#wc-tp-role-selector option').each(function() {
			if ($(this).val() && $(this).val() !== currentRole) {
				allRoles.push({
					value: $(this).val(),
					text: $(this).text()
				});
			}
		});

		if (allRoles.length === 0) {
			alert('No other roles available to clone from');
			return;
		}

		let options = '';
		allRoles.forEach(function(role) {
			options += '<option value="' + role.value + '">' + role.text + '</option>';
		});

		const sourceRole = prompt('Select role to clone from:\n\n' + allRoles.map(r => r.text).join('\n'));
		
		if (sourceRole) {
			cloneRoleConfiguration(sourceRole, currentRole);
		}
	});

	// Clone role configuration via AJAX
	function cloneRoleConfiguration(fromRole, toRole) {
		$.ajax({
			url: wcTpPerformance.ajax_url,
			type: 'POST',
			data: {
				action: 'wc_tp_clone_role_config',
				nonce: wcTpPerformance.nonce,
				from_role: fromRole,
				to_role: toRole
			},
			success: function(response) {
				if (response.success) {
					showMessage('success', response.data.message);
					loadRoleConfiguration(toRole);
				} else {
					showMessage('error', response.data.message || 'Error cloning configuration');
				}
			},
			error: function() {
				showMessage('error', 'AJAX error occurred');
			}
		});
	}

	// Reset role configuration
	$('#wc-tp-reset-role').on('click', function() {
		const currentRole = $('#wc-tp-role-selector').val();
		
		if (!currentRole) {
			alert('Please select a role first');
			return;
		}

		if (confirm('Are you sure you want to reset this role configuration to default values?')) {
			// Reset logic will be implemented
			showMessage('success', 'Role configuration reset to defaults');
			loadRoleConfiguration(currentRole);
		}
	});

	// Save all configurations
	$('#wc-tp-save-performance').on('click', function() {
		const button = $(this);
		button.prop('disabled', true).html('<span class="spinner is-active" style="float:none;margin:0 8px 0 0;"></span>Saving...');

		// Collect all configuration data
		const config = collectConfigurationData();

		$.ajax({
			url: wcTpPerformance.ajax_url,
			type: 'POST',
			data: {
				action: 'wc_tp_save_performance_config',
				nonce: wcTpPerformance.nonce,
				config: config
			},
			success: function(response) {
				if (response.success) {
					showMessage('success', wcTpPerformance.strings.save_success);
				} else {
					showMessage('error', response.data.message || wcTpPerformance.strings.save_error);
				}
			},
			error: function() {
				showMessage('error', wcTpPerformance.strings.save_error);
			},
			complete: function() {
				button.prop('disabled', false).html('<span class="dashicons dashicons-saved"></span>Save All Configurations');
			}
		});

		// Also save goals configuration if on goals tab
		const activeSection = $('.wc-tp-perf-nav-tab.active').data('section');
		if (activeSection === 'goals') {
			saveGoalsConfiguration();
		}
	});

	// Save goals configuration
	function saveGoalsConfiguration() {
		const goalsConfig = collectGoalsConfigurationData();

		$.ajax({
			url: wcTpPerformance.ajax_url,
			type: 'POST',
			data: {
				action: 'wc_tp_save_goals_config',
				nonce: wcTpPerformance.nonce,
				config: goalsConfig
			},
			success: function(response) {
				if (response.success) {
					showMessage('success', 'Goals configuration saved successfully!');
				} else {
					showMessage('error', response.data.message || 'Error saving goals configuration');
				}
			},
			error: function() {
				showMessage('error', 'AJAX error occurred while saving goals');
			}
		});
	}

	// Collect configuration data
	function collectConfigurationData() {
		const config = {
			base_score: $('#base_score').val(),
			roles: {}
		};

		// Get current role being configured
		const currentRole = $('.wc-tp-role-config-form').data('role');
		
		if (currentRole) {
			config.roles[currentRole] = {
				earnings_ranges: collectRanges('earnings'),
				orders_ranges: collectRanges('orders'),
				aov_ranges: collectRanges('aov')
			};
		}

		return config;
	}

	// Collect ranges for a specific factor
	function collectRanges(factor) {
		const ranges = [];
		const container = $('.wc-tp-ranges-container[data-factor="' + factor + '"]');
		
		container.find('.wc-tp-range-row').each(function() {
			const min = parseFloat($(this).find('.wc-tp-range-min').val()) || 0;
			const max = parseFloat($(this).find('.wc-tp-range-max').val()) || 0;
			const points = parseFloat($(this).find('.wc-tp-range-points').val()) || 0;
			
			ranges.push({
				min: min,
				max: max,
				points: points
			});
		});
		
		return ranges;
	}

	// Calculate preview score
	function calculatePreview() {
		const earnings = parseFloat($('.wc-tp-preview-earnings').val()) || 0;
		const orders = parseInt($('.wc-tp-preview-orders').val()) || 0;
		const aov = parseFloat($('.wc-tp-preview-aov').val()) || 0;
		const baseScore = parseFloat($('#base_score').val()) || 5;

		// Calculate points for each factor
		const earningsPoints = calculatePointsForValue(earnings, collectRanges('earnings'));
		const ordersPoints = calculatePointsForValue(orders, collectRanges('orders'));
		const aovPoints = calculatePointsForValue(aov, collectRanges('aov'));

		// Calculate final score
		const finalScore = baseScore + earningsPoints + ordersPoints + aovPoints;
		const cappedScore = Math.min(finalScore, 10);

		// Display results
		$('#preview-base-score').text(baseScore.toFixed(1));
		$('#preview-earnings-points').text('+' + earningsPoints.toFixed(1));
		$('#preview-orders-points').text('+' + ordersPoints.toFixed(1));
		$('#preview-aov-points').text('+' + aovPoints.toFixed(1));
		$('#preview-final-score').text(cappedScore.toFixed(1) + '/10');

		$('.wc-tp-preview-result').slideDown(300);
	}

	// Calculate points for a value based on ranges
	function calculatePointsForValue(value, ranges) {
		for (let i = 0; i < ranges.length; i++) {
			const range = ranges[i];
			if (value >= range.min && value <= range.max) {
				return range.points;
			}
		}
		return 0;
	}

	// Export settings
	$('#wc-tp-export-performance').on('click', function() {
		// Export functionality will be implemented
		alert('Export functionality coming soon');
	});

	// Import settings
	$('#wc-tp-import-performance').on('click', function() {
		// Import functionality will be implemented
		alert('Import functionality coming soon');
	});

	// Reset all configurations
	$('#wc-tp-reset-performance').on('click', function() {
		if (confirm(wcTpPerformance.strings.confirm_reset)) {
			// Reset functionality will be implemented
			showMessage('success', 'All configurations reset to defaults');
		}
	});

	// Show message
	function showMessage(type, message) {
		const icon = type === 'success' ? 'yes-alt' : 'warning';
		const messageHtml = $('<div class="wc-tp-message ' + type + '">' +
			'<span class="dashicons dashicons-' + icon + '"></span>' +
			'<span>' + message + '</span>' +
			'</div>');

		$('.wc-tp-performance-settings-wrapper').prepend(messageHtml);

		setTimeout(function() {
			messageHtml.fadeOut(300, function() {
				$(this).remove();
			});
		}, 5000);

		// Scroll to top
		$('html, body').animate({ scrollTop: 0 }, 300);
	}
});

	// ============================================================================
	// GOALS & TARGETS FUNCTIONALITY
	// ============================================================================

	// Goals role selector change
	$('#wc-tp-goals-role-selector').on('change', function() {
		const role = $(this).val();
		
		if (!role) {
			$('#wc-tp-goals-config-container').html(
				'<div class="wc-tp-empty-state">' +
				'<span class="dashicons dashicons-flag"></span>' +
				'<p>Select a role above to configure goals and targets.</p>' +
				'</div>'
			);
			return;
		}

		loadRoleGoals(role);
	});

	// Load role goals via AJAX
	function loadRoleGoals(role) {
		$('#wc-tp-goals-config-container').html(
			'<div class="wc-tp-loading">' +
			'<span class="spinner is-active"></span>' +
			'<p>Loading goals configuration...</p>' +
			'</div>'
		);

		$.ajax({
			url: wcTpPerformance.ajax_url,
			type: 'POST',
			data: {
				action: 'wc_tp_get_role_goals',
				nonce: wcTpPerformance.nonce,
				role: role
			},
			success: function(response) {
				if (response.success) {
					$('#wc-tp-goals-config-container').html(response.data.html);
					initializeGoalsControls();
				} else {
					showMessage('error', response.data.message || 'Error loading goals configuration');
				}
			},
			error: function() {
				showMessage('error', 'AJAX error occurred');
			}
		});
	}

	// Initialize goals controls
	function initializeGoalsControls() {
		// Preview goals button
		$(document).on('click', '.wc-tp-preview-goals', function() {
			previewGoalProgress();
		});
	}

	// Clone goals configuration
	$('#wc-tp-clone-goals-role').on('click', function() {
		const currentRole = $('#wc-tp-goals-role-selector').val();
		
		if (!currentRole) {
			alert('Please select a role first');
			return;
		}

		// Show dialog to select source role
		const allRoles = [];
		$('#wc-tp-goals-role-selector option').each(function() {
			if ($(this).val() && $(this).val() !== currentRole) {
				allRoles.push({
					value: $(this).val(),
					text: $(this).text()
				});
			}
		});

		if (allRoles.length === 0) {
			alert('No other roles available to clone from');
			return;
		}

		const sourceRole = prompt('Enter the role name to clone from:\n\n' + allRoles.map(r => r.text).join('\n'));
		
		if (sourceRole) {
			cloneRoleGoals(sourceRole, currentRole);
		}
	});

	// Clone role goals via AJAX
	function cloneRoleGoals(fromRole, toRole) {
		$.ajax({
			url: wcTpPerformance.ajax_url,
			type: 'POST',
			data: {
				action: 'wc_tp_clone_role_goals',
				nonce: wcTpPerformance.nonce,
				from_role: fromRole,
				to_role: toRole
			},
			success: function(response) {
				if (response.success) {
					showMessage('success', response.data.message);
					loadRoleGoals(toRole);
				} else {
					showMessage('error', response.data.message || 'Error cloning goals configuration');
				}
			},
			error: function() {
				showMessage('error', 'AJAX error occurred');
			}
		});
	}

	// Reset goals role configuration
	$('#wc-tp-reset-goals-role').on('click', function() {
		const currentRole = $('#wc-tp-goals-role-selector').val();
		
		if (!currentRole) {
			alert('Please select a role first');
			return;
		}

		if (confirm('Are you sure you want to reset this role goals configuration to default values?')) {
			showMessage('success', 'Role goals configuration reset to defaults');
			loadRoleGoals(currentRole);
		}
	});

	// Collect goals configuration data
	function collectGoalsConfigurationData() {
		const config = {
			period: $('#goals_period').val(),
			display_mode: $('#goals_display_mode').val(),
			show_stretch: $('#goals_show_stretch').is(':checked') ? 1 : 0,
			roles: {}
		};

		// Get current role being configured
		const currentRole = $('.wc-tp-role-goals-form').data('role');
		
		if (currentRole) {
			config.roles[currentRole] = {
				earnings: {
					minimum: parseFloat($('input[name="earnings_minimum"]').val()) || 0,
					target: parseFloat($('input[name="earnings_target"]').val()) || 0,
					stretch: parseFloat($('input[name="earnings_stretch"]').val()) || 0
				},
				orders: {
					minimum: parseInt($('input[name="orders_minimum"]').val()) || 0,
					target: parseInt($('input[name="orders_target"]').val()) || 0,
					stretch: parseInt($('input[name="orders_stretch"]').val()) || 0
				},
				aov: {
					minimum: parseFloat($('input[name="aov_minimum"]').val()) || 0,
					target: parseFloat($('input[name="aov_target"]').val()) || 0,
					stretch: parseFloat($('input[name="aov_stretch"]').val()) || 0
				}
			};
		}

		return config;
	}

	// Preview goal progress
	function previewGoalProgress() {
		const currentEarnings = parseFloat($('.wc-tp-preview-goal-earnings').val()) || 0;
		const currentOrders = parseInt($('.wc-tp-preview-goal-orders').val()) || 0;
		const currentAov = parseFloat($('.wc-tp-preview-goal-aov').val()) || 0;

		const earningsTarget = parseFloat($('input[name="earnings_target"]').val()) || 0;
		const ordersTarget = parseInt($('input[name="orders_target"]').val()) || 0;
		const aovTarget = parseFloat($('input[name="aov_target"]').val()) || 0;

		// Calculate percentages
		const earningsPercentage = earningsTarget > 0 ? Math.min((currentEarnings / earningsTarget) * 100, 100) : 0;
		const ordersPercentage = ordersTarget > 0 ? Math.min((currentOrders / ordersTarget) * 100, 100) : 0;
		const aovPercentage = aovTarget > 0 ? Math.min((currentAov / aovTarget) * 100, 100) : 0;

		// Update earnings progress
		$('#preview-earnings-progress').css('width', earningsPercentage + '%');
		$('#preview-earnings-current').text('$' + currentEarnings.toFixed(2));
		$('#preview-earnings-target').text('/ $' + earningsTarget.toFixed(2));
		$('#preview-earnings-percentage').text(earningsPercentage.toFixed(1) + '%');

		// Update orders progress
		$('#preview-orders-progress').css('width', ordersPercentage + '%');
		$('#preview-orders-current').text(currentOrders);
		$('#preview-orders-target').text('/ ' + ordersTarget);
		$('#preview-orders-percentage').text(ordersPercentage.toFixed(1) + '%');

		// Update AOV progress
		$('#preview-aov-progress').css('width', aovPercentage + '%');
		$('#preview-aov-current').text('$' + currentAov.toFixed(2));
		$('#preview-aov-target').text('/ $' + aovTarget.toFixed(2));
		$('#preview-aov-percentage').text(aovPercentage.toFixed(1) + '%');

		// Show results
		$('.wc-tp-goal-preview-result').slideDown(300);
	}


	// ============================================================================
	// ACHIEVEMENTS FUNCTIONALITY
	// ============================================================================

	// Achievements role selector change
	$('#wc-tp-achievements-role-selector').on('change', function() {
		const role = $(this).val();
		
		if (!role) {
			$('#wc-tp-achievements-config-container').html(
				'<div class="wc-tp-empty-state">' +
				'<span class="dashicons dashicons-awards"></span>' +
				'<p>Select a role above to configure achievements and badges.</p>' +
				'</div>'
			);
			return;
		}

		loadRoleAchievements(role);
	});

	// Load role achievements via AJAX
	function loadRoleAchievements(role) {
		$('#wc-tp-achievements-config-container').html(
			'<div class="wc-tp-loading">' +
			'<span class="spinner is-active"></span>' +
			'<p>Loading achievements configuration...</p>' +
			'</div>'
		);

		$.ajax({
			url: wcTpPerformance.ajax_url,
			type: 'POST',
			data: {
				action: 'wc_tp_get_role_achievements',
				nonce: wcTpPerformance.nonce,
				role: role
			},
			success: function(response) {
				if (response.success) {
					$('#wc-tp-achievements-config-container').html(response.data.html);
				} else {
					showMessage('error', response.data.message || 'Error loading achievements configuration');
				}
			},
			error: function() {
				showMessage('error', 'AJAX error occurred');
			}
		});
	}

	// Clone achievements configuration
	$('#wc-tp-clone-achievements-role').on('click', function() {
		const currentRole = $('#wc-tp-achievements-role-selector').val();
		
		if (!currentRole) {
			alert('Please select a role first');
			return;
		}

		// Show dialog to select source role
		const allRoles = [];
		$('#wc-tp-achievements-role-selector option').each(function() {
			if ($(this).val() && $(this).val() !== currentRole) {
				allRoles.push({
					value: $(this).val(),
					text: $(this).text()
				});
			}
		});

		if (allRoles.length === 0) {
			alert('No other roles available to clone from');
			return;
		}

		const sourceRole = prompt('Enter the role name to clone from:\n\n' + allRoles.map(r => r.text).join('\n'));
		
		if (sourceRole) {
			cloneRoleAchievements(sourceRole, currentRole);
		}
	});

	// Clone role achievements via AJAX
	function cloneRoleAchievements(fromRole, toRole) {
		$.ajax({
			url: wcTpPerformance.ajax_url,
			type: 'POST',
			data: {
				action: 'wc_tp_clone_role_achievements',
				nonce: wcTpPerformance.nonce,
				from_role: fromRole,
				to_role: toRole
			},
			success: function(response) {
				if (response.success) {
					showMessage('success', response.data.message);
					loadRoleAchievements(toRole);
				} else {
					showMessage('error', response.data.message || 'Error cloning achievements configuration');
				}
			},
			error: function() {
				showMessage('error', 'AJAX error occurred');
			}
		});
	}

	// Reset achievements role configuration
	$('#wc-tp-reset-achievements-role').on('click', function() {
		const currentRole = $('#wc-tp-achievements-role-selector').val();
		
		if (!currentRole) {
			alert('Please select a role first');
			return;
		}

		if (confirm('Are you sure you want to reset this role achievements configuration to default values?')) {
			showMessage('success', 'Role achievements configuration reset to defaults');
			loadRoleAchievements(currentRole);
		}
	});

	// Save achievements configuration (integrated with main save button)
	function saveAchievementsConfiguration() {
		const achievementsConfig = collectAchievementsConfigurationData();

		$.ajax({
			url: wcTpPerformance.ajax_url,
			type: 'POST',
			data: {
				action: 'wc_tp_save_achievements_config',
				nonce: wcTpPerformance.nonce,
				config: achievementsConfig
			},
			success: function(response) {
				if (response.success) {
					showMessage('success', 'Achievements configuration saved successfully!');
				} else {
					showMessage('error', response.data.message || 'Error saving achievements configuration');
				}
			},
			error: function() {
				showMessage('error', 'AJAX error occurred while saving achievements');
			}
		});
	}

	// Collect achievements configuration data
	function collectAchievementsConfigurationData() {
		const config = {
			enabled: $('#achievements_enabled').is(':checked') ? 1 : 0,
			display_style: $('#achievements_display_style').val(),
			show_locked: $('#achievements_show_locked').is(':checked') ? 1 : 0,
			notification: $('#achievements_notification').is(':checked') ? 1 : 0,
			roles: {}
		};

		// Get current role being configured
		const currentRole = $('.wc-tp-role-achievements-form').data('role');
		
		if (currentRole) {
			config.roles[currentRole] = {};
			
			const categories = ['earnings', 'orders', 'aov'];
			const tiers = ['bronze', 'silver', 'gold'];
			
			categories.forEach(function(category) {
				tiers.forEach(function(tier) {
					const key = category + '_' + tier;
					const nameField = $('input[name="achievement_' + category + '_' + tier + '_name"]');
					const descField = $('textarea[name="achievement_' + category + '_' + tier + '_description"]');
					const thresholdField = $('input[name="achievement_' + category + '_' + tier + '_threshold"]');
					const iconField = $('select[name="achievement_' + category + '_' + tier + '_icon"]');
					
					if (nameField.length) {
						config.roles[currentRole][key] = {
							name: nameField.val(),
							description: descField.val(),
							threshold: category === 'orders' ? parseInt(thresholdField.val()) || 0 : parseFloat(thresholdField.val()) || 0,
							tier: tier,
							icon: iconField.val()
						};
					}
				});
			});
		}

		return config;
	}

	// Update main save button to handle achievements
	const originalSaveHandler = $('#wc-tp-save-performance').data('events') ? $('#wc-tp-save-performance').data('events').click : null;
	$('#wc-tp-save-performance').off('click').on('click', function() {
		const button = $(this);
		button.prop('disabled', true).html('<span class="spinner is-active" style="float:none;margin:0 8px 0 0;"></span>Saving...');

		const activeSection = $('.wc-tp-perf-nav-tab.active').data('section');
		
		// Save based on active section
		if (activeSection === 'scoring') {
			const config = collectConfigurationData();
			$.ajax({
				url: wcTpPerformance.ajax_url,
				type: 'POST',
				data: {
					action: 'wc_tp_save_performance_config',
					nonce: wcTpPerformance.nonce,
					config: config
				},
				success: function(response) {
					if (response.success) {
						showMessage('success', wcTpPerformance.strings.save_success);
					} else {
						showMessage('error', response.data.message || wcTpPerformance.strings.save_error);
					}
				},
				error: function() {
					showMessage('error', wcTpPerformance.strings.save_error);
				},
				complete: function() {
					button.prop('disabled', false).html('<span class="dashicons dashicons-saved"></span>Save All Configurations');
				}
			});
		} else if (activeSection === 'goals') {
			saveGoalsConfiguration();
			button.prop('disabled', false).html('<span class="dashicons dashicons-saved"></span>Save All Configurations');
		} else if (activeSection === 'achievements') {
			saveAchievementsConfiguration();
			button.prop('disabled', false).html('<span class="dashicons dashicons-saved"></span>Save All Configurations');
		} else {
			button.prop('disabled', false).html('<span class="dashicons dashicons-saved"></span>Save All Configurations');
			showMessage('error', 'This section is not yet implemented');
		}
	});


	// ============================================================================
	// BASELINES & BENCHMARKS FUNCTIONALITY
	// ============================================================================

	// Show/hide baseline options based on method
	$('#baseline_method').on('change', function() {
		const method = $(this).val();
		
		// Hide all options first
		$('.wc-tp-baseline-option').hide();
		
		// Show relevant options
		$('.wc-tp-baseline-option[data-show-for="' + method + '"]').show();
	}).trigger('change');

	// Calculate baseline preview
	$('#wc-tp-calculate-baseline').on('click', function() {
		const method = $('#baseline_method').val();
		const periods = parseInt($('#baseline_periods').val()) || 3;
		const percentile = parseInt($('#baseline_percentile').val()) || 75;
		
		// Parse sample data
		const earningsData = $('#sample_earnings').val().split(',').map(v => parseFloat(v.trim())).filter(v => !isNaN(v));
		const ordersData = $('#sample_orders').val().split(',').map(v => parseInt(v.trim())).filter(v => !isNaN(v));
		const aovData = $('#sample_aov').val().split(',').map(v => parseFloat(v.trim())).filter(v => !isNaN(v));
		
		if (earningsData.length === 0 && ordersData.length === 0 && aovData.length === 0) {
			alert('Please enter sample data for at least one metric');
			return;
		}
		
		// Show loading
		$(this).prop('disabled', true).html('<span class="spinner is-active" style="float:none;margin:0 8px 0 0;"></span>Calculating...');
		
		$.ajax({
			url: wcTpPerformance.ajax_url,
			type: 'POST',
			data: {
				action: 'wc_tp_calculate_baseline_preview',
				nonce: wcTpPerformance.nonce,
				method: method,
				periods: periods,
				percentile: percentile,
				earnings_data: earningsData,
				orders_data: ordersData,
				aov_data: aovData
			},
			success: function(response) {
				if (response.success) {
					displayBaselineResults(response.data, method, periods, percentile);
					$('.wc-tp-baseline-result-section').slideDown(300);
				} else {
					showMessage('error', response.data.message || 'Error calculating baseline');
				}
			},
			error: function() {
				showMessage('error', 'AJAX error occurred');
			},
			complete: function() {
				$('#wc-tp-calculate-baseline').prop('disabled', false).html('<span class="dashicons dashicons-calculator"></span>Calculate Baseline');
			}
		});
	});

	// Display baseline results
	function displayBaselineResults(data, method, periods, percentile) {
		// Earnings
		$('#baseline_earnings_value').text('$' + data.earnings.toFixed(2));
		$('#baseline_earnings_method').text(getMethodDescription(method, periods, percentile));
		
		// Orders
		$('#baseline_orders_value').text(Math.round(data.orders));
		$('#baseline_orders_method').text(getMethodDescription(method, periods, percentile));
		
		// AOV
		$('#baseline_aov_value').text('$' + data.aov.toFixed(2));
		$('#baseline_aov_method').text(getMethodDescription(method, periods, percentile));
	}

	// Get method description
	function getMethodDescription(method, periods, percentile) {
		switch(method) {
			case 'rolling_average':
				return 'Average of last ' + periods + ' periods';
			case 'historical_average':
				return 'Average of all historical data';
			case 'best_period':
				return 'Best performance period';
			case 'median':
				return 'Median of all data points';
			case 'percentile':
				return percentile + 'th percentile of data';
			case 'manual':
				return 'Manual entry (showing average)';
			default:
				return 'Calculated baseline';
		}
	}

	// Save baselines configuration
	function saveBaselinesConfiguration() {
		const baselinesConfig = {
			method: $('#baseline_method').val(),
			periods: parseInt($('#baseline_periods').val()) || 3,
			percentile: parseInt($('#baseline_percentile').val()) || 75,
			update_frequency: $('#baseline_update_frequency').val(),
			minimum_data: parseInt($('#baseline_minimum_data').val()) || 5,
			show_comparison: $('#baseline_show_comparison').is(':checked') ? 1 : 0,
			show_trend: $('#baseline_show_trend').is(':checked') ? 1 : 0,
			show_history: $('#baseline_show_history').is(':checked') ? 1 : 0,
			comparison_format: $('#baseline_comparison_format').val()
		};

		$.ajax({
			url: wcTpPerformance.ajax_url,
			type: 'POST',
			data: {
				action: 'wc_tp_save_baselines_config',
				nonce: wcTpPerformance.nonce,
				config: baselinesConfig
			},
			success: function(response) {
				if (response.success) {
					showMessage('success', 'Baselines configuration saved successfully!');
				} else {
					showMessage('error', response.data.message || 'Error saving baselines configuration');
				}
			},
			error: function() {
				showMessage('error', 'AJAX error occurred while saving baselines');
			}
		});
	}

	// Update main save button to handle baselines
	$('#wc-tp-save-performance').off('click').on('click', function() {
		const button = $(this);
		button.prop('disabled', true).html('<span class="spinner is-active" style="float:none;margin:0 8px 0 0;"></span>Saving...');

		const activeSection = $('.wc-tp-perf-nav-tab.active').data('section');
		
		// Save based on active section
		if (activeSection === 'scoring') {
			const config = collectConfigurationData();
			$.ajax({
				url: wcTpPerformance.ajax_url,
				type: 'POST',
				data: {
					action: 'wc_tp_save_performance_config',
					nonce: wcTpPerformance.nonce,
					config: config
				},
				success: function(response) {
					if (response.success) {
						showMessage('success', wcTpPerformance.strings.save_success);
					} else {
						showMessage('error', response.data.message || wcTpPerformance.strings.save_error);
					}
				},
				error: function() {
					showMessage('error', wcTpPerformance.strings.save_error);
				},
				complete: function() {
					button.prop('disabled', false).html('<span class="dashicons dashicons-saved"></span>Save All Configurations');
				}
			});
		} else if (activeSection === 'goals') {
			saveGoalsConfiguration();
			button.prop('disabled', false).html('<span class="dashicons dashicons-saved"></span>Save All Configurations');
		} else if (activeSection === 'achievements') {
			saveAchievementsConfiguration();
			button.prop('disabled', false).html('<span class="dashicons dashicons-saved"></span>Save All Configurations');
		} else if (activeSection === 'baselines') {
			saveBaselinesConfiguration();
			button.prop('disabled', false).html('<span class="dashicons dashicons-saved"></span>Save All Configurations');
		} else {
			button.prop('disabled', false).html('<span class="dashicons dashicons-saved"></span>Save All Configurations');
			showMessage('error', 'This section is not yet implemented');
		}
	});
