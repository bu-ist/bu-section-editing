<?php

/**
 * Section editor group controller
 *
 * @todo investigate replacing in-memory groups store with cache API
 */
class BU_Edit_Groups {

	const POST_TYPE_NAME = 'buse_group';
	const MEMBER_KEY = '_bu_section_group_users';

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

	static public function register_hooks() {

		add_action( 'init', array( __CLASS__, 'register_post_type' ) );

	}

	/**
	 * Register hidden post type for group data storage
	 */
	static public function register_post_type() {

		$labels = array(
			'name'                => _x( 'Section Groups', 'Post Type General Name', BUSE_TEXTDOMAIN ),
			'singular_name'       => _x( 'Section Group', 'Post Type Singular Name', BUSE_TEXTDOMAIN ),
		);

		$args = array(
			'labels'              => $labels,
			'supports'            => array(),
			'hierarchical'        => false,
			'public'              => false,
			'show_ui'             => false,
			'show_in_menu'        => false,
			'show_in_nav_menus'   => false,
			'show_in_admin_bar'   => false,
			'menu_position'       => 5,
			'menu_icon'           => '',
			'can_export'          => true,
			'has_archive'         => false,
			'exclude_from_search' => false,
			'publicly_queryable'  => false,
			'rewrite'             => false,
			'capability_type'     => 'post',
		);

		register_post_type( self::POST_TYPE_NAME, $args );

	}

	// ___________________PUBLIC_INTERFACE_____________________

	/**
	 * Returns a group by ID from internal groups array
	 *
	 * @param int $id unique ID of section group to return
	 * @return BU_Edit_Group|bool the requested section group object, or false on bad ID
	 */
	public function get( $id ) {

		foreach( $this->groups as $group ) {
			if( $group->id == $id )
				return $group;
		}

		return false;
	}

	/**
	 * Add a group object to the internal groups array
	 *
	 * @param BU_Edit_Group $group a valid section editing group object
	 */
	public function add( $group ) {

		if( ! $group instanceof BU_Edit_Group )
			return false;

		$this->groups[] = $group;

	}

	/**
	 * Remove a group by ID from the internal groups array
	 *
	 * @param int $id unique ID of section group to delete
	 * @return BU_Edit_Group|bool the deleted section group object on success, otherwise false
	 */
	public function delete( $id ) {

		foreach( $this->groups as $i => $g ) {
			if( $g->id == $id ) {
				unset($this->groups[$i] );
				$this->groups = array_values($this->groups);	// reindex
				return $g;
			}
		}

		return false;

	}

	/**
	 * Return an array of all groups
	 *
	 * @todo *_groups methods usually touch the DB
	 * 	- investigate renaming to get_all()
	 *
	 * @return type
	 */
	public function get_groups() {

		return $this->groups;

	}

	/**
	 * Remove all groups from internal array
	 *
	 * @todo *_groups methods usually touch the DB
	 * 	- investigate renaming to delete_all()
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
			BU_Group_Permissions::update_group_permissions( $group->id, $data['perms'] );

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
		if( isset( $data['perms'] ) )
			BU_Group_Permissions::update_group_permissions( $group->id, $data['perms'] );

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
		$group = $this->delete( $id );

		if( ! $group ) {
			error_log('Error deleting group: ' . $id );
			return false;
		}

		// Delete from db
		$result = wp_delete_post( $id, true );

		if( $result === false )
			return false;

		// Remove group permissions.
		BU_Group_Permissions::delete_group_permissions($id);

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
	 * @todo remove this if it unused
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
	 * @todo implement cacheing with md5 of args
	 * @todo re-examine and optimize this query
	 * @todo possibly move to BU_Group_Permissions
	 *
	 * @param $args array optional args
	 *
	 * @return int allowed post count for the given post type, group or user
	 */
	public function get_allowed_post_count( $args = array() ) {
		global $wpdb, $bu_navigation_plugin;

		$defaults = array(
			'user_id' => null,
			'group' => null,
			'post_type' => null,
			'include_unpublished' => false,
			'include_links' => true
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

			if( is_numeric( $group ) && $group > 0 )
				$group_ids = array($group);

		}

		// Bail if we don't have any valid groups by now
		if( empty( $group_ids ) ) {
			return false;
		}

		// Generate query
		$post_type_clause = $post_status_clause = '';

		// Maybe filter by post type and status
		if( ! is_null( $post_type ) && ! is_null( $pto = get_post_type_object( $post_type ) ) ) {

			$post_type_clause = "AND post_type = '$post_type' ";

			if( $include_links && $post_type == 'page' && isset( $bu_navigation_plugin ) ) {
				if ( $bu_navigation_plugin->supports( 'links' ) ) {
					$link_post_type = defined( 'BU_NAVIGATION_LINK_POST_TYPE' ) ? BU_NAVIGATION_LINK_POST_TYPE : 'bu_link';
					$post_type_clause = sprintf( "AND post_type IN ('page','%s') ", $link_post_type );
				}
			}

		}

		// Include unpublished should only work for hierarchical post types
		if( $include_unpublished ) {

			// Flat post types are not allowed to include unpublished, as perms can be set for drafts
			if( $post_type ) {

				$pto = get_post_type_object( $post_type );

				if( $pto->hierarchical ) {

					$post_status_clause = "OR (post_status IN ('draft','pending') $post_type_clause)";

				}

			} else {

				$post_status_clause = "OR post_status IN ('draft','pending')";

			}

		}

		$count_query = sprintf( "SELECT DISTINCT( ID ) FROM %s, %s WHERE ID = post_ID AND ( meta_key = '%s' AND meta_value IN (%s) %s) %s",
			$wpdb->posts,
			$wpdb->postmeta,
			BU_Group_Permissions::META_KEY,
			implode( ',', $group_ids ),
			$post_type_clause,
			$post_status_clause
			);

		// Execute query
		$ids = $wpdb->get_col( $count_query );

		// Count and return results
		return count( $ids );

	}

	// ____________________PERSISTENCE________________________

	/**
	 * Load all groups
	 */
	public function load() {

		$args = array(
			'post_type'=>self::POST_TYPE_NAME,
			'numberposts'=>-1,
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
	 * Save all groups
	 *
	 * @todo refactor so that both insert and update groups utilize this method
	 * @todo test coverage
	 */
	public function save() {

		$result = true;

		foreach( $this->groups as $group ) {

			$postdata = $this->_group_to_post( $group );

			// Update DB
			$result = wp_insert_post( $postdata );

			// Set group ID with post ID if needed
			if( $group->id < 0 )
				$group->id = $result;

			if( is_wp_error( $result ) ) {
				error_log(sprintf('Error updating group %s: %s', $group->id, $result->get_error_message()));
				$result = false;
			}

			// Update group member meta
			update_post_meta( $group->id, self::MEMBER_KEY, $group->users );

		}

		return $result;
	}

	/**
	 * Insert a new group
	 *
	 * @todo test coverage
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
	 * @todo test coverage
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

		// Update modified time stamp
		$group->modified = get_post_modified_time('U',false,$result);

		// Update group member meta
		update_post_meta( $group->id, self::MEMBER_KEY, $group->users );

		// Update internal groups store
		foreach( $this->groups as $i => $g ) {

			if( $g->id == $group->id )
				$this->groups[$i] = $group;

		}

		return $group;

	}

	/**
	 * Sanitizes array of group data prior to group creation or updating
	 */
	protected function _clean_group_data( &$args ) {

		// Process input
		$args['name'] = sanitize_text_field( stripslashes( $args['name'] ) );
		$args['description'] = isset($args['description']) ? sanitize_text_field( stripslashes( $args['description'] ) ) : '';
		$args['users'] = isset($args['users']) ? array_map( 'absint', $args['users'] ) : array();

		if( isset($args['perms']) && is_array($args['perms'])) {

			foreach( $args['perms'] as $post_type => $ids_by_status ) {

				if( ! is_array( $ids_by_status ) ) {

					error_log("Unepected value for permissions data: $ids_by_status" );
					unset( $args['perms'][$post_type]);
					continue;
				}

				if( !isset( $ids_by_status['allowed'] ) ) $args['perms'][$post_type]['allowed'] = array();
				if( !isset( $ids_by_status['denied'] ) ) $args['perms'][$post_type]['denied'] = array();

				foreach( $ids_by_status as $status => $post_ids ) {

					if( ! in_array( $status, array( 'allowed', 'denied', '' ) ) ) {
						error_log("Unexpected status: $status" );
						unset( $args['perms'][$post_type][$status] );
					}

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
		$data['created'] = strtotime($post->post_date);
		$data['modified'] = strtotime($post->post_modified);

		// Users are stored in post meta
		$users = get_post_meta( $post->ID, self::MEMBER_KEY, true );
		$data['users'] = $users ? $users : array();

		// Create a new group
		$group = new BU_Edit_Group( $data );

		return $group;

	}

}

/**
 * Class for listing groups (designed to be extended)
 *
 * @todo rework to use standard array traversal function and allow for keyed arrays
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

	const MAX_NAME_LENGTH = 60;

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
	 * @todo test coverage
	 *
	 * @return bool true if user exists, false otherwise
	 */
	public function has_user($user_id) {
		return in_array($user_id, $this->users);
	}

	/**
	 * Add a new user to this group
	 *
	 * @todo test coverage
	 *
	 * @param int $user_id WordPress user ID to add for this group
	 */
	public function add_user($user_id) {

		// need to make sure the user is a member of the site
		if(!$this->has_user($user_id)) {
			array_push($this->users, $user_id);
		}

	}

	/**
	 * Remove a user from this group
	 *
	 * @todo test coverage
	 *
	 * @param int $user_id WordPress user ID to remove from this group
	 */
	public function remove_user($user_id) {

		if($this->has_user($user_id)) {
			unset($this->users[array_search($user_id, $this->users)]);
		}

	}

	/**
	 * Update data fields for this group
	 *
	 * @param array $args an array of key => value parameters to update
	 */
	public function update($args = array()) {

		$valid_fields = array_keys( $this->get_attributes() );

		foreach( $args as $key => $val ) {
			if( in_array($key, $valid_fields)) {
				$this->$key = $val;
			}
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
