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
						<a href="#" class="perm-tree-expand" data-target="perm-editor-<?php echo $pt->name; ?>"><?php _e( 'Expand All', BUSE_TEXTDOMAIN ); ?></a> |
						<a href="#" class="perm-tree-collapse" data-target="perm-editor-<?php echo $pt->name; ?>"><?php _e( 'Collapse All', BUSE_TEXTDOMAIN ); ?></a>
					</p>
					<?php else: ?>
					<p class="alignleft">
						<input id="perm-search-<?php echo $pt->name; ?>" type="text" name="perm-action[][search]" class="perm-search <?php echo $hiearchical_class; ?>" >
						<button class="perm-search flat button-secondary"><?php printf( __( 'Search %s', BUSE_TEXTDOMAIN ), $pt->label ); ?></button>
					</p>
					<p class="alignright">
						<a class="perm-editor-bulk-edit" href="#" title="<?php esc_attr_e( 'Enable bulk edit mode', BUSE_TEXTDOMAIN ); ?>"><?php _e( 'Bulk Edit', BUSE_TEXTDOMAIN ); ?></a>
					</p>
					<?php endif; ?>
				</div><!-- .perm-tooblar.top -->
				<?php if( ! $hierarchical ): ?>
				<div class="perm-editor-bulk-edit-panel clearfix">
					<div class="bulk-edit-actions">
						<input type="checkbox" class="bulk-edit-select-all" name="perm-ed-bulk-edit[select-all]" value="1">
						<select name="perm-ed-bulk-edit[action]">
							<option value="none"><?php _e( 'Bulk Actions', BUSE_TEXTDOMAIN ); ?></option>
							<option value="allowed"><?php _e( 'Allow selected', BUSE_TEXTDOMAIN ); ?></option>
							<option value="denied"><?php _e( 'Deny selected', BUSE_TEXTDOMAIN ); ?></option>
						</select>
						<button class="button-secondary"><?php _e( 'Apply', BUSE_TEXTDOMAIN ); ?></button>
					</div>
				</div>
				<?php endif; ?>
				<div class="perm-scroll-area">
					<input type="hidden" id="buse-edits-<?php echo $pt->name; ?>" class="buse-edits" name="group[perms][<?php echo $pt->name; ?>]" value="" />
					<div id="perm-editor-<?php echo $pt->name; ?>" class="perm-editor <?php echo $hiearchical_class; ?>" data-post-type="<?php echo $pt->name; ?>"></div><!-- perm-editor-<?php echo $pt->name; ?> -->
				</div>
				<?php if( ! $hierarchical ): // Flat post editors get pagination ?>
				<div class="perm-toolbar bottom clearfix">
					<div class="tablenav">
						<div id="perm-editor-pagination-<?php echo $pt->name; ?>" class="tablenav-pages">
							<span id=""class="displaying-num"><?php _e( '0 items', BUSE_TEXTDOMAIN ); ?></span>
							<span class="pagination-links">
								<a class="first-page" title="<?php esc_attr_e( 'Go to the first page', BUSE_TEXTDOMAIN ); ?>" href="#">«</a>
								<a class="prev-page" title="<?php esc_attr_e( 'Go to the previous page', BUSE_TEXTDOMAIN ); ?>" href="#">‹</a>
								<span class="paging-input">
									<input type="text" class="current-page" name="perm-editor-page[<?php echo $pt->name; ?>]" size="2" value="1"> of <span class="total-pages">1</span>
								</span>
								<a class="next-page" title="<?php esc_attr_e( 'Go to the next page', BUSE_TEXTDOMAIN ); ?>" href="#">›</a>
								<a class="last-page" title="<?php esc_attr_e( 'Go to the last page', BUSE_TEXTDOMAIN ); ?>" href="#">»</a>
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