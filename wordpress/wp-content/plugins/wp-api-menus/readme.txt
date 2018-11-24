=== WP API Menus ===
Contributors: nekojira
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=P64V9NTEYFKDL
Tags: wp-api, wp-rest-api, json-rest-api, json, menus, rest, api, menu-routes
Requires at least: 3.6.0
Tested up to: 4.4.2
Stable tag: 1.3.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Extends WordPress WP REST API with new routes pointing to WordPress menus.

== Description ==

This plugin extends the [WordPress JSON REST API](https://wordpress.org/plugins/json-rest-api/) with new routes for WordPress registered menus

The new routes available will be:

* `/menus` list of every registered menu.
* `/menus/<id>` data for a specific menu.
* `/menu-locations` list of all registered theme locations.
* `/menu-locations/<location>` data for menu in specified menu in theme location.

Currently, the `menu-locations/<location>` route for individual menus will return a tree with full menu hierarchy, with correct menu item order and listing children for each menu item. The `menus/<id>` route will output menu details and a flat array of menu items. Item order or if each item has a parent will be indicated in each item attributes, but this route won't output items as a tree.

You can alter the data arrangement of each individual menu items and children using the filter hook `json_menus_format_menu_item`.

**An important note on WP API V2:**

In V1 the routes are located by default at `wp-json/menus/` etc.

In V2 the routes by default are at `wp-json/wp-api-menus/v2/` (e.g. `wp-json/wp-api-menus/v2/menus/`, etc.) since V2 encourages prefixing and version namespacing.

== Installation ==

This plugin requires having [WP API](https://wordpress.org/plugins/json-rest-api/) installed and activated or it won't be of any use.

Install the plugin as you would with any WordPress plugin in your `wp-content/plugins/` directory or equivalent.

Once installed, activate WP API Menus from WordPress plugins dashboard page and you're ready to go, WP API will respond to new routes and endpoints to your registered menus.


== Frequently Asked Questions ==

= Is this an official extension of WP API? =

There's no such thing.

= Will this plugin do 'X' ? =

You can submit a pull request to:
https://github.com/unfulvio/wp-api-menus
However, menu data organization in json is a bit arbitrary and subjective, and that's why probably hasn't made it into WP API by the time of writing. You could also fork this plugin altogether and write your json output for a specific use case.

== Screenshots ==

Nothing to show really, this plugin has no settings or frontend, it just extends WP API with new routes. It's up to you how to use them :)

== Changelog ==

= 1.3.1 =
* Tweak: The `object_slug` property is now available to get the slug for relative URLs - props @Fahrradflucht

= 1.3.0 =
* Fix (V2): Nodes duplication in sublevel menu items, see https://github.com/unfulvio/wp-api-menus/pull/22 - props @bpongvh
* Fix (V2): The items array was empty because it was looking for "ID" key instead of "id" - props @Dobbler
* Fix (V1): Check for JSON_API_VERSION constant, as in a mu-plugin installation of WP API 1.0 it will not show up under active_plugins - props @pdufour

= 1.2.1 =
* Tweak (V2 only): Use lowercase `id` instead of uppercase `ID` in API responses, to match the standard lowercase `id` used across WP REST API - props @puredazzle
* Fix: Fixed WP API v1 version detection for WordPress 4.4 - props	Thomas Chille

= 1.2.0 =
* Enhancement: Added WP REST API v2 support - props @foxpaul
* Misc: Supports WordPress 4.3

= 1.1.5 =
* Misc: Minor edits to headers and phpdocs
* Misc: Improved security

= 1.1.4 =
* Misc: Supports WordPress 4.2, add composer.json for wp-packagist

= 1.1.3 =
* Fix: Fixes bug where duplicate items where created in nested menus - props @josh-taylor

= 1.1.2 =
* Tweak: Introduced `json_menus_format_menu_item` filter hook - props @Noctine

= 1.1.1 =
* Misc: Submission to WordPress.org plugins directory.

= 1.1.0 =
* Enhancement: Routes for menus in theme locations now include complete tree with item order and nested children
* Tweak: `description` attribute for individual items is now included in results
* Fix: Fixed typo confusing `parent` with `collection` in meta   

= 1.0.0 =
* First public release

== Upgrade Notice ==

= 1.2.1 =

API V2 only: mind lowercase `id` instead of uppercase `ID` in API responses, to match the standard for `id` used across WP REST API.