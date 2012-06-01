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
	 * Add a new section editing group
	 * 
	 * @param array $args an array of parameters for group initialization
	 * @return BU_Edit_Group the group that was just added
	 */ 
	public function add_group($args) {

		// sanitize
		$args['name'] = sanitize_text_field( stripslashes( $args['name'] ) );
		$args['users'] = isset($args['users']) ? array_map( 'absint', $args['users'] ) : array();

		// create group
		$group = new BU_Edit_Group($args);
		
		// update attributes
		$group->id = $this->index;
		$group->created = time();
		$group->modified = time();

		// add to internal array & increment group index counter
		$this->add($group);
		$this->increment_index();

		// extract permission updates
		$perms = array();

		foreach( $args['perms'] as $post_type => $json_data ) {
			if( $json_data )
				$perms[$post_type] = json_decode( stripslashes( $json_data ), true );
		}

		// Set group permissions
		$this->update_group_permissions( $group->id, $perms );

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

			// sanitize
			$args['name'] = sanitize_text_field( stripslashes( $args['name'] ) );
			$args['users'] = isset($args['users']) ? array_map( 'absint', $args['users'] ) : array();

			$group->update($args);

			$group->modified = time();

			// extract permission updates
			$perms = array();

			// Convert JSON string to array
			foreach( $args['perms'] as $post_type => $json_data ) {

				if( $json_data )
					$perms[$post_type] = json_decode( stripslashes( $json_data ), true );

			}


			// Set group permissions
			$this->update_group_permissions( $group->id, $perms );

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
				return true;
			
			}
		}

		return false;

	}

	/**
	 * Remove all groups from internal array 
	 */
	public function delete_groups() {

		$this->groups = array();
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

	// not currently used, possibly delete
	public function delete_user($user_id) {

		$groups = $this->find_groups_for_user($user_id);
		
		foreach($groups as $group) {
			$group->remove_user($user_id);
		}

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

	
	/**
	 * Add a BU_Edit_Group object to internal groups array
	 */ 
	private function add( BU_Edit_Group $group ) {

		array_push($this->groups, $group);

	}

	// ____________________PERSISTENCE________________________


	/**
	 * Update permissions for a group
	 * 
	 * @param int $group_id ID of group to modify ACL for
	 * @param array $permissions Permissions, as an associative array indexed by post type
	 */ 
	private function update_group_permissions( $group_id, $permissions ) {

		foreach( $permissions as $post_type => $perm_settings ) {

			// error_log('= Updating permissions for: ' . $post_type . '=' );

			foreach( $perm_settings as $id => $status ) {

				// Clear all possible existing values
				delete_post_meta( $id, BU_Edit_Group::META_KEY, $group_id );
				delete_post_meta( $id, BU_Edit_Group::META_KEY, $group_id . '-denied' );

				switch( $status ) {

					case 'allowed':
						add_post_meta( $id, BU_Edit_Group::META_KEY, $group_id );
						// error_log('Updating ' . $id . ': ' . $group_id );
						break;

					case 'denied':
						add_post_meta( $id, BU_Edit_Group::META_KEY, $group_id . '-' . $status );
						// error_log('Updating ' . $id . ': ' . $group_id . '-' . $status );
						break;


				}

			}

			// For debug ... gives us the full picture of data post update
			if( defined( 'WP_DEBUG' ) && WP_DEBUG ) {

				$meta_query = array(
					'key' => BU_Edit_Group::META_KEY,
					'value' => $group_id,
					'compare' => 'LIKE'
					);

				$args = array(
					'post_type' => $post_type,
					'meta_query' => array( $meta_query ),
					'posts_per_page' => -1
					);

				$query = new WP_Query( $args );
				// error_log('= Group ' . $group_id . ' Stats =' );

				while( $query->have_posts() ) {
					$query->the_post();
					$results = get_post_meta( get_the_ID(), BU_Edit_Group::META_KEY );
					$results = preg_grep( '/^' . $group_id . '/', $results );
					$status = array_pop( $results );
					
					if( $status == $group_id ) $status = 'Allowed';
					else if( $status == $group_id . '-denied' ) $status = 'Denied';
					else error_log('Unexpected status...');

					// error_log( 'Post: ' . get_the_title() . ' (' . get_the_ID() . ') => ' . $status );

				}
			}

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
	 * Can this group edit this post?
	 * 
	 * @todo not currently used, need to test
	 */
	public function permissions_for_post( $post_id ) {
		global $wpdb;

		$query = sprintf("SELECT post_id, meta_value FROM %s WHERE meta_key = '%s' AND post_id IN (%s) AND meta_value LIKE '%%%s%%'", $wpdb->postmeta, BU_Edit_Group::META_KEY, $post_id, $this->id );
		$group_meta = $wpdb->get_results($query, OBJECT_K); // get results as objects in an array keyed on post_id
		
		// error_log('Permissions for post meta results: ' . print_r( $group_meta, true ) );

		if (!is_array($group_meta)) $group_meta = array();

		if( empty( $group_meta ) ) {

			return 'inherit';

		} else {

			if( $group_meta[0]->meta_value == $this->id )
				return 'allowed';
			else
				return 'denied';
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
	public function get_posts_count( $args = array() ) {

		$defaults = array(
			'post_type' => 'page',
			'meta_key' => self::META_KEY,
			'meta_value' => $this->id,
			'posts_per_page' => -1,
			'fields' => 'ids'
			);

		$args = wp_parse_args( $args, $defaults );

		$allowed_query = new WP_Query( $args );

		$count = count( $allowed_query->posts );

		add_filter( 'bu_navigation_filter_pages', array(&$this, 'post_count_filter' ) );
		add_filter( 'bu_navigation_filter_fields', array(&$this, 'post_count_fields' ) );

		foreach( $allowed_query->posts as  $allowed_id ) {

			$sections = bu_navigation_gather_sections( $allowed_id, array( 'post_type' => $args['post_type'], 'direction' => 'down' ) );

			if( count( $sections ) > 0 ) {

				$page_args = array(
					'sections' => $sections,
					'post_types' => $args['post_type'],
					'suppress_urls' => true
					);


				$root_pages = bu_navigation_get_pages( $page_args );
				
				$count += count($root_pages);

			}

		}
		
		remove_filter( 'bu_navigation_filter_pages', array(&$this, 'post_count_filter' ) );
		remove_filter( 'bu_navigation_filter_fields', array(&$this, 'post_count_fields' ) );

		return $count;

	}

	/**
	 * @todo Fix this 
	 */ 
	public function post_count_filter( $posts ) {
		global $wpdb;

		/* Groups */
		$ids = array_keys($posts);
		$query = sprintf("SELECT post_id, meta_value FROM %s WHERE meta_key = '%s' AND post_id IN (%s) AND meta_value LIKE '%%%s%%'", $wpdb->postmeta, BU_Edit_Group::META_KEY, implode(',', $ids), $this->id );
		$group_meta = $wpdb->get_results($query, OBJECT_K); // get results as objects in an array keyed on post_id
		
		if (!is_array($group_meta)) $group_meta = array();

		//$pages_by_parent = bu_navigation_pages_by_parent( $posts );
		// Need to count recursively, stopping if a node with children has any status set

		foreach( $posts as $post ) {

			if( array_key_exists( $post->ID, $group_meta ) ) {

				unset( $posts[$post->ID] );

			}
		}

		return $posts;
	}

	public function post_count_fields( $fields ) {
		return array('ID','post_type', 'post_parent');
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