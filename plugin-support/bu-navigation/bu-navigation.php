<?php

$current_user = get_current_user_id();

// Only add filters for section editors
if( BU_Section_Editing_Plugin::is_allowed_user( $current_user ) ) {
	
	add_action( 'bu_nav_tree_enqeueue_scripts', 'buse_bu_navigation_scripts' );
	add_filter( 'bu_nav_tree_script_context', 'buse_bu_navigation_script_context');
	add_filter( 'bu_navigation_filter_pages', 'buse_bu_navigation_filter_pages');
	add_filter( 'bu_nav_tree_view_format_post', 'buse_bu_navigation_format_post', 10, 3 );

}

function buse_bu_navigation_scripts() {
	wp_enqueue_script( 'buse-navigation-support', plugins_url( 'section-editor-support.js', __FILE__ ), array('bu-navigation'), '1.0', true );
}

function buse_bu_navigation_script_context( $config ) {
	$config['isSectionEditor'] = true;
	return $config;
}

function buse_bu_navigation_filter_pages( $posts ) {
	global $wpdb;

	$current_user = get_current_user_id();
	$groups = BU_Edit_Groups::get_instance();
	$section_groups = $groups->find_groups_for_user( $current_user, 'ids' );

	if( ( is_array( $posts ) ) && ( count( $posts ) > 0 ) ) {

		// Section editors with no groups have all posts denied
		if( is_array( $section_groups ) && ! empty( $section_groups ) ) {

			/* Gather all group post meta in one shot */
			$ids = array_keys($posts);
			$query = sprintf("SELECT post_id, meta_value FROM %s WHERE meta_key = '%s' AND post_id IN (%s) AND meta_value IN (%s)", $wpdb->postmeta, BU_Group_Permissions::META_KEY, implode(',', $ids), implode( ',', $section_groups ) );
			$group_meta = $wpdb->get_results($query, OBJECT_K); // get results as objects in an array keyed on post_id
			if (!is_array($group_meta)) $group_meta = array();

			// Append permissions to post object
			foreach( $posts as $post ) {

				$post->perm = 'denied';

				if( array_key_exists( $post->ID, $group_meta ) ) {
					$perm = $group_meta[$post->ID];

					if( in_array( $perm->meta_value, $section_groups ) ) {
						$post->perm = 'allowed';
					}
				}
				
				// Hierarchical perm editors ignore draft/pending, allowed by default
				if(in_array($post->post_status,array('draft','pending'))) {
					$post->perm = 'allowed';
				}

			}

		} else {

			foreach( $posts as $post ) {
				$post->perm = 'denied';
				// Hierarchical perm editors ignore draft/pending, allowed by default
				if(in_array($post->post_status,array('draft','pending'))) {
					$post->perm = 'allowed';
				}
			}
			
		}

	}

	return $posts;

}

function buse_bu_navigation_format_post( $p, $post, $has_children ) {

	$perm = isset($page->perm) ? $page->perm : null;

	if( $perm == 'denied' ) {
		$p['attr']['rel'] = 'denied';
		$p['metadata']['post_meta']['denied'] = true;
	} else {
		$p['metadata']['post_meta']['denied'] = false;
	}

	if( ! isset( $p['attr']['class'] ) )
		$p['attr']['class'] = '';

	$p['attr']['class'] = ' ' . $post->perm;

	return $p;
}