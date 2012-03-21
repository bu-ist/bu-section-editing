<fieldset>
	<legend>Assign Permissions To</legend>
	<div id="group_permission_editor">
		<?php 
		/* 
		 * This should eventually look something like this:
		 *		BU_Groups_Admin::render_permission_editor_form();
		 */
		?>
		<ul id="content_types">
			<?php /* @todo Dynamically populate based on content types that support section editing */ ?>
			<li id="perm-tab-page" class="perm-tab"><a href="#perm-panel-page">Pages</a></li>
			<li id="perm-tab-post" class="perm-tab"><a href="#perm-panel-post">Posts</a></li>
		</ul>
		<?php /* @todo dynamically populate permission panels with supported content type editors */ ?>
		<div id="perm-panel-page" class="perm-panel">
			<p>Page permission editor goes here</p>
			<div id="perm-editor-page" class="perm-editor hierarchical">
			</div>
		</div>
		<div id="perm-panel-post" class="perm-panel">
			<p>Post permission editor goes here</p>
			<div id="perm-editor-post" class="perm-editor flat">
			</div>
		</div>
	</div>
</fieldset>