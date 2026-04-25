<div id="section-group-editor" class="wrap">
	<?php
	$group_members = BU_Section_Editing_Plugin::get_group_member_users( $group );
	$available_group_users = BU_Section_Editing_Plugin::get_group_available_users( $group, $group_members );
	$member_count = count( $group_members );
	?>
	<div id="icon-section-group" class="icon32"></div>
	<h2><?php echo esc_html($page_title, 'bu-section-editing'); ?> <a href="<?php echo esc_attr( BU_Groups_Admin::manage_groups_url( 'add' ), 'bu-section-editing' );?>" class="button add-new-h2"><?php esc_html_e( 'Add New', 'bu-section-editing' ); ?></a></h2>
	<div class="form-wrap">
		<h3 class="nav-tab-wrapper">
			<a id="nav-tab-properties" href="#group-properties-panel" class="nav-link nav-tab <?php if ( $tab == 'properties' ) { echo 'nav-tab-active'; } ?>" data-target="properties" ><?php esc_html_e( 'Properties', 'bu-section-editing' ); ?></a>
			<a id="nav-tab-members" href="#group-members-panel" class="nav-link nav-tab <?php if ( $tab == 'members' ) { echo 'nav-tab-active'; } ?>" data-target="members" ><?php esc_html_e( 'Members', 'bu-section-editing' ); ?></a>
			<a id="nav-tab-permissions" href="#group-permissions-panel" class="nav-link nav-tab <?php if ( $tab == 'permissions' ) { echo 'nav-tab-active'; } ?>" data-target="permissions" ><?php esc_html_e( 'Permissions', 'bu-section-editing' ); ?></a>
		</h3>
		<form name="group-edit-form" id="group-edit-form" method="post">
			<?php if ( -1 == $group_id ) :  ?>
			<input type="hidden" name="action" value="add"/>
			<?php else : ?>
			<input type="hidden" name="action" value="update"/>
			<input type="hidden" id="group_id" name="id" value="<?php echo esc_attr($group_id, 'bu-section-editing'); ?>" />
			<?php endif; ?>
			<input type="hidden" id="tab" name="tab" value="<?php echo esc_attr($tab, 'bu-section-editing'); ?>" />
			<input type="hidden" id="perm_panel" name="perm_panel" value="<?php echo esc_attr($perm_panel, 'bu-section-editing'); ?>" />
			<?php wp_nonce_field( 'save_section_editing_group' ); ?>

			<div id="stats-container">
				<?php include 'group-stats.php'; ?>
			</div>
			<div id="panel-container">
				<div id="group-properties-panel" class="group-panel<?php if ( $tab == 'properties' ) { echo ' active'; } ?>">
					<a name="group-properties-panel"></a>
					<?php include 'group-properties.php'; ?>
				</div>
				<div id="group-members-panel" class="group-panel<?php if ( $tab == 'members' ) { echo ' active'; } ?>">
					<a name="group-members-panel"></a>
					<?php include 'group-members.php'; ?>
				</div>
				<div id="group-permissions-panel" class="group-panel<?php if ( $tab == 'permissions' ) { echo ' active'; } ?>">
					<a name="group-permissions-panel"></a>
					<?php include 'group-permissions.php'; ?>
				</div>
			</div><!-- /#panel-container -->
		</form>
	</div><!-- /.form-wrap -->
</div><!-- /.wrap -->
