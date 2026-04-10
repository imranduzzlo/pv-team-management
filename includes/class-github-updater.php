<?php
/**
 * GitHub Plugin Updater
 * Enables automatic updates from GitHub repository
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Team_Payroll_GitHub_Updater {

	private $plugin_slug;
	private $plugin_file;
	private $github_repo;
	private $github_user;
	private $github_branch;
	private $github_api_url;

	public function __construct() {
		$this->plugin_slug = 'woocommerce-team-payroll';
		$this->plugin_file = 'woocommerce-team-payroll/woocommerce-team-payroll.php';
		$this->github_user = 'imranduzzlo';
		$this->github_repo = 'pv-team-payroll';
		$this->github_branch = 'main';
		$this->github_api_url = "https://api.github.com/repos/{$this->github_user}/{$this->github_repo}";

		// Hook into WordPress update checks
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_for_update' ) );
		add_filter( 'plugins_api', array( $this, 'plugin_info' ), 10, 3 );
		
		// Clear cache on plugin activation
		add_action( 'activated_plugin', array( $this, 'clear_cache' ) );
		
		// Force check on admin page load
		add_action( 'admin_init', array( $this, 'force_check' ) );
	}

	/**
	 * Clear update cache
	 */
	public function clear_cache() {
		delete_transient( 'wc_tp_github_release' );
		delete_transient( 'update_plugins' );
	}

	/**
	 * Force update check on admin init
	 */
	public function force_check() {
		// Only run once per hour
		$last_check = get_transient( 'wc_tp_last_update_check' );
		if ( $last_check ) {
			return;
		}

		// Clear cache to force fresh check
		delete_transient( 'wc_tp_github_release' );
		
		// Mark that we checked
		set_transient( 'wc_tp_last_update_check', 1, HOUR_IN_SECONDS );
		
		// Trigger WordPress update check
		wp_update_plugins();
	}

	/**
	 * Check for plugin updates from GitHub
	 */
	public function check_for_update( $transient ) {
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		// Initialize response if not set
		if ( ! isset( $transient->response ) ) {
			$transient->response = array();
		}

		$current_version = $this->get_current_version();

		// Get latest release from GitHub
		$latest_release = $this->get_latest_release();

		if ( ! $latest_release ) {
			// Remove from response if no release found
			unset( $transient->response[ $this->plugin_file ] );
			return $transient;
		}

		// Normalize versions for comparison
		$latest_version = $this->normalize_version( $latest_release['version'] );
		$current_version_normalized = $this->normalize_version( $current_version );

		// Only add to response if there's a newer version
		if ( version_compare( $latest_version, $current_version_normalized, '>' ) ) {
			$transient->response[ $this->plugin_file ] = (object) array(
				'id'          => $this->github_repo,
				'slug'        => $this->plugin_slug,
				'plugin'      => $this->plugin_file,
				'new_version' => $latest_version,
				'url'         => $latest_release['url'],
				'package'     => $latest_release['download_url'],
				'tested'      => '6.4',
				'requires'    => '5.0',
				'requires_php' => '7.2',
				'icons'       => array(),
				'banners'     => array(),
			);
		} else {
			// Explicitly remove from response if versions are equal or current is newer
			unset( $transient->response[ $this->plugin_file ] );
		}

		return $transient;
	}

	/**
	 * Get current plugin version
	 */
	private function get_current_version() {
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $this->plugin_file );
		return isset( $plugin_data['Version'] ) ? $plugin_data['Version'] : '0';
	}

	/**
	 * Normalize version string
	 */
	private function normalize_version( $version ) {
		// Remove 'v' prefix if present
		$version = ltrim( $version, 'v' );
		// Ensure it's a valid version format
		if ( ! preg_match( '/^\d+\.\d+\.\d+/', $version ) ) {
			$version = '0.0.0';
		}
		return $version;
	}

	/**
	 * Get plugin info for the update modal
	 */
	public function plugin_info( $result, $action, $args ) {
		if ( $action !== 'plugin_information' ) {
			return $result;
		}

		if ( $args->slug !== $this->plugin_slug ) {
			return $result;
		}

		$latest_release = $this->get_latest_release();

		if ( ! $latest_release ) {
			return $result;
		}

		$result = (object) array(
			'name'            => 'WooCommerce Team Payroll & Commission System',
			'slug'            => $this->plugin_slug,
			'version'         => $latest_release['version'],
			'author'          => 'Imran',
			'author_profile'  => 'https://imranhossain.me/',
			'download_link'   => $latest_release['download_url'],
			'trunk'           => $latest_release['download_url'],
			'requires'        => '5.0',
			'requires_php'    => '7.2',
			'tested'          => '6.4',
			'last_updated'    => $latest_release['updated'],
			'sections'        => array(
				'description' => 'Manage team-based commission and payroll system with agents and processors',
				'changelog'   => $this->get_changelog(),
			),
			'banners'         => array(),
			'icons'           => array(),
		);

		return $result;
	}

	/**
	 * Get latest release from GitHub
	 */
	private function get_latest_release() {
		$transient_key = 'wc_tp_github_release';
		$cached = get_transient( $transient_key );

		if ( $cached !== false ) {
			return $cached;
		}

		$response = wp_remote_get(
			"{$this->github_api_url}/releases/latest",
			array(
				'timeout'   => 10,
				'sslverify' => true,
				'headers'   => array(
					'Accept' => 'application/vnd.github.v3+json',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$body = wp_remote_retrieve_body( $response );
		$release = json_decode( $body, true );

		if ( ! isset( $release['tag_name'] ) ) {
			return false;
		}

		$version = ltrim( $release['tag_name'], 'v' );
		$download_url = "{$this->github_api_url}/zipball/{$release['tag_name']}";

		$result = array(
			'version'      => $version,
			'url'          => $release['html_url'],
			'download_url' => $download_url,
			'updated'      => $release['published_at'],
		);

		// Cache for 1 hour (shorter cache for faster updates)
		set_transient( $transient_key, $result, 1 * HOUR_IN_SECONDS );

		return $result;
	}

	/**
	 * Get changelog from GitHub releases
	 */
	private function get_changelog() {
		$response = wp_remote_get(
			"{$this->github_api_url}/releases",
			array(
				'timeout'   => 10,
				'sslverify' => true,
			)
		);

		if ( is_wp_error( $response ) ) {
			return 'Unable to fetch changelog from GitHub.';
		}

		$body = wp_remote_retrieve_body( $response );
		$releases = json_decode( $body, true );

		if ( ! is_array( $releases ) || empty( $releases ) ) {
			return 'No releases found.';
		}

		$changelog = '<ul>';
		foreach ( array_slice( $releases, 0, 5 ) as $release ) {
			$changelog .= '<li><strong>' . esc_html( $release['tag_name'] ) . '</strong> - ' . esc_html( $release['published_at'] ) . '<br/>';
			$changelog .= wp_kses_post( wpautop( $release['body'] ) ) . '</li>';
		}
		$changelog .= '</ul>';

		return $changelog;
	}
}

// Initialize updater on plugins_loaded to ensure plugin version is defined
add_action( 'plugins_loaded', function() {
	new WC_Team_Payroll_GitHub_Updater();
}, 25 );
