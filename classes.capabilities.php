<?php

class BU_Section_Capabilities {

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
	 * Add (edit|publish|delete)_*_in_section caps to the given role.
	 *
	 * @param mixed $role a WP_Role object, or string representation of a role name
	 */
	public function add_caps( $role ) {

		if ( is_string( $role ) ) {
			$role = get_role( $role );
		}

		if ( empty( $role ) || ! is_object( $role ) ) {
			error_log( __METHOD__ . ' - Invalid role!' );
			return false;
		}

		foreach( $this->get_caps() as $cap ) {
			$role->add_cap( $cap );
		}

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

		// if user alread has the caps as passed by map_meta_cap() pre-filter or
		// the user doesn't have the main "section editing" cap
		if( $this->user_has_caps( $user, $caps ) || ! $this->user_has_cap( $user, 'edit_in_section' ) ) {
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
			if( BU_Group_Permissions::can_edit_section( $user, $post_id ) ) {
				$caps = array($this->get_section_cap('edit', $post->post_type));
			}
		} else {

			if( $this->is_parent_changing( $post ) ) {
				$parent_id = $this->get_new_parent( $post );

				if( $post->post_status == 'publish' && BU_Group_Permissions::can_edit_section( $user, $parent_id ) ) {
					$caps = array($this->get_section_cap('edit', $post->post_type));
				}
			}

			if( $post_id && $post->post_status == 'publish' && BU_Group_Permissions::can_edit_section( $user, $post_id ) ) {
				$caps = array($this->get_section_cap('edit', $post->post_type));
			}
		}
		return $caps;
	}

	private function _override_delete_caps(WP_User $user, $post_id, $caps) {

		$post = get_post( $post_id );
		$post_type = get_post_type_object( $post->post_type );

		if( $post_type->hierarchical != true ) {
			if( BU_Group_Permissions::can_edit_section( $user, $post_id ) ) {
				$caps = array($this->get_section_cap('delete', $post->post_type));
			}
		} else {
			if( $post_id && in_array( $post->post_status, array( 'publish', 'trash' ) ) && BU_Group_Permissions::can_edit_section( $user, $post_id ) ) {
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
			if( BU_Group_Permissions::can_edit_section( $user, $post_id ) ) {
				$caps = array($this->get_section_cap('publish', $post->post_type));
			}
		} else {
			// User is attempting to switch post parent while publishing
			if( $this->is_parent_changing( $post ) ) {

				$parent_id = $this->get_new_parent( $post );

				// Can't move published posts under sections they can't edit
				if( BU_Group_Permissions::can_edit_section( $user, $parent_id ) ) {
					$caps = array($this->get_section_cap('publish', $post->post_type));
				}

			} else {
				if ( isset( $post_id ) && BU_Group_Permissions::can_edit_section( $user, $post->post_parent ) ) {
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


	public function user_has_caps( WP_User $user, $caps ) {

		foreach( $caps as $cap ) {
			if( ! $this->user_has_cap( $user, $cap ) ) {
				return false;
			}
		}

		return true;
	}

	public function user_has_cap( WP_User $user, $cap ) {
		if( isset( $user->allcaps[ $cap ] ) && $user->allcaps[ $cap ] ) {
			return true;
		}
		return false;
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
