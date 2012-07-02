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
				<?php if( $pt->hierarchical ): ?>
				<input type="hidden" id="buse-edits-<?php echo $pt->name; ?>" class="buse-edits" name="group[perms][<?php echo $pt->name; ?>]" value="" />
				<div id="perm-editor-<?php echo $pt->name; ?>" class="perm-editor hierarchical" data-post-type="<?php echo $pt->name; ?>" >
				</div>
				<?php else: ?>
				<div id="perm-editor-<?php echo $pt->name; ?>" class="perm-editor flat" data-post-type="<?php echo $pt->name; ?>">
				</div>
				<?php endif; ?>
			</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
	</div>
</div>