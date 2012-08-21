<?php

class BU_Section_Editing_Roles {

	public function maybe_create() {
		
		$role = get_role('section_editor');

		if(empty($role)) {
			add_role('section_editor', 'Section Editor');
		}

		$role =& get_role('section_editor');

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

		$caps = BU_Section_Editing_Plugin::$caps->get_caps();
		
		foreach( $caps as $cap ) {
			$role->add_cap( $cap );
		}

		if(defined('BU_CMS') && BU_CMS == true) {
			$role->add_cap('unfiltered_html');
		}

	}

	/**
	 * This filter is only needed for BU installations where the bu_user_management plugin is active
	 *
	 */
	public function allowed_roles( $roles ) {

		if( ! array_key_exists( 'section_editor', $roles ) ) {
			$roles[] = 'section_editor';
		}

		return $roles;

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
	 private function can_edit_section( WP_User $user, $post_id )  {
		
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
	

	/**
	 * Get all Section Editing caps for the registered post types.
	 *
	 * @return array $caps
	 **/
	public function get_caps() {

		$caps = array('edit_in_section');
		$operations = array('edit', 'delete', 'publish');
		$post_types = $this->get_post_types();
		
		foreach( $post_types as $post_type ) {
			if( $post_type->public != true || in_array( $post_type->name, array( 'attachment' ) ) ) {
				continue;
			}
			foreach( $operations as $op ) {
				$caps[] = $this->get_section_cap($op, $post_type->name);
			}
		}

		return $caps;

	}

	/**
	 * Filter that modifies the caps needing to take certain actions in cases 
	 * where the user ($user_id) does not have the capabilities that WordPress 
	 * has mapped to the meta capability. The mapping is based on which post is 
	 * being edited, the Section Groups granted access to the post, and the 
	 * membership of the user in those groups.
	 *
	 *
	 * @param array $caps
	 * @param string $cap
	 * @param int $user_id
	 * @param mixed $args
	 * @return array
	 */
	public function map_meta_cap($caps, $cap, $user_id, $args) {
		global $post_ID;
		
		
		$user = new WP_User( intval( $user_id ) );
		
		// avoid infinite loop
		remove_filter( 'map_meta_cap', array( $this, 'map_meta_cap' ), 10, 4 );
		
		// if user alread has the caps as passed by map_meta_cap() pre-filter or 
		// the user doesn't have the main "section editing" cap
		if( $this->has_caps( $user, $caps ) || ! user_can( $user, 'edit_in_section' ) ) {
			add_filter( 'map_meta_cap', array( $this, 'map_meta_cap' ), 10, 4 );
			return $caps; // bail early
		}
		
		if( $this->is_post_cap( $cap, 'edit_post' ) ) {
			$caps = $this->_override_edit_caps( $user, $args[0], $caps );
		}

		if( $this->is_post_cap( $cap, 'delete_post' ) ) {
			$caps = $this->_override_delete_caps( $user, $args[0], $caps );
		}

		// As publish_posts does not come tied to a post ID, relying on the global $post_ID is fragile
		// For instance, the "Quick Edit" interface of the edit posts page does not populate this
		// global, and therefore the "Published" status is unavailable with this meta_cap check in place
		if( $this->is_post_cap( $cap, 'publish_posts' ) ) {
			$caps = $this->_override_publish_caps( $user, $post_ID, $caps );
		}
		
		
		add_filter( 'map_meta_cap', array( $this, 'map_meta_cap' ), 10, 4 );
		return $caps;
	}
	
	/**
	 * Check some $_POST variables to see if the posted data matches the post 
	 * we are checking permissions against
	 **/	
	private function is_parent_changing( $post ) {
		return isset( $_POST['post_ID'] ) && $post->ID == $_POST['post_ID'] && isset( $_POST['parent_id'] ) &&  $post->post_parent != $_POST['parent_id'];
	}

	private function get_new_parent() {
		return (int) $_POST['parent_id'];
	}

	private function _override_edit_caps(WP_User $user, $post_id, $caps) {

		$parent_id = null;
		$post = get_post( $post_id );
		$post_type = get_post_type_object( $post->post_type );

		if( $post_type->hierarchical != true ) {
			if( $this->can_edit_section( $user, $post_id ) ) {
				$caps = array($this->get_section_cap('edit', $post->post_type));
			}
		} else {
			
			if( $this->is_parent_changing( $post ) ) {
				$parent_id = $this->get_new_parent( $post );

				if( $post->post_status == 'publish' && $this->can_edit_section( $user, $parent_id ) ) {
					$caps = array($this->get_section_cap('edit', $post->post_type));
				}
			}

			if( $id && $post->post_status == 'publish' && $this->can_edit_section( $user, $post_id ) ) {
				$caps = array($this->get_section_cap('edit', $post->post_type));
			}
		}

		return $caps;
	}

	private function _override_delete_caps(WP_User $user, $post_id, $caps) {

		$post = get_post( $post_id );
		$post_type = get_post_type_object( $post->post_type );

		if( $post_type->hierarchical != true ) {
			if( $this->can_edit_section( $user, $post_id ) ) {
				$caps = array($this->get_section_cap('delete', $post->post_type));
			}
		} else {
			if( $post_id && $post->post_status == 'publish' && $this->can_edit_section( $user, $post_id ) ) {
				$caps = array($this->get_section_cap('delete', $post->post_type));
			}
		}

		return $caps;
	}

	private function _override_publish_caps(WP_User $user, $post_id, $caps ) {
		
		if( ! isset( $post_id ) ) {
			return $caps;
		}
		
		$parent_id = null;

		$post = get_post($post_id);
		
		$post_type = get_post_type_object( $post->post_type );
		
		$is_alt = false;
		
		// BU Versions uses the post_parent to relate the alternate version 
		// to the original
		if(class_exists('BU_Version_Workflow')) {
			$is_alt = BU_Version_Workflow::$v_factory->is_alt( $post->post_type );
		}

		if( $post_type->hierarchical != true && $is_alt != true ) {
			if( $this->can_edit_section( $user, $post_id ) ) {
				$caps = array($this->get_section_cap('publish', $post->post_type));
			}
		} else {
			// User is attempting to switch post parent while publishing
			if( $this->is_parent_changing( $post ) ) {

				$parent_id = $this->get_new_parent( $post );

				// Can't move published posts under sections they can't edit
				if( $this->can_edit_section( $user, $parent_id ) ) {
					$caps = array($this->get_section_cap('publish', $post->post_type));
				}

			} else {
				if ( isset( $post_id ) && $this->can_edit_section( $user, $post->post_parent ) ) {
					$caps = array($this->get_section_cap('publish', $post->post_type));
				}
			}
		}

		return $caps;
	}

	public function get_section_cap($type, $post_type) {
		
		$cap = '';	
		switch($type) {
			case 'edit':
				$cap = 'edit_' . $post_type . '_in_section';
				break;

			case 'publish':
				$cap = 'publish_' . $post_type . '_in_section';
				break;

			case 'delete':
				$cap = 'delete_' . $post_type . '_in_section';
				break;

			default:
				$cap = 'edit_in_section';
		}
		return $cap;
	}


	public function has_caps( WP_User $user, $caps ) {
		
		foreach( $caps as $cap ) {
			if( ! user_can( $user, $cap ) ) { 
				return false;
			}
		}

		return true;
	}
	
	/**
	 * Get post types and store them in a property.
	 *
	 * @return Array
	 **/
	public function get_post_types() {
		if( ! isset( $this->post_types ) ) {
			$this->post_types = get_post_types(null, 'objects');
		}

		return $this->post_types;
	}	
	
	/**
	 * Whether or not the $cap is a meta cap for one of the registered post types.
	 *
	 * @param $cap
	 * @param $meta_cap
	 * @return bool
	 **/	
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
