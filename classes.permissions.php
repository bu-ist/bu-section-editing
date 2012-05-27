<?php

/**
 * Abstract base class for post permissions editor
 */ 
abstract class BU_Permissions_Editor {

	protected $group;
	protected $post_type;

	protected $per_page;
	protected $posts;

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

	abstract protected function load();

	abstract public function render();

	/**
	 * Allows developers to opt-out for section editing feature
	 */ 
	public static function get_supported_post_types( $output = 'objects') {

		$post_types = get_post_types( array( 'public' => true ), 'objects' );
		$supported_post_types = array();

		foreach( $post_types as $post_type ) {
			// @todo temporarily disabled flat post types for alpha release
			if( post_type_supports( $post_type->name, 'section-editing' ) && $post_type->hierarchical )
				$supported_post_types[] = ( $output == 'objects' ) ? $post_type : $post_type->name;
		}

		return $supported_post_types;

	}

}

/**
 * @todo implement flat permissions editor
 */ 
class BU_Flat_Permissions_Editor extends BU_Permissions_Editor {

	protected function load() {

		// Setup posts to render
		$query_args = array(
			'post_type' => $this->post_type,
			'post_status' => 'any',		// true?
			'posts_per_page' => -1, 	// for now, eventually we'll make the river flow
			'orderby' => 'modified',
			'order' => 'DESC'
			);

		$query = new WP_Query( $query_args );

		$this->posts = $query->posts;

		wp_reset_postdata();

	}

	public function render() {

		if( ! empty( $this->posts ) ) {

			echo "<ul id=\"{$this->post_type}-perm-list\" class=\"perm-list flat\">\n";

			/* @todo -- implement this as a checkbox for no-js */

			foreach( $this->posts as $id => $post ) {

				// Permission status
				$perm = "<span id=\"{$this->post_type}-{$post->ID}-perm\" class=\"perm-status post_restricted\"></span>\n";

				// Date info
				if( $post->post_status == 'publish' )
					$published = " - Published " . $post->post_date;
				else if( $post->post_status == 'draft' )
					$published = " - <em>Draft</em>";

				// Output
				echo "<li>$perm{$post->post_title}$published</li>";

			}
			
			echo "</ul>\n";

		}
		
	}

}

/**
 * Isolates functionality related to generating group permission editor
 * 
 * @todo bu-navigation should not be required ... need to investigate alternatives
 */ 
class BU_Hierarchical_Permissions_Editor extends BU_Permissions_Editor {

	protected function load() {

		

	}

	// ____________________INTERFACE_________________________

	/**
	 * For investigation of sans bu-navigation approach to hierarchical post display
	 */ 
	public function render_alternate( $child_of = 0 ) {

		$args = array(
			'post_type' => $this->post_type,
			'post_parent' => $child_of,
			'posts_per_page' => -1,
			'fields' => 'id=>parent'
			);

		$query = new WP_Query( $args );

	}

	public function render( $child_of = 0 ) {

		// Navigation filters
		remove_filter('bu_navigation_filter_pages', 'bu_navigation_filter_pages_exclude' );

		add_filter('bu_navigation_filter_pages', array( &$this, 'filter_posts' ) );
		add_filter('bu_navigation_filter_fields', array( &$this, 'filter_post_fields' ) );
		
		$section_args = array( 'direction' => 'down', 'post_types' => $this->post_type );

		// If we're looking for the first level, limit to 
		if( $child_of == 0 ) $section_args['depth'] = 1;
		else $section_args['depth'] = 0; // fetch all levels otherwise

		// Get root pages
		$sections = bu_navigation_gather_sections( $child_of, $section_args);

		$page_args = array(
			'sections' => $sections,
			'post_types' => $this->post_type,
			'suppress_urls' => true
			);

		$root_pages = bu_navigation_get_pages( $page_args );
		$pages_by_parent = bu_navigation_pages_by_parent($root_pages);

		$parent_state = 'denied';

		// We are not the root page
		if( $child_of != 0 ) {

			// We need the patriarch's setting here to start
			$root_id = $pages_by_parent[$child_of][0]->post_parent;
			$perms = get_post_meta( $root_id, BU_Edit_Group::META_KEY );

			if( in_array( $this->group->id . BU_Edit_Group::SUFFIX_ALLOWED, $perms ) )
				$parent_state = 'allowed';

		}

		// Display posts (recursively)
		$this->display_posts( $child_of, $pages_by_parent, $parent_state );
	
		// Navigation filters 
		remove_filter('bu_navigation_filter_pages', array( &$this, 'filter_posts' ) );
		remove_filter('bu_navigation_filter_fields', array( &$this, 'filter_post_fields' ) );
	
	}

	public function display_posts( $parent_id, $pages_by_parent, $parent_state = 'denied' ) {

		if( array_key_exists( $parent_id, $pages_by_parent ) && ( count( $pages_by_parent[$parent_id] ) > 0 ) )
			$posts = $pages_by_parent[$parent_id];
		else
			$posts = array();

		foreach( $posts as $post ) {

			$has_children = array_key_exists( $post->ID, $pages_by_parent );
			$title = isset( $post->navigation_label ) ? $post->navigation_label : $post->post_title;
			$classes = ( $has_children ) ? 'jstree-closed' : 'jstree-default';
			$rel = $post->perm ? $post->perm : $parent_state;

?>
	<li id="p<?php echo $post->ID; ?>" class="<?php echo $classes; ?>" data-perm="<?php echo $post->perm; ?>" rel="<?php echo $rel; ?>">
		<a href="#"><?php echo $title; ?></a>
		<?php if( $has_children && $parent_id != 0 ): ?>
		<ul>
			<?php $this->display_posts( $post->ID, $pages_by_parent, $rel ); ?>
		</ul>
	<?php endif; ?>
	</li>
<?php
		}
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
			$query = sprintf("SELECT post_id, meta_value FROM %s WHERE meta_key = '%s' AND post_id IN (%s) AND meta_value LIKE '%%%s%%'", $wpdb->postmeta, BU_Edit_Group::META_KEY, implode(',', $ids), $this->group->id );
			$group_meta = $wpdb->get_results($query, OBJECT_K); // get results as objects in an array keyed on post_id
			if (!is_array($group_meta)) $group_meta = array();

			// Append permissions to post object
			foreach( $posts as $post ) {

				$post->perm = '';

				if( array_key_exists( $post->ID, $group_meta ) ) {
					$perm = $group_meta[$post->ID];

					if( $perm->meta_value === (string) $this->group->id . BU_Edit_Group::SUFFIX_DENIED ) {
						$post->perm = 'denied';
					}

					if( $perm->meta_value === (string) $this->group->id . BU_Edit_Group::SUFFIX_ALLOWED ) {
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