<div id="add-group-members" class="buse-widget">
	<div class="buse-widget-header">
		<h4 id="add-group-members-header"><?php _e( 'Add User to this Group', BUSE_TEXTDOMAIN ); ?></h4>
	</div>
	<div class="buse-widget-body">
		<div id="members-message" ></div>
		<div class="form-field">
			<label for="user_login"><?php _e( 'Enter the email address of an existing user on this network to add them to this Editor Group.', BUSE_TEXTDOMAIN ); ?></label>
			<input id="user_login" type="text" class="with-button buse-suggest-user ui-autocomplete-input" autocomplete="off" role="textbox" aria-autocomplete="list" aria-haspopup="true" name="user_login" value="" />
			<button id="add_member" class="button-secondary"><?php _e( 'Add', BUSE_TEXTDOMAIN ); ?></button>
		</div>
	</div>
</div>

<div id="group-members" class="buse-widget">
	<div class="buse-widget-header">
		<div id="member-list-count">
			<span class="member-count"><?php echo count( $group->users ); ?></span> <span class="member-count-label"><?php echo _n( 'member', 'members', count( $group->users ), BUSE_TEXTDOMAIN ); ?></span>
		</div>
		<h4 id="edit-group-members-header"><?php _e( 'Group Member List', BUSE_TEXTDOMAIN ); ?></h4>
	</div>
	<div class="buse-widget-body">
		<ul id="group-member-list">
			<?php $users = BU_Section_Editing_Plugin::get_allowed_users(); ?>
			<?php foreach( $users as $user ): ?>
			<?php $checked = $group->has_user( $user->ID ) ? 'checked="checked"' : ''; ?>
			<li class="member<?php if( $group->has_user( $user->ID ) ): ?> active<?php endif; ?>" >
				<a id="remove_member_<?php echo $user->ID; ?>" class="remove_member" href="#"><?php _e( 'Remove', BUSE_TEXTDOMAIN ); ?></a>
				<input id="member_<?php echo $user->ID; ?>" type="checkbox" name="group[users][]" value="<?php echo $user->ID; ?>" <?php echo $checked; ?> />
				<label for="member_<?php echo $user->ID; ?>"><?php echo $user->display_name; ?></label>
			</li>
			<?php endforeach; ?>
		</ul>
	</div>
	<ul id="inactive-members"></ul>
</div>