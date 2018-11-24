<?php
/**
** Module for WordPress comment.
**/

add_action( 'wp_insert_comment', 'flamingo_insert_comment' );

function flamingo_insert_comment( $comment_id ) {
	$comment = get_comment( $comment_id );

	if ( 1 != (int) $comment->comment_approved ) {
		return;
	}

	Flamingo_Contact::add( array(
		'email' => $comment->comment_author_email,
		'name' => $comment->comment_author,
		'channel' => 'comment',
	) );
}

add_action( 'transition_comment_status',
	'flamingo_transition_comment_status', 10, 3 );

function flamingo_transition_comment_status( $new_status, $old_status, $comment ) {
	if ( 'approved' != $new_status ) {
		return;
	}

	$email = $comment->comment_author_email;
	$name = $comment->comment_author;

	Flamingo_Contact::add( array(
		'email' => $email,
		'name' => $name,
		'channel' => 'comment',
	) );
}

/* Collect contact info from existing comments when activating plugin */
add_action( 'activate_' . FLAMINGO_PLUGIN_BASENAME,
	'flamingo_collect_contacts_from_comments' );

function flamingo_collect_contacts_from_comments() {
	$comments = get_comments( array(
		'status' => 'approve',
		'type' => 'comment',
		'number' => 20,
	) );

	foreach ( $comments as $comment ) {
		$email = $comment->comment_author_email;
		$name = $comment->comment_author;

		if ( empty( $email ) ) {
			continue;
		}

		Flamingo_Contact::add( array(
			'email' => $email,
			'name' => $name,
			'channel' => 'comment',
		) );
	}
}
