<?php

require_once( dirname(__FILE__) . '/includes/classes.group-factory.php' );

/**
 * Integration tests for BU_Edit_Groups controller class
 * 
 * @todo investigate using mock objects here
 * 
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

		$this->assertEquals( asort( array_keys($data['perms']['post']) ), asort( array_map( 'intval', $allowedposts ) ) );
		$this->assertEquals( asort( array_keys($data['perms']['page']) ), asort( array_map( 'intval', $allowedpages ) ) );

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

		$this->assertEquals( asort( array_keys($updates['perms']['post']) ), asort( array_map( 'intval', $allowedposts ) ) );
		$this->assertEquals( asort( array_keys($updates['perms']['page']) ), asort( array_map( 'intval', $allowedpages ) ) );

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

		$group_after = array_shift($gc->get_groups());

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
		$drafts = $this->factory->post->create_many(3, array('post_status'=>'draft'));
		$pages = $this->factory->post->create_many(3, array('post_type'=>'page'));

		$user_one = $this->factory->post->create();
		$user_two = $this->factory->post->create();

		$perms = array(
			'post' => array_combine( $posts, array('allowed','allowed','allowed')),
			);

		$group_one = $this->factory->group->create( array( 'perms' => $perms, 'users' => array( $user_one, $user_two ) ) );

		$perms = array(
			'page' => array_combine( $pages, array('allowed','allowed','allowed'))
			);

		$group_two = $this->factory->group->create( array( 'perms' => $perms, 'users' => array( $user_two ) ) );

		error_log('All posts: ' . print_r($posts,true) );
		error_log('All pages: ' . print_r($pages,true) );
		error_log('All drafts: ' . print_r($drafts,true) );
		// error_log('Group Two: ' . print_r($group_two,true) );
		// error_log('Group One: ' . print_r($group_one,true) );
		// error_log('Group Two: ' . print_r($group_two,true) );

		$gc = BU_Edit_Groups::get_instance();

		// Generate with different arguments
		$count_for_user_one = $gc->get_allowed_post_count( array('user_id' => $user_one ) );
		$count_for_user_two = $gc->get_allowed_post_count( array('user_id' => $user_two ) );
		$count_for_user_two_drafts_inc = $gc->get_allowed_post_count( array( 'user_id' => $user_two, 'include_unpublished' => true ) );

		$count_for_group_one = $gc->get_allowed_post_count( array('group' => $group_one->id ) );
		$count_for_group_two = $gc->get_allowed_post_count( array('group' => $group_two->id ) );

		$post_count_for_group_one = $gc->get_allowed_post_count( array('group' => $group_one->id, 'post_type' => 'post' ) );
		$page_count_for_group_one = $gc->get_allowed_post_count( array('group' => $group_one->id, 'post_type' => 'page'  ) );

		// Assertions
		$this->assertEquals( 3, $count_for_user_one );
		$this->assertEquals( 6, $count_for_user_two );
		$this->assertEquals( 9, $count_for_user_two_drafts_inc ); // THIS FAILS!
		$this->assertEquals( 3, $count_for_group_one );
		$this->assertEquals( 3, $count_for_group_two );
		$this->assertEquals( 3, $post_count_for_group_one );
		$this->assertEquals( 0, $page_count_for_group_one );

	}

	// ___ HELPERS ___

	function _generate_group_data( $args = array() ) {

		// Configure group
		$users = $this->factory->user->create_many(2,array('role'=>'section_editor'));
		$posts = $this->factory->post->create_many(2,array('post_type'=>'post'));
		$pages = $this->factory->post->create_many(2,array('post_type'=>'page'));
		$allowedposts = array_combine($posts,array('allowed','allowed'));
		$allowedpages = array_combine($pages,array('allowed','allowed'));

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