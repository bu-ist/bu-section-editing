<div id="group-permission-editor">
	<?php $content_types = BU_Group_Permissions::get_supported_post_types(); ?>
	<?php if( ! empty( $content_types ) ) : ?>
		<div id="perm-tab-container">
			<?php foreach( $content_types as $index => $pt ): ?>
				<?php $active = $perm_panel == $pt->name ? ' nav-tab-active' : ''; ?>
				<a href="#perm-panel-<?php echo $pt->name; ?>" class="nav-link nav-tab inline<?php echo $active; ?>" data-target="<?php echo $pt->name; ?>" ><?php echo $pt->label; ?></a>
			<?php endforeach; ?>
		</div><!-- perm-tab-container -->
		<div id="perm-panel-container">
		<?php foreach( $content_types as $index => $pt ): ?>
			<?php 
			$active = $perm_panel == $pt->name ? ' active' : '';
			$hierarchical = $pt->hierarchical ? true : false;
			$hiearchical_class = $hierarchical ? 'hierarchical' : 'flat';
			?>
			<div id="perm-panel-<?php echo $pt->name; ?>" class="perm-panel <?php echo $active; ?>">
				<div id="perm-toolbar-<?php echo $pt->name; ?>-top" class="perm-toolbar top clearfix">
					<?php if( $hierarchical ): ?>
					<p class="alignright">
						<a href="#" class="perm-tree-expand" data-target="perm-editor-<?php echo $pt->name; ?>">Expand All</a> | 
						<a href="#" class="perm-tree-collapse" data-target="perm-editor-<?php echo $pt->name; ?>">Collapse All</a>
					</p>
					<?php else: ?>
					<p class="alignleft">
						<input id="perm-search-<?php echo $pt->name; ?>" type="text" name="perm-action[][search]" class="perm-search <?php echo $hiearchical_class; ?>" > 
						<button class="perm-search flat button-secondary">Search <?php echo $pt->label; ?></button>
					</p>
					<?php endif; ?>
				</div><!-- .perm-tooblar.top -->
				<div class="perm-scroll-area">
					<input type="hidden" id="buse-edits-<?php echo $pt->name; ?>" class="buse-edits" name="group[perms][<?php echo $pt->name; ?>]" value="" />
					<div id="perm-editor-<?php echo $pt->name; ?>" class="perm-editor <?php echo $hiearchical_class; ?>" data-post-type="<?php echo $pt->name; ?>">
					</div><!-- perm-editor-<?php echo $pt->name; ?> -->
				</div>
				<?php if( ! $hierarchical ): // Flat post editors get pagination ?>
				<div class="perm-toolbar bottom clearfix">
					<div class="tablenav">
						<div id="perm-editor-pagination-<?php echo $pt->name; ?>" class="tablenav-pages">
							<span id=""class="displaying-num">0 items</span>
							<span class="pagination-links">
								<a class="first-page" title="Go to the first page" href="#">«</a>
								<a class="prev-page" title="Go to the previous page" href="#">‹</a>
								<span class="paging-input">
									<span class="current-page">1</span> of <span class="total-pages">1</span>
								</span>
								<a class="next-page" title="Go to the next page" href="#">›</a>
								<a class="last-page" title="Go to the last page" href="#">»</a>
							</span>
						</div>
					</div><!-- .tablenav -->
				</div><!-- .perm-toolbar.bottom -->
				<?php endif; ?>
			</div><!-- perm-panel-<?php echo $pt->name; ?> -->
		<?php endforeach; ?>
		</div><!-- perm-panel-container -->
	<?php endif; ?>
</div><!-- group-permissions-editor -->