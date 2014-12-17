=== BU Section Editing ===
Contributors: mgburns, gcorne
Tags: permissions, section, access, acl, user management, custom roles, content editing, workflow, boston university, bu
Requires at least: 3.1
Tested up to: 4.1
Stable tag: 0.9.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Advanced content editing workflow in WordPress through the use of section editing groups and permissions.

== Description ==

BU Section Editing provides advanced permissions for managing the editors on your website team. Create “section editing groups” and granularly control who can edit what content. Assign editors the new Section Editor role, and define what the group can edit with an innovative new interface for specifying any of your content – pages, posts, or any custom post types – right down to the single page or single post level.

The plugin was written by [Boston University IS&T](http://www.bu.edu/tech) staff with design and UX support from the [Interactive Design](http://www.bu.edu/id) group in Marketing & Communications.

= Features =

* Group users with similar editing privileges into “Section Groups”
* Each section group can have a unique ACL to determine what content is considered editable for group members
* Supports custom post types
* Integrations with navigation management views provided by the [BU Navigation Plugin](http://wordpress.org/extend/plugins/bu-navigation "BU Navigation Plugin")

For more information check out [http://developer.bu.edu/bu-section-editing/](http://developer.bu.edu/bu-section-editing/).

= Developers =

For developer documentation, feature roadmaps and more visit the [plugin repository on Github](https://github.com/bu-ist/bu-section-editing/).

== Installation ==

This plugin depends on the BU Navigation plugin for certain functionality. While it will work without it, you won’t be able to set permissions for hierarchical post types (such as pages) unless it is activated.

For more information about BU Navigation, visit the plugin page here:
[http://wordpress.org/extend/plugins/bu-navigation](http://wordpress.org/extend/plugins/bu-navigation "BU Navigation Plugin")

Both plugins can be installed automatically through the WordPress admin interface, or the by clicking the downlaod link on this page and installing manually.

= Manual Installation =

1. Upload the `bu-section-editing` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

To complete the advanced permissions workflow, install the [BU Versions Plugin](http://wordpress.org/extend/plugins/bu-versions "BU Versions Plugin").

== Frequently Asked Questions ==

= I just activated the plugin and started creating sections groups, but it’s not allowing me to add users. What gives?  =

In order for a user to be added to a section group they must first be assigned a role that is limited to section editing capabilities. The plugin comes with one such role – “Section Editor”. Any user you would like to add to section editing groups must first have their role changed to section editor.
It is also possible to use a role management plugin (such as [Members](http://wordpress.org/extend/plugins/members/)) to create your own “section editing”-ready roles.

For more information on creating roles for use with section editing groups, see [the Github Wiki page](https://github.com/bu-ist/bu-section-editing/wiki/Custom-Section-Editing-Roles).

== Screenshots ==

1. Manage content editing permissions using “section groups”
2. Each section group can contain multiple members or “section editors”
3. Manage editing & publishing permissions for all of your post types
4. Section editors are only allowed to edit published content if their group memberships permit it
5. Fully integrated with navigation management views presented by the BU Navigation plugin

== Changelog ==

= 0.9.3 =
* Tested for 4.1 compatibility
* Style tweaks for WP >= 3.8
* Minor bug fixes (PHP 5.4+ warnings)
* Unit test restructuring
* Added Grunt for script compilation
* Added TravisCI integration

= 0.9.2 =
* Added hook for modifying section editor caps
* Removed BU-specific handling from plugin

= 0.9.1 =
* Initial WordPress.org release
* Added localization support
* Added notices if BU Navigation plugin is not active
* General cleanup and bug fixes

= 0.8 =
* Initial Boston University release
* UI updates to match navigation trees used for other BU plugins (props clrux)
* Added hard dependency on BU Navigation plugin for hierarchical permissions editors
* Added section editing restrictions to BU Navigation views
* Tweaked permissions stats diff view to better represent changes since last save
* Improvements to capabilities logic
* Updates to Selenium test cases
* General cleanup & bug fixes
