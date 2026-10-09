<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div id="group-permission-editor">
	<?php $buse_content_types = BU_Group_Permissions::get_supported_post_types(); ?>
	<?php if ( ! empty( $buse_content_types ) ) : ?>
		<div id="perm-tab-container">
			<?php foreach ( $buse_content_types as $buse_post_type ) :  ?>
				<?php $buse_active = $perm_panel == $buse_post_type->name ? ' nav-tab-active' : ''; ?>
				<a href="#perm-panel-<?php echo esc_attr($buse_post_type->name, 'bu-section-editing'); ?>" class="nav-link nav-tab inline<?php echo esc_attr($buse_active, 'bu-section-editing'); ?>" data-target="<?php echo esc_attr($buse_post_type->name, 'bu-section-editing'); ?>" ><?php echo esc_html($buse_post_type->label, 'bu-section-editing'); ?></a>
			<?php endforeach; ?>
		</div><!-- perm-tab-container -->
		<div id="perm-panel-container">
		<?php foreach ( $buse_content_types as $buse_post_type ) :  ?>
			<?php
			$buse_active = $perm_panel == $buse_post_type->name ? ' active' : '';
			$buse_hierarchical = $buse_post_type->hierarchical ? true : false;
			$buse_hierarchical_class = $buse_hierarchical ? 'hierarchical' : 'flat';
			$buse_is_post = 'post' === $buse_post_type->name;
			$buse_editable = $groups->get_allowed_posts( array( 'group' => $group_id, 'post_type' => $buse_post_type->name ) );
			?>
			<div id="perm-panel-<?php echo esc_attr($buse_post_type->name, 'bu-section-editing'); ?>" class="perm-panel <?php echo esc_attr($buse_active, 'bu-section-editing'); ?>" data-editable-original="<?php echo esc_attr(htmlspecialchars( implode( ',', $buse_editable ) ), 'bu-section-editing'); ?>">
				<?php if ( ! $buse_hierarchical && ! $buse_is_post ) : ?>
					<div class="perm-global-edit clearfix">
						<div class="perm-global-edit-checkbox">
							<input id="perm-global-edit-<?php echo esc_attr($buse_post_type->name, 'bu-section-editing'); ?>" class="perm-global-edit-action" type="checkbox" name="group[global_edit][]" value="<?php echo esc_attr($buse_post_type->name, 'bu-section-editing'); ?>" <?php echo esc_html($groups->post_is_globally_editable_by_group( $buse_post_type->name, $group_id ), 'bu-section-editing') ? 'checked' : ''; ?> >
							<label class="perm-global-edit-label" for="perm-global-edit-<?php echo esc_attr($buse_post_type->name, 'bu-section-editing') ?>">
								<?php esc_html_e( 'Full access (edit/publish/delete) to all posts of this type', 'bu-section-editing' ); ?>
							</label>
						</div>
					</div>
				<?php endif; ?>
				<div id="perm-toolbar-<?php echo esc_attr($buse_post_type->name, 'bu-section-editing'); ?>-top" class="perm-toolbar top clearfix">
					<?php if ( $buse_hierarchical ) :  ?>
					<p class="alignright">
						<a href="#" class="perm-tree-expand" data-target="perm-editor-<?php echo esc_attr($buse_post_type->name, 'bu-section-editing'); ?>"><?php esc_html_e( 'Expand All', 'bu-section-editing' ); ?></a> |
						<a href="#" class="perm-tree-collapse" data-target="perm-editor-<?php echo esc_attr($buse_post_type->name, 'bu-section-editing'); ?>"><?php esc_html_e( 'Collapse All', 'bu-section-editing' ); ?></a>
					</p>
					<?php else : ?>
					<p class="alignleft">
						<input id="perm-search-<?php echo esc_attr($buse_post_type->name, 'bu-section-editing'); ?>" type="text" name="perm-action[][search]" class="perm-search <?php echo esc_attr($buse_hierarchical_class, 'bu-section-editing'); ?>" >
						<?php // translators: %s stands for the name of the content type ?>
						<button class="perm-search flat button-secondary"><?php printf( esc_html__( 'Search %s', 'bu-section-editing' ), esc_html( $buse_post_type->label ) ); ?></button>
					</p>
					<p class="alignright">
						<a class="perm-editor-bulk-edit" href="#" title="<?php esc_attr_e( 'Enable bulk edit mode', 'bu-section-editing' ); ?>"><?php esc_html_e( 'Bulk Edit', 'bu-section-editing' ); ?></a>
					</p>
					<?php endif; ?>
				</div><!-- .perm-tooblar.top -->
				<?php if ( ! $buse_hierarchical ) :  ?>
				<div class="perm-editor-bulk-edit-panel clearfix">
					<div class="bulk-edit-actions">
						<input type="checkbox" class="bulk-edit-select-all" name="perm-ed-bulk-edit[select-all]" value="1">
						<select name="perm-ed-bulk-edit[action]">
							<option value="none"><?php esc_html_e( 'Bulk Actions', 'bu-section-editing' ); ?></option>
							<option value="allowed"><?php esc_html_e( 'Allow selected', 'bu-section-editing' ); ?></option>
							<option value="denied"><?php esc_html_e( 'Deny selected', 'bu-section-editing' ); ?></option>
						</select>
						<button class="button-secondary"><?php esc_html_e( 'Apply', 'bu-section-editing' ); ?></button>
					</div>
				</div>
				<?php endif; ?>
				<div class="perm-scroll-area">
					<input type="hidden" id="buse-edits-<?php echo esc_attr($buse_post_type->name, 'bu-section-editing'); ?>" class="buse-edits" name="group[perms][<?php echo esc_attr($buse_post_type->name, 'bu-section-editing'); ?>]" value="" />
					<div id="perm-editor-<?php echo esc_attr($buse_post_type->name, 'bu-section-editing'); ?>" class="perm-editor <?php echo esc_attr($buse_hierarchical_class, 'bu-section-editing'); ?>" data-post-type="<?php echo esc_attr($buse_post_type->name, 'bu-section-editing'); ?>" data-original-global-edit="<?php echo esc_attr($groups->post_is_globally_editable_by_group( $buse_post_type->name, $group_id ), 'bu-section-editing') ? 'true' : ''; ?>"></div><!-- perm-editor-<?php echo esc_attr($buse_post_type->name, 'bu-section-editing'); ?> -->
				</div>
				<?php if ( ! $buse_hierarchical ) :  // Flat post editors get pagination ?>
				<div class="perm-toolbar bottom clearfix">
					<div class="tablenav">
						<div id="perm-editor-pagination-<?php echo esc_attr($buse_post_type->name, 'bu-section-editing'); ?>" class="tablenav-pages">
							<span id=""class="displaying-num"><?php esc_attr_e( '0 items', 'bu-section-editing' ); ?></span>
							<span class="pagination-links">
								<a class="first-page" title="<?php esc_attr_e( 'Go to the first page', 'bu-section-editing' ); ?>" href="#">«</a>
								<a class="prev-page" title="<?php esc_attr_e( 'Go to the previous page', 'bu-section-editing' ); ?>" href="#">‹</a>
								<span class="paging-input">
									<input type="text" class="current-page" name="perm-editor-page[<?php echo esc_attr($buse_post_type->name, 'bu-section-editing'); ?>]" size="2" value="1"> of <span class="total-pages">1</span>
								</span>
								<a class="next-page" title="<?php esc_attr_e( 'Go to the next page', 'bu-section-editing' ); ?>" href="#">›</a>
								<a class="last-page" title="<?php esc_attr_e( 'Go to the last page', 'bu-section-editing' ); ?>" href="#">»</a>
							</span>
						</div>
					</div><!-- .tablenav -->
				</div><!-- .perm-toolbar.bottom -->
				<?php endif; ?>
			</div><!-- perm-panel-<?php echo esc_attr($buse_post_type->name, 'bu-section-editing'); ?> -->
		<?php endforeach; ?>
		</div><!-- perm-panel-container -->
	<?php endif; ?>
</div><!-- group-permissions-editor -->
