<?php
/**
 * WP REST API - filter fields
 *
 * @package             REST_Api_Filter_Fields
 * @author              Stephan van Rooij <github@svrooij.nl>
 * @license             MIT
 *
 * @wordpress-plugin
 * Plugin Name:         WP REST API - filter fields
 * Plugin URI:          https://github.com/svrooij/rest-api-filter-fields
 * Description:         Enables you to filter the fields returned by the api.
 * Version:             1.0.7
 * Author:              Stephan van Rooij
 * Author URI:          https://svrooij.nl
 * License:             MIT
 * License URI:         https://raw.githubusercontent.com/svrooij/rest-api-filter-fields/master/LICENSE
 */

// Only include the file if we actually have the WP_REST_Controller class.
if(class_exists( 'WP_REST_Controller' )){
  require_once('includes/class-rest-api-filter-fields.php');
}
