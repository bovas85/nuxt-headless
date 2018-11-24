<?php
/**
 * Project: User Role Editor plugin
 * Author: Vladimir Garagulya
 * Author email: support@role-editor.com
 * Author URI: https://www.role-editor.com
 * License: GPL v2+
 * 
 * Assign multiple roles to the list of selected users directly from the "Users" page
 */

class URE_Grant_Roles {

    const NO_ROLE_FOR_THIS_SITE = 'no-role-for-this-site';
    
    private $lib = null;
    private static $counter = 0;
    
    
    public function __construct() {
        
        $this->lib = URE_Lib::get_instance();        
        
        add_action('restrict_manage_users', array($this, 'show_grant_roles_html'));
        add_action('admin_head', array(User_Role_Editor::get_instance(), 'add_css_to_users_page'));
        add_action('admin_enqueue_scripts', array($this, 'load_js'));
                
    }
    // end of __construct()
    
    
    private static function validate_users($users) {
        if (!is_array($users)) {
            return false;
        }
        
        foreach ($users as $user_id) {
            if (!is_numeric($user_id)) {
                return false;
            }
            if (!current_user_can('edit_user', $user_id)) {
                return false;
            }
        }
        
        return true;
    }
    // end of validate_users()            
    
    
    private static function validate_roles($roles) {

        if (!is_array($roles)) {
            return false;
        }
        
        $lib = URE_Lib::get_instance();
        $editable_roles = $lib->get_all_editable_roles();
        $valid_roles = array_keys($editable_roles);
        foreach($roles as $role) {
            if (!in_array($role, $valid_roles)) {
                return false;
            }
        }
        
        return true;        
    }
    // end of validate_roles()
        
    
    private static function grant_primary_role_to_user($user_id, $role) {
                        
        $user = get_user_by('id', $user_id);
        if (empty($user)) {
            return;
        }
                     
        if ($role===self::NO_ROLE_FOR_THIS_SITE) {
            $role = '';
        }
        $old_roles = $user->roles;  // Save currently granted roles to restore from them the bbPress roles later if there are any...
        $user->set_role($role); 
        
        $lib = URE_Lib::get_instance();
        $bbpress = $lib->get('bbpress');
        if (empty($bbpress)) {
            return;
        }
        
        $bbp_roles = $bbpress->extract_bbp_roles($old_roles);
        if (count($bbp_roles)>0) {  //  restore bbPress roles
            foreach($bbp_roles as $role) {
                $user->add_role($role);
            }        
        }        
        
    }
    // end of grant_primary_role_to_user()
            
    
    private static function grant_other_roles_to_user($user_id, $roles) {
                        
        $user = get_user_by('id', $user_id);
        if (empty($user)) {
            return;
        }
        
        $primary_role = array_shift(array_values($user->roles));    // Get the 1st element from the roles array
        $lib = URE_Lib::get_instance();
        $bbpress = $lib->get('bbpress');
        if (empty($bbpress)) {
            $bbp_roles = array();
        } else {
            $bbp_roles = $bbpress->extract_bbp_roles($user->roles);
        }
        $user->remove_all_caps();
        $roles = array_merge(array($primary_role), $bbp_roles, $roles);
        foreach($roles as $role) {
            $user->add_role($role);
        }
        
    }
    // end of grant_other_roles_to_user()
    
    
    /**
     * Decide if primary role should be granted or left as it is
     * 
     * @param string $primary_role
     * @return boolean
     */
    private static function is_select_primary_role($primary_role) {
        
        if (empty($primary_role)) {
            return false;   // Primary role was not selected by user, leave an older one
        }
        
        $lib = URE_Lib::get_instance();
        if ($lib->is_super_admin()) {
            $select_primary_role = true;
        } else {
            $select_primary_role = apply_filters('ure_users_select_primary_role', true);
        }
        
        return $select_primary_role;
    }
    // end of is_select_primary_role()
    
    
    public static function grant_roles() {

        if (!current_user_can('edit_users')) {
            $answer = array('result'=>'error', 'message'=>esc_html__('Not enough permissions', 'user-role-editor'));
            return $answer;
        }
                
        $users = $_POST['users'];        
        if (!self::validate_users($users)) {
            $answer = array('result'=>'error', 'message'=>esc_html__('Can not edit user or invalid data at the users list', 'user-role-editor'));
            return $answer;
        }

// Primary role       
        $primary_role = $_POST['primary_role'];        
        if (!empty($primary_role) && ($primary_role!==self::NO_ROLE_FOR_THIS_SITE) && 
            !self::validate_roles(array($primary_role=>$primary_role))) {
            $answer = array('result'=>'error', 'message'=>esc_html__('Invalid primary role', 'user-role-editor'));
            return $answer;
        }
                
        if (self::is_select_primary_role($primary_role)) {            
            foreach ($users as $user_id) {                
                self::grant_primary_role_to_user($user_id, $primary_role);
            }            
        }
        
// Other roles        
        $other_roles = isset($_POST['other_roles']) ? $_POST['other_roles'] : null;
        if (!empty($other_roles) && !self::validate_roles($other_roles)) {
            $answer = array('result'=>'error', 'message'=>esc_html__('Invalid data at the other roles list', 'user-role-editor'));
            return $answer;
        }
        
        if (!empty($other_roles)) {
            foreach($users as $user_id) {
                self::grant_other_roles_to_user($user_id, $other_roles); 
            }                
        }
        $answer = array('result'=>'success', 'message'=>esc_html__('Roles were granted to users successfully', 'user-role-editor'));
        
        return $answer;
    }
    // end of grant_roles()
    
    
    public static function get_user_roles() {

        if (!current_user_can('edit_users')) {
            $answer = array('result'=>'error', 'message'=>esc_html__('Not enough permissions', 'user-role-editor'));
            return $answer;
        }
        
        $lib = URE_Lib::get_instance();
        $user_id = (int) $lib->get_request_var('user_id', 'post', 'int');
        if (empty($user_id)) {
            $answer = array('result'=>'error', 'message'=>esc_html__('Wrong request, valid user ID was missed', 'user-role-editor'));
            return $answer;
        }
    
        $user = get_user_by('id', $user_id);
        if (empty($user)) {
            $answer = array('result'=>'error', 'message'=>esc_html__('Requested user does not exist', 'user-role-editor'));
            return $answer;
        }
        
        $other_roles = array_values($user->roles);
        $primary_role = array_shift($other_roles);
        
        $answer = array('result'=>'success', 'primary_role'=>$primary_role, 'other_roles'=>$other_roles);
        
        return $answer;
    }
    // end of get_user_roles()
    
    
    
    private function select_primary_role_html() {
        
        $select_primary_role = apply_filters('ure_users_select_primary_role', true);
        if (!$select_primary_role && !$this->lib->is_super_admin()) {
            return;
        }
?>        
        <span style="font-weight: bold;">
            <?php esc_html_e('Primary Role: ', 'role-editor');?> 
        </span>
        <select name="primary_role" id="primary_role">
<?php            
        // print the full list of roles with the primary one selected.
        wp_dropdown_roles('');
        echo '<option value="'. self::NO_ROLE_FOR_THIS_SITE .'">' . esc_html__('&mdash; No role for this site &mdash;') . '</option>'. PHP_EOL;
?>        
        </select>
        <hr/>
<?php        
    }
    // end of select_primary_role_html()
            
    
    private function select_other_roles_html() {
?>        
        <div id="other_roles_container">
            <span style="font-weight: bold;">
<?php          
        esc_html_e('Other Roles: ', 'role-editor');
?>        
        </span><br>
<?php        
        $show_admin_role = $this->lib->show_admin_role_allowed();        
        $roles = $this->lib->get_all_editable_roles();        
        foreach ($roles as $role_id => $role) {
            if (!$show_admin_role && $role_id=='administrator') {
                continue;
            }
            echo '<label for="wp_role_' . $role_id . '"><input type="checkbox"	id="wp_role_' . $role_id .
                 '" name="ure_roles[]" value="' . $role_id . '" />&nbsp;' .
            esc_html__($role['name'], 'user-role-editor') .' ('. $role_id .')</label><br />'. PHP_EOL;            
        }
?>
        </div>
<?php        
    }
    // end of select_other_roles_html()
    
    
    public function show_grant_roles_html() {
        if (!$this->lib->is_right_admin_path('users.php')) {      
            return;
        }      
        if (!current_user_can('edit_users')) {
            return;
        }
        $button_number =  (self::$counter>0) ? '_2': '';
?>        
            &nbsp;&nbsp;<input type="button" name="ure_grant_roles<?php echo $button_number;?>" id="ure_grant_roles<?php echo $button_number;?>" class="button"
                        value="<?php esc_html_e('Grant Roles', 'user-role-editor');?>">
<?php
    if (self::$counter==0) {
?>
            <div id="ure_grant_roles_dialog" class="ure-dialog">
                <div id="ure_grant_roles_content">
<?php                
                $this->select_primary_role_html();
                $this->select_other_roles_html();
?>                
                </div>
            </div>
<?php
        URE_View::output_task_status_div();
        self::$counter++;
    }
        
    }
    // end of show_grant_roles_html()
    
    
    public function load_js() {
        if (isset($_GET['page'])) {
          return;
        }
        if (!$this->lib->is_right_admin_path('users.php')) {
          return;
        }

        $show_wp_change_role = apply_filters('ure_users_show_wp_change_role', true);
        
        wp_enqueue_script('jquery-ui-dialog', '', array('jquery-ui-core','jquery-ui-button', 'jquery') );
        wp_register_script('ure-users-grant-roles', plugins_url('/js/users-grant-roles.js', URE_PLUGIN_FULL_PATH));
        wp_enqueue_script('ure-users-grant-roles', '', array(), false, true);
        wp_localize_script('ure-users-grant-roles', 'ure_users_grant_roles_data', array(
            'wp_nonce' => wp_create_nonce('user-role-editor'),
            'dialog_title'=> esc_html__('Grant roles to selected users', 'user-role-editor'),
            'select_users_first' => esc_html__('Select users to which you wish to grant roles!', 'user-role-editor'),
            'select_roles_first' => esc_html__('Select role(s) which you wish to grant!', 'user-role-editor'),
            'show_wp_change_role' => $show_wp_change_role ? 1: 0
        ));
    }
    // end of load_js()
    
}
// end of URE_Grant_Roles class
