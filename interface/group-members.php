<div id="add-group-members" class="buse-widget">
	<div class="buse-widget-header">
		<h4 id="add-group-members-header">Add User to this Group</h4>
	</div>
	<div class="buse-widget-body">
		<div id="members-message" ></div>
		<div class="form-field">
			<label for="user_login">Enter the email address of an existing user on this network to add them to this Editor Group.</label>
			<input id="user_login" type="text" class="regular-text" name="user_login" value="" />
			<button id="add_member" class="button-secondary">Add User</button>
		</div>
		<!--<div class="form-row">
			<button id="find_user" class="button-secondary">Find User</button>
		</div>-->
	</div>
</div>

<div id="group-members" class="buse-widget">
	<div class="buse-widget-header">
		<div id="member-list-count">
			<span class="member-count"><?php echo count( $group->get_active_users() ); ?></span> members
		</div>
		<h4 id="edit-group-members-header">Group Member List</h4>
	</div>
	<div class="buse-widget-body">
		<ul id="group-member-list">
			<?php $users = BU_Section_Editing_Plugin::get_allowed_users(); ?>
			<?php foreach( $users as $user ): ?>
			<?php $checked = $group->has_user( $user->ID ) ? 'checked="checked"' : ''; ?>
			<li class="member<?php if( $group->has_user( $user->ID ) ): ?> active<?php endif; ?>" >
				<a id="remove_member_<?php echo $user->ID; ?>" class="remove_member" href="#">Remove</a>
				<input id="member_<?php echo $user->ID; ?>" type="checkbox" name="group[users][]" value="<?php echo $user->ID; ?>" <?php echo $checked; ?> />
				<label for="member_<?php echo $user->ID; ?>"><?php echo $user->display_name; ?></label>
			</li>
			<?php endforeach; ?>
		</ul>
	</div>
	<ul id="inactive-members"></ul>
</div>