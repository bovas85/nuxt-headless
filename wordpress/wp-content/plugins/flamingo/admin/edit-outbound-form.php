<?php

// don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

if ( ! empty( $post->id ) ) {
	$nonce_action = 'flamingo-update-outbound_' . $post->id;
} else {
	$nonce_action = 'flamingo-add-outbound';
}

?>
<div class="wrap">

<h1><?php
	if ( 'new' == $action ) {
		echo esc_html( __( 'Compose a Message', 'flamingo' ) );
	} else {
		echo esc_html( __( 'Outbound Message', 'flamingo' ) );
	}
?></h1>

<?php do_action( 'flamingo_admin_updated_message', $post ); ?>

<form name="editoutbound" id="editoutbound" method="post" action="<?php echo esc_url( add_query_arg( array( 'post' => $post->id ), menu_page_url( 'flamingo_outbound', false ) ) ); ?>">
<?php
wp_nonce_field( $nonce_action );
wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );
?>

<div id="poststuff">
<div id="post-body" class="metabox-holder columns-2">

<div id="post-body-content">
	<table class="message-main-fields">
	<tbody>

	<tr class="message-to">
		<th><?php echo esc_html( __( 'To', 'flamingo' ) ); ?>:</th>
		<td><?php if ( $contact_tag ) : ?>
			<?php echo esc_html( $contact_tag->name ); ?>
			<input type="hidden" name="contact-tag-id" value="<?php echo absint( $contact_tag->term_id ); ?>" />
		<?php endif; ?></td>
	</tr>

	<tr class="message-from">
		<th><?php echo esc_html( __( 'From', 'flamingo' ) ); ?>:</th>
		<td><input type="text" name="from" class="large-text" value="" /></td>
	</tr>

	<tr class="message-subject">
		<th><?php echo esc_html( __( 'Subject', 'flamingo' ) ); ?>:</th>
		<td><input type="text" name="subject" class="large-text" value="" /></td>
	</tr>

	<tr class="message-body">
		<th><?php echo esc_html( __( 'Body', 'flamingo' ) ); ?>:</th>
		<td><textarea name="body" class="large-text" cols="50" rows="10"></textarea></td>
	</tr>

	</tbody>
	</table>
</div><!-- #post-body-content -->

<div id="postbox-container-1" class="postbox-container">
<?php
	do_meta_boxes( null, 'side', $post );
?>
</div><!-- #postbox-container-1 -->

<div id="postbox-container-2" class="postbox-container">
<?php
	do_meta_boxes( null, 'normal', $post );
	do_meta_boxes( null, 'advanced', $post );
?>
</div><!-- #postbox-container-2 -->

</div><!-- #post-body -->
<br class="clear" />

</div><!-- #poststuff -->

<input type="hidden" name="action" value="save" />
<?php if ( ! empty( $post->id ) ) : ?>
<input type="hidden" name="post" value="<?php echo (int) $post->id; ?>" />
<?php endif; ?>
</form>

</div><!-- .wrap -->
