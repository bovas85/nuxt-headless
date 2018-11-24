<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Flamingo_Inbound_Messages_List_Table extends WP_List_Table {

	private $is_trash = false;
	private $is_spam = false;

	public static function define_columns() {
		$columns = array(
			'cb' => '<input type="checkbox" />',
			'subject' => __( 'Subject', 'flamingo' ),
			'from' => __( 'From', 'flamingo' ),
			'channel' => __( 'Channel', 'flamingo' ),
			'date' => __( 'Date', 'flamingo' ),
		);

		$columns = apply_filters(
			'manage_flamingo_inbound_posts_columns', $columns );

		return $columns;
	}

	function __construct() {
		parent::__construct( array(
			'singular' => 'post',
			'plural' => 'posts',
			'ajax' => false,
		) );
	}

	function prepare_items() {
		$current_screen = get_current_screen();
		$per_page = $this->get_items_per_page( $current_screen->id . '_per_page' );

		$this->_column_headers = $this->get_column_info();

		$args = array(
			'posts_per_page' => $per_page,
			'offset' => ( $this->get_pagenum() - 1 ) * $per_page,
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

		if ( ! empty( $_REQUEST['post_status'] ) ) {
			if ( 'trash' == $_REQUEST['post_status'] ) {
				$args['post_status'] = 'trash';
				$this->is_trash = true;
			} elseif ( 'spam' == $_REQUEST['post_status'] ) {
				$args['post_status'] = Flamingo_Inbound_Message::spam_status;
				$this->is_spam = true;
			}
		}

		$this->items = Flamingo_Inbound_Message::find( $args );

		$total_items = Flamingo_Inbound_Message::$found_items;
		$total_pages = ceil( $total_items / $per_page );

		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'total_pages' => $total_pages,
			'per_page' => $per_page,
		) );
	}

	function get_views() {
		$status_links = array();
		$post_status = empty( $_REQUEST['post_status'] )
			? '' : $_REQUEST['post_status'];

		// Inbox
		Flamingo_Inbound_Message::find( array( 'post_status' => 'any' ) );
		$posts_in_inbox = Flamingo_Inbound_Message::$found_items;

		$inbox = sprintf(
			_nx( 'Inbox <span class="count">(%s)</span>',
				'Inbox <span class="count">(%s)</span>',
				$posts_in_inbox, 'posts', 'flamingo' ),
			number_format_i18n( $posts_in_inbox ) );

		$status_links['inbox'] = sprintf( '<a href="%1$s"%2$s>%3$s</a>',
			menu_page_url( 'flamingo_inbound', false ),
			( $this->is_trash || $this->is_spam ) ? '' : ' class="current"',
			$inbox );

		// Spam
		Flamingo_Inbound_Message::find( array(
			'post_status' => Flamingo_Inbound_Message::spam_status ) );
		$posts_in_spam = Flamingo_Inbound_Message::$found_items;

		$spam = sprintf(
			_nx( 'Spam <span class="count">(%s)</span>',
				'Spam <span class="count">(%s)</span>',
				$posts_in_spam, 'posts', 'flamingo' ),
			number_format_i18n( $posts_in_spam ) );

		$status_links['spam'] = sprintf( '<a href="%1$s"%2$s>%3$s</a>',
			esc_url( add_query_arg(
				array(
					'post_status' => 'spam',
				),
				menu_page_url( 'flamingo_inbound', false )
			) ),
			'spam' == $post_status ? ' class="current"' : '',
			$spam
		);

		// Trash
		Flamingo_Inbound_Message::find( array( 'post_status' => 'trash' ) );
		$posts_in_trash = Flamingo_Inbound_Message::$found_items;

		if ( empty( $posts_in_trash ) ) {
			return $status_links;
		}

		$trash = sprintf(
			_nx( 'Trash <span class="count">(%s)</span>',
				'Trash <span class="count">(%s)</span>',
				$posts_in_trash, 'posts', 'flamingo' ),
			number_format_i18n( $posts_in_trash ) );

		$status_links['trash'] = sprintf( '<a href="%1$s"%2$s>%3$s</a>',
			esc_url( add_query_arg(
				array(
					'post_status' => 'trash',
				),
				menu_page_url( 'flamingo_inbound', false )
			) ),
			'trash' == $post_status ? ' class="current"' : '',
			$trash );

		return $status_links;
	}

	function get_columns() {
		return get_column_headers( get_current_screen() );
	}

	function get_sortable_columns() {
		$columns = array(
			'subject' => array( 'subject', false ),
			'from' => array( 'from', false ),
			'date' => array( 'date', true ),
		);

		return $columns;
	}

	function get_bulk_actions() {
		$actions = array();

		if ( $this->is_trash ) {
			$actions['untrash'] = __( 'Restore', 'flamingo' );
		}

		if ( $this->is_trash || ! EMPTY_TRASH_DAYS ) {
			$actions['delete'] = __( 'Delete Permanently', 'flamingo' );
		} else {
			$actions['trash'] = __( 'Move to Trash', 'flamingo' );
		}

		if ( $this->is_spam ) {
			$actions['unspam'] = __( 'Not Spam', 'flamingo' );
		} else {
			$actions['spam'] = __( 'Mark as Spam', 'flamingo' );
		}

		return $actions;
	}

	function extra_tablenav( $which ) {
		$channel = 0;

		if ( ! empty( $_REQUEST['channel_id'] ) ) {
			$term = get_term( $_REQUEST['channel_id'], Flamingo_Inbound_Message::channel_taxonomy );

			if ( ! empty( $term ) && ! is_wp_error( $term ) ) {
				$channel = $term->term_id;
			}

		} elseif ( ! empty( $_REQUEST['channel'] ) ) {
			$term = get_term_by( 'slug', $_REQUEST['channel'],
				Flamingo_Inbound_Message::channel_taxonomy );

			if ( ! empty( $term ) && ! is_wp_error( $term ) ) {
				$channel = $term->term_id;
			}
		}

?>
<div class="alignleft actions">
<?php
		if ( 'top' == $which ) {
			$this->months_dropdown( Flamingo_Inbound_Message::post_type );

			wp_dropdown_categories( array(
				'taxonomy' => Flamingo_Inbound_Message::channel_taxonomy,
				'name' => 'channel_id',
				'show_option_all' => __( 'View all channels', 'flamingo' ),
				'show_count' => 1,
				'hide_empty' => 0,
				'hide_if_empty' => 1,
				'orderby' => 'name',
				'hierarchical' => 1,
				'selected' => $channel,
			) );

			submit_button( __( 'Filter', 'flamingo' ),
				'secondary', false, false, array( 'id' => 'post-query-submit' ) );

			if ( ! $this->is_spam && ! $this->is_trash ) {
				submit_button( __( 'Export', 'flamingo' ),
					'secondary', 'export', false );
			}
		}

		if ( $this->is_trash && current_user_can( 'flamingo_delete_inbound_messages' ) ) {
			submit_button( __( 'Empty Trash', 'flamingo' ),
				'button-secondary apply', 'delete_all', false );
		}
?>
</div>
<?php
	}

	function column_default( $item, $column_name ) {
		do_action( 'manage_flamingo_inbound_posts_custom_column',
			$column_name, $item->id );
	}

	function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			$this->_args['singular'],
			$item->id );
	}

	function column_subject( $item ) {
		if ( $this->is_trash ) {
			return '<strong>' . esc_html( $item->subject ) . '</strong>';
		}

		$actions = array();
		$post_id = absint( $item->id );

		$base_url = add_query_arg(
			array(
				'post' => $post_id,
			),
			menu_page_url( 'flamingo_inbound', false )
		);

		$edit_link = add_query_arg( array( 'action' => 'edit' ), $base_url );

		if ( current_user_can( 'flamingo_edit_inbound_message', $post_id ) ) {
			$actions['edit'] = sprintf( '<a href="%1$s">%2$s</a>',
				esc_url( $edit_link ), esc_html( __( 'View', 'flamingo' ) ) );
		}

		if ( $item->spam
		&& current_user_can( 'flamingo_unspam_inbound_message', $post_id ) ) {
			$link = add_query_arg( array( 'action' => 'unspam' ), $base_url );
			$link = wp_nonce_url( $link,
				'flamingo-unspam-inbound-message_' . $post_id );

			$actions['unspam'] = sprintf( '<a href="%1$s">%2$s</a>',
				esc_url( $link ), esc_html( __( 'Not Spam', 'flamingo' ) ) );
		}

		if ( ! $item->spam
		&& current_user_can( 'flamingo_spam_inbound_message', $post_id ) ) {
			$link = add_query_arg( array( 'action' => 'spam' ), $base_url );
			$link = wp_nonce_url( $link,
				'flamingo-spam-inbound-message_' . $post_id );

			$actions['spam'] = sprintf( '<a href="%1$s">%2$s</a>',
				esc_url( $link ), esc_html( __( 'Spam', 'flamingo' ) ) );
		}

		if ( current_user_can( 'flamingo_edit_inbound_message', $post_id ) ) {
			return sprintf( '<strong><a class="row-title" href="%1$s" aria-label="%2$s">%3$s</a></strong> %4$s',
				esc_url( $edit_link ),
				esc_attr( sprintf( __( 'Edit &#8220;%s&#8221;', 'flamingo' ), $item->subject ) ),
				esc_html( $item->subject ),
				$this->row_actions( $actions ) );
		} else {
			return sprintf( '<strong>%1$s</strong> %2$s',
				esc_html( $item->subject ),
				$this->row_actions( $actions ) );
		}
	}

	function column_from( $item ) {
		return esc_html( $item->from );
	}

	function column_channel( $item ) {
		if ( empty( $item->channel ) ) {
			return '';
		}

		$term = get_term_by( 'slug', $item->channel,
			Flamingo_Inbound_Message::channel_taxonomy );

		if ( empty( $term ) || is_wp_error( $term ) ) {
			return $item->channel;
		}

		$output = '';

		$ancestors = (array) get_ancestors( $term->term_id,
			Flamingo_Inbound_Message::channel_taxonomy );

		while ( $parent = array_pop( $ancestors ) ) {
			$parent = get_term( $parent, Flamingo_Inbound_Message::channel_taxonomy );

			if ( empty( $parent ) || is_wp_error( $parent ) ) {
				continue;
			}

			$link = add_query_arg(
				array(
					'channel' => $parent->slug,
				),
				menu_page_url( 'flamingo_inbound', false )
			);

			$output .= sprintf( '<a href="%1$s" aria-label="%2$s">%3$s</a> / ',
				esc_url( $link ),
				esc_attr( $parent->name ),
				esc_html( $parent->name )
			);
		}

		$link = add_query_arg(
			array(
				'channel' => $term->slug,
			),
			menu_page_url( 'flamingo_inbound', false )
		);

		$output .= sprintf( '<a href="%1$s" aria-label="%2$s">%3$s</a>',
			esc_url( $link ),
			esc_attr( $term->name ),
			esc_html( $term->name )
		);

		return $output;
	}

	function column_date( $item ) {
		$post = get_post( $item->id );

		if ( ! $post ) {
			return '';
		}

		$t_time = get_the_time( __( 'Y/m/d g:i:s A', 'flamingo' ), $item->id );
		$m_time = $post->post_date;
		$time = get_post_time( 'G', true, $item->id );

		$time_diff = time() - $time;

		if ( $time_diff > 0 && $time_diff < 24*60*60 ) {
			$h_time = sprintf( __( '%s ago', 'flamingo' ), human_time_diff( $time ) );
		} else {
			$h_time = mysql2date( __( 'Y/m/d', 'flamingo' ), $m_time );
		}

		return '<abbr aria-label="' . $t_time . '">' . $h_time . '</abbr>';
	}
}
