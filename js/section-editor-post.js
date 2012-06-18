jQuery(function($) {
	var current_parent = $('#post [name="parent_id"]').val();
	$('#bu-page-parent').bind('nodeSelected', function(e){
		var scrollingTree = $(e.target).data('scrollingTree');
		var parent_id = scrollingTree.getSelection();
		if(current_parent == parent_id) {
			return;
		}

		var data = {
			action: 'buse_can_edit',
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
});


