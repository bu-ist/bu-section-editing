<?php

require_once __DIR__ . '/../../admin-ajax.groups.php';

/**
 * Integration tests for the group administration screens.
 *
 * @group bu
 * @group bu-section-editing
 */
class Test_BU_Groups_Admin extends WP_UnitTestCase {
	private $group_factory;
	private $original_wp_scripts;
	private $original_wp_styles;

	function setUp(): void {
		parent::setUp();
		$this->group_factory = new BUSE_UnitTest_Factory_For_Group( $this->factory );
		$this->original_wp_scripts = isset( $GLOBALS['wp_scripts'] ) ? $GLOBALS['wp_scripts'] : null;
		$this->original_wp_styles = isset( $GLOBALS['wp_styles'] ) ? $GLOBALS['wp_styles'] : null;
		$default_scripts_hook = isset( $GLOBALS['wp_filter']['wp_default_scripts'] ) ? $GLOBALS['wp_filter']['wp_default_scripts'] : null;
		$default_styles_hook = isset( $GLOBALS['wp_filter']['wp_default_styles'] ) ? $GLOBALS['wp_filter']['wp_default_styles'] : null;
		unset( $GLOBALS['wp_filter']['wp_default_scripts'], $GLOBALS['wp_filter']['wp_default_styles'] );
		$GLOBALS['wp_scripts'] = new WP_Scripts();
		$GLOBALS['wp_styles'] = new WP_Styles();
		if ( is_null( $default_scripts_hook ) ) {
			unset( $GLOBALS['wp_filter']['wp_default_scripts'] );
		} else {
			$GLOBALS['wp_filter']['wp_default_scripts'] = $default_scripts_hook;
		}
		if ( is_null( $default_styles_hook ) ) {
			unset( $GLOBALS['wp_filter']['wp_default_styles'] );
		} else {
			$GLOBALS['wp_filter']['wp_default_styles'] = $default_styles_hook;
		}
	}

	function tearDown(): void {
		BU_Edit_Groups::get_instance()->delete_groups();
		BU_Groups_Admin::$manage_groups_hooks = null;
		remove_action( 'init', array( $GLOBALS['wp_scripts'], 'init' ), 0 );
		if ( is_null( $this->original_wp_scripts ) ) {
			unset( $GLOBALS['wp_scripts'] );
		} else {
			$GLOBALS['wp_scripts'] = $this->original_wp_scripts;
		}
		if ( is_null( $this->original_wp_styles ) ) {
			unset( $GLOBALS['wp_styles'] );
		} else {
			$GLOBALS['wp_styles'] = $this->original_wp_styles;
		}
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	/**
	 * New groups have no global permissions and must render without warnings.
	 */
	function test_group_permissions_string_handles_unsaved_group() {
		$output = BU_Groups_Admin::group_permissions_string(
			new BU_Edit_Group(),
			array( 'post_type' => 'post' )
		);

		$this->assertNotFalse( strpos( $output, 'perm-stat-count">0</span>' ) );
		$this->assertFalse( strpos( $output, 'global-edit' ) );
	}

	/**
	 * Group names and edit URLs are escaped before the Users table outputs them.
	 */
	function test_manage_users_group_column_escapes_output() {
		$user_id = $this->factory->user->create();
		$group = $this->group_factory->create(
			array(
				'name' => 'Research & Development',
				'users' => array( $user_id ),
			)
		);
		$output = BU_Groups_Admin::manage_users_group_column(
			'',
			BU_Groups_Admin::MANAGE_USERS_COLUMN,
			$user_id
		);
		$edit_url = esc_url( BU_Groups_Admin::manage_groups_url( 'edit', array( 'id' => $group->id ) ) );

		$this->assertNotFalse( strpos( $output, 'href="' . $edit_url . '"' ) );
		$this->assertNotFalse( strpos( $output, 'Research &amp; Development' ) );
	}

	/**
	 * The dynamic site-user script URL carries a nonce for its protected endpoint.
	 */
	function test_site_users_script_url_contains_valid_nonce() {
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'administrator' ) ) );
		BU_Groups_Admin::$manage_groups_hooks = array( 'buse-test-hook' );

		BU_Groups_Admin::admin_scripts( 'buse-test-hook' );

		$scripts = wp_scripts();
		$this->assertArrayHasKey( 'buse-site-users', $scripts->registered );
		$query = array();
		parse_str(
			(string) wp_parse_url( html_entity_decode( $scripts->registered['buse-site-users']->src ), PHP_URL_QUERY ),
			$query
		);

		$this->assertSame( 'buse_site_users_script', $query['action'] );
		$this->assertNotEmpty( $query['_ajax_nonce'] );
		$this->assertNotFalse( wp_verify_nonce( $query['_ajax_nonce'], BU_Groups_Admin_Ajax::NONCE_ACTION ) );
	}

	/**
	 * The full form handler must not remove an extra level of request slashes.
	 */
	function test_group_update_preserves_established_backslash_handling() {
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'administrator' ) ) );
		$group = $this->group_factory->create( array( 'name' => __FUNCTION__ ) );
		$submitted_slashes = str_repeat( '\\', 4 );
		$permissions = array();

		foreach ( BU_Group_Permissions::get_supported_post_types( 'names' ) as $post_type ) {
			$permissions[ $post_type ] = wp_json_encode(
				array(
					'allowed' => array(),
					'denied' => array(),
				)
			);
		}

		$request = array(
			'action' => 'update',
			'_wpnonce' => wp_create_nonce( 'save_section_editing_group' ),
			'id' => $group->id,
			'tab' => 'properties',
			'perm_panel' => 'post',
			'group' => array(
				'name' => 'Editors ' . $submitted_slashes . ' West',
				'description' => 'Description ' . $submitted_slashes . ' text',
				'users' => array(),
				'global_edit' => array(),
				'perms' => $permissions,
			),
		);
		$old_get = $_GET;
		$old_post = $_POST;
		$old_request = $_REQUEST;
		$_GET = array();
		$_POST = wp_slash( $request );
		$_REQUEST = $_POST;
		$redirected = false;
		$redirect_interceptor = function ( $location ) {
			throw new Exception( 'buse-test-redirect:' . $location );
		};
		add_filter( 'wp_redirect', $redirect_interceptor );

		try {
			BU_Groups_Admin::load_manage_groups();
		} catch ( Exception $exception ) {
			$redirected = 0 === strpos( $exception->getMessage(), 'buse-test-redirect:' );
		} finally {
			remove_filter( 'wp_redirect', $redirect_interceptor );
			$_GET = $old_get;
			$_POST = $old_post;
			$_REQUEST = $old_request;
		}

		$this->assertTrue( $redirected );
		$stored_group = get_post( $group->id );
		$this->assertSame( 'Editors ' . $submitted_slashes . ' West', $stored_group->post_title );
		$this->assertSame( 'Description ' . $submitted_slashes . ' text', $stored_group->post_content );
	}
}

/**
 * AJAX regressions for group administration.
 *
 * This class intentionally is not assigned to WordPress's ajax group so the
 * plugin's default PHPUnit command exercises these security checks.
 *
 * @group bu
 * @group bu-section-editing
 */
class Test_BU_Groups_Admin_Ajax extends WP_Ajax_UnitTestCase {

	public function set_up() {
		parent::set_up();
		BU_Groups_Admin_Ajax::register_hooks();
	}

	public function tear_down() {
		remove_action( 'wp_ajax_buse_site_users_script', array( 'BU_Groups_Admin_Ajax', 'site_users_script' ) );
		remove_action( 'wp_ajax_buse_render_post_list', array( 'BU_Groups_Admin_Ajax', 'render_post_list' ) );
		BU_Edit_Groups::get_instance()->delete_groups();
		$_REQUEST = array();
		parent::tear_down();
	}

	/**
	 * The user-data script rejects otherwise authorized requests without a nonce.
	 */
	function test_site_users_script_rejects_missing_nonce() {
		$this->_setRole( 'administrator' );
		$rejected = false;

		try {
			$this->_handleAjax( 'buse_site_users_script' );
		} catch ( WPAjaxDieStopException $exception ) {
			$rejected = true;
			$this->assertSame( '-1', $exception->getMessage() );
		} catch ( WPAjaxDieContinueException $exception ) {
			$rejected = true;
			$this->assertSame( '-1', $exception->getMessage() );
		}

		$this->assertTrue( $rejected );
		$this->assertFalse( strpos( $this->_last_response, 'var buse_site_users' ) );
	}

	/**
	 * The -1 new-group sentinel must not resolve to existing group ID 1.
	 */
	function test_render_post_list_preserves_new_group_sentinel() {
		$this->_setRole( 'administrator' );
		$this->assertTrue( wp_doing_ajax() );
		$this->assertNotFalse( has_action( 'wp_ajax_buse_render_post_list', array( 'BU_Groups_Admin_Ajax', 'render_post_list' ) ) );
		BU_Edit_Groups::get_instance()->add( new BU_Edit_Group( array( 'id' => 1 ) ) );
		$post_id = $this->factory->post->create( array( 'post_type' => 'post' ) );
		add_post_meta( $post_id, BU_Group_Permissions::META_KEY, 1 );
		$_POST = array(
			'_ajax_nonce' => wp_create_nonce( BU_Groups_Admin_Ajax::NONCE_ACTION ),
			'group_id' => '-1',
			'post_type' => 'post',
			'query' => array(
				'post__in' => array( $post_id ),
				'posts_per_page' => 1,
			),
		);

		try {
			$this->_handleAjax( 'buse_render_post_list' );
		} catch ( WPAjaxDieContinueException $exception ) {
			unset( $exception );
		}

		$json_start = strpos( $this->_last_response, '{"posts"' );
		$this->assertNotFalse( $json_start, $this->_last_response );
		$response = json_decode( substr( $this->_last_response, $json_start ), true );
		$this->assertIsArray( $response );
		$this->assertNotFalse( strpos( $response['posts'], 'data-editable="false"' ) );
		$this->assertFalse( strpos( $response['posts'], 'data-editable="true"' ) );
	}
}
