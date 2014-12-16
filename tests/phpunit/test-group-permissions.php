<?php

/**
 * Integration tests for group permissions operations
 *
 * @group bu
 * @group bu-section-editing
 **/
class Test_BU_Group_Permissions extends WP_UnitTestCase {

	function setUp() {
		parent::setUp();
		$this->factory->group = new WP_UnitTest_Factory_For_Group( $this->factory );
	}

	/**
	 */
	function test_get_and_update_group_permissions() {

		$group = $this->factory->group->create(array('name'=>__FUNCTION__));
		$posts = $this->factory->post->create_many(3, array('post_type' => 'post'));
		$pages = $this->factory->post->create_many(5, array('post_type' => 'page'));

		$allowedposts = BU_Group_Permissions::get_allowed_posts_for_group( $group->id, array('post_type' => 'post'));
		$allowedpages = BU_Group_Permissions::get_allowed_posts_for_group( $group->id, array('post_type' => 'page'));

		$this->assertTrue( empty( $allowedposts ) );
		$this->assertTrue( empty( $allowedpages ) );

		$perms = array(
			'post' => array( 'allowed' => $posts ),
			'page' => array( 'allowed' => $pages )
			);

		BU_Group_Permissions::update_group_permissions( $group->id, $perms );

		$allowedposts = BU_Group_Permissions::get_allowed_posts_for_group( $group->id, array('post_type' => 'post', 'fields' => 'ids' ));
		$allowedpages = BU_Group_Permissions::get_allowed_posts_for_group( $group->id, array('post_type' => 'page', 'fields' => 'ids' ));
		$allowedposts = array_map( 'intval', $allowedposts );
		$allowedpages = array_map( 'intval', $allowedpages );
		$this->assertEquals( asort( $posts ), asort( $allowedposts ) );
		$this->assertEquals( asort( $pages ), asort( $allowedpages ) );

	}

	/**
	 */
	function test_delete_group_permissions() {

		$posts = $this->factory->post->create_many(2, array('post_type' => 'post'));
		$pages = $this->factory->post->create_many(2, array('post_type' => 'page'));

		$perms = array(
			'post' => array( 'allowed' => $posts ),
			'page' => array( 'allowed' => $pages )
			);

		$group = $this->factory->group->create( array('name'=>__FUNCTION__, 'perms' => $perms ) );

		$allowedposts = BU_Group_Permissions::get_allowed_posts_for_group( $group->id, array('post_type' => 'post', 'fields' => 'ids' ));
		$allowedpages = BU_Group_Permissions::get_allowed_posts_for_group( $group->id, array('post_type' => 'page', 'fields' => 'ids' ));
		$allowedposts = array_map( 'intval', $allowedposts );
		$allowedpages = array_map( 'intval', $allowedpages );
		$this->assertEquals( asort( $posts ), asort( $allowedposts ) );
		$this->assertEquals( asort( $pages ), asort( $allowedpages ) );

		BU_Group_Permissions::delete_group_permissions( $group->id );

		$allowedposts = BU_Group_Permissions::get_allowed_posts_for_group( $group->id, array('post_type' => 'post'));
		$allowedpages = BU_Group_Permissions::get_allowed_posts_for_group( $group->id, array('post_type' => 'page'));

		$this->assertTrue( empty( $allowedposts ) );
		$this->assertTrue( empty( $allowedpages ) );

	}

	/**
	 */
	function test_group_can_edit() {

		$posts = $this->factory->post->create_many(2, array('post_type' => 'post'));
		$pages = $this->factory->post->create_many(2, array('post_type' => 'page'));

		$perms = array(
			'post' => array( 'allowed' => array( $posts[0] ) ),
			'page' => array( 'allowed' => array( $pages[1] ) )
			);

		$group = $this->factory->group->create( array( 'name'=>__FUNCTION__, 'perms' => $perms ) );

		$this->assertTrue( BU_Group_Permissions::group_can_edit( $group->id, reset($posts) ));
		$this->assertFalse( BU_Group_Permissions::group_can_edit( $group->id, next($posts) ));
		$this->assertFalse( BU_Group_Permissions::group_can_edit( $group->id, reset($pages) ));
		$this->assertTrue( BU_Group_Permissions::group_can_edit( $group->id, next($pages) ));

	}

	/**
	 * Coverage for group meta inheritance on post save
	 */
	function test_transition_post_status_inheritance() {
		$allowed = $this->factory->post->create(array('post_type'=>'page'));
		$perms = array( 'page' => array( 'allowed' => array( $allowed ) ) );
		$group = $this->factory->group->create( array( 'name' => __FUNCTION__, 'perms' => $perms ) );

		add_action( 'transition_post_status', array( 'BU_Groups_Admin', 'transition_post_status' ), 10, 3 );

		// 1. New top-level page (should not be editable)
		$post = $this->factory->post->create( array( 'post_type' => 'page', 'post_parent' => 0, 'post_status' => 'publish' ) );
		$this->assertFalse( BU_Group_Permissions::group_can_edit( $group->id, $post ) );

		// 2. Page place in editable section (should be editable)
		$post = $this->factory->post->create( array( 'post_type' => 'page', 'post_parent' => $allowed, 'post_status' => 'publish' ) );
		$this->assertTrue( BU_Group_Permissions::group_can_edit( $group->id, $post ) );

		// 3. Non-hierarchical post type (should not be editable)
		$post = $this->factory->post->create( array( 'post_type' => 'post', 'post_status' => 'publish' ) );
		$this->assertFalse( BU_Group_Permissions::group_can_edit( $group->id, $post ) );

		// 4. Draft -> Publish in non-editable section
		$post = $this->factory->post->create( array( 'post_type' => 'page', 'post_parent' => 0, 'post_status' => 'draft' ));
		$post = wp_update_post( array( 'ID' => $post, 'post_status' => 'publish' ) );
		$this->assertFalse( BU_Group_Permissions::group_can_edit( $group->id, $post ) );

		// 5. Draft -> Publish in editable section
		$post = $this->factory->post->create( array( 'post_type' => 'page', 'post_parent' => 0, 'post_status' => 'draft' ));
		$post = wp_update_post( array( 'ID' => $post, 'post_status' => 'publish', 'post_parent' => $allowed ) );
		$this->assertTrue( BU_Group_Permissions::group_can_edit( $group->id, $post ) );

		// 6. Publish -> draft
		$post = $this->factory->post->create( array( 'post_type' => 'page', 'post_parent' => $allowed, 'post_status' => 'publish' ));
		$this->assertTrue( BU_Group_Permissions::group_can_edit( $group->id, $post ) );
		$post = wp_update_post( array( 'ID' => $post, 'post_status' => 'draft' ) );
		$this->assertFalse( BU_Group_Permissions::group_can_edit( $group->id, $post ) );

		// 7. Publish -> trash (should not lose section editing privileges)
		$post = $this->factory->post->create( array( 'post_type' => 'page', 'post_parent' => $allowed, 'post_status' => 'publish' ));
		$this->assertTrue( BU_Group_Permissions::group_can_edit( $group->id, $post ) );
		$post = wp_update_post( array( 'ID' => $post, 'post_status' => 'trash' ) );
		$this->assertTrue( BU_Group_Permissions::group_can_edit( $group->id, $post ) );

		register_post_type( 'bu_link', array( 'hierarchical' => true ) );
		$link = $this->factory->post->create( array( 'post_type' => 'bu_link', 'post_parent' => $allowed, 'post_status' => 'publish' ) );
		$this->assertTrue( BU_Group_Permissions::group_can_edit( $group->id, $link ) );

		register_post_type( 'flat', array( 'hierarchical' => false ) );
		$flat = $this->factory->post->create( array( 'post_type' => 'flat', 'post_parent' => $allowed, 'post_status' => 'publish' ) );
		$this->assertFalse( BU_Group_Permissions::group_can_edit( $group->id, $flat ) );
	}

}

?>