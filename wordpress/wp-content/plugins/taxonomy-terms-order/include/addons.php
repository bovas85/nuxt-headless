<?php

    //Co-Authors Plus fix
    add_action ('to/get_terms_orderby/ignore', 'to__get_terms_orderby__ignore', 10, 3);
    function to__get_terms_orderby__ignore( $ignore, $orderby, $args )
        {
            if( !function_exists('is_plugin_active') )
                include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
            
            if( !   is_plugin_active( 'co-authors-plus/co-authors-plus.php' ))
                return $ignore;
            
            if ( ! isset($args['taxonomy']) ||  count($args['taxonomy']) !==    1 ||    array_search('author', $args['taxonomy'])   === FALSE )
                return $ignore;    
                
            return TRUE;
            
        }
    

?>