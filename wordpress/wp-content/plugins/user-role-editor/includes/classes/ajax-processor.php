<?php

/*
 * User Role Editor WordPress plugin
 * Author: Vladimir Garagulya
 * Email: support@role-editor.com
 * License: GPLv2 or later
 */


/**
 * Process AJAX request from User Role Editor
 *
 * @author vladimir
 */
class URE_Ajax_Processor {

    protected $lib = null;
    protected $action = null;
    

    public function __construct($lib) {
        
        $this->lib = $lib;
        
    }
    // end of __construct()
    
    
    protected function get_action() {
        $action = filter_input(INPUT_POST, 'sub_action', FILTER_SANITIZE_STRING);
        if (empty($action)) {
            $action = filter_input(INPUT_GET, 'sub_action', FILTER_SANITIZE_STRING);
        }
        
        $this->action = $action;
        
        return $action;
    }
    
    
    protected function get_required_cap() {
        
        if ($this->action=='grant_roles' || $this->action=='get_user_roles') {
            $cap = 'edit_users';
        } else {
            $cap = URE_Own_Capabilities::get_key_capability();
        }
        
        return $cap;
    }
    // end of get_required_cap()
    
    
    protected function ajax_check_permissions() {
        
        if (!wp_verify_nonce($_REQUEST['wp_nonce'], 'user-role-editor')) {
            echo json_encode(array('result'=>'error', 'message'=>'URE: Wrong or expired request'));
            die;
        }
        
        $capability = $this->get_required_cap();                
        if (!current_user_can($capability)) {
            echo json_encode(array('result'=>'error', 'message'=>'URE: Insufficient permissions'));
            die;
        }
        
    }
    // end of ajax_check_permissions()
    
    
    protected function get_caps_to_remove() {
    
        $html = URE_Role_View::caps_to_remove_html();
        $answer = array('result'=>'success', 'html'=>$html, 'message'=>'success');
        
        return $answer;
    }
    // end of get_caps_to_remove()
    
                
    protected function get_users_without_role() {
        global $wp_roles;
        
        $new_role = filter_input(INPUT_POST, 'new_role', FILTER_SANITIZE_STRING);
        if (empty($new_role)) {
            $answer = array('result'=>'error', 'message'=>'Provide new role');
            return $answer;
        }
        
        $assign_role = $this->lib->get_assign_role();
        if ($new_role==='no_rights') {
            $assign_role->create_no_rights_role();
        }        
        
        if (!isset($wp_roles)) {
            $wp_roles = new WP_Roles();
        }
        if (!isset($wp_roles->roles[$new_role])) {
            $answer = array('result'=>'error', 'message'=>'Selected new role does not exist');
            return $answer;
        }
                
        $users = $assign_role->get_users_without_role($new_role);
        
        $answer = array('result'=>'success', 'users'=>$users, 'new_role'=>$new_role, 'message'=>'success');
        
        return $answer;
    }
    // end of get_users_without_role()
    
    
    protected function grant_roles() {
        
        $answer = URE_Grant_Roles::grant_roles();
        
        return $answer;
        
    }
    // end of grant_roles()
    
    
    protected function get_user_roles() {
        
        $answer = URE_Grant_Roles::get_user_roles();
        
        return $answer;
        
    }
    // end of get_user_roles()
    
    
    protected function get_role_caps() {
        $role = filter_input(INPUT_POST, 'role', FILTER_SANITIZE_STRING);
        if (empty($role)) {
            $answer = array('result'=>'error', 'message'=>'Provide role ID');
            return $answer;
        }
        
        $wp_roles = wp_roles();
        if (!isset($wp_roles->roles[$role])) {
            $answer = array('result'=>'error', 'message'=>'Requested role does not exist');
            return $answer;
        }
        
        $active_items = URE_Role_Additional_Options::get_active_items();
        if (isset($active_items[$role])) {
            $role_options = $active_items[$role];
        } else {
            $role_options = array();
        }
        
        $answer = array(
            'result'=>'success', 
            'message'=>'Role capabilities retrieved successfully', 
            'role_id'=>$role,
            'role_name'=>$wp_roles->roles[$role]['name'],
            'caps'=>$wp_roles->roles[$role]['capabilities'],
            'options'=>$role_options
            );
        
        return $answer;
    }
    // end of get_role_caps()
    
    
    protected function _dispatch() {
        switch ($this->action) {
            case 'get_caps_to_remove':
                $answer = $this->get_caps_to_remove();
                break;
            case 'get_users_without_role':
                $answer = $this->get_users_without_role();
                break;
            case 'grant_roles':
                $answer = $this->grant_roles();
                break;
            case 'get_user_roles':
                $answer = $this->get_user_roles();
                break;
            case 'get_role_caps': 
                $answer = $this->get_role_caps();
                break;
            default:
                $answer = array('result' => 'error', 'message' => 'unknown action "' . $this->action . '"');
        }
        
        return $answer;
    }
    // end of _dispatch()
    
    
    /**
     * AJAX requests dispatcher
     */    
    public function dispatch() {
        
        $this->get_action();
        $this->ajax_check_permissions();                
        $answer = $this->_dispatch();
        
        $json_answer = json_encode($answer);
        echo $json_answer;
        die;
    }    
    
}
// end of URE_Ajax_Processor
