<?php
/**
 * EMCP_Tools_Database_Guard write protection: the raw insert-row /
 * update-rows / delete-rows tools must refuse the tables through which a
 * single row change becomes site takeover (options: active_plugins,
 * default_role, users_can_register, siteurl; multisite sitemeta/signups),
 * while the read-only `query` tool keeps its narrower users/usermeta list.
 *
 * @package EMCP_Tools
 */

use PHPUnit\Framework\TestCase;

require_once EMCP_TOOLS_DIR . 'includes/sql/class-sql-policy.php';
require_once EMCP_TOOLS_DIR . 'includes/class-database-guard.php';

final class DatabaseGuardWriteProtectionTest extends TestCase {

	protected function setUp(): void {
		emcp_test_reset();
		$GLOBALS['wpdb'] = (object) array(
			'prefix'   => 'wp_',
			'users'    => 'wp_users',
			'usermeta' => 'wp_usermeta',
			'options'  => 'wp_options',
			'sitemeta' => 'wp_sitemeta',
			'signups'  => 'wp_signups',
			'blogs'    => 'wp_blogs',
			'site'     => 'wp_site',
		);
	}

	protected function tearDown(): void {
		unset( $GLOBALS['wpdb'] );
	}

	public function test_user_tables_stay_protected_for_reads_and_writes(): void {
		$this->assertTrue( EMCP_Tools_Database_Guard::is_protected( 'wp_users' ) );
		$this->assertTrue( EMCP_Tools_Database_Guard::is_write_protected( 'wp_users' ) );
		$this->assertTrue( EMCP_Tools_Database_Guard::is_write_protected( 'wp_usermeta' ) );
	}

	public function test_options_and_network_tables_are_write_protected_only(): void {
		foreach ( array( 'wp_options', 'WP_OPTIONS', 'wp_sitemeta', 'wp_signups', 'wp_blogs', 'wp_site' ) as $t ) {
			$this->assertTrue( EMCP_Tools_Database_Guard::is_write_protected( $t ), "$t should refuse raw writes" );
			$this->assertFalse( EMCP_Tools_Database_Guard::is_protected( $t ), "$t should still be readable" );
		}
	}

	public function test_content_tables_remain_writable(): void {
		foreach ( array( 'wp_posts', 'wp_postmeta', 'wp_terms', 'wp_options_backup' ) as $t ) {
			$this->assertFalse( EMCP_Tools_Database_Guard::is_write_protected( $t ), "$t should stay writable" );
		}
	}

	public function test_single_site_without_network_tables_does_not_error(): void {
		$GLOBALS['wpdb'] = (object) array( 'users' => 'wp_users', 'usermeta' => 'wp_usermeta', 'options' => 'wp_options' );
		$list            = EMCP_Tools_Database_Guard::write_protected_tables();
		$this->assertSame( array( 'wp_users', 'wp_usermeta', 'wp_options' ), array_values( $list ) );
	}
}
