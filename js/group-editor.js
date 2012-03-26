jQuery(document).ready(function($){

	// Active tab switching
	var $tabs = $('a.nav-tab');
	var $panels = $('.edit-group-panel');

	$tabs.click(function(e){
		e.preventDefault();
		var $active = $(this);
		var $panel = $($active.attr('href'));

		$active.addClass('nav-tab-active').siblings().removeClass('nav-tab-active');
		$panel.addClass('active').siblings().removeClass('active');

	});

	// Remove a member from the editor group list
	$('.remove_member').live('click', function(e) {

		$(this).parent('.member').slideUp( 'fast', function() {

			// Hide and uncheck
			$(this).removeClass('active').find('input[type="checkbox"]').removeAttr('checked');

		});

		e.preventDefault();

	});

	$('#find_user').click( function(e) {

		var userData = {
			action: 'buse_find_user',
			user: $('#user_login').val()
		}

		$.ajax({
			url: ajaxurl,
			data: userData,
			type: 'POST',
			success: function(response) {
				console.log(response);
			}
		});

		e.preventDefault();
	})

	$('#add_member').click( function(e) {

		var userData = {
			action: 'buse_add_member',
			group_id: $('#group_id').val(),
			user: $('#user_login').val()
		}

		$.ajax({
			url: ajaxurl,
			data: userData,
			type: 'POST',
			success: function(response) {
				if( response.status ) { 
					$('#member_' + response.user_id ).attr('checked','checked').parent('.member').addClass('active').slideDown();

				} else {
					// Display error message
				}
				console.log(response);
			}
		});

		e.preventDefault();
	})

});