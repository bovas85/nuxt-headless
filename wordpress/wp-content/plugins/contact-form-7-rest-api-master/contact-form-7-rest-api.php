<?php

/**
 * Plugin Name: Contact Form 7 Rest API
 * Description: Adds Contact Form 7 get, create & update endpoints to the WP REST API v2
 * Version: 1.0
 * Author: Bradley Tollett
 * Plugin URI: https://github.com/CodeBradley/contact-form-7-rest-api
 */

if ( ! defined( 'ABSPATH' ) ) {
    die( "You can't do anything by accessing this file directly." );
}

if ( !function_exists( 'is_plugin_active' ) ) {
    require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
}

if ( is_plugin_active('contact-form-7/wp-contact-form-7.php') ) {
    /* Adds our callback function to the rest_api_init hook if Contact Form 7 is active so that it's loaded with the REST API */
    add_action('rest_api_init', 'register_rest_routes');
}

function register_rest_routes() {

    if(!class_exists('WP_REST_Contact_Form_7_Controller')) {
        require_once plugin_dir_path(__FILE__).'/lib/endpoints/class-wp-rest-contact-form-7-controller.php';
    }

    $controller = new WP_REST_Contact_Form_7_Controller();
    $controller->register_routes();

}
