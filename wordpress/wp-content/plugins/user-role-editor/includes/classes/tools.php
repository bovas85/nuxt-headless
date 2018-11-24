<?php

class URE_Tools {
    private $lib;
    private $multisite = null;
    private $link = null;
    
    public function __construct() {
        
        $this->lib = URE_Lib::get_instance();
        $this->multisite = $this->lib->get('multisite');
        
        if ($this->multisite && is_network_admin()) {
            $this->link = 'settings.php';
        } else {
            $this->link = 'options-general.php';
        }
        
    }
    // end of __construct()
    

    public function show_reset($tab_idx) {

        if (!$this->multisite || (is_main_site(get_current_blog_id()) || (is_network_admin() && $this->lib->is_super_admin()))) {
            if (current_user_can('ure_reset_roles')) {
?>               

    <div style="margin: 10px 0 10px 0; border: 1px solid red; padding: 0 10px 10px 10px; text-align:left;">        
        <form name="ure_reset_roles_form" id="ure_reset_roles_form" method="post" action="<?php echo $this->link; ?>?page=settings-<?php echo URE_PLUGIN_FILE; ?>" >
            <h3>Reset User Roles</h3>
            <span style="color: red;"><?php esc_html_e('WARNING!', 'user-role-editor');?></span>&nbsp;
<?php        
        esc_html_e('Resetting will setup default user roles and capabilities from WordPress core.', 'user-role-editor'); echo '<br>';
        esc_html_e('If any plugins (such as WooCommerce, S2Member and many others) have changed user roles and capabilities during installation, those changes will be LOST!', 'user-role-editor'); echo '<br>';
        esc_html_e('For more information on how to undo undesired changes and restore plugins capabilities in case you lost them by mistake go to: ', 'user-role-editor'); 
        echo '<a href="http://role-editor.com/how-to-restore-deleted-wordpress-user-roles/">http://role-editor.com/how-to-restore-deleted-wordpress-user-roles/</a>';
        
        if ($this->multisite) { 
            
?>
            <br><br>
            <input type="checkbox" name="ure_apply_to_all" id="ure_apply_to_all" value="1" />
            <label for="ure_apply_to_all"><?php esc_html_e('Apply to All Sites', 'user-role-editor'); ?></label> 
        (<?php esc_html_e('If checked, then apply action to ALL sites. Main site only is affected in other case.', 'user-role-editor'); ?>)
<?php
        }
?>
            <br><br>            
            <button id="ure_reset_roles_button" style="width: 100px; color: red;" title="<?php esc_html_e('Reset Roles to its original state', 'user-role-editor'); ?>"><?php esc_html_e('Reset', 'user-role-editor');?></button> 
            <?php wp_nonce_field('user-role-editor'); ?>
            <input type="hidden" name="ure_reset_roles_exec" value="1" />
            <input type="hidden" name="ure_tab_idx" value="<?php echo $tab_idx; ?>" />
        </form>                
    </div>    

<?php
            }
        }
    }
    // end of show_reset()


    public function show($tab_idx) {

        $this->show_reset($tab_idx);
    }
    // end of show()

}
// end of URE_Tools