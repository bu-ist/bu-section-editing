jQuery(document).ready(function($){

	// Globals
	var members_list = $('#group-member-list');

	// Navigation tabs/panels
	var $nav_links = $('a.nav-link');

	$nav_links.click(function(e){
		e.preventDefault();

		var $tab = $('a.nav-tab[href=' + this.hash + ']');
		var $target = $(this.hash);

		$tab.addClass('nav-tab-active').siblings().removeClass('nav-tab-active');
		$target.addClass('active').siblings().removeClass('active');

	});

	/* Group Name */
	$('#edit-group-name').blur(function(e){
		$('#group-stats-name').html($(this).val());
	});

	/* Group members */ 

	$('.member:not(.active)').appendTo('#inactive-members');

	// Remove a member from the editor group list
	members_list.delegate( 'a.remove_member', 'click', function(e){
		e.preventDefault();

		$(this).parent('.member').removeClass('active').slideUp( 'fast', function() {
			$(this).appendTo('#inactive-members')
			.find('input[type="checkbox"]').removeAttr('checked');

			updateMemberCount();
		});

	});

	/* Find Users - not yet implemented */

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
		plugins : [ 'themes', 'types', 'html_data', 'ui' ],
		core : {
			animation: 0,
			html_titles : true
		},
		types : {
			types : {
				'default' : {
					clickable	: true,
					renameable	: false,
					deletable	: false,
					creatable	: false,
					draggable	: false,
					max_children	: -1,
					max_depth	: -1,
					valid_children	: "all",
					icon: {
						"image": buse_config.pluginUrl + "/images/group_perm_denied.png"
					}
				},
				'denied' : {
					clickable	: true,
					renameable	: false,
					deletable	: false,
					creatable	: false,
					draggable	: false,
					max_children	: -1,
					max_depth	: -1,
					valid_children	: "all",
					icon: {
						image: buse_config.pluginUrl + "/images/group_perm_denied.png"
					}
				},
				'allowed' : {
					clickable	: true,
					renameable	: false,
					deletable	: false,
					creatable	: false,
					draggable	: false,
					max_children	: -1,
					max_depth	: -1,
					valid_children	: "all",
					icon: {
						image: buse_config.pluginUrl + "/images/group_perm_allowed.png"
					}
				},
				'desc_allowed' : {
					clickable	: true,
					renameable	: false,
					deletable	: false,
					creatable	: false,
					draggable	: false,
					max_children	: -1,
					max_depth	: -1,
					valid_children	: "all",
					icon: {
						image: buse_config.pluginUrl + "/images/group_perm_desc_allowed.png"
					}
				}
			}
		},
		ui: {
			select_limit: 1
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
		.jstree( options )
		.bind('select_node.jstree', function( event, data ) {

			//console.log('Selecting node!');
			toggleContextMenu( data.rslt.obj, data.inst );

		})
		.bind('deselect_node.jstree', function( event, data ) {

			//console.log('Deselecting node!');
			toggleContextMenu( data.rslt.obj, data.inst );

		})
		.bind('deselect_all.jstree', function( event, data ) {

			//console.log('Deselecting all nodes!');

			// Remove existing contect menus if we have a previous selection
			if( data.rslt.obj.length ) {

				toggleContextMenu( data.rslt.obj, data.inst );

			}

		});

	var toggleContextMenu = function( $el, inst ) {

		var $hasExisting = $el.children('.edit-node').length ? true : false;

		if( $hasExisting == true ) {

			removeContextMenu( $el, inst );

		} else {

			createContextMenu( $el, inst );

		}

	}

	var createContextMenu = function( $el, inst ) {

		// Generate appropriate label
		var state = $el.attr('rel');
		var label = state == 'allowed' ? 'Deny Editing' : 'Allow Editing';

		// Create actual state modifying link
		var $editNodeLink = $('<a href="#edit-node" class="' + state + '">' + label + '</a>');

		// Attach event handlers
		$editNodeLink.click(function(e){

			e.stopPropagation();

			// When button is clicked, deselect parent li
			inst.deselect_node($el);

			// And process the action
			handleContextMenuAction( $el, inst );

		});

		// Create edit button
		var $editNode = $('<span class="edit-node tri-border left"></span>').append($editNodeLink).hide();

		// Append and fade in
		$el.children('a:first').after($editNode);
		$editNode.fadeIn();
	}

	var removeContextMenu = function( $el, inst ) {

		var $menu = $el.children('.edit-node').first();

		if( $menu.length ) {
			
			$menu.fadeOut( function(){ 

				$(this).remove();

			});

		}

	}

	/**
	 * The user has allowed/denied a specific node
	 */
	var handleContextMenuAction = function( $node, inst ) {

		var id = $node.attr('id').substr(1);
		var is_editable = false;
		var post_type = inst.get_container().data('post-type');

		// Look at previous value to determine new one
		switch( $node.attr('rel') ) {

			// Previously allowed: deny
			case 'allowed':
				//@todo deny all children (?)
				
				// Update rel attribute (look for editable children first)
				if( $node.find('li[rel="allowed"]').length > 0 ) {

					$node.attr('rel', 'desc_allowed');
				
				} else {
				
					$node.attr('rel', 'denied');
				}

				is_editable = false;
				break;

			// Previously denied: allow
			case 'denied':
				// @todo allow all descendents

				$node.attr('rel', 'allowed' );
				is_editable = true;
				break;

			// Descendents allowed:
			case 'desc_allowed':
				// @todo remove editable flags from all editable descendents

				$node.attr('rel', 'allowed');
				is_editable = true;
				break;
		}

		// @todo Propogate state to ancestors

		// Update edit state
		updatePostPermissions( id, is_editable, post_type );

	}

	/**
	 * Given an incoming state change on a specific post, update the list of edits to be processed on save
	 */
	var updatePostPermissions = function( post_id, is_editable, post_type ) {
		
		// fetch existing edits
		var $edits_field = $('#buse-edits-' + post_type );

		var existing_edits = $edits_field.val() || '';
		var edits = existing_edits ? JSON.parse(existing_edits) : {};
		edits[post_id] = is_editable;

		// Update edits input
		$edits_field.val( JSON.stringify(edits) );

		// Update counter
		updatePermissionsCount( post_type, edits );

	}

	/**
	 * Update stats widget counter to reflect current state of permissions for a given post type
	 *
	 * @uses ajax
	 */
	var updatePermissionsCount = function( post_type, edits ) {

		var editable_count = 1;

		var permissionsData = {
			action: 'buse_update_permissions_count',
			count: editable_count,
			post_type: post_type,
			edits: edits,
			group_id: $('#group_id').val()
		};

		$.ajax({
			url: ajaxurl,
			data: permissionsData,
			type: 'POST',
			success: function(response) {

				$container = $('#group-stats-permissions');
				$existing = $container.children('#' + post_type + '-stats');

				// We have an updated count fragment for this post type
				if( response.length > 0 ) {

					// check for existance of #add-permissions-link, remove if its there
					$('#add-permissions-link').remove();

					if( $existing.length > 0 ) {
						$existing.replaceWith($(response));
					} else {
						$container.append($(response));
					}
					
				} else { // We no longer have any count for this post tpye
					
					$existing.remove();

					// Reset to 'Add Permissions' link if this was the last editable post
					if( $container.children('.perm-stats').length == 0 ) {
						$container.html('<a id="add-permissions-link" class="nav-link" href="#group-permissions-panel" title="Add permissions for this group">Add Permissions</a>');
					}

				}
			},
			error: function(response) {
				console.log(response);
			}
		});
	}

	function updateMemberCount() {
		var count = members_list.children('.member').length;
		$('.member-count').html( count );
	}

});