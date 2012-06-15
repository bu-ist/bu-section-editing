jQuery(function($) {
	$('#bu-page-parent').bind('nodeSelected', function(e){
		var parent_id = $(e.target).data('scrollingTree').getSelection();
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
				if($('#post [name="parent_id"]').val() == response.parent_id && response.can_edit == false) {
					alert("You cannot place this page there.");
					$('#post [name="parent_id"]').val(response.original_parent);
				}
			},
			error: function(response) {
				console.log(response);
			}
		});
	});
});


