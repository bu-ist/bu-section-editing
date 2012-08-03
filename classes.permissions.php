<?php

class BU_Group_Permissions {

	const META_KEY = '_bu_section_group';

	/**
	 * Allows developers to opt-out for section editing feature
	 */ 
	public static function get_supported_post_types( $output = 'objects') {

		$post_types = get_post_types( array( 'public' => true ), 'objects' );
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
	 * Update permissions for a group
	 * 
	 * @param int $group_id ID of group to modify ACL for
	 * @param array $permissions Permissions, as an associative array indexed by post type
	 */ 
	public static function update_group_permissions( $group_id, $permissions ) {
		global $wpdb;

		if( ! is_array( $permissions ) )
			return false;

		foreach( $permissions as $post_type => $new_perms ) {

			if( ! is_array( $new_perms ) ) {
				error_log( "Unexpected value found while updating permissions: $new_perms" );
				continue;
			}

			// Incoming allowed posts
			$allowed_ids = array_keys( $permissions[$post_type], 'allowed' );

			if( ! empty( $allowed_ids ) ) {

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
			$denied_ids = array_keys( $permissions[$post_type], 'denied' );

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

					$denied_meta_delete = sprintf("DELETE FROM %s WHERE meta_id IN (%s)",
						$wpdb->postmeta,
						implode(',', $denied_meta_ids )
						);

					// Remove allowed status in one query
					$results = $wpdb->query( $wpdb->prepare( $denied_meta_delete ) );

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
	public function group_can_edit( $group_id, $post_id ) {
		
		$allowed_groups = get_post_meta( $post_id, self::META_KEY );

		return in_array( $group_id, $allowed_groups ) ? true : false;
	
	}

	/**
	 * Query for all posts that have section editing permissions assigned for this group
	 * 
	 * @uses WP_Query
	 *
	 * @param array $args an optional array of WP_Query arguments, will override defaults
	 * @return array an array of posts that have section editing permissions for this group
	 */ 
	public function get_allowed_posts_for_group( $group_id, $args = array() ) {

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
			'posts_per_page' => -1, // @todo get_option('posts_per_page') when pagination is implemented
			'orderby' => 'modified',
			'order' => 'DESC',
			);

		$args = wp_parse_args( $args, $defaults );

		$query = new WP_Query( $args );

		$this->posts = $query->posts;

		wp_reset_postdata();

	}

	abstract public function get_posts( $post_id = 0 );
	abstract public function filter_posts( $posts );
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

		add_filter( 'the_posts', array( &$this, 'filter_posts' ) );

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

		$posts = array();

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

		switch( $p['metadata']['post_status'] ) {

			case 'publish':
				$meta = ' &mdash; Published on ' . $p['metadata']['post_date'];
				break;

			case 'draft':
				$meta = ' &mdash; <em>Draft</em>';
				break;

		}

		// Anchor
		$a = sprintf( "<a href=\"#\">%s %s%s</a>",
			$icon,
			$p['data']['title'],
			$meta
		 );

		// Post list item
		$li = sprintf( "<li id=\"%s\" class=\"%s\" rel=\"%s\">%s</li>\n", 
			$p['attr']['id'],
			$p['attr']['class'],
			$p['attr']['rel'],
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

		$title = isset( $post->navigation_label ) ? $post->navigation_label : $post->post_title;

		$p = array(
			'attr' => array( 
				'id' => esc_attr( 'p' . $post->ID ), 
				'rel' => esc_attr( $post->perm ),
				'class' => '',
				'data-perm' => esc_attr( $post->perm )
			),
			'data' => array(
				'title' => esc_html( $title ),
				'icon' => 'flat-perm-icon'
			),
			'metadata' => array(
				'post_id' => $post->ID,
				'post_date' => date( get_option('date_format'), strtotime( $post->post_date ) ),
				'post_status' => $post->post_status
				)
			);

		return $p;

	}

	/**
	 * Add permissions fields to post object
	 */ 
	public function filter_posts( $posts ) {

		foreach( $posts as $post ) {
			$post->perm = ( BU_Group_Permissions::group_can_edit( $this->group->id, $post->ID ) ) ? 'allowed' : 'denied';
		}

		return $posts;

	}

}

/**
 * Permissions editor for hierarchical post types 
 * 
 * @uses (depends on) BU Navigation library
 */ 
class BU_Hierarchical_Permissions_Editor extends BU_Permissions_Editor {

	private $child_of = 0;

	protected function load() {

		// We don't need these
		remove_filter('bu_navigation_filter_pages', 'bu_navigation_filter_pages_exclude' );

		// But we definitely need these
		add_filter('bu_navigation_filter_pages', array( &$this, 'filter_posts' ) );
		add_filter('bu_navigation_filter_fields', array( &$this, 'filter_post_fields' ) );

	}

	// ____________________INTERFACE_________________________

	/**
	 * Custom query for hierarchical posts
	 * 
	 * @uses BU Navigation library
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

					// Let users known descendents have not yet been loaded
					$p['attr']['rel'] = $post->perm . '-desc-unknown';

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

		$markup = sprintf("<li id=\"%s\" class=\"%s\" rel=\"%s\" data-perm=\"%s\">%s %s</li>\n",
			$p['attr']['id'],
			$p['attr']['class'],
			$p['attr']['rel'],
			$p['attr']['data-perm'],
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

		$p = array(
			'attr' => array( 
				'id' => esc_attr( 'p' . $post->ID ), 
				'rel' => esc_attr( $post->perm ),
				'class' => esc_attr( $classes ),
				'data-perm' => esc_attr( $post->perm )
			),
			'data' => array(
				'title' => esc_html( $title )
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

				$post->perm = 'denied';

				if( array_key_exists( $post->ID, $group_meta ) ) {
					$perm = $group_meta[$post->ID];

					if( $perm->meta_value === (string) $this->group->id ) {
						$post->perm = 'allowed';
					}

				}

			}

		}

		return $posts;

	}

	/**
	 * Get only the post fields that we need
	 */ 
	public function filter_post_fields( $fields ) {

		$fields = array(
			'ID',
			'post_title',
			'post_type',
			'post_parent'
			);

		return $fields;

	}

}