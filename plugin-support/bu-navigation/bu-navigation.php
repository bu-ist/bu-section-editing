<?php

// Only add filters for section editors
if( BU_Section_Editing_Plugin::is_allowed_user( get_current_user_id() ) ) {

	add_action( 'bu_nav_tree_enqeueue_scripts', 'buse_bu_navigation_scripts' );
	add_filter( 'bu_nav_tree_script_context', 'buse_bu_navigation_script_context');
	add_filter( 'bu_nav_tree_view_filter_fields', 'buse_bu_navigation_filter_fields');
	add_filter( 'bu_nav_tree_view_filter_posts', 'buse_bu_navigation_filter_pages');
	add_filter( 'bu_nav_tree_view_format_post', 'buse_bu_navigation_format_post', 10, 3 );

}

function buse_bu_navigation_scripts() {
	wp_enqueue_script( 'buse-navigation-support', plugins_url( 'section-editor-support.js', __FILE__ ), array('bu-navigation'), '1.0', true );
}

function buse_bu_navigation_script_context( $config ) {
	$config['isSectionEditor'] = true;
	return $config;
}

/**
 * Add 'post_author' to the post fields to return during bu_navigation_get_pages
 *
 * @param array $fields
 * @return string
 */
function buse_bu_navigation_filter_fields($fields) {
	if( ! in_array( 'post_author', $fields ) ) {
		$fields[] = 'post_author';
	}
	if( ! in_array( 'post_status', $fields ) ) {
		$fields[] = 'post_status';
	}
	return $fields;
}

/**
 * Filters pages during bu_navigation_get_pages to add section group related meta data -- can_edit and can_remove
 *
 * @global type $wpdb
 * @param type $posts
 * @return type
 */
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

				$post->can_edit = false;
				$post->can_remove = false;

				if( array_key_exists( $post->ID, $group_meta ) ) {
					$perm = $group_meta[$post->ID];

					if( in_array( $perm->meta_value, $section_groups ) ) {
						$post->can_edit = true;
						$post->can_remove = true;
					}
				}

				// Hierarchical perm editors ignore draft/pending, allowed by default
				if(in_array($post->post_status,array('draft','pending'))) {
					$post->can_edit = true;
					$post->can_remove = ($post->post_author == $current_user);
				}

			}

		} else {

			foreach( $posts as $post ) {

				$post->can_edit = false;
				$post->can_remove = false;

				// Hierarchical perm editors ignore draft/pending, allowed by default
				if(in_array($post->post_status,array('draft','pending'))) {
					$post->can_edit = true;
					$post->can_remove = ($post->post_author == $current_user);
				}
			}

		}

	}

	return $posts;

}

/**
 * Unused -- This is the ideal filter in that it relies on current_user_can to determine the actual edit and delete capability
 *
 * The issue is that since we are bypassing WP_Query in favor of bu_navigation_get_pages, this approach winds up
 * using a lot of memory.  Also, since links are currently a non-registered custom post type, there's some extra
 * logic needed as well.
 */
function buse_bu_navigation_filter_pages_slow( $posts ) {
		if( is_array( $posts ) && count( $posts ) > 0 ) {
			foreach( $posts as $post ) {

				// Links, being a non-registered custom post type, bypass map_meta_cap and must be accounted for specially here
				if( $post->post_type == 'link') {

					$post->can_edit = BU_Group_Permissions::can_edit_section( wp_get_current_user(), $post->ID );
					$post->can_remove = $post->can_edit;

				} else {

					$post->can_edit = current_user_can( 'edit_post', $post->ID );
					$post->can_remove = current_user_can( 'delete_post', $post->ID );

				}
			}
		}
		return $posts;
}

function buse_bu_navigation_format_post( $p, $post, $has_children ) {

	$p['metadata']['post']['post_meta']['canEdit'] = $post->can_edit;
	$p['metadata']['post']['post_meta']['canRemove'] = $post->can_remove;

	if( ! isset( $p['attr']['class'] ) )
		$p['attr']['class'] = '';

	if( ! $post->can_edit ) {
		$p['attr']['class'] = ' denied';
	}

	return $p;
}