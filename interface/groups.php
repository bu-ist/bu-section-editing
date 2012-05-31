<div class="wrap">
	<?php screen_icon(); ?>
	<h2>Section Groups <a href="<?php echo BU_Groups_Admin::manage_groups_url( 'add' );?>" class="add-new-h2">Add New</a></h2>
	<p><a href="<?php echo BU_Groups_Admin::manage_groups_url( 'add' ); ?>" class="button-secondary">Add an Editor Group</a></p>
	<!--<table id="buse-group-table">-->
	<table class="wp-list-table widefat">
		<thead>
			<tr>
				<th>Name</th>
				<th>Members</th>
				<th>Remove</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th>Name</th>
				<th>Members</th>
				<th>Remove</th>
			</tr>
		</tfoot>
		<tbody>
		<?php if($group_list->have_groups()): ?>
			<?php while($group_list->have_groups()): $group = $group_list->the_group(); ?>
			<?php $edit_url = BU_Groups_Admin::manage_groups_url( 'edit', array( 'id' => $group->id ) ); ?>
			<tr>
				<td><a href="<?php echo $edit_url ?>"><?php echo $group->name; ?></a></td>
				<td><?php echo count( $group->users ); ?></td>
				<td>
					<a class="submitdelete" href="<?php echo BU_Groups_Admin::manage_groups_url( 'delete', array( 'id' => $group->id ) ); ?>">
					<img src="<?php echo plugins_url( BUSE_PLUGIN_PATH . '/images/group_remove.png' ); ?>" alt="Delete"></a>
				</td>
			</tr>
			<?php endwhile; ?>
		<?php endif; ?>
		</tbody>
	</table>
	<p><a href="<?php echo BU_Groups_Admin::manage_groups_url( 'add' ); ?>" class="button-secondary">Add an Editor Group</a></p>
</div>