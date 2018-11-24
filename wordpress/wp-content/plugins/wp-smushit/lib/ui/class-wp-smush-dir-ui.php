<?php
/**
 * Directory Smush UI: WP_Smush_Dir_UI class
 *
 * @package WP_Smush
 * @subpackage Admin/UI
 * @since 2.8.1
 */

if ( ! class_exists( 'WP_Smush_Dir_UI' ) ) {
	/**
	 * Class WP_Smush_Dir_UI
	 */
	class WP_Smush_Dir_UI {

		/**
		 * WP_Smush_Dir_UI constructor.
		 */
		public function __construct() {
			// Hook UI at the end of Settings UI.
			add_action( 'smush_directory_settings_ui', array( $this, 'ui' ), 11 );
			// Output Stats after Resize savings.
			add_action( 'stats_ui_after_resize_savings', array( $this, 'stats_ui' ), 10 );
		}

		/**
		 * Bulk Smush UI and progress bar.
		 */
		public function ui() {
			/* @var WP_Smush_Dir $wpsmush_dir */
			global $wp_smush, $wpsmushit_admin, $wpsmush_bulkui, $wpsmush_dir;

			// Print Directory Smush UI, if not a network site.
			if ( is_network_admin() ) {
				return;
			}

			// Reset the bulk limit.
			if ( ! $wp_smush->validate_install() ) {
				/**
				 * Reset transient.
				 *
				 * @var WpSmushitAdmin $wpsmushit_admin
				 */
				$wpsmushit_admin->check_bulk_limit( true, 'dir_sent_count' );
			}

			wp_nonce_field( 'smush_get_dir_list', 'list_nonce' );
			wp_nonce_field( 'smush_get_image_list', 'image_list_nonce' );

			$upgrade_url = add_query_arg(
				array(
					'utm_source'   => 'smush',
					'utm_medium'   => 'plugin',
					'utm_campaign' => 'smush_directorysmush_limit_notice',
				),
				$wpsmushit_admin->upgrade_url
			);

			echo '<div class="sui-box" id="wp-smush-dir-wrap-box">';

			/**
			 * Container header.
			 *
			 * @var WpSmushBulkUi $wpsmush_bulkui
			 */
			$wpsmush_bulkui->container_header( esc_html__( 'Directory Smush', 'wp-smushit' ) ); ?>

			<div class="sui-box-body">
				<!-- Directory Path -->
				<input type="hidden" class="wp-smush-dir-path" value=""/>
				<div class="wp-smush-scan-result">
					<div class="content">
						<!-- Show a list of images, inside a fixed height div, with a scroll. As soon as the image is
						optimised show a tick mark, with savings below the image. Scroll the li each time for the
						current optimised image -->
						<span class="wp-smush-no-image tc">
							<img src="<?php echo esc_url( WP_SMUSH_URL . 'assets/images/smush-no-media.png' ); ?>" alt="<?php esc_html_e( 'Directory Smush - Choose Folder', 'wp-smushit' ); ?>">
						</span>
						<p class="wp-smush-no-images-content tc roboto-regular">
							<?php esc_html_e( 'In addition to smushing your media uploads, you may want to also smush images living outside your uploads directory.', 'wp-smushit' ); ?><br>
							<?php esc_html_e( 'Get started by adding files and folders you wish to optimize.', 'wp-smushit' ); ?>
						</p>
						<span class="wp-smush-upload-images sui-no-padding-bottom tc">
							<button type="button" class="sui-button sui-button-primary wp-smush-browse tc" data-a11y-dialog-show="wp-smush-list-dialog"><?php esc_html_e( 'CHOOSE DIRECTORY', 'wp-smushit' ); ?></button>
						</span>
					</div>
					<!-- Notices -->
					<?php echo $this->smush_result_notice(); ?>
					<div class="sui-notice sui-notice-info wp-smush-dir-limit sui-hidden">
						<p>
							<?php
							printf(
								/* translators: %1$s: a tag start, %2$s: closing a tag */
								esc_html__( '%1$sUpgrade to pro%2$s to bulk smush all your directory images with one click. Free users can smush 50 images with each click.', 'wp-smushit' ),
								'<a href="' . esc_url( $upgrade_url ) . '" target="_blank" title="' . esc_html__( 'Smush Pro', 'wp-smushit' ) . '">',
								'</a>'
							);
							?>
						</p>
					</div>
					<?php wp_nonce_field( 'wp_smush_all', 'wp-smush-all' ); ?>
				</div>
				<input type="hidden" name="wp-smush-base-path" value="<?php echo esc_attr( $wpsmush_dir->get_root_path() ); ?>">
			</div>
			<?php
			$current_screen = get_current_screen();
			if ( ! empty( $current_screen ) && ! empty( $current_screen->base ) && ( 'toplevel_page_smush' === $current_screen->base || 'toplevel_page_smush-network' === $current_screen->base ) ) {
				$this->directory_list_dialog();
				$this->progress_dialog();
			}

			echo '</div>';
		}

		/**
		 * Show directory smush result notice.
		 *
		 * If we are redirected from a directory smush finish page,
		 * show the result notice if success/fail count is available.
		 *
		 * @since 2.9.0
		 */
		public function smush_result_notice() {
			// Get the counts from transient.
			$items = get_transient( 'wp-smush-show-dir-scan-notice' );
			$failed_items = get_transient( 'wp-smush-dir-scan-failed-items' );
			$notice_message = esc_html__( 'All images failed to optimize.', 'wp-smushit' );
			$notice_class = 'sui-notice-error';

			// Not all images optimized.
			if ( ! empty( $failed_items ) && ! empty( $items ) ) :
				$notice_message = sprintf(
					/* translators: %1$d: number of images smushed and %1$d number of failed. */
					esc_html__( '%1$d images were successfully optimized and %2$d images failed.', 'wp-smushit' ),
					absint( $items ),
					absint( $failed_items )
				);
				$notice_class = 'sui-notice-warning';
			// Yay! All images were optimized.
			elseif ( ! empty( $items ) && empty( $failed_items ) ) :
				$notice_message = sprintf(
					/* translators: %d: number of images */
					esc_html__( '%d images were successfully optimized.', 'wp-smushit' ),
					absint( $items )
				);
				$notice_class = 'sui-notice-success';
			endif;
			
			// If we have counts, show the notice.
			if ( ! empty( $items ) || ! empty( $failed_items ) ) :
				// Delete the transients.
				delete_transient( 'wp-smush-show-dir-scan-notice' );
				delete_transient( 'wp-smush-dir-scan-failed-items' );
				?>
				<div class="sui-notice-top sui-can-dismiss <?php echo $notice_class; ?>">
					<p class="sui-notice-content">
						<?php echo $notice_message; ?>
					</p>
					<span class="sui-notice-dismiss">
						<a role="button" href="#" aria-label="Dismiss" class="sui-icon-check"></a>
					</span>
				</div>
				<?php
			endif;
		}

		/**
		 * Output the content for Directory smush list dialog content
		 */
		public function directory_list_dialog() {
			?>
			<div class="sui-dialog wp-smush-list-dialog" aria-hidden="true" id="wp-smush-list-dialog">
				<div class="sui-dialog-overlay sui-fade-in" tabindex="0"></div>
				<div class="sui-dialog-content sui-bounce-in" role="dialog">
					<div class="sui-box" role="document">
						<div class="sui-box-header">
							<h3 class="sui-box-title"><?php esc_html_e( 'Choose Directory', 'wp-smushit' ); ?></h3>
							<div class="sui-actions-right">
								<button class="sui-dialog-close" aria-label="<?php esc_html_e( 'Close', 'wp-smushit' ); ?>"></button>
							</div>
						</div>

						<div class="sui-box-body">
							<p><?php esc_html_e( 'Choose which folder you wish to smush. Smush will automatically include any images in subfolders of your selected folder.', 'wp-smushit' ); ?></p>
							<div class="content"></div>
						</div>

						<div class="sui-box-footer">
							<div class="sui-actions-right">
								<span class="add-dir-loader"></span>
								<button class="sui-modal-close sui-button sui-button-primary wp-smush-select-dir" disabled>
									<?php esc_html_e( 'SMUSH', 'wp-smushit' ); ?>
								</button>
							</div>
						</div>
					</div>
				</div>
			</div>
			<?php
		}

		/**
		 * Output the progress dialog for the Directory smush list dialog
		 */
		public function progress_dialog() {
			?>
			<div class="sui-dialog wp-smush-progress-dialog" aria-hidden="true" id="wp-smush-progress-dialog">
				<div class="sui-dialog-overlay sui-fade-in" tabindex="0"></div>
				<div class="sui-dialog-content sui-bounce-in" role="dialog">
					<div class="sui-box" role="document">
						<div class="sui-box-header">
							<h3 class="sui-box-title"><?php esc_html_e( 'Choose Directory', 'wp-smushit' ); ?></h3>
							<div class="sui-actions-right">
								<button class="sui-dialog-close" aria-label="<?php esc_html_e( 'Close', 'wp-smushit' ); ?>"></button>
							</div>
						</div>

						<div class="sui-box-body">
							<p>
								<?php
								esc_html_e( 'Bulk smushing is in progress, you need to leave this tab open
								until the process completes.', 'wp-smushit' );
								?>
							</p>

							<div class="sui-notice sui-notice-warning sui-hidden">
								<p>
									<?php
									esc_html_e( "You've reached the 50 attachment limit for bulk smushing in
									the free version. Upgrade to Pro to smush unlimited images, or click resume to
									smush another 50 attachments.", 'wp-smushit' );
									?>
								</p>
							</div>

							<div class="sui-progress-block sui-progress-can-close">
								<div class="sui-progress">
									<div class="sui-progress-text sui-icon-loader sui-loading">
										<span>0%</span>
									</div>
									<div class="sui-progress-bar">
										<span style="width: 0"></span>
									</div>
								</div>
								<button class="sui-progress-close sui-tooltip" id="cancel-directory-smush" type="button" data-tooltip="<?php esc_attr_e( 'Cancel', 'wp-smushit' ); ?>">
									<i class="sui-icon-close"></i>
								</button>
							</div>

							<div class="sui-progress-state">
								<span class="sui-progress-state-text">
									<?php esc_html_e( '-/- images optimized', 'wp-smushit' ); ?>
								</span>
							</div>
						</div>

						<div class="sui-box-footer">
							<div class="sui-actions-right">
								<span class="add-dir-loader"></span>
								<button class="sui-modal-close sui-button wp-smush-cancel-dir"><?php esc_html_e( 'CANCEL', 'wp-smushit' ); ?></button>
							</div>

							<div class="sui-actions-right sui-hidden">
								<button class="sui-button wp-smush-resume-scan"><?php esc_html_e( 'RESUME', 'wp-smushit' ); ?></button>
							</div>
						</div>
					</div>
				</div>
			</div>
			<?php
		}

		/**
		 * Set directory smush stats to stats box.
		 *
		 * @return void
		 */
		public function stats_ui() {
			$dir_smush_stats = get_option( 'dir_smush_stats' );
			$human           = 0;
			if ( ! empty( $dir_smush_stats ) && ! empty( $dir_smush_stats['dir_smush'] ) ) {
				$human = ! empty( $dir_smush_stats['dir_smush']['bytes'] ) && $dir_smush_stats['dir_smush']['bytes'] > 0 ? $dir_smush_stats['dir_smush']['bytes'] : 0;
			}
			?>
			<!-- Savings from Directory Smush -->
			<li class="smush-dir-savings">
				<span class="sui-list-label"><?php esc_html_e( 'Directory Smush Savings', 'wp-smushit' ); ?>
					<?php if ( $human <= 0 ) { ?>
						<p class="wp-smush-stats-label-message">
							<?php esc_html_e( "Smush images that aren't located in your uploads folder.", 'wp-smushit' ); ?>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=smush&tab=directory' ) ); ?>" class="wp-smush-dir-link"
							   title="<?php esc_attr_e( "Select a directory you'd like to Smush.", 'wp-smushit' ); ?>">
								<?php esc_html_e( 'Choose directory', 'wp-smushit' ); ?>
							</a>
						</p>
					<?php } ?>
				</span>
				<span class="wp-smush-stats sui-list-detail">
					<i class="sui-icon-loader sui-loading" aria-hidden="true" title="<?php esc_html_e( 'Updating Stats', 'wp-smushit' ); ?>"></i>
					<span class="wp-smush-stats-human"></span>
					<span class="wp-smush-stats-sep sui-hidden">/</span>
					<span class="wp-smush-stats-percent"></span>
				</span>
			</li>
			<?php
		}

	}
} // End if().
