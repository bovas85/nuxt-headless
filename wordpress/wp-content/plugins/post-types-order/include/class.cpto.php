<?php

    if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
    
    class CPTO 
        {
            var $current_post_type = null;
            
            var $functions;
            
            function __construct() 
                {

                    $this->functions    =   new CptoFunctions();
                   
                    $is_configured = get_option('CPT_configured');
                    if ($is_configured == '')
                        add_action( 'admin_notices', array($this, 'admin_configure_notices'));
                        
                    
                    add_filter('init',          array($this, 'on_init'));
                    
                    
                    add_filter('pre_get_posts', array($this, 'pre_get_posts'));
                    add_filter('posts_orderby', array($this, 'posts_orderby'), 99, 2);
                    
                        
                }
                
                
            function init()
                {
                    
                    include_once(CPTPATH . '/include/class.walkers.php');
                    
                    add_action( 'admin_init',                               array(&$this, 'registerFiles'), 11 );
                    add_action( 'admin_init',                               array(&$this, 'admin_init'), 10 );
                    add_action( 'admin_menu',                               array(&$this, 'addMenu') );
                    
                    add_action('admin_menu',                                array(&$this, 'plugin_options_menu'));
                    
                    //load archive drag&drop sorting dependencies
                    add_action( 'admin_enqueue_scripts',                    array(&$this, 'archiveDragDrop'), 10 );
                    
                    add_action( 'wp_ajax_update-custom-type-order',         array(&$this, 'saveAjaxOrder') );
                    add_action( 'wp_ajax_update-custom-type-order-archive', array(&$this, 'saveArchiveAjaxOrder') );
                
                
                }

            
            /**
            * On WordPress Init hook
            * This is being used to set the navigational links
            * 
            */
            function on_init()
                {
                    
                    if(is_admin())
                        return;
                    
                    
                    //check the navigation_sort_apply option
                    $options          =     $this->functions->get_options();
                    
                    $navigation_sort_apply   =  ($options['navigation_sort_apply'] ==  "1")    ?   TRUE    :   FALSE;
                    $navigation_sort_apply   =  apply_filters('cpto/navigation_sort_apply', $navigation_sort_apply);
                    
                    if( !   $navigation_sort_apply)
                        return;
                    
                    add_filter('get_previous_post_where',   array($this->functions, 'cpto_get_previous_post_where'),    99, 3);
                    add_filter('get_previous_post_sort',    array($this->functions, 'cpto_get_previous_post_sort')          );
                    add_filter('get_next_post_where',       array($this->functions, 'cpto_get_next_post_where'),        99, 3);
                    add_filter('get_next_post_sort',        array($this->functions, 'cpto_get_next_post_sort')              );
                
                }    
            
            
            
            function pre_get_posts($query)
                {
                        
                    //no need if it's admin interface
                    if (is_admin())
                        return $query;
                    
                    //check for ignore_custom_sort
                    if (isset($query->query_vars['ignore_custom_sort']) && $query->query_vars['ignore_custom_sort'] === TRUE)
                        return $query; 
                    
                    //ignore if  "nav_menu_item"
                    if(isset($query->query_vars)    &&  isset($query->query_vars['post_type'])   && $query->query_vars['post_type'] ==  "nav_menu_item")
                        return $query;    
                        
                    $options          =     $this->functions->get_options();
                    
                    //if auto sort    
                    if ($options['autosort'] == "1")
                        {
                            //remove the supresed filters;
                            if (isset($query->query['suppress_filters']))
                                $query->query['suppress_filters'] = FALSE;    
                            
                 
                            if (isset($query->query_vars['suppress_filters']))
                                $query->query_vars['suppress_filters'] = FALSE;
                 
                        }
                        
                    return $query;
                }
            
            
            
            function posts_orderby($orderBy, $query) 
                {
                    global $wpdb;
                    
                    $options          =     $this->functions->get_options();
                    
                    //check for ignore_custom_sort
                    if (isset($query->query_vars['ignore_custom_sort']) && $query->query_vars['ignore_custom_sort'] === TRUE)
                        return $orderBy;  
                    
                    //ignore the bbpress
                    if (isset($query->query_vars['post_type']) && ((is_array($query->query_vars['post_type']) && in_array("reply", $query->query_vars['post_type'])) || ($query->query_vars['post_type'] == "reply")))
                        return $orderBy;
                    if (isset($query->query_vars['post_type']) && ((is_array($query->query_vars['post_type']) && in_array("topic", $query->query_vars['post_type'])) || ($query->query_vars['post_type'] == "topic")))
                        return $orderBy;
                        
                    //check for orderby GET paramether in which case return default data
                    if (isset($_GET['orderby']) && $_GET['orderby'] !=  'menu_order')
                        return $orderBy;
                        
                    //Avada orderby
                    if (isset($_GET['product_orderby']) && $_GET['product_orderby'] !=  'default')
                        return $orderBy;
                    
                    //check to ignore
                    /**
                    * Deprecated filter
                    * do not rely on this anymore
                    */
                    if(apply_filters('pto/posts_orderby', $orderBy, $query) === FALSE)
                        return $orderBy;
                        
                    $ignore =   apply_filters('pto/posts_orderby/ignore', FALSE, $orderBy, $query);
                    if($ignore  === TRUE)
                        return $orderBy;
                    
                    //ignore search
                    if( $query->is_search()  &&  isset( $query->query['s'] )   &&  ! empty ( $query->query['s'] ) )
                        return( $orderBy );
                    
                    if (is_admin())
                            {
                                
                                if ( $options['adminsort'] == "1" || (defined('DOING_AJAX') && isset($_REQUEST['action']) && $_REQUEST['action'] == 'query-attachments') )
                                    {
                                        
                                        global $post;
                                        
                                        //temporary ignore ACF group and admin ajax calls, should be fixed within ACF plugin sometime later
                                        if (is_object($post) && $post->post_type    ==  "acf-field-group"
                                                ||  (defined('DOING_AJAX') && isset($_REQUEST['action']) && strpos($_REQUEST['action'], 'acf/') === 0))
                                            return $orderBy;
                                            
                                        if(isset($_POST['query'])   &&  isset($_POST['query']['post__in'])  &&  is_array($_POST['query']['post__in'])   &&  count($_POST['query']['post__in'])  >   0)
                                            return $orderBy;   
                                        
                                        $orderBy = "{$wpdb->posts}.menu_order, {$wpdb->posts}.post_date DESC";
                                    }
                            }
                        else
                            {   
                                $order  =   '';
                                if ($options['use_query_ASC_DESC'] == "1")
                                    $order  =   isset($query->query_vars['order'])  ?   " " . $query->query_vars['order'] : '';
                                
                                if ($options['autosort'] == "1")
                                    {
                                        if(trim($orderBy) == '')
                                            $orderBy = "{$wpdb->posts}.menu_order " . $order;
                                        else
                                            $orderBy = "{$wpdb->posts}.menu_order". $order .", " . $orderBy;
                                    }
                            }

                    return($orderBy);
                }
            
            
            
            /**
            * Show not configured notive
            *     
            */
            function admin_configure_notices()
                {
                    if (isset($_POST['form_submit']))
                        return;
                        
                    ?>
                        <div class="error fade">
                            <p><strong><?php _e('Post Types Order must be configured. Please go to', 'post-types-order') ?> <a href="<?php echo get_admin_url() ?>options-general.php?page=cpto-options"><?php _e('Settings Page', 'post-types-order') ?></a> <?php _e('make the configuration and save', 'post-types-order') ?></strong></p>
                        </div>
                    <?php
                }
            
            
            /**
            * Plugin options menu
            * 
            */
            function plugin_options_menu()
                {
                    
                    include (CPTPATH . '/include/class.options.php');
                    
                    $options_interface  =    new CptoOptionsInterface();
                    $options_interface->check_options_update();
                    
                    add_options_page('Post Types Order', '<img class="menu_pto" src="'. CPTURL .'/images/menu-icon.png" alt="" />Post Types Order', 'manage_options', 'cpto-options', array($options_interface, 'plugin_options_interface'));
                    
                }    
            
                
            
            /**
            * Load archive drag&drop sorting dependencies
            * 
            * Since version 1.8.8
            */
            function archiveDragDrop()
                {
                    $options          =     $this->functions->get_options();
                    
                    //if functionality turned off, continue
                    if( $options['archive_drag_drop']   !=      '1')
                        return;
                    
                    //if adminsort turned off no need to continue
                    if( $options['adminsort']           !=      '1')
                        return;
                    
                    $screen = get_current_screen();
                        
                    //check if the right interface
                    if(!isset($screen->post_type)   ||  empty($screen->post_type))
                        return;
                        
                    //check if post type is sortable
                    if(isset($options['show_reorder_interfaces'][$screen->post_type]) && $options['show_reorder_interfaces'][$screen->post_type] != 'show')
                        return;
                    
                    //if is taxonomy term filter return
                    if(is_category()    ||  is_tax())
                        return;
                    
                    //return if use orderby columns
                    if (isset($_GET['orderby']) && $_GET['orderby'] !=  'menu_order')
                        return false;
                        
                    //return if post status filtering
                    if (isset($_GET['post_status']))
                        return false;
                        
                    //return if post author filtering
                    if (isset($_GET['author']))
                        return false;
                    
                    //load required dependencies
                    wp_enqueue_style('cpt-archive-dd', CPTURL . '/css/cpt-archive-dd.css');
                    
                    wp_enqueue_script('jquery');
                    wp_enqueue_script('jquery-ui-sortable');
                    wp_register_script('cpto', CPTURL . '/js/cpt.js', array('jquery')); 
                    
                    global $userdata;
                    
                    // Localize the script with new data
                    $CPTO_variables = array(
                                                'archive_sort_nonce' => wp_create_nonce( 'CPTO_archive_sort_nonce_' . $userdata->ID)
                                            );
                    wp_localize_script( 'cpto', 'CPTO', $CPTO_variables );

                    // Enqueued script with localized data.
                    wp_enqueue_script( 'cpto' );   
                    
                }    
            
            function registerFiles() 
                {
                    if ( $this->current_post_type != null ) 
                        {
                            wp_enqueue_script('jQuery');
                            wp_enqueue_script('jquery-ui-sortable');
                        }
                        
                    wp_register_style('CPTStyleSheets', CPTURL . '/css/cpt.css');
                    wp_enqueue_style( 'CPTStyleSheets');
                }
            
            function admin_init() 
                {
                    if ( isset($_GET['page']) && substr($_GET['page'], 0, 17) == 'order-post-types-' ) 
                        {
                            $this->current_post_type = get_post_type_object(str_replace( 'order-post-types-', '', $_GET['page'] ));
                            if ( $this->current_post_type == null) 
                                {
                                    wp_die('Invalid post type');
                                }
                        }
                        
                    //add compatibility filters and code
                    include_once(CPTPATH . '/compatibility/LiteSpeed_Cache.php');
                    
                }
            
            
            /**
            * Save the order set through separate interface
            * 
            */
            function saveAjaxOrder() 
                {
                    
                    set_time_limit(600);
                    
                    global $wpdb;
                    
                    $nonce      =   $_POST['interface_sort_nonce'];
                    
                    //verify the nonce
                    if (! wp_verify_nonce( $nonce, 'interface_sort_nonce') )
                        die();
                    
                    parse_str($_POST['order'], $data);
                    
                    if (is_array($data))
                        {
                            foreach($data as $key => $values ) 
                                {
                                    if ( $key == 'item' ) 
                                        {
                                            foreach( $values as $position => $id ) 
                                                {
                                                    
                                                    //sanitize
                                                    $id =   (int)$id;
                                                    
                                                    $data = array('menu_order' => $position);
                                                    $data = apply_filters('post-types-order_save-ajax-order', $data, $key, $id);
                                                    
                                                    $wpdb->update( $wpdb->posts, $data, array('ID' => $id) );
                                                } 
                                        } 
                                    else 
                                        {
                                            foreach( $values as $position => $id ) 
                                                {
                                                    
                                                    //sanitize
                                                    $id =   (int)$id;
                                                    
                                                    $data = array('menu_order' => $position, 'post_parent' => str_replace('item_', '', $key));
                                                    $data = apply_filters('post-types-order_save-ajax-order', $data, $key, $id);
                                                    
                                                    $wpdb->update( $wpdb->posts, $data, array('ID' => $id) );
                                                }
                                        }
                                }
                            
                        }
                        
                    //trigger action completed
                    do_action('PTO/order_update_complete');
                }
                
                
            /**
            * Save the order set throgh the Archive 
            * 
            */
            function saveArchiveAjaxOrder()
                {
                    
                    set_time_limit(600);
                    
                    global $wpdb, $userdata;
                    
                    $post_type  =   filter_var ( $_POST['post_type'], FILTER_SANITIZE_STRING);
                    $paged      =   filter_var ( $_POST['paged'], FILTER_SANITIZE_NUMBER_INT);
                    $nonce      =   $_POST['archive_sort_nonce'];
                    
                    //verify the nonce
                    if (! wp_verify_nonce( $nonce, 'CPTO_archive_sort_nonce_' . $userdata->ID ) )
                        die();
                    
                    parse_str($_POST['order'], $data);
                    
                    if (!is_array($data)    ||  count($data)    <   1)
                        die();
                    
                    //retrieve a list of all objects
                    $mysql_query    =   $wpdb->prepare("SELECT ID FROM ". $wpdb->posts ." 
                                                            WHERE post_type = %s AND post_status IN ('publish', 'pending', 'draft', 'private', 'future')
                                                            ORDER BY menu_order, post_date DESC", $post_type);
                    $results        =   $wpdb->get_results($mysql_query);
                    
                    if (!is_array($results)    ||  count($results)    <   1)
                        die();
                    
                    //create the list of ID's
                    $objects_ids    =   array();
                    foreach($results    as  $result)
                        {
                            $objects_ids[]  =   (int)$result->ID;   
                        }
                    
                    global $userdata;
                    $objects_per_page   =   get_user_meta($userdata->ID ,'edit_' .  $post_type  .'_per_page', TRUE);
                    if(empty($objects_per_page))
                        $objects_per_page   =   20;
                    
                    $edit_start_at      =   $paged  *   $objects_per_page   -   $objects_per_page;
                    $index              =   0;
                    for($i  =   $edit_start_at; $i  <   ($edit_start_at +   $objects_per_page); $i++)
                        {
                            if(!isset($objects_ids[$i]))
                                break;
                                
                            $objects_ids[$i]    =   (int)$data['post'][$index];
                            $index++;
                        }
                    
                    //update the menu_order within database
                    foreach( $objects_ids as $menu_order   =>  $id ) 
                        {
                            $data = array(
                                            'menu_order' => $menu_order
                                            );
                            $data = apply_filters('post-types-order_save-ajax-order', $data, $menu_order, $id);
                            
                            $wpdb->update( $wpdb->posts, $data, array('ID' => $id) );
                        }
                        
                    //trigger action completed
                    do_action('PTO/order_update_complete');
                                    
                }
            

            function addMenu() 
                {
                    global $userdata;
                    //put a menu for all custom_type
                    $post_types = get_post_types();
                    
                    $options          =     $this->functions->get_options();
                    //get the required user capability
                    $capability = '';
                    if(isset($options['capability']) && !empty($options['capability']))
                        {
                            $capability = $options['capability'];
                        }
                    else if (is_numeric($options['level']))
                        {
                            $capability = $this->functions->userdata_get_user_level();
                        }
                        else
                            {
                                $capability = 'manage_options';  
                            }
                    
                    foreach( $post_types as $post_type_name ) 
                        {
                            if ($post_type_name == 'page')
                                continue;
                                
                            //ignore bbpress
                            if ($post_type_name == 'reply' || $post_type_name == 'topic')
                                continue;
                            
                            if(is_post_type_hierarchical($post_type_name))
                                continue;
                                
                            $post_type_data = get_post_type_object( $post_type_name );
                            if($post_type_data->show_ui === FALSE)
                                continue;
                                
                            if(isset($options['show_reorder_interfaces'][$post_type_name]) && $options['show_reorder_interfaces'][$post_type_name] != 'show')
                                continue;
                            
                            if ($post_type_name == 'post')
                                add_submenu_page('edit.php', __('Re-Order', 'post-types-order'), __('Re-Order', 'post-types-order'), $capability, 'order-post-types-'.$post_type_name, array(&$this, 'SortPage') );
                            elseif ($post_type_name == 'attachment') 
                                add_submenu_page('upload.php', __('Re-Order', 'post-types-order'), __('Re-Order', 'post-types-order'), $capability, 'order-post-types-'.$post_type_name, array(&$this, 'SortPage') ); 
                            else
                                {
                                    add_submenu_page('edit.php?post_type='.$post_type_name, __('Re-Order', 'post-types-order'), __('Re-Order', 'post-types-order'), $capability, 'order-post-types-'.$post_type_name, array(&$this, 'SortPage') );    
                                }
                        }
                }
            

            function SortPage() 
                {
                    ?>
                    <div id="cpto" class="wrap">
                        <div class="icon32" id="icon-edit"><br></div>
                        <h2><?php echo $this->current_post_type->labels->singular_name . ' -  '. __('Re-Order', 'post-types-order') ?></h2>

                        <?php $this->functions->cpt_info_box(); ?>  
                        
                        <div id="ajax-response"></div>
                        
                        <noscript>
                            <div class="error message">
                                <p><?php _e('This plugin can\'t work without javascript, because it\'s use drag and drop and AJAX.', 'post-types-order') ?></p>
                            </div>
                        </noscript>
                        
                        <div id="order-post-type">
                            <ul id="sortable">
                                <?php $this->listPages('hide_empty=0&title_li=&post_type='.$this->current_post_type->name); ?>
                            </ul>
                            
                            <div class="clear"></div>
                        </div>
                        
                        <p class="submit">
                            <a href="javascript: void(0)" id="save-order" class="button-primary"><?php _e('Update', 'post-types-order' ) ?></a>
                        </p>
                        
                        <?php wp_nonce_field( 'interface_sort_nonce', 'interface_sort_nonce' ); ?>
                        
                        <script type="text/javascript">
                            jQuery(document).ready(function() {
                                jQuery("#sortable").sortable({
                                    'tolerance':'intersect',
                                    'cursor':'pointer',
                                    'items':'li',
                                    'placeholder':'placeholder',
                                    'nested': 'ul'
                                });
                                
                                jQuery("#sortable").disableSelection();
                                jQuery("#save-order").bind( "click", function() {
                                    
                                    jQuery("html, body").animate({ scrollTop: 0 }, "fast");
                                    
                                    jQuery.post( ajaxurl, { action:'update-custom-type-order', order:jQuery("#sortable").sortable("serialize"), 'interface_sort_nonce' : jQuery('#interface_sort_nonce').val() }, function() {
                                        jQuery("#ajax-response").html('<div class="message updated fade"><p><?php _e('Items Order Updated', 'post-types-order') ?></p></div>');
                                        jQuery("#ajax-response div").delay(3000).hide("slow");
                                    });
                                });
                            });
                        </script>
                        
                    </div>
                    <?php
                }

            function listPages($args = '') 
                {
                    $defaults = array(
                        'depth'             => -1, 
                        'date_format'       => get_option('date_format'),
                        'child_of'          => 0, 
                        'sort_column'       => 'menu_order',
                        'post_status'       =>  'any' 
                    );

                    $r = wp_parse_args( $args, $defaults );
                    extract( $r, EXTR_SKIP );

                    $output = '';

                    $r['exclude'] = implode( ',', apply_filters('wp_list_pages_excludes', array()) );

                    // Query pages.
                    $r['hierarchical'] = 0;
                    $args = array(
                                'sort_column'       =>  'menu_order',
                                'post_type'         =>  $post_type,
                                'posts_per_page'    => -1,
                                'post_status'       =>  'any',
                                'orderby'            => array(
                                                            'menu_order'    => 'ASC',
                                                            'post_date'     =>  'DESC'
                                                            )
                    );
                    
                    $the_query  = new WP_Query($args);
                    $pages      = $the_query->posts;

                    if ( !empty($pages) ) 
                        {
                            $output .= $this->walkTree($pages, $r['depth'], $r);
                        }

                    $output = apply_filters('wp_list_pages', $output, $r);

                    echo $output;
                }
            
            function walkTree($pages, $depth, $r) 
                {
                    $walker = new Post_Types_Order_Walker;

                    $args = array($pages, $depth, $r);
                    return call_user_func_array(array(&$walker, 'walk'), $args);
                }
        }
   



?>