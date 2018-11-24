<?php

/* 
 * User Role Editor WordPress plugin
 * Miscellaneous support stuff, which should still be defined beyond of classes
 * 
 * Author: Vladimir Garagulya
 * Author email: suport@role-editor.com
 * Author URI: https://role-editor.com
 * License: GPL v3
 * 
*/

// if Gravity Forms is installed
if ( class_exists( 'GFForms' ) ) {    
/* 
 * Support for Gravity Forms capabilities
	*		As Gravity Form has integrated support for the Members plugin - let's imitate its presense, so GF code, like
	*		self::has_members_plugin()) considers that it is has Members plugin   
 */
    if ( !function_exists( 'members_get_capabilities' ) ) { 
        include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        if ( ! is_plugin_active( 'members/members.php' ) ) {
            /*
												 *  Define stub function to say "Gravity Forms" plugin: 'Hey! Yes, I'm not the "Members" plugin, but I'm "User Role Editor" and 
												 *  I'm  capable to manage your roles and capabilities too.        
												 */
            function members_get_capabilities() { 
																
            }
        }
    }
}


if ( ! function_exists( 'ure_get_post_view_access_users' ) ) {
				/*
				 * Returns the list of users with front-end content view restrictions
				 */
    function ure_get_post_view_access_users( $post_id ) {
        if ( ! $GLOBALS['user_role_editor']->is_pro() ) {
            return false;
        }
        
        $result = $GLOBALS['user_role_editor']->get_post_view_access_users( $post_id ); 
        
        return $result;
    }   
    // end of ure_get_post_view_users()
    
}   


if ( ! function_exists( 'ure_hide_admin_bar' ) ) {
    function ure_hide_admin_bar() {
        
        show_admin_bar(false);
        
    }
    // end of hide_admin_bar()
}


if ( ! function_exists( 'wp_roles' ) ) {
   /**    
    * Included for back compatibility with WP 4.0+
    * Retrieves the global WP_Roles instance and instantiates it if necessary.
    * 
    * @since 4.3.0
    * 
    * @global WP_Roles $wp_roles WP_Roles global instance.
    *
    * @return WP_Roles WP_Roles global instance if not already instantiated.
    */
    function wp_roles() {
        global $wp_roles;

        if (!isset($wp_roles)) {
            $wp_roles = new WP_Roles();
        }
        return $wp_roles;
    }

}