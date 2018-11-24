<?php

/**
 *
 * @package WP_Smush
 * @subpackage Migrate
 * @version 1.0
 *
 * @author Umesh Kumar <umesh@incsub.com>
 * @author Sam Najian <sam@incsub.com>
 *
 * @copyright (c) 2016, Incsub (http://incsub.com)
 */

class WpSmushMigrate {

	/**
	 * Returns percent saved from the api call response
	 *
	 * @param string $string
	 *
	 * @return float
	 */
	private function _get_saved_percentage( $string ) {
		if ( preg_match( '/\d+(\.\d+)?%/', $string, $matches ) ) {
			return isset( $matches[0] ) ? (float) str_replace( '%', '', $matches[0] ) : -1;
		}

		return -1;
	}

	/**
	 * Returns bytes saved from the api call response
	 *
	 * @param string $string
	 *
	 * @return float
	 */
	private function _get_saved_bytes( $string ) {
		if ( preg_match( '/\((.*)\)/', $string, $matches ) ) {
			$size = isset( $matches[1] ) ? $matches[1] : false;
			if ( $size ) {
				$size_array = explode( '&nbsp;', $size );

				if ( ! isset( $size_array[0] ) || ! isset( $size_array[1] ) ) {
					return -1;
				}

				$unit  = strtoupper( $size_array[1] );
				$sizes = array(
					'B'  => '1',
					'KB' => 1024,
					'MB' => 1048576,
				);
				return (float) $size_array[0] * $sizes[ $unit ];
			}
		}
		return -1;
	}


	/**
	 * Migrates smushit message structure
	 *
	 * @param array $message
	 *
	 * @return array
	 */
	public function migrate_api_message( array $message ) {
		if ( ! isset( $message['wp_smushit'] ) ) {
			return array();
		}

		$new_message = array(
			'stats' => array(
				'size_before' => -1,
				'size_after'  => -1,
				'percent'     => -1,
				'time'        => -1,
				'api_version' => -1,
				'lossy'       => -1,
			),
			'sizes' => array(),
		);

		if ( isset( $message['sizes'] ) ) {
			foreach ( $message['sizes'] as $key => $size ) {
				if ( isset( $size['wp_smushit'] ) ) {
					$new_size = new stdClass();

					$new_size->compression = $this->_get_saved_percentage( $size['wp_smushit'] );
					$new_size->bytes_saved = $this->_get_saved_bytes( $size['wp_smushit'] );
					$new_size->before_size = -1;
					$new_size->after_size  = -1;
					$new_size->time        = -1;

					if ( $new_size->compression !== -1 && $new_size->bytes_saved !== -1 ) {
						$new_size->before_size = ( $new_size->bytes_saved * 100 ) / $new_size->compression;
						$new_size->after_size  = ( 100 - $new_size->compression ) * $new_size->before_size / 100;
					}

					$new_message['sizes'][ $key ] = $new_size;
				}
			}
		}

		$new_message['stats']['percent'] = $this->_get_saved_percentage( $message['wp_smushit'] );
		$new_message['stats']['bytes']   = $this->_get_saved_bytes( $message['wp_smushit'] );

		if ( $new_message['stats']['percent'] !== -1 && $new_message['stats']['bytes'] !== -1 ) {
			$new_message['stats']['size_before'] = ( $new_message['stats']['bytes'] * 100 ) / $new_message['stats']['percent'];
			$new_message['stats']['size_after']  = ( 100 - $new_message['stats']['percent'] ) * $new_message['stats']['size_before'] / 100;
		}

		return $new_message;
	}
}
