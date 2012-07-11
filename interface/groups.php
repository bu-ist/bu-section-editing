<div class="wrap">
	<?php screen_icon(); ?>
	<h2>Section Groups</h2>
	<p><a href="<?php echo BU_Groups_Admin::manage_groups_url( 'add' ); ?>" class="button-secondary">Add an Editor Group</a></p>
	<table id="section-groups" class="wp-list-table widefat">
		<thead>
			<tr>
				<th>Name</th>
				<th>Description</th>
				<th>Members</th>
				<th>Editable</th>
				<th>Remove</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th>Name</th>
				<th>Description</th>
				<th>Members</th>
				<th>Editable</th>
				<th>Remove</th>
			</tr>
		</tfoot>
		<tbody>
		<?php if($group_list->have_groups()): ?>
			<?php $count = 0; ?>
			<?php while($group_list->have_groups()): $group = $group_list->the_group(); ?>
			<?php
			$li_class = $count % 2 ? '' : 'class="alternate"';
			$edit_url = BU_Groups_Admin::manage_groups_url( 'edit', array( 'id' => $group->id ) ); 
			$description = (strlen($group->description) > 60) ? substr($group->description, 0, 60) . ' [...]' : $group->description;
			?>
			<tr <?php echo $li_class; ?>>
				<td><a href="<?php echo $edit_url ?>"><?php echo $group->name; ?></a></td>
				<td><?php echo $description; ?></td>
				<td><?php echo count( $group->get_active_users() ); ?></td>
				<td><?php echo BU_Groups_Admin::group_permissions_string( $group->id ); ?></td>
				<td>
					<a class="submitdelete" href="<?php echo BU_Groups_Admin::manage_groups_url( 'delete', array( 'id' => $group->id ) ); ?>">
					<img src="<?php echo plugins_url( BUSE_PLUGIN_PATH . '/images/group_remove.png' ); ?>" alt="Delete"></a>
				</td>
			</tr>
			<?php $count++; ?>
			<?php endwhile; ?>
		<?php endif; ?>
		</tbody>
	</table>
	<p><a href="<?php echo BU_Groups_Admin::manage_groups_url( 'add' ); ?>" class="button-secondary">Add an Editor Group</a></p>
</div>