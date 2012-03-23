<?php
/* Group Stats widget */
?>

<h4>Global Stats and Actions</h4>
<div id="group-stats-widget">
	<ul>
		<li><span class="title">Group Name:</span> <span id="group-stats-name"><?php echo ''; ?></span></li>
		<li><span class="title">Group Members:</span> <span id="group-stats-count"><?php echo '20'; ?></span></li>
		<li><span class="title">Permissions:</span> <span id="group-stats-permissions"><a href="#">Add Permissions</a></span></li>
	</ul>
	<div class="actions">
		<a href="#" class="submitdelete deletion" title="Delete group">[ Delete ]</a>
		<p class="alignright">
			<input type="submit" class="button-primary" name="update" value="Update Group" />
		</p>
	</div>
</div>