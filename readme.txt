=== BU Section Editing ===
Contributors: gcorne, mgburns
Tags: permissions, section, acl, user management, custom roles
Requires at least: 3.1
Tested up to: 3.3.2
Stable tag: 0.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

@todo short description

== Description ==

BU Section Editing is a WordPress plugin that adds new workflows to WordPress that grant site administrators granular control over who can edit and publish content.

The plugin was written by [Boston University IS&T](http://www.bu.edu/tech) staff with design and UX support from the [Interactive Design](http://www.bu.edu/id) group in Marketing & Communications.

=== Features ===

* Group users with similar editing privileges in to "Section Groups"
* Each section group can have a unique ACL to determine what content is considered editable for group members
* Supports custom post types
* 

=== Roadmap ===

* Add support for taxonmies

To report an issue, file an issue on [Github](https://github.com/bu-ist/bu-section-editing/issue).

== Installation ==

This plugin requires the BU Navigation plugin in order to work with hierarchical permissions.  (for now).

1. Upload the `bu-section-editing` directory to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Change user role to "Section Editor" for any user who you want to add to section editing groups
4. Create section editing groups at User > Section Groups

To complete the advanced permissions work flow, install the [BU Versions Plugin](http://github.com/bu-ist/bu-versions "BU Versions Plugin").


== Changelog ==

0.4
* Changed data schema for storing section groups from serialized wp_option to post in custom post type with associated meta
* Updated upgrade class to migrate groups created pre-0.4

0.3 (Beta)
* First public release