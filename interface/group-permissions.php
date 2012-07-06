<div id="group-permission-editor">
	<?php $content_types = BU_Permissions_Editor::get_supported_post_types(); ?>
	<?php if( ! empty( $content_types ) ) : ?>
		<div id="perm-tab-container">
			<?php foreach( $content_types as $index => $pt ): ?>
				<?php $active = $index == 0 ? ' nav-tab-active' : ''; ?>
				<a href="#perm-panel-<?php echo $pt->name; ?>" class="nav-link nav-tab inline<?php echo $active; ?>"><?php echo $pt->label; ?></a>
			<?php endforeach; ?>
		</div><!-- perm-tab-container -->
		<div id="perm-panel-container">
			<?php foreach( $content_types as $index => $pt ): ?>
			<?php 
			$active = $index == 0 ? ' active' : ''; 
			$hierarchical = $pt->hierarchical ? true : false;
			$hiearchical_class = $hierarchical ? 'hierarchical' : 'flat';
			?>
			<div id="perm-panel-<?php echo $pt->name; ?>" class="perm-panel <?php echo $active; ?>">
				<div id="perm-toolbar-<?php echo $pt->name; ?>-top" class="perm-toolbar top">
					<?php if( $hierarchical ): ?>
					<p class="alignright">
						<a href="#" class="perm-tree-expand" data-target="perm-editor-<?php echo $pt->name; ?>">Expand All</a> | 
						<a href="#" class="perm-tree-collapse" data-target="perm-editor-<?php echo $pt->name; ?>">Collapse All</a>
					</p>
					<?php else: ?>
					<p class="alignleft">
						<input id="perm-search-<?php echo $pt->name; ?>" type="text" name="perm-action[][search]" class="perm-search <?php echo $hiearchical_class; ?>" > 
						<button class="perm-search flat button-secondary">Search <?php echo $pt->label; ?></button>
					</p>
					<p class="alignright">
						<label for="perm-sort-<?php echo $pt->name; ?>" class="inline">Sort by: </label>
						<select id="perm-sort-<?php echo $pt->name; ?>" name="perm-action[<?php echo $pt->name; ?>][sort]" class="perm-sort">
							<option value="modified">Last Modified</option>
							<option value="date">Publish Date</option>
							<option value="title:ASC">Title (Ascending)</option>
							<option value="title:DESC">Title (Descending)</option>
						</select>
					</p>
					<?php endif; ?>
				</div>
				<div class="perm-scroll-area">
					<input type="hidden" id="buse-edits-<?php echo $pt->name; ?>" class="buse-edits" name="group[perms][<?php echo $pt->name; ?>]" value="" />
					<div id="perm-editor-<?php echo $pt->name; ?>" class="perm-editor <?php echo $hiearchical_class; ?>" data-post-type="<?php echo $pt->name; ?>">
					</div><!-- perm-editor-<?php echo $pt->name; ?> -->
				</div>
			</div><!-- perm-panel-<?php echo $pt->name; ?> -->
			<?php endforeach; ?>
		</div><!-- perm-panel-container -->
	<?php endif; ?>
</div><!-- group-permissions-editor -->