<div id="group_permission_editor">
	<h4>Assign Permissions to:</h4>
	<fieldset>
	<?php $content_types = BU_Permissions_Editor::get_supported_post_types(); ?>
	<?php if( ! empty( $content_types ) ) : ?>
		<div id="perm-tab-container">
			<?php foreach( $content_types as $index => $pt ): ?>
				<?php $active = $index == 0 ? ' nav-tab-active' : ''; ?>
				<a href="#perm-panel-<?php echo $pt->name; ?>" class="nav-tab inline<?php echo $active; ?>"><?php echo $pt->label; ?></a>
			<?php endforeach; ?>
		</div>
		<div id="perm-panel-container">
			<?php foreach( $content_types as $index => $pt ): ?>
			<?php $active = $index == 0 ? ' active' : ''; ?>
			<div id="perm-panel-<?php echo $pt->name; ?>" class="perm-panel <?php echo $active; ?>">
				<input type="hidden" id="buse-edits-<?php echo $pt->name; ?>" name="group[perms][<?php echo $pt->name; ?>]" value="" />
				<?php if( $pt->hierarchical ): ?>
				<div id="perm-editor-<?php echo $pt->name; ?>" class="perm-editor-hierarchical" data-post-type="<?php echo $pt->name; ?>" >
					<?php $permission_editor = new BU_Hierarchical_Permissions_Editor( $group, $pt->name ); ?>
					<?php $permission_editor->render(); ?>
				</div>
				<?php else: ?>
				<div id="perm-editor-<?php echo $pt->name; ?>" class="perm-editor-flat">
					<?php $permission_editor = new BU_Flat_Permissions_Editor( $group, $pt->name ); ?>
					<?php $permission_editor->render(); ?>
				</div>
				<?php endif; ?>
			</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
	</fieldset>
</div>