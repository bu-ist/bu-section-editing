jQuery(document).ready(function($){

	// Globals
	var $members_list = $('#group-member-list');
	var $nav_links = $('a.nav-link');

	// _______________________ Navigation Tabs ________________________
	
	if( location.hash ) {
		// Initial state based on incoming hash...
		var $tab = $('a.nav-tab[href=' + location.hash + ']');
		$target = $(location.hash);

		$tab.addClass('nav-tab-active').siblings().removeClass('nav-tab-active');
		$target.addClass('active').siblings().removeClass('active');
	}

	$nav_links.click(function(e){
		e.preventDefault();

		var $tab = $('a.nav-tab[href=' + this.hash + ']');
		var $target = $(this.hash);

		$tab.addClass('nav-tab-active').siblings().removeClass('nav-tab-active');
		$target.addClass('active').siblings().removeClass('active');

	});

	// _______________________ Group Name ________________________
	
	$('#edit-group-name').blur(function(e){
		$('#group-stats-name').html($(this).val());
	});

	// _______________________ Group Members ________________________
	
	$('.member:not(.active)').appendTo('#inactive-members');

	// Remove a member from the editor group list
	$members_list.delegate( 'a.remove_member', 'click', function(e){
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
							.appendTo($members_list)
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
	
	/**
	 * Stats widget -- update member count on add/remove
	 */ 
	var updateMemberCount = function() {

		var count = $members_list.children('.member').length;

		$('.member-count').html( count );
		
	}

	// ______________________ PERMISSIONS _____________________

	/* Overlay UI */

	var createOverlay = function( $el, callbck ) {

		// Generate appropriate label
		var state = $el.attr('rel');
		console.log("Overlay state: " + state );
		var label = state == 'allowed' ? 'Deny Editing' : 'Allow Editing';

		// Create actual state modifying link
		var $overlayLink = $('<a href="#edit-node" class="' + state + '">' + label + '</a>');

		// Attach event handlers
		$overlayLink.click(function(e){

			e.stopPropagation();
			e.preventDefault();

			// And process the action
			callbck.call( $el, e )

		});

		// Create overlay
		var $overlay = $('<span class="edit-node"></span>').append($overlayLink).hide();

		// Append and fade in
		$el.children('a:first').after($overlay);
		$overlay.fadeIn();
	}

	var removeOverlay = function( $el, callbck ) {

		var $overlay = $el.children('.edit-node').first();

		if( $overlay.length ) {
			
			$overlay.fadeOut( function(){ 

				$(this).remove();

			});

		}

	}

	// _______________________ Flat Permissions Editor _______________________

	$('.perm-list.flat').delegate('li', 'click', function(e){
		console.log('Post was clicked!');

		var callbck = function(e) {
			console.log("Clicked me:");
			console.log($(this));
			$(this).togglePermissions();
		}

		createOverlay( $(this), callbck );
		
	});


	// _______________________ Hierarchical Permissions Editor _______________________
	
	// jstree configuration
	var options = {
		plugins : [ 'themes', 'types', 'html_data', 'ui' ],
		core : {
			animation: 0,
			html_titles : true
		},
		themes : {
			theme: 'classic'	
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
		}
	};

	/**
	 * Create a jstree instance for each hierarchical post type
	 */ 
	$('.perm-editor-hierarchical').each( function() {
		var post_type = $(this).data('post-type');
		
		options['html_data'] = {
			ajax : {
				url : ajaxurl,
				type: 'GET',
				data : function(n) {
					return { 
						parent_id : n.attr ? n.attr('id') : 0,
						action : 'buse_fetch_children',
						post_type : post_type,
						group_id : $('#group_id').val()
					}
				}
			}
		}

		$(this).jstree( options )
			.bind('loaded.jstree', function( event, data ) {
				/* no op, yet... */
			})
			.bind('select_node.jstree', function( event, data ) {

				// Event handler for permissions update
				var actionCallback = function( e ) {
					var $el = $(this);
					updateTreePermissions( $el, data.inst );
				}

				if( data.inst.is_selected( data.rslt.obj ) ) {

					createOverlay( data.rslt.obj, actionCallback );

				} else {

					removeOverlay( data.rslt.obj );
				}

			})
			.bind('deselect_node.jstree', function( event, data ) {

				removeOverlay( data.rslt.obj );

			})
			.bind('deselect_all.jstree', function( event, data ) {

				// Remove existing contect menus if we have a previous selection
				if( data.rslt.obj.length ) {

					removeOverlay( data.rslt.obj );

				}

			});
	})

	/**
	 * The user has allowed/denied a specific node
	 */
	var updateTreePermissions = function( $node, inst ) {
	
		// When button is clicked, deselect parent li
		inst.deselect_node($node);

		var post_type = inst.get_container().data('post-type');

		// Possibly load tree
		if( $node.parent().parent().hasClass('perm-editor-hierarchical') ) {

			// Need to load node first
			inst.open_node( $node, function() {

				inst.open_all( $node );

				// Toggle state
				toggleState( $node );

				// Diff the changes
				processUpdatesForNode( $node, post_type );

			} );

		} else {
			// Reveal children
			inst.open_all( $node );
				
			// Toggle state
			toggleState( $node );

			// Diff the changes
			processUpdatesForNode( $node, post_type );

		}

	}

	/**
	 * Toggles the visual state of a given node
	 */
	var toggleState = function( $node ) {
		
		var previous = $node.attr('rel');

		// Look at previous value to determine new one
		switch( previous ) {

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

	}

	/**
	 * Process changes to hierarchical permissions
	 * 
	 * Run whenever a node state is toggled
	 */ 
	var processUpdatesForNode = function( $node, post_type ) {

		var id = $node.attr('id').substr(1);
		var status = $node.attr('rel');

		// Track changes
		var edits = getExistingEdits(post_type);
		var count = 0;

		// Set our persistent data attr first
		if( $node.parent().parent().hasClass('perm-editor-hierarchical') ) {

			// For root nodes, only explicitly set allowed
			if( status == 'allowed' )
				edits[id] = status;
			else
				edits[id] = '';

		} else {
			
			// For children, need to check for inheritance
			var $parent = $node.parentsUntil('li').parent();
			var parent_state = $parent.data('perm');

			if( parent_state == status ) {
				$node.data('perm','');
				edits[id] = '';
			} else {
				$node.data('perm',status);
				edits[id] = status;
			}

		}

		if( status == 'allowed' ) count += 1;
		else count -= 1;

		// Fetch all children
		var $children = $node.find('li');

		// Propogate permissions (both visually and cleaning up redundant data attr)
		$children.each(function(index, child) {

			var existing_perm = $(child).data('perm');
			var current_rel = $(child).attr('rel');
			var child_id = $(this).attr('id').substr(1);

			if( existing_perm == status ) {
				$(child).data('perm','');	// reset
				edits[child_id] = '';

			} else if( existing_perm == '' ) {	// this node is inheriting

				// Examine existing state, switch if necessary
				if( current_rel != status ) {

					$(child).attr('rel',status);
					
					if( status == 'allowed' ) count += 1;
					else count -= 1;

				}
			}

		});

		/*

		HOLDING OFF ON THIS PROBLEM FOR NOW

		// Update ancestors visiual appearance to reflect allowed children where necessary

		$node.parents('li').each( function( index, post ) {

			var ancestor_status = $(post).attr('rel');
			var ancestor_id = $(post).attr('id').substr(1);

			if( ancestor_status == 'denied' ) {
				if( $(post).find('li[rel="allowed"]').length ) {

					$(post).attr('rel','denied-desc-allowed');

				}
			} else if ( ancestor_status == 'denied-desc-allowed' ) {
				// Look for allowed descendents
				if( $(post).find('li[rel="allowed"]').length == 0 ) {

					$(post).attr('rel','denied' );

				}
			}

		});
		*/

		// Save edits
		commitEdits( edits, post_type );

		// Update the stats widget counter
		//updatePermStats( count, post_type );

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

		// console.log( '==== Incoming Edits ====' );
		
		for( id in edits ) {
			title = $('#p' + id ).children('a').first().text();

			// console.log( post_type + ': ' + title + ' (' + id + ') set to: ' + edits[id] );
		}
		
		// console.log( '========================' );
			
		// Update input
		$edits_field.val( JSON.stringify(edits) );

	}

	/* Perm Stats */

	/**
	 * Stats widget -- permissions count 
	 */
	var updatePermStats = function( count, post_type ) {

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

	// _______________________ Pending Edits ________________________
	
	// State detection
	var origName, origMemberList;

	origName = $('#edit-group-name').val();

	var getMembers = function() {
		var ids = [];
		$('#group-member-list').children('li.member.active').each(function(index, li){
			ids.push( $(li).children('input').first().val() );
		});
		return ids;
	}
	
	// Store current members
	origMemberList = getMembers();

	/**
	 * Prevent leaving this page when edits are present without confirmation
	 */ 
	window.onbeforeunload = function() {

		// Detect if we have any changes...
		if( hasEdits() ) {
			return 'Your group has pending edits.  If you leave now, your changes will be lost.';
		}
		
	};

	/**
	 * Logic to determine if a group has pending edits
	 */ 
	var hasEdits = function() {
	
		// Check name field
		if( origName != $('#edit-group-name').val() ) {
			//console.log('Name has changed!');
			return true;
		}

		var currentMemberList = getMembers();
		
		// Check member list
		if( origMemberList.length != currentMemberList.length ) {
			//console.log('Member list has changed!');
			return true;
		} else {
			for( index in origMemberList ) {
				if( $.inArray( origMemberList[index], currentMemberList ) == -1 ) {
					//console.log('Member list has changed!');
					return true;
				}
			}
		}
		
		var permEdits = false;
		
		// Check permissions for all post types
		$('.buse-edits').each( function(i, input) {
			// @todo do a better check -- if json object in buse-edits only has ID's
			// with a value of "", then actually nothing has changed...
			if( $(input).val() ) {
				//console.log('Permissions have changed!');
				permEdits = true;
			}
		});

		return permEdits;
		
	}

	/**
	 * Saving/updating does not trigger alerts
	 */ 
	$('input[type="submit"]').click(function(e) {
		window.onbeforeunload = null;

		//@todo client-side validation
		// - Does this group have a name?
	});

	/**
	 * Generates an alert whenever a user attemps to delete a group
	 */
	$('a.submitdelete').click(function(e){
		
		e.preventDefault();
		
		var msg = "You are about to permanently delete this section editing group.  " +
			"This action is irreversible.\n\nAre you sure you want to do this?";
		
		if( confirm(msg) ) {
			
			window.onbeforeunload = null;
			window.location = $(this).attr('href');
			
			// delete the group...
		} else {
			// don't follow the link'
			e.preventDefault();	
		}
	});

});