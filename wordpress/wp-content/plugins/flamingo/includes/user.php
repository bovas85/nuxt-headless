<?php
/**
** Module for WordPress user.
**/

add_action( 'profile_update', 'flamingo_user_profile_update' );
add_action( 'user_register', 'flamingo_user_profile_update' );

function flamingo_user_profile_update( $user_id ) {
	$user = new WP_User( $user_id );

	$email = $user->user_email;
	$name = $user->display_name;

	$props = array(
		'first_name' => $user->first_name,
		'last_name' => $user->last_name,
	);

	if ( ! empty( $email ) ) {
		Flamingo_Contact::add( array(
			'email' => $email,
			'name' => $name,
			'props' => $props,
			'channel' => 'user',
		) );
	}
}

/* Collect contact info from existing users when activating plugin */
add_action( 'activate_' . FLAMINGO_PLUGIN_BASENAME,
	'flamingo_collect_contacts_from_users' );

function flamingo_collect_contacts_from_users() {
	$users = get_users( array(
		'number' => 20,
	) );

	foreach ( $users as $user ) {
		$email = $user->user_email;
		$name = $user->display_name;

		if ( empty( $email ) ) {
			continue;
		}

		$props = array(
			'first_name' => empty( $user->first_name ) ? '' : $user->first_name,
			'last_name' => empty( $user->last_name ) ? '' : $user->last_name,
		);

		Flamingo_Contact::add( array(
			'email' => $email,
			'name' => $name,
			'props' => $props,
			'channel' => 'user',
		) );
	}
}
