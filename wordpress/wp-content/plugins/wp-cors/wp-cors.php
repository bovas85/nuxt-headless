<?php
/*
 * Plugin Name: WP-CORS 
 * Plugin URI: http://knowprocess.com/wp-plugins/wp-cors
 * Description: Simply allows you to control which external domains may make 
 *   AJAX calls to integrate your content using the CORS standard. 
 * Author: Tim Stephenson
 * Version: 0.2.1
 * Author URI: http://knowprocess.com
 * License: GPLv2 or later
 */

  define("CORS_ID", 'wp-cors');
  define("CORS_VERSION", "0.2.1");
  define("CORS_NAME", 'CORS');
  define("CORS_DEBUG", false);
  //require_once("includes/shortcodes.php");

  if ( is_admin() ) { // admin actions
    add_action( 'admin_menu', 'add_cors_admin_menu' );
    add_action( 'admin_init', 'register_cors_admin_settings' );
  } else {
    add_action( 'send_headers', 'add_cors_header' );
  }
  add_action( 'wp_ajax_cors_change_domains', 'cors_change_domains' );

  function add_cors_header() {
    $referrer = $_SERVER['HTTP_REFERER'];
    $origin = $_SERVER['HTTP_ORIGIN'];

    if (CORS_DEBUG) error_log('referer: '.$referrer.', '.$origin);
    $site_url = substr(site_url(), strpos(site_url(), '//'));
    //error_log('site url '.$site_url);
    if (preg_match('#^https?:'.$site_url.'#i', $referrer) !== 1) {
      if (CORS_DEBUG) error_log('Test if CORS allowed for: '.$referrer);
      if (CORS_DEBUG) error_log(' allowed cors domains are: '.get_option('cors_domains'));
      $domains = explode(',', get_option('cors_domains'));
      foreach ($domains as &$value) { 
        if (CORS_DEBUG) error_log('Test referrer for match with '.$value);
        if (preg_match('#^https?://'.$value.'.*#i', $referrer) === 1) {
          if (CORS_DEBUG) error_log('Allowing CORS from '.$referrer);
          header("Access-Control-Allow-Origin: *"); 
        } else { 
          if (CORS_DEBUG) error_log('Rejecting CORS from '.$referrer);
        }
      }
      unset($value); // break the reference with the last element
    } else { 
      if (CORS_DEBUG) error_log('CORS plugin ignoring local request: '.$referrer); 
    } 
  }

  /** Render the settings / options page in the admin dashboard */
  function cors_options_page() {
    if ( !current_user_can( 'manage_options' ) )  {
      wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
    }?>
    <div class="wrap">
    <h2><?php echo CORS_NAME; ?> Settings</h2>
    <p>You may enter one or more comma-separated domains to allow access to this site using the CORS standard for authorizing cross site requests.</p>
    <p>To allow <em>any</em> site to access yours via CORS put a *, though explicitly listing domains is a better practice for production sites.</p>
    <form method="post" action="options.php">
      <table class="form-table">
        <tr valign="top">
          <th scope="row">Allowed domains</th>
          <td><input type="text" name="cors_domains" value="<?php echo get_option('cors_domains'); ?>" /></td>
        </tr>
      </table>
    <?php 
    settings_fields( CORS_ID.'-basic-group' );
    do_settings_sections( CORS_ID.'-basic-group' );
    submit_button();?>
    </form>
    </div>
    <?php
  }

  function add_cors_admin_menu() {
    add_options_page( CORS_NAME.' Options', CORS_NAME, 'manage_options', CORS_ID, 'cors_options_page' );
  }

  function register_cors_admin_settings() { 
    if (CORS_DEBUG) error_log('Registering settings...');
    register_setting( CORS_ID.'-basic-group', 'cors_domains' );
  }

  function cors_change_domains() {
    if (!empty($_POST['cors_domains'])) {
      //error_log('Request to change subscription WITH expected params: cors_domains: '.$_POST['cors_domains']);
      $user = wp_get_current_user();

      update_option( $user->ID, 'cors_domains', $_POST['cors_domains']);
      die();
    } else {
      if (CORS_DEBUG) error_log('Request to change options without expected params: cors_domains');
      die($st);
    }
  }
?>
