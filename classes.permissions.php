<?php

require_once( dirname(__FILE__) . '/admin.groups.php' );

class BU_Group_Permissions {

	const META_KEY = '_bu_section_group';

	/**
	 * Allows developers to opt-out for section editing feature
	 */
	public static function get_supported_post_types( $output = 'objects') {

		$post_types = get_post_types( array( 'show_ui' => true ), 'objects' );
		$supported_post_types = array();

		foreach( $post_types as $post_type ) {
			if( post_type_supports( $post_type->name, 'section-editing' ) ) {

				switch( $output ) {

					case 'names':
						$supported_post_types[] = $post_type->name;
						break;

					case 'objects': default:
						$supported_post_types[] = $post_type;
						break;
				}
			}

		}

		return $supported_post_types;

	}

	/**
	 * Relocated from BU_Section_Capabilities in classes.capabilities.php
	 */
	public static function can_edit_section( WP_User $user, $post_id ) {

		$user_id = $user->ID;

		if( $user_id == 0 ) return false;
		if( $post_id == 0 ) return false;

		// Get all groups for this user
		$edit_groups_o = BU_Edit_Groups::get_instance();
		$groups = $edit_groups_o->find_groups_for_user( $user_id );

		if(empty($groups)) {
			return false;
		}

		foreach( $groups as $key => $group ) {

			// This group is good, bail here
			if( self::group_can_edit( $group->id, $post_id ) ) {
				return true;
			}

		}

		// User is section editor, but not allowed for this section
		return false;

	}

	/**
	 * Update permissions for a group
	 *
	 * @param int $group_id ID of group to modify ACL for
	 * @param array $permissions Permissions, as an associative array indexed by post type
	 */
	public static function update_group_permissions( $group_id, $permissions ) {
		global $wpdb;

		if( ! is_array( $permissions ) )
			return false;

		foreach( $permissions as $post_type => $ids_by_status ) {

			if( ! is_array( $ids_by_status ) ) {
				error_log( "Unexpected value found while updating permissions: $ids_by_status" );
				continue;
			}

			// Incoming allowed posts
			$allowed_ids = isset( $ids_by_status['allowed'] ) ? $ids_by_status['allowed'] : array();

			if( ! empty( $allowed_ids ) ) {

				// Make sure we don't add allowed meta twice
				$allowed_select = sprintf("SELECT post_id FROM %s WHERE post_id IN (%s) AND meta_key = '%s' AND meta_value = '%s'",
					$wpdb->postmeta,
					implode( ',', $allowed_ids ),
					self::META_KEY,
					$group_id
					);

				$previously_allowed = $wpdb->get_col( $allowed_select );
				$additions = array_merge( array_diff( $allowed_ids, $previously_allowed ) );

				foreach( $additions as $post_id ) {

					add_post_meta( $post_id, self::META_KEY, $group_id );
				}

			}

			// Incoming restricted posts
			$denied_ids = isset( $ids_by_status['denied'] ) ? $ids_by_status['denied'] : array();

			if( ! empty( $denied_ids ) ) {

				// Select meta_id's for removal based on incoming posts
				$denied_select = sprintf("SELECT meta_id FROM %s WHERE post_id IN (%s) AND meta_key = '%s' AND meta_value = '%s'",
					$wpdb->postmeta,
					implode( ',', $denied_ids ),
					self::META_KEY,
					$group_id
					);

				$denied_meta_ids = $wpdb->get_col( $denied_select );

				// Bulk deletion
				if( ! empty( $denied_meta_ids ) ) {

					$delete_query = sprintf( "DELETE FROM $wpdb->postmeta WHERE meta_id IN (%s)", implode( ',', $denied_meta_ids ) );

					// Remove allowed status in one query
					$results = $wpdb->query( $delete_query );

					// Purge cache
					foreach( $denied_ids as $post_id ) {
						wp_cache_delete( $post_id, 'post_meta' );
					}

				}

			}

		}

	}

	public static function delete_group_permissions( $group_id ) {

		$supported_post_types = self::get_supported_post_types( 'names' );

		$meta_query = array(
			'key' => self::META_KEY,
			'value' => $group_id,
			'compare' => 'LIKE'
			);

		$args = array(
			'post_type' => $supported_post_types,
			'meta_query' => array( $meta_query ),
			'posts_per_page' => -1,
			'fields' => 'ids'
			);

		$query = new WP_Query( $args );

		foreach( $query->posts as $post_id ) {
			delete_post_meta( $post_id, self::META_KEY, $group_id );
		}

	}

	/**
	 * Can this group edit a particular post
	 */
	public static function group_can_edit( $group_id, $post_id ) {

		$allowed_groups = get_post_meta( $post_id, self::META_KEY );

		return ( is_array( $allowed_groups ) && in_array( $group_id, $allowed_groups ) ) ? true : false;

	}

	/**
	 * Query for all posts that have section editing permissions assigned for this group
	 *
	 * @uses WP_Query
	 *
	 * @param array $args an optional array of WP_Query arguments, will override defaults
	 * @return array an array of posts that have section editing permissions for this group
	 */
	public static function get_allowed_posts_for_group( $group_id, $args = array() ) {

		$defaults = array(
			'post_type' => 'page',
			'meta_key' => self::META_KEY,
			'meta_value' => $group_id,
			'posts_per_page' => -1
			);

		$args = wp_parse_args( $args, $defaults );

		$query = new WP_Query( $args );

		return $query->posts;
	}

}

/**
 * Abstract base class for post permissions editor
 */
abstract class BU_Permissions_Editor {

	protected $group;
	protected $post_type;
	protected $posts;

	public $page;
	public $found_posts;
	public $post_count;
	public $max_num_pages;

	public $format = 'html';

	/**
	 * $group can be either a BU_Edit_Group object or a group ID
	 */
	function __construct( $group, $post_type ) {

		if( is_numeric( $group ) ) {

			$group_id = intval( $group );

			$controller = BU_Edit_Groups::get_instance();

			$this->group = $controller->get( $group_id );

			// Could be a new group
			if( ! $this->group ) {

				$this->group = new BU_Edit_Group();
			}

		} else if ( $group instanceof BU_Edit_Group ) {

			$this->group = $group;

		} else {

			error_log('Not a valid group ID or object: ' . $group );
		}

		$this->post_type = $post_type;

		$this->load();

	}

	public function query( $args = array() ) {

		$defaults = array(
			'post_type' => $this->post_type,
			'post_status' => 'any',
			'posts_per_page' => $this->per_page,
			'orderby' => 'modified',
			'order' => 'DESC',
			'paged' => 1
			);

		$args = wp_parse_args( $args, $defaults );

		$query = new WP_Query( $args );

		// Parse results
		$this->posts = $query->posts;
		$this->page = $args['paged'];
		$this->found_posts = $query->found_posts;
		$this->post_count = $query->post_count;
		$this->max_num_pages = $query->max_num_pages;

		wp_reset_postdata();

	}

	abstract public function get_posts( $post_id = 0 );
	abstract public function display();

	abstract protected function load();
	abstract protected function format_post( $post, $has_children = false );
	abstract protected function get_post_markup( $p );

}

/**
 * Permissions editor for flat post types
 */
class BU_Flat_Permissions_Editor extends BU_Permissions_Editor {

	protected function load() {

		// Load user setting for posts per page on the manage groups screen
		$user = get_current_user_id();
		$per_page = get_user_meta( $user, BU_Groups_Admin::POSTS_PER_PAGE_OPTION, true );

		if ( empty ( $per_page) || $per_page < 1 ) {
			// get the default value if none is set
			$per_page = 10;
		}

		$this->per_page = $per_page;

	}

	/**
	 * Display posts using designated output format
	 */
	public function display() {

		switch( $this->format ) {

			case 'json':
				$output = $this->get_posts();
				echo json_encode( $output );
				break;

			case 'html':default:
				echo $this->get_posts();
				break;
		}

	}

	/**
	 * Get posts intended for display by permissions editors
	 */
	public function get_posts( $post_id = 0 ) {

		if( $this->format == 'json' )
			$posts = array();
		else if ( $this->format == 'html' )
			$posts = '';

		if( ! empty( $this->posts ) ) {

			$count = 0;

			if( $this->format == 'html' )
				$posts = '<ul class="perm-list flat">';

			foreach( $this->posts as $id => $post ) {

				// Format post data for permissions editor display
				$p = $this->format_post( $post );

				// Alternating table rows for prettiness
				$alt_class = $count % 2 ? '' : 'alternate';

				if( $alt_class )
					$p['attr']['class'] = $alt_class;

				// Add this post with the specified format
				switch( $this->format ) {

					case 'json':
						array_push( $posts, $p );
						break;

					case 'html': default:
						$posts .= $this->get_post_markup( $p );
						break;
				}

				$count++;

			}

			if( $this->format == 'html' )
				$posts .= "</ul>";

		} else {
			$labels = get_post_type_object( $this->post_type )->labels;
			$posts = sprintf('<ul class="perm-list flat"><li><p>%s</p></li></ul>', $labels->not_found );
		}

		return $posts;

	}

	/**
	 * Takes an array of post data formatted for permissions editor output,
	 * converts to HTML markup
	 *
	 * The format of this markup lines up with default jstree markup
	 */
	public function get_post_markup( $p ) {

		// Permission status
		$icon = "<ins class=\"{$p['data']['icon']}\"> </ins>\n";

		// Publish information
		$meta = '';
		$published_label = __( 'Published on', BUSE_TEXTDOMAIN );
		$draft_label = __( 'Draft', BUSE_TEXTDOMAIN );

		switch( $p['metadata']['post_status'] ) {

			case 'publish':
				$meta = " &mdash; $published_label {$p['metadata']['post_date']}";
				break;

			case 'draft':
				$meta = " &mdash; <em>$draft_label</em>";
				break;

		}

		// Bulk Edit Checkbox
		$checkbox = sprintf("<input type=\"checkbox\" name=\"bulk-edit[%s][%s]\" value=\"1\">",
			$this->post_type,
			$p['metadata']['post_id']
			);

		// Perm actions button
		$perm_state = $p['metadata']['editable'] ? 'denied' : 'allowed';
		$perm_label = $perm_state == 'allowed' ? __( 'Allow', BUSE_TEXTDOMAIN ) : __( 'Deny', BUSE_TEXTDOMAIN );
		$button = sprintf("<button class=\"edit-perms %s\">%s</button>", $perm_state, $perm_label );

		// Anchor
		$a = sprintf( "<a href=\"#\"><span class=\"title\">%s</span>%s%s</a>",
			$p['data']['title'],
			$meta,
			$button
		 );

		// Post list item
		$li = sprintf( "<li id=\"%s\" class=\"%s\" rel=\"%s\" data-editable=\"%s\" data-editable-original=\"%s\">%s%s%s</li>\n",
			$p['attr']['id'],
			$p['attr']['class'],
			$p['attr']['rel'],
			json_encode($p['metadata']['editable']),
			json_encode($p['metadata']['editable-original']),
			$icon,
			$checkbox,
			$a
			);

		return $li;

	}

	/**
	 * Format a single post for display by the permissions editor
	 *
	 * Data structure is jstree-friendly
	 *
	 * @todo merge with hierarchical format_post logic
	 */
	public function format_post( $post, $has_children = false ) {

		$editable = BU_Group_Permissions::group_can_edit( $this->group->id, $post->ID );
		$perm = $editable ? 'allowed' : 'denied';

		$post->post_title = empty( $post->post_title ) ? __('(no title)', BUSE_TEXTDOMAIN ) : $post->post_title;

		$p = array(
			'attr' => array(
				'id' => esc_attr( 'p' . $post->ID ),
				'rel' => esc_attr( $perm ),
				'class' => ''
			),
			'data' => array(
				'title' => esc_html( $post->post_title ),
				'icon' => 'flat-perm-icon'
			),
			'metadata' => array(
				'post_id' => $post->ID,
				'post_date' => date( get_option('date_format'), strtotime( $post->post_date ) ),
				'post_status' => $post->post_status,
				'editable' => $editable,
				'editable-original' => $editable
				)
			);

		return $p;

	}

}

/**
 * Permissions editor for hierarchical post types
 *
 * @todo now that the navigation plugin has the BU_Navigation_Tree_View class, most of this
 *  logic is redundant.  The only added complexity is the need for a "group_id" field for
 *  filtering post meta.
 *
 * @uses (depends on) BU Navigation library
 */
class BU_Hierarchical_Permissions_Editor extends BU_Permissions_Editor {

	private $child_of = 0;

	protected function load() {

		// We don't need these
		remove_filter('bu_navigation_filter_pages', 'bu_navigation_filter_pages_exclude' );
		remove_filter('bu_navigation_filter_pages', 'bu_navigation_filter_pages_external_links' );

		// But we definitely need these
		add_filter('bu_navigation_filter_pages', array( $this, 'filter_posts' ) );

	}

	// ____________________INTERFACE_________________________

	/**
	 * Custom query for hierarchical posts
	 *
	 * @uses BU Navigation plugin
	 */
	public function query( $args = array() ) {

		$defaults = array(
			'child_of' => 0,
			'post_type' => $this->post_type,
			);

		$r = wp_parse_args( $args, $defaults );

		// Search term
		// @todo not yet implemented
		if( !empty($r['s']) ) {
			if(isset($r['child_of'])) unset($r['child_of']);
			parent::query( $args );
			return;
		}

		$this->child_of = $r['child_of'];

		$section_args = array( 'direction' => 'down', 'post_types' => $r['post_type'] );

		// Don't load the whole tree at once
		if( $this->child_of == 0 ) $section_args['depth'] = 1;
		else $section_args['depth'] = 0;

		// Make sure navigation plugin functions are available before querying
		if( ! function_exists('bu_navigation_get_pages') ) {
			$this->posts = array();
			error_log('BU Navigation Plugin must be activated in order for hierarchical permissions editors to work');
			return false;
		}

		// Get post IDs for this section
		$sections = bu_navigation_gather_sections( $this->child_of, $section_args);

		// Fetch posts
		$page_args = array(
			'sections' => $sections,
			'post_types' => $r['post_type'],
			'suppress_urls' => true
			);

		$root_pages = bu_navigation_get_pages( $page_args );
		$this->posts = bu_navigation_pages_by_parent($root_pages);

	}

	/**
	 * Display posts using designated output format
	 */
	public function display() {

		switch( $this->format ) {

			case 'json':
				$posts = $this->get_posts( $this->child_of );
				echo json_encode( $posts );
				break;

			case 'html': default:
				echo $this->get_posts( $this->child_of );
				break;

		}

	}

	/**
	 * Get posts intended for display by permissions editors
	 */
	public function get_posts( $post_id = 0 ) {

		if( array_key_exists( $post_id, $this->posts ) && ( count( $this->posts[$post_id] ) > 0 ) )
			$posts = $this->posts[$post_id];
		else
			$posts = array();

		// Initialize output var depending on format
		$output = null;

		switch( $this->format ) {

			case 'json':
				$output = array();
				break;

			case 'html':default:
				$output = '';
				break;
		}

		// Loop through posts recursively
		foreach( $posts as $post ) {

			$has_children = array_key_exists( $post->ID, $this->posts );

			// Format post data
			$p = $this->format_post( $post, $has_children );

			// Maybe fetch descendents
			if( $has_children ) {

				// Default to closed with children
				$p['state'] = 'closed';

				if( $this->child_of > 0 ) {

					$post_id = $post->ID;
					$descendents = $this->get_posts( $post_id );

					if( !empty( $descendents ) ) {
						$p['children'] = $descendents;
					}

				} else {
					$perm = $post->editable ? 'allowed' : 'denied';
					// Let users known descendents have not yet been loaded
					$p['attr']['rel'] = $perm . '-desc-unknown';

				}

			}

			// Return post in correct format
			switch( $this->format ) {

				case 'json':
					array_push( $output, $p );
					break;

				case 'html': default:
					$output .= get_post_markup( $p );
					break;

			}

		}

		return $output;

	}

	/**
	 * Takes an array of post data formatted for permissions editor output,
	 * converts to HTML markup
	 *
	 * The format of this markup lines up with default jstree markup
	 */
	protected function get_post_markup( $p ) {

		$a = sprintf('<a href="#">%s</a>', $p['data'] );

		$descendents = !empty($p['children']) ? sprintf("<ul>%s</ul>\n", $p['children'] ) : '';

		$markup = sprintf("<li id=\"%s\" class=\"%s\" rel=\"%s\" data-editable=\"%s\" data-editable-original=\"%s\">%s %s</li>\n",
			$p['attr']['id'],
			$p['attr']['class'],
			$p['attr']['rel'],
			$p['metadata']['editable'],
			$p['metadata']['editable-original'],
			$a,
			$descendents
			);

		return $markup;
	}

	/**
	 * Format a single post for display by the permissions editor
	 *
	 * Data structure is jstree-friendly
	 *
	 * @todo merge with flat format_post logic
	 */
	protected function format_post( $post, $has_children = false ) {

		$title = isset( $post->navigation_label ) ? $post->navigation_label : $post->post_title;
		$classes = ( $has_children ) ? 'jstree-closed' : 'jstree-default';
		$perm = $post->editable ? 'allowed' : 'denied';

		$p = array(
			'attr' => array(
				'id' => esc_attr( 'p' . $post->ID ),
				'rel' => esc_attr( $perm ),
				'class' => esc_attr( $classes )
			),
			'data' => array(
				'title' => esc_html( $title )
				),
			'metadata' => array(
				'editable' => $post->editable,
				'editable-original' => $post->editable
				),
			'children' => null
			);

		return $p;

	}


	//__________________NAVIGATION FILTERS______________________

	/**
	 * Add custom section editable properties to the post objects returned by bu_navigation_get_pages()
	 */
	public function filter_posts( $posts ) {
		global $wpdb;

		if( ( is_array( $posts ) ) && ( count( $posts ) > 0 ) ) {

			/* Gather all group post meta in one shot */
			$ids = array_keys($posts);
			$query = sprintf("SELECT post_id, meta_value FROM %s WHERE meta_key = '%s' AND post_id IN (%s) AND meta_value = '%s'", $wpdb->postmeta, BU_Group_Permissions::META_KEY, implode(',', $ids), $this->group->id );
			$group_meta = $wpdb->get_results($query, OBJECT_K); // get results as objects in an array keyed on post_id
			if (!is_array($group_meta)) $group_meta = array();

			// Append permissions to post object
			foreach( $posts as $post ) {

				$post->editable = false;

				if( array_key_exists( $post->ID, $group_meta ) ) {
					$perm = $group_meta[$post->ID];

					if( $perm->meta_value === (string) $this->group->id ) {
						$post->editable = true;
					}

				}

			}

		}

		return $posts;

	}

}