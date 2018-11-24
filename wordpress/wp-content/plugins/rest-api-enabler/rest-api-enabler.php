<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * Dashboard. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://wordpress.org/plugins/rest-api-enabler
 * @since             1.0.0
 * @package           REST_API_Enabler
 *
 * @wordpress-plugin
 * Plugin Name:       REST API Enabler
 * Plugin URI:        http://wordpress.org/plugins/rest-api-enabler
 * Description:       Enable the WP REST API to work with custom post types, custom fields, and custom endpoints.
 * Version:           1.1.0
 * Author:            Mickey Kay Creative
 * Author URI:        http://mickeykaycreative.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       rest-api-enabler
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-rest-api-enabler-activator.php
 */
function activate_rest_api_enabler() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-rest-api-enabler-activator.php';
	REST_API_Enabler_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-rest-api-enabler-deactivator.php
 */
function deactivate_rest_api_enabler() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-rest-api-enabler-deactivator.php';
	REST_API_Enabler_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_rest_api_enabler' );
register_deactivation_hook( __FILE__, 'deactivate_rest_api_enabler' );

/**
 * The core plugin class that is used to define internationalization,
 * dashboard-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-rest-api-enabler.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_rest_api_enabler() {

	// Pass main plugin file through to plugin class for later use.
	$args = array(
		'plugin_file' => __FILE__,
	);

	$plugin = REST_API_Enabler::get_instance( $args );
	$plugin->run();

}
run_rest_api_enabler();
