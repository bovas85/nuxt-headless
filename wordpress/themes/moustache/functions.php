<?php
/**
 * Moustache Design functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package WordPress
 * @subpackage moustachedesign
 * @since 1.0
 */


/**
 * Sets up theme defaults and registers support for various WordPress features.
 *
 * Note that this function is hooked into the after_setup_theme hook, which
 * runs before the init hook. The init hook is too late for some features, such
 * as indicating support for post thumbnails.
 */
function moustachedesign_theme_setup() {

    add_theme_support( 'post-thumbnails' );

    add_image_size( 'small', 600, 600, false );
    add_image_size( 'medium', 1024, 1024, false );
    add_image_size( 'large', 1920, 1920, false );
    add_image_size( 'ultra', 2048, 2048, false );
    add_image_size( '4k', 4096, 4096, false );

}
add_action( 'after_setup_theme', 'moustachedesign_theme_setup' );

add_filter( 'rest_cache_skip', function( $skip, $request_uri ) {
	if ( ! $skip && false !== stripos( $request_uri, 'contact-form-7' ) ) {
		return true;
	}
	return $skip;
}, 10, 2 );