<?php
/**
 * Auto resize functionality: WpSmushAutoResize class
 *
 * @package WP_Smush
 * @subpackage AutoResize
 * @version 2.8.0
 *
 * @author Joel James <joel@incsub.com>
 * @author Anton Vanyukov <anton@incsub.com>
 *
 * @copyright (c) 2018, Incsub (http://incsub.com)
 */

if ( ! class_exists( 'WpSmushAutoResize' ) ) {

	/**
	 * Class WpSmushAutoResize
	 *
	 * Reference: EWWW Optimizer.
	 */
	class WpSmushAutoResize {

		/**
		 * Is auto detection enabled.
		 *
		 * @var bool
		 */
		var $can_auto_detect = false;

		/**
		 * Can auto resize.
		 *
		 * @var bool
		 */
		var $can_auto_resize = false;

		/**
		 * These are the supported file extensions.
		 *
		 * @var array
		 */
		var $supported_extensions = array(
			'gif',
			'jpg',
			'jpeg',
			'png',
		);

		/**
		 * WpSmushAutoResize constructor.
		 */
		public function __construct() {
			// Set auto resize flag.
			add_action( 'wp', array( $this, 'init_flags' ) );

			// Load js file that is required in public facing pages.
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_resize_assets' ) );

			add_action( 'wp_footer', array( $this, 'generate_markup') );

			// Update responsive image srcset if required.
			//add_filter( 'wp_calculate_image_srcset', array( $this, 'update_image_srcset' ), 99, 5 );

			// Update responsive image sizes if required.
			//add_filter( 'wp_calculate_image_sizes', array( $this, 'update_image_sizes' ), 10, 5 );

			// Set a flag to media library images.
			add_action( 'smush_image_src_before_cdn', array( $this, 'set_image_flag' ), 99, 2 );

			// Add resizing arguments to image src.
			//add_filter( 'smush_image_cdn_args', array( $this, 'update_cdn_image_src_args' ), 99, 3 );
		}

		/**
		 * Check if auto resize can be performed.
		 *
		 * Allow only if current user is admin and auto resize
		 * detection is enabled in settings.
		 */
		public function init_flags() {
			/* @var WpSmushSettings $wpsmush_settings */
			global $wpsmush_settings, $wp_smush;//, $wpsmush_cdn;

			$is_pro = $wp_smush->validate_install();

			// All these are members only feature.
			// @todo add other checks if required.
			// if ( $is_pro && $wpsmush_cdn->cdn_active ) {
			if ( $is_pro ) {
				$this->can_auto_resize = true;
			}

			// Only required for admin users.
			if ( (bool) $wpsmush_settings->settings['detection'] && current_user_can( 'manage_options' ) ) {
				$this->can_auto_detect = true;
			}

			// Auto detection is required only for free users.
			if ( ! $is_pro ) {
				// We need smush settings.
				$wpsmush_settings->init_settings();
			}
		}

		/**
		 * Enqueque JS files required in public pages.
		 *
		 * Enque resize detection js and css files to public
		 * facing side of the site. Load only if auto detect
		 * is enabled.
		 *
		 * @return void
		 */
		public function enqueue_resize_assets() {
			// Required only if auto detection is required.
			if ( ! $this->can_auto_detect ) {
				return;
			}

			// Required scripts for front end.
			wp_enqueue_script(
				'smush-resize-detection',
				plugins_url( 'assets/js/resize-detection.min.js', __DIR__ ),
				array( 'jquery' ),
				null,
				true
			);

			// Required styles for front end.
			wp_enqueue_style(
				'smush-resize-detection',
				plugins_url( 'assets/css/resize-detection.min.css', __DIR__ )
			);

			// Define ajaxurl var.
			wp_localize_script(
				'smush-resize-detection', 'wp_smush_resize_vars', array(
					'ajaxurl'     => admin_url( 'admin-ajax.php' ),
					'ajax_nonce'  => wp_create_nonce( 'smush_resize_nonce' ),
					// translators: %s - width, %s - height.
					'large_image' => sprintf( __( 'This image is too large for its container. Adjust the image dimensions to %1$s x %2$spx for optimal results.', 'wp-smushit' ), 'width', 'height' ),
					// translators: %s - width, %s - height.
					'small_image' => sprintf( __( 'This image is too small for its container. Adjust the image dimensions to %1$s x %2$spx for optimal results.', 'wp-smushit' ), 'width', 'height' ),
				)
			);
		}

		/**
		 * Set image flag attribute to img tag.
		 *
		 * In order to highlight images, let's set a flag to
		 * image so that it can be easily detected in front end.
		 *
		 * @param string $src   Image src.
		 * @param object $image Image tag object.
		 *
		 * @return mixed
		 */
		public function set_image_flag( $src, $image ) {
			// No need to add attachment id if auto detection is not enabled.
			if ( ! $this->can_auto_detect ) {
				return $src;
			}

			// Set image flag attribute.
			$image->setAttribute( 'data-smush-image', true );

			return $src;
		}

		/**
		 * Filters an array of image srcset values, replacing each URL with resized CDN urls.
		 *
		 * Keep the existing srcset sizes if already added by WP, then calculate extra sizes
		 * if required.
		 *
		 * @param array  $sources       An array of image urls and widths.
		 * @param array  $size_array    Array of width and height values in pixels.
		 * @param string $image_src     The src of the image.
		 * @param array  $image_meta    The image metadata.
		 * @param int    $attachment_id Image attachment ID.
		 *
		 * @return array $sources
		 */
		public function update_image_srcset( $sources, $size_array, $image_src, $image_meta, $attachment_id = 0 ) {
			/* @var WpSmushCDN $wpsmush_cdn */
			global $wpsmush_cdn;

			$main_image_url = false;

			if ( ! empty( $attachment_id ) ) {
				// Or get from attachment id.
				$url = $main_image_url = wp_get_attachment_url( $attachment_id );
			}

			// Loop through each image.
			foreach ( $sources as $i => $source ) {

				$img_url = $source['url'];
				$args    = array();

				// If don't have attachment id, get original image by removing dimensions from url.
				if ( empty( $url ) ) {
					$url = $this->get_url_without_dimensions( $img_url );
				}

				/**
				 * TODO: Validate image before continue.
				 */

				// Filter already documented in class-wp-smush-cdn.php.
				if ( apply_filters( 'smush_skip_image_from_cdn', false, $img_url, $source ) ) {
					continue;
				}

				list( $width, $height ) = $this->get_size_from_file_name( $img_url );

				// If we got size from url, add them.
				if ( ! empty( $width ) && ! empty( $height ) ) {
					// Set size arg.
					$args = array(
						'size' => $width . ',' . $height,
					);
				}

				// Replace with CDN url.
				$sources[ $i ]['url'] = $wpsmush_cdn->generate_cdn_url( $url, $args );
			}

			// Set additional sizes if required.
			$sources = $this->set_additional_srcset( $sources, $size_array, $main_image_url, $image_meta, $image_src );

			return $sources;
		}

		/**
		 * Filters an array of image srcset values, and add additional values.
		 *
		 * @param array  $sources    An array of image urls and widths.
		 * @param array  $size_array Array of width and height values in pixels.
		 * @param string $url        Image URL.
		 * @param array  $image_meta The image metadata.
		 * @param string $image_src  The src of the image.
		 *
		 * @return array $sources
		 */
		private function set_additional_srcset( $sources, $size_array, $url, $image_meta, $image_src ) {
			/* @var WpSmushCDN $wpsmush_cdn */
			global $wpsmush_cdn;

			$content_width = $this->max_content_width();

			// If url is empty, try to get from src.
			if ( empty( $url ) ) {
				$url = $this->get_url_without_dimensions( $image_src );
			}

			// We need to add additional dimensions.
			$full_width     = $image_meta['width'];
			$full_height    = $image_meta['height'];
			$current_width  = $size_array[0];
			$current_height = $size_array[1];
			// Get width and height calculated by WP.
			list( $constrained_width, $constrained_height ) = wp_constrain_dimensions( $full_width, $full_height, $current_width, $current_height );

			// Calculate base width.
			// If $constrained_width sizes are smaller than current size, set maximum content width.
			if ( abs( $constrained_width - $current_width ) <= 1 && abs( $constrained_height - $current_height ) <= 1 ) {
				$base_width = $content_width ? $content_width : 1900;
			} else {
				$base_width = $current_width;
			}

			$current_widths = array_keys( $sources );
			$new_sources    = array();

			/**
			 * Filter to add/update/bypass additional srcsets.
			 *
			 * If empty value or false is retured, additional srcset
			 * will not be generated.
			 *
			 * @param array|bool $additional_multipliers Additional multipliers.
			 */
			$additional_multipliers = apply_filters( 'smush_srcset_additional_multipliers', array(
				0.2,
				0.4,
				0.6,
				0.8,
				1,
				2,
				3
			) );

			// Continue only if additional multipliers found or not skipped.
			// Filter already documented in class-wp-smush-cdn.php.
			if ( apply_filters( 'smush_skip_image_from_cdn', false, $url, false ) || empty( $additional_multipliers ) ) {
				return $sources;
			}

			// Loop through each multipliers and generate image.
			foreach ( $additional_multipliers as $multiplier ) {
				// New width by multiplying with original size.
				$new_width = intval( $base_width * $multiplier );
				// If a nearly sized image already exist, skip.
				foreach ( $current_widths as $_width ) {
					if ( abs( $_width - $new_width ) < 50 || ( $new_width > $full_width ) ) {
						continue 2;
					}
				}

				// Arguments for cdn url.
				$args = array(
					'size' => $new_width,
				);

				// Add new srcset item.
				$new_sources[ $new_width ] = array(
					'url'        => $wpsmush_cdn->generate_cdn_url( $url, $args ),
					'descriptor' => 'w',
					'value'      => $new_width,
				);
			}

			// Assign new srcset items to existing ones.
			if ( ! empty( $new_sources ) ) {
				// Loop through each items and replace/add.
				foreach ( $new_sources as $_width_key => $_width_values ) {
					$sources[ $_width_key ] = $_width_values;
				}
			}

			return $sources;
		}

		/**
		 * Add resize arguments to content image src.
		 *
		 * @param array  $args  Current arguments.
		 * @param object $image Image tag object from DOM.
		 *
		 * @return array $args
		 */
		public function update_cdn_image_src_args( $args, $image ) {
			// Get registered image sizes.
			$image_sizes = $this->get_image_sizes();

			// Image class.
			$class = $image->getAttribute( 'class' );

			$size = '';

			// Detect WP registered image size from HTML class.
			if ( preg_match( '#size-([^"\'\s]+)[^"\']*["|\']?#i', $class, $size ) ) {
				$size = array_pop( $size );

				// If this size exists in registered sizes, add argument.
				if ( 'full' !== $size && array_key_exists( $size, $image_sizes ) ) {
					$args['size'] = (int) $image_sizes[ $size ]['width'] . ',' . (int) $image_sizes[ $size ]['height'];
				}
			}

			return $args;
		}

		/**
		 * Update image sizes for responsive size.
		 *
		 * @param string $sizes A source size value for use in a 'sizes' attribute.
		 * @param array  $size  Requested size.
		 *
		 * @return string
		 */
		public function update_image_sizes( $sizes, $size ) {
			// Get maximum content width.
			$content_width = $this->max_content_width();

			// If content width is empty, set 1900.
			if ( empty( $content_width ) ) {
				$content_width = 1900;
			}

			if ( ( is_array( $size ) && $size[0] <= $content_width ) ) {
				return $sizes;
			}

			return sprintf( '(max-width: %1$dpx) 100vw, %1$dpx', $content_width );
		}

		/**
		 * Try to determine height and width from strings WP appends to resized image filenames.
		 *
		 * @param string $src The image URL.
		 *
		 * @return array An array consisting of width and height.
		 */
		private function get_size_from_file_name( $src ) {
			$size = array();

			// Using regex to get image size from file name.
			if ( preg_match( '#-(\d+)x(\d+)(@2x)?\.(?:' . implode( '|', $this->supported_extensions ) . '){1}(?:\?.+)?$#i', $src, $size ) ) {
				// Get size and width.
				$width  = (int) isset( $size[1] ) ? $size[1] : 0;
				$height = (int) isset( $size[2] ) ? $size[2] : 0;

				// Handle retina images.
				if ( strpos( $src, '@2x' ) ) {
					$width  = 2 * $width;
					$height = 2 * $height;
				}

				// Return width and height as array.
				if ( $width && $height ) {
					return array( $width, $height );
				}
			}

			return array( false, false );
		}

		/**
		 * Get full size image url from resized one.
		 *
		 * @param string $src Image URL.
		 *
		 * @return string
		 **/
		private function get_url_without_dimensions( $src ) {
			// Build URL, first removing WP's resized string so we pass the original image to ExactDN.
			if ( preg_match( '#(-\d+x\d+)\.(' . implode( '|', $this->supported_extensions ) . '){1}(?:\?.+)?$#i', $src, $src_parts ) ) {

				$orginal_src = str_replace( $src_parts[1], '', $src );

				// Upload directory.
				$upload_dir = wp_get_upload_dir();
				// Extracts the file path to the image minus the base url.
				$file_path = substr( $orginal_src, strlen( $upload_dir['baseurl'] ) );
				// Continue only if the file exists.
				if ( file_exists( $upload_dir['basedir'] . $file_path ) ) {
					$src = $orginal_src;
				}
			}

			return $src;
		}

		/**
		 * Get $content_width global var value.
		 *
		 * @return bool|string
		 */
		private function max_content_width() {
			// Get global content width.
			$content_width = isset( $GLOBALS['content_width'] ) ? $GLOBALS['content_width'] : false;

			/**
			 * Filter the content width value.
			 *
			 * @param string $content_width Global content width.
			 */
			return apply_filters( 'smush_max_content_width', $content_width );
		}

		/**
		 * Get registered image sizes and its sizes.
		 *
		 * Custom function to get all registered image sizes
		 * and their width and height.
		 *
		 * @return array|bool|mixed
		 */
		private function get_image_sizes() {
			// Get from cache if available to avoid duplicate looping.
			$sizes = wp_cache_get( 'get_image_sizes', 'smush_image_sizes' );
			if ( $sizes ) {
				return $sizes;
			}

			// Get additional sizes registered by themes.
			global $_wp_additional_image_sizes;

			$sizes = array();

			// Get intermediate image sizes.
			$get_intermediate_image_sizes = get_intermediate_image_sizes();

			// Create the full array with sizes and crop info.
			foreach ( $get_intermediate_image_sizes as $_size ) {
				if ( in_array( $_size, array( 'thumbnail', 'medium', 'large' ), true ) ) {
					$sizes[ $_size ]['width']  = get_option( $_size . '_size_w' );
					$sizes[ $_size ]['height'] = get_option( $_size . '_size_h' );
					$sizes[ $_size ]['crop']   = (bool) get_option( $_size . '_crop' );
				} elseif ( isset( $_wp_additional_image_sizes[ $_size ] ) ) {
					$sizes[ $_size ] = array(
						'width'  => $_wp_additional_image_sizes[ $_size ]['width'],
						'height' => $_wp_additional_image_sizes[ $_size ]['height'],
						'crop'   => $_wp_additional_image_sizes[ $_size ]['crop'],
					);
				}
			}

			// Set cache to avoid this loop next time.
			wp_cache_set( 'get_image_sizes', $sizes, 'smush_image_sizes' );

			return $sizes;
		}

		/**
		 * Generate markup for the template engine.
		 *
		 * @since 2.9
		 */
		public function generate_markup() {
			// Required only if auto detection is required.
			if ( ! $this->can_auto_detect ) {
				return;
			}
			?>
			<div id="smush-image-bar-toggle" class="closed">
				<i class="sui-icon-info" aria-hidden="true"></i>
			</div>
			<div id="smush-image-bar" class="closed">
				<h3><?php esc_html_e( 'Image Issues', 'wp-smushit' ); ?></h3>
				<p>
					<?php esc_html_e( 'The images listed below are being resized to fit a container. To avoid serving oversized or blurry image, try to match the images to their container sizes.', 'wp-smushit' ); ?>
				</p>

				<div id="smush-image-bar-items-bigger">
					<strong><?php esc_html_e( 'Oversized', 'wp-smushit' ); ?></strong>
				</div>
				<div id="smush-image-bar-items-smaller">
					<strong><?php esc_html_e( 'Under', 'wp-smushit' ); ?></strong>
				</div>
				<p>
					<?php esc_html_e( 'Note: It’s not always easy to make this happen, fix up what you can.', 'wp-smushit' ); ?>
				</p>
			</div>
			<?php
		}

	}

	global $wpsmush_auto_resize;
	$wpsmush_auto_resize = new WpSmushAutoResize();
} // End if().
