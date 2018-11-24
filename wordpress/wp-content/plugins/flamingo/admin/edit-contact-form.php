<?php

// don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

if ( ! empty( $post->id ) ) {
	$nonce_action = 'flamingo-update-contact_' . $post->id;
} else {
	$nonce_action = 'flamingo-add-contact';
}

?>
<div class="wrap">

<h1><?php echo esc_html( __( 'Edit Contact', 'flamingo' ) ); ?></h1>

<?php do_action( 'flamingo_admin_updated_message', $post ); ?>

<form name="editcontact" id="editcontact" method="post" action="<?php echo esc_url( add_query_arg( array( 'post' => $post->id ), menu_page_url( 'flamingo', false ) ) ); ?>">
<?php
wp_nonce_field( $nonce_action );
wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );
?>

<div id="poststuff">
<div id="post-body" class="metabox-holder columns-2">

<div id="post-body-content">
	<div id="titlediv">
	<div id="titlewrap">
	<?php if ( ! empty( $post->id ) ) : ?>
	<input type="text" name="post_title" size="30" tabindex="1" value="<?php echo esc_attr( $post->email ); ?>" id="title" disabled="disabled" />
	<?php else : ?>
	<label class="hide-if-no-js" style="visibility:hidden" id="title-prompt-text" for="title"><?php echo esc_html( __( 'Enter email here', 'flamingo' ) ); ?></label>
	<input type="text" name="post_title" size="30" tabindex="1" value="<?php echo esc_attr( $post->email ); ?>" id="title" autocomplete="off" />
	<?php endif; ?>
	</div>
	</div>
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
