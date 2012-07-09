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

	// @todo
	// - test browser compatibility for position js
	// - clean up / refactor in to "class"
	// - with positioning handled in Javascript we don't need to create the overlay everytime
	// - handle re-positioning on window resize

	var createOverlay = function( $el, callbck ) {

		// Generate appropriate label
		var state = $el.attr('rel');
		var $a = $el.children('a:first').first();
		var $container = $('#perm-panel-container');

		var label = state == 'allowed' || state == 'allowed-desc-denied' ? 'Deny Editing' : 'Allow Editing';

		// Create actual state modifying link
		var $overlayLink = $('<a href="#edit-node" class="' + state + '">' + label + '</a>');

		// Attach event handlers
		$overlayLink.click(function(e){

			e.stopPropagation();
			e.preventDefault();

			// And process the action
			callbck.call( $el, e )

		});

		$('<span class="edit-node"></span>').append($overlayLink).hide( 0, function(){

			// Append to container
			$el.closest('.perm-panel.active').append($(this));

			$(this).position({
				of: $a,
				my: 'left center',
				at: 'right center',
				within: $container,
				offset: '15 0',
				collision: 'fit none'
			}).show();

		});

	}

	var removeOverlay = function( $el, callbck ) {

		var $overlay = $el.closest('.perm-panel.active').children('.edit-node').first();

		if( $overlay.length ) {
			
			$overlay.hide( 0, function(){ 

				$(this).remove();

			});

		}

	}

	/* Overlay removal on container click */
	$('#perm-panel-container').click(function(e){

		/* For hierarchical permission editors */
		var $perms_hierarchical = $(this).find('.perm-panel.active .perm-editor.hierarchical');

		if( $perms_hierarchical.length > 0 ) {

			/* 
			Need to make sure we're not in the process of selecting a node,
			as the jstree method select_node does not allows us to stop click
			events from bubbling up on selection
			*/

			if( $(e.target).hasClass('jstree-clicked') || $(e.target).parents('.jstree-clicked').length )
				return;

			var $inst = $.jstree._reference($perms_hierarchical);

			if( $inst ) {

				var $selected = $inst.get_selected();

				if( $selected.length > 0 ) 
					$inst.deselect_all();

			}

		}

		/* For flat permission editors */
		var $perms_flat = $(this).children('.perm-panel.active').find('.perm-editor.flat');

		if( $perms_flat.length > 0 ) {

			var $selected_lis = $perms_flat.find('a.perm-item-clicked').parent('li');

			$selected_lis.each(function(){
				$(this).children('a').removeClass('perm-item-clicked');
				removeOverlay( $(this) );
			});

		}

	});


	// _______________________ Flat Permissions Editor _______________________

	$('.perm-editor.flat').delegate( 'a', 'click', function(e) {

		// Don't follow me
		e.preventDefault();
		e.stopPropagation();

		// Keep track of current selection
		var $editor = $(this).closest('.perm-editor.flat');
		var post_type = $editor.data('post-type');
		var $target_a = $(this);
		var $post = $target_a.parent('li');

		$target_a.addClass('perm-item-clicked')

		var callbck = function(e) {

			// Remove the overlay
			removeOverlay($post);

			// Remove selection
			$target_a.removeClass('perm-item-clicked');

			// Toggle state
			toggleState( $post );
			
			processUpdatesForPost( $post, $editor );

		}

		// Remove any previously active overlays
		$post.siblings('li').children('.perm-item-clicked').each( function() {

			var $parent_li = $(this).parents('li').first();

			$(this).removeClass('perm-item-clicked');
			removeOverlay( $parent_li );

		});

		// Create a new one for the current selection if none existed
		if( $post.children('.edit-node').length == 0 )
			createOverlay( $post, callbck );
		
	});


	// _______________________ Hierarchical Permissions Editor _______________________
	
	// jstree global configuration
	var options = {
		plugins : [ 'themes', 'types', 'json_data', 'ui' ],
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
				},
				'allowed-desc-denied' : {
					clickable	: true,
					renameable	: false,
					deletable	: false,
					creatable	: false,
					draggable	: false,
					max_children	: -1,
					max_depth	: -1,
					valid_children	: "all",
					icon: {
						image: buse_config.pluginUrl + "/images/group_perm_allowed_desc_denied.png"
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
	var loadHierarchicalEditor = function( $editor ) {

		var post_type = $editor.data('post-type');
		
		// Append global jstree options with configuration for this post type
		options['json_data'] = {
			ajax : {
				url : ajaxurl,
				type: 'GET',
				data : function(n) {
					return {
						action : 'buse_render_post_list',
						group_id : $('#group_id').val(),
						post_type : post_type,
						query: {
							child_of : n.attr ? n.attr('id').substr(1) : 0
						}
					}
				}
			}
		}

		$editor.jstree( options )
			.bind('loaded.jstree', function( event, data ) {

				// Start lazy loading
				$(this).find('ul > .jstree-closed').each( function(){
					var $post = $(this);
					data.inst.load_node( $(this), function(){
						correctIconsForSection($post);
					});
				});

			})
			.bind('select_node.jstree', function( event, data ) {

				// Event handler for permissions update
				var actionCallback = function( e ) {

					var $post = $(this);

					data.inst.deselect_node( $post );
					
					updatePostPermissions( $post, data.inst.get_container() );
					
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

	};

	var loadFlatEditor = function( $editor ) {
		
		var pt = $editor.data('post-type');

		$editor.bind( 'posts-loaded', function(e, data ) {

			// Merge incoming server state with pending edits
			var $pending = $(this).siblings('input.buse-edits').first();

			if( $pending.val().length === 0 )
				return;

			var edits = JSON.parse($pending.val());

			for( post_id in edits ) {
				var $p = $editor.find('#p' + post_id );

				if( $p.length ) {
					$p.attr( 'rel', edits[post_id] );
				}
			}

		});

		displayPosts( $editor, { post_type: pt });

	}

	// @todo
	// create an object around the editor instances with a consistent
	// API for these actions
	var loadToolbar = function( $panel, $editor ) {

		// Search
		$panel.delegate( 'button.perm-search', 'click', function(e){
			e.preventDefault();
			var term = $(this).siblings('input').first().val();
			var args = {
				'post_type': $editor.data('post-type'),
				'query': {
					s: term
				}
			};

			displayPosts( $editor, args );

		});

		// Sort
		$panel.delegate( 'select.perm-sort', 'change', function(e){
			e.preventDefault();

			var sort = $(this).val().split(':'),
				orderby = sort[0],
				order = sort[1] || 'DESC';

			var args = {
				'post_type': $editor.data('post-type'),
				'query': {
					orderby: orderby,
					order: order
				}
			};

			displayPosts( $editor, args );

		});

		$panel.delegate( 'input.perm-search', 'keypress', function(e) {
			if( e.keyCode == 13 ) {
				e.preventDefault();
				$(this).siblings('button').first().click();
			}
		});

		// Expand all
		$panel.delegate('a.perm-tree-expand', 'click', function(e) {
			e.preventDefault();
			$.jstree._reference($editor).open_all();
		});
		
		// Collapse all
		$panel.delegate('a.perm-tree-collapse', 'click', function(e) {
			e.preventDefault();
			$.jstree._reference($editor).close_all();
		});

	}

	var displayPosts = function( $editor, query ) {

		var editorData = {
			action : 'buse_render_post_list',
			group_id : $('#group_id').val(),
			query : {}
		}

		if( typeof query !== undefined )
			$.extend( editorData, query );

		$.ajax({
			url : ajaxurl,
			type: 'GET',
			data: editorData,
			success: function(response) {

				if( editorData.query.offset ) {
					$editor.append(response);
				} else {
					$editor.html(response);
				}

				// Modify icons looking for edits
				$editor.trigger( 'posts-loaded', { posts : response } );

			},
			error: function(response){
				//console.log(response);
			}
		});

	}

	/**
	 * Load post type editors dynamically on click
	 */
	var loadPermissionsPanel = function( $panel ) {

		var $editor = $panel.find('.perm-editor').first();

		if( $editor.hasClass('hierarchical') ) {
			
			loadHierarchicalEditor( $editor );

		} else {

			loadFlatEditor( $editor );
		}

		loadToolbar( $panel, $editor );

		$panel.addClass('loaded');
	}

	$('#perm-tab-container').delegate( 'a', 'click', function(e) {

		var $panel = $($(this).attr('href'));
		
		if( ! $panel.hasClass('loaded') )
			loadPermissionsPanel( $panel );

	});
	
	// Initial loading
	var $initialPanel = $('#perm-panel-container').find('.perm-panel').first();
	if( $initialPanel.length )
		loadPermissionsPanel( $initialPanel );

	
	/**
	 * The user has allowed/denied a specific node
	 */
	var updatePostPermissions = function( $post, $editor ) {

		// Hierarchical post types
		if( $editor.hasClass('hierarchical') ) {

			var $jstree = $.jstree._reference( $editor );

			if( $post.hasClass('jstree-closed') ) {

				// Need to load node first
				$jstree.open_node( $post, function() {

					// Open all children to expose permissions cascade
					$jstree.open_all( $post );

					// Toggle state
					toggleState( $post );

					// Diff the changes
					processUpdatesForPost( $post, $editor );

				});

			} else {

					// Toggle state
					toggleState( $post );

					// Diff the changes
					processUpdatesForPost( $post, $editor );

			}

		}

 		// Flat post types
		if( $editor.hasClass('flat') ) { 
			
			// Toggle state
			toggleState( $post );
			
			// Diff the changes
			processUpdatesForPost( $post, $editor );
		
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
			case 'allowed-desc-denied':
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
	 * Update post permissions status and add to pending edits
	 */
	var processUpdatesForPost = function( $post, $editor ) {

		var id = $post.attr('id').substr(1);
		var status = $post.attr('rel');
		var post_type = $editor.data('post-type');

		// Track changes
		var edits = getExistingEdits( post_type );
		var count = 0;

		// Set our persistent data attr first
		$post.data('perm',status);
		edits[id] = status;

		if( status == 'allowed' ) count += 1;
		else count -= 1;

		// Hierarchical post types need extra love
		if( $editor.hasClass('hierarchical') ) {

			// Fetch all children
			var $children = $post.find('li');

			// Cascade permissions
			$children.each(function(index, child) {

				var existing_perm = $(child).data('perm');
				var child_id = $(this).attr('id').substr(1);

				// Note incoming edit if status changed
				if( existing_perm != status ) {

					$(child).data('perm', status);
					edits[child_id] = status;

					if( status == 'allowed' ) count += 1;
					else count -= 1;

				}

				// Change icon no matter what
				$(child).attr('rel', status);

			});

			// Find root node for update post
			$root_post = $post.parentsUntil( $editor, 'li' ).last();

			// Correct icons for ancestors affected by this change
			correctIconsForSection( $root_post );

		}

		// Save edits
		commitEdits( edits, post_type );

		// Update the stats widget counter
		// @todo make this happen through notifications
		updatePermStats( count, post_type );

	}

	/**
	 * Notify parents of a modified post that child statuses have changed
	 */
	var correctIconsForSection = function( $section ) {

		$sections = $section.find('ul');

		// Iterate over each section
		$sections.each(function(){

			var $parent_post = $(this).parents('li').first();

			if( $parent_post.length ) {

				var state = $parent_post.attr('rel');
				var mismatch = false;

				switch( state ) {

					case 'allowed': case 'allowed-desc-denied':
						mismatch = ($(this).find('li[rel="denied"],li[rel="denied-desc-allowed"]').length > 0 );
						
						// Adjust state
						if( mismatch ) $parent_post.attr('rel','allowed-desc-denied');
						else $parent_post.attr('rel','allowed');

						break;

					case 'denied': case 'denied-desc-allowed':
						mismatch = ( $(this).find('li[rel="allowed"],li[rel="allowed-desc-denied"]').length > 0 );
						
						// Adjust state
						if( mismatch ) $parent_post.attr('rel','denied-desc-allowed');
						else $parent_post.attr('rel','denied');
						break;

				}
				
			}

		});

	}

	/**
	 * Retrieve pending edits for the specified post type
	 */
	var getExistingEdits = function( post_type ) {

		// fetch existing edits
		var $edits_field = $('#buse-edits-' + post_type );
		var edits = $edits_field.val() || '';

		// Convert to object
		return edits ? JSON.parse(edits) : {};
	}

	/**
	 * Merge existing edits with new ones for the specified post type
	 */
	var commitEdits = function( edits, post_type ) {

		var $edits_field = $('#buse-edits-' + post_type );
					
		// Update input
		$edits_field.val( JSON.stringify(edits) );

	}

	/* Perm Stats */

	/**
	 * Stats widget -- permissions count
	 *
	 * @todo redo this logic
	 */
	var updatePermStats = function( count, post_type ) {

		var $container = $('#group-stats-permissions'),
			$stats_el = $('#' + post_type + '-stats'),
			$diff_el = $('#' + post_type + '-pending-diff'),
			start_count = 0;

		if( $diff_el.length == 0 )
			$diff_el = $('<span id="' + post_type + '-pending-diff" class="perm-stats-diff" data-count="0"></span>').appendTo($stats_el);

		// Grab existing count
		start_count = parseInt( $diff_el.data('count') );

		// Check total count for post type with incoming edits
		var total = start_count + count;
		var count_str = '';

		// Generate diff string
		if( total > 0 ) {
			count_str = ' ( +' + total +' )';
			$diff_el.addClass('positive');
		} else if( total < 0 ) {
			count_str = ' ( ' + total +' )';
			$diff_el.addClass('negative');
		} else {
			$diff_el.removeClass('positive negative');
		}

		// Update diff count
		$diff_el.data('count',total).text(count_str);

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
			
		}
				
	});

});