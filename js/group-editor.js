jQuery(document).ready(function($){

	// Globals
	var members_list = $('#group-member-list');

	// Navigation tabs/panels
	var $tabs = $('.nav-tab');
	var $panels = $('.edit-group-panel');

	$tabs.click(function(e){
		e.preventDefault();

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

	/* Find Users */

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

	/* Add Members */

	$('#add_member').bind( 'click', function(e){
		e.preventDefault();

		add_member();
	});

	$('#user_login').keypress( function(e) {

		// Enter key
		if( e.keyCode == '13' ) {
			e.preventDefault();

			add_member();
		}
	});

	var add_member = function() {

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

	}

	// _______________________ Hierarchical permissions editor ________________________
	
	var options = {
		plugins : [ 'themes', 'types', 'html_data', 'ui', 'contextmenu' ],
		core : {
			animation: 0,
			html_titles : true
		},
		types : {
			types : {
				'default' : {
					clickable	: true,
					renameable	: true,
					deletable	: true,
					creatable	: true,
					draggable	: true,
					max_children	: -1,
					max_depth	: -1,
					valid_children	: "all",
					icon: {
						"image": buse_config.pluginUrl + "/images/group_perm_restrict.png"
					}
				},
				'section_restricted' : {
					clickable	: true,
					renameable	: false,
					deletable	: true,
					creatable	: true,
					draggable	: true,
					max_children	: -1,
					max_depth	: -1,
					valid_children	: "all",
					icon: {
						image: buse_config.pluginUrl + "/images/group_perm_restrict.png"
					}
				},
				'section_editable' : {
					clickable	: true,
					renameable	: false,
					deletable	: true,
					creatable	: true,
					draggable	: true,
					max_children	: -1,
					max_depth	: -1,
					valid_children	: "all",
					icon: {
						image: buse_config.pluginUrl + "/images/group_perm_editable.png"
					}
				},
				'section_children_editable' : {
					clickable	: true,
					renameable	: false,
					deletable	: true,
					creatable	: true,
					draggable	: true,
					max_children	: -1,
					max_depth	: -1,
					valid_children	: "all",
					icon: {
						image: buse_config.pluginUrl + "/images/group_perm_children_editable.png"
					}
				},
				'post_editable' : {
					clickable	: true,
					renameable	: false,
					deletable	: true,
					creatable	: true,
					draggable	: true,
					max_children	: -1,
					max_depth	: -1,
					valid_children	: "all",
					icon: {
						image: buse_config.pluginUrl + "/images/group_perm_editable.png"
					}
				},
				'post_restricted' : {
					clickable	: true,
					renameable	: true,
					deletable	: true,
					creatable	: true,
					draggable	: true,
					max_children	: -1,
					max_depth	: -1,
					valid_children	: "all",
					icon: {
						"image": buse_config.pluginUrl + "/images/group_perm_restrict.png"
					}
				}
			}
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
		},
		contextmenu : {
			items : function(node) {

				var label = node.attr('rel') == 'section_editable' ? 'Restrict' : 'Make Editable';

				switch( node.attr('rel') ) {
					case 'section_editable':
						label = 'Restrict Section';
						break;
					case 'section_restricted':
					case 'section_children_editable':
						label = 'Make Section Editable';
						break;
					case 'post_editable':
						label = 'Restrict post';
						break;
					case 'post_restricted':
						label = 'Make post editable';
						break;
				}

				return {
					"edit" : {
						"label" : label,
						"action" : editNode,
						"icon" : "remove"
					}
				}
			}
		}
	};

	/**
	 * Need to figure out the best way to propogate permissions to children,
	 * both programmatically and visually
	 */
	var editNode = function(n) {

		var $node = $(n);
		var id = $node.attr('id').substr(1);
		var is_editable = false;
		var post_type = this.get_container().data('post-type');

		// @todo call a function and pass this node
		switch( $node.attr('rel') ) {

			case 'section_editable':

				// Has editable children
				if( $node.find('li[rel="section_editable"],li[rel="post_editable"]').length > 0 )
					$node.attr('rel', 'section_children_editable');
				else
					$node.attr('rel', 'section_restricted')

				is_editable = false;
				break;
			case 'section_restricted':
				$node.attr('rel', 'section_editable' );
				is_editable = true;
				break;
			case 'section_children_editable':
				$node.attr('rel', 'section_editable');
				is_editable = false;
				break;
			case 'post_editable':
				$node.attr('rel', 'post_restricted');
				is_editable = false
				break;
			case 'post_restricted':
				$node.attr('rel', 'post_editable');
				is_editable = true;
				break;
		}

		// fetch existing edits
		var existing_edits = $('#buse-edits-' + post_type ).val() || '';
		var edits = existing_edits ? JSON.parse(existing_edits) : {};
		edits[id] = is_editable;

		// update
		$('#buse-edits-' + post_type ).val( JSON.stringify(edits) );

	}

	var makeSectionEditable = function() {
		// Make all children section editable?  How should we hadnle this from both a UI and data standpoint?
	}

	var restrictSection = function() {

	}

	// Should move JSON parse/stringify logic to this function...
	$('#group-edit-form').bind('submit', function(){

	})

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