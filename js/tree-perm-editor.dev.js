/**
 * ========================================================================
 * BU Section Editing plugin - Hierarchiacl Permissions Editor
 * ========================================================================
 */

/*jslint browser: true, todo: true */
/*global bu: true, jQuery: false, console: false, window: false, document: false */
(function ($) {

	// Check prerequisites
	if((typeof bu === 'undefined') ||
		(typeof bu.plugins.navigation === 'undefined') ||
		(typeof bu.plugins.navigation.tree === 'undefined'))
			return;

	var Nav = bu.plugins.navigation;

	// ----------------------------
	// Hierarchical perm editor
	// ----------------------------
	Nav.trees['buse_perm_editor'] = function (config, my) {
		my = my || {};

		// Functional inheritance
		var that = Nav.trees.base( config, my );

		// Aliases
		var d = that.data;
		var c = $.extend(that.config, config || {});	// instance configuration

		var $tree = that.$el;

		// Remove base plugins and config that we don't need
		// @todo consider removing from bu-navigation base
		var crrm = $.inArray('crrm', d.treeConfig['plugins']);
		if (crrm > -1) {
			d.treeConfig['plugins'].splice(crrm,1);
			if (typeof d.treeConfig['crrm'] !== 'undefined' ) {
				delete d.treeConfig['crrm'];
			}
		}
		var dnd = $.inArray('dnd', d.treeConfig['plugins']);
		if (dnd > -1) {
			d.treeConfig['plugins'].splice(dnd,1);
			if (typeof d.treeConfig['dnd'] !== 'undefined' ) {
				delete d.treeConfig['dnd'];
			}
		}

		// Custom tree types for perm editor (icon asset config is temporary)
		d.treeConfig['types'] = {
			"types" : {
				'default' : {},
				'denied' : {},
				'denied-desc-allowed' : {},
				'denied-desc-unknown' : {},
				'allowed' : {},
				'allowed-desc-denied' : {},
				'allowed-desc-unknown' : {}
			}
		};

		// UI plugin
		d.treeConfig["ui"] = {
			"select_limit": 1
		}

		// Append global jstree options with configuration for this post type
		d.treeConfig['json_data']['ajax'] = {
			url : c.rpcUrl,
			type: 'GET',
			cache: false,
			data : function(n) {
				return {
					group_id : c.groupID,
					node_prefix : c.nodePrefix,
					post_type : c.postType,
					query: {
						child_of : n.attr ? my.stripNodePrefix(n.attr('id')) : 0
					}
				}
			},
			success: function( response ) {
				return response.posts;
			},
			error: function( response ) {
				// @todo handle error
			}
		};

		// Interferes with icon state correction with lazy load
		d.treeConfig['json_data']['progressive_render'] = false;

		var _prepare_perm_actions = function (node) {
			node = !node || node == -1 ? $tree.find("> ul > li") : $.jstree._reference($tree)._get_node(node);

			var $li, $button, action_class, action_label;

			node.each(function () {
				$li = $(this);
				$li.find("li").andSelf().each(function () {
					action_class = $(this).data('editable') ? 'denied' : 'allowed';

					if (action_class == 'allowed') {
						action_label = c.allowLabel;
					} else {
						action_label = c.denyLabel;
					}

					$button = $('<button class="edit-perms ' + action_class + '"></button>')
						.text(action_label);
					$(this).children("a").not(":has(.edit-perms)").append($button);
				});
			});
		};

		// Add perm actions button to each node as needed
		$tree.bind("open_node.jstree create_node.jstree clean_node.jstree refresh.jstree", function (e, data) {
			_prepare_perm_actions(data.rslt.obj);
		});

		$tree.bind("loaded.jstree", function (e) {
			_prepare_perm_actions();
		});

		$tree.addClass('buse-perm-editor');

		return that;
	};

}(jQuery));