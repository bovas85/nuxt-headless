<?php

/*
 * Main class of User Role Editor WordPress plugin
 * Author: Vladimir Garagulya
 * Author email: support@role-editor.com
 * Author URI: https://www.role-editor.com
 * License: GPL v2+
 * 
 */

class URE_Protect_Admin {
    
    private $lib = null;
    private $user_to_check = null;  // cached list of user IDs, who has Administrator role     	 
    
    public function __construct() {
        global $pagenow;
        
        $this->lib = URE_Lib::get_instance();
        $this->user_to_check = array();
        
        // Exclude administrator role from edit list.
        add_filter('editable_roles', array($this, 'exclude_admin_role'));
        if (in_array($pagenow, array('users.php', 'user-edit.php'))) {
            // prohibit any actions with user who has Administrator role
            add_filter('user_has_cap', array($this, 'not_edit_admin'), 10, 3);
        }
        // exclude users with 'Administrator' role from users list
        add_action('pre_user_query', array($this, 'exclude_administrators'));
        // do not show 'Administrator (s)' view above users list
        add_filter('views_users', array($this, 'exclude_admins_view'));       
    }
    // end of __construct()
    

    // apply protection to the user edit pages only
    protected function is_protection_applicable() {
        global $pagenow;
        
        $result = false;
        $pages_to_block = array('profile.php', 'users.php', 'user-new.php', 'user-edit.php');
        if (in_array($pagenow, $pages_to_block)) {
            $result = true;
        }
        
        return $result;
    }
    // end of is_protection_applicable()
    
    
    /**
     * exclude administrator role from the roles list
     * 
     * @param string $roles
     * @return array
     */
    public function exclude_admin_role($roles) {

        if ($this->is_protection_applicable() && isset($roles['administrator'])) {
            unset($roles['administrator']);
        }

        return $roles;
    }
    // end of exclude_admin_role()
    
    
        /**
     * Check if user has "Administrator" role assigned
     * 
     * @global wpdb $wpdb
     * @param int $user_id
     * @return boolean returns true is user has Role "Administrator"
     */
    private function has_administrator_role($user_id) {
        global $wpdb;

        if (empty($user_id) || !is_numeric($user_id)) {
            return false;
        }

        $meta_key = $wpdb->prefix .'capabilities';
        $query = $wpdb->prepare(
                "SELECT count(*)
                    FROM {$wpdb->usermeta}
                    WHERE user_id=%d AND meta_key=%s AND meta_value like %s", 
                array($user_id, $meta_key, '%administrator%'));
        $has_admin_role = $wpdb->get_var($query);
        if ($has_admin_role > 0) {
            $result = true;
        } else {
            $result = false;
        }
        // cache checking result for the future use
        $this->user_to_check[$user_id] = $result;

        return $result;
    }

    // end of has_administrator_role()
    
    
    /**
     * We have two vulnerable queries with user id at admin interface, which should be processed
     * 1st: http://blogdomain.com/wp-admin/user-edit.php?user_id=ID&wp_http_referer=%2Fwp-admin%2Fusers.php
     * 2nd: http://blogdomain.com/wp-admin/users.php?action=delete&user=ID&_wpnonce=ab34225a78
     * If put Administrator user ID into such request, user with lower capabilities (if he has 'edit_users')
     * can edit, delete admin record
     * This function removes 'edit_users' or 'delete_users' or 'remove_users' capability from current user capabilities,
     * if request sent against a user with 'administrator' role
     *
     * @param array $allcaps
     * @param type $caps
     * @param string $name
     * @return array
     */
    public function not_edit_admin($allcaps, $caps, $name) {
        
        if (is_array($caps) & count($caps)>0) {
            // 1st element of this array not always has index 0. Use workaround to extract it.
            $caps_v = array_values($caps);
            $cap = $caps_v[0];
        } else {
            $cap = $caps;
        }
        $checked_caps = array('edit_users', 'delete_users', 'remove_users');
        if (!in_array($cap, $checked_caps)) {
            return $allcaps;
        }
        
        $user_keys = array('user_id', 'user');
        foreach ($user_keys as $user_key) {
            $access_deny = false;
            $user_id = (int) $this->lib->get_request_var($user_key, 'get', 'int');
            if (empty($user_id)) {  // check the next key
                continue;
            }
            if ($user_id == 1) {  // built-in WordPress Admin
                $access_deny = true;
            } else {
                if (!isset($this->user_to_check[$user_id])) {
                    // check if user_id has Administrator role
                    $access_deny = $this->has_administrator_role($user_id);
                } else {
                    // user_id was checked already, get result from cash
                    $access_deny = $this->user_to_check[$user_id];
                }
            }
            if ($access_deny && isset($allcaps[$cap])) {
                unset($allcaps[$cap]);
                
            }
            break;            
        }

        return $allcaps;
    }
    // end of not_edit_admin()
    
    
    /**
     * add where criteria to exclude users with 'Administrator' role from users list
     * 
     * @global wpdb $wpdb
     * @param  type $user_query
     */
    public function exclude_administrators($user_query) {
        global $wpdb;
        
        if (!$this->is_protection_applicable()) { // block the user edit stuff only
            return;
        }

        // get user_id of users with 'Administrator' role  
        $current_user_id = get_current_user_id();
        $meta_key = $wpdb->prefix . 'capabilities';
        $query = $wpdb->prepare(
                    "SELECT user_id
                        FROM {$wpdb->usermeta}
                        WHERE user_id!=%d AND meta_key=%s AND meta_value like %s",
                      array($current_user_id, $meta_key, '%administrator%'));
        $ids_arr = $wpdb->get_col($query);
        if (is_array($ids_arr) && count($ids_arr) > 0) {
            $ids = implode(',', $ids_arr);
            $user_query->query_where .= " AND ( $wpdb->users.ID NOT IN ( $ids ) )";
        }
    }
    // end of exclude_administrators()

        
    private function extract_view_quantity($text) {
        $match = array();
        $result = preg_match('#\((.*?)\)#', $text, $match);
        if ($result) {
            $quantity = $match[1];
        } else {
            $quantity = 0;
        }
        
        return $quantity;
    }
    // end of extract_view_quantity()
    
    
    private function extract_int($str_val) {
        $str_val1 = str_replace(',', '', $str_val);  // remove ',' from numbers like '2,015'
        $int_val = (int) preg_replace('/[^\-\d]*(\-?\d*).*/','$1', $str_val1);  // extract numeric value strings like from '2015 bla-bla'
        
        return $int_val;
    }
    // end of extract_int()
    
    
    /*
     * Exclude view of users with Administrator role
     * 
     */
    public function exclude_admins_view($views) {

        if (!isset($views['administrator'])) {
            return $views;
        }
        
        if (isset($views['all'])) {        
            // Decrease quant of all users for a quant of hidden admins
            $admins_orig_s = $this->extract_view_quantity($views['administrator']);
            $admins_int = $this->extract_int($admins_orig_s);
            $all_orig_s = $this->extract_view_quantity($views['all']);
            $all_orig_int = $this->extract_int($all_orig_s);
            $all_new_int = $all_orig_int - $admins_int;
            $all_new_s = number_format_i18n($all_new_int);
            $views['all'] = str_replace($all_orig_s, $all_new_s, $views['all']);
        }
        
        unset($views['administrator']);

        return $views;
    }
    // end of exclude_admins_view()
        
}
// end of URE_Protect_Admin class
