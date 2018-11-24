<?php
/**
 * Smush integration with various plugins: WP_Smush_Common class
 *
 * @package WP_Smush
 * @subpackage Admin
 * @since 2.8.0
 *
 * @author Anton Vanyukov <anton@incsub.com>
 *
 * @copyright (c) 2018, Incsub (http://incsub.com)
 */

/**
 * Singleton class WP_Smush_Common.
 *
 * @since 2.8.0
 */
class WP_Smush_Common {
	/**
	 * Class instance variable.
	 *
	 * @since 2.8.0
	 * @var null|WP_Smush_Common
	 */
	private static $_instance = null;

	/**
	 * WP_Smush_Common constructor.
	 */
	private function __construct() {
		// AJAX Thumbnail Rebuild integration.
		add_filter( 'wp_smush_media_image', array( $this, 'skip_images' ), 10, 2 );
	}

	/**
	 * Get class instance.
	 *
	 * @since 2.8.0
	 *
	 * @return null|WP_Smush_Common
	 */
	public static function get_instance() {
		if ( null !== self::$_instance ) {
			return self::$_instance;
		}

		return new self();
	}

	/**
	 * AJAX Thumbnail Rebuild integration.
	 *
	 * If this is a thumbnail regeneration - only continue for selected thumbs
	 * (no need to regenerate everything else).
	 *
	 * @since 2.8.0
	 *
	 * @param string $smush_image  Image size.
	 * @param string $size_key     Thumbnail size.
	 *
	 * @return bool
	 */
	function skip_images( $smush_image, $size_key ) {
		if ( empty( $_POST['regen'] ) || ! is_array( $_POST['regen'] ) ) { // Input var ok.
			return $smush_image;
		}

		$smush_sizes = wp_unslash( $_POST['regen'] ); // Input var ok.

		if ( in_array( $size_key, $smush_sizes, true ) ) {
			return $smush_image;
		}

		// Do not regenrate other thumbnails for regenerate action.
		return false;
	}

}
