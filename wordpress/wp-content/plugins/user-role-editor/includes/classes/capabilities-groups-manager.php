<?php
/**
 * Class to group user capabilities for better structuring
 *
 * @package    User-Role-Editor
 * @subpackage Admin
 * @author     Vladimir Garagulya <support@role-editor.com>
 * @copyright  Copyright (c) 2010 - 2016, Vladimir Garagulya
 **/

class URE_Capabilities_Groups_Manager {

    private static $instance = null;
    private $lib = null;
    private $groups = null;
    private $built_in_wp_caps = null;
    private $cpt_caps = null;
    
    
    public static function get_instance() {
        
        if (self::$instance === null) {            
            // new static() will work too
            self::$instance = new URE_Capabilities_Groups_Manager();
        }

        return self::$instance;
    }
    // end of get_instance()
    
    
    private function __construct() {
        
        $this->lib = URE_Lib::get_instance();
        $this->_get_built_in_wp_caps();
        $this->_get_all_custom_post_types_caps();
        
    }
    // end of __construct()
    
    
    public function add_custom_post_types() {
                
        $this->groups['custom_post_types'] = array('caption'=>esc_html__('Custom Post Types', 'user-role-editor'), 'parent'=>'all', 'level'=>1);
        
        $post_types = get_post_types(array(), 'objects');
        $_post_types = $this->lib->_get_post_types();
        $built_in_pt = array('post', 'page');
        if ($post_types['attachment']->cap->edit_posts=='edit_posts') {
            $built_in_pt[] = 'attachment';
        }

        $show_wc_post_types_under_wc_only = apply_filters('ure_show_wc_post_types_under_wc_only', false);
        foreach($post_types as $post_type) {
            if (!isset($_post_types[$post_type->name]) || in_array($post_type->name, $built_in_pt)) {
                continue;
            }
            if ($show_wc_post_types_under_wc_only && in_array($post_type->name, URE_Woocommerce_Capabilities::$post_types)) {
                continue;
            }
            /*
            if (!$post_type->public) {
                continue;
            }
             * 
             */
            $this->groups[$post_type->name] = array('caption'=>$post_type->labels->name, 'parent'=>'custom_post_types', 'level'=>2);
        }
    }
    // end of add_custom_post_types()
    
    
    private function add_ure_group() {
        
        $this->groups['user_role_editor'] = array('caption'=>esc_html__('User Role Editor', 'user-role-editor'), 'parent'=>'custom', 'level'=>2);
        
    }
    // end of get_ure_group()
    
    
    private function add_woocommerce_groups() {
        
        $full_caps = $this->lib->get('full_capabilities');
        if (!isset($full_caps['manage_woocommerce'])) {
            return;
        }
        
        $post_types = get_post_types(array(), 'objects');
        
        $this->groups['woocommerce'] = array('caption'=>esc_html__('WooCommerce', 'user-role-editor'), 'parent'=>'custom', 'level'=>2);
        $this->groups['woocommerce_core'] = array('caption'=>esc_html__('Core', 'user-role-editor'), 'parent'=>'woocommerce', 'level'=>3);
        foreach(URE_Woocommerce_Capabilities::$post_types as $post_type) {
            if (!isset($post_types[$post_type])) {
                continue;                
            }    
            $this->groups['woocommerce_'. $post_type] = array('caption'=>$post_types[$post_type]->labels->name, 'parent'=>'woocommerce', 'level'=>3);
        }
        
    }
    // end of add_woocommerce_group()
    
    
    public function get_groups_tree() {
        
        if ($this->groups!==null) {
            return $this->groups;
        }        
        
        $this->groups = array(
            'all'=>array('caption'=>esc_html__('All', 'user-role-editor'), 'parent'=>null, 'level'=>0),
            'core'=>array('caption'=>esc_html__('Core', 'user-role-editor'), 'parent'=>'all', 'level'=>1),
            'general'=>array('caption'=>esc_html__('General', 'user-role-editor'), 'parent'=>'core', 'level'=>2),
            'themes'=>array('caption'=>esc_html__('Themes', 'user-role-editor'), 'parent'=>'core', 'level'=>2),
            'posts'=>array('caption'=>esc_html__('Posts', 'user-role-editor'), 'parent'=>'core', 'level'=>2),
            'pages'=>array('caption'=>esc_html__('Pages', 'user-role-editor'), 'parent'=>'core', 'level'=>2),
            'plugins'=>array('caption'=>esc_html__('Plugins', 'user-role-editor'), 'parent'=>'core', 'level'=>2),
            'users'=>array('caption'=>esc_html__('Users', 'user-role-editor'), 'parent'=>'core', 'level'=>2)            
        );
        $multisite = $this->lib->get('multisite');
        if ($multisite) {
            $this->groups['multisite'] = array('caption'=>esc_html__('Multisite', 'user-role-editor'), 'parent'=>'core', 'level'=>2);
        }
        $this->groups['deprecated'] = array('caption'=>esc_html__('Deprecated', 'user-role-editor'), 'parent'=>'core', 'level'=>2);
        
        $this->add_custom_post_types();                                
        $this->groups['custom'] = array('caption'=>esc_html__('Custom capabilities', 'user-role-editor'), 'parent'=>'all', 'level'=>1);
        $this->add_ure_group();
        $this->add_woocommerce_groups();
        
        $this->groups = apply_filters('ure_capabilities_groups_tree', $this->groups);        
        
        return $this->groups;
    }
    // end of get_groups_tree()
    
    
    /**
     * return array of built-in WP capabilities (WP 3.1 wp-admin/includes/schema.php) 
     * 
     * @return array 
     */
    private function _get_built_in_wp_caps() {

        $wp_version = get_bloginfo('version');
        $multisite = $this->lib->get('multisite');
        
        $caps = array();
        $caps['switch_themes'] = array('core', 'themes');
        $caps['edit_themes'] = array('core', 'themes');
        $caps['activate_plugins'] = array('core', 'plugins');
        $caps['edit_plugins'] = array('core', 'plugins');
        $caps['edit_users'] = array('core', 'users');
        $caps['edit_files'] = array('core', 'deprecated');
        $caps['manage_options'] = array('core', 'general');
        $caps['moderate_comments'] = array('core', 'posts', 'general');
        $caps['manage_categories'] = array('core', 'posts', 'general');
        $caps['manage_links'] = array('core', 'general');
        $caps['upload_files'] = array('core', 'general'); 
        $caps['import'] = array('core', 'general');
        $caps['unfiltered_html'] = array('core','general');
        if ($multisite) {
            $caps['unfiltered_html'] = array('deprecated');
        }
        $caps['edit_posts'] = array('core', 'posts');
        $caps['edit_others_posts'] = array('core', 'posts');
        $caps['edit_published_posts'] = array('core', 'posts');
        $caps['publish_posts'] = array('core', 'posts');
        $caps['edit_pages'] = array('core', 'pages');
        $caps['read'] = array('core', 'general');
        $caps['level_10'] = array('core', 'deprecated');
        $caps['level_9'] = array('core', 'deprecated');
        $caps['level_8'] = array('core', 'deprecated');
        $caps['level_7'] = array('core', 'deprecated');
        $caps['level_6'] = array('core', 'deprecated');
        $caps['level_5'] = array('core', 'deprecated');
        $caps['level_4'] = array('core', 'deprecated');
        $caps['level_3'] = array('core', 'deprecated');
        $caps['level_2'] = array('core', 'deprecated');
        $caps['level_1'] = array('core', 'deprecated');
        $caps['level_0'] = array('core', 'deprecated');
        $caps['edit_others_pages'] = array('core', 'pages');
        $caps['edit_published_pages'] = array('core', 'pages');
        $caps['publish_pages'] = array('core', 'pages');
        $caps['delete_pages'] = array('core', 'pages');
        $caps['delete_others_pages'] = array('core', 'pages');
        $caps['delete_published_pages'] = array('core', 'pages');
        $caps['delete_posts'] = array('core', 'posts');
        $caps['delete_others_posts'] = array('core', 'posts');
        $caps['delete_published_posts'] = array('core', 'posts');
        $caps['delete_private_posts'] = array('core', 'posts');
        $caps['edit_private_posts'] = array('core', 'posts');
        $caps['read_private_posts'] = array('core', 'posts');
        $caps['delete_private_pages'] = array('core', 'pages');
        $caps['edit_private_pages'] = array('core', 'pages');
        $caps['read_private_pages'] = array('core', 'pages');
        $caps['unfiltered_upload'] = array('core', 'general');
        $caps['edit_dashboard'] = array('core', 'general');
        $caps['update_plugins'] = array('core', 'plugins');
        $caps['delete_plugins'] = array('core', 'plugins');
        $caps['install_plugins'] = array('core', 'plugins');
        $caps['update_themes'] = array('core', 'themes');
        $caps['install_themes'] = array('core', 'themes');
        $caps['update_core'] = array('core', 'general');
        $caps['list_users'] = array('core', 'users');
        $caps['remove_users'] = array('core', 'users');
                
        if (version_compare($wp_version, '4.4', '<')) {
            $caps['add_users'] = array('core', 'users');  // removed from WP v. 4.4.
        }
        
        $caps['promote_users'] = array('core', 'users');
        $caps['edit_theme_options'] = array('core', 'themes');
        $caps['delete_themes'] = array('core', 'themes');
        $caps['export'] = array('core', 'general');
        $caps['delete_users'] = array('core', 'users');
        $caps['create_users'] = array('core', 'users');
        if ($multisite) {
            $caps['create_sites'] = array('core', 'multisite', 'general');
            $caps['delete_sites'] = array('core', 'multisite', 'general');
            $caps['manage_network'] = array('core', 'multisite', 'general');
            $caps['manage_sites'] = array('core', 'multisite', 'general');            
            $caps['manage_network_users'] = array('core', 'multisite', 'users');
            $caps['manage_network_plugins'] = array('core', 'multisite', 'plugins');
            $caps['manage_network_themes'] = array('core', 'multisite', 'themes');           
            $caps['manage_network_options'] = array('core', 'multisite', 'general');
            $caps['upgrade_network'] = array('core', 'multisite', 'general');
        }
                
        $caps = apply_filters('ure_built_in_wp_caps', $caps);
        
        $this->built_in_wp_caps = $caps;
        
        return $this->built_in_wp_caps;
    }
    // end of _get_built_in_wp_caps()
    
    
    public function get_built_in_wp_caps() {
        
        return $this->built_in_wp_caps;
    }
    // end of get_built_in_wp_caps()

    
    private function get_post_types_without_caps() {
        $pt_without_caps = array();
        $wc_pts = URE_Woocommerce_Capabilities::get_post_types_without_caps();
        
        $pt_without_caps = array_merge($pt_without_caps, $wc_pts);
        
        return $pt_without_caps;
    }
    // end of get_post_types_without_caps()
    
    
    /**
     * Get capabilities registered with custom post type
     * 
     * @param object $post_type
     * @param array $post_edit_caps
     */
    private function get_registered_cpt_caps($post_type, $post_edit_caps) {
        foreach ($post_edit_caps as $capability) {
            if (isset($post_type->cap->$capability)) {
                $cap = $post_type->cap->$capability;
            } else {
                continue;
            }
            if (isset($this->cpt_caps[$cap])) {
                continue;
            }
            $this->cpt_caps[$cap] = array();
            if (!isset($this->built_in_wp_caps[$cap])) {
                $this->cpt_caps[$cap][] = 'custom';
            }
            $this->cpt_caps[$cap][] = 'custom_post_types';
            $this->cpt_caps[$cap][] = $post_type->name;                        
        }
    }
    // end of get_registered_cpt_caps()


    private function add_group_to_edit_post_cap($post_type, $post_edit_caps) {
        
        foreach($post_edit_caps as $cap_id) {
            $this->built_in_wp_caps[$cap_id][] = $post_type->name;
            if (!in_array('custom_post_types', $this->built_in_wp_caps[$cap_id])) {
                $this->built_in_wp_caps[$cap_id][] = 'custom_post_types';
            }
        }
        
    }
    // end of add_group_to_edit_post_cap()
    
    
    private function get_custom_post_type_caps($post_type, $post_edit_caps) {
        $pt_without_caps = $this->get_post_types_without_caps();
        if (in_array($post_type->name, $pt_without_caps)) {  
            return;
        }
        
        // take into account custom post types, which uses built-in post or page capabilities
        if (in_array($post_type->capability_type, array('post', 'page'))) {
            $this->add_group_to_edit_post_cap($post_type, $post_edit_caps);
            return;
        }
        
        $this->get_registered_cpt_caps($post_type, $post_edit_caps);
    }
    // end of get_custom_post_type_caps()
    
    
    private function _get_all_custom_post_types_caps() {
        
        $post_edit_caps = $this->lib->get_edit_post_capabilities();
        $post_types = get_post_types(array(), 'objects');
        $_post_types = $this->lib->_get_post_types();
        $built_in_pt = array('post', 'page');
        if ($post_types['attachment']->cap->edit_posts=='edit_posts') {
            $built_in_pt[] = 'attachment';
        }
        $this->cpt_caps = array();
        foreach($post_types as $post_type) {
            if (!isset($_post_types[$post_type->name])) {
                continue;
            }
            if (in_array($post_type->name, $built_in_pt)) {
                continue;
            }
            if (!isset($post_type->cap)) {
                continue;
            }
            $this->get_custom_post_type_caps($post_type, $post_edit_caps);
        }
        
        return $this->cpt_caps;
    }
    // end of _get_all_custom_post_types_capabilities()
                
    
    private function get_groups_for_custom_cap($cap_id) {
        
        $ure_caps = URE_Own_Capabilities::get_caps_groups();
        if (isset($ure_caps[$cap_id])) {
            $groups1 = $ure_caps[$cap_id];
        }
        
        if (empty($groups1)) {
            $wc_caps = URE_Woocommerce_Capabilities::get_caps_groups();
            if (isset($wc_caps[$cap_id])) {
                $groups1 = $wc_caps[$cap_id];
            }
        }
        
        if (isset($this->cpt_caps[$cap_id])) {
            $groups2 = $this->cpt_caps[$cap_id];
        }
        
        $groups = array('custom');
        if (!empty($groups1)) {
            $groups = array_merge($groups, $groups1);
        }
        if (!empty($groups2)) {
            $groups = array_merge($groups, $groups2);
        }
        
        return $groups;
    }
    // end of get_groups_for_custom_cap()
    
    
    public function get_cap_groups($cap_id, $built_in_wp_caps=null) {
                
        if (isset($this->built_in_wp_caps[$cap_id])) {
            $groups = $built_in_wp_caps[$cap_id]; 
        } else {
            $groups = $this->get_groups_for_custom_cap($cap_id);
        } 
        $groups = apply_filters('ure_custom_capability_groups', $groups, $cap_id);        
        $groups[] = 'all'; // Every capability belongs to the 'all' group        
        $groups = array_unique($groups);
        
        foreach($groups as $key=>$value) {
            $groups[$key] = 'ure-'. $value;            
        }
        
        return $groups;
    }
    // end of get_cap_groups()
        
}
// end of class URE_Capabilities_Groups_Manager