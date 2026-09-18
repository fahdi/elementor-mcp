<?php
/**
 * Shared admin page shell and tab content.
 *
 * Included by EMCP_Tools_Admin::render_page() with $active_tab in scope.
 *
 * @package EMCP_Tools
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

		?>
		<div class="wrap elementor-mcp-admin">
			<h1><?php esc_html_e( 'EMCP Tools', 'emcp-tools' ); ?></h1>

			<?php
			// Success notice after a Settings API save (options.php redirects back
			// with settings-updated=true). Shown for any EMCP settings tab.
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- options.php verifies the settings nonce before redirecting.
			if ( isset( $_GET['settings-updated'] ) && 'true' === sanitize_text_field( wp_unslash( $_GET['settings-updated'] ) ) ) :
				?>
				<?php
				// Rendered as the finished toast rather than as a notice the script
				// then moves. Moving it meant the browser painted it at the top of
				// the page first and jumped it to the corner a frame later, and it
				// put the confirmation at the mercy of the script running at all.
				// This way the first paint is already bottom-right, and admin.js
				// only adds the dismissing.
				//
				// `inline` is load-bearing, not cosmetic. On jQuery ready core runs
				//   $( 'div.notice' ).not( '.inline, .below-h2' ).insertAfter( $headerEnd )
				// which relocates every other notice to just under the page h1, and
				// would pull this one straight back out of the toast.
				?>
				<div class="emcp-toasts" aria-live="polite">
					<div class="emcp-toast emcp-toast--success" role="status">
						<div class="notice notice-success inline emcp-saved-notice emcp-toast__notice">
							<p><strong><?php esc_html_e( 'Settings saved.', 'emcp-tools' ); ?></strong></p>
						</div>
						<button type="button" class="emcp-toast__close" aria-label="<?php esc_attr_e( 'Dismiss', 'emcp-tools' ); ?>">&times;</button>
					</div>
				</div>
				<?php
			endif;
			?>

			<?php
			// Only show the upgrade CTA to sites without a valid Pro license.
			// Freemius adds its own Contact / Account / Upgrade items to the
			// EMCP Tools menu, so we don't need a redundant header link.
			$emcp_tools_show_upgrade = ! function_exists( 'emcp_tools_fs' )
				|| ! emcp_tools_fs()->can_use_premium_code();
			?>

			<?php
			// App-bar notifications bell + cloud button state (Cloud-fed, cached,
			// graceful offline — see EMCP_Tools_Notifications).
			$emcp_notifs  = class_exists( 'EMCP_Tools_Notifications' ) ? EMCP_Tools_Notifications::get() : array();
			$emcp_uid     = get_current_user_id();
			$emcp_unread  = class_exists( 'EMCP_Tools_Notifications' ) ? EMCP_Tools_Notifications::unread_count( $emcp_uid ) : 0;
			$emcp_seen    = (array) get_user_meta( $emcp_uid, '_emcp_tools_read_notifications', true );
			$emcp_cloud_connected = class_exists( 'EMCP_Tools_Cloud' ) && EMCP_Tools_Cloud::is_connected();
			?>

			<!-- Rotating promo / announcement bar -->
			<?php
			$emcp_anncs = array(
				array(
					'key'   => 'cloud',
					'badge' => __( 'New', 'emcp-tools' ),
					'icon'  => 'dashicons-cloud',
					'title' => __( 'EMCP Cloud is live', 'emcp-tools' ),
					'text'  => __( 'Back up, sync and sell your blocks, widgets and snippets across every site you run.', 'emcp-tools' ),
					'cta'   => __( 'Explore Cloud', 'emcp-tools' ),
					'url'   => 'https://emcptools.com/cloud',
				),
			);
			if ( $emcp_tools_show_upgrade ) {
				$emcp_anncs[] = array(
					'key'   => 'ltd',
					'badge' => __( 'Limited', 'emcp-tools' ),
					'icon'  => 'dashicons-clock',
					'title' => __( 'Lifetime deal ends soon', 'emcp-tools' ),
					'text'  => __( 'Pay once, own EMCP Pro forever — this lifetime deal is going away for good.', 'emcp-tools' ),
					'cta'   => __( 'Get the LTD', 'emcp-tools' ),
					'url'   => function_exists( 'emcp_tools_upgrade_url' ) ? emcp_tools_upgrade_url() : 'https://emcptools.com/pricing',
				);
			}
			$emcp_annc_rotate = count( $emcp_anncs ) > 1;
			?>
			<div class="emcp-annc" data-emcp-annc data-rotate="<?php echo $emcp_annc_rotate ? '1' : '0'; ?>">
				<div class="emcp-annc-slides">
					<?php foreach ( $emcp_anncs as $emcp_i => $emcp_a ) : ?>
						<a class="emcp-annc-slide emcp-annc-slide--<?php echo esc_attr( $emcp_a['key'] ); ?><?php echo 0 === $emcp_i ? ' is-active' : ''; ?>" href="<?php echo esc_url( $emcp_a['url'] ); ?>" target="_blank" rel="noopener">
							<span class="emcp-annc-badge"><?php echo esc_html( $emcp_a['badge'] ); ?></span>
							<span class="emcp-annc-icon dashicons <?php echo esc_attr( $emcp_a['icon'] ); ?>" aria-hidden="true"></span>
							<span class="emcp-annc-text"><strong><?php echo esc_html( $emcp_a['title'] ); ?></strong> <?php echo esc_html( $emcp_a['text'] ); ?></span>
							<span class="emcp-annc-cta"><?php echo esc_html( $emcp_a['cta'] ); ?><span class="dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span></span>
						</a>
					<?php endforeach; ?>
				</div>
				<?php if ( $emcp_annc_rotate ) : ?>
					<div class="emcp-annc-dots">
						<?php foreach ( $emcp_anncs as $emcp_i => $emcp_a ) : ?>
							<button type="button" class="emcp-annc-dot<?php echo 0 === $emcp_i ? ' is-active' : ''; ?>" data-i="<?php echo (int) $emcp_i; ?>" aria-label="<?php echo esc_attr( sprintf( /* translators: %d: announcement number */ __( 'Announcement %d', 'emcp-tools' ), $emcp_i + 1 ) ); ?>"></button>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
			<script>
			( function () {
				var b = document.querySelector( '[data-emcp-annc]' );
				if ( ! b ) { return; }
				var slides = b.querySelectorAll( '.emcp-annc-slide' ), dots = b.querySelectorAll( '.emcp-annc-dot' ), i = 0, t;
				function go( n ) { i = ( n + slides.length ) % slides.length; slides.forEach( function ( s, x ) { s.classList.toggle( 'is-active', x === i ); } ); dots.forEach( function ( d, x ) { d.classList.toggle( 'is-active', x === i ); } ); }
				function reset() { if ( b.getAttribute( 'data-rotate' ) !== '1' ) { return; } clearInterval( t ); t = setInterval( function () { go( i + 1 ); }, 7000 ); }
				dots.forEach( function ( d ) { d.addEventListener( 'click', function () { go( parseInt( d.getAttribute( 'data-i' ), 10 ) ); reset(); } ); } );
				reset();
			} )();
			</script>

			<!-- App bar -->
			<div class="emcp-appbar">
				<div class="emcp-appbar-brand">
					<img class="emcp-appbar-logo" src="<?php echo esc_url( EMCP_TOOLS_URL . 'assets/img/icon-sm.png' ); ?>" alt="" />
					<span class="emcp-appbar-title emcp-appbar-title--full"><?php esc_html_e( 'EMCP Tools', 'emcp-tools' ); ?></span>
					<span class="emcp-appbar-title emcp-appbar-title--short"><?php esc_html_e( 'MCP Tools', 'emcp-tools' ); ?></span>
					<span class="emcp-appbar-version">v<?php echo esc_html( EMCP_TOOLS_VERSION ); ?></span>
				</div>
				<div class="emcp-appbar-actions">
					<a class="emcp-appbar-changelog<?php echo 'mcp-log' === $active_tab ? ' is-active' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '-mcp-log' ) ); ?>">
						<span class="dashicons dashicons-list-view" aria-hidden="true"></span>
						<?php esc_html_e( 'MCP Log', 'emcp-tools' ); ?>
					</a>
					<a class="emcp-appbar-changelog<?php echo 'history' === $active_tab ? ' is-active' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '-history' ) ); ?>">
						<span class="dashicons dashicons-clock" aria-hidden="true"></span>
						<?php esc_html_e( 'History', 'emcp-tools' ); ?>
					</a>
					<a class="emcp-appbar-changelog<?php echo 'changelog' === $active_tab ? ' is-active' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '-changelog' ) ); ?>">
						<span class="dashicons dashicons-backup" aria-hidden="true"></span>
						<?php esc_html_e( 'Changelog', 'emcp-tools' ); ?>
					</a>
					<?php if ( self::affiliation_page_available() ) : ?>
						<a class="emcp-appbar-changelog" href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '-affiliation' ) ); ?>">
							<span class="dashicons dashicons-money-alt" aria-hidden="true"></span>
							<?php esc_html_e( 'Affiliate', 'emcp-tools' ); ?>
						</a>
					<?php endif; ?>
					<?php if ( $emcp_tools_show_upgrade ) : ?>
						<a class="emcp-appbar-upgrade" href="<?php echo esc_url( emcp_tools_upgrade_url() ); ?>" target="_blank" rel="noopener noreferrer">
							<span class="dashicons dashicons-star-filled" aria-hidden="true"></span>
							<?php esc_html_e( 'Upgrade to Pro', 'emcp-tools' ); ?>
						</a>
					<?php endif; ?>
					<div class="emcp-help-menu">
						<button type="button" class="emcp-help-toggle" aria-haspopup="true">
							<span class="dashicons dashicons-info-outline" aria-hidden="true"></span>
							<?php esc_html_e( 'Get Help', 'emcp-tools' ); ?>
							<span class="dashicons dashicons-arrow-down-alt2 emcp-help-caret" aria-hidden="true"></span>
						</button>
						<div class="emcp-help-dropdown" role="menu">
							<a role="menuitem" href="https://support.msrbuilds.com/" target="_blank" rel="noopener noreferrer"><span class="dashicons dashicons-sos" aria-hidden="true"></span><?php esc_html_e( 'Ticket Support', 'emcp-tools' ); ?></a>
							<a role="menuitem" href="https://emcptools.com/docs" target="_blank" rel="noopener noreferrer"><span class="dashicons dashicons-book" aria-hidden="true"></span><?php esc_html_e( 'Documentation', 'emcp-tools' ); ?></a>
							<a role="menuitem" href="https://www.facebook.com/groups/emcptools" target="_blank" rel="noopener noreferrer"><span class="dashicons dashicons-groups" aria-hidden="true"></span><?php esc_html_e( 'Community', 'emcp-tools' ); ?></a>
							<a role="menuitem" href="https://discord.gg/vJfksd3S9j" target="_blank" rel="noopener noreferrer"><span class="dashicons dashicons-format-chat" aria-hidden="true"></span><?php esc_html_e( 'Discord', 'emcp-tools' ); ?></a>
							<a role="menuitem" href="https://emcptools.com/tutorials" target="_blank" rel="noopener noreferrer"><span class="dashicons dashicons-video-alt3" aria-hidden="true"></span><?php esc_html_e( 'Tutorials', 'emcp-tools' ); ?></a>
						</div>
					</div>
					<div class="emcp-notif">
						<button type="button" class="emcp-notif-toggle" aria-haspopup="true" aria-expanded="false" data-nonce="<?php echo esc_attr( wp_create_nonce( 'emcp_tools_notifications' ) ); ?>">
							<span class="dashicons dashicons-bell" aria-hidden="true"></span>
							<span class="emcp-notif-badge<?php echo 0 === $emcp_unread ? ' is-empty' : ''; ?>"><?php echo esc_html( (string) $emcp_unread ); ?></span>
						</button>
						<div class="emcp-notif-overlay" aria-hidden="true"></div>
						<aside class="emcp-notif-drawer" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Announcements', 'emcp-tools' ); ?>">
							<div class="emcp-notif-header">
								<span><?php esc_html_e( 'Announcements', 'emcp-tools' ); ?></span>
								<button type="button" class="emcp-notif-close" aria-label="<?php esc_attr_e( 'Close', 'emcp-tools' ); ?>"><span class="dashicons dashicons-no-alt" aria-hidden="true"></span></button>
							</div>
							<div class="emcp-notif-list">
								<?php if ( empty( $emcp_notifs ) ) : ?>
									<div class="emcp-notif-empty"><?php esc_html_e( 'No announcements yet.', 'emcp-tools' ); ?></div>
								<?php else : ?>
									<?php foreach ( $emcp_notifs as $emcp_n ) : ?>
										<?php
										$emcp_n_id      = isset( $emcp_n['id'] ) ? (string) $emcp_n['id'] : '';
										$emcp_n_unread  = '' !== $emcp_n_id && ! in_array( $emcp_n_id, $emcp_seen, true );
										$emcp_n_level   = isset( $emcp_n['level'] ) && '' !== $emcp_n['level'] ? sanitize_html_class( $emcp_n['level'] ) : 'info';
										$emcp_n_icon    = isset( $emcp_n['icon'] ) && '' !== $emcp_n['icon'] ? sanitize_html_class( $emcp_n['icon'] ) : 'megaphone';
										$emcp_n_created = isset( $emcp_n['created_at'] ) ? strtotime( (string) $emcp_n['created_at'] ) : false;
										?>
										<div class="emcp-notif-item emcp-notif-item--<?php echo esc_attr( $emcp_n_level ); ?><?php echo $emcp_n_unread ? ' is-unread' : ''; ?>" data-id="<?php echo esc_attr( $emcp_n_id ); ?>">
											<span class="emcp-notif-item-icon dashicons dashicons-<?php echo esc_attr( $emcp_n_icon ); ?>" aria-hidden="true"></span>
											<div class="emcp-notif-item-body">
												<strong><?php echo esc_html( isset( $emcp_n['title'] ) ? $emcp_n['title'] : '' ); ?></strong>
												<p><?php echo esc_html( isset( $emcp_n['body'] ) ? $emcp_n['body'] : '' ); ?></p>
												<div class="emcp-notif-item-meta">
													<?php if ( false !== $emcp_n_created && $emcp_n_created > 0 ) : ?>
														<span class="emcp-notif-item-time">
															<?php
															/* translators: %s: human-readable time difference (e.g. "2 hours") */
															echo esc_html( sprintf( __( '%s ago', 'emcp-tools' ), human_time_diff( $emcp_n_created ) ) );
															?>
														</span>
													<?php endif; ?>
													<?php if ( ! empty( $emcp_n['url'] ) ) : ?>
														<a class="emcp-notif-item-cta" href="<?php echo esc_url( $emcp_n['url'] ); ?>" target="_blank" rel="noopener">
															<?php echo esc_html( ! empty( $emcp_n['cta'] ) ? $emcp_n['cta'] : __( 'Learn more', 'emcp-tools' ) ); ?>
														</a>
													<?php endif; ?>
												</div>
											</div>
										</div>
									<?php endforeach; ?>
								<?php endif; ?>
							</div>
						</aside>
					</div>
					<a class="emcp-cloud-btn" href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '-connection' ) ); ?>" title="<?php echo esc_attr( $emcp_cloud_connected ? __( 'EMCP Cloud: Connected', 'emcp-tools' ) : __( 'EMCP Cloud: Not connected — click to connect', 'emcp-tools' ) ); ?>">
						<span class="dashicons dashicons-cloud emcp-cloud-icon" aria-hidden="true"></span>
						<span class="emcp-cloud-dot<?php echo $emcp_cloud_connected ? ' is-connected' : ''; ?>"></span>
					</a>
				</div>
			</div>

			<!-- Tab nav -->
						<div class="emcp-appnav-wrap">
				<button type="button" class="emcp-appnav-arrow emcp-appnav-arrow--prev" aria-label="<?php esc_attr_e( 'Scroll tabs left', 'emcp-tools' ); ?>" hidden><span class="dashicons dashicons-arrow-left-alt2" aria-hidden="true"></span></button>
<nav class="emcp-appnav" aria-label="<?php esc_attr_e( 'EMCP Tools sections', 'emcp-tools' ); ?>">
				<?php
				foreach ( $this->get_submenus() as $emcp_slug => $emcp_label ) :
					$emcp_tab_id = ( self::PAGE_SLUG === $emcp_slug ) ? 'dashboard' : substr( $emcp_slug, strlen( self::PAGE_SLUG . '-' ) );
					// Changelog + History + MCP Log live in the app-bar top-right, not the tab nav.
					if ( 'changelog' === $emcp_tab_id || 'history' === $emcp_tab_id || 'mcp-log' === $emcp_tab_id ) {
						continue;
					}
					$emcp_is_on = ( $emcp_tab_id === $active_tab );
					?>
					<a class="emcp-appnav-item<?php echo $emcp_is_on ? ' is-active' : ''; ?>"
						href="<?php echo esc_url( admin_url( 'admin.php?page=' . $emcp_slug ) ); ?>"
						<?php echo $emcp_is_on ? 'aria-current="page"' : ''; ?>>
						<span class="dashicons <?php echo esc_attr( self::tab_icon( $emcp_tab_id ) ); ?>" aria-hidden="true"></span>
						<span class="emcp-appnav-label"><?php echo esc_html( $emcp_label ); ?></span>
						<?php
						if ( self::PAGE_SLUG . '-memory' === $emcp_slug ) {
							$emcp_pending = $this->memory_pending_count();
							if ( $emcp_pending > 0 ) {
								echo '<span class="emcp-appnav-badge" title="' . esc_attr__( 'Pending memory proposals awaiting review', 'emcp-tools' ) . '">' . (int) $emcp_pending . '</span>';
							}
						}
						?>
					</a>
				<?php endforeach; ?>
			</nav>
				<button type="button" class="emcp-appnav-arrow emcp-appnav-arrow--next" aria-label="<?php esc_attr_e( 'Scroll tabs right', 'emcp-tools' ); ?>" hidden><span class="dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span></button>
			</div>

			<!-- Content -->
			<div class="tab-content<?php echo 'dashboard' === $active_tab ? ' tab-content--flush' : ''; ?>">
				<?php
				if ( 'dashboard' === $active_tab ) {
					include EMCP_TOOLS_DIR . 'includes/admin/views/page-dashboard.php';
				} elseif ( 'page-builders' === $active_tab ) {
					include EMCP_TOOLS_DIR . 'includes/admin/views/page-builders.php';
				} elseif ( 'modules' === $active_tab ) {
					include EMCP_TOOLS_DIR . 'includes/admin/views/page-modules.php';
				} elseif ( 'connection' === $active_tab ) {
					include EMCP_TOOLS_DIR . 'includes/admin/views/page-connection.php';
				} elseif ( 'ai-chat' === $active_tab && $this->ai_chat_tab_visible() ) {
					$emcp_pro_view = EMCP_Tools_Pro_Loader::path( 'includes/admin/views/page-ai-chat.php' );
					if ( '' !== $emcp_pro_view ) {
						include $emcp_pro_view;
					} else {
						include EMCP_TOOLS_DIR . 'includes/admin/views/page-ai-chat-upsell.php';
					}
				} elseif ( 'context' === $active_tab ) {
					include EMCP_TOOLS_DIR . 'includes/admin/views/page-context.php';
				} elseif ( 'memory' === $active_tab && $this->memory_tab_visible() ) {
					$emcp_mem_view = EMCP_Tools_Pro_Loader::path( 'includes/admin/views/page-memory.php' );
					if ( '' !== $emcp_mem_view ) {
						include $emcp_mem_view;
					}
				} elseif ( 'prompts' === $active_tab && $this->module_tab_visible( 'prompts' ) ) {
					include EMCP_TOOLS_DIR . 'includes/admin/views/page-prompts.php';
				} elseif ( 'templates' === $active_tab && $this->module_tab_visible( 'templates' ) ) {
					include EMCP_TOOLS_DIR . 'includes/admin/views/page-templates.php';
				} elseif ( 'brand-kits' === $active_tab && $this->module_tab_visible( 'brand-kits' ) ) {
					include EMCP_TOOLS_DIR . 'includes/admin/views/page-brand-kits.php';
				} elseif ( 'skills' === $active_tab ) {
					$emcp_pro_view = EMCP_Tools_Pro_Loader::path( 'includes/admin/views/page-skills.php' );
					if ( '' !== $emcp_pro_view ) {
						include $emcp_pro_view;
					} else {
						$emcp_upsell_feature = __( 'Skills', 'emcp-tools' );
						include EMCP_TOOLS_DIR . 'includes/admin/views/page-pro-upsell.php';
					}
				} elseif ( 'history' === $active_tab ) {
					include EMCP_TOOLS_DIR . 'includes/admin/views/page-history.php';
				} elseif ( 'redirects' === $active_tab && $this->module_tab_visible( 'redirects' ) ) {
					include EMCP_TOOLS_DIR . 'includes/admin/views/page-redirects.php';
				} elseif ( 'migrate' === $active_tab && $this->module_tab_visible( 'migrate' ) ) {
					$emcp_migrate_view = EMCP_Tools_Pro_Loader::path( 'includes/admin/views/page-migrate.php' );
					if ( '' !== $emcp_migrate_view ) {
						include $emcp_migrate_view;
					}
				} elseif ( 'widgets' === $active_tab ) {
					include EMCP_TOOLS_DIR . 'includes/admin/views/page-widgets.php';
				} elseif ( 'marketplace' === $active_tab ) {
					include EMCP_TOOLS_DIR . 'includes/admin/views/page-marketplace.php';
				} elseif ( 'mcp-log' === $active_tab ) {
					include EMCP_TOOLS_DIR . 'includes/admin/views/page-mcp-log.php';
				} elseif ( 'changelog' === $active_tab ) {
					include EMCP_TOOLS_DIR . 'includes/admin/views/page-changelog.php';
				} else {
					include EMCP_TOOLS_DIR . 'includes/admin/views/page-tools.php';
				}
				?>
			</div>
		</div>
		<?php
