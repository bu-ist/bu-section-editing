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
	 * Allows developers to opt-in for section editing feature
	 */ 
	public static function get_supported_post_types() {

		$post_types = get_post_types( array( 'public' => true ), 'objects' );
		$supported_post_types = array();

		foreach( $post_types as $post_type ) {
			if( post_type_supports( $post_type->name, 'section-editing' ) )
				$supported_post_types[] = $post_type;
		}

		return $supported_post_types;

	}

}

/**
 * @todo implement flat permissions editor
 */ 
class BU_Flat_Permissions_Editor extends BU_Permissions_Editor {

	protected function load() {

	}

	public function render() {
		echo '<p>Flat permissions editor: <br>Coming soon to a BU Section Editing Plugin near you...</p>';
		
	}
}

/**
 * Isolates functionality related to generating group permission editor
 * 
 * @todo bu-navigation should not be required ... need to investigate alternatives
 */ 
class BU_Hierarchical_Permissions_Editor extends BU_Permissions_Editor {

	protected function load() {

		// Navigation filters 
		add_filter('bu_navigation_filter_pages', array( &$this, 'filter_posts' ) );
		add_filter('bu_navigation_filter_fields', array( &$this, 'filter_post_fields' ) );

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

		$section_args = array( 'direction' => 'down', 'post_types' => $this->post_type );

		// Force a depth of 1 for the initial load -- keeps the initial page load manageable
		if( $child_of == 0 ) $section_args['depth'] = 1;
		else $section_args['depth'] = 0;

		// Get root pages
		$sections = bu_navigation_gather_sections( $child_of, $section_args);

		$page_args = array(
			'sections' => $sections,
			'post_types' => $this->post_type,
			'suppress_urls' => true
			);

		$root_pages = bu_navigation_get_pages( $page_args );
		$pages_by_parent = bu_navigation_pages_by_parent($root_pages);

		// Display posts (recursively)
		$this->display_posts( $child_of, $pages_by_parent );
	
	}

	public function display_posts( $parent_id, $pages_by_parent ) {

		if( array_key_exists( $parent_id, $pages_by_parent ) && ( count( $pages_by_parent[$parent_id] ) > 0 ) )
			$posts = $pages_by_parent[$parent_id];
		else
			$posts = array();

		foreach( $posts as $post ) {

			$has_children = array_key_exists( $post->ID, $pages_by_parent );
			$classes = '';
			$types = '';

			if( $has_children ) {
				$classes = 'jstree-closed';
				$types = 'default';

				if( $post->section_editable ) {
					$types = 'section_editable';
				} else if ( $this->has_editable_child( $post->ID, $pages_by_parent ) ) {
					$types = 'section_children_editable';
				} else {
					$types = 'section_restricted';
				}
			} else {
				if( $post->section_editable ) {
					$types = 'post_editable';
				} else {
					$types = 'post_restricted';
				}
			}

?>
	<li id="p<?php echo $post->ID; ?>" class="<?php echo $classes; ?>" rel="<?php echo $types; ?>">
		<a href="#"><?php echo $post->post_title; ?></a>
		<?php if( $has_children && $parent_id != 0 ): ?>
		<ul>
			<?php $this->display_posts( $post->ID, $pages_by_parent ); ?>
		</ul>
	<?php endif; ?>
	</li>
<?php
		}
	}

	public function has_editable_child( $id, $posts ) {
		$children = $posts[$id];

		foreach( $children as $child ) {
			if( $child->section_editable )
				return true;
		}
		return false;
	}

	//__________________NAVIGATION FILTERS______________________

	/**
	 * Add custom section editable properties to the post objects returned by bu_navigation_get_pages()
	 */ 
	public function filter_posts( $posts ) {

		if( ( is_array( $posts ) ) && ( count( $posts ) > 0 ) ) {

			// Get post ids that this group can edit
			$editable_ids = $this->group->get_posts( array( 'post_type' => $this->post_type, 'fields' => 'ids' ) );

			// Append property to post object
			foreach( $posts as $post ) {

				if( in_array( $post->ID, $editable_ids ) )
					$post->section_editable = true;
				else
					$post->section_editable = false;

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