=== BU Section Editing ===
Contributors: gcorne, mgburns
Tags: permissions, section, acl, user management, custom roles
Requires at least: 3.1
Tested up to: 3.5
Stable tag: 0.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

@todo short description

== Description ==

BU Section Editing is a WordPress plugin that adds new workflows to WordPress that grant site administrators granular control over who can edit and publish content.

The plugin was written by [Boston University IS&T](http://www.bu.edu/tech) staff with design and UX support from the [Interactive Design](http://www.bu.edu/id) group in Marketing & Communications.

To report an issue, file an issue on [Github](https://github.com/bu-ist/bu-section-editing/issue).

=== Features ===

* Group users with similar editing privileges in to "Section Groups"
* Each section group can have a unique ACL to determine what content is considered editable for group members
* Supports custom post types

=== Roadmap ===

* Add support for taxonomies

== Installation ==

This plugin requires the BU Navigation plugin in order to work with hierarchical permissions.  (for now).

1. Upload the `bu-section-editing` directory to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Change user role to "Section Editor" for any user who you want to add to section editing groups
4. Create section editing groups by clicking the "Section Groups > Add New" menu item

To complete the advanced permissions work flow, install the [BU Versions Plugin](http://github.com/bu-ist/bu-versions "BU Versions Plugin").

== Changelog ==

0.7
* UI updates to match navigation trees used for other BU plugins (props clrux)
* Added hard dependency on BU Navigation plugin for hierarchical permissions editors
* Added section editing restrictions to BU Navigation views
* Tweaked permissions stats diff view to better represent changes since last save
* Improvements to capabilities logic
* Updates to Selenium test cases
* General cleanup & bug fixes

0.6
* Moved manage groups page to top-level menu item
* Implemented "Find Users Tool" to aid section managers in adding members to groups
* Major refactoring of role/capability handling
* Added limit to group name field length
* General code refactoring / loading optimizations
* Minor bug fixes

0.5
* Added bulk actions and pagination for flat post type permission editors
* Update icons for hierarchical permission editors
* Added a "Section Groups" column to the manage users table
* Fixes for "Editable" bucket available to section editors
* Fixes for allowed post count methods
* IE style fixes
* General code cleanup / refactoring

0.4
* Changed data schema for storing section groups from serialized wp_option to post in custom post type with associated meta
* Modified upgrade class to automatically migrate groups created pre-0.4

0.3
* First public release (beta)
* Added support for flat post types
* Updates to stats widget
* Changed data schema for storing per-post permissions meta (only store allowed state)

0.2
* User acceptance testing release (alpha)