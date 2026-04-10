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
	}

	/**
	 * Check for plugin updates from GitHub
	 */
	public function check_for_update( $transient ) {
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		$current_version = isset( $transient->checked[ $this->plugin_file ] ) ? $transient->checked[ $this->plugin_file ] : '0';

		// Get latest release from GitHub
		$latest_release = $this->get_latest_release();

		if ( $latest_release && version_compare( $latest_release['version'], $current_version, '>' ) ) {
			$transient->response[ $this->plugin_file ] = (object) array(
				'id'          => $this->github_repo,
				'slug'        => $this->plugin_slug,
				'plugin'      => $this->plugin_file,
				'new_version' => $latest_release['version'],
				'url'         => $latest_release['url'],
				'package'     => $latest_release['download_url'],
				'tested'      => '6.4',
				'requires'    => '5.0',
				'requires_php' => '7.2',
				'icons'       => array(),
				'banners'     => array(),
			);
		}

		return $transient;
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

		// Cache for 12 hours
		set_transient( $transient_key, $result, 12 * HOUR_IN_SECONDS );

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

// Initialize updater
new WC_Team_Payroll_GitHub_Updater();
