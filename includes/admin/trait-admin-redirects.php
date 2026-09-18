<?php
/**
 * Redirect Manager form actions and URLs.
 *
 * Internal implementation of EMCP_Tools_Admin; loaded by class-admin.php.
 * Methods retain the admin class scope for existing callbacks and callers.
 *
 * @package EMCP_Tools
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Redirect Manager form actions and URLs.
 */
trait EMCP_Tools_Admin_Redirects_Trait {

	/**
	 * Bounce back to the Redirects tab with a status code.
	 *
	 * @param string $status Status slug for a notice.
	 */
	private function redirect_back_to_redirects( string $status ): void {
		wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '-redirects&notice=' . rawurlencode( $status ) ) );
		exit;
	}

	/**
	 * Create or update a redirect from the management form. Routes through the
	 * store + ledger so admin edits are reversible in History.
	 *
	 * @since 3.11.0
	 */
	public function handle_redirect_save(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do that.', 'emcp-tools' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'emcp_tools_redirect_save' );
		if ( ! class_exists( 'EMCP_Tools_Redirect_Store' ) ) {
			$this->redirect_back_to_redirects( 'error' );
		}
		$id             = isset( $_POST['redirect_id'] ) ? absint( wp_unslash( $_POST['redirect_id'] ) ) : 0;
		$source         = isset( $_POST['source'] ) ? sanitize_text_field( wp_unslash( $_POST['source'] ) ) : '';
		$target_raw     = isset( $_POST['target'] ) ? esc_url_raw( wp_unslash( $_POST['target'] ) ) : '';
		$target_post_id = isset( $_POST['target_post_id'] ) ? absint( wp_unslash( $_POST['target_post_id'] ) ) : 0;
		$status_code    = isset( $_POST['status_code'] ) ? absint( wp_unslash( $_POST['status_code'] ) ) : 301;
		$ignore_query   = ! empty( $_POST['ignore_query'] );

		$data = array(
			'source'       => $source,
			'status_code'  => $status_code,
			'ignore_query' => $ignore_query,
		);
		if ( $target_post_id ) {
			$data['target_post_id'] = $target_post_id;
		} else {
			$data['target'] = $target_raw;
		}

		if ( $id ) {
			$prior = EMCP_Tools_Redirect_Store::get( $id );
			$res   = EMCP_Tools_Redirect_Store::update( $id, $data );
			if ( ! is_wp_error( $res ) && $prior && class_exists( 'EMCP_Tools_Change_Recorder' ) ) {
				EMCP_Tools_Change_Recorder::record_redirect( 'update', array( 'row' => $prior ), sprintf( 'Updated redirect %s', $res['source_path'] ), (string) $res['source_path'] );
			}
		} else {
			$res = EMCP_Tools_Redirect_Store::create( $data );
			if ( ! is_wp_error( $res ) && class_exists( 'EMCP_Tools_Change_Recorder' ) ) {
				EMCP_Tools_Change_Recorder::record_redirect( 'create', array( 'id' => (int) $res['id'] ), sprintf( 'Created redirect %s', $res['source_path'] ), (string) $res['source_path'] );
			}
		}
		$this->redirect_back_to_redirects( is_wp_error( $res ) ? 'error:' . $res->get_error_code() : ( $id ? 'updated' : 'created' ) );
	}

	/**
	 * Delete a redirect (nonce per-id), recorded for rollback.
	 *
	 * @since 3.11.0
	 */
	public function handle_redirect_delete(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do that.', 'emcp-tools' ), '', array( 'response' => 403 ) );
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- nonce verified just below against the per-id action.
		$id = isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0;
		check_admin_referer( 'emcp_tools_redirect_delete_' . $id );
		if ( class_exists( 'EMCP_Tools_Redirect_Store' ) ) {
			$prior = EMCP_Tools_Redirect_Store::get( $id );
			if ( $prior && EMCP_Tools_Redirect_Store::delete( $id ) && class_exists( 'EMCP_Tools_Change_Recorder' ) ) {
				EMCP_Tools_Change_Recorder::record_redirect( 'delete', array( 'row' => $prior ), sprintf( 'Deleted redirect %s', $prior['source_path'] ), (string) $prior['source_path'] );
			}
		}
		$this->redirect_back_to_redirects( 'deleted' );
	}

	/**
	 * Toggle a redirect's enabled state (nonce per-id), recorded for rollback.
	 *
	 * @since 3.11.0
	 */
	public function handle_redirect_toggle(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do that.', 'emcp-tools' ), '', array( 'response' => 403 ) );
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- nonce verified just below against the per-id action.
		$id = isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0;
		check_admin_referer( 'emcp_tools_redirect_toggle_' . $id );
		if ( class_exists( 'EMCP_Tools_Redirect_Store' ) ) {
			$prior = EMCP_Tools_Redirect_Store::get( $id );
			if ( $prior ) {
				$res = EMCP_Tools_Redirect_Store::update( $id, array( 'enabled' => empty( $prior['enabled'] ) ) );
				if ( ! is_wp_error( $res ) && class_exists( 'EMCP_Tools_Change_Recorder' ) ) {
					EMCP_Tools_Change_Recorder::record_redirect( 'update', array( 'row' => $prior ), sprintf( 'Toggled redirect %s', $prior['source_path'] ), (string) $prior['source_path'] );
				}
			}
		}
		$this->redirect_back_to_redirects( 'updated' );
	}

	/**
	 * Nonce'd URL that deletes one redirect.
	 *
	 * @since 3.11.0
	 * @param int $id Redirect id.
	 * @return string
	 */
	public static function redirect_delete_url( int $id ): string {
		return wp_nonce_url(
			admin_url( 'admin-post.php?action=emcp_tools_redirect_delete&id=' . $id ),
			'emcp_tools_redirect_delete_' . $id
		);
	}

	/**
	 * Nonce'd URL that toggles one redirect's enabled state.
	 *
	 * @since 3.11.0
	 * @param int $id Redirect id.
	 * @return string
	 */
	public static function redirect_toggle_url( int $id ): string {
		return wp_nonce_url(
			admin_url( 'admin-post.php?action=emcp_tools_redirect_toggle&id=' . $id ),
			'emcp_tools_redirect_toggle_' . $id
		);
	}
}
