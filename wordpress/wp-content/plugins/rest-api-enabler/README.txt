=== REST API Enabler ===
Contributors:      McGuive7
Donate link:       http://wordpress.org/plugins/rest-api-enabler
Tags:              REST, API, custom, post, type, field, meta, taxonomy, category
Requires at least: 3.5
Tested up to:      4.4
Stable tag:        1.1.0
License:           GPLv2 or later
License URI:       http://www.gnu.org/licenses/gpl-2.0.html

Enable the WP REST API to work with custom post types, custom fields, and custom endpoints.

== Description ==

**Like this plugin? Please consider [leaving a 5-star review](https://wordpress.org/support/view/plugin-reviews/rest-api-enabler).**

By default, custom post types and custom fields are not accessible via the WordPress REST API. REST API Enabler allows you to:

1. Enable the WP REST API for custom post types and specify WP REST API custom endpoints.
2. Choose which custom fields to include in WP REST API responses for posts, pages, and custom post types.

All enabled custom field data is included in the REST API response, nested under the `rest_api_enabler` key, like so:

`
[
  {
    "id": 179,
    "date": "2016-07-03T18:06:50",
    "title": {
      "rendered": "Test Job"
    },
    .
    .
    .
    "rest_api_enabler": {
      "custom_meta_1": "Value 1",
      "custom_meta_2": "Value 2",
      "custom_meta_3": [
        "Array value 1",
        "Array value 2"
      ]
    }
  }
]
`

Note: prior to verion 1.1.0, all meta keys were included as top-level keys in the API response. Additionally, all values were returned as arrays, regardless of whether the original value was actually an array. This functionality is now deprecated as it risks key-name collisions. Please reference the `rest_api_enabler` top-level key instead.

= Usage =

1. Activate the plugin, then go to **Settings &rarr; REST API Enabler** in the admin.
2. Click the **Post Types** tab to enable/disable post types and customize their endpoints.
3. Click the **Post Meta** tab to enable/disable post meta (custom fields).

**NOTE:** by default, the plugin does not display settings for protected post meta (post meta that begins with an underscore and is intended for internal use only). If you wish to include protected post meta in the plugin settings, you can use the `rae_include_protected_meta` filter to do so. The following code can be placed in your theme's `functions.php` file, or in a custom plugin (on `init` priority 10 or earlier):

`
add_filter( 'rae_include_protected_meta', '__return_true' );
`


== Installation ==

= Manual Installation =

1. Upload the entire `/rest-api-enabler` directory to the `/wp-content/plugins/` directory.
2. Activate REST API Enabler through the 'Plugins' menu in WordPress.


== Screenshots ==

1. Enabling post types and customizing their endpoints.


== Changelog ==

= 1.1.0 =
* Add mapping of meta keys to be nested under the new top level rest_api_enabler response key. Note: top-level key support is still maintained, though now considered deprecated.
* Add functionality to support singular and array values, to prevent issue in which ALL values were previously returned as arrays.

= 1.0.2 =
* Fix issue in which media uploads via the REST API don't work.

= 1.0.1 =
* Fix typo preventing post meta enabling.
* Fix post meta alphabetical sorting.

= 1.0.0 =
* First release

== Upgrade Notice ==

= 1.1.0 =
* Add mapping of Rest API Enabler meta keys to be nested under the new top level rest_api_enabler response key. Note: top-level key support is still maintained, though now considered deprecated.
* Add functionality to support singular and array values, to prevent issue in which ALL values were previously returned as arrays.

= 1.0.2 =
* Fix issue in which media uploads via the REST API don't work.

= 1.0.1 =
* Fix typo preventing post meta enabling.
* Fix post meta alphabetical sorting.

= 1.0.0 =
First Release