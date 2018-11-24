<?php
/*
Plugin Name: Moustache Design
Plugin URI: https://moustachedesign.xyz
Description: A plugin to keep custom code for Wordpress / Vue Sites
Version: 1.0
Author: Alessandro Giordo
Author URI: https://moustachedesign.xyz
License: GPL2
*/


/****************************************************************************************************************
 * Custom Post types
 *
 */
function create_post_type() {
    register_post_type( 'casestudies', // /casestudies instead of /posts
        array(
            'labels' => array(
                'name' => __( 'Case Studies' ),
                'singular_name' => __( 'Case Study' )
                
            ),
            'public' => true,
            'has_archive' => true,
            'show_in_rest' => true
        )
    );
}
add_action( 'init', 'create_post_type' );



/****************************************************************************************************************
 * Custom Taxonomies
 *
 */
add_action( 'init', 'taxonomies_init' );
function taxonomies_init() {

    // create a new taxonomy
    // register_taxonomy(
    //     'event_categories',
    //     ['events', 'offers'],
    //     array(
    //         'label' => __( 'Categories' ),
    //         'rewrite' => array( 'slug' => 'event_category' ),
    //         'hierarchical' => true,
    //         'show_ui' => true,
    //         'show_admin_column' => true,
    //         'query_var' => true,
    //         "show_in_rest" => true,
    //     )
    // );
}


/****************************************************************************************************************
 * Add 'meta_query' into REST API
 *
 */
add_filter( 'rest_query_vars', 'test_query_vars' );
function test_query_vars ( $vars ) {
    $vars[] = 'meta_query';
    return $vars;
}

/****************************************************************************************************************
 * Remove admin menu items
 *
 */
add_action ( 'admin_menu', 'wpsites_remove_menu_links', 999 );
function wpsites_remove_menu_links() {

    // Remove just for client users
    if ( ! current_user_can( 'update_core' ) ) {

        // Comments section
        remove_menu_page( 'edit-comments.php');
        remove_menu_page( 'tools.php');

        // Contact form 7
        remove_menu_page('wpcf7');

    }

    // Remove for everyone

    // Post format box
    remove_theme_support('post-formats');

}


/****************************************************************************************************************
 * Remove featured image box from pages
 *
 */
add_action('do_meta_boxes', 'remove_thumbnail_box');
function remove_thumbnail_box() {
    remove_meta_box( 'postimagediv','page','side' );
}


/****************************************************************************************************************
 * Hide preview button from pages/posts
 *
 */
function posttype_admin_css() {
    global $post_type;
    $post_types = array(
        // set post types
        'post_type_name',
        'post',
        'page',
        'casestudies'
    );
    if(in_array($post_type, $post_types))
        echo '<style type="text/css">#post-preview, #view-post-btn{display: none;}</style>';
}
add_action( 'admin_head-post-new.php', 'posttype_admin_css' );
add_action( 'admin_head-post.php', 'posttype_admin_css' );

/**
 * Limit WordPress media uploader maximum upload file size
 * Uploading very large images is pointless as they will hardly ever be used at full size.
 * Crunching larger files takes more memory; larger files take more space too.
 *
 * @param	mixed	$file	the uploaded file item to filter
 * 
 * @return 	array 	$file	the filtered file item with response
 */
function limit_upload_file_size( $file ) {
	
	$images = 512; // size in KB (example)
	$others = 5048; // size in KB (example)
	// exclude admins
	if ( ! current_user_can( 'manage_options' ) ) :
		// get filesize of upload
		$size = $file['size'];
		$size = $size / 1024; // Calculate down to KB
		// get imagetype of upload
		$type = $file['type'];
		$is_image = strpos( $type, 'image' );
		// set sizelimit in kB
		$image_limit = $images;
		$others_limit = $others;
		if ( $is_image == true && $size > $image_limit ) {
			$file['error'] = sprintf( __( 'WARNING: You should not upload images larger than %d KB. Please reduce the image file size and try again.' ), $images );
		} elseif ( $is_image == false && $size > $others_limit ) {
			$file['error'] = sprintf( __( 'WARNING: You should not upload files larger than %d KB. Please reduce the file size and try again.' ), $others );
		}
	endif;
	return $file;
	
}
add_filter ( 'wp_handle_upload_prefilter', 'limit_upload_file_size', 10, 1 );



/****************************************************************************************************************
 * Run script when publishing a new post - which will flag a call to 'npm run generate' to be picked up by cron
 *
 */
// $options = get_option( 'ni_settings' );

// if ($options['ni_text_field_0'] == 'transition_post_status') {
//     add_action( 'transition_post_status', 'post_saved', 10, 3 );
//     function post_saved( $new_status, $old_status, $post ) {
//         if ( $new_status == 'publish' ) {
//             file_put_contents(getenv('APP_PATH').'generate_pending', "awaiting 'npm run generate' - to be executed by cron", FILE_APPEND | LOCK_EX);
//         }
//     }
// }
// else if ($options['ni_text_field_0']) {
//     add_action( $options['ni_text_field_0'], 'post_saved', 10, 3 );
//     function post_saved( $new_status, $old_status, $post ) {
//         file_put_contents(getenv('APP_PATH').'generate_pending', "awaiting 'npm run generate' - to be executed by cron", FILE_APPEND | LOCK_EX);
//     }
// }




/****************************************************************************************************************
 * SETTINGS
 *
 */

add_action( 'admin_menu', 'ni_add_admin_menu' );
add_action( 'admin_init', 'ni_settings_init' );


function ni_add_admin_menu(  ) { 

	add_options_page( 'Moustache Design', 'Moustache Design', 'manage_options', 'moustache_design', 'ni_options_page' );

}


function ni_settings_init(  ) { 

	register_setting( 'pluginPage', 'ni_settings' );

	add_settings_section(
		'ni_pluginPage_section', 
		__( 'Plugin Settings', 'wordpress' ), 
		'ni_settings_section_callback', 
		'pluginPage'
	);

	add_settings_field( 
		'ni_text_field_0', 
		__( 'Page generation handle', 'wordpress' ), 
		'ni_text_field_0_render', 
		'pluginPage', 
		'ni_pluginPage_section' 
	);


}


function ni_text_field_0_render(  ) { 

	$options = get_option( 'ni_settings' );
	?>
	<input type='text' name='ni_settings[ni_text_field_0]' value='<?php echo $options['ni_text_field_0']; ?>'>
	<?php

}


function ni_settings_section_callback(  ) { 

	echo __( 'Cron Section', 'wordpress' );

}


function ni_options_page(  ) { 

	?>
	<form action='options.php' method='post'>

		<h2>Moustache Design</h2>

		<?php
		settings_fields( 'pluginPage' );
		do_settings_sections( 'pluginPage' );
		submit_button();
		?>

	</form>
	<?php

}

/**
 * 
 * Contact form functionality
 * 
 * Endponts:
 *  - /forms/contact
 * 
 */
function contact_form_func( $data ) {
  $yourName = $data['your-name'];
  $yourEmail = $data['your-email'];
  $yourMessage = $data['your-message'];
  
  $to = 'bovas85@gmail.com';
  $subject = "Nunziella contact form submission from ".$yourName;
  
  $email_message  = "From: ".$yourName."<br/>";
  $email_message .= "Email: ".$yourEmail."<br/>";
  $email_message .= "Message: ".$yourMessage."<br/><br/>";

  $headers = array('Content-Type: text/html; charset=UTF-8');
  
  wp_mail( $to, $subject, $email_message, $headers);
  
  return 'Form received (CONTACT)';
}

add_action( 'rest_api_init', function () {
  register_rest_route( 'forms', '/contact', array(
    'methods' => 'POST',
    'callback' => 'contact_form_func',
  ) );
} );