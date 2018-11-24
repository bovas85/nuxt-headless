<?php
/**
 * Classic Editor
 *
 * Plugin Name: Classic Editor
 * Plugin URI:  https://wordpress.org
 * Description: Enables the WordPress classic editor and the old-style Edit Post screen layout (TinyMCE, meta boxes, etc.). Supports the older plugins that extend this screen.
 * Version:     0.5
 * Author:      WordPress Contributors
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: classic-editor
 * Domain Path: /languages
 */
 /*
 * This program is free software; you can redistribute it and/or modify it under the terms of the GNU
 * General Public License version 2, as published by the Free Software Foundation.  You may NOT assume
 * that you can use any other version of the GPL.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without
 * even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Invalid request.' );
}

add_action( 'plugins_loaded', 'classic_editor_init_actions' );
function classic_editor_init_actions() {
	$gutenberg = false;
	$block_editor = false;

	// Always remove the "Try Gutenberg" dashboard widget. See https://core.trac.wordpress.org/ticket/44635.
	// TODO: not needed in 5.0-beta
	remove_action( 'try_gutenberg_panel', 'wp_try_gutenberg_panel' );

	// Always show the settings and the link to them in the plugins list table.
	add_filter( 'plugin_action_links', 'classic_editor_add_settings_link', 10, 2 );
	add_action( 'admin_init', 'classic_editor_admin_init' );

	if ( has_filter( 'replace_editor', 'gutenberg_init' ) ) {
		// Gutenberg is installed and activated.
		$gutenberg = true;
	}

	if ( version_compare( $GLOBALS['wp_version'], '5.0-beta', '>' ) ) {
		// Block editor.
		$block_editor = true;
	}

	if ( ! $gutenberg && ! $block_editor ) {
		return; // Nothing to do :)
	}

	$replace = ( get_option( 'classic-editor-replace' ) !== 'no-replace' );

	if ( $block_editor && ( $replace || isset( $_GET['classic-editor'] ) ) ) {
		// TODO: consider disabling other block editor functionality.
		add_filter( 'use_block_editor_for_post_type', '__return_false', 100 );
	}

	if ( $gutenberg && ( $replace || isset( $_GET['classic-editor'] ) ) ) {
		// gutenberg.php
		remove_action( 'admin_menu', 'gutenberg_menu' );
		remove_action( 'admin_notices', 'gutenberg_build_files_notice' );
		remove_action( 'admin_notices', 'gutenberg_wordpress_version_notice' );
		remove_action( 'admin_init', 'gutenberg_redirect_demo' );

		remove_filter( 'replace_editor', 'gutenberg_init' );

		// lib/client-assets.php
		remove_action( 'wp_enqueue_scripts', 'gutenberg_register_scripts_and_styles', 5 );
		remove_action( 'admin_enqueue_scripts', 'gutenberg_register_scripts_and_styles', 5 );
		remove_action( 'wp_enqueue_scripts', 'gutenberg_common_scripts_and_styles' );
		remove_action( 'admin_enqueue_scripts', 'gutenberg_common_scripts_and_styles' );

		// lib/compat.php
		remove_filter( 'wp_refresh_nonces', 'gutenberg_add_rest_nonce_to_heartbeat_response_headers' );

		// lib/rest-api.php
		remove_action( 'rest_api_init', 'gutenberg_register_rest_routes' );
		remove_action( 'rest_api_init', 'gutenberg_add_taxonomy_visibility_field' );

		remove_filter( 'rest_request_after_callbacks', 'gutenberg_filter_oembed_result' );
		remove_filter( 'registered_post_type', 'gutenberg_register_post_prepare_functions' );
		remove_filter( 'register_post_type_args', 'gutenberg_filter_post_type_labels' );

		// lib/meta-box-partial-page.php
		remove_action( 'do_meta_boxes', 'gutenberg_meta_box_save', 1000 );
		remove_action( 'submitpost_box', 'gutenberg_intercept_meta_box_render' );
		remove_action( 'submitpage_box', 'gutenberg_intercept_meta_box_render' );
		remove_action( 'edit_page_form', 'gutenberg_intercept_meta_box_render' );
		remove_action( 'edit_form_advanced', 'gutenberg_intercept_meta_box_render' );

		remove_filter( 'redirect_post_location', 'gutenberg_meta_box_save_redirect' );
		remove_filter( 'filter_gutenberg_meta_boxes', 'gutenberg_filter_meta_boxes' );

		// add_filter( 'replace_editor', 'classic_editor_replace' );
	}

	if ( $gutenberg && $replace ) {
		// gutenberg.php
		remove_action( 'admin_init', 'gutenberg_add_edit_link_filters' );
		remove_action( 'admin_print_scripts-edit.php', 'gutenberg_replace_default_add_new_button' );

		remove_filter( 'body_class', 'gutenberg_add_responsive_body_class' );
		remove_filter( 'admin_url', 'gutenberg_modify_add_new_button_url' );

		// Keep
		// remove_filter( 'wp_kses_allowed_html', 'gutenberg_kses_allowedtags', 10, 2 ); // not needed in 5.0
		// remove_filter( 'bulk_actions-edit-wp_block', 'gutenberg_block_bulk_actions' );

		// lib/compat.php
		remove_action( 'admin_enqueue_scripts', 'gutenberg_check_if_classic_needs_warning_about_blocks' );

		// lib/register.php
		remove_action( 'edit_form_top', 'gutenberg_remember_classic_editor_when_saving_posts' );

		remove_filter( 'redirect_post_location', 'gutenberg_redirect_to_classic_editor_when_saving_posts' );
		remove_filter( 'get_edit_post_link', 'gutenberg_revisions_link_to_editor' );
		remove_filter( 'wp_prepare_revision_for_js', 'gutenberg_revisions_restore' );
		remove_filter( 'display_post_states', 'gutenberg_add_gutenberg_post_state' );

		// lib/plugin-compat.php
		remove_filter( 'rest_pre_insert_post', 'gutenberg_remove_wpcom_markdown_support' );

		// Keep

		// lib/blocks.php
		// remove_filter( 'the_content', 'do_blocks', 9 );

		// Continue to disable wpautop inside TinyMCE for posts that were started in Gutenberg.
		// remove_filter( 'wp_editor_settings', 'gutenberg_disable_editor_settings_wpautop' );

		// Keep the tweaks to the PHP wpautop.
		// add_filter( 'the_content', 'wpautop' );
		// remove_filter( 'the_content', 'gutenberg_wpautop', 8 );

		// remove_action( 'init', 'gutenberg_register_post_types' );
	}

	if ( ! $replace ) {
		// Menus
		add_action( 'admin_menu', 'classic_editor_add_submenus' );
		add_action( 'admin_bar_menu', 'classic_editor_admin_bar_menu', 120 );

		// Row actions (edit.php)
		add_filter( 'page_row_actions', 'classic_editor_add_edit_links', 15, 2 );
		add_filter( 'post_row_actions', 'classic_editor_add_edit_links', 15, 2 );

		add_filter( 'get_edit_post_link', 'classic_editor_get_edit_post_link' );

		if ( ! $gutenberg ) {
			// Redirect on saving in the classic editor.
			add_filter( 'redirect_post_location', 'classic_editor_redirect_location' );
			add_action( 'edit_form_top', 'classic_editor_remember_when_saving_posts' );
		}
	}
}

function classic_editor_admin_init() {
	// Add an option to Settings -> Writing
	register_setting( 'writing', 'classic-editor-replace', array(
		'sanitize_callback' => 'classic_editor_validate_options',
	) );

	add_option_whitelist( array(
		'writing' => array( 'classic-editor-replace' ),
	) );

	add_settings_field( 'classic-editor', __( 'Classic editor settings', 'classic-editor' ), 'classic_editor_settings', 'writing' );
}

/**
 * Output HTML for the settings.
 */
function classic_editor_settings() {
	$replace = get_option( 'classic-editor-replace' ) !== 'no-replace';

	?>
	<p id="classic-editor-options" style="margin: 0;">
		<input type="radio" name="classic-editor-replace" id="classic-editor-replace" value="replace"<?php if ( $replace ) echo ' checked'; ?> />
		<label for="classic-editor-replace">
		<?php _e( 'Replace the Block editor with the Classic editor.', 'classic-editor' ); ?>
		</label>
		<br>

		<input type="radio" name="classic-editor-replace" id="classic-editor-no-replace" value="no-replace"<?php if ( ! $replace ) echo ' checked'; ?> />
		<label for="classic-editor-no-replace">
		<?php _e( 'Use the Block editor by default and include optional links back to the Classic editor.', 'classic-editor' ); ?>
		</label>
	</p>
	<script>
	jQuery( 'document' ).ready( function( $ ) {
		if ( window.location.hash === '#classic-editor-options' ) {
			$( '#classic-editor-options' ).closest( 'td' ).addClass( 'highlight' );
		}
	} );
	</script>
	<?php
}

/**
 * Validate
 */
function classic_editor_validate_options( $value ) {
	if ( $value === 'no-replace' ) {
		return 'no-replace';
	}

	return 'replace';
}

/**
 * Add a hidden field in edit-form-advanced.php
 * to help redirect back to the classic editor on saving.
 */
function classic_editor_remember_when_saving_posts() {
	?>
	<input type="hidden" name="classic-editor" value="" />
	<?php
}

/**
 * Keep the `classic-editor` query arg through redirects when saving posts.
 */
function classic_editor_redirect_location( $location ) {
	if (
		isset( $_REQUEST['classic-editor'] ) ||
		( isset( $_POST['_wp_http_referer'] ) && strpos( $_POST['_wp_http_referer'], '&classic-editor' ) !== false )
	) {
		$location = add_query_arg( 'classic-editor', '', $location );
	}

	return $location;
}

/**
 * Keep the `classic-editor` query arg when looking at revisions.
 */
function classic_editor_get_edit_post_link( $url ) {
	if ( isset( $_REQUEST['classic-editor'] ) ) {
		$url = add_query_arg( 'classic-editor', '', $url );
	}

	return $url;
}

/**
 * Add an `Add New (Classic)` submenu for Posts, Pages, etc.
 */
function classic_editor_add_submenus() {
	foreach ( get_post_types( array( 'show_ui' => true ) ) as $type ) {
		$type_obj = get_post_type_object( $type );

		if ( ! $type_obj->show_in_menu || ! post_type_supports( $type, 'editor' ) ) {
			continue;
		}

		if ( $type_obj->show_in_menu === true ) {
			if ( 'post' === $type ) {
				$parent_slug = 'edit.php';
			} elseif ( 'page' === $type ) {
				$parent_slug = 'edit.php?post_type=page';
			} else {
				// Not for a submenu.
				continue;
			}
		} else {
			$parent_slug = $type_obj->show_in_menu;
		}

		$item_name = $type_obj->labels->add_new . ' ' . __( '(Classic)', 'classic-editor' );

		add_submenu_page( $parent_slug, $type_obj->labels->add_new, $item_name, $type_obj->cap->edit_posts, "post-new.php?post_type={$type}&classic-editor" );
	}
}

/**
 * Add an `Edit (Classic)` link in the toolbar.
 */
function classic_editor_admin_bar_menu( $wp_admin_bar ) {
	global $post_id, $wp_the_query;
	$edit_url = null;

	if ( get_option( 'classic-editor-replace' ) !== 'no-replace' ) {
		return;
	}

	if ( is_admin() ) {
		$post = get_post( $post_id );
	} else {
		$post = $wp_the_query->get_queried_object();
	}

	if ( empty( $post ) || empty( $post->ID ) ) {
		return;
	}

	// Capability check is in get_edit_post_link().
	$edit_url = get_edit_post_link( $post->ID, 'url' );

	if ( $edit_url &&
		( ( is_admin() && 'post' === get_current_screen()->base ) || ( ! is_admin() && ! empty( $post->post_type ) ) ) &&
		post_type_supports( $post->post_type, 'editor' ) ) {

		if ( isset( $_GET['classic-editor'] ) ) {
			$wp_admin_bar->add_menu( array(
				'id' => 'classic-editor',
				'title' => __( 'Edit (Block editor)', 'classic-editor' ),
				'href' => remove_query_arg( 'classic-editor', $edit_url ),
			) );
		} else {
			$wp_admin_bar->add_menu( array(
				'id' => 'classic-editor',
				'title' => __( 'Edit (Classic)', 'classic-editor' ),
				'href' => add_query_arg( 'classic-editor', '', $edit_url ),
			) );
		}
	}
}

/**
 * Add a link to the settings on the Plugins screen.
 */
function classic_editor_add_settings_link( $links, $file ) {
	if ( $file === 'classic-editor/classic-editor.php' && current_user_can( 'manage_options' ) ) {
		$settings_link = sprintf( '<a href="%s">%s</a>', admin_url( 'options-writing.php#classic-editor-options' ), __( 'Settings', 'classic-editor' ) );
		array_unshift( $links, $settings_link );
	}

	return $links;
}

/**
 * Adds links in the post/page screens to edit any post or page in
 * the Classic editor.
 *
 * @param  array   $actions Post actions.
 * @param  WP_Post $post    Edited post.
 *
 * @return array   Updated post actions.
 */
function classic_editor_add_edit_links( $actions, $post ) {
	// This is in Gutenberg now.
	if ( array_key_exists( 'classic', $actions ) ) {
		return $actions;
	}

	if ( 'trash' === $post->post_status || ! post_type_supports( $post->post_type, 'editor' ) ) {
		return $actions;
	}

	$edit_url = get_edit_post_link( $post->ID, 'raw' );

	if ( ! $edit_url ) {
		return $actions;
	}

	$edit_url = add_query_arg( 'classic-editor', '', $edit_url );

	// Build the classic edit action. See also: WP_Posts_List_Table::handle_row_actions().
	$title       = _draft_or_post_title( $post->ID );
	$edit_action = array(
		'classic' => sprintf(
			'<a href="%s" aria-label="%s">%s</a>',
			esc_url( $edit_url ),
			esc_attr( sprintf(
				/* translators: %s: post title */
				__( 'Edit &#8220;%s&#8221; in the classic editor', 'classic-editor' ),
				$title
			) ),
			__( 'Edit (Classic)', 'classic-editor' )
		),
	);

	// Insert the Classic Edit action after the Edit action.
	$edit_offset = array_search( 'edit', array_keys( $actions ), true );
	array_splice( $actions, $edit_offset + 1, 0, $edit_action );

	return $actions;
}

/**
 * Set default on activation.
 */
register_activation_hook( __FILE__, 'classic_editor_activate' );
function classic_editor_activate() {
	if ( ! get_option( 'classic-editor-replace' ) ) {
		update_option( 'classic-editor-replace', 'replace' );
	}
}

function classic_editor_replace( $return ) {
	// Bail if the editor has been replaced already.
	if ( true === $return ) {
		return $return;
	}

	$suffix = SCRIPT_DEBUG ? '' : '.min';
	$js_url = plugin_dir_url( __FILE__ ) . 'js/';
	$css_url = plugin_dir_url( __FILE__ ) . 'css/';

	// Enqueued conditionally from legacy-edit-form-advanced.php
	wp_register_script( 'editor-expand', $js_url . "editor-expand$suffix.js", array( 'jquery', 'underscore' ), false, 1 );

	// The dependency 'tags-suggest' is also needed for 'inline-edit-post', not included.
	wp_register_script( 'tags-box', $js_url . "tags-box$suffix.js", array( 'jquery', 'tags-suggest' ), false, 1 );
	wp_register_script( 'word-count', $js_url . "word-count$suffix.js", array(), false, 1 );

	// The dependency 'heartbeat' is also loaded on most wp-admin screens, not included.
	wp_register_script( 'autosave', $js_url . "autosave$suffix.js", array( 'heartbeat' ), false, 1 );
	wp_localize_script( 'autosave', 'autosaveL10n', array(
		'autosaveInterval' => AUTOSAVE_INTERVAL,
		'blog_id' => get_current_blog_id(),
	) );

	wp_enqueue_script( 'post', $js_url . "post$suffix.js", array(
	//	'suggest', // deprecated
		'tags-box', // included
		'word-count', // included
		'autosave', // included
		'wp-lists', // not included, also dependency for 'admin-comments', 'link', and 'nav-menu'.
		'postbox', // not included, also dependency for 'link', 'comment', 'dashboard', and 'nav-menu'.
		'underscore', // not included, library
		'wp-a11y', // not included, library
	), false, 1 );

	wp_localize_script( 'post', 'postL10n', array(
		'ok' => __( 'OK', 'classic-editor' ),
		'cancel' => __( 'Cancel', 'classic-editor' ),
		'publishOn' => __( 'Publish on:', 'classic-editor' ),
		'publishOnFuture' =>  __( 'Schedule for:', 'classic-editor' ),
		'publishOnPast' => __( 'Published on:', 'classic-editor' ),
		/* translators: 1: month, 2: day, 3: year, 4: hour, 5: minute */
		'dateFormat' => __( '%1$s %2$s, %3$s @ %4$s:%5$s', 'classic-editor' ),
		'showcomm' => __( 'Show more comments', 'classic-editor' ),
		'endcomm' => __( 'No more comments found.', 'classic-editor' ),
		'publish' => __( 'Publish', 'classic-editor' ),
		'schedule' => __( 'Schedule', 'classic-editor' ),
		'update' => __( 'Update', 'classic-editor' ),
		'savePending' => __( 'Save as Pending', 'classic-editor' ),
		'saveDraft' => __( 'Save Draft', 'classic-editor' ),
		'private' => __( 'Private', 'classic-editor' ),
		'public' => __( 'Public', 'classic-editor' ),
		'publicSticky' => __( 'Public, Sticky', 'classic-editor' ),
		'password' => __( 'Password Protected', 'classic-editor' ),
		'privatelyPublished' => __('Privately Published', 'classic-editor' ),
		'published' => __( 'Published', 'classic-editor' ),
		'saveAlert' => __( 'The changes you made will be lost if you navigate away from this page.', 'classic-editor' ),
		'savingText' => __( 'Saving Draft&#8230;', 'classic-editor' ),
		'permalinkSaved' => __( 'Permalink saved', 'classic-editor' ),
	) );

	wp_enqueue_style( 'classic-edit', plugin_dir_url( __FILE__ ) . "css/edit$suffix.css" );

	// Other scripts and stylesheets:
	// wp_enqueue_script( 'admin-comments' ) is a dependency for 'dashboard', also used in edit-comments.php.
	// wp_enqueue_script( 'image-edit' ) and wp_enqueue_style( 'imgareaselect' ) are also used in media.php and media-upload.php.

	include_once( plugin_dir_path( __FILE__ ) . 'classic-edit-form-advanced.php' );

	return true;
}
