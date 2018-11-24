<?php
/**
 *
 * @package WP_Smush
 * @subpackage CDN
 * @version 2.8.0
 *
 * @author Joel James <joel@incsub.com>
 *
 * @copyright (c) 2018, Incsub (http://incsub.com)
 */
if ( ! class_exists( 'WpSmushCDN' ) ) {

	class WpSmushCDN {

		/**
		 * Smush CDN base url.
		 *
		 * @var null|string
		 */
		var $cdn_base = null;

		/**
		 * Flag to check if CDN is active.
		 *
		 * @var bool
		 */
		var $cdn_active = false;

		/**
		 * WPMUDEV API key for the member.
		 *
		 * @var null
		 */
		var $api_key = null;

		/**
		 * WpSmushCDN constructor.
		 */
		public function __construct() {

			// Set auto resize flag.
			add_action( 'wp', array( $this, 'init_flags' ) );

			// Set Smush API config.
			add_action( 'init', array( $this, 'set_cdn_url' ) );

			// Start an output buffer before any output starts.
			add_action( 'template_redirect', array( $this, 'process_buffer' ), 1 );

			// Hook into CDN settings section.
			add_action( 'smush_cdn_settings_ui', array( $this, 'ui' ) );

			// Add cdn url to dns prefetch.
			add_filter( 'wp_resource_hints', array( $this, 'dns_prefetch' ), 99, 2 );
		}

		/**
		 * Set the API base for the member.
		 *
		 * @return void
		 */
		public function set_cdn_url() {

			// Get the user id of current member.
			// @todo handle this.
			$user_id = 0;

			// Site id to help mapping multisite installations.
			$site_id = get_current_blog_id();

			// This is member's custom cdn path.
			$this->cdn_base = trailingslashit( "https://{$user_id}.smushcdn.com/{$site_id}" );

			// $this->cdn_base = trailingslashit( "http://localhost" );
		}

		/**
		 * Initialize required flags.
		 *
		 * @return void
		 */
		public function init_flags() {
			global $wp_smush;

			// @todo handle this after implementing CDN settings.
			$this->cdn_active = false;

			// All these are members only feature.
			if ( ! $wp_smush->validate_install() ) {
				return;
			}
		}

		/**
		 * Admin UI section for the CDN settings.
		 *
		 * @return void
		 */
		public function ui() {

			global $wpsmush_bulkui;

			echo '<div class="sui-box" id="wp-smush-cdn-wrap-box">';

			// Container header.
			$wpsmush_bulkui->container_header( esc_html__( 'CDN', 'wp-smushit' ) );

			echo '<div class="sui-box-body"></div>';

			echo '</div>';
		}

		/**
		 * Generate CDN url from given image url.
		 *
		 * @param string $src Image url.
		 * @param array  $args Query parameters.
		 *
		 * @return string
		 */
		public function generate_cdn_url( $src, $args = array() ) {
			global $wp_smush;

			// Do not continue incase we try this when cdn is disabled.
			if ( ! $this->cdn_active ) {
				return $src;
			}

			// Parse url to get all parts.
			$url_parts = parse_url( $src );

			// If path not found, do not continue.
			if ( empty( $url_parts['path'] ) ) {
				return $src;
			}

			// Arguments for CDN.
			$pro_args = array(
				'lossy' => $wp_smush->lossy_enabled ? 1 : 0,
				'strip' => $wp_smush->keep_exif ? 0 : 1,
				'webp'  => 0,
			);

			$args = wp_parse_args( $pro_args, $args );

			// Replace base url with cdn base.
			$url = $this->cdn_base . ltrim( $url_parts['path'], '/' );

			// Now we need to add our CDN parameters for resizing.
			$url = add_query_arg( $args, $url );

			return $url;
		}

		/**
		 * Starts an output buffer and register the callback function.
		 *
		 * Register callback function that adds attachment ids of images
		 * those are from media library and has an attachment id.
		 *
		 * @uses ob_start()
		 *
		 * @return void
		 */
		public function process_buffer() {

			ob_start( array( $this, 'process_img_tags' ) );
		}

		/**
		 * Process images from current buffer content.
		 *
		 * Use DOMDocument class to find all available images
		 * in current HTML content and set attachmet id attribute.
		 *
		 * @param string $content Current buffer content.
		 *
		 * @return string
		 */
		public function process_img_tags( $content ) {

			$content  = mb_convert_encoding( $content, 'HTML-ENTITIES', 'UTF-8' );
			$document = new DOMDocument();
			libxml_use_internal_errors( true );
			$document->loadHTML( utf8_decode( $content ) );

			// Get images from current DOM elements.
			$images = $document->getElementsByTagName( 'img' );
			// If images found, set attachment ids.
			if ( ! empty( $images ) ) {

				/**
				 * Action hook to modify DOM images.
				 *
				 * Images are saved at the end of this function. So no need
				 * to return anything in this hook.
				 */
				do_action( 'smush_images_from_content', $images );

				$this->process_images( $images );
			}

			return $document->saveHTML();
		}

		/**
		 * Set attachment IDs of images as data.
		 *
		 * Get attachment ids from urls and set new data
		 * property to img.
		 * We can use WP_Query to find attachment ids of
		 * all images on current page content.
		 *
		 * @param array $images Current page images.
		 *
		 * @return void
		 */
		public function process_images( $images ) {

			$dir = wp_upload_dir();

			// Loop through each image.
			foreach ( $images as $key => $image ) {

				// Get the src value.
				$src = $image->getAttribute( 'src' );

				// Make sure this image is inside upload directory.
				if ( false === strpos( $src, $dir['baseurl'] . '/' ) ) {
					continue;
				}

				/**
				 * Filter to skip a single image from cdn.
				 *
				 * @param bool false Should skip?
				 * @param string $img_url Image url.
				 * @param array|bool $image Image object or false.
				 */
				if ( apply_filters( 'smush_skip_image_from_cdn', false, $src, $image ) ) {
					continue;
				}

				/**
				 * Filter hook to alter image src arguments before going through cdn.
				 *
				 * @param array $args Arguments.
				 * @param string $src Image src.
				 * @param object $image Image tag object.
				 */
				$args = apply_filters( 'smush_image_cdn_args', array(), $image );

				/**
				 * Filter hook to alter image src before going through cdn.
				 *
				 * @param string $src Image src.
				 * @param object $image Image tag object.
				 */
				$src = apply_filters( 'smush_image_src_before_cdn', $src, $image );

				// Do not continue if CDN is not active.
				if ( $this->cdn_active ) {

					// Generate cdn url from local url.
					$src = $this->generate_cdn_url( $src, $args );

					/**
					 * Filter hook to alter image src after replacing with CDN base.
					 *
					 * @param string $src Image src.
					 * @param object $image Image tag object.
					 */
					$src = apply_filters( 'smush_image_src_after_cdn', $src, $image );
				}

				// Update src with cdn url.
				$image->setAttribute( 'src', $src );
			}
		}

		/**
		 * Add CDN url to header for better speed.
		 *
		 * @param array  $urls URLs to print for resource hints.
		 * @param string $relation_type The relation type the URLs are printed.
		 *
		 * @return array
		 */
		public function dns_prefetch( $urls, $relation_type ) {

			// Add only if CDN active.
			if ( 'dns-prefetch' === $relation_type && $this->cdn_active && ! empty( $this->cdn_base ) ) {
				$urls[] = $this->cdn_base;
			}

			return $urls;
		}
	}

	global $wpsmush_cdn;

	$wpsmush_cdn = new WpSmushCDN();
}
