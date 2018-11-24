<?php

/**
 * Adds the Bulk Page and Smush Column to NextGen Gallery
 *
 * @package WP_Smush
 * @subpackage NextGen Gallery
 * @version 1.0
 *
 * @author Umesh Kumar <umesh@incsub.com>
 *
 * @copyright (c) 2016, Incsub (http://incsub.com)
 */
if ( ! class_exists( 'WpSmushNextGenAdmin' ) ) {

	class WpSmushNextGenAdmin extends WpSmushNextGen {

		var $total_count = 0;

		/**
		 *
		 * @var int $smushed_count Count of images ( Attachments ), Does not includes additional sizes that might have been created
		 */
		var $smushed_count = 0;

		/**
		 *
		 * @var int $image_count Includes the count of different sizes an image might have
		 */
		var $image_count     = 0;
		var $remaining_count = 0;
		var $super_smushed   = 0;
		var $smushed         = array();
		var $bulk_page_handle;

		// Stores all lossless smushed ids
		public $resmush_ids = array();

		function __construct() {

			global $wpsmushnextgenstats;

			// Update the number of columns
			add_filter( 'ngg_manage_images_number_of_columns', array( &$this, 'wp_smush_manage_images_number_of_columns' ) );

			// Add a bulk smush option for NextGen gallery
			add_action( 'admin_menu', array( &$this, 'wp_smush_bulk_menu' ) );

			// Localize variables for NextGen Manage gallery page
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );

			// Update resmush list, if a NextGen image is deleted
			add_action( 'ngg_delete_picture', array( $this, 'update_resmush_list' ) );

			// Update Stats, if a NextGen image is deleted
			add_action( 'ngg_delete_picture', array( $this, 'update_nextgen_stats' ) );

			// Update Stats, Lists -  if a NextGen Gallery is deleted
			add_action( 'ngg_delete_gallery', array( $wpsmushnextgenstats, 'update_stats_cache' ) );

			// Update the Super Smush count, after the smushing
			add_action( 'wp_smush_image_optimised_nextgen', array( $this, 'update_lists' ), '', 2 );

		}

		/**
		 * Enqueue Scripts on Manage Gallery page
		 */
		function enqueue() {
			$current_screen = get_current_screen();
			if ( ! empty( $current_screen ) && $current_screen->base == 'nggallery-manage-images' ) {
				$this->localize();
			}
		}

		/**
		 * Add a WP Smush page for bulk smush and settings related to Nextgen gallery
		 */
		function wp_smush_bulk_menu() {
			global $wp_smush;

			if ( defined( 'NGGFOLDER' ) ) {
				$title                  = $wp_smush->validate_install() ? esc_html__( 'Smush Pro', 'wp-smushit' ) : esc_html__( 'Smush', 'wp-smushit' );
				$this->bulk_page_handle = add_submenu_page(
					NGGFOLDER, $title, $title, 'NextGEN Manage gallery', 'wp-smush-nextgen-bulk', array(
						&$this,
						'ui',
					)
				);
				// Enqueue js on Post screen (Edit screen for media )
				add_action( 'admin_print_scripts-' . $this->bulk_page_handle, array( $this, 'localize' ) );
			}
		}

		/**
		 * Returns a column name for WP Smush
		 *
		 * @param $columns
		 *
		 * @return mixed
		 */
		function wp_smush_image_column_name( $columns ) {
			// Latest next gen takes string, while the earlier WP Smush plugin shows there use to be a array
			if ( is_array( $columns ) ) {
				$columns['wp_smush_image'] = esc_html__( 'Smush', 'wp-smushit' );
			} else {
				$columns = esc_html__( 'Smush', 'wp-smushit' );
			}

			return $columns;
		}

		/**
		 * Returns Smush option / Stats, depending if image is already smushed or not
		 *
		 * @param $column_name
		 * @param $id
		 */
		function wp_smush_column_options( $column_name, $id, $echo = false ) {
			global $wpsmushnextgenstats, $wpsmushit_admin;

			// NExtGen Doesn't returns Column name, weird? yeah, right, it is proper because hook is called for the particular column
			if ( $column_name == 'wp_smush_image' || $column_name == '' ) {

				// We're not using our in-house function WpSmushNextGen::get_nextgen_image_from_id()
				// as we're already instializing the nextgen gallery object, we need $storage instance later
				// Registry Object for NextGen Gallery
				$registry = C_Component_Registry::get_instance();

				// Gallery Storage Object
				$storage = $registry->get_utility( 'I_Gallery_Storage' );

				// We'll get the image object in $id itself, else fetch it using Gallery Storage
				if ( is_object( $id ) ) {
					$image = $id;
				} else {
					// get an image object
					$image = $storage->object->_image_mapper->find( $id );
				}

				// Check if it is supported image format, get image type to do that
				// get the absolute path
				$file_path = $storage->get_image_abspath( $image, 'full' );

				// Get image type from file path
				$image_type = $this->get_file_type( $file_path );

				// If image type not supported
				if ( ! $image_type || ! in_array( $image_type, $wpsmushit_admin->mime_types ) ) {
					return;
				}

				$image->meta_data = $this->get_combined_stats( $image->meta_data );

				// Check Image metadata, if smushed, print the stats or super smush button
				if ( ! empty( $image->meta_data['wp_smush'] ) ) {
					// Echo the smush stats
					return $wpsmushnextgenstats->show_stats( $image->pid, $image->meta_data['wp_smush'], $image_type, false, $echo );
				}

				// Print the status of image, if Not smushed
				return $this->set_status( $image->pid, $echo, false );

			}
		}

		/**
		 * Localize Translations And Stats
		 */
		function localize() {
			global $wpsmushnextgenstats;

			$handle = 'smush-admin';

			$wp_smush_msgs = array(
				'resmush'          => esc_html__( 'Super-Smush', 'wp-smushit' ),
				'smush_now'        => esc_html__( 'Smush Now', 'wp-smushit' ),
				'error_in_bulk'    => esc_html__( '{{smushed}}/{{total}} images were successfully compressed, {{errors}} encountered issues.', 'wp-smushit' ),
				'all_resmushed'    => esc_html__( 'All images are fully optimized.', 'wp-smushit' ),
				'restore'          => esc_html__( 'Restoring image..', 'wp-smushit' ),
				'smushing'         => esc_html__( 'Smushing image..', 'wp-smushit' ),
				'checking'         => esc_html__( 'Checking images..', 'wp-smushit' ),
				// Button text
				'resmush_check'    => esc_html__( 'RE-CHECK IMAGES', 'wp-smushit' ),
				'resmush_complete' => esc_html__( 'CHECK COMPLETE', 'wp-smushit' ),
			);

			wp_localize_script( $handle, 'wp_smush_msgs', $wp_smush_msgs );

			// If premium, Super smush allowed, all images are smushed, localize lossless smushed ids for bulk compression
			if ( $resmush_ids = get_option( 'wp-smush-nextgen-resmush-list', array() ) ) {

				$this->resmush_ids = $resmush_ids;
			}

			// Setup image counts ( Total, Smushed, Super-smushed, Remaining )
			$this->setup_image_counts();

			// Get the Latest Stats
			$this->stats = $wpsmushnextgenstats->get_smush_stats();

			// Get the unsmushed ids, used for localized stats as well as normal localization
			$unsmushed = $wpsmushnextgenstats->get_ngg_images( 'unsmushed' );
			$unsmushed = ( ! empty( $unsmushed ) && is_array( $unsmushed ) ) ? array_keys( $unsmushed ) : '';

			$smushed = $wpsmushnextgenstats->get_ngg_images();
			$smushed = ( ! empty( $smushed ) && is_array( $smushed ) ) ? array_keys( $smushed ) : '';

			$this->smushed = $smushed;
			if ( ! empty( $_REQUEST['ids'] ) ) {
				// Sanitize the ids and assign it to a variable
				$this->ids = array_map( 'intval', explode( ',', $_REQUEST['ids'] ) );
			} else {
				$this->ids = $unsmushed;
			}

			$this->super_smushed = get_option( 'wp-smush-super_smushed_nextgen', array() );
			$this->super_smushed = ! empty( $this->super_smushed['ids'] ) ? $this->super_smushed['ids'] : array();

			// If we have images to be resmushed, Update supersmush list
			if ( ! empty( $this->resmush_ids ) && ! empty( $this->super_smushed ) ) {
				$this->super_smushed = array_diff( $this->super_smushed, $this->resmush_ids );
			}

			// If supersmushedimages are more than total, clean it up
			if ( sizeof( $this->super_smushed ) > $this->total_count ) {
				$this->super_smushed = $this->cleanup_super_smush_data();
			}

			// Array of all smushed, unsmushed and lossless ids
			$data = array(
				'count_smushed'      => $this->smushed_count,
				'count_supersmushed' => count( $this->super_smushed ),
				'count_total'        => $this->total_count,
				'count_images'       => $this->image_count,
				'smushed'            => $smushed,
				'unsmushed'          => $unsmushed,
				'resmush'            => $this->resmush_ids,
			);

			// Add the stats to arrray
			if ( ! empty( $this->stats ) && is_array( $this->stats ) ) {
				$data = array_merge( $data, $this->stats );
			}

			wp_localize_script( $handle, 'wp_smushit_data', $data );

		}

		/**
		 * Display the whole admin page ui.
		 *
		 * Load all sub sections such as stats container, settings
		 * bulk smush container, integrations CDN etc, under this function.
		 * This function directory echo the output.
		 *
		 * @return void
		 */
		public function ui() {

			global $wpsmush_bulkui;

			// Shared UI wrapper.
			echo '<div class="sui-wrap">';

			// Load page header.
			$wpsmush_bulkui->smush_page_header();

			// Show status box.
			$this->smush_stats_container();

			// Bulk smush container.
			$this->bulk_smush_container();

			// Close shared ui wrapper.
			echo '</div>';
		}

		/**
		 * Increase the count of columns for Nextgen Gallery Manage page
		 *
		 * @param $count
		 *
		 * @return mixed
		 */
		function wp_smush_manage_images_number_of_columns( $count ) {
			$count ++;

			// Add column Heading
			add_filter( "ngg_manage_images_column_{$count}_header", array( &$this, 'wp_smush_image_column_name' ) );

			// Add Column data
			add_filter(
				"ngg_manage_images_column_{$count}_content", array(
					&$this,
					'wp_smush_column_options',
				), 10, 2
			);

			return $count;
		}

		/**
		 * Set send button status
		 *
		 * @param $pid
		 * @param bool $echo
		 * @param bool $text_only
		 *
		 * @return string|void
		 */
		function set_status( $pid, $echo = true, $text_only = false ) {
			global $wp_smush;

			// the status
			$status_txt = __( 'Not processed', 'wp-smushit' );

			// we need to show the smush button
			$show_button = true;

			// the button text
			$button_txt = __( 'Smush', 'wp-smushit' );
			if ( $text_only ) {
				return $status_txt;
			}

			// If we are not showing smush button, append progree bar, else it is already there
			if ( ! $show_button ) {
				$status_txt .= $wp_smush->progress_bar();
			}

			$text = $this->column_html( $pid, $status_txt, $button_txt, $show_button, false, $echo );
			if ( ! $echo ) {
				return $text;
			}
		}

		/**
		 * Print the column html
		 *
		 * @param string  $pid Media id
		 * @param string  $status_txt Status text
		 * @param string  $button_txt Button label
		 * @param boolean $show_button Whether to shoe the button
		 *
		 * @return null
		 */
		function column_html( $pid, $status_txt = '', $button_txt = '', $show_button = true, $smushed = false, $echo = true, $wrapper = false ) {
			global $wp_smush;

			$class = $smushed ? '' : ' sui-hidden';
			$html  = '<p class="smush-status' . $class . '">' . $status_txt . '</p>';
			$html .= wp_nonce_field( 'wp_smush_nextgen', '_wp_smush_nonce', '', false );
			// if we aren't showing the button
			if ( ! $show_button ) {
				if ( $echo ) {
					echo $html . $wp_smush->progress_bar();

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
			if ( ! $echo ) {
				$html .= '
				<button  class="button button-primary wp-smush-nextgen-send" data-id="' . $pid . '">
	                <span>' . $button_txt . '</span>
				</button>';
				if ( ! $smushed ) {
					$class = ' unsmushed';
				} else {
					$class = ' smushed';
				}
				$html .= $wp_smush->progress_bar();
				$html  = $wrapper ? '<div class="smush-wrap' . $class . '">' . $html . '</div>' : $html;

				return $html;
			} else {
				$html .= '<button class="button button-primary wp-smush-nextgen-send" data-id="' . $pid . '">
                    <span>' . $button_txt . '</span>
				</button>';
				echo $html . $wp_smush->progress_bar();
			}
		}

		/**
		 * Outputs the Content for Bulk Smush Div
		 */
		function bulk_smush_content() {
			global $wpsmushit_admin, $wp_smush, $wpsmush_bulkui;

			// If all the images are smushed.
			$all_done = ( $this->smushed_count == $this->total_count ) && 0 == count( $this->resmush_ids );

			$resmush_ids = get_option( 'wp-smush-nextgen-resmush-list', false );

			$count = $resmush_ids ? count( $resmush_ids ) : 0;

			// Whether to show the remaining re-smush notice.
			$show = $count > 0 ? true : false;

			$count += $this->remaining_count;

			// Get the counts.
			echo $wpsmush_bulkui->bulk_resmush_content( $count, $show );

			// If there are no images in Media Library
			if ( 0 >= $this->total_count ) : ?>
				<span class="wp-smush-no-image tc">
					<img src="<?php echo WP_SMUSH_URL . 'assets/images/smush-no-media.png'; ?>" alt="<?php esc_html_e( 'No attachments found - Upload some images', 'wp-smushit' ); ?>">
		        </span>
                <p class="wp-smush-no-images-content tc"><?php printf( esc_html__( "We haven't found any images in your %sgallery%s yet, so there's no smushing to be done! Once you upload images, reload this page and start playing!", 'wp-smushit' ), '<a href="' . esc_url( admin_url( 'admin.php?page=ngg_addgallery' ) ) . '">', '</a>' ); ?></p>
                <span class="wp-smush-upload-images sui-no-padding-bottom tc">
                <a class="sui-button sui-button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=ngg_addgallery' ) ); ?>"><?php esc_html_e( "UPLOAD IMAGES", "wp-smushit" ); ?></a>
                </span>
			<?php else : ?>
				<!-- Hide All done div if there are images pending -->
				<div class="sui-notice sui-notice-success wp-smush-all-done<?php echo $all_done ? '' : ' sui-hidden'; ?>">
					<p><?php esc_html_e( 'All images are smushed and up to date. Awesome!', 'wp-smushit' ); ?></p>
				</div>
				<div class="wp-smush-bulk-wrapper <?php echo $all_done ? ' sui-hidden' : ''; ?>">
				<!-- Do not show the remaining notice if we have resmush ids -->
				<div class="sui-notice sui-notice-warning wp-smush-remaining  <?php echo count( $this->resmush_ids ) > 0 ? ' sui-hidden' : ''; ?>">
					<p>
						<span class="wp-smush-notice-text">
							<?php printf( _n( '%1$s, you have %2$s%3$s%4$d%5$s attachment%6$s that needs smushing!', '%1$s, you have %2$s%3$s%4$d%5$s attachments%6$s that need smushing!', $this->remaining_count, 'wp-smushit' ), $wpsmushit_admin->get_user_name(), '<strong>', '<span class="wp-smush-remaining-count">', $this->remaining_count, '</span>', '</strong>' ); ?>
						</span>
					</p>
				</div>
				<div class="sui-actions-right">
					<button type="button" class="sui-button sui-button-primary wp-smush-nextgen-bulk"><?php esc_html_e( 'BULK SMUSH', 'wp-smushit' ); ?></button>
				</div>
				<?php
				// Enable Super Smush
				if ( ! $wp_smush->lossy_enabled ) :
					$url = admin_url( 'upload.php' );
					$url = add_query_arg(
						array(
							'page' => 'smush#wp-smush-settings-box',
						), $url
					);
					?>
					<span class="wp-smush-enable-lossy"><?php printf( esc_html__( 'Enable Super-smush in the %1$sSettings%2$s area to get even more savings with almost no visible drop in quality.', 'wp-smushit' ), '<a href="' . $url . '" target="_blank">', '</a>' ); ?></span>
					<?php
				endif;
				echo '</div>';
				$wpsmush_bulkui->progress_bar( $this ); ?>

				<div class="smush-final-log sui-hidden">
					<div class="smush-bulk-errors"></div>
					<div class="smush-bulk-errors-actions">
						<a href="<?php echo esc_url( admin_url( 'upload.php' ) ); ?>" class="sui-button sui-button-icon sui-button-ghost">
							<i class="sui-icon-photo-picture" aria-hidden="true"></i>
							<?php esc_html_e( 'View all', 'wp-smushit' ); ?>
						</a>
					</div>
				</div>

				<?php
			endif;
		}

		/**
		 * Bulk Smush UI and progress bar.
		 *
		 * @return void
		 */
		public function bulk_smush_container() {
			global $wp_smush, $wpsmush_bulkui;

			$smush_individual_msg = sprintf( esc_html__( 'Smush individual images via your %1$sManage Galleries%2$s section', 'wp-smushit' ), '<a href="' . esc_url( admin_url() . 'admin.php?page=nggallery-manage-gallery' ) . '" title="' . esc_html__( 'Manage Galleries', 'wp-smushit' ) . '">', '</a>' );

			// Class for bulk smush box.
			$class = $wp_smush->validate_install() ? 'bulk-smush-wrapper wp-smush-pro-install' : 'bulk-smush-wrapper';

			echo '<div class="sui-box ' . $class . '" id="wp-smush-bulk-wrap-box">';

			// Container header.
			$wpsmush_bulkui->container_header( esc_html__( 'Bulk Smush', 'wp-smushit' ), $smush_individual_msg );

			echo '<div class="sui-box-body">';

			// Bulk smush box.
			$this->bulk_smush_content();

			echo '</div>';
			echo '</div>';
		}

		/**
		 * Outputs the smush stats for the site.
		 *
		 * @todo Implement this
		 *
		 * @return void
		 */
		public function smush_stats_container() {

			global $wpsmush_bulkui;

			echo '<div class="sui-box sui-summary sui-summary-smush-nextgen">';
			$this->smush_stats_content();

			// Allows you to output any content within the stats box at the end.
			do_action( 'wp_smush_after_stats' );
			echo '</div>';
		}

		/**
		 * Outputs the smush stats for the site.
		 *
		 * @todo Implement this
		 *
		 * @return void
		 */
		public function smush_stats_content() {
			global $wp_smush, $wpsmushnextgenstats, $wpsmush_db;

			// If we have resmush list, smushed_count = totalcount - resmush count, else smushed_count.
			$smushed_count = ( $resmush_count = count( $this->resmush_ids ) ) > 0 ? ( $this->total_count - ( $resmush_count + $this->remaining_count ) ) : $this->smushed_count;

			?>
			<div class="sui-summary-image-space"></div>
			<div class="sui-summary-segment">
				<div class="sui-summary-details">
					<span class="sui-summary-large wp-smush-total-optimised"><?php echo $this->image_count; ?></span>
					<span class="sui-summary-sub"><?php esc_html_e( 'Images smushed', 'wp-smushit' ); ?></span>
				</div>
			</div>
			<div class="sui-summary-segment">
				<ul class="sui-list smush-stats-list-nextgen">
					<li>
						<div class="wp-smush-savings">
							<span class="sui-list-label"><?php esc_html_e( 'Total savings', 'wp-smushit' ); ?></span>
							<span class="sui-list-detail wp-smush-stats">
								<span class="wp-smush-stats-percent"><?php echo $this->stats['percent'] > 0 ? number_format_i18n( $this->stats['percent'], 1, '.', '' ) : 0; ?></span>%
								<span class="wp-smush-stats-sep">/</span>
								<span class="wp-smush-stats-human"><?php echo $this->stats['human'] > 0 ? $this->stats['human'] : '0MB'; ?></span>
							</span>
						</div>
						<?php wp_nonce_field( 'save_wp_smush_options', 'wp_smush_options_nonce', '' ); ?>
					</li>
					<?php if ( apply_filters( 'wp_smush_show_nextgen_lossy_stats', true ) ) : ?>
						<li class="super-smush-attachments">
							<div class="super-smush-attachments">
								<span class="sui-list-label"><?php esc_html_e( 'Super-smushed images', 'wp-smushit' ); ?></span>
								<span class="sui-list-detail wp-smush-stats">
									<?php if ( $wp_smush->lossy_enabled ) : ?>
										<?php $smushed_image = $wpsmushnextgenstats->get_ngg_images( 'smushed' ); ?>
										<?php if ( ! empty( $smushed_image ) && is_array( $smushed_image ) && ! empty( $this->resmush_ids ) && is_array( $this->resmush_ids ) ) : ?>
											<?php $smushed_image = array_diff_key( $smushed_image, array_flip( $this->resmush_ids ) ); // Get smushed images excluding resmush ids. ?>
										<?php endif; ?>
										<?php $smushed_image_count = is_array( $smushed_image ) ? sizeof( $smushed_image ) : 0; ?>
										<span class="smushed-count"><?php echo $smushed_image_count; ?></span> / <?php echo $this->total_count; ?>
									<?php else : ?>
										<span class="sui-tag sui-tag-disabled wp-smush-lossy-disabled"><?php esc_html_e( 'Disabled', 'wp-smushit' ); ?></span>
									<?php endif; ?>
								</span>
							</div
						</li>
					<?php endif; ?>
				</ul>
			</div>
			<?php
		}

		/**
		 * Updates the resmush list for NextGen gallery, remove the given id
		 *
		 * @param $attachment_id
		 */
		function update_resmush_list( $attachment_id ) {
			global $wpsmushit_admin;
			$wpsmushit_admin->update_resmush_list( $attachment_id, 'wp-smush-nextgen-resmush-list' );
		}

		/**
		 * Fetch the stats for the given attachment id, and subtract them from Global stats
		 *
		 * @param $attachment_id
		 *
		 * @return bool
		 */
		function update_nextgen_stats( $attachment_id ) {
			global $wpsmushit_admin;

			if ( empty( $attachment_id ) ) {
				return false;
			}

			$image_id = absint( (int) $attachment_id );

			// Get the absolute path for original image
			$image = $this->get_nextgen_image_from_id( $image_id );

			// Image Meta data
			$metadata = ! empty( $image ) ? $image->meta_data : '';

			$smush_stats = ! empty( $metadata['wp_smush'] ) ? $metadata['wp_smush'] : '';

			if ( empty( $smush_stats ) ) {
				return false;
			}

			$nextgen_stats = get_option( 'wp_smush_stats_nextgen', false );
			if ( ! $nextgen_stats ) {
				return false;
			}

			if ( ! empty( $nextgen_stats['size_before'] ) && ! empty( $nextgen_stats['size_after'] ) && $nextgen_stats['size_before'] > 0 && $nextgen_stats['size_after'] > 0 && $nextgen_stats['size_before'] > $smush_stats['stats']['size_before'] ) {
				$nextgen_stats['size_before'] = $nextgen_stats['size_before'] - $smush_stats['stats']['size_before'];
				$nextgen_stats['size_after']  = $nextgen_stats['size_after'] - $smush_stats['stats']['size_after'];
				$nextgen_stats['bytes']       = $nextgen_stats['size_before'] - $nextgen_stats['size_after'];
				$nextgen_stats['percent']     = ( $nextgen_stats['bytes'] / $nextgen_stats['size_before'] ) * 100;
				$nextgen_stats['human']       = size_format( $nextgen_stats['bytes'], 1 );
			}

			// Update Stats
			update_option( 'wp_smush_stats_nextgen', $nextgen_stats, false );

			// Remove from Super Smush list
			$wpsmushit_admin->update_super_smush_count( $attachment_id, 'remove', 'wp-smush-super_smushed_nextgen' );

		}

		/**
		 * Update the Super Smush count for NextGen Gallery
		 *
		 * @param $image_id
		 * @param $stats
		 */
		function update_lists( $image_id, $stats ) {
			global $wpsmushit_admin;
			$wpsmushit_admin->update_lists( $image_id, $stats, 'wp-smush-super_smushed_nextgen' );
			if ( ! empty( $this->resmush_ids ) && in_array( $image_id, $this->resmush_ids ) ) {
				$this->update_resmush_list( $image_id );
			}
		}

		/**
		 * Initialize NextGen Gallery Stats
		 */
		function setup_image_counts() {
			global $wpsmushnextgenstats;

			$smushed_images = $wpsmushnextgenstats->get_ngg_images( 'smushed' );

			// Check if resmush ids are not set, get it
			if ( empty( $this->resmush_ids ) ) {
				$this->resmush_ids = get_option( 'wp-smush-nextgen-resmush-list', array() );
			}

			// I fwe have images to be resmushed, exclude it
			if ( ! empty( $this->resmush_ids ) ) {
				// Get the Smushed images, exlude resmush ids
				$smushed_images = array_diff_key( $smushed_images, array_flip( $this->resmush_ids ) );
			}

			// Set the counts
			$this->total_count = $wpsmushnextgenstats->total_count();

			// Includes the count of different sizes an image might have
			$this->image_count = $this->get_image_count( $smushed_images );

			// Count of images ( Attachments ), Does not includes additioanl sizes that might have been created
			$this->smushed_count = isset( $smushed_images ) && is_array( $smushed_images ) ? sizeof( $smushed_images ) : $smushed_images;

			$this->remaining_count = $wpsmushnextgenstats->get_ngg_images( 'unsmushed', true );
		}

		/**
		 * Get the image count for nextgen images
		 *
		 * @param array $images Array of attachments to get the image count for
		 *
		 * @param bool  $exclude_resmush_ids Whether to exclude resmush ids or not
		 *
		 * @return int
		 */
		function get_image_count( $images = array(), $exclude_resmush_ids = true ) {
			if ( empty( $images ) || ! is_array( $images ) ) {
				return 0;
			}

			$image_count = 0;
			// $image in here is expected to be metadata array
			foreach ( $images as $image_k => $image ) {
				// Get image object if not there already
				if ( ! is_array( $image ) ) {
					$image = $this->get_nextgen_image_from_id( $image );
					// Get the meta
					$image = ! empty( $image->meta_data ) ? $image->meta_data : '';
				}
				// If there are no smush stats, skip
				if ( empty( $image['wp_smush'] ) ) {
					continue;
				}

				// If resmush ids needs to be excluded
				if ( $exclude_resmush_ids && ( ! empty( $this->resmush_ids ) && in_array( $image_k, $this->resmush_ids ) ) ) {
					continue;
				}

				// Get the image count
				if ( ! empty( $image['wp_smush']['sizes'] ) ) {
					$image_count += count( $image['wp_smush']['sizes'] );
				}
			}

			return $image_count;
		}

		/**
		 * Combine the resizing stats and smush stats , One time operation - performed during the image optimization
		 *
		 * @param $metadata
		 *
		 * @return bool|string
		 */
		function get_combined_stats( $metadata ) {
			if ( empty( $metadata ) ) {
				return $metadata;
			}

			$smush_stats    = ! empty( $metadata['wp_smush'] ) ? $metadata['wp_smush'] : '';
			$resize_savings = ! empty( $metadata['wp_smush_resize_savings'] ) ? $metadata['wp_smush_resize_savings'] : '';

			if ( empty( $resize_savings ) || empty( $smush_stats ) ) {
				return $metadata;
			}

			$smush_stats['stats']['bytes']       = ! empty( $resize_savings['bytes'] ) ? $smush_stats['stats']['bytes'] + $resize_savings['bytes'] : $smush_stats['stats']['bytes'];
			$smush_stats['stats']['size_before'] = ! empty( $resize_savings['size_before'] ) ? $smush_stats['stats']['size_before'] + $resize_savings['size_before'] : $smush_stats['stats']['size_before'];
			$smush_stats['stats']['size_after']  = ! empty( $resize_savings['size_after'] ) ? $smush_stats['stats']['size_after'] + $resize_savings['size_after'] : $smush_stats['stats']['size_after'];
			$smush_stats['stats']['percent']     = ! empty( $resize_savings['size_before'] ) ? ( $smush_stats['stats']['bytes'] / $smush_stats['stats']['size_before'] ) * 100 : $smush_stats['stats']['percent'];

			// Round off
			$smush_stats['stats']['percent'] = round( $smush_stats['stats']['percent'], 2 );

			if ( ! empty( $smush_stats['sizes']['full'] ) ) {
				// Full Image
				$smush_stats['sizes']['full']['bytes']       = ! empty( $resize_savings['bytes'] ) ? $smush_stats['sizes']['full']['bytes'] + $resize_savings['bytes'] : $smush_stats['sizes']['full']['bytes'];
				$smush_stats['sizes']['full']['size_before'] = ! empty( $resize_savings['size_before'] ) ? $smush_stats['sizes']['full']['size_before'] + $resize_savings['size_before'] : $smush_stats['sizes']['full']['size_before'];
				$smush_stats['sizes']['full']['size_after']  = ! empty( $resize_savings['size_after'] ) ? $smush_stats['sizes']['full']['size_after'] + $resize_savings['size_after'] : $smush_stats['sizes']['full']['size_after'];
				$smush_stats['sizes']['full']['percent']     = ! empty( $smush_stats['sizes']['full']['bytes'] ) && $smush_stats['sizes']['full']['size_before'] > 0 ? ( $smush_stats['sizes']['full']['bytes'] / $smush_stats['sizes']['full']['size_before'] ) * 100 : $smush_stats['sizes']['full']['percent'];

				$smush_stats['sizes']['full']['percent'] = round( $smush_stats['sizes']['full']['percent'], 2 );
			} else {
				$smush_stats['sizes']['full'] = $resize_savings;
			}

			$metadata['wp_smush'] = $smush_stats;

			return $metadata;

		}

		/**
		 * Cleanup Super-smush images array against the all ids in gallery
		 *
		 * @return array|mixed|void
		 */
		function cleanup_super_smush_data() {
			global $wpsmushnextgenstats;
			$super_smushed = get_option( 'wp-smush-super_smushed_nextgen', array() );
			$ids           = $wpsmushnextgenstats->total_count( false, true );

			if ( is_array( $super_smushed ) && ! empty( $super_smushed['ids'] ) && is_array( $ids ) ) {
				$super_smushed['ids'] = array_intersect( $super_smushed['ids'], $ids );
			}

			update_option( 'wp-smush-super_smushed_nextgen', $super_smushed );

			return $super_smushed['ids'];

		}

	}//end class

}//End Of if class not exists
