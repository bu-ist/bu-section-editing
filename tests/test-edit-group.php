<?php

/**
 * More traditional unit tests against the BU_Edit_Group class
 *
 * @group bu
 * @group bu-section-editing
 **/
class Test_BU_Edit_Group extends WP_UnitTestCase {


	function test_constructor() {

		$groupdata = array(
			'name' => 'Test group',
			'description' => 'Test group description',
			'users' => array(1,2),
			);

		$group = new BU_Edit_Group( $groupdata );

		$this->assertEquals( $groupdata['name'], $group->name );
		$this->assertEquals( $groupdata['description'], $group->description );
		$this->assertEquals( $groupdata['users'], $group->users );

	}

	/**
	 * Test fetching of section editing group from group controller
	 */
	function test_has_user() {

		$groupdata = array(
			'name' => 'Test group',
			'users' => array(1,2),
			);

		$group = new BU_Edit_Group( $groupdata );

		$this->assertTrue( $group->has_user( 1 ) );
		$this->assertFalse( $group->has_user( 3 ) );

	}

	/**
	 * Test deletion of existing section editing group from group controller
	 */
	function test_remove_user() {

		$groupdata = array(
			'name' => 'Test group',
			'users' => array(1,2),
			);

		$group = new BU_Edit_Group( $groupdata );

		$this->assertTrue( $group->has_user( 2 ) );

		$group->remove_user( 2 );

		$this->assertFalse( $group->has_user( 2 ) );

	}

	/**
	 * Test modification of existing section editing group from group controller
	 */
	function test_update() {

		$groupdata = array(
			'name' => 'Test group',
			'description' => 'Test description',
			'users' => array(1,2),
			);

		$group = new BU_Edit_Group( $groupdata );

		$this->assertEquals( $groupdata['name'], $group->name );
		$this->assertEquals( $groupdata['description'], $group->description );
		$this->assertEquals( $groupdata['users'], $group->users );

		$updates = array(
			'name' => 'Test group renamed',
			'description' => 'Test description change',
			'users' => array(3,4)
			);

		$group->update( $updates );

		$this->assertEquals( $updates['name'], $group->name );
		$this->assertEquals( $updates['description'], $group->description );
		$this->assertEquals( $updates['users'], $group->users );

	}

}

?>