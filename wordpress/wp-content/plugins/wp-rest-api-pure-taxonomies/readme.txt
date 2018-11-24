=== Plugin Name ===
Contributors: Andrew MAGIK
Tags: wp-api, api, rest-api, taxonomies, categories, tags, json, wp-json, custom taxonomy, custom taxonomies, custom post type, Andrew MAGIK, Andrew MAGIK REST API
Requires at least: 4.4
Tested up to: 4.4.1
Stable tag: 1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin include all available taxonomy attributes into the WordPress REST API (v2) without additional API requests.

== Description ==

Now you have no need to make additional requests to get taxonomy info (term_id, name, slug, term_group, term_taxonomy_id, taxonomy, description, parent, count, filter) from their id that is available in the default json response.

Now all available taxonomy data is available in 'pure_taxonomies' field from your json response. It works for all custom added taxonomies, and for custom post types.

For example in 'wp-json/wp/v2/posts' you can find default fields 'categories', 'tags' and name of custom added taxonomies that contain only its id. With this plugin you can also find new 'pure_taxonomies' field that include all available 'categories', 'tags' and custom taxonomies data.

**Before:**
`{
	...
	categories: [
		3
	],
	tags: [
		2
	],
	custom_taxonomy_name: [
		1
	]
	...
}`

**After:**
`{
	...
	pure_taxonomies: {
		categories: [
			{
				term_id: 3,
				name: "First category",
				slug: "first-category",
				term_group: 0,
				term_taxonomy_id: 3,
				taxonomy: "category",
				description: "",
				parent: 0,
				count: 3,
				filter: "raw",
				cat_ID: 3,
				category_count: 3,
				category_description: "",
				cat_name: "First category",
				category_nicename: "first-category",
				category_parent: 0
			}
		],
		tags: [
			{
				term_id: 2,
				name: "First tag",
				slug: "first-tag",
				term_group: 0,
				term_taxonomy_id: 2,
				taxonomy: "post_tag",
				description: "",
				parent: 0,
				count: 2,
				filter: "raw"
			}
		],
		custom_taxonomy_name: [
			{
				term_id: 1,
				name: "Custom Taxonomy Name",
				slug: "custom-taxonomy-name",
				term_group: 0,
				term_taxonomy_id: 1,
				taxonomy: "custom_taxonomy_name",
				description: "",
				parent: 0,
				count: 1,
				filter: "raw"
			}
		]
	}
	...
}`

Check my other useful rest-api plugins: [https://wordpress.org/plugins/tags/andrew-magik-rest-api](https://wordpress.org/plugins/tags/andrew-magik-rest-api).


== Installation ==

1. Double check you have the WordPress REST (v2) API installed and active
1. Upload the plugin folder to the `/wp-content/plugins/` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress


== Frequently Asked Questions ==

= How to add custom taxonomies into Rest API: =
`
/**
 * Add REST API support to an already registered taxonomy.
 */
    add_action( 'init', 'my_custom_taxonomy_rest_support', 25 );
    function my_custom_taxonomy_rest_support() {
        global $wp_taxonomies;

        //Here should be a list of names of the already created custom taxonomies:
        $taxonomy_names = array(
            'clients',
            'technologies'
        );
        foreach ( $taxonomy_names as $key => $taxonomy_name ) {
            if (isset($wp_taxonomies[$taxonomy_name])) {
                $wp_taxonomies[$taxonomy_name]->show_in_rest = true;
                $wp_taxonomies[$taxonomy_name]->rest_base = $taxonomy_name;
                $wp_taxonomies[$taxonomy_name]->rest_controller_class = 'WP_REST_Terms_Controller';
            }
        }
    }
`

= How to add custom post type into Rest API: =
`
/**
 * Add REST API support to an already registered post type.
 */
    add_action( 'init', 'my_custom_post_type_rest_support', 25 );
    function my_custom_post_type_rest_support() {
        global $wp_post_types;

		//Here should be a name of your already created custom post type:
        $post_type_name = 'portfolio';
        if( isset( $wp_post_types[ $post_type_name ] ) ) {
            $wp_post_types[$post_type_name]->show_in_rest = true;
            $wp_post_types[$post_type_name]->rest_base = $post_type_name;
            $wp_post_types[$post_type_name]->rest_controller_class = 'WP_REST_Posts_Controller';
        }
    }
`

= Do you have other useful REST-API plugins? =
Yes, I have. You can check them by tag: [https://wordpress.org/plugins/tags/andrew-magik-rest-api](https://wordpress.org/plugins/tags/andrew-magik-rest-api).


== Changelog ==

= 1.0 =
* Initial release!

