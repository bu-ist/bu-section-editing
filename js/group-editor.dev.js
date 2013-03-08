jQuery(document).ready(function($){
	var Nav;

	// Check dependencies for hierarchical perm editors
	if((typeof bu !== 'undefined') &&
		(typeof bu.plugins.navigation !== 'undefined') &&
		(typeof bu.plugins.navigation.tree !== 'undefined'))
			Nav = bu.plugins.navigation;

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

		var name = $.trim($(this).val());

		// Auto truncate name field
		if( name.length > GROUP_NAME_MAX_LENGTH ) {
			name = name.slice( 0, GROUP_NAME_MAX_LENGTH - 1 );
			$(this).val(name);
		}

		if( name.length < 1 ) {
			addNotice(buse_group_editor_settings.nameRequiredNotice);
			return false;
		}

		// Remove previously existing notices
		removeNotice();

		$('#group-stats-name').html(name);
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

		return $.map( $('li.member.active input[type="checkbox"]'), function(o) { return parseInt(o.value, 10) } );

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

			// Add member to this group
			if( user ) {

				if( ! user.is_section_editor ) {

					// User is not capable of being added to section editing groups
					// @todo rethink this error message...
					var msg = '<b>' + user.display_name + '</b> ' + $('<p/>').html(buse_group_editor_settings.userWrongRoleNotice).text();
					addNotice( msg, 'members-message' );

				} else if( _is_existing_member( user ) ) {

					// User is already a member
					var msg = '<b>' + user.display_name + '</b> ' + $('<p/>').html(buse_group_editor_settings.userAlreadyMemberNotice).text();
					addNotice( msg, 'members-message' );

				} else {

					// Remove any existing errors
					removeNotice( 'members-message' );

					// Add the member
					add_member( user );

				}

			} else {

				// No user exists on this site
				// @todo rethink this error message...
				var msg = '<b>' + input + '</b> ' + $('<p/>').html(buse_group_editor_settings.userNotExistsNotice).text();
				addNotice( msg, 'members-message' );

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
		var count, label;

		count = $members_list.children('.member').length;
		if (count == 1) {
			label = buse_group_editor_settings.memberCountSingularLabel;
		} else {
			label = buse_group_editor_settings.memberCountPluralLabel;
		}

		$('.member-count').html(count);
		$('.member-count-label').text(label);

	}

	// ______________________ PERMISSIONS PANEL ______________________

	/**
	 * Load post type editors dynamically on click
	 */
	var loadPermissionsPanel = function( $panel ) {
		var $editor = $panel.find('.perm-editor').first();

		// Load appropriate editor
		if( $editor.hasClass('hierarchical') ) {

			if (typeof Nav === 'undefined') {
				alert(buse_group_editor_settings.navDepAlertText);
				$editor.html(buse_group_editor_settings.navDepEditorText);
			} else {
				loadHierarchicalEditor( $editor );
			}

		} else {

			loadFlatEditor( $editor );

		}

		// Load toolbar
		loadToolbars( $panel, $editor );

		// Handle permission actions
		$editor.delegate('.edit-perms', 'click', function (e) {
			var $button = $(e.currentTarget),
				$post = $button.closest('li'),
				classes = $button.attr('class'),
				action, isEditable;

			e.stopPropagation();
			e.preventDefault();

			if (classes.indexOf('allowed') > -1 ) {
				action = 'allowed';
			} else if (classes.indexOf('denied') > -1 ) {
				action = 'denied';
			}

			// Update post
			isEditable = action == 'allowed' ? true : false;
			setPostPermissions($post, isEditable, $editor);

			// Notify
			$editor.trigger('perm_updated', [{post: $post, action: action }]);

		});

		// Deselect all on click outside active perm panel
		$(document).bind('click', function (e) {

			var $active_perm_panel = $('.perm-panel.active');
			var $editor = $('.perm-editor', $active_perm_panel);
			var clickedActivePanel = $.contains( $active_perm_panel[0], e.target );

			if (!clickedActivePanel) {
				if ($editor.hasClass('hierarchical') && typeof $editor.jstree !== 'undefined') {
					$editor.jstree('deselect_all');
				} else {
					$editor.find('.perm-item-selected').removeClass('perm-item-selected');
				}
			}

		});

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

		// Toggle bulk edit mode
		$panel.delegate( 'a.perm-editor-bulk-edit', 'click', function(e) {
			e.preventDefault();
			var $a = $(this);

			if ($panel.hasClass('bulk-edit')) {
				$a.removeClass('bulk-edit-close').attr('title',buse_group_editor_settings.bulkEditOpenTitle).text(buse_group_editor_settings.bulkEditOpenText);
				$panel.removeClass('bulk-edit');
			} else {
				$a.addClass('bulk-edit-close').attr('title',buse_group_editor_settings.bulkEditCloseTitle).text(buse_group_editor_settings.bulkEditCloseText);
				$panel.addClass('bulk-edit');
			}

			// Reset selections & bulk editor state
			$editor.find('.perm-item-selected').removeClass('perm-item-selected');
			$panel.find('input[type="checkbox"]').attr('checked', false);
			$('.bulk-edit-actions select').val('none');

		});

		// Select all behavior toolbar checkbox
		$panel.delegate('.bulk-edit-select-all', 'click', function(e) {
			var $allposts = $editor.find('li');
			$allposts.children('input[type="checkbox"]').attr( 'checked', this.checked );
		});

		// Apply bulk actions
		$panel.delegate('.bulk-edit-actions button', 'click', function(e) {
			e.preventDefault();

			var $selector = $(this).siblings('select');
			var action = $selector.val();
			var selections = $editor.find('input[type="checkbox"]:checked');

			if( selections.length > 0 ) {

				if( action == 'allowed' || action == 'denied' ) {

					selections.each(function () {
						var $post = $(this).parents('li');
						var isEditable = action == 'allowed' ? true : false;
						setPostPermissions($post, isEditable, $editor);
					});

				}

			}


			// Reset bulk actions to default state
			$panel.find('.bulk-edit-select-all').attr('checked', false);
			$selector.val('none');
			selections.attr('checked', false);

		});

		/* Hierarchical editor */

		// Expand all
		$panel.delegate('a.perm-tree-expand', 'click', function(e) {
			e.preventDefault();
			if(typeof $.jstree !== 'undefined') {
				$.jstree._reference($editor).open_all();
			}
		});

		// Collapse all
		$panel.delegate('a.perm-tree-collapse', 'click', function(e) {
			e.preventDefault();
			if(typeof $.jstree !== 'undefined') {
				$.jstree._reference($editor).close_all();
			}
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

		// Reset bulk edit toolbar
		$panel.find('.bulk-edit-select-all').attr('checked',false );
		$panel.find('.bulk-edit-actions select').val('none');

		// Render post list
		displayPosts( $editor, args );

	}

	// Toggle permissions action based in current value
	var togglePermAction = function ($el) {
		var previous = $el.hasClass('allowed') ? 'allowed' : 'denied';
		var next = ( previous == 'allowed' ) ? 'denied' : 'allowed';
		var label = '';

		if (next == 'allowed') {
			label = buse_group_editor_settings.permAllowLabel;
		} else {
			label = buse_group_editor_settings.permDenyLabel;
		}

		$el.removeClass(previous).addClass(next).text(label);
	};

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
			.bind('posts_loaded.buse', function (e, data) {

				// Merge incoming server state with pending edits
				var edits = $editor.data('perm-edits') || {"allowed":[], "denied": []},
					i, post_id, $p;

				for (i = 0; i < edits["allowed"].length; i = i + 1) {
					post_id = edits["allowed"][i];
					$p = $editor.find('#p' + post_id );
					if ($p.length) {
						setPostPermissions($p, true, $editor);
					}
				}
				for (i = 0; i < edits["denied"].length; i = i + 1) {
					post_id = edits["denied"][i];
					$p = $editor.find('#p' + post_id);
					if ($p.length) {
						setPostPermissions($p, false, $editor);
					}
				}

			});

		// Display initial posts
		displayPosts( $editor, { post_type: pt });

	}

	/**
	 * Translates native events in to custom events for consumption
	 */
	var attachFlatEditorHandlers = function( $editor ) {

		// Post selection
		$editor.delegate( 'a', 'click', function (e) {
			e.preventDefault();
			e.stopPropagation();

			// Keep track of current selection
			var $post = $(this).parent('li').first();

			// Remove previous selections
			$post.siblings('li.perm-item-selected').each( function () {
				$(this).removeClass('perm-item-selected');
			});

			$post.addClass('perm-item-selected');

		});

		$editor.bind('perm_updated', function (e, data ) {

			// Deselect
			data.post.removeClass('perm-item-selected');

		});

	}


	// _______________________ Hierarchical Permissions Editor _______________________

	/**
	 * Create a jstree instance for each hierarchical post type
	 */
	var loadHierarchicalEditor = function( $editor ) {
		var settings = {
			el: '#' + $editor.attr('id'),
			groupID: $('#group_id').val() || -1,
			postType: $editor.data('post-type')
		};
		$.extend(settings, buse_perm_editor_settings );

		// Create nav tree
		Nav.tree( 'buse_perm_editor', settings );

		// Attach handlers and instantiate
		$editor
			.bind('load_node.jstree', function( event, data ) {

				// Correct state post-load for all non-root nodes
				if( data.rslt.obj != -1 ) {
					correctIconsForSection(data.rslt.obj);
				}

			})
			.bind('perm_updated', function (e, data) {
				var $post = data.post;

				if ($post.hasClass('jstree-closed')) {
					$editor.jstree('open_all', $post);
				}

				$editor.jstree('deselect_node', $post);

			});

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
			$editor.append('<li class="loader">' + buse_group_editor_settings.loadingText + '</li>');
		} else {
			$editor.html('<ul><li class="loader">' + buse_group_editor_settings.loadingText + '</li></ul>');
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

		// Only show pagination if we have enough items to warrant it
		if (pageVars.max_num_pages > 1) {

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

			$pagination.show();

		} else {

			$pagination.hide();

		}
	}

	/**
	 * The user has allowed/denied a specific node
	 */
	var setPostPermissions = function( $post, isEditable, $editor ) {

		var post_type = $editor.data('post-type');
		var edits = $editor.data('perm-edits') || {'allowed':[], 'denied':[]};
		var $section;

		// Diff the changes
		processUpdatesForPost( $post, isEditable, edits );

		// Update icons for hierarchical post types
		// @todo trigger updates through notifications
		if( $editor.hasClass('hierarchical') ) {

			// Correct icons for affected section
			if ($post.parent('ul').parent('div').attr('id') != $editor.attr('id')) {
				$section = $post.parents('li:last');
			} else {
				$section = $post;
			}

			correctIconsForSection( $section );

		}

		// Update pending edits
		$editor.data('perm-edits', edits);

		// Update the stats widget counter
		// @todo trigger updates through notifications
		updatePermStats( post_type );
	}

	/**
	 * Update post permissions state and add to pending edits
	 */
	var processUpdatesForPost = function ($post, isEditable, edits ) {

		var id = $post.attr('id').substr(1);

		// Update perm action button if permission has changed
		var $button = $post.find('.edit-perms').first();
		if (isEditable != $post.data('editable')) {
			togglePermAction($button);
		}

		// Track changes
		var perm = isEditable ? 'allowed' : 'denied';
		var wasEditable = $post.data('editable-original');
		var index;

		// Update state
		$post.data('editable', isEditable);
		$post.attr('rel', perm);

		// Update pending edits
		if (wasEditable != isEditable) {

			index = $.inArray(id, edits[perm]);
			if (index === -1) {
				edits[perm].push(id);
			}

		} else {

			// Revert pending edits for this post
			index = $.inArray(id, edits['allowed']);
			if (index > -1) {
				edits['allowed'].splice(index, 1);
			}
			index = $.inArray(id, edits['denied']);
			if (index > -1) {
				edits['denied'].splice(index, 1);
			}

		}

		// Recurse if necessary
		var $children = $post.find('> ul > li');

		// Cascade permissions
		$children.each(function () {
			processUpdatesForPost($(this), isEditable, edits);
		});
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
						mismatch = $(this).find('li[rel="denied"],li[rel="denied-desc-allowed"],li[rel="denied-desc-unknown"]').length;

						// Adjust state
						if( mismatch ) {
							$parent_post.attr('rel','allowed-desc-denied');
						} else {
							$parent_post.attr('rel','allowed');
						}

						updatePostMismatchCount( $parent_post, mismatch );
						break;

					case 'denied': case 'denied-desc-allowed': case 'denied-desc-unknown':
						mismatch = $(this).find('li[rel="allowed"],li[rel="allowed-desc-denied"],li[rel="allowed-desc-unknown"]').length;

						// Adjust state
						if( mismatch ) {
							$parent_post.attr('rel','denied-desc-allowed');
						} else {
							$parent_post.attr('rel','denied');
						}

						updatePostMismatchCount( $parent_post, mismatch );
						break;
						break;

				}

			}

		});

	}

	var updatePostMismatchCount = function ($parent, mismatches) {
		var $stats = $parent.find('> a > .perm-stats');
		var child_count;
		var status = $parent.data('editable');

		if ($stats.length === 0) {
			$stats = $(' <span class="perm-stats"><ins class="jstree-icon">&nbsp;</ins><span class="label"></span></span>');
			$parent.find('> a > .title-count').after($stats);
		}

		// Clear previous state
		$stats.removeClass('allowed denied').children('.label').text('');

		if (mismatches) {
			if (status) {
				$stats.addClass('denied').children('.label').text(mismatches + ' ' + buse_group_editor_settings.permNonEditableLabel);
			} else {
				$stats.addClass('allowed').children('.label').text(mismatches + ' ' + buse_group_editor_settings.permEditableLabel);
			}
		}
	};

	// ____________________ PERM STATS ______________________

	/**
	 * Stats widget -- permissions count
	 */
	var updatePermStats = function( post_type ) {

		var $stats_el = $('#' + post_type + '-stats'),
			$diff_el = $('.perm-stats-diff', $stats_el),
			edits = $('#perm-editor-'+post_type).data('perm-edits');

		if ($diff_el.length === 0) {
			$diff_el = $('<span class="perm-stats-diff"></span>');
			$stats_el.append($diff_el);
		}

		var stats = [], perm, sign;

		for (perm in edits) {
			if (edits[perm].length) {
				sign = perm === 'allowed' ? '+' : '-';
				stats.push('<span class="' + perm + '">' + sign + edits[perm].length + '</span>');
			}
		}

		var stats_html = stats.join(', ');

		if (stats_html) {
			$diff_el.html(' (' + stats_html + ')');
		} else {
			$diff_el.html('');
		}

	}

	// __________________________ Utility ___________________________

	var addNotice = function( msg, target_id, settings ) {

		var conf = {
			classes: 'error',
			before_msg: '<p>',
			after_msg: '</p>'
		};

		if( settings && typeof(settings) == 'object' ) {
			$.extend( conf, settings );
		}

		var selector = target_id || 'message',
			$container = $('#' + selector );

		$container.attr('class', conf.classes ).html( conf.before_msg + msg + conf.after_msg ).fadeIn();

	}

	var removeNotice = function( target_id ) {
		var selector = target_id || 'message';

		$('#' + selector).fadeOut('fast', function(e){$(this).attr('class','').html('')});

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
			return buse_group_editor_settings.dirtyLeaverNotice;
		}

	};

	/**
	 * Logic to determine if a group has pending edits
	 */
	var hasEdits = function () {

		var hasEdits = false, currentName, currentMemberList, permEdits, i;

		// Check name field
		currentName = $('#edit-group-name').val();
		if (origName != currentName) {
			hasEdits = true;
		}

		// Check member list
		currentMemberList = getMembers();
		if (origMemberList.length != currentMemberList.length) {
			hasEdits = true;
		} else {
			for (i = 0; i < origMemberList.length; i = i + 1 ) {
				if ($.inArray(origMemberList[i], currentMemberList) == -1) {
					hasEdits = true;
				}
			}
		}

		// Check permissions editors for all post types
		$('.perm-editor').each(function () {
			permEdits = $(this).data('perm-edits');
			if (typeof permEdits !== 'undefined' && ( permEdits['allowed'].length || permEdits['denied'].length ) ) {
				hasEdits = true;
			}
		});

		return hasEdits;

	};

	/**
	 * Saving/updating does not trigger alerts
	 */
	$('#group-edit-form').submit(function (e){
		window.onbeforeunload = null;

		// Name
		var name = $.trim($('#edit-group-name').val());

		if( name.length < 1 ) {
			addNotice( buse_group_editor_settings.nameRequiredNotice );
			return false;
		}

		// Commit pending edits for each permissions editor to input value
		$('.perm-editor').each(function (){
			var edits = $(this).data('perm-edits') || {'allowed': [], 'denied': []};
			$(this).siblings('.buse-edits').val( JSON.stringify(edits) );
		});

	});

	/**
	 * Generates an alert whenever a user attemps to delete a group
	 */
	$('a.submitdelete').click(function(e){

		e.preventDefault();

		var msg = buse_group_editor_settings.deleteGroupNotice + "\n\n" + buse_group_editor_settings.confirmActionNotice;

		if( confirm(msg) ) {

			window.onbeforeunload = null;
			window.location = $(this).attr('href');

		}

	});

	// ___________________ ON PAGE LOAD _____________________

	// Initial loading

	// Image preloading for dynamically loaded images
	if( document.images ) {

		var permSprite = new Image();
		var permSpinner = new Image();

		permSprite.src = buse_group_editor_settings.pluginUrl + "/images/group_perms_sprite.png";
		permSpinner.src = buse_group_editor_settings.pluginUrl + "/images/loading.gif";

	}

	var $initialPanel = $('#perm-panel-container').find('.perm-panel.active').first();
	if( $initialPanel.length ) {
		loadPermissionsPanel( $initialPanel );
	}

	// Recreate default WP beahvior of moving notice containers under h2 for blank
	$('div#message').insertAfter( $('div.wrap h2:first') );


});