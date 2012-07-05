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
	abstract public function filter_posts( $posts );

	public function query_posts( $args = array() ) {

		// Setup posts to render
		$defaults = array(
			'post_type' => $this->post_type,
			'post_status' => 'any',
			'posts_per_page' => get_option('posts_per_page'),
			'orderby' => 'modified',
			'order' => 'DESC',
			);

		$args = wp_parse_args( $args, $defaults );

		error_log('Query args: ' . print_r($args,true) );

		$query = new WP_Query( $args );

		$this->posts = $query->posts;

		wp_reset_postdata();

	}

	abstract public function display_posts_list();

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

}

/**
 * Permissions editor for non-hierarchical post types
 * 
 */ 
class BU_Flat_Permissions_Editor extends BU_Permissions_Editor {

	protected function load() {

		add_filter( 'the_posts', array( &$this, 'filter_posts' ) );

	}

	public function display_posts_list() {

		$count = 0;

		if( ! empty( $this->posts ) ) {

			echo "<ul id=\"{$this->post_type}-perm-list\" class=\"perm-list flat\">\n";

			foreach( $this->posts as $id => $post ) {

				// HTML checkbox
				$is_checked = ( $post->perm === 'allowed' ) ? 'checked="checked"' : '';
				$input = sprintf( "<input type=\"checkbox\" name=\"group[perms][%s][%s]\" value=\"allowed\" %s/>",
					$this->post_type,
					$post->ID,
					$is_checked
					 );

				// Permission status
				$icon = "<ins class=\"flat-perm-icon\"> </ins>\n";

				// Date info
				if( $post->post_status == 'publish' )
					$post_status = " - Published " . $post->post_date;
				else if( $post->post_status == 'draft' )
					$post_status = " - <em>Draft</em>";


				// Alternating backgrounds
				$odd_even = $count % 2;
				$li_class = $odd_even ? 'odd' : 'even';

				$li = sprintf( "<li id=\"p%s\" class=\"%s\" rel=\"%s\">%s <a href=\"#\">%s %s %s</a></li>\n", 
					$post->ID,
					$li_class,
					$post->perm,
					$input,
					$icon,
					$post->post_title,
					$post_status
					);

				echo $li;

				$count++;

			}
			
			echo "</ul>\n";

		}
		
	}

	public function filter_posts( $posts ) {

		foreach( $posts as $post ) {

			$post->perm = ( $this->group->can_edit( $post->ID ) ) ? 'allowed' : 'denied';

		}

		return $posts;
	}

}

/**
 * Isolates functionality related to generating group permission editor
 * 
 */ 
class BU_Hierarchical_Permissions_Editor extends BU_Permissions_Editor {

	private $start_post = 0;
	private $current_post = 0;

	protected function load() { }

	// ____________________INTERFACE_________________________

	public function query_posts( $args = array() ) {

		$defaults = array(
			'child_of' => 0,
			'post_type' => $this->post_type,
			);

		$r = wp_parse_args( $args, $defaults );

		// Search term
		// @todo not yet implemented
		if( !empty($r['s']) ) {
			if(isset($r['child_of'])) unset($r['child_of']);
			parent::query_posts( $args );
			return;
		}

		$this->start_post = $this->current_post = $r['child_of'];

		// We don't need these
		remove_filter('bu_navigation_filter_pages', 'bu_navigation_filter_pages_exclude' );

		// But we definitely need these
		add_filter('bu_navigation_filter_pages', array( &$this, 'filter_posts' ) );
		add_filter('bu_navigation_filter_fields', array( &$this, 'filter_post_fields' ) );
		
		$section_args = array( 'direction' => 'down', 'post_types' => $r['post_type'] );

		// Don't load the whole tree at once
		if( $this->start_post == 0 ) $section_args['depth'] = 1;
		else $section_args['depth'] = 0;

		error_log(print_r($section_args,true));

		// Get post IDs for this section
		$sections = bu_navigation_gather_sections( $this->start_post, $section_args);

		// Fetch posts
		$page_args = array(
			'sections' => $sections,
			'post_types' => $r['post_type'],
			'suppress_urls' => true
			);

		$root_pages = bu_navigation_get_pages( $page_args );
		$this->posts = bu_navigation_pages_by_parent($root_pages);
	
		// Remove our custom filters 
		remove_filter('bu_navigation_filter_pages', array( &$this, 'filter_posts' ) );
		remove_filter('bu_navigation_filter_fields', array( &$this, 'filter_post_fields' ) );
	
	}

	public function display_posts_list() {

		if( array_key_exists( $this->current_post, $this->posts ) && ( count( $this->posts[$this->current_post] ) > 0 ) )
			$posts = $this->posts[$this->current_post];
		else
			$posts = array();

		foreach( $posts as $post ) {

			$has_children = array_key_exists( $post->ID, $this->posts );
			$title = isset( $post->navigation_label ) ? $post->navigation_label : $post->post_title;
			$classes = ( $has_children ) ? 'jstree-closed' : 'jstree-default';
			$rel = $post->perm;

?>
	<li id="p<?php echo $post->ID; ?>" class="<?php echo $classes; ?>" data-perm="<?php echo $post->perm; ?>" rel="<?php echo $rel; ?>">
		<a href="#"><?php echo $title; ?></a>
		<?php 
		if( $has_children && $this->start_post != 0 ): 
			$this->current_post = $post->ID;
		?>
		<ul>
			<?php $this->display_posts_list(); ?>
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
			$query = sprintf("SELECT post_id, meta_value FROM %s WHERE meta_key = '%s' AND post_id IN (%s) AND meta_value = '%s'", $wpdb->postmeta, BU_Edit_Group::META_KEY, implode(',', $ids), $this->group->id );
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