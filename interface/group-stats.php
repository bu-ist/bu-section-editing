<div id="group-stats-widget" class="buse-widget">
	<div class="buse-widget-header"><h4><?php _e( 'Modify Group', BUSE_TEXTDOMAIN ); ?></h4></div>
	<div class="buse-widget-body">
		<?php $perm_str = BU_Groups_Admin::group_permissions_string( $group, array( 'sep' => "\n" ) ); ?>
		<ul>
			<li><span class="title"><?php _e( 'Name', BUSE_TEXTDOMAIN ); ?>:</span> <span id="group-stats-name"><?php echo $group->name; ?></span></li>
			<li><span class="title"><?php _e( 'Members', BUSE_TEXTDOMAIN ); ?>:</span> <span class="member-count"><?php echo count( $group->users ); ?></span></li>
			<li class="clearfix"><span id="group-stats-permissions"><?php echo $perm_str; ?></span> <span class="title"><?php _e( 'Permission to Edit', BUSE_TEXTDOMAIN ); ?>:</span> </li>
		</ul>
		<div class="actions clearfix">
			<?php if( $group_id == -1): ?>
			<div id="update-action">
				<input type="submit" class="button-primary" name="submit" value="<?php esc_attr_e( 'Add Group', BUSE_TEXTDOMAIN ); ?>" />
			</div>
			<?php else: ?>
			<?php $delete_url = BU_Groups_Admin::manage_groups_url( 'delete', array( 'id' => $group_id ) ); ?>
			<div id="delete-action">
				<a href="<?php echo $delete_url; ?>" class="submitdelete deletion" title="<?php esc_attr_e( 'Delete group', BUSE_TEXTDOMAIN ); ?>"><?php _e( 'Delete', BUSE_TEXTDOMAIN ); ?></a>
			</div>
			<div id="update-action">
				<input type="submit" class="button-primary" name="submit" value="<?php esc_attr_e( 'Update Group', BUSE_TEXTDOMAIN ); ?>" />
			</div>
			<?php endif; ?>
		</div><!-- /.actions -->
	</div><!-- /.buse-widget-body -->
</div><!-- /#group-stats-widget -->