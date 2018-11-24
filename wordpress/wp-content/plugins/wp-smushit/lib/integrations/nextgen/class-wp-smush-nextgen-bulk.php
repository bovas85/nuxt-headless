<?php
if ( ! class_exists( 'WPSmushNextGenBulk' ) ) {

	/**
	 * Class WPSmushNextGenBulk
	 */
	class WPSmushNextGenBulk extends WpSmushNextGen {

		/**
		 * WPSmushNextGenBulk constructor.
		 */
		public function __construct() {
			add_action( 'wp_ajax_wp_smushit_nextgen_bulk', array( $this, 'smush_bulk' ) );
		}

		/**
		 * Bulk Smush for Nextgen.
		 */
		public function smush_bulk() {
			global $wpsmushnextgenstats, $wpsmushit_admin, $wpsmushnextgenadmin;

			$stats = array();

			if ( empty( $_GET['attachment_id'] ) ) {
				wp_send_json_error(
					array(
						'error'         => 'missing_id',
						'error_message' => esc_html__( 'No attachment ID was received', 'wp-smushit' ),
						'file_name'     => 'undefined',
					)
				);
			}

			$atchmnt_id = (int) $_GET['attachment_id'];

			$smush = $this->smush_image( $atchmnt_id, '', false, true );

			if ( is_wp_error( $smush ) ) {
				$error_message = $smush->get_error_message();

				// Check for timeout error and suggest to filter timeout.
				if ( strpos( $error_message, 'timed out' ) ) {
					$error         = 'timeout';
					$error_message = esc_html__( 'Smush request timed out. You can try setting a higher value ( > 60 ) for `WP_SMUSH_API_TIMEOUT`.', 'wp-smushit' );
				}

				$error     = isset( $error ) ? $error : 'other';
				$file_name = $this->get_nextgen_image_from_id( $atchmnt_id );

				wp_send_json_error(
					array(
						'error'         => $error,
						'stats'         => $stats,
						'error_message' => $error_message,
						'file_name'     => isset( $file_name->filename ) ? $file_name->filename : 'undefined',
					)
				);
			}

			// Check if a re-Smush request, update the re-Smush list.
			if ( ! empty( $_REQUEST['is_bulk_resmush'] ) && $_REQUEST['is_bulk_resmush'] ) {
				$wpsmushit_admin->update_resmush_list( $atchmnt_id, 'wp-smush-nextgen-resmush-list' );
			}
			$stats['is_lossy'] = ! empty( $smush['stats'] ) ? $smush['stats']['lossy'] : 0;

			// Size before and after smush.
			$stats['size_before'] = ! empty( $smush['stats'] ) ? $smush['stats']['size_before'] : 0;
			$stats['size_after']  = ! empty( $smush['stats'] ) ? $smush['stats']['size_after'] : 0;

			// Get the re-Smush IDs list.
			if ( empty( $wpsmushnextgenadmin->resmush_ids ) ) {
				$wpsmushnextgenadmin->resmush_ids = get_option( 'wp-smush-nextgen-resmush-list' );
			}

			$wpsmushnextgenadmin->resmush_ids = empty( $wpsmushnextgenadmin->resmush_ids ) ? get_option( 'wp-smush-nextgen-resmush-list' ) : array();
			$resmush_count                    = ! empty( $wpsmushnextgenadmin->resmush_ids ) ? count( $wpsmushnextgenadmin->resmush_ids ) : 0;
			$smushed_images                   = $wpsmushnextgenstats->get_ngg_images( 'smushed' );

			// Remove re-Smush IDs from smushed images list.
			if ( $resmush_count > 0 && is_array( $wpsmushnextgenadmin->resmush_ids ) ) {
				foreach ( $smushed_images as $image_k => $image ) {
					if ( in_array( $image_k, $wpsmushnextgenadmin->resmush_ids, true ) ) {
						unset( $smushed_images[ $image_k ] );
					}
				}
			}

			// Get the image count and smushed images count.
			$image_count   = ! empty( $smush ) && ! empty( $smush['sizes'] ) ? count( $smush['sizes'] ) : 0;
			$smushed_count = is_array( $smushed_images ) ? count( $smushed_images ) : 0;

			$stats['smushed'] = ! empty( $wpsmushnextgenadmin->resmush_ids ) ? $smushed_count - $resmush_count : $smushed_count;
			$stats['count']   = $image_count;

			wp_send_json_success( array(
				'stats' => $stats,
			) );
		}

	}

}
