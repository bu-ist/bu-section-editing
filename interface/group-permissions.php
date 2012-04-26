<div id="group_permission_editor">
	<h4>Assign Permissions to:</h4>
	<fieldset>
	<?php $content_types = BU_Permissions_Editor::get_supported_post_types(); ?>
	<?php if( ! empty( $content_types ) ) : ?>
		<ul id="content_types">
			<?php foreach( $content_types as $pt ): ?>
			<li id="perm-tab-<?php echo $pt->name; ?>" class="perm-tab"><a href="#perm-panel-<?php echo $pt->name; ?>"><?php echo $pt->label; ?></a></li>
			<?php endforeach; ?>
		</ul>
		<?php foreach( $content_types as $pt ): ?>
		<div id="perm-panel-<?php echo $pt->name; ?>" class="perm-panel">
			<?php if( $pt->hierarchical ): ?>
			<div id="perm-editor-<?php echo $pt->name; ?>" class="perm-editor-hierarchical">
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
	<?php endif; ?>
	</fieldset>
</div>