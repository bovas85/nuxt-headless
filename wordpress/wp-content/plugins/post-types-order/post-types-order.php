<?php
/*
Plugin Name: Post Types Order
Plugin URI: http://www.nsp-code.com
Description: Posts Order and Post Types Objects Order using a Drag and Drop Sortable javascript capability
Author: Nsp Code
Author URI: http://www.nsp-code.com 
Version: 1.9.3.9
Text Domain: post-types-order
Domain Path: /languages/
*/

    define('CPTPATH',   plugin_dir_path(__FILE__));
    define('CPTURL',    plugins_url('', __FILE__));
    
    include_once(CPTPATH . '/include/class.cpto.php');
    include_once(CPTPATH . '/include/class.functions.php');
  
   
    add_action( 'plugins_loaded', 'cpto_class_load');     
    function cpto_class_load()
        {
            
            global $CPTO;
            $CPTO   =   new CPTO();
        }
                

    add_action( 'plugins_loaded', 'cpto_load_textdomain'); 
    function cpto_load_textdomain() 
        {
            load_plugin_textdomain('post-types-order', FALSE, dirname( plugin_basename( __FILE__ ) ) . '/languages');
        }
 
        
    add_action('wp_loaded', 'initCPTO' ); 	
    function initCPTO() 
        {
	        global $CPTO;

            $options          =     $CPTO->functions->get_options();

            if (is_admin())
                {
                    if(isset($options['capability']) && !empty($options['capability']))
                        {
                            if( current_user_can($options['capability']) )
                                $CPTO->init(); 
                        }
                    else if (is_numeric($options['level']))
                        {
                            if ( $CPTO->functions->userdata_get_user_level(true) >= $options['level'] )
                                $CPTO->init();
                        }
                        else
                            {
                                $CPTO->init();
                            }
                }        
        }
        
   

?>