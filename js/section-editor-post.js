jQuery(function($) {
	var current_parent = $('#post [name="parent_id"]').val();
	$('#bu-page-parent').bind('nodeSelected', function(e){
		var scrollingTree = $(e.target).data('scrollingTree');
		var parent_id = scrollingTree.getSelection();
		if(current_parent == parent_id) {
			return;
		}

		var data = {
			action: 'buse_can_move',
			parent_id: parent_id,
			post_id: $('#post [name="post_ID"]').val()
		}

		$.ajax({
			url: ajaxurl,
			data: data,
			type: 'POST',
			success: function(response) {
				if(response.can_edit == false) {
					if(response.status == 'publish') {
						alert("You cannot place this page there.");
						$('#post [name="parent_id"]').val(response.original_parent);
						scrollingTree.selectNode(response.original_parent, true);
					} else {
						$('#post #publish').val('Submit for Review');
						$('.misc-pub-section.curtime').hide();
					}
					return;
				}

				if(response.can_edit == true) {
					if(response.status != 'publish') {
						$('#post #publish').val('Publish');
						$('.misc-pub-section.curtime').show();
					}
				}
			},
			error: function(response) {
			}
		});
	});

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
				if(response.can_edit == true) {
					$('#edit-' + response.post_id + ' [name="_status"]').prepend('<option value="publish">Published</option>');
				} else {
					$('#edit-' + response.post_id +  ' [name="_status"] [value="publish"]').remove();
				}
				$('#edit-' + response.post_id + ' [name="_status"] [value="' + response.status + '"]').attr('selected', 'selected');
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




