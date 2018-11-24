<?php
/*
 * Main class of User Role Editor WordPress plugin
 * Author: Vladimir Garagulya
 * Author email: support@role-editor.com
 * Author URI: https://www.role-editor.com
 * License: GPL v2+
 * 
 */

class User_Role_Editor {
    
    protected static $instance = null; // object exemplar reference  
    
    // plugin specific library object: common code stuff, including options data processor
    protected $lib = null;
    
    // work with user multiple roles class
    protected $user_other_roles = null;
    
    // plugin's Settings page reference, we've got it from add_options_pages() call
    protected $setting_page_hook = null;
    // URE's key capability
    public $key_capability = 'not allowed';
	
    protected $main_page_hook_suffix = null;
    protected $settings_hook_suffix = null;
    // URE pages hook suffixes
    protected $ure_hook_suffixes = null;
    
    
    public static function get_instance() {
        if (self::$instance===null) {        
            self::$instance = new User_Role_Editor();
        }
        
        return self::$instance;
    }
    // end of get_instance()
    
    
    /**
     * Private clone method to prevent cloning of the instance of the *Singleton* 
     *
     * @return void
     */
    private function __clone() {
        
    }
    // end of __clone()
    
    /**
     * Private unserialize method to prevent unserializing of the *Singleton*
     * instance.
     *
     * @return void
     */
    private function __wakeup() {
        
    }
    // end of __wakeup()

    
    /**
     * class constructor
     */
    protected function __construct() {

        if (empty($this->lib)) {
            $this->lib = URE_Lib::get_instance('user_role_editor');
        }

        $this->user_other_roles = new URE_User_Other_Roles();
        
        if ($this->lib->is_pro()) {
            $this->main_page_hook_suffix = 'users_page_users-user-role-editor-pro';
            $this->settings_hook_suffix = 'settings_page_settings-user-role-editor-pro';         
        } else {
            $this->main_page_hook_suffix = 'users_page_users-user-role-editor';
            $this->settings_hook_suffix = 'settings_page_settings-user-role-editor';
        }
        $this->ure_hook_suffixes = array($this->settings_hook_suffix, $this->main_page_hook_suffix);
        
        // activation action
        register_activation_hook(URE_PLUGIN_FULL_PATH, array($this, 'setup'));

        // deactivation action
        register_deactivation_hook(URE_PLUGIN_FULL_PATH, array($this, 'cleanup'));
        		
        // Who can use this plugin
        $this->key_capability = URE_Own_Capabilities::get_key_capability();
                
        // Process URE's internal tasks queue
        $task_queue = URE_Task_Queue::get_instance();
        $task_queue->process();
        
        $this->set_hooks();        
        
    }
    // end of __construct()
            
    
    private function set_hooks() {
        $multisite = $this->lib->get('multisite');
        if ($multisite) {
            // new blog may be registered not at admin back-end only but automatically after new user registration, e.g. 
            // Gravity Forms User Registration Addon does
            add_action( 'wpmu_new_blog', array($this, 'duplicate_roles_for_new_blog'), 10, 2);
        }
                
        // setup additional options hooks for the roles
        add_action('init', array($this, 'set_role_additional_options_hooks'), 9);
        
        if (!is_admin()) {
            return;
        }
        
        add_action('admin_init', array($this, 'plugin_init'), 1);

        // Add the translation function after the plugins loaded hook.
        add_action('plugins_loaded', array($this, 'load_translation'));

        // add own submenu 
        add_action('admin_menu', array($this, 'plugin_menu'));
      		
        if ($multisite) {
            // add own submenu 
            add_action('network_admin_menu', array($this, 'network_plugin_menu'));
        }


        // add a Settings link in the installed plugins page
        add_filter('plugin_action_links_'. URE_PLUGIN_BASE_NAME, array($this, 'plugin_action_links'), 10, 1);
        add_filter('plugin_row_meta', array($this, 'plugin_row_meta'), 10, 2);    
    }
    // end of set_hooks()
    
    
    /**
     * True - if it's an instance of Pro version, false - for free version
     * @return boolean
     */    
    public function is_pro() {
        
        return $this->lib->is_pro();
    }
    // end of is_pro()
        
    
    /**
     * Plugin initialization
     * 
     */
    public function plugin_init() {

        global $pagenow;

        $user_id = get_current_user_id();
        $supress_protection = apply_filters('ure_supress_administrators_protection', false);
        // these filters and actions should prevent editing users with administrator role
        // by other users with 'edit_users' capability
        if (!$supress_protection && !$this->lib->user_is_admin($user_id)) {
            new URE_Protect_Admin();
        }

        add_action('admin_enqueue_scripts', array($this, 'admin_load_js'));
        add_action('user_row_actions', array($this, 'user_row'), 10, 2);
        add_filter('all_plugins', array($this, 'exclude_from_plugins_list'));

        $multisite = $this->lib->get('multisite');
        if ($multisite) {
            $allow_edit_users_to_not_super_admin = $this->lib->get_option('allow_edit_users_to_not_super_admin', 0);
            if ($allow_edit_users_to_not_super_admin) {
                add_filter('map_meta_cap', array($this, 'restore_users_edit_caps'), 1, 4);
                remove_all_filters('enable_edit_any_user_configuration');
                add_filter('enable_edit_any_user_configuration', '__return_true');
                add_action('admin_head', array($this, 'edit_user_permission_check'), 1);
                if ($pagenow == 'user-new.php') {
                    add_filter('site_option_site_admins', array($this, 'allow_add_user_as_superadmin'));
                }
            }
        } else {
            $count_users_without_role = $this->lib->get_option('count_users_without_role', 0);
            if ($count_users_without_role) {
                add_action('restrict_manage_users', array($this, 'move_users_from_no_role_button'));
                add_action('admin_head', array($this, 'add_css_to_users_page'));
                add_action('admin_footer', array($this, 'add_js_to_users_page'));
            }
        }

        $bulk_grant_roles = apply_filters('ure_bulk_grant_roles', true);
        if ($bulk_grant_roles) {
            new URE_Grant_Roles();
        }
       
        add_action('wp_ajax_ure_ajax', array($this, 'ure_ajax'));
    }
    // end of plugin_init()
    

    /**
   * Allow non-superadmin user to add/create users to the site as superadmin does.
   * Include current user to the list of superadmins - for the user-new.php page only, and 
   * if user really can create_users and promote_users
   * @global string $pagenow
   * @param array $site_admins
   * @return array
   */
  public function allow_add_user_as_superadmin($site_admins) {  
      global $pagenow;
      
      $this->lib->set_raised_permissions(false);
      
      if ($pagenow!=='user-new.php') {
          return $site_admins;
      }
      
      // Check if current user really can create and promote users
      remove_filter('site_option_site_admins', array($this, 'allow_add_user_as_superadmin'));
      $can_add_user = current_user_can('create_users') && current_user_can('promote_users');
      add_filter('site_option_site_admins', array($this, 'allow_add_user_as_superadmin'));
      
      if (!$can_add_user) {
          return $site_admins; // no help in this case
      }

      $current_user = wp_get_current_user();
      if (!in_array($current_user->user_login, $site_admins)) {
          $this->lib->set_raised_permissions(true);
          $site_admins[] = $current_user->user_login;
      }
      
      return $site_admins;      
  }
  // end of allow_add_user_as_superadmin()
  
  
  public function move_users_from_no_role_button() {
      
      if (!$this->lib->is_right_admin_path('users.php')) {      
            return;
      }
      if (!current_user_can('edit_users')) {
          return;
      }
      
      $assign_role = $this->lib->get_assign_role();
      $assign_role->show_html();
      
  }
  // end of move_users_from_no_role()
  
  
  public function add_css_to_users_page() {
      
      if (isset($_GET['page'])) {
          return;
      }
      if (!$this->lib->is_right_admin_path('users.php')) {
          return;
      }                  

      wp_enqueue_style('wp-jquery-ui-dialog');
      wp_enqueue_style('ure-admin-css', URE_PLUGIN_URL . 'css/ure-admin.css', array(), false, 'screen');
      
  }
  // end of add_css_to_users_page()
  
  
  public function add_js_to_users_page() {
  
      if (isset($_GET['page'])) {
          return;
      }
      if (!$this->lib->is_right_admin_path('users.php')) {
          return;
      }             
      
      wp_enqueue_script('jquery-ui-dialog', '', array('jquery-ui-core','jquery-ui-button', 'jquery') );
      wp_register_script( 'ure-users', plugins_url( '/js/users.js', URE_PLUGIN_FULL_PATH ) );
      wp_enqueue_script ( 'ure-users' );      
      wp_localize_script( 'ure-users', 'ure_users_data', array(
        'wp_nonce' => wp_create_nonce('user-role-editor'),
        'move_from_no_role_title' => esc_html__('Change role for users without role', 'user-role-editor'),
        'to' => esc_html__('To:', 'user-role-editor'),  
        'no_rights_caption' => esc_html__('No rights', 'user-role-editor'),  
        'provide_new_role_caption' => esc_html__('Provide new role', 'user-role-editor')
              ));
      
  }
  // end of add_js_to_users_page()
  
  
  /**
   * restore edit_users, delete_users, create_users capabilities for non-superadmin users under multisite
   * (code is provided by http://wordpress.org/support/profile/sjobidoo)
   * 
   * @param type $caps
   * @param type $cap
   * @param type $user_id
   * @param type $args
   * @return type
   */
  public function restore_users_edit_caps($caps, $cap, $user_id, $args) {

        foreach ($caps as $key => $capability) {

            if ($capability != 'do_not_allow')
                continue;

            switch ($cap) {
                case 'edit_user':
                case 'edit_users':
                    $caps[$key] = 'edit_users';
                    break;
                case 'delete_user':
                case 'delete_users':
                    $caps[$key] = 'delete_users';
                    break;
                case 'create_users':
                    $caps[$key] = $cap;
                    break;
            }
        }

        return $caps;
    }
    // end of restore_user_edit_caps()
    
    
    /**
     * Checks that both the editing user and the user being edited are
     * members of the blog and prevents the super admin being edited.
     * (code is provided by http://wordpress.org/support/profile/sjobidoo)
     * 
     */
    public function edit_user_permission_check() {
        global $profileuser;

        $current_user_id = get_current_user_id();
        if ($current_user_id===0) {
            return;
        }
        if ($this->lib->is_super_admin()) { // Superadmin may do all
            return;
        }
                        
        $screen = get_current_screen();
        if (empty($screen)) {
            return;
        }
        
        if ($screen->base !== 'user-edit' && $screen->base !== 'user-edit-network') { 
            return;
        }
        
        // editing a user profile: it's correct to call is_super_admin() directly here, as permissions are raised for the $current_user only
        if (!$this->lib->is_super_admin($current_user_id) && is_super_admin($profileuser->ID)) { // trying to edit a superadmin while himself is less than a superadmin
            wp_die(esc_html__('You do not have permission to edit this user.', 'user-role-editor'));
        } elseif (!( is_user_member_of_blog($profileuser->ID, get_current_blog_id()) && is_user_member_of_blog($current_user_id, get_current_blog_id()) )) { // editing user and edited user aren't members of the same blog
            wp_die(esc_html__('You do not have permission to edit this user.', 'user-role-editor'));
        }

    }
    // end of edit_user_permission_check()
    
    
  /**
   * Add/hide edit actions for every user row at the users list
   * 
   * @global type $pagenow
   * @param string $actions
   * @param type $user
   * @return string
   */
    public function user_row($actions, $user) {
        global $pagenow;

        if ($pagenow!=='users.php') {
            return $actions;
        }
        
        $current_user = wp_get_current_user();
        if ($current_user->has_cap($this->key_capability)) {
            $actions['capabilities'] = '<a href="' .
                    wp_nonce_url("users.php?page=users-" . URE_PLUGIN_FILE . "&object=user&amp;user_id={$user->ID}", "ure_user_{$user->ID}") .
                    '">' . esc_html__('Capabilities', 'user-role-editor') . '</a>';
        }
        
        return $actions;
    }

    // end of user_row()

  
  /**
   * Every time when new blog is created - duplicate for it the roles from the main blog  
   * @global wpdb $wpdb
   * @global WP_Roles $wp_roles
   * @param int $blog_id
   * @param int $user_id
   *
   */
    public function duplicate_roles_for_new_blog($blog_id) {
        global $wpdb, $wp_roles;

        // get Id of 1st (main) blog
        $main_blog_id = $this->lib->get_main_blog_id();
        if (empty($main_blog_id)) {
            return;
        }
        $current_blog = $wpdb->blogid;
        switch_to_blog($main_blog_id);
        $main_roles = new WP_Roles();  // get roles from primary blog
        $default_role = get_option('default_role');  // get default role from primary blog
        $addons_data = apply_filters('ure_get_addons_data_for_new_blog', array());   // load addons data - for internal use in a Pro version
        
        switch_to_blog($blog_id);  // switch to the new created blog
        $main_roles->use_db = false;  // do not touch DB
        $main_roles->add_cap('administrator', 'dummy_123456');   // just to save current roles into new blog
        $main_roles->role_key = $wp_roles->role_key;
        $main_roles->use_db = true;  // save roles into new blog DB
        $main_roles->remove_cap('administrator', 'dummy_123456');  // remove unneeded dummy capability
        update_option('default_role', $default_role); // set default role for new blog as it set for primary one
        do_action('ure_set_addons_data_for_new_blog', $blog_id, $addons_data);  // save addons data for new blog - for internal use in a Pro version
        
        switch_to_blog($current_blog);  // return to blog where we were at the begin
    }
    // end of duplicate_roles_for_new_blog()
    

    /** 
   * Filter out URE plugin from not admin users to prevent its not authorized deactivation
   * @param type array $plugins plugins list
   * @return type array $plugins updated plugins list
   */
  public function exclude_from_plugins_list($plugins) {
        $multisite = $this->lib->get('multisite');
        // if multi-site, then allow plugin activation for network superadmins and, if that's specially defined, - for single site administrators too    
        if ($multisite) {
            if ($this->lib->is_super_admin() || $this->lib->user_is_admin()) {
                return $plugins;
            }
        } else {
// is_super_admin() defines superadmin for not multisite as user who can 'delete_users' which I don't like. 
// So let's check if user has 'administrator' role better.
            if (current_user_can('administrator') || $this->lib->user_is_admin()) {
                return $plugins;
            }
        }

        // exclude URE from plugins list
        $key = basename(URE_PLUGIN_DIR) . '/' . URE_PLUGIN_FILE;
        unset($plugins[$key]);

        return $plugins;
    }
    // end of exclude_from_plugins_list()
    

    /**
     * Load plugin translation files - linked to the 'plugins_loaded' action
     * 
     */
    function load_translation() {

        load_plugin_textdomain('user-role-editor', '', dirname( plugin_basename( URE_PLUGIN_FULL_PATH ) ) .'/lang');
        
    }
    // end of ure_load_translation()

    
    /**
     * Modify plugin action links
     * 
     * @param array $links
     * @return array
     */
    public function plugin_action_links($links) {
        $single_site_settings_link = '<a href="options-general.php?page=settings-' . URE_PLUGIN_FILE . '">' . esc_html__('Settings', 'user-role-editor') .'</a>';
        $multisite = $this->lib->get('multisite');        
        if (!$multisite ) {
            $settings_link = $single_site_settings_link;
        } else {
            $ure = basename(URE_PLUGIN_DIR) . '/' . URE_PLUGIN_FILE;
            $active_for_network = is_plugin_active_for_network($ure);
            if (!$active_for_network) {
                $settings_link = $single_site_settings_link;
            } else {
                if (!current_user_can('manage_network_plugins')) {
                    return $links;
                }
                $settings_link = '<a href="'. network_admin_url() .'settings.php?page=settings-'. URE_PLUGIN_FILE .'">'. esc_html__('Settings', 'user-role-editor') .'</a>';
            }
        }
        array_unshift($links, $settings_link);

        return $links;
    }
    // end of plugin_action_links()


    public function plugin_row_meta($links, $file) {

        if ($file == plugin_basename(dirname(URE_PLUGIN_FULL_PATH) .'/'.URE_PLUGIN_FILE)) {
            $links[] = '<a target="_blank" href="https://www.role-editor.com/changelog">' . esc_html__('Changelog', 'user-role-editor') . '</a>';
        }

        return $links;
    }

    // end of plugin_row_meta
    
    
    public function settings_screen_configure() {
        $multisite = $this->lib->get('multisite');
        $settings_page_hook = $this->settings_page_hook;
        if ($multisite) {
            $settings_page_hook .= '-network';
        }
        $screen = get_current_screen();
        // Check if current screen is URE's settings page
        if ($screen->id != $settings_page_hook) {
            return;
        }
        $screen_help = new Ure_Screen_Help();
        $screen->add_help_tab( array(
            'id'	=> 'general',
            'title'	=> esc_html__('General', 'user-role-editor'),
            'content'	=> $screen_help->get_settings_help('general')
            ));
        if ($this->lib->is_pro() || !$multisite) {
            $screen->add_help_tab( array(
                'id'	=> 'additional_modules',
                'title'	=> esc_html__('Additional Modules', 'user-role-editor'),
                'content'	=> $screen_help->get_settings_help('additional_modules')
                ));
        }
        $screen->add_help_tab( array(
            'id'	=> 'default_roles',
            'title'	=> esc_html__('Default Roles', 'user-role-editor'),
            'content'	=> $screen_help->get_settings_help('default_roles')
            ));
        if ($multisite) {
            $screen->add_help_tab( array(
                'id'	=> 'multisite',
                'title'	=> esc_html__('Multisite', 'user-role-editor'),
                'content'	=> $screen_help->get_settings_help('multisite')
                ));
        }
    }
    // end of settings_screen_configure()
    
    
    public function plugin_menu() {

        $translated_title = esc_html__('User Role Editor', 'user-role-editor');
        if (function_exists('add_submenu_page')) {
            $ure_page = add_submenu_page(
                    'users.php', 
                    $translated_title,
                    $translated_title,
                    'ure_edit_roles', 
                    'users-' . URE_PLUGIN_FILE, 
                    array($this, 'edit_roles'));
            add_action("admin_print_styles-$ure_page", array($this, 'admin_css_action'));
        }

        $multisite = $this->lib->get('multisite');
        $active_for_network = $this->lib->get('active_for_network');
        if ( !$multisite || ($multisite && !$active_for_network) ) {
            $settings_capability = URE_Own_Capabilities::get_settings_capability();
            $this->settings_page_hook = add_options_page(
                    $translated_title,
                    $translated_title,
                    $settings_capability, 
                    'settings-' . URE_PLUGIN_FILE, 
                    array($this, 'settings'));
            add_action( 'load-'.$this->settings_page_hook, array($this,'settings_screen_configure') );
            add_action("admin_print_styles-{$this->settings_page_hook}", array($this, 'settings_css_action'));
        }
    }
    // end of plugin_menu()


    public function network_plugin_menu() {        
        if (is_multisite()) {
            $translated_title = esc_html__('User Role Editor', 'user-role-editor');
            $this->settings_page_hook = add_submenu_page(
                    'settings.php', 
                    $translated_title,
                    $translated_title, 
                    $this->key_capability, 
                    'settings-' . URE_PLUGIN_FILE, 
                    array(&$this, 'settings'));
            add_action( 'load-'.$this->settings_page_hook, array($this,'settings_screen_configure') );
            add_action("admin_print_styles-{$this->settings_page_hook}", array($this, 'settings_css_action'));
        }
        
    }

    // end of network_plugin_menu()
        

    public function settings() {
        $settings_capability = URE_Own_Capabilities::get_settings_capability();
        if (!current_user_can($settings_capability)) {
            wp_die(esc_html__( 'You do not have sufficient permissions to manage options for User Role Editor.', 'user-role-editor' ));
        }
        
        URE_Settings::show();
                
    }
    // end of settings()


    public function admin_css_action() {

        wp_enqueue_style('wp-jquery-ui-selectable');        
        wp_enqueue_style('ure-jquery-ui-general', URE_PLUGIN_URL . 'css/jquery-ui.min.css', array(), false, 'screen');
        wp_enqueue_style('ure-admin-css', URE_PLUGIN_URL . 'css/ure-admin.css', array(), false, 'screen');
    }
    // end of admin_css_action()
    
    
    public function settings_css_action() {


        wp_enqueue_style('ure-jquery-ui-tabs', URE_PLUGIN_URL . 'css/jquery-ui.min.css', array(), false, 'screen');
        wp_enqueue_style('ure-admin-css', URE_PLUGIN_URL . 'css/ure-admin.css', array(), false, 'screen');

    }
    // end of admin_css_action()

    
    
    // call roles editor page
    public function edit_roles() {

        if (!current_user_can('ure_edit_roles')) {
            wp_die(esc_html__('Insufficient permissions to work with User Role Editor', 'user-role-editor'));
        }

        $this->lib->editor();
    }
    // end of edit_roles()
	

    /**
     *  execute on plugin activation
     */
    function setup() {

        $this->lib->backup_wp_roles();
        URE_Own_Capabilities::init_caps();
        
        $task_queue = URE_Task_Queue::get_instance();
        $task_queue->add('on_activation');
                
    }
    // end of setup()
            
    
    protected function load_main_page_js() {
        
        $confirm_role_update = $this->lib->get_option('ure_confirm_role_update', 1);        
        $page_url = $this->lib->get_ure_page_url();
        
        wp_enqueue_script('jquery-ui-dialog', '', array('jquery-ui-core', 'jquery-ui-button', 'jquery'));
        wp_enqueue_script('jquery-ui-selectable', '', array('jquery-ui-core', 'jquery'));
        wp_register_script('ure', plugins_url('/js/ure.js', URE_PLUGIN_FULL_PATH));
        wp_enqueue_script('ure');
        wp_localize_script('ure', 'ure_data', array(
            'wp_nonce' => wp_create_nonce('user-role-editor'),
            'network_admin' => is_network_admin() ? 1 : 0,
            'page_url' => $page_url,
            'is_multisite' => is_multisite() ? 1 : 0,
            'confirm_role_update' => $confirm_role_update ? 1 : 0,
            'confirm_title' => esc_html__('Confirm', 'user-role-editor'),
            'yes_label' => esc_html__('Yes', 'user-role-editor'),
            'no_label' => esc_html__('No', 'user-role-editor'),            
            'update' => esc_html__('Update', 'user-role-editor'),
            'confirm_submit' => esc_html__('Please confirm permissions update', 'user-role-editor'),
            'add_new_role_title' => esc_html__('Add New Role', 'user-role-editor'),
            'rename_role_title' => esc_html__('Rename Role', 'user-role-editor'),
            'role_name_required' => esc_html__(' Role name (ID) can not be empty!', 'user-role-editor'),
            'role_name_valid_chars' => esc_html__(' Role name (ID) must contain latin characters, digits, hyphens or underscore only!', 'user-role-editor'),
            'numeric_role_name_prohibited' => esc_html__(' WordPress does not support numeric Role name (ID). Add latin characters to it.', 'user-role-editor'),
            'add_role' => esc_html__('Add Role', 'user-role-editor'),
            'rename_role' => esc_html__('Rename Role', 'user-role-editor'),
            'delete_role' => esc_html__('Delete Role', 'user-role-editor'),
            'cancel' => esc_html__('Cancel', 'user-role-editor'),
            'add_capability' => esc_html__('Add Capability', 'user-role-editor'),
            'delete_capability' => esc_html__('Delete Capability', 'user-role-editor'),
            esc_html__('Continue?', 'user-role-editor'),
            'default_role' => esc_html__('Default Role', 'user-role-editor'),
            'set_new_default_role' => esc_html__('Set New Default Role', 'user-role-editor'),
            'delete_capability' => esc_html__('Delete Capability', 'user-role-editor'),
            'delete_capability_warning' => esc_html__('Warning! Be careful - removing critical capability could crash some plugin or other custom code', 'user-role-editor'),
            'capability_name_required' => esc_html__(' Capability name (ID) can not be empty!', 'user-role-editor'),
            'capability_name_valid_chars' => esc_html__(' Capability name (ID) must contain latin characters, digits, hyphens or underscore only!', 'user-role-editor'),
        ));
        
        // load additional JS stuff for Pro version, if exists
        do_action('ure_load_js');        
        
    }
    // end of load_main_page_js()
    
    
    protected function load_settings_js() {
    
        $page_url = $this->lib->get_ure_page_url();
        
        wp_enqueue_script('jquery-ui-tabs', '', array('jquery-ui-core', 'jquery'));
        wp_enqueue_script('jquery-ui-dialog', '', array('jquery-ui-core', 'jquery'));
        wp_enqueue_script('jquery-ui-button', '', array('jquery-ui-core', 'jquery'));
        wp_register_script('ure-js', plugins_url('/js/settings.js', URE_PLUGIN_FULL_PATH));
        wp_enqueue_script('ure-js');
        
        wp_localize_script('ure-js', 'ure_data', array(
            'wp_nonce' => wp_create_nonce('user-role-editor'),
            'network_admin' => is_network_admin() ? 1 : 0,
            'page_url' => $page_url,
            'is_multisite' => is_multisite() ? 1 : 0,
            'confirm_title' => esc_html__('Confirm', 'user-role-editor'),
            'yes_label' => esc_html__('Yes', 'user-role-editor'),
            'no_label' => esc_html__('No', 'user-role-editor'),
            'reset' => esc_html__('Reset', 'user-role-editor'),
            'reset_warning' => '<span style="color: red;">'. esc_html__('DANGER!', 'user-role-editor') .'</span>'. 
            esc_html__(' Resetting will restore default user roles and capabilities from WordPress core.', 'user-role-editor') .'<br><br>'.
            esc_html__('If any plugins (such as WooCommerce, S2Member and many others) have changed user roles and capabilities during installation, all those changes will be LOST!', 'user-role-editor') .'<br>'.
            esc_html__('For more information on how to undo undesired changes and restore plugin capabilities go to', 'user-role-editor') .'<br>'.
            '<a href="http://role-editor.com/how-to-restore-deleted-wordpress-user-roles/">http://role-editor.com/how-to-restore-deleted-wordpress-user-roles/</a>' .'<br><br>'.
            esc_html__('Continue?', 'user-role-editor')
        ));
                
        do_action('ure_load_js_settings');
        
    }
    // end of load_settings_js()
    

    /**
     * Load plugin javascript stuff
     * 
     * @param string $hook_suffix
     */
    public function admin_load_js($hook_suffix) {

        URE_Known_JS_CSS_Compatibility_Issues::fix($hook_suffix, $this->ure_hook_suffixes);                
        
        if ($hook_suffix==$this->main_page_hook_suffix) {
            $this->load_main_page_js();
        } elseif($hook_suffix==$this->settings_hook_suffix) {
            $this->load_settings_js();
        }                

    }
    // end of admin_load_js()
                       
    
    public function ure_ajax() {
                
        $ajax_processor = new URE_Ajax_Processor($this->lib);
        $ajax_processor->dispatch();
        
    }
    // end of ure_ajax()

    
    public function set_role_additional_options_hooks() {

        $role_additional_options = URE_Role_Additional_Options::get_instance($this->lib);
        $role_additional_options->set_active_items_hooks();
        
    }
    // end of set_role_additional_options_hooks()
    
    
    // execute on plugin deactivation
    function cleanup() {
		
    }
    // end of setup()
        
 
}
// end of User_Role_Editor
