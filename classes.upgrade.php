<?php

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

		if( version_compare( $last_version, '0.2', '<' ) && version_compare( $current_version, '0.2', '>=' ) )
			self::upgrade_02();

		if( version_compare( $last_version, '0.3', '<' ) && version_compare( $current_version, '0.3', '>=' ) )
			self::upgrade_03();

	}

	private static function upgrade_02() {
		global $wpdb;

		// Upgrade (0.1 -> 0.2)
		$patterns = array( '/^(\d+)$/', '/^(\d+)-denied$/');
		$replacements = array('${1}:allowed', '${1}:denied' );

		// Fetch existing values
		$query = sprintf( 'SELECT `post_id`, `meta_value` FROM %s WHERE `meta_key` = "%s"', $wpdb->postmeta, BU_Edit_Group::META_KEY );
		$posts = $wpdb->get_results( $query );

		// Loop through and update
		foreach( $posts as $post ) {
			$result = preg_replace( $patterns, $replacements, $post->meta_value );
			update_post_meta( $post->post_id, BU_Edit_Group::META_KEY, $result, $post->meta_value );
		}

	}

	private static function upgrade_03() {
		global $wpdb;

		// Upgrade (0.2 -> 0.3)
		$patterns = array( '/^(\d+):allowed$/');
		$replacements = array('${1}' );

		// Fetch existing values
		$allowed_query = sprintf( 'SELECT `post_id`, `meta_value` FROM %s  WHERE `meta_key` = "%s" AND `meta_value` LIKE "%%:allowed"', 
			$wpdb->postmeta, 
			BU_Edit_Group::META_KEY
			);
		
		$allowed_posts = $wpdb->get_results( $allowed_query );

		foreach( $allowed_posts as $post ) {
			$new_meta_value = preg_replace( $patterns, $replacements, $post->meta_value );
			update_post_meta( $post->post_id, BU_Edit_Group::META_KEY, $new_meta_value, $post->meta_value );
		}

		// Fetch existing values
		$denied_query = sprintf( 'SELECT `post_id`, `meta_value` FROM %s WHERE `meta_key` = "%s" AND `meta_value` LIKE "%%denied"', 
			$wpdb->postmeta,
			BU_Edit_Group::META_KEY
			);
		$denied_posts = $wpdb->get_results( $denied_query );

		// Loop through and update
		foreach( $denied_posts as $post ) {
			delete_post_meta( $post->post_id, BU_Edit_Group::META_KEY, $post->meta_value );
		}

	}


}


?>