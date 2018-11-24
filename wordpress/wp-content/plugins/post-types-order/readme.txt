=== Post Types Order  ===
Contributors: nsp-code, tdgu
Donate link: http://www.nsp-code.com/donate.php
Tags: post order, posts order, sort, post sort, posts sort, post type order, custom order, admin posts order
Requires at least: 2.8
Tested up to: 4.9.7
Stable tag: 1.9.3.9

Post Order and custom Post Type Objects (custom post types) using a Drag and Drop Sortable JavaScript AJAX interface or default WordPress dashboard. 

== Description ==

<strong>Over 3.2 MILLIONS DOWNLOADS and near PERFECT rating out of 200 REVIEWS</strong>. <br />
A powerful plugin, Order Posts and Post Types Objects using a Drag and Drop Sortable JavaScript capability. 

The order can be customized within **default WordPress post type archive list page** or **a separate Re-Order interface** which display all objects.
It allows to reorder the posts for any custom post types you defined, including the default Posts. Also you can display the posts within admin interface sorted per your new sort. Post Order has never been easier.

= Usage =
This was built considering everyone to be able to use the sorting, no matter the WordPress experience:

* Install the plugin through the Install Plugins interface or by uploading the `post-types-order` folder to your `/wp-content/plugins/` directory.
* Activate the Post Order plugin.
* A new setting page will be created within Settings > Post Types Order, you should check with that, and make a first options save. 
* Using the <strong>AutoSort option as ON</strong> you don't need to worry about any code changes, the <strong>plugin will apply the customized post order</strong> on fly. 
* Use the Re-Order interface which appear to every custom post type (non-hierarchical) to change the post order to a new one.
* If prefer sort apply through the code, include 'orderby' =>'menu_order' within custom query arguments, more details at http://www.nsp-code.com/sample-code-on-how-to-apply-the-sort-for-post-types-order-plugin/

= Example of Usage =
[youtube http://www.youtube.com/watch?v=VEbNKFSfhCc] 

As you can see just a matter of drag and drop and post ordering will change on front side right away.
If for some reason the post order does not update on your front side, you either do something wrong or the theme code you are using does not use a standard query per WordPress Codex rules and regulations. But we can still help, use the forum to report your issue as there are many peoples who gladly help or get in touch with us.

<br />Something is wrong with this plugin on your site? Just use the forum or get in touch with us at <a target="_blank" href="http://www.nsp-code.com">Contact</a> and we'll check it out.

<br />Need More? Check out the advanced version of this plugin at <a target="_blank" href="http://www.nsp-code.com/premium-plugins/wordpress-plugins/advanced-post-types-order/">Advanced Post Types Order</a> which include Hierarchically post types order, Manual / Automatic Sorting, Individual Categories Order, Conditionals to apply, Paginations for large list, Mobile ready, Enhanced Interface, Plugins compatibility (MultiSite Network Support, WPML, Polylang, WooCommerce, WP E-Commerce, Platform Pro, Genesis etc), font side re-order interface,  ... and many more !!

<br />
<br />This plugin is developed by <a target="_blank" href="http://www.nsp-code.com">Nsp-Code</a>

== Installation ==

1. Upload `post-types-order` folder to your `/wp-content/plugins/` directory.
2. Activate the plugin from Admin > Plugins menu.
3. Once activated you should check with Settings > Post Types Order 
4. Use Re-Order link which appear into each post type section or use default WordPress interface to make your sorting.


== Screenshots ==

1. The ReOrder interface through which the sort can be created.

2. Sort can be managed within default WordPress post type interface.


== Frequently Asked Questions  ==

Feel free to contact us at electronice_delphi@yahoo.com

= I have no PHP knowledge at all, i will still be able to use this plugin? =

Absolutely, you can! 
Unlike many other plugins, you don't have to do any code changes to make your post order to change accordingly to custom defined post order. There is an option to autoupdate the WordPress queries so the posts order will be returned in the required order. Anyway this can be turned off (Autosort) to allow customized code usage.

= How to manually apply the sort on queries =

Include a 'orderby' => 'menu_order' property within your custom query.

= What kind of posts/pages this plugin allow me to sort? =

You can sort ALL post types that you have defined into your wordpress as long they are not <strong>hierarhically</strong> defined: Posts (default WordPress custom post type), Movies, Reviews, Data etc..

= Ok, i understand about the template post types order, how about the admin interface? =

There's a option you can trigger, to see the post types order as you defined in the sort list, right into the main admin post list interface.

= There is a feature that i want it implemented, can you do something about it? =

All ideas are welcome and i put them on my list to be implemented into the new versions. Anyway this may take time, but if you are in a rush, please consider a small donation and we can arrange something.

= Can i make certain queries to ignore the custom sort when Autosort is turned On? =

This can be done by including the ignore_custom_sort within custom query arguments. An example can be found at <a target="_blank" href="http://www.nsp-code.com/advanced-post-types-order-api/sample-usage/">http://www.nsp-code.com/advanced-post-types-order-api/sample-usage/</a>

= How can i force sort apply for certain queries when Autosort is turned On? =

A filter can be used to achieve that pto/posts_orderby. An example can be found at <a target="_blank" href="http://www.nsp-code.com/ignore-sort-apply-for-certain-query-on-post-types-order/">http://www.nsp-code.com/ignore-sort-apply-for-certain-query-on-post-types-order/</a>

= I still need more features like front sorting interface, shortcodes, filters, conditionals, advanced queries, taxonomy/ category sorting etc =

Consider upgrading to our advanced version of this plugin at a very resonable price <a target="_blank" href="http://www.nsp-code.com/premium-plugins/wordpress-plugins/advanced-post-types-order/">Advanced Post Types Order</a>


== Change Log ==

= 1.9.3.9 =
  - Ignore sorting when doing Search and there's a search key-phrase specified.
  - Ignore sorting when doing Search within admin dashboard
  - Removed Google Social as it produced some JavaScript errors
  - WordPress 4.9.7 tag update 

= 1.9.3.6 =
  - Clear LiteSpeed Cache on order update to reflect on front side
  - WordPress 4.9.1 tag update 

= 1.9.3.5 =
  - Fix: updated capability from switch_theme to manage_options within 'Minimum Level to use this plugin' option
  - Default admin capability changed from install_plugins to manage_options to prevent DISALLOW_FILE_MODS issue. https://wordpress.org/support/topic/plugin-breaks-when-disallow_file_mods-is-set-to-true/
  - Prepare plugin for Composer package

= 1.9.3.3 =
  - Plugin option to include query argument ASC / DESC

= 1.9.3.2 =
  - Include ASC / DESC if there is a query order argument
  - Avada fix 'product_orderby' ignore

= 1.9.3.1 =
  - WordPress 4.8 compatibility notice
  - Slight code changes, remove unused activate / deactivate hooks
  - Updated po translation file
  - Updated assets

= 1.9.3 =
  - Fix for custom post type objects per page when using default archive interface drag & drop sort
  - Plugin code redo and re-structure
  - Improved compatibility with other plugins
  - Security improvements for AJAX order updates

= 1.9 =
  - Remove translations from the package
  - Remove link for donate
  - Wp Hide plugin availability notification
  - New Filter pto/get_options to allow to change default options; Custom capability can be set for 'capability'
  - New Filter pto/admin/plugin_options/capability to allow custom capability option to be inserted within html

= 1.8.9.2 =
  - WPDB Prepare argument fix
  - User preferance objects per page set to default if empty

= 1.8.9 =
  - Add Nonce for admin settings
  - Update queries to use prepare
  - Drag & Drop Sortable within Post Type archive interface
  - Code cleanup
  - Set time limit for ajax calls to attempt a code execution extend

= 1.8.7 =
  - Admin Post / Page Gallery items order fix
  - New filter pto/posts_orderby  to ignore sort apply

= 1.8.6 =
  - PHP 7 deprecated nottice fix Deprecated: Methods with the same name as their class will not be constructors in a future version of PHP;  
  - Fix: $_REQUEST['action'] comparison evaluate as Identical instead equal
  - New filter cpto/interface_itme_data to append additional data for items within sortable interface
  - Slight style updates
  - Replaced Socialize FB like page

= 1.8.5 =
  - Text domain change to post-types-order to allow translations at https://translate.wordpress.org/projects/wp-plugins/post-types-order  
  - New query argument ignore_custom_sort , to be used with Autosort. Ignore any customised sort and return posts in default order.

= 1.8.4.1 =
  - Sortable interface styling improvements
  - Portuguese translation update - Pedro Mendonca - http://www.pedromendonca.pt
  - Text doamin fix for few texts
  
= 1.8.3.1 =
  - Advanced Custom Fields Page Rule fix
  - Show / Hide Re_order inderface for certain menus. Option available within Settings area.
  - Media Sort interface objects order fix, when query-attachments REQUEST
  - Bug - Thumbnails test code remove

= 1.8.2 =
  - Media Uploaded To after sort fix

= 1.8.1 =
  - Next / Previous sorting apply bug fix for custom taxonomies
  - Portuguese translation update - Pedro Mendonca - http://www.pedromendonca.pt
  - Options - phrase translation fix  

= 1.7.9 =
  - Next / Previous sorting apply option
  - Filter for Next / Previous sorting applpy
  - Help updates
  - Appearance /css updates
  - Admin columns sort fix
  - Media re-order

= 1.7.7 =
  - Next / Previous post link functionality update
  - Code improvements  
  - Norvegian translation update - Bjorn Johansen bjornjohansen.no
  - Czech translation - dUDLAJ; Martin Kucera - http://jsemweb.cz/

= 1.7.4 =
  - Japanese translation - Git6 Sosuke Watanabe  - http://git6.com/  
  - Portuguese translation update - Pedro Mendon?a - http://www.pedromendonca.pt 
  - Chinese translation - Coolwp coolwp.com@gmail.com

= 1.7.0 =
  - Swedish translation - Onlinebyran - http://onlinebyran.se
  - Portuguese translation - Pedro Mendon?a - http://www.pedromendonca.pt
  - AJAX save filter

= 1.6.8 = 
 - Edit Gallery - image order fix
 - "re-order" menu item allow translation 
 - Hungarian translation - Adam Laki - http://codeguide.hu/
 - Minor admin style improvements

= 1.6.5 = 
 - Updates/Fixes
 - German translation
 - Norwegian (norsk) translation

= 1.6.4 = 
 - DISALLOW_FILE_MODS fix, change the administrator capability to switch_themes

= 1.6.3 = 
 - Updates/Fixes
 - Menu Walker nottices Fix

= 1.6.2 = 
 - Updates/Fixes
 - Turkish - T?rk?e translation
 
= 1.6.1 = 
 - Updates/Fixes
 - Menu Walker nottices Fix
 - Hebrew translation - Lunasite Team http://www.lunasite.co.il
 - Dutch translation - Denver Sessink

= 1.5.8 = 
 - Updates/Fixes
 - Ignore Search queries when Autosort is ON
 - Text Instances translatable fix
 - Italian translation - Black Studio http://www.blackstudio.it 
 - Spanish translation - Marcelo Cannobbio

= 1.5.7 = 
 - Updates/Fixes
 - Using Capabilities instead levels
 - Updating certain code for WordPress 3.5 compatibility
 - Set default order as seccondary query order param

= 1.5.4 = 
 - Updates/Fixes
 
= 1.5.1 = 
 - Updates/Fixes

= 1.4.6 = 
 - Get Previous / Next Posts Update

= 1.4.3 = 
 - Small improvements

= 1.4.1 = 
 - Re-Order Menu Item Appearance fix for update versions
 - Improved post order code
 
= 1.3.9 =
 - Re-Order Menu Item Appearance fix   

= 1.3.8 = 
 - Another Plugin conflict fix (thanks Steve Reed)
 - Multiple Improvments (thanks for support Video Geek - bestpocketvideocams.com)
 - Localisation Update (thanks Gabriel Reguly - ppgr.com.br/wordpress/)

= 1.1.2 = 
 - Bug Fix
 
= 1.0.9 =
 - Admin will set the roles which can use the plugins (thanks for support Nick - peerpressurecreative.com)

= 1.0.2 =
 - Default order used if no sort occour
 
= 1.0.1 =
 - Post order support implemented
 
= 1.0 =
 - First stable version (thanks for support Andrew - PageLines.com)

= 0.9. =
 - Initial Release
 
 
== Upgrade Notice ==

Make sure you get the latest version.


== Localization ==

Want to contribute with a translation to your language? Please check at https://translate.wordpress.org/projects/wp-plugins/post-types-order
http://www.nsp-code.com
