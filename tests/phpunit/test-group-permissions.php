<?php

/**
 * Integration tests for group permissions operations
 *
 * @group bu
 * @group bu-section-editing
 **/
class Test_BU_Group_Permissions extends WP_UnitTestCase {

	function setUp(): void {
		parent::setUp();
		$this->factory->group = new BUSE_UnitTest_Factory_For_Group( $this->factory );
		register_post_type( 'custom', array( 'hierarchical' => false ) );
	}

	function tearDown(): void {
		parent::tearDown();

		if ( function_exists( 'unregister_post_type' ) ) {
			unregister_post_type( 'custom' );
		} else {
			_unregister_post_type( 'custom' );
		}
	}

	/**
	 */
	function test_get_and_update_group_permissions() {

		$group = $this->factory->group->create( array( 'name' => __FUNCTION__ ) );
		$posts = $this->factory->post->create_many( 3, array( 'post_type' => 'post' ) );
		$pages = $this->factory->post->create_many( 5, array( 'post_type' => 'page' ) );

		$allowedposts = BU_Group_Permissions::get_allowed_posts_for_group( $group->id, array( 'post_type' => 'post' ) );
		$allowedpages = BU_Group_Permissions::get_allowed_posts_for_group( $group->id, array( 'post_type' => 'page' ) );

		$this->assertTrue( empty( $allowedposts ) );
		$this->assertTrue( empty( $allowedpages ) );
		$this->assertSame( array(), get_post_meta( $posts[0], BU_Group_Permissions::META_KEY ) );

		$perms = array(
			'post' => array( 'allowed' => $posts ),
			'page' => array( 'allowed' => $pages ),
			);

		BU_Group_Permissions::update_group_permissions( $group->id, $perms );

		$allowedposts = BU_Group_Permissions::get_allowed_posts_for_group( $group->id, array( 'post_type' => 'post', 'fields' => 'ids' ) );
		$allowedpages = BU_Group_Permissions::get_allowed_posts_for_group( $group->id, array( 'post_type' => 'page', 'fields' => 'ids' ) );
		$allowedposts = array_map( 'intval', $allowedposts );
		$allowedpages = array_map( 'intval', $allowedpages );
		$this->assertEquals( asort( $posts ), asort( $allowedposts ) );
		$this->assertEquals( asort( $pages ), asort( $allowedpages ) );
		$this->assertSame(
			array( $group->id ),
			array_map( 'intval', get_post_meta( $posts[0], BU_Group_Permissions::META_KEY ) )
		);

	}

	/**
	 * Denied permissions are removed in bulk and affected metadata caches are cleared.
	 */
	function test_update_group_permissions_bulk_deletes_denied_meta() {
		global $wpdb;

		$group = $this->factory->group->create( array( 'name' => __FUNCTION__ ) );
		$other_group = $this->factory->group->create( array( 'name' => __FUNCTION__ . '_other' ) );
		$posts = $this->factory->post->create_many( 3, array( 'post_type' => 'post' ) );

		add_post_meta( $posts[0], BU_Group_Permissions::META_KEY, $group->id );
		add_post_meta( $posts[0], BU_Group_Permissions::META_KEY, $group->id );
		add_post_meta( $posts[0], BU_Group_Permissions::META_KEY, $other_group->id );
		add_post_meta( $posts[1], BU_Group_Permissions::META_KEY, $group->id );
		add_post_meta( $posts[1], 'unrelated_meta', 'keep' );
		add_post_meta( $posts[2], BU_Group_Permissions::META_KEY, $group->id );

		// Prime the post meta caches before the direct database deletion.
		get_post_meta( $posts[0], BU_Group_Permissions::META_KEY );
		get_post_meta( $posts[1], BU_Group_Permissions::META_KEY );

		$queries = array();
		$query_logger = function ( $query ) use ( &$queries ) {
			$queries[] = $query;
			return $query;
		};

		add_filter( 'query', $query_logger );

		BU_Group_Permissions::update_group_permissions(
			$group->id,
			array(
				'post' => array( 'denied' => array( $posts[0], $posts[1] ) ),
			)
		);

		remove_filter( 'query', $query_logger );

		$delete_queries = array_filter(
			$queries,
			function ( $query ) use ( $wpdb ) {
				return false !== stripos( $query, 'DELETE FROM ' . $wpdb->postmeta );
			}
		);

		$this->assertCount( 1, $delete_queries );
		$this->assertSame(
			array( $other_group->id ),
			array_map( 'intval', get_post_meta( $posts[0], BU_Group_Permissions::META_KEY ) )
		);
		$this->assertSame( array(), get_post_meta( $posts[1], BU_Group_Permissions::META_KEY ) );
		$this->assertSame( 'keep', get_post_meta( $posts[1], 'unrelated_meta', true ) );
		$this->assertSame(
			array( $group->id ),
			array_map( 'intval', get_post_meta( $posts[2], BU_Group_Permissions::META_KEY ) )
		);
	}

	/**
	 * ACL values must match the canonical group ID exactly.
	 */
	function test_group_ids_are_matched_as_exact_strings() {
		$user_id = $this->factory->user->create();
		$group = $this->factory->group->create(
			array(
				'name' => __FUNCTION__,
				'users' => array( $user_id ),
			)
		);
		$posts = $this->factory->post->create_many( 3, array( 'post_type' => 'custom' ) );
		$group_id = (string) $group->id;
		$legacy_value = $group_id . ':denied';
		$padded_value = '0' . $group_id;

		add_post_meta( $posts[0], BU_Group_Permissions::META_KEY, $legacy_value );
		add_post_meta( $posts[1], BU_Group_Permissions::META_KEY, $padded_value );
		add_post_meta( $posts[2], BU_Group_Permissions::META_KEY, $group->id );

		$this->assertFalse( BU_Group_Permissions::group_can_edit( $group->id, $posts[0], 'ignore_global' ) );
		$this->assertFalse( BU_Group_Permissions::group_can_edit( $group->id, $posts[1], 'ignore_global' ) );
		$this->assertTrue( BU_Group_Permissions::group_can_edit( $group->id, $posts[2], 'ignore_global' ) );
		$this->assertFalse( BU_Group_Permissions::group_can_edit( -1, $posts[2], 'ignore_global' ) );

		$allowed_posts = BU_Edit_Groups::get_instance()->get_allowed_posts(
			array(
				'group' => $group->id,
				'post_type' => 'custom',
			)
		);
		$this->assertSame( array( $posts[2] ), array_map( 'intval', $allowed_posts ) );

		// A legacy row must not prevent insertion of the canonical allowed value.
		BU_Group_Permissions::update_group_permissions(
			$group->id,
			array(
				'custom' => array( 'allowed' => array( $posts[0] ) ),
			)
		);
		$this->assertSame(
			array( $legacy_value, $group_id ),
			get_post_meta( $posts[0], BU_Group_Permissions::META_KEY )
		);

		// Denying the canonical value must leave similarly prefixed legacy data alone.
		BU_Group_Permissions::update_group_permissions(
			$group->id,
			array(
				'custom' => array( 'denied' => array( $posts[0] ) ),
			)
		);
		$this->assertSame( array( $legacy_value ), get_post_meta( $posts[0], BU_Group_Permissions::META_KEY ) );

		$hierarchical_posts = array(
			$posts[0] => get_post( $posts[0] ),
			$posts[1] => get_post( $posts[1] ),
			$posts[2] => get_post( $posts[2] ),
		);
		$editor = new BU_Hierarchical_Permissions_Editor( $group, 'custom' );
		$hierarchical_posts = $editor->filter_posts( $hierarchical_posts );
		$this->assertFalse( $hierarchical_posts[ $posts[0] ]->editable );
		$this->assertFalse( $hierarchical_posts[ $posts[1] ]->editable );
		$this->assertTrue( $hierarchical_posts[ $posts[2] ]->editable );

		wp_set_current_user( $user_id );
		require_once __DIR__ . '/../../plugin-support/bu-navigation/section-editor-nav.php';
		$navigation_posts = buse_bu_navigation_filter_posts(
			array(
				$posts[0] => get_post( $posts[0] ),
				$posts[1] => get_post( $posts[1] ),
				$posts[2] => get_post( $posts[2] ),
			)
		);
		wp_set_current_user( 0 );
		$this->assertFalse( $navigation_posts[ $posts[0] ]->can_edit );
		$this->assertFalse( $navigation_posts[ $posts[1] ]->can_edit );
		$this->assertTrue( $navigation_posts[ $posts[2] ]->can_edit );

		$parent_id = $this->factory->post->create( array( 'post_type' => 'page' ) );
		$child_id = $this->factory->post->create(
			array(
				'post_type' => 'page',
				'post_parent' => $parent_id,
				'post_status' => 'draft',
			)
		);
		add_post_meta( $parent_id, BU_Group_Permissions::META_KEY, $padded_value );
		BU_Groups_Admin::transition_post_status( 'publish', 'draft', get_post( $child_id ) );
		$this->assertFalse( BU_Group_Permissions::group_can_edit( $group->id, $child_id, 'ignore_global' ) );
	}

	/**
	 */
	function test_delete_group_permissions() {

		$posts = $this->factory->post->create_many( 2, array( 'post_type' => 'post' ) );
		$pages = $this->factory->post->create_many( 2, array( 'post_type' => 'page' ) );

		$perms = array(
			'post' => array( 'allowed' => $posts ),
			'page' => array( 'allowed' => $pages ),
			);

		$group = $this->factory->group->create( array( 'name' => __FUNCTION__, 'perms' => $perms ) );

		$allowedposts = BU_Group_Permissions::get_allowed_posts_for_group( $group->id, array( 'post_type' => 'post', 'fields' => 'ids' ) );
		$allowedpages = BU_Group_Permissions::get_allowed_posts_for_group( $group->id, array( 'post_type' => 'page', 'fields' => 'ids' ) );
		$allowedposts = array_map( 'intval', $allowedposts );
		$allowedpages = array_map( 'intval', $allowedpages );
		$this->assertEquals( asort( $posts ), asort( $allowedposts ) );
		$this->assertEquals( asort( $pages ), asort( $allowedpages ) );

		BU_Group_Permissions::delete_group_permissions( $group->id );

		$allowedposts = BU_Group_Permissions::get_allowed_posts_for_group( $group->id, array( 'post_type' => 'post' ) );
		$allowedpages = BU_Group_Permissions::get_allowed_posts_for_group( $group->id, array( 'post_type' => 'page' ) );

		$this->assertTrue( empty( $allowedposts ) );
		$this->assertTrue( empty( $allowedpages ) );

	}

	/**
	 */
	function test_group_can_edit() {

		$posts = $this->factory->post->create_many( 2, array( 'post_type' => 'post' ) );
		$pages = $this->factory->post->create_many( 2, array( 'post_type' => 'page' ) );
		$custom_posts = $this->factory->post->create_many( 2, array( 'post_type' => 'custom' ) );

		$perms = array(
			'post' => array( 'allowed' => array( $posts[0] ) ),
			'page' => array( 'allowed' => array( $pages[1] ) ),
			);

		$group = $this->factory->group->create(
			array(
				'name' => __FUNCTION__,
				'perms' => $perms,
				'global_edit' => array('custom')
			)
		);

		$this->assertTrue( BU_Group_Permissions::group_can_edit( $group->id, reset( $posts ) ) );
		$this->assertFalse( BU_Group_Permissions::group_can_edit( $group->id, next( $posts ) ) );
		$this->assertFalse( BU_Group_Permissions::group_can_edit( $group->id, reset( $pages ) ) );
		$this->assertTrue( BU_Group_Permissions::group_can_edit( $group->id, next( $pages ) ) );
		$this->assertTrue( BU_Group_Permissions::group_can_edit( $group->id, reset( $custom_posts ) ) );
		$this->assertTrue( BU_Group_Permissions::group_can_edit( $group->id, next( $custom_posts ) ) );
	}

	/**
	 * The flat permissions editor emits markup while escaping post data.
	 */
	function test_flat_permissions_editor_displays_safe_markup() {
		$group = $this->factory->group->create( array( 'name' => __FUNCTION__ ) );
		$post = $this->factory->post->create(
			array(
				'post_type' => 'custom',
				'post_status' => 'publish',
				'post_title' => 'Rock & Roll',
			)
		);

		$editor = new BU_Flat_Permissions_Editor( $group, 'custom' );
		$editor->query(
			array(
				'post__in' => array( $post ),
				'posts_per_page' => 1,
			)
		);

		ob_start();
		$editor->display();
		$output = ob_get_clean();

		$this->assertNotFalse( strpos( $output, '<ul class="perm-list flat">' ) );
		$this->assertNotFalse( strpos( $output, '<li ' ) );
		$this->assertNotFalse( strpos( $output, '<button class="edit-perms ' ) );
		$this->assertFalse( strpos( $output, '&lt;ul' ) );
		$this->assertNotFalse( strpos( $output, 'Rock &amp; Roll' ) );

		$unsafe_markup = $editor->get_post_markup(
			array(
				'attr' => array(
					'id' => 'post"><script>',
					'class' => 'alternate"><script>',
					'rel' => 'allowed"><script>',
				),
				'data' => array(
					'title' => '<script>alert("xss")</script> & title',
					'icon' => 'icon"><script>',
				),
				'metadata' => array(
					'post_id' => '1"><script>',
					'post_date' => '<script>alert("date")</script>',
					'post_status' => 'publish',
					'editable' => true,
					'editable-original' => true,
				),
			)
		);

		$this->assertFalse( strpos( $unsafe_markup, '<script>' ) );
		$this->assertNotFalse( strpos( $unsafe_markup, '&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt; &amp; title' ) );
	}

	/**
	 * Coverage for group meta inheritance on post save
	 */
	function test_transition_post_status_inheritance() {
		$allowed = $this->factory->post->create( array( 'post_type' => 'page' ) );
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
		$post = $this->factory->post->create( array( 'post_type' => 'page', 'post_parent' => 0, 'post_status' => 'draft' ) );
		$post = wp_update_post( array( 'ID' => $post, 'post_status' => 'publish' ) );
		$this->assertFalse( BU_Group_Permissions::group_can_edit( $group->id, $post ) );

		// 5. Draft -> Publish in editable section
		$post = $this->factory->post->create( array( 'post_type' => 'page', 'post_parent' => 0, 'post_status' => 'draft' ) );
		$post = wp_update_post( array( 'ID' => $post, 'post_status' => 'publish', 'post_parent' => $allowed ) );
		$this->assertTrue( BU_Group_Permissions::group_can_edit( $group->id, $post ) );

		// 6. Publish -> draft
		$post = $this->factory->post->create( array( 'post_type' => 'page', 'post_parent' => $allowed, 'post_status' => 'publish' ) );
		$this->assertTrue( BU_Group_Permissions::group_can_edit( $group->id, $post ) );
		$post = wp_update_post( array( 'ID' => $post, 'post_status' => 'draft' ) );
		$this->assertFalse( BU_Group_Permissions::group_can_edit( $group->id, $post ) );

		// 7. Publish -> trash (should not lose section editing privileges)
		$post = $this->factory->post->create( array( 'post_type' => 'page', 'post_parent' => $allowed, 'post_status' => 'publish' ) );
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
