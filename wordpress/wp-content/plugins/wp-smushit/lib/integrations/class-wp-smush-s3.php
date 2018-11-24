<?php
/**
 * S3 integration: WpSmushS3 class
 *
 * @package WP_Smush
 * @subpackage S3
 * @version 2.7
 *
 * @author Umesh Kumar <umesh@incsub.com>
 *
 * @copyright (c) 2017, Incsub (http://incsub.com)
 */

if ( ! class_exists( 'WpSmushS3' ) ) {

	/**
	 * Class WpSmushS3
	 */
	class WpSmushS3 {

		/**
		 * Module slug.
		 *
		 * @since 2.8.1
		 *
		 * @var string $module
		 */
		private $module = 's3';

		/**
		 * WpSmushS3 constructor.
		 */
		function __construct() {
			add_action( 'admin_init', array( $this, 'init' ), 5 );

			// Hook at the end of setting row to output a error div.
			add_action( 'smush_setting_column_right_inside', array( $this, 's3_setup_message' ) );
		}

		/**
		 * Init actions
		 */
		function init() {
			global $wp_smush;

			// Filters the setting variable to add S3 setting title and description.
			add_filter( 'wp_smush_settings', array( $this, 'register' ), 6 );

			// Filters the setting variable to add S3 setting in premium features.
			add_filter( 'wp_smush_integration_settings', array( $this, 'add_setting' ), 1 );

			// Disable setting.
			add_filter( 'wp_smush_integration_status_' . $this->module, array( $this, 'setting_status' ) );

			// Return if not a pro user or the S3 plugin is not installed.
			if ( ! $wp_smush->validate_install() || ( ! class_exists( 'Amazon_S3_And_CloudFront' ) && ! class_exists( 'Amazon_S3_And_CloudFront_Pro' ) ) ) {
				return;
			}

			// Check if the file exists for the given path and download.
			add_action( 'smush_file_exists', array( $this, 'maybe_download_file' ), 10, 3 );

			// Check if the backup file exists.
			add_filter( 'smush_backup_exists', array( $this, 'backup_exists_on_s3' ), 10, 3 );

			add_action( 'smush_setting_column_right_inside', array( $this, 'additional_notice' ) );

			// Show submit button when a pro user and the S3 plugin is installed.
			add_filter( 'wp_smush_integration_show_submit', '__return_true' );
		}

		/**
		 * Filters the setting variable to add S3 setting title and description.
		 *
		 * @param array $settings  Settings array.
		 *
		 * @return mixed
		 */
		function register( $settings ) {
			$plugin_url                = esc_url( 'https://wordpress.org/plugins/amazon-s3-and-cloudfront/' );
			$settings[ $this->module ] = array(
				'label'       => esc_html__( 'Enable Amazon S3 support', 'wp-smushit' ),
				'short_label' => esc_html__( 'Amazon S3', 'wp-smushit' ),
				'desc'        => sprintf(
					esc_html__( "Storing your image on S3 buckets using %1\$sWP Offload S3%2\$s? Smush can detect and smush those assets for you, including when you're removing files from your host server.", 'wp-smushit' ),
					"<a href='" . $plugin_url . "' target = '_blank'>",
					'</a>',
					'<b>',
					'</b>'
				),
			);

			return $settings;
		}

		/**
		 * Append S3 in PRO feature list
		 *
		 * @param array $pro_settings  Pro settings.
		 *
		 * @return array
		 */
		function add_setting( $pro_settings ) {
			if ( ! isset( $pro_settings[ $this->module ] ) ) {
				$pro_settings[] = $this->module;
			}

			return $pro_settings;
		}

		/**
		 * Prints the message for S3 setup
		 *
		 * @param string $setting_key  Settings key.
		 *
		 * @return null
		 */
		function s3_setup_message( $setting_key ) {
			// Return if not S3.
			if ( $this->module !== $setting_key ) {
				return;
			}

			global $as3cf, $wp_smush, $wpsmush_settings;

			$is_pro = $wp_smush->validate_install();

			// If S3 integration is not enabled, return.
			$setting_val = $is_pro ? $wpsmush_settings->settings[ $this->module ] : 0;

			// If integration is disabled when S3 offload is active, do not continue.
			if ( ! $setting_val && is_object( $as3cf ) ) {
				return;
			}

			$class = $message = '';

			// If S3 offlocad global variable is not available, plugin is not active.
			if ( ! is_object( $as3cf ) ) {
				$message = __( 'To use this feature you need to install WP Offload S3 and have an Amazon S3 account setup.', 'wp-smushit' );
			} elseif ( ! method_exists( $as3cf, 'is_plugin_setup' ) ) {
				// Check if in case for some reason, we couldn't find the required function.
				$class       = ' sui-notice-warning';
				$support_url = esc_url( 'https://premium.wpmudev.org/contact' );
				$message     = sprintf( esc_html__( 'We are having trouble interacting with WP Offload S3, make sure the plugin is activated. Or you can %1$sreport a bug%2$s.', 'wp-smushit' ), '<a href="' . $support_url . '" target="_blank">', '</a>' );
			} elseif ( ! $as3cf->is_plugin_setup() ) {
				// Plugin is not setup, or some information is missing.
				$class         = ' sui-notice-warning';
				$configure_url = $as3cf->get_plugin_page_url();
				$message       = sprintf( esc_html__( 'It seems you haven’t finished setting up WP Offload S3 yet. %1$sConfigure it now%2$s to enable Amazon S3 support.', 'wp-smushit' ), '<a href="' . $configure_url . '" target="_blank">', '</a>' );
			} else {
				// S3 support is active.
				$class   = ' sui-notice-info';
				$message = __( 'Amazon S3 support is active.', 'wp-smushit' );
			}

			// Return early if we don't need to do anything.
			if ( empty( $message ) || ! $is_pro ) {
				return;
			}

			echo '<div class="sui-notice' . $class . ' smush-notice-sm"><p>' . $message . '</p></div>';
		}

		/**
		 * Show additional notice if the required plugins are not istalled.
		 *
		 * @since 2.8.0
		 *
		 * @param string $name  Setting name.
		 */
		public static function additional_notice( $name ) {
			// If we don't have free or pro version for WP Offload S3, return.
			if ( 's3' === $name && ! class_exists( 'Amazon_S3_And_CloudFront' ) && ! class_exists( 'Amazon_S3_And_CloudFront_Pro' ) ) { ?>
				<div class="sui-notice sui-notice-sm">
					<p>
						<?php esc_html_e( 'To use this feature you need to install WP Offload S3 and have an Amazon S3 account setup.', 'wp-smushit' ); ?>
					</p>
				</div>
				<?php
			}
		}

		/**
		 * Error message to show when S3 support is required.
		 *
		 * Show a error message to admins, if they need to enable S3 support. If "remove files from
		 * server" option is enabled in WP Offload S3 plugin, we need WP Smush Pro to enable S3 support.
		 *
		 * @return mixed
		 */
		function s3_support_required_notice() {

			global $wpsmushit_admin, $wpsmush_settings;

			// Do not display it for other users.
			// Do not display on network screens, if networkwide option is disabled.
			if ( ! current_user_can( 'manage_options' ) || ( is_network_admin() && ! $wpsmush_settings->settings['networkwide'] ) ) {
				return true;
			}

			// Do not display the notice on Bulk Smush Screen.
			global $current_screen;
			if ( ! empty( $current_screen->base ) && 'toplevel_page_smush' != $current_screen->base && 'gallery_page_wp-smush-nextgen-bulk' != $current_screen->base && 'toplevel_page_smush-network' != $current_screen->base ) {
				return true;
			}

			// If already dismissed, do not show.
			if ( 1 == get_site_option( 'wp-smush-hide_s3support_alert' ) ) {
				return true;
			}

			// Return early, if support is not required.
			if ( ! $this->s3_support_required() ) {
				return true;
			}

			// Settings link.
			$settings_link = is_multisite() && is_network_admin() ? network_admin_url( 'admin.php?page=smush' ) : menu_page_url( 'smush', false );

			if ( $wpsmushit_admin->validate_install() ) {
				// If premium user, but S3 support is not enabled.
				$message = sprintf( __( "We can see you have WP Offload S3 installed with the <strong>Remove Files From Server</strong> option activated. If you want to optimize your S3 images you'll need to enable the <a href='%s'><strong>Amazon S3 Support</strong></a> feature in Smush's settings.", 'wp-smushit' ), $settings_link );
			} else {
				// If not a premium user.
				$message = sprintf( __( "We can see you have WP Offload S3 installed with the <strong>Remove Files From Server</strong> option activated. If you want to optimize your S3 images you'll need to <a href='%s'><strong>upgrade to Smush Pro</strong></a>", 'wp-smushit' ), esc_url( 'https://premium.wpmudev.org/project/wp-smush-pro' ) );
			}

			echo '<div class="sui-notice sui-notice-warning wp-smush-s3support-alert"><p>' . $message . '</p><span class="sui-notice-dismiss"><a href="#">' . esc_html__( 'Dismiss', 'wp-smushit' ) . '</a></span></div>';
		}

		/**
		 * Check if S3 support is required for Smush.
		 *
		 * @return bool
		 */
		function s3_support_required() {

			global $wpsmush_settings, $wpsmushit_admin, $as3cf;

			// Check if S3 offload plugin is active and delete file from server option is enabled.
			if ( ! is_object( $as3cf ) || ! method_exists( $as3cf, 'get_setting' ) || ! $as3cf->get_setting( 'remove-local-file' ) ) {
				return false;
			}

			// If not Pro user or S3 support is disabled.
			return ( ! $wpsmushit_admin->validate_install() || ! $wpsmush_settings->settings[ $this->module ] );
		}

		/**
		 * Checks if the given attachment is on S3 or not, Returns S3 URL or WP Error
		 *
		 * @param $attachment_id
		 *
		 * @return bool|false|string
		 */
		function is_image_on_s3( $attachment_id = '' ) {
			global $as3cf;
			if ( empty( $attachment_id ) || ! is_object( $as3cf ) ) {
				return false;
			}

			//If we only have the attachment id
			$full_url = $as3cf->is_attachment_served_by_provider( $attachment_id, true );
			//If the filepath contains S3, get the s3 URL for the file
			if ( ! empty( $full_url ) ) {
				$full_url = $as3cf->get_attachment_url( $attachment_id );
			} else {
				$full_url = false;
			}

			return $full_url;

		}

		/**
		 * Download a specified file to local server with respect to provided attachment id
		 *  and/or Attachment path
		 *
		 * @param $attachment_id
		 *
		 * @param array         $size_details
		 *
		 * @param string        $uf_file_path
		 *
		 * @return string|bool Returns file path or false
		 */
		function download_file( $attachment_id, $size_details = array(), $uf_file_path = '' ) {
			global $wp_smush, $wpsmush_settings;
			if ( empty( $attachment_id ) || ! $wpsmush_settings->settings[ $this->module ] || ! $wp_smush->validate_install() ) {
				return false;
			}

			global $as3cf;
			$renamed = $s3_object = $s3_url = $file = false;

			// If file path wasn't specified in argument
			$uf_file_path = empty( $uf_file_path ) ? get_attached_file( $attachment_id, true ) : $uf_file_path;

			//If we have plugin method available, us that otherwise check it ourselves
			if ( method_exists( $as3cf, 'is_attachment_served_by_provider' ) ) {
				$s3_object        = $as3cf->is_attachment_served_by_provider( $attachment_id, true );
				$size_prefix      = dirname( $s3_object['key'] );
				$size_file_prefix = ( '.' === $size_prefix ) ? '' : $size_prefix . '/';
				if ( ! empty( $size_details ) && is_array( $size_details ) ) {
					$s3_object['key'] = path_join( $size_file_prefix, $size_details['file'] );
				} elseif ( ! empty( $uf_file_path ) ) {
					// Get the File path using basename for given attachment path
					$s3_object['key'] = path_join( $size_file_prefix, wp_basename( $uf_file_path ) );
				}

				//Try to download the attachment
				if ( $s3_object && is_object( $as3cf->plugin_compat ) && method_exists( $as3cf->plugin_compat, 'copy_provider_file_to_server' ) ) {
					//Download file
					$file = $as3cf->plugin_compat->copy_provider_file_to_server( $s3_object, $uf_file_path );
				}

				if ( $file ) {
					return $file;
				}
			}

			// If we don't have the file, Try it the basic way
			if ( ! $file ) {
				$s3_url = $this->is_image_on_s3( $attachment_id );

				// If we couldn't get the image URL, return false
				if ( is_wp_error( $s3_url ) || empty( $s3_url ) || ! $s3_url ) {
					return false;
				}

				if ( ! empty( $size_details ) ) {
					// If size details are available, Update the URL to get the image for the specified size
					$s3_url = str_replace( wp_basename( $s3_url ), $size_details['file'], $s3_url );
				} elseif ( ! empty( $uf_file_path ) ) {
					// Get the File path using basename for given attachment path
					$s3_url = str_replace( wp_basename( $s3_url ), wp_basename( $uf_file_path ), $s3_url );
				}

				// Download the file
				$temp_file = download_url( $s3_url );
				if ( ! is_wp_error( $temp_file ) ) {
					$renamed = @copy( $temp_file, $uf_file_path );
					unlink( $temp_file );
				}

				// If we were able to successfully rename the file, return file path
				if ( $renamed ) {

					return $uf_file_path;
				}
			}

			return false;
		}

		/**
		 * Check if file exists for the given path
		 *
		 * @param string $attachment_id
		 * @param string $file_path
		 *
		 * @return bool
		 */
		function does_image_exists( $attachment_id = '', $file_path = '' ) {
			global $as3cf;
			if ( empty( $attachment_id ) || empty( $file_path ) ) {
				return false;
			}
			//Return if method doesn't exists
			if ( ! method_exists( $as3cf, 'is_attachment_served_by_provider' ) ) {
				error_log( "Couldn't find method is_attachment_served_by_provider." );

				return false;
			}
			//Get s3 object for the file
			$s3_object = $as3cf->is_attachment_served_by_provider( $attachment_id, true );

			$size_prefix      = dirname( $s3_object['key'] );
			$size_file_prefix = ( '.' === $size_prefix ) ? '' : $size_prefix . '/';

			// Get the File path using basename for given attachment path
			$s3_object['key'] = path_join( $size_file_prefix, wp_basename( $file_path ) );

			// Get bucket details
			$bucket = $as3cf->get_setting( 'bucket' );
			$region = $as3cf->get_setting( 'region' );

			if ( is_wp_error( $region ) ) {
				return false;
			}

			$s3client = $as3cf->get_provider_client( $region );

			// If we still have the older version of S3 Offload, use old method.
			if ( method_exists( $s3client, 'doesObjectExist' ) ) {
				$file_exists = $s3client->doesObjectExist( $bucket, $s3_object['key'] );
			} else {
				$file_exists = $s3client->does_object_exist( $bucket, $s3_object['key'] );
			}

			return $file_exists;
		}

		/**
		 * Check if the file is served by S3 and download the file for given path
		 *
		 * @param string $file_path Full file path
		 * @param string $attachment_id
		 * @param array  $size_details Array of width and height for the image
		 *
		 * @return bool|string False/ File Path
		 */
		function maybe_download_file( $file_path = '', $attachment_id = '', $size_details = array() ) {
			if ( empty( $file_path ) || empty( $attachment_id ) ) {
				return false;
			}
			// Download if file not exists and served by S3
			if ( ! file_exists( $file_path ) && $this->is_image_on_s3( $attachment_id ) ) {
				return $this->download_file( $attachment_id, $size_details, $file_path );
			}

			return false;
		}

		/**
		 * Checks if we've backup on S3 for the given attachment id and backup path
		 *
		 * @param string $attachment_id
		 * @param string $backup_path
		 *
		 * @return bool
		 */
		function backup_exists_on_s3( $exists, $attachment_id = '', $backup_path = '' ) {
			// If the file is on S3, Check if backup image object exists
			if ( $this->is_image_on_s3( $attachment_id ) ) {
				return $this->does_image_exists( $attachment_id, $backup_path );
			}

			return $exists;
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
			if ( ! class_exists( 'Amazon_S3_And_CloudFront' ) && ! class_exists( 'Amazon_S3_And_CloudFront_Pro' ) ) {
				$disabled = true;
			}

			return $disabled;
		}

	}

	global $wpsmush_s3;
	$wpsmush_s3 = new WpSmushS3();

}

if ( class_exists( 'AS3CF_Plugin_Compatibility' ) && ! class_exists( 'wp_smush_s3_compat' ) ) {
	class wp_smush_s3_compat extends AS3CF_Plugin_Compatibility {

		function __construct() {
			$this->init();
		}

		function init() {
			// Plugin Compatibility with Amazon S3
			add_filter( 'as3cf_get_attached_file', array( $this, 'smush_download_file' ), 11, 4 );
		}

		/**
		 * Download the attached file from S3 to local server
		 *
		 * @param $url
		 * @param $file
		 * @param $attachment_id
		 * @param $s3_object
		 */
		function smush_download_file( $url, $file, $attachment_id, $s3_object ) {

			global $as3cf, $wpsmush_settings, $wp_smush;

			// Return if integration is disabled, or not a pro user
			if ( ! $wpsmush_settings->settings['s3'] || ! $wp_smush->validate_install() ) {
				return $url;
			}

			// If we already have the local file at specified path
			if ( file_exists( $file ) ) {
				return $url;
			}

			// Download image for Manual and Bulk Smush
			$action = ! empty( $_GET['action'] ) ? $_GET['action'] : '';
			if ( empty( $action ) || ! in_array( $action, array( 'wp_smushit_manual', 'wp_smushit_bulk' ) ) ) {
				return $url;
			}

			// If the plugin compat object is not available, or the method has been updated
			if ( ! is_object( $as3cf->plugin_compat ) || ! method_exists( $as3cf->plugin_compat, 'copy_image_to_server_on_action' ) ) {
				return $url;
			}

			$as3cf->plugin_compat->copy_image_to_server_on_action( $action, true, $url, $file, $s3_object );
		}

	}

	global $wpsmush_s3_compat;
	$wpsmush_s3_compat = new wp_smush_s3_compat();
}
