/*
 * BU Navigation plugin - Section Editor integration
 *
 * This file ties in to hooks generated by the navigation plugin scripts
 * while controlling page movements via the "Edit Order" and "Navigation Attributes"
 * metabox.
 *
 * It is responsible for enforcing section editing restrictions.
 */
(function($){
	if(
		(typeof bu === 'undefined' ) ||
		(typeof bu.plugins === 'undefined' ) ||
		(typeof bu.plugins.navigation === 'undefined' ) )
			return;

	/*
	 * Make sure that current user has sufficient capabilities to
	 * move post to new location
	 */
	var isEditable = function (allowed, move, tree) {

		if (tree.config.isSectionEditor) {
			var post = move.o.data('post');
			var post_parent = move.np.data('post');

			// Section editing restrictions only affect published content
			if (post['post_status'] !== 'publish' && post['post_type'] !== tree.config.linkPostType) {
				return allowed;
			}

			// Can't move to top level
			if (move.cr === -1) {
				return false;
			}
			// Can't move a denied post
			if (!post['post_meta']['canEdit']) {
				return false;
			}
			// Can't move inside denied post
			if (!post_parent || !post_parent['post_meta']['canEdit']) {
				return false;
			}
		}

		return allowed;

	};

	bu.hooks.addFilter('moveAllowed', isEditable);

	var preInsertPost = function (post, parent) {

		post.post_meta.canEdit = true;
		post.post_meta.canRemove = true;

		return post;
	};

	bu.hooks.addFilter('preInsertPost', preInsertPost);

	/*
	 * Prevent the Navman interface from showing the "Add a Link" button
	 * when the current selection is not within an editable section
	 */
	var canAddLink = function (allowed, selection, tree) {
		if (!selection) {
			return false;
		}

		return allowed;
	};

	bu.hooks.addFilter('navmanCanAddLink', canAddLink);

	/*
	 * Make sure that current user has sufficient capabilities to
	 * execute options menu items
	 */
	var filterNavmanOptionsMenuItems = function (items, node) {
		var post = node.data('post');

		if( ! post['post_meta']['canEdit'] && items['edit'] )
			delete items['edit'];

		if( ! post['post_meta']['canRemove'] && items['remove'] )
			delete items['remove'];

		return items;
	}

	bu.hooks.addFilter('navmanOptionsMenuItems', filterNavmanOptionsMenuItems);

})(jQuery);