<div id="section-group-editor" class="wrap">
	<?php screen_icon(); ?>
	<h2>Edit Section Group <a href="<?php echo esc_attr(BU_Groups_Admin::manage_groups_url( 'add' ));?>" class="button add-new-h2">Add New</a></h2>
	<div class="form-wrap">
		<!-- Tab Interface -->
		<h3 class="nav-tab-wrapper">
			<a id="nav-tab-properties" href="#group-properties-panel" class="nav-link nav-tab <?php if($tab == 'properties') echo 'nav-tab-active'; ?>" data-target="properties" >Properties</a>
			<a id="nav-tab-members" href="#group-members-panel" class="nav-link nav-tab <?php if($tab == 'members') echo 'nav-tab-active'; ?>" data-target="members" >Members</a>
			<a id="nav-tab-permissions" href="#group-permissions-panel" class="nav-link nav-tab <?php if($tab == 'permissions') echo 'nav-tab-active'; ?>" data-target="permissions" >Permissions</a>
		</h3>
		<form name="group-edit-form" id="group-edit-form" method="post">
			<input type="hidden" name="action" value="update"/>
			<input type="hidden" id="group_id" name="id" value="<?php echo $group_id; ?>" />
			<input type="hidden" id="tab" name="tab" value="<?php echo $tab; ?>" />
			<input type="hidden" id="perm_panel" name="perm_panel" value="<?php echo $perm_panel; ?>" />
			<?php wp_nonce_field( 'update_section_editing_group' ); ?>
			<div id="stats-container">
				<?php include "group-stats.php"; ?>
			</div>
			<div id="panel-container">
				<div id="group-properties-panel" class="group-panel<?php if($tab == 'properties') echo ' active'; ?>">
					<a name="group-properties-panel"></a>
					<?php include 'group-properties.php'; ?>	
				</div>
				<div id="group-members-panel" class="group-panel<?php if($tab == 'members') echo ' active'; ?>">
					<a name="group-members-panel"></a>
					<?php include 'group-members.php'; ?>	
				</div>
				<div id="group-permissions-panel" class="group-panel<?php if($tab == 'permissions') echo ' active'; ?>">
					<a name="group-permissions-panel"></a>
					<?php include 'group-permissions.php'; ?>	
				</div>
			</div><!-- /#panel-container -->
		</form>
	</div><!-- /.form-wrap -->
</div><!-- /.wrap -->