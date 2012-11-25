jQuery(function($) {

	//bulk-edit
	$('#bulk-edit #post_parent').bind('change', function(e) {
		var parent_id = $('#post_parent option:selected').val();
		if(parent_id == -1) {
			return;
		}

		var data = {
			action: 'buse_can_edit',
			post_id: parent_id
		}

		$.ajax({
			url: ajaxurl,
			data: data,
			type: 'POST',
			success: function(response) {
				if(response.can_edit == false) {
					alert("You are not able to edit the parent.");
					$('#bulk-edit #post_parent [value="-1"]').attr('selected', 'selected');
				}
			},
			error: function(response) {
			}
		});
	});

	//inline-edit
	$('#inline-edit #post_parent').bind('change', function(e) {
		var parent_id = $('#post_parent option:selected').val();
		var id = $(this).closest('tr').attr('id');
		var parts = id.split('-');
		var post_id =  parts[parts.length - 1];

		var data = {
			action: 'buse_can_move',
			post_id: post_id,
			parent_id: parent_id
		}

		$.ajax({
			url: ajaxurl,
			data: data,
			type: 'POST',
			success: function(response) {
				if(response.can_edit == false) {
					alert("You are not able to edit the parent, so you cannot place this page under the parent.");
					$('#post_parent [value="' + response.original_parent + '"]').attr('selected', 'selected');
				}
			},
			error: function(response) {
			}
		});
	});


	if(window.inlineEditPost === undefined) {
		return;
	}
	// fun hacks to deal with post_status
	inlineEditPost.pre_edit = function(id) {
		if( typeof(id) == 'object' ) {
			var post_id = this.getId(id);
		}

		var data = {
			action: 'buse_can_edit',
			post_id: post_id
		}

		$.ajax({
			url: ajaxurl,
			data: data,
			type: 'POST',
			id: id,
			success: function(response) {
				inlineEditPost.post_edit(this.id);
				var edit = '#edit-' + response.post_id;
				if(response.can_edit == true) {
					if($(edit + ' [name="_status"] [value="publish"]').length == 0) {
						$(edit + ' [name="_status"]').prepend('<option value="publish">Published</option>');
					}
				} else {
					$(edit + ' [name="_status"] [value="publish"]').remove();
				}
				$(edit + ' [name="_status"] [value="' + response.status + '"]').attr('selected', 'selected');
			},
			error: function(response) {
			}
		});
	}

	inlineEditPost.post_edit = inlineEditPost.edit;

	inlineEditPost.edit = function(id) {
		inlineEditPost.pre_edit(id);
	}
});

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