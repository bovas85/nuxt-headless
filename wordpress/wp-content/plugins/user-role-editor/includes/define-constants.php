<?php

/*
 * User Role Editor WordPress plugin constants definitions
 * 
 * Author: Vladimir Garagulia
 * Author email: support@role-editor.com
 * Author URI: https://role-editor.com
 * 
*/

define( 'URE_WP_ADMIN_URL', admin_url() );
define( 'URE_ERROR', 'Error was encountered' );
define( 'URE_PARENT', is_network_admin() ? 'network/users.php' : 'users.php' );
define( 'URE_KEY_CAPABILITY', 'ure_manage_options' );