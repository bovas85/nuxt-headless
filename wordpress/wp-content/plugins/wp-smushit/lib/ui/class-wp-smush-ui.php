<?php
/**
 * Smush UI: WpSmushBulkUi class.
 *
 * @package WP_Smush
 * @subpackage Admin/UI
 * @version 1.0
 *
 * @author Umesh Kumar <umesh@incsub.com>
 *
 * @copyright (c) 2016, Incsub (http://incsub.com)
 */

if ( ! class_exists( 'WpSmushBulkUi' ) ) {

	/**
	 * Class WpSmushBulkUi
	 *
	 * Handle the UI part for the plugin.
	 */
	class WpSmushBulkUi {

		/**
		 * Settings group for resize options.
		 *
		 * @var array
		 */
		public $resize_group = array(
			'detection',
		);

		/**
		 * Settings group for full size image options.
		 *
		 * @var array
		 */
		public $full_size_group = array(
			'backup',
		);

		/**
		 * Settings group for integration options.
		 *
		 * @var array
		 */
		public $intgration_group = array();

		/**
		 * Tabs that can be shown in network admin networkwide.
		 *
		 * @var array
		 */
		public $network_tabs = array(
			'bulk',
			'integrations',
			'cdn',
		);

		/**
		 * Tabs that can be shown in subsites if networkwide.
		 *
		 * @var array
		 */
		public $subsite_tabs = array(
			'bulk',
			'directory',
		);

		/**
		 * Setting tabs.
		 *
		 * @var array
		 */
		public $tabs = array();

		/**
		 * Current tab.
		 *
		 * @var string
		 */
		public $current_tab = 'bulk';

		/**
		 * WpSmushBulkUi constructor.
		 */
		public function __construct() {
			add_action( 'smush_setting_column_right_inside', array( $this, 'settings_desc' ), 10, 2 );
			add_action( 'smush_setting_column_right_inside', array( $this, 'image_sizes' ), 15, 2 );
			add_action( 'smush_setting_column_right_inside', array( $this, 'resize_settings' ), 20, 2 );
			add_action( 'smush_setting_column_right_outside', array( $this, 'full_size_options' ), 20, 2 );
			add_action( 'smush_setting_column_right_outside', array( $this, 'detect_size_options' ), 25, 2 );
			add_action( 'smush_settings_ui_bottom', array( $this, 'pro_features_container' ) );

			// Add stats to stats box.
			add_action( 'stats_ui_after_resize_savings', array( $this, 'pro_savings_stats' ), 15 );
			add_action( 'stats_ui_after_resize_savings', array( $this, 'conversion_savings_stats' ), 15 );
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
			global $wp_smush, $wpsmushit_admin, $wpsmush_settings;

			// Hook into integration settings.
			$this->intgration_group = apply_filters( 'wp_smush_integration_settings', array() );

			// Set current active tab.
			$this->set_current_tab();

			// If a free user, update the limits.
			if ( ! $wp_smush->validate_install() ) {
				// Reset transient.
				$wpsmushit_admin->check_bulk_limit( true );
			}

			// Shared UI wrapper.
			echo '<div class="sui-wrap">';

			// Load page header.
			$this->smush_page_header();

			// Check if current page network admin page.
			$is_network     = is_network_admin();
			$is_networkwide = $wpsmush_settings->settings['networkwide'];

			// Show stats section only to subsite admins.
			if ( ! $is_network ) {
				// Show configure screen for only a new installation and for only network admins.
				if ( ( '1' !== get_site_option( 'skip-smush-setup' ) && '1' !== get_option( 'wp-smush-hide_smush_welcome' ) ) && '1' !== get_option( 'hide_smush_features' ) && is_super_admin() ) {
					$this->quick_setup();
				}
				// Show status box.
				$this->smush_stats_container();

				// If not a pro user.
				if ( ! $wp_smush->validate_install() ) {
					/**
					 * Allows to hook in additional containers after stats box for free version
					 * Pro Version has a full width settings box, so we don't want to do it there.
					 */
					do_action( 'wp_smush_after_stats_box' );
				}
			}

			// Start the nav bar for settings.
			echo '<div class="sui-row-with-sidenav">';

			// Load the settings nav.
			$this->settings_nav();

			// Nonce field.
			wp_nonce_field( 'save_wp_smush_options', 'wp_smush_options_nonce', '' );

			// If network wide option is disabled, show only bulk page.
			if ( $is_network && ! $is_networkwide ) {
				// Show settings box.
				$this->settings_container();
			} else {
				switch ( $this->current_tab ) {

					case 'directory':
						// Action hook to add settings to directory smush.
						do_action( 'smush_directory_settings_ui' );
						break;

					case 'integrations':
						// Show integrations box.
						$this->integrations_ui();
						break;

					case 'cdn':
						// Action hook to add settings to cdn section.
						do_action( 'smush_cdn_settings_ui' );
						break;

					case 'bulk':
					default:
						// Show bulk smush box if a subsite admin.
						if ( ! $is_network ) {
							// Bulk smush box.
							$this->bulk_smush_container();
						}

						if ( $is_network || ! $is_networkwide ) {
							// Show settings box.
							$this->settings_container();
						}
						break;
				}
			} // End if().

			// Close nav bar box.
			echo '</div>';

			/**
			 * Action hook to add extra containers at bottom of admin UI.
			 */
			do_action( 'smush_admin_ui_bottom' );

			// Close shared ui wrapper.
			echo '</div>';
		}

		/**
		 * Prints the header section for a container as per the Shared UI
		 *
		 * @param string $heading      Box Heading.
		 * @param string $sub_heading  Any additional text to be shown by the side of Heading.
		 *
		 * @return string
		 */
		public function container_header( $heading = '', $sub_heading = '' ) {
			if ( empty( $heading ) ) {
				return '';
			} ?>

			<div class="sui-box-header">
				<h3 class="sui-box-title"><?php echo esc_html( $heading ); ?></h3>
				<?php if ( ! empty( $sub_heading ) ) : ?>
					<div class="sui-actions-right">
						<?php echo $sub_heading; ?>
					</div>
				<?php endif; ?>
			</div>
			<?php
		}

		/**
		 * Prints the footer section for a container as per the Shared UI
		 *
		 * @param string $content      Footer content.
		 * @param string $sub_content  Any additional text to be shown by the side of footer.
		 *
		 * @return void
		 */
		public function container_footer( $content = '', $sub_content = '' ) {
			?>
			<div class="sui-box-footer">
				<?php echo $content; ?>
				<?php if ( ! empty( $sub_content ) ) : ?>
					<div class="sui-actions-right">
						<?php echo $sub_content; ?>
					</div>
				<?php endif; ?>
			</div>
			<?php
		}

		/**
		 * Prints the content of welcome screen for the new installation.
		 *
		 * For new installation show a quick settings form on a welcome
		 * modal box to get started.
		 *
		 * @return void
		 */
		public function quick_setup() {
			global $wp_smush, $wpsmushit_admin, $wpsmush_settings;
			?>

			<div class="sui-dialog" aria-hidden="true" tabindex="-1" id="smush-quick-setup-dialog">
				<div class="sui-dialog-overlay sui-fade-in" data-a11y-dialog-hide=""></div>
				<div class="sui-dialog-content sui-bounce-in"
					 aria-labelledby="smush-quick-setup-modal-title"  role="dialog">
					<div class="sui-box" role="document">
						<div class="sui-box-header">
							<h3 class="sui-box-title" id="smush-quick-setup-modal-title"><?php esc_html_e( 'QUICK SETUP', 'wp-smushit' ); ?></h3>
							<div class="sui-actions-right">
								<button data-a11y-dialog-hide class="sui-button sui-button-ghost smush-skip-setup" aria-label="<?php esc_html_e( 'Skip this.', 'wp-smushit' ); ?>">
									<?php esc_html_e( 'SKIP', 'wp-smushit' ); ?>
								</button>
							</div>
						</div>
						<div class="sui-box-body smush-quick-setup-settings">
							<p><?php esc_html_e( 'Welcome to Smush - Winner of Torque Plugin Madness 2017 & 2018! Let\'s quickly set up the basics for you, then you can fine tune each setting as you go - our recommendations are on by default.', 'wp-smushit' ); ?></p>
							<form method="post" id="smush-quick-setup-form">
								<input type="hidden" value="setupSmush" name="action"/>
								<?php wp_nonce_field( 'setupSmush' ); ?>
								<?php
								$exclude = array( 'backup', 'png_to_jpg', 'nextgen', 's3', 'detection' );
								// Settings for free and pro version.
								foreach ( $wpsmushit_admin->settings as $name => $values ) {
									// Skip networkwide settings, we already printed it.
									if ( 'networkwide' === $name ) {
										continue;
									}
									// Skip premium features if not a member.
									if ( ! in_array( $name, $wpsmushit_admin->basic_features, true ) && ! $wp_smush->validate_install() ) {
										continue;
									}
									// Do not output settings listed in exclude array list.
									if ( in_array( $name, $exclude, true ) ) {
										continue;
									}
									$setting_m_key = WP_SMUSH_PREFIX . $name;
									$setting_val   = $wpsmush_settings->settings[ $name ];
									// Set the default value 1 for auto smush.
									if ( 'auto' === $name && false === $setting_val ) {
										$setting_val = 1;
									}
									?>
									<div class="sui-box-settings-row">
										<div class="sui-box-settings-col-1">
											<span class="sui-settings-label"><?php echo $wpsmushit_admin->settings[ $name ]['label']; ?></span>
											<span class="sui-description"><?php echo $wpsmushit_admin->settings[ $name ]['desc']; ?></span>
										</div>
										<div class="sui-box-settings-col-2">
											<label class="sui-toggle">
												<input type="checkbox" class="toggle-checkbox" id="<?php echo $setting_m_key . '-quick-setup'; ?>" name="smush_settings[]" <?php checked( $setting_val, 1, true ); ?> value="<?php echo $setting_m_key; ?>" tabindex="0">
												<span class="sui-toggle-slider"></span>
											</label>
										</div>
										<?php if ( 'resize' === $name ) { // Add resize width and height setting. ?>
											<div class="wp-smush-resize-settings-col">
												<?php $this->resize_settings( $name, 'quick-setup-' ); ?>
											</div>
										<?php } ?>
									</div>
									<?php
								}
								?>
							</form>
						</div>
						<div class="sui-box-footer">
							<div class="sui-actions-right">
								<button type="submit" class="sui-button sui-button-lg sui-button-blue" id="smush-quick-setup-submit">
									<?php esc_html_e( 'Get Started', 'wp-smushit' ); ?>
								</button>
							</div>
						</div>
					</div>
				</div>
			</div>
			<?php
		}

		/**
		 * Bulk Smush UI and progress bar.
		 *
		 * @return void
		 */
		private function bulk_smush_container() {
			global $wp_smush;

			$smush_individual_msg = sprintf(
				/* translators: %1$s - a href opening tag, %2$s - a href closing tag */
				esc_html__( 'Smush individual images via your %1$sMedia Library%2$s', 'wp-smushit' ),
				'<a href="' . esc_url( admin_url( 'upload.php' ) ) . '" title="' . esc_html__( 'Media Library', 'wp-smushit' ) . '">', '</a>'
			);

			// Class for bulk smush box.
			$class = $wp_smush->validate_install() ? 'bulk-smush-wrapper wp-smush-pro-install' : 'bulk-smush-wrapper';

			echo '<div class="sui-box ' . esc_attr( $class ) . '" id="wp-smush-bulk-wrap-box">';

			// Container header.
			$this->container_header( esc_html__( 'Bulk Smush', 'wp-smushit' ), $smush_individual_msg );

			echo '<div class="sui-box-body">';

			// Bulk smush box.
			$this->bulk_smush_content();

			echo '</div>';
			echo '</div>';
		}

		/**
		 * All the settings for basic and advanced users.
		 *
		 * @return void
		 */
		public function settings_container() {
			global $wp_smush;

			// Class for box.
			$class = $wp_smush->validate_install() ? 'smush-settings-wrapper wp-smush-pro' : 'smush-settings-wrapper';

			echo '<div class="sui-box ' . $class . '" id="wp-smush-settings-box">';

			// Container header.
			$this->container_header( esc_html__( 'Settings', 'wp-smushit' ) );

			echo '<div class="sui-box-body">';

			// Load settings content.
			$this->options_ui();

			// Close box body.
			echo '</div>';

			// Footer content including buttons.
			$div_end = '<span class="wp-smush-submit-wrap">
				<input type="submit" id="wp-smush-save-settings" class="sui-button sui-button-primary" aria-describedby="smush-submit-description" value="' . esc_html__( 'UPDATE SETTINGS', 'wp-smushit' ) . '">
		        <span class="sui-icon-loader sui-loading sui-hidden"></span>
		        <span class="smush-submit-note" id="smush-submit-description">' . esc_html__( 'Smush will automatically check for any images that need re-smushing.', 'wp-smushit' ) . '</span>
		        </span>';

			// Container footer.
			$this->container_footer( '', $div_end );

			// Close settings container.
			echo '</div>';

			/**
			 * Action hook to add extra containers after settings.
			 */
			do_action( 'smush_settings_ui_bottom' );
		}

		/**
		 * Integration settings for Smush.
		 *
		 * All integrations suhc as S3, NextGen can be added to this container.
		 *
		 * @return void
		 */
		public function integrations_ui() {
			global $wp_smush;

			// If no integration settings found, bail.
			if ( empty( $this->intgration_group ) ) {
				return;
			}

			$is_pro = $wp_smush->validate_install();

			// Container box class.
			$class = $is_pro ? 'smush-integrations-wrapper wp-smush-pro' : 'smush-integrations-wrapper';

			echo '<div class="sui-box ' . esc_attr( $class ) . '" id="wp-smush-integrations-box">';

			// Container header.
			$this->container_header( esc_html__( 'Integrations', 'wp-smushit' ) );

			// Box body class.
			$box_body_class = $is_pro ? 'sui-box-body' : 'sui-box-body sui-upsell-items';
			echo '<div class="' . esc_attr( $box_body_class ) . '">';

			// Integration settings content.
			$this->integrations_settings();

			if ( ! $is_pro ) {
				$this->integrations_upsell();
			}

			echo '</div>';

			/**
			 * Filter to enable/disable submit button in integration settings.
			 *
			 * @param bool $show_submit Should show submit?
			 */
			$show_submit = apply_filters( 'wp_smush_integration_show_submit', false );

			// Box footer content including buttons.
			$div_end = '<span class="wp-smush-submit-wrap">
				<input type="submit" id="wp-smush-save-settings" class="sui-button sui-button-primary" aria-describedby="smush-submit-description" value="' . esc_html__( 'UPDATE SETTINGS', 'wp-smushit' ) . '" ' . disabled( ! $show_submit, true, false ) . '>
		        <span class="sui-icon-loader sui-loading sui-hidden"></span>
		        <span class="smush-submit-note" id="smush-submit-description">' . esc_html__( 'Smush will automatically check for any images that need re-smushing.', 'wp-smushit' ) . '</span>
		        </span>';

			// Container footer if pro.
			if ( $show_submit ) {
				$this->container_footer( '', $div_end );
			}
			echo '</div>';

			/**
			 * Action hook to add extra containers after integration settings.
			 */
			do_action( 'smush_integrations_ui_bottom' );
		}

		/**
		 * Outputs the smush stats for the site.
		 *
		 * @return void
		 */
		public function smush_stats_container() {
			global $wpsmushit_admin, $wpsmush_db, $wpsmush_settings;

			$settings       = $wpsmush_settings->settings;
			$networkwide    = (bool) $settings['networkwide'];
			$resize_enabled = (bool) $settings['resize'];
			$resize_count   = $wpsmush_db->resize_savings( false, false, true );
			$resize_count   = ! $resize_count ? 0 : $resize_count;
			$remaining      = $wpsmushit_admin->remaining_count;
			// Split human size to get format and size.
			$human        = explode( ' ', $wpsmushit_admin->stats['human'] );
			$human_size   = empty( $human[0] ) ? '0' : $human[0];
			$human_format = empty( $human[1] ) ? 'B' : $human[1];
			?>

			<div class="sui-box sui-summary sui-summary-smush">
				<div class="sui-summary-image-space">
				</div>
				<div class="sui-summary-segment">
					<div class="sui-summary-details">
						<span class="sui-summary-large wp-smush-stats-human"><?php echo $human_size; ?></span>
						<i class="sui-icon-info sui-warning smush-stats-icon <?php echo $remaining > 0 ? '' : 'sui-hidden'; ?>" aria-hidden="true"></i>
						<span class="sui-summary-detail wp-smush-savings">
							<span class="wp-smush-stats-human"><?php echo $human_format; ?></span> /
							<span class="wp-smush-stats-percent"><?php echo $wpsmushit_admin->stats['percent'] > 0 ? number_format_i18n( $wpsmushit_admin->stats['percent'], 1, '.', '' ) : 0; ?></span>%
						</span>
						<span class="sui-summary-sub"><?php _e( 'Total Savings', 'wp-smushit' ); ?></span>
						<span class="smushed-items-count">
							<span class="wp-smush-count-total">
								<span class="sui-summary-detail wp-smush-total-optimised"><?php echo $wpsmushit_admin->stats['total_images']; ?></span>
								<span class="sui-summary-sub"><?php _e( 'Images Smushed', 'wp-smushit' ); ?></span>
							</span>
							<?php if ( $resize_count > 0 ) { ?>
								<span class="wp-smush-count-resize-total">
									<span class="sui-summary-detail wp-smush-total-optimised"><?php echo $resize_count; ?></span>
									<span class="sui-summary-sub"><?php _e( 'Images Resized', 'wp-smushit' ); ?></span>
								</span>
							<?php } ?>
						</span>
					</div>
				</div>
				<div class="sui-summary-segment">
					<ul class="sui-list smush-stats-list">
						<li class="smush-resize-savings">
							<?php
							$resize_savings = 0;
							// Get current resize savings.
							if ( ! empty( $wpsmushit_admin->stats['resize_savings'] ) && $wpsmushit_admin->stats['resize_savings'] > 0 ) {
								$resize_savings = size_format( $wpsmushit_admin->stats['resize_savings'], 1 );
							}
							?>
							<span class="sui-list-label">
								<?php _e( 'Image Resize Savings', 'wp-smushit' ); ?>
								<?php if ( ! $resize_enabled && $resize_savings <= 0 ) { ?>
									<p class="wp-smush-stats-label-message">
										<?php
										$link_class = 'wp-smush-resize-enable-link';
										if ( is_multisite() && $networkwide ) {
											$settings_link = $wpsmushit_admin->settings_link( array(), true, true ) . '#enable-resize';
										} elseif ( 'bulk' !== $this->current_tab ) {
											$settings_link = $wpsmushit_admin->settings_link( array(), true ) . '#enable-resize';
										} else {
											$settings_link = '#';
											$link_class    = 'wp-smush-resize-enable';
										}
										printf(
											esc_html__( 'Save a ton of space by not storing over-sized images on your server. %1$1sEnable image resizing%2$2s', 'wp-smushit' ),
											'<a role="button" class="' . esc_attr( $link_class ) . '" href="' . esc_url( $settings_link ) . '">',
											'<span class="sui-screen-reader-text">' . __( 'Clicking this link will toggle the Enable image resizing checkbox.', 'wp-smushit' ) . '</span></a>'
										);
										?>
									</p>
								<?php } ?>
							</span>
							<span class="sui-list-detail wp-smush-stats">
							<?php if ( $resize_enabled || $resize_savings > 0 ) { ?>
								<?php echo $resize_savings > 0 ? $resize_savings : __( 'No resize savings available', 'wp-smushit' ); ?>
							<?php } ?>
							</span>
						</li>
						<?php
						/**
						 * Allows to output Directory Smush stats
						 */
						do_action( 'stats_ui_after_resize_savings' );
						?>
					</ul>
				</div>
			</div>
			<?php
		}

		/**
		 * Show super smush stats in stats section.
		 *
		 * If a pro member and super smush is enabled, show super smushed
		 * stats else show message that encourage them to enable super smush.
		 * If free user show the avg savings that can be achived using Pro.
		 *
		 * @return void
		 */
		public function pro_savings_stats() {
			global $wp_smush, $wpsmushit_admin, $wpsmush_settings;

			$settings = $wpsmush_settings->settings;

			$networkwide = (bool) $settings['networkwide'];

			if ( ! $wp_smush->validate_install() ) {
				if ( empty( $wpsmushit_admin->stats ) || empty( $wpsmushit_admin->stats['pro_savings'] ) ) {
					$wpsmushit_admin->set_pro_savings();
				}
				$pro_savings      = $wpsmushit_admin->stats['pro_savings'];
				$show_pro_savings = $pro_savings['savings'] > 0 ? true : false;
				if ( $show_pro_savings ) {
					?>
					<li class="smush-avg-pro-savings" id="smush-avg-pro-savings">
						<span class="sui-list-label"><?php esc_html_e( 'Pro Savings', 'wp-smushit' ); ?>
							<span class="sui-tag sui-tag-pro sui-tooltip sui-tooltip-constrained" data-tooltip="<?php esc_html_e( 'Join WPMU DEV to unlock multi-pass lossy compression', 'wp-smushit' ); ?>"><?php esc_html_e( 'PRO', 'wp-smushit' ); ?></span>
						</span>
						<span class="sui-list-detail wp-smush-stats">
							<span class="wp-smush-stats-human"><?php echo esc_html( $pro_savings['savings'] ); ?></span>
							<span class="wp-smush-stats-sep">/</span>
							<span class="wp-smush-stats-percent"><?php echo esc_html( $pro_savings['percent'] ); ?></span>%
						</span>
					</li>
					<?php
				}
			} else {
				$compression_savings = 0;
				if ( ! empty( $wpsmushit_admin->stats ) && ! empty( $wpsmushit_admin->stats['bytes'] ) ) {
					$compression_savings = $wpsmushit_admin->stats['bytes'] - $wpsmushit_admin->stats['resize_savings'];
				}
				?>
				<li class="super-smush-attachments">
					<span class="sui-list-label">
						<?php esc_html_e( 'Super-Smush Savings', 'wp-smushit' ); ?>
						<?php if ( ! $wp_smush->lossy_enabled ) { ?>
							<p class="wp-smush-stats-label-message">
								<?php
								$link_class = 'wp-smush-lossy-enable-link';
								if ( is_multisite() && $networkwide ) {
									$settings_link = $wpsmushit_admin->settings_link( array(), true, true ) . '#enable-lossy';
								} elseif ( 'bulk' !== $this->current_tab ) {
									$settings_link = $wpsmushit_admin->settings_link( array(), true ) . '#enable-lossy';
								} else {
									$settings_link = '#';
									$link_class    = 'wp-smush-lossy-enable';
								}
								printf(
									esc_html__( 'Compress images up to 2x more than regular smush with almost no visible drop in quality. %1$sEnable Super-smush%2$s', 'wp-smushit' ),
									'<a role="button" class="' . esc_attr( $link_class ) . '" href="' . esc_url( $settings_link ) . '">',
									'<span class="sui-screen-reader-text">' . __( 'Clicking this link will toggle the Super Smush checkbox.', 'wp-smushit' ) . '</span></a>'
								);
								?>
							</p>
						<?php } ?>
					</span>
					<?php if ( $wp_smush->lossy_enabled ) { ?>
						<span class="sui-list-detail wp-smush-stats">
							<span class="smushed-savings"><?php echo size_format( $compression_savings, 1 ); ?></span>
						</span>
					<?php } ?>
				</li>
				<?php
			}
		}

		/**
		 * Show conversion savings stats in stats section.
		 *
		 * Show Png to Jpg conversion savings in stats box if the
		 * settings enabled or savings found.
		 *
		 * @return void
		 */
		public function conversion_savings_stats() {
			global $wp_smush, $wpsmushit_admin;

			if ( $wp_smush->validate_install() && ! empty( $wpsmushit_admin->stats['conversion_savings'] ) && $wpsmushit_admin->stats['conversion_savings'] > 0 ) {
				?>
				<li class="smush-conversion-savings">
					<span class="sui-list-label"><?php esc_html_e( 'PNG to JPEG savings', 'wp-smushit' ); ?></span>
					<span class="sui-list-detail wp-smush-stats"><?php echo $wpsmushit_admin->stats['conversion_savings'] > 0 ? size_format( $wpsmushit_admin->stats['conversion_savings'], 1 ) : '0MB'; ?></span>
				</li>
				<?php
			}
		}

		/**
		 * Process and display the settings.
		 *
		 * Free and pro version settings are shown in same section. For free users, pro settings won't be shown.
		 * To print full size smush, resize and backup in group, we hook at `smush_setting_column_right_end`.
		 *
		 * @return void
		 */
		public function options_ui() {
			global $wp_smush, $wpsmushit_admin, $wpsmush_settings;

			// Get all grouped settings that can be skipped.
			$grouped_settings = array_merge( $this->resize_group, $this->full_size_group, $this->intgration_group );

			// Get settings values.
			$settings = empty( $wpsmush_settings->settings ) ? $wpsmush_settings->init_settings() : $wpsmush_settings->settings;
			?>

			<!-- Start settings form -->
			<form id="wp-smush-settings-form" method="post">

			<input type="hidden" name="setting_form" id="setting_form" value="bulk">

			<?php $opt_networkwide = WP_SMUSH_PREFIX . 'networkwide'; ?>
			<?php $opt_networkwide_val = $wpsmush_settings->settings['networkwide']; ?>

			<?php if ( is_multisite() && is_network_admin() ) : ?>

				<?php $class = $wpsmush_settings->settings['networkwide'] ? '' : ' sui-hidden'; ?>

			<div class="sui-box-settings-row wp-smush-basic">
				<div class="sui-box-settings-col-1">
					<label for="<?php echo $opt_networkwide; ?>" aria-hidden="true">
						<span class="sui-settings-label"><?php echo $wpsmushit_admin->settings['networkwide']['short_label']; ?></span>
						<span class="sui-description"><?php echo $wpsmushit_admin->settings['networkwide']['desc']; ?></span>
					</label>
				</div>
				<div class="sui-box-settings-col-2">
					<label class="sui-toggle">
						<input type="checkbox" id="<?php echo $opt_networkwide; ?>" name="<?php echo $opt_networkwide; ?>" <?php checked( $opt_networkwide_val, 1, true ); ?> value="1">
						<span class="sui-toggle-slider"></span>
						<label class="toggle-label" for="<?php echo $opt_networkwide; ?>" aria-hidden="true"></label>
					</label>
					<label for="<?php echo $opt_networkwide; ?>">
						<?php echo $wpsmushit_admin->settings['networkwide']['label']; ?>
					</label>
				</div>
			</div>
			<input type="hidden" name="setting-type" value="network">
			<div class="network-settings-wrapper<?php echo $class; ?>">
				<?php
				endif;
				if ( ! is_multisite() || ( ! $wpsmush_settings->settings['networkwide'] && ! is_network_admin() ) || is_network_admin() ) {
					foreach ( $wpsmushit_admin->settings as $name => $values ) {
						// Skip networkwide settings, we already printed it.
						if ( 'networkwide' == $name ) {
							continue;
						}

						// Skip premium features if not a member.
						if ( ! in_array( $name, $wpsmushit_admin->basic_features ) && ! $wp_smush->validate_install() ) {
							continue;
						}

						$setting_m_key = WP_SMUSH_PREFIX . $name;
						$setting_val   = empty( $settings[ $name ] ) ? 0 : $settings[ $name ];

						// Set the default value 1 for auto smush.
						if ( 'auto' == $name && ( false === $setting_val || ! isset( $setting_val ) ) ) {
							$setting_val = 1;
						}

						// Group Original, Resize and Backup for pro users
						if ( in_array( $name, $grouped_settings ) ) {
							continue;
						}

						$label = ! empty( $wpsmushit_admin->settings[ $name ]['short_label'] ) ? $wpsmushit_admin->settings[ $name ]['short_label'] : $wpsmushit_admin->settings[ $name ]['label'];

						// Show settings option.
						$this->settings_row( $setting_m_key, $label, $name, $setting_val );
					}
					// Hook after general settings.
					do_action( 'wp_smush_after_basic_settings' );
				}
				if ( is_multisite() && is_network_admin() ) {
					echo '</div>';
				}
				?>
			</form>
			<?php
		}

		/**
		 * Process and display the integration settings.
		 *
		 * Free and pro version settings are shown in same section. For free users, pro settings won't be shown.
		 * To print full size smush, resize and backup in group, we hook at `smush_setting_column_right_end`.
		 *
		 * @return void
		 */
		public function integrations_settings() {
			global $wp_smush, $wpsmushit_admin, $wpsmush_settings;

			// Get settings values.
			$settings = empty( $wpsmush_settings->settings ) ? $wpsmush_settings->init_settings() : $wpsmush_settings->settings;
			?>

			<!-- Start integration form -->
			<form id="wp-smush-settings-form" method="post">

				<input type="hidden" name="setting_form" id="setting_form" value="integration">
				<?php if ( is_multisite() && is_network_admin() ) : ?>
					<input type="hidden" name="wp-smush-networkwide" id="wp-smush-networkwide" value="1">
					<input type="hidden" name="setting-type" value="network">
					<?php
				endif;

				wp_nonce_field( 'save_wp_smush_options', 'wp_smush_options_nonce', '', true );

				// For subsite admins show only if networkwide options is not enabled.
				if ( ! is_multisite() || ( ! $wpsmush_settings->settings['networkwide'] && ! is_network_admin() ) || is_network_admin() ) {
					foreach ( $this->intgration_group as $name ) {
						// Settings key.
						$setting_m_key = WP_SMUSH_PREFIX . $name;
						// Disable setting.
						$disable = apply_filters( 'wp_smush_integration_status_' . $name, false );
						// Gray out row, disable setting.
						$upsell = ( ! in_array( $name, $wpsmushit_admin->basic_features ) && ! $wp_smush->validate_install() );
						// Current setting value.
						$setting_val = ( $upsell || empty( $settings[ $name ] ) || $disable ) ? 0 : $settings[ $name ];
						// Current setting label.
						$label = ! empty( $wpsmushit_admin->settings[ $name ]['short_label'] ) ? $wpsmushit_admin->settings[ $name ]['short_label'] : $wpsmushit_admin->settings[ $name ]['label'];

						// Show settings option.
						$this->settings_row( $setting_m_key, $label, $name, $setting_val, true, $disable, $upsell );

					}
					// Hook after showing integration settings.
					do_action( 'wp_smush_after_integration_settings' );
				}
				?>
			</form>
			<?php
		}

		/**
		 * Show upsell notice.
		 *
		 * @since 2.8.0
		 */
		public function integrations_upsell() {
			global $wpsmushit_admin;

			// Upgrade url for upsell.
			$upsell_url = add_query_arg(
				array(
					'utm_source'   => 'smush',
					'utm_medium'   => 'plugin',
					'utm_campaign' => 'smush-nextgen-settings-upsell',
				), $wpsmushit_admin->upgrade_url
			);
			?>
			<div class="sui-box-settings-row sui-upsell-row">
				<img class="sui-image sui-upsell-image sui-upsell-image-smush integrations-upsell-image" src="<?php echo esc_url( WP_SMUSH_URL . 'assets/images/smush-promo.png' ); ?>">
				<div class="sui-upsell-notice">
					<p>
						<?php
						printf(
							/* translators: %1$s - a href tag, %2$s - a href closing tag */
							esc_html__( 'Smush Pro supports hosting images on Amazon S3 and optimizing NextGen Gallery images directly through NextGen Gallery settings. %1$sTry it free%2$s with a WPMU DEV membership today!', 'wp-smushit' ),
							'<a href="' . esc_url( $upsell_url ) . '" target="_blank" title="' . esc_html__( 'Try Smush Pro for FREE', 'wp-smushit' ) . '">',
							'</a>'
						);
						?>
					</p>
				</div>
			</div>
			<?php
		}

		/**
		 * Single settings row html content.
		 *
		 * @param string $setting_m_key  Setting key.
		 * @param string $label          Setting label.
		 * @param string $name           Setting name.
		 * @param mixed  $setting_val    Setting value.
		 * @param bool   $skip_group     Skip group settings.
		 * @param bool   $disable        Disable the setting.
		 * @param bool   $upsell         Gray out row to show upsell.
		 *
		 * @return void
		 */
		public function settings_row( $setting_m_key, $label, $name, $setting_val, $skip_group = false, $disable = false, $upsell = false ) {
			global $wpsmushit_admin;

			// Get all grouped settings that can be skipped.
			$grouped_settings = array_merge( $this->resize_group, $this->full_size_group, $this->intgration_group );

			?>
			<div class="sui-box-settings-row wp-smush-basic <?php echo $upsell ? 'sui-disabled' : ''; ?>">
				<div class="sui-box-settings-col-1">
					<span class="sui-settings-label">
						<?php echo $label; ?>
						<?php if ( 'gutenberg' === $name ) : ?>
							<span class="sui-tag sui-tag-beta sui-tooltip sui-tooltip-constrained"
								  data-tooltip="<?php esc_attr_e( 'This feature is likely to work without issue, however Gutenberg is in beta stage and some issues are still present.', 'wp-smushit' ); ?>"
							>
								<?php esc_html_e( 'Beta', 'wp-smushit' ); ?>
							</span>
						<?php endif; ?>
					</span>

					<span class="sui-description">
						<?php echo $wpsmushit_admin->settings[ $name ]['desc']; ?>
					</span>
				</div>
				<div class="sui-box-settings-col-2" id="column-<?php echo $setting_m_key; ?>">
					<?php if ( ! in_array( $name, $grouped_settings ) || $skip_group ) : ?>
						<div class="sui-form-field">
							<label class="sui-toggle">
								<input type="checkbox" aria-describedby="<?php echo $setting_m_key . '-desc'; ?>" id="<?php echo $setting_m_key; ?>" name="<?php echo $setting_m_key; ?>" <?php checked( $setting_val, 1, true ); ?> value="1" <?php disabled( $disable ); ?>>
								<span class="sui-toggle-slider"></span>
							</label>
							<label for="<?php echo $setting_m_key; ?>">
								<?php echo $wpsmushit_admin->settings[ $name ]['label']; ?>
							</label>
							<!-- Print/Perform action in right setting column -->
							<?php do_action( 'smush_setting_column_right_inside', $name ); ?>
						</div>
					<?php endif; ?>
					<!-- Print/Perform action in right setting column -->
					<?php do_action( 'smush_setting_column_right_outside', $name ); ?>
				</div>
			</div>
			<?php
		}

		/**
		 * Outputs the content for bulk smush div.
		 *
		 * Container box to handle bulk smush actions. Show progress bars,
		 * bulk smush action buttons etc. in this box.
		 *
		 * @return void
		 */
		public function bulk_smush_content() {
			global $wp_smush, $wpsmushit_admin, $wpsmush_settings;

			// Check if Pro user.
			$is_pro = $wp_smush->validate_install();

			// Check if all items are smushed.
			$all_done = ( $wpsmushit_admin->smushed_count == $wpsmushit_admin->total_count ) && 0 == count( $wpsmushit_admin->resmush_ids );

			// Show re-smush notice.
			echo $this->bulk_resmush_content();
			$upgrade_url = add_query_arg(
				array(
					'utm_source'   => 'smush',
					'utm_medium'   => 'plugin',
					'utm_campaign' => 'smush_stats_enable_lossy',
				), $wpsmushit_admin->upgrade_url
			);

			// Check whether to show pagespeed recommendation or not.
			$hide_pagespeed = get_site_option( WP_SMUSH_PREFIX . 'hide_pagespeed_suggestion' );

			// If there are no images in media library.
			if ( 0 >= $wpsmushit_admin->total_count ) :
				?>
				<span class="wp-smush-no-image tc">
					<img src="<?php echo WP_SMUSH_URL . 'assets/images/smush-no-media.png'; ?>" alt="<?php esc_html_e( 'No attachments found - Upload some images', 'wp-smushit' ); ?>">
				</span>
				<p class="wp-smush-no-images-content tc roboto-regular">
					<?php esc_html_e( 'We haven’t found any images in your media library yet so there’s no smushing to be done!', 'wp-smushit' ); ?><br>
					<?php esc_html_e( 'Once you upload images, reload this page and start playing!', 'wp-smushit' ); ?>
				</p>
				<span class="wp-smush-upload-images sui-no-padding-bottom tc">
					<a class="sui-button sui-button-primary tc" href="<?php echo esc_url( admin_url( 'media-new.php' ) ); ?>"><?php esc_html_e( 'UPLOAD IMAGES', 'wp-smushit' ); ?></a>
				</span>
			<?php
				return;
				endif;
			?>

			<div class="sui-notice sui-notice-success wp-smush-all-done<?php echo $all_done ? '' : ' sui-hidden'; ?>" tabindex="0">
				<p><?php esc_html_e( 'All attachments have been smushed. Awesome!', 'wp-smushit' ); ?></p>
			</div>

			<?php $this->progress_bar( $wpsmushit_admin ); ?>

			<div class="smush-final-log sui-hidden">
				<div class="smush-bulk-errors"></div>
				<div class="smush-bulk-errors-actions sui-hidden">
					<a href="<?php echo esc_url( admin_url( 'upload.php' ) ); ?>" class="sui-button sui-button-icon sui-button-ghost">
						<i class="sui-icon-photo-picture" aria-hidden="true"></i>
						<?php esc_html_e( 'View all', 'wp-smushit' ); ?>
					</a>
				</div>
			</div>

			<?php if ( ! $hide_pagespeed ) : ?>
				<div class="wp-smush-pagespeed-recommendation<?php echo $all_done ? '' : ' sui-hidden'; ?>">
					<span class="smush-recommendation-title"><?php esc_html_e( 'Still having trouble with PageSpeed tests? Give these a go…', 'wp-smushit' ); ?></span>
					<ol class="smush-recommendation-list">
						<?php if ( ! $is_pro ) : ?>
							<li class="smush-recommendation-lossy"><?php printf( esc_html__( 'Upgrade to Smush Pro for advanced lossy compression. %1$sTry pro free%2$s.', 'wp-smushit' ), '<a href="' . $upgrade_url . '" target="_blank">', '</a>' ); ?></li>
						<?php elseif ( ! $wpsmush_settings->settings['lossy'] ) : ?>
							<li class="smush-recommendation-lossy"><?php printf( esc_html__( 'Enable %1$sSuper-smush%2$s for advanced lossy compression to optimise images further with almost no visible drop in quality.', 'wp-smushit' ), '<a href="#" class="wp-smush-lossy-enable">', '</a>' ); ?></li>
						<?php endif; ?>
						<li class="smush-recommendation-resize"><?php printf( esc_html__( 'Make sure your images are the right size for your theme. %1$sLearn more%2$s.', 'wp-smushit' ), '<a href="' . esc_url( 'https://goo.gl/kCqWxS' ) . '" target="_blank">', '</a>' ); ?></li>
						<?php if ( ! $wpsmush_settings->settings['resize'] ) : ?>
							<?php // Check if resize original is disabled ?>
							<li class="smush-recommendation-resize-original"><?php printf( esc_html__( 'Enable %1$sResize Full Size Images%2$s to scale big images down to a reasonable size and save a ton of space.', 'wp-smushit' ), '<a href="#" class="wp-smush-resize-enable">', '</a>' ); ?></li>
						<?php endif; ?>
					</ol>
					<span class="dismiss-recommendation"><?php esc_html_e( 'DISMISS', 'wp-smushit' ); ?></span>
				</div>
			<?php endif; ?>

			<div class="wp-smush-bulk-wrapper <?php echo $all_done ? ' sui-hidden' : ''; ?>">
				<?php
				if ( $wpsmushit_admin->remaining_count > 0 ) :
					$class       = count( $wpsmushit_admin->resmush_ids ) > 0 ? ' sui-hidden' : '';
					$upgrade_url = add_query_arg(
						array(
							'utm_source'   => 'smush',
							'utm_medium'   => 'plugin',
							'utm_campaign' => 'smush_bulksmush_limit_notice',
						),
						$wpsmushit_admin->upgrade_url
					);
					?>
				<div class="sui-notice sui-notice-warning<?php echo $class; ?>" tabindex="0">
					<p>
						<?php printf( _n( '%1$s, you have %2$s%3$s%4$d%5$s attachment%6$s that needs smushing!', '%1$s, you have %2$s%3$s%4$d%5$s attachments%6$s that need smushing!', $wpsmushit_admin->remaining_count, 'wp-smushit' ), $wpsmushit_admin->get_user_name(), '<strong>', '<span class="wp-smush-remaining-count">', $wpsmushit_admin->remaining_count, '</span>', '</strong>' ); ?>
						<?php if ( ! $is_pro && $wpsmushit_admin->remaining_count > 50 ) : ?>
							<?php printf( esc_html__( ' %1$sUpgrade to Pro%2$s to bulk smush all your images with one click.', 'wp-smushit' ), '<a href="' . esc_url( $upgrade_url ) . '" target="_blank" title="' . esc_html__( 'Smush Pro', 'wp-smushit' ) . '">', '</a>' ); ?>
							<?php esc_html_e( ' Free users can smush 50 images with each click.', 'wp-smushit' ); ?>
						<?php endif; ?>
					</p>
				</div>
				<?php endif; ?>
				<button type="button" class="wp-smush-all sui-button sui-button-primary" title="<?php esc_html_e( 'Click to start Bulk Smushing images in Media Library', 'wp-smushit' ); ?>">
					<?php esc_html_e( 'BULK SMUSH NOW', 'wp-smushit' ); ?>
				</button>
			</div>
			<?php
			if ( $is_pro && $wp_smush->lossy_enabled ) {
				?>
				<p class="wp-smush-enable-lossy tc sui-hidden">
					<?php esc_html_e( 'Tip: Enable Super-smush in the Settings area to get even more savings with almost no visible drop in quality.', 'wp-smushit' ); ?>
				</p>
				<?php
			}
			$this->super_smush_promo();
		}

		/**
		 * Show nav side bars for settings box.
		 *
		 * Usign Shared UI nav box to show sidebar navigation.
		 *
		 * @return void
		 */
		public function settings_nav() {

			global $wpsmushit_admin, $wpsmush_settings;

			$remaining   = $wpsmushit_admin->remaining_count;
			$is_network  = is_network_admin();
			$networkwide = $wpsmush_settings->settings['networkwide'];

			$bulk_tag_content = $bulk_tag_class = '';
			if ( $remaining > 0 && ! $is_network ) {
				$bulk_tag_class   = ' sui-tag sui-tag-warning wp-smush-remaining-count';
				$bulk_tag_content = $remaining;
			}

			?>
			<div class="sui-sidenav smush-sidenav">
				<?php $main_nav_li = $select_nav = $mob_nav_li = ''; ?>
				<?php foreach ( $this->tabs as $tab => $label ) : ?>
					<?php $class = $tag_content = $tag_class = ''; ?>
					<?php if ( $tab === 'bulk' && ! $is_network ) : ?>
						<?php $tag_content = $bulk_tag_content; ?>
						<?php $tag_class = $bulk_tag_class; ?>
					<?php endif; ?>
					<?php if ( ( $tab === 'directory' && $is_network ) || ( $tab === 'integrations' && empty( $this->intgration_group ) ) ) : ?>
						<?php continue; ?>
					<?php endif; ?>
					<?php if ( $is_network && ! $networkwide && 'bulk' !== $tab ) : ?>
						<?php $class = ' sui-hidden'; ?>
					<?php endif; ?>
					<?php if ( $is_network && $networkwide && ! in_array( $tab, $this->network_tabs ) ) : ?>
						<?php continue; ?>
					<?php endif; ?>
					<?php if ( ! $is_network && $networkwide && ! in_array( $tab, $this->subsite_tabs ) ) : ?>
						<?php continue; ?>
						<?php
					endif;
					// This is to avoid duplicate foreach loop.
					$main_nav_li .= '<li class="sui-vertical-tab smush-' . $tab . $class . ( $tab === $this->current_tab ? ' current' : '' ) . '">';
					$main_nav_li .= '<a href="' . add_query_arg( 'tab', esc_html( $tab ) ) . '">' . $label . '</a>';
					$main_nav_li .= '<span class="smush-nav-icon ' . $tab . $tag_class . '" aria-hidden="true">' . $tag_content . '</span>';
					$main_nav_li .= '</li>';
					// Mobile nav
					if ( is_multisite() && is_network_admin() ) {
						$url = network_admin_url( 'admin.php?page=smush' . '&tab=' . $tab );
					} else {
						$url = admin_url( 'admin.php?page=smush' . '&tab=' . $tab );
					}
					$select_nav .= '<option class="' . $class . '" value="' . esc_url( $url ) . '" ' . selected( $tab, $this->current_tab, false ) . '>' . $label . '</option>';
					$mob_nav_li .= '<li class="smush-' . $tab . $class . ( $tab === $this->current_tab ? ' current' : '' ) . '">' . $label . '</li>';
					?>
				<?php endforeach; ?>
				<ul class="sui-vertical-tabs sui-sidenav-hide-md">
					<?php echo $main_nav_li; ?>
				</ul>
				<div class="sui-sidenav-hide-lg">
					<select class="sui-mobile-nav">
						<?php echo $select_nav; ?>
					</select>
				</div>
			</div>
			<?php
		}

		/**
		 * Content for showing progress bar.
		 *
		 * @param object $count
		 */
		public function progress_bar( $count ) {
			?>
			<div class="wp-smush-bulk-progress-bar-wrapper sui-hidden">
				<p class="wp-smush-bulk-active roboto-medium">
					<?php
					printf(
						esc_html__( '%1$sBulk smush is currently running.%2$s You need to keep this page open for the process to complete.', 'wp-smushit' ),
						'<strong>',
						'</strong>'
					);
					?>
				</p>

				<div class="sui-notice sui-notice-warning sui-hidden"></div>

				<div class="sui-progress-block sui-progress-can-close">
					<div class="sui-progress">
						<div class="sui-progress-text sui-icon-loader sui-loading">
							<span class="wp-smush-images-percent">0</span><span>%</span>
						</div>
						<div class="sui-progress-bar">
							<span class="wp-smush-progress-inner" style="width: 0%"></span>
						</div>
					</div>
					<button class="sui-progress-close sui-tooltip wp-smush-cancel-bulk" type="button" data-tooltip="<?php esc_html_e( 'Stop current bulk smush process.', 'wp-smushit' ); ?>">
						<i class="sui-icon-close"></i>
					</button>
					<button class="sui-progress-close sui-tooltip wp-smush-all sui-hidden" type="button" data-tooltip="<?php esc_html_e( 'Resume scan.', 'wp-smushit' ); ?>">
						<i class="sui-icon-close"></i>
					</button>
				</div>

				<div class="sui-progress-state">
					<span class="sui-progress-state-text">
						<span>0</span>/<span><?php echo  absint( $count->remaining_count ); ?></span> <?php esc_html_e( 'images optimized', 'wp-smushit' ); ?>
					</span>
				</div>

				<div class="sui-box-body sui-no-padding-right sui-hidden">
					<button type="button" class="wp-smush-all sui-button wp-smush-started">
						<?php esc_html_e( 'RESUME', 'wp-smushit' ); ?>
					</button>
				</div>
			</div>
			<?php
		}

		/**
		 * Shows a option to ignore the Image ids which can be resmushed while bulk smushing.
		 *
		 * @param bool $count Resmush + Unsmushed Image count.
		 * @param bool $show Should show?
		 *
		 * @return string
		 */
		public function bulk_resmush_content( $count = false, $show = false ) {
			global $wpsmushit_admin;

			// If we already have count, don't fetch it.
			if ( false === $count ) {
				// If we have the resmush ids list, Show Resmush notice and button
				if ( $resmush_ids = get_option( 'wp-smush-resmush-list' ) ) {

					// Count.
					$count = count( $resmush_ids );

					// Whether to show the remaining re-smush notice.
					$show = $count > 0 ? true : false;

					// Get the actual remainaing count.
					if ( ! isset( $wpsmushit_admin->remaining_count ) ) {
						$wpsmushit_admin->setup_global_stats();
					}

					$count = $wpsmushit_admin->remaining_count;
				}
			}

			// Show only if we have any images to ber resmushed.
			if ( $show ) {
				return '<div class="sui-notice sui-notice-warning wp-smush-resmush-notice wp-smush-remaining" tabindex="0">
						<p>
							<span class="wp-smush-notice-text">' . sprintf( _n( '%1$s, you have %2$s%3$s%4$d%5$s attachment%6$s that needs re-compressing!', '%1$s, you have %2$s%3$s%4$d%5$s attachments%6$s that need re-compressing!', $count, 'wp-smushit' ), $wpsmushit_admin->get_user_name(), '<strong>', '<span class="wp-smush-remaining-count">', $count, '</span>', '</strong>' ) . '</span>
						</p>
						<div class="sui-notice-buttons">
							<button class="sui-button sui-button-ghost wp-smush-skip-resmush sui-tooltip" data-tooltip="' . esc_html__( 'Skip re-smushing the images', 'wp-smushit' ) . '">' . esc_html__( 'Skip', 'wp-smushit' ) . '</button>
						</div>
	                </div>';
			}
		}

		/**
		 * Pro features list box to show after settings.
		 *
		 * @return void
		 */
		public function pro_features_container() {
			global $wp_smush, $wpsmush_settings, $wpsmushit_admin;

			// Do not show if pro user.
			if ( $wp_smush->validate_install() || ( is_network_admin() && ! $wpsmush_settings->settings['networkwide'] ) ) {
				return;
			}

			// Upgrade url with analytics keys.
			$upgrade_url = add_query_arg(
				array(
					'utm_source'   => 'smush',
					'utm_medium'   => 'plugin',
					'utm_campaign' => 'smush_advancedsettings_profeature_tag',
				),
				$wpsmushit_admin->upgrade_url
			);

			// Upgrade url for upsell.
			$upsell_url = add_query_arg(
				array(
					'utm_source'   => 'smush',
					'utm_medium'   => 'plugin',
					'utm_campaign' => 'smush-advanced-settings-upsell',
				),
				$wpsmushit_admin->upgrade_url
			);

			?>

			<div class="sui-box">
				<div class="sui-box-header">
					<h3 class="sui-box-title"><?php esc_html_e( 'Pro Features', 'wp-smushit' ); ?></h3>
					<div class="sui-actions-right">
						<a class="sui-button sui-button-green sui-tooltip" target="_blank" href="<?php echo esc_url( $upgrade_url ); ?>" data-tooltip="<?php _e( 'Join WPMU DEV to try Smush Pro for free.', 'wp-smushit' ); ?>"><?php _e( 'UPGRADE TO PRO', 'wp-smushit' ); ?></a>
					</div>
				</div>
				<div class="sui-box-body">
					<ul class="smush-pro-features">
						<li class="smush-pro-feature-row">
							<div class="smush-pro-feature-title">
								<?php esc_html_e( 'Super-smush lossy compression', 'wp-smushit' ); ?></div>
							<div class="smush-pro-feature-desc"><?php esc_html_e( 'Optimize images 2x more than regular smushing and with no visible loss in quality using Smush’s intelligent multi-pass lossy compression.', 'wp-smushit' ); ?></div>
						</li>
						<li class="smush-pro-feature-row">
							<div class="smush-pro-feature-title">
								<?php esc_html_e( 'Smush my original full size images', 'wp-smushit' ); ?></div>
							<div class="smush-pro-feature-desc"><?php esc_html_e( 'By default, Smush only compresses thumbnails and image sizes generated by WordPress. With Smush Pro you can also smush your original images.', 'wp-smushit' ); ?></div>
						</li>
						<li class="smush-pro-feature-row">
							<div class="smush-pro-feature-title">
								<?php esc_html_e( 'Make a copy of my full size images', 'wp-smushit' ); ?></div>
							<div class="smush-pro-feature-desc"><?php esc_html_e( 'Save copies of the original full-size images you upload to your site so you can restore them at any point. Note: Activating this setting will double the size of the uploads folder where your site’s images are stored.', 'wp-smushit' ); ?></div>
						</li>
						<li class="smush-pro-feature-row">
							<div class="smush-pro-feature-title">
								<?php esc_html_e( 'Auto-convert PNGs to JPEGs (lossy)', 'wp-smushit' ); ?></div>
							<div class="smush-pro-feature-desc"><?php esc_html_e( 'When you compress a PNG, Smush will check if converting it to JPEG could further reduce its size, and do so if necessary,', 'wp-smushit' ); ?></div>
						</li>
						<li class="smush-pro-feature-row">
							<div class="smush-pro-feature-title">
								<?php esc_html_e( 'NextGen Gallery Integration', 'wp-smushit' ); ?></div>
							<div class="smush-pro-feature-desc"><?php esc_html_e( 'Allow smushing images directly through NextGen Gallery settings.', 'wp-smushit' ); ?></div>
						</li>
					</ul>
					<div class="sui-upsell-row">
						<img class="sui-image sui-upsell-image sui-upsell-image-smush" src="<?php echo WP_SMUSH_URL . 'assets/images/smush-promo.png'; ?>">
						<div class="sui-upsell-notice">
							<p><?php printf( esc_html__( 'Smush Pro gives you all these extra settings and absolutely not limits on smushing your images? Did we mention Smush Pro also gives you up to 2x better compression too? %1$sTry it all free%2$s with a WPMU DEV membership today!', 'wp-smushit' ), '<a href="' . esc_url( $upsell_url ) . '" target="_blank" title="' . esc_html__( 'Try Smush Pro for FREE', 'wp-smushit' ) . '">', '</a>' ); ?></p>
						</div>
					</div>
				</div>
			</div>

			<?php

		}

		/**
		 * Get membership validation message.
		 *
		 * @param bool $notice Is a notice?
		 *
		 * @return string
		 */
		public function get_user_validation_message( $notice = true ) {
			$notice_class = $notice ? ' sui-notice sui-notice-warning' : ' notice notice-warning is-dismissible';
			$wpmu_contact = sprintf( '<a href="%s" target="_blank">', esc_url( 'https://premium.wpmudev.org/contact' ) );
			$attr_message = esc_html__( 'Validating..', 'wp-smushit' );
			$recheck_link = '<a href="#" id="wp-smush-revalidate-member" data-message="%s">';
			$message      = sprintf( esc_html__( 'It looks like Smush couldn’t verify your WPMU DEV membership so Pro features have been disabled for now. If you think this is an error, run a %1$sre-check%2$s or get in touch with our %3$ssupport team%4$s.', 'wp-smushit' ), $recheck_link, '</a>', $wpmu_contact, '</a>' );
			$content      = sprintf( '<div id="wp-smush-invalid-member" data-message="%s" class="sui-hidden hidden' . $notice_class . '"><p>%s</p></div>', $attr_message, $message );

			return $content;
		}

		/**
		 * Prints Resize, Smush Original, and Backup settings.
		 *
		 * @param string $name Name of the current setting being processed
		 * @param string $section Section name.
		 *
		 * @return void
		 */
		public function full_size_options( $name = '' ) {
			global $wp_smush, $wpsmushit_admin, $wpsmush_settings;

			// Continue only if orginal image option.
			if ( 'original' !== $name || ! $wp_smush->validate_install() ) {
				return;
			}

			foreach ( $this->full_size_group as $name ) {

				$setting_val = $wpsmush_settings->settings[ $name ];
				$setting_key = WP_SMUSH_PREFIX . $name;
				?>
				<div class="sui-form-field">
					<label class="sui-toggle">
						<input type="checkbox" aria-describedby="<?php echo $setting_key; ?>-desc" id="<?php echo $setting_key; ?>" name="<?php echo $setting_key; ?>" <?php checked( $setting_val, 1 ); ?> value="1">
						<span class="sui-toggle-slider"></span>
						<label class="toggle-label <?php echo $setting_key . '-label'; ?>" for="<?php echo $setting_key; ?>" aria-hidden="true"></label>
					</label>
					<label for="<?php echo $setting_key; ?>">
						<?php echo $wpsmushit_admin->settings[ $name ]['label']; ?>
					</label>
					<span class="sui-description sui-toggle-description"><?php echo $wpsmushit_admin->settings[ $name ]['desc']; ?></span>

				</div>
				<?php
			}
		}

		/**
		 * Prints front end image size detection option.
		 *
		 * @param string $name Name of the current setting being processed
		 * @param string $section Section name.
		 *
		 * @return void
		 */
		public function detect_size_options( $name ) {
			// Only add to resize setting.
			if ( 'resize' !== $name ) {
				return;
			}

			global $wpsmushit_admin, $wpsmush_settings;

			foreach ( $this->resize_group as $name ) {
				// Do not continue if setting is not found.
				if ( ! isset( $wpsmush_settings->settings[ $name ] ) ) {
					continue;
				}

				$setting_val = $wpsmush_settings->settings[ $name ];
				$setting_key = WP_SMUSH_PREFIX . $name;
				?>
				<div class="sui-form-field">
					<label class="sui-toggle">
						<input type="checkbox" aria-describedby="<?php echo $setting_key; ?>-desc" id="<?php echo $setting_key; ?>" name="<?php echo $setting_key; ?>" <?php checked( $setting_val, 1, true ); ?> value="1">
						<span class="sui-toggle-slider"></span>
						<label class="toggle-label <?php echo $setting_key . '-label'; ?>" for="<?php echo $setting_key; ?>" aria-hidden="true"></label>
					</label>
					<label for="<?php echo $setting_key; ?>">
						<?php echo $wpsmushit_admin->settings[ $name ]['label']; ?>
					</label>
					<span class="sui-description sui-toggle-description">
						<?php echo $wpsmushit_admin->settings[ $name ]['desc']; ?>
						<?php if ( 'detection' === $name ) : ?>
							<?php if ( $setting_val === 1 ) : // If detection is enabled. ?>
								<div class="sui-notice sui-notice-info smush-notice-sm smush-highlighting-notice">
									<p>
										<?php printf( esc_html__( 'Incorrect image size highlighting is active. %1$sView the frontend%2$s of your website to see which images aren\'t the correct size for their containers.', 'wp-smushit' ), '<a href="' . home_url() . '" target="_blank">', '</a>' ); ?>
									</p>
								</div>
							<?php endif; ?>
							<div class="sui-notice sui-notice-warning smush-notice-sm smush-highlighting-warning sui-hidden">
								<p>
									<?php esc_html_e( 'Almost there! To finish activating this feature you must save your settings.', 'wp-smushit' ); ?>
								</p>
							</div>
							<span class="sui-description">Note: This feature is only visible on screens wider than 800px.</span>
						<?php endif; ?>
					</span>
				</div>
				<?php
			}
		}

		/**
		 * Prints out the page header for bulk smush page.
		 *
		 * @return void
		 */
		public function smush_page_header() {
			global $wpsmushit_admin, $wpsmush_dir, $wpsmush_s3;

			$current_screen = get_current_screen();

			if ( $wpsmushit_admin->remaining_count === 0 || $wpsmushit_admin->smushed_count === 0 ) {
				// Initialize global stats.
				$wpsmushit_admin->setup_global_stats();
			}

			// Page heading for free and pro version.
			$page_heading = esc_html__( 'DASHBOARD', 'wp-smushit' );

			// Re-check images notice.
			$recheck_notice = $this->get_recheck_message();

			// User API check, and display a message if not valid
			$user_validation = $this->get_user_validation_message();
			?>

			<div class="sui-header wp-smush-page-header">
				<h1 class="sui-header-title"><?php echo $page_heading; ?></h1>
				<div class="sui-actions-right">
					<?php if ( ! is_network_admin() && 'bulk' === $this->current_tab ) : ?>
						<?php $data_type = 'gallery_page_wp-smush-nextgen-bulk' === $current_screen->id ? ' data-type="nextgen"' : ''; ?>
						<button class="sui-button wp-smush-scan" data-tooltip="<?php esc_html_e( 'Lets you check if any images can be further optimized. Useful after changing settings.', 'wp-smushit' ); ?>"<?php echo $data_type; ?>><?php esc_html_e( 'Re-Check Images', 'wp-smushit' ); ?></button>
					<?php endif; ?>
					<a href="https://premium.wpmudev.org/project/wp-smush-pro/#wpmud-hg-project-documentation" class="sui-button sui-button-ghost" target="_blank"><i class="sui-icon-academy" aria-hidden="true"></i> <?php esc_html_e( 'Documentation', 'wp-smushit' ); ?></a>
				</div>
			</div>

			<?php
			// Show messages.
			echo $user_validation;
			echo $recheck_notice;
			// Check and show missing directory smush table error only on main site.
			if ( $wpsmush_dir->should_continue() ) {
				echo $wpsmush_dir->show_table_error();
			}

			// Check for any stored API message and show it.
			$this->show_api_message();

			$this->settings_updated();

			// Show S3 integration message, if user hasn't enabled it.
			if ( is_object( $wpsmush_s3 ) && method_exists( $wpsmush_s3, 's3_support_required_notice' ) ) {
				$wpsmush_s3->s3_support_required_notice();
			}
		}

		/**
		 * Show additional descriptions for settings.
		 *
		 * @param string $setting_key Setting key.
		 *
		 * @return void
		 */
		public function settings_desc( $setting_key = '' ) {

			if ( empty( $setting_key ) || ! in_array(
				$setting_key, array(
					'resize',
					'original',
					'strip_exif',
					'png_to_jpg',
					's3',
				)
			)
			) {
				return;
			}
			?>
			<span class="sui-description sui-toggle-description" id="<?php echo WP_SMUSH_PREFIX . $setting_key . '-desc'; ?>">
				<?php
				switch ( $setting_key ) {

					case 'resize':
						esc_html_e( 'Save a ton of space by not storing over-sized images on your server. Set a maximum height and width for all images uploaded to your site so that any unnecessarily large images are automatically scaled down to a reasonable size. Note: Image resizing happens automatically when you upload attachments. This setting does not apply to images smushed using Directory Smush feature. To support retina devices, we recommend using 2x the dimensions of your image size.', 'wp-smushit' );
						break;
					case 'original':
						esc_html_e( 'By default, bulk smush will ignore your original uploads and only compress the thumbnail sizes your theme outputs. Enable this setting to also smush your original uploads. We recommend storing copies of your originals (below) in case you ever need to restore them.', 'wp-smushit' );
						break;
					case 'strip_exif':
						esc_html_e( 'Note: This data adds to the size of the image. While this information might be important to photographers, it’s unnecessary for most users and safe to remove.', 'wp-smushit' );
						break;
					case 'png_to_jpg':
						esc_html_e( 'Note: Any PNGs with transparency will be ignored. Smush will only convert PNGs if it results in a smaller file size. The resulting file will have a new filename and extension (JPEG), and any hard-coded URLs on your site that contain the original PNG filename will need to be updated.', 'wp-smushit' );
						break;
					case 's3':
						esc_html_e( 'Note: For this process to happen automatically you need automatic smushing enabled.', 'wp-smushit' );
						break;
					case 'default':
						break;
				}
				?>
			</span>
			<?php
		}

		/**
		 * Get re-check notice after settings update.
		 *
		 * @return string|void
		 */
		public function get_recheck_message() {
			global $wpsmush_settings;

			// Return if not multisite, or on network settings page, Netowrkwide settings is disabled.
			if ( ! is_multisite() || is_network_admin() || ! $wpsmush_settings->settings['networkwide'] ) {
				return;
			}

			// Check the last settings stored in db.
			$run_recheck = get_site_option( WP_SMUSH_PREFIX . 'run_recheck', false );

			// If not same, Display notice
			if ( ! $run_recheck ) {
				return;
			}

			$message  = '<div class="sui-notice sui-notice-success wp-smush-re-check-message">';
			$message .= '<p>' . esc_html__( 'Smush settings were updated, performing a quick scan to check if any of the images need to be Smushed again.', 'wp-smushit' ) . '</p>';
			$message .= '<span class="sui-notice-dismiss"><a href="#">' . esc_html__( 'Dismiss', 'wp-smushit' ) . '</a></span>';
			$message .= '</div>';

			return $message;
		}

		/**
		 * Prints all the registererd image sizes, to be selected/unselected for smushing.
		 *
		 * @param string $name Setting key.
		 *
		 * @return void
		 */
		public function image_sizes( $name = '' ) {
			// Add only to auto smush settings.
			if ( 'auto' !== $name ) {
				return;
			}

			global $wpsmushit_admin, $wpsmush_settings, $wp_smush;

			// Additional Image sizes.
			$image_sizes = $wpsmush_settings->get_setting( WP_SMUSH_PREFIX . 'image_sizes', false );
			$sizes       = $wpsmushit_admin->image_dimensions();

			/**
			 * Add an additional item for full size.
			 * Do not use intermediate_image_sizes filter.
			 */
			$sizes['full'] = array();

			$is_pro   = $wp_smush->validate_install();
			$disabled = '';

			$setting_status = empty( $wpsmush_settings->settings['auto'] ) ? 0 : $wpsmush_settings->settings['auto'];

			if ( ! empty( $sizes ) ) {
				?>
				<!-- List of image sizes recognised by WP Smush -->
				<div class="wp-smush-image-size-list <?php echo $setting_status ? '' : ' sui-hidden'; ?>">
				<span class="sui-description"><?php esc_html_e( 'Every time you upload an image to your site, WordPress generates a resized version of that image for every default and/or custom image size that your theme has registered. This means there are multiple versions of your images in your media library. Choose the images sizes below that you would like optimized:', 'wp-smushit' ); ?></span>
					<?php
					foreach ( $sizes as $size_k => $size ) {
						// If image sizes array isn't set, mark all checked ( Default Values ).
						if ( false === $image_sizes ) {
							$checked = true;
						} else {
							$checked = is_array( $image_sizes ) ? in_array( $size_k, $image_sizes ) : false;
						}
						// For free users, disable full size option.
						if ( $size_k === 'full' ) {
							$disabled = $is_pro ? '' : 'disabled';
							$checked  = $is_pro ? $checked : false;
						}
						?>
					<label class="sui-checkbox sui-description">
					<input type="checkbox" id="wp-smush-size-<?php echo $size_k; ?>" <?php checked( $checked, true ); ?> name="wp-smush-image_sizes[]" value="<?php echo $size_k; ?>" <?php echo $disabled; ?>>
					<span aria-hidden="true"></span>
																	<?php if ( isset( $size['width'], $size['height'] ) ) { ?>
						<span class="sui-description"><?php echo $size_k . ' (' . $size['width'] . 'x' . $size['height'] . ') '; ?></span>
					<?php } else { ?>
						<span class="sui-description"><?php echo $size_k; ?>
							<?php if ( ! $is_pro ) { ?>
								<span class="sui-tag sui-tag-pro sui-tooltip sui-tooltip-constrained" data-tooltip="<?php esc_html_e( 'Join WPMU DEV to unlock multi-pass lossy compression', 'wp-smushit' ); ?>"><?php esc_html_e( 'PRO', 'wp-smushit' ); ?></span>
							<?php } ?>
						</span>
					<?php } ?>
					</label>
						<?php
					}
					?>
				</div>
				<?php
			}
		}

		/**
		 * Prints Dimensions required for Resizing
		 *
		 * @param string $name Setting name.
		 * @param string $class_prefix Custom class prefix.
		 *
		 * @return void
		 */
		public function resize_settings( $name = '', $class_prefix = '' ) {
			// Add only to full size settings.
			if ( 'resize' !== $name ) {
				return;
			}

			global $wpsmush_settings, $wpsmushit_admin;

			// Dimensions.
			$resize_sizes = $wpsmush_settings->get_setting(
				WP_SMUSH_PREFIX . 'resize_sizes', array(
					'width'  => '',
					'height' => '',
				)
			);

			// Set default prefix is custom prefix is empty.
			$prefix = empty( $class_prefix ) ? WP_SMUSH_PREFIX : $class_prefix;

			// Get max dimensions.
			$max_sizes = $wpsmushit_admin->get_max_image_dimensions();

			$setting_status = empty( $wpsmush_settings->settings['resize'] ) ? 0 : $wpsmush_settings->settings['resize'];

			// Placeholder width and Height.
			$p_width = $p_height = 2048;
			?>
			<div class="wp-smush-resize-settings-wrap<?php echo $setting_status ? '' : ' sui-hidden'; ?>">
				<div class="sui-row">
					<div class="sui-col">
						<label aria-labelledby="<?php echo $prefix; ?>label-max-width" for="<?php echo $prefix . $name . '_width'; ?>" class="sui-label"><?php esc_html_e( 'Max width', 'wp-smushit' ); ?></label>
						<input aria-required="true" type="number" aria-describedby="<?php echo $prefix; ?>resize-note" id="<?php echo $prefix . $name . '_width'; ?>" name="<?php echo WP_SMUSH_PREFIX . $name . '_width'; ?>" class="sui-form-control wp-smush-resize-input" value="<?php echo isset( $resize_sizes['width'] ) && '' != $resize_sizes['width'] ? $resize_sizes['width'] : $p_width; ?>">
					</div>
					<div class="sui-col">
						<label aria-labelledby="<?php echo $prefix; ?>label-max-height" for="<?php echo $prefix . $name . '_height'; ?>" class="sui-label"><?php esc_html_e( 'Max height', 'wp-smushit' ); ?></label>
						<input aria-required="true" type="number" aria-describedby="<?php echo $prefix; ?>resize-note" id="<?php echo $prefix . $name . '_height'; ?>" name="<?php echo WP_SMUSH_PREFIX . $name . '_height'; ?>" class="sui-form-control wp-smush-resize-input" value="<?php echo isset( $resize_sizes['height'] ) && '' != $resize_sizes['height'] ? $resize_sizes['height'] : $p_height; ?>">
					</div>
				</div>
				<div class="sui-description" id="<?php echo $prefix; ?>resize-note"><?php printf( esc_html__( 'Currently, your largest image size is set at %1$s%2$dpx wide %3$s %4$dpx high%5$s.', 'wp-smushit' ), '<strong>', $max_sizes['width'], '&times;', $max_sizes['height'], '</strong>' ); ?></div>
				<div class="sui-description sui-notice sui-notice-info wp-smush-update-width sui-hidden" tabindex="0"><?php esc_html_e( "Just to let you know, the width you've entered is less than your largest image and may result in pixelation.", 'wp-smushit' ); ?></div>
				<div class="sui-description sui-notice sui-notice-info wp-smush-update-height sui-hidden" tabindex="0"><?php esc_html_e( 'Just to let you know, the height you’ve entered is less than your largest image and may result in pixelation.', 'wp-smushit' ); ?></div>
			</div>
			<span class="sui-description sui-toggle-description">
			<?php printf( esc_html__( 'Note: Image resizing happens automatically when you upload attachments. To support retina devices, we recommend using 2x the dimensions of your image size. Animated GIFs will not be resized as they will lose their animation, please use a tool such as %s to resize then re-upload.', 'wp-smushit' ), '<a href="http://gifgifs.com/resizer/" target="_blank">http://gifgifs.com/resizer/</a>' ); ?>
			<?php esc_html_e( ' ', 'wp-smushit' ); ?>
			</span>
			<?php
		}

		/**
		 * Content of the install/upgrade notice based on free or pro version.
		 *
		 * @return void
		 */
		public function installation_notice() {
			global $wpsmushit_admin;

			// Whether new/existing installation.
			$install_type = get_site_option( 'wp-smush-install-type', false );

			if ( ! $install_type ) {
				$install_type = $wpsmushit_admin->smushed_count > 0 ? 'existing' : 'new';
				update_site_option( 'wp-smush-install-type', $install_type );
			}

			// Prepare notice.
			if ( 'new' === $install_type ) {
				$notice_heading = esc_html__( 'Thanks for installing Smush. We hope you like it!', 'wp-smushit' );
				$notice_content = esc_html__( 'And hey, if you do, you can join WPMU DEV for a free 30 day trial and get access to even more features!', 'wp-smushit' );
				$button_content = esc_html__( 'Try Smush Pro Free', 'wp-smushit' );
			} else {
				$notice_heading = esc_html__( 'Thanks for upgrading Smush!', 'wp-smushit' );
				$notice_content = esc_html__( 'Did you know she has secret super powers? Yes, she can super-smush images for double the savings, store original images, and bulk smush thousands of images in one go. Get started with a free WPMU DEV trial to access these advanced features.', 'wp-smushit' );
				$button_content = esc_html__( 'Try Smush Pro Free', 'wp-smushit' );
			}

			$upgrade_url = add_query_arg(
				array(
					'utm_source'   => 'smush',
					'utm_medium'   => 'plugin',
					'utm_campaign' => 'smush_dashboard_upgrade_notice',
				),
				$wpsmushit_admin->upgrade_url
			);
			?>
			<div class="notice smush-notice" style="display: none;">
				<div class="smush-notice-logo"><span></span></div>
				<div class="smush-notice-message<?php echo 'new' === $install_type ? ' wp-smush-fresh' : ' wp-smush-existing'; ?>">
					<strong><?php echo $notice_heading; ?></strong>
					<?php echo $notice_content; ?>
				</div>
				<div class="smush-notice-cta">
					<a href="<?php echo esc_url( $upgrade_url ); ?>" class="smush-notice-act button-primary" target="_blank">
						<?php echo $button_content; ?>
					</a>
					<button class="smush-notice-dismiss smush-dismiss-welcome" data-msg="<?php esc_html_e( 'Saving', 'wp-smushit' ); ?>"><?php esc_html_e( 'Dismiss', 'wp-smushit' ); ?></button>
				</div>
			</div>
			<?php
		}

		/**
		 * Super smush promo content.
		 *
		 * @return void
		 */
		public function super_smush_promo() {
			global $wp_smush, $wpsmushit_admin;

			// Do not show if pro user.
			if ( $wp_smush->validate_install() ) {
				return;
			}

			// Upgrade url with analytics keys.
			$upgrade_url = add_query_arg(
				array(
					'utm_source'   => 'smush',
					'utm_medium'   => 'plugin',
					'utm_campaign' => 'smush_bulksmush_upsell_notice',
				), $wpsmushit_admin->upgrade_url
			);

			?>
			<div class="sui-upsell-row">
				<img class="sui-image sui-upsell-image sui-upsell-image-smush" src="<?php echo WP_SMUSH_URL . 'assets/images/smush-graphic-bulksmush-upsell@2x.png'; ?>">
				<div class="sui-upsell-notice">
					<p><?php printf( esc_html__( 'Did you know WP Smush Pro delivers up to 2x better compression, allows you to smush your originals and removes any bulk smushing limits? – %1$sTry it absolutely FREE%2$s', 'wp-smushit' ), '<a href="' . esc_url( $upgrade_url ) . '" target="_blank" title="' . esc_html__( 'Try Smush Pro for FREE', 'wp-smushit' ) . '">', '</a>' ); ?></p>
				</div>
			</div>
			<?php
		}

		/**
		 * Displays a admin notice for settings update.
		 *
		 * @return void
		 */
		public function settings_updated() {
			global $wpsmushit_admin, $wpsmush_settings;

			// Check if networkwide settings are enabled, do not show settings updated message.
			if ( is_multisite() && $wpsmush_settings->settings['networkwide'] && ! is_network_admin() ) {
				return;
			}

			// Show setttings saved message.
			if ( 1 == $wpsmush_settings->get_setting( 'wp-smush-settings_updated', false ) ) {

				// Default message.
				$message = esc_html__( 'Your settings have been updated!', 'wp-smushit' );
				// Notice class.
				$message_class = ' sui-notice-success';

				// Additonal message if we got work to do!
				$resmush_count = is_array( $wpsmushit_admin->resmush_ids ) && count( $wpsmushit_admin->resmush_ids ) > 0;
				$smush_count   = is_array( $wpsmushit_admin->remaining_count ) && $wpsmushit_admin->remaining_count > 0;

				if ( $smush_count || $resmush_count ) {
					$message_class = ' sui-notice-warning';
					// Show link to bulk smush tab from other tabs.
					$bulk_smush_link = 'bulk' === $this->current_tab ? '<a href="#" class="wp-smush-trigger-bulk">' : '<a href="' . $wpsmushit_admin->settings_link( array(), true ) . '">';
					$message        .= ' ' . sprintf( esc_html__( 'You have images that need smushing. %1$sBulk smush now!%2$s', 'wp-smushit' ), $bulk_smush_link, '</a>' );
				}

				echo '<div class="sui-notice-top sui-can-dismiss' . $message_class . '">
						<div class="sui-notice-content">
							<p>' . $message . '</p>
						</div>
						<span class="sui-notice-dismiss">
							<a role="button" href="#" aria-label="' . __( 'Dismiss', 'wp-smushit' ) . '" class="sui-icon-check"></a>
						</span>
					</div>';

				// Remove the option.
				$wpsmush_settings->delete_setting( 'wp-smush-settings_updated' );
			}
		}

		/**
		 * Set current active tab.
		 *
		 * @return void
		 */
		public function set_current_tab() {
			global $wpsmush_settings, $wpsmush_dir;

			/**
			 * Filter to alter setting tabs.
			 *
			 * @param array Tabs.
			 */
			$this->tabs = apply_filters(
				'smush_setting_tabs', array(
					'bulk'         => esc_html__( 'Bulk Smush', 'wp-smushit' ),
					'directory'    => esc_html__( 'Directory Smush', 'wp-smushit' ),
					'integrations' => esc_html__( 'Integrations', 'wp-smushit' ),
				// 'cdn'          => esc_html__( 'CDN', 'wp-smushit' ),
				)
			);

			// Check if current page network admin page.
			$is_network     = is_network_admin();
			$is_networkwide = $wpsmush_settings->settings['networkwide'];

			// Set the current tab.
			$this->current_tab = isset( $_GET['tab'] ) && in_array( $_GET['tab'], array_keys( $this->tabs ) ) ? $_GET['tab'] : 'bulk';

			// If no integration found, set current tab as bulk smush.
			if ( ( empty( $this->intgration_group ) && 'integrations' === $this->current_tab ) ||
				 ( $is_network && ! $is_networkwide ) ||
				 ( ! $is_network && $is_networkwide && ! in_array( $this->current_tab, $this->subsite_tabs ) ) ||
				 ( 'directory' === $this->current_tab && ! $wpsmush_dir->should_continue() )
			) {
				// If networkwide option is enabled only show bulk and directory smush.
				$this->current_tab = 'bulk';
			}

			/**
			 * Filter to change current tab.
			 *
			 * @param string Current tab key.
			 */
			$this->current_tab = apply_filters( 'smush_setting_current_tab', $this->current_tab );
		}

		/**
		 * Display a stored API message.
		 *
		 * @return null
		 */
		public function show_api_message() {

			// Do not show message for any other users.
			if ( ! is_network_admin() && ! is_super_admin() ) {
				return null;
			}

			$api_message = get_site_option( WP_SMUSH_PREFIX . 'api_message', array() );
			$api_message = current( $api_message );

			// Return if the API message is not set or user dismissed it earlier
			if ( empty( $api_message ) || ! is_array( $api_message ) || $api_message['status'] !== 'show' ) {
				return null;
			}

			$message      = empty( $api_message['message'] ) ? '' : $api_message['message'];
			$message_type = ( is_array( $api_message ) && ! empty( $api_message['type'] ) ) ? $api_message['type'] : 'info';
			$type_class   = 'warning' === $message_type ? 'sui-notice-warning' : 'sui-notice-info';

			echo '<div class="sui-notice wp-smush-api-message ' . $type_class . '">
				<p>' . $message . '</p>
				<span class="sui-notice-dismiss"><a href="#">' . esc_html__( 'Dismiss', 'wp-smushit' ) . '</a></span>
				</div>';
		}
	}

	global $wpsmush_bulkui;

	$wpsmush_bulkui = new WpSmushBulkUi();
}
