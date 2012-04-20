<div id="section-group-editor" class="wrap">
	<?php screen_icon(); ?>
	<h2>Edit Group</h2>
	<?php /* @todo Better error handling */ ?>
	<?php if( isset($_GET['errors'])): ?><div class="error"><p>Error saving group!</p></div><?php endif; ?>
	<div class="form-wrap">
		<!-- Tab Interface -->
		<h2 class="nav-tab-wrapper">
			<a id="nav-tab-name" href="#group-name-panel" class="nav-tab <?php if($tab == 'name') echo 'nav-tab-active'; ?>">Name</a>
			<a id="nav-tab-members" href="#group-members-panel" class="nav-tab <?php if($tab == 'members') echo 'nav-tab-active'; ?>">Members</a>
			<a id="nav-tab-permissions" href="#group-permissions-panel" class="nav-tab <?php if($tab == 'permissions') echo 'nav-tab-active'; ?>">Permissions</a>
		</h2>
		<form method="POST">
			<input type="hidden" name="action" value="update"/>
			<input id="group_id" type="hidden" name="id" value="<?php echo $group_id; ?>" />
			<?php wp_nonce_field( 'update_section_editing_group' ); ?>
			<div id="panel-container">
				<div id="group-name-panel" class="edit-group-panel<?php if($tab == 'name') echo ' active'; ?>">
					<?php include 'group-name.php'; ?>	
				</div>
				<div id="group-members-panel" class="edit-group-panel<?php if($tab == 'members') echo ' active'; ?>">
					<?php include 'group-members.php'; ?>	
				</div>
				<div id="group-permissions-panel" class="edit-group-panel<?php if($tab == 'permissions') echo ' active'; ?>">
					<?php include 'group-permissions.php'; ?>	
				</div>
			</div><!-- /#panel-container -->
			<div id="stats-container">
				<?php include "group-stats.php"; ?>
			</div><!-- /#stats-container -->
		</form>
	</div><!-- /.form-wrap -->
</div><!-- /.wrap -->