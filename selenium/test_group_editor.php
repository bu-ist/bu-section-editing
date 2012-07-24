<?php

/**
 * @group bu-section-editing-selenium
 */ 
class BUSE_GroupEditorTests extends WP_SeleniumTestCase {

	public $users;

	public function setUp() {

		parent::setUp();

        // Take site URL from installation
		$this->setBrowserUrl( site_url() );

		// Login to start each test
		$this->wp_login( 'admin', 'password' );

		// Activate section editing plugin
		$this->activate_plugin( 'bu-section-editing' );

		// @todo create all state for testing

		$user_args = array(
			'user_login' => 'section_editor',
			'user_email' => 'wpcms01@bu.edu',
			'user_pass' => 'buse_test_pass',
			'role' => 'Section Editor'
			);
		
		$this->add_user( $user_args );

	}

	public function tearDown() {

        parent::tearDown();

        if( $this->hasFailed() ) {
            $date = "screenshot_" . date('Y-m-d-H-i-s') . ".png" ;
            $this->webdriver->getScreenshotAndSaveToFile( $date );
        }

        $this->close();
	}

	public function test_load_panels() {

		$edit_page = new BUSE_EditGroupPage( $this );

		$activeProperties = $this->getElement( LocatorStrategy::cssSelector, '#group-properties-panel.group-panel.active' );
		$this->assertNotNull( $activeProperties );

		$edit_page->loadPanel('Members');
		$activeMembers = $this->getElement( LocatorStrategy::cssSelector, '#group-members-panel.group-panel.active' );
		$this->assertNotNull( $activeMembers );

		$edit_page->loadPanel('Permissions');
		$activePerms = $this->getElement( LocatorStrategy::cssSelector, '#group-permissions-panel.group-panel.active' );
		$this->assertNotNull( $activePerms );

	}

	public function test_create_group() {

		$edit_page = new BUSE_EditGroupPage( $this );

		$edit_page->setName( 'Test Group - Create Group' );
		$edit_page->setDescription( 'A test group created during the test_create_group test' );
		$edit_page->saveGroup();

		$groups_page = new BUSE_GroupsPage( $this );
		$edit_link = $this->getElement( LocatorStrategy::linkText, 'Test Group - Create Group' );

		$this->assertNotNull( $edit_link );

		$url = $edit_link->getAttribute('href');
		$parts = parse_url( $url );
		$args = wp_parse_args( $parts['query'] );

		$group_id = $args['id'];

		$this->assertNotNull( $group_id );

	}

	// public function test_add_member() {

	// 	$edit_page = new BUSE_EditGroupPage( $this );

	// 	$edit_page->setName( 'Test Group - Add Member' );
	// 	$edit_page->addMember( 'section_editor' );
	// 	$edit_page->saveGroup();

	// 	// @todo assertions

	// }
 
}

/**
 * Page objects for group editor interface
 * 
 * @todo
 *	- better isolate markup/url dependencies in to constants
 */

class BUSE_GroupsPage {

	private $selenium = null;

	function __construct( $selenium ) {
		$this->selenium = $selenium;
		$request_url = '/wp-admin/' . BU_Groups_Admin::MANAGE_GROUPS_PAGE;

		$this->selenium->open( $request_url );

		$page_title = $this->selenium->getTitle();

		if( strpos( $this->selenium->getTitle(), 'Section Groups' ) === false )
			throw new Exception('Section Groups page failed to load -- unable to load URL: ' . $request_url );
	}

}

class BUSE_EditGroupPage {

	private $selenium = null;
	private $group_form = null;

	function __construct( $selenium, $group_id = null ) {
		$this->selenium = $selenium;

		// Generate request URL
		// @todo use BU_Groups_Admin::manage_groups_url method
		$action_str = $group_str ='';

		if( is_null( $group_id ) ) {
			$action_str = '&action=add';
		} else {
			$action_str = '&action=edit';
			$group_str = '&id=' . $group_id;
		}
		$query_str = sprintf('%s%s', $action_str, $group_str );
		$request_url = '/wp-admin/' . BU_Groups_Admin::MANAGE_GROUPS_PAGE . $query_str;

		$this->selenium->open( $request_url  );

		$page_title = $this->selenium->getTitle();

		if( strpos( $page_title, 'Section Groups' ) === false )
			throw new Exception('Edit Group Page failed to load -- Unable to load URL: ' . $request_url );

		$this->group_form = new SeleniumFormHelper( $this->selenium, 'group-edit-form' );

	}

	function loadPanel( $name ) {

		$tab = $this->selenium->getElement( LocatorStrategy::linkText, $name );
		$tab->click();

	}

	function setName( $name ) {

		$this->loadPanel( 'Properties' );
		$this->group_form->populateFields( array( 'edit-group-name' => array( 'type' => 'text', 'value' => $name ) ) );

	}

	function setDescription( $description ) {

		$this->loadPanel( 'Properties' );
		$this->group_form->populateFields( array( 'edit-group-description' => array( 'type' => 'textarea', 'value' => $description ) ) );

	}

	// @todo implement
	function addMember( $login ) {

		$this->loadPanel( 'Members' );

	}

	// @todo implement
	function removeMember() {

		$this->loadPanel( 'Members' );

	}

	function saveGroup() {

		$this->group_form->submit();

	}

	function deleteGroup() {

	}
	
}

?>