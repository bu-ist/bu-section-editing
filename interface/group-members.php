<div id="add-group-members" class="buse-widget">
	<div class="buse-widget-header">
		<h4 id="add-group-members-header"><?php esc_html_e( 'Add User to this Group', 'bu-section-editing' ); ?></h4>
	</div>
	<div class="buse-widget-body">
		<div id="members-message" ></div>
		<div class="form-field">
			<label for="user_login"><?php esc_html_e( 'Enter the email address of an existing user on this network to add them to this Editor Group.', 'bu-section-editing' ); ?></label>
			<input id="user_login" type="text" class="with-button buse-suggest-user ui-autocomplete-input" autocomplete="off" role="textbox" aria-autocomplete="list" aria-haspopup="true" name="user_login" value="" />
			<button id="add_member" class="button-secondary"><?php esc_html_e( 'Add', 'bu-section-editing' ); ?></button>
		</div>
	</div>
</div>

<div id="group-members" class="buse-widget">
	<div class="buse-widget-header">
		<div id="member-list-count">
			<span class="member-count"><?php echo esc_html(count( $group->users ), 'bu-section-editing'); ?></span> <span class="member-count-label"><?php echo esc_html( 'member', 'members', count( $group->users ), 'bu-section-editing' ); ?></span>
		</div>
		<h4 id="edit-group-members-header"><?php esc_html_e( 'Group Member List', 'bu-section-editing' ); ?></h4>
	</div>
	<div class="buse-widget-body">
		<ul id="group-member-list">
			<?php $users = BU_Section_Editing_Plugin::get_allowed_users(); ?>
			<?php foreach ( $users as $user ) :  ?>
			<?php $checked = $group->has_user( $user->ID ) ? 'checked="checked"' : ''; ?>
			<li class="member<?php if ( $group->has_user( $user->ID ) ) :  ?> active<?php endif; ?>" >
				<a id="remove_member_<?php echo esc_attr($user->ID, 'bu-section-editing'); ?>" class="remove_member" href="#"><?php esc_html_e( 'Remove', 'bu-section-editing' ); ?></a>
				<input id="member_<?php echo esc_attr($user->ID, 'bu-section-editing'); ?>" type="checkbox" name="group[users][]" value="<?php echo esc_html($user->ID, 'bu-section-editing'); ?>" <?php echo esc_html($checked, 'bu-section-editing'); ?> />
				<label for="member_<?php echo esc_attr($user->ID, 'bu-section-editing'); ?>"><?php echo esc_html($user->display_name, 'bu-section-editing'); ?></label>
			</li>
			<?php endforeach; ?>
		</ul>
	</div>
	<ul id="inactive-members"></ul>
</div>
