<?php
/**
 * Smush core class: WP_Smush class
 *
 * @package WP_Smush
 */

/**
 * Class WP_Smush.
 */
class WP_Smush {
	/**
	 * Class instance variable.
	 *
	 * @since 2.8.0
	 * @var null|WP_Smush $instance
	 */
	private static $instance = null;

	/**
	 * Stores the value of is_pro function.
	 *
	 * @var bool $is_pro
	 */
	private $is_pro;

	/**
	 * API server url to check API key validity.
	 *
	 * @var string $api_server
	 */
	private $api_server = 'https://premium.wpmudev.org/api/smush/v1/check/';

	/**
	 * Meta key to save migrated version.
	 *
	 * @var string $migrated_version_key
	 */
	private $migrated_version_key = 'wp-smush-migrated-version';

	/**
	 * Attachment type, being Smushed currently.
	 *
	 * @var string $media_type  Default: 'wp'. Accepts: 'wp', 'nextgen'.
	 */
	private $media_type = 'wp';

	/**
	 * Meta key to save smush result to db.
	 *
	 * @var string $smushed_meta_key
	 */
	public $smushed_meta_key = 'wp-smpro-smush-data';

	/**
	 * Super Smush is enabled or not.
	 *
	 * @var bool $lossy_enabled
	 */
	public $lossy_enabled = false;

	/**
	 * Whether to Smush the original image.
	 *
	 * @var bool $smush_original
	 */
	public $smush_original = false;

	/**
	 * Whether to preserve the EXIF data or not.
	 *
	 * @var bool $keep_exif
	 */
	public $keep_exif = false;

	/**
	 * Attachment ID for the image being Smushed currently.
	 *
	 * @var int $attachment_id
	 */
	public $attachment_id;

	/**
	 * DB option name.
	 */
	const OPTION_NAME = 'smush_option';

	/**
	 * Plugin options.
	 *
	 * @var null|array
	 */
	protected $options = null;

	/**
	 * Default options and values go here.
	 *
	 * @var array $defaults
	 */
	protected $defaults = array(
		'version' => WP_SMUSH_VERSION, // This one should not change.
	);

	/**
	 * Get singleton instance.
	 *
	 * @since 2.8.0
	 *
	 * @return null|WP_Smush
	 */
	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * WP_Smush constructor.
	 */
	protected function __construct() {
		$this->includes();

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			new WP_Smush_Ajax();
		}

		// Smush image (Auto Smush) when `wp_update_attachment_metadata` filter is fired.
		add_filter( 'wp_update_attachment_metadata', array( $this, 'smush_image' ), 15, 2 );

		// Delete backup files.
		add_action( 'delete_attachment', array( $this, 'delete_images' ), 12 );

		// Optimise WP retina 2x images.
		add_action( 'wr2x_retina_file_added', array( $this, 'smush_retina_image' ), 20, 3 );

		// Add Smush Columns.
		add_filter( 'manage_media_columns', array( $this, 'columns' ) );
		add_action( 'manage_media_custom_column', array( $this, 'custom_column' ), 10, 2 );
		add_filter( 'manage_upload_sortable_columns', array( $this, 'sortable_column' ) );
		// Manage column sorting.
		add_action( 'pre_get_posts', array( $this, 'smushit_orderby' ) );

		// Enqueue scripts and initialize variables.
		add_action( 'admin_init', array( $this, 'admin_init' ) );

		// Send Smush stats for PRO members.
		add_filter( 'wpmudev_api_project_extra_data-912164', array( $this, 'send_smush_stats' ) );
		add_action( 'wp_ajax_smush_show_warning', array( $this, 'show_warning_ajax' ) );

		/**
		 * Load NextGen Gallery, instantiate the Async class. if hooked too late or early, auto Smush doesn't
		 * work, also load after settings have been saved on init action.
		 */
		add_action( 'plugins_loaded', array( $this, 'load_libs' ), 90 );

		// Handle the Async optimisation.
		add_action( 'wp_async_wp_generate_attachment_metadata', array( $this, 'wp_smush_handle_async' ) );
		add_action( 'wp_async_wp_save_image_editor_file', array( $this, 'wp_smush_handle_editor_async' ), '', 2 );

		// Register function for sending unsmushed image count to hub.
		add_filter( 'wdp_register_hub_action', array( $this, 'smush_stats' ) );

		// Add information to privacy policy page (only during creation).
		add_action( 'admin_init', array( $this, 'add_policy' ) );

		// Register REST API metas.
		WP_Smush_Rest::get_instance()->register_metas();

		if ( is_admin() ) {
			add_action( 'admin_init', array( 'WP_Smush_Installer', 'upgrade_settings' ) );
		}
	}

	/**
	 * Include required files.
	 *
	 * @since 1.9.0
	 */
	private function includes() {
		// Ajax Class.
		/* @noinspection PhpIncludeInspection */
		require_once WP_SMUSH_DIR . 'lib/class-wp-smush-ajax.php';

		// Helper Class.
		/* @noinspection PhpIncludeInspection */
		require_once WP_SMUSH_DIR . 'lib/class-wp-smush-helper.php';

		// Settings Class.
		/* @noinspection PhpIncludeInspection */
		require_once WP_SMUSH_DIR . 'lib/class-wp-smush-settings.php';

		// Migration Class.
		/* @noinspection PhpIncludeInspection */
		require_once WP_SMUSH_DIR . 'lib/class-wp-smush-migrate.php';

		// Stats.
		/* @noinspection PhpIncludeInspection */
		require_once WP_SMUSH_DIR . 'lib/class-wp-smush-db.php';

		// Include Resize class.
		/* @noinspection PhpIncludeInspection */
		require_once WP_SMUSH_DIR . 'lib/class-wp-smush-resize.php';

		// Include Resize class.
		/* @noinspection PhpIncludeInspection */
		require_once WP_SMUSH_DIR . 'lib/class-wp-smush-auto-resize.php';

		// Include PNG to JPG Converter.
		/* @noinspection PhpIncludeInspection */
		require_once WP_SMUSH_DIR . 'lib/class-wp-smush-png_jpg.php';

		// Include Image backup class.
		/* @noinspection PhpIncludeInspection */
		require_once WP_SMUSH_DIR . 'lib/class-wp-smush-backup.php';

		// Include Smush Async class.
		/* @noinspection PhpIncludeInspection */
		require_once WP_SMUSH_DIR . 'lib/class-wp-smush-async.php';

		// Include REST API integration.
		/* @noinspection PhpIncludeInspection */
		require_once WP_SMUSH_DIR . 'lib/class-wp-smush-rest.php';

		// Include admin classes.
		/* @noinspection PhpIncludeInspection */
		require_once WP_SMUSH_DIR . 'lib/class-wp-smush-admin.php';

		// Include Directory Smush.
		/* @noinspection PhpIncludeInspection */
		require_once WP_SMUSH_DIR . 'lib/class-wp-smush-dir.php';

		// Include CDN.
		/* @noinspection PhpIncludeInspection */
		// require_once WP_SMUSH_DIR . 'lib/class-wp-smush-cdn.php';

		// Include Plugin Recommendations.
		/* @noinspection PhpIncludeInspection */
		require_once WP_SMUSH_DIR . 'lib/class-wp-smush-recommender.php';

		// Installer Class.
		/* @noinspection PhpIncludeInspection */
		require_once WP_SMUSH_DIR . 'lib/class-wp-smush-installer.php';
	}

	/**
	 * Initialise the setting variables
	 */
	function initialise() {
		global $wpsmush_settings;

		// Check if lossy enabled.
		$this->lossy_enabled = $this->validate_install() && $wpsmush_settings->settings['lossy'];

		// Check if Smush original enabled.
		$this->smush_original = $this->validate_install() && $wpsmush_settings->settings['original'];

		// Check whether to keep EXIF data or not.
		$this->keep_exif = empty( $wpsmush_settings->settings['strip_exif'] );
	}

	/**
	 * Enqueue scripts and initialize variables.
	 */
	function admin_init() {
		// Handle notice dismiss.
		$this->dismiss_smush_upgrade();

		// Perform migration if required.
		$this->migrate();

		// Initialize variables.
		$this->initialise();

		// Localize version, update.
		$this->getOptions();

		// Load integrations.
		$this->load_integrations();
	}

	/**
	 * Process an image with Smush.
	 *
	 * @param string $file_path  Absolute path to the image.
	 *
	 * @return array|bool
	 */
	function do_smushit( $file_path = '' ) {
		$errors   = new WP_Error();
		$dir_name = trailingslashit( dirname( $file_path ) );

		// Check if file exists and the directory is writable.
		if ( empty( $file_path ) ) {
			$errors->add( 'empty_path', __( 'File path is empty', 'wp-smushit' ) );
		} elseif ( ! file_exists( $file_path ) || ! is_file( $file_path ) ) {
			// Check that the file exists.
			$errors->add( 'file_not_found', sprintf( __( 'Could not find %s', 'wp-smushit' ), $file_path ) );
		} elseif ( ! is_writable( $dir_name ) ) {
			// Check that the file is writable.
			$errors->add( 'not_writable', sprintf( __( '%s is not writable', 'wp-smushit' ), $dir_name ) );
		}

		$file_size = file_exists( $file_path ) ? filesize( $file_path ) : '';

		// Check if premium user.
		$max_size = $this->validate_install() ? WP_SMUSH_PREMIUM_MAX_BYTES : WP_SMUSH_MAX_BYTES;

		// Check if file exists.
		if ( $file_size == 0 ) {
			$errors->add( 'image_not_found', '<p>' . sprintf( __( 'Skipped (%1$s), image not found. Attachment: %2$s', 'wp-smushit' ), size_format( $file_size, 1 ), basename( $file_path ) ) . '</p>' );
		} elseif ( $file_size > $max_size ) {
			// Check size limit.
			$errors->add( 'size_limit', '<p>' . sprintf( __( 'Skipped (%1$s), size limit exceeded. Attachment: %2$s', 'wp-smushit' ), size_format( $file_size, 1 ), basename( $file_path ) ) . '</p>' );
		}

		if ( count( $errors->get_error_messages() ) ) {
			return $errors;
		}

		// save original file permissions
		clearstatcache();
		$perms = fileperms( $file_path ) & 0777;

		/** Send image for smushing, and fetch the response */
		$response = $this->_post( $file_path, $file_size );

		if ( ! $response['success'] ) {
			$errors->add( 'false_response', $response['message'] );
		} elseif ( empty( $response['data'] ) ) {
			// If there is no data.
			$errors->add( 'no_data', __( 'Unknown API error', 'wp-smushit' ) );
		}

		if ( count( $errors->get_error_messages() ) ) {
			return $errors;
		}

		// If there are no savings, or image returned is bigger in size.
		if ( ( ! empty( $response['data']->bytes_saved ) && intval( $response['data']->bytes_saved ) <= 0 )
			 || empty( $response['data']->image )
		) {
			return $response;
		}
		$tempfile = $file_path . '.tmp';

		// Add the file as tmp.
		file_put_contents( $tempfile, $response['data']->image );

		// Replace the file.
		$success = @rename( $tempfile, $file_path );

		// If tempfile still exists, unlink it.
		if ( file_exists( $tempfile ) ) {
			@unlink( $tempfile );
		}

		// If file renaming failed.
		if ( ! $success ) {
			@copy( $tempfile, $file_path );
			@unlink( $tempfile );
		}

		// Some servers are having issue with file permission, this should fix it.
		if ( empty( $perms ) || ! $perms ) {
			// Source: WordPress Core.
			$stat  = stat( dirname( $file_path ) );
			$perms = $stat['mode'] & 0000666; // Same permissions as parent folder, strip off the executable bits.
		}
		@chmod( $file_path, $perms );

		return $response;
	}

	/**
	 * Fills $placeholder array with values from $data array
	 *
	 * @param array $placeholders
	 * @param array $data
	 *
	 * @return array
	 */
	function _array_fill_placeholders( array $placeholders, array $data ) {
		$placeholders['percent']     = $data['compression'];
		$placeholders['bytes']       = $data['bytes_saved'];
		$placeholders['size_before'] = $data['before_size'];
		$placeholders['size_after']  = $data['after_size'];
		$placeholders['time']        = $data['time'];

		return $placeholders;
	}

	/**
	 * Returns signature for single size of the smush api message to be saved to db;
	 *
	 * @return array
	 */
	function _get_size_signature() {
		return array(
			'percent'     => 0,
			'bytes'       => 0,
			'size_before' => 0,
			'size_after'  => 0,
			'time'        => 0,
		);
	}

	/**
	 * Optimises the image sizes
	 *
	 * Note: Function name is bit confusing, it is for optimisation, and calls the resizing function as well
	 *
	 * Read the image paths from an attachment's meta data and process each image
	 * with wp_smushit().
	 *
	 * @param array    $meta  Image meta data.
	 * @param null|int $id    Image ID.
	 *
	 * @return mixed
	 */
	public function resize_from_meta_data( $meta, $id = null ) {
		global $wpsmush_settings, $wpsmush_helper, $wpsmushit_admin;

		$settings = $wpsmush_settings->settings;
		// Flag to check, if original size image should be smushed or not.
		$original   = $settings['original'];
		$smush_full = ( $this->validate_install() && 1 === $original ) ? true : false;

		$errors = new WP_Error();
		$stats  = array(
			'stats' => array_merge(
				$this->_get_size_signature(), array(
					'api_version' => - 1,
					'lossy'       => - 1,
					'keep_exif'   => false,
				)
			),
			'sizes' => array(),
		);

		if ( $id && false === wp_attachment_is_image( $id ) ) {
			return $meta;
		}

		// Set attachment id and media type.
		$this->attachment_id = $id;
		$this->media_type    = 'wp';

		// File path and URL for original image.
		$attachment_file_path = $wpsmush_helper->get_attached_file( $id );

		// If images has other registered size, smush them first.
		if ( ! empty( $meta['sizes'] ) ) {
			if ( class_exists( 'finfo' ) ) {
				$finfo = new finfo( FILEINFO_MIME_TYPE );
			} else {
				$finfo = false;
			}

			foreach ( $meta['sizes'] as $size_key => $size_data ) {
				// Check if registered size is supposed to be Smushed or not.
				if ( 'full' !== $size_key && $wpsmushit_admin->skip_image_size( $size_key ) ) {
					continue;
				}

				// We take the original image. The 'sizes' will all match the same URL and
				// path. So just get the dirname and replace the filename.
				$attachment_file_path_size = path_join( dirname( $attachment_file_path ), $size_data['file'] );

				/**
				 * Allows S3 to hook over here and check if the given file path exists else download the file.
				 */
				do_action( 'smush_file_exists', $attachment_file_path_size, $id, $size_data );

				if ( $finfo ) {
					$ext = is_file( $attachment_file_path_size ) ? $finfo->file( $attachment_file_path_size ) : '';
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
				 * Allows to skip a image from smushing.
				 *
				 * @param bool , Smush image or not
				 * @$size string, Size of image being smushed
				 */
				$smush_image = apply_filters( 'wp_smush_media_image', true, $size_key );
				if ( ! $smush_image ) {
					continue;
				}

				// Store details for each size key.
				$response = $this->do_smushit( $attachment_file_path_size );

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

				// All Clear, Store the stat.
				// TODO: Move the existing stats code over here, we don't need to do the stats part twice.
				$stats['sizes'][ $size_key ] = (object) $this->_array_fill_placeholders( $this->_get_size_signature(), (array) $response['data'] );

				if ( empty( $stats['stats']['api_version'] ) || $stats['stats']['api_version'] == - 1 ) {
					$stats['stats']['api_version'] = $response['data']->api_version;
					$stats['stats']['lossy']       = $response['data']->lossy;
					$stats['stats']['keep_exif']   = ! empty( $response['data']->keep_exif ) ? $response['data']->keep_exif : 0;
				}
			}
			// Upfront Integration.
			$stats = $this->smush_upfront_images( $id, $stats );
		} else {
			$smush_full = true;
		}

		/**
		 * Allows to skip a image from smushing
		 *
		 * @param bool , Smush image or not
		 * @$size string, Size of image being smushed
		 */
		$smush_full_image = apply_filters( 'wp_smush_media_image', true, 'full' );

		// Whether to update the image stats or not.
		$store_stats = true;

		// If original size is supposed to be smushed.
		if ( $smush_full && $smush_full_image ) {

			$full_image_response = $this->do_smushit( $attachment_file_path );

			if ( is_wp_error( $full_image_response ) ) {
				return $full_image_response;
			}

			// If there are no stats.
			if ( empty( $full_image_response['data'] ) ) {
				$store_stats = false;
			}

			// If the image size grew after smushing, skip it.
			if ( $full_image_response['data']->after_size > $full_image_response['data']->before_size ) {
				$store_stats = false;
			}

			if ( $store_stats ) {
				$stats['sizes']['full'] = (object) $this->_array_fill_placeholders( $this->_get_size_signature(), (array) $full_image_response['data'] );
			}

			// Api version and lossy, for some images, full image i skipped and for other images only full exists
			// so have to add code again.
			if ( empty( $stats['stats']['api_version'] ) || $stats['stats']['api_version'] == - 1 ) {
				$stats['stats']['api_version'] = $full_image_response['data']->api_version;
				$stats['stats']['lossy']       = $full_image_response['data']->lossy;
				$stats['stats']['keep_exif']   = ! empty( $full_image_response['data']->keep_exif ) ? $full_image_response['data']->keep_exif : 0;
			}
		}

		$has_errors = (bool) count( $errors->get_error_messages() );

		// Set smush status for all the images, store it in wp-smpro-smush-data.
		if ( ! $has_errors ) {

			$existing_stats = get_post_meta( $id, $this->smushed_meta_key, true );

			if ( ! empty( $existing_stats ) ) {

				// Update stats for each size.
				if ( isset( $existing_stats['sizes'] ) && ! empty( $stats['sizes'] ) ) {

					foreach ( $existing_stats['sizes'] as $size_name => $size_stats ) {
						// If stats for a particular size doesn't exists.
						if ( empty( $stats['sizes'][ $size_name ] ) ) {
							$stats['sizes'][ $size_name ] = $existing_stats['sizes'][ $size_name ];
						} else {

							$existing_stats_size = (object) $existing_stats['sizes'][ $size_name ];

							// Store the original image size.
							$stats['sizes'][ $size_name ]->size_before = ( ! empty( $existing_stats_size->size_before ) && $existing_stats_size->size_before > $stats['sizes'][ $size_name ]->size_before ) ? $existing_stats_size->size_before : $stats['sizes'][ $size_name ]->size_before;

							// Update compression percent and bytes saved for each size.
							$stats['sizes'][ $size_name ]->bytes   = $stats['sizes'][ $size_name ]->bytes + $existing_stats_size->bytes;
							$stats['sizes'][ $size_name ]->percent = $this->calculate_percentage( $stats['sizes'][ $size_name ], $existing_stats_size );
						}
					}
				}
			}

			// Sum Up all the stats.
			$stats = $this->total_compression( $stats );

			// If there was any compression and there was no error in smushing.
			if ( isset( $stats['stats']['bytes'] ) && $stats['stats']['bytes'] >= 0 && ! $has_errors ) {
				/**
				 * Runs if the image smushing was successful
				 *
				 * @param int $id Image Id
				 *
				 * @param array $stats Smush Stats for the image
				 */
				do_action( 'wp_smush_image_optimised', $id, $stats );
			}
			update_post_meta( $id, $this->smushed_meta_key, $stats );
		}

		unset( $stats );

		// Unset response.
		if ( ! empty( $response ) ) {
			unset( $response );
		}

		return $meta;
	}

	/**
	 * Read the image paths from an attachment's meta data and process each image
	 * with wp_smushit()
	 *
	 * @uses resize_from_meta_data
	 *
	 * @param $meta
	 * @param null $ID
	 *
	 * @return mixed
	 */
	function smush_image( $meta, $ID = null ) {
		// Our async task runs when action is upload-attachment and post_id found. So do not run on these conditions.
		if ( ( ( ! empty( $_POST['action'] ) && 'upload-attachment' == $_POST['action'] ) || isset( $_POST['post_id'] ) )
			 // And, check if Async is enabled.
			 && defined( 'WP_SMUSH_ASYNC' ) && WP_SMUSH_ASYNC
		) {
			return $meta;
		}

		// Return directly if not a image.
		if ( ! wp_attachment_is_image( $ID ) ) {
			return $meta;
		}

		// Check if we're restoring the image Or already smushing the image.
		if ( get_option( "wp-smush-restore-$ID", false ) || get_option( "smush-in-progress-$ID", false ) ) {
			return $meta;
		}

		/**
		 * Filter: wp_smush_image
		 *
		 * Whether to smush the given attachment id or not
		 *
		 * @param bool $skip  Bool, whether to Smush image or not.
		 * @param int  $ID    Attachment Id, Attachment id of the image being processed.
		 */
		if ( ! apply_filters( 'wp_smush_image', true, $ID ) ) {
			return false;
		}

		// Set a transient to avoid multiple request.
		update_option( 'smush-in-progress-' . $ID, true );

		global $wpsmush_resize, $wpsmush_pngjpg, $wpsmush_settings, $wpsmush_helper;

		// While uploading from Mobile App or other sources, admin_init action may not fire.
		// So we need to manually initialize those.
		$this->initialise();
		$wpsmush_resize->initialize( true );

		// Check if auto is enabled.
		$auto_smush = $this->is_auto_smush_enabled();

		// Get the file path for backup.
		$attachment_file_path = $wpsmush_helper->get_attached_file( $ID );

		// Take backup.
		global $wpsmush_backup;
		$wpsmush_backup->create_backup( $attachment_file_path, '', $ID );

		// Optionally resize images.
		$meta = $wpsmush_resize->auto_resize( $ID, $meta );

		// Auto Smush the new image.
		if ( $auto_smush ) {
			// Optionally convert PNGs to JPG.
			$meta = $wpsmush_pngjpg->png_to_jpg( $ID, $meta );

			/**
			 * Fix for Hostgator.
			 * Check for use of http url (Hostgator mostly).
			 */
			$use_http = wp_cache_get( WP_SMUSH_PREFIX . 'use_http', 'smush' );

			if ( ! $use_http ) {
				$use_http = $wpsmush_settings->get_setting( WP_SMUSH_PREFIX . 'use_http', false );
				wp_cache_add( WP_SMUSH_PREFIX . 'use_http', $use_http, 'smush' );
			}

			if ( $use_http ) {
				// HTTP url.
				define( 'WP_SMUSH_API_HTTP', 'http://smushpro.wpmudev.org/1.0/' );
			}

			$this->resize_from_meta_data( $meta, $ID );
		} else {
			// Remove the smush metadata.
			delete_post_meta( $ID, $this->smushed_meta_key );
		}

		// Delete transient.
		delete_option( 'smush-in-progress-' . $ID );

		return $meta;
	}


	/**
	 * Posts an image to Smush.
	 *
	 * @param $file_path path of file to send to Smush
	 * @param $file_size
	 *
	 * @return bool|array array containing success status, and stats
	 */
	function _post( $file_path, $file_size ) {
		global $wpsmushit_admin, $wpsmush_settings, $wpsmush_helper;

		$data = false;

		$file_data = file_get_contents( $file_path );

		$headers = array(
			'accept'       => 'application/json', // The API returns JSON
			'content-type' => 'application/binary', // Set content type to binary
		);

		// Check if premium member, add API key
		$api_key = $this->_get_api_key();
		if ( ! empty( $api_key ) && $this->validate_install() ) {
			$headers['apikey'] = $api_key;
		}

		if ( $this->validate_install() && $wpsmush_settings->settings['lossy'] ) {
			$headers['lossy'] = 'true';
		} else {
			$headers['lossy'] = 'false';
		}

		$headers['exif'] = $wpsmush_settings->settings['strip_exif'] ? 'false' : 'true';

		$api_url = defined( 'WP_SMUSH_API_HTTP' ) ? WP_SMUSH_API_HTTP : WP_SMUSH_API;
		$args    = array(
			'headers'    => $headers,
			'body'       => $file_data,
			'timeout'    => WP_SMUSH_TIMEOUT,
			'user-agent' => WP_SMUSH_UA,
		);
		// Temporary increase the limit.
		$wpsmush_helper->increase_memory_limit();
		$result = wp_remote_post( $api_url, $args );

		unset( $file_data ); // Free memory.
		if ( is_wp_error( $result ) ) {
			$er_msg = $result->get_error_message();

			// Hostgator Issue.
			if ( ! empty( $er_msg ) && strpos( $er_msg, 'SSL CA cert' ) !== false ) {
				// Update DB for using http protocol
				$wpsmush_settings->update_setting( WP_SMUSH_PREFIX . 'use_http', 1 );
			}
			// Check for timeout error and suggest to filter timeout.
			if ( strpos( $er_msg, 'timed out' ) ) {
				$data['message'] = esc_html__( "Skipped due to a timeout error. You can increase the request timeout to make sure Smush has enough time to process larger files. define('WP_SMUSH_API_TIMEOUT', 150);", 'wp-smushit' );
			} else {
				// Handle error
				$data['message'] = sprintf( __( 'Error posting to API: %s', 'wp-smushit' ), $result->get_error_message() );
			}
			$data['success'] = false;
			unset( $result ); // Free memory.
			return $data;
		} elseif ( '200' != wp_remote_retrieve_response_code( $result ) ) {
			// Handle error
			$data['message'] = sprintf( __( 'Error posting to API: %1$s %2$s', 'wp-smushit' ), wp_remote_retrieve_response_code( $result ), wp_remote_retrieve_response_message( $result ) );
			$data['success'] = false;
			unset( $result ); // Free memory.
			return $data;
		}

		// If there is a response and image was successfully optimised.
		$response = json_decode( $result['body'] );
		if ( $response && true === $response->success ) {

			// If there is any savings.
			if ( $response->data->bytes_saved > 0 ) {
				// base64_decode is necessary to send binary img over JSON, no security problems here!
				$image     = base64_decode( $response->data->image );
				$image_md5 = md5( $response->data->image );
				if ( $response->data->image_md5 !== $image_md5 ) {
					// Handle error.
					$data['message'] = __( 'Smush data corrupted, try again.', 'wp-smushit' );
					$data['success'] = false;
				} else {
					$data['success']     = true;
					$data['data']        = $response->data;
					$data['data']->image = $image;
				}
				unset( $image );// Free memory.
			} else {
				// Just return the data.
				$data['success'] = true;
				$data['data']    = $response->data;
			}

			// Check for API message and store in db.
			if ( isset( $response->data->api_message ) && ! empty( $response->data->api_message ) ) {
				$this->add_api_message( $response->data->api_message );
			}

			// If is_premium is set in response, send it over to check for member validity.
			if ( ! empty( $response->data ) && isset( $response->data->is_premium ) ) {
				$wpsmushit_admin->api_headers['is_premium'] = $response->data->is_premium;
			}
		} else {
			// Server side error, get message from response
			$data['message'] = ! empty( $response->data ) ? $response->data : __( "Image couldn't be smushed", 'wp-smushit' );
			$data['success'] = false;
		}

		// Free memory and return data.
		unset( $result );
		unset( $response );
		return $data;
	}

	/**
	 * Print column header for Smush results in the media library using
	 * the `manage_media_columns` hook.
	 */
	function columns( $defaults ) {
		$defaults['smushit'] = 'Smush';

		return $defaults;
	}

	/**
	 * Return the filesize in a humanly readable format.
	 * Taken from http://www.php.net/manual/en/function.filesize.php#91477
	 */
	function format_bytes( $bytes, $precision = 1 ) {
		$units  = array( 'B', 'KiB', 'MiB', 'GiB', 'TiB' );
		$bytes  = max( $bytes, 0 );
		$pow    = floor( ( $bytes ? log( $bytes ) : 0 ) / log( 1024 ) );
		$pow    = min( $pow, count( $units ) - 1 );
		$bytes /= pow( 1024, $pow );

		return round( $bytes, $precision ) . $units[ $pow ];
	}

	/**
	 * Print column data for Smush results in the media library using
	 * the `manage_media_custom_column` hook.
	 */
	function custom_column( $column_name, $id ) {
		if ( 'smushit' == $column_name ) {
			$this->set_status( $id );
		}
	}

	/**
	 * Check if user is premium member, check for API key.
	 *
	 * @return bool  True if a premium member, false if regular user.
	 */
	function validate_install() {
		if ( isset( $this->is_pro ) ) {
			return $this->is_pro;
		}

		// No API key set, always false.
		$api_key = $this->_get_api_key();

		if ( empty( $api_key ) ) {
			return false;
		}

		// Flag to check if we need to revalidate the key.
		$revalidate = false;

		$api_auth = get_site_option( 'wp_smush_api_auth' );

		// Check if need to revalidate.
		if ( ! $api_auth || empty( $api_auth ) || empty( $api_auth[ $api_key ] ) ) {
			$revalidate = true;
		} else {
			$last_checked = $api_auth[ $api_key ]['timestamp'];
			$valid        = $api_auth[ $api_key ]['validity'];

			$diff = current_time( 'timestamp' ) - $last_checked;

			// Difference in hours.
			$diff_h = $diff / 3600;

			// Difference in minutes.
			$diff_m = $diff / 60;

			switch ( $valid ) {
				case 'valid':
					// If last checked was more than 12 hours.
					if ( $diff_h > 12 ) {
						$revalidate = true;
					}
					break;
				case 'invalid':
					// If last checked was more than 24 hours.
					if ( $diff_h > 24 ) {
						$revalidate = true;
					}
					break;
				case 'network_failure':
					// If last checked was more than 5 minutes.
					if ( $diff_m > 5 ) {
						$revalidate = true;
					}
					break;
			}
		}

		// If we are suppose to validate API, update the results in options table.
		if ( $revalidate ) {
			if ( empty( $api_auth[ $api_key ] ) ) {
				// For api key resets.
				$api_auth[ $api_key ] = array();

				// Storing it as valid, unless we really get to know from API call.
				$api_auth[ $api_key ]['validity'] = 'valid';
			}

			// Aaron suggested to Update timestamp before making the API call, to avoid any concurrent calls, clever ;)
			$api_auth[ $api_key ]['timestamp'] = current_time( 'timestamp' );
			update_site_option( 'wp_smush_api_auth', $api_auth );

			// Call API.
			$url = $this->api_server . $api_key;

			$request = wp_remote_get(
				$url, array(
					'user-agent' => WP_SMUSH_UA,
					'timeout'    => 10,
				)
			);

			if ( ! is_wp_error( $request ) && '200' == wp_remote_retrieve_response_code( $request ) ) {
				$result = json_decode( wp_remote_retrieve_body( $request ) );
				if ( ! empty( $result->success ) && $result->success ) {
					$valid = 'valid';
				} else {
					$valid = 'invalid';
				}
			} else {
				$valid = 'network_failure';
			}

			// Reset value.
			$api_auth = array();

			// Add a fresh timestamp.
			$timestamp            = current_time( 'timestamp' );
			$api_auth[ $api_key ] = array(
				'validity'  => $valid,
				'timestamp' => $timestamp,
			);

			// Update API validity.
			update_site_option( 'wp_smush_api_auth', $api_auth );

		}

		$this->is_pro = ( 'valid' === $valid );

		return $this->is_pro;
	}

	/**
	 * Returns api key.
	 *
	 * @return mixed
	 */
	function _get_api_key() {
		$api_key = false;

		// If API key defined manually, get that.
		if ( defined( 'WPMUDEV_APIKEY' ) && WPMUDEV_APIKEY ) {
			$api_key = WPMUDEV_APIKEY;
		} elseif ( class_exists( 'WPMUDEV_Dashboard' ) ) {
			// If dashboard plugin is active, get API key from db.
			$api_key = get_site_option( 'wpmudev_apikey' );
		}

		return $api_key;
	}

	/**
	 * Returns size saved from the api call response
	 *
	 * @param string $message
	 *
	 * @return string|bool
	 */
	function get_saved_size( $message ) {
		if ( preg_match( '/\((.*)\)/', $message, $matches ) ) {
			return isset( $matches[1] ) ? $matches[1] : false;
		}

		return false;
	}

	/**
	 * Set send button status
	 *
	 * @param $id
	 * @param bool $echo
	 * @param bool $text_only Returns the stats text instead of button
	 * @param bool $wrapper required for `column_html`, to include the wrapper div or not
	 *
	 * @return string|void
	 */
	function set_status( $id, $echo = true, $text_only = false, $wrapper = true ) {
		global $wpsmush_s3_compat;
		$status_txt  = $button_txt = $stats = $links = '';
		$show_button = $show_resmush = false;

		$links = '';

		// If variables are not initialized properly, initialize it.
		if ( ! has_action( 'admin_init', array( $this, 'admin_init' ) ) ) {
			$this->initialise();
		}

		$wp_smush_data      = get_post_meta( $id, $this->smushed_meta_key, true );
		$wp_resize_savings  = get_post_meta( $id, WP_SMUSH_PREFIX . 'resize_savings', true );
		$conversion_savings = get_post_meta( $id, WP_SMUSH_PREFIX . 'pngjpg_savings', true );

		$combined_stats = $this->combined_stats( $wp_smush_data, $wp_resize_savings );

		$combined_stats = $this->combine_conversion_stats( $combined_stats, $conversion_savings );

		// Remove Smush s3 hook, as it downloads the file again.
		remove_filter( 'as3cf_get_attached_file', array( $wpsmush_s3_compat, 'smush_download_file' ), 11, 4 );
		$attachment_data = wp_get_attachment_metadata( $id );

		// If the image is smushed.
		if ( ! empty( $wp_smush_data ) ) {

			$image_count    = count( $wp_smush_data['sizes'] );
			$bytes          = isset( $combined_stats['stats']['bytes'] ) ? $combined_stats['stats']['bytes'] : 0;
			$bytes_readable = ! empty( $bytes ) ? size_format( $bytes, 1 ) : '';
			$percent        = isset( $combined_stats['stats']['percent'] ) ? $combined_stats['stats']['percent'] : 0;
			$percent        = $percent < 0 ? 0 : $percent;

			// Show resmush link, if the settings were changed.
			$show_resmush = $this->show_resmush( $id, $wp_smush_data );

			if ( empty( $wp_resize_savings['bytes'] ) && isset( $wp_smush_data['stats']['size_before'] ) && $wp_smush_data['stats']['size_before'] == 0 && ! empty( $wp_smush_data['sizes'] ) ) {
				$status_txt = __( 'Already Optimized', 'wp-smushit' );
				if ( $show_resmush ) {
					$links .= $this->get_resmsuh_link( $id );
				}
				$show_button = false;
			} else {
				if ( $bytes == 0 || $percent == 0 ) {
					$status_txt = __( 'Already Optimized', 'wp-smushit' );

					if ( $show_resmush ) {
						$links .= $this->get_resmsuh_link( $id );
					}
				} elseif ( ! empty( $percent ) && ! empty( $bytes_readable ) ) {
					$status_txt = $image_count > 1 ? sprintf( __( '%d images reduced ', 'wp-smushit' ), $image_count ) : __( 'Reduced ', 'wp-smushit' );

					$stats_percent = number_format_i18n( $percent, 2, '.', '' );
					$stats_percent = $stats_percent > 0 ? sprintf( '(  %01.1f%% )', $stats_percent ) : '';
					$status_txt   .= sprintf( __( 'by %1$s %2$s', 'wp-smushit' ), $bytes_readable, $stats_percent );

					$file_path = get_attached_file( $id );
					$size      = file_exists( $file_path ) ? filesize( $file_path ) : 0;
					if ( $size > 0 ) {
						$update_size = size_format( $size, 0 ); // Used in js to update image stat.
						$size        = size_format( $size, 1 );
						$image_size  = sprintf( __( '<br /> Image Size: %s', 'wp-smushit' ), $size );
						$status_txt .= $image_size;
					}

					$show_resmush = $this->show_resmush( $id, $wp_smush_data );

					if ( $show_resmush ) {
						$links .= $this->get_resmsuh_link( $id );
					}

					// Restore Image: Check if we need to show the restore image option.
					$show_restore = $this->show_restore_option( $id, $attachment_data );

					if ( $show_restore ) {
						$links .= $this->get_restore_link( $id );
					}

					// Detailed Stats: Show detailed stats if available.
					if ( ! empty( $wp_smush_data['sizes'] ) ) {

						// Detailed Stats Link.
						$links .= sprintf(
							'<a href="#" class="wp-smush-action smush-stats-details wp-smush-title sui-tooltip sui-tooltip-constrained button" data-tooltip="%s">%s</a>',
							esc_html__( 'Detailed stats for all the image sizes', 'wp-smushit' ),
							esc_html__( 'View Stats', 'wp-smushit' )
						);

						// Stats.
						$stats = $this->get_detailed_stats( $id, $wp_smush_data, $attachment_data );

						if ( ! $text_only ) {
							$links .= $stats;
						}
					}
				}
			}
			// Wrap links if not empty.
			$links = ! empty( $links ) ? "<div class='sui-smush-media smush-status-links'>" . $links . '</div>' : '';

			/** Super Smush Button  */
			// IF current compression is lossy.
			if ( ! empty( $wp_smush_data ) && ! empty( $wp_smush_data['stats'] ) ) {
				$lossy    = ! empty( $wp_smush_data['stats']['lossy'] ) ? $wp_smush_data['stats']['lossy'] : '';
				$is_lossy = $lossy == 1 ? true : false;
			}

			// Check image type.
			$image_type = get_post_mime_type( $id );

			// Check if premium user, compression was lossless, and lossy compression is enabled.
			// If we are displaying the resmush option already, no need to show the Super Smush button.
			if ( ! $show_resmush && ! $is_lossy && $this->lossy_enabled && $image_type != 'image/gif' ) {
				// the button text
				$button_txt  = __( 'Super-Smush', 'wp-smushit' );
				$show_button = true;
			}
		} elseif ( get_option( 'smush-in-progress-' . $id, false ) ) {
			// The status.
			$status_txt = __( 'Smushing in progress..', 'wp-smushit' );

			// Set WP Smush data to true in order to show the text.
			$wp_smush_data = true;

			// We need to show the smush button.
			$show_button = false;

			// The button text.
			$button_txt = '';
		} else {

			// Show status text
			$wp_smush_data = true;

			// The status.
			$status_txt = __( 'Not processed', 'wp-smushit' );

			// We need to show the smush button.
			$show_button = true;

			// The button text.
			$button_txt = __( 'Smush', 'wp-smushit' );
		}

		$class      = $wp_smush_data ? '' : ' hidden';
		$status_txt = '<p class="smush-status' . $class . '">' . $status_txt . '</p>';

		$status_txt .= $links;

		if ( $text_only ) {
			// For ajax response.
			return array(
				'status'       => $status_txt,
				'stats'        => $stats,
				'show_warning' => intval( $this->show_warning() ),
				'new_size'     => isset( $update_size ) ? $update_size : 0,
			);
		}

		// If we are not showing smush button, append progree bar, else it is already there.
		if ( ! $show_button ) {
			$status_txt .= $this->progress_bar();
		}

		$text = $this->column_html( $id, $status_txt, $button_txt, $show_button, $wp_smush_data, $echo, $wrapper );
		if ( ! $echo ) {
			return $text;
		}
	}

	/**
	 * Print the column html
	 *
	 * @param string  $id Media id
	 * @param string  $status_txt Status text
	 * @param string  $button_txt Button label
	 * @param boolean $show_button Whether to shoe the button
	 * @param bool    $smushed Whether image is smushed or not
	 * @param bool    $echo If true, it directly outputs the HTML
	 * @param bool    $wrapper Whether to return the button with wrapper div or not
	 *
	 * @return string|void
	 */
	function column_html( $id, $html = '', $button_txt = '', $show_button = true, $smushed = false, $echo = true, $wrapper = true ) {
		$allowed_images = array( 'image/jpeg', 'image/jpg', 'image/x-citrix-jpeg', 'image/png', 'image/x-png', 'image/gif' );

		// don't proceed if attachment is not image, or if image is not a jpg, png or gif
		if ( ! wp_attachment_is_image( $id ) || ! in_array( get_post_mime_type( $id ), $allowed_images ) ) {
			$status_txt = __( 'Not processed', 'wp-smushit' );
			if ( $echo ) {
				echo $status_txt;

				return;
			} else {
				return $status_txt;
			}
		}

		// if we aren't showing the button
		if ( ! $show_button ) {
			if ( $echo ) {
				echo $html;

				return;
			} else {
				if ( ! $smushed ) {
					$class = ' currently-smushing';
				} else {
					$class = ' smushed';
				}

				return $wrapper ? '<div class="smush-wrap' . $class . '">' . $html . '</div>' : $html;
			}
		}
		$mode_class = ! empty( $_POST['mode'] ) && 'grid' == $_POST['mode'] ? ' button-primary' : '';
		if ( ! $echo ) {
			$button_class = $wrapper || ! empty( $mode_class ) ? 'button button-primary wp-smush-send' : 'button button-primary wp-smush-send';
			$html        .= '
			<button  class="' . $button_class . '" data-id="' . $id . '">
                ' . $button_txt . '
			</button>';
			if ( ! $smushed ) {
				$class = ' unsmushed';
			} else {
				$class = ' smushed';
			}

			$html .= $this->progress_bar();
			$html  = $wrapper ? '<div class="smush-wrap' . $class . '">' . $html . '</div>' : $html;

			return $html;
		} else {
			$html .= '<button class="button button-primary wp-smush-send' . $mode_class . '" data-id="' . $id . '">
                ' . $button_txt . '
			</button>';
			$html  = $html . $this->progress_bar();
			echo $html;
		}
	}

	/**
	 * Migrates smushit api message to the latest structure
	 *
	 * @return void
	 */
	function migrate() {
		if ( ! version_compare( WP_SMUSH_VERSION, '1.7.1', 'lte' ) ) {
			return;
		}

		$migrated_version = get_site_option( $this->migrated_version_key );

		if ( $migrated_version === WP_SMUSH_VERSION ) {
			return;
		}

		global $wpdb;

		$q       = $wpdb->prepare( "SELECT * FROM {$wpdb->postmeta} WHERE meta_key=%s AND meta_value LIKE %s", '_wp_attachment_metadata', '%wp_smushit%' );
		$results = $wpdb->get_results( $q );

		if ( count( $results ) < 1 ) {
			return;
		}

		$migrator = new WpSmushMigrate();
		foreach ( $results as $attachment_meta ) {
			$migrated_message = $migrator->migrate_api_message( maybe_unserialize( $attachment_meta->meta_value ) );
			if ( $migrated_message !== array() ) {
				update_post_meta( $attachment_meta->post_id, $this->smushed_meta_key, $migrated_message );
			}
		}

		update_site_option( $this->migrated_version_key, WP_SMUSH_VERSION );
	}

	/**
	 * Updates the smush stats for a single image size
	 *
	 * @param $id
	 * @param $stats
	 * @param $image_size
	 */
	function update_smush_stats_single( $id, $smush_stats, $image_size = '' ) {
		// Return, if we don't have image id or stats for it
		if ( empty( $id ) || empty( $smush_stats ) || empty( $image_size ) ) {
			return false;
		}
		$data = $smush_stats['data'];
		// Get existing Stats
		$stats = get_post_meta( $id, $this->smushed_meta_key, true );
		// Update existing Stats
		if ( ! empty( $stats ) ) {

			// Update stats for each size
			if ( isset( $stats['sizes'] ) ) {

				// if stats for a particular size doesn't exists
				if ( empty( $stats['sizes'][ $image_size ] ) ) {
					// Update size wise details
					$stats['sizes'][ $image_size ] = (object) $this->_array_fill_placeholders( $this->_get_size_signature(), (array) $data );
				} else {
					// Update compression percent and bytes saved for each size
					$stats['sizes'][ $image_size ]->bytes   = $stats['sizes'][ $image_size ]->bytes + $data->bytes_saved;
					$stats['sizes'][ $image_size ]->percent = $stats['sizes'][ $image_size ]->percent + $data->compression;
				}
			}
		} else {
			// Create new stats
			$stats                         = array(
				'stats' => array_merge(
					$this->_get_size_signature(), array(
						'api_version' => - 1,
						'lossy'       => - 1,
					)
				),
				'sizes' => array(),
			);
			$stats['stats']['api_version'] = $data->api_version;
			$stats['stats']['lossy']       = $data->lossy;
			$stats['stats']['keep_exif']   = ! empty( $data->keep_exif ) ? $data->keep_exif : 0;

			// Update size wise details
			$stats['sizes'][ $image_size ] = (object) $this->_array_fill_placeholders( $this->_get_size_signature(), (array) $data );
		}
		// Calculate the total compression
		$stats = $this->total_compression( $stats );

		update_post_meta( $id, $this->smushed_meta_key, $stats );
	}

	/**
	 * Smush Retina images for WP Retina 2x, Update Stats
	 *
	 * @param $id
	 * @param $retina_file
	 * @param $image_size
	 */
	function smush_retina_image( $id, $retina_file, $image_size ) {
		// Initialize attachment id and media type
		$this->attachment_id = $id;
		$this->media_type    = 'wp';

		/**
		 * Allows to Enable/Disable WP Retina 2x Integration
		 */
		$smush_retina_images = apply_filters( 'smush_retina_images', true );

		// Check if Smush retina images is enbled
		if ( ! $smush_retina_images ) {
			return;
		}
		// Check for Empty fields
		if ( empty( $id ) || empty( $retina_file ) || empty( $image_size ) ) {
			return;
		}

		// Do not smush if auto smush is turned off
		if ( ! $this->is_auto_smush_enabled() ) {
			return;
		}

		/**
		 * Allows to skip a image from smushing
		 *
		 * @param bool , Smush image or not
		 * @$size string, Size of image being smushed
		 */
		$smush_image = apply_filters( 'wp_smush_media_image', true, $image_size );
		if ( ! $smush_image ) {
			return;
		}

		$stats = $this->do_smushit( $retina_file );
		// If we squeezed out something, Update stats
		if ( ! is_wp_error( $stats ) && ! empty( $stats['data'] ) && isset( $stats['data'] ) && $stats['data']->bytes_saved > 0 ) {

			$image_size = $image_size . '@2x';

			$this->update_smush_stats_single( $id, $stats, $image_size );
		}
	}

	/**
	 * Return a list of images not smushed and reason
	 *
	 * @param $image_id
	 * @param $size_stats
	 * @param $attachment_metadata
	 *
	 * @return array
	 */
	function get_skipped_images( $image_id, $size_stats, $attachment_metadata ) {
		$skipped = array();

		// Get a list of all the sizes, Show skipped images
		$media_size = get_intermediate_image_sizes();

		// Full size
		$full_image = get_attached_file( $image_id );

		// If full image was not smushed, reason 1. Large Size logic, 2. Free and greater than 1Mb
		if ( ! array_key_exists( 'full', $size_stats ) ) {
			// For free version, Check the image size
			if ( ! $this->validate_install() ) {
				// For free version, check if full size is greater than 1 Mb, show the skipped status
				$file_size = file_exists( $full_image ) ? filesize( $full_image ) : '';
				if ( ! empty( $file_size ) && ( $file_size / WP_SMUSH_MAX_BYTES ) > 1 ) {
					$skipped[] = array(
						'size'   => 'full',
						'reason' => 'size_limit',
					);
				}
			// In other case, if full size is skipped.
			} elseif ( ! isset( $skipped['full'] ) ) {
				// Paid version, Check if we have large size
				$skipped[] = array(
					'size'   => 'full',
					'reason' => 'large_size',
				);
			}
		}
		// For other sizes, check if the image was generated and not available in stats
		if ( is_array( $media_size ) ) {
			foreach ( $media_size as $size ) {
				if ( array_key_exists( $size, $attachment_metadata['sizes'] ) && ! array_key_exists( $size, $size_stats ) && ! empty( $size['file'] ) ) {
					// Image Path
					$img_path   = path_join( dirname( $full_image ), $size['file'] );
					$image_size = file_exists( $img_path ) ? filesize( $img_path ) : '';
					if ( ! empty( $image_size ) && ( $image_size / WP_SMUSH_MAX_BYTES ) > 1 ) {
						$skipped[] = array(
							'size'   => 'full',
							'reason' => 'size_limit',
						);
					}
				}
			}
		}

		return $skipped;
	}

	/**
	 * Skip messages respective to their ids
	 *
	 * @param $msg_id
	 *
	 * @return bool
	 */
	function skip_reason( $msg_id ) {
		$count           = count( get_intermediate_image_sizes() );
		$smush_orgnl_txt = sprintf( esc_html__( 'When you upload an image to WordPress it automatically creates %s thumbnail sizes that are commonly used in your pages. WordPress also stores the original full-size image, but because these are not usually embedded on your site we don’t Smush them. Pro users can override this.', 'wp-smushit' ), $count );
		$skip_msg        = array(
			'large_size' => $smush_orgnl_txt,
			'size_limit' => esc_html__( "Image couldn't be smushed as it exceeded the 1Mb size limit, Pro users can smush images with size up to 32Mb.", 'wp-smushit' ),
		);
		$skip_rsn        = ! empty( $skip_msg[ $msg_id ] ) ? esc_html__( ' Skipped', 'wp-smushit', 'wp-smushit' ) : '';
		$skip_rsn        = ! empty( $skip_rsn ) ? $skip_rsn . '<span class="sui-tooltip sui-tooltip-constrained sui-tooltip-left" data-tooltip="' . $skip_msg[ $msg_id ] . '"><i class="dashicons dashicons-editor-help"></i></span>' : '';

		return $skip_rsn;
	}

	/**
	 * Shows the image size and the compression for each of them
	 *
	 * @param $image_id
	 * @param $wp_smush_data
	 *
	 * @return string
	 */
	function get_detailed_stats( $image_id, $wp_smush_data, $attachment_metadata ) {
		global $wpsmushit_admin;

		$stats      = '<div id="smush-stats-' . $image_id . '" class="sui-smush-media smush-stats-wrapper hidden">
			<table class="wp-smush-stats-holder">
				<thead>
					<tr>
						<th class="smush-stats-header">' . esc_html__( 'Image size', 'wp-smushit' ) . '</th>
						<th class="smush-stats-header">' . esc_html__( 'Savings', 'wp-smushit' ) . '</th>
					</tr>
				</thead>
				<tbody>';
		$size_stats = $wp_smush_data['sizes'];

		// Reorder Sizes as per the maximum savings.
		uasort( $size_stats, array( $this, 'cmp' ) );

		if ( ! empty( $attachment_metadata['sizes'] ) ) {
			// Get skipped images
			$skipped = $this->get_skipped_images( $image_id, $size_stats, $attachment_metadata );

			if ( ! empty( $skipped ) ) {
				foreach ( $skipped as $img_data ) {
					$skip_class = $img_data['reason'] == 'size_limit' ? ' error' : '';
					$stats     .= '<tr>
				<td>' . strtoupper( $img_data['size'] ) . '</td>
				<td class="smush-skipped' . $skip_class . '">' . $this->skip_reason( $img_data['reason'] ) . '</td>
			</tr>';
				}
			}
		}
		// Show Sizes and their compression
		foreach ( $size_stats as $size_key => $size_value ) {
			$dimensions = '';
			// Get the dimensions for the image size if available
			if ( ! empty( $wpsmushit_admin->image_sizes ) && ! empty( $wpsmushit_admin->image_sizes[ $size_key ] ) ) {
				$dimensions = $wpsmushit_admin->image_sizes[ $size_key ]['width'] . 'x' . $wpsmushit_admin->image_sizes[ $size_key ]['height'];
			}
			$dimensions = ! empty( $dimensions ) ? sprintf( ' <br /> (%s)', $dimensions ) : '';
			if ( $size_value->bytes > 0 ) {
				$percent = round( $size_value->percent, 1 );
				$percent = $percent > 0 ? ' ( ' . $percent . '% )' : '';
				$stats  .= '<tr>
				<td>' . strtoupper( $size_key ) . $dimensions . '</td>
				<td>' . size_format( $size_value->bytes, 1 ) . $percent . '</td>
			</tr>';
			}
		}
		$stats .= '</tbody>
			</table>
		</div>';

		return $stats;
	}

	/**
	 * Compare Values
	 *
	 * @param $a
	 * @param $b
	 *
	 * @return int
	 */
	function cmp( $a, $b ) {
		return $a->bytes < $b->bytes;
	}

	/**
	 * Load Plugin Modules
	 */
	function load_libs() {
		// Load Nextgen lib, and initialize wp smush async class
		$this->load_nextgen();
		$this->wp_smush_async();
		$this->load_gutenberg();
	}

	/**
	 * Check if NextGen is active or not
	 * Include and instantiate classes
	 */
	function load_nextgen() {
		global $wpsmush_settings;

		// Check if integration is enabled or not.
		if ( ! empty( $wpsmush_settings->settings ) ) {
			$opt_nextgen_val = $wpsmush_settings->settings['nextgen'];
		} else {
			// Smush NextGen key
			$opt_nextgen     = WP_SMUSH_PREFIX . 'nextgen';
			$opt_nextgen_val = $wpsmush_settings->get_setting( $opt_nextgen, false );
		}

		require_once WP_SMUSH_DIR . '/lib/integrations/class-wp-smush-nextgen.php';
		// Do not continue if integration not enabled or not a pro user.
		if ( ! $opt_nextgen_val || ! $this->validate_install() ) {
			return;
		}
		require_once WP_SMUSH_DIR . '/lib/integrations/nextgen/class-wp-smush-nextgen-admin.php';
		require_once WP_SMUSH_DIR . '/lib/integrations/nextgen/class-wp-smush-nextgen-stats.php';
		require_once WP_SMUSH_DIR . '/lib/integrations/nextgen/class-wp-smush-nextgen-bulk.php';

		global $wpsmushnextgen, $wpsmushnextgenadmin, $wpsmushnextgenstats;
		// Initialize Nextgen support
		if ( ! is_object( $wpsmushnextgen ) ) {
			$wpsmushnextgen = new WpSmushNextGen();
		}
		$wpsmushnextgenstats = new WpSmushNextGenStats();
		$wpsmushnextgenadmin = new WpSmushNextGenAdmin();
		new WPSmushNextGenBulk();
	}

	/**
	 * Load Gutenberg integration.
	 *
	 * @since 2.8.1
	 */
	private function load_gutenberg() {
		require_once WP_SMUSH_DIR . '/lib/integrations/class-wp-smush-gutenberg.php';

		new WP_Smush_Gutenberg();
	}

	/**
	 * Load integrations class.
	 *
	 * @since 2.8.0
	 */
	private function load_integrations() {
		// Integrations class.
		require_once WP_SMUSH_DIR . 'lib/integrations/class-wp-smush-common.php';

		WP_Smush_Common::get_instance();
	}

	/**
	 * Add the Smushit Column to sortable list
	 *
	 * @param $columns
	 *
	 * @return mixed
	 */
	function sortable_column( $columns ) {
		$columns['smushit'] = 'smushit';

		return $columns;
	}

	/**
	 * Orderby query for smush columns
	 */
	function smushit_orderby( $query ) {
		global $current_screen;

		// Filter only media screen
		if ( ! is_admin() || ( ! empty( $current_screen ) && $current_screen->base != 'upload' ) ) {
			return;
		}

		$orderby = $query->get( 'orderby' );

		if ( isset( $orderby ) && 'smushit' == $orderby ) {
			$query->set(
				'meta_query', array(
					'relation' => 'OR',
					array(
						'key'     => $this->smushed_meta_key,
						'compare' => 'EXISTS',
					),
					array(
						'key'     => $this->smushed_meta_key,
						'compare' => 'NOT EXISTS',
					),
				)
			);
			$query->set( 'orderby', 'meta_value_num' );
		}

		return $query;
	}

	/**
	 * If any of the image size have a backup file, show the restore option
	 *
	 * @param $attachment_data
	 *
	 * @return bool
	 */
	function show_restore_option( $image_id, $attachment_data ) {
		global $wpsmushit_admin;

		// No Attachment data, don't go ahead
		if ( empty( $attachment_data ) ) {
			return false;
		}

		// Get the image path for all sizes
		$file    = get_attached_file( $image_id );
		$uf_file = get_attached_file( $image_id, true );

		// Get stored backup path, if any
		$backup_sizes = get_post_meta( $image_id, '_wp_attachment_backup_sizes', true );

		// Check if we've a backup path
		if ( ! empty( $backup_sizes ) && ( ! empty( $backup_sizes['smush-full'] ) || ! empty( $backup_sizes['smush_png_path'] ) ) ) {
			// Check for PNG backup
			$backup = ! empty( $backup_sizes['smush_png_path'] ) ? $backup_sizes['smush_png_path'] : '';

			// Check for original full size image backup
			$backup = empty( $backup ) && ! empty( $backup_sizes['smush-full'] ) ? $backup_sizes['smush-full'] : $backup;

			$backup = ! empty( $backup['file'] ) ? $backup['file'] : '';
		}

		// If we still don't have a backup path, use traditional method to get it
		if ( empty( $backup ) ) {
			// Check backup for Full size
			$backup = $wpsmushit_admin->get_image_backup_path( $file );
		} else {
			// Get the full path for file backup
			$backup = str_replace( wp_basename( $file ), wp_basename( $backup ), $file );
		}

		$file_exists = apply_filters( 'smush_backup_exists', file_exists( $backup ), $image_id, $backup );

		if ( $file_exists ) {
			return true;
		}

		// Additional Backup Check for JPEGs converted from PNG
		$pngjpg_savings = get_post_meta( $image_id, WP_SMUSH_PREFIX . 'pngjpg_savings', true );
		if ( ! empty( $pngjpg_savings ) ) {

			// Get the original File path and check if it exists
			$backup = get_post_meta( $image_id, WP_SMUSH_PREFIX . 'original_file', true );
			$backup = $this->original_file( $backup );

			if ( ! empty( $backup ) && is_file( $backup ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Returns a restore link for given image id
	 *
	 * @param $image_id
	 *
	 * @return bool|string
	 */
	function get_restore_link( $image_id, $type = 'wp' ) {
		if ( empty( $image_id ) ) {
			return false;
		}

		$class  = 'wp-smush-action wp-smush-title sui-tooltip';
		$class .= 'wp' == $type ? ' wp-smush-restore button' : ' wp-smush-nextgen-restore';

		$ajax_nonce = wp_create_nonce( 'wp-smush-restore-' . $image_id );

		return sprintf( '<a href="#" data-tooltip="%s" data-id="%d" data-nonce="%s" class="%s">%s</a>', esc_html__( 'Restore original image.', 'wp-smushit' ), $image_id, $ajax_nonce, $class, esc_html__( 'Restore', 'wp-smushit' ) );
	}

	/**
	 * Returns the HTML for progress bar
	 *
	 * @return string
	 */
	function progress_bar() {
		return '<span class="spinner wp-smush-progress"></span>';
	}

	/**
	 * If auto smush is set to true or not, default is true
	 *
	 * @return int|mixed|void
	 */
	function is_auto_smush_enabled() {

		global $wpsmush_settings;

		$auto_smush = $wpsmush_settings->settings['auto'];

		// Keep the auto smush on by default
		if ( $auto_smush === false || ! isset( $auto_smush ) ) {
			$auto_smush = 1;
		}

		return $auto_smush;
	}

	/**
	 * Generates a Resmush link for a image
	 *
	 * @param $image_id
	 *
	 * @return bool|string
	 */
	function get_resmsuh_link( $image_id, $type = 'wp' ) {
		if ( empty( $image_id ) ) {
			return false;
		}
		$class  = 'wp-smush-action wp-smush-title sui-tooltip sui-tooltip-constrained';
		$class .= 'wp' == $type ? ' wp-smush-resmush button' : ' wp-smush-nextgen-resmush';

		$ajax_nonce = wp_create_nonce( 'wp-smush-resmush-' . $image_id );

		return sprintf( '<a href="#" data-tooltip="%s" data-id="%d" data-nonce="%s" class="%s">%s</a>', esc_html__( 'Smush image including original file.', 'wp-smushit' ), $image_id, $ajax_nonce, $class, esc_html__( 'Resmush', 'wp-smushit' ) );
	}

	/**
	 * Returns the backup path for attachment
	 *
	 * @param $attachment_path
	 *
	 * @return bool|string
	 */
	function get_image_backup_path( $attachment_path ) {
		// If attachment id is not available, return false
		if ( empty( $attachment_path ) ) {
			return false;
		}
		$path = pathinfo( $attachment_path );

		// If we don't have complete filename return false
		if ( empty( $path['extension'] ) ) {
			return false;
		}

		$backup_name = trailingslashit( $path['dirname'] ) . $path['filename'] . '.bak.' . $path['extension'];

		return $backup_name;
	}

	/**
	 * Deletes all the backup files when an attachment is deleted
	 * Update resmush List
	 * Update Super Smush image count
	 *
	 * @param $image_id
	 */
	function delete_images( $image_id ) {
		global $wpsmush_db;

		// Update the savings cache
		$wpsmush_db->resize_savings( true );

		// Update the savings cache
		$wpsmush_db->conversion_savings( true );

		// If no image id provided
		if ( empty( $image_id ) ) {
			return false;
		}

		// Check and Update resmush list
		if ( $resmush_list = get_option( 'wp-smush-resmush-list' ) ) {
			global $wpsmushit_admin;
			$wpsmushit_admin->update_resmush_list( $image_id, 'wp-smush-resmush-list' );
		}

		/** Delete Backups  */
		// Check if we have any smush data for image
		$this->delete_backup_files( $image_id );
	}

	/**
	 * Return Global stats
	 *
	 * Stats sent
	 *
	 *  array( 'total_images','bytes', 'human', 'percent')
	 *
	 * @return array|bool|mixed
	 */
	function send_smush_stats() {
		global $wpsmushit_admin;

		$stats = $wpsmushit_admin->global_stats();

		$required_stats = array( 'total_images', 'bytes', 'human', 'percent' );

		$stats = is_array( $stats ) ? array_intersect_key( $stats, array_flip( $required_stats ) ) : array();

		return $stats;
	}

	/**
	 * Smushes the upfront images and Updates the respective stats
	 *
	 * @param $attachment_id
	 * @param $stats
	 *
	 * @return mixed
	 */
	function smush_upfront_images( $attachment_id, $stats ) {
		// Check if upfront is active or not
		if ( empty( $attachment_id ) || ! class_exists( 'Upfront' ) ) {
			return $stats;
		}

		// Set attachment id and Media type
		$this->attachment_id = $attachment_id;
		$this->media_type    = 'upfront';

		// Get post meta to check for Upfront images
		$upfront_images = get_post_meta( $attachment_id, 'upfront_used_image_sizes', true );

		// If there is no upfront meta for the image
		if ( ! $upfront_images || empty( $upfront_images ) || ! is_array( $upfront_images ) ) {
			return $stats;
		}
		// Loop over all the images in upfront meta
		foreach ( $upfront_images as $element_id => $image ) {
			if ( isset( $image['is_smushed'] ) && 1 == $image['is_smushed'] ) {
				continue;
			}
			// Get the image path and smush it
			if ( isset( $image['path'] ) && file_exists( $image['path'] ) ) {
				$res = $this->do_smushit( $image['path'] );
				// If sizes key is not yet initialised
				if ( empty( $stats['sizes'] ) ) {
					$stats['sizes'] = array();
				}

				// If the smushing was successful
				if ( ! is_wp_error( $res ) && ! empty( $res['data'] ) ) {
					if ( $res['data']->bytes_saved > 0 ) {
						// Update attachment stats
						$stats['sizes'][ $element_id ] = (object) $this->_array_fill_placeholders( $this->_get_size_signature(), (array) $res['data'] );
					}

					// Update upfront stats for the element id
					$upfront_images[ $element_id ]['is_smushed'] = 1;
				}
			}
		}
		// Finally Update the upfront meta key
		update_post_meta( $attachment_id, 'upfront_used_image_sizes', $upfront_images );

		return $stats;
	}

	/**
	 * Checks the current settings and returns the value whether to enable or not the resmush option
	 *
	 * @param $id
	 * @param $wp_smush_data
	 *
	 * @return bool
	 */
	function show_resmush( $id = '', $wp_smush_data ) {
		global $wpsmush_resize;
		// Resmush: Show resmush link, Check if user have enabled smushing the original and full image was skipped
		// Or: If keep exif is unchecked and the smushed image have exif
		// PNG To JPEG
		if ( $this->smush_original ) {
			// IF full image was not smushed
			if ( ! empty( $wp_smush_data ) && empty( $wp_smush_data['sizes']['full'] ) ) {
				return true;
			}
		}

		// If image needs to be resized
		if ( $wpsmush_resize->should_resize( $id ) ) {
			return true;
		}

		// EXIF Check
		if ( ! $this->keep_exif ) {
			// If Keep Exif was set to true initially, and since it is set to false now
			if ( isset( $wp_smush_data['stats']['keep_exif'] ) && $wp_smush_data['stats']['keep_exif'] == 1 ) {
				return true;
			}
		}

		// PNG to JPEG
		global $wpsmush_pngjpg;
		if ( $wpsmush_pngjpg->can_be_converted( $id ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Calculate saving percentage from existing and current stats
	 *
	 * @param $stats
	 * @param $existing_stats
	 *
	 * @return float
	 */
	function calculate_percentage( $stats = '', $existing_stats = '' ) {
		if ( empty( $stats ) || empty( $existing_stats ) ) {
			return 0;
		}
		$size_before = ! empty( $stats->size_before ) ? $stats->size_before : $existing_stats->size_before;
		$size_after  = ! empty( $stats->size_after ) ? $stats->size_after : $existing_stats->size_after;
		$savings     = $size_before - $size_after;
		if ( $savings > 0 ) {
			$percentage = ( $savings / $size_before ) * 100;
			$percentage = $percentage > 0 ? round( $percentage, 2 ) : $percentage;

			return $percentage;
		}

		return 0;
	}

	/**
	 * Calculate saving percentage for a given size stats
	 *
	 * @param $stats
	 *
	 * @return float|int
	 */
	function calculate_percentage_from_stats( $stats ) {
		if ( empty( $stats ) || ! isset( $stats->size_before, $stats->size_after ) ) {
			return 0;
		}

		$savings = $stats->size_before - $stats->size_after;
		if ( $savings > 0 ) {
			$percentage = ( $savings / $stats->size_before ) * 100;
			$percentage = $percentage > 0 ? round( $percentage, 2 ) : $percentage;

			return $percentage;
		}
	}

	/**
	 * Clear up all the backup files for the image, if any
	 *
	 * @param $image_id
	 */
	function delete_backup_files( $image_id ) {
		$smush_meta = get_post_meta( $image_id, $this->smushed_meta_key, true );
		if ( empty( $smush_meta ) ) {
			// Return if we don't have any details
			return;
		}

		// Get the attachment details
		$meta = wp_get_attachment_metadata( $image_id );

		// Attachment file path
		$file = get_attached_file( $image_id );

		// Get the backup path
		$backup_name = $this->get_image_backup_path( $file );

		// If file exists, corresponding to our backup path, delete it
		@unlink( $backup_name );

		// Check meta for rest of the sizes
		if ( ! empty( $meta ) && ! empty( $meta['sizes'] ) ) {
			foreach ( $meta['sizes'] as $size ) {
				// Get the file path
				if ( empty( $size['file'] ) ) {
					continue;
				}

				// Image Path and Backup path
				$image_size_path  = path_join( dirname( $file ), $size['file'] );
				$image_bckup_path = $this->get_image_backup_path( $image_size_path );
				@unlink( $image_bckup_path );
			}
		}
	}

	/**
	 * Manually Dismiss Smush Upgrade notice
	 */
	function dismiss_smush_upgrade() {
		if ( isset( $_GET['remove_smush_upgrade_notice'] ) && 1 == $_GET['remove_smush_upgrade_notice'] ) {
			global $wpsmushit_admin;
			$wpsmushit_admin->dismiss_upgrade_notice( false );
		}
	}

	/**
	 * Iterate over all the size stats and calculate the total stats
	 *
	 * @param $stats
	 *
	 * @return mixed
	 */
	function total_compression( $stats ) {
		$stats['stats']['size_before'] = $stats['stats']['size_after'] = $stats['stats']['time'] = 0;
		foreach ( $stats['sizes'] as $size_stats ) {
			$stats['stats']['size_before'] += ! empty( $size_stats->size_before ) ? $size_stats->size_before : 0;
			$stats['stats']['size_after']  += ! empty( $size_stats->size_after ) ? $size_stats->size_after : 0;
			$stats['stats']['time']        += ! empty( $size_stats->time ) ? $size_stats->time : 0;
		}
		$stats['stats']['bytes'] = ! empty( $stats['stats']['size_before'] ) && $stats['stats']['size_before'] > $stats['stats']['size_after'] ? $stats['stats']['size_before'] - $stats['stats']['size_after'] : 0;
		if ( ! empty( $stats['stats']['bytes'] ) && ! empty( $stats['stats']['size_before'] ) ) {
			$stats['stats']['percent'] = ( $stats['stats']['bytes'] / $stats['stats']['size_before'] ) * 100;
		}

		return $stats;
	}

	/**
	 * Smush and Resizing Stats Combined together
	 *
	 * @param $smush_stats
	 * @param $resize_savings
	 *
	 * @return array Array of all the stats
	 */
	function combined_stats( $smush_stats, $resize_savings ) {
		if ( empty( $smush_stats ) || empty( $resize_savings ) ) {
			return $smush_stats;
		}

		// Initialize key full if not there already
		if ( ! isset( $smush_stats['sizes']['full'] ) ) {
			$smush_stats['sizes']['full']              = new stdClass();
			$smush_stats['sizes']['full']->bytes       = 0;
			$smush_stats['sizes']['full']->size_before = 0;
			$smush_stats['sizes']['full']->size_after  = 0;
			$smush_stats['sizes']['full']->percent     = 0;
		}

		// Full Image
		if ( ! empty( $smush_stats['sizes']['full'] ) ) {
			$smush_stats['sizes']['full']->bytes       = ! empty( $resize_savings['bytes'] ) ? $smush_stats['sizes']['full']->bytes + $resize_savings['bytes'] : $smush_stats['sizes']['full']->bytes;
			$smush_stats['sizes']['full']->size_before = ! empty( $resize_savings['size_before'] ) && ( $resize_savings['size_before'] > $smush_stats['sizes']['full']->size_before ) ? $resize_savings['size_before'] : $smush_stats['sizes']['full']->size_before;
			$smush_stats['sizes']['full']->percent     = ! empty( $smush_stats['sizes']['full']->bytes ) && $smush_stats['sizes']['full']->size_before > 0 ? ( $smush_stats['sizes']['full']->bytes / $smush_stats['sizes']['full']->size_before ) * 100 : $smush_stats['sizes']['full']->percent;

			$smush_stats['sizes']['full']->size_after = $smush_stats['sizes']['full']->size_before - $smush_stats['sizes']['full']->bytes;

			$smush_stats['sizes']['full']->percent = round( $smush_stats['sizes']['full']->percent, 1 );
		}

		$smush_stats = $this->total_compression( $smush_stats );

		return $smush_stats;
	}

	/**
	 * Combine Savings from PNG to JPG conversion with smush stats
	 *
	 * @param array $stats               Savings from Smushing the image.
	 * @param array $conversion_savings  Savings from converting the PNG to JPG.
	 *
	 * @return Object|array Total Savings
	 */
	function combine_conversion_stats( $stats, $conversion_savings ) {
		if ( empty( $stats ) || empty( $conversion_savings ) ) {
			return $stats;
		}
		foreach ( $conversion_savings as $size_k => $savings ) {

			// Initialize Object for size
			if ( empty( $stats['sizes'][ $size_k ] ) ) {
				$stats['sizes'][ $size_k ]              = new stdClass();
				$stats['sizes'][ $size_k ]->bytes       = 0;
				$stats['sizes'][ $size_k ]->size_before = 0;
				$stats['sizes'][ $size_k ]->size_after  = 0;
				$stats['sizes'][ $size_k ]->percent     = 0;
			}

			if ( ! empty( $stats['sizes'][ $size_k ] ) && ! empty( $savings ) ) {
				$stats['sizes'][ $size_k ]->bytes       = $stats['sizes'][ $size_k ]->bytes + $savings['bytes'];
				$stats['sizes'][ $size_k ]->size_before = $stats['sizes'][ $size_k ]->size_before > $savings['size_before'] ? $stats['sizes'][ $size_k ]->size_before : $savings['size_before'];
				$stats['sizes'][ $size_k ]->percent     = ! empty( $stats['sizes'][ $size_k ]->bytes ) && $stats['sizes'][ $size_k ]->size_before > 0 ? ( $stats['sizes'][ $size_k ]->bytes / $stats['sizes'][ $size_k ]->size_before ) * 100 : $stats['sizes'][ $size_k ]->percent;
				$stats['sizes'][ $size_k ]->percent     = round( $stats['sizes'][ $size_k ]->percent, 1 );
			}
		}

		$stats = $this->total_compression( $stats );

		return $stats;
	}

	/**
	 * Original File path
	 *
	 * @param string $original_file
	 *
	 * @return string File Path
	 */
	function original_file( $original_file = '' ) {
		$uploads     = wp_get_upload_dir();
		$upload_path = $uploads['basedir'];

		return path_join( $upload_path, $original_file );
	}

	/**
	 * Check whether to show warning or not for Pro users, if they don't have a valid install
	 *
	 * @return bool
	 */
	function show_warning() {
		// If it's a free setup, Go back right away!
		if ( ! $this->validate_install() ) {
			return false;
		}

		global $wpsmushit_admin;
		// Return. If we don't have any headers
		if ( ! isset( $wpsmushit_admin->api_headers ) ) {
			return false;
		}

		// Show warning, if function says it's premium and api says not premium
		if ( isset( $wpsmushit_admin->api_headers['is_premium'] ) && ! intval( $wpsmushit_admin->api_headers['is_premium'] ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Send JSON response whether to show or not the warning
	 */
	function show_warning_ajax() {
		$show = $this->show_warning();
		wp_send_json( intval( $show ) );
	}

	/**
	 * Initialize the Smush Async class
	 */
	function wp_smush_async() {
		// Include Smush Async class.
		require_once WP_SMUSH_DIR . 'lib/integrations/class-wp-smush-s3.php';

		// Don't load the Async task, if user not logged in or not in backend
		if ( ! is_admin() || ! is_user_logged_in() ) {
			return;
		}

		// Check if Async is disabled
		if ( defined( 'WP_SMUSH_ASYNC' ) && ! WP_SMUSH_ASYNC ) {
			return;
		}

		// Instantiate Class
		new WpSmushAsync();
		new WpSmushEditorAsync();
	}

	/**
	 * Send a smush request for the attachment
	 *
	 * @param $id Attachment ID
	 */
	function wp_smush_handle_async( $id ) {
		// If we don't have image id, or the smush is already in progress for the image, return
		if ( empty( $id ) || get_option( 'smush-in-progress-' . $id, false ) || get_option( "wp-smush-restore-$id", false ) ) {
			return;
		}

		// If auto Smush is disabled
		if ( ! $this->is_auto_smush_enabled() ) {
			return;
		}

		/**
		 * Filter: wp_smush_image
		 *
		 * Whether to smush the given attachment id or not
		 *
		 * @param $skip bool, whether to Smush image or not
		 *
		 * @param $Attachment Id, Attachment id of the image being processed
		 */
		if ( ! apply_filters( 'wp_smush_image', true, $id ) ) {
			return;
		}

		global $wpsmushit_admin;
		$wpsmushit_admin->smush_single( $id, true );
	}

	/**
	 * Send a smush request for the attachment
	 *
	 * @param $id Attachment ID
	 */
	function wp_smush_handle_editor_async( $id, $post_data ) {
		// If we don't have image id, or the smush is already in progress for the image, return
		if ( empty( $id ) || get_option( "smush-in-progress-$id", false ) || get_option( "wp-smush-restore-$id", false ) ) {
			return;
		}

		// If auto Smush is disabled
		if ( ! $this->is_auto_smush_enabled() ) {
			return;
		}

		/**
		 * Filter: wp_smush_image
		 *
		 * Whether to smush the given attachment id or not
		 *
		 * @param $skip bool, whether to Smush image or not
		 *
		 * @param $Attachment Id, Attachment id of the image being processed
		 */
		if ( ! apply_filters( 'wp_smush_image', true, $id ) ) {
			return;
		}

		// If filepath is not set or file doesn't exists
		if ( ! isset( $post_data['filepath'] ) || ! file_exists( $post_data['filepath'] ) ) {
			return;
		}

		// Send image for smushing
		$res = $this->do_smushit( $post_data['filepath'] );

		// Exit if smushing wasn't successful
		if ( is_wp_error( $res ) || empty( $res['success'] ) || ! $res['success'] ) {
			return;
		}

		// Update stats if it's the full size image
		// Return if it's not the full image size
		if ( $post_data['filepath'] != get_attached_file( $post_data['postid'] ) ) {
			return;
		}

		// Get the existing Stats
		$smush_stats = get_post_meta( $post_data['postid'], $this->smushed_meta_key, true );
		$stats_full  = ! empty( $smush_stats['sizes'] ) && ! empty( $smush_stats['sizes']['full'] ) ? $smush_stats['sizes']['full'] : '';

		if ( empty( $stats_full ) ) {
			return;
		}

		// store the original image size
		$stats_full->size_before = ( ! empty( $stats_full->size_before ) && $stats_full->size_before > $res['data']->before_size ) ? $stats_full->size_before : $res['data']->before_size;
		$stats_full->size_after  = $res['data']->after_size;

		// Update compression percent and bytes saved for each size
		$stats_full->bytes = $stats_full->size_before - $stats_full->size_after;

		$stats_full->percent          = $this->calculate_percentage_from_stats( $stats_full );
		$smush_stats['sizes']['full'] = $stats_full;

		// Update Stats
		update_post_meta( $post_data['postid'], $this->smushed_meta_key, $smush_stats );
	}

	/**
	 * Registers smush action for HUB API
	 *
	 * @param $actions
	 *
	 * @return mixed
	 */
	function smush_stats( $actions ) {
		$actions['smush_get_stats'] = array( $this, 'smush_attachment_count' );

		return $actions; // always return at least the original array so we don't mess up other integrations
	}

	/**
	 * Send stats to Hub
	 *
	 * @return array An array containing Total, Smushed, Unsmushed Images count and savings if images are alreay smushed
	 */
	function smush_attachment_count( $params, $action, $request ) {
		$stats = array(
			'count_total'     => 0,
			'count_smushed'   => 0,
			'count_unsmushed' => 0,
			'savings'         => array(),
		);

		global $wpsmushit_admin;
		if ( ! isset( $wpsmushit_admin->stats ) ) {
			// Setup stats, if not set already
			$wpsmushit_admin->setup_global_stats();
		}
		// Total, Smushed, Unsmushed, Savings
		$stats['count_total']   = $wpsmushit_admin->total_count;
		$stats['count_smushed'] = $wpsmushit_admin->smushed_count;
		// Considering the images to be resmushed
		$stats['count_unsmushed'] = $wpsmushit_admin->remaining_count;
		$stats['savings']         = $wpsmushit_admin->stats;

		$request->send_json_success( $stats );
	}

	/**
	 * Replace the old API message with the latest one if it doesn't exists already
	 *
	 * @param array $api_message
	 *
	 * @return null
	 */
	function add_api_message( $api_message = array() ) {
		if ( empty( $api_message ) || ! sizeof( $api_message ) || empty( $api_message['timestamp'] ) || empty( $api_message['message'] ) ) {
			return null;
		}
		$o_api_message = get_site_option( WP_SMUSH_PREFIX . 'api_message', array() );
		if ( array_key_exists( $api_message['timestamp'], $o_api_message ) ) {
			return null;
		}
		$api_message['status'] = 'show';

		$message                              = array();
		$message[ $api_message['timestamp'] ] = array(
			'message' => sanitize_text_field( $api_message['message'] ),
			'type'    => sanitize_text_field( $api_message['type'] ),
			'status'  => 'show',
		);
		update_site_option( WP_SMUSH_PREFIX . 'api_message', $message );
	}

	/**
	 * Store/Perform updates as per the plugin version
	 *
	 * @uses $wpsmush_helper, $wpdb, $wpsmush_dir
	 *
	 * @return array|mixed|null|void
	 *
	 * Source: Stackoverflow
	 * https://wordpress.stackexchange.com/a/49797/32466
	 */
	public function getOptions() {
		// already did the checks
		if ( isset( $this->options ) ) {
			return $this->options;
		}

		// first call, get the options
		$options = get_option( self::OPTION_NAME );

		// options exist
		if ( $options !== false ) {

			$new_version = version_compare( $options['version'], WP_SMUSH_VERSION, '!=' );
			// $desync      = array_diff_key( $this->defaults, $options ) !== array_diff_key( $options, $this->defaults );
			// update options if version changed
			if ( $new_version ) {

				$new_options = array();

				// check for new options and set defaults if necessary
				foreach ( $this->defaults as $option => $value ) {
					$new_options[ $option ] = isset( $options[ $option ] ) ? $options[ $option ] : $value;
				}

				// update version info
				$new_options['version'] = WP_SMUSH_VERSION;

				update_option( self::OPTION_NAME, $new_options );
				$this->options = $new_options;

				// no update was required
			} else {
				$this->options = $options;
			}

			// new install (plugin was just activated)
		} else {
			// Store the version details
			update_option( self::OPTION_NAME, $this->defaults );
			$this->options = $this->defaults;
		}

		return $this->options;
	}

	/**
	 * Add Smush Policy to "Privace Policy" page during creation.
	 *
	 * @since 2.3.0
	 */
	public function add_policy() {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}

		$content  = '<h3>' . __( 'Plugin: Smush', 'wp-smushit' ) . '</h3>';
		$content .=
			'<p>' . __( 'Note: Smush does not interact with end users on your website. The only input option Smush has is to a newsletter subscription for site admins only. If you would like to notify your users of this in your privacy policy, you can use the information below.', 'wp-smushit' ) . '</p>';
		$content .=
			'<p>' . __( 'Smush sends images to the WPMU DEV servers to optimize them for web use. This includes the transfer of EXIF data. The EXIF data will either be stripped or returned as it is. It is not stored on the WPMU DEV servers.', 'wp-smushit' ) . '</p>';
		$content .=
			'<p>' . sprintf(
				__( "Smush uses the Stackpath Content Delivery Network (CDN). Stackpath may store web log information of site visitors, including IPs, UA, referrer, Location and ISP info of site visitors for 7 days. Files and images served by the CDN may be stored and served from countries other than your own. Stackpath's privacy policy can be found %1\$shere%2\$s.", 'wp-smushit' ),
				'<a href="https://www.stackpath.com/legal/privacy-statement/" target="_blank">',
				'</a>'
			) . '</p>';

		if ( strpos( WP_SMUSH_DIR, 'wp-smushit' ) !== false ) {
			// Only for wordpress.org members
			$content .=
				'<p>' . __( 'Smush uses a third-party email service (Drip) to send informational emails to the site administrator. The administrator\'s email address is sent to Drip and a cookie is set by the service. Only administrator information is collected by Drip.', 'wp-smushit' ) . '</p>';
		}

		wp_add_privacy_policy_content(
			__( 'WP Smush', 'wp-smushit' ),
			wp_kses_post( wpautop( $content, false ) )
		);
	}

}
