<?php

// don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

if ( ! empty( $post->id ) ) {
	$nonce_action = 'flamingo-update-inbound_' . $post->id;
} else {
	$nonce_action = 'flamingo-add-inbound';
}

?>
<div class="wrap">

<h1><?php echo esc_html( __( 'Inbound Message', 'flamingo' ) ); ?></h1>

<?php do_action( 'flamingo_admin_updated_message', $post ); ?>

<form name="editinbound" id="editinbound" method="post" action="<?php echo esc_url( add_query_arg( array( 'post' => $post->id ), menu_page_url( 'flamingo_inbound', false ) ) ); ?>">
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

	<tr class="message-subject">
		<th><?php echo esc_html( __( 'Subject', 'flamingo' ) ); ?>:</th>
		<td><?php echo esc_html( $post->subject ); ?></td>
	</tr>

	<tr class="message-from">
		<th><?php echo esc_html( __( 'From', 'flamingo' ) ); ?>:</th>
		<td><?php if ( ! empty( $post->from_email ) ) { ?><a href="<?php
	echo esc_url( add_query_arg(
		array(
			's' => $post->from_email,
		),
		menu_page_url( 'flamingo', false )
	) );
	?>" aria-label="<?php echo esc_attr( $post->from ); ?>"><?php echo esc_html( $post->from ); ?></a><?php } else { echo esc_html( $post->from ); } ?></td>
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

<?php if ( $post->id ) : ?>
<input type="hidden" name="action" value="save" />
<input type="hidden" name="post" value="<?php echo (int) $post->id; ?>" />
<?php else: ?>
<input type="hidden" name="action" value="add" />
<?php endif; ?>
</form>

</div><!-- .wrap -->
