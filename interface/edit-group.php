<div class="wrap">
	<?php screen_icon(); ?>
	<h2>Edit Group</h2>
	<div class="form-wrap">
		<!-- Tab Interface -->
		<h2 class="nav-tab-wrapper">
			<a id="nav-tab-name" href="#edit_group_name" class="nav-tab <?php if($tab == 'name') echo 'nav-tab-active'; ?>">Name</a>
			<a id="nav-tab-members" href="#edit_group_members" class="nav-tab <?php if($tab == 'members') echo 'nav-tab-active'; ?>">Members</a>
			<a id="nav-tab-permissions" href="#edit_group_permissions" class="nav-tab <?php if($tab == 'permissions') echo 'nav-tab-active'; ?>">Permissions</a>
		</h2>
		<form method="POST">
			<input type="hidden" name="action" value="update"/>
			<?php echo $nonce; ?>
			<div id="panel_container">
			<div id="edit_group_name" class="edit_group_panel <?php if($tab == 'name') echo 'active'; ?>">
				<?php include 'group-name.php'; ?>	
			</div>
			<div id="edit_group_members" class="edit_group_panel <?php if($tab == 'members') echo 'active'; ?>">
				<?php include 'group-members.php'; ?>	
			</div>
			<div id="edit_group_permissions" class="edit_group_panel <?php if($tab == 'permissions') echo 'active'; ?>">
				<?php include 'group-permissions.php'; ?>	
			</div>
			</div><!-- /#panel_container -->
			<?php include "group-stats.php"; ?>
		</form>
	</div><!-- /.form-wrap -->
</div><!-- /.wrap -->