<?php
/**
 * Class to work with user capability
 *
 * @package    User-Role-Editor
 * @subpackage Admin
 * @author     Vladimir Garagulya <support@role-editor.com>
 * @copyright  Copyright (c) 2010 - 2016, Vladimir Garagulya
 **/

class URE_Capability {

    const SPACE_REPLACER = '_URE-SR_';
    const SLASH_REPLACER = '_URE-SLR_';
    const VERT_LINE_REPLACER = '_URE-VLR_';

    
    public static function escape($cap_id) {
        
        $search = array(' ', '/', '|');
        $replace = array(self::SPACE_REPLACER, self::SLASH_REPLACER, self::VERT_LINE_REPLACER);
        
        $cap_id_esc = str_replace($search, $replace, $cap_id);
                
        return $cap_id_esc;
    }
    // end escape()

    
    // sanitize user input for security
    // do not allow to use internally used capabilities
    public static function validate($cap_id_raw) {
        $match = array();
        $found = preg_match('/[A-Za-z0-9_\-]*/', $cap_id_raw, $match);
        if (!$found || ($found && ($match[0]!=$cap_id_raw))) { // some non-alphanumeric charactes found!    
            $data = array(
                'result'=>false, 
                'message'=>esc_html__('Error: Capability name must contain latin characters and digits only!', 'user-role-editor'),
                'cap_id'=>'');
            return $data;
        } 
        
        $cap_id = strtolower($match[0]);
        if ($cap_id=='do_not_allow') {
            $data = array(
                'result'=>false, 
                'message'=>esc_html__('Error: this capability is used internally by WordPress', 'user-role-editor'),
                'cap_id'=>'do_not_allow');
            return $data;
        }
        
        $data = array(
            'result'=>true, 
            'message'=>'Success',
            'cap_id'=>$cap_id);
        
        return $data;
    }
    // end of validate()
    
    
    /**
     * Add new user capability
     * 
     * @global WP_Roles $wp_roles
     * @return string
     */
    public static function add() {
        global $wp_roles;

        if (!current_user_can('ure_create_capabilities')) {
            return esc_html__('Insufficient permissions to work with User Role Editor','user-role-editor');
        }
        
        $mess = '';
        if (!isset($_POST['capability_id']) || empty($_POST['capability_id'])) {
            return 'Wrong Request';
        }
        
        $data = self::validate($_POST['capability_id']);                
        if (!$data['result']) {
            return $data['message'];
        }
        
        $cap_id = $data['cap_id'];                
        $lib = URE_Lib::get_instance();
        $lib->get_user_roles();
        $lib->init_full_capabilities();
        $full_capabilities = $lib->get('full_capabilities');
        if (!isset($full_capabilities[$cap_id])) {
            $admin_role = $lib->get_admin_role();            
            $wp_roles->use_db = true;
            $wp_roles->add_cap($admin_role, $cap_id);
            $mess = sprintf(esc_html__('Capability %s was added successfully', 'user-role-editor'), $cap_id);
        } else {
            $mess = sprintf(esc_html__('Capability %s exists already', 'user-role-editor'), $cap_id);
        }
        
        return $mess;
    }
    // end of add()
    
    
    /**
     * Extract capabilities selected from deletion from the $_POST global
     * 
     * @return array
     */
    private static function get_caps_for_deletion_from_post($caps_allowed_to_remove) {
    
        $caps = array();
        foreach($_POST as $key=>$value) {
            if (substr($key, 0, 3)!=='rm_') {
                continue;
            }
            if (!isset($caps_allowed_to_remove[$value])) {
                continue;
            }
            $caps[] = $value;
        }
        
        return $caps;
    }
    // end of get_caps_for_deletion_from_post()
    
    
        
    private static function revoke_caps_from_user($user_id, $caps) {
        $user = get_user_to_edit($user_id);
        foreach($caps as $cap_id) {
            if (isset($user->caps[$cap_id])) {
                $user->remove_cap($cap_id);
            }
        }
    }
    // end of revoke_caps_from_user()
    
    
    private static function revoke_caps_from_role($wp_role, $caps) {
        foreach($caps as $cap_id) {
            if ($wp_role->has_cap($cap_id)) {
                $wp_role->remove_cap($cap_id);
            }
        }
    }
    // end of revoke_caps_from_role()
    
    
    private static function revoke_caps($caps) {
        global $wpdb, $wp_roles;
        
        // remove caps from users
        $users_ids = $wpdb->get_col("SELECT $wpdb->users.ID FROM $wpdb->users");
        foreach ($users_ids as $user_id) {
            self::revoke_caps_from_user($user_id, $caps);
        }

        // remove caps from roles
        foreach ($wp_roles->role_objects as $wp_role) {
            self::revoke_caps_from_role($wp_role, $caps);            
        }        
    }
    // end of revoke_caps()
    
            
    /**
     * Delete capability
     * 
     * @global WP_Roles $wp_roles
     * @return string - information message
     */
    public static function delete() {        
        
        if (!isset($_POST['action']) || $_POST['action']!='delete-user-capability') {
            return 'Wrong Request';
        }
        
        if (!current_user_can('ure_delete_capabilities')) {
            return esc_html__('Insufficient permissions to work with User Role Editor','user-role-editor');
        }
                        
        $lib = URE_Lib::get_instance();
        $mess = '';                
        $caps_allowed_to_remove = $lib->get_caps_to_remove();
        if (!is_array($caps_allowed_to_remove) || count($caps_allowed_to_remove) == 0) {
            return esc_html__('There are no capabilities available for deletion!', 'user-role-editor');
        }
        
        $capabilities = self::get_caps_for_deletion_from_post($caps_allowed_to_remove);
        if (empty($capabilities)) {
            return esc_html__('There are no capabilities available for deletion!', 'user-role-editor');
        }

        self::revoke_caps($capabilities);        
        
        if (count($capabilities)==1) {
            $mess = sprintf(esc_html__('Capability %s was removed successfully', 'user-role-editor'), $capabilities[0]);
        } else {
            $short_list_str = $lib->get_short_list_str($capabilities);
            $mess = count($capabilities) .' '. esc_html__('capabilities were removed successfully', 'user-role-editor') .': '. 
                    $short_list_str;
        }

        return $mess;
    }
    // end of delete()
    
}
// end of class URE_Capability
