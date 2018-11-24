=== User Role Editor ===
Contributors: shinephp
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=vladimir%40shinephp%2ecom&lc=RU&item_name=ShinePHP%2ecom&item_number=User%20Role%20Editor%20WordPress%20plugin&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted
Tags: user, role, editor, security, access, permission, capability
Requires at least: 4.0
Tested up to: 4.9.8
Stable tag: 4.46
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

User Role Editor WordPress plugin makes user roles and capabilities changing easy. Edit/add/delete WordPress user roles and capabilities.

== Description ==

User Role Editor WordPress plugin allows you to change user roles and capabilities easy.
Just turn on check boxes of capabilities you wish to add to the selected role and click "Update" button to save your changes. That's done. 
Add new roles and customize its capabilities according to your needs, from scratch of as a copy of other existing role. 
Unnecessary self-made role can be deleted if there are no users whom such role is assigned.
Role assigned every new created user by default may be changed too.
Capabilities could be assigned on per user basis. Multiple roles could be assigned to user simultaneously.
You can add new capabilities and remove unnecessary capabilities which could be left from uninstalled plugins.
Multi-site support is provided.

To read more about 'User Role Editor' visit [this page](http://www.shinephp.com/user-role-editor-wordpress-plugin/) at [shinephp.com](http://shinephp.com)


Do you need more functionality with quality support in a real time? Do you wish to remove advertisements from User Role Editor pages? 
[Buy Pro version](https://www.role-editor.com). 
[User Role Editor Pro](https://www.role-editor.com) includes extra modules:
<ul>
<li>Block selected admin menu items for role.</li>
<li>Hide selected front-end menu items for no logged-in visitors, logged-in users, roles.</li>
<li>Block selected widgets under "Appearance" menu for role.</li>
<li>Show widgets at front-end for selected roles.</li>
<li>Block selected meta boxes (dashboard, posts, pages, custom post types) for role.</li>
<li>"Export/Import" module. You can export user role to the local file and import it to any WordPress site or other sites of the multi-site WordPress network.</li> 
<li>Roles and Users permissions management via Network Admin  for multisite configuration. One click Synchronization to the whole network.</li>
<li>"Other roles access" module allows to define which other roles user with current role may see at WordPress: dropdown menus, e.g assign role to user editing user profile, etc.</li>
<li>Manage user access to editing posts/pages/custom post type using posts/pages, authors, taxonomies ID list.</li>
<li>Per plugin users access management for plugins activate/deactivate operations.</li>
<li>Per form users access management for Gravity Forms plugin.</li>
<li>Shortcode to show enclosed content to the users with selected roles only.</li>
<li>Posts and pages view restrictions for selected roles.</li>
<li>Admin back-end pages permissions viewer</li>
</ul>
Pro version is advertisement free. Premium support is included.

== Installation ==

Installation procedure:

1. Deactivate plugin if you have the previous version installed.
2. Extract "user-role-editor.zip" archive content to the "/wp-content/plugins/user-role-editor" directory.
3. Activate "User Role Editor" plugin via 'Plugins' menu in WordPress admin menu. 
4. Go to the "Users"-"User Role Editor" menu item and change your WordPress standard roles capabilities according to your needs.

== Frequently Asked Questions ==
- Does it work with WordPress in multi-site environment?
Yes, it works with WordPress multi-site. By default plugin works for every blog from your multi-site network as for locally installed blog.
To update selected role globally for the Network you should turn on the "Apply to All Sites" checkbox. You should have superadmin privileges to use User Role Editor under WordPress multi-site.
Pro version allows to manage roles of the whole network from the Netwok Admin.

To read full FAQ section visit [this page](http://www.shinephp.com/user-role-editor-wordpress-plugin/#faq) at [shinephp.com](shinephp.com).

== Screenshots ==
1. screenshot-1.png User Role Editor main form
2. screenshot-2.png Add/Remove roles or capabilities
3. screenshot-3.png User Capabilities link
4. screenshot-4.png User Capabilities Editor
5. screenshot-5.png Bulk change role for users without roles
6. screenshot-6.png Assign multiple roles to the selected users

To read more about 'User Role Editor' visit [this page](http://www.shinephp.com/user-role-editor-wordpress-plugin/) at [shinephp.com](shinephp.com).

= Translations =

If you wish to check available translations or help with plugin translation to your language visit this link
https://translate.wordpress.org/projects/wp-plugins/user-role-editor/


== Changelog =
= [4.46] 25.09.2018
* Update: "Users" page, "Without role" button: underlying SQL queries were replaced with more robust versions (about 10 times faster).
  It is critical for sites with large quant of users.New query does not take into account though some cases with incorrect users data (usually imported from the external sources).
  It's possible to use older (comprehensive but slower) query version defining a PHP constant: "define('URE_COUNT_USERS_WITHOUT_ROLE_THOROUGHLY', true);" or
  return false from a custom 'ure_count_users_without_role_quick' filter.
* Update: Error checking was enhanced after default role change for the WordPress multisite subsite.
* Update: URE settings page template: HTML helper checked() is used where applicable.
* Fix: 2 spelling mistakes were fixed in the text labels.

= [4.45] 18.08.2018 =
* Fix: Capability checkbox was shown as turned ON incorrectly for not granted capability included into a role, JSON: "caps":{"sample_cap":"false"}. Bug took place after the changing a currently selected role.
* Fix: Custom capabilities groups "User Role Editor" and "WooCommerce" were registered at the wrong 3rd tree level - changed to 2. 

= [4.44] 05.07.2018 =
* Update: URE had executed 'profile_update' action after update of user permissions from the user permissions editor page: Users->selected user->Capabilities. 
  It was replaced with 'ure_user_permissions_update' action now. It will allow to exclude conflicts with other plugins - "WP Members" [lost checkbox fields values](https://wordpress.org/support/topic/conflict-with-wp-members-2/), for example.
* Update: Additional options for role (like "Hide admin bar" at the bottom of URE page) did not applied to the user with 'ure_edit_roles' capability. This conditon was removed.
* Update: fix PHP notice 'Undefined offset: 0 in ...' at includes/classes/protect-admin.php, not_edit_admin(), where the 1st element of $caps array not always has index 0.
* Update: PHP required version was increased up to 5.4.

= [4.43] 05.06.2018 =
* Update: references to non-existed roles are removed from the URE role additional options data storage after any role update.
* Fix: Additional options section view for the current role was not refreshed properly after other current role selection.

= [4.42] 16.05.2018 =
* Fix: Type checking was added (URE_Lib::restore_visual_composer_caps()) to fix "Warning: Invalid argument supplied for foreach() in .../user-role-editor-pro/includes/classes/ure-lib.php on line 315".

= [4.41] 07.05.2018 =
* New: URE changes currently selected role via AJAX request, without full "Users->User Role Editor" page refresh.
* Update: All [WPBakery Visual Composer](http://vc.wpbakery.com) plugin custom user capabilities (started from 'vc_access_rules_') were excluded from processing by User Role Editor. Visual Composer loses settings made via its own "Role Manager" after the role update by User Role Editor in other case. The reason - Visual Composer stores not boolean values with user capabilities granted to the roles via own "Role Manager". User Role Editor converted them to related boolean values during role(s) update.

= [4.40.3] 05.04.2018 =
* Update: bbPress detection and code for integration with it was updated to support multisite installations when URE is network activated but bbPress is activated on some sites of the network only. Free version does not support bbPress roles. It excludes them from processing as bbPress creates them dynamically.

= [4.40.2] 04.04.2018 =
* Update: Load required .php files from the active bbPress plugin directly, as in some cases URE code may be executed earlier than they are loaded by bbPress.

= [4.40.1] 09.03.2018 =
* Update: wp_roles() function (introduced with WP 4.3) was included conditionally to URE code for backward compatibility with WordPress 4.0+
* Fix: WordPress multisite: bbPress plugin detection code was changed from checking bbPress API function existence to checking WordPress active plugins list. bbPress plugin activated for the site was not available yet for the network activated User Role Editor at the point of URE instance creation. URE did not work with bbPress roles as it should by design for that reason. URE (free version) should ignore bbPress roles and capabilities as the special efforts are required for this.

= [4.40] 31.01.2018 =
* Update: use wp_roles() function from WordPress API instead of initializing $wp_roles global variable directly.
* Fix: Bug was introduced by version 4.37 with users recalculation for "All" tab after excluding users with "administrator" role. Code worked incorrectly for Japanese locale.


For full list of changes applied to User Role Editor plugin look changelog.txt file.


== Additional Documentation ==

You can find more information about "User Role Editor" plugin at [this page](http://www.shinephp.com/user-role-editor-wordpress-plugin/)

I am ready to answer on your questions about plugin usage. Use [plugin page comments](http://www.shinephp.com/user-role-editor-wordpress-plugin/) for that.

== Upgrade Notice ==
= [4.43] 05.06.2018 =
* Update: references to non-existed roles are removed from the URE role additional options data storage after any role update.
* Fix: Additional options section view for the current role was not refreshed properly after other current role selection.




