<?php
/**
 * EMCP_Tools_Filesystem_Guard private upload directories: backups of edited
 * files and WP-CLI job logs live under wp-content/uploads, which every web
 * server serves. An Apache-only .htaccess is not a control on Nginx or
 * LiteSpeed, so the directory name must also be unguessable and the deny
 * rules must cover every common server.
 *
 * @package EMCP_Tools
 */

use PHPUnit\Framework\TestCase;

require_once EMCP_TOOLS_DIR . 'includes/class-filesystem-guard.php';

final class FilesystemGuardPrivateDirTest extends TestCase {

	protected function setUp(): void {
		emcp_test_reset();
	}

	public function test_token_is_generated_once_and_persisted(): void {
		$a = EMCP_Tools_Filesystem_Guard::private_dir_token();
		$b = EMCP_Tools_Filesystem_Guard::private_dir_token();
		$this->assertMatchesRegularExpression( '/^[a-f0-9]{32}$/', $a );
		$this->assertSame( $a, $b );
		$this->assertSame( $a, $GLOBALS['emcp_test']['options'][ EMCP_Tools_Filesystem_Guard::TOKEN_OPTION ] );
	}

	public function test_tampered_or_short_stored_token_is_replaced(): void {
		$GLOBALS['emcp_test']['options'][ EMCP_Tools_Filesystem_Guard::TOKEN_OPTION ] = '../../etc';
		$t = EMCP_Tools_Filesystem_Guard::private_dir_token();
		$this->assertMatchesRegularExpression( '/^[a-f0-9]{32}$/', $t );
	}

	public function test_private_dir_name_embeds_token_and_rejects_bad_chars(): void {
		$this->assertSame( 'emcp-fs-backups-0123456789abcdef0123456789abcdef', EMCP_Tools_Filesystem_Guard::private_dir_name( 'emcp-fs-backups', '0123456789abcdef0123456789abcdef' ) );
		$this->assertSame( 'emcp-wpcli-jobs-abc', EMCP_Tools_Filesystem_Guard::private_dir_name( 'emcp-wpcli-jobs', '../abc' ) );
	}

	public function test_deny_rules_cover_apache_22_24_and_iis(): void {
		$ht = EMCP_Tools_Filesystem_Guard::htaccess_deny_rules();
		$this->assertStringContainsString( 'Require all denied', $ht );
		$this->assertStringContainsString( 'Deny from all', $ht );
		$this->assertStringContainsString( 'IfModule mod_authz_core.c', $ht );
		$this->assertStringContainsString( 'Options -Indexes', $ht );

		$wc = EMCP_Tools_Filesystem_Guard::web_config_deny_rules();
		$this->assertStringContainsString( '<requestFiltering>', $wc );
		$this->assertStringContainsString( 'denyUrlSequences', $wc );
	}

	public function test_protect_dir_writes_all_deny_files(): void {
		$dir = sys_get_temp_dir() . '/emcp-private-' . bin2hex( random_bytes( 4 ) );
		mkdir( $dir );
		try {
			EMCP_Tools_Filesystem_Guard::protect_dir( $dir );
			$this->assertFileExists( $dir . '/.htaccess' );
			$this->assertFileExists( $dir . '/web.config' );
			$this->assertFileExists( $dir . '/index.php' );
			$this->assertFileExists( $dir . '/index.html' );
		} finally {
			foreach ( array( '.htaccess', 'web.config', 'index.php', 'index.html' ) as $f ) {
				@unlink( $dir . '/' . $f );
			}
			rmdir( $dir );
		}
	}
}
