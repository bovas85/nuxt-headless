<?php
/*
Plugin Name: Flamingo
Description: A trustworthy message storage plugin for Contact Form 7.
Author: Takayuki Miyoshi
Text Domain: flamingo
Domain Path: /languages/
Version: 1.9
*/

define( 'FLAMINGO_VERSION', '1.9' );

define( 'FLAMINGO_PLUGIN', __FILE__ );

define( 'FLAMINGO_PLUGIN_BASENAME',
	plugin_basename( FLAMINGO_PLUGIN ) );

define( 'FLAMINGO_PLUGIN_NAME',
	trim( dirname( FLAMINGO_PLUGIN_BASENAME ), '/' ) );

define( 'FLAMINGO_PLUGIN_DIR',
	untrailingslashit( dirname( FLAMINGO_PLUGIN ) ) );

// Deprecated, not used in the plugin core. Use flamingo_plugin_url() instead.
define( 'FLAMINGO_PLUGIN_URL',
	untrailingslashit( plugins_url( '', FLAMINGO_PLUGIN ) ) );

require_once FLAMINGO_PLUGIN_DIR . '/includes/functions.php';
require_once FLAMINGO_PLUGIN_DIR . '/includes/formatting.php';
require_once FLAMINGO_PLUGIN_DIR . '/includes/csv.php';
require_once FLAMINGO_PLUGIN_DIR . '/includes/capabilities.php';
require_once FLAMINGO_PLUGIN_DIR . '/includes/class-contact.php';
require_once FLAMINGO_PLUGIN_DIR . '/includes/class-inbound-message.php';
require_once FLAMINGO_PLUGIN_DIR . '/includes/class-outbound-message.php';
require_once FLAMINGO_PLUGIN_DIR . '/includes/user.php';
require_once FLAMINGO_PLUGIN_DIR . '/includes/comment.php';
require_once FLAMINGO_PLUGIN_DIR . '/includes/akismet.php';

if ( is_admin() ) {
	require_once FLAMINGO_PLUGIN_DIR . '/admin/admin.php';
}

/* Init */

add_action( 'init', 'flamingo_init' );

function flamingo_init() {

	/* L10N */
	load_plugin_textdomain( 'flamingo', false, 'flamingo/languages' );

	/* Custom Post Types */
	Flamingo_Contact::register_post_type();
	Flamingo_Inbound_Message::register_post_type();
	Flamingo_Outbound_Message::register_post_type();

	do_action( 'flamingo_init' );
}
