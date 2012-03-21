<fieldset>
	<legend>Add User to this Editor Group:</legend>
	<div class="form-row">
		<label for="user_login">Find User:</label>
		<input id="user_login" type="text" name="user_login" value="" />
		<button id="find_user" class="button-secondary">Find User</button>
	</div>
	<div class="form-row">
		<button id="add_group_member" class="button-secondary">Add User</button>
	</div>
</fieldset>
<fieldset>
	<legend>Editor Group List</legend>
	<?php 
	/* List members belonging to this group */
	BU_Groups_Admin::group_member_list($group->users); 
	?>
</fieldset>