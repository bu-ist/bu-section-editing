<?php

class BU_Section_Editing_Upgrader {

	/**
	 * Perform any data modifications as needed based on version diff
	 */
	public function upgrade( $existing_version ) {

		// Every version bump will trigger re-population of roles
		$this->populate_roles();

		// Pre-alpha release
		if( version_compare( $existing_version, '0.2', '<' ) ) {
			$this->upgrade_02();
		}
		if( version_compare( $existing_version, '0.3', '<' ) ) {
			$this->upgrade_03();
		}
		// Post alpha release
		if( version_compare( $existing_version, '0.4', '<' ) ) {
			$this->upgrade_04();
		}
		if( version_compare( $existing_version, '0.6', '<' ) ) {
			$this->upgrade_06();
		}

	}

	/**
	 * Install default section editor role and capability set
	 */
	public function populate_roles() {

		// Allow plugins to skip installation of section editor role
		$create_section_editor = apply_filters( 'buse_create_section_editor_role', true );

		if( $create_section_editor ) {

			$role = get_role('section_editor');

			if(empty($role)) {
				add_role('section_editor', 'Section Editor');
			}

			$role = get_role('section_editor');

			$role->add_cap('upload_files');

			$role->add_cap('read');
			$role->add_cap('read_private_posts');
			$role->add_cap('read_private_pages');

			$role->add_cap('edit_posts');
			$role->add_cap('edit_others_posts');
			$role->add_cap('edit_private_posts');

			$role->add_cap('edit_pages');
			$role->add_cap('edit_others_pages');
			$role->add_cap('edit_private_pages');

			$role->add_cap('delete_posts');
			$role->add_cap('delete_pages');

			$role->add_cap('level_1');
			$role->add_cap('level_0');

			// Add post type specific section editing capabilities
			BU_Section_Editing_Plugin::$caps->add_caps( $role );

			// Allow others to customize default section editor caps
			do_action( 'buse_section_editor_caps', $role );

		}

		do_action( 'buse_populate_roles' );

	}

	/**
	 * Switched data structure for perms
	 */
	private function upgrade_02() {
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
	private function upgrade_03() {
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

		// Role/cap changes in 04b54ea79c1bc935eee5ce04118812c1d8dad229
		if( $role = get_role('section_editor') ) {

			$role->remove_cap('edit_published_posts');
			$role->remove_cap('edit_published_pages');
			$role->remove_cap('delete_others_pages');
			$role->remove_cap('delete_others_posts');
			$role->remove_cap('delete_published_posts');
			$role->remove_cap('delete_published_pages');
			$role->remove_cap('publish_pages');
			$role->remove_cap('publish_posts');

			$role->add_cap('delete_published_in_section');
			$role->add_cap('edit_published_in_section');
			$role->add_cap('publish_in_section');

		}

	}

	/**
	 * Switched from options -> custom post type posts for group storage
	 */
	private function upgrade_04() {
		global $wpdb;

		// Migrate groups schema
		$groups = get_option('_bu_section_groups');

		if( $groups ) {

			$gc = BU_Edit_Groups::get_instance();

			foreach( $groups as $groupdata ) {

				// Need to remove pre-existing ID and let wp_insert_post do its thing
				$old_id = $groupdata['id'];
				unset($groupdata['id']);

				// Convert to new structure
				$group = $gc->add_group($groupdata);

				// Grab all post IDS that have permissions set for this group
				$post_meta_query = sprintf("SELECT post_id FROM %s WHERE meta_key = '%s' AND meta_value = '%s'", $wpdb->postmeta, BU_Group_Permissions::META_KEY, $old_id );
				$posts_to_update = $wpdb->get_col( $post_meta_query );

				// Update one by one
				foreach( $posts_to_update as $pid ) {
					update_post_meta( $pid, BU_Group_Permissions::META_KEY, $group->id, $old_id );
				}

			}

			// Cleanup
			delete_option( '_bu_section_groups' );
			delete_option( '_bu_section_groups_index');

		}

	}

	/**
	 * Switched from <action>_published_in_section to <action>_<post_type>_in_section
	 * Changes made in caps branch
	 */
	private function upgrade_06() {

		// Role/cap mods introduced in 114fcedf80ebdb0ef93948f41a6984006ff74031
		if( $role = get_role('section_editor') ) {

			$role->remove_cap( 'delete_published_in_section' );
			$role->remove_cap( 'edit_published_in_section' );
			$role->remove_cap( 'publish_in_section' );

			$caps = BU_Section_Editing_Plugin::$caps->get_caps();

			foreach( $caps as $cap ) {
				$role->add_cap( $cap );
			}

		}

	}

}

?>