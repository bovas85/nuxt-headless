<?php
/**
 * Support for bbPress user roles and capabilities
 * 
 * Project: User Role Editor WordPress plugin
 * Author: Vladimir Garagulya
 * Author email: vladimir@shinephp.com
 * Author URI: http://shinephp.com
 * 
 **/

class URE_bbPress {
   
    protected $lib = null;
    protected $bbpress_detected = false;
    
    
    public function __construct(URE_Lib $lib) {
        
        $this->lib = $lib;
        
        add_action('plugins_loaded', array($this, 'detect_bbpress'), 8);
    }
    // end of __construct()
           
    
    public function detect_bbpress() {

        if (!function_exists('is_plugin_active')) {
            require_once(ABSPATH .'/wp-admin/includes/plugin.php');
        }
        $this->bbpress_detected = false;
        if (function_exists('bbp_filter_blog_editable_roles')) {
            $this->bbpress_detected = true;  // bbPress plugin is installed and active
        }
        
    }
    // end of detect_bbpress()
    
    
    public function is_active() {
        
        return $this->bbpress_detected;
    }
    // end of is_active()
    

    /**
     * Exclude roles created by bbPress
     * 
     * @global array $wp_roles
     * @return array
     */
    public function get_roles() {
        
        $wp_roles = wp_roles();                        
        if ($this->bbpress_detected) {
            $roles = bbp_filter_blog_editable_roles($wp_roles->roles);  // exclude bbPress roles
        } else {
            $roles = $wp_roles->roles;
        }
        
        return $roles;
    }
    // end of get_roles()
    
    
    /**
     * Get full list user capabilities created by bbPress
     * 
     * @return array
     */   
    public function get_caps() {
        
        if ($this->bbpress_detected) {
            $caps = array_keys(bbp_get_caps_for_role(bbp_get_keymaster_role()));
        } else {
            $caps = array();
        }
        
        return $caps;
    }
    // end of get_caps()
    
    
    /**
     * Return empty array in order do not include bbPress roles into selectable lists: supported by Pro version only
     * @return array
     */
    public function get_bbp_editable_roles() {
        
        $all_bbp_roles = array();
        
        return $all_bbp_roles;        
    }
    // end of get_bbp_editable_roles()
    
    
    /**
     * Return bbPress roles found at $roles array. Used to exclude bbPress roles from processing as free version should not support them
     * 
     * @param array $roles
     * @return array
     */
    public function extract_bbp_roles($roles) {

        $user_bbp_roles = array();
        if ($this->bbpress_detected) {
            $all_bbp_roles = array_keys(bbp_get_dynamic_roles());
            foreach($roles as $role) {
                if (in_array($role, $all_bbp_roles)) {
                    $user_bbp_roles[] = $role;                    
                }            
            }
        }    
        
        return $user_bbp_roles;
    }
    // end of extract_bbp_roles()

}
// end of URE_bbPress class