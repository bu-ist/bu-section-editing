(function($){
	if(
		(typeof bu === 'undefined' ) ||
		(typeof bu.plugins === 'undefined' ) ||
		(typeof bu.plugins.navigation === 'undefined' ) )
			return;
		
	var isEditable = function( allowed, move, instance ) {
		
		if ( instance.config.isSectionEditor ) {
			var post = move.o.data();
			var post_parent = move.np.data();

			// Can't move to top level
			if ( move.cr === -1 )
				return false;

			// Can't move a denied post
			if ( post['post_meta']['denied'] ) {
				return false;
			}
			// Can't move inside denied post
			if ( post_parent['post_meta']['denied'] ) {
				return false;
			}
		}

		return true;

	};

	bu.hooks.addFilter('moveAllowed', isEditable );

	var nodeToPost = function( post ) {

		return post;

	};

	bu.hooks.addFilter('nodeToPost', nodeToPost );


	var postToNode = function( node ) {

		return node;

	};

	bu.hooks.addFilter('postToNode', postToNode );

})(jQuery);