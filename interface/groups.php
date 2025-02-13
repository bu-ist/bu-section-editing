<div class="wrap">
	<div id="icon-section-groups" class="icon32"></div>
	<h2><?php esc_html_e( 'Section Groups', 'bu-section-editing' ); ?></h2>
	<p><a href="<?php echo esc_url( BU_Groups_Admin::manage_groups_url( 'add' ), 'bu-section-editing' ); ?>" class="button-secondary"><?php esc_html_e( 'Add an Editor Group', 'bu-section-editing' ); ?></a></p>
	<table id="section-groups" class="wp-list-table widefat">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Name', 'bu-section-editing' ); ?></th>
				<th><?php esc_html_e( 'Description', 'bu-section-editing' ); ?></th>
				<th><?php esc_html_e( 'Members', 'bu-section-editing' ); ?></th>
				<th><?php esc_html_e( 'Editable', 'bu-section-editing' ); ?></th>
				<th><?php esc_html_e( 'Remove', 'bu-section-editing' ); ?></th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th><?php esc_html_e( 'Name', 'bu-section-editing' ); ?></th>
				<th><?php esc_html_e( 'Description', 'bu-section-editing' ); ?></th>
				<th><?php esc_html_e( 'Members', 'bu-section-editing' ); ?></th>
				<th><?php esc_html_e( 'Editable', 'bu-section-editing' ); ?></th>
				<th><?php esc_html_e( 'Remove', 'bu-section-editing' ); ?></th>
			</tr>
		</tfoot>
		<tbody>
		<?php if ( $group_list->have_groups() ) : ?>
			<?php $count = 0; ?>
			<?php while ( $group_list->have_groups() ) : $group = $group_list->the_group(); ?>
			<?php
			$li_class = $count % 2 ? '' : 'class="alternate"';
			$edit_url = esc_url( BU_Groups_Admin::manage_groups_url( 'edit', array( 'id' => $group->id ) ), 'bu-section-editing' );
			$description = (strlen( $group->description ) > 60) ? substr( $group->description, 0, 60 ) . ' [...]' : $group->description;
			?>
			<tr <?php echo esc_html( $li_class, 'bu-section-editing' ); ?>>
				<td><a href="<?php echo esc_url( $edit_url, 'bu-section-editing' ) ?>"><?php echo esc_html( $group->name, 'bu-section-editing' ); ?></a></td>
				<td><?php echo wp_kses_post( $description, 'bu-section-editing' ); ?></td>
				<td><?php echo count( $group->users ); ?></td>
				<td><?php echo wp_kses_post( BU_Groups_Admin::group_permissions_string( $group ), 'bu-section-editing' ); ?></td>
				<td>
					<a class="submitdelete" href="<?php echo esc_attr( BU_Groups_Admin::manage_groups_url( 'delete', array( 'id' => esc_attr($group->id, 'bu-section-editing') ) ), 'bu-section-editing' ); ?>">
					<img src="<?php echo esc_url( plugins_url( BUSE_PLUGIN_PATH . '/images/group_remove.png' ), 'bu-section-editing' ); ?>" alt="<?php esc_attr_e( 'Delete', 'bu-section-editing' ); ?>"></a>
				</td>
			</tr>
			<?php $count++; ?>
			<?php endwhile; ?>
		<?php endif; ?>
		</tbody>
	</table>
	<p><a href="<?php echo esc_attr(BU_Groups_Admin::manage_groups_url( 'add' ), 'bu-section-editing'); ?>" class="button-secondary"><?php esc_html_e( 'Add an Editor Group', 'bu-section-editing' ); ?></a></p>
</div>
