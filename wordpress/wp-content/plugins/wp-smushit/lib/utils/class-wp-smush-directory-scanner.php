<?php
/**
 * Smush directory smush scanner: WP_Smush_Directory_Scanner class
 *
 * @package WP_Smush
 * @subpackage Utils
 * @since 2.8.1
 *
 * @author Anton Vanyukov <anton@incsub.com>
 *
 * @copyright (c) 2018, Incsub (http://incsub.com)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WP_Smush_Directory_Scanner
 *
 * @since 2.8.1
 */
class WP_Smush_Directory_Scanner {

	/**
	 * Indicates if a scan is in process
	 *
	 * @var bool
	 */
	private $is_scanning = false;

	/**
	 * Indicates the current step being scanned
	 *
	 * @var int
	 */
	private $current_step = 0;

	/**
	 * Options names
	 */
	const IS_SCANNING_SLUG = 'wp-smush-files-scanning';
	const CURRENT_STEP     = 'wp-smush-scan-step';

	/**
	 * Refresh status variables.
	 */
	private function refresh_status() {
		$this->is_scanning  = get_transient( self::IS_SCANNING_SLUG );
		$this->current_step = (int) get_option( self::CURRENT_STEP );
	}

	/**
	 * Initializes the scan.
	 */
	public function init_scan() {
		set_transient( self::IS_SCANNING_SLUG, true, 60 * 5 ); // 5 minutes max
		update_option( self::CURRENT_STEP, 0 );
		$this->refresh_status();
	}

	/**
	 * Reset the scan as if it weren't being executed (on finish and cancel).
	 */
	public function reset_scan() {
		delete_transient( self::IS_SCANNING_SLUG );
		delete_option( self::CURRENT_STEP );
		$this->refresh_status();
	}

	/**
	 * Update the current step being scanned.
	 *
	 * @param int $step  Current scan step.
	 */
	public function update_current_step( $step ) {
		update_option( self::CURRENT_STEP, absint( $step ) );
		$this->refresh_status();
	}

	/**
	 * Get the current scan step being scanned.
	 *
	 * @return mixed
	 */
	public function get_current_scan_step() {
		$this->refresh_status();
		return $this->current_step;
	}

	/**
	 * Return the number of total steps to finish the scan.
	 *
	 * @return int
	 */
	public function get_scan_steps() {
		/* @var WP_Smush_Dir $wpsmush_dir */
		global $wpsmush_dir;

		return count( $wpsmush_dir->get_scanned_images() );
	}

	/**
	 * Check if a scanning is in process
	 *
	 * @return bool
	 */
	public function is_scanning() {
		$this->refresh_status();
		return $this->is_scanning;
	}

}
