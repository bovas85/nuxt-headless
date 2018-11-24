<?php


    class PTO_LiteSpeed_Cache
        {
                        
            function __construct()
                {
                    
                    if( !is_plugin_active( 'litespeed-cache/litespeed-cache.php' ))
                        return false;
                    
                    add_action( 'PTO/order_update_complete', array( $this, 'order_update_complete') );
                }
                
                
            function order_update_complete()
                {
                    
                    if( method_exists( 'LiteSpeed_Cache_API', 'purge_all' ) ) 
                        {
                            LiteSpeed_Cache_API::purge_all() ;
                        }
                
                }                        
                                
        }
        
    new PTO_LiteSpeed_Cache();


?>