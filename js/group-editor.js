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
	
	var updateMemberCount = function() {

		var count = members_list.children('.member').length;

		$('.member-count').html( count );
		
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
				'denied-desc-allowed' : {
					clickable	: true,
					renameable	: false,
					deletable	: false,
					creatable	: false,
					draggable	: false,
					max_children	: -1,
					max_depth	: -1,
					valid_children	: "all",
					icon: {
						image: buse_config.pluginUrl + "/images/group_perm_denied_desc_allowed.png"
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

		var post_type = inst.get_container().data('post-type');
		var status = $node.attr('rel');

		// Look at previous value to determine new one
		switch( status ) {

			// Previously allowed: denied
			case 'allowed':
				$node.attr('rel', 'denied');
				break;

			// Previously denied: allowed
			case 'denied':
			case 'denied-desc-allowed':
				$node.attr('rel', 'allowed' );
				break;

		}

		// Diff the changes
		processUpdatesForNode( $node, post_type );

	}

	var processUpdatesForNode = function( $node, post_type ) {

		var id = $node.attr('id').substr(1);
		var status = $node.attr('rel');
		var count = 0;

		// Set edit to root note (needs to be adjusted, based on parent settings)
		console.log( 'Parent status: ' + $node.parentsUntil('li').parent().attr('rel'));
		console.log( 'New status: ' + status);

		// Update count for edited node
		if( status == 'allowed' ) count += 1;
		else if ( status == 'denied' ) count -= 1;

		if( status == 'allowed' ) console.log('Status is now allowed!');
		if( status == 'denied' ) console.log('Status is now denied!');

		// Fetch all children
		var $children = $node.find('li');

		// Propogate permissions
		$children.attr('rel', status)

		// Update count for edited node
		if( status == 'allowed' ) count += $children.length;
		else if ( status == 'denied' ) count -= $children.length;

		// Track changes
		var edits = getExistingEdits(post_type);
		
		// Possibly reset status if we are now inheriting from our ancestors
		if( $node.parentsUntil('li').parent().attr('rel') == status ) edits[id] = '';
		else edits[id] = status;

		// Look down
		$children = $node.find('li').each(function(index, child){

			if( status == $(child).attr('rel') ) {
				child_id = $(this).attr('id').substr(1);
				edits[child_id] = '';	// remove redundant statusi

				// Revert counts
				if( status = 'allowed' ) count--;
				else if ( status = 'denied' ) count++;
			}
		});

		// Loop up
		$node.parents('li').each( function( index, post ) {

			var ancestor_status = $(post).attr('rel');
			var ancestor_id = $(post).attr('id').substr(1);

			switch( ancestor_status ) {

				case 'allowed':
					/* no op */
					break;

				case 'denied':
					// @todo you left off here
					// need to differentiate between an explicitly denied post (meaning inherited would be allowed otherwise)
					// should do this at the type level
					// trigger denied-explicit state when an ancestor is allowed
					// when ancestor is denied, explicit denied states should go away

					// this is getting way to overly fucking complicated...

					// Respect explicity denied parents
					if( ( ancestor_id in edits ) && ( edits[ancestor_id] == 'denied' ) )
						break;

					// Otherwise look for allowed descendents
					if( $(post).find('li[rel="allowed"]').length ) {

						$(post).attr('rel','denied-desc-allowed');
						edits[ancestor_id] = 'denied-desc-allowed';

					}

					break;

				case 'denied-desc-allowed':

					// Look for allowed descendents
					if( $(post).find('li[rel="allowed"]').length == 0 ) {

						$(post).attr('rel','denied' );
						edits[ancestor_id] = '';

					}

					break;

			}

		});

		commitEdits( edits, post_type );

		// Calculate count update for this post
		var count = $children.length + 1;
		count = ( status == 'allowed' ) ? count : -count;

		// Update the stats widget counter
		updatePermStats( count, post_type );

	}

	var getExistingEdits = function( post_type ) {

		// fetch existing edits
		var $edits_field = $('#buse-edits-' + post_type );
		var edits = $edits_field.val() || '';

		// Convert to object
		return edits ? JSON.parse(edits) : {};
	}

	var commitEdits = function( edits, post_type ) {

		var $edits_field = $('#buse-edits-' + post_type );

		for( id in edits ) {
			console.log( post_type + ' ' + id + ' set to: ' + edits[id] );
		}
			
		// Update input
		$edits_field.val( JSON.stringify(edits) );

	}

	var mergeEdits = function( new_edits, post_type ) {

		// fetch existing edits
		var $edits_field = $('#buse-edits-' + post_type );
		var edits = $edits_field.val() || '';

		// Convert to object
		edits = edits ? JSON.parse(edits) : {};

		// Merge
		$.extend( edits, new_edits );

		console.log('Resulting updates:');
		for( id in edits ) {
			console.log( post_type + ' ' + id + ' set to: ' + edits[id] );
		}
			
		// Update input
		$edits_field.val( JSON.stringify(edits) );

	}

	var updatePermStats = function( count, post_type ) {

		console.log('Update count for `' + post_type + '`: ' + count );

		var $container = $('#group-stats-permissions');
		var $existing = $('#' + post_type + '-stat-count');
		var start_count = 0;

		if( $existing.length == 0 ) {
			$container.append('<span id="' + post_type + '-stats" class="perm-stats"><span id="' + post_type + '-stat-count"></span> Pages</span>');
			$existing = $('#' + post_type + '-stat-count' );
		} else {
			start_count = parseInt( $existing.text() );
		}

		var total = start_count + count;

		// We have an updated count fragment for this post type
		if( total > 0 ) {

			// check for existance of #add-permissions-link, remove if its there
			$('#add-permissions-link').remove();

			$existing.text(total);
			
		} else { // We no longer have any count for this post tpye
			
			$existing.parent().remove();

			// Reset to 'Add Permissions' link if this was the last editable post
			if( $container.children('.perm-stats').length == 0 ) {

				$container.html('<a id="add-permissions-link" class="nav-link" href="#group-permissions-panel" title="Add permissions for this group">Add Permissions</a>');
			
			}

		}


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


});