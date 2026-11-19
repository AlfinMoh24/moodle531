MB2NL theme 11.8.1 - Moodle 5.3 compatibility fixes
====================================================

Original package: theme_mb2nl-11.8.0_MOODLE-4.0-5.0.zip
Target: Moodle 5.3.1 (Build 20261112, version 2026100501)
Tested with PHP 8.4 / MariaDB 11.4 on XAMPP (Windows).

Changes made
------------
1. scss/style.scss
   Import the Boost 5.3 design system (legacy SCSS variables + CSS custom
   properties):
       @import '../../boost/scss/design-system';
   Without the legacy variables the theme SCSS failed to compile ("Undefined
   variable $mds-spacing-md") and Moodle served only the parent Boost CSS.
   Without the CSS custom properties (--mds-*) dialogs such as the file picker
   had a transparent background and a broken header.

2. lib/lib_menu.php
   user_get_user_navigation_info() is deprecated in Moodle 5.3.
   Replaced with \core\user::get_user_navigation_info().

3. externallib.php
   Added "use" statements for the new core_external classes
   (external_api, external_function_parameters, external_single_structure,
   external_value, external_warnings) to remove deprecated class loading
   notices in Moodle 5.x.

4. lib/lib_course.php
   Activity icons now request the new 'monologo' image instead of the
   deprecated 'icon' image (3 places), removing 5.3 debugging notices.

5. version.php
   $plugin->version  = 2025061213
   $plugin->release  = '11.8.1 (Moodle 4.0 - 5.3+)'

Requirements / notes
--------------------
* Install and activate the companion filter plugin "filter_mb2shortcodes"
  (v2.0.1 or newer) - the theme displays an admin notice and its shortcodes
  (including the [year] footer placeholder) will not be processed without it.
* "Site home" is a Moodle site setting, not a theme setting. To show the site
  home normally:
      Site administration > Appearance > Navigation > Enable "My home" = Yes
      and keep "Force users to log in" = No.
  Otherwise non-logged-in users are redirected to the custom login page
  (theme setting "Link to Login Page" also sends the front page to /login).
* Optional companion plugins used by advanced theme features (not required
  for normal pages): local_mb2builder (front page builder), local_mb2slides
  (slider), local_mb2megamenu (mega menu), format_mb2sections.
