<?php

/**
 * Integration tests for BU_Edit_Groups controller class
 *
 * @todo investigate using mock objects here
 *
 * @group bu
 * @group bu-section-editing
 **/
class Test_BU_Edit_Groups extends WP_UnitTestCase {


	function setUp() {
		parent::setUp();
		$this->factory->group = new WP_UnitTest_Factory_For_Group( $this->factory );
	}

	function tearDown() {
		parent::tearDown();

		// While the DB will rollback, the BU_Edit_Groups persists in memory
		// and there for the groups remain cached in its internal array
		// The delete_groups() method clears them, placed here so that it
		// happens automatically after each test case is run
		$gc = BU_Edit_Groups::get_instance();
		$gc->delete_groups();

	}

	/**
	 * Test group controller instance creation
	 */
	function test_get_instance() {

		$gc = BU_Edit_Groups::get_instance();

		$this->assertTrue( is_object( $gc ) );
		$this->assertTrue( $gc instanceof BU_Edit_Groups );

	}

	/**
	 * Test adding of group
	 */
	function test_add_and_get_group() {

		$data = $this->_generate_group_data();

		$gc = BU_Edit_Groups::get_instance();
		$groups_before = $gc->get_groups();

		// Add
		$group = $gc->add_group( $data );

		$groups_after = $gc->get_groups();

		// Internally cached group
		$this->assertNotEquals( $groups_before, $groups_after );
		$this->assertContains( $group, $gc->get_groups() );

		// Group assertions
		$this->assertInstanceOf( 'BU_Edit_Group', $group );
		$this->assertGreaterThan( 0, $group->id);

		// Properties
		$this->assertEquals( $data['name'], $group->name );
		$this->assertEquals( $data['description'], $group->description );
		$this->assertEquals( $data['users'], $group->users );

		// Time stamps
		$this->assertNotNull( $group->created );
		$this->assertNotNull( $group->modified );

		// Permissions
		$allowedposts = BU_Group_Permissions::get_allowed_posts_for_group( $group->id, array('post_type' => 'post', 'fields' => 'ids' ));
		$allowedpages = BU_Group_Permissions::get_allowed_posts_for_group( $group->id, array('post_type' => 'page', 'fields' => 'ids' ));

		$expected_allowed_posts = array_keys($data['perms']['post']);
		$expected_allowed_pages = array_keys($data['perms']['page']);
		$actual_allowed_posts = array_map( 'intval', $allowedposts );
		$actual_allowed_pages = array_map( 'intval', $allowedpages );
		$this->assertEquals( asort( $expected_allowed_posts ), asort( $actual_allowed_posts ) );
		$this->assertEquals( asort( $expected_allowed_pages ), asort( $actual_allowed_pages ) );

	}

	/**
	 * Test updating of group
	 */
	function test_update_and_get_group() {

		// Objects are passed by reference, so clone to maintain original data
		$original = clone $this->factory->group->create();
		$updates = $this->_generate_group_data();

		// Give it a second so that modified time stamp is different
		sleep(1);

		$gc = BU_Edit_Groups::get_instance();
		$group = $gc->update_group( $original->id, $updates );

		// Group assertions
		$this->assertInstanceOf( 'BU_Edit_Group', $group );
		$this->assertSame( $original->id, $group->id);

		// Properties
		$this->assertEquals( $updates['name'], $group->name );
		$this->assertEquals( $updates['description'], $group->description );
		$this->assertEquals( $updates['users'], $group->users );

		// Time stamps
		$this->assertEquals( $original->created, $group->created );
		$this->assertNotEquals( $original->modified, $group->modified );

		// Permissions
		$allowedposts = BU_Group_Permissions::get_allowed_posts_for_group( $group->id, array('post_type' => 'post', 'fields' => 'ids' ));
		$allowedpages = BU_Group_Permissions::get_allowed_posts_for_group( $group->id, array('post_type' => 'page', 'fields' => 'ids' ));

		$expected_allowed_posts = array_keys($updates['perms']['post']);
		$expected_allowed_pages = array_keys($updates['perms']['page']);
		$actual_allowed_posts = array_map( 'intval', $allowedposts );
		$actual_allowed_pages = array_map( 'intval', $allowedpages );
		$this->assertEquals( asort( $expected_allowed_posts ), asort( $actual_allowed_posts ) );
		$this->assertEquals( asort( $expected_allowed_pages ), asort( $actual_allowed_pages ) );

	}

	/**
	 * Test loading of group
	 */
	function test_load_group() {

		$gc = BU_Edit_Groups::get_instance();

		$this->assertEmpty( $gc->get_groups() );

		// Add test group
		$group = $this->factory->group->create();
		$this->assertCount( 1, $gc->get_groups() );
		$this->assertEquals( $group, $gc->get( $group->id ) );

		// Remove all groups from internal array
		$gc->delete_groups();
		$this->assertEmpty( $gc->get_groups() );

		// Re-load from DB
		$gc->load();
		$this->assertCount( 1, $gc->get_groups());
		$this->assertEquals( $group, $gc->get( $group->id ) );

	}

	/**
	 * Test saving of group
	 */
	function test_save_group() {

		// Create group object
		$data = $this->_generate_group_data();
		$group = new BU_Edit_Group( $data );

		$gc = BU_Edit_Groups::get_instance();
		$this->assertEmpty( $gc->get_groups() );

		// Add to groups array
		$gc->add( $group );
		$this->assertEquals( $group, $gc->get( $group->id ) );

		// Commit to DB
		$gc->save();
		$gc->delete_groups();
		$this->assertEmpty( $gc->get_groups() );

		// Reload for testing
		$gc->load();
		$this->assertNotEmpty( $gc->get_groups() );

		$groups = $gc->get_groups();
		$group_after = array_shift($groups);

		$this->assertEquals( $group->name, $group_after->name );
		$this->assertEquals( $group->description, $group_after->description );
		$this->assertEquals( $group->users, $group_after->users );

	}

	/**
	 * Test deletion of group
	 */
	function test_delete_group() {

		// Create group object
		$groups = $this->factory->group->create_many(2);

		// Fetch using group controller
		$gc = BU_Edit_Groups::get_instance();
		$groups_before = $gc->get_groups();

		// Confirm initial state
		$this->assertContains( $groups[0], $gc->get_groups() );
		$this->assertContains( $groups[1], $gc->get_groups() );

		// Delete using API
		$gc->delete_group( $groups[0]->id );

		$groups_after = $gc->get_groups();

		// Internally cached group
		$this->assertNotEquals( $groups_before, $groups_after );
		$this->assertNotContains( $groups[0], $gc->get_groups() );
		$this->assertContains( $groups[1], $gc->get_groups() );

		// Clear groups array from memory
		$gc->delete_groups();

		// Confirm removal from DB by forcing reload
		$gc->load();

		$groups_reloaded = $gc->get_groups();

		$this->assertNotEquals( $groups_before, $groups_reloaded );
		$this->assertEquals( $groups_after, $groups_reloaded );

	}

	/**
	 * Assert that find groups for user returns the correct results
	 */
	function test_find_groups_for_user() {

		$user = $this->factory->user->create();
		$groups_user_in = $this->factory->group->create_many( 3, array( 'users' => array( $user ) ) );
		$groups_user_not_in = $this->factory->group->create_many( 3 );

		$gc = BU_Edit_Groups::get_instance();

		$found_groups = $gc->find_groups_for_user( $user );

		$this->assertCount( count($groups_user_in), $found_groups );

		foreach( $found_groups as $group ) {

			$this->assertContains( $group, $groups_user_in );
			$this->assertNotContains( $group, $groups_user_not_in );

		}

	}

	/**
	 * Test if a user exists in an array of groups
	 */
	function test_has_user() {

		$user_in = $this->factory->user->create();
		$user_not_in = $this->factory->user->create();
		$group = $this->factory->group->create( array( 'users' => array( $user_in ) ) );

		$gc = BU_Edit_Groups::get_instance();

		$this->assertTrue( $gc->has_user( array( $group->id ), $user_in ) );
		$this->assertFalse( $gc->has_user( array( $group->id ), $user_not_in ) );

	}

	/**
	 * @todo
	 */
	function test_get_allowed_post_count() {

		// Configure state
		$posts = $this->factory->post->create_many(3);
		$drafts = $this->factory->post->create_many(3, array('post_type' => 'page', 'post_status'=>'draft'));
		$pages = $this->factory->post->create_many(3, array('post_type'=>'page'));
		$denied_pages = $this->factory->post->create_many(3);

		$u1 = $this->factory->post->create( array('role' => 'section_editor') );
		$u2 = $this->factory->post->create( array('role' => 'section_editor') );
		$u3 = $this->factory->post->create();

		// Group one - 3 allowed posts, 0 allowed pages (3 draft pages)
		$g1_perms = array(
			'post' => array( 'allowed' => $posts )
			);

		$g1 = $this->factory->group->create( array( 'perms' => $g1_perms, 'users' => array( $u1, $u2 ) ) );

		// Group two - 0 allowed posts, 3 allowed pages (3 draft pages)
		$g2_perms = array(
			'page' => array( 'allowed' => $pages )
			);

		$g2 = $this->factory->group->create( array( 'perms' => $g2_perms, 'users' => array( $u2 ) ) );

		$gc = BU_Edit_Groups::get_instance();

		// Generate counts with all possible combinations of args

		// User one
		$count_all_u1 = $gc->get_allowed_post_count( array('user_id' => $u1 ) );
		$count_post_u1 = $gc->get_allowed_post_count( array('user_id' => $u1, 'post_type' => 'post' ) );
		$count_page_u1 = $gc->get_allowed_post_count( array('user_id' => $u1, 'post_type' => 'page' ) );
		$count_post_drafts_inc_u1 = $gc->get_allowed_post_count( array('user_id' => $u1, 'post_type' => 'post', 'include_unpublished' => true  ) );
		$count_page_drafts_inc_u1 = $gc->get_allowed_post_count( array('user_id' => $u1, 'post_type' => 'page', 'include_unpublished' => true  ) );
		$count_all_drafts_inc_u1 = $gc->get_allowed_post_count( array( 'user_id' => $u1, 'include_unpublished' => true ) );

		// User two
		$count_all_u2 = $gc->get_allowed_post_count( array('user_id' => $u2 ) );
		$count_post_u2 = $gc->get_allowed_post_count( array('user_id' => $u2, 'post_type' => 'post' ) );
		$count_page_u2 = $gc->get_allowed_post_count( array('user_id' => $u2, 'post_type' => 'page' ) );
		$count_post_drafts_inc_u2 = $gc->get_allowed_post_count( array('user_id' => $u2, 'post_type' => 'post', 'include_unpublished' => true  ) );
		$count_page_drafts_inc_u2 = $gc->get_allowed_post_count( array('user_id' => $u2, 'post_type' => 'page', 'include_unpublished' => true  ) );
		$count_all_drafts_inc_u2 = $gc->get_allowed_post_count( array( 'user_id' => $u2, 'include_unpublished' => true ) );

		// User 3 (no permissions)
		$count_for_user_three = $gc->get_allowed_post_count( array('user_id' => $u3 ) );

		// Group 1
		$count_all_g1 = $gc->get_allowed_post_count( array( 'group' => $g1->id ) );
		$count_post_g1 = $gc->get_allowed_post_count( array( 'group' => $g1->id, 'post_type' => 'post' ) );
		$count_page_g1 = $gc->get_allowed_post_count( array( 'group' => $g1->id, 'post_type' => 'page'  ) );
		$count_post_draft_inc_g1 = $gc->get_allowed_post_count( array( 'group' => $g1->id, 'post_type' => 'post', 'include_unpublished' => true ) );
		$count_page_draft_inc_g1 = $gc->get_allowed_post_count( array( 'group' => $g1->id, 'post_type' => 'page', 'include_unpublished' => true ) );
		$count_all_draft_inc_g1 = $gc->get_allowed_post_count( array( 'group' => $g1->id, 'include_unpublished' => true ) );

		$count_all_g2 = $gc->get_allowed_post_count( array( 'group' => $g2->id ) );
		$count_post_g2 = $gc->get_allowed_post_count( array( 'group' => $g2->id, 'post_type' => 'post' ) );
		$count_page_g2 = $gc->get_allowed_post_count( array( 'group' => $g2->id, 'post_type' => 'page'  ) );
		$count_post_draft_inc_g2 = $gc->get_allowed_post_count( array( 'group' => $g2->id, 'post_type' => 'post', 'include_unpublished' => true ) );
		$count_page_draft_inc_g2 = $gc->get_allowed_post_count( array( 'group' => $g2->id, 'post_type' => 'page', 'include_unpublished' => true ) );
		$count_all_draft_inc_g2 = $gc->get_allowed_post_count( array( 'group' => $g2->id, 'include_unpublished' => true ) );

		// Invalid args
		$invalid_count_one = $gc->get_allowed_post_count( array( 'post_type' => 'page' ) );
		$invalid_count_two = $gc->get_allowed_post_count( array( 'post_type' => 'page', 'include_unpublished' => true ) );
		$invalid_count_three = $gc->get_allowed_post_count( array( 'user_id' => -1 ) );
		$invalid_count_four = $gc->get_allowed_post_count( array( 'group' => -1 ) );

		// Assertions

		// User One
		$this->assertEquals( 3, $count_all_u1 );
		$this->assertEquals( 3, $count_post_u1 );
		$this->assertEquals( 0, $count_page_u1 );
		$this->assertEquals( 3, $count_post_drafts_inc_u1 );
		$this->assertEquals( 3, $count_page_drafts_inc_u1 );
		$this->assertEquals( 6, $count_all_drafts_inc_u1 );

		// User two
		$this->assertEquals( 6, $count_all_u2 );
		$this->assertEquals( 3, $count_post_u2 );
		$this->assertEquals( 3, $count_page_u2 );
		$this->assertEquals( 3, $count_post_drafts_inc_u2 );
		$this->assertEquals( 6, $count_page_drafts_inc_u2 );
		$this->assertEquals( 9, $count_all_drafts_inc_u2 );

		// User three
		$this->assertEquals( 0, $count_for_user_three );

		// Group 1
		$this->assertEquals( 3, $count_all_g1 );
		$this->assertEquals( 3, $count_post_g1 );
		$this->assertEquals( 0, $count_page_g1 );
		$this->assertEquals( 3, $count_post_draft_inc_g1 );
		$this->assertEquals( 3, $count_page_draft_inc_g1 );
		$this->assertEquals( 6, $count_all_draft_inc_g1 );

		// Group 2
		$this->assertEquals( 3, $count_all_g2 );
		$this->assertEquals( 0, $count_post_g2 );
		$this->assertEquals( 3, $count_page_g2 );
		$this->assertEquals( 0, $count_post_draft_inc_g2 );
		$this->assertEquals( 6, $count_page_draft_inc_g2 );
		$this->assertEquals( 6, $count_all_draft_inc_g2 );

		// Invalid args
		$this->assertFalse( $invalid_count_one );
		$this->assertFalse( $invalid_count_two );
		$this->assertFalse( $invalid_count_three );
		$this->assertFalse( $invalid_count_four );


	}

	// ___ HELPERS ___

	function _generate_group_data( $args = array() ) {

		// Configure group
		$users = $this->factory->user->create_many(2,array('role'=>'section_editor'));
		$posts = $this->factory->post->create_many(2,array('post_type'=>'post'));
		$pages = $this->factory->post->create_many(2,array('post_type'=>'page'));
		$allowedposts = array( 'allowed' => $posts );
		$allowedpages = array( 'allowed' => $pages );

		$defaults = array(
			'name' => 'Test group',
			'description' => 'Test description',
			'users' => $users,
			'perms' => array(
				'post' => $allowedposts,
				'page' => $allowedpages
				)
			);

		$data = wp_parse_args( $args, $defaults );

		return $data;

	}

}

?>