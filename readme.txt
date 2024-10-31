=== Sideways8 Private Pages ===
Contributors: sideways8, technical_mastermind, areimann
Tags: s8, sideways8, private, private pages, s8 private pages, customer page, customer pages, client pages, client page, client, hide, client area, private area, user pages
Requires at least: 3.3
Tested up to: 3.5.1
Stable tag: 0.8.2
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Allows admins to create private pages for individual users that only that user and admins can access.

== Description ==
Ever needed to have a page with some content for a client and only wanted that client to have access to the page? Well now you can! This simple plugin from the guys at Sideways8 Interactive allows admins to create private pages (a custom post type) for a specific user that only that user and admins can access. Other users are simply redirected to the home page or to their private page if one exists.

You also have the ability to change the URL slug, so instead of "http://example.com/private/PAGE-NAME" you can make it "http://example.com/client-access/PAGE-NAME", for example.

= Features =
* A quick and easy way to create a private page for a user
* Allows the use of most custom templates that are available for use with regular pages
* Allows themers and developers to easily customize what private pages look like with custom templates (See the FAQ for details)

= Support/Help =
We have tutorial videos available on our site and are constantly working on adding more and keeping them up to date as needed. Click [here](http://sideways8.com/tutorials/ "Sideways8 Tutorials") to visit our tutorials page.

== Installation ==
1. Download and extract the plugin from the zip file.
2. Upload the entire "s8-private-pages" folder to your wp-content/plugins directory.
3. Activate the plugin.

== Frequently Asked Questions ==
= How can I create my own templates? =
As of version 0.8.2 you are able to use ANY templates in your theme that contain the "Template Name" PHP comment ([details here](http://codex.wordpress.org/Theme_Development#Custom_Page_Templates)). You can also create a template file (that doesn't have the "Template Name" comment) that is automatically applied as a default to ALL private pages. A file in the root of your theme named "s8-private-page.php" will automatically be applied to all private pages. You can also create a file named "s8-private-subpage.php" and it will act as a default for all CHILD private pages.

== Screenshots ==
1. Creating a "Private Page" and assigning it to a user
2. Changing the End Point

== Upgrade Notice ==
= 0.8.2 =
Better template support and several bug fixes
= 0.8.1 =
Added a login redirect option and fixed a minor bug
= 0.8.0 =
Initial release in WP repository

== Changelog ==
= 0.8.2 =
* Fixed a conflict with our "Sideways8 Custom Login & Registration Plugin"
* Updated private page template system, it will now look for s8-private-page.php, page.php, & index.php in the theme (in that order), using the first one it finds
* You can set a default template for private subpages by adding a file named s8-private-subpage.php to the theme
* You can now use a custom template with ANY private page (custom template that shows in the Pages "Template" dropdown, must use the "Template Name" PHP comment)
* Several other minor improvements and bug fixes
= 0.8.1 =
* Fixed a bug where editing an existing private page can act like it removed the owner
* Added the option to have users below a certain level be redirected to their private page upon login
= 0.8.0 =
* Initial release
