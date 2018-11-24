<?php
/**
 * Nextgen integration: WpSmushNextGen class
 *
 * @package WP_Smush
 * @subpackage NextGen Gallery
 * @version 1.0
 *
 * @author Umesh Kumar <umesh@incsub.com>
 *
 * @copyright (c) 2016, Incsub (http://incsub.com)
 */

if ( ! class_exists( 'WpSmushNextGen' ) ) {

	/**
	 * Class WpSmushNextGen
	 */
	class WpSmushNextGen {

		/**
		 * Module slug.
		 *
		 * @since 2.8.1
		 *
		 * @var string $module
		 */
		private $module = 'nextgen';

		/**
		 * Contains the total Stats, for displaying it on bulk page
		 *
		 * @var array $stats
		 */
		var $stats = array(
			'savings_bytes'   => 0,
			'size_before'     => 0,
			'size_after'      => 0,
			'savings_percent' => 0,
		);

		var $is_nextgen_active = false;

		/**
		 * WpSmushNextGen constructor.
		 */
		function __construct() {
			$this->init();
		}

		/**
		 * Init filters and actions.
		 */
		function init() {
			global $wp_smush, $wpsmush_settings;

			$is_pro = $wp_smush->validate_install();

			// Filters the setting variable to add Nextgen setting title and description.
			add_filter( 'wp_smush_settings', array( $this, 'register' ), 5 );

			// Filters the setting variable to add Nextgen setting in premium features.
			add_filter( 'wp_smush_integration_settings', array( $this, 'add_setting' ), 5 );

			// Disable setting.
			add_filter( 'wp_smush_integration_status_' . $this->module, array( $this, 'setting_status' ) );

			// Show submit button when Gutenberg is active.
			add_filter( 'wp_smush_integration_show_submit', array( $this, 'show_submit' ) );

			// Show alert message only if Pro user.
			if ( $is_pro ) {
				// Hook at the end of setting row to output a error div.
				add_action( 'smush_setting_column_right_inside', array( $this, 'additional_notice' ) );
			}

			// Check if integration is Enabled or not.
			if ( ! empty( $wpsmush_settings->settings ) && $is_pro ) {
				$opt_nextgen_val = $wpsmush_settings->settings[ $this->module ];
			} else {
				// Smush NextGen key.
				$opt_nextgen     = WP_SMUSH_PREFIX . $this->module;
				$opt_nextgen_val = $wpsmush_settings->get_setting( $opt_nextgen, false );
			}

			// Return if not a pro user, or nextgen integration is not enabled.
			if ( ! $is_pro || ! $opt_nextgen_val ) {
				return;
			}

			// Auto Smush image, if enabled, runs after Nextgen is finished uploading the image.
			// Allows to override whether to auto smush nextgen image or not.
			$auto_smush = apply_filters( 'smush_nextgen_auto', $wp_smush->is_auto_smush_enabled() );
			if ( $auto_smush ) {
				add_action( 'ngg_added_new_image', array( &$this, 'auto_smush' ) );
			}

			// Single Smush/Manual Smush: Handles the Single/Manual smush request for Nextgen Gallery.
			add_action( 'wp_ajax_smush_manual_nextgen', array( $this, 'manual_nextgen' ) );

			// Restore Image: Handles the single/Manual restore image request for NextGen Gallery.
			add_action( 'wp_ajax_smush_restore_nextgen_image', array( $this, 'restore_image' ) );

			// Resmush Image: Handles the single/Manual resmush image request for NextGen Gallery.
			add_action( 'wp_ajax_smush_resmush_nextgen_image', array( $this, 'resmush_image' ) );
		}

		/**
		 * Show additional notice if the required plugins are not istalled.
		 *
		 * @since 2.8.0
		 *
		 * @param string $name  Setting name.
		 */
		public static function additional_notice( $name ) {
			if ( 'nextgen' === $name && ! class_exists( 'C_NextGEN_Bootstrap' ) ) { ?>
				<div class="sui-notice sui-notice-sm">
					<p>
						<?php esc_html_e( 'To use this feature you need to install and activate NextGen Gallery.', 'wp-smushit' ); ?>
					</p>
				</div>
				<?php
			}
		}

		/**
		 * Filters the setting variable to add NextGen setting title and description
		 *
		 * @param array $settings Settings.
		 *
		 * @return mixed
		 */
		function register( $settings ) {
			$settings[ $this->module ] = array(
				'label'       => esc_html__( 'Enable NextGen Gallery integration', 'wp-smushit' ),
				'short_label' => esc_html__( 'NextGen Gallery', 'wp-smushit' ),
				'desc'        => esc_html__( 'Allow smushing images directly through NextGen Gallery settings.', 'wp-smushit' ),
			);

			return $settings;
		}

		/**
		 * Append nextgen in pro feature list
		 *
		 * @param array $int_settings Integration setting keys.
		 *
		 * @return array
		 */
		function add_setting( $int_settings ) {
			if ( ! isset( $int_settings[ $this->module ] ) ) {
				$int_settings[] = $this->module;
			}

			return $int_settings;
		}

		/**
		 * Queries Nextgen table for a list of image ids
		 *
		 * @return mixed Array of ids
		 */
		function get_nextgen_attachments() {
			global $wpdb;

			// Query images from the nextgen table.
			$images = $wpdb->get_col( "SELECT pid FROM $wpdb->nggpictures ORDER BY pid ASC" );

			// Return empty array, if there was error querying the images.
			if ( empty( $images ) || is_wp_error( $images ) ) {
				$images = array();
			}

			return $images;
		}

		/**
		 * Get the NextGen Image object from attachment id
		 *
		 * @param $pid
		 *
		 * @return mixed
		 */
		function get_nextgen_image_from_id( $pid ) {

			// Registry Object for NextGen Gallery.
			$registry = C_Component_Registry::get_instance();

			// Gallery Storage Object.
			$storage = $registry->get_utility( 'I_Gallery_Storage' );

			$image = $storage->object->_image_mapper->find( $pid );

			return $image;
		}

		/**
		 * Get the NextGen attachment id from image object
		 *
		 * @param $image
		 *
		 * @return mixed
		 */
		function get_nextgen_id_from_image( $image ) {

			// Registry Object for NextGen Gallery.
			$registry = C_Component_Registry::get_instance();

			// Gallery Storage Object.
			$storage = $registry->get_utility( 'I_Gallery_Storage' );

			$pid = $storage->object->_get_image_id( $image );

			return $pid;
		}

		/**
		 * Get image mime type
		 *
		 * @param $file_path
		 *
		 * @return bool|string
		 */
		function get_file_type( $file_path ) {
			if ( empty( $file_path ) || ! file_exists( $file_path ) ) {
				return false;
			}
			if ( function_exists( 'exif_imagetype' ) ) {
				$image_type = exif_imagetype( $file_path );
				if ( ! empty( $image_type ) ) {
					$image_mime = image_type_to_mime_type( $image_type );
				}
			} else {
				$image_details = getimagesize( $file_path );
				$image_mime    = ! empty( $image_details ) && is_array( $image_details ) ? $image_details['mime'] : '';
			}

			return $image_mime;
		}

		/**
		 * Read the image paths from an attachment's meta data and process each image
		 * with wp_smushit().
		 *
		 * @param $meta
		 * @param null $ID
		 *
		 * @return mixed
		 */
		function resize_from_meta_data( $image ) {
			global $wp_smush;

			$errors = new WP_Error();
			$stats  = array(
				'stats' => array_merge(
					$wp_smush->_get_size_signature(),
					array(
						'api_version' => - 1,
						'lossy'       => - 1,
					)
				),
				'sizes' => array(),
			);

			$size_before = $size_after = $compression = $total_time = $bytes_saved = 0;

			// Registry Object for NextGen Gallery.
			$registry = C_Component_Registry::get_instance();

			// Storage Object for NextGen Gallery.
			$storage = $registry->get_utility( 'I_Gallery_Storage' );

			// File path and URL for original image.
			// Get an array of sizes available for the $image.
			$sizes = $storage->get_image_sizes();

			// If images has other registered size, smush them first.
			if ( ! empty( $sizes ) ) {

				if ( class_exists( 'finfo' ) ) {
					$finfo = new finfo( FILEINFO_MIME_TYPE );
				} else {
					$finfo = false;
				}

				foreach ( $sizes as $size ) {

					// Skip Full size, if smush original is not checked.
					if ( 'full' == $size && ! $wp_smush->smush_original ) {
						continue;
					}

					// Check if registered size is supposed to be converted or not.
					global $wpsmushit_admin;
					if ( 'full' != $size && $wpsmushit_admin->skip_image_size( $size ) ) {
						return false;
					}

					// We take the original image. Get the absolute path using the storage object.
					$attachment_file_path_size = $storage->get_image_abspath( $image, $size );

					if ( $finfo ) {
						$ext = file_exists( $attachment_file_path_size ) ? $finfo->file( $attachment_file_path_size ) : '';
					} elseif ( function_exists( 'mime_content_type' ) ) {
						$ext = mime_content_type( $attachment_file_path_size );
					} else {
						$ext = false;
					}
					if ( $ext ) {
						$valid_mime = array_search(
							$ext,
							array(
								'jpg' => 'image/jpeg',
								'png' => 'image/png',
								'gif' => 'image/gif',
							),
							true
						);
						if ( false === $valid_mime ) {
							continue;
						}
					}
					/**
					 * Allows to skip a image from smushing
					 *
					 * @param bool , Smush image or not
					 * @$size string, Size of image being smushed
					 */
					$smush_image = apply_filters( 'wp_smush_nextgen_image', true, $size );
					if ( ! $smush_image ) {
						continue;
					}
					// Store details for each size key.
					$response = $wp_smush->do_smushit( $attachment_file_path_size, $image->pid, $this->module );

					if ( is_wp_error( $response ) ) {
						return $response;
					}

					// If there are no stats.
					if ( empty( $response['data'] ) ) {
						continue;
					}

					// If the image size grew after smushing, skip it.
					if ( $response['data']->after_size > $response['data']->before_size ) {
						continue;
					}

					$stats['sizes'][ $size ] = (object) $wp_smush->_array_fill_placeholders( $wp_smush->_get_size_signature(), (array) $response['data'] );

					if ( empty( $stats['stats']['api_version'] ) || $stats['stats']['api_version'] == - 1 ) {
						$stats['stats']['api_version'] = $response['data']->api_version;
						$stats['stats']['lossy']       = $response['data']->lossy;
						$stats['stats']['keep_exif']   = ! empty( $response['data']->keep_exif ) ? $response['data']->keep_exif : 0;
					}
				}
			}

			$has_errors = (bool) count( $errors->get_error_messages() );

			// Set smush status for all the images, store it in wp-smpro-smush-data.
			if ( ! $has_errors ) {

				$existing_stats = ( ! empty( $image->meta_data ) && ! empty( $image->meta_data['wp_smush'] ) ) ? $image->meta_data['wp_smush'] : '';

				if ( ! empty( $existing_stats ) ) {
					// Update stats for each size.
					if ( ! empty( $existing_stats['sizes'] ) && ! empty( $stats['sizes'] ) ) {

						foreach ( $existing_stats['sizes'] as $size_name => $size_stats ) {
							// If stats for a particular size doesn't exists.
							if ( empty( $stats['sizes'] ) || empty( $stats['sizes'][ $size_name ] ) ) {
								$stats = empty( $stats ) ? array() : $stats;
								if ( empty( $stats['sizes'] ) ) {
									$stats['sizes'] = array();
								}
								$stats['sizes'][ $size_name ] = $existing_stats['sizes'][ $size_name ];
							} else {
								$existing_stats_size = (object) $existing_stats['sizes'][ $size_name ];

								// Store the original image size.
								$stats['sizes'][ $size_name ]->size_before = ( ! empty( $existing_stats_size->size_before ) && $existing_stats_size->size_before > $stats['sizes'][ $size_name ]->size_before ) ? $existing_stats_size->size_before : $stats['sizes'][ $size_name ]->size_before;

								// Update compression percent and bytes saved for each size.
								$stats['sizes'][ $size_name ]->bytes = $stats['sizes'][ $size_name ]->bytes + $existing_stats_size->bytes;
								// Calculate percentage.
								$stats['sizes'][ $size_name ]->percent = $wp_smush->calculate_percentage( $stats['sizes'][ $size_name ], $existing_stats_size );
							}
						}
					}
				}
				// Total Stats.
				$stats                 = $wp_smush->total_compression( $stats );
				$stats['total_images'] = ! empty( $stats['sizes'] ) ? count( $stats['sizes'] ) : 0;

				// If there was any compression and there was no error in smushing.
				if ( isset( $stats['stats']['bytes'] ) && $stats['stats']['bytes'] >= 0 && ! $has_errors ) {
					/**
					 * Runs if the image smushing was successful
					 *
					 * @param int $ID Image Id
					 *
					 * @param array $stats Smush Stats for the image
					 */
					do_action( 'wp_smush_image_optimised_nextgen', $image->pid, $stats );
				}
				$image->meta_data['wp_smush'] = $stats;
				nggdb::update_image_meta( $image->pid, $image->meta_data );

				// Allows To get the stats for each image, after the image is smushed.
				do_action( 'wp_smush_nextgen_image_stats', $image->pid, $stats );
			}

			return $image->meta_data['wp_smush'];
		}

		/**
		 * Performs the actual smush process
		 *
		 * @usedby: `manual_nextgen`, `auto_smush`, `smush_bulk`
		 *
		 * @param string $pid      NextGen Gallery Image id.
		 * @param string $image    Nextgen gallery image object.
		 * @param bool   $echo     Whether to echo the stats or not, false for auto smush.
		 * @param bool   $is_bulk  Whether it's called by bulk smush or not.
		 *
		 * @return mixed Stats / Status / Error
		 */
		public function smush_image( $pid = '', $image = '', $echo = true, $is_bulk = false ) {
			global $wpsmushnextgenstats, $wp_smush;

			$wp_smush->initialise();

			// Get image, if we have image id.
			if ( ! empty( $pid ) ) {
				$image = $this->get_nextgen_image_from_id( $pid );
			} elseif ( ! empty( $image ) ) {
				$pid = $this->get_nextgen_id_from_image( $image );
			}

			$metadata = ! empty( $image ) ? $image->meta_data : '';

			if ( empty( $metadata ) ) {
				/**
				 * We use error_msg for single images to append to the div and error_message to
				 * append to bulk smush errors list.
				 */
				wp_send_json_error( array(
					'error'         => 'no_metadata',
					'error_msg'     => '<p class="wp-smush-error-message">' . esc_html__( "We couldn't find the metadata for the image, possibly the image has been deleted.", 'wp-smushit' ) . '</p>',
					'error_message' => esc_html__( "We couldn't find the metadata for the image, possibly the image has been deleted.", 'wp-smushit' ),
					'file_name'     => isset( $image->filename ) ? $image->filename : 'undefined',
				) );
			}

			$registry = C_Component_Registry::get_instance();
			$storage  = $registry->get_utility( 'I_Gallery_Storage' );

			// Perform Resizing.
			$metadata = $this->resize_image( $pid, $image, $metadata, $storage );

			// Store Meta.
			$image->meta_data = $metadata;
			nggdb::update_image_meta( $image->pid, $image->meta_data );

			// Smush the main image and its sizes.
			$smush = $this->resize_from_meta_data( $image, $registry, $storage );

			if ( ! is_wp_error( $smush ) ) {
				$status = $wpsmushnextgenstats->show_stats( $pid, $smush, false, true );
			}

			// If we are suppose to send the stats, not required for auto smush.
			if ( $echo ) {
				// Send stats.
				if ( is_wp_error( $smush ) ) {
					/**
					 * Not used for bulk smush.
					 *
					 * @param WP_Error $smush
					 */
					wp_send_json_error( $smush->get_error_message() );
				} else {
					wp_send_json_success( $status );
				}
			} else {
				if ( ! $is_bulk ) {
					if ( is_wp_error( $smush ) ) {
						return $smush;
					} else {
						return $status;
					}
				} else {
					return $smush;
				}
			}
		}

		/**
		 * Handles the smushing of each image and its registered sizes
		 * Calls the function to update the compression stats
		 */
		function manual_nextgen() {
			$pid   = ! empty( $_GET['attachment_id'] ) ? absint( (int) $_GET['attachment_id'] ) : '';
			$nonce = ! empty( $_GET['_nonce'] ) ? $_GET['_nonce'] : '';

			// Verify Nonce.
			if ( ! wp_verify_nonce( $nonce, 'wp_smush_nextgen' ) ) {
				wp_send_json_error( array(
					'error' => 'nonce_verification_failed'
				) );
			}

			// Check for media upload permission.
			if ( ! current_user_can( 'upload_files' ) ) {
				wp_die( __( "You don't have permission to work with uploaded files.", 'wp-smushit' ) );
			}

			if ( empty( $pid ) ) {
				wp_die( __( 'No attachment ID was provided.', 'wp-smushit' ) );
			}

			$this->smush_image( $pid, '' );
		}

		/**
		 * Process auto smush request for nextgen gallery images
		 *
		 * @param $image
		 */
		function auto_smush( $image ) {

			$this->smush_image( '', $image, false );

		}

		/**
		 * Checks for file backup, if available for any of the size,
		 * Function returns true
		 *
		 * @param $pid
		 * @param $attachment_data
		 *
		 * @return bool
		 */
		function show_restore_option( $pid, $attachment_data ) {
			global $wp_smush;

			// Registry Object for NextGen Gallery.
			$registry = C_Component_Registry::get_instance();

			// Gallery Storage Object.
			$storage = $registry->get_utility( 'I_Gallery_Storage' );

			$image = $storage->object->_image_mapper->find( $pid );

			// Get image full path.
			$attachment_file_path = $storage->get_image_abspath( $image, 'full' );

			// Get the backup path.
			$backup_path = $wp_smush->get_image_backup_path( $attachment_file_path );

			// If one of the backup(Ours/NextGen) exists, show restore option.
			if ( file_exists( $backup_path ) || file_exists( $attachment_file_path . '_backup' ) ) {
				return true;
			}

			// Get Sizes, and check for backup.
			if ( empty( $attachment_data['sizes'] ) ) {
				return false;
			}
			foreach ( $attachment_data['sizes'] as $size => $size_data ) {
				if ( 'full' == $size ) {
					continue;
				}
				// Get file path.
				$attachment_size_file_path = $storage->get_image_abspath( $image, $size );

				// Get the backup path.
				$backup_path = $wp_smush->get_image_backup_path( $attachment_size_file_path );

				// If one of the backup(Ours/NextGen) exists, show restore option.
				if ( file_exists( $backup_path ) || file_exists( $attachment_size_file_path . '_backup' ) ) {
					return true;
				}
			}

		}

		/**
		 * Handles the ajax request to restore a image from backup and return button HTML
		 *
		 * @uses WpSmushNextGenAdmin::wp_smush_column_options()
		 */
		function restore_image() {
			global $wp_smush, $wpsmushnextgenadmin;

			// Check Empty fields.
			if ( empty( $_POST['attachment_id'] ) || empty( $_POST['_nonce'] ) ) {
				wp_send_json_error(
					array(
						'error'   => 'empty_fields',
						'message' => esc_html__( 'Error in processing restore action, Fields empty.', 'wp-smushit' ),
					)
				);
			}

			// Check Nonce.
			if ( ! wp_verify_nonce( $_POST['_nonce'], 'wp-smush-restore-' . $_POST['attachment_id'] ) ) {
				wp_send_json_error(
					array(
						'error'   => 'empty_fields',
						'message' => esc_html__( 'Image not restored, Nonce verification failed.', 'wp-smushit' ),
					)
				);
			}

			// Store the restore success/failure for all the sizes.
			$restored = array();

			// Registry Object for NextGen Gallery.
			$registry = C_Component_Registry::get_instance();

			// Gallery Storage Object.
			$storage = $registry->get_utility( 'I_Gallery_Storage' );

			// Process Now.
			$image_id = absint( (int) $_POST['attachment_id'] );

			// Get the absolute path for original image.
			$image = $this->get_nextgen_image_from_id( $image_id );

			// Get image full path.
			$attachment_file_path = $storage->get_image_abspath( $image, 'full' );

			// Get the backup path.
			$backup_path = $wp_smush->get_image_backup_path( $attachment_file_path );

			// Restoring the full image.
			// If file exists, corresponding to our backup path.
			if ( file_exists( $backup_path ) ) {
				// Restore.
				$restored[] = @copy( $backup_path, $attachment_file_path );

				// Delete the backup.
				@unlink( $backup_path );
			} elseif ( file_exists( $attachment_file_path . '_backup' ) ) {
				// Restore from other backups.
				$restored[] = @copy( $attachment_file_path . '_backup', $attachment_file_path );
			}
			// Restoring the other sizes.
			$attachment_data = ! empty( $image->meta_data['wp_smush'] ) ? $image->meta_data['wp_smush'] : '';
			if ( ! empty( $attachment_data['sizes'] ) ) {
				foreach ( $attachment_data['sizes'] as $size => $size_data ) {
					if ( 'full' == $size ) {
						continue;
					}
					// Get file path.
					$attachment_size_file_path = $storage->get_image_abspath( $image, $size );

					// Get the backup path.
					$backup_path = $wp_smush->get_image_backup_path( $attachment_size_file_path );

					// If file exists, corresponding to our backup path.
					if ( file_exists( $backup_path ) ) {
						// Restore.
						$restored[] = @copy( $backup_path, $attachment_size_file_path );

						// Delete the backup.
						@unlink( $backup_path );
					} elseif ( file_exists( $attachment_size_file_path . '_backup' ) ) {
						// Restore from other backups.
						$restored[] = @copy( $attachment_size_file_path . '_backup', $attachment_size_file_path );
					}
				}
			}
			// If any of the image is restored, we count it as success.
			if ( in_array( true, $restored ) ) {

				// Update the global Stats.
				$wpsmushnextgenadmin->update_nextgen_stats( $image_id );

				// Remove the Meta, And send json success.
				$image->meta_data['wp_smush'] = '';
				nggdb::update_image_meta( $image->pid, $image->meta_data );

				// Get the Button html without wrapper.
				$button_html = $wpsmushnextgenadmin->wp_smush_column_options( '', $image_id, false );

				wp_send_json_success(
					array(
						'button' => $button_html,
					)
				);
			}
			wp_send_json_error(
				array(
					'message' => '<div class="wp-smush-error">' . __( 'Unable to restore image', 'wp-smushit' ) . '</div>',
				)
			);
		}

		/**
		 * Handles the Ajax request to resmush a image, if the full image wasn't smushed earlier
		 */
		function resmush_image() {
			// Check Empty fields.
			if ( empty( $_POST['attachment_id'] ) || empty( $_POST['_nonce'] ) ) {
				wp_send_json_error(
					array(
						'error'   => 'empty_fields',
						'message' => '<div class="wp-smush-error">' . esc_html__( "We couldn't process the image, fields empty.", 'wp-smushit' ) . '</div>',
					)
				);
			}
			// Check Nonce.
			if ( ! wp_verify_nonce( $_POST['_nonce'], 'wp-smush-resmush-' . $_POST['attachment_id'] ) ) {
				wp_send_json_error(
					array(
						'error'   => 'empty_fields',
						'message' => '<div class="wp-smush-error">' . esc_html__( "Image couldn't be smushed as the nonce verification failed, try reloading the page.", 'wp-smushit' ) . '</div>',
					)
				);
			}

			$image_id = intval( $_POST['attachment_id'] );

			$smushed = $this->smush_image( $image_id, '', false );

			// If any of the image is restored, we count it as success.
			if ( ! empty( $smushed ) && ! is_wp_error( $smushed ) ) {

				// Send button content.
				wp_send_json_success(
					array(
						'button' => $smushed['status'] . $smushed['stats'],
					)
				);

			} elseif ( is_wp_error( $smushed ) ) {

				// Send Error Message.
				wp_send_json_error(
					array(
						'message' => sprintf( '<div class="wp-smush-error">' . __( 'Unable to smush image, %s', 'wp-smushit' ) . '</div>', $smushed->get_error_message() ),
					)
				);

			}
		}

		/**
		 * Get file extension from file path
		 *
		 * @param string $file_path Absolute image path to get the mime for.
		 *
		 * @return string Null/ Mime Type
		 */
		function get_file_ext( $file_path = '' ) {
			if ( empty( $file_path ) ) {
				return '';
			}

			if ( class_exists( 'finfo' ) ) {
				$finfo = new finfo( FILEINFO_MIME_TYPE );
			} else {
				$finfo = false;
			}

			if ( $finfo ) {
				$ext = file_exists( $file_path ) ? $finfo->file( $file_path ) : '';
			} elseif ( function_exists( 'mime_content_type' ) ) {
				$ext = mime_content_type( $file_path );
			} else {
				$ext = '';
			}

			return $ext;
		}

		/**
		 * Optionally resize a NextGen image
		 *
		 * @param $attachment_id Gallery Image id
		 * @param $image Image object for NextGen gallery
		 * @param $meta Image meta from nextgen gallery
		 * @param $storage Storage object for nextgen gallery
		 *
		 * @return mixed
		 */
		function resize_image( $attachment_id, $image, $meta, $storage ) {
			global $wpsmush_resize, $wpsmushit_admin;
			if ( empty( $attachment_id ) || empty( $meta ) || ! is_object( $storage ) ) {
				return $meta;
			}

			// Initialize resize class.
			$wpsmush_resize->initialize();

			// If resizing not enabled, or if both max width and height is set to 0, return.
			if ( ! $wpsmush_resize->resize_enabled || ( $wpsmush_resize->max_w == 0 && $wpsmush_resize->max_h == 0 ) ) {
				return $meta;
			}

			$file_path = $storage->get_image_abspath( $image );
			if ( ! file_exists( $file_path ) ) {
				return $meta;
			}

			$ext = $this->get_file_ext( $file_path );

			$mime_supported = in_array( $ext, $wpsmushit_admin->mime_types );

			// If type of upload doesn't matches the criteria return.
			$mime_supported = apply_filters( 'wp_smush_resmush_mime_supported', $mime_supported, $mime );
			if ( ! empty( $mime ) && ! $mime_supported ) {
				return $meta;
			}

			// If already resized.
			if ( ! empty( $meta['wp_smush_resize_savings'] ) ) {
				return $meta;
			}

			$sizes = $storage->get_image_sizes();

			$should_resize = true;

			/**
			 * Filter whether the NextGen image should be resized or not
			 *
			 * @since 2.3
			 *
			 * @param bool $should_resize
			 *
			 * @param object NextGen Gallery image object
			 *
			 * @param array NextGen Gallery image object
			 *
			 * @param string $context The type of upload action. Values include 'upload' or 'sideload'.
			 */
			$should_resize = apply_filters( 'wp_smush_resize_nextgen_image', $should_resize, $image, $meta );
			if ( ! $should_resize ) {
				return $meta;
			}

			$original_file_size = filesize( $file_path );

			$resized = $wpsmush_resize->perform_resize( $file_path, $original_file_size, $attachment_id, '', false );

			// If resize wasn't successful.
			if ( ! $resized || $resized['filesize'] == $original_file_size ) {
				// Unlink Image, if other size path is not similar.
				$this->maybe_unlink( $file_path, $sizes, $image, $storage );

				return $meta;
			} else {

				// Else Replace the Original file with resized file.
				$replaced = @copy( $resized['file_path'], $file_path );
				$this->maybe_unlink( $resized['file_path'], $sizes, $image, $storage );
			}

			if ( $replaced ) {
				// Updated File size.
				$u_file_size = filesize( $file_path );

				$savings['bytes']       = $original_file_size > $u_file_size ? $original_file_size - $u_file_size : 0;
				$savings['size_before'] = $original_file_size;
				$savings['size_after']  = $u_file_size;

				// Store savings in meta data.
				if ( ! empty( $savings ) ) {
					$meta['wp_smush_resize_savings'] = $savings;
				}

				// Update dimensions of the image in meta.
				$meta['width']         = ! empty( $resized['width'] ) ? $resized['width'] : $meta['width'];
				$meta['full']['width'] = ! empty( $resized['width'] ) ? $resized['width'] : $meta['width'];

				$meta['height']         = ! empty( $resized['height'] ) ? $resized['height'] : $meta['height'];
				$meta['full']['height'] = ! empty( $resized['height'] ) ? $resized['height'] : $meta['height'];

				/**
				 * Called after the image has been successfully resized
				 * Can be used to update the stored stats
				 */
				do_action(
					'wp_smush_image_nextgen_resized', $attachment_id,
					array(
						'stats' => $savings,
					)
				);

				/**
				 * Called after the image has been successfully resized
				 * Can be used to update the stored stats
				 */
				do_action( 'wp_smush_image_resized', $attachment_id, $savings );
			}

			return $meta;

		}

		/**
		 * Unlinks a file if none of the thumbnails have same file path
		 *
		 * @param $path Full path of the file to be unlinked
		 * @param $sizes All the available image sizes for the image
		 * @param $image Image object to fetch the full path of all the sizes
		 * @param $storage Gallery storage object
		 *
		 * @return bool Whether the file was unlinked or not
		 */
		function maybe_unlink( $path, $sizes, $image, $storage ) {
			if ( empty( $path ) || ! is_object( $storage ) || ! is_object( $image ) ) {
				return false;
			}

			// Unlink directly if meta value is not specified.
			if ( empty( $sizes ) ) {
				@unlink( $path );
			}

			$unlink = true;

			// Check if the file name is similar to one of the image sizes.
			$path_parts = pathinfo( $path );

			$filename = ! empty( $path_parts['basename'] ) ? $path_parts['basename'] : $path_parts['filename'];
			foreach ( $sizes as $image_size ) {
				$file_path_size = $storage->get_image_abspath( $image, $image_size );
				if ( false === strpos( $file_path_size, $filename ) ) {
					continue;
				}
				$unlink = false;
			}

			// Unlink the file.
			if ( $unlink ) {
				@unlink( $path );
			}

			return $unlink;
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
			if ( ! class_exists( 'C_NextGEN_Bootstrap' ) ) {
				$disabled = true;
			}

			return $disabled;
		}

		/**
		 * Show submit button for integration settings.
		 *
		 * If a pro user and NextGen plugin is active, we need to
		 * make sure settings submit button is shown.
		 *
		 * @param bool $show Should show?.
		 *
		 * @since 2.8.1
		 *
		 * @return bool
		 */
		public function show_submit( $show ) {
			global $wp_smush;

			// If a pro user and NextGen plugin is active.
			if ( $wp_smush->validate_install() && class_exists( 'C_NextGEN_Bootstrap' ) ) {
				$show = true;
			}

			return $show;
		}

	}// End of Class.

}// End Of if class not exists.

// Extend NextGen Mixin class to smush dynamic images.
if ( class_exists( 'WpSmushNextGen' ) ) {
	global $wp_smush, $wpsmushnextgen;
	if ( ! is_object( $wpsmushnextgen ) ) {
		$wpsmushnextgen = new WpSmushNextGen();
	}

	// Extend Nextgen Mixin class and override the generate_image_size, to optimize dynamic thumbnails, generated by nextgen, check for auto smush.
	if ( ! class_exists( 'WpSmushNextGenDynamicThumbs' ) && class_exists( 'Mixin' ) && $wp_smush->is_auto_smush_enabled() ) {

		class WpSmushNextGenDynamicThumbs extends Mixin {

			/**
			 * Overrides the NextGen Gallery function, to smush the dynamic images and thumbnails created by gallery
			 *
			 * @param C_Image|int|stdClass $image
			 * @param $size
			 * @param null                 $params
			 * @param bool|false           $skip_defaults
			 *
			 * @return bool|object
			 */
			function generate_image_size( $image, $size, $params = null, $skip_defaults = false ) {
				global $wp_smush;
				$image_id = ! empty( $image->pid ) ? $image->pid : '';
				// Get image from storage object if we don't have it already.
				if ( empty( $image_id ) ) {
					// Get metadata For the image.
					// Registry Object for NextGen Gallery.
					$registry = C_Component_Registry::get_instance();

					// Gallery Storage Object.
					$storage = $registry->get_utility( 'I_Gallery_Storage' );

					$image_id = $storage->object->_get_image_id( $image );
				}
				// Call the actual function to generate the image, and pass the image to smush.
				$success = $this->call_parent( 'generate_image_size', $image, $size, $params, $skip_defaults );
				if ( $success ) {
					$filename = $success->fileName;
					// Smush it, if it exists.
					if ( file_exists( $filename ) ) {
						$response = $wp_smush->do_smushit( $filename, $image_id, $this->module );

						// If the image was smushed.
						if ( ! is_wp_error( $response ) && ! empty( $response['data'] ) && $response['data']->bytes_saved > 0 ) {
							// Check for existing stats.
							if ( ! empty( $image->meta_data ) && ! empty( $image->meta_data['wp_smush'] ) ) {
								$stats = $image->meta_data['wp_smush'];
							} else {
								// Initialize stats array.
								$stats                = array(
									'stats' => array_merge(
										$wp_smush->_get_size_signature(),
										array(
											'api_version' => - 1,
											'lossy'       => - 1,
											'keep_exif'   => false,
										)
									),
									'sizes' => array(),
								);
								$stats['bytes']       = $response['data']->bytes_saved;
								$stats['percent']     = $response['data']->compression;
								$stats['size_after']  = $response['data']->after_size;
								$stats['size_before'] = $response['data']->before_size;
								$stats['time']        = $response['data']->time;
							}
							$stats['sizes'][ $size ] = (object) $wp_smush->_array_fill_placeholders( $wp_smush->_get_size_signature(), (array) $response['data'] );

							if ( isset( $image->metadata ) ) {
								$image->meta_data['wp_smush'] = $stats;
								nggdb::update_image_meta( $image->pid, $image->meta_data );
							}

							// Allows To get the stats for each image, after the image is smushed.
							do_action( 'wp_smush_nextgen_image_stats', $image_id, $stats );
						}
					}
				}

				return $success;
			}
		}
	}
}
if ( class_exists( 'WpSmushNextGenDynamicThumbs' ) ) {
	if ( ! get_option( 'ngg_options' ) ) {
		return;
	}
	$storage = C_Gallery_Storage::get_instance();
	$storage->get_wrapped_instance()->add_mixin( 'WpSmushNextGenDynamicThumbs' );
}
