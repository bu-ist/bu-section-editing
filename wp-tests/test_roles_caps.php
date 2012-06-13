<?php

class Test_BU_Section_Editing extends WPTestCase {

	function setUp() {
		parent::setUp();
		$this->insertPages();
		$this->addUser();

	}

	function tearDown() {
		parent::tearDown();
	}


	function test_publish() {
		$editor = get_user_by('login', 'section_editor');

		$perms = new stdObj;
		$perms[8] = "allowed";

		$this->addGroup('test', array($editor->ID), $perms);
	}

	function test_edit() {

	}

	function test_delete() {

	}

	function test_publish_new() {

	}

	function addUser($login = 'section_editor', $role = 'section_editor') {

		$userdata = array(
			'user_login' => $login,
			'user_email' => $login . '@example.org',
			'user_pass' => $login . 'password',
			'role' => $role
		);

		wp_insert_user($userdata);
	}


	function addGroup($name, $user_ids = array(), $perms = array()) {

	}

	function insertPages() {
		$pages = array(
			'top-level1' => array(),
			'top-level2' => array(
				'second-level1' => array(),
				'second-level2', array(
					'third-level1' => array(),
					'third-level2' => array()
				)
			),
			'top-level3' => array()
		);
		$author = get_user_by('login', 'admin');
		$this->walkAndInsert($pages, $author->ID);
	}

	function walkAndInsert($pages, $author_id, $parent = 0) {

		foreach($pages as $title => $child_pages) {
			$post = array(
				'post_author' => $user_id,
				'post_status' => 'publish',
				'post_title' => "{$title} title",
				'post_content' => "{$title} content",
				'post_excerpt' => "{$title} excerpt",
				'post_type' => 'page'
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
