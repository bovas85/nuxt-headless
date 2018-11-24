<?php

add_filter( 'wp_privacy_personal_data_erasers',
	'flamingo_privacy_register_personal_data_erasers', 10, 1 );

function flamingo_privacy_register_personal_data_erasers( $erasers ) {
	return array_merge( (array) $erasers, array(
		'flamingo-contact' => array(
			'eraser_friendly_name' => __( 'Flamingo Address Book', 'flamingo' ),
			'callback' => 'flamingo_privacy_contact_eraser',
		),
		'flamingo-inbound' => array(
			'eraser_friendly_name' => __( 'Flamingo Inbound Messages', 'flamingo' ),
			'callback' => 'flamingo_privacy_inbound_eraser',
		),
	) );
}

function flamingo_privacy_contact_eraser( $email_address, $page = 1 ) {
	$number = 100;

	$posts = Flamingo_Contact::find( array(
		'meta_key' => '_email',
		'meta_value' => $email_address,
		'posts_per_page' => $number,
		'paged' => (int) $page,
	) );

	$items_removed = false;
	$items_retained = false;
	$messages = array();

	foreach ( (array) $posts as $post ) {
		if ( ! current_user_can( 'flamingo_delete_contact', $post->id ) ) {
			$items_retained = true;

			$messages = array(
				__( "Flamingo Address Book: You are not allowed to delete contact data.", 'flamingo' ),
			);

			continue;
		}

		if ( $post->delete() ) {
			$items_removed = true;
		} else {
			$items_retained = true;
		}
	}

	$done = Flamingo_Contact::$found_items < $number;

	return array(
		'items_removed' => $items_removed,
		'items_retained' => $items_retained,
		'messages' => array_map( 'esc_html', (array) $messages ),
		'done' => $done,
	);
}

function flamingo_privacy_inbound_eraser( $email_address, $page = 1 ) {
	$number = 100;

	$posts = Flamingo_Inbound_Message::find( array(
		'meta_key' => '_from_email',
		'meta_value' => $email_address,
		'posts_per_page' => $number,
		'paged' => (int) $page,
	) );

	$items_removed = false;
	$items_retained = false;
	$messages = array();

	foreach ( (array) $posts as $post ) {
		if ( ! current_user_can( 'flamingo_delete_inbound_message', $post->id ) ) {
			$items_retained = true;

			$messages = array(
				__( "Flamingo Inbound Messages: You are not allowed to delete inbound messages.", 'flamingo' ),
			);

			continue;
		}

		if ( $post->delete() ) {
			$items_removed = true;
		} else {
			$items_retained = true;
		}
	}

	$done = Flamingo_Inbound_Message::$found_items < $number;

	return array(
		'items_removed' => $items_removed,
		'items_retained' => $items_retained,
		'messages' => array_map( 'esc_html', (array) $messages ),
		'done' => $done,
	);
}
