=== Better REST API Featured Images ===
Contributors: Braad
Donate link: http://braadmartin.com/
Tags: featured, images, post, thumbnail, rest, api, better
Requires at least: 4.0
Tested up to: 4.6
Stable tag: 1.2.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Adds a top-level field with featured image data including available sizes and URLs to the post object returned by the REST API.

== Description ==

**Note:** You probably do not need this plugin. The REST API already supports adding the query param `?_embed` to your URL and the response will then include all "embedded media", including the featured image, and the data you get there is exactly what this plugin gives you. The only reasons to use this plugin at this point are if you prefer to have the featured image data in a top level field in the response rather than among other embedded media in the `_embedded` field, and if you _always_ want the featured image data in the response rather than having to ask for it with `?_embed`. I still use this plugin because I do usually want both these things, but definitely give `?_embed` a try before using this plugin. :)

Version 2 of the WordPress REST API returns a `featured_media` field (formerly featured_image) on the post object by default, but this field is simply the image ID.

This plugin adds a `better_featured_image` field to the post object that contains the available image sizes and urls, allowing you to get this information without making a second request.

It takes this:

`
"featured_media": 13,
`

And turns it into this:

`
"featured_media": 13,
"better_featured_image": {
    "id": 13,
    "alt_text": "Hot Air Balloons",
    "caption": "The event featured hot air balloon rides",
    "description": "The hot air balloons from the big event",
    "media_type": "image",
    "media_details": {
      "width": 5760,
      "height": 3840,
      "file": "2015/09/balloons.jpg",
      "sizes": {
        "thumbnail": {
          "file": "balloons-150x150.jpg",
          "width": 150,
          "height": 150,
          "mime-type": "image/jpeg",
          "source_url": "http://api.example.com/wp-content/uploads/2015/09/balloons-150x150.jpg"
        },
        "medium": {
          "file": "balloons-300x200.jpg",
          "width": 300,
          "height": 200,
          "mime-type": "image/jpeg",
          "source_url": "http://api.example.com/wp-content/uploads/2015/09/balloons-300x200.jpg"
        },
        "large": {
          "file": "balloons-1024x683.jpg",
          "width": 1024,
          "height": 683,
          "mime-type": "image/jpeg",
          "source_url": "http://api.example.com/wp-content/uploads/2015/09/balloons-1024x683.jpg"
        },
        "post-thumbnail": {
          "file": "balloons-825x510.jpg",
          "width": 825,
          "height": 510,
          "mime-type": "image/jpeg",
          "source_url": "http://api.example.com/wp-content/uploads/2015/09/balloons-825x510.jpg"
        }
      },
      "image_meta": {
        "aperture": 6.3,
        "credit": "",
        "camera": "Canon EOS 5D Mark III",
        "caption": "",
        "created_timestamp": 1433110262,
        "copyright": "",
        "focal_length": "50",
        "iso": "100",
        "shutter_speed": "0.004",
        "title": "",
        "orientation": 1
      }
    },
    "post": null,
    "source_url": "http://api.example.com/wp-content/uploads/2015/09/balloons.jpg"
},
`

The format of the response is nearly identical to what you would get sending a request to `/wp-json/wp/v2/media/13` or using `?_embed`. When no featured image has been set on the post the `better_featured_image` field will have a value of `null`.

I've done some basic performance tests that indicate the difference in response times with and without this plugin to be about 10-15ms for a collection of 10 posts and 0-5ms for a single post. For me this is much faster than making a second request to `/media/`, especially for multiple posts.

As of version 1.1.0, there is a filter `better_rest_api_featured_image` that allows you to add custom data to the better_featured_image field. The filter is directly on the return value of the function that returns the better_featured_image field. This can be used to do things like add custom image meta or an SVG version of the image to the response. Here's an example of how you might use it:

`
add_filter( 'better_rest_api_featured_image', 'xxx_modify_rest_api_featured_image', 10, 2 );
/**
 * Modify the Better REST API Featured Image response.
 *
 * @param   array  $featured_image  The array of image data.
 * @param   int    $image_id        The image ID.
 *
 * @return  array                   The modified image data.
 */
function xxx_modify_rest_api_featured_image( $featured_image, $image_id ) {

  // Add an extra_data_string field with a string value.
  $featured_image['extra_data_string'] = 'A custom value.';

  // Add an extra_data_array field with an array value.
  $featured_image['extra_data_array'] = array(
    'custom_key' => 'A custom value.',
  );

  return $featured_image;
}
`

This plugin is on [on Github](https://github.com/BraadMartin/better-rest-api-featured-images "Better REST API Featured Images") and pull requests are always welcome. :)

== Installation ==

= Manual Installation =

1. Upload the entire `/better-rest-api-featured-images` directory to the `/wp-content/plugins/` directory.
1. Activate 'Better REST API Featured Images' through the 'Plugins' menu in WordPress.

= Better Installation =

1. Go to Plugins > Add New in your WordPress admin and search for 'Better REST API Featured Images'.
1. Click Install.

== Frequently Asked Questions ==

= How does it work? =

The WP REST API includes a filter on the response data it returns, and this plugin uses that filter to add a new field `better_featured_image` with the extra data for the featured image.

= When does the plugin load? =

The plugin loads on `init` at priority 12, in order to come after any custom post types have been registered.

= Why doesn't the plugin replace the default `featured_media` field? =

The `featured_media` field is a core field, and other applications might expect it to always be an integer value. To avoid any issues, this plugin includes the extra data under the `better_featured_image` field name.

= Why is the core field called `featured_media` but the plugin field is `better_featured_image`? =

Prior to V2 Beta 11 of the REST API the core field was called `featured_image`. As of Beta 11 this field was changed to `featured_media`, with the idea that at some point in the future there may be additional media items included on this field beyond the featured image. Version 1.1.1 of this plugin is compatible with both Beta 11 and all previous versions of V2.

== Changelog ==

= 1.2.1 =
* Add fix for bug caused by conflicts with plugins that manipulate image metadata

= 1.2.0 =
* Fix translation files present but not loading
* Add note to the readme explaining that `?_embed` should be tried before using this plugin
* Fix compat with older betas
* Add missing PHPDoc statements
* Tested with v2 beta 12

= 1.1.1 =
* Compatibility with v2 beta 11 of the REST API (now the core field is called featured_media; this plugin's field is still better_featured_image). Props: filose

= 1.1.0 =
* Add a better_rest_api_featured_image filter for adding custom data to the response. Props: avishayil

= 1.0.2 =
* Change register_api_field to register_rest_field for compatibility with the REST API v2 beta 9. Props: Soean

= 1.0.1 =
* Switch to returning null instead of 0 when no featured image is present

= 1.0.0 =
* First Release

== Upgrade Notice ==

= 1.2.1 =
* Add fix for bug caused by conflicts with plugins that manipulate image metadata

= 1.2.0 =
* Fix translation files present but not loading
* Add note to the readme explaining that `?_embed` should be tried before using this plugin
* Fix compat with older betas
* Add missing PHPDoc statements
* Tested with v2 beta 12

= 1.1.1 =
* Compatibility with v2 beta 11 of the REST API (now the core field is called featured_media; this plugin's field is still better_featured_image). Props: filose

= 1.1.0 =
* Add a better_rest_api_featured_image filter for adding custom data to the response. Props: avishayil

= 1.0.2 =
* Change register_api_field to register_rest_field for compatibility with the REST API v2 beta 9. Props: Soean

= 1.0.1 =
* Switch to returning null instead of 0 when no featured image is present

= 1.0.0 =
* First Release
