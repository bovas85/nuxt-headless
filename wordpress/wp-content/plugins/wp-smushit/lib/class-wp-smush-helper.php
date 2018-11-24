<?php
/**
 *
 * @package WP_Smush
 * @subpackage Admin
 * @version 1.0
 *
 * @author Umesh Kumar <umesh@incsub.com>
 *
 * @copyright (c) 2017, Incsub (http://incsub.com)
 */


if ( ! class_exists( 'WpSmushHelper' ) ) {

	class WpSmushHelper {

		function __construct() {
			$this->init();
		}

		function init() {

		}

		/**
		 * Return unfiltered file path
		 *
		 * @param $attachment_id
		 *
		 * @return bool
		 */
		function get_attached_file( $attachment_id ) {
			if ( empty( $attachment_id ) ) {
				return false;
			}

			$file_path = get_attached_file( $attachment_id );
			if ( ! empty( $file_path ) && strpos( $file_path, 's3' ) !== false ) {
				$file_path = get_attached_file( $attachment_id, true );
			}

			return $file_path;
		}

		/**
		 * Iterate over PNG->JPG Savings to return cummulative savings for an image
		 *
		 * @param string $attachment_id
		 *
		 * @return array|bool
		 */
		function get_pngjpg_savings( $attachment_id = '' ) {
			// Initialize empty array
			$savings = array(
				'bytes'       => 0,
				'size_before' => 0,
				'size_after'  => 0,
			);

			// Return empty array if attaachment id not provided
			if ( empty( $attachment_id ) ) {
				return $savings;
			}

			$pngjpg_savings = get_post_meta( $attachment_id, WP_SMUSH_PREFIX . 'pngjpg_savings', true );
			if ( empty( $pngjpg_savings ) || ! is_array( $pngjpg_savings ) ) {
				return $savings;
			}

			foreach ( $pngjpg_savings as $size => $s_savings ) {
				if ( empty( $s_savings ) ) {
					continue;
				}
				$savings['size_before'] += $s_savings['size_before'];
				$savings['size_after']  += $s_savings['size_after'];
			}
			$savings['bytes'] = $savings['size_before'] - $savings['size_after'];

			return $savings;
		}

		/**
		 * Multiple Needles in an array
		 *
		 * @param $haystack
		 * @param $needle
		 * @param int      $offset
		 *
		 * @return bool
		 */
		function strposa( $haystack, $needle, $offset = 0 ) {
			if ( ! is_array( $needle ) ) {
				$needle = array( $needle );
			}
			foreach ( $needle as $query ) {
				if ( strpos( $haystack, $query, $offset ) !== false ) {
					return true;
				} // stop on first true result
			}

			return false;
		}


		/**
		 * Checks if file for given attachment id exists on s3, otherwise looks for local path
		 *
		 * @param $id
		 * @param $file_path
		 *
		 * @return bool
		 */
		function file_exists( $id, $file_path ) {

			// If not attachment id is given return false
			if ( empty( $id ) ) {
				return false;
			}

			// Get file path, if not provided
			if ( empty( $file_path ) ) {
				$file_path = $this->get_attached_file( $id );
			}

			global $wpsmush_s3;

			// If S3 is enabled
			if ( is_object( $wpsmush_s3 ) && method_exists( $wpsmush_s3, 'is_image_on_s3' ) && $wpsmush_s3->is_image_on_s3( $id ) ) {
				$file_exists = true;
			} else {
				$file_exists = file_exists( $file_path );
			}

			return $file_exists;
		}

		/**
		 * Add ellipsis in middle of long strings
		 *
		 * @param string $string
		 *
		 * @return string Truncated string
		 */
		function add_ellipsis( $string = '' ) {
			if ( empty( $string ) ) {
				return $string;
			}
			// Return if the character length is 120 or less, else add ellipsis in between
			if ( strlen( $string ) < 121 ) {
				return $string;
			}
			$start  = substr( $string, 0, 60 );
			$end    = substr( $string, -40 );
			$string = $start . '...' . $end;

			return $string;
		}

		/**
		 * Bump up the PHP memory limit temporarily
		 */
		function increase_memory_limit() {
			$mlimit     = ini_get( 'memory_limit' );
			$trim_limit = rtrim( $mlimit, 'M' );
			if ( $trim_limit < '256' ) {
				@ini_set( 'memory_limit', '256M' );
			}
		}

		/**
		 * Returns true if a database table column exists. Otherwise returns false.
		 *
		 * @link http://stackoverflow.com/a/5943905/2489248
		 * @global wpdb $wpdb
		 *
		 * @param string $table_name Name of table we will check for column existence.
		 * @param string $column_name Name of column we are checking for.
		 *
		 * @return boolean True if column exists. Else returns false.
		 */
		function table_column_exists( $table_name, $column_name ) {
			global $wpdb;
			$column = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s ',
					DB_NAME, $table_name, $column_name
				)
			);
			if ( ! empty( $column ) ) {
				return true;
			}
			return false;
		}

		/**
		 * Drops a specified index from a table.
		 *
		 * @since 1.0.1
		 *
		 * @global wpdb  $wpdb
		 *
		 * @param string $table Database table name.
		 * @param string $index Index name to drop.
		 * @return true True, when finished.
		 */
		function drop_index( $table, $index ) {
			global $wpdb;
			$wpdb->query( "ALTER TABLE `$table` DROP INDEX `$index`" );
			return true;
		}

		/**
		 * Sanitizes a hex color.
		 *
		 * @since 2.9  Moved from wp-smushit.php file.
		 *
		 * @param string $color  HEX color code.
		 *
		 * @return string Returns either '', a 3 or 6 digit hex color (with #), or nothing
		 */
		private function smush_sanitize_hex_color( $color ) {
			if ( '' === $color ) {
				return '';
			}

			// 3 or 6 hex digits, or the empty string.
			if ( preg_match( '|^#([A-Fa-f0-9]{3}){1,2}$|', $color ) ) {
				return $color;
			}

			return false;
		}

		/**
		 * Sanitizes a hex color without hash.
		 *
		 * @since 2.9  Moved from wp-smushit.php file.
		 *
		 * @param string $color  HEX color code with hash.
		 *
		 * @return string Returns either '', a 3 or 6 digit hex color (with #), or nothing
		 */
		public function smush_sanitize_hex_color_no_hash( $color ) {
			$color = ltrim( $color, '#' );

			if ( '' === $color ) {
				return '';
			}

			return $this->smush_sanitize_hex_color( '#' . $color ) ? $color : null;
		}

		/**
		 * Get the link to the media library page for the image.
		 *
		 * @since 2.9.0
		 *
		 * @param int    $id    Image ID.
		 * @param string $name  Image file name.
		 *
		 * @return string
		 */
		public function get_image_media_link( $id, $name ) {
			$mode = get_user_option( 'media_library_mode' );
			if ( 'grid' === $mode ) {
				$link = admin_url( "upload.php?item={$id}" );
			} else {
				$link = admin_url( "post.php?post={$id}&action=edit" );
			}

			return "<a href='{$link}'>{$name}</a>";
		}

	}

	global $wpsmush_helper;
	$wpsmush_helper = new WpSmushHelper();

}
