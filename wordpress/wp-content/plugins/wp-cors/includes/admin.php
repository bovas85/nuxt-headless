<?php 
  error_log('Loading wp-cors options');
  // add the admin options page
  add_action('admin_menu', 'plugin_admin_add_page');
  function plugin_admin_add_page() {
    add_options_page('Custom Plugin Page', 'Custom Plugin Menu', 'manage_options', 'plugin', 'plugin_options_page');
  }
// display the admin options page
function plugin_options_page() {
?>
<div>
<h2>My custom plugin</h2>
Options relating to the Custom Plugin.
<form action="options.php" method="post">
<?php settings_fields('plugin_options'); ?>
<?php do_settings_sections('plugin'); ?>
 
<input name="Submit" type="submit" value="<?php esc_attr_e('Save Changes'); ?>" />
</form></div>
 
<?php
}

// add the admin settings and such
add_action('admin_init', 'plugin_admin_init');
function plugin_admin_init(){
register_setting( 'plugin_options', 'plugin_options', 'plugin_options_validate' );
add_settings_section('plugin_main', 'Main Settings', 'plugin_section_text', 'plugin');
add_settings_field('plugin_text_string', 'Plugin Text Input', 'plugin_setting_string', 'plugin', 'plugin_main');
}

// validate our options
function plugin_options_validate($input) {
$newinput['text_string'] = trim($input['text_string']);
if(!preg_match('/^[a-z0-9]{32}$/i', $newinput['text_string'])) {
$newinput['text_string'] = '';
}
return $newinput;
}
?>

