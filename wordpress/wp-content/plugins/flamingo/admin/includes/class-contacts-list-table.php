<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Flamingo_Contacts_List_Table extends WP_List_Table {

	public static function define_columns() {
		$columns = array(
			'cb' => '<input type="checkbox" />',
			'email' => __( 'Email', 'flamingo' ),
			'full_name' => __( 'Name', 'flamingo' ),
			'tags' => __( 'Tags', 'flamingo' ),
			'history' => __( 'History', 'flamingo' ),
			'last_contacted' => __( 'Last Contact', 'flamingo' ),
		);

		$columns = apply_filters(
			'manage_flamingo_contact_posts_columns', $columns );

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
			'orderby' => 'meta_value',
			'order' => 'DESC',
			'meta_key' => '_last_contacted',
		);

		if ( ! empty( $_REQUEST['s'] ) ) {
			$args['s'] = $_REQUEST['s'];
		}

		if ( ! empty( $_REQUEST['orderby'] ) ) {
			if ( 'email' == $_REQUEST['orderby'] ) {
				$args['meta_key'] = '_email';
			} elseif ( 'name' == $_REQUEST['orderby'] ) {
				$args['meta_key'] = '_name';
			}
		}

		if ( ! empty( $_REQUEST['order'] )
		&& 'asc' == strtolower( $_REQUEST['order'] ) ) {
			$args['order'] = 'ASC';
		}

		if ( ! empty( $_REQUEST['contact_tag_id'] ) ) {
			$args['contact_tag_id'] = explode( ',', $_REQUEST['contact_tag_id'] );
		}

		$this->items = Flamingo_Contact::find( $args );

		$total_items = Flamingo_Contact::$found_items;
		$total_pages = ceil( $total_items / $per_page );

		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'total_pages' => $total_pages,
			'per_page' => $per_page,
		) );
	}

	function get_columns() {
		return get_column_headers( get_current_screen() );
	}

	function get_sortable_columns() {
		$columns = array(
			'email' => array( 'email', false ),
			'full_name' => array( 'name', false ),
			'last_contacted' => array( 'last_contacted', true ),
		);

		return $columns;
	}

	function get_bulk_actions() {
		$actions = array(
			'delete' => __( 'Delete', 'flamingo' ),
		);

		return $actions;
	}

	function extra_tablenav( $which ) {
		$tag = 0;

		if ( ! empty( $_REQUEST['contact_tag_id'] ) ) {
			$tag_id = explode( ',', $_REQUEST['contact_tag_id'] );

			$term = get_term( $tag_id[0], Flamingo_Contact::contact_tag_taxonomy );

			if ( ! empty( $term ) && ! is_wp_error( $term ) ) {
				$tag = $term->term_id;
			}
		}

?>
<div class="alignleft actions">
<?php
		if ( 'top' == $which ) {
			wp_dropdown_categories( array(
				'taxonomy' => Flamingo_Contact::contact_tag_taxonomy,
				'name' => 'contact_tag_id',
				'show_option_all' => __( 'View all tags', 'flamingo' ),
				'hide_empty' => 1,
				'hide_if_empty' => 1,
				'orderby' => 'name',
				'selected' => $tag,
			) );

			submit_button( __( 'Filter', 'flamingo' ),
				'secondary', false, false, array( 'id' => 'post-query-submit' ) );

			submit_button( __( 'Export', 'flamingo' ), 'secondary', 'export', false );
		}
?>
</div>
<?php
	}

	function column_default( $item, $column_name ) {
		do_action( 'manage_flamingo_contact_posts_custom_column',
			$column_name, $item->id );
	}

	function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			$this->_args['singular'],
			$item->id );
	}

	function column_email( $item ) {
		$actions = array();
		$post_id = absint( $item->id );

		$edit_link = add_query_arg(
			array(
				'post' => $post_id,
				'action' => 'edit',
			),
			menu_page_url( 'flamingo', false )
		);

		if ( current_user_can( 'flamingo_edit_contact', $post_id ) ) {
			$actions['edit'] = sprintf( '<a href="%1$s">%2$s</a>',
				esc_url( $edit_link ), esc_html( __( 'Edit', 'flamingo' ) ) );
		}

		if ( current_user_can( 'flamingo_edit_contact', $post_id ) ) {
			return sprintf( '<strong><a class="row-title" href="%1$s" aria-label="%2$s">%3$s</a></strong> %4$s',
				esc_url( $edit_link ),
				esc_attr( sprintf( __( 'Edit &#8220;%s&#8221;', 'flamingo' ), $item->email ) ),
				esc_html( $item->email ),
				$this->row_actions( $actions ) );
		} else {
			return sprintf( '<strong>%1$s</strong> %2$s',
				esc_html( $item->email ),
				$this->row_actions( $actions ) );
		}
	}

	function column_full_name( $item ) {
		return esc_html( $item->name );
	}

	function column_tags( $item ) {
		if ( empty( $item->tags ) ) {
			return __( 'No Tags', 'flamingo' );
		}

		$output = '';

		foreach ( (array) $item->tags as $tag ) {
			$term = get_term_by( 'name', $tag, Flamingo_Contact::contact_tag_taxonomy );

			if ( empty( $term ) || is_wp_error( $term ) ) {
				continue;
			}

			if ( $output ) {
				$output .= ', ';
			}

			$link = add_query_arg(
				array(
					'contact_tag_id' => $term->term_id,
				),
				menu_page_url( 'flamingo', false )
			);

			$output .= sprintf( '<a href="%1$s" aria-label="%2$s">%3$s</a>',
				esc_url( $link ),
				esc_attr( $term->name ),
				esc_html( $term->name )
			);
		}

		return $output;
	}

	function column_history( $item ) {
		$history = array();

		// User
		if ( $user = get_user_by( 'email', $item->email ) ) {
			$link = sprintf( 'user-edit.php?user_id=%d', $user->ID );
			$history[] = '<a href="' . admin_url( $link ) . '">'
				. esc_html( __( 'User', 'flamingo' ) ) . '</a>';
		}

		// Comment
		$comment_count = (int) get_comments( array(
			'count' => true,
			'author_email' => $item->email,
			'status' => 'approve',
			'type' => 'comment',
		) );

		if ( 0 < $comment_count ) {
			$link = sprintf( 'edit-comments.php?s=%s', urlencode( $item->email ) );
			$history[] = '<a href="' . admin_url( $link ) . '">'
				. sprintf( __( 'Comment (%d)', 'flamingo' ), $comment_count ) . '</a>';
		}

		// Contact channels
		$terms = get_terms( Flamingo_Inbound_Message::channel_taxonomy );

		if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
			foreach ( (array) $terms as $term ) {
				Flamingo_Inbound_Message::find( array(
					'channel' => $term->slug,
					's' => $item->email,
				) );

				$count = (int) Flamingo_Inbound_Message::$found_items;

				if ( ! $count ) {
					continue;
				}

				$link = add_query_arg(
					array(
						'channel' => $term->slug,
						's' => $item->email,
					),
					menu_page_url( 'flamingo_inbound', false )
				);

				$history[] = '<a href="' . esc_url( $link ) . '">'
					/* translators: 1: contact channel name, 2: contact count */
					. sprintf( _x( '%1$s (%2$d)', 'contact history', 'flamingo' ),
						$term->name,
						$count )
					. '</a>';
			}
		}

		$output = '';

		foreach ( $history as $item ) {
			$output .= '<li>' . $item . '</li>';
		}

		$output = '<ul class="contact-history">' . $output . '</ul>';

		return $output;
	}

	function column_last_contacted( $item ) {
		if ( empty( $item->last_contacted ) ) {
			return '';
		}

		$t_time = mysql2date( __( 'Y/m/d g:i:s A', 'flamingo' ), $item->last_contacted, true );
		$m_time = $item->last_contacted;
		$time = mysql2date( 'G', $item->last_contacted ) - get_option( 'gmt_offset' ) * 3600;

		$time_diff = time() - $time;

		if ( $time_diff > 0 && $time_diff < 24*60*60 ) {
			$h_time = sprintf( __( '%s ago', 'flamingo' ), human_time_diff( $time ) );
		} else {
			$h_time = mysql2date( __( 'Y/m/d', 'flamingo' ), $m_time );
		}

		return '<abbr aria-label="' . $t_time . '">' . $h_time . '</abbr>';
	}
}
