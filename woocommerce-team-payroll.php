<?php
/**
 * Plugin Name: WooCommerce Team Payroll & Commission System
 * Plugin URI: https://github.com/imranduzzlo/pv-team-payroll
 * Description: Manage team-based commission and payroll system with agents and processors
 * Version: 1.0.6
 * Author: Imran
 * Author URI: https://imranhossain.me/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 * Text Domain: wc-team-payroll
 * Domain Path: /languages
 * GitHub Plugin URI: imranduzzlo/pv-team-payroll
 * GitHub Branch: main
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WC_TEAM_PAYROLL_VERSION', '1.0.6' );
define( 'WC_TEAM_PAYROLL_PATH', plugin_dir_path( __FILE__ ) );
define( 'WC_TEAM_PAYROLL_URL', plugin_dir_url( __FILE__ ) );

// ============================================================================
// PLUGIN ACTIVATION - FLUSH REWRITE RULES
// ============================================================================

register_activation_hook( __FILE__, function() {
	// Ensure endpoints are registered before flushing
	add_rewrite_endpoint( 'my-salary-details', EP_ROOT | EP_PAGES );
	add_rewrite_endpoint( 'my-earnings', EP_ROOT | EP_PAGES );
	add_rewrite_endpoint( 'my-orders-commission', EP_ROOT | EP_PAGES );
	add_rewrite_endpoint( 'my-reports', EP_ROOT | EP_PAGES );
	
	// Flush rewrite rules
	flush_rewrite_rules();
} );

// ============================================================================
// LOAD ON PLUGINS_LOADED - AFTER WOOCOMMERCE IS LOADED
// ============================================================================

add_action( 'plugins_loaded', function() {
	// Load text domain
	load_plugin_textdomain( 'wc-team-payroll', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

	// Check if WooCommerce is active
	if ( ! class_exists( 'WooCommerce' ) && ! function_exists( 'WC' ) && ! function_exists( 'wc_get_orders' ) ) {
		add_action( 'admin_notices', function() {
			?>
			<div class="notice notice-error is-dismissible">
				<p><?php esc_html_e( 'WooCommerce Team Payroll requires WooCommerce to be installed and activated.', 'wc-team-payroll' ); ?></p>
			</div>
			<?php
		} );
		return;
	}

	// Load all classes
	require_once WC_TEAM_PAYROLL_PATH . 'includes/class-core-engine.php';
	require_once WC_TEAM_PAYROLL_PATH . 'includes/class-payroll-engine.php';
	require_once WC_TEAM_PAYROLL_PATH . 'includes/class-settings.php';
	require_once WC_TEAM_PAYROLL_PATH . 'includes/class-dashboard.php';
	require_once WC_TEAM_PAYROLL_PATH . 'includes/class-payroll-page.php';
	require_once WC_TEAM_PAYROLL_PATH . 'includes/class-checkout-integration.php';
	require_once WC_TEAM_PAYROLL_PATH . 'includes/class-employee-management.php';
	require_once WC_TEAM_PAYROLL_PATH . 'includes/class-employee-detail.php';
	require_once WC_TEAM_PAYROLL_PATH . 'includes/class-custom-fields.php';
	require_once WC_TEAM_PAYROLL_PATH . 'includes/class-myaccount.php';
	require_once WC_TEAM_PAYROLL_PATH . 'includes/class-github-updater.php';

	// Initialize custom fields (creates meta fields)
	new WC_Team_Payroll_Custom_Fields();

	// Initialize core engine (handles commission calculations)
	new WC_Team_Payroll_Core_Engine();

	// Initialize checkout integration (handles agent dropdown)
	new WC_Team_Payroll_Checkout_Integration();

	// Initialize My Account integration
	WC_Team_Payroll_MyAccount::init();

	// Block inactive employees from logging in
	add_filter( 'wp_authenticate_user', function( $user, $password ) {
		if ( is_wp_error( $user ) ) {
			return $user;
		}

		// Check if user is an employee (has team payroll roles)
		$team_roles = array( 'shop_employee', 'shop_manager', 'administrator' );
		$user_roles = $user->roles;
		$is_team_member = false;

		foreach ( $team_roles as $role ) {
			if ( in_array( $role, $user_roles ) ) {
				$is_team_member = true;
				break;
			}
		}

		// Only check status for team members
		if ( $is_team_member ) {
			$employee_status = get_user_meta( $user->ID, '_wc_tp_employee_status', true );
			
			if ( $employee_status === 'inactive' ) {
				// Get contact information from settings
				$contact_whatsapp = get_option( 'wc_team_payroll_contact_whatsapp', '' );
				$contact_email = get_option( 'wc_team_payroll_contact_email', '' );
				$contact_telegram = get_option( 'wc_team_payroll_contact_telegram', '' );
				
				$contact_links = array();
				if ( $contact_whatsapp ) {
					$contact_links[] = '<a href="https://wa.me/' . esc_attr( $contact_whatsapp ) . '" target="_blank">WhatsApp</a>';
				}
				if ( $contact_email ) {
					$contact_links[] = '<a href="mailto:' . esc_attr( $contact_email ) . '">Email</a>';
				}
				if ( $contact_telegram ) {
					$contact_links[] = '<a href="https://t.me/' . esc_attr( $contact_telegram ) . '" target="_blank">Telegram</a>';
				}
				
				$contact_text = '';
				if ( ! empty( $contact_links ) ) {
					$contact_text = ' Contact us: ' . implode( ', ', $contact_links );
				}
				
				$error_message = __( 'Warning: You are no longer an employee. If it\'s a mistake, please contact us to activate your employee ID.', 'wc-team-payroll' ) . $contact_text;
				
				return new WP_Error( 'inactive_employee', $error_message );
			}
		}

		return $user;
	}, 10, 2 );

	// Check and log out inactive employees who are currently logged in
	add_action( 'init', function() {
		// Only check for logged-in users
		if ( ! is_user_logged_in() ) {
			return;
		}

		$current_user = wp_get_current_user();
		
		// Check if user is an employee (has team payroll roles)
		$team_roles = array( 'shop_employee', 'shop_manager', 'administrator' );
		$user_roles = $current_user->roles;
		$is_team_member = false;

		foreach ( $team_roles as $role ) {
			if ( in_array( $role, $user_roles ) ) {
				$is_team_member = true;
				break;
			}
		}

		// Only check status for team members
		if ( $is_team_member ) {
			$employee_status = get_user_meta( $current_user->ID, '_wc_tp_employee_status', true );
			
			if ( $employee_status === 'inactive' ) {
				// Log out the user
				wp_logout();
				
				// Get contact information for redirect message
				$contact_whatsapp = get_option( 'wc_team_payroll_contact_whatsapp', '' );
				$contact_email = get_option( 'wc_team_payroll_contact_email', '' );
				$contact_telegram = get_option( 'wc_team_payroll_contact_telegram', '' );
				
				$contact_params = array();
				if ( $contact_whatsapp ) {
					$contact_params['whatsapp'] = $contact_whatsapp;
				}
				if ( $contact_email ) {
					$contact_params['email'] = $contact_email;
				}
				if ( $contact_telegram ) {
					$contact_params['telegram'] = $contact_telegram;
				}
				
				// Redirect to login page with inactive employee message
				$login_url = wp_login_url();
				$redirect_url = add_query_arg( array_merge( array( 'wc_tp_inactive' => '1' ), $contact_params ), $login_url );
				
				wp_redirect( $redirect_url );
				exit;
			}
		}
	} );

	// Display inactive employee message on login page
	add_action( 'login_message', function( $message ) {
		if ( isset( $_GET['wc_tp_inactive'] ) && $_GET['wc_tp_inactive'] === '1' ) {
			$contact_links = array();
			
			if ( isset( $_GET['whatsapp'] ) && ! empty( $_GET['whatsapp'] ) ) {
				$whatsapp = sanitize_text_field( $_GET['whatsapp'] );
				$contact_links[] = '<a href="https://wa.me/' . esc_attr( $whatsapp ) . '" target="_blank" style="color: #25D366; text-decoration: none;">WhatsApp</a>';
			}
			
			if ( isset( $_GET['email'] ) && ! empty( $_GET['email'] ) ) {
				$email = sanitize_email( $_GET['email'] );
				$contact_links[] = '<a href="mailto:' . esc_attr( $email ) . '" style="color: #0073aa; text-decoration: none;">Email</a>';
			}
			
			if ( isset( $_GET['telegram'] ) && ! empty( $_GET['telegram'] ) ) {
				$telegram = sanitize_text_field( $_GET['telegram'] );
				$contact_links[] = '<a href="https://t.me/' . esc_attr( $telegram ) . '" target="_blank" style="color: #0088cc; text-decoration: none;">Telegram</a>';
			}
			
			$contact_text = '';
			if ( ! empty( $contact_links ) ) {
				$contact_text = '<br><strong>Contact us:</strong> ' . implode( ' | ', $contact_links );
			}
			
			$warning_message = '<div id="login_error" style="background: #ffebee; border: 1px solid #f44336; border-radius: 4px; padding: 12px; margin: 16px 0; color: #c62828;">';
			$warning_message .= '<strong>⚠️ Account Deactivated</strong><br>';
			$warning_message .= 'Your employee account has been deactivated and you have been logged out.<br>';
			$warning_message .= 'If this is a mistake, please contact us to reactivate your employee ID.';
			$warning_message .= $contact_text;
			$warning_message .= '</div>';
			
			$message .= $warning_message;
		}
		
		return $message;
	} );

	// ============================================================================
	// AJAX HANDLERS
	// ============================================================================

	// Hide WooCommerce feature compatibility notices on plugin pages
	add_action( 'admin_notices', function() {
		$screen = get_current_screen();
		if ( $screen && strpos( $screen->base, 'wc-team-payroll' ) !== false ) {
			// Remove WooCommerce feature compatibility notices
			remove_action( 'admin_notices', array( 'WC_Admin_Notices', 'feature_compatibility_notice' ) );
		}
	}, 1 );

	add_action( 'wp_ajax_wc_tp_update_employee_salary', function() {
		$employees = new WC_Team_Payroll_Employee_Management();
		$employees->ajax_update_employee_salary();
	} );

	// Debug: Check GitHub update status
	add_action( 'wp_ajax_wc_tp_check_github_update', function() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'wc-team-payroll' ) );
		}

		// Clear cache
		delete_transient( 'wc_tp_github_release' );
		delete_transient( 'wc_tp_last_update_check' );

		// Get current version
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/woocommerce-team-payroll/woocommerce-team-payroll.php' );
		$current_version = isset( $plugin_data['Version'] ) ? $plugin_data['Version'] : '0';

		// Get latest release from GitHub
		$response = wp_remote_get(
			'https://api.github.com/repos/imranduzzlo/pv-team-payroll/releases/latest',
			array(
				'timeout'   => 10,
				'sslverify' => true,
				'headers'   => array(
					'Accept' => 'application/vnd.github.v3+json',
					'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ),
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( array(
				'message' => 'GitHub API Error: ' . $response->get_error_message(),
				'current_version' => $current_version,
			) );
		}

		$http_code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		$release = json_decode( $body, true );

		if ( $http_code !== 200 ) {
			wp_send_json_error( array(
				'message' => "GitHub API HTTP {$http_code}",
				'response' => $body,
				'current_version' => $current_version,
			) );
		}

		if ( ! isset( $release['tag_name'] ) ) {
			wp_send_json_error( array(
				'message' => 'No tag_name in GitHub response',
				'response' => $release,
				'current_version' => $current_version,
			) );
		}

		$latest_version = ltrim( $release['tag_name'], 'v' );

		wp_send_json_success( array(
			'current_version' => $current_version,
			'latest_version' => $latest_version,
			'github_tag' => $release['tag_name'],
			'github_url' => $release['html_url'],
			'published_at' => $release['published_at'],
			'update_available' => version_compare( $latest_version, $current_version, '>' ),
		) );
	} );

	add_action( 'wp_ajax_wc_tp_add_payment', function() {
		$employees = new WC_Team_Payroll_Employee_Management();
		$employees->ajax_add_payment();
	} );

	add_action( 'wp_ajax_wc_tp_delete_payment', function() {
		$employees = new WC_Team_Payroll_Employee_Management();
		$employees->ajax_delete_payment();
	} );

	// Payment Methods AJAX Handlers
	add_action( 'wp_ajax_wc_tp_get_payment_methods', function() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'wc-team-payroll' ) );
		}

		$user_id = intval( $_POST['user_id'] );
		if ( ! $user_id ) {
			wp_send_json_error( __( 'Invalid user ID', 'wc-team-payroll' ) );
		}

		$methods = get_user_meta( $user_id, '_wc_tp_payment_methods', true );
		if ( ! is_array( $methods ) ) {
			$methods = array();
		}

		wp_send_json_success( array( 'methods' => $methods ) );
	} );

	add_action( 'wp_ajax_wc_tp_add_payment_method', function() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'wc-team-payroll' ) );
		}

		$user_id = intval( $_POST['user_id'] );
		$method_name = sanitize_text_field( $_POST['method_name'] );
		$method_details = sanitize_text_field( $_POST['method_details'] );

		if ( ! $user_id || ! $method_name || ! $method_details ) {
			wp_send_json_error( __( 'Invalid parameters', 'wc-team-payroll' ) );
		}

		$methods = get_user_meta( $user_id, '_wc_tp_payment_methods', true );
		if ( ! is_array( $methods ) ) {
			$methods = array();
		}

		$new_method = array(
			'id' => time(),
			'method_name' => $method_name,
			'method_details' => $method_details,
		);

		$methods[] = $new_method;
		update_user_meta( $user_id, '_wc_tp_payment_methods', $methods );

		wp_send_json_success( array( 'message' => __( 'Payment method added', 'wc-team-payroll' ) ) );
	} );

	add_action( 'wp_ajax_wc_tp_delete_payment_method', function() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'wc-team-payroll' ) );
		}

		$user_id = intval( $_POST['user_id'] );
		$method_id = intval( $_POST['method_id'] );

		if ( ! $user_id || ! $method_id ) {
			wp_send_json_error( __( 'Invalid parameters', 'wc-team-payroll' ) );
		}

		$methods = get_user_meta( $user_id, '_wc_tp_payment_methods', true );
		if ( ! is_array( $methods ) ) {
			wp_send_json_error( __( 'No payment methods found', 'wc-team-payroll' ) );
		}

		$methods = array_filter( $methods, function( $method ) use ( $method_id ) {
			return $method['id'] !== $method_id;
		} );

		update_user_meta( $user_id, '_wc_tp_payment_methods', array_values( $methods ) );

		wp_send_json_success( array( 'message' => __( 'Payment method deleted', 'wc-team-payroll' ) ) );
	} );

	// Update Payment Method AJAX Handler
	add_action( 'wp_ajax_wc_tp_update_payment_method', function() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'wc-team-payroll' ) );
		}

		$user_id = intval( $_POST['user_id'] );
		$method_id = intval( $_POST['method_id'] );
		$method_name = sanitize_text_field( $_POST['method_name'] );
		$method_details = sanitize_text_field( $_POST['method_details'] );

		if ( ! $user_id || ! $method_id || ! $method_name || ! $method_details ) {
			wp_send_json_error( __( 'Invalid parameters', 'wc-team-payroll' ) );
		}

		$methods = get_user_meta( $user_id, '_wc_tp_payment_methods', true );
		if ( ! is_array( $methods ) ) {
			wp_send_json_error( __( 'No payment methods found', 'wc-team-payroll' ) );
		}

		// Find and update the method
		$found = false;
		foreach ( $methods as &$method ) {
			if ( $method['id'] === $method_id ) {
				$method['method_name'] = $method_name;
				$method['method_details'] = $method_details;
				$found = true;
				break;
			}
		}

		if ( ! $found ) {
			wp_send_json_error( __( 'Payment method not found', 'wc-team-payroll' ) );
		}

		update_user_meta( $user_id, '_wc_tp_payment_methods', $methods );

		wp_send_json_success( array( 'message' => __( 'Payment method updated', 'wc-team-payroll' ) ) );
	} );

	// Employee Payments AJAX Handler
	add_action( 'wp_ajax_wc_tp_get_employee_payments', function() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'wc-team-payroll' ) );
		}

		$user_id = intval( $_POST['user_id'] );
		if ( ! $user_id ) {
			wp_send_json_error( __( 'Invalid user ID', 'wc-team-payroll' ) );
		}

		$payments = get_user_meta( $user_id, '_wc_tp_payments', true );
		if ( ! is_array( $payments ) ) {
			$payments = array();
		}

		// Format payments for display
		$formatted_payments = array();
		foreach ( $payments as $payment ) {
			$added_by_id = isset( $payment['created_by'] ) ? $payment['created_by'] : 0;
			$added_by_user = $added_by_id ? get_user_by( 'id', $added_by_id ) : null;
			
			$formatted_payments[] = array(
				'id' => isset( $payment['id'] ) ? $payment['id'] : time(),
				'amount' => isset( $payment['amount'] ) ? $payment['amount'] : 0,
				'date' => isset( $payment['date'] ) ? $payment['date'] : date( 'Y-m-d H:i' ),
				'payment_method' => isset( $payment['payment_method'] ) ? $payment['payment_method'] : '',
				'note' => isset( $payment['note'] ) ? $payment['note'] : '',
				'added_by_id' => $added_by_id,
				'added_by_name' => $added_by_user ? $added_by_user->display_name : 'System',
				'added_by_email' => $added_by_user ? $added_by_user->user_email : '',
				'added_by_role' => $added_by_user ? implode( ', ', $added_by_user->roles ) : '',
			);
		}

		wp_send_json_success( array( 'payments' => $formatted_payments ) );
	} );

	// Salary AJAX Handlers
	add_action( 'wp_ajax_wc_tp_get_employee_salary', function() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'wc-team-payroll' ) );
		}

		$user_id = intval( $_POST['user_id'] );
		if ( ! $user_id ) {
			wp_send_json_error( __( 'Invalid user ID', 'wc-team-payroll' ) );
		}

		$salary_type = get_user_meta( $user_id, '_wc_tp_salary_type', true );
		$salary_amount = get_user_meta( $user_id, '_wc_tp_salary_amount', true );
		$salary_frequency = get_user_meta( $user_id, '_wc_tp_salary_frequency', true );

		if ( ! $salary_type ) {
			$salary_type = 'commission';
		}
		if ( ! $salary_frequency ) {
			$salary_frequency = 'monthly';
		}

		wp_send_json_success( array(
			'type' => $salary_type,
			'amount' => $salary_amount,
			'frequency' => $salary_frequency,
		) );
	} );

	add_action( 'wp_ajax_wc_tp_get_salary_history', function() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'wc-team-payroll' ) );
		}

		$user_id = intval( $_POST['user_id'] );
		if ( ! $user_id ) {
			wp_send_json_error( __( 'Invalid user ID', 'wc-team-payroll' ) );
		}

		$history = get_user_meta( $user_id, '_wc_tp_salary_history', true );
		if ( ! is_array( $history ) ) {
			$history = array();
		}

		// Format history with user information
		$formatted_history = array();
		foreach ( $history as $entry ) {
			$changed_by_id = isset( $entry['changed_by'] ) ? $entry['changed_by'] : 0;
			$changed_by_user = $changed_by_id ? get_user_by( 'id', $changed_by_id ) : null;
			
			$formatted_history[] = array(
				'date' => isset( $entry['date'] ) ? $entry['date'] : date( 'Y-m-d H:i' ),
				'old_type' => isset( $entry['old_type'] ) ? $entry['old_type'] : 'commission',
				'new_type' => isset( $entry['new_type'] ) ? $entry['new_type'] : 'commission',
				'old_amount' => isset( $entry['old_amount'] ) ? $entry['old_amount'] : 0,
				'new_amount' => isset( $entry['new_amount'] ) ? $entry['new_amount'] : 0,
				'new_frequency' => isset( $entry['new_frequency'] ) ? $entry['new_frequency'] : '',
				'changed_by_id' => $changed_by_id,
				'changed_by_name' => $changed_by_user ? $changed_by_user->display_name : 'System',
				'changed_by_email' => $changed_by_user ? $changed_by_user->user_email : '',
				'changed_by_role' => $changed_by_user ? implode( ', ', $changed_by_user->roles ) : '',
			);
		}

		wp_send_json_success( array( 'history' => $formatted_history ) );
	} );

	add_action( 'wp_ajax_wc_tp_update_payment', function() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'wc-team-payroll' ) );
		}

		$user_id = intval( $_POST['user_id'] );
		$payment_id = sanitize_text_field( $_POST['payment_id'] );
		$amount = floatval( $_POST['amount'] );
		$date = sanitize_text_field( $_POST['date'] );
		$payment_method = isset( $_POST['payment_method'] ) ? sanitize_text_field( $_POST['payment_method'] ) : '';
		$note = isset( $_POST['note'] ) ? sanitize_textarea_field( $_POST['note'] ) : '';

		if ( ! $user_id || ! $payment_id || ! $amount || ! $date ) {
			wp_send_json_error( __( 'Invalid parameters', 'wc-team-payroll' ) );
		}

		$payments = get_user_meta( $user_id, '_wc_tp_payments', true );
		if ( ! is_array( $payments ) || empty( $payments ) ) {
			wp_send_json_error( __( 'No payments found', 'wc-team-payroll' ) );
		}

		// Find and update the payment by ID
		$found = false;
		foreach ( $payments as &$payment ) {
			if ( isset( $payment['id'] ) && $payment['id'] === $payment_id ) {
				$payment['amount'] = $amount;
				$payment['date'] = $date;
				$payment['payment_method'] = $payment_method;
				$payment['note'] = $note;
				$found = true;
				break;
			}
		}

		if ( ! $found ) {
			wp_send_json_error( __( 'Payment not found', 'wc-team-payroll' ) );
		}

		// Save updated payments
		if ( update_user_meta( $user_id, '_wc_tp_payments', $payments ) !== false ) {
			wp_send_json_success( array( 'message' => __( 'Payment updated successfully', 'wc-team-payroll' ) ) );
		} else {
			wp_send_json_error( __( 'Failed to update payment', 'wc-team-payroll' ) );
		}
	} );

	add_action( 'wp_ajax_wc_tp_get_payment_data', function() {
		$employees = new WC_Team_Payroll_Employee_Management();
		$employees->ajax_get_payment_data();
	} );

	add_action( 'wp_ajax_wc_tp_add_order_bonus', function() {
		$employees = new WC_Team_Payroll_Employee_Management();
		$employees->ajax_add_order_bonus();
	} );

	// Employee Status Update AJAX Handler
	add_action( 'wp_ajax_wc_tp_update_employee_status', function() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'wc-team-payroll' ) );
		}

		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'wc_team_payroll_nonce' ) ) {
			wp_send_json_error( __( 'Security check failed', 'wc-team-payroll' ) );
		}

		$user_id = intval( $_POST['user_id'] );
		$status = sanitize_text_field( $_POST['status'] );

		if ( ! $user_id || ! in_array( $status, array( 'active', 'inactive' ) ) ) {
			wp_send_json_error( __( 'Invalid parameters', 'wc-team-payroll' ) );
		}

		// Update employee status
		$updated = update_user_meta( $user_id, '_wc_tp_employee_status', $status );

		if ( $updated !== false ) {
			wp_send_json_success( array( 
				'message' => __( 'Employee status updated successfully', 'wc-team-payroll' ),
				'status' => $status
			) );
		} else {
			wp_send_json_error( __( 'Failed to update employee status', 'wc-team-payroll' ) );
		}
	} );

	add_action( 'wp_ajax_wc_tp_get_dashboard_data', function() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'wc-team-payroll' ) );
		}

		$date_preset = isset( $_POST['date_preset'] ) ? sanitize_text_field( $_POST['date_preset'] ) : 'all-time';
		$start_date = isset( $_POST['start_date'] ) ? sanitize_text_field( $_POST['start_date'] ) : date( 'Y-m-01' );
		$end_date = isset( $_POST['end_date'] ) ? sanitize_text_field( $_POST['end_date'] ) : date( 'Y-m-t' );

		// Get payroll data (filtered by order creation/modification date)
		$payroll = array();
		if ( class_exists( 'WC_Team_Payroll_Payroll_Engine' ) ) {
			$payroll = WC_Team_Payroll_Payroll_Engine::get_payroll_by_date_range( $start_date, $end_date );
		}

		// Process payroll data to include vb_user_id and formatted names
		$processed_payroll = array();
		foreach ( $payroll as $user_id => $data ) {
			$vb_user_id = $data['user'] ? get_user_meta( $data['user']->ID, 'vb_user_id', true ) : '';
			$employee_name = $vb_user_id ? esc_html( $vb_user_id ) . ' ' . esc_html( $data['user']->display_name ) : ( $data['user'] ? esc_html( $data['user']->display_name ) : 'Unknown' );
			$profile_picture_id = $data['user'] ? get_user_meta( $data['user']->ID, '_wc_tp_profile_picture', true ) : '';
			$profile_picture_url = '';
			$phone = $data['user'] ? get_user_meta( $data['user']->ID, 'billing_phone', true ) : '';

			if ( $profile_picture_id ) {
				$profile_picture_url = wp_get_attachment_url( $profile_picture_id );
			}
			
			$processed_payroll[ $user_id ] = array(
				'user_id'         => $data['user_id'],
				'user'            => $data['user'],
				'user_email'      => $data['user'] ? $data['user']->user_email : 'N/A',
				'vb_user_id'      => $vb_user_id,
				'name'            => $employee_name,
				'total'           => $data['total'],
				'orders'          => $data['orders'],
				'paid'            => $data['paid'],
				'due'             => $data['due'],
				'profile_picture' => $profile_picture_url,
				'phone'           => $phone,
				'user_role'       => $data['user'] ? implode( ', ', $data['user']->roles ) : 'N/A',
				'manage_url'      => add_query_arg( array( 'page' => 'wc-team-payroll-employee-detail', 'user_id' => $data['user_id'] ), admin_url( 'admin.php' ) ),
			);
		}
		$payroll = $processed_payroll;

		// Calculate stats
		$total_earnings = 0;
		$total_paid = 0;
		$total_due = 0;

		foreach ( $payroll as $data ) {
			$total_earnings += $data['total'];
			$total_paid += $data['paid'];
			$total_due += $data['due'];
		}

		// Count unique orders (filtered by order creation/modification date)
		$unique_orders = array();
		$args = array(
			'limit'  => -1,
			'status' => array( 'completed', 'processing', 'refunded' ),
			'date_query' => array(
				array(
					'after'     => $start_date,
					'before'    => $end_date,
					'inclusive' => true,
				),
			),
		);
		$orders = wc_get_orders( $args );
		foreach ( $orders as $order ) {
			$commission_data = $order->get_meta( '_commission_data' );
			
			// Only count orders that have commission data
			if ( $commission_data ) {
				$unique_orders[ $order->get_id() ] = true;
			}
		}
		$total_orders = count( $unique_orders );

		// Get latest employees (filtered by user creation date)
		$latest_employees_data = array();
		$start_timestamp = strtotime( $start_date . ' 00:00:00' );
		$end_timestamp = strtotime( $end_date . ' 23:59:59' );
		
		$all_employees = get_users( array(
			'role__in' => array( 'shop_employee', 'shop_manager', 'administrator' ),
			'orderby'  => 'user_registered',
			'order'    => 'DESC',
			'number'   => -1,
		) );

		$filtered_employees = array();
		foreach ( $all_employees as $employee ) {
			$user_registered_timestamp = strtotime( $employee->user_registered );
			
			// Filter by user creation date
			if ( $user_registered_timestamp >= $start_timestamp && $user_registered_timestamp <= $end_timestamp ) {
				$filtered_employees[] = $employee;
			}
		}

		// Get only latest 10 from filtered employees
		$filtered_employees = array_slice( $filtered_employees, 0, 10 );

		foreach ( $filtered_employees as $employee ) {
			$is_fixed_salary = get_user_meta( $employee->ID, '_wc_tp_fixed_salary', true );
			$is_combined_salary = get_user_meta( $employee->ID, '_wc_tp_combined_salary', true );
			$salary = get_user_meta( $employee->ID, '_wc_tp_salary_amount', true );
			$frequency = get_user_meta( $employee->ID, '_wc_tp_salary_frequency', true );
			$vb_user_id = get_user_meta( $employee->ID, 'vb_user_id', true );
			$profile_picture_id = get_user_meta( $employee->ID, '_wc_tp_profile_picture', true );
			$profile_picture_url = '';
			$phone = get_user_meta( $employee->ID, 'billing_phone', true );
			$employee_status = get_user_meta( $employee->ID, '_wc_tp_employee_status', true );
			if ( ! $employee_status ) {
				$employee_status = 'active'; // Default to active
			}

			if ( $profile_picture_id ) {
				$profile_picture_url = wp_get_attachment_url( $profile_picture_id );
			}

			if ( $is_fixed_salary ) {
				$type = __( 'Fixed Salary', 'wc-team-payroll' );
				$salary_info = wc_price( $salary ) . ' / ' . esc_html( $frequency );
			} elseif ( $is_combined_salary ) {
				$type = __( 'Combined', 'wc-team-payroll' );
				$salary_info = wc_price( $salary ) . ' / ' . esc_html( $frequency );
			} else {
				$type = __( 'Commission', 'wc-team-payroll' );
				$salary_info = __( 'Commission Based', 'wc-team-payroll' );
			}

			$employee_name = $vb_user_id ? esc_html( $vb_user_id ) . ' ' . esc_html( $employee->display_name ) : esc_html( $employee->display_name );

			$latest_employees_data[] = array(
				'user_id'           => $employee->ID,
				'vb_user_id'        => $vb_user_id,
				'display_name'      => $employee_name,
				'user_email'        => $employee->user_email,
				'phone'             => $phone,
				'type'              => $type,
				'salary_info'       => $salary_info,
				'status'            => $employee_status,
				'manage_url'        => add_query_arg( array( 'page' => 'wc-team-payroll-employee-detail', 'user_id' => $employee->ID ), admin_url( 'admin.php' ) ),
				'profile_picture'   => $profile_picture_url,
				'user_role'         => implode( ', ', $employee->roles ),
			);
		}

		// Count total employees (filtered by user creation date)
		$total_employees = count( $filtered_employees );

		// Get top earners (filtered by order creation/modification date, only those with at least 1 order, up to 10)
		$top_earners_data = array();
		$sorted_payroll = $payroll;
		usort( $sorted_payroll, function( $a, $b ) {
			return $b['total'] - $a['total'];
		} );

		$count = 0;
		foreach ( $sorted_payroll as $data ) {
			if ( $count >= 10 ) {
				break;
			}
			// Only include if they have at least 1 order
			if ( $data['orders'] < 1 ) {
				continue;
			}
			$vb_user_id = $data['user'] ? get_user_meta( $data['user']->ID, 'vb_user_id', true ) : '';
			$employee_name = $vb_user_id ? esc_html( $vb_user_id ) . ' ' . esc_html( $data['user']->display_name ) : ( $data['user'] ? esc_html( $data['user']->display_name ) : 'Unknown' );
			$profile_picture_id = $data['user'] ? get_user_meta( $data['user']->ID, '_wc_tp_profile_picture', true ) : '';
			$profile_picture_url = '';
			$phone = $data['user'] ? get_user_meta( $data['user']->ID, 'billing_phone', true ) : '';

			if ( $profile_picture_id ) {
				$profile_picture_url = wp_get_attachment_url( $profile_picture_id );
			}
			
			$top_earners_data[] = array(
				'user_id'         => $data['user_id'],
				'name'            => $employee_name,
				'earnings'        => $data['total'],
				'orders'          => $data['orders'],
				'profile_picture' => $profile_picture_url,
				'user_email'      => $data['user'] ? $data['user']->user_email : 'N/A',
				'phone'           => $phone,
				'user_role'       => $data['user'] ? implode( ', ', $data['user']->roles ) : 'N/A',
				'manage_url'      => add_query_arg( array( 'page' => 'wc-team-payroll-employee-detail', 'user_id' => $data['user_id'] ), admin_url( 'admin.php' ) ),
			);
			$count++;
		}

		// Get recent payments (filtered by payment date)
		global $wpdb;
		$recent_payments_data = array();
		
		// Get all employees
		$all_employees_for_payments = get_users( array(
			'role__in' => array( 'shop_employee', 'shop_manager', 'administrator' ),
			'number'   => -1,
		) );

		$all_payments = array();
		foreach ( $all_employees_for_payments as $employee ) {
			$payments = get_user_meta( $employee->ID, '_wc_tp_payments', true );
			if ( is_array( $payments ) ) {
				foreach ( $payments as $payment ) {
					$payment_date_str = $payment['date'] ?? current_time( 'mysql' );
					
					// Convert datetime-local format
					if ( strpos( $payment_date_str, 'T' ) !== false ) {
						$payment_date_str = str_replace( 'T', ' ', $payment_date_str );
					}
					
					$payment_timestamp = strtotime( $payment_date_str );
					
					// Filter by payment date
					if ( $payment_timestamp !== false && $payment_timestamp >= $start_timestamp && $payment_timestamp <= $end_timestamp ) {
						$vb_user_id = get_user_meta( $employee->ID, 'vb_user_id', true );
						$employee_name = $vb_user_id ? esc_html( $vb_user_id ) . ' ' . esc_html( $employee->display_name ) : esc_html( $employee->display_name );
						$profile_picture_id = get_user_meta( $employee->ID, '_wc_tp_profile_picture', true );
						$profile_picture_url = '';
						$phone = get_user_meta( $employee->ID, 'billing_phone', true );

						if ( $profile_picture_id ) {
							$profile_picture_url = wp_get_attachment_url( $profile_picture_id );
						}
						
						$all_payments[] = array(
							'user_id'         => $employee->ID,
							'employee_name'   => $employee_name,
							'amount'          => $payment['amount'] ?? 0,
							'date'            => date( 'M d, Y', $payment_timestamp ),
							'timestamp'       => $payment_timestamp,
							'status'          => $payment['status'] ?? 'pending',
							'profile_picture' => $profile_picture_url,
							'user_email'      => $employee->user_email,
							'phone'           => $phone,
							'user_role'       => implode( ', ', $employee->roles ),
							'manage_url'      => add_query_arg( array( 'page' => 'wc-team-payroll-employee-detail', 'user_id' => $employee->ID ), admin_url( 'admin.php' ) ),
						);
					}
				}
			}
		}

		// Sort by date descending and get latest 10
		usort( $all_payments, function( $a, $b ) {
			return $b['timestamp'] - $a['timestamp'];
		} );

		$recent_payments_data = array_slice( $all_payments, 0, 10 );
		
		// Keep timestamp for sorting but rename it
		foreach ( $recent_payments_data as &$payment ) {
			$payment['date_timestamp'] = $payment['timestamp'];
			unset( $payment['timestamp'] );
		}

		wp_send_json_success( array(
			'total_employees'   => $total_employees,
			'total_orders'      => $total_orders,
			'total_earnings'    => $total_earnings,
			'total_paid'        => $total_paid,
			'total_due'         => $total_due,
			'payroll'           => $payroll,
			'latest_employees'  => $latest_employees_data,
			'top_earners'       => $top_earners_data,
			'recent_payments'   => $recent_payments_data,
			'currency'          => get_woocommerce_currency(),
			'currency_symbol'   => get_woocommerce_currency_symbol(),
			'currency_pos'      => get_option( 'woocommerce_currency_pos', 'left' ),
		) );
	} );

	add_action( 'wp_ajax_wc_tp_get_payroll_data', function() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'wc-team-payroll' ) );
		}

		$month = intval( $_POST['month'] );
		$year = intval( $_POST['year'] );

		// Get payroll data
		$payroll = array();
		if ( class_exists( 'WC_Team_Payroll_Payroll_Engine' ) ) {
			$payroll = WC_Team_Payroll_Payroll_Engine::get_monthly_payroll( $year, $month );
		}

		// Process payroll data to include vb_user_id and formatted names
		$processed_payroll = array();
		foreach ( $payroll as $user_id => $data ) {
			$vb_user_id = $data['user'] ? get_user_meta( $data['user']->ID, 'vb_user_id', true ) : '';
			$employee_name = $vb_user_id ? '(' . esc_html( $vb_user_id ) . ') ' . esc_html( $data['user']->display_name ) : ( $data['user'] ? esc_html( $data['user']->display_name ) : 'Unknown' );
			
			$processed_payroll[ $user_id ] = array(
				'user_id'    => $data['user_id'],
				'user'       => $data['user'],
				'vb_user_id' => $vb_user_id,
				'name'       => $employee_name,
				'total'      => $data['total'],
				'orders'     => $data['orders'],
				'paid'       => $data['paid'],
				'due'        => $data['due'],
			);
		}
		$payroll = $processed_payroll;

		// Calculate stats
		$total_employees = 0;
		$total_earnings = 0;
		$total_paid = 0;
		$total_due = 0;
		$total_orders = 0;

		foreach ( $payroll as $data ) {
			$total_employees++;
			$total_earnings += $data['total'];
			$total_paid += $data['paid'];
			$total_due += $data['due'];
			$total_orders += $data['orders'];
		}

		wp_send_json_success( array(
			'total_employees'   => $total_employees,
			'total_orders'      => $total_orders,
			'total_earnings'    => $total_earnings,
			'total_paid'        => $total_paid,
			'total_due'         => $total_due,
			'payroll'           => $payroll,
			'currency'          => get_woocommerce_currency(),
			'currency_symbol'   => get_woocommerce_currency_symbol(),
			'currency_pos'      => get_option( 'woocommerce_currency_pos', 'left' ),
		) );
	} );

	add_action( 'wp_ajax_wc_tp_get_payroll_data_range', function() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'wc-team-payroll' ) );
		}

		$start_date = isset( $_POST['start_date'] ) ? sanitize_text_field( $_POST['start_date'] ) : date( 'Y-m-01' );
		$end_date = isset( $_POST['end_date'] ) ? sanitize_text_field( $_POST['end_date'] ) : date( 'Y-m-t' );
		$search_query = isset( $_POST['search_query'] ) ? sanitize_text_field( $_POST['search_query'] ) : '';
		$salary_type = isset( $_POST['salary_type'] ) ? sanitize_text_field( $_POST['salary_type'] ) : '';

		// Get payroll data
		$payroll = array();
		if ( class_exists( 'WC_Team_Payroll_Payroll_Engine' ) ) {
			$payroll = WC_Team_Payroll_Payroll_Engine::get_payroll_by_date_range( $start_date, $end_date );
		}

		// Process payroll data to include vb_user_id and formatted names
		$processed_payroll = array();
		foreach ( $payroll as $user_id => $data ) {
			$vb_user_id = $data['user'] ? get_user_meta( $data['user']->ID, 'vb_user_id', true ) : '';
			$employee_name = $vb_user_id ? esc_html( $vb_user_id ) . ' ' . esc_html( $data['user']->display_name ) : ( $data['user'] ? esc_html( $data['user']->display_name ) : 'Unknown' );
			$profile_picture_id = $data['user'] ? get_user_meta( $data['user']->ID, '_wc_tp_profile_picture', true ) : '';
			$profile_picture_url = '';
			
			if ( $profile_picture_id ) {
				$profile_picture_url = wp_get_attachment_url( $profile_picture_id );
			}
			
			// Determine salary type
			$is_fixed_salary = $data['user'] ? get_user_meta( $data['user']->ID, '_wc_tp_fixed_salary', true ) : false;
			$is_combined_salary = $data['user'] ? get_user_meta( $data['user']->ID, '_wc_tp_combined_salary', true ) : false;
			
			if ( $is_fixed_salary ) {
				$type_key = 'fixed';
			} elseif ( $is_combined_salary ) {
				$type_key = 'combined';
			} else {
				$type_key = 'commission';
			}
			
			$user_email = $data['user'] ? $data['user']->user_email : '';
			$phone = $data['user'] ? get_user_meta( $data['user']->ID, 'billing_phone', true ) : '';
			$user_role = $data['user'] ? ( isset( $data['user']->roles[0] ) ? $data['user']->roles[0] : '' ) : '';
			
			$processed_payroll[ $user_id ] = array(
				'user_id'    => $data['user_id'],
				'user'       => $data['user'],
				'vb_user_id' => $vb_user_id,
				'name'       => $employee_name,
				'total'      => $data['total'],
				'orders'     => $data['orders'],
				'paid'       => $data['paid'],
				'due'        => $data['due'],
				'salary_type' => $type_key,
				'profile_picture' => $profile_picture_url,
				'user_email' => $user_email,
				'phone'      => $phone,
				'user_role'  => $user_role,
			);
		}
		$payroll = $processed_payroll;

		// Apply salary type filter if provided
		if ( ! empty( $salary_type ) ) {
			$filtered_payroll = array();
			foreach ( $payroll as $user_id => $data ) {
				if ( $data['salary_type'] === $salary_type ) {
					$filtered_payroll[ $user_id ] = $data;
				}
			}
			$payroll = $filtered_payroll;
		}

		// Apply search filter if search query is provided
		if ( ! empty( $search_query ) ) {
			$search_query_lower = strtolower( $search_query );
			$filtered_payroll = array();

			foreach ( $payroll as $user_id => $data ) {
				$user = $data['user'];
				$vb_user_id = $data['vb_user_id'];
				$email = $user ? $user->user_email : '';
				$phone = $user ? get_user_meta( $user->ID, 'billing_phone', true ) : '';
				$display_name = $user ? $user->display_name : '';

				// Check if search query matches any field
				$matches = false;
				if ( stripos( $display_name, $search_query ) !== false ) {
					$matches = true;
				} elseif ( stripos( $vb_user_id, $search_query ) !== false ) {
					$matches = true;
				} elseif ( stripos( (string) $user_id, $search_query ) !== false ) {
					$matches = true;
				} elseif ( stripos( $email, $search_query ) !== false ) {
					$matches = true;
				} elseif ( stripos( $phone, $search_query ) !== false ) {
					$matches = true;
				}

				if ( $matches ) {
					$filtered_payroll[ $user_id ] = $data;
				}
			}

			$payroll = $filtered_payroll;
		}

		wp_send_json_success( array(
			'payroll'           => $payroll,
			'currency'          => get_woocommerce_currency(),
			'currency_symbol'   => get_woocommerce_currency_symbol(),
			'currency_pos'      => get_option( 'woocommerce_currency_pos', 'left' ),
		) );
	} );

	add_action( 'wp_ajax_wc_tp_get_employees_data', function() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'wc-team-payroll' ) );
		}

		$search_query = isset( $_POST['search_query'] ) ? sanitize_text_field( $_POST['search_query'] ) : '';
		$salary_type = isset( $_POST['salary_type'] ) ? sanitize_text_field( $_POST['salary_type'] ) : '';
		$start_date = isset( $_POST['start_date'] ) ? sanitize_text_field( $_POST['start_date'] ) : '';
		$end_date = isset( $_POST['end_date'] ) ? sanitize_text_field( $_POST['end_date'] ) : '';

		// Get all employees
		$employees = get_users( array(
			'role__in' => array( 'shop_employee', 'shop_manager', 'administrator' ),
			'number'   => -1,
		) );

		$employees_data = array();

		foreach ( $employees as $employee ) {
			$is_fixed_salary = get_user_meta( $employee->ID, '_wc_tp_fixed_salary', true );
			$is_combined_salary = get_user_meta( $employee->ID, '_wc_tp_combined_salary', true );
			$salary = get_user_meta( $employee->ID, '_wc_tp_salary_amount', true );
			$frequency = get_user_meta( $employee->ID, '_wc_tp_salary_frequency', true );
			$vb_user_id = get_user_meta( $employee->ID, 'vb_user_id', true );

			// Determine salary type
			if ( $is_fixed_salary ) {
				$type = __( 'Fixed Salary', 'wc-team-payroll' );
				$type_key = 'fixed';
				$salary_info = wc_price( $salary ) . ' / ' . esc_html( $frequency );
			} elseif ( $is_combined_salary ) {
				$type = __( 'Combined (Base + Commission)', 'wc-team-payroll' );
				$type_key = 'combined';
				$salary_info = wc_price( $salary ) . ' / ' . esc_html( $frequency );
			} else {
				$type = __( 'Commission Based', 'wc-team-payroll' );
				$type_key = 'commission';
				$salary_info = __( 'Commission Based', 'wc-team-payroll' );
			}

			// Apply salary type filter
			if ( ! empty( $salary_type ) && $salary_type !== $type_key ) {
				continue;
			}

			// Apply date range filter (employee creation date)
			if ( ! empty( $start_date ) || ! empty( $end_date ) ) {
				$employee_created = strtotime( $employee->user_registered );
				$filter_start = ! empty( $start_date ) ? strtotime( $start_date ) : 0;
				$filter_end = ! empty( $end_date ) ? strtotime( $end_date . ' 23:59:59' ) : PHP_INT_MAX;

				if ( $employee_created < $filter_start || $employee_created > $filter_end ) {
					continue;
				}
			}

			// Apply search filter
			if ( ! empty( $search_query ) ) {
				$matches = false;
				if ( stripos( $employee->display_name, $search_query ) !== false ) {
					$matches = true;
				} elseif ( stripos( $employee->user_email, $search_query ) !== false ) {
					$matches = true;
				} elseif ( stripos( $vb_user_id, $search_query ) !== false ) {
					$matches = true;
				}

				if ( ! $matches ) {
					continue;
				}
			}

			$employee_name = $vb_user_id ? esc_html( $vb_user_id ) . ' ' . esc_html( $employee->display_name ) : esc_html( $employee->display_name );
			$profile_picture_id = get_user_meta( $employee->ID, '_wc_tp_profile_picture', true );
			$profile_picture_url = '';
			
			if ( $profile_picture_id ) {
				$profile_picture_url = wp_get_attachment_url( $profile_picture_id );
			}
			
			$employee_status = get_user_meta( $employee->ID, '_wc_tp_employee_status', true );
			if ( ! $employee_status ) {
				$employee_status = 'active'; // Default to active
			}

			$employees_data[] = array(
				'user_id'      => $employee->ID,
				'vb_user_id'   => $vb_user_id,
				'display_name' => $employee_name,
				'user_email'   => $employee->user_email,
				'type'         => $type,
				'salary_info'  => $salary_info,
				'manage_url'   => add_query_arg( array( 'page' => 'wc-team-payroll-employee-detail', 'user_id' => $employee->ID ), admin_url( 'admin.php' ) ),
				'profile_picture' => $profile_picture_url,
				'status'       => $employee_status,
			);
		}

		wp_send_json_success( array(
			'employees' => $employees_data,
		) );
	} );

	add_action( 'wp_ajax_wc_tp_get_employee_stats', function() {
		check_ajax_referer( 'wc_team_payroll_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'wc-team-payroll' ) );
		}

		$user_id = isset( $_POST['user_id'] ) ? intval( $_POST['user_id'] ) : 0;
		if ( ! $user_id ) {
			wp_send_json_error( __( 'Invalid user ID', 'wc-team-payroll' ) );
		}

		$core_engine = new WC_Team_Payroll_Core_Engine();
		$total_earnings = $core_engine->get_user_total_earnings( $user_id );
		$total_paid = $core_engine->get_user_total_paid( $user_id );
		$total_due = $total_earnings - $total_paid;

		wp_send_json_success( array(
			'total_earnings' => $total_earnings,
			'total_paid'     => $total_paid,
			'total_due'      => $total_due,
		) );
	} );

	add_action( 'wp_ajax_wc_tp_get_employee_orders', function() {
		check_ajax_referer( 'wc_team_payroll_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'wc-team-payroll' ) );
		}

		$user_id = isset( $_POST['user_id'] ) ? intval( $_POST['user_id'] ) : 0;
		$start_date = isset( $_POST['start_date'] ) ? sanitize_text_field( $_POST['start_date'] ) : date( 'Y-m-01' );
		$end_date = isset( $_POST['end_date'] ) ? sanitize_text_field( $_POST['end_date'] ) : date( 'Y-m-t' );
		$status_filter = isset( $_POST['status'] ) ? sanitize_text_field( $_POST['status'] ) : '';
		$flag_filter = isset( $_POST['flag'] ) ? sanitize_text_field( $_POST['flag'] ) : '';
		$search_query = isset( $_POST['search'] ) ? sanitize_text_field( $_POST['search'] ) : '';

		if ( ! $user_id ) {
			wp_send_json_error( __( 'Invalid user ID', 'wc-team-payroll' ) );
		}

		// Get core engine for commission recalculation
		$core_engine = new WC_Team_Payroll_Core_Engine();

		// Get all orders
		$args = array(
			'limit'  => -1,
			'status' => array( 'completed', 'processing', 'pending', 'cancelled', 'refunded' ),
		);

		$orders = wc_get_orders( $args );
		$orders_data = array();

		foreach ( $orders as $order ) {
			$agent_id = $order->get_meta( '_primary_agent_id' );
			$processor_id = $order->get_meta( '_processor_user_id' );
			$commission_data = $order->get_meta( '_commission_data' );

			// Check if user is involved in this order
			$user_role = null;
			if ( intval( $agent_id ) === intval( $user_id ) ) {
				$user_role = 'agent';
			} elseif ( intval( $processor_id ) === intval( $user_id ) ) {
				$user_role = 'processor';
			}

			if ( ! $user_role ) {
				continue; // User not involved in this order
			}

			// Apply status filter
			if ( ! empty( $status_filter ) && $order->get_status() !== $status_filter ) {
				continue;
			}

			// Recalculate commission based on current salary types
			if ( $commission_data ) {
				$recalculated_commission = $core_engine->calculate_commission( $order, $agent_id, $processor_id );
				$commission_data = $recalculated_commission;
			}

			// Determine flag
			$flag = 'owner';
			$flag_label = __( 'Owner', 'wc-team-payroll' );

			if ( $agent_id && $processor_id && intval( $agent_id ) !== intval( $processor_id ) ) {
				if ( $user_role === 'agent' ) {
					$flag = 'affiliate_to';
					$flag_label = __( 'Affiliate To', 'wc-team-payroll' );
				} else {
					$flag = 'affiliate_from';
					$flag_label = __( 'Affiliate From', 'wc-team-payroll' );
				}
			}

			// Apply flag filter
			if ( ! empty( $flag_filter ) && $flag !== $flag_filter ) {
				continue;
			}

			// Calculate user earnings from recalculated commission
			$user_earnings = 0;
			if ( $commission_data ) {
				if ( $user_role === 'agent' ) {
					$user_earnings = $commission_data['agent_earnings'];
				} else {
					$user_earnings = $commission_data['processor_earnings'];
				}
			}

			// Get customer info
			$customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
			$customer_email = $order->get_billing_email();
			$customer_phone = $order->get_billing_phone();

			// Apply search filter
			if ( ! empty( $search_query ) ) {
				$matches = false;
				if ( stripos( $order->get_order_number(), $search_query ) !== false ) {
					$matches = true;
				} elseif ( stripos( $customer_name, $search_query ) !== false ) {
					$matches = true;
				} elseif ( stripos( $customer_email, $search_query ) !== false ) {
					$matches = true;
				} elseif ( stripos( $customer_phone, $search_query ) !== false ) {
					$matches = true;
				}

				if ( ! $matches ) {
					continue;
				}
			}

			// Apply date range filter
			$order_date = $order->get_date_created();
			if ( $order_date ) {
				$order_date_str = $order_date->format( 'Y-m-d' );
				if ( $order_date_str < $start_date || $order_date_str > $end_date ) {
					continue;
				}
			}

			$orders_data[] = array(
				'order_id'        => $order->get_id(),
				'customer_name'   => $customer_name,
				'customer_email'  => $customer_email,
				'customer_phone'  => $customer_phone,
				'total'           => $order->get_total(),
				'status'          => ucfirst( $order->get_status() ),
				'commission'      => $commission_data ? $commission_data['total_commission'] : 0,
				'user_earnings'   => $user_earnings,
				'flag'            => $flag,
				'flag_label'      => $flag_label,
				'date'            => $order->get_date_created()->format( 'Y-m-d' ),
			);
		}

		wp_send_json_success( array(
			'orders' => $orders_data,
		) );
	} );

	// AJAX handler for getting all payments
	add_action( 'wp_ajax_wc_tp_get_all_payments', function() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'wc-team-payroll' ) );
		}

		$start_date = isset( $_POST['start_date'] ) ? sanitize_text_field( $_POST['start_date'] ) : date( 'Y-m-01' );
		$end_date = isset( $_POST['end_date'] ) ? sanitize_text_field( $_POST['end_date'] ) : date( 'Y-m-t' );
		$salary_type = isset( $_POST['salary_type'] ) ? sanitize_text_field( $_POST['salary_type'] ) : '';
		$search = isset( $_POST['search'] ) ? sanitize_text_field( $_POST['search'] ) : '';

		$start_timestamp = strtotime( $start_date . ' 00:00:00' );
		$end_timestamp = strtotime( $end_date . ' 23:59:59' );

		// Get all employees
		$all_employees = get_users( array(
			'role__in' => array( 'shop_employee', 'shop_manager', 'administrator' ),
			'number'   => -1,
		) );

		$all_payments = array();
		foreach ( $all_employees as $employee ) {
			// Filter by salary type if specified
			if ( $salary_type ) {
				$is_fixed = get_user_meta( $employee->ID, '_wc_tp_fixed_salary', true );
				$is_combined = get_user_meta( $employee->ID, '_wc_tp_combined_salary', true );
				
				$employee_type = 'commission';
				if ( $is_fixed ) {
					$employee_type = 'fixed';
				} elseif ( $is_combined ) {
					$employee_type = 'combined';
				}

				if ( $employee_type !== $salary_type ) {
					continue;
				}
			}

			// Get employee details for search
			$vb_user_id = get_user_meta( $employee->ID, 'vb_user_id', true );
			$user_phone = get_user_meta( $employee->ID, 'billing_phone', true );
			$employee_name = $vb_user_id ? '(' . $vb_user_id . ') ' . $employee->display_name : $employee->display_name;

			// Search filter
			if ( $search ) {
				$search_lower = strtolower( $search );
				$match = false;

				if ( stripos( $employee->display_name, $search ) !== false ||
				     stripos( $employee->user_email, $search ) !== false ||
				     stripos( $vb_user_id, $search ) !== false ||
				     stripos( $user_phone, $search ) !== false ) {
					$match = true;
				}

				if ( ! $match ) {
					continue;
				}
			}

			// Get payments for this employee
			$payments = get_user_meta( $employee->ID, '_wc_tp_payments', true );
			if ( is_array( $payments ) ) {
				foreach ( $payments as $payment ) {
					$payment_date_str = $payment['date'] ?? current_time( 'mysql' );
					
					// Convert datetime-local format
					if ( strpos( $payment_date_str, 'T' ) !== false ) {
						$payment_date_str = str_replace( 'T', ' ', $payment_date_str );
					}
					
					$payment_timestamp = strtotime( $payment_date_str );
					
					// Filter by payment date
					if ( $payment_timestamp !== false && $payment_timestamp >= $start_timestamp && $payment_timestamp <= $end_timestamp ) {
						// Get salary type label
						$is_fixed = get_user_meta( $employee->ID, '_wc_tp_fixed_salary', true );
						$is_combined = get_user_meta( $employee->ID, '_wc_tp_combined_salary', true );
						
						$salary_type_label = __( 'Commission Based', 'wc-team-payroll' );
						if ( $is_fixed ) {
							$salary_type_label = __( 'Fixed Salary', 'wc-team-payroll' );
						} elseif ( $is_combined ) {
							$salary_type_label = __( 'Combined', 'wc-team-payroll' );
						}

						// Get who added the payment
						$added_by_id = $payment['created_by'] ?? 0;
						$added_by_user = get_user_by( 'ID', $added_by_id );
						$added_by_name = $added_by_user ? $added_by_user->display_name : __( 'Unknown', 'wc-team-payroll' );

						$all_payments[] = array(
							'user_id'       => $employee->ID,
							'employee_name' => $employee_name,
							'vb_user_id'    => $vb_user_id ? $vb_user_id : '-',
							'amount'        => $payment['amount'] ?? 0,
							'date'          => date( 'M d, Y H:i', $payment_timestamp ),
							'timestamp'     => $payment_timestamp,
							'added_by'      => $added_by_name,
							'salary_type'   => $salary_type_label,
						);
					}
				}
			}
		}

		// Sort by date descending (newest first)
		usort( $all_payments, function( $a, $b ) {
			return $b['timestamp'] - $a['timestamp'];
		} );

		wp_send_json_success( array(
			'payments' => $all_payments,
		) );
	} );
}, 20 ); // Priority 20 - after WooCommerce loads (priority 10)

// ============================================================================
// ADMIN MENU - REGISTER EARLY (before plugins_loaded)
// ============================================================================

add_action( 'admin_menu', function() {
	// Main menu
	add_menu_page(
		__( 'Team Payroll', 'wc-team-payroll' ),
		__( 'Team Payroll', 'wc-team-payroll' ),
		'manage_options',
		'wc-team-payroll',
		'__return_null',
		'dashicons-money-alt',
		25
	);

	// Dashboard submenu
	add_submenu_page(
		'wc-team-payroll',
		__( 'Dashboard', 'wc-team-payroll' ),
		__( 'Dashboard', 'wc-team-payroll' ),
		'manage_options',
		'wc-team-payroll',
		function() {
			if ( class_exists( 'WC_Team_Payroll_Dashboard' ) ) {
				$dashboard = new WC_Team_Payroll_Dashboard();
				$dashboard->render_dashboard();
			} else {
				echo '<div class="wrap"><h1>Dashboard</h1>';
				echo '<div class="notice notice-error"><p>Plugin not fully loaded.</p></div>';
				echo '</div>';
			}
		}
	);

	// Payroll submenu
	add_submenu_page(
		'wc-team-payroll',
		__( 'Payroll', 'wc-team-payroll' ),
		__( 'Payroll', 'wc-team-payroll' ),
		'manage_options',
		'wc-team-payroll-details',
		function() {
			if ( class_exists( 'WC_Team_Payroll_Page' ) ) {
				$payroll_page = new WC_Team_Payroll_Page();
				$payroll_page->render_payroll();
			} else {
				echo '<div class="wrap"><h1>Payroll</h1>';
				echo '<div class="notice notice-error"><p>Plugin not fully loaded.</p></div>';
				echo '</div>';
			}
		}
	);

	// Team Members submenu
	add_submenu_page(
		'wc-team-payroll',
		__( 'Team Members', 'wc-team-payroll' ),
		__( 'Team Members', 'wc-team-payroll' ),
		'manage_options',
		'wc-team-payroll-employees',
		function() {
			if ( class_exists( 'WC_Team_Payroll_Employee_Management' ) ) {
				$employees = new WC_Team_Payroll_Employee_Management();
				$employees->render_employees_page();
			} else {
				echo '<div class="wrap"><h1>Team Members</h1>';
				echo '<div class="notice notice-error"><p>Plugin not fully loaded.</p></div>';
				echo '</div>';
			}
		}
	);

	// Settings submenu
	add_submenu_page(
		'wc-team-payroll',
		__( 'Settings', 'wc-team-payroll' ),
		__( 'Settings', 'wc-team-payroll' ),
		'manage_options',
		'wc-team-payroll-settings',
		function() {
			if ( class_exists( 'WC_Team_Payroll_Settings' ) ) {
				$settings = new WC_Team_Payroll_Settings();
				$settings->render_settings_page();
			} else {
				echo '<div class="wrap"><h1>Settings</h1>';
				echo '<div class="notice notice-error"><p>Plugin not fully loaded.</p></div>';
				echo '</div>';
			}
		}
	);

	// Employee Detail (hidden submenu)
	add_submenu_page(
		'wc-team-payroll',
		__( 'Employee Detail', 'wc-team-payroll' ),
		'',
		'manage_options',
		'wc-team-payroll-employee-detail',
		function() {
			if ( class_exists( 'WC_Team_Payroll_Employee_Detail' ) ) {
				$detail = new WC_Team_Payroll_Employee_Detail();
				$detail->render_employee_detail();
			} else {
				echo '<div class="wrap"><h1>Employee Detail</h1>';
				echo '<div class="notice notice-error"><p>Plugin not fully loaded.</p></div>';
				echo '</div>';
			}
		}
	);
}, 10 );

// ============================================================================
// ENQUEUE ADMIN SCRIPTS AND STYLES
// ============================================================================

add_action( 'admin_enqueue_scripts', function( $hook ) {
	// Enqueue global toast system on all Team Payroll pages
	if ( strpos( $hook, 'wc-team-payroll' ) !== false ) {
		wp_enqueue_script( 'wc-team-payroll-toast', WC_TEAM_PAYROLL_URL . 'assets/js/wc-tp-toast.js', array( 'jquery' ), WC_TEAM_PAYROLL_VERSION, true );
		wp_enqueue_script( 'wc-team-payroll-delete-modal', WC_TEAM_PAYROLL_URL . 'assets/js/wc-tp-delete-modal.js', array( 'jquery' ), WC_TEAM_PAYROLL_VERSION, true );
	}

	if ( strpos( $hook, 'wc-team-payroll' ) === false ) {
		return;
	}

	wp_enqueue_script( 'jquery-datatables', 'https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js', array( 'jquery' ), '1.13.6', true );
	wp_enqueue_style( 'jquery-datatables', 'https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css', array(), '1.13.6' );

	wp_enqueue_style( 'wc-team-payroll-dashboard', WC_TEAM_PAYROLL_URL . 'assets/css/dashboard.css', array(), WC_TEAM_PAYROLL_VERSION );
	wp_enqueue_script( 'wc-team-payroll-dashboard', WC_TEAM_PAYROLL_URL . 'assets/js/dashboard.js', array( 'jquery', 'jquery-datatables' ), WC_TEAM_PAYROLL_VERSION, true );

	// Hide WooCommerce compatibility warnings on plugin pages
	wp_add_inline_style( 'wc-team-payroll-dashboard', '
		.woocommerce-admin-notice-wrapper { display: none !important; }
		.woocommerce-admin-notice { display: none !important; }
		.notice.woocommerce-notice { display: none !important; }
	' );
} );

// ============================================================================
// PLUGIN ACTION LINKS
// ============================================================================

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), function( $links ) {
	$settings_link = '<a href="' . esc_url( admin_url( 'admin.php?page=wc-team-payroll-settings' ) ) . '">' . esc_html__( 'Settings', 'wc-team-payroll' ) . '</a>';
	$dashboard_link = '<a href="' . esc_url( admin_url( 'admin.php?page=wc-team-payroll' ) ) . '">' . esc_html__( 'Dashboard', 'wc-team-payroll' ) . '</a>';
	array_unshift( $links, $settings_link, $dashboard_link );
	return $links;
} );

// ============================================================================
// ACTIVATION AND DEACTIVATION HOOKS
// ============================================================================

register_activation_hook( __FILE__, function() {
	if ( file_exists( WC_TEAM_PAYROLL_PATH . 'includes/class-installer.php' ) ) {
		require_once WC_TEAM_PAYROLL_PATH . 'includes/class-installer.php';
		WC_Team_Payroll_Installer::install();
	}
} );

register_deactivation_hook( __FILE__, function() {
	// Cleanup if needed
} );

// Global Search AJAX Handler
add_action( 'wp_ajax_wc_tp_global_search', function() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( __( 'Unauthorized', 'wc-team-payroll' ) );
	}

	// Verify nonce
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'wc_tp_search_nonce' ) ) {
		wp_send_json_error( __( 'Security check failed', 'wc-team-payroll' ) );
	}

	$query = isset( $_POST['query'] ) ? sanitize_text_field( $_POST['query'] ) : '';

	if ( strlen( $query ) < 2 ) {
		wp_send_json_error( __( 'Query too short', 'wc-team-payroll' ) );
	}

	$results = array();

	// Search Orders
	$orders = wc_get_orders( array(
		'limit'  => 100, // Limit to prevent timeout
		'status' => array( 'wc-completed', 'wc-processing', 'wc-pending', 'wc-on-hold', 'wc-cancelled', 'wc-refunded' ),
	) );

	foreach ( $orders as $order ) {
		$order_id = $order->get_id();
		$customer_name = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
		$customer_email = $order->get_billing_email();
		$customer_phone = $order->get_billing_phone();
		$order_total = $order->get_total();
		$order_status = $order->get_status();

		// Search in order ID, customer name, email, phone, or status
		if ( stripos( (string) $order_id, $query ) !== false || 
		     stripos( $customer_name, $query ) !== false || 
		     stripos( $customer_email, $query ) !== false ||
		     stripos( $customer_phone, $query ) !== false ||
		     stripos( $order_status, $query ) !== false ) {
			
			$meta_items = array();
			if ( $order_total ) {
				$meta_items[] = 'Total: ' . wc_price( $order_total );
			}
			if ( $order_status ) {
				$meta_items[] = 'Status: ' . ucfirst( str_replace( 'wc-', '', $order_status ) );
			}
			if ( $customer_email ) {
				$meta_items[] = 'Email: ' . $customer_email;
			}
			
			$results[] = array(
				'type'  => 'order',
				'title' => 'Order #' . $order_id . ( $customer_name ? ' - ' . $customer_name : '' ),
				'meta'  => $meta_items,
				'url'   => admin_url( 'post.php?post=' . $order_id . '&action=edit' ),
			);
		}
	}

	// Search Employees
	$employees = get_users( array(
		'role__in' => array( 'shop_employee', 'shop_manager', 'administrator' ),
		'number'   => -1,
	) );

	foreach ( $employees as $employee ) {
		$vb_user_id = get_user_meta( $employee->ID, 'vb_user_id', true );
		$employee_name = $employee->display_name;
		$employee_email = $employee->user_email;
		$employee_phone = get_user_meta( $employee->ID, 'billing_phone', true );
		$first_name = $employee->first_name;
		$last_name = $employee->last_name;
		$is_fixed = get_user_meta( $employee->ID, '_wc_tp_fixed_salary', true );
		$is_combined = get_user_meta( $employee->ID, '_wc_tp_combined_salary', true );

		$employee_type = 'Commission';
		if ( $is_fixed ) {
			$employee_type = 'Fixed Salary';
		} elseif ( $is_combined ) {
			$employee_type = 'Combined';
		}

		// Search in employee name, VB user ID, email, phone, first name, last name
		if ( stripos( $employee_name, $query ) !== false || 
		     stripos( $vb_user_id, $query ) !== false || 
		     stripos( $employee_email, $query ) !== false ||
		     stripos( $employee_phone, $query ) !== false ||
		     stripos( $first_name, $query ) !== false ||
		     stripos( $last_name, $query ) !== false ||
		     stripos( (string) $employee->ID, $query ) !== false ) {
			
			$meta_items = array();
			if ( $employee_email ) {
				$meta_items[] = 'Email: ' . $employee_email;
			}
			$meta_items[] = 'Type: ' . $employee_type;
			if ( $employee_phone ) {
				$meta_items[] = 'Phone: ' . $employee_phone;
			}
			
			$results[] = array(
				'type'  => 'employee',
				'title' => $employee_name . ( $vb_user_id ? ' (' . $vb_user_id . ')' : '' ),
				'meta'  => $meta_items,
				'url'   => admin_url( 'user-edit.php?user_id=' . $employee->ID ),
			);
		}
	}

	// Search Customers (WooCommerce customers)
	$customers = get_users( array(
		'role'   => 'customer',
		'number' => 200, // Limit to prevent timeout
	) );

	foreach ( $customers as $customer ) {
		$customer_name = $customer->display_name;
		$customer_email = $customer->user_email;
		$customer_phone = get_user_meta( $customer->ID, 'billing_phone', true );
		$first_name = get_user_meta( $customer->ID, 'billing_first_name', true );
		$last_name = get_user_meta( $customer->ID, 'billing_last_name', true );
		$full_name = trim( $first_name . ' ' . $last_name );

		// Search in customer name, email, phone, first name, last name, user ID
		if ( stripos( $customer_name, $query ) !== false || 
		     stripos( $customer_email, $query ) !== false || 
		     stripos( $customer_phone, $query ) !== false ||
		     stripos( $first_name, $query ) !== false ||
		     stripos( $last_name, $query ) !== false ||
		     stripos( $full_name, $query ) !== false ||
		     stripos( (string) $customer->ID, $query ) !== false ) {
			
			$meta_items = array();
			if ( $customer_email ) {
				$meta_items[] = 'Email: ' . $customer_email;
			}
			if ( $customer_phone ) {
				$meta_items[] = 'Phone: ' . $customer_phone;
			}
			$meta_items[] = 'User ID: ' . $customer->ID;
			
			$results[] = array(
				'type'  => 'customer',
				'title' => $full_name ? $full_name : $customer_name,
				'meta'  => $meta_items,
				'url'   => admin_url( 'user-edit.php?user_id=' . $customer->ID ),
			);
		}
	}

	// Search Payments
	$all_employees_for_payments = get_users( array(
		'role__in' => array( 'shop_employee', 'shop_manager', 'administrator' ),
		'number'   => 100, // Limit to prevent timeout
	) );

	foreach ( $all_employees_for_payments as $employee ) {
		$payments = get_user_meta( $employee->ID, '_wc_tp_payments', true );
		if ( ! is_array( $payments ) ) {
			continue;
		}

		foreach ( $payments as $payment ) {
			$payment_amount = isset( $payment['amount'] ) ? $payment['amount'] : 0;
			$payment_date = isset( $payment['date'] ) ? $payment['date'] : '';
			$payment_method = isset( $payment['payment_method'] ) ? $payment['payment_method'] : '';
			$payment_note = isset( $payment['note'] ) ? $payment['note'] : '';

			// Search in payment amount, date, method, note, or employee name
			if ( stripos( (string) $payment_amount, $query ) !== false || 
			     stripos( $payment_date, $query ) !== false || 
			     stripos( $payment_method, $query ) !== false ||
			     stripos( $payment_note, $query ) !== false ||
			     stripos( $employee->display_name, $query ) !== false ) {
				
				$meta_items = array();
				if ( $payment_date ) {
					$meta_items[] = 'Date: ' . $payment_date;
				}
				if ( $payment_method ) {
					$meta_items[] = 'Method: ' . $payment_method;
				} else {
					$meta_items[] = 'Method: N/A';
				}
				if ( $payment_note ) {
					$meta_items[] = 'Note: ' . $payment_note;
				}
				
				$results[] = array(
					'type'  => 'payment',
					'title' => 'Payment: ' . wc_price( $payment_amount ) . ' - ' . $employee->display_name,
					'meta'  => $meta_items,
					'url'   => admin_url( 'admin.php?page=wc-team-payroll-employee-detail&user_id=' . $employee->ID ),
				);
			}
		}
	}

	// Remove duplicates and limit results
	$results = array_slice( array_unique( $results, SORT_REGULAR ), 0, 50 );

	if ( empty( $results ) ) {
		wp_send_json_error( __( 'No results found', 'wc-team-payroll' ) );
	}

	wp_send_json_success( array(
		'results' => $results,
	) );
} );

