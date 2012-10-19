(function($){
	if(
		(typeof bu === 'undefined' ) ||
		(typeof bu.plugins === 'undefined' ) ||
		(typeof bu.plugins.navigation === 'undefined' ) )
			return;

	var s = bu.plugins.navigation.settings;

	var isEditable = function( allowed, move ) {

		if ( s.isSectionEditor ) {

			// Can't move to top level
			if ( move.cr === -1 )
				return false;

			// Can't move a denied post
			if ( move.o.data('denied') ) {
				return false;
			}
			// Can't move inside denied post
			if ( move.np.data('denied') ) {
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