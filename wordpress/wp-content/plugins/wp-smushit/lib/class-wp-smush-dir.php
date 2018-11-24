<?php
/**
 * Directory Smush: WP_Smush_Dir class
 *
 * @package WP_Smush
 * @subpackage Admin
 * @since 2.6
 *
 * @author Umesh Kumar <umesh@incsub.com>
 *
 * @copyright (c) 2016, Incsub (http://incsub.com)
 */

require_once 'utils/class-wp-smush-directory-scanner.php';
require_once 'ui/class-wp-smush-dir-ui.php';

if ( ! class_exists( 'WP_Smush_Dir' ) ) {
	/**
	 * Class WP_Smush_Dir
	 */
	class WP_Smush_Dir {
		/**
		 * Contains a list of optimised images.
		 *
		 * @var $optimised_images
		 */
		public $optimised_images;

		/**
		 * Flag to check if dir smush table exist.
		 *
		 * @var $table_exist
		 */
		public $table_exist;

		/**
		 * Total Stats for the image optimisation.
		 *
		 * @var $stats
		 */
		public $stats;

		/**
		 * Directory scanner.
		 *
		 * @var WP_Smush_Directory_Scanner
		 */
		public $scanner;

		/**
		 * Directory Smush UI.
		 *
		 * @since 2.8.1
		 *
		 * @var WP_Smush_Dir_UI
		 */
		private $ui;

		/**
		 * WP_Smush_Dir constructor.
		 */
		public function __construct() {
			if ( ! $this->should_continue() ) {
				// Remove directory smush from tabs if not required.
				add_filter( 'smush_setting_tabs', array( $this, 'remove_directory_tab' ) );

				return;
			}

			$this->ui      = new WP_Smush_Dir_UI();
			$this->scanner = new WP_Smush_Directory_Scanner();

			if ( ! $this->scanner->is_scanning() ) {
				$this->scanner->reset_scan();
			}

			// Check directory smush table after screen is set.
			add_action( 'current_screen', array( $this, 'check_table' ) );

			// Check to see if the scanner should be running.
			add_action( 'admin_footer', array( $this, 'check_scan' ) );

			// Handle Ajax request 'smush_get_directory_list'.
			add_action( 'wp_ajax_smush_get_directory_list', array( $this, 'directory_list' ) );

			// Scan the given directory path for the list of images.
			add_action( 'wp_ajax_image_list', array( $this, 'image_list' ) );

			// Handle Ajax Request to optimise images.
			add_action( 'wp_ajax_optimise', array( $this, 'optimise' ) );

			/**
			 * Scanner ajax actions.
			 *
			 * @since 2.8.1
			 */
			add_action( 'wp_ajax_directory_smush_start', array( $this, 'directory_smush_start' ) );
			add_action( 'wp_ajax_directory_smush_check_step', array( $this, 'directory_smush_check_step' ) );
			add_action( 'wp_ajax_directory_smush_finish', array( $this, 'directory_smush_finish' ) );
			add_action( 'wp_ajax_directory_smush_cancel', array( $this, 'directory_smush_cancel' ) );
		}

		/**
		 * Run the scanner on page refresh (if it's running).
		 *
		 * @since 2.8.1
		 */
		public function check_scan() {
			if ( $this->scanner->is_scanning() ) {
				?>
				<script>
					jQuery( document ).ready( function() {
						jQuery('#wp-smush-progress-dialog').show();
						window.WP_Smush.directory.scanner.scan();
					});
				</script>
				<?php
			}
		}

		/**
		 * Directory Smush: Start smush.
		 *
		 * @since 2.8.1
		 */
		public function directory_smush_start() {
			$this->scanner->init_scan();
			wp_send_json_success();
		}

		/**
		 * Directory Smush: Smush step.
		 *
		 * @since 2.8.1
		 */
		public function directory_smush_check_step() {
			$urls         = $this->get_scanned_images();
			$current_step = absint( $_POST['step'] ); // Input var ok.

			$this->scanner->update_current_step( $current_step );

			if ( isset( $urls[ $current_step ] ) ) {
				$this->optimise_image( $urls[ $current_step ]['id'] );
			}

			wp_send_json_success();
		}

		/**
		 * Directory Smush: Finish smush.
		 *
		 * @since 2.8.1
		 */
		public function directory_smush_finish() {
			$items = isset( $_POST['items'] ) ? absint( $_POST['items'] ) : 0; // Input var ok.
			$failed = isset( $_POST['failed'] ) ? absint( $_POST['failed'] ) : 0; // Input var ok.
			// If any images failed to smush, store count.
			if ( $failed > 0 ) {
				set_transient( 'wp-smush-dir-scan-failed-items', $failed, 60 * 5 ); // 5 minutes max.
			}
			// Store optimized items count.
			set_transient( 'wp-smush-show-dir-scan-notice', $items, 60 * 5 ); // 5 minutes max.
			$this->scanner->reset_scan();
			wp_send_json_success();
		}

		/**
		 * Directory Smush: Cancel smush.
		 *
		 * @since 2.8.1
		 */
		public function directory_smush_cancel() {
			$this->scanner->reset_scan();
			wp_send_json_success();
		}

		/**
		 * Handles the ajax request for image optimisation in a folder
		 *
		 * @param int $image_id  Image ID.
		 */
		private function optimise_image( $image_id ) {
			global $wpdb, $wp_smush, $wpsmushit_admin;

			$error_msg = '';

			// No image ID.
			if ( ! isset( $image_id ) ) {
				$error_msg = esc_html__( 'Incorrect image id', 'wp-smushit' );
				wp_send_json_error( $error_msg );
			}

			// Check smush limit for free users.
			if ( ! $wp_smush->validate_install() ) {
				/**
				 * Free version bulk smush, check the transient counter value.
				 *
				 * @var WpSmushitAdmin $wpsmushit_admin
				 */
				$should_continue = $wpsmushit_admin->check_bulk_limit( false, 'dir_sent_count' );

				// Send a error for the limit.
				if ( ! $should_continue ) {
					wp_send_json_error(
						array(
							'error'    => 'dir_smush_limit_exceeded',
							'continue' => false,
						)
					);
				}
			}

			$id = intval( $image_id );
			if ( ! $scanned_images = wp_cache_get( 'wp_smush_scanned_images' ) ) {
				$scanned_images = $this->get_scanned_images();
			}

			$image = $this->get_image( $id, '', $scanned_images );

			if ( empty( $image ) ) {
				// If there are no stats.
				$error_msg = esc_html__( 'Could not find image id in last scanned images', 'wp-smushit' );
				wp_send_json_error( $error_msg );
			}

			$path = $image['path'];

			// We have the image path, optimise.
			$smush_results = $wp_smush->do_smushit( $path );

			if ( is_wp_error( $smush_results ) ) {
				/* @var WP_Error $smush_results */
				$error_msg = $smush_results->get_error_message();
			} elseif ( empty( $smush_results['data'] ) ) {
				// If there are no stats.
				$error_msg = esc_html__( "Image couldn't be optimized", 'wp-smushit' );
			}

			if ( ! empty( $error_msg ) ) {
				// Store the error in DB. All good, Update the stats.
				$wpdb->query(
					$wpdb->prepare(
						"UPDATE {$wpdb->prefix}smush_dir_images SET error=%s WHERE id=%d LIMIT 1",
						$error_msg, $id
					)
				); // Db call ok; no-cache ok.

				wp_send_json_error(
					array(
						'error' => $error_msg,
						'image' => array(
							'id' => $id,
						),
					)
				);
			}

			// Get file time.
			$file_time = @filectime( $path );

			// If super-smush enabled, update supersmushed meta value also.
			$lossy = $wp_smush->lossy_enabled ? 1 : 0;

			// All good, Update the stats.
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$wpdb->prefix}smush_dir_images SET image_size=%d, file_time=%d, lossy=%s WHERE id=%d LIMIT 1",
					$smush_results['data']->after_size, $file_time, $lossy, $id
				)
			); // Db call ok; no-cache ok.

			// Update bulk limit transient.
			$wpsmushit_admin->update_smush_count( 'dir_sent_count' );
		}

		/**
		 * Do not display Directory smush for subsites.
		 *
		 * @return bool True/False, whether to display the Directory smush or not
		 */
		public function should_continue() {
			// Do not show directory smush, if not main site in a network.
			if ( ! is_main_site() || is_network_admin() ) {
				return false;
			}

			return true;
		}

		/**
		 * Create the Smush image table to store the paths of scanned images, and stats
		 */
		public function create_table() {
			global $wpdb;

			$charset_collate = $wpdb->get_charset_collate();

			/**
			 * Table: wp_smush_dir_images
			 * Columns:
			 * id         -> Auto Increment ID
			 * path       -> Absolute path to the image file
			 * resize     -> Whether the image was resized or not
			 * lossy      -> Whether the image was super-smushed/lossy or not
			 * image_size -> Current image size post optimisation
			 * orig_size  -> Original image size before optimisation
			 * file_time  -> Unix time for the file creation, to match it against the current creation time,
			 *                  in order to confirm if it is optimised or not
			 * last_scan  -> Timestamp, Get images form last scan by latest timestamp
			 *                  are from latest scan only and not the whole list from db
			 * meta       -> For any future use
			 */
			$sql = "CREATE TABLE {$wpdb->base_prefix}smush_dir_images (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				path text NOT NULL,
				path_hash CHAR(32),
				resize varchar(55),
				lossy varchar(55),
				error varchar(55) DEFAULT NULL,
				image_size int(10) unsigned,
				orig_size int(10) unsigned,
				file_time int(10) unsigned,
				last_scan timestamp DEFAULT '0000-00-00 00:00:00',
				meta text,
				UNIQUE KEY id (id),
				UNIQUE KEY path_hash (path_hash),
				KEY image_size (image_size)
			) $charset_collate;";

			// Include the upgrade library to initialize a table.
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );

			// Set flag.
			$this->table_exist = true;
		}

		/**
		 * Update path_hash, and store a flag if all the rows were updated
		 *
		 * @return null
		 *
		 * @todo, Stop running this function after 2-3 updates using version check
		 */
		public function update_dir_path_hash() {
			// If we've already performed the update.
			if ( get_option( 'smush-directory-path-hash-updated', false ) ) {
				return null;
			}

			global $wpsmush_helper, $wpdb;

			// Check if column exists.
			if ( ! $wpsmush_helper->table_column_exists( $wpdb->prefix . 'smush_dir_images', 'path_hash' ) ) {
				return null;
			}

			// Update the rows.
			$wpdb->query( "UPDATE {$wpdb->prefix}smush_dir_images SET path_hash = MD5(path) WHERE path IS NOT NULL" );

			// Check if there are any pending rows that needs to be updated.
			$pending_rows = "SELECT count(*) FROM {$wpdb->prefix}smush_dir_images WHERE path_hash is NULL AND path IS NOT NULL";
			$index_exists = "SHOW INDEX FROM {$wpdb->prefix}smush_dir_images WHERE KEY_NAME = 'path'";
			// If all the rows are updated and Index exists.
			if ( ! $wpdb->get_var( $pending_rows ) && $wpdb->get_var( $index_exists ) != null ) {
				$wpsmush_helper->drop_index( $wpdb->prefix . 'smush_dir_images', 'path' );
				update_option( 'smush-directory-path-hash-updated', 1 );
			}
		}

		/**
		 * Get the image ids and path for last scanned images
		 *
		 * @return array Array of last scanned images containing image id and path
		 */
		public function get_scanned_images() {
			global $wpdb;

			$results = $wpdb->get_results( "SELECT id, path, orig_size FROM {$wpdb->prefix}smush_dir_images WHERE last_scan = (SELECT MAX(last_scan) FROM {$wpdb->prefix}smush_dir_images )  GROUP BY id ORDER BY id", ARRAY_A ); // Db call ok; no-cache ok.

			// Return image ids.
			if ( is_wp_error( $results ) ) {
				error_log( sprintf( 'WP Smush Query Error in %s at %s: %s', __FILE__, __LINE__, $results->get_error_message() ) );
				$results = array();
			}

			return $results;
		}

		/**
		 * Check if the image file is media library file
		 *
		 * @param string $file_path  File path.
		 *
		 * @return bool
		 */
		private function is_media_library_file( $file_path ) {
			$upload_dir  = wp_upload_dir();
			$upload_path = $upload_dir['path'];

			// Get the base path of file.
			$base_dir = dirname( $file_path );
			if ( $base_dir === $upload_path ) {
				return true;
			}

			return false;
		}

		/**
		 * Return a directory/File list
		 *
		 * PHP Connector
		 */
		public function directory_list() {
			// Check For permission.
			if ( ! current_user_can( 'manage_options' ) || ! is_user_logged_in() ) {
				wp_send_json_error( __( 'Unauthorized', 'wp-smushit' ) );
			}
			// Verify nonce.
			check_ajax_referer( 'smush_get_dir_list', 'list_nonce' );

			// Get the root path for a main site or subsite.
			$root = realpath( $this->get_root_path() );

			$dir      = ( isset( $_GET['dir'] ) && ! is_array( $_GET['dir'] ) ) ? ltrim( sanitize_text_field( wp_unslash( $_GET['dir'] ) ), '/' ) : null; // Input var ok.
			$post_dir = strlen( $dir ) > 1 ? path_join( $root, $dir ) : $root . $dir;
			$post_dir = realpath( rawurldecode( $post_dir ) );

			// If the final path doesn't contains the root path, bail out.
			if ( ! $root || false === $post_dir || 0 !== strpos( $post_dir, $root ) ) {
				wp_send_json_error( __( 'Unauthorized', 'wp-smushit' ) );
			}

			$supported_image = array(
				'gif',
				'jpg',
				'jpeg',
				'png',
			);

			if ( file_exists( $post_dir ) ) {
				$files = scandir( $post_dir );
				// Exclude hidden files.
				if ( ! empty( $files ) ) {
					$files = preg_grep( '/^([^.])/', $files );
				}
				$return_dir = substr( $post_dir, strlen( $root ) );

				natcasesort( $files );

				if ( count( $files ) !== 0 && ! $this->skip_dir( $post_dir ) ) {
					$tree = array();

					foreach ( $files as $file ) {
						$html_rel  = htmlentities( ltrim( path_join( $return_dir, $file ), '/' ) );
						$html_name = htmlentities( $file );
						$ext       = preg_replace( '/^.*\./', '', $file );

						$file_path = path_join( $post_dir, $file );
						if ( ! file_exists( $file_path ) || '.' === $file || '..' === $file ) {
							continue;
						}

						// Skip unsupported files and files that are already in the media library.
						if ( ! is_dir( $file_path ) && ( ! in_array( $ext, $supported_image, true ) || $this->is_media_library_file( $file_path ) ) ) {
							continue;
						}

						$skip_path = $this->skip_dir( $file_path );

						$tree[] = array(
							'title'        => $html_name,
							'key'          => $html_rel,
							'folder'       => is_dir( $file_path ),
							'lazy'         => ! $skip_path,
							'checkbox'     => true,
							'unselectable' => $skip_path, // Skip Uploads folder - Media Files.
						);
					}

					wp_send_json_success( $tree );
				}
			} // End if().
		}

		/**
		 * Get root path of the installation.
		 *
		 * @return string Root path.
		 */
		public function get_root_path() {
			// If main site.
			if ( is_main_site() ) {

				/**
				 * Sometimes content directories may reside outside
				 * the installation sub directory. We need to make sure
				 * we are selecting the root directory, not installation
				 * directory.
				 *
				 * @see https://xnau.com/finding-the-wordpress-root-path-for-an-alternate-directory-structure/
				 * @see https://app.asana.com/0/14491813218786/487682361460247/f
				 */
				$content_path = explode( '/', WP_CONTENT_DIR );
				// Get root path and explod.
				$root_path = explode( '/', get_home_path() );
				// Find the length of the shortest one.
				$end         = min( count( $content_path ), count( $root_path ) );
				$i           = 0;
				$common_path = array();
				// Add the component if they are the same in both paths.
				while ( $content_path[ $i ] === $root_path[ $i ] && $i < $end ) {
					$common_path[] = $content_path[ $i ];
					$i++;
				}

				return implode( '/', $common_path );
			} else {
				$up = wp_upload_dir();

				return $up['basedir'];
			}
		}

		/**
		 * Get the image list in a specified directory path.
		 *
		 * @since 2.8.1  Added support for selecting files.
		 *
		 * @param string|array $paths  Path where to look for images, or selected images.
		 *
		 * @return array
		 */
		private function get_image_list( $paths = '' ) {
			global $wpsmush_helper;

			// Error with directory tree.
			if ( ! is_array( $paths ) ) {
				wp_send_json_error(
					array(
						'message' => __( 'There was a problem getting the selected directories', 'wp-smushit' ),
					)
				);
			}

			$count     = 0;
			$images    = array();
			$values    = array();
			$timestamp = gmdate( 'Y-m-d H:i:s' );

			/**
			 * Temporary increase the limit.
			 *
			 * @var WpSmushHelper $wpsmush_helper
			 */
			$wpsmush_helper->increase_memory_limit();

			// Iterate over all the selected items (can be either an image or directory).
			foreach ( $paths as $path ) {
				/**
				 * Path is an image.
				 */
				if ( ! is_dir( $path ) && ! $this->is_media_library_file( $path ) && ! strpos( $path, '.bak' ) ) {
					if ( ! $this->is_image( $path ) ) {
						continue;
					}

					// Image already added. Skip.
					if ( in_array( $path, $images, true ) ) {
						continue;
					}

					$images[] = $path;
					$images[] = md5( $path );
					$images[] = @filesize( $path );  // Get the file size.
					$images[] = @filectime( $path ); // Get the file modification time.
					$images[] = $timestamp;
					$values[] = '(%s, %s, %d, %d, %s)';
					$count++;

					// Store the images in db at an interval of 5k.
					if ( $count >= 5000 ) {
						$count = 0;
						$this->store_images( $values, $images );
						$images = $values = array();
					}

					continue;
				}

				/**
				 * Path is a directory.
				 */
				$base_dir = realpath( rawurldecode( $path ) );

				if ( ! $base_dir ) {
					wp_send_json_error(
						array(
							'message' => __( 'Unauthorized', 'wp-smushit' ),
						)
					);
				}

				// Directory Iterator, Exclude . and ..
				$filtered_dir = new WPSmushRecursiveFilterIterator( new RecursiveDirectoryIterator( $base_dir ) );

				// File Iterator.
				$iterator = new RecursiveIteratorIterator( $filtered_dir, RecursiveIteratorIterator::CHILD_FIRST );

				foreach ( $iterator as $file ) {
					// Used in place of Skip Dots, For php 5.2 compatibility.
					if ( basename( $file ) === '..' || basename( $file ) === '.' ) {
						continue;
					}

					// Not a file. Skip.
					if ( ! $file->isFile() ) {
						continue;
					}

					$file_path = $file->getPathname();

					if ( $this->is_image( $file_path ) && ! $this->is_media_library_file( $file_path ) && strpos( $file, '.bak' ) === false ) {
						/** To be stored in DB, Part of code inspired from Ewwww Optimiser  */
						$images[] = $file_path;
						$images[] = md5( $file_path );
						$images[] = $file->getSize();
						$images[] = @filectime( $file_path ); // Get the file modification time.
						$images[] = $timestamp;
						$values[] = '(%s, %s, %d, %d, %s)';
						$count++;
					}

					// Store the images in db at an interval of 5k.
					if ( $count >= 5000 ) {
						$count = 0;
						$this->store_images( $values, $images );
						$images = $values = array();
					}
				} // End foreach().
			} // End foreach().

			// Update rest of the images.
			if ( ! empty( $images ) && $count > 0 ) {
				$this->store_images( $values, $images );
			}

			// Remove scanned images from cache.
			wp_cache_delete( 'wp_smush_scanned_images' );

			// Get the image ids.
			$images = $this->get_scanned_images();

			// Store scanned images in cache.
			wp_cache_add( 'wp_smush_scanned_images', $images );

			return $images;
		}

		/**
		 * Write to the database.
		 *
		 * @since 2.8.1
		 *
		 * @param array $values  Values for query build.
		 * @param array $images  Array of images.
		 */
		private function store_images( $values, $images ) {
			global $wpdb;

			$query = $this->build_query( $values, $images );
			$wpdb->query( $query ); // Db call ok; no-cache ok.
		}

		/**
		 * Build and prepare query from the given values and image array.
		 *
		 * @param array $values  Values.
		 * @param array $images  Images.
		 *
		 * @return bool|string
		 */
		private function build_query( $values, $images ) {
			if ( empty( $images ) || empty( $values ) ) {
				return false;
			}

			global $wpdb;
			$values = implode( ',', $values );

			// Replace with image path and respective parameters.
			$query = "INSERT INTO {$wpdb->prefix}smush_dir_images (path, path_hash, orig_size,file_time,last_scan) VALUES $values ON DUPLICATE KEY UPDATE image_size = IF( file_time < VALUES(file_time), NULL, image_size ), file_time = IF( file_time < VALUES(file_time), VALUES(file_time), file_time ), last_scan = VALUES( last_scan )";
			$query = $wpdb->prepare( $query, $images ); // Db call ok; no-cache ok.

			return $query;
		}

		/**
		 * Sends a Ajax response if no images are found in selected directory.
		 */
		private function send_error() {
			$message = sprintf( "<div class='sui-notice sui-notice-info'><p>%s</p></div>", esc_html__( 'We could not find any images in the selected directory.', 'wp-smushit' ) );
			wp_send_json_error(
				array(
					'message' => $message,
				)
			);
		}

		/**
		 * Handles Ajax request to obtain the Image list within a selected directory path
		 */
		public function image_list() {
			// Check For permission.
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( __( 'Unauthorized', 'wp-smushit' ) );
			}

			// Verify nonce.
			check_ajax_referer( 'smush_get_image_list', 'image_list_nonce' );

			// Check if directory path is set or not.
			if ( empty( $_GET['smush_path'] ) ) { // Input var ok.
				wp_send_json_error( __( 'Empty Directory Path', 'wp-smushit' ) );
			}

			// This will add the images to the database and get the file list.
			$files = $this->get_image_list( $_GET['smush_path'] ); // Input var ok.

			// If files array is empty, send a message.
			if ( empty( $files ) ) {
				$this->send_error();
			}

			// Send response.
			wp_send_json_success( count( $files ) );
		}

		/**
		 * Check whether the given path is a image or not.
		 *
		 * Do not include backup files.
		 *
		 * @param string $path  Image path.
		 *
		 * @return bool
		 */
		private function is_image( $path ) {
			// Check if the path is valid.
			if ( ! file_exists( $path ) || ! $this->is_image_from_extension( $path ) ) {
				return false;
			}

			$a = @getimagesize( $path );

			// If a is not set.
			if ( ! $a || empty( $a ) ) {
				return false;
			}

			$image_type = $a[2];

			if ( in_array( $image_type, array( IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG ) ) ) {
				return true;
			}

			return false;
		}

		/**
		 * Obtain the path to the admin directory.
		 *
		 * @return string
		 *
		 * Thanks @andrezrv (Github)
		 * TODO: this does not properly get the admin path in Bedrock
		 */
		private function get_admin_path() {
			// Replace the site base URL with the absolute path to its installation directory.
			$admin_path = rtrim( str_replace( get_bloginfo( 'url' ) . '/', ABSPATH, get_admin_url() ), '/' );

			// Make it filterable, so other plugins can hook into it.
			$admin_path = apply_filters( 'wp_smush_get_admin_path', $admin_path );

			return $admin_path;
		}

		/**
		 * Check if the given file path is a supported image format
		 *
		 * @param string $path  File path.
		 *
		 * @return bool Whether a image or not
		 */
		private function is_image_from_extension( $path ) {
			$supported_image = array( 'gif', 'jpg', 'jpeg', 'png' );
			$ext             = strtolower( pathinfo( $path, PATHINFO_EXTENSION ) ); // Using strtolower to overcome case sensitive.

			if ( in_array( $ext, $supported_image, true ) ) {
				return true;
			}

			return false;
		}

		/**
		 * Excludes the Media Upload Directory ( Checks for Year and Month ).
		 *
		 * Borrowed from Shortpixel - (y)*
		 * TODO: Add a option to filter images if User have turned off the Year and Month Organize option
		 *
		 * @param string $path  Path.
		 *
		 * @return bool
		 */
		public function skip_dir( $path ) {
			// Admin directory path.
			$admin_dir = $this->get_admin_path();

			// Includes directory path.
			$includes_dir = ABSPATH . WPINC;

			// Upload directory.
			$upload_dir = wp_upload_dir();
			$base_dir   = $upload_dir['basedir'];

			$skip = false;

			// Skip sites folder for multisite.
			if ( false !== strpos( $path, $base_dir . '/sites' ) ) {
				$skip = true;
			} elseif ( false !== strpos( $path, $base_dir ) ) {
				// If matches the current upload path contains one of the year sub folders of the media library.
				$path_arr = explode( '/', str_replace( $base_dir . '/', '', $path ) );
				if ( count( $path_arr ) >= 1
					&& is_numeric( $path_arr[0] ) && $path_arr[0] > 1900 && $path_arr[0] < 2100 // Contains the year sub folder.
					&& ( 1 === count( $path_arr ) // If there is another sub folder then it's the month sub folder.
					|| ( is_numeric( $path_arr[1] ) && $path_arr[1] > 0 && $path_arr[1] < 13 ) )
				) {
					$skip = true;
				}
			} elseif ( ( false !== strpos( $path, $admin_dir ) ) || false !== strpos( $path, $includes_dir ) ) {
				$skip = true;
			}

			// Can be used to skip/include folders matching a specific directory path.
			apply_filters( 'wp_smush_skip_folder', $skip, $path );

			return $skip;
		}

		/**
		 * Search for image from given image id or path.
		 *
		 * @param string $id      Image id to search for.
		 * @param string $path    Image path to search for.
		 * @param array  $images  Image array to search within.
		 *
		 * @return array  Image array or empty array.
		 */
		private function get_image( $id = '', $path = '', $images ) {
			foreach ( $images as $key => $val ) {
				if ( ! empty( $id ) && (int) $val['id'] === $id ) {
					return $images[ $key ];
				} elseif ( ! empty( $path ) && $val['path'] === $path ) {
					return $images[ $key ];
				}
			}

			return array();
		}

		/**
		 * Fetch all the optimised image, calculate stats.
		 *
		 * @param bool $force_update Should force update?
		 *
		 * @return array Total stats.
		 */
		function total_stats( $force_update = false ) {
			// If not forced to update.
			if ( ! $force_update ) {
				// Get stats from cache.
				$total_stats = wp_cache_get( WP_SMUSH_PREFIX . 'dir_total_stats', 'wp-smush' );
				// If we have already calculated the stats and found in cache, return it.
				if ( false !== $total_stats ) {
					return $total_stats;
				}
			}

			global $wpdb;

			$offset    = 0;
			$optimised = 0;
			$limit     = 1000;
			$images    = array();

			$total = $wpdb->get_col( "SELECT count(id) FROM {$wpdb->prefix}smush_dir_images" ); // Db call ok; no-cache ok.

			$total = ! empty( $total ) && is_array( $total ) ? $total[0] : 0;

			$continue = true;

			while ( $continue && $results = $wpdb->get_results( "SELECT path, image_size, orig_size FROM {$wpdb->prefix}smush_dir_images WHERE image_size IS NOT NULL ORDER BY `id` LIMIT $offset, $limit", ARRAY_A ) ) { // Db call ok; no-cache ok.
				if ( ! empty( $results ) ) {
					$images = array_merge( $images, $results );
				}
				$offset += $limit;
				// If offset is above total number, do not query.
				if ( $offset > $total ) {
					$continue = false;
				}
			}

			// Iterate over stats, return count and savings.
			if ( ! empty( $images ) ) {
				// Init the stats array.
				$this->stats = array(
					'path'       => '',
					'image_size' => 0,
					'orig_size'  => 0,
				);

				foreach ( $images as $im ) {
					foreach ( $im as $key => $val ) {
						if ( 'path' === $key ) {
							$this->optimised_images[ $val ] = $im;
							continue;
						}
						$this->stats[ $key ] += (int) $val;
					}
					$optimised++;
				}
			}

			// Get the savings in bytes and percent.
			if ( ! empty( $this->stats ) && ! empty( $this->stats['orig_size'] ) ) {
				$this->stats['bytes']   = ( $this->stats['orig_size'] > $this->stats['image_size'] ) ? $this->stats['orig_size'] - $this->stats['image_size'] : 0;
				$this->stats['percent'] = number_format_i18n( ( ( $this->stats['bytes'] / $this->stats['orig_size'] ) * 100 ), 1 );
				// Convert to human readable form.
				$this->stats['human']   = size_format( $this->stats['bytes'], 1 );
			}

			$this->stats['total']     = $total;
			$this->stats['optimised'] = $optimised;

			// Set stats in cache.
			wp_cache_set( WP_SMUSH_PREFIX . 'dir_total_stats', $this->stats, 'wp-smush' );

			return $this->stats;
		}

		/**
		 * Returns the number of images scanned and optimised
		 *
		 * @return array
		 */
		private function last_scan_stats() {
			global $wpdb;
			$results = $wpdb->get_results( "SELECT id, image_size, orig_size FROM {$wpdb->prefix}smush_dir_images WHERE last_scan = (SELECT MAX(last_scan) FROM {$wpdb->prefix}smush_dir_images ) GROUP BY id", ARRAY_A ); // Db call ok; no-cache ok.
			$total   = count( $results );
			$smushed = 0;
			$stats   = array(
				'image_size' => 0,
				'orig_size'  => 0,
			);

			// Get the Smushed count, and stats sum.
			foreach ( $results as $image ) {
				if ( ! is_null( $image['image_size'] ) ) {
					$smushed ++;
				}
				// Summation of stats.
				foreach ( $image as $k => $v ) {
					if ( 'id' === $k ) {
						continue;
					}
					$stats[ $k ] += $v;
				}
			}

			// Stats.
			$stats['total']   = $total;
			$stats['smushed'] = $smushed;

			return $stats;
		}

		/**
		 * Handles the ajax request for image optimisation in a folder
		 *
		 * TODO: refactor this, don't think that we need all this stuff.
		 */
		public function optimise() {
			global $wpdb, $wp_smush, $wpsmushit_admin;

			// Verify the ajax nonce.
			check_ajax_referer( 'wp_smush_all', 'nonce' );

			$error_msg = '';
			if ( empty( $_GET['image_id'] ) ) { // Input var ok.
				// If there are no stats.
				wp_send_json_error( esc_html__( 'Incorrect image id', 'wp-smushit' ) );
			}

			// Get the last scan stats.
			$last_scan = $this->last_scan_stats();
			$stats     = array();

			// Check smush limit for free users.
			if ( ! $wp_smush->validate_install() ) {
				// Free version bulk smush, check the transient counter value.
				$should_continue = $wpsmushit_admin->check_bulk_limit( false, 'dir_sent_count' );

				// Send a error for the limit.
				if ( ! $should_continue ) {
					wp_send_json_error(
						array(
							'error'    => 'dir_smush_limit_exceeded',
							'continue' => false,
						)
					);
				}
			}

			$id = intval( $_GET['image_id'] ); // Input var ok.
			if ( ! $scanned_images = wp_cache_get( 'wp_smush_scanned_images' ) ) {
				$scanned_images = $this->get_scanned_images();
			}

			$image = $this->get_image( $id, '', $scanned_images );

			if ( empty( $image ) ) {
				// If there are no stats.
				wp_send_json_error( esc_html__( 'Could not find image id in last scanned images', 'wp-smushit' ) );
			}

			$path = $image['path'];

			// We have the image path, optimise.
			$smush_results = $wp_smush->do_smushit( $path );

			if ( is_wp_error( $smush_results ) ) {
				/* @var WP_Error $smush_results */
				$error_msg = $smush_results->get_error_message();
			} elseif ( empty( $smush_results['data'] ) ) {
				// If there are no stats.
				$error_msg = esc_html__( "Image couldn't be optimized", 'wp-smushit' );
			}

			if ( ! empty( $error_msg ) ) {
				// Store the error in DB. All good, Update the stats.
				$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}smush_dir_images SET error=%s WHERE id=%d LIMIT 1", $error_msg, $id ) ); // Db call ok; no-cache ok.

				$error_msg = '<div class="wp-smush-error">' . $error_msg . '</div>';

				wp_send_json_error(
					array(
						'error' => $error_msg,
						'image' => array(
							'id' => $id,
						),
					)
				);
			}
			// Get file time.
			$file_time = @filectime( $path );

			// If super-smush enabled, update supersmushed meta value also.
			$lossy = $wp_smush->lossy_enabled ? 1 : 0;

			// All good, Update the stats.
			$query = "UPDATE {$wpdb->prefix}smush_dir_images SET image_size=%d, file_time=%d, lossy=%s WHERE id=%d LIMIT 1";
			$query = $wpdb->prepare( $query, $smush_results['data']->after_size, $file_time, $lossy, $id );
			$wpdb->query( $query );

			// Get the global stats if current dir smush completed.
			if ( isset( $_GET['get_stats'] ) && 1 == $_GET['get_stats'] ) {
				// This will setup directory smush stats too.
				$wpsmushit_admin->setup_global_stats();
				$stats            = $wpsmushit_admin->stats;
				$stats['total']   = $wpsmushit_admin->total_count;
				$stats['smushed'] = $wpsmushit_admin->smushed_count;
				if ( 1 === $lossy ) {
					$stats['super_smushed'] = $wpsmushit_admin->super_smushed;
				}
				// Set tootltip text to update.
				$stats['tooltip_text'] = ! empty( $stats['total_images'] ) ? sprintf( __( "You've smushed %d images in total.", 'wp-smushit' ), $stats['total_images'] ) : '';
				// Get the total dir smush stats.
				$total = $wpsmushit_admin->dir_stats;
			} else {
				$total = $this->total_stats();
			}

			// Show the image wise stats.
			$image = array(
				'id'          => $id,
				'size_before' => $image['orig_size'],
				'size_after'  => $smush_results['data']->after_size,
			);

			$bytes            = $image['size_before'] - $image['size_after'];
			$image['savings'] = size_format( $bytes, 1 );
			$image['percent'] = $image['size_before'] > 0 ? number_format_i18n( ( ( $bytes / $image['size_before'] ) * 100 ), 1 ) . '%' : 0;

			$data = array(
				'image'       => $image,
				'total'       => $total,
				'latest_scan' => $last_scan,
			);

			// If current dir smush completed, include global stats.
			if ( ! empty( $stats ) ) {
				$data['stats'] = $stats;
			}

			// Update Bulk Limit Transient.
			$wpsmushit_admin->update_smush_count( 'dir_sent_count' );

			wp_send_json_success( $data );
		}

		/**
		 * Combine the stats from Directory Smush and Media Library Smush.
		 *
		 * @param array $stats  Directory Smush stats.
		 *
		 * @return array Combined array of stats.
		 */
		public function combined_stats( $stats ) {
			if ( empty( $stats ) || empty( $stats['percent'] ) || empty( $stats['bytes'] ) ) {
				return array();
			}

			/* @var WpSmushitAdmin $wpsmushit_admin */
			global $wpsmushit_admin;

			$dasharray = 125.663706144;

			// Initialize global stats.
			$wpsmushit_admin->setup_global_stats();

			// Get the total/Smushed attachment count.
			$total_attachments = $wpsmushit_admin->total_count + $stats['total'];
			$total_images      = $wpsmushit_admin->stats['total_images'] + $stats['total'];

			$smushed     = $wpsmushit_admin->smushed_count + $stats['optimised'];
			$savings     = ! empty( $wpsmushit_admin->stats ) ? $wpsmushit_admin->stats['bytes'] + $stats['bytes'] : $stats['bytes'];
			$size_before = ! empty( $wpsmushit_admin->stats ) ? $wpsmushit_admin->stats['size_before'] + $stats['orig_size'] : $stats['orig_size'];
			$percent     = $size_before > 0 ? ( $savings / $size_before ) * 100 : 0;

			// Store the stats in array.
			$result = array(
				'total_count'   => $total_attachments,
				'smushed_count' => $smushed,
				'savings'       => size_format( $savings ),
				'percent'       => round( $percent, 1 ),
				'image_count'   => $total_images,
				'dash_offset'   => $total_attachments > 0 ? $dasharray - ( $dasharray * ( $smushed / $total_attachments ) ) : $dasharray,
				/* translators: %s: total number of images */
				'tooltip_text'  => ! empty( $total_images ) ? sprintf( __( "You've smushed %d images in total.", 'wp-smushit' ), $total_images ) : '',
			);

			return $result;
		}

		/**
		 * Display a admin notice on smush screen if the custom table wasn't created
		 *
		 * @return string $notice  Notice if table doesn't exists.
		 *
		 * @todo: Update text
		 */
		public function show_table_error() {
			$notice = '';

			$current_screen = get_current_screen();
			if ( 'toplevel_page_smush' !== $current_screen->id && 'toplevel_page_smush-network' !== $current_screen->id ) {
				return $notice;
			}

			if ( ! $this->table_exist() ) {
				// Display a notice.
				$notice  = '<div class="sui-notice sui-notice-warning missing_table"><p>';
				$notice .= esc_html__( 'Directory smushing requires custom tables and it seems there was an error creating tables. For help, please contact our team on the support forums', 'wp-smushit' );
				$notice .= '</p></div>';
			}

			return $notice;
		}

		/**
		 * Check and create dir smush table if required.
		 *
		 * @since 2.9.0
		 */
		public function check_table() {
			global $wpsmushit_admin;

			// Get current screen.
			$current_screen = get_current_screen();

			// Only run on required pages.
			if ( ! empty( $current_screen ) && ! in_array( $current_screen->id, $wpsmushit_admin->pages, true ) ) {
				return;
			}

			// Create custom table for directory smush.
			if ( ! $this->table_exist() ) {
				WP_Smush_Installer::directory_smush_table();
			}
		}

		/**
		 * Check if required directory smush table exist.
		 *
		 * @param bool $force Should force check?.
		 *
		 * @since 2.9.0
		 *
		 * @return bool
		 */
		public function table_exist( $force = false ) {
			global $wpdb;

			// If not forced, try to get from cache.
			if ( ! $force && isset( $this->table_exist ) ) {
				return $this->table_exist;
			}

			// If not already checked, check.
			$table_exist = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $wpdb->base_prefix . 'smush_dir_images' ) ) ); // Db call ok; no-cache ok.

			$this->table_exist = $table_exist ? true : false;

			return $this->table_exist;
		}

		/**
		 * Remove directory smush from tabs.
		 *
		 * If not in main site, do not show directory smush.
		 *
		 * @param array $tabs Tabs.
		 *
		 * @return array
		 */
		public function remove_directory_tab( $tabs ) {
			if ( isset( $tabs['directory'] ) ) {
				unset( $tabs['directory'] );
			}

			return $tabs;
		}

	}

	global $wpsmush_dir;
	$wpsmush_dir = new WP_Smush_Dir();
} // End if().

/**
 * Filters the list of directories, exclude the media subfolders.
 */
if ( class_exists( 'RecursiveFilterIterator' ) && ! class_exists( 'WPSmushRecursiveFilterIterator' ) ) {
	/**
	 * Class WPSmushRecursiveFilterIterator
	 */
	class WPSmushRecursiveFilterIterator extends RecursiveFilterIterator {
		/**
		 * Accept method.
		 *
		 * @return bool
		 */
		public function accept() {
			/* @var WP_Smush_Dir $wpsmush_dir */
			global $wpsmush_dir;

			$path = $this->current()->getPathname();

			if ( $this->isDir() && ! $wpsmush_dir->skip_dir( $path ) ) {
				return true;
			}

			if ( ! $this->isDir() ) {
				return true;
			}

			return false;
		}
	}
}
