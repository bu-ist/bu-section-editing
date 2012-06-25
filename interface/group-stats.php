<?php
/* Group Stats widget */
?>

<div id="group-stats-widget" class="buse-widget">
	<div class="buse-widget-header"><h4>Global Stats and Actions</h4></div>
	<div class="buse-widget-body">
		<?php $perm_str = BU_Groups_Admin::group_permissions_string( $group ); ?>
		<ul>
			<li><span class="title">Name:</span> <span id="group-stats-name"><?php echo $group->name; ?></span></li>
			<li><span class="title">Members:</span> <span class="member-count"><?php echo count( $group->get_active_users() ); ?></span></li>
			<li><span class="title">Permissions:</span> <span id="group-stats-permissions"><?php echo $perm_str; ?></span></li>
		</ul>
		<div class="actions">
			<?php if( $group_id == -1): ?>
			<div id="update-action">
				<input type="submit" class="button-primary" name="submit" value="Add Group" />
			</div>
			<?php else: ?>
			<?php $delete_url = BU_Groups_Admin::manage_groups_url( 'delete', array( 'id' => $group_id ) ); ?>
			<div id="delete-action">
				<a href="<?php echo $delete_url; ?>" class="submitdelete deletion" title="Delete group">Delete</a>
			</div>
			<div id="update-action">
				<input type="submit" class="button-primary" name="submit" value="Update Group" />
			</div>
			<?php endif; ?>
			<div class="clearfix">&nbsp;</div>
		</div><!-- /.actions -->
	</div><!-- /.buse-widget-body -->
</div><!-- /#group-stats-widget -->