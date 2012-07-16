<?php

/**
 * @group bu-section-editing
 **/
class Test_BU_Section_Editing extends WP_UnitTestCase {

	//________________________GROUP CONTROLLER TESTS_______________________

	/**
	 * Test proper creation of BU_Edit_Group object from key/value parameter list
	 */
	function test_create_section_editing_group() {

		$group_args = $this->generate_test_group_args();
		$group = new BU_Edit_Group( $group_args );

		$this->assertEquals( $group_args['name'], $group->name );
		$this->assertEquals( $group_args['description'], $group->description );
		$this->assertEquals( $group_args['users'], $group->users );

	}

	/**
	 * Test addition of new section editing group to group controller
	 */
	function test_add_section_editing_group() {

		$controller = BU_Edit_Groups::get_instance();

		// This should be a method
		$groups_before = $controller->get_groups();

		$group_args = $this->generate_test_group_args();
		$group = $controller->add_group( $group_args );

		// This should be a method (get_groups)
		$groups_after = $controller->get_groups();

		$this->assertNotEquals( $groups_before, $groups_after );
		$this->assertContains( $group, $groups_after );
	}

	/**
	 * Test fetching of section editing group from group controller
	 */
	function test_get_section_editing_group() {

		$controller = BU_Edit_Groups::get_instance();

		$createdgroup = $this->quick_add_group( array('name' => 'Test group for getting' ) );
		$fetchedgroup = $controller->get( $createdgroup->id );

		$this->assertSame( $createdgroup, $fetchedgroup );

	}

	/**
	 * Test deletion of existing section editing group from group controller
	 */
	function test_delete_section_editing_group() {

		$controller = BU_Edit_Groups::get_instance();

		$group = $this->quick_add_group( array('name' => 'Test group for deleting' ) );

		$groups_before = $controller->get_groups();

		$controller->delete_group( $group->id );

		$groups_after = $controller->get_groups();

		$this->assertNotContains( $group, $groups_after );
		$this->assertNotEquals( $groups_before, $groups_after );
	}

	/**
	 * Test modification of existing section editing group from group controller
	 */
	function test_update_section_editing_group() {

		$controller = BU_Edit_Groups::get_instance();

		// Add group first
		$group = $this->quick_add_group( array('name' => 'Test group for updating' ) );

		// Make a copy so it doesn't get updated
		$originalgroup = clone $group;

		// Modify group
		$update_args = array(
			'name' => 'Test Group Updated',
			'description' => 'Description updated',
			'users' => array( 3, 4 ),
			'perms' => array( 'page' => array(), 'post' => array() )
		);

		$newgroup = $controller->update_group( $originalgroup->id, $update_args );

		$this->assertInstanceOf( 'BU_Edit_Group', $originalgroup );
		$this->assertInstanceOf( 'BU_Edit_Group', $newgroup );
		$this->assertNotSame( $originalgroup, $newgroup, 'Orignal and modified group are the same object!' );

		$this->assertEquals( $originalgroup->id, $newgroup->id );
		$this->assertNotEquals( $originalgroup->name, $newgroup->name );
		$this->assertNotEquals( $originalgroup->description, $newgroup->description );
		$this->assertNotEquals( $originalgroup->users, $newgroup->users );
	}

	/**
	 * Test save to DB
	 */
	function test_save_section_editing_groups() {

		// Start fresh
		$controller = BU_Edit_Groups::get_instance();

		// Generate 3 random groups
		$args = $this->generate_test_group_args();
		$groups = $this->quick_add_group( $args, 3 );
		foreach( $groups as $group ) {
			$this->assertInstanceOf( 'BU_Edit_Group', $group );
		}

		// Cleanup
		$controller->delete_groups();

	}

	/**
	 * Test load from DB
	 */
	function test_load_section_editing_groups() {

		// Start fresh
		$controller = BU_Edit_Groups::get_instance();

		// Generate 3 random groups
		$args = $this->generate_test_group_args();
		$groups = $this->quick_add_group( $args, 3 );

		// Reset internal groups array
		$controller->delete_groups();

		// Re-load from db
		$controller->load();

		// Compare
		$this->assertCount( 3, $controller->get_groups() );

		foreach( $controller->get_groups() as $group ) {

			$this->assertInstanceOf( 'BU_Edit_Group', $group );
			$this->assertEquals( 'Test Group 1', $group->name );
			$this->assertEquals( 'Group for testing', $group->description );
			$this->assertEquals( array(1,2), $group->users );

		}

		// Cleanup
		$controller->delete_groups();

	}


	//_______________________HELPERS___________________________

	function generate_test_group_args( $args = array() ) {

		$group_args = array(
			'name' => 'Test Group 1',
			'description' => 'Group for testing',
			'users' => array( 1, 2 ),
			'perms' => array( 'page' => array(), 'post' => array())
		);

		return wp_parse_args( $args, $group_args );
	}

	function quick_add_group( $args = array(), $count = 1 ) {

		$groups = array();
		$controller = BU_Edit_Groups::get_instance();

		$group_args = $this->generate_test_group_args( $args );

		for( $i = 0; $i < $count; $i++ ) {
			$groups[] = $controller->add_group( $group_args );
		}

		return count($groups == 1) ? $groups[0] : $groups;

	}

}

?>
