=== Protected Posts Logout Button ===
Contributors: natereist
Donate link: http://omfgitsnater.com/protected-posts-logout-button/
Tags: logout, password protected posts logout button, wordpress security
Requires at least: 2.8
Tested up to: 4.4.2
Stable tag: 1.4.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Automatically adds a logout button to your password protected content.

== Description ==

This plugin simply adds a logout button to the content of any password protected post. Sometimes clients want a password protected page to share information with privileged individuals and the default 10 days for the cookie to expire is too long for their liking. So I wrote a little plugin to do this with AJAX and set the cookie to expire immediately, well actually 10 days in the past.

* Works logged in or out as a Wordpress user.
* Uses the same functionality Wordpress uses to set post cookies.
* Has a simple settings page to make everything easier.
* Allows you to alert user they have logged out.

== Installation ==

1. Upload `pplb_logout_button.zip` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Customize your settings.
1. Confirm it is working &amp; you're done!

== Frequently Asked Questions ==

= The logout button shows up, but nothing happens when clicked, what's wrong? =

Does your theme have `<?php wp_head(); ?>` and `<?php wp_footer(); ?>` installed correctly?  This plugin uses some javascript that gets embedded automatically in the header or footer, and requires it to work properly.

= This button is so ugly?! What can I do? =

Well, this button is setup to be no intrusive on your theme, so it adapts to the styles that come with you theme.

That being said, you can style the button as you wish using a css class you define on the settings page, or add your themes button class.

= I get more than one button on my page when I am logged in to a protected post, what gives? =

Well that logout button gets added to the function `the_content()` but only when the function `in_the_loop()` returns true (as of version 1.2).
if your theme is using `apply_filters('the_content', $some_str);` it is possible it will show up more than once.

== Screenshots ==

1. Admin Screen for simple settings
2. Optional alert
3. Button added to the content

== Changelog ==
= 1.4.1 =
* Added an option for changing the button text
* re added the function `pplb_logout_button` to fix potential PHP errors

= 1.4.0 =
* Refactored code into a class
* Moved the options page to a template
* Tested with version 4.4.2

= 1.3.2.1 =
* Moving javascript to footers
* Checking to see if logout cookie option is numeric

= 1.3.2 =
* Added a optional debug to the send response to the console.log function ( only for debugging use should not be used in production. )
* Added a check to see the `message` is not of type "undefined"
* Added an option to the options page for turning on or off debug.
* Tested with version 4.0!

= 1.3.1 =
* Added the ability to change the default WordPress postpass cookie in the admin area.
* Cleaned up some logic and php code.
= 1.3 =
* Added conditional logic to the allow admin to disable the automatic filter.
* Added a shortcode to allow users to place the button inside posts and a php function to place it in template files or hook it.
= 1.2 =
Added conditional logic to the filter to only add the button inside of the loop.
= 1.1 =
Fixed a bug with javascript enqueue that was causing a 404 file not found for `logout.js`.
= 1.0 =
Original Release: uses `wp_enqueue_scripts` and ajax to logout password protected posts by setting the cookie to expire immediately.

== Upgrade Notice ==

= 1.1 =
Fixes a bug that leads to a 404, you should update this immediately for it to work properly.
= 1.0 =
Original Release. 

