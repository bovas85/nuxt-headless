<?php
/**
 * WP JSON API Menu routes
 *
 * @package WP_API_Menus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WP_JSON_Menus' ) ) :


	/**
	 * WP JSON Menus class.
	 *
	 * WP API Menus support for WP API v1.
	 *
	 * @package WP_API_Menus
	 * @since 1.0.0
	 */
	class WP_JSON_Menus {


		/**
		 * Register menu routes for WP API.
		 *
		 * @since 1.0.0
		 * @param  array $routes Existing routes
		 * @return array Modified routes
		 */
		public function register_routes( $routes ) {

			// all registered menus
			$routes['/menus'] = array(
				array( array( $this, 'get_menus' ), WP_JSON_Server::READABLE ),
			);
			// a specific menu
			$routes['/menus/(?P<id>\d+)'] = array(
				array( array( $this, 'get_menu' ), WP_JSON_Server::READABLE ),
			);
			// all registered menu locations
			$routes['/menu-locations'] = array(
				array( array( $this, 'get_menu_locations' ), WP_JSON_Server::READABLE ),
			);
			// menu for given location
			$routes['/menu-locations/(?P<location>[a-zA-Z0-9_-]+)'] = array(
				array( array( $this, 'get_menu_location' ), WP_JSON_Server::READABLE ),
			);

			return $routes;
		}


		/**
		 * Get menus.
		 *
		 * @since  1.0.0
		 * @return array All registered menus
		 */
		public static function get_menus() {

			$json_url = get_json_url() . '/menus/';
			$wp_menus = wp_get_nav_menus();

			$i          = 0;
			$json_menus = array();

			foreach ( $wp_menus as $wp_menu ) :

				$menu = (array) $wp_menu;

				$json_menus[ $i ]                 = $menu;
				$json_menus[ $i ]['ID']           = $menu['term_id'];
				$json_menus[ $i ]['name']         = $menu['name'];
				$json_menus[ $i ]['slug']         = $menu['slug'];
				$json_menus[ $i ]['description']  = $menu['description'];
				$json_menus[ $i ]['count']        = $menu['count'];

				$json_menus[ $i ]['meta']['links']['collection'] = $json_url;
				$json_menus[ $i ]['meta']['links']['self']       = $json_url . $menu['term_id'];

				$i ++;

			endforeach;

			return $json_menus;
		}


		/**
		 * Get a menu.
		 *
		 * @since  1.0.0
		 * @param  int   $id ID of the menu
		 * @return array Menu data
		 */
		public function get_menu( $id ) {

			$json_url       = get_json_url() . '/menus/';
			$wp_menu_object = $id ? wp_get_nav_menu_object( $id ) : array();
			$wp_menu_items  = $id ? wp_get_nav_menu_items( $id ) : array();

			$json_menu = array();

			if ( $wp_menu_object ) :

				$menu = (array) $wp_menu_object;
				$json_menu['ID']            = abs( $menu['term_id'] );
				$json_menu['name']          = $menu['name'];
				$json_menu['slug']          = $menu['slug'];
				$json_menu['description']   = $menu['description'];
				$json_menu['count']         = abs( $menu['count'] );

				$json_menu_items = array();

				foreach ( $wp_menu_items as $item_object ) {

					$json_menu_items[] = $this->format_menu_item( $item_object );
				}

				$json_menu['items']                       = $json_menu_items;
				$json_menu['meta']['links']['collection'] = $json_url;
				$json_menu['meta']['links']['self']       = $json_url . $id;

			endif;

			return $json_menu;
		}


		/**
		 * Get menu locations.
		 *
		 * @since  1.0.0
		 * @return array All registered menus locations
		 */
		public static function get_menu_locations() {

			$locations        = get_nav_menu_locations();
			$registered_menus = get_registered_nav_menus();
			$json_url         = get_json_url() . '/menu-locations/';
			$json_menus       = array();

			if ( $locations && $registered_menus ) :

				foreach ( $registered_menus as $slug => $label ) :

					// Sanity check
					if ( ! isset( $locations[ $slug ] ) ) {
						continue;
					}

					$json_menus[ $slug ]['ID']                          = $locations[ $slug ];
					$json_menus[ $slug ]['label']                       = $label;
					$json_menus[ $slug ]['meta']['links']['collection'] = $json_url;
					$json_menus[ $slug ]['meta']['links']['self']       = $json_url . $slug;

				endforeach;

			endif;

			return $json_menus;
		}


		/**
		 * Get menu for location.
		 *
		 * @since  1.0.0
		 * @param  string $location The theme location menu name
		 * @return array The menu for the corresponding location
		 */
		public function get_menu_location( $location ) {

			$locations = get_nav_menu_locations();

			if ( ! isset( $locations[ $location ] ) ) {

				return array();
			}

			$wp_menu = wp_get_nav_menu_object( $locations[ $location ] );
			$menu_items = wp_get_nav_menu_items( $wp_menu->term_id );

			$sorted_menu_items = $top_level_menu_items = $menu_items_with_children = array();

			foreach ( (array) $menu_items as $menu_item ) {

				$sorted_menu_items[ $menu_item->menu_order ] = $menu_item;
			}

			foreach ( $sorted_menu_items as $menu_item ) {

				if ( (int) $menu_item->menu_item_parent !== 0 ) {
					$menu_items_with_children[ $menu_item->menu_item_parent ] = true;
				} else {
					$top_level_menu_items[] = $menu_item;
				}
			}

			$menu = array();

			while ( $sorted_menu_items ) :

				$i = 0;

				foreach ( $top_level_menu_items as $top_item ) :

					$menu[ $i ] = $this->format_menu_item( $top_item, false );

					if ( isset( $menu_items_with_children[ $top_item->ID ] ) ) {
						$menu[ $i ]['children'] = $this->get_nav_menu_item_children( $top_item->ID, $menu_items, false );
					} else {
						$menu[ $i ]['children'] = array();
					}

					$i++;

				endforeach;

				break;

			endwhile;

			return $menu;
		}


		/**
		 * Returns all child nav_menu_items under a specific parent.
		 *
		 * @since  1.1.0
		 * @param  int     $parent_id      the parent nav_menu_item ID
		 * @param  array   $nav_menu_items navigation menu items
		 * @param  bool    $depth          gives all children or direct children only
		 * @return array   returns filtered array of nav_menu_items
		 */
		public function get_nav_menu_item_children( $parent_id, $nav_menu_items, $depth = true ) {

			$nav_menu_item_list = array();

			foreach ( (array) $nav_menu_items as $nav_menu_item ) :

				if ( $nav_menu_item->menu_item_parent == $parent_id ) :

					$nav_menu_item_list[] = $this->format_menu_item( $nav_menu_item, true, $nav_menu_items );

					if ( $depth ) {

						if ( $children = $this->get_nav_menu_item_children( $nav_menu_item->ID, $nav_menu_items ) ) {

							$nav_menu_item_list = array_merge( $nav_menu_item_list, $children );
						}
					}

				endif;

			endforeach;

			return $nav_menu_item_list;
		}


		/**
		 * Format a menu item for JSON API consumption.
		 *
		 * @since   1.1.0
		 * @param   object|array    $menu_item  the menu item
		 * @param   bool            $children   get menu item children (default false)
		 * @param   array           $menu       the menu the item belongs to (used when $children is set to true)
		 * @return  array   a formatted menu item for JSON
		 */
		public function format_menu_item( $menu_item, $children = false, $menu = array() ) {

			$item = (array) $menu_item;

			$menu_item = array(
				'ID'          => abs( $item['ID'] ),
				'order'       => (int) $item['menu_order'],
				'parent'      => abs( $item['menu_item_parent'] ),
				'title'       => $item['title'],
				'url'         => $item['url'],
				'attr'        => $item['attr_title'],
				'target'      => $item['target'],
				'classes'     => implode( ' ', $item['classes'] ),
				'xfn'         => $item['xfn'],
				'description' => $item['description'],
				'object_id'   => abs( $item['object_id'] ),
				'object'      => $item['object'],
                'object_slug' => get_post($item['object_id'])->post_name,
				'type'        => $item['type'],
				'type_label'  => $item['type_label'],
			);

			if ( $children === true && ! empty( $menu ) ) {

				$menu_item['children'] = $this->get_nav_menu_item_children( $item['ID'], $menu );
			}

			return apply_filters( 'json_menus_format_menu_item', $menu_item );
		}


	}


endif;
