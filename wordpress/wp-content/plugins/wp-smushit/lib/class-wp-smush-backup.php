<?php

if ( ! class_exists( 'WpSmushBackup' ) ) {

	class WpSmushBackup {

		/**
		 * Whether to backp images or not
		 *
		 * @var bool
		 */
		var $backup_enabled = false;

		/**
		 * Key for storing file path for image backup
		 *
		 * @var string
		 */
		var $backup_key = 'smush-full';

		/**
		 * Constructor
		 */
		function __construct() {
			// Initialize Variables and perform other operations.
			add_action( 'admin_init', array( $this, 'admin_init' ) );

			// Handle Restore operation.
			add_action( 'wp_ajax_smush_restore_image', array( $this, 'restore_image' ) );
		}

		function admin_init() {

			$this->initialize();

		}

		function initialize() {

			global $wpsmush_settings;

			// Whether backup is enabled or not
			$this->backup_enabled = isset( $wpsmush_settings->settings['backup'] ) ? $wpsmush_settings->settings['backup'] : 0;
		}

		/**
		 * Creates a backup of file for the given attachment path
		 *
		 * Checks if there is a existing backup, else create one
		 *
		 * @param string $file_path
		 * @param string $backup_path
		 * @param string $attachment_id
		 *
		 * @return string
		 */
		function create_backup( $file_path = '', $backup_path = '', $attachment_id = '' ) {
			global $wp_smush, $wpsmush_pngjpg;

			$copied = false;

			if ( empty( $file_path ) ) {
				return '';
			}

			// Return file path if backup is disabled
			if ( ! $this->backup_enabled || ! $wp_smush->validate_install() ) {
				return $file_path;
			}

			// Get a backup path if empty
			if ( empty( $backup_path ) ) {
				$backup_path = $wp_smush->get_image_backup_path( $file_path );
			}

			// If we don't have any backup path yet, bail!
			if ( empty( $backup_path ) ) {
				return $file_path;
			}

			$attachment_id = ! empty( $wp_smush->attachment_id ) ? $wp_smush->attachment_id : $attachment_id;
			if ( ! empty( $attachment_id ) && $wpsmush_pngjpg->is_converted( $attachment_id ) ) {
				// No need to create a backup, we already have one if enabled
				return $file_path;
			}

			// Check for backup from other plugins, like nextgen, if it doesn't exists, create our own
			if ( ! file_exists( $backup_path ) ) {
				$copied = @copy( $file_path, $backup_path );
			}
			// Store the backup path in image backup sizes
			if ( $copied ) {
				$this->add_to_image_backup_sizes( $attachment_id, $backup_path );
			}

		}

		/**
		 * Store new backup path for the image
		 *
		 * @param string $attachment_id
		 * @param string $backup_path
		 * @param string $backup_key
		 *
		 * @return bool|int|void
		 */
		function add_to_image_backup_sizes( $attachment_id = '', $backup_path = '', $backup_key = '' ) {
			if ( empty( $attachment_id ) || empty( $backup_path ) ) {
				return;
			}
			// Get the Existing backup sizes
			$backup_sizes = get_post_meta( $attachment_id, '_wp_attachment_backup_sizes', true );
			if ( empty( $backup_sizes ) ) {
				$backup_sizes = array();
			}

			// Return if backup file doesn't exists
			if ( ! file_exists( $backup_path ) ) {
				return;
			}
			list( $width, $height ) = getimagesize( $backup_path );
			// Store our backup Path
			$backup_key                  = empty( $backup_key ) ? $this->backup_key : $backup_key;
			$backup_sizes[ $backup_key ] = array(
				'file'   => wp_basename( $backup_path ),
				'width'  => $width,
				'height' => $height,
			);

			return update_post_meta( $attachment_id, '_wp_attachment_backup_sizes', $backup_sizes );
		}

		/**
		 * Restore the image and its sizes from backup
		 *
		 * @param string $attachment
		 * @param bool   $resp
		 *
		 * @return bool
		 */
		function restore_image( $attachment = '', $resp = true ) {
			global $wp_smush, $wpsmush_helper;
			// If no attachment id is provided, check $_POST variable for attachment_id.
			if ( empty( $attachment ) ) {
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
			}

			// Store the restore success/failure for Full size image.
			$restored = $restore_png = false;

			// Process Now
			$attachment_id = empty( $attachment ) ? absint( (int) $_POST['attachment_id'] ) : $attachment;

			// Set a Option to avoid the smush-restore-smush loop
			update_option( "wp-smush-restore-$attachment_id", true );

			// Restore Full size -> get other image sizes -> restore other images
			// Get the Original Path
			$file_path = $wpsmush_helper->get_attached_file( $attachment_id );

			// Get the backup path
			$backup_sizes = get_post_meta( $attachment_id, '_wp_attachment_backup_sizes', true );

			// If there are
			if ( ! empty( $backup_sizes ) ) {

				// 1. Check if the image was converted from PNG->JPG, Get the corresponding backup path
				if ( ! empty( $backup_sizes['smush_png_path'] ) ) {
					$backup_path = $backup_sizes['smush_png_path'];
					// If we don't have the backup path in backup sizes, Check for legacy original file path
					if ( empty( $backup_path ) ) {
						// Check if it's a jpg converted from png, and restore the jpg to png
						$original_file = get_post_meta( $attachment_id, WP_SMUSH_PREFIX . 'original_file', true );
						$backup_path   = $wp_smush->original_file( $original_file );
					}

					// If we have a backup path for PNG file, use restore_png()
					if ( ! empty( $backup_path ) ) {
						$restore_png = true;
					}
				}

				// 2. If we don't have a backup path from PNG->JPG, check for normal smush backup path
				if ( empty( $backup_path ) ) {

					if ( ! empty( $backup_sizes[ $this->backup_key ] ) ) {
						$backup_path = $backup_sizes[ $this->backup_key ];
					} else {
						// If we don't have a backup path, check for legacy backup naming convention
						$backup_path = $wp_smush->get_image_backup_path( $file_path );
					}
				}
				$backup_path = is_array( $backup_path ) && ! empty( $backup_path['file'] ) ? $backup_path['file'] : $backup_path;
			}

			$backup_full_path = str_replace( wp_basename( $file_path ), wp_basename( $backup_path ), $file_path );

			// Finally, if we have the backup path, perform the restore operation
			if ( ! empty( $backup_full_path ) ) {

				/**
				 * Allows S3 to hook, check and download the file
				 */
				do_action( 'smush_file_exists', $backup_full_path, $attachment_id, array() );

				if ( $restore_png ) {
					// restore PNG full size and all other image sizes
					$restored = $this->restore_png( $attachment_id, $backup_full_path, $file_path );

					// JPG file is already deleted, Update backup sizes
					if ( $restored ) {
						$this->remove_from_backup_sizes( $attachment_id, 'smush_png_path', $backup_sizes );
					}
				} else {
					// If file exists, corresponding to our backup path
					// Restore
					$restored = @copy( $backup_full_path, $file_path );

					// Remove the backup, if we were able to restore the image
					if ( $restored ) {

						// Update backup sizes
						$this->remove_from_backup_sizes( $attachment_id, '', $backup_sizes );

						// Delete the backup
						$this->remove_backup( $attachment_id, $backup_full_path );
					}
				}
			} elseif ( file_exists( $file_path . '_backup' ) ) {
				// Try to restore from other backups, if any
				$restored = @copy( $file_path . '_backup', $file_path );
			}

			// Generate all other image size, and update attachment metadata
			$metadata = wp_generate_attachment_metadata( $attachment_id, $file_path );

			// Update metadata to db if it was successfully generated
			if ( ! empty( $metadata ) && ! is_wp_error( $metadata ) ) {
				wp_update_attachment_metadata( $attachment_id, $metadata );
			}

			// If any of the image is restored, we count it as success
			if ( $restored ) {

				// Remove the Meta, And send json success
				delete_post_meta( $attachment_id, $wp_smush->smushed_meta_key );

				// Remove PNG to JPG conversion savings
				delete_post_meta( $attachment_id, WP_SMUSH_PREFIX . 'pngjpg_savings' );

				// Remove Original File
				delete_post_meta( $attachment_id, WP_SMUSH_PREFIX . 'original_file' );

				// Delete resize savings
				delete_post_meta( $attachment_id, WP_SMUSH_PREFIX . 'resize_savings' );

				// Get the Button html without wrapper
				$button_html = $wp_smush->set_status( $attachment_id, false, false, false );

				// Remove the transient
				delete_option( "wp-smush-restore-$attachment_id" );

				if ( $resp ) {
					$size = file_exists( $file_path ) ? filesize( $file_path ) : 0;
					if ( $size > 0 ) {
						$update_size = size_format( $size, 0 ); // Used in js to update image stat.
					}

					wp_send_json_success(
						array(
							'button'   => $button_html,
							'new_size' => isset( $update_size ) ? $update_size : 0,
						)
					);
				} else {
					return true;
				}
			}
			// Remove the transient
			delete_option( "wp-smush-restore-$attachment_id" );

			if ( ! $resp ) {
				wp_send_json_error( array( 'message' => '<div class="wp-smush-error">' . __( 'Unable to restore image', 'wp-smushit' ) . '</div>' ) );
			}

			return false;
		}


		/**
		 *
		 * @param string $image_id
		 * @param string $original_file
		 * @param string $file_path
		 *
		 * @return bool
		 */
		function restore_png( $image_id = '', $original_file = '', $file_path = '' ) {

			global $wp_smush, $wpsmush_pngjpg;

			// If we don't have attachment id, there is nothing we can do
			if ( empty( $image_id ) ) {
				return false;
			}

			$meta = '';

			// Else get the Attachment details
			/**
			 * For Full Size
			 * 1. Get the original file path
			 * 2. Update the attachment metadata and all other meta details
			 * 3. Delete the JPEG
			 * 4. And we're done
			 * 5. Add a action after updating the URLs, that'd allow the users to perform a additional search, replace action
			 */
			if ( empty( $original_file ) ) {
				$original_file = get_post_meta( $image_id, WP_SMUSH_PREFIX . 'original_file', true );
			}
			$original_file_path = $wp_smush->original_file( $original_file );
			if ( file_exists( $original_file_path ) ) {
				// Update the path details in meta and attached file, replace the image
				$meta = $wpsmush_pngjpg->update_image_path( $image_id, $file_path, $original_file_path, $meta, 'full', 'restore' );

				// Unlink JPG
				if ( ! empty( $meta['file'] ) && $original_file == $meta['file'] ) {
					@unlink( $file_path );
				}

				$meta = wp_generate_attachment_metadata( $image_id, $original_file_path );

				/**
				 *  Perform a action after the image URL is updated in post content
				 */
				do_action( 'wp_smush_image_url_updated', $image_id, $file_path, $original_file );
			}
			// Update Meta
			if ( ! empty( $meta ) ) {
				// Remove Smushing, while attachment data is updated for the image
				remove_filter( 'wp_update_attachment_metadata', array( $wp_smush, 'smush_image' ), 15 );
				wp_update_attachment_metadata( $image_id, $meta );

				return true;
			}

			return false;

		}

		/**
		 *  Remove the backup path for a give attachment id and path
		 *
		 * @param string $attachment_id
		 *
		 * @param string $path
		 */
		function remove_backup( $attachment_id = '', $path = '' ) {
			@unlink( $path );
		}

		/**
		 * Remove a specific backup key from Backup Size array
		 *
		 * @param string $attachment_id
		 * @param string $backup_key
		 * @param array  $backup_sizes
		 */
		function remove_from_backup_sizes( $attachment_id = '', $backup_key = '', $backup_sizes = array() ) {
			// Get backup sizes
			$backup_sizes = empty( $backup_sizes ) ? get_post_meta( $attachment_id, '_wp_attachment_backup_sizes', true ) : $backup_sizes;
			$backup_key   = empty( $backup_key ) ? $this->backup_key : $backup_key;

			// If we don't have any backup sizes list or if the particular key is not set, return
			if ( empty( $backup_sizes ) || ! isset( $backup_sizes[ $backup_key ] ) ) {
				return;
			}

			unset( $backup_sizes[ $backup_key ] );

			// Store it in attachment meta
			update_post_meta( $attachment_id, '_wp_attachment_backup_sizes', $backup_sizes );

		}
	}

	global $wpsmush_backup;
	$wpsmush_backup = new WpSmushBackup();

}
