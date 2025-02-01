<div id="group-stats-widget" class="buse-widget">
	<div class="buse-widget-header"><h4><?php esc_html_e( 'Modify Group', 'bu-section-editing' ); ?></h4></div>
	<div class="buse-widget-body">
		<?php $perm_str = BU_Groups_Admin::group_permissions_string( $group, array( 'sep' => "\n" ) ); ?>
		<ul>
			<li><span class="title"><?php esc_html_e( 'Name', 'bu-section-editing' ); ?>:</span> <span id="group-stats-name"><?php echo esc_html($group->name, 'bu-section-editing'); ?></span></li>
			<li><span class="title"><?php esc_html_e( 'Members', 'bu-section-editing' ); ?>:</span> <span class="member-count"><?php echo count( $group->users ); ?></span></li>
			<li class="clearfix"><span id="group-stats-permissions"><?php echo esc_html($perm_str, 'bu-section-editing'); ?></span> <span class="title"><?php esc_html_e( 'Permission to Edit', 'bu-section-editing' ); ?>:</span> </li>
		</ul>
		<div class="actions clearfix">
			<?php if ( $group_id == -1 ) : ?>
			<div id="update-action">
				<input type="submit" class="button-primary" name="submit" value="<?php esc_attr_e( 'Add Group', 'bu-section-editing' ); ?>" />
			</div>
			<?php else : ?>
			<?php $delete_url = BU_Groups_Admin::manage_groups_url( 'delete', array( 'id' => $group_id ) ); ?>
			<div id="delete-action">
				<a href="<?php echo esc_html($delete_url, 'bu-section-editing'); ?>" class="submitdelete deletion" title="<?php esc_attr_e( 'Delete group', 'bu-section-editing' ); ?>"><?php esc_html_e( 'Delete', 'bu-section-editing' ); ?></a>
			</div>
			<div id="update-action">
				<input type="submit" class="button-primary" name="submit" value="<?php esc_attr_e( 'Update Group', 'bu-section-editing' ); ?>" />
			</div>
			<?php endif; ?>
		</div><!-- /.actions -->
	</div><!-- /.buse-widget-body -->
</div><!-- /#group-stats-widget -->
