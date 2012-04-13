<div class="wrap">
	<?php screen_icon(); ?>
	<h2>Edit Group</h2>
	<p><a href="<?php echo BU_Groups_Admin::group_edit_url(); ?>" class="button-secondary">Add a Editor Group</a></p>
	<table class="widefat">
		<thead>
			<tr>
				<th>Group Name</th>
				<th>Actions</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th>Group Name</th>
				<th>Actions</th>
			</tr>
		</tfoot>
		<tbody>
	<?php if($group_list->have_groups()): ?>
		<?php while($group_list->have_groups()): $group = $group_list->the_group(); ?>
		<tr>
			<td><?php echo $group->name; ?></td>
			<td><a href="<?php echo BU_Groups_Admin::group_edit_url( $group->id ); ?>">Edit</a> |
				<a class="submitdelete" href="<?php echo BU_Groups_Admin::group_delete_url( $group->id ); ?>">Delete</a>
			</td>
		</tr>
		<?php endwhile; ?>
	<?php endif; ?>
		</tbody>
	</table>
</div>