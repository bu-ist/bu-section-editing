<?php
$id = intval(substr($_POST['id'], 1));
$post_type = isset($_GET['post_type']) ? $_GET['post_type'] : 'page,link';
$post_types = explode(',', $post_type);

$response = array();

$section_args = array('direction' => 'down', 'depth' => 0, 'sections' => array($id), 'post_types' => $post_types);
$sections = bu_navigation_gather_sections(0, $section_args);

/* remove default page filter and add our own */
remove_filter('bu_navigation_filter_pages', 'bu_navigation_filter_pages_exclude');
add_filter('bu_navigation_filter_pages', 'bu_navman_filter_pages');

$pages = bu_navigation_get_pages(array('sections' => $sections, 'post_types' => $post_types));

/* remove our page filter and add back in the default */
remove_filter('bu_navigation_filter_pages', 'bu_navman_filter_pages');
add_filter('bu_navigation_filter_pages', 'bu_navigation_filter_pages_exclude');

$pages_by_parent = bu_navigation_pages_by_parent($pages);

if ((is_array($pages_by_parent[$id])) && (count($pages_by_parent[$id]) > 0))
{
	foreach ($pages_by_parent[$id] as $page)
	{
		if (!isset($page->navigation_label)) $page->navigation_label = apply_filters('the_title', $page->post_title);

		$title = $page->navigation_label;
		
		$p = array(
			'attr' => array('id' => sprintf('p%d', $page->ID)),
			'data' => $title
			);
			
		$classes = array(); // css classes
		
		if (isset($pages_by_parent[$page->ID]) && (is_array($pages_by_parent[$page->ID])) && (count($pages_by_parent[$page->ID]) > 0))
		{
			$p['state'] = 'closed';
			$p['attr']['rel'] = 'folder';
			
			$children = bu_navman_get_children($page->ID, $pages_by_parent);
			
			if (count($children) > 0) $p['children'] = $children;
		}
		
		if (!array_key_exists('state', $p))
		{
			$p['attr']['rel'] = ($page->post_type == 'link' ? $page->post_type : 'page');
		}
		
		// we need to ignore "link" post types, otherwise the interface right click on links do not work.
		if (isset($page->excluded) and $page->excluded and $page->post_type != 'link') 
		{
			$p['attr']['rel'] .= '_excluded';
			array_push($classes, 'excluded');
		}
		
		if ($page->restricted)
		{
			$p['attr']['rel'] .= '_restricted';
			array_push($classes, 'restricted');
		}
		
		$p['attr']['class'] = implode(' ', $classes);
		
		array_push($response, $p);
	}
}

echo json_encode($response);
?>