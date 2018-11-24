<?php
/**
 * Class to provide the routine for the own User Role Editor user capabilities list
 *
 * @package    User-Role-Editor
 * @subpackage Admin
 * @author     Vladimir Garagulya <support@role-editor.com>
 * @copyright  Copyright (c) 2010 - 2016, Vladimir Garagulya
 **/
class URE_Own_Capabilities {
    const URE_SETTINGS_CAP_TR = 'ure_settings_cap';

    
    public static function get_caps() {
        
        $lib = URE_Lib::get_instance();
        
        $ure_caps = array(
            'ure_edit_roles' => 1,
            'ure_create_roles' => 1,
            'ure_delete_roles' => 1,
            'ure_create_capabilities' => 1,
            'ure_delete_capabilities' => 1,
            'ure_manage_options' => 1,
            'ure_reset_roles' => 1
        );        
                
        if ($lib->is_pro()) {                                    
            $ure_caps['ure_export_roles'] = 1;
            $ure_caps['ure_import_roles'] = 1;
            $ure_caps['ure_admin_menu_access'] = 1;
            $ure_caps['ure_widgets_access'] = 1;
            $ure_caps['ure_widgets_show_access'] = 1;
            $ure_caps['ure_meta_boxes_access'] = 1;
            $ure_caps['ure_other_roles_access'] = 1;
            $ure_caps['ure_edit_posts_access'] = 1;
            $ure_caps['ure_plugins_activation_access'] = 1;   
            $ure_caps['ure_view_posts_access'] = 1;   
            $ure_caps['ure_front_end_menu_access'] = 1;   
            $multisite = $lib->get('multisite');
            if ($multisite) {
                $ure_caps['ure_themes_access'] = 1;
            }
        }             
        
        return $ure_caps;
    }
    // end of get_caps()
        
    
    /**
     * return key capability to have access to User Role Editor Plugin
     */
    public static function get_key_capability() {
        
        $lib = URE_Lib::get_instance();
        $key_cap = $lib->get('key_capability');
        
        if (!empty($key_cap)) {
            return $key_cap;
        }
        
        $multisite = $lib->get('multisite');
        if (!$multisite) {
            $key_cap = URE_KEY_CAPABILITY;
        } else {
            $enable_simple_admin_for_multisite = $lib->get_option('enable_simple_admin_for_multisite', 0);
            if ( (defined('URE_ENABLE_SIMPLE_ADMIN_FOR_MULTISITE') && URE_ENABLE_SIMPLE_ADMIN_FOR_MULTISITE == 1) || 
                 $enable_simple_admin_for_multisite) {
                $key_cap = URE_KEY_CAPABILITY;
            } else {
                $key_cap = 'manage_network_plugins';
            }
        }        
        $lib->set('key_capability', $key_cap);
                
        return $key_cap;
    }
    // end of get_key_capability()
    
    
    /**
     * Return user capability for the User Role Editor Settings page
     * 
     * @return string
     */
    public static function get_settings_capability() {
        
        $lib = URE_Lib::get_instance();
        $settings_cap = $lib->get('settings_capability');
        if (!empty($settings_cap)) {
            return $settings_cap;
        }
                
        $multisite = $lib->get('multisite');
        if (!$multisite) {
            $settings_cap = 'ure_manage_options';
        } else {
            $enable_simple_admin_for_multisite = $lib->get_option('enable_simple_admin_for_multisite', 0);
            if ((defined('URE_ENABLE_SIMPLE_ADMIN_FOR_MULTISITE') && URE_ENABLE_SIMPLE_ADMIN_FOR_MULTISITE == 1) || 
                $enable_simple_admin_for_multisite) {
                $settings_cap = 'ure_manage_options';
            } else {
                $settings_cap = self::get_key_capability();
            }
        }
        $lib->set('settings_capability', $settings_cap);
        
        return $settings_cap;
    }
    // end of get_settings_capability()

    
    public static function init_caps() {
        global $wp_roles;
        
        if (!isset($wp_roles)) {
            $wp_roles = new WP_Roles();
        }
        
        if (!isset($wp_roles->roles['administrator'])) {
            return;
        }
        
        $lib = URE_Lib::get_instance();
        $multisite = $lib->get('multisite');
        // Do not turn on URE caps for local administrator by default under multisite, as there is a superadmin.
        $turn_on = !$multisite;   
        
        $old_use_db = $wp_roles->use_db;
        $wp_roles->use_db = true;
        $administrator = $wp_roles->role_objects['administrator'];
        $ure_caps = self::get_caps();
        foreach(array_keys($ure_caps) as $cap) {
            if (!$administrator->has_cap($cap)) {
                $administrator->add_cap($cap, $turn_on);
            }
        }
        $wp_roles->use_db = $old_use_db;
    }
    // end of init_caps()
    
    
    /**
     * Return list of URE capabilities with data about groups they were included
     * 
     * @return array
     */
    public static function get_caps_groups() {
        
        $ure_caps = self::get_caps();
        
        $caps = array();
        foreach($ure_caps as $ure_cap=>$value) {
            $caps[$ure_cap] = array('custom', 'user_role_editor');
        }        
        
        return $caps;        
    }
    // end of get_caps_groups()

}
// end of URE_Capabilities class