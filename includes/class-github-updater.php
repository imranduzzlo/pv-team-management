<?php
/**
 * GitHub Plugin Updater
 * 
 * Professional GitHub updater that checks for plugin updates from GitHub releases
 * Works like WordPress.org plugins - shows updates whether plugin is active or not
 * 
 * @package WC_Team_Payroll
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Team_Payroll_GitHub_Updater {

	/**
	 * GitHub repository owner
	 * @var string
	 */
	private $github_user = 'imranduzzlo';

	/**
	 * GitHub repository name
	 * @var string
	 */
	private $github_repo = 'pv-team-payroll';

	/**
	 * Plugin slug (folder name)
	 * @var string
	 */
	private $plugin_slug = 'woocommerce-team-payroll';

	/**
	 * Plugin basename (folder/file.php)
	 * @var string
	 */
	private $plugin_basename = 'woocommerce-team-payroll/woocommerce-team-payroll.php';

	/**
	 * GitHub API URL
	 * @var string
	 */
	private $github_api_url;

	/**
	 * Plugin data
	 * @var array
	 */
	private $plugin_data = array();

	/**
	 * Constructor
	 */
	public function __construct() {
		// Build GitHub API URL
		$this->github_api_url = sprintf(
			'https://api.github.com/repos/%s/%s',
			$this->github_user,
			$this->github_repo
		);

		// Get plugin data
		$this->plugin_data = $this->get_plugin_data();

		// Hook into WordPress update system
		$this->init_hooks();
	}

	/**
	 * Initialize WordPress hooks
	 */
	private function init_hooks() {
		// Check for updates (runs on every update check)
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_for_update' ) );
		
		// Provide plugin information for update details
		add_filter( 'plugins_api', array( $this, 'get_plugin_info' ), 20, 3 );
		
		// Add "View details" link
		add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );
		
		// Clear cache when needed
		add_action( 'upgrader_process_complete', array( $this, 'clear_update_cache' ), 10, 2 );
		
		// Force check on admin init (optional - can be removed for production)
		if ( isset( $_GET['force-check'] ) && current_user_can( 'update_plugins' ) ) {
			add_action( 'admin_init', array( $this, 'force_update_check' ) );
		}
	}

	/**
	 * Get plugin data from main file
	 * 
	 * @return array Plugin data
	 */
	private function get_plugin_data() {
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugin_file = WP_PLUGIN_DIR . '/' . $this->plugin_basename;
		
		if ( ! file_exists( $plugin_file ) ) {
			return array();
		}

		return get_plugin_data( $plugin_file, false, false );
	}

	/**
	 * Check for plugin updates
	 * 
	 * @param object $transient Update transient
	 * @return object Modified transient
	 */
	public function check_for_update( $transient ) {
		// If no checked plugins, return early
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		// Get current version
		$current_version = isset( $this->plugin_data['Version'] ) ? $this->plugin_data['Version'] : '0.0.0';

		// Get latest release from GitHub
		$latest_release = $this->get_latest_release();

		// If no release found, return transient unchanged
		if ( ! $latest_release ) {
			return $transient;
		}

		// Compare versions
		$latest_version = $this->normalize_version( $latest_release->version );
		$current_version = $this->normalize_version( $current_version );

		// Debug log (only if WP_DEBUG is enabled)
		$this->log( sprintf(
			'Update Check: Current=%s, Latest=%s, Update Available=%s',
			$current_version,
			$latest_version,
			version_compare( $latest_version, $current_version, '>' ) ? 'YES' : 'NO'
		) );

		// If newer version available, add to update transient
		if ( version_compare( $latest_version, $current_version, '>' ) ) {
			$transient->response[ $this->plugin_basename ] = (object) array(
				'slug'          => $this->plugin_slug,
				'plugin'        => $this->plugin_basename,
				'new_version'   => $latest_version,
				'url'           => $latest_release->html_url,
				'package'       => $latest_release->download_url,
				'icons'         => array(),
				'banners'       => array(),
				'banners_rtl'   => array(),
				'tested'        => '6.7',
				'requires_php'  => '7.2',
				'compatibility' => new stdClass(),
			);
		} else {
			// Remove from response if no update available
			if ( isset( $transient->response[ $this->plugin_basename ] ) ) {
				unset( $transient->response[ $this->plugin_basename ] );
			}
		}

		return $transient;
	}

	/**
	 * Get plugin information for the update modal
	 * 
	 * @param false|object|array $result The result object or array
	 * @param string $action The type of information being requested
	 * @param object $args Plugin API arguments
	 * @return false|object Modified result
	 */
	public function get_plugin_info( $result, $action, $args ) {
		// Only handle plugin_information requests
		if ( $action !== 'plugin_information' ) {
			return $result;
		}

		// Only handle our plugin
		if ( ! isset( $args->slug ) || $args->slug !== $this->plugin_slug ) {
			return $result;
		}

		// Get latest release
		$latest_release = $this->get_latest_release();

		if ( ! $latest_release ) {
			return $result;
		}

		// Build plugin info object
		$plugin_info = new stdClass();
		$plugin_info->name = $this->plugin_data['Name'];
		$plugin_info->slug = $this->plugin_slug;
		$plugin_info->version = $latest_release->version;
		$plugin_info->author = $this->plugin_data['Author'];
		$plugin_info->author_profile = $this->plugin_data['AuthorURI'];
		$plugin_info->requires = '5.0';
		$plugin_info->tested = '6.7';
		$plugin_info->requires_php = '7.2';
		$plugin_info->download_link = $latest_release->download_url;
		$plugin_info->trunk = $latest_release->download_url;
		$plugin_info->last_updated = $latest_release->published_at;
		$plugin_info->homepage = $this->plugin_data['PluginURI'];
		
		// Sections
		$plugin_info->sections = array(
			'description' => $this->plugin_data['Description'],
			'changelog'   => $this->get_changelog_html( $latest_release ),
		);

		// Additional info
		$plugin_info->banners = array();
		$plugin_info->icons = array();

		return $plugin_info;
	}

	/**
	 * Get latest release from GitHub
	 * 
	 * @return object|false Release object or false on failure
	 */
	private function get_latest_release() {
		// Check cache first
		$cache_key = 'wc_tp_github_release_' . md5( $this->github_repo );
		$cached = get_transient( $cache_key );

		if ( false !== $cached ) {
			return $cached;
		}

		// Fetch from GitHub API
		$api_url = $this->github_api_url . '/releases/latest';
		
		$response = wp_remote_get( $api_url, array(
			'timeout' => 10,
			'headers' => array(
				'Accept'     => 'application/vnd.github.v3+json',
				'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . get_bloginfo( 'url' ),
			),
		) );

		// Handle errors
		if ( is_wp_error( $response ) ) {
			$this->log( 'GitHub API Error: ' . $response->get_error_message() );
			return false;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		
		if ( $response_code !== 200 ) {
			$this->log( sprintf( 'GitHub API returned HTTP %d', $response_code ) );
			return false;
		}

		// Parse response
		$body = wp_remote_retrieve_body( $response );
		$release_data = json_decode( $body );

		if ( ! $release_data || ! isset( $release_data->tag_name ) ) {
			$this->log( 'Invalid GitHub API response - no tag_name found' );
			return false;
		}

		// Build release object
		$release = new stdClass();
		$release->version = $this->normalize_version( $release_data->tag_name );
		$release->html_url = $release_data->html_url;
		$release->download_url = $release_data->zipball_url;
		$release->published_at = $release_data->published_at;
		$release->body = isset( $release_data->body ) ? $release_data->body : '';
		$release->name = isset( $release_data->name ) ? $release_data->name : $release_data->tag_name;

		// Cache for 6 hours
		set_transient( $cache_key, $release, 6 * HOUR_IN_SECONDS );

		return $release;
	}

	/**
	 * Get changelog HTML from release notes
	 * 
	 * @param object $release Release object
	 * @return string Changelog HTML
	 */
	private function get_changelog_html( $release ) {
		if ( empty( $release->body ) ) {
			return '<p>No changelog available.</p>';
		}

		// Convert markdown to HTML (basic conversion)
		$changelog = $release->body;
		
		// Convert headers
		$changelog = preg_replace( '/^### (.+)$/m', '<h3>$1</h3>', $changelog );
		$changelog = preg_replace( '/^## (.+)$/m', '<h2>$1</h2>', $changelog );
		$changelog = preg_replace( '/^# (.+)$/m', '<h1>$1</h1>', $changelog );
		
		// Convert lists
		$changelog = preg_replace( '/^\* (.+)$/m', '<li>$1</li>', $changelog );
		$changelog = preg_replace( '/^- (.+)$/m', '<li>$1</li>', $changelog );
		
		// Wrap lists in ul tags
		$changelog = preg_replace( '/(<li>.*<\/li>)/s', '<ul>$1</ul>', $changelog );
		
		// Convert line breaks to paragraphs
		$changelog = wpautop( $changelog );

		return $changelog;
	}

	/**
	 * Normalize version string
	 * Removes 'v' prefix and ensures proper format
	 * 
	 * @param string $version Version string
	 * @return string Normalized version
	 */
	private function normalize_version( $version ) {
		// Remove 'v' prefix
		$version = ltrim( trim( $version ), 'v' );
		
		// Ensure valid version format
		if ( ! preg_match( '/^\d+\.\d+\.\d+/', $version ) ) {
			// If only X.Y, add .0
			if ( preg_match( '/^\d+\.\d+$/', $version ) ) {
				$version .= '.0';
			}
		}

		return $version;
	}

	/**
	 * Add plugin row meta links
	 * 
	 * @param array $links Plugin row meta
	 * @param string $file Plugin basename
	 * @return array Modified links
	 */
	public function plugin_row_meta( $links, $file ) {
		if ( $file === $this->plugin_basename ) {
			$links[] = sprintf(
				'<a href="%s" target="_blank">%s</a>',
				'https://github.com/' . $this->github_user . '/' . $this->github_repo,
				__( 'View on GitHub', 'wc-team-payroll' )
			);
		}

		return $links;
	}

	/**
	 * Clear update cache after plugin update
	 * 
	 * @param WP_Upgrader $upgrader Upgrader instance
	 * @param array $options Update options
	 */
	public function clear_update_cache( $upgrader, $options ) {
		if ( $options['action'] === 'update' && $options['type'] === 'plugin' ) {
			$cache_key = 'wc_tp_github_release_' . md5( $this->github_repo );
			delete_transient( $cache_key );
		}
	}

	/**
	 * Force update check (for debugging)
	 */
	public function force_update_check() {
		// Clear cache
		$cache_key = 'wc_tp_github_release_' . md5( $this->github_repo );
		delete_transient( $cache_key );
		delete_site_transient( 'update_plugins' );
		
		// Trigger update check
		wp_update_plugins();
		
		// Redirect to plugins page
		wp_redirect( admin_url( 'plugins.php' ) );
		exit;
	}

	/**
	 * Log debug messages (only if WP_DEBUG is enabled)
	 * 
	 * @param string $message Log message
	 */
	private function log( $message ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[WC Team Payroll Updater] ' . $message );
		}
	}
}

// Initialize the updater
function wc_team_payroll_init_github_updater() {
	new WC_Team_Payroll_GitHub_Updater();
}

// Hook into plugins_loaded with priority 5 (early)
add_action( 'plugins_loaded', 'wc_team_payroll_init_github_updater', 5 );
