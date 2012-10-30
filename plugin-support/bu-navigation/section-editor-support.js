(function($){
	if(
		(typeof bu === 'undefined' ) ||
		(typeof bu.plugins === 'undefined' ) ||
		(typeof bu.plugins.navigation === 'undefined' ) )
			return;
	
	/**
	 * Make sure that current user has sufficient capabilities to
	 * move node to new location
	 */	
	var isEditable = function (allowed, move, instance) {
		
		if ( instance.config.isSectionEditor ) {
			var post = move.o.data();
			var post_parent = move.np.data();

			// Can't move to top level
			if ( move.cr === -1 )
				return false;

			// Can't move a denied post
			if ( post['post_meta']['canEdit'] ) {
				return false;
			}
			// Can't move inside denied post
			if ( post_parent['post_meta']['canEdit'] ) {
				return false;
			}
		}

		return true;

	};

	bu.hooks.addFilter('moveAllowed', isEditable );

	/**
	 * Make sure that current user has sufficient capabilities to
	 * execute context menu items
	 */
	var filterNavmanContextItems = function (items, node) {
		var post = node.data();

		if( ! post['post_meta']['canEdit'] && items['edit'] )
			delete items['edit'];

		if( ! post['post_meta']['canRemove'] && items['remove'] )
			delete items['remove'];

		return items;
	}

	bu.hooks.addFilter('navmanContextItems', filterNavmanContextItems );

	var nodeToPost = function( post ) {

		return post;

	};

	bu.hooks.addFilter('nodeToPost', nodeToPost );


	var postToNode = function( node ) {

		return node;

	};

	bu.hooks.addFilter('postToNode', postToNode );

})(jQuery);