<?php
/**
 * Displays the UI for .org plugin recommendations
 *
 * @package WP_Smush
 * @subpackage Admin
 * @since 2.7.9
 *
 * @author Umesh Kumar <umesh@incsub.com>
 *
 * @copyright (c) 2018, Incsub (http://incsub.com)
 */

if ( ! class_exists( 'WpSmushRecommender' ) ) {

	class WpSmushRecommender {

		/**
		 * WpSmushRecommender constructor.
		 */
		public function __construct() {

			// Hook UI at the end of Settings UI.
			add_action( 'smush_admin_ui_bottom', array( $this, 'ui' ), 12 );
		}

		/**
		 * Do not display Directory smush for Subsites
		 *
		 * @return bool True/False, whether to display the Directory smush or not.
		 */
		public function should_continue() {
			global $wp_smush;

			// Do not show directory smush, if not main site in a network.
			if ( $wp_smush->validate_install() ) {
				return false;
			}

			return true;
		}

		/**
		 * Output the required UI for Plugin recommendations.
		 *
		 * @return void
		 */
		public function ui() {
			if ( $this->should_continue() ) { ?>

				<div class="sui-row" id="sui-cross-sell-footer">
					<div><span class="sui-icon-plugin-2"></span></div>
					<h3><?php esc_html_e( 'Check out our other free wordpress.org plugins!', 'wp-smushit' ); ?></h3>
				</div>
				<div class="sui-row sui-cross-sell-modules">
					<?php
					// Hummingbird.
					$hb_title   = esc_html__( 'Hummingbird Page Speed Optimization', 'wp-smushit' );
					$hb_content = esc_html__( 'Performance Tests, File Optimization & Compression, Page, Browser & Gravatar Caching, GZIP Compression, CloudFlare Integration & more.', 'wp-smushit' );
					$hb_class   = 'hummingbird';
					$hb_url     = esc_url( 'https://wordpress.org/plugins/hummingbird-performance/' );
					echo $this->recommendation_box( $hb_title, $hb_content, $hb_url, $hb_class, 1 );
					// Defender.
					$df_title   = esc_html__( 'Defender Security, Monitoring, and Hack Protection', 'wp-smushit' );
					$df_content = esc_html__( 'Security Tweaks & Recommendations, File & Malware Scanning, Login & 404 Lockout Protection, Two-Factor Authentication & more.', 'wp-smushit' );
					$df_class   = 'defender';
					$df_url     = esc_url( 'https://wordpress.org/plugins/defender-security/' );
					echo $this->recommendation_box( $df_title, $df_content, $df_url, $df_class, 2 );
					// SmartCrawl.
					$sc_title   = esc_html__( 'SmartCrawl Search Engine Optimization', 'wp-smushit' );
					$sc_content = esc_html__( 'Customize Titles & Meta Data, OpenGraph, Twitter & Pinterest Support, Auto-Keyword Linking, SEO & Readability Analysis, Sitemaps, URL Crawler & more.', 'wp-smushit' );
					$sc_class   = 'smartcrawl';
					$sc_url     = esc_url( 'https://wordpress.org/plugins/smartcrawl-seo' );
					echo $this->recommendation_box( $sc_title, $sc_content, $sc_url, $sc_class, 3 );
					$site_url = esc_url( 'https://premium.wpmudev.org/projects/' );
					$site_url = add_query_arg(
						array(
							'utm_source'   => 'smush',
							'utm_medium'   => 'plugin',
							'utm_campaign' => 'smush_footer_upsell_notice',
						),
						$site_url
					);
					?>
				</div>
				<div class="sui-cross-sell-bottom">
					<h3><?php esc_html_e( 'WPMU DEV - Your WordPress Toolkit', 'wp-smushit' ); ?></h3>
					<p><?php esc_html_e( 'Pretty much everything you need for developing and managing WordPress based websites, and then some.', 'wp-smushit' ); ?></p>
					<a class="sui-button sui-button-green" href="<?php echo $site_url; ?>" id="dash-uptime-update-membership" target="_blank"><?php esc_html_e( 'Learn more', 'wp-smushit' ); ?></a>
					<img class="sui-image" src="<?php echo WP_SMUSH_URL . 'assets/images/dev-team.png'; ?>" srcset="<?php echo WP_SMUSH_URL . 'assets/images/dev-team@2x.png'; ?> 2x" alt="<?php esc_html_e( 'Try pro features for free!', 'wp-smushit' ); ?>">
				</div>
				<?php
			}
			?>
			<div class="sui-footer"><?php esc_html_e( 'Made with', 'wp-smushit' ); ?> <i class="sui-icon-heart" aria-hidden="true"></i> <?php esc_html_e( 'by WPMU DEV', 'wp-smushit' ); ?></div>
			<?php
		}

		/**
		 * Prints the UI for the given recommended plugin
		 *
		 * @param string $title Box title.
		 * @param string $content Box content.
		 * @param string $link Plugin link.
		 * @param string $plugin_class Plugin class.
		 *
		 * @return void
		 */
		public function recommendation_box( $title, $content, $link, $plugin_class, $seq ) {
			// Put bg to box parent div
			?>
			<div class="sui-col-md-4">
			<div class="sui-cross-<?php echo $seq; ?> sui-cross-<?php echo $plugin_class; ?>"><span></span></div>
			<div class="sui-box">
				<div class="sui-box-body">
					<h3><?php echo $title; ?></h3>
					<p><?php echo $content; ?></p>
					<a href="<?php echo esc_url( $link ); ?>" class="sui-button sui-button-ghost" target="_blank"><?php esc_html_e( 'View features', 'wp-smushit' ); ?> <i class="sui-icon-arrow-right"></i></a>
				</div>
			</div>
			</div>
			<?php
		}
	}

	// Class Object.
	global $wpsmush_recommender;
	$wpsmush_promo = new WpSmushRecommender();
}
