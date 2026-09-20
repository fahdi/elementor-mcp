<?php
/** Page builder integration selector. @package EMCP_Tools */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$selected = EMCP_Tools_Page_Builders::selected();
?>
<div class="emcp-builders-tab">
	<h2><?php esc_html_e( 'Page Builders', 'emcp-tools' ); ?></h2>
	<p class="description"><?php esc_html_e( 'Enable one standalone page builder integration. Gutenberg and its block packs remain available alongside it. Switching integrations keeps your pages and per-tool settings.', 'emcp-tools' ); ?></p>
	<?php settings_errors( EMCP_Tools_Page_Builders::OPTION ); ?>
	<form method="post" action="options.php" id="emcp-page-builders-form">
		<?php settings_fields( EMCP_Tools_Page_Builders::SETTINGS_GROUP ); ?>
		<input type="hidden" name="<?php echo esc_attr( EMCP_Tools_Page_Builders::OPTION ); ?>" value="" />
		<div class="elementor-mcp-tools-grid emcp-modules-grid">
			<div class="elementor-mcp-tool-card emcp-module-card">
				<label class="emcp-module-head emcp-switch">
					<input type="checkbox" checked disabled aria-label="<?php esc_attr_e( 'Gutenberg is always available', 'emcp-tools' ); ?>" />
					<span class="elementor-mcp-toggle" aria-hidden="true"><span class="elementor-mcp-toggle-track"></span></span>
					<span class="elementor-mcp-tool-info">
						<span class="elementor-mcp-tool-name"><?php esc_html_e( 'Gutenberg', 'emcp-tools' ); ?></span>
						<span class="elementor-mcp-tool-desc"><?php esc_html_e( 'Always available. Includes core block tools and installed block-pack integrations such as Spectra, Kadence Blocks, GenerateBlocks, and Blocksy.', 'emcp-tools' ); ?></span>
					</span>
				</label>
			</div>
			<?php foreach ( EMCP_Tools_Page_Builders::catalog() as $id => $builder ) : ?>
				<?php $available = EMCP_Tools_Page_Builders::available( $id ); ?>
				<div class="elementor-mcp-tool-card emcp-module-card<?php echo $available ? '' : ' is-unavailable'; ?>">
					<label class="emcp-module-head emcp-switch">
						<input type="checkbox" data-emcp-builder name="<?php echo esc_attr( EMCP_Tools_Page_Builders::OPTION ); ?>" value="<?php echo esc_attr( $id ); ?>" <?php checked( $selected === $id && $available ); ?> <?php disabled( ! $available ); ?> />
						<span class="elementor-mcp-toggle" aria-hidden="true"><span class="elementor-mcp-toggle-track"></span></span>
						<span class="elementor-mcp-tool-info">
							<span class="elementor-mcp-tool-name"><?php echo esc_html( $builder['label'] ); ?></span>
							<span class="elementor-mcp-tool-desc"><?php echo esc_html( $builder['description'] ); ?></span>
						</span>
					</label>
					<?php if ( ! $available ) : ?>
						<p class="emcp-module-unavailable"><?php echo esc_html( $builder['requirement'] ); ?></p>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
		<h3><?php esc_html_e('Gutenberg Block Plugins', 'emcp-tools'); ?></h3>
		<p class="description"><?php esc_html_e('Enable any combination. Each block plugin has its own tools tab and works alongside the selected standalone builder. Blocksy Blocks also requires its native Blocksy theme and Companion plugin.', 'emcp-tools'); ?></p>
		<input type="hidden" name="<?php echo esc_attr(EMCP_Tools_Page_Builders::BLOCK_PACK_OPTION); ?>[]" value="" />
		<div class="elementor-mcp-tools-grid emcp-modules-grid">
		<?php foreach (EMCP_Tools_Page_Builders::block_packs() as $id=>$pack) : ?>
			<?php $available=EMCP_Tools_Page_Builders::available($id); ?>
			<div class="elementor-mcp-tool-card emcp-module-card">
				<label class="emcp-module-head emcp-switch">
					<input type="checkbox" name="<?php echo esc_attr(EMCP_Tools_Page_Builders::BLOCK_PACK_OPTION); ?>[]" value="<?php echo esc_attr($id); ?>" <?php checked(EMCP_Tools_Page_Builders::enabled($id)); ?> <?php disabled(!$available); ?> />
					<span class="elementor-mcp-toggle" aria-hidden="true"><span class="elementor-mcp-toggle-track"></span></span>
					<span class="elementor-mcp-tool-info"><span class="elementor-mcp-tool-name"><?php echo esc_html($pack['label']); ?></span><span class="elementor-mcp-tool-desc"><?php esc_html_e('Native blocks, schemas and page editing alongside Gutenberg.', 'emcp-tools'); ?></span></span>
				</label>
				<?php if(!$available) : ?><p class="emcp-module-unavailable"><?php echo esc_html($pack['requirement']??('Requires the active '.$pack['label'].' plugin'.($id==='generateblocks'?' and EMCP Pro':'').'.')); ?></p><?php endif; ?>
			</div>
		<?php endforeach; ?>
		</div>
		<p class="description"><?php esc_html_e( 'These switches control EMCP integrations. Your WordPress plugins and themes stay active. Reconnect your AI client after saving to refresh its tool list.', 'emcp-tools' ); ?></p>
		<?php submit_button( __( 'Save Changes', 'emcp-tools' ) ); ?>
	</form>
</div>
