<?php

/*
@todo write upgrade method for option -> post type group storage switch
*/

class BU_Section_Editing_Upgrader {

	const BUSE_VERSION_OPTION = '_buse_version';

	public static function version_check() {

		$last_version = get_option( self::BUSE_VERSION_OPTION );

		// Check if plugin has been updated (or just installed) and store current version
		if( $last_version === false || $last_version != BU_Section_Editing_Plugin::BUSE_VERSION ) {

			if( $last_version )
				self::upgrade( $last_version );

			update_option( self::BUSE_VERSION_OPTION, BU_Section_Editing_Plugin::BUSE_VERSION );

		}

	}

	/**
	 * Perform any data modifications as needed based on version diff
	 */
	public static function upgrade( $last_version ) {

		$current_version = BU_Section_Editing_Plugin::BUSE_VERSION;

		// @todo Delete these before final release
		if( version_compare( $last_version, '0.2', '<' ) && version_compare( $current_version, '0.2', '>=' ) )
			self::upgrade_02();

		if( version_compare( $last_version, '0.3', '<' ) && version_compare( $current_version, '0.3', '>=' ) )
			self::upgrade_03();

		// Post alpha release

		if( version_compare( $last_version, '0.4', '<' ) && version_compare( $current_version, '0.4', '>=' ) )
			self::upgrade_04();

	}

	/**
	 * Switched data structure for perms
	 */ 
	private static function upgrade_02() {
		global $wpdb;

		// Upgrade (0.1 -> 0.2)
		$patterns = array( '/^(\d+)$/', '/^(\d+)-denied$/');
		$replacements = array('${1}:allowed', '${1}:denied' );

		// Fetch existing values
		$query = sprintf( 'SELECT `post_id`, `meta_value` FROM %s WHERE `meta_key` = "%s"', $wpdb->postmeta, BU_Group_Permissions::META_KEY );
		$posts = $wpdb->get_results( $query );

		// Loop through and update
		foreach( $posts as $post ) {
			$result = preg_replace( $patterns, $replacements, $post->meta_value );
			update_post_meta( $post->post_id, BU_Group_Permissions::META_KEY, $result, $post->meta_value );
		}

	}

	/**
	 * Switched data structure for perms (again)
	 */ 
	private static function upgrade_03() {
		global $wpdb;

		// Upgrade (0.2 -> 0.3)
		$patterns = array( '/^(\d+):allowed$/');
		$replacements = array('${1}' );

		// Fetch existing values
		$allowed_query = sprintf( 'SELECT `post_id`, `meta_value` FROM %s  WHERE `meta_key` = "%s" AND `meta_value` LIKE "%%:allowed"', 
			$wpdb->postmeta, 
			BU_Group_Permissions::META_KEY
			);
		
		$allowed_posts = $wpdb->get_results( $allowed_query );

		foreach( $allowed_posts as $post ) {
			$new_meta_value = preg_replace( $patterns, $replacements, $post->meta_value );
			update_post_meta( $post->post_id, BU_Group_Permissions::META_KEY, $new_meta_value, $post->meta_value );
		}

		// Fetch existing values
		$denied_query = sprintf( 'SELECT `post_id`, `meta_value` FROM %s WHERE `meta_key` = "%s" AND `meta_value` LIKE "%%denied"', 
			$wpdb->postmeta,
			BU_Group_Permissions::META_KEY
			);
		$denied_posts = $wpdb->get_results( $denied_query );

		// Loop through and update
		foreach( $denied_posts as $post ) {
			delete_post_meta( $post->post_id, BU_Group_Permissions::META_KEY, $post->meta_value );
		}

	}

	/**
	 * Switched from options -> custom post type posts for group storage
	 */ 
	private static function upgrade_04() {
		global $wpdb;

		error_log( "[buse_upgrade -> 0.4] BU Section Editing plugin upgrading from 0.3 -> 0.4" );

		// Get old groups
		$groups = get_option('_bu_section_groups');

		if( false === $groups ) {
			error_log( "[buse_upgrade -> 0.4] No previous groups found, exiting upgrade" );
			return;
		}

		error_log( "[buse_upgrade -> 0.4] Groups for upgrade: " . count($groups) );

		$gc = BU_Edit_Groups::get_instance();

		foreach( $groups as $groupdata ) {

			// Need to remove pre-existing ID and let wp_insert_post do its thing
			$old_id = $groupdata['id'];
			unset($groupdata['id']);

			// Convert to new structure
			$group = $gc->add_group($groupdata);

			error_log( "[buse_upgrade -> 0.4] Group upgraded: {$group->name} (#{$group->id})" );

			// Migrate permissions
			$count = $wpdb->update( 
				$wpdb->postmeta, 
				array( 'meta_value' => $group->id ), // New value
				array( 'meta_key' => BU_Group_Permissions::META_KEY, 'meta_value' => $old_id )	// Old value 
			);

			error_log( "[buse_upgrade -> 0.4] Group permissions migrated for group {$group->id}: $count" );

		}

		// Cleanup
		delete_option( '_bu_section_groups' );
		delete_option( '_bu_section_groups_index');

		error_log( "[buse_upgrade -> 0.4] Upgrade completed succesfully!" );

	}

}

?>