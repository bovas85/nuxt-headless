<?php
/*
Plugin Name: Disable Comments
Plugin URI: https://wordpress.org/plugins/disable-comments/
Description: Allows administrators to globally disable comments on their site. Comments can be disabled according to post type.
Version: 1.8.0
Author: Samir Shah
Author URI: http://www.rayofsolaris.net/
License: GPL2
Text Domain: disable-comments
Domain Path: /languages/
*/

if( !defined( 'ABSPATH' ) )
	exit;

class Disable_Comments {
	const DB_VERSION = 6;
	private static $instance = null;
	private $options;
	private $networkactive;
	private $modified_types = array();

	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	function __construct() {
		// are we network activated?
		$this->networkactive = ( is_multisite() && array_key_exists( plugin_basename( __FILE__ ), (array) get_site_option( 'active_sitewide_plugins' ) ) );

		// Load options
		if( $this->networkactive ) {
			$this->options = get_site_option( 'disable_comments_options', array() );
		}
		else {
			$this->options = get_option( 'disable_comments_options', array() );
		}

		// If it looks like first run, check compat
		if( empty( $this->options ) ) {
			$this->check_compatibility();
		}

		// Upgrade DB if necessary
		$this->check_db_upgrades();

		$this->init_filters();
	}

	private function check_compatibility() {
		if ( version_compare( $GLOBALS['wp_version'], '4.7', '<' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			deactivate_plugins( __FILE__ );
			if ( isset( $_GET['action'] ) && ( $_GET['action'] == 'activate' || $_GET['action'] == 'error_scrape' ) ) {
				exit( sprintf( __( 'Disable Comments requires WordPress version %s or greater.', 'disable-comments' ), '4.7' ) );
			}
		}
	}

	private function check_db_upgrades() {
		$old_ver = isset( $this->options['db_version'] ) ? $this->options['db_version'] : 0;
		if( $old_ver < self::DB_VERSION ) {
			if( $old_ver < 2 ) {
				// upgrade options from version 0.2.1 or earlier to 0.3
				$this->options['disabled_post_types'] = get_option( 'disable_comments_post_types', array() );
				delete_option( 'disable_comments_post_types' );
			}
			if( $old_ver < 5 ) {
				// simple is beautiful - remove multiple settings in favour of one
				$this->options['remove_everywhere'] = isset( $this->options['remove_admin_menu_comments'] ) ? $this->options['remove_admin_menu_comments'] : false;
				foreach( array( 'remove_admin_menu_comments', 'remove_admin_bar_comments', 'remove_recent_comments', 'remove_discussion', 'remove_rc_widget' ) as $v )
					unset( $this->options[$v] );
			}

			foreach( array( 'remove_everywhere', 'permanent', 'extra_post_types' ) as $v ) {
				if( !isset( $this->options[$v] ) ) {
					$this->options[$v] = false;
				}
			}

			$this->options['db_version'] = self::DB_VERSION;
			$this->update_options();
		}
	}

	private function update_options() {
		if( $this->networkactive ) {
			update_site_option( 'disable_comments_options', $this->options );
		}
		else {
			update_option( 'disable_comments_options', $this->options );
		}
	}

	/*
	 * Get an array of disabled post type.
	 */
	private function get_disabled_post_types() {
		$types = $this->options['disabled_post_types'];
		// Not all extra_post_types might be registered on this particular site
		if( $this->networkactive ) {
			foreach( (array) $this->options['extra_post_types'] as $extra ) {
				if( post_type_exists( $extra ) ) {
					$types[] = $extra;
				}
			}
		}
		return $types;
	}

	/*
	 * Check whether comments have been disabled on a given post type.
	 */
	private function is_post_type_disabled( $type ) {
		return in_array( $type, $this->get_disabled_post_types() );
	}

	private function init_filters() {
		// These need to happen now
		if( $this->options['remove_everywhere'] ) {
			add_action( 'widgets_init', array( $this, 'disable_rc_widget' ) );
			add_filter( 'wp_headers', array( $this, 'filter_wp_headers' ) );
			add_action( 'template_redirect', array( $this, 'filter_query' ), 9 );	// before redirect_canonical

			// Admin bar filtering has to happen here since WP 3.6
			add_action( 'template_redirect', array( $this, 'filter_admin_bar' ) );
			add_action( 'admin_init', array( $this, 'filter_admin_bar' ) );
		}

		// These can happen later
		add_action( 'plugins_loaded', array( $this, 'register_text_domain' ) );
		add_action( 'wp_loaded', array( $this, 'init_wploaded_filters' ) );
	}

	public function register_text_domain() {
		load_plugin_textdomain( 'disable-comments', false, dirname( plugin_basename( __FILE__ ) ) .  '/languages' );
	}

	public function init_wploaded_filters(){
		$disabled_post_types = $this->get_disabled_post_types();
		if( !empty( $disabled_post_types ) ) {
			foreach( $disabled_post_types as $type ) {
				// we need to know what native support was for later
				if( post_type_supports( $type, 'comments' ) ) {
					$this->modified_types[] = $type;
					remove_post_type_support( $type, 'comments' );
					remove_post_type_support( $type, 'trackbacks' );
				}
			}
			add_filter( 'comments_array', array( $this, 'filter_existing_comments' ), 20, 2 );
			add_filter( 'comments_open', array( $this, 'filter_comment_status' ), 20, 2 );
			add_filter( 'pings_open', array( $this, 'filter_comment_status' ), 20, 2 );
		}
		elseif( is_admin() && !$this->options['remove_everywhere'] ) {
			// It is possible that $disabled_post_types is empty if other
			// plugins have disabled comments. Hence we also check for
			// remove_everywhere. If you still get a warning you probably
			// shouldn't be using this plugin.
			add_action( 'all_admin_notices', array( $this, 'setup_notice' ) );
		}

		// Filters for the admin only
		if( is_admin() ) {
			if( $this->networkactive ) {
				add_action( 'network_admin_menu', array( $this, 'settings_menu' ) );
				add_action( 'network_admin_menu', array( $this, 'tools_menu' ) );
				add_filter( 'network_admin_plugin_action_links', array( $this, 'plugin_actions_links'), 10, 2 );
			}
			else {
				add_action( 'admin_menu', array( $this, 'settings_menu' ) );
				add_action( 'admin_menu', array( $this, 'tools_menu' ) );
				add_filter( 'plugin_action_links', array( $this, 'plugin_actions_links'), 10, 2 );
				if( is_multisite() )	// We're on a multisite setup, but the plugin isn't network activated.
					register_deactivation_hook( __FILE__, array( $this, 'single_site_deactivate' ) );
			}

			add_action( 'admin_notices', array( $this, 'discussion_notice' ) );
			add_filter( 'plugin_row_meta', array( $this, 'set_plugin_meta' ), 10, 2 );

			// if only certain types are disabled, remember the original post status
			if( !( $this->persistent_mode_allowed() && $this->options['permanent'] ) && !$this->options['remove_everywhere'] ) {
				add_action( 'edit_form_advanced', array( $this, 'edit_form_inputs' ) );
				add_action( 'edit_page_form', array( $this, 'edit_form_inputs' ) );
			}

			if( $this->options['remove_everywhere'] ) {
				add_action( 'admin_menu', array( $this, 'filter_admin_menu' ), 9999 );	// do this as late as possible
				add_action( 'admin_print_styles-index.php', array( $this, 'admin_css' ) );
				add_action( 'admin_print_styles-profile.php', array( $this, 'admin_css' ) );
				add_action( 'wp_dashboard_setup', array( $this, 'filter_dashboard' ) );
				add_filter( 'pre_option_default_pingback_flag', '__return_zero' );
			}
		}
		// Filters for front end only
		else {
			add_action( 'template_redirect', array( $this, 'check_comment_template' ) );

			if( $this->options['remove_everywhere'] ) {
				add_filter( 'feed_links_show_comments_feed', '__return_false' );
			}
		}
	}

	/*
	 * Replace the theme's comment template with a blank one.
	 * To prevent this, define DISABLE_COMMENTS_REMOVE_COMMENTS_TEMPLATE
	 * and set it to True
	 */
	public function check_comment_template() {
		if( is_singular() && ( $this->options['remove_everywhere'] || $this->is_post_type_disabled( get_post_type() ) ) ) {
			if( !defined( 'DISABLE_COMMENTS_REMOVE_COMMENTS_TEMPLATE' ) || DISABLE_COMMENTS_REMOVE_COMMENTS_TEMPLATE == true ) {
				// Kill the comments template.
				add_filter( 'comments_template', array( $this, 'dummy_comments_template' ), 20 );
			}
			// Remove comment-reply script for themes that include it indiscriminately
			wp_deregister_script( 'comment-reply' );
			// feed_links_extra inserts a comments RSS link
			remove_action( 'wp_head', 'feed_links_extra', 3 );
		}
	}

	public function dummy_comments_template() {
		return dirname( __FILE__ ) . '/includes/comments-template.php';
	}


	/*
	 * Remove the X-Pingback HTTP header
	 */
	public function filter_wp_headers( $headers ) {
		unset( $headers['X-Pingback'] );
		return $headers;
	}

	/*
	 * Issue a 403 for all comment feed requests.
	 */
	public function filter_query() {
		if( is_comment_feed() ) {
			wp_die( __( 'Comments are closed.' ), '', array( 'response' => 403 ) );
		}
	}

	/*
	 * Remove comment links from the admin bar.
	 */
	public function filter_admin_bar() {
		if( is_admin_bar_showing() ) {
			// Remove comments links from admin bar
			remove_action( 'admin_bar_menu', 'wp_admin_bar_comments_menu', 60 );
			if( is_multisite() ) {
				add_action( 'admin_bar_menu', array( $this, 'remove_network_comment_links' ), 500 );
			}
		}
	}

	/*
	 * Remove comment links from the admin bar in a multisite network.
	 */
	public function remove_network_comment_links( $wp_admin_bar ) {
		if( $this->networkactive && is_user_logged_in() ) {
			foreach( (array) $wp_admin_bar->user->blogs as $blog ) {
				$wp_admin_bar->remove_menu( 'blog-' . $blog->userblog_id . '-c' );
			}
		}
		else {
			// We have no way to know whether the plugin is active on other sites, so only remove this one
			$wp_admin_bar->remove_menu( 'blog-' . get_current_blog_id() . '-c' );
		}
	}

	public function edit_form_inputs() {
		global $post;
		// Without a dicussion meta box, comment_status will be set to closed on new/updated posts
		if( in_array( $post->post_type, $this->modified_types ) ) {
			echo '<input type="hidden" name="comment_status" value="' . $post->comment_status . '" /><input type="hidden" name="ping_status" value="' . $post->ping_status . '" />';
		}
	}

	public function discussion_notice() {
		$disabled_post_types = $this->get_disabled_post_types();
		if( get_current_screen()->id == 'options-discussion' && !empty( $disabled_post_types ) ) {
			$names = array();
			foreach( $disabled_post_types as $type )
				$names[$type] = get_post_type_object( $type )->labels->name;

			echo '<div class="notice notice-warning"><p>' . sprintf( __( 'Note: The <em>Disable Comments</em> plugin is currently active, and comments are completely disabled on: %s. Many of the settings below will not be applicable for those post types.', 'disable-comments' ), implode( __( ', ' ), $names ) ) . '</p></div>';
		}
	}

	/**
	 * Return context-aware settings page URL
	 */
	private function settings_page_url() {
		$base =  $this->networkactive ? network_admin_url( 'settings.php' ) : admin_url( 'options-general.php' );
		return add_query_arg( 'page', 'disable_comments_settings', $base );
	}

	/**
	 * Return context-aware tools page URL
	 */
	private function tools_page_url() {
		$base =  $this->networkactive ? network_admin_url( 'settings.php' ) : admin_url( 'tools.php' );
		return add_query_arg( 'page', 'disable_comments_tools', $base );
	}

	public function setup_notice(){
		if( strpos( get_current_screen()->id, 'settings_page_disable_comments_settings' ) === 0 )
			return;
		$hascaps = $this->networkactive ? is_network_admin() && current_user_can( 'manage_network_plugins' ) : current_user_can( 'manage_options' );
		if( $hascaps ) {
			echo '<div class="updated fade"><p>' . sprintf( __( 'The <em>Disable Comments</em> plugin is active, but isn\'t configured to do anything yet. Visit the <a href="%s">configuration page</a> to choose which post types to disable comments on.', 'disable-comments'), esc_attr( $this->settings_page_url() ) ) . '</p></div>';
		}
	}

	public function filter_admin_menu(){
		global $pagenow;

		if ( $pagenow == 'comment.php' || $pagenow == 'edit-comments.php' )
			wp_die( __( 'Comments are closed.' ), '', array( 'response' => 403 ) );

		remove_menu_page( 'edit-comments.php' );

		if ( ! $this->discussion_settings_allowed() ) {
			if ( $pagenow == 'options-discussion.php' )
				wp_die( __( 'Comments are closed.' ), '', array( 'response' => 403 ) );

			remove_submenu_page( 'options-general.php', 'options-discussion.php' );
		}
	}

	public function filter_dashboard(){
		remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'normal' );
	}

	public function admin_css(){
		echo '<style>
			#dashboard_right_now .comment-count,
			#dashboard_right_now .comment-mod-count,
			#latest-comments,
			#welcome-panel .welcome-comments,
			.user-comment-shortcuts-wrap {
				display: none !important;
			}
		</style>';
	}

	public function filter_existing_comments($comments, $post_id) {
		$post = get_post( $post_id );
		return ( $this->options['remove_everywhere'] || $this->is_post_type_disabled( $post->post_type ) ) ? array() : $comments;
	}

	public function filter_comment_status( $open, $post_id ) {
		$post = get_post( $post_id );
		return ( $this->options['remove_everywhere'] || $this->is_post_type_disabled( $post->post_type ) ) ? false : $open;
	}

	public function disable_rc_widget() {
		unregister_widget( 'WP_Widget_Recent_Comments' );
		// The widget has added a style action when it was constructed - which will
		// still fire even if we now unregister the widget... so filter that out
		add_filter( 'show_recent_comments_widget_style', '__return_false');
	}

	public function set_plugin_meta( $links, $file ) {
		static $plugin;
		$plugin = plugin_basename( __FILE__ );
		if ( $file == $plugin ) {
			$links[] = '<a href="https://github.com/solarissmoke/disable-comments">GitHub</a>';
		}
		return $links;
	}

	/**
	 * Add links to Settings page
	*/
	public function plugin_actions_links( $links, $file ) {
		static $plugin;
		$plugin = plugin_basename( __FILE__ );
		if( $file == $plugin && current_user_can('manage_options') ) {
			array_unshift(
				$links,
				sprintf( '<a href="%s">%s</a>', esc_attr( $this->settings_page_url() ), __( 'Settings' ) ),
				sprintf( '<a href="%s">%s</a>', esc_attr( $this->tools_page_url() ), __( 'Tools' ) )
			);
		}

		return $links;
	}

	public function settings_menu() {
		$title = _x( 'Disable Comments', 'settings menu title', 'disable-comments' );
		if( $this->networkactive )
			add_submenu_page( 'settings.php', $title, $title, 'manage_network_plugins', 'disable_comments_settings', array( $this, 'settings_page' ) );
		else
			add_submenu_page( 'options-general.php', $title, $title, 'manage_options', 'disable_comments_settings', array( $this, 'settings_page' ) );
	}

	public function settings_page() {
		include dirname( __FILE__ ) . '/includes/settings-page.php';
	}

	public function tools_menu() {
		$title = __( 'Delete Comments', 'disable-comments' );
		if( $this->networkactive )
			add_submenu_page( 'settings.php', $title, $title, 'manage_network_plugins', 'disable_comments_tools', array( $this, 'tools_page' ) );
		else
			add_submenu_page( 'tools.php', $title, $title, 'manage_options', 'disable_comments_tools', array( $this, 'tools_page' ) );
	}

	public function tools_page() {
		include dirname( __FILE__ ) . '/includes/tools-page.php';
	}

	private function enter_permanent_mode() {
		$types = $this->get_disabled_post_types();
		if( empty( $types ) )
			return;

		global $wpdb;

		if( $this->networkactive ) {
			// NOTE: this can be slow on large networks!
			$blogs = $wpdb->get_col( $wpdb->prepare( "SELECT blog_id FROM $wpdb->blogs WHERE site_id = %d AND public = '1' AND archived = '0' AND deleted = '0'", $wpdb->siteid ) );

			foreach ( $blogs as $id ) {
				switch_to_blog( $id );
				$this->close_comments_in_db( $types );
				restore_current_blog();
			}
		}
		else {
			$this->close_comments_in_db( $types );
		}
	}

	private function close_comments_in_db( $types ){
		global $wpdb;
		$bits = implode( ', ', array_pad( array(), count( $types ), '%s' ) );
		$wpdb->query( $wpdb->prepare( "UPDATE `$wpdb->posts` SET `comment_status` = 'closed', ping_status = 'closed' WHERE `post_type` IN ( $bits )", $types ) );
	}

	private function persistent_mode_allowed() {
		if( defined( 'DISABLE_COMMENTS_ALLOW_PERSISTENT_MODE' ) && DISABLE_COMMENTS_ALLOW_PERSISTENT_MODE == false ) {
			return false;
		}
	}

	private function discussion_settings_allowed() {
		if( defined( 'DISABLE_COMMENTS_ALLOW_DISCUSSION_SETTINGS' ) && DISABLE_COMMENTS_ALLOW_DISCUSSION_SETTINGS == true ) {
			return true;
		}
	}

	public function single_site_deactivate() {
		// for single sites, delete the options upon deactivation, not uninstall
		delete_option( 'disable_comments_options' );
	}
}

Disable_Comments::get_instance();
