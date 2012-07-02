<fieldset>
	<div class="form-field">
		<label for="edit-group-name">Name</label>
		<input name="group[name]" id="edit-group-name" type="text" value="<?php echo esc_attr( $group->name ); ?>"/>
	</div>
	<div class="form-field">
		<label for="edit-group-description">Description</label>
		<textarea name="group[description]" rows="5" cols="30" id="edit-group-description"><?php echo esc_html( $group->description ); ?></textarea>
	</div>
</fieldset>