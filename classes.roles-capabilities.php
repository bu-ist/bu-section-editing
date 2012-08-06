<?php

class BU_Section_Editing_Roles {

	// need to figure out the *best* way to create roles
	public function maybe_create() {
		/** Temporary **/
		$role = get_role('section_editor');

		if(empty($role)) {
			add_role('section_editor', 'Section Editor');
		}

		$role = get_role('section_editor');

		$role->add_cap('upload_files');

		$role->add_cap('read');
		$role->add_cap('edit_pages');
		$role->add_cap('edit_others_pages');

		$role->add_cap('delete_posts');
		$role->add_cap('delete_pages');


//		$role->remove_cap('edit_published_pages');
//		$role->remove_cap('publish_pages');
//		$role->remove_cap('delete_others_pages');
//		$role->remove_cap('delete_published_pages');

		$role->add_cap('delete_published_in_section');
		$role->add_cap('edit_published_in_section');
		$role->add_cap('publish_in_section');

		$role->add_cap('edit_posts');
		$role->add_cap('edit_others_posts');

//		$role->remove_cap('edit_published_posts');
//		$role->remove_cap('publish_posts');
//		$role->remove_cap('delete_others_posts');
//		$role->remove_cap('delete_published_posts');

		$role->add_cap('level_1');
		$role->add_cap('level_0');
		$role->add_cap('edit_private_posts');
		$role->add_cap('read_private_posts');
		$role->add_cap('edit_private_pages');
		$role->add_cap('read_private_pages');

		if(defined('BU_CMS') && BU_CMS == true) {
			$role->add_cap('unfiltered_html');
		}

	}

	/**
	 * This filter is only needed for BU installations where the bu_user_management plugin is active
	 *
	 * Hopefully this will not be required some day soon
	 */
	public function allowed_roles( $roles ) {

		if( ! array_key_exists( 'lead_editor', $roles ) )
			$roles[] = 'lead_editor';

		if( ! array_key_exists( 'section_editor', $roles ) )
			$roles[] = 'section_editor';

		return $roles;

	}

	/**
	 * Placeholder function until we determine the best method to determine how
	 * to grant users the ability to edit sections
	 */
	public function get_allowed_users( $query_args = array() ) {

		// For now, allowed users are section editors that belong to the current blog
		$default_args = array(
			'role' => 'section_editor'
			);

		$query_args = wp_parse_args( $query_args, $default_args );

		$wp_user_query = new WP_User_Query( $query_args );

		if( isset( $query_args['count_total'] ) )
			return $wp_user_query->get_total();

		return $wp_user_query->get_results();

	}

	/**
	 * Another placeholder -- checks if the given user is allowed by the plugin
	 * to hold section editing priviliges
	 */
	public function is_allowed_user( $user = null, $query_args = array() ) {

		if( is_null( $user ) ) {
			$user = wp_get_current_user();
		} else if( is_numeric( $user ) ) {
			$user = new WP_User( intval( $user ) );
		}

		if( isset( $user->roles ) && is_array( $user->roles ) ) {

			return( in_array( 'section_editor', $user->roles ) );

		} else {

			error_log( 'Error checking for allowed user: ' . print_r($user,true) );
			return false;
		}

	}

}

class BU_Section_Capabilities {

	/**
	 * @todo this should be part of the edit groups bit.
	 *   
	 *
	 * @param WP_User $user
	 * @param int $post_id
	 */
	 private function in_edit_group( WP_User $user, $post_id )  {
		
		$user_id = $user->ID;

		if( $user_id == 0 ) return false;
		if( $post_id == 0 ) return false;

		// Get all groups for this user
		$edit_groups_o = BU_Edit_Groups::get_instance();
		$groups = $edit_groups_o->find_groups_for_user( $user_id );

		if(empty($groups)) {
			return false;
		}

		foreach( $groups as $key => $group ) {

			// This group is good, bail here
			if( BU_Group_Permissions::group_can_edit( $group->id, $post_id ) ) {
				return true;
			}

		}

		// User is section editor, but not allowed for this section
		return false;
	}

	
	public function get_caps() {

	}

	/**
	 * Filter that modifies the caps based on the current state.
	 *
	 * @param array $caps
	 * @param string $cap
	 * @param int $user_id
	 * @param mixed $args
	 * @return array
	 */
	public function map_meta_cap($caps, $cap, $user_id, $args) {
		global $post_ID;
		
		// avoid infinite loop	

		remove_filter( 'map_meta_cap', array( $this, 'map_meta_cap' ), 10, 4 );
		
		$user = new WP_User( intval( $user_id ) );
		
		if( $this->has_caps( $user, $caps ) || ! user_can( $user, 'edit_in_section' ) ) {
			
			add_filter( 'map_meta_cap', array( $this, 'map_meta_cap' ), 10, 4 );	
			return $caps; // bail early
		}
		
		// edit_page and delete_page get a post ID passed, but publish does not
		if( isset( $args[0] ) ) {
			$id = $args[0];
		}

		if( $this->is_post_cap( $cap, 'edit_post' ) ) {
			$caps = $this->_override_edit_caps( $user, $id, $caps );
		}

		if( $this->is_post_cap( $cap, 'delete_post' ) ) {
			$caps = $this->_override_delete_caps( $user, $id, $caps );
		}

		// As publish_posts does not come tied to a post ID, relying on the global $post_ID is fragile
		// For instance, the "Quick Edit" interface of the edit posts page does not populate this
		// global, and therefore the "Published" status is unavailable with this meta_cap check in place
		if( $this->is_post_cap( $cap, 'publish_posts' ) ) {
			
			if( isset( $post_ID ) ) {
				$id = $post_ID;
			}
			
			$caps = $this->_override_publish_caps( $user, $id, $caps );
				
		}

		add_filter( 'map_meta_cap', array( $this, 'map_meta_cap' ), 10, 4 );	
		
		return $caps;
	}
	
	/**
	 * Returns post_parent or the new post parent if there is $_POST data
	 *
	 **/	
	private function get_post_parent( $post_id ) {

	}

	private function _override_edit_caps(WP_User $user, $post_id, $caps) {

		$parent_id = null;
		$post = get_post( $post_id );
		$post_type = get_post_type_object( $post->post_type );

		if( $post_type->hierarchical != true ) {
			if( $this->in_edit_group( $user, $post_id ) ) {
				$caps = array('edit_published_in_section');
			}
		} else {
			
			// Post parent has switched
			if(isset($_POST['post_ID']) && $post_id == $_POST['post_ID'] && isset($_POST['parent_id']) &&  $post->post_parent != $_POST['parent_id']) {
				$parent_id = (int) $_POST['parent_id'];

				if( $post->post_status == 'publish' && $this->in_edit_group($user, $parent_id)) {
					$caps = array('edit_published_in_section');
				}

			}

			if( $id && $post->post_status == 'publish' && $this->in_edit_group( $user, $post_id ) ) {
				$caps = array('edit_published_in_section');
			}
		}

		return $caps;
	}

	private function _override_delete_caps(WP_User $user, $post_id, $caps) {

		$post = get_post( $post_id );
		$post_type = get_post_type_object( $post->post_type );

		if($post_type->hierarchical != true) {
			if( $this->in_edit_group( $user, $post_id ) ) {
				$caps = array('delete_published_in_section');
			}
		} else {
			if($post_id && $post->post_status == 'publish' && $this->in_edit_group( $user, $post_id ) ) {
				$caps = array('delete_published_in_section');
			}
		}

		return $caps;
	}

	private function _override_publish_caps(WP_User $user, $post_id, $caps ) {
		
		if( ! isset( $post_id ) ) {
			return;
		}
		
		$parent_id = null;

		$post = get_post($post_id);
		
		$post_type = get_post_type($post);
		
		$post_type = get_post_type_object($post_type);
		
		$is_alt = false;
		
		// BU Versions uses the post_parent to relate the alternate version 
		// to the original
		if(class_exists('BU_Version_Workflow')) {
			$is_alt = BU_Version_Workflow::$v_factory->is_alt(get_post_type($post));
		}

		if( $post_type->hierarchical != true && $is_alt != true ) {
			if( $this->in_edit_group( $user, $post_id ) ) {
				$caps = array('publish_in_section');
			}
		} else {
			// User is attempting to switch post parent while publishing
			if( isset($_POST['post_ID']) && $post_id == $_POST['post_ID'] && isset($_POST['parent_id']) && $post->post_parent != $_POST['parent_id'] ) {

				$parent_id = (int) $_POST['parent_id'];

				// Can't move published posts under sections they can't edit
				if( $this->in_edit_group( $user, $parent_id ) ) {
					$caps = array('publish_in_section');
				}

			} else {
				if ( isset( $post_id ) && $this->in_edit_group( $user, $post->post_parent ) ) {
					$caps = array('publish_in_section');
				}
			}
		}

		return $caps;
	}

	
	public function has_caps( WP_User $user, $caps ) {
		
		foreach( $caps as $cap ) {
			if( ! user_can( $user, $cap ) ) { 
				return false;
			}
		}

		return true;
	}

	// grab all post caps so we can quickly check whether we are dealing with a 
	// post cap
	public function set_post_caps() {

	}

	public function get_post_types() {
		if( ! isset( $this->post_types ) ) {
			$this->post_types = get_post_types(null, 'objects');
		}

		return $this->post_types;
	}	
	
	public function is_post_cap( $cap, $map_cap ) {
		
		$caps = array();

		foreach( $this->get_post_types() as $post_type ) {
			if( isset( $post_type->cap->$map_cap ))
				$caps[] = $post_type->cap->$map_cap;
		}

		return in_array( $cap, $caps );
	}
}

?>
