<?php
/**
 * Section editor group controller
 * 
 * @todo store groups as posts in a custom post type.
 */
class BU_Edit_Groups {

	public $option_name = '_bu_section_groups';
	public $groups = array();

	static protected $instance;

	protected function __construct() {
		$this->load();
	}

	static public function get_instance() {
		if(!isset(BU_Edit_Groups::$instance)) {
			BU_Edit_Groups::$instance = new BU_Edit_Groups();
		}
		return BU_Edit_Groups::$instance;
	}

//___________________PUBLIC_INTERFACE_________________________


	public function add_group($args) {
		$args['name'] = strip_tags(trim($args['name']));
		$args['description'] = strip_tags(trim($args['description']));

		$group = new BU_Edit_Group($args);
		$this->add($group);
	}

	public function update_group($id, $args = array()) {
		$group = $this->get($id);
		if($group) {
			$group->update($args);
		}
	}

	public function delete_group($id) {
		if($this->get($id)) {
			unset($this->groups[$id]);
		}
	}

	public function find_user($user_id) {
		$groups = array();
		foreach ($this->groups as $group) {
			if($group->has_user($user_id)) {
				array_push($groups, $group);
			}
		}
		return $groups;
	}

	public function delete_user($user_id) {
		$groups = $this->find_user($user_id);
		foreach($groups as $group) {
			$group->remove_user($user_id);
		}
	}

	public function has_user($groups, $user_id) {

			foreach($groups as $group_id) {
				$group = $this->get($group_id);
				if($group->has_user($user_id)) {
					return true;
				}
			}

			return false;
	}

//___________________HELPERS________________________

	private function load() {
		$groups = get_option($this->option_name);
		if(is_array($groups)) $this->groups = $groups;
	}

	private function add(BU_Edit_Group $group) {
		array_push($this->groups, $group);
	}

	private function get($id) {
		if(isset($this->groups[$id])) {
			return $this->groups[$id];
		}
	}

	private function update() {
		update_option($this->option_name, $this->groups);
	}

	private function delete() {
		delete_option($this->option_name);
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
		return $this->edit_groups->get($this->current_group);
	}

	function rewind() {
		$this->current_group = -1;
	}

}

/**
 * A Section Editing group model
 */ 
class BU_Edit_Group {

	public $name = null;
	public $description = null;
	public $users = array();

	function __construct($args = array()) {
		$defaults = $this->defaults();

		$args = wp_parse_args( $args, $defaults );

		$this->name = $args['name'];
		$this->description = $args['description'];

		if(isset($args['users']) && is_array($args['users'])) {
			foreach($args['users'] as $user) {
				$this->add_user($user);
			}
		}
	}

	private function defaults() {
		$fields = array(
			'name' => 'New group',
			'description' => 'My group description',
			'users' => array()
			);
		return apply_filters( 'bu_section_edit_group_fields', $fields );
	}

	public function has_user($user_id) {
		return in_array($user_id, $this->users);
	}

	public function add_user($user_id) {
		// need to make sure the user is a member of the site
		if(!$this->has_user($user_id)) {
			array_push($this->users, $user_id);
		}
	}

	public function remove_user($user_id) {
		if($this->have_user($user_id)) {
			unset($this->users[array_search($user_id, $this->users)]);
		}
	}

	public function update($args = array()) {
		$current = array(
			'name' => $this->name,
			'description' => $this->description,
			'users' => $this->users
		);

		$updated = array_merge($current, $args);

		$this->name = $updated['name'];
		$this->description = $updated['description'];
		$this->users = $updated['users'];
	}

	public function get_name() {
		return $this->name;
	}

	public function get_description() {
		return $this->description;
	}

	public function get_users() {
		return $this->users;
	}

	/**
	 * Unfinished.
	 */
	function get_posts() {
		$query = new WP_Query();
	}
}

?>