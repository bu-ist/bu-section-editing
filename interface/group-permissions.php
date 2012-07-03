<div id="group-permission-editor" class="buse-widget">
	<div class="buse-widget-header"><h4>Content Types</h4></div>
	<div class="buse-widget-body">
	<!--<p><em>Allow/disallow members of this groups from editing/publishing content</em></p>-->
	<?php $content_types = BU_Permissions_Editor::get_supported_post_types(); ?>
	<?php if( ! empty( $content_types ) ) : ?>
		<div id="perm-tab-container">
			<?php foreach( $content_types as $index => $pt ): ?>
				<?php $active = $index == 0 ? ' nav-tab-active' : ''; ?>
				<a href="#perm-panel-<?php echo $pt->name; ?>" class="nav-link nav-tab inline<?php echo $active; ?>"><?php echo $pt->label; ?></a>
			<?php endforeach; ?>
		</div>
		<div id="perm-panel-container">
			<?php foreach( $content_types as $index => $pt ): ?>
			<?php $active = $index == 0 ? ' active' : ''; ?>
			<div id="perm-panel-<?php echo $pt->name; ?>" class="perm-panel <?php echo $active; ?>">
				<div id="perm-toolbar-<?php echo $pt->name; ?>" class="perm-toolbar top">
					<p class="alignleft"><select name="action"><option val="">Bulk Actions</option></select><input type="submit" class="button-secondary action"></p>
					<p class="alignright"><input type="text" name="search" > <button class="button-secondary">Search <?php echo $pt->label; ?></button></p>
				</div>
				<div class="perm-scroll-area">
					<?php if( $pt->hierarchical ): ?>
					<input type="hidden" id="buse-edits-<?php echo $pt->name; ?>" class="buse-edits" name="group[perms][<?php echo $pt->name; ?>]" value="" />
					<div id="perm-editor-<?php echo $pt->name; ?>" class="perm-editor hierarchical" data-post-type="<?php echo $pt->name; ?>" >
					</div>
					<?php else: ?>
					<div id="perm-editor-<?php echo $pt->name; ?>" class="perm-editor flat" data-post-type="<?php echo $pt->name; ?>">
					</div>
					<?php endif; ?>
				</div>
				<div id="perm-toolbar-<?php echo $pt->name; ?>" class="perm-toolbar bottom">
					<p class="alignleft"><a href="">Load More <?php echo $pt->label; ?>...</a></p>
					<p class="alignright"><a href="">Select All</a> | <a href="">Deselect All</a></p>
				</div>
			</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
	</div>
</div>