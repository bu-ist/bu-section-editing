<div id="section-group-editor" class="wrap">
	<?php screen_icon(); ?>
	<h2>Edit Section Group <a href="<?php echo BU_Groups_Admin::manage_groups_url( 'add' );?>" class="add-new-h2">Add New</a></h2>
	<?php if( isset($_GET['errors'])): ?><div class="error"><p>Error saving group!</p></div><?php endif; ?>
	<div class="form-wrap">
		<!-- Tab Interface -->
		<h3 class="nav-tab-wrapper">
			<a id="nav-tab-name" href="#group-name-panel" class="nav-link nav-tab <?php if($tab == 'name') echo 'nav-tab-active'; ?>">Name</a>
			<a id="nav-tab-members" href="#group-members-panel" class="nav-link nav-tab <?php if($tab == 'members') echo 'nav-tab-active'; ?>">Members</a>
			<a id="nav-tab-permissions" href="#group-permissions-panel" class="nav-link nav-tab <?php if($tab == 'permissions') echo 'nav-tab-active'; ?>">Permissions</a>
		</h3>
		<form id="group-edit-form" method="POST">
			<input type="hidden" name="action" value="update"/>
			<input id="group_id" type="hidden" name="id" value="<?php echo $group_id; ?>" />
			<?php wp_nonce_field( 'update_section_editing_group' ); ?>
			<div id="panel-container">
				<div id="group-name-panel" class="edit-group-panel<?php if($tab == 'name') echo ' active'; ?>">
					<a name="group-name-panel"></a>
					<?php include 'group-name.php'; ?>	
				</div>
				<div id="group-members-panel" class="edit-group-panel<?php if($tab == 'members') echo ' active'; ?>">
					<a name="group-members-panel"></a>
					<?php include 'group-members.php'; ?>	
				</div>
				<div id="group-permissions-panel" class="edit-group-panel<?php if($tab == 'permissions') echo ' active'; ?>">
					<a name="group-permissions-panel"></a>
					<?php include 'group-permissions.php'; ?>	
				</div>
				<div id="stats-container">
					<?php include "group-stats.php"; ?>
				</div><!-- /#stats-container -->
			</div><!-- /#panel-container -->
		</form>
	</div><!-- /.form-wrap -->
</div><!-- /.wrap -->