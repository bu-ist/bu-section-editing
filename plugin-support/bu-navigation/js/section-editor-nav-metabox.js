jQuery(document).ready(function ($) {

	// Navigation attributes modal post tree
	var navMetabox;

	if ((typeof bu !== 'undefined') &&
		(typeof bu.plugins !== 'undefined') &&
		(typeof bu.plugins.navigation !== 'undefined') &&
		(typeof bu.plugins.navigation.metabox !== 'undefined')) {
			navMetabox = bu.plugins.navigation.metabox;
		}

	if (typeof navMetabox !== 'undefined') {

		// Extra check for section editors
		if (navMetabox.settings.isSectionEditor) {
			var modal = navMetabox.data.modalTree;

			modal.listenFor('locationUpdated', function (post) {
				var parent = modal.tree.getPost(post.post_parent);

				// Update text for publish button
				if (parent) {
					if (parent.post_meta.canEdit) {
						$('#post #publish').val('Publish');
					} else {
						$('#post #publish').val('Submit for Review');
					}
				} else {
					// Moved to top level location
					$('#post #publish').val('Submit for Review');
				}
			});
		}
	}
});