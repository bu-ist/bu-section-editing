<div class="wrap">
	<?php screen_icon(); ?>
	<h2>Edit Group</h2>
	<p><a href="<?php echo BU_Groups_Admin::group_edit_url('edit'); ?>" class="button-secondary">Add a Editor Group</a></p>
	<?php if($group_list->have_groups()): ?>
	<ul>
		<?php while($group_list->have_groups()): $group = $group_list->the_group(); ?>
		<li><a href="<?php echo BU_Groups_Admin::group_edit_url( 'edit', $group_list->current_group); ?>"><?php echo $group->get_name(); ?></a></li>
		<?php endwhile; ?>
	</ul>
	<?php endif; ?>
</div>