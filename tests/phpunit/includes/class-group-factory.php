<?php

// @todo decide if we need to / how to handle permissions
class WP_UnitTest_Factory_For_Group extends WP_UnitTest_Factory_For_Thing {

	function __construct( $factory = null ) {
		parent::__construct( $factory );
		$this->default_generation_definitions = array(
			'name' => new WP_UnitTest_Generator_Sequence( "Test Group %s" ),
			'description' => new WP_UnitTest_Generator_Sequence( "Group description %s" ),
		);
	}

	function create_object( $args ) {
		return BU_Edit_Groups::get_instance()->add_group( $args );
	}

	function update_object( $group_id, $fields ) {
		return BU_Edit_Groups::get_instance()->update_group( $group_id, $fields );
	}

	function get_object_by_id( $group_id ) {
		return BU_Edit_Groups::get_instance()->get( $group_id );
	}
}

?>
