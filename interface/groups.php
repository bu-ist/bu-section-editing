<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap">
	<div id="icon-section-groups" class="icon32"></div>
	<h2><?php esc_html_e( 'Section Groups', 'bu-section-editing' ); ?></h2>
	<p><a href="<?php echo esc_url( BU_Groups_Admin::manage_groups_url( 'add' ) ); ?>" class="button-secondary"><?php esc_html_e( 'Add an Editor Group', 'bu-section-editing' ); ?></a></p>
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
			<?php $buse_count = 0; ?>
			<?php while ( $group_list->have_groups() ) : $buse_group = $group_list->the_group(); ?>
			<?php
			$buse_edit_url = BU_Groups_Admin::manage_groups_url( 'edit', array( 'id' => absint( $buse_group->id ) ) );
			$buse_description = (strlen( $buse_group->description ) > 60) ? substr( $buse_group->description, 0, 60 ) . ' [...]' : $buse_group->description;
			?>
			<tr class="<?php echo esc_attr( $buse_count % 2 ? '' : 'alternate' ); ?>">
				<td><a href="<?php echo esc_url( $buse_edit_url ); ?>"><?php echo esc_html( $buse_group->name, 'bu-section-editing' ); ?></a></td>
				<td><?php echo wp_kses_post( $buse_description, 'bu-section-editing' ); ?></td>
				<td><?php echo count( $buse_group->users ); ?></td>
				<td><?php echo wp_kses_post( BU_Groups_Admin::group_permissions_string( $buse_group ), 'bu-section-editing' ); ?></td>
				<td>
					<a class="submitdelete" href="<?php echo esc_url( BU_Groups_Admin::manage_groups_url( 'delete', array( 'id' => absint( $buse_group->id ) ) ) ); ?>">
					<span class="dashicons dashicons-no-alt" aria-hidden="true"></span><span class="screen-reader-text"><?php esc_html_e( 'Delete', 'bu-section-editing' ); ?></span></a>
				</td>
			</tr>
			<?php ++$buse_count; ?>
			<?php endwhile; ?>
		<?php endif; ?>
		</tbody>
	</table>
	<p><a href="<?php echo esc_url( BU_Groups_Admin::manage_groups_url( 'add' ) ); ?>" class="button-secondary"><?php esc_html_e( 'Add an Editor Group', 'bu-section-editing' ); ?></a></p>
</div>
