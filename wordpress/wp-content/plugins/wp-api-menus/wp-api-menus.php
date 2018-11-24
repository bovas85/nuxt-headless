<?php
/**
 * Plugin Name: WP REST API Menus
 * Plugin URI:  https://github.com/nekojira/wp-api-menus
 * Description: Extends WP API with WordPress menu routes.
 *
 * Version:     1.3.1
 *
 * Author:      Fulvio Notarstefano
 * Author URI:  https://github.com/unfulvio
 *
 * Text Domain: wp-api-menus
 *
 * @package WP_API_Menus
 */

/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2 or, at
 * your discretion, any later version, as published by the Free
 * Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// WP API v1.
include_once 'includes/wp-api-menus-v1.php';
// WP API v2.
include_once 'includes/wp-api-menus-v2.php';

if ( ! function_exists ( 'wp_rest_menus_init' ) ) :

	/**
	 * Init JSON REST API Menu routes.
	 *
	 * @since 1.0.0
	 */
	function wp_rest_menus_init() {

        if ( ! defined( 'JSON_API_VERSION' ) && ! in_array( 'json-rest-api/plugin.php', get_option( 'active_plugins' ) ) ) {
			$class = new WP_REST_Menus();
			 add_filter( 'rest_api_init', array( $class, 'register_routes' ) );
		} else {
			$class = new WP_JSON_Menus();
			add_filter( 'json_endpoints', array( $class, 'register_routes' ) );
		}
	}

	add_action( 'init', 'wp_rest_menus_init' );

endif;
