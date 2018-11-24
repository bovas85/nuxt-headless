<?php

class Flamingo_Outbound_Message {

	const post_type = 'flamingo_outbound';

	public static $found_items = 0;

	public $id;
	public $date;
	public $to;
	public $from;
	public $subject;
	public $body;
	public $meta;

	public static function register_post_type() {
		register_post_type( self::post_type, array(
			'labels' => array(
				'name' => __( 'Flamingo Outbound Messages', 'flamingo' ),
				'singular_name' => __( 'Flamingo Outbound Message', 'flamingo' ),
			),
			'rewrite' => false,
			'query_var' => false,
		) );
	}

	public static function find( $args = '' ) {
		$defaults = array(
			'posts_per_page' => 10,
			'offset' => 0,
			'orderby' => 'ID',
			'order' => 'ASC',
			'meta_key' => '',
			'meta_value' => '',
			'post_status' => 'any',
			'tax_query' => array(),
		);

		$args = wp_parse_args( $args, $defaults );

		$args['post_type'] = self::post_type;

		$q = new WP_Query();
		$posts = $q->query( $args );

		self::$found_items = $q->found_posts;

		$objs = array();

		foreach ( (array) $posts as $post ) {
			$objs[] = new self( $post );
		}

		return $objs;
	}

	public static function add( $args = '' ) {
		$defaults = array(
			'to' => '',
			'from' => '',
			'subject' => '',
			'body' => '',
			'meta' => array(),
		);

		$args = wp_parse_args( $args, $defaults );

		$obj = new self();

		$obj->to = $args['to'];
		$obj->from = $args['from'];
		$obj->subject = $args['subject'];
		$obj->meta = $args['meta'];

		$obj->save();

		return $obj;
	}

	public function __construct( $post = null ) {
		if ( ! empty( $post ) && ( $post = get_post( $post ) ) ) {
			$this->id = $post->ID;

			$this->date = get_the_time(
				__( 'Y/m/d g:i:s A', 'flamingo' ), $this->id );
			$this->to = get_post_meta( $post->ID, '_to', true );
			$this->from = get_post_meta( $post->ID, '_from', true );
			$this->subject = get_post_meta( $post->ID, '_subject', true );
			$this->meta = get_post_meta( $post->ID, '_meta', true );
		}
	}

	public function save() {
		if ( ! empty( $this->subject ) ) {
			$post_title = $this->subject;
		} else {
			$post_title = __( '(No Title)', 'flamingo' );
		}

		$post_content = implode( "\n", array(
			$this->to, $this->from, $this->subject, $this->body ) );

		$post_status = 'publish';

		$postarr = array(
			'ID' => absint( $this->id ),
			'post_type' => self::post_type,
			'post_status' => $post_status,
			'post_title' => $post_title,
			'post_content' => $post_content,
		);

		$post_id = wp_insert_post( $postarr );

		if ( $post_id ) {
			$this->id = $post_id;
			update_post_meta( $post_id, '_to', $this->to );
			update_post_meta( $post_id, '_from', $this->from );
			update_post_meta( $post_id, '_subject', $this->subject );
			update_post_meta( $post_id, '_meta', $this->meta );
		}

		return $post_id;
	}

	public function trash() {
		if ( empty( $this->id ) ) {
			return;
		}

		if ( ! EMPTY_TRASH_DAYS ) {
			return $this->delete();
		}

		$post = wp_trash_post( $this->id );

		return (bool) $post;
	}

	public function untrash() {
		if ( empty( $this->id ) ) {
			return;
		}

		$post = wp_untrash_post( $this->id );

		return (bool) $post;
	}

	public function delete() {
		if ( empty( $this->id ) ) {
			return;
		}

		if ( $post = wp_delete_post( $this->id, true ) ) {
			$this->id = 0;
		}

		return (bool) $post;
	}
}
