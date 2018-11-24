<?php
/*
Plugin Name: HookPress
Plugin URI: http://mitcho.com/code/hookpress/
Description: HookPress turns all of your WordPress-internal hooks into webhooks. Possible uses include generating push notifications or using non-PHP web technology to extend WordPress. Read more about webhooks at <a href='http://webhooks.org/'>the webhooks site</a>.
Version: 1.14
Author: mitcho (Michael Yoshitaka Erlewine)
Author URI: http://mitcho.com/
Donate link: http://tinyurl.com/donatetomitcho
*/

define('HOOKPRESS_PRIORITY',12838790321);
$hookpress_version = "1.14";
require('includes.php');

function hookpress_init() {
	global $hookpress_version;

	if ( !get_option('hookpress_version') ||
		 version_compare($hookpress_version,get_option('hookpress_version')) > 0 )
		update_option('hookpress_version',$hookpress_version);

	add_action('admin_menu', 'hookpress_config_page');
}
add_action('init', 'hookpress_init');
hookpress_register_hooks();

// register ajax service
add_action('wp_ajax_hookpress_get_fields', 'hookpress_ajax_get_fields');
add_action('wp_ajax_hookpress_add_fields', 'hookpress_ajax_add_fields');
add_action('wp_ajax_hookpress_delete_hook', 'hookpress_ajax_delete_hook');
add_action('wp_ajax_hookpress_edit_hook', 'hookpress_ajax_edit_hook');
add_action('wp_ajax_hookpress_get_hooks', 'hookpress_ajax_get_hooks');
add_action('wp_ajax_hookpress_set_enabled', 'hookpress_ajax_set_enabled');

function hookpress_config_page() {
	$hook = add_submenu_page('options-general.php', __('Webhooks','hookpress'), __('Webhooks','hookpress'), 'manage_options', 'webhooks', 'hookpress_options');
	add_action("load-$hook",'hookpress_load_thickbox');
}

function hookpress_load_thickbox() {
	wp_enqueue_script( 'thickbox' );
	wp_enqueue_style( 'thickbox' );
}

// Infrastructure:

function hookpress_options() {
	global $wpdb, $hookpress_actions, $hookpress_version;
	require(str_replace('hookpress.php','options.php',__FILE__));
}

function hookpress_get_hooks() {
	return get_option('hookpress_webhooks', array());
}

function hookpress_delete_hook($hook_id) {
	$webhooks = hookpress_get_hooks();
	unset( $webhooks[$hook_id] );
	hookpress_save_hooks( $webhooks );
	return TRUE;
}

function hookpress_add_hook( $hook ) {
	$webhooks = hookpress_get_hooks();
	$webhooks[] = $hook;
	hookpress_save_hooks($webhooks);
	return end(array_keys($webhooks));
}

function hookpress_update_hook( $hook_id, $hook ) {
	$webhooks = hookpress_get_hooks();
	$webhooks[$hook_id] = $hook;
	hookpress_save_hooks($webhooks);
	return $hook_id;
}
function hookpress_save_hooks($webhooks) {
	update_option('hookpress_webhooks', $webhooks);
}
