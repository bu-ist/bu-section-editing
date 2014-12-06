<?php

/**
 * @group bu
 * @group bu-section-editing
 **/
class Test_BU_Section_Editing_Caps extends WP_UnitTestCase {

	function setUp() {
		parent::setUp();

		// Ensure that the section editor role exists
		// TODO: This shouldn't be this hard
		require_once __DIR__ . '/../../classes.upgrade.php';
		$upgrader = new BU_Section_Editing_Upgrader();
		$upgrader->populate_roles();

		$this->groups = array();
		$pages = array(
			'top-level1' => array(
				'status' => 'publish'
			),
			'top-level2' => array(
				'groups' => array('alpha'),
				'status' => 'publish',
				'children' => array(
					'second-level1' => array(
						'status' => 'publish'
					),
					'second-level2' => array(
						'status' => 'publish',
						'children' => array(
							'third-level1' => array(
								'status' => 'publish',
								'groups' => array('alpha', 'beta')
							),
							'third-level2' => array(
								'status' => 'publish'
							)
						)
					),
					'section_editor-draft' => array(
						'status' => 'draft',
						'author' => 'section_editor1'
					)
				)
			),
			'top-level3' => array(
				'status' => 'publish'
			)
		);

		$posts = array(
			'draft1' => array(
				'status' => 'draft',
				'groups' => array('alpha')
			),
			'publish1' => array(
				'status' => 'publish',
				'groups' => array('alpha')
			),
			'nogroups-draft' => array(
				'status' => 'draft',
				'author' => 'section_editor2'
			),
			'nogroups-published' => array(
				'status' => 'publish',
				'author' => 'section_editor2'
			)

		);
		$this->addUser('section_editor1');
		$this->addUser('section_editor2');
		$section_editor1 = get_user_by('login', 'section_editor1');
		$section_editor2 = get_user_by('login', 'section_editor2');
		$this->insertPosts($pages, 'page');
		$this->insertPosts($posts, 'post');
		$perms = $this->getEditable('alpha');
		$this->addGroup('alpha', array($section_editor1->ID), $perms);
		$perms = $this->getEditable('beta');
		$this->addGroup('beta', array($section_editor2->ID), $perms);
	}

	function tearDown() {
		parent::tearDown();
		$groups = BU_Edit_Groups::get_instance();
		foreach($this->groups as $group) {
			$this->deleteGroup($group->id);
		}
	}

	function test_edit() {

		$editor = get_user_by('login', 'section_editor1');
		wp_set_current_user($editor->ID);

		$post_id = $this->pages['section_editor-draft']->ID;
		$this->assertTrue(current_user_can('edit_page', $post_id));
		$post_id = $this->pages['top-level1']->ID;
		$this->assertFalse(current_user_can('edit_page', $post_id));

		$post_id = $this->posts['draft1']->ID;
		$this->assertTrue(current_user_can('edit_post', $post_id));

		$post_id = $this->posts['publish1']->ID;
		$this->assertTrue(current_user_can('edit_post', $post_id));

		$post_id = $this->posts['nogroups-draft']->ID;
		$this->assertTrue(current_user_can('edit_post', $post_id));

		$post_id = $this->posts['nogroups-published']->ID;
		$this->assertFalse(current_user_can('edit_post', $post_id));
	}

	function test_delete() {
		$editor = get_user_by('login', 'section_editor1');
		wp_set_current_user($editor->ID);

		$post_id = $this->pages['section_editor-draft']->ID;
		$this->assertTrue(current_user_can('delete_page', $post_id));

		$post_id = $this->pages['top-level1']->ID;
		$this->assertFalse(current_user_can('delete_page', $post_id));

		$post_id = $this->posts['draft1']->ID;
		$this->assertTrue(current_user_can('delete_post', $post_id));
		$post_id = $this->posts['publish1']->ID;
		$this->assertTrue(current_user_can('delete_post', $post_id));

		$post_id = $this->posts['nogroups-draft']->ID;
		$this->assertFalse(current_user_can('delete_post', $post_id));

		$post_id = $this->posts['nogroups-published']->ID;
		$this->assertFalse(current_user_can('delete_post', $post_id));

	}


	/**
	 * Testing the publish action is more complicated because there isn't a
	 * publish_post meta cap.
	 *
	 */
	function test_publish() {

		$editor = get_user_by('login', 'section_editor1');
		wp_set_current_user($editor->ID);

		$this->assertFalse(current_user_can('publish_pages'));
		$this->assertFalse(current_user_can('publish_posts'));

		// now the fun begins....

		$post_id = $this->pages['section_editor-draft']->ID;
		$GLOBALS['post_ID'] = $post_id;
		$this->assertTrue(current_user_can('publish_pages'));

		$_POST['post_ID'] = $post_id;
		$_POST['parent_id'] = 0;
		$this->assertFalse(current_user_can('publish_pages'));

		$post_id = $this->posts['draft1']->ID;
		$GLOBALS['post_ID'] = $post_id;
		$this->assertTrue(current_user_can('publish_posts'));

		$post_id = $this->posts['publish1']->ID;
		$GLOBALS['post_ID'] = $post_id;
		$this->assertTrue(current_user_can('publish_posts'));

		$post_id = $this->posts['nogroups-draft']->ID;
		$GLOBALS['post_ID'] = $post_id;
		$this->assertFalse(current_user_can('publish_posts'));

		$post_id = $this->posts['nogroups-published']->ID;
		$GLOBALS['post_ID'] = $post_id;
		$this->assertFalse(current_user_can('publish_posts'));

		unset($_POST['parent_id']);
		unset($_POST['post_ID']);

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
		$args['perms'] = $perms;
		$args['name'] = $name;
		$args['users'] = $user_ids;
		$args['description'] = 'lorem ipsum.';
		$this->groups[] = $groups->add_group($args);
	}

	function getEditable($group_name) {
		$perms = array();

		if(is_array($this->pages)) {
			$perms['page']['allowed'] = array();
			foreach($this->pages as $page) {
				if(isset($page->groups) && in_array($group_name, $page->groups)) {
					$perms['page']['allowed'][] = $page->ID;
				}
			}
		}

		if(is_array($this->posts)) {
			$perms['post']['allowed'] = array();
			foreach($this->posts as $post) {
				if(isset($post->groups) && in_array($group_name, $post->groups)) {
					$perms['post']['allowed'][] = $post->ID;
				}
			}
		}
		return $perms;
	}

	function deleteGroup($id) {
		$groups = BU_Edit_Groups::get_instance();
		$groups->delete_group($id);
		if(isset($this->groups[$id])) {
			unset($this->groups[$id]);
		}
	}

	function insertPage($post, $groups = null) {
		$result = wp_insert_post($post);
		if(!is_wp_error($result)) {
			$page = get_post($result);
			if(isset($groups))  {
				$page->groups = $groups;
			}
			$this->pages[$page->post_title] = $page;
		}

	}

	function insertPosts($pages, $post_type = 'page', $parent = 0) {

		foreach($pages as $title => $properties) {
			if(!isset($properties['status'])) {
				$status = 'publish';
			} else {
				$status = $properties['status'];
			}

			if(!isset($properties['author'])) {
				$author = get_user_by('login', 'admin');
			} else {
				$author = get_user_by('login', $properties['author']);
			}

			$post = array(
				'post_author' => $author->ID,
				'post_status' => $status,
				'post_title' => $title,
				'post_content' => "{$title} content",
				'post_excerpt' => "{$title} excerpt",
				'post_type' => $post_type,
				'post_parent' => $parent

			);

			$result = wp_insert_post($post);

			if(!is_wp_error($result)) {
				$post = get_post($result);
				if(isset($properties['groups'])) {
					$post->groups = $properties['groups'];
				}

				$this->{$post_type . "s"}[$post->post_title] = $post;
				if(!empty($properties['children'])) {
					$this->insertPosts($properties['children'], $post_type, $post->ID);
				}
			}

		}
	}



	function registerCustomPostType() {

	}
}


?>
