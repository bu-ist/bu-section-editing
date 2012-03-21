<?php

/**
 * Temporary class until a good solution for creating roles / assignig
 * capabilities is arrived upon
 */
class BU_Section_Editing_Roles {

	// need to figure out the *best* way to create roles
	static public function maybe_create() {

		$role = get_role('administrator');

		if(empty($role)) {
			add_role('administrator', 'Administrator');
			include( ABSPATH . '/wp-admin/includes/schema.php');// hack to add all roles if they were deleted.
			populate_roles();
		}

		$role = get_role('administrator');

		$role->add_cap('read_page_revisions');
		$role->add_cap('edit_page_revisions');
		$role->add_cap('edit_others_page_revisions');
		$role->add_cap('edit_published_page_revisions');
		$role->add_cap('publish_page_revisions');
		$role->add_cap('delete_page_revisions');
		$role->add_cap('delete_others_page_revisions');
		$role->add_cap('delete_published_page_revisions');

		$role = get_role( 'lead_editor' );

		if(empty($role)) {
			add_role('lead_editor', 'Lead Editor');
		}

		$role = get_role('lead_editor');
		$role->remove_cap('edit_published_pages');
		$role->add_cap('manage_training_manager');
		$role->add_cap('upload_files');
		$role->add_cap('edit_posts');
		$role->add_cap('read');
		$role->add_cap('delete_posts');

		$role->add_cap('moderate_comments');
		$role->add_cap('manage_categories');
		$role->add_cap('manage_links');
		$role->add_cap('upload_files');
		$role->add_cap('import');
		$role->add_cap('unfiltered_html');
		$role->add_cap('edit_posts');
		$role->add_cap('edit_others_posts');
		$role->add_cap('edit_published_posts');
		$role->add_cap('publish_posts');
		$role->add_cap('edit_pages');
		$role->add_cap('read');
		$role->add_cap('level_10');
		$role->add_cap('level_9');
		$role->add_cap('level_8');
		$role->add_cap('level_7');
		$role->add_cap('level_6');
		$role->add_cap('level_5');
		$role->add_cap('level_4');
		$role->add_cap('level_3');
		$role->add_cap('level_2');
		$role->add_cap('level_1');
		$role->add_cap('level_0');

		$role->add_cap('edit_others_pages');
		$role->add_cap('edit_published_pages');
		$role->add_cap('publish_pages');
		$role->add_cap('delete_pages');
		$role->add_cap('delete_others_pages');
		$role->add_cap('delete_published_pages');
		$role->add_cap('delete_posts');
		$role->add_cap('delete_others_posts');
		$role->add_cap('delete_published_posts');
		$role->add_cap('delete_private_posts');
		$role->add_cap('edit_private_posts');
		$role->add_cap('read_private_posts');
		$role->add_cap('delete_private_pages');
		$role->add_cap('edit_private_pages');
		$role->add_cap('read_private_pages');

		$role->add_cap('read_page_revisions');
		$role->add_cap('edit_page_revisions');
		$role->add_cap('edit_others_page_revisions');
		$role->add_cap('edit_published_page_revisions');
		$role->add_cap('publish_page_revisions');
		$role->add_cap('delete_page_revisions');
		$role->add_cap('delete_others_page_revisions');
		$role->add_cap('delete_published_page_revisions');


		$role->add_cap('read_private_posts');
		$role->add_cap('read_private_pages');
		$role->add_cap('unfiltered_html');


		/** Temporary **/
		$role = get_role('section_editor');
		if(empty($role)) {
			add_role('section_editor', 'Section Editor');
		}

		$role = get_role('section_editor');
		$role->add_cap('manage_training_manager');
		$role->add_cap('upload_files');

		$role->add_cap('read');
		$role->add_cap('edit_pages');
		$role->add_cap('edit_others_pages');

		// the following roles are overriden by the section editor functionality
		$role->add_cap('edit_published_pages');
		$role->add_cap('publish_pages');

		$role->add_cap('moderate_comments');
		$role->add_cap('manage_categories');
		$role->add_cap('manage_links');
		$role->add_cap('upload_files');
		$role->add_cap('edit_posts');
		$role->add_cap('read');
		$role->add_cap('level_7');
		$role->add_cap('level_6');
		$role->add_cap('level_5');
		$role->add_cap('level_4');
		$role->add_cap('level_3');
		$role->add_cap('level_2');
		$role->add_cap('level_1');
		$role->add_cap('level_0');
		$role->add_cap('edit_private_posts');
		$role->add_cap('read_private_posts');
		$role->add_cap('edit_private_pages');
		$role->add_cap('read_private_pages');

		$role->add_cap('read_page_revisions');
		$role->add_cap('edit_page_revisions');
		$role->add_cap('edit_others_page_revisions');
		$role->add_cap('edit_published_page_revisions');
		$role->add_cap('publish_page_revisions');
		$role->add_cap('delete_page_revisions');
		$role->add_cap('delete_others_page_revisions');
		$role->add_cap('delete_published_page_revisions');

		$role->add_cap('unfiltered_html');

				/** Temporary **/
		$role = get_role('contributor');
		if(empty($role)) {
			add_role('contributor', 'Contributor');
		}

		$role = get_role('contributor');
		$role->add_cap('manage_training_manager');
		$role->add_cap('upload_files');

		$role->add_cap('read');
		$role->add_cap('edit_pages');

		$role->add_cap('read_page_revisions');
		$role->add_cap('edit_page_revisions');
		$role->add_cap('edit_others_page_revisions');
		$role->add_cap('edit_published_page_revisions');
		$role->add_cap('delete_page_revisions');

		$role->add_cap('unfiltered_html');
	}
}

/**
 * Section Editor
 */ 
class BU_Section_Editor {

	static function can_edit($post_id, $user_id)  {

		if($user_id == 0) return false;

		$user = get_userdata($user_id);

		if($user && in_array('section_editor', $user->roles)) {
			$post = get_post($post_id, OBJECT, null);
			$groups = get_post_meta($post_id, 'bu_group');
			$edit_groups_o = BU_Edit_Groups::get_instance();
			if($edit_groups_o->has_user($groups, $user_id)) {
				return true;
			} else {
				$ancestors = get_post_ancestors($post);
				// iterate through ancestors; needs to be optimized
				foreach(array_reverse($ancestors) as $ancestor_id) {
					$groups = get_post_meta($ancestor_id, 'bu_group');
					if($edit_groups_o->has_user($groups, $user_id)) {
						return true;
					}
				}
			}

			return false;
		}

		return true;
	}

	/**
	 * Filter that modifies the caps based on the current state.
	 *
	 * @param type $caps
	 * @param type $cap
	 * @param type $user_id
	 * @param type $args
	 * @return string
	 */
	static function map_meta_cap($caps, $cap, $user_id, $args) {

		if( isset( $args[0] ) )
			$post_id = $args[0];

		if($cap == 'edit_page') {
			$post = get_post($post_id);

			if($post_id && $post->post_status == 'publish' && !BU_Section_Editor::can_edit($post_id, $user_id)) {
				$caps = array('do_not_allow');
			}
		}

		if($cap == 'delete_page') {
			if($post_id && !BU_Section_Editor::can_edit($post_id, $user_id)) {
				$caps = array('do_not_allow');
			}
		}

		if($cap == 'publish_pages') {
			global $post_ID;

			$post_id = $post_ID;

			if($post_id && !BU_Section_Editor::can_edit($post_id, $user_id)) {
				$caps = array('do_not_allow');
			}
		}

		if($cap == 'publish_page_revisions') {
			global $post_ID;

			$post_id = $post_ID;

			$revision = get_post($post_id);

			if(!$revision || !BU_Section_Editor::can_edit($revision->post_parent, $user_id)) {
				$caps = array('do_not_allow');
			}
		}

		return $caps;
	}

}

?>