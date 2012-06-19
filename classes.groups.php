<?php

/**
 * Section editor group controller
 * 
 * @todo store groups as posts in a custom post type.
 */
class BU_Edit_Groups {

	const OPTION_NAME = '_bu_section_groups';
	const INDEX_NAME = '_bu_section_groups_index';

	public $groups = array();
	public $index = null;

	static protected $instance;

	/**
	 * Load groups and index from db on instantiation
	 * 
	 * Usage of global singleton pattern assures this method is only called once
	 */ 
	protected function __construct() {

		$this->load();
		
	}

	/**
	 * Generates/fetches global singleton instance
	 */ 
	static public function get_instance() {

		if(!isset(BU_Edit_Groups::$instance)) {
			BU_Edit_Groups::$instance = new BU_Edit_Groups();
		}

		return BU_Edit_Groups::$instance;
	}

	// ___________________PUBLIC_INTERFACE_____________________

	/**
	 * Returns a group by id from internal groups array
	 * 
	 * @param int $id edit group ID to return
	 * @return BU_Edit_Group|bool a BU_Edit_Group object if one exists, otherwise false
	 */ 
	public function get($id) {

		foreach( $this->groups as $group ) {
			if( $group->id == $id )
				return $group;
		}

		return false;
	}

	/**
	 * Return an array of all groups
	 * 
	 * @return type 
	 */
	public function get_groups() {

		return $this->groups;
		
	}

	/**
	 * Add a BU_Edit_Group object to internal groups array
	 */ 
	private function add( BU_Edit_Group $group ) {

		array_push($this->groups, $group);

	}

	/**
	 * Remove all groups from internal array 
	 */
	public function delete_groups() {

		$this->groups = array();
	}

	/**
	 * Add a new section editing group
	 * 
	 * @param array $args an array of parameters for group initialization
	 * @return BU_Edit_Group the group that was just added
	 */ 
	public function add_group($args) {

		// Process input
		$args['name'] = sanitize_text_field( stripslashes( $args['name'] ) );
		$args['users'] = isset($args['users']) ? array_map( 'absint', $args['users'] ) : array();

		foreach( $args['perms'] as $post_type => $post_statuses ) {
			if( ! is_array( $post_statuses ) ) {
				error_log("Unepected value for post stati: $post_statuses" );
				unset( $args['perms'][$post_type]);
				continue;
			}

			foreach( $post_statuses as $post_id => $status ) {
				if( ! in_array( $status, array( 'allowed', 'denied', '' ) ) ) {
					error_log("Removing post $post_id due to unexpected status: $status" );
					unset( $args['perms'][$post_type][$post_id] );
				}
			}
		}

		// Create new model
		$group = new BU_Edit_Group($args);
		
		// Update attributes
		$group->id = $this->index;
		$group->created = time();
		$group->modified = time();

		// Add to our model list
		$this->add($group);

		// Commit updates
		$this->increment_index();
		$this->update_group_permissions( $group->id, $args['perms'] );
		$this->save();

		add_action( 'bu_add_section_editing_group', $group );

		return $group;

	}

	/**
	 * Update an existing section editing group
	 * 
	 * @param int $id the id of the group to update
	 * @param array $args an array of parameters with group fields to update
	 * @return BU_Edit_Group|bool the group that was just updated or false if none existed
	 */
	public function update_group($id, $args = array()) {

		$group = $this->get($id);

		if($group) {

			// Process input
			$args['name'] = sanitize_text_field( stripslashes( $args['name'] ) );
			$args['users'] = isset($args['users']) ? array_map( 'absint', $args['users'] ) : array();

			foreach( $args['perms'] as $post_type => $post_statuses ) {
				if( ! is_array( $post_statuses ) ) {
					error_log("Unepected value for post stati: $post_statuses" );
					unset( $args['perms'][$post_type]);
					continue;
				}

				foreach( $post_statuses as $post_id => $status ) {
					if( ! in_array( $status, array( 'allowed', 'denied', '' ) ) ) {
						error_log("Removing post $post_id due to unexpected status: $status" );
						unset( $args['perms'][$post_type][$post_id] );
					}
				}
			}

			// Update our model list
			$group->update($args);

			// Bump modified time
			$group->modified = time();

			// Commit updates
			$this->update_group_permissions( $group->id, $args['perms'] );
			$this->save();

			return $group;

		}

		return false;

	}

	/**
	 * Delete an existing section editing group
	 * 
	 * @param int $id the id of the group to delete
	 * @return bool true on success, false on failure
	 */
	public function delete_group($id) {

		foreach( $this->groups as $index => $group ) {

			if( $group->id == $id) {

				unset($this->groups[$index]);
				$this->groups = array_values($this->groups);	// reindex

				// Remove permissions data
				$this->delete_group_permissions($id);

				// Commit changes
				$this->save();

				return true;
			
			}

		}

		return false;

	}

	/**
	 * Returns an array of group ID's for which the specified user is a member
	 * 
	 * @param int $user_id WordPress user id
	 * @return array array of group ids for which the specified user belongs
	 */ 
	public function find_groups_for_user($user_id) {
		
		$groups = array();
		
		foreach ($this->groups as $group) {
			if($group->has_user($user_id)) {
				array_push($groups, $group);
			}
		}

		return $groups;
	}

	/**
	 * Returns whether or not a user exists in an array of edit groups
	 * 
	 * @param array $groups an array of BU_Edit_Group objects to check
	 * @param int $user_id WordPress user id to check
	 */ 
	public function has_user($groups, $user_id) {

			if( ! is_array( $groups ) )
				$groups = array( $groups );

			foreach($groups as $group_id) {

				$group = $this->get($group_id);
				
				if( $group && $group->has_user($user_id)) {
					return true;
				}

			}

			return false;
	}

	/**
	 * Convert internal BU_Edit_Group array to data array and commit to db
	 * 
	 * @return bool true on succesfully save, false on failure
	 */ 
	public function save() {

		$group_data = array();

		foreach( $this->groups as $group ) {

			$group_data[] = $group->get_attributes();

		}

		return $this->update( $group_data );

	}

	/**
	 * Load group data models and group index counter from database
	 */ 
	public function load() {

		$groups = get_option(self::OPTION_NAME);

		// create groups from db data
		if(is_array($groups)) {

			foreach( $groups as $group_data ) {

				$group = new BU_Edit_Group( $group_data );
				$this->add($group);

			}

		}

		// Auto-increment index (starts at 1)
		$index = get_option(self::INDEX_NAME);
		if( $index === false ) $index = 1;

		$this->index = $index;

	}

	// ____________________PERSISTENCE________________________


	/**
	 * Update permissions for a group
	 * 
	 * @param int $group_id ID of group to modify ACL for
	 * @param array $permissions Permissions, as an associative array indexed by post type
	 */ 
	private function update_group_permissions( $group_id, $permissions ) {
		global $wpdb;

		if( ! is_array( $permissions ) )
			return false;

		foreach( $permissions as $post_type => $new_perms ) {

			// error_log('= Updating permissions for: ' . $post_type . '=' );
			if( ! is_array( $new_perms ) ) {
				error_log( "Unexpected value found while updating permissions: $new_perms" );
				continue;
			}

			// For later need to differentiate between flat/hierarchical post types
			$post_type_obj = get_post_type_object( $post_type );

			if( is_null( $post_type_obj ) ) {
				error_log('Bad post type!');
				continue;
			}

			// Hierarchical post types
			if( $post_type_obj->hierarchical ) {

				// Incoming allowed posts
				$allowed_ids = array_keys( $permissions[$post_type], 'allowed' );

				if( ! empty( $allowed_ids ) ) {

					/* 
					Could cut down on update_post_meta calls by only allowing posts that weren't previously allowed:

					$allowed_select = sprintf("SELECT post_id FROM %s WHERE post_id IN (%s) AND meta_key = '%s' AND meta_value = '%s'", 
						$wpdb->postmeta,
						implode( ',', $allowed_ids ),
						BU_Edit_Group::META_KEY,
						$group_id
						);

					$previously_allowed = $wpdb->get_col( $allowed_select );
					$additions = array_merge( array_diff( $allowed_ids, $previously_allowed ) );
					*/

					foreach( $allowed_ids as $post_id ) {
						error_log('Allowing post: ' . $post_id );
						update_post_meta( $post_id, BU_Edit_Group::META_KEY, $group_id, $group_id );
					}

				}

				// Incoming restricted posts
				$denied_ids = array_keys( $permissions[$post_type], 'denied' );

				if( ! empty( $denied_ids ) ) {

					// Select meta_id's for removal based on incoming posts
					$denied_select = sprintf("SELECT meta_id FROM %s WHERE post_id IN (%s) AND meta_key = '%s' AND meta_value = '%s'", 
						$wpdb->postmeta,
						implode( ',', $denied_ids ),
						BU_Edit_Group::META_KEY,
						$group_id
						);

					$denied_meta_ids = $wpdb->get_col( $denied_select );

					// Bulk deletion
					if( ! empty( $denied_meta_ids ) ) {

						$denied_meta_delete = sprintf("DELETE FROM %s WHERE meta_id IN (%s)",
							$wpdb->postmeta,
							implode(',', $denied_meta_ids )
							);

						// Remove allowed status in one query
						$results = $wpdb->query( $wpdb->prepare( $denied_meta_delete ) );

						// Purge cache
						foreach( $denied_meta_ids as $meta_id ) {
							error_log('Puring post_meta cache for meta id: ' . $meta_id );
							wp_cache_delete( $meta_id, 'post_meta' );
						}

					}

				}

			} else {

				// Fetch all existing permissions for this post type for possible removal
				$prev_perm_query = sprintf("SELECT post_id, post_type, meta_value FROM %s INNER JOIN %s AS p ON p.ID = post_id WHERE meta_key = '%s' AND meta_value = '%s' AND p.post_type = '%s'", 
					$wpdb->postmeta,
					$wpdb->posts, 
					BU_Edit_Group::META_KEY, 
					$group_id,
					$post_type
					);

				$prev_perms = $wpdb->get_results( $prev_perm_query, OBJECT_K );

				// Create list of all unique post ID's with permissions assigned, new or existing
				$post_ids = array_merge( array_keys( $prev_perms ), array_keys( $new_perms ) );
				$post_ids = array_unique( $post_ids );

				// @todo clean this up so that we don't update post meta unnecessarily
				foreach( $post_ids as $post_id ) {
				
					// Get new status, if there is one
					$status = array_key_exists( $post_id, $new_perms ) ? $new_perms[$post_id] : 'denied';

					// No new value = post is now denied, delete existing perms
					if( $status == 'allowed' ) {

							update_post_meta( $post_id, BU_Edit_Group::META_KEY, $group_id );
							error_log('Updating status for post: ' . $post_id );

					} else {

							delete_post_meta( $post_id, BU_Edit_Group::META_KEY, $group_id );
							error_log('Deleting existing statuses for post: ' . $post_id );

					}

				}

			}

		}

	}

	private function delete_group_permissions( $group_id ) {

		$supported_post_types = BU_Permissions_Editor::get_supported_post_types( 'names' );

		$meta_query = array(
			'key' => BU_Edit_Group::META_KEY,
			'value' => $group_id . ':',
			'compare' => 'LIKE'
			);

		$args = array(
			'post_type' => $supported_post_types,
			'meta_query' => array( $meta_query ),
			'posts_per_page' => -1,
			'fields' => 'ids'
			);

		$query = new WP_Query( $args );

		foreach( $query->posts as $post_id ) {
			delete_post_meta( $post_id, BU_Edit_Group::META_KEY, $group_id . BU_Edit_Group::SUFFIX_ALLOWED );
			delete_post_meta( $post_id, BU_Edit_Group::META_KEY, $group_id . BU_Edit_Group::SUFFIX_DENIED );
		}

	}

	/**
	 * Commit section editing group data to database
	 */ 
	private function update( $group_data ) {

		return update_option(self::OPTION_NAME, $group_data );
	
	}

	/**
	 * Remove section editing group data from database
	 */ 
	private function delete() {

		return delete_option(self::OPTION_NAME);
	
	}

	/**
	 * Simulates MySQL autoincrement for group ID field
	 */ 
	private function increment_index() {

		$this->index++;

		return update_option( self::INDEX_NAME, $this->index );

	}

}

/**
 * Class for listing groups (designed to be extended) 
 */
class BU_Groups_List {

	public $current_group;
	public $edit_groups;

	function __construct() {
		$this->edit_groups = BU_Edit_Groups::get_instance();
		$this->current_group = -1;
	}

	function have_groups() {
		if(count($this->edit_groups->groups) > 0 && $this->current_group < (count($this->edit_groups->groups) - 1)) {
			return true;
		} else {
			return false;
		}
	}

	function the_group() {
		$this->current_group++;
		return $this->edit_groups->groups[$this->current_group];
	}

	function rewind() {
		$this->current_group = -1;
	}

}

/**
 * A Section Editing group model
 */ 
class BU_Edit_Group {

	private $id = null;
	private $name = null;
	private $description = null;
	private $users = array();
	private $created = null;
	private $modified = null;

	const META_KEY = '_bu_section_group';

	/**
	 * Instantiate new edit group
	 * 
	 * @param array $args optional parameter list to merge with defaults
	 */ 
	function __construct( $args = array() ) {

		// Merge defaults
		$defaults = $this->defaults();
		$args = wp_parse_args( $args, $defaults );

		// Update fields based on incoming parameter list
		$fields = array_keys($this->get_attributes());
		foreach( $fields as $key ) {
			$this->$key = $args[$key];
		}

	}

	/**
	 * Returns an array with default parameter values for edit group
	 * 
	 * @return array default values for edit group model
	 */ 
	private function defaults() {
		
		$fields = array(
			'id' => -1,
			'name' => '',
			'description' => '',
			'users' => array(),
			'created' => time(),
			'modified' => time()
			);

		return $fields;
	}

	/**
	 * Does the specified user exist for this group?
	 * 
	 * @return bool true if user exists, false otherwise
	 */ 
	public function has_user($user_id) {
		return in_array($user_id, $this->users);
	}

	/**
	 * Add a new user to this group
	 * 
	 * @param int $user_id WordPress user ID to add for this group
	 */ 
	public function add_user($user_id) {

		// need to make sure the user is a member of the site
		if(!$this->has_user($user_id)) {
			array_push($this->users, $user_id);
		}

	}

	public function get_active_users() {

		$active_users = array();

		foreach( $this->users as $user_id ) {
			if( BU_Section_Editing_Plugin::is_allowed_user( $user_id ) )
				$active_users[] = $user_id;
		}

		return $active_users;
		
	}

	/**
	 * Remove a user from this group
	 * 
	 * @param int $user_id WordPress user ID to remove from this group
	 */ 
	public function remove_user($user_id) {
		
		if($this->have_user($user_id)) {
			unset($this->users[array_search($user_id, $this->users)]);
		}

	}

	/**
	 * Query for all posts that have section editing permissions assigned for this group
	 * 
	 * @uses WP_Query
	 *
	 * @param array $args an optional array of WP_Query arguments, will override defaults
	 * @return array an array of posts that have section editing permissions for this group
	 */ 
	public function get_posts( $args = array() ) {

		$defaults = array(
			'post_type' => 'page',
			'meta_key' => self::META_KEY,
			'meta_value' => $this->id,
			'posts_per_page' => -1
			);

		$args = wp_parse_args( $args, $defaults );

		$query = new WP_Query( $args );

		return $query->posts;
	}

	/**
	 * Get count for posts with permissions by post type
	 * 
	 * @todo Fix this, it's broken :(
	 * @uses WP_Query
	 *
	 * @param array $args an optional array of WP_Query arguments, will override defaults
	 * @return array an array of posts that have section editing permissions for this group
	 */ 
	public function get_posts_count() {
		global $wpdb;

		// YOU WERE HERE

		return $count;

	}

	/**
	 * Update data fields for this group
	 * 
	 * @param array $args an array of key => value parameters to update
	 */ 
	public function update($args = array()) {

		$valid_fields = array_keys( $this->get_attributes() );

		foreach( $args as $key => $val ) {
			if( in_array($key, $valid_fields))
				$this->$key = $val;
		}

	}

	/**
	 * Returns privata data field keys as an array of attribute names
	 * 
	 * Used for data serialization
	 */ 
	public function get_attributes() {

		return get_object_vars( $this );

	}

	public function __get( $key ) {

		if( isset( $this->$key ) )
			return $this->$key;

		return null;
	}

	public function __set( $key, $val ) {

		$this->$key = $val;
	
	}

}

?>
