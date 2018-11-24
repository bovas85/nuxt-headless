<?php
if( !defined( 'ABSPATH' ) ) {
	exit;
}

$typeargs = array( 'public' => true );
if( $this->networkactive ) {
	$typeargs['_builtin'] = true;	// stick to known types for network
}
$types = get_post_types( $typeargs, 'objects' );
foreach( array_keys( $types ) as $type ) {
	if( ! in_array( $type, $this->modified_types ) && ! post_type_supports( $type, 'comments' ) )	// the type doesn't support comments anyway
		unset( $types[$type] );
}

$persistent_allowed = $this->persistent_mode_allowed();

if ( isset( $_POST['submit'] ) ) {
	check_admin_referer( 'disable-comments-admin' );
	$this->options['remove_everywhere'] = ( $_POST['mode'] == 'remove_everywhere' );

	if( $this->options['remove_everywhere'] )
		$disabled_post_types = array_keys( $types );
	else
		$disabled_post_types =  empty( $_POST['disabled_types'] ) ? array() : (array) $_POST['disabled_types'];

	$disabled_post_types = array_intersect( $disabled_post_types, array_keys( $types ) );

	// entering permanent mode, or post types have changed
	if( $persistent_allowed && !empty( $_POST['permanent'] ) && ( !$this->options['permanent'] || $disabled_post_types != $this->options['disabled_post_types'] ) )
		$this->enter_permanent_mode();

	$this->options['disabled_post_types'] = $disabled_post_types;
	$this->options['permanent'] = $persistent_allowed && isset( $_POST['permanent'] );

	// Extra custom post types
	if( $this->networkactive && !empty( $_POST['extra_post_types'] ) ) {
		$extra_post_types = array_filter( array_map( 'sanitize_key', explode( ',', $_POST['extra_post_types'] ) ) );
		$this->options['extra_post_types'] = array_diff( $extra_post_types, array_keys( $types ) );	// Make sure we don't double up builtins
	}

	$this->update_options();
	$cache_message = WP_CACHE ? ' <strong>' . __( 'If a caching/performance plugin is active, please invalidate its cache to ensure that changes are reflected immediately.' ) . '</strong>' : '';
	echo '<div id="message" class="updated"><p>' . __( 'Options updated. Changes to the Admin Menu and Admin Bar will not appear until you leave or reload this page.', 'disable-comments' ) . $cache_message . '</p></div>';
}
?>
<style> .indent {padding-left: 2em} </style>
<div class="wrap">
<h1><?php _ex( 'Disable Comments', 'settings page title', 'disable-comments') ?></h1>
<?php
if( $this->networkactive )
	echo '<div class="updated"><p>' . __( '<em>Disable Comments</em> is Network Activated. The settings below will affect <strong>all sites</strong> in this network.', 'disable-comments') . '</p></div>';
if( WP_CACHE )
	echo '<div class="updated"><p>' . __( "It seems that a caching/performance plugin is active on this site. Please manually invalidate that plugin's cache after making any changes to the settings below.", 'disable-comments') . '</p></div>';
?>
<form action="" method="post" id="disable-comments">
<ul>
<li><label for="remove_everywhere"><input type="radio" id="remove_everywhere" name="mode" value="remove_everywhere" <?php checked( $this->options['remove_everywhere'] );?> /> <strong><?php _e( 'Everywhere', 'disable-comments') ?></strong>: <?php _e( 'Disable all comment-related controls and settings in WordPress.', 'disable-comments') ?></label>
	<p class="indent"><?php printf( __( '%s: This option is global and will affect your entire site. Use it only if you want to disable comments <em>everywhere</em>. A complete description of what this option does is <a href="%s" target="_blank">available here</a>.', 'disable-comments' ), '<strong style="color: #900">' . __('Warning', 'disable-comments') . '</strong>', 'https://wordpress.org/plugins/disable-comments/other_notes/' ); ?></p>
</li>
<li><label for="selected_types"><input type="radio" id="selected_types" name="mode" value="selected_types" <?php checked( ! $this->options['remove_everywhere'] );?> /> <strong><?php _e( 'On certain post types', 'disable-comments') ?></strong>:</label>
	<p></p>
	<ul class="indent" id="listoftypes">
		<?php foreach( $types as $k => $v ) echo "<li><label for='post-type-$k'><input type='checkbox' name='disabled_types[]' value='$k' ". checked( in_array( $k, $this->options['disabled_post_types'] ), true, false ) ." id='post-type-$k'> {$v->labels->name}</label></li>";?>
	</ul>
	<?php if( $this->networkactive ) :?>
	<p class="indent" id="extratypes"><?php _e( 'Only the built-in post types appear above. If you want to disable comments on other custom post types on the entire network, you can supply a comma-separated list of post types below (use the slug that identifies the post type).', 'disable-comments' ); ?>
	<br /><label><?php _e( 'Custom post types:', 'disable-comments' ); ?> <input type="text" name="extra_post_types" size="30" value="<?php echo implode( ', ', (array) $this->options['extra_post_types'] ); ?>" /></label></p>
	<?php endif; ?>
	<p class="indent"><?php _e( 'Disabling comments will also disable trackbacks and pingbacks. All comment-related fields will also be hidden from the edit/quick-edit screens of the affected posts. These settings cannot be overridden for individual posts.', 'disable-comments') ?></p>
</li>
</ul>

<?php if( $persistent_allowed && $this->options['permanent'] ): ?>
<h2><?php _e( 'Other options', 'disable-comments') ?></h2>
<ul>
	<li>
	<?php
	echo '<label for="permanent"><input type="checkbox" name="permanent" id="permanent" '. checked( $this->options['permanent'], true, false ) . '> <strong>' . __( 'Use persistent mode', 'disable-comments') . '</strong></label>';
	echo '<p class="indent">' . sprintf( __( '%s: <strong>This will make persistent changes to your database &mdash; comments will remain closed even if you later disable the plugin!</strong> You should not use it if you only want to disable comments temporarily. Please <a href="%s" target="_blank">read the FAQ</a> before selecting this option.', 'disable-comments'), '<strong style="color: #900">' . __('Warning', 'disable-comments') . '</strong>', 'https://wordpress.org/plugins/disable-comments/faq/' ) . '</p>';
	if( $this->networkactive )
		echo '<p class="indent">' . sprintf( __( '%s: Entering persistent mode on large multi-site networks requires a large number of database queries and can take a while. Use with caution!', 'disable-comments'), '<strong style="color: #900">' . __('Warning', 'disable-comments') . '</strong>' ) . '</p>';
	?>
	</li>
</ul>
<?php endif; ?>

<?php wp_nonce_field( 'disable-comments-admin' ); ?>
<p class="submit"><input class="button-primary" type="submit" name="submit" value="<?php _e( 'Save Changes', 'disable-comments') ?>"></p>
</form>
</div>
<script>
jQuery(document).ready(function($){
	function disable_comments_uihelper(){
		var indiv_bits = $("#listoftypes, #extratypes");
		if( $("#remove_everywhere").is(":checked") )
			indiv_bits.css("color", "#888").find(":input").attr("disabled", true );
		else
			indiv_bits.css("color", "#000").find(":input").attr("disabled", false );
	}

	$("#disable-comments :input").change(function(){
		$("#message").slideUp();
		disable_comments_uihelper();
	});

	disable_comments_uihelper();

	$("#permanent").change( function() {
		if( $(this).is(":checked") && ! confirm(<?php echo json_encode( sprintf( __( '%s: Selecting this option will make persistent changes to your database. Are you sure you want to enable it?', 'disable-comments'), __( 'Warning', 'disable-comments' ) ) );?>) )
			$(this).attr("checked", false );
	});
});
</script>
