<?php

require_once FLAMINGO_PLUGIN_DIR . '/admin/admin-functions.php';
require_once FLAMINGO_PLUGIN_DIR . '/admin/includes/privacy.php';

add_action( 'admin_menu', 'flamingo_admin_menu', 8 );

function flamingo_admin_menu() {
	global $_wp_last_object_menu;

	$_wp_last_object_menu++;

	add_menu_page(
		__( 'Flamingo Address Book', 'flamingo' ),
		__( 'Flamingo', 'flamingo' ),
		'flamingo_edit_contacts', 'flamingo',
		'flamingo_contact_admin_page', 'dashicons-feedback',
		$_wp_last_object_menu );

	$contact_admin = add_submenu_page( 'flamingo',
		__( 'Flamingo Address Book', 'flamingo' ),
		__( 'Address Book', 'flamingo' ),
		'flamingo_edit_contacts', 'flamingo',
		'flamingo_contact_admin_page' );

	add_action( 'load-' . $contact_admin, 'flamingo_load_contact_admin' );

	$inbound_admin = add_submenu_page( 'flamingo',
		__( 'Flamingo Inbound Messages', 'flamingo' ),
		__( 'Inbound Messages', 'flamingo' ),
		'flamingo_edit_inbound_messages', 'flamingo_inbound',
		'flamingo_inbound_admin_page' );

	add_action( 'load-' . $inbound_admin, 'flamingo_load_inbound_admin' );
}

add_filter( 'set-screen-option', 'flamingo_set_screen_options', 10, 3 );

function flamingo_set_screen_options( $result, $option, $value ) {
	$flamingo_screens = array(
		'toplevel_page_flamingo_per_page',
		'flamingo_page_flamingo_inbound_per_page',
	);

	if ( in_array( $option, $flamingo_screens ) ) {
		$result = $value;
	}

	return $result;
}

add_action( 'admin_enqueue_scripts', 'flamingo_admin_enqueue_scripts' );

function flamingo_admin_enqueue_scripts( $hook_suffix ) {
	if ( false === strpos( $hook_suffix, 'flamingo' ) ) {
		return;
	}

	wp_enqueue_style( 'flamingo-admin',
		flamingo_plugin_url( 'admin/css/style.css' ),
		array(), FLAMINGO_VERSION, 'all' );

	if ( is_rtl() ) {
		wp_enqueue_style( 'flamingo-admin-rtl',
			flamingo_plugin_url( 'admin/css/style-rtl.css' ),
			array(), FLAMINGO_VERSION, 'all' );
	}

	wp_enqueue_script( 'flamingo-admin',
		flamingo_plugin_url( 'admin/js/script.js' ),
		array( 'postbox' ), FLAMINGO_VERSION, true );

	$current_screen = get_current_screen();

	wp_localize_script( 'flamingo-admin', 'flamingo', array(
		'screenId' => $current_screen->id,
	) );
}

/* Updated Message */

add_action( 'flamingo_admin_updated_message',
	'flamingo_admin_updated_message' );

function flamingo_admin_updated_message() {
	if ( empty( $_REQUEST['message'] ) ) {
		return;
	}

	if ( 'contactupdated' == $_REQUEST['message'] ) {
		$message = __( 'Contact updated.', 'flamingo' );
	} elseif ( 'contactdeleted' == $_REQUEST['message'] ) {
		$message = __( 'Contact deleted.', 'flamingo' );
	} elseif ( 'inboundupdated' == $_REQUEST['message'] ) {
		$message = __( 'Messages updated.', 'flamingo' );
	} elseif ( 'inboundtrashed' == $_REQUEST['message'] ) {
		$message = __( 'Messages trashed.', 'flamingo' );
	} elseif ( 'inbounduntrashed' == $_REQUEST['message'] ) {
		$message = __( 'Messages restored.', 'flamingo' );
	} elseif ( 'inbounddeleted' == $_REQUEST['message'] ) {
		$message = __( 'Messages deleted.', 'flamingo' );
	} elseif ( 'inboundspammed' == $_REQUEST['message'] ) {
		$message = __( 'Messages got marked as spam.', 'flamingo' );
	} elseif ( 'inboundunspammed' == $_REQUEST['message'] ) {
		$message = __( 'Messages got marked as not spam.', 'flamingo' );
	} elseif ( 'outboundupdated' == $_REQUEST['message'] ) {
		$message = __( 'Messages updated.', 'flamingo' );
	}

	if ( isset( $message ) && '' !== $message ) {
		echo sprintf( '<div id="message" class="notice notice-success is-dismissible"><p>%s</p></div>', esc_html( $message ) );
	}
}

/* Contact */

function flamingo_load_contact_admin() {
	$action = flamingo_current_action();

	$redirect_to = menu_page_url( 'flamingo', false );

	if ( 'save' == $action && ! empty( $_REQUEST['post'] ) ) {
		$post = new Flamingo_Contact( $_REQUEST['post'] );

		if ( ! empty( $post ) ) {
			if ( ! current_user_can( 'flamingo_edit_contact', $post->id ) ) {
				wp_die( __( 'You are not allowed to edit this item.', 'flamingo' ) );
			}

			check_admin_referer( 'flamingo-update-contact_' . $post->id );

			$post->props = (array) $_POST['contact'];

			$post->name = trim( $_POST['contact']['name'] );

			$post->tags = ! empty( $_POST['tax_input'][Flamingo_Contact::contact_tag_taxonomy] )
				? explode( ',', $_POST['tax_input'][Flamingo_Contact::contact_tag_taxonomy] )
				: array();

			$post->save();

			$redirect_to = add_query_arg(
				array(
					'action' => 'edit',
					'post' => $post->id,
					'message' => 'contactupdated',
				), $redirect_to
			);
		}

		wp_safe_redirect( $redirect_to );
		exit();
	}

	if ( 'delete' == $action && ! empty( $_REQUEST['post'] ) ) {
		if ( ! is_array( $_REQUEST['post'] ) ) {
			check_admin_referer( 'flamingo-delete-contact_' . $_REQUEST['post'] );
		} else {
			check_admin_referer( 'bulk-posts' );
		}

		$deleted = 0;

		foreach ( (array) $_REQUEST['post'] as $post ) {
			$post = new Flamingo_Contact( $post );

			if ( empty( $post ) ) {
				continue;
			}

			if ( ! current_user_can( 'flamingo_delete_contact', $post->id ) ) {
				wp_die( __( 'You are not allowed to delete this item.', 'flamingo' ) );
			}

			if ( ! $post->delete() ) {
				wp_die( __( 'Error in deleting.', 'flamingo' ) );
			}

			$deleted += 1;
		}

		if ( ! empty( $deleted ) ) {
			$redirect_to = add_query_arg(
				array( 'message' => 'contactdeleted' ), $redirect_to );
		}

		wp_safe_redirect( $redirect_to );
		exit();
	}

	if ( ! empty( $_GET['export'] ) ) {
		check_admin_referer( 'bulk-posts' );

		$sitename = sanitize_key( get_bloginfo( 'name' ) );

		$filename = ( empty( $sitename ) ? '' : $sitename . '-' )
			. sprintf( 'flamingo-contact-%s.csv', date( 'Y-m-d' ) );

		header( 'Content-Description: File Transfer' );
		header( "Content-Disposition: attachment; filename=$filename" );
		header( 'Content-Type: text/csv; charset=' . get_option( 'blog_charset' ) );

		$labels = array(
			__( 'Email', 'flamingo' ),
			__( 'Full name', 'flamingo' ),
			__( 'First name', 'flamingo' ),
			__( 'Last name', 'flamingo' ),
		);

		echo flamingo_csv_row( $labels );

		$args = array(
			'posts_per_page' => -1,
			'orderby' => 'meta_value',
			'order' => 'ASC',
			'meta_key' => '_email',
		);

		if ( ! empty( $_GET['s'] ) ) {
			$args['s'] = $_GET['s'];
		}

		if ( ! empty( $_GET['orderby'] ) ) {
			if ( 'email' == $_GET['orderby'] ) {
				$args['meta_key'] = '_email';
			} elseif ( 'name' == $_GET['orderby'] ) {
				$args['meta_key'] = '_name';
			}
		}

		if ( ! empty( $_GET['order'] ) && 'asc' == strtolower( $_GET['order'] ) ) {
			$args['order'] = 'ASC';
		}

		if ( ! empty( $_GET['contact_tag_id'] ) ) {
			$args['contact_tag_id'] = explode( ',', $_GET['contact_tag_id'] );
		}

		$items = Flamingo_Contact::find( $args );

		foreach ( $items as $item ) {
			$row = array(
				$item->email,
				$item->get_prop( 'name' ),
				$item->get_prop( 'first_name' ),
				$item->get_prop( 'last_name' ),
			);

			echo "\r\n" . flamingo_csv_row( $row );
		}

		exit();
	}

	if ( ! empty( $_GET['sendmail'] )
	&& ! empty( $_REQUEST['contact_tag_id'] ) ) {
		$redirect_to = menu_page_url( 'flamingo_outbound', false );

		$redirect_to = add_query_arg(
			array(
				'action' => 'new',
				'contact_tag_id' => absint( $_REQUEST['contact_tag_id'] ),
			), $redirect_to
		);

		wp_safe_redirect( $redirect_to );
		exit();
	}

	if ( 'edit' == $action ) {
		$post_id = isset( $_REQUEST['post'] ) ? (int) $_REQUEST['post'] : 0;

		if ( ! $post_id ) {
			wp_safe_redirect( $redirect_to );
			exit();
		}

		if ( ! current_user_can( 'flamingo_edit_contact', $post_id )
		|| Flamingo_Contact::post_type !== get_post_type( $post_id ) ) {
			wp_die( __( "You are not allowed to edit this item.", 'flamingo' ) );
		}

		add_meta_box( 'submitdiv', __( 'Save', 'flamingo' ),
			'flamingo_contact_submit_meta_box', null, 'side', 'core' );

		add_meta_box( 'contacttagsdiv', __( 'Tags', 'flamingo' ),
			'flamingo_contact_tags_meta_box', null, 'side', 'core' );

		add_meta_box( 'contactnamediv', __( 'Name', 'flamingo' ),
			'flamingo_contact_name_meta_box', null, 'normal', 'core' );

	} else {
		if ( ! class_exists( 'Flamingo_Contacts_List_Table' ) ) {
			require_once FLAMINGO_PLUGIN_DIR
				. '/admin/includes/class-contacts-list-table.php';
		}

		$current_screen = get_current_screen();

		add_filter( 'manage_' . $current_screen->id . '_columns',
			array( 'Flamingo_Contacts_List_Table', 'define_columns' ) );

		add_screen_option( 'per_page', array(
			'label' => __( 'Contacts', 'flamingo' ),
			'default' => 20,
		) );
	}
}

function flamingo_contact_admin_page() {
	if ( 'edit' == flamingo_current_action() ) {
		flamingo_contact_edit_page();
		return;
	}

	$list_table = new Flamingo_Contacts_List_Table();
	$list_table->prepare_items();

?>
<div class="wrap">

<h1 class="wp-heading-inline"><?php
	echo esc_html( __( 'Flamingo Address Book', 'flamingo' ) );
?></h1>

<?php
	if ( ! empty( $_REQUEST['s'] ) ) {
		echo sprintf( '<span class="subtitle">'
			. __( 'Search results for &#8220;%s&#8221;', 'flamingo' )
			. '</span>', esc_html( $_REQUEST['s'] ) );
	}
?>

<hr class="wp-header-end">

<?php do_action( 'flamingo_admin_updated_message' ); ?>

<form method="get" action="">
	<input type="hidden" name="page" value="<?php echo esc_attr( $_REQUEST['page'] ); ?>" />
	<?php $list_table->search_box( __( 'Search Contacts', 'flamingo' ), 'flamingo-contact' ); ?>
	<?php $list_table->display(); ?>
</form>

</div>
<?php
}

function flamingo_contact_edit_page() {
	$post = new Flamingo_Contact( $_REQUEST['post'] );

	if ( empty( $post ) ) {
		return;
	}

	require_once FLAMINGO_PLUGIN_DIR . '/admin/includes/meta-boxes.php';

	include FLAMINGO_PLUGIN_DIR . '/admin/edit-contact-form.php';
}

/* Inbound Messages */

function flamingo_load_inbound_admin() {
	$action = flamingo_current_action();

	$redirect_to = menu_page_url( 'flamingo_inbound', false );

	if ( 'save' == $action && ! empty( $_REQUEST['post'] ) ) {
		$post = new Flamingo_Inbound_Message( $_REQUEST['post'] );

		if ( ! empty( $post ) ) {
			if ( ! current_user_can( 'flamingo_edit_inbound_message', $post->id ) ) {
				wp_die( __( 'You are not allowed to edit this item.', 'flamingo' ) );
			}

			check_admin_referer( 'flamingo-update-inbound_' . $post->id );

			$status = isset( $_POST['inbound']['status'] )
				? $_POST['inbound']['status'] : '';

			if ( ! $post->spam && 'spam' === $status ) {
				$post->spam();
			} elseif ( $post->spam && 'ham' === $status ) {
				$post->unspam();
			}

			$redirect_to = add_query_arg(
				array(
					'action' => 'edit',
					'post' => $post->id,
					'message' => 'inboundupdated',
				), $redirect_to
			);
		}

		wp_safe_redirect( $redirect_to );
		exit();
	}

	if ( 'trash' == $action && ! empty( $_REQUEST['post'] ) ) {
		if ( ! is_array( $_REQUEST['post'] ) ) {
			check_admin_referer(
				'flamingo-trash-inbound-message_' . $_REQUEST['post'] );
		} else {
			check_admin_referer( 'bulk-posts' );
		}

		$trashed = 0;

		foreach ( (array) $_REQUEST['post'] as $post ) {
			$post = new Flamingo_Inbound_Message( $post );

			if ( empty( $post ) ) {
				continue;
			}

			if ( ! current_user_can(
			'flamingo_delete_inbound_message', $post->id ) ) {
				wp_die( __( 'You are not allowed to move this item to the Trash.', 'flamingo' ) );
			}

			if ( ! $post->trash() ) {
				wp_die( __( 'Error in moving to Trash.', 'flamingo' ) );
			}

			$trashed += 1;
		}

		if ( ! empty( $trashed ) ) {
			$redirect_to = add_query_arg(
				array( 'message' => 'inboundtrashed' ), $redirect_to );
		}

		wp_safe_redirect( $redirect_to );
		exit();
	}

	if ( 'untrash' == $action && ! empty( $_REQUEST['post'] ) ) {
		if ( ! is_array( $_REQUEST['post'] ) ) {
			check_admin_referer(
				'flamingo-untrash-inbound-message_' . $_REQUEST['post'] );
		} else {
			check_admin_referer( 'bulk-posts' );
		}

		$untrashed = 0;

		foreach ( (array) $_REQUEST['post'] as $post ) {
			$post = new Flamingo_Inbound_Message( $post );

			if ( empty( $post ) ) {
				continue;
			}

			if ( ! current_user_can(
			'flamingo_delete_inbound_message', $post->id ) ) {
				wp_die( __( 'You are not allowed to restore this item from the Trash.', 'flamingo' ) );
			}

			if ( ! $post->untrash() ) {
				wp_die( __( 'Error in restoring from Trash.', 'flamingo' ) );
			}

			$untrashed += 1;
		}

		if ( ! empty( $untrashed ) ) {
			$redirect_to = add_query_arg(
				array( 'message' => 'inbounduntrashed' ), $redirect_to );
		}

		wp_safe_redirect( $redirect_to );
		exit();
	}

	if ( 'delete_all' == $action ) {
		check_admin_referer( 'bulk-posts' );

		$_REQUEST['post'] = flamingo_get_all_ids_in_trash(
			Flamingo_Inbound_Message::post_type );

		$action = 'delete';
	}

	if ( 'delete' == $action && ! empty( $_REQUEST['post'] ) ) {
		if ( ! is_array( $_REQUEST['post'] ) ) {
			check_admin_referer(
				'flamingo-delete-inbound-message_' . $_REQUEST['post'] );
		} else {
			check_admin_referer( 'bulk-posts' );
		}

		$deleted = 0;

		foreach ( (array) $_REQUEST['post'] as $post ) {
			$post = new Flamingo_Inbound_Message( $post );

			if ( empty( $post ) ) {
				continue;
			}

			if ( ! current_user_can(
			'flamingo_delete_inbound_message', $post->id ) ) {
				wp_die( __( 'You are not allowed to delete this item.', 'flamingo' ) );
			}

			if ( ! $post->delete() ) {
				wp_die( __( 'Error in deleting.', 'flamingo' ) );
			}

			$deleted += 1;
		}

		if ( ! empty( $deleted ) ) {
			$redirect_to = add_query_arg(
				array( 'message' => 'inbounddeleted' ), $redirect_to );
		}

		wp_safe_redirect( $redirect_to );
		exit();
	}

	if ( 'spam' == $action && ! empty( $_REQUEST['post'] ) ) {
		if ( ! is_array( $_REQUEST['post'] ) ) {
			check_admin_referer(
				'flamingo-spam-inbound-message_' . $_REQUEST['post'] );
		} else {
			check_admin_referer( 'bulk-posts' );
		}

		$submitted = 0;

		foreach ( (array) $_REQUEST['post'] as $post ) {
			$post = new Flamingo_Inbound_Message( $post );

			if ( empty( $post ) ) {
				continue;
			}

			if ( ! current_user_can( 'flamingo_spam_inbound_message', $post->id ) ) {
				wp_die( __( 'You are not allowed to spam this item.', 'flamingo' ) );
			}

			if ( $post->spam() ) {
				$submitted += 1;
			}
		}

		if ( ! empty( $submitted ) ) {
			$redirect_to = add_query_arg(
				array( 'message' => 'inboundspammed' ), $redirect_to );
		}

		wp_safe_redirect( $redirect_to );
		exit();
	}

	if ( 'unspam' == $action && ! empty( $_REQUEST['post'] ) ) {
		if ( ! is_array( $_REQUEST['post'] ) ) {
			check_admin_referer(
				'flamingo-unspam-inbound-message_' . $_REQUEST['post'] );
		} else {
			check_admin_referer( 'bulk-posts' );
		}

		$submitted = 0;

		foreach ( (array) $_REQUEST['post'] as $post ) {
			$post = new Flamingo_Inbound_Message( $post );

			if ( empty( $post ) ) {
				continue;
			}

			if ( ! current_user_can(
			'flamingo_unspam_inbound_message', $post->id ) ) {
				wp_die( __( 'You are not allowed to unspam this item.', 'flamingo' ) );
			}

			if ( $post->unspam() ) {
				$submitted += 1;
			}
		}

		if ( ! empty( $submitted ) ) {
			$redirect_to = add_query_arg(
				array( 'message' => 'inboundunspammed' ), $redirect_to );
		}

		wp_safe_redirect( $redirect_to );
		exit();
	}

	if ( ! empty( $_GET['export'] ) ) {
		check_admin_referer( 'bulk-posts' );

		$sitename = sanitize_key( get_bloginfo( 'name' ) );

		$filename = ( empty( $sitename ) ? '' : $sitename . '-' )
			. sprintf( 'flamingo-inbound-%s.csv', date( 'Y-m-d' ) );

		header( 'Content-Description: File Transfer' );
		header( "Content-Disposition: attachment; filename=$filename" );
		header( 'Content-Type: text/csv; charset=' . get_option( 'blog_charset' ) );

		$args = array(
			'posts_per_page' => -1,
			'orderby' => 'date',
			'order' => 'DESC',
		);

		if ( ! empty( $_REQUEST['s'] ) ) {
			$args['s'] = $_REQUEST['s'];
		}

		if ( ! empty( $_REQUEST['orderby'] ) ) {
			if ( 'subject' == $_REQUEST['orderby'] ) {
				$args['meta_key'] = '_subject';
				$args['orderby'] = 'meta_value';
			} elseif ( 'from' == $_REQUEST['orderby'] ) {
				$args['meta_key'] = '_from';
				$args['orderby'] = 'meta_value';
			}
		}

		if ( ! empty( $_REQUEST['order'] )
		&& 'asc' == strtolower( $_REQUEST['order'] ) ) {
			$args['order'] = 'ASC';
		}

		if ( ! empty( $_REQUEST['m'] ) ) {
			$args['m'] = $_REQUEST['m'];
		}

		if ( ! empty( $_REQUEST['channel_id'] ) ) {
			$args['channel_id'] = $_REQUEST['channel_id'];
		}

		if ( ! empty( $_REQUEST['channel'] ) ) {
			$args['channel'] = $_REQUEST['channel'];
		}

		$items = Flamingo_Inbound_Message::find( $args );

		if ( empty( $items ) ) {
			exit();
		}

		$labels = array_keys( $items[0]->fields );

		echo flamingo_csv_row(
			array_merge( $labels, array( __( 'Date', 'flamingo' ) ) ) );

		foreach ( $items as $item ) {
			$row = array();

			foreach ( $labels as $label ) {
				$col = isset( $item->fields[$label] ) ? $item->fields[$label] : '';

				if ( is_array( $col ) ) {
					$col = flamingo_array_flatten( $col );
					$col = array_filter( array_map( 'trim', $col ) );
					$col = implode( ', ', $col );
				}

				$row[] = $col;
			}

			$row[] = get_post_time( 'c', true, $item->id ); // Date

			echo "\r\n" . flamingo_csv_row( $row );
		}

		exit();
	}

	if ( 'edit' == $action ) {
		$post_id = isset( $_REQUEST['post'] ) ? (int) $_REQUEST['post'] : 0;

		if ( ! $post_id ) {
			wp_safe_redirect( $redirect_to );
			exit();
		}

		if ( ! current_user_can( 'flamingo_edit_inbound_message', $post_id )
		|| Flamingo_Inbound_Message::post_type !== get_post_type( $post_id ) ) {
			wp_die( __( "You are not allowed to edit this item.", 'flamingo' ) );
		}

		$post = new Flamingo_Inbound_Message( $post_id );

		add_meta_box( 'submitdiv', __( 'Status', 'flamingo' ),
			'flamingo_inbound_submit_meta_box', null, 'side', 'core' );

		if ( ! empty( $post->fields ) ) {
			add_meta_box( 'inboundfieldsdiv', __( 'Fields', 'flamingo' ),
				'flamingo_inbound_fields_meta_box', null, 'normal', 'core' );
		}

		if ( ! empty( $post->consent ) ) {
			add_meta_box( 'inboundconsentdiv', __( 'Consent', 'flamingo' ),
				'flamingo_inbound_consent_meta_box', null, 'normal', 'core' );
		}

		if ( ! empty( $post->meta ) ) {
			add_meta_box( 'inboundmetadiv', __( 'Meta', 'flamingo' ),
				'flamingo_inbound_meta_meta_box', null, 'normal', 'core' );
		}

	} else {
		if ( ! class_exists( 'Flamingo_Inbound_Messages_List_Table' ) )
			require_once FLAMINGO_PLUGIN_DIR . '/admin/includes/class-inbound-messages-list-table.php';

		$current_screen = get_current_screen();

		add_filter( 'manage_' . $current_screen->id . '_columns',
			array( 'Flamingo_Inbound_Messages_List_Table', 'define_columns' ) );

		add_screen_option( 'per_page', array(
			'label' => __( 'Messages', 'flamingo' ),
			'default' => 20,
		) );
	}
}

function flamingo_inbound_admin_page() {
	if ( 'edit' == flamingo_current_action() ) {
		flamingo_inbound_edit_page();
		return;
	}

	$list_table = new Flamingo_Inbound_Messages_List_Table();
	$list_table->prepare_items();

?>
<div class="wrap">

<h1 class="wp-heading-inline"><?php
	echo esc_html( __( 'Inbound Messages', 'flamingo' ) );
?></h1>

<?php
	if ( ! empty( $_REQUEST['s'] ) ) {
		echo sprintf( '<span class="subtitle">'
			. __( 'Search results for &#8220;%s&#8221;', 'flamingo' )
			. '</span>', esc_html( $_REQUEST['s'] ) );
	}
?>

<hr class="wp-header-end">

<?php do_action( 'flamingo_admin_updated_message' ); ?>

<?php $list_table->views(); ?>

<form method="get" action="">
	<input type="hidden" name="page" value="<?php echo esc_attr( $_REQUEST['page'] ); ?>" />
	<?php $list_table->search_box( __( 'Search Messages', 'flamingo' ), 'flamingo-inbound' ); ?>
	<?php $list_table->display(); ?>
</form>

</div>
<?php
}

function flamingo_inbound_edit_page() {
	$post = new Flamingo_Inbound_Message( $_REQUEST['post'] );

	if ( empty( $post ) ) {
		return;
	}

	require_once FLAMINGO_PLUGIN_DIR . '/admin/includes/meta-boxes.php';

	include FLAMINGO_PLUGIN_DIR . '/admin/edit-inbound-form.php';

}

/* Outbound Messages */

function flamingo_load_outbound_admin() {
	$action = flamingo_current_action();

	$redirect_to = menu_page_url( 'flamingo_outbound', false );

	$post_id = ! empty( $_REQUEST['post'] ) ? $_REQUEST['post'] : '';

	if ( 'save' == $action ) {
		if ( $post_id ) {
			check_admin_referer( 'flamingo-update-outbound_' . $post_id );
		} else {
			check_admin_referer( 'flamingo-add-outbound' );
		}

		if ( ! empty( $_REQUEST['send'] ) ) {
			// send mail
		}

		if ( $post_id ) {
			if ( ! current_user_can( 'flamingo_edit_outbound_message', $post_id ) ) {
				wp_die( __( 'You are not allowed to edit this item.', 'flamingo' ) );
			}

//			$post = new Flamingo_Outbound_Message( $post_id );
		} else {
//			$post = Flamingo_Outbound_Message::add();
		}

		//$post->save();

		$redirect_to = add_query_arg(
			array(
				'action' => 'edit',
				//'post' => $post->id,
				'message' => 'outboundupdated',
			), $redirect_to
		);

		wp_safe_redirect( $redirect_to );
		exit();
	}

	if ( 'new' == $action ) {
		add_meta_box( 'submitdiv', __( 'Send', 'flamingo' ),
			'flamingo_outbound_submit_meta_box', null, 'side', 'core' );

	} else {
		if ( ! class_exists( 'Flamingo_Outbound_Messages_List_Table' ) )
			require_once FLAMINGO_PLUGIN_DIR . '/admin/includes/class-outbound-messages-list-table.php';

		$current_screen = get_current_screen();

		add_filter( 'manage_' . $current_screen->id . '_columns',
			array( 'Flamingo_Outbound_Messages_List_Table', 'define_columns' ) );

		add_screen_option( 'per_page', array(
			'label' => __( 'Messages', 'flamingo' ),
			'default' => 20,
		) );
	}
}

function flamingo_outbound_admin_page() {
	if ( 'new' == flamingo_current_action() ) {
		flamingo_outbound_edit_page();
		return;
	}

	$list_table = new Flamingo_Outbound_Messages_List_Table();
	$list_table->prepare_items();

?>
<div class="wrap">

<h1 class="wp-heading-inline"><?php
	echo esc_html( __( 'Outbound Messages', 'flamingo' ) );
?></h1>

<?php
	if ( ! empty( $_REQUEST['s'] ) ) {
		echo sprintf( '<span class="subtitle">'
			. __( 'Search results for &#8220;%s&#8221;', 'flamingo' )
			. '</span>', esc_html( $_REQUEST['s'] ) );
	}
?>

<hr class="wp-header-end">

<?php do_action( 'flamingo_admin_updated_message' ); ?>

<?php $list_table->views(); ?>

<form method="get" action="">
	<input type="hidden" name="page" value="<?php echo esc_attr( $_REQUEST['page'] ); ?>" />
	<?php $list_table->search_box( __( 'Search Messages', 'flamingo' ), 'flamingo-outbound' ); ?>
	<?php $list_table->display(); ?>
</form>

</div>
<?php
}

function flamingo_outbound_edit_page() {
	$action = flamingo_current_action();
	$post = null;

	if ( 'edit' == $action ) {
		$post = new Flamingo_Outbound_Message( $_REQUEST['post'] );

		if ( empty( $post ) ) {
			return;
		}
	} else { // maybe 'new' == $action
		if ( ! empty( $_REQUEST['contact_tag_id'] ) ) {
			$tag_id = explode( ',', $_REQUEST['contact_tag_id'] );

			$contact_tag = get_term( $tag_id[0],
				Flamingo_Contact::contact_tag_taxonomy );

			if ( empty( $contact_tag ) || is_wp_error( $contact_tag ) ) {
				$contact_tag = null;
			}
		}
	}

	require_once FLAMINGO_PLUGIN_DIR . '/admin/includes/meta-boxes.php';
	include FLAMINGO_PLUGIN_DIR . '/admin/edit-outbound-form.php';
}
