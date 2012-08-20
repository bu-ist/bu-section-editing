<?php

require_once( dirname(__FILE__) . '/includes/classes.group-factory.php' );

/**
 * Integration tests for group permissions operations
 * 
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

		$group = $this->factory->group->create();
		$posts = $this->factory->post->create_many(3, array('post_type' => 'post'));
		$pages = $this->factory->post->create_many(5, array('post_type' => 'page'));

		$allowedposts = BU_Group_Permissions::get_allowed_posts_for_group( $group->id, array('post_type' => 'post'));
		$allowedpages = BU_Group_Permissions::get_allowed_posts_for_group( $group->id, array('post_type' => 'page'));

		$this->assertTrue( empty( $allowedposts ) );
		$this->assertTrue( empty( $allowedpages ) );

		$perms = array(
			'post' => array_combine( $posts, array( 'allowed', 'allowed', 'allowed' ) ),
			'page' => array_combine( $pages, array( 'allowed', 'allowed', 'allowed', 'allowed','allowed') )
			);

		BU_Group_Permissions::update_group_permissions( $group->id, $perms );

		$allowedposts = BU_Group_Permissions::get_allowed_posts_for_group( $group->id, array('post_type' => 'post', 'fields' => 'ids' ));
		$allowedpages = BU_Group_Permissions::get_allowed_posts_for_group( $group->id, array('post_type' => 'page', 'fields' => 'ids' ));

		$this->assertEquals( asort( $posts ), asort( array_map( 'intval', $allowedposts ) ) );
		$this->assertEquals( asort( $pages ), asort( array_map( 'intval', $allowedpages ) ) );

	}

	/**
	 */
	function test_delete_group_permissions() {

		$posts = $this->factory->post->create_many(2, array('post_type' => 'post'));
		$pages = $this->factory->post->create_many(2, array('post_type' => 'page'));

		$perms = array(
			'post' => array_combine( $posts, array( 'allowed', 'allowed' ) ),
			'page' => array_combine( $pages, array( 'allowed', 'allowed' ) )
			);

		$group = $this->factory->group->create( array( 'perms' => $perms ) );

		$allowedposts = BU_Group_Permissions::get_allowed_posts_for_group( $group->id, array('post_type' => 'post', 'fields' => 'ids' ));
		$allowedpages = BU_Group_Permissions::get_allowed_posts_for_group( $group->id, array('post_type' => 'page', 'fields' => 'ids' ));

		$this->assertEquals( asort( $posts ), asort( array_map( 'intval', $allowedposts ) ) );
		$this->assertEquals( asort( $pages ), asort( array_map( 'intval', $allowedpages ) ) );

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
			'post' => array_combine( $posts, array( 'allowed', '' ) ),
			'page' => array_combine( $pages, array( '', 'allowed' ) )
			);

		$group = $this->factory->group->create( array( 'perms' => $perms ) );

		$this->assertTrue( BU_Group_Permissions::group_can_edit( $group->id, reset($posts) ));
		$this->assertFalse( BU_Group_Permissions::group_can_edit( $group->id, next($posts) ));
		$this->assertFalse( BU_Group_Permissions::group_can_edit( $group->id, reset($pages) ));
		$this->assertTrue( BU_Group_Permissions::group_can_edit( $group->id, next($pages) ));

	}

}

?>