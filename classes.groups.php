<?php

/**
 * Section editor group controller
 * 
 * @todo investigate replacing in-memory groups store with cache API
 */
class BU_Edit_Groups {

	const POST_TYPE_NAME = 'buse_group';
	const MEMBER_KEY = '_buse_group_users';

	public $groups = array();

	static protected $instance;

	/**
	 * Load groups and index from db on instantiation
	 * 
	 * Usage of global singleton pattern assures this method is only called once
	 */ 
	protected function __construct() {

		// Load group data
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

	/**
	 * Register hidden post type for group data storage
	 */ 
	static public function register_post_type() {

		$args = array(
			'label' => 'Section Groups',
			'public' => false,
			'publicly_queryable' => false,
			'show_ui' => false, 
			'show_in_menu' => false, 
			'query_var' => true,
			'rewrite' => false,
			'capability_type' => 'post',
			'has_archive' => false, 
			'hierarchical' => false,
			'menu_position' => null,
			'can_export' => true
		);

		register_post_type( self::POST_TYPE_NAME, $args );

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
	 * Remove all groups from internal array 
	 */
	public function delete_groups() {

		$this->groups = array();

	}

	/**
	 * Add a new section editing group
	 * 
	 * @param array $data an array of parameters for group initialization
	 * @return BU_Edit_Group the group that was just added
	 */ 
	public function add_group($data) {

		// Sanitize input
		$this->_clean_group_data( $data );

		// Create new group from args
		$group = $this->insert( $data );

		if( ! $group instanceof BU_Edit_Group )
			return false;
		
		// Set permissions
		if( isset( $data['perms'] ) )
			$this->update_group_permissions( $group->id, $data['perms'] );

		// Notify
		add_action( 'bu_add_section_editing_group', $group );

		return $group;

	}

	/**
	 * Update an existing section editing group
	 * 
	 * @param int $id the id of the group to update
	 * @param array $data an array of parameters with group fields to update
	 * @return BU_Edit_Group|bool the group that was just updated or false if none existed
	 */
	public function update_group($id, $data = array()) {

		if( $this->get($id) === false )
			return false;

		// Sanitize.
		$this->_clean_group_data( $data );

		// Update group.
		$group = $this->update( $id, $data);

		if( ! $group instanceof BU_Edit_Group )
			return false;

		// Update permissions.
		$this->update_group_permissions( $id, $data['perms'] );

		return $group;

	}

	/**
	 * Delete an existing section editing group
	 * 
	 * @param int $id the id of the group to delete
	 * @return bool true on success, false on failure
	 */
	public function delete_group($id) {

		// Remove group.
		$result = $this->delete( $id );

		if( ! $result ) {
			error_log('Error deleting group: ' . $id );
			return false;
		}

		// Remove group permissions.
		$this->delete_group_permissions($id);

		return true;

	}

	/**
	 * Returns an array of group ID's for which the specified user is a member
	 * 
	 * @param int $user_id WordPress user id
	 * @return array array of group ids for which the specified user belongs
	 */ 
	public function find_groups_for_user($user_id, $output = 'objects' ) {
		
		$groups = array();
		
		foreach ($this->groups as $group) {
			if($group->has_user($user_id)) {

				if( $output === 'objects' )
					$groups[$group->id] = $group;
				else if( $output === 'ids' )
					array_push( $groups, $group->id );
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
	 * Get allowed post count, optionally filtered by user ID, group or post_type
	 *
	 * @todo cleanup this query
	 *  
	 * @param $args array optional args
	 * 
	 * @return int allowed post count for the given post type 
	 */ 
	public function get_allowed_post_count( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'user_id' => null,
			'group' => null,
			'post_type' => null,
			'include_unpublished' => false
			);

		extract( wp_parse_args( $args, $defaults ) );

		$group_ids = array();

		// If user_id is passed, populate group ID's from their memberships
		if( $user_id ) {

			if( is_null( get_userdata( $user_id ) ) ) {
				error_log('No user found for ID: ' . $user_id );
				return false;
			}

			// Get groups for users
			$group_ids = $this->find_groups_for_user( $user_id, 'ids' );

		}

		// If no user ID is passed, but a group is, convert to array
		if( is_null( $user_id ) && $group ) {

			if( is_array( $group ) )
				$group_ids = $group;

			if( is_numeric( $group ) )
				$group_ids = array($group);

		}

		// Bail if we don't have any valid groups by now
		if( empty( $group_ids ) ) {
			//error_log('Exiting allowed post count, no valid groups...');
			return false;
		}

		$posts_join = $post_type_clause = $post_status_or = '';

		// Maybe filter by post type and status
		if( ! is_null( $post_type ) && ! is_null( $pto = get_post_type_object( $post_type ) ) ) {

			$posts_join = "INNER JOIN {$wpdb->posts} AS p ON p.ID = post_id ";
			$post_type_clause = "AND p.post_type = '$post_type' ";

		}

		if( $include_unpublished )
			$post_status_or = "OR (p.post_status IN ('draft','pending') $post_type_clause)";

		$count_query = sprintf( "SELECT DISTINCT(p.ID) FROM %s %s WHERE (meta_key = '%s' AND meta_value IN (%s) %s) %s GROUP BY p.ID",
			$wpdb->postmeta,
			$posts_join,
			BU_Edit_Group::META_KEY,
			implode( ',', $group_ids ),
			$post_type_clause,
			$post_status_or
			);

		$ids = $wpdb->get_col( $count_query );

		$count = count($ids);

		return $count;

	}

	// ____________________PERSISTENCE________________________

	/**
	 * Load all groups
	 */ 
	public function load() {

		$args = array(
			'post_type'=>self::POST_TYPE_NAME,
			'numberposts'=>-1,
			'orderby' => 'ID',
			'order' => 'ASC'
			);

		$group_posts = get_posts($args);

		if(is_array($group_posts)) {

			foreach( $group_posts as $group_post ) {

				$this->groups[] = $this->_post_to_group( $group_post );

			}

		}

	}

	/**
	 * Insert a new group
	 * 
	 * @param array $data a parameter list of group data for insertion 
	 * @return bool|BU_Edit_Group False on failure.  A BU_Edit_Group instance for the new group on success.
	 */ 
	protected function insert( $data ) {

		// Create new group
		$group = new BU_Edit_Group($data);

		// Map group data to post for insertion
		$postdata = $this->_group_to_post( $group );

		// Insert into DB
		$result = wp_insert_post( $postdata );

		if( is_wp_error( $result ) ) {
			error_log(sprintf('Error adding group: %s', $result->get_error_message()));
			return false;
		}

		// Add auto-generated ID
		$group->id = $result;

		// Add group member meta
		add_post_meta( $group->id, self::MEMBER_KEY, $group->users );

		// Add group to internal groups store
		$this->groups[] = $group;

		return $group;

	 }

	/**
	 * Update an existing group
	 * 
	 * @param int $id ID of group to update
	 * @param array $data a parameter list of group data for update 
	 * @return bool|BU_Edit_Group False on failure.  A BU_Edit_Group instance for the updated group on success.
	 */ 
	 protected function update( $id, $data ) {

	 	// Fetch existing group
		$group = $this->get( $id );

		if( ! $group instanceof BU_Edit_Group )
			return false;

		// Update group data
		$group->update( $data );

		// Map group data to post for update
		$postdata = $this->_group_to_post( $group );

		// Update DB
		$result = wp_update_post( $postdata );

		if( is_wp_error( $result ) ) {
			error_log(sprintf('Error updating group %s: %s', $group->id, $result->get_error_message()));
			return false;
		}

		// Update group member meta
		update_post_meta( $group->id, self::MEMBER_KEY, $group->users );

		// Update internal groups store
		foreach( $this->groups as $index => $group ) {

			if( $group->id == $id) {

				$this->groups[$index] = $group;
			}

		}

		return $group;

	}

	/**
	 * Delete section editing group
	 * 
	 * @param int $id ID of group to delete
	 * @return bool 
	 */ 
	protected function delete( $id ) {

		foreach( $this->groups as $index => $group ) {

			if( $group->id == $id) {

				// Delete from db
				$result = wp_delete_post( $id, true );

				if( $result === false )
					return false;

				// Delete from internal groups store
				unset($this->groups[$index]);
				$this->groups = array_values($this->groups);	// reindex

				return $group;
			
			}

		}

		return false;

	}

	/**
	 * Sanitizes array of group data prior to group creation or updating
	 */ 
	protected function _clean_group_data( &$args ) {

		// Process input
		$args['name'] = sanitize_text_field( stripslashes( $args['name'] ) );
		$args['description'] = isset($args['description']) ? sanitize_text_field( stripslashes( $args['description'] ) ) : '';
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

	}
	
	/**
	 * Maps a group object to post object
	 * 
	 * @param BU_Edit_Group $group Group object for translation
	 * @return StdClass $post Resulting post object
	 */ 
	protected function _group_to_post( $group ) {

		$post = new stdClass();

		if( $group->id > 0 )
			$post->ID = $group->id;

		$post->post_type = self::POST_TYPE_NAME;
		$post->post_title = $group->name;
		$post->post_content = $group->description;
		$post->post_status = 'publish';

		return $post;

	}

	/**
	 * Maps a WP post object to group object
	 * 
	 * @param StdClass $post Post object for translation
	 * @return BU_Edit_Group $group Resulting group object
	 */ 
	protected function _post_to_group( $post ) {

		// Map post -> group fields
		$data['id'] = $post->ID;
		$data['name'] = $post->post_title;
		$data['description'] = $post->post_content;
		$data['created'] = $post->post_date;
		$data['modified'] = $post->post_modified;
		$data['users'] = get_post_meta( $post->ID, self::MEMBER_KEY, true );

		// Create a new group
		$group = new BU_Edit_Group( $data );

		return $group;

	}

	// ____________________PERMISSIONS________________________

	/**
	 * Update permissions for a group
	 * 
	 * @todo move this to a BU_Group_Permissions class
	 * 
	 * @param int $group_id ID of group to modify ACL for
	 * @param array $permissions Permissions, as an associative array indexed by post type
	 */ 
	private function update_group_permissions( $group_id, $permissions ) {
		global $wpdb;

		if( ! is_array( $permissions ) )
			return false;

		foreach( $permissions as $post_type => $new_perms ) {

			if( ! is_array( $new_perms ) ) {
				error_log( "Unexpected value found while updating permissions: $new_perms" );
				continue;
			}

			// Incoming allowed posts
			$allowed_ids = array_keys( $permissions[$post_type], 'allowed' );

			if( ! empty( $allowed_ids ) ) {

				$allowed_select = sprintf("SELECT post_id FROM %s WHERE post_id IN (%s) AND meta_key = '%s' AND meta_value = '%s'", 
					$wpdb->postmeta,
					implode( ',', $allowed_ids ),
					BU_Edit_Group::META_KEY,
					$group_id
					);

				$previously_allowed = $wpdb->get_col( $allowed_select );
				$additions = array_merge( array_diff( $allowed_ids, $previously_allowed ) );

				foreach( $additions as $post_id ) {

					add_post_meta( $post_id, BU_Edit_Group::META_KEY, $group_id );

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
					foreach( $denied_ids as $post_id ) {
						wp_cache_delete( $post_id, 'post_meta' );
					}

				}

			}
			
		}

	}

	private function delete_group_permissions( $group_id ) {

		$supported_post_types = BU_Permissions_Editor::get_supported_post_types( 'names' );

		$meta_query = array(
			'key' => BU_Edit_Group::META_KEY,
			'value' => $group_id,
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
			delete_post_meta( $post_id, BU_Edit_Group::META_KEY, $group_id );
		}

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
	 * Can this group edit a particular post
	 * 
	 */ 
	public function can_edit( $post_id ) {
		
		$allowed_groups = get_post_meta( $post_id, BU_Edit_Group::META_KEY );
		return in_array( $this->id, $allowed_groups ) ? true : false;
	
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
