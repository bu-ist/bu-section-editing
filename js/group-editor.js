jQuery(document).ready(function($){

	// Globals
	var members_list = $('#group-member-list');

	// Active tab switching
	var $tabs = $('a.nav-tab');
	var $panels = $('.edit-group-panel');

	$tabs.click(function(e){
		var $active = $(this);
		var $panel = $($active.attr('href'));

		$active.addClass('nav-tab-active').siblings().removeClass('nav-tab-active');
		$panel.addClass('active').siblings().removeClass('active');

	});

	// Move non active users to different location
	$('.member:not(.active)').appendTo('#inactive-members');

	// Remove a member from the editor group list
	members_list.delegate( 'a.remove_member', 'click', function(e){

		$(this).parent('.member').removeClass('active').slideUp( 'fast', function() {
			$(this).appendTo('#inactive-members')
			.find('input[type="checkbox"]').removeAttr('checked');

			updateMemberCount();
		});

		e.preventDefault();

	});

	$('#user_login').keypress( function(e) {

		// Enter key
		if( e.keyCode == '13' ) {

			$('#add_member').trigger('click');
			e.preventDefault();
		}
	})

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
				// console.log(response);
			},
			error: function(response) {
				// console.log(response);
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

					var user_id = response.user_id;
					var user_login = userData.user;

					var already_added = $('.member input[value="' + user_id + '"]').is(':checked');

					if( already_added ) {
					
						$('#members-message').attr('class','error').html( '<p>' + user_login +' has already been added to the group member list.</p>' ).fadeIn();
					
					} else {

						// I don't think we need an update message
						//$('#members-message').attr('class','updated').html('<p>' + user_login + ' has been added to this group.</p>').fadeIn();

						// Remove any errors
						$('#members-message').fadeOut('fast', function(e){$(this).attr('class','').html('');});

						$('#member_' + response.user_id ).attr('checked','checked')
							.parent('.member')
							.addClass('active')
							.appendTo(members_list)
							.slideDown('fast');

						updateMemberCount();
					}

				} else {

					$('#members-message').attr('class', 'error').html( response.message ).fadeIn();

				}

			},
			error: function(response) {

				// @todo handle ajax errors more gracefully
				// console.log(response);
				//$('#members-message').attr('class', 'error').html( response ).fadeIn();
			
			}
		});

		// For quick member entry (should this only run on successful add?)
		$('#user_login').val('').focus();

		e.preventDefault();

	});

	// Hierarchical permissions editor
	
	var options = {
		plugins : [ 'themes', 'html_data' ],
		core : {
			html_titles : true
		},
		html_data : {
			ajax : {
				url : ajaxurl,
				type: 'GET',
				data : function(n) {
					return { 
						parent_id : n.attr ? n.attr('id') : 0,
						action : 'buse_fetch_children',
						post_type : 'page',
						group_id : $('#group_id').val()
					}
				}
			}
		}
	};

	// jstree
	$('.perm-editor-hierarchical')
		.bind('loaded.jstree', function( event, data ) {
			// console.log('JS TREE LOADED');
		})
		.jstree( options );

	function updateMemberCount() {
		var count = members_list.children('.member').length;
		$('.member-count').html( count + ' members' );
		$('#group-stats-count').html( count );
	}

});