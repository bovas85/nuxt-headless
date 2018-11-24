<?php
/**
 * PNG to JPG conversion: WpSmushPngtoJpg class
 *
 * @package WP_Smush
 *
 * @version 2.4
 *
 * @author Umesh Kumar <umesh@incsub.com>
 *
 * @copyright (c) 2016, Incsub (http://incsub.com)
 */
if ( ! class_exists( 'WpSmushPngtoJpg' ) ) {

	class WpSmushPngtoJpg {

		var $is_transparent = false;

		/**
		 * Check if Imagick is available or not
		 *
		 * @return bool True/False Whether Imagick is available or not
		 */
		function supports_imagick() {
			if ( ! class_exists( 'Imagick' ) ) {
				return false;
			}

			return true;
		}

		/**
		 * Check if GD is loaded
		 *
		 * @return bool True/False Whether GD is available or not
		 */
		function supports_GD() {
			if ( ! function_exists( 'gd_info' ) ) {
				return false;
			}

			return true;
		}

		/**
		 * Checks if the Given PNG file is transparent or not
		 *
		 * @param string $id Attachment id
		 * @param string $file
		 *
		 * @return bool|int
		 */
		function is_transparent( $id = '', $file = '' ) {

			global $wpsmush_helper;

			// No attachment id/ file path, return
			if ( empty( $id ) && empty( $file ) ) {
				return false;
			}

			if ( empty( $file ) ) {
				$file = $wpsmush_helper->get_attached_file( $id );
			}

			// Check if File exists
			if ( empty( $file ) || ! file_exists( $file ) ) {
				return false;
			}

			$transparent = '';

			// Try to get transparency using Imagick
			if ( $this->supports_imagick() ) {
				try {
					$im = new Imagick( $file );

					return $im->getImageAlphaChannel();
				} catch ( Exception $e ) {
					error_log( 'Imagick: Error in checking PNG transparency ' . $e->getMessage() );
				}
			} else {
				// Simple check
				// Src: http://camendesign.com/code/uth1_is-png-32bit
				if ( ord( file_get_contents( $file, false, null, 25, 1 ) ) & 4 ) {
					return true;
				}
				// Src: http://www.jonefox.com/blog/2011/04/15/how-to-detect-transparency-in-png-images/
				$contents = file_get_contents( $file );
				if ( stripos( $contents, 'PLTE' ) !== false && stripos( $contents, 'tRNS' ) !== false ) {
					return true;
				}

				// If both the conditions failed, that means not transparent
				return false;

			}

			// If Imagick is installed, and the code exited due to some error
			// Src: StackOverflow
			if ( empty( $transparent ) && $this->supports_GD() ) {
				// Check for transparency using GD
				$i       = imagecreatefrompng( $file );
				$palette = ( imagecolortransparent( $i ) < 0 );
				if ( $palette ) {
					return true;
				}
			}

			return false;

		}

		/**
		 * Check whether to convert the PNG to JPG or not
		 *
		 * @param $id Attachment ID
		 * @param $file File path for the attachment
		 *
		 * @return bool Whether to convert the PNG or not
		 */
		function should_convert( $id, $file ) {

			global $wpsmush_settings;

			$should_convert = false;

			// Get the Transparency conversion settings
			$convert_png = $wpsmush_settings->settings['png_to_jpg'];

			if ( ! $convert_png ) {
				return $should_convert;
			}

			// Whether to convert transparent images or not
			$transparent_settings = $wpsmush_settings->get_setting( WP_SMUSH_PREFIX . 'transparent_png', false );

			$convert_transparent = $transparent_settings['convert'];

			/** Transparency Check */
			$this->is_transparent = $this->is_transparent( $id, $file );

			// If we are suppose to convert transaprent images, skip is transparent check
			if ( $convert_transparent || ! $this->is_transparent ) {
				$should_convert = true;
			}

			return $should_convert;
		}

		/**
		 * Check if given attachment id can be converted to JPEG or not
		 *
		 * @param string $id Atachment id
		 *
		 * @param string $id
		 * @param string $size
		 * @param string $mime
		 * @param string $file
		 *
		 * @return bool True/False Can be converted or not
		 */
		function can_be_converted( $id = '', $size = 'full', $mime = '', $file = '' ) {

			if ( empty( $id ) ) {
				return false;
			}

			// False if not a PNG
			$mime = empty( $mime ) ? get_post_mime_type( $id ) : $mime;
			if ( 'image/png' != $mime && 'image/x-png' != $mime ) {
				return false;
			}

			/** Return if Imagick and GD is not available */
			if ( ! $this->supports_imagick() && ! $this->supports_GD() ) {
				return false;
			}

			// If already tried the conversion
			if ( get_post_meta( $id, WP_SMUSH_PREFIX . 'pngjpg_savings', true ) ) {
				return false;
			}

			// Check if registered size is supposed to be converted or not
			global $wpsmushit_admin, $wpsmush_helper;
			if ( 'full' != $size && $wpsmushit_admin->skip_image_size( $size ) ) {
				return false;
			}

			if ( empty( $file ) ) {
				$file = $wpsmush_helper->get_attached_file( $id );
			}

			/** Whether to convert to jpg or not */
			$should_convert = $this->should_convert( $id, $file );

			/**
			 * Filter whether to convert the PNG to JPG or not
			 *
			 * @since 2.4
			 *
			 * @param bool $should_convert Current choice for image conversion
			 *
			 * @param int $id Attachment id
			 *
			 * @param string $file File path for the image
			 *
			 * @param string $size Image size being converted
			 */
			$should_convert = apply_filters( 'wp_smush_convert_to_jpg', $should_convert, $id, $file, $size );

			return $should_convert;

		}

		/**
		 * Update the image URL, MIME Type, Attached File, file path in Meta, URL in post content
		 *
		 * @param $id Attachment ID
		 * @param $o_file Original File Path that has to be replaced
		 * @param $n_file New File Path which replaces the old file
		 * @param $meta Attachment Meta
		 * @param $size_k Image Size
		 * @param $o_type Operation Type "conversion", "restore"
		 *
		 * @return mixed Attachment Meta with updated file path
		 */
		function update_image_path( $id, $o_file, $n_file, $meta, $size_k, $o_type = 'conversion' ) {

			global $wpsmush_settings;

			// Upload Directory
			$upload_dir = wp_upload_dir();

			// Upload Path
			$upload_path = trailingslashit( $upload_dir['basedir'] );

			$dir_name = pathinfo( $o_file, PATHINFO_DIRNAME );

			// Full Path to new file
			$n_file_path = path_join( $dir_name, $n_file );

			// Current URL for image
			$o_url = wp_get_attachment_url( $id );

			// Update URL for image size
			if ( 'full' != $size_k ) {
				$base_url = dirname( $o_url );
				$o_url    = $base_url . '/' . basename( $o_file );
			}

			// Update File path, Attached File, GUID
			$meta = empty( $meta ) ? wp_get_attachment_metadata( $id ) : $meta;

			// Get the File mime
			if ( class_exists( 'finfo' ) ) {
				$finfo = new finfo( FILEINFO_MIME_TYPE );
			} else {
				$finfo = false;
			}

			if ( $finfo ) {
				$mime = file_exists( $n_file_path ) ? $finfo->file( $n_file_path ) : '';
			} elseif ( function_exists( 'mime_content_type' ) ) {
				$mime = mime_content_type( $n_file_path );
			} else {
				$mime = false;
			}

			// Update File Path, Attached file, Mime Type for Image
			if ( 'full' == $size_k ) {
				if ( ! empty( $meta ) ) {
					$new_file     = str_replace( $upload_path, '', $n_file_path );
					$meta['file'] = $new_file;
				}
				// Update Attached File
				update_attached_file( $id, $meta['file'] );

				// Update Mime type
				wp_update_post(
					array(
						'ID'             => $id,
						'post_mime_type' => $mime,
					)
				);
			} else {
				$meta['sizes'][ $size_k ]['file']      = basename( $n_file );
				$meta['sizes'][ $size_k ]['mime-type'] = $mime;
			}

			// To be called after the attached file key is updated for the image
			$this->update_image_url( $id, $size_k, $n_file, $o_url );

			// Delete the Original files if backup not enabled
			if ( 'conversion' == $o_type && ! $wpsmush_settings->settings['backup'] ) {
				@unlink( $o_file );
			}

			return $meta;
		}

		function update_stats( $id = '', $savings = '' ) {
			if ( empty( $id ) || empty( $savings ) ) {
				return false;
			}

		}

		/**
		 * Replace the file if there are savings, and return savings
		 *
		 * @param string $file Original File Path
		 * @param array  $result Array structure
		 * @param string $n_file Updated File path
		 *
		 * @return array
		 */
		function replace_file( $file = '', $result = array(), $n_file = '' ) {

			if ( empty( $file ) || empty( $n_file ) ) {
				return $result;
			}

			// Get the file size of original image
			$o_file_size = filesize( $file );

			$n_file = path_join( dirname( $file ), $n_file );

			$n_file_size = filesize( $n_file );

			// If there aren't any savings return
			if ( $n_file_size >= $o_file_size ) {
				// Delete the JPG image and return
				@unlink( $n_file );

				return $result;
			}

			// Get the savings
			$savings = $o_file_size - $n_file_size;

			// Store Stats
			$savings = array(
				'bytes'       => $savings,
				'size_before' => $o_file_size,
				'size_after'  => $n_file_size,
			);

			$result['savings'] = $savings;

			return $result;
		}

		/**
		 * Perform the conversion process, using WordPress Image Editor API
		 *
		 * @param $id Attachment Id
		 * @param $file Attachment File path
		 * @param $meta Attachment meta
		 * @param $size Image size, default empty for full image
		 *
		 * @return array $result array(
		 *  'meta'  => array Update Attachment metadata
		 *  'savings'   => Reduction of Image size in bytes
		 * )
		 */
		function convert_to_jpg( $id = '', $file = '', $meta = '', $size = 'full' ) {

			$result = array(
				'meta'    => $meta,
				'savings' => '',
			);

			// Flag: Whether the image was converted or not
			if ( 'full' == $size ) {
				$result['converted'] = false;
			}

			// If any of the values is not set
			if ( empty( $id ) || empty( $file ) || empty( $meta ) ) {
				return $result;
			}

			$editor = wp_get_image_editor( $file );

			if ( is_wp_error( $editor ) ) {
				// Use custom method maybe
				return $result;
			}

			$n_file = pathinfo( $file );

			if ( ! empty( $n_file['filename'] ) && $n_file['dirname'] ) {
				// Get a unique File name
				$n_file['filename'] = wp_unique_filename( $n_file['dirname'], $n_file['filename'] . '.jpg' );
				$n_file             = path_join( $n_file['dirname'], $n_file['filename'] );
			} else {
				return $result;
			}

			// Save PNG as JPG
			$new_image_info = $editor->save( $n_file, 'image/jpeg' );

			// If image editor was unable to save the image, return
			if ( is_wp_error( $new_image_info ) ) {
				return $result;
			}

			$n_file = ! empty( $new_image_info ) ? $new_image_info['file'] : '';

			// Replace file, and get savings
			$result = $this->replace_file( $file, $result, $n_file );

			if ( ! empty( $result['savings'] ) ) {
				if ( 'full' == $size ) {
					$result['converted'] = true;
				}
				// Update the File Details. and get updated meta
				$result['meta'] = $this->update_image_path( $id, $file, $n_file, $meta, $size );

				/**
				 *  Perform a action after the image URL is updated in post content
				 */
				do_action( 'wp_smush_image_url_changed', $id, $file, $n_file, $size );
			}

			return $result;
		}

		/**
		 * Convert a PNG to JPG, Lossless Conversion, if we have any savings
		 *
		 * @param string $id
		 * @param string $meta
		 *
		 * @uses WpSmushBackup::add_to_image_backup_sizes()
		 *
		 * @return mixed|string
		 *
		 * @todo: Save cummulative savings
		 */
		function png_to_jpg( $id = '', $meta = '' ) {
			global $wpsmush_backup, $wp_smush;

			// If we don't have meta or ID, or if not a premium user.
			if ( empty( $id ) || empty( $meta ) || ! $wp_smush->validate_install() ) {
				return $meta;
			}

			global $wpsmush_helper;

			$file = $wpsmush_helper->get_attached_file( $id );

			/** Whether to convert to jpg or not */
			$should_convert = $this->can_be_converted( $id );

			if ( ! $should_convert ) {
				return $meta;
			}

			$result['meta'] = $meta;

			if ( ! $this->is_transparent ) {
				// Perform the conversion, and update path
				$result = $this->convert_to_jpg( $id, $file, $result['meta'] );
			} else {
				$result = $this->convert_tpng_to_jpg( $id, $file, $result['meta'] );
			}

			$savings['full'] = ! empty( $result['savings'] ) ? $result['savings'] : '';

			// If original image was converted and other sizes are there for the image, Convert all other image sizes
			if ( $result['converted'] ) {
				if ( ! empty( $meta['sizes'] ) ) {
					foreach ( $meta['sizes'] as $size_k => $data ) {

						$s_file = path_join( dirname( $file ), $data['file'] );

						/** Whether to convert to jpg or not */
						$should_convert = $this->can_be_converted( $id, $size_k, 'image/png', $s_file );

						// Perform the conversion
						if ( ! $should_convert ) {
							continue;
						}

						// Perform the conversion, and update path
						if ( ! $this->is_transparent ) {
							// Perform the conversion, and update path
							$result = $this->convert_to_jpg( $id, $s_file, $result['meta'], $size_k );
						} else {
							$result = $this->convert_tpng_to_jpg( $id, $s_file, $result['meta'], $size_k );
						}
						if ( ! empty( $result['savings'] ) ) {
							$savings[ $size_k ] = $result['savings'];
						}
					}
				}

				// Save the original File URL
				$o_file = ! empty( $file ) ? $file : get_post_meta( $id, '_wp_attached_file', true );
				$wpsmush_backup->add_to_image_backup_sizes( $id, $o_file, 'smush_png_path' );

				/**
				 * Do action, if the PNG to JPG conversion was successful
				 */
				do_action( 'wp_smush_png_jpg_converted', $id, $meta, $savings );
			}

			// Update the Final Stats
			update_post_meta( $id, WP_SMUSH_PREFIX . 'pngjpg_savings', $savings );

			return $result['meta'];

		}

		/**
		 * Convert a transparent PNG to JPG, with specified background color
		 *
		 * @param string $id Attachment ID
		 * @param string $file File Path Original Image
		 * @param string $meta Attachment Meta
		 * @param string $size Image size. set to 'full' by default
		 *
		 * @return array Savings and Updated Meta
		 */
		function convert_tpng_to_jpg( $id = '', $file = '', $meta = '', $size = 'full' ) {

			global $wpsmush_settings;

			$result = array(
				'meta'    => $meta,
				'savings' => '',
			);

			// Flag: Whether the image was converted or not
			if ( 'full' == $size ) {
				$result['converted'] = false;
			}

			// If any of the values is not set
			if ( empty( $id ) || empty( $file ) || empty( $meta ) ) {
				return $result;
			}

			// Get the File name without ext
			$n_file = pathinfo( $file );

			if ( empty( $n_file['dirname'] ) || empty( $n_file['filename'] ) ) {
				return $result;
			}

			$n_file['filename'] = wp_unique_filename( $n_file['dirname'], $n_file['filename'] . '.jpg' );

			// Updated File name
			$n_file = path_join( $n_file['dirname'], $n_file['filename'] );

			$transparent_png = $wpsmush_settings->get_setting( WP_SMUSH_PREFIX . 'transparent_png' );

			/**
			 * Filter Background Color for Transparent PNGs
			 */
			$bg = apply_filters( 'wp_smush_bg', $transparent_png['background'], $id, $size );

			$quality = $this->get_quality( $file );

			if ( $this->supports_imagick() ) {
				try {
					$imagick = new Imagick( $file );
					$imagick->setImageBackgroundColor( new ImagickPixel( '#' . $bg ) );
					$imagick->setImageAlphaChannel( 11 );
					$imagick->setImageFormat( 'JPG' );
					$imagick->setCompressionQuality( $quality );
					$imagick->writeImage( $n_file );
				} catch ( Exception $e ) {
					error_log( 'WP Smush PNG to JPG Conversion error in ' . __FILE__ . ' at ' . __LINE__ . ' ' . $e->getMessage() );

					return $result;
				}
			} else {
				// Use GD for conversion
				// Get data from PNG
				$input = imagecreatefrompng( $file );

				// Width and Height of image
				list( $width, $height ) = getimagesize( $file );

				// Create New image
				$output = imagecreatetruecolor( $width, $height );

				// set background color for GD
				$r = hexdec( '0x' . strtoupper( substr( $bg, 0, 2 ) ) );
				$g = hexdec( '0x' . strtoupper( substr( $bg, 2, 2 ) ) );
				$b = hexdec( '0x' . strtoupper( substr( $bg, 4, 2 ) ) );

				// Set the Background color
				$rgb = imagecolorallocate( $output, $r, $g, $b );

				// Fill Background
				imagefilledrectangle( $output, 0, 0, $width, $height, $rgb );

				// Create New image
				imagecopy( $output, $input, 0, 0, 0, 0, $width, $height );

				// Create JPG
				imagejpeg( $output, $n_file, $quality );
			}

			// Replace file, and get savings
			$result = $this->replace_file( $file, $result, $n_file );

			if ( ! empty( $result['savings'] ) ) {
				if ( 'full' == $size ) {
					$result['converted'] = true;
				}
				// Update the File Details. and get updated meta
				$result['meta'] = $this->update_image_path( $id, $file, $n_file, $meta, $size );

				/**
				 *  Perform a action after the image URL is updated in post content
				 */
				do_action( 'wp_smush_image_url_changed', $id, $file, $n_file, $size );
			}

			return $result;
		}

		/**
		 * Get JPG quality from WordPress Image Editor
		 *
		 * @param $file
		 *
		 * @return int Quality for JPEG images
		 */
		function get_quality( $file ) {
			if ( empty( $file ) ) {
				return 82;
			}
			$editor = wp_get_image_editor( $file );

			if ( ! is_wp_error( $editor ) ) {

				$quality = $editor->get_quality();
			}

			// Choose the default quaity if we didn't get it
			if ( ! $quality || $quality < 1 || $quality > 100 ) {
				// The default quality
				$quality = 82;
			}

			return $quality;

		}

		/**
		 * Check whether the given attachment was converted from PNG to JPG
		 *
		 * @param string $id
		 *
		 * @return bool True/False Whether the image was converted from PNG or not
		 */
		function is_converted( $id = '' ) {
			if ( empty( $id ) ) {
				return false;
			}
			// Get the original file path and check if it exists
			$original_file = get_post_meta( $id, WP_SMUSH_PREFIX . 'original_file', true );

			// If original file path is not stored, then it wasn't converted or was restored to original
			if ( empty( $original_file ) ) {
				return false;
			}
			// Upload Directory
			$upload_dir = wp_upload_dir();

			// Upload Path
			$upload_path = trailingslashit( $upload_dir['basedir'] );

			// If file exists return true
			if ( file_exists( path_join( $upload_path, $original_file ) ) ) {
				return true;
			}

			return false;
		}

		/**
		 * Update Image URL in post content
		 *
		 * @param $id
		 * @param $size_k
		 * @param $n_file
		 * @param $o_url
		 */
		function update_image_url( $id, $size_k, $n_file, $o_url ) {
			if ( 'full' == $size_k ) {
				// Get the updated image URL
				$n_url = wp_get_attachment_url( $id );
			} else {
				$n_url = trailingslashit( dirname( $o_url ) ) . basename( $n_file );
			}

			// Update In Post Content, Loop Over a set of posts to avoid the query failure for large sites
			global $wpdb;
			// Get existing Images with current URL
			$query = $wpdb->prepare(
				"SELECT ID, post_content FROM $wpdb->posts WHERE post_content LIKE '%%%s%%'", $o_url
			);

			$rows = $wpdb->get_results( $query, ARRAY_A );

			// Iterate over rows to update post content
			if ( ! empty( $rows ) && is_array( $rows ) ) {
				foreach ( $rows as $row ) {
					// replace old URLs with new URLs.
					$post_content = $row['post_content'];
					$post_content = str_replace( $o_url, $n_url, $post_content );
					// Update Post content
					$wpdb->update(
						$wpdb->posts,
						array(
							'post_content' => $post_content,
						),
						array(
							'ID' => $row['ID'],
						)
					);
				}
			}
		}
	}

	global $wpsmush_pngjpg;
	$wpsmush_pngjpg = new WpSmushPngtoJpg();
}
