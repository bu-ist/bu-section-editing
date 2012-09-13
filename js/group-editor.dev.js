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

	var GROUP_NAME_MAX_LENGTH = 60;
	
	$('#edit-group-name').blur(function(e){

		var name = $(this).val()

		// Auto truncate name field
		if( name.length > GROUP_NAME_MAX_LENGTH ) {
			$(this).val( name.slice( 0, GROUP_NAME_MAX_LENGTH - 1 ) );
		}

		$('#group-stats-name').html($(this).val());
	});

	// _______________________ Group Members ________________________
	
	$('.member:not(.active)').appendTo('#inactive-members');

	// Remove a member from the editor group list
	$members_list.delegate( 'a.remove_member', 'click', function(e){
		e.preventDefault();

		$(this).parent('.member').removeClass('active').slideUp( 'fast', function() {
			
			// Move to #inactive-members bucket
			$(this)
				.appendTo('#inactive-members')
				.find('input[type="checkbox"]').removeAttr('checked');

			// Update member count
			updateMemberCount();

		});

	});

	// Get ID's for users that belong to the current group
	var get_active_member_ids = function() {

		return $.map( $('li.member.active input[type="checkbox"]'), function(o) { return o.value } );

	}

	/**
	 * Compare user input to site section editors
	 *
	 * Returns the original list filtered to include only valid section editors that match
	 * the search term.
	 */
	var _match_section_editors_with_term = function( list, term ) {


		return $.grep( list, function( el, i ) {

			term = term.toLowerCase();

			// Search for term in applicable fields
			return ( 
				el.user.is_section_editor &&
				( 
					el.user.display_name.toLowerCase().indexOf( term ) != -1 ||
					el.user.login.toLowerCase().indexOf( term ) != -1 ||
					el.user.nicename.toLowerCase().indexOf( term ) != -1 ||
					el.user.email.toLowerCase().indexOf( term ) != -1
				)
			);

		});

	}

	/**
	 * Removes users from a list if they are already a member of the current group
	 */
	var _remove_existing_members = function( list ) {

		return $.grep( list, function( el, i ) {
			return ! _is_existing_member( el.user );
		});

	}

	var _is_existing_member = function( user ) {

		var existing_ids = get_active_member_ids();
		return $.inArray( user.id, existing_ids ) > -1;

	}

	/**
	 * Match user input string to existing user for this site
	 */
	var _translate_input_to_user = function( input ) {

		var results = $.grep( buse_site_users, function( el, i ) {

			var match = input.toLowerCase();

			// Search for term in applicable fields
			return ( 
				el.user.display_name.toLowerCase() == match ||
				el.user.login.toLowerCase()  == match  ||
				el.user.nicename.toLowerCase()  == match ||
				el.user.email.toLowerCase() == match
				);

		});


		// Not a valid user -- pass through input
		if( results.length > 1 || results.length == 0 )
			return false;

		return results[0].user;
		
	}

	/**
	 * Find users tool - autocomplete from current site's pool of section editors
	 *
	 * Modeled after user input on wp-admin/user-new.php post-WP 3.3
	 * @uses jquery-ui-autocomplete
	 */
	var add_member_input = $( '.buse-suggest-user' ).autocomplete({
		source: function( request, response) {

			// Filter section editors based on term
			var filtered = _match_section_editors_with_term( buse_site_users, request.term );
			var filtered = _remove_existing_members( filtered );
			var results = $.map( filtered, function(o) { return o.autocomplete; });

			// Let autocomplete process valid results
			response( results );

		},
		delay:     500,
		minLength: 2,
		position:  ( 'undefined' !== typeof isRtl && isRtl ) ? { my: 'right top', at: 'right bottom', offset: '0, -1' } : { offset: '0, -1' },
		open:      function() { $(this).addClass('open'); },
		close:     function() { $(this).removeClass('open'); }
	});

	/* Add Members */

	$('#add_member').bind( 'click', function(e){

		e.preventDefault();
		handle_member_add();

	});

	$('#user_login').keypress( function(e) {

		// Enter key
		if( e.keyCode == '13' ) {

			e.preventDefault();
			handle_member_add();

		}

	});

	/**
	 * Helper function for adding errors while attempting to add group members
	 */
	var add_member_error = function( message, message_class ) {
		
		if( typeof message_class === "undefined" ) message_class = 'error';

		$('#members-message').attr('class', message_class ).html('<p>' + message + '</p>').fadeIn();

	}

	/**
	 * Helper function for removing error messages from members panel
	 */
	var remove_member_error = function() {

		$('#members-message').fadeOut('fast', function(e){$(this).attr('class','').html('');});	

	}

	/**
	 * Processes input for adding users to group
	 */
	var handle_member_add = function() {

		// Remove extra white-space
		var input = $.trim($('#user_login').val());

		if( input ) {

			// Clear any autocomplete results
			add_member_input.autocomplete('search','');

			// Attempt to translate user input to valid login
			var user = _translate_input_to_user( input );
			var url = buse_config.usersUrl;

			// Add member to this group
			if( user ) {
				
				if( ! user.is_section_editor ) {

					// User is not capable of being added to section editing groups
					// @todo rethink this error message...
					url += '?s=' + user.login;
					add_member_error('<b>' + user.display_name + '</b> is not a section editor.  Before you can assign them to a group, you must change their role to "Section Editor" on the <a href="'+ url +'">users page</a>.')

				} else if( _is_existing_member( user ) ) {

					// User is already a member
					add_member_error('<b>' + user.display_name + '</b> is already a member of this group.')

				} else {
					
					// Remove any existing errors
					remove_member_error();

					// Add the member
					add_member( user );
			
				}

			} else {

				// No user exists on this site
				// @todo rethink this error message...
				url = buse_config.userNewUrl;
				add_member_error('<b>' + input + '</b> is not a member of this site.  Please <a href="'+ url +'">add them to your site</a> with the "Section Editor" role.')
			
			}

		}

		// Clear login input, keep focus
		$('#user_login').val('').focus();

	}

	/**
	 * Updates member list with new member data
	 */
	var add_member = function( user ) {

		// Add member
		$('#member_' + user.id ).attr('checked','checked')
			.parent('.member')
			.addClass('active')
			.appendTo($members_list)
			.slideDown('fast');

		// Update counts
		updateMemberCount();

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
		loadToolbars( $panel, $editor );

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
	var loadToolbars = function( $panel, $editor ) {

		// Search
		$panel.delegate( 'button.perm-search', 'click', function(e){
			e.preventDefault();

			var post_type = $editor.data('post-type');
			var term = $('#perm-search-' + post_type ).val();
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

		/* Pagination */

		// Pagination using links
		$panel.find('.pagination-links').delegate( 'a', 'click', function(e){
			e.preventDefault();

			if( $(this).hasClass('disabled') )
				return;

			var target = $(this).attr('class');
			var current = parseInt( $(this).parent().find('.current-page').val() );
			var last = parseInt( $(this).parent().find('.total-pages').text() );
			var paged = 1;

			switch( target ) {
				case 'first-page':
					paged = 1;
					break;

				case 'prev-page':
					paged = current - 1;
					break;

				case 'next-page':
					paged = current + 1;
					break;

				case 'last-page':
					paged = last;
					break;
			}

			setPageForEditor( paged, $editor );

		});

		// Manually advanced page using current page text input
		$panel.delegate( 'input.current-page', 'keypress', function(e) {
			
			if( e.keyCode == '13' ) {
				e.preventDefault();

				var paged = $(this).val();
				var max_page = parseInt( $(this).parent().find('.total-pages').text());

				if( paged < 1 )
					$(this).val(1);
				else if( paged > max_page )
					$(this).val( max_page );

				paged = $(this).val();

				setPageForEditor( paged, $editor );
			}

		});

		// Search
		$panel.delegate( 'input.perm-search', 'keypress', function(e) {
			if( e.keyCode == 13 ) {
				e.preventDefault();
				$(this).siblings('button').first().click();
			}
		});

		/* Bulk editor */

		// Open editor
		$panel.delegate( 'a.perm-editor-bulk-edit', 'click', function(e) {
			e.preventDefault();
			$panel.addClass('bulk-edit');
		});

		// Close editor
		$panel.delegate( 'a.bulk-edit-close', 'click', function(e) {
			e.preventDefault();
			$panel.removeClass('bulk-edit');
		});

		// Select all behavior toolbar checkbox
		$panel.delegate( '.bulk-edit-select-all', 'click', function(e) {
			$editor.find('input[type="checkbox"]').attr( 'checked', this.checked );
		});

		// Apply bulk actions
		$panel.delegate( '.bulk-edit-actions button', 'click', function(e) {
			e.preventDefault();

			var $selector = $(this).siblings('select');
			var action = $selector.val();
			var selections = $editor.find('input[type="checkbox"]:checked');

			if( selections.length > 0 ) {

				if( action == 'allowed' || action == 'denied' ) {

					selections.each( function() {
						var $post = $(this).parents('li');
						$post.attr('rel', action );
						setPostPermissions( action, $post, $editor );

					});

				}

			}

			// Reset bulk actions to default state
			$panel.find('.bulk-edit-select-all').attr('checked',false );
			$selector.val('none');
			selections.attr( 'checked', false );

		});

		/* Hierarchical editor */

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

	/**
	 * Set the current page for the given flat editor
	 */
	var setPageForEditor = function( page, $editor ) {

		var $panel = $editor.closest('.perm-panel');
		var post_type = $editor.data('post-type');

		var args = {
			'post_type': post_type,
			'query': {
				paged: page
			}
		};

		// Possibly append search term to query
		var term = $('#perm-search-' + post_type ).val();

		if( term.length > 0 )
			args['query']['s'] = term;

		// Clear any selections
		hideOverlay( $editor );

		// Reset bulk edit toolbar
		$panel.find('.bulk-edit-select-all').attr('checked',false );
		$panel.find('.bulk-edit-actions select').val('none');

		// Render post list
		displayPosts( $editor, args );

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
		'allowed-desc-unknown' : {
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
		},
		'denied-desc-unknown' : {
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

		if( typeof st == 'undefined' )
			return;

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

					var $post = $(this);
					var state = toggleState( $post );

					// Update permissions
					setPostPermissions( state, $post, $editor );

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
						group_id : $('#group_id').val() || -1,
						post_type : post_type,
						query: {
							child_of : n.attr ? n.attr('id').substr(1) : 0
						}
					}
				},
				success: function( response ) {
					return response.posts;
				},
				error: function( response ) {
					// @todo handle error
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

					// Load using API -- they require callback functions, but we're
					// handling actions in the load_node.jstree even handler below
					// so we just pass empty functions
					data.inst.load_node( $(this), function(){}, function(){} );
				});

			})
			.bind('load_node.jstree', function( event, data ) {

				// Correct state post-load for all non-root nodes
				if( data.rslt.obj != -1 ) {
					correctIconsForSection(data.rslt.obj);
				}

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

					var $post = $(this);
					var state = toggleState( $post );

					// Update permissions
					setPostPermissions( state, $(this), $editor );

					// Deselect
					$editor.jstree('deselect_node', $post );

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
						image: buse_config.pluginUrl + "/images/group_perms_sprite.png",
						position: "-60px 0"
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
						image: buse_config.pluginUrl + "/images/group_perms_sprite.png",
						position: "-60px 0"
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
						image: buse_config.pluginUrl + "/images/group_perms_sprite.png",
						position: "2px 0"
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
						image: buse_config.pluginUrl + "/images/group_perms_sprite.png",
						position: "-100px 0"
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
						image: buse_config.pluginUrl + "/images/group_perms_sprite.png",
						position: "-40px 0"
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
						image: buse_config.pluginUrl + "/images/group_perms_sprite.png",
						position: "-20px 0"
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
						image: buse_config.pluginUrl + "/images/group_perms_sprite.png",
						position: "-120px 0"
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
			group_id : $('#group_id').val() || -1,
			query : {}
		}

		if( typeof query !== undefined )
			$.extend( editorData, query );

		$editor.addClass('loading');

		// Set up loading spinner
		if( editorData.query.offset ) {
			$editor.append('<span class="loader">Loading...</span>');
		} else {
			$editor.html('<span class="loader">Loading...</span>');
		}
		
		$.ajax({
			url : ajaxurl,
			type: 'GET',
			data: editorData,
			cache: false,
			success: function(response) {

				if( editorData.query.offset ) {
					$editor.append(response.posts);
				} else {
					$editor.html(response.posts);
				}

				var pageVars = { 
					page: response.page,
					found_posts: response.found_posts,
					post_count: response.post_count,
					max_num_pages: response.max_num_pages
				};

				updatePaginationForEditor( pageVars, $editor );

				// Modify icons looking for edits
				$editor.trigger( 'posts_loaded.buse', { posts : response.posts } );
				$editor.removeClass('loading');

			},
			error: function(response){
				//console.log(response);
			}
		});

	}

	var updatePaginationForEditor = function( pageVars, $editor ) {

		var post_type = $editor.data('post-type');
		var group_id = $('#group_id').val();

		$pagination 	= $('#perm-editor-pagination-' + post_type );
		$total_items 	= $pagination.find('.displaying-num');
		$current_page 	= $pagination.find('.current-page');
		$total_pages 	= $pagination.find('.total-pages');
		$first_page 	= $pagination.find('.first-page');
		$prev_page 		= $pagination.find('.prev-page');
		$next_page 		= $pagination.find('.next-page');
		$last_page 		= $pagination.find('.last-page');

		// Update found posts
		var noun = ( parseInt( pageVars.found_posts ) == 1 ) ? ' item' : ' items';
		$total_items.text( pageVars.found_posts + noun );

		// Update page counts (current page, total pages)
		$current_page.val( pageVars.page );
		$total_pages.text( pageVars.max_num_pages );

		// Update classes for first-page, prev-page, next-page, last-page (disabled or not)
		if( pageVars.page == 1 ) {
			$first_page.addClass('disabled');
			$prev_page.addClass('disabled');
		} else {
			$first_page.removeClass('disabled');
			$prev_page.removeClass('disabled');
		}

		if( pageVars.page == pageVars.max_num_pages ) {
			$next_page.addClass('disabled');
			$last_page.addClass('disabled');
		} else {
			$next_page.removeClass('disabled');
			$last_page.removeClass('disabled');
		}
	}

	/**
	 * The user has allowed/denied a specific node
	 */
	var setPostPermissions = function( state, $post, $editor ) {

		// Set visual state
		$post.attr( 'rel', state );

		// Hierarchical post types
		if( $editor.hasClass('hierarchical') ) {

			var $jstree = $.jstree._reference( $editor );

			if( $post.hasClass('jstree-closed') ) {

				// Need to load node first
				$jstree.open_node( $post, function() {

					// Open all children to expose permissions cascade
					$jstree.open_all( $post );

					// Diff the changes
					processUpdatesForPost( state, $post, $editor );

				});

			} else {

					// Diff the changes
					processUpdatesForPost( state, $post, $editor );

			}

		}

 		// Flat post types
		if( $editor.hasClass('flat') ) { 
			
			// Diff the changes
			processUpdatesForPost( state, $post, $editor );
		
		}

	}

	/**
	 * Returns the toggled state for the given node 
	 */
	var toggleState = function( $node ) {
		
		var previous = $node.data('perm');
		var state = '';

		if( previous == 'allowed' ) return 'denied';
		if( previous == 'denied' ) return 'allowed';

		return 'denied';

	}

	/**
	 * Update post permissions state and add to pending edits
	 */
	var processUpdatesForPost = function( state, $post, $editor ) {

		var id = $post.attr('id').substr(1);
		var post_type = $editor.data('post-type');

		// Track changes
		var edits = getExistingEdits( post_type );
		var count = 0;

		// Set our persistent data attr first
		prev_state = $post.data('perm');
		$post.data('perm',state);
		edits[id] = state;

		if( prev_state != state ) {
			if( state == 'allowed' ) count +=1;
			else count -= 1;
		}

		// Hierarchical post types need extra love
		if( $editor.hasClass('hierarchical') ) {

			// Fetch all children
			var $children = $post.find('li');

			// Cascade permissions
			$children.each(function(index, child) {

				var existing_perm = $(child).data('perm');
				var child_id = $(this).attr('id').substr(1);

				// Note incoming edit if state changed
				if( existing_perm != state ) {

					$(child).data('perm', state);
					edits[child_id] = state;

					if( state == 'allowed' ) count += 1;
					else count -= 1;

				}

				// Change icon no matter what
				$(child).attr('rel', state);

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
	$('#group-edit-form').submit(function(e){
		window.onbeforeunload = null;

		// Name
		var name = $.trim($('#edit-group-name').val());

		if( name.length < 1 ) {
			errorOut( 'Please give your group a name before saving.');
			return false;
		}

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

	/**
	 * Present the user with an error message
	 */ 
	var errorOut = function( msg ) {

		var $container = $('#message');

		if( $container.length ) {

			$container.attr('class','error');
			$container.html( '<p>' + msg + '</p>' );

		} else {

			$container = $('<div id="message" class="error">');
			$container.html( '<p>' + msg + '</p>' );

			$('.form-wrap').before( $container );

		}

	}

	// ___________________ ON PAGE LOAD _____________________

	// Initial loading

	// Image preloading for dynamically loaded images
	if( document.images ) {

		var permSprite = new Image();
		var permSpinner = new Image();

		permSprite.src = buse_config.pluginUrl + "/images/group_perms_sprite.png";
		permSpinner.src = buse_config.pluginUrl + "/images/loading.gif";

	}

	var $initialPanel = $('#perm-panel-container').find('.perm-panel.active').first();
	if( $initialPanel.length ) {
		loadPermissionsPanel( $initialPanel );
	}


});