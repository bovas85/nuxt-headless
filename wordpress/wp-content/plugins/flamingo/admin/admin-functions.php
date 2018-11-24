<?php

function flamingo_current_action() {
	if ( isset( $_REQUEST['delete_all'] )
	|| isset( $_REQUEST['delete_all2'] ) ) {
		return 'delete_all';
	}

	if ( isset( $_REQUEST['action'] ) && -1 != $_REQUEST['action'] ) {
		return $_REQUEST['action'];
	}

	if ( isset( $_REQUEST['action2'] ) && -1 != $_REQUEST['action2'] ) {
		return $_REQUEST['action2'];
	}

	return false;
}

function flamingo_get_all_ids_in_trash( $post_type ) {
	global $wpdb;

	$q = "SELECT ID FROM $wpdb->posts WHERE post_status = 'trash'"
		. $wpdb->prepare( " AND post_type = %s", $post_type );

	return $wpdb->get_col( $q );
}
