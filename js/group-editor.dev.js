jQuery(document).ready(function($){

	// Globals
	var $members_list = $('#group-member-list');
	var $nav_links = $('a.nav-link');

	// _______________________ Navigation Tabs ________________________

	$nav_links.click(function(e){
		e.preventDefault();

		var $tab = $('a.nav-tab[href=' + this.hash + ']');
		var $target = $(this.hash);

		// Update hidden inputs
		$input = $target.hasClass('group-panel') ? $('#tab') : $('#perm_panel');
		$input.val($tab.data('target'));

		// Switch panels
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
			cache: false,
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
			cache: false,
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

	// ______________________ PERMISSIONS PANEL ______________________

	/**
	 * Load post type editors dynamically on click
	 */
	var loadPermissionsPanel = function( $panel ) {

		var $editor = $panel.find('.perm-editor').first();

		// Load appropriate editor
		if( $editor.hasClass('hierarchical') ) {
			
			loadHierarchicalEditor( $editor );

		} else {

			loadFlatEditor( $editor );
		}

		// Load toolbar
		loadToolbar( $panel, $editor );

		// Load overlay
		loadOverlay( $editor );

		$panel.addClass('loaded');
	}

	/**
	 * Permissions editor loading on post type tab click
	 */
	$('#perm-tab-container').delegate( 'a', 'click', function(e) {

		var $panel = $($(this).attr('href'));
		
		if( ! $panel.hasClass('loaded') ) {
	
			loadPermissionsPanel( $panel );
	
		}

	});

// _____________________ PERM EDITOR TOOLBAR ____________________

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

			// Clear any selections
			hideOverlay( $editor );

			// Render post list
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

			// Clear any selections
			hideOverlay( $editor );

			// Render post list
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

	// _______________________ PERMISSIONS OVERLAY _______________________

	// @todo test
	// @todo encapsulate in a class
	
	var overlayStates = {
		'allowed' : {
			'label' : 'Deny Editing',
			'class' : 'allowed'
		},
		'allowed-desc-denied' : {
			'label' : 'Deny Editing',
			'class' : 'allowed'
		},
		'denied' : {
			'label' : 'Allow Editing',
			'class' : 'denied'
		},
		'denied-desc-allowed' : {
			'label' : 'Allow Editing',
			'class' : 'denied'
		}
	}

	var loadOverlay = function( $editor ) {

		// Insert markup
		var inner = '<a href="#" class="buse-action"><ins class="buse-icon">&nbsp;</ins></a>';
		var outer = '<span class="buse-overlay inactive"></span>';

		// Start hidden
		var o = $(outer).html(inner).insertAfter($editor);

		// Trigger event on click
		o.delegate( '.buse-action', 'click', function(e){

			e.stopPropagation();
			e.preventDefault();

			// Hide overlay by default
			hideOverlay( $editor );

			// Provide hook for handling click actions
			$editor.trigger('overlay_clicked.buse');

		});

	}

	var showOverlay = function( $el, $ed ) {

		// Generate appropriate label
		var status = $el.attr('rel');
		var $a = $el.children('a:first').first();
		var $container = $('#perm-panel-container');
		var $o = $ed.siblings('.buse-overlay').first();

		// Current state
		var st = overlayStates[status];

		// Setup positioning
		var pos = {
			of: $a,
			my: 'left center',
			at: 'right center',
			within: $container,
			offset: '15 0',
			collision: 'fit none'
		};

		// Switch label
		$o.find('.buse-action').html('<ins class="buse-icon">&nbsp;</ins> ' + st['label'] );

		// Display
		$o.removeClass( 'inactive allowed allowed-desc-denied denied denied-desc-allowed')
			.addClass( st['class'] )
			.position( pos );

	}

	var hideOverlay = function( $ed ) {

		// Remove all classes on the link
		$ed.siblings('.buse-overlay')
			.removeClass( 'allowed allowed-desc-denied denied denied-desc-allowed' )
			.addClass('inactive')[0].removeAttribute('style');

	}

	// _______________________ Flat Permissions Editor _______________________

	/**
	 * Creates a flat permissions editor
	 */
	var loadFlatEditor = function( $editor ) {
		
		var pt = $editor.data('post-type');

		// Attach event handlers
		attachFlatEditorHandlers( $editor );

		// Event binding
		$editor
			.bind( 'posts_loaded.buse', function(e, data ) {

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

			})
			.bind( 'select_post.buse', function(e, data) {
				showOverlay( data.post, $editor );
				
			})
			.bind( 'deselect_all.buse', function(e, data) {
				hideOverlay( $editor );

			})
			.bind( 'overlay_clicked.buse', function(e) {
				
				$editor.find('.perm-item-selected').each(function(){

					// Update permissions
					updatePostPermissions( $(this), $editor );

					// Deselect
					$(this).removeClass('perm-item-selected');

				});

			});

		// Display initial posts
		displayPosts( $editor, { post_type: pt });

	}

	/**
	 * Translates native events in to custom events for consumption
	 */
	var attachFlatEditorHandlers = function( $editor ) {

		// Post selection
		$editor.delegate( 'a', 'click', function(e) {

			e.preventDefault();
			e.stopPropagation();

			// Keep track of current selection
			$post = $(this).parent('li').first();

			// Remove previous selections 
			$post.siblings('li.perm-item-selected').each( function() {

				$(this).removeClass('perm-item-selected');

				$editor.trigger( 'deselect_post.buse', { post: $(this) } );

			});

			$post.addClass('perm-item-selected');

			// Trigger a post selection event
			$editor.trigger( 'select_post.buse', { post: $post } );

		});

		// Deselect all on click within parent perm panel
		$editor.closest('.perm-panel').bind( 'click', function(e) {

			$editor.trigger( 'deselect_all.buse' );

		});

	}


	// _______________________ Hierarchical Permissions Editor _______________________

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
				cache: false,
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

		// Attach handlers and instantiate
		$editor
			.bind('loaded.jstree', function( event, data ) {

				// Lazy loading causes huge performance issues in IE < 8
				if( $.browser.msie == true &&  parseInt($.browser.version, 10) < 8 )
					return;

				// Start lazy loading once tree is fully loaded
				$(this).find('ul > .jstree-closed').each( function(){
					var $post = $(this);
					data.inst.load_node( $(this), function(){
						correctIconsForSection($post);
					});
				});

			})
			.bind('select_node.jstree', function( event, data ) {

				if( data.inst.is_selected( data.rslt.obj ) ) {

					showOverlay( data.rslt.obj, $editor );

				} 

			})
			.bind('deselect_node.jstree', function( event, data ) {

				hideOverlay( $editor );

			})
			.bind('deselect_all.jstree', function( event, data ) {

				hideOverlay( $editor );

			})
			.bind( 'overlay_clicked.buse', function(e) {
				
				$editor.jstree( 'get_selected' ).each( function(){

					// Update permissions
					updatePostPermissions( $(this), $editor );

					// Deselect
					$editor.jstree('deselect_node', $(this) );

				});

			})
			.jstree(options);	// create the tree


		// Deselect all on click within parent perm panel
		$editor.closest('.perm-panel').bind( 'click', function(e) {

			/* 
			Need to make sure we're not in the process of selecting a node,
			as the jstree event select_node.jstree does not allows us to stop click
			events from bubbling up on selection
			*/

			// Bail if click target is a selected anchor (label) or ins (icon)
			if( $(e.target).hasClass('jstree-clicked') || $(e.target).parents('.jstree-clicked').length )
				return;

			// Otherwise force deselection
			if( $editor.jstree('get_selected').length )
				$editor.jstree( 'deselect_all' );

		});

	};

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
				'denied-desc-unknown' : {
					clickable	: true,
					renameable	: false,
					deletable	: false,
					creatable	: false,
					draggable	: false,
					max_children	: -1,
					max_depth	: -1,
					valid_children	: "all",
					icon: {
						image: buse_config.pluginUrl + "/images/group_perm_denied_desc_unknown.png"
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
				},
				'allowed-desc-unknown' : {
					clickable	: true,
					renameable	: false,
					deletable	: false,
					creatable	: false,
					draggable	: false,
					max_children	: -1,
					max_depth	: -1,
					valid_children	: "all",
					icon: {
						image: buse_config.pluginUrl + "/images/group_perm_allowed_desc_unknown.png"
					}
				}
			}
		},
		ui: {
			select_limit: 1
		}
	};
	
	// ____________________ PERM EDITOR API _____________________

	/**
	 * Display posts, fetched dynamically based on query args
	 */
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
			cache: false,
			success: function(response) {

				if( editorData.query.offset ) {
					$editor.append(response);
				} else {
					$editor.html(response);
				}

				// Modify icons looking for edits
				$editor.trigger( 'posts_loaded.buse', { posts : response } );

			},
			error: function(response){
				//console.log(response);
			}
		});

	}

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
			$root_post = $post.parentsUntil( '#' + $editor.attr('id'), 'li' ).last();

			// Root post will be empty if we are a top-level post
			if( $root_post.length ) {
				// Correct icons for both descendents and ancestors affected by this change
				correctIconsForSection( $root_post );
			}

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

					case 'allowed': case 'allowed-desc-denied': case 'allowed-desc-unknown':
						mismatch = ($(this).find('li[rel="denied"],li[rel="denied-desc-allowed"],li[rel="denied-desc-unknown"]').length > 0 );
						
						// Adjust state
						if( mismatch ) $parent_post.attr('rel','allowed-desc-denied');
						else $parent_post.attr('rel','allowed');

						break;

					case 'denied': case 'denied-desc-allowed': case 'denied-desc-unknown':
						mismatch = ( $(this).find('li[rel="allowed"],li[rel="allowed-desc-denied"],li[rel="allowed-desc-unknown"]').length > 0 );
						
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

	// ____________________ PERM STATS ______________________ 

	/**
	 * Stats widget -- permissions count
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
			$diff_el.removeClass('negative').addClass('positive');
		} else if( total < 0 ) {
			count_str = ' ( ' + total +' )';
			$diff_el.removeClass('positive').addClass('negative');
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

	// ___________________ ON PAGE LOAD _____________________

	// Initial loading
	var $initialPanel = $('#perm-panel-container').find('.perm-panel.active').first();
	if( $initialPanel.length ) {
		loadPermissionsPanel( $initialPanel );
	}


});