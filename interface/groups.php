<div class="wrap">
	<?php screen_icon(); ?>
	<h2>Edit Group</h2>
	<p><a href="<?php echo BU_Groups_Admin::manage_groups_url( 'add' ); ?>" class="button-secondary">Add a Editor Group</a></p>
	<table id="buse-group-table">
		<thead>
			<tr>
				<th></th>
				<th>Remove</th>
			</tr>
		</thead>
		<tbody>
		<?php if($group_list->have_groups()): ?>
			<?php while($group_list->have_groups()): $group = $group_list->the_group(); ?>
			<?php $edit_url = BU_Groups_Admin::manage_groups_url( 'edit', array( 'id' => $group->id ) ); ?>
			<tr>
				<td><a href="<?php echo $edit_url ?>"><?php echo $group->name; ?></a></td>
				<td><a href="<?php echo $edit_url ?>">Edit</a> 
					<a class="submitdelete" href="<?php echo BU_Groups_Admin::manage_groups_url( 'delete', array( 'id' => $group->id ) ); ?>">Delete</a>
				</td>
			</tr>
			<?php endwhile; ?>
		<?php endif; ?>
		</tbody>
	</table>
	<p><a href="<?php echo BU_Groups_Admin::manage_groups_url( 'add' ); ?>" class="button-secondary">Add a Editor Group</a></p>
</div>