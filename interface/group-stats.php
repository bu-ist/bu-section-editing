<?php
/* Group Stats widget */
?>

<h4>Global Stats and Actions</h4>
<div id="group-stats-widget">
	<ul>
		<li><span class="title">Group Name:</span> <span id="group-stats-name"><?php echo ''; ?></span></li>
		<li><span class="title">Group Members:</span> <span id="group-stats-count"><?php echo count( $group->users ); ?></span></li>
		<li><span class="title">Permissions:</span> <span id="group-stats-permissions"><a class="nav-link" href="#group-member-permissions">Add Permissions</a></span></li>
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
	</div>
</div>