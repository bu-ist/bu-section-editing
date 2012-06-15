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
				console.log(response);
			},
			error: function(response) {
				console.log(response);
			}
		});
	});
});


