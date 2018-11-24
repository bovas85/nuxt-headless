=== REST API - Filter Fields ===
Contributors: svrooij
Donate link: https://svrooij.nl/buy-me-a-beer
Tags: json, rest, api, rest-api
Requires at least: 4.4
Tested up to: 4.7.4
Stable tag: 1.0.7
License: MIT
License URI: https://raw.githubusercontent.com/svrooij/rest-api-filter-fields/master/LICENSE

Filter the properties returned by the Wordpress rest api V2

== Description ==

The [wp-rest-api-v2](https://wordpress.org/plugins/rest-api/) returns a lot of properties.
It could be very useful (or mobile-data-friendly) to only return the properties needed by the application.

If you only want titles and links of some articles it doesn't make sense to return the content or the excerpt.

This isn't limited to posts, it also works on custom posttypes, categories, pages, terms, taxonomies and comments.

Instead of returning:

    {
      "id": 2138,
      "date": "2015-10-25T15:31:03",
      "guid": {
        "rendered": "http://worldofict.nl/?p=2138"
      },
      "modified": "2015-10-25T15:31:03",
      "modified_gmt": "2015-10-25T14:31:03",
      "slug": "rechtenvrije-fotos",
      "type": "post",
      "link": "http://worldofict.nl/tip/2138-rechtenvrije-fotos/",
      "title": {
        "rendered": "Rechtenvrije foto&#8217;s"
      },
      "content": {
        "rendered": ".. A lot of content .. "
      },
      "excerpt": {
        "rendered": " .. A lot of content ..."
      },
      "author": 2,
      "featured_image": 2139,
      "comment_status": "open",
      "ping_status": "open",
      "sticky": false,
      "format": "standard",
      //.. even more tags ....
    }

It can return (with ``fields=id,title.rendered,link`` as GET parameter)

    {
      "id": 2138,
      "link": "http://worldofict.nl/tip/2138-rechtenvrije-fotos/",
      "title": {
        "rendered": "Rechtenvrije foto&#8217;s"
      }
    }

= Notes =

1. If you specify fields so it wouldn't return data the default response is send back to the client.
2. (for developers) something wrong with this plugin? [Github](https://github.com/svrooij/rest-api-filter-fields/)
3. If you like the plugin [buy me a beer](https://svrooij.nl/buy-me-a-beer/)

== Installation ==

Installing this plugin is really easy.

Just search the plugin directory for `rest api filter fields` and press install.
Or download it right from [Github](https://github.com/svrooij/rest-api-filter-fields/releases) and copy the `rest-api-filter-fields` directory in the archive to `wp-content/plugins/`.

== Frequently Asked Questions ==

= Do you add data to the response? =

No, this plugin only removes entries.
When you want to add [featured_images](https://github.com/svrooij/rest-api-filter-fields/issues/5), I recommend using [better-rest-api-featured-images](https://wordpress.org/plugins/better-rest-api-featured-images/)

= How about nested propterties? =

You can filter on nested properties with a '.' like 'title.rendered'. Not sure if this also works on arrays. [Existing issues](https://github.com/svrooij/rest-api-filter-fields/issues) or [Submit issue](https://github.com/svrooij/rest-api-filter-fields/issues/new)

= Does this also work for my custom posttype? =

Yes, we picked 20 as priority (default = 10) for activating.
This mean this plugin is probably activated last, so all custom post types should already be loaded.
But this only works if you made it public for the api.
See [Adding REST API Support for Custom Content Types](https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-rest-api-support-for-custom-content-types/) for more information.

= I found a bug, what should I do? =

All the bugs/issues are maintained on [github.com/svrooij/rest-api-filter-fields](https://github.com/svrooij/rest-api-filter-fields/issues)
so please create an issue (or a pull request with a fix) there.

== Changelog ==

= 1.0.7 =
* Filter fields enabled on users [Issue #9](https://github.com/svrooij/rest-api-filter-fields/issues/9)
* Bumped wordpress version

= 1.0.6 =
* Filter fields enabled on custom taxonomies [Issue #6](https://github.com/svrooij/rest-api-filter-fields/issues/6), thanks to [Denis Yilmaz](https://github.com/denisyilmaz) for the fix!

= 1.0.5 =
* Support for embedded fields (when you include the `_embed` GET parameter!).
* The `_links` field doesn't get stripped anymore.
* Taking the first element of an collection with `first`, like `_embedded.author.first.name`.
* Moved all the logic to a separate class, so it won't intervene the Wordpress core.

= 1.0.4 =
* Updated readme.

= 1.0.3 =
* Filter on nested fields [Issue #1](https://github.com/svrooij/rest-api-filter-fields/issues/1) implemented. Please test and leave a response [here](https://github.com/svrooij/rest-api-filter-fields/issues/1).

= 1.0.2 =
* Added filtering for categories [Issue #4](https://github.com/svrooij/rest-api-filter-fields/issues/4)
* Tested on Wordpress version 4.6.1

= 1.0.1 =
* Bumped tested wordpress version to 4.4
* Metadata update (personal website is https only :D)

= 1.0.0 =
* First release of rest-api-filter-fields
