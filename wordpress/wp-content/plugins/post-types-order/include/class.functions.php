<?php

    if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
    
    class CptoFunctions
        {
            
            /**
            * Return the user level
            * 
            * This is deprecated, will be removed in the next versions
            * 
            * @param mixed $return_as_numeric
            */
            function userdata_get_user_level($return_as_numeric = FALSE)
                {
                    global $userdata;
                    
                    $user_level = '';
                    for ($i=10; $i >= 0;$i--)
                        {
                            if (current_user_can('level_' . $i) === TRUE)
                                {
                                    $user_level = $i;
                                    if ($return_as_numeric === FALSE)
                                        $user_level = 'level_'.$i;    
                                    break;
                                }    
                        }        
                    return ($user_level);
                }
                
            
            /**
            * Retrieve the plugin options
            * 
            */
            function get_options()
                {
                    //make sure the vars are set as default
                    $options = get_option('cpto_options');
                    
                    $defaults   = array (
                                            'show_reorder_interfaces'   =>  array(),
                                            'autosort'                  =>  1,
                                            'adminsort'                 =>  1,
                                            'use_query_ASC_DESC'        =>  '',
                                            'archive_drag_drop'         =>  1,
                                            'capability'                =>  'manage_options',
                                            'navigation_sort_apply'     =>  1,
                                            
                                        );
                    $options          = wp_parse_args( $options, $defaults );
                    
                    $options            =   apply_filters('pto/get_options', $options);
                    
                    return $options;            
                }
            
            
            /**
            * General messages box
            *     
            */
            function cpt_info_box()
                {
                    ?>
                        <div id="cpt_info_box">
                             <div id="p_right"> 
                                
                                <div id="p_socialize">
                                    
                                    <div class="p_s_item s_f">
                                        <div id="fb-root"></div>
                                        <script>(function(d, s, id) {
                                          var js, fjs = d.getElementsByTagName(s)[0];
                                          if (d.getElementById(id)) return;
                                          js = d.createElement(s); js.id = id;
                                          js.src = "//connect.facebook.net/en_US/sdk.js#xfbml=1&version=v2.5";
                                          fjs.parentNode.insertBefore(js, fjs);
                                        }(document, 'script', 'facebook-jssdk'));</script>
                                        
                                        <div class="fb-like" data-href="https://www.facebook.com/Nsp-Code-190329887674484/" data-layout="button_count" data-action="like" data-show-faces="true" data-share="false"></div>
                                        
                                    </div>
                                    
                                    <div class="p_s_item s_t">
                                        <a href="https://twitter.com/share" class="twitter-share-button" data-url="http://www.nsp-code.com" data-text="Define custom order for your post types through an easy to use javascript AJAX drag and drop interface. No theme code updates are necessarily, this plugin will take care of query update." data-count="none">Tweet</a><script type="text/javascript" src="//platform.twitter.com/widgets.js"></script>
                                    </div>
                  
                                    <div class="clear"></div>
                                </div>
                     
                            </div>
                            
                            <p><?php _e('Did you find this plugin useful? Please support our work by purchasing the advanced version or write an article about this plugin in your blog with a link to our site', 'post-types-order') ?> <a href="http://www.nsp-code.com/" target="_blank"><strong>http://www.nsp-code.com/</strong></a>.</p>
                            <h4><?php _e('Did you know there is available an Advanced version of this plug-in?', 'post-types-order') ?> <a target="_blank" href="http://www.nsp-code.com/premium-plugins/wordpress-plugins/advanced-post-types-order/"><?php _e('Read more', 'post-types-order') ?></a></h4>
                            <p><?php _e('Check our', 'post-types-order') ?> <a target="_blank" href="http://wordpress.org/plugins/taxonomy-terms-order/">Category Order - Taxonomy Terms Order</a> <?php _e('plugin which allow to custom sort categories and custom taxonomies terms', 'post-types-order') ?> </p>
                            <p><span style="color:#CC0000" class="dashicons dashicons-megaphone" alt="f488">&nbsp;</span> <?php _e('Check out', 'post-types-order') ?> <a href="https://wordpress.org/plugins/wp-hide-security-enhancer/" target="_blank"><b>WP Hide & Security Enhancer</b></a> <?php _e('the easy way to completely hide your WordPress core files, theme and plugins', 'post-types-order') ?>.</p>
                            
                            <div class="clear"></div>
                        </div>
                    
                    <?php   
                }

                
            
            function cpto_get_previous_post_where($where, $in_same_term, $excluded_terms)
                {
                    global $post, $wpdb;

                    if ( empty( $post ) )
                        return $where;
                    
                    //?? WordPress does not pass through this varialbe, so we presume it's category..
                    $taxonomy = 'category';
                    if(preg_match('/ tt.taxonomy = \'([^\']+)\'/i',$where, $match)) 
                        $taxonomy   =   $match[1];
                    
                    $_join = '';
                    $_where = '';
                    
                    if ( $in_same_term || ! empty( $excluded_terms ) ) 
                        {
                            $_join = " INNER JOIN $wpdb->term_relationships AS tr ON p.ID = tr.object_id INNER JOIN $wpdb->term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id";
                            $_where = $wpdb->prepare( "AND tt.taxonomy = %s", $taxonomy );

                            if ( ! empty( $excluded_terms ) && ! is_array( $excluded_terms ) ) 
                                {
                                    // back-compat, $excluded_terms used to be $excluded_terms with IDs separated by " and "
                                    if ( false !== strpos( $excluded_terms, ' and ' ) ) 
                                        {
                                            _deprecated_argument( __FUNCTION__, '3.3', sprintf( __( 'Use commas instead of %s to separate excluded terms.' ), "'and'" ) );
                                            $excluded_terms = explode( ' and ', $excluded_terms );
                                        } 
                                    else 
                                        {
                                            $excluded_terms = explode( ',', $excluded_terms );
                                        }

                                    $excluded_terms = array_map( 'intval', $excluded_terms );
                                }

                            if ( $in_same_term ) 
                                {
                                    $term_array = wp_get_object_terms( $post->ID, $taxonomy, array( 'fields' => 'ids' ) );

                                    // Remove any exclusions from the term array to include.
                                    $term_array = array_diff( $term_array, (array) $excluded_terms );
                                    $term_array = array_map( 'intval', $term_array );
                            
                                    $_where .= " AND tt.term_id IN (" . implode( ',', $term_array ) . ")";
                                }

                            if ( ! empty( $excluded_terms ) ) {
                                $_where .= " AND p.ID NOT IN ( SELECT tr.object_id FROM $wpdb->term_relationships tr LEFT JOIN $wpdb->term_taxonomy tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id) WHERE tt.term_id IN (" . implode( $excluded_terms, ',' ) . ') )';
                            }
                        }
                        
                    $current_menu_order = $post->menu_order;
                    
                    $query = $wpdb->prepare( "SELECT p.* FROM $wpdb->posts AS p
                                $_join
                                WHERE p.post_date < %s  AND p.menu_order = %d AND p.post_type = %s AND p.post_status = 'publish' $_where" ,  $post->post_date, $current_menu_order, $post->post_type);
                    $results = $wpdb->get_results($query);
                            
                    if (count($results) > 0)
                            {
                                $where .= $wpdb->prepare( " AND p.menu_order = %d", $current_menu_order );
                            }
                        else
                            {
                                $where = str_replace("p.post_date < '". $post->post_date  ."'", "p.menu_order > '$current_menu_order'", $where);  
                            }
                    
                    return $where;
                }
                
            function cpto_get_previous_post_sort($sort)
                {
                    global $post, $wpdb;
                    
                    $sort = 'ORDER BY p.menu_order ASC, p.post_date DESC LIMIT 1';

                    return $sort;
                }

            function cpto_get_next_post_where($where, $in_same_term, $excluded_terms)
                {
                    global $post, $wpdb;

                    if ( empty( $post ) )
                        return $where;
                    
                    $taxonomy = 'category';
                    if(preg_match('/ tt.taxonomy = \'([^\']+)\'/i',$where, $match)) 
                        $taxonomy   =   $match[1];
                    
                    $_join = '';
                    $_where = '';
                                
                    if ( $in_same_term || ! empty( $excluded_terms ) ) 
                        {
                            $_join = " INNER JOIN $wpdb->term_relationships AS tr ON p.ID = tr.object_id INNER JOIN $wpdb->term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id";
                            $_where = $wpdb->prepare( "AND tt.taxonomy = %s", $taxonomy );

                            if ( ! empty( $excluded_terms ) && ! is_array( $excluded_terms ) ) 
                                {
                                    // back-compat, $excluded_terms used to be $excluded_terms with IDs separated by " and "
                                    if ( false !== strpos( $excluded_terms, ' and ' ) ) 
                                        {
                                            _deprecated_argument( __FUNCTION__, '3.3', sprintf( __( 'Use commas instead of %s to separate excluded terms.' ), "'and'" ) );
                                            $excluded_terms = explode( ' and ', $excluded_terms );
                                        } 
                                    else 
                                        {
                                            $excluded_terms = explode( ',', $excluded_terms );
                                        }

                                    $excluded_terms = array_map( 'intval', $excluded_terms );
                                }

                            if ( $in_same_term ) 
                                {
                                    $term_array = wp_get_object_terms( $post->ID, $taxonomy, array( 'fields' => 'ids' ) );

                                    // Remove any exclusions from the term array to include.
                                    $term_array = array_diff( $term_array, (array) $excluded_terms );
                                    $term_array = array_map( 'intval', $term_array );
                            
                                    $_where .= " AND tt.term_id IN (" . implode( ',', $term_array ) . ")";
                                }

                            if ( ! empty( $excluded_terms ) ) {
                                $_where .= " AND p.ID NOT IN ( SELECT tr.object_id FROM $wpdb->term_relationships tr LEFT JOIN $wpdb->term_taxonomy tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id) WHERE tt.term_id IN (" . implode( $excluded_terms, ',' ) . ') )';
                            }
                        }
                        
                    $current_menu_order = $post->menu_order;
                    
                    //check if there are more posts with lower menu_order
                    $query = $wpdb->prepare( "SELECT p.* FROM $wpdb->posts AS p
                                $_join
                                WHERE p.post_date > %s AND p.menu_order = %d AND p.post_type = %s AND p.post_status = 'publish' $_where", $post->post_date, $current_menu_order, $post->post_type );
                    $results = $wpdb->get_results($query);
                            
                    if (count($results) > 0)
                            {
                                $where .= $wpdb->prepare(" AND p.menu_order = %d", $current_menu_order );
                            }
                        else
                            {
                                $where = str_replace("p.post_date > '". $post->post_date  ."'", "p.menu_order < '$current_menu_order'", $where);  
                            }
                    
                    return $where;
                }

            function cpto_get_next_post_sort($sort)
                {
                    global $post, $wpdb; 
                    
                    $sort = 'ORDER BY p.menu_order DESC, p.post_date ASC LIMIT 1';
                    
                    return $sort;    
                }

                
                
        }

?>