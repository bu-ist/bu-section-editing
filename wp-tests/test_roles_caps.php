<?php

class Test_BU_Section_Editing_Caps extends WP_UnitTestCase {

	function setUp() {
		parent::setUp();
		$pages = array(
			'top-level1' => array(),
			'top-level2' => array(
				'second-level1' => array(),
				'second-level2' => array(
					'third-level1' => array(),
					'third-level2' => array()
				)
			),
			'top-level3' => array()
		);
		$this->insertPages($pages);
		$this->addUser();
		$editor = get_user_by('login', 'section_editor');


		$perms = array();

		$parent = $this->pages['top-level2'];
		$perms['pages'] = array($parent->ID =>"allowed");

		$this->addGroup('test', array($editor->ID), $perms);

		$post = array(
			'post_author' => $editor->ID,
			'post_status' => 'draft',
			'post_title' => "section_editor-draft",
			'post_content' => "section editor content",
			'post_excerpt' => "section editor excerpt",
			'post_type' => 'page',
			'post_parent' => $parent->ID
		);

		$this->insertPage($post);

	}

	function tearDown() {
		parent::tearDown();
		$groups = BU_Edit_Groups::get_instance();
		foreach($this->pages as $page) {
			wp_delete_post($page->ID);
		}

		foreach($this->groups as $group) {
			$this->deleteGroup($group->ID);
		}
	}

	function test_edit() {

		$editor = get_user_by('login', 'section_editor');
		wp_set_current_user($editor->ID);

		$post_id = $this->pages['section_editor-draft']->ID;
		$this->assertTrue(current_user_can('edit_page', $post_id));
		$post_id = $this->pages['top-level1']->ID;
		$this->assertFalse(current_user_can('edit_page', $post_id));
	}

	function test_delete() {
		$editor = get_user_by('login', 'section_editor');
		wp_set_current_user($editor->ID);

		$post_id = $this->pages['section_editor-draft']->ID;
		$this->assertTrue(current_user_can('delete_page', $post_id));

		$post_id = $this->pages['top-level1']->ID;
		$this->assertFalse(current_user_can('delete_page', $post_id));

	}


	/**
	 * Testing the publish action is more complicated because there isn't a
	 * publish_post meta cap.
	 *
	 * @todo finish this bit
	 */
	function test_publish() {

		$editor = get_user_by('login', 'section_editor');
		wp_set_current_user($editor->ID);

		$this->assertFalse(current_user_can('publish_pages'));
		$this->assertFalse(current_user_can('publish_posts'));

		// now the fun begins....
	}



	function addUser($login = 'section_editor', $role = 'section_editor') {

		$userdata = array(
			'user_login' => $login,
			'user_email' => $login . '@example.org',
			'user_pass' => $login . 'password',
			'role' => $role
		);

		$this->users[] = wp_insert_user($userdata);
	}


	function addGroup($name, $user_ids = array(), $perms = array()) {
		$args = array();

		$groups = BU_Edit_Groups::get_instance();
		$args['perms'] = array();

		foreach($perms as $post_type => $acl) {
			$args['perms'][$post_type] = json_encode($acl);
		}

		$args['name'] = $name;
		$args['users'] = $user_ids;


		$this->groups[] = $groups->add_group($args);
		$groups->save();
	}

	function deleteGroup($id) {
		$groups = BU_Edit_Groups::get_instance();
		$groups->delete_group($id);
		if(isset($this->groups[$id])) {
			unset($this->groups[$id]);
		}
		$groups->save();
	}

	function insertPage($post) {
		$result = wp_insert_post($post);
		if(!is_wp_error($result)) {
			$page = get_post($result);
			$this->pages[$page->post_title] = $page;
		}
	}

	function insertPages($pages) {
		$author = get_user_by('login', 'admin');
		$this->walkAndInsert($pages, $author->ID);
	}

	function walkAndInsert($pages, $author_id, $parent = 0) {

		foreach($pages as $title => $child_pages) {
			$post = array(
				'post_author' => $author_id,
				'post_status' => 'publish',
				'post_title' => $title,
				'post_content' => "{$title} content",
				'post_excerpt' => "{$title} excerpt",
				'post_type' => 'page',
				'post_parent' => $parent
			);

			$result = wp_insert_post($post);

			if(!is_wp_error($result)) {
				$page = get_post($result);
				$this->pages[$page->post_title] = $page;
				if(!empty($child_pages)) {
					$this->walkAndInsert($child_pages, $author_id, $page->post_parent);
				}
			}

		}
	}



	function registerCustomPostType() {

	}
}


?>
