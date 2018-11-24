<?php
/**
 * Smush integration with Gutenberg editor: WP_Smush_Gutenberg class
 *
 * @package WP_Smush
 * @subpackage Admin/Integrations
 * @since 2.8.1
 *
 * @author Anton Vanyukov <anton@incsub.com>
 *
 * @copyright (c) 2018, Incsub (http://incsub.com)
 */

/**
 * Class WP_Smush_Gutenberg for Gutenberg integration.
 *
 * @since 2.8.1
 */
class WP_Smush_Gutenberg {

	/**
	 * Module slug.
	 *
	 * @since 2.8.1
	 *
	 * @var string $module
	 */
	private $module = 'gutenberg';

	/**
	 * WP_Smush_Gutenberg constructor.
	 *
	 * @since 2.8.1
	 */
	function __construct() {
		// Filters the setting variable to add Gutenberg setting title and description.
		add_filter( 'wp_smush_settings', array( $this, 'register_setting' ), 6 );

		// Filters the setting variable to add Nextgen to the Integration tab.
		add_filter( 'wp_smush_integration_settings', array( $this, 'add_setting' ), 1 );

		// Disable setting.
		add_filter( 'wp_smush_integration_status_' . $this->module, array( $this, 'setting_status' ), 10, 2 );

		// Hook at the end of setting row to output an error div.
		add_action( 'smush_setting_column_right_inside', array( $this, 'integration_error' ) );

		// Register gutenberg block assets.
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_gb' ) );

		// Show submit button when Gutenberg is active.
		add_filter( 'wp_smush_integration_show_submit', array( $this, 'show_submit' ) );
	}

	/**
	 * Filters the setting variable to add Gutenberg setting title and description.
	 *
	 * @since 2.8.1
	 *
	 * @param array $settings  Settings array.
	 *
	 * @return mixed
	 */
	public function register_setting( $settings ) {
		$settings[ $this->module ] = array(
			'label'       => esc_html__( 'Show Smush stats in Gutenberg blocks', 'wp-smushit' ),
			'short_label' => esc_html__( 'Gutenberg Support', 'wp-smushit' ),
			'desc'        => esc_html__(
				'Add statistics and the manual smush button to Gutenberg blocks that
							display images.', 'wp-smushit'
			),
		);

		return $settings;
	}

	/**
	 * Adds the setting to the intgration_group array in the WpSmushBulkUi class.
	 *
	 * @used-by wp_smush_integration_settings filter
	 *
	 * @param array $settings  Settings array.
	 *
	 * @return array
	 */
	public function add_setting( $settings ) {
		$settings[] = $this->module;

		return $settings;
	}

	/**
	 * Prints the message for Gutenberg setup.
	 *
	 * @since 2.8.1
	 *
	 * @param string $setting_key  Settings key.
	 *
	 * @return null
	 */
	public function integration_error( $setting_key ) {
		// Return if not Gutenberg integration.
		if ( $this->module !== $setting_key ) {
			return;
		}

		// If Gutenberg is active, do not continue.
		if ( $this->is_gutenberg_active() ) {
			return;
		}
		?>
		<div class="sui-notice smush-notice-sm">
			<p><?php esc_html_e( 'To use this feature you need to install and activate the Gutenberg plugin.', 'wp-smushit' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Update setting status - disable it if Gutenberg is not active.
	 *
	 * @since 2.8.1
	 *
	 * @param bool $disabled  Setting status.
	 *
	 * @return bool
	 */
	public function setting_status( $disabled ) {
		if ( ! $this->is_gutenberg_active() ) {
			$disabled = true;
		}

		return $disabled;
	}

	/**
	 * Show submit button for integration settings.
	 *
	 * If Gutenberg plugin is active we will enable integration,
	 * so show submit button if Gutenberg is active.
	 *
	 * @param bool $show Should show?.
	 *
	 * @since 2.8.1
	 *
	 * @return bool
	 */
	public function show_submit( $show ) {
		if ( $this->is_gutenberg_active() ) {
			$show = true;
		}

		return $show;
	}

	/**
	 * Check if Gutenberg is active.
	 *
	 * @since 2.8.1
	 *
	 * @return bool
	 */
	private function is_gutenberg_active() {
		return is_plugin_active( 'gutenberg/gutenberg.php' );
	}

	/**
	 * Enqueue Gutenberg block assets for backend editor.
	 *
	 * `wp-blocks`: includes block type registration and related functions.
	 * `wp-element`: includes the WordPress Element abstraction for describing the structure of your blocks.
	 * `wp-i18n`: To internationalize the block's text.
	 *
	 * @since 2.8.1
	 */
	public function enqueue_gb() {
		/* @var WpSmushSettings $wpsmush_settings */
		global $wpsmush_settings;

		$enabled = $wpsmush_settings->settings[ $this->module ];

		if ( ! $enabled || ! $this->is_gutenberg_active() ) {
			return;
		}

		// Gutenberg block scripts.
		wp_enqueue_script(
			'smush-gutenberg',
			WP_SMUSH_URL . 'assets/js/blocks.min.js',
			array( 'wp-blocks', 'wp-i18n', 'wp-element' ),
			WP_SMUSH_VERSION,
			true
		);
	}

}
