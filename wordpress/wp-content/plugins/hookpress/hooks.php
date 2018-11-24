<?php

// ACTIONS:

global $hookpress_actions, $hookpress_filters;

$hookpress_actions = array(
	'add_attachment'=>array('ATTACHMENT'),
	'add_category'=>array('CATEGORY'),
	'clean_post_cache'=>array('POST'),
	'create_category'=>array('CATEGORY'),
	'delete_attachment'=>array('ATTACHMENT'),
	'delete_category'=>array('CATEGORY'),
	'delete_post'=>array('POST'),
	'deleted_post'=>array('POST'),
	'edit_attachment'=>array('ATTACHMENT'),
	'edit_category'=>array('CATEGORY'),
	'edit_post'=>array('POST'),
	'pre_post_update'=>array('POST'),
	'private_to_publish'=>array('POST'), // TODO: check if this is really the post ID
	'publish_page'=>array('POST'),
	'publish_phone'=>array('POST'),
	'publish_post'=>array('POST'),
	'save_post'=>array('POST', 'PARENT_POST'),
	// TODO: make sure the original post stuff is working
	'wp_insert_post'=>array('POST'),
	'xmlrpc_publish_post'=>array('POST'),

	'comment_closed'=>array('POST'),
	'comment_id_not_found'=>array('POST'),
	'comment_flood_trigger'=>array('time_lastcomment','time_newcomment'),
	'comment_on_draft'=>array('POST'),
	'comment_post'=>array('COMMENT','approval'),
	'edit_comment'=>array('COMMENT'),
	'delete_comment'=>array('COMMENT'),
	'pingback_post'=>array('COMMENT'),
	'pre_ping'=>array('COMMENT'),
	'traceback_post'=>array('COMMENT'),
	'wp_blacklist_check'=>array('comment_author','comment_author_email','comment_author_url','comment_content','comment_author_IP','comment_agent'),
	'wp_set_comment_status'=>array('COMMENT','status'),
	
	'add_link'=>array('LINK'),
	'delete_link'=>array('LINK'),
	'edit_link'=>array('LINK'),

//	'atom_entry'=>array(),
//	'atom_head'=>array(),
//	'atom_ns'=>array(),
	'commentrss2_item'=>array('COMMENT','POST'),
//	'rdf_header'=>array(),
//	'rdf_item'=>array(),
//	'rdf_ns'=>array(),
//	'rss_head'=>array(),
//	'rss_item'=>array(),
//	'rss2_head'=>array(),
//	'rss2_item'=>array(),
//	'rss2_ns'=>array(),

	'comment_form'=>array('POST'),
//	'do_robots'=>array(),
//	'do_robotstxt'=>array(),
//	'do_robotstxt'=>array(),
	'get_footer'=>array('footer_name'),
	'get_header'=>array('header_name'),
	'switch_theme'=>array('theme_name'),
//	'template_redirect'=>array(),
//	'wp_footer'=>array(),
//	'wp_head'=>array(),
//	'wp_meta'=>array(),
//	'wp_print_scripts'=>array(),
//	'activity_box_end'=>array(),
//	'add_category_form_pre'=>array(),
//	'admin_head'=>array(),
//	'admin_init'=>array(),
//	'admin_footer'=>array(),
//	'admin_print_scripts'=>array(),
//	'admin_print_styles'=>array(),
//	'admin_print_scripts-(page_hook)'=>array(),	
	'check_passwords'=>array('user_login','pass1','pass2'),
//	'dbx_post_advanced'=>array(),
//	'dbx_post_sidebar'=>array(),
////	'dbx_post_advanced'=>array(), // these aren't being used???
////	'dbx_post_sidebar'=>array(),
	'delete_user'=>array('USER'),
	'edit_category_form'=>array('CATEGORY'),
	'edit_category_form_pre'=>array('CATEGORY'),
	'edit_tag_form'=>array('TAG_OBJ'),
	'edit_tag_form_pre'=>array('TAG_OBJ'),
//	'edit_form_advanced'=>array(),
//	'edit_page_form'=>array(),
	'edit_user_profile'=>array('USER_OBJ'),
//	'login_form'=>array(),
//	'login_head'=>array(),
//	'lost_password'=>array(),
//	'lostpassword_form'=>array(),
//	'lostpassword_post'=>array(),
	'manage_link_custom_column'=>array('column_name','LINK'),
	'manage_posts_custom_column'=>array('column_name','POST'),
	'manage_pages_custom_column'=>array('column_name','POST'),
	'password_reset'=>array('USER_OBJ','new_pass'),
	'personal_options_update'=>array('USER'),
//	'plugins_loaded'=>array(),
	'profile_personal_options'=>array('USER_OBJ'),
	'profile_update'=>array('USER','OLD_USER_OBJ'),
//	'register_form'=>array(),
//// not sure how to meaningfully parse/pass the WP_Error obj.
//	'register_post'=>array('user_login','user_pass','ERRORS_OBJ'),
//	'restrict_manage_posts'=>array(),
	'retrieve_password'=>array('user_login'),
//	'set_current_user'=>array(),
	'show_user_profile'=>array('USER_OBJ'),
//	'simple_edit_form'=>array(), // is this really used?
	'user_register'=>array('USER'),
	'wp_authenticate'=>array('USER_OBJ'),
	'wp_login'=>array('user_login'),
//	'wp_logout'=>array(),

// ADVANCED ACTIONS
//	'admin_menu'=>array(),
//	'admin_notices'=>array(),
//	'blog_privacy_selector'=>array(),
	'check_admin_referer'=>array('action_nonce','result'),
	'check_ajax_referer'=>array('action_nonce','result')
//// Not sure what vars to map these objects to...
//	'generate_rewrite_rules'=>array('WP_REWRITE_ARRAY'),
//	'init'=>array(),
//	'loop_end'=>array('WP_QUERY_ARRAY'),
//	'loop_start'=>array('WP_QUERY_ARRAY'),
//	'parse_query'=>array('WP_QUERY_ARRAY'),
//	'parse_request'=>array('WP_ARRAY'),
//	'pre_get_posts'=>array('WP_QUERY_ARRAY'),
//	'sanitize_comment_cookies'=>array(),
//	'send_headers'=>array('WP_ARRAY'),
//	'shutdown'=>array(),
//	'wp'=>array('WP_ARRAY')
	// TODO: ADD MORE...
);

$hookpress_actions = apply_filters( 'hookpress_actions', $hookpress_actions );

//foreach ($wp_rewrite->feeds as $feedname) {
//	$hookpress_actions["do_feed_$feedname"] = array('is_comment_feed');
//}
// TODO: add more dynamically later:
// activate_(plugin name)
// admin_head-(page hook)
// admin_print_scripts-(page_hook)
// admin_print_styles-(page_hook)
// deactivate_(plugin file name)
// load-(page)
// update_option_(option_name)
// upload_files_(tab)
// wp_ajax_(action)


// FILTERS:

$hookpress_filters = array(
// Post, Page, and Attachment Filters: db read
	'attachment_icon'=>array('icon','ATTACHMENT'),
	'attachment_innerHTML'=>array('attachment_html','ATTACHMENT'),
	'content_edit_pre'=>array('content'),
	'excerpt_edit_pre'=>array('excerpt'),
	'get_attached_file'=>array('file','ATTACHMENT'),
	'get_enclosed'=>array('enclosures'),
	'get_pages'=>array('pages','arguments'),
	'get_pung'=>array('pung_urls'),
	'get_the_excerpt'=>array('excerpt'),
	'get_the_guid'=>array('guid'),
//	'get_to_ping'=>array('to_ping'), // parse list
	'icon_dir'=>array('icon_dir'),
	'icon_dir_uri'=>array('icon_dir_uri'),
	'prepend_attachment'=>array('attachment_html'),
	'sanitize_title'=>array('title','raw_title'),
	'single_post_title'=>array('title'),
	'the_content'=>array('content'),
	'the_content_rss'=>array('content'),
	'the_editor_content'=>array('content'),
	'the_excerpt'=>array('excerpt'),
	'the_excerpt_rss'=>array('excerpt'),
	'the_tags'=>array('tags_html','before','sep','after'),
	'the_title'=>array('title'),
	'the_title_rss'=>array('title'),
//	'title_edit_pre'=>array(), // unused?
	'wp_dropdown_pages'=>array('dropdown_html'),
	'wp_list_pages'=>array('list_html'),
//	'wp_list_pages_excludes'=>array('ARR_OF_excluded_pages'),
	'wp_get_attachment_metadata'=>array('data'),
	'wp_get_attachment_thumb_file'=>array('thumbfile','POST'),
	'wp_get_attachment_thumb_url'=>array('url','POST'),
	'wp_get_attachment_url'=>array('url','POST'),
	'wp_mime_type_icon'=>array('icon','mime','POST'),
	'wp_title'=>array('title','sep','seplocation'),
	
// Post, Page, and Attachment Filters: db write
	'add_ping'=>array('new'),
//	'attachment_max_dims'=>array('ARR_max_dims'),
//	'category_save_pre'=>array(), // unused?
//	'comment_status_pre'=>array(), // unused?
//	'content_filtered_save_pre'=>array(), // unused?
//	'content_save_pre'=>array('description'),
//	'excerpt_save_pre'=>array(), // unused?
//	'name_save_pre'=>array(), // unused?
	'phone_content'=>array('content'),
//	'ping_status_pre'=>array(), // unused?
//	'post_mime_type_pre'=>array(), // unused?
//	'status_save_pre'=>array(), // unused?
//	'thumbnail_filename'=>array(), // unused?
//	'wp_thumbnail_creation_size_limit'=>array(), // unused?
//	'wp_thumbnail_max_side_length'=>array(), // unused?
//	'title_save_pre'=>array(), // unused?
	'update_attached_file'=>array('file_path','ATTACHMENT'),
	'wp_delete_file'=>array('file_name'),
//	'wp_generate_attachment_metadata'=>array('ARR_of_metadata'),
//	'wp_update_attachment_metadata'=>array('ARR_of_metadata','ATTACHMENT'),

	// Comment, Trackback, and Ping Filters: db reads
	'comment_excerpt'=>array('comment_excerpt'),
//	'comment_flood_filter'=>array('BOOL_flood_dye','time_lastcomment','time_newcomment'),
//	'comment_post_redirect'=>array('location','COMMENT_OBJ'),
	'comment_text'=>array('comment_text'),
	'comment_text_rss'=>array('comment_text'),
//	'comments_array'=>array('ARR_comments','POST'),
	'comments_number'=>array('output','number'),
//	'get_comment_number'=>array('output'), // unused?
	'get_comment_ID'=>array('comment_ID'),
	'get_comment_text'=>array('comment_text'),
	'get_comment_type'=>array('comment_type'),
	'get_comments_number'=>array('count'),
	'post_comments_feed_link'=>array('url'),
	
	// Comment, Trackback, and Ping Filters: db writes
	'comment_save_pre'=>array('comment_text'),
	'pre_comment_approved'=>array('approved'), // '0,1,spam'
//	'preprocess_comment'=>array('COMMENT_ARR'),
//	'wp_insert_post_data'=>array('POST_DATA_ARR'),
	'pre_comment_content'=>array('comment_text')
	
	
	// TODO: ADD MORE...
);

$hookpress_filters = apply_filters( 'hookpress_filters', $hookpress_filters );
