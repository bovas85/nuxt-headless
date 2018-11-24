<?php
/**
 * Smush class for storing all Ajax related functionality: WP_Smush_Ajax class
 *
 * @package WP_Smush
 * @subpackage Admin
 * @since 2.9.0
 *
 * @copyright (c) 2018, Incsub (http://incsub.com)
 */

/**
 * Class WP_Smush_Ajax for storing all Ajax related functionality.
 *
 * @since 2.9.0
 */
class WP_Smush_Ajax {

	/**
	 * WP_Smush_Ajax constructor.
	 */
	public function __construct() {
		// Handle Ajax request for directory smush stats (stats meta box).
		add_action( 'wp_ajax_get_dir_smush_stats', array( $this, 'get_dir_smush_stats' ) );
	}

	/**
	 * Returns Directory Smush stats and Cumulative stats
	 */
	public function get_dir_smush_stats() {
		/**
		 * WP_Smush_Dir global.
		 *
		 * @var WP_Smush_Dir $wpsmush_dir
		 */
		global $wpsmush_dir;

		$result = array();

		// Store the Total/Smushed count.
		$stats = $wpsmush_dir->total_stats();

		$result['dir_smush'] = $stats;

		// Cumulative Stats.
		$result['combined_stats'] = $wpsmush_dir->combined_stats( $stats );

		// Store the stats in options table.
		update_option( 'dir_smush_stats', $result, false );

		// Send ajax response.
		wp_send_json_success( $result );
	}

}
