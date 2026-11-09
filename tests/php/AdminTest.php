<?php
/**
 * Tests of the settings panel's server side.
 *
 * @package UltraMax\Tests
 */

use UltraMax\Core\Admin\Admin;
use UltraMax\Core\Admin\Products;
use UltraMax\Core\Admin\SystemStatus;
use UltraMax\Core\CustomCode;
use UltraMax\Core\Duplicate;
use UltraMax\Core\Lib\Settings\Modules;
use UltraMax\Core\Lib\Settings\Store;

/**
 * REST routes, duplication, custom code and status (ADM-01 to ADM-10).
 */
final class AdminTest extends WP_UnitTestCase {

	private int $admin;

	public function set_up(): void {
		parent::set_up();
		delete_option( Store::OPTION );
		delete_option( Store::BACKUPS );
		delete_option( Modules::OPTION );
		$this->admin = self::factory()->user->create( array( 'role' => 'administrator' ) );
		do_action( 'rest_api_init' );
	}

	/**
	 * Sends a REST request.
	 *
	 * @param string               $method Method.
	 * @param string               $path   Path below the namespace.
	 * @param array<string, mixed> $body   JSON body.
	 */
	private function rest( string $method, string $path, array $body = array() ): WP_REST_Response {
		$request = new WP_REST_Request( $method, '/ultra-max/v1/' . $path );
		if ( array() !== $body ) {
			$request->set_header( 'Content-Type', 'application/json' );
			$request->set_body( (string) wp_json_encode( $body ) );
		}
		return rest_get_server()->dispatch( $request );
	}

	public function test_routes_refuse_visitors(): void {
		wp_set_current_user( 0 );
		$this->assertSame( 401, $this->rest( 'GET', 'settings' )->get_status() );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
		$this->assertSame( 403, $this->rest( 'POST', 'modules', array( 'id' => 'duplicate' ) )->get_status() );
	}

	public function test_save_reset_and_backups_over_rest(): void {
		wp_set_current_user( $this->admin );
		$response = $this->rest(
			'POST',
			'settings',
			array(
				'values' => array(
					'radius' => 6,
					'digits' => 'xx',
				),
			)
		);
		$data     = $response->get_data();
		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 6, $data['values']['radius'] );
		$this->assertSame( array( 'digits' ), $data['rejected'] );

		$this->rest( 'POST', 'settings/reset', array( 'section' => 'appearance' ) );
		$this->assertSame( 14, Store::get( 'radius' ) );
		$backups = $this->rest( 'GET', 'settings/backups' )->get_data()['backups'];
		$this->assertCount( 1, $backups );

		$restored = $this->rest( 'POST', 'settings/backups/restore', array( 'id' => $backups[0]['id'] ) );
		$this->assertSame( 6, $restored->get_data()['values']['radius'] );
		$this->assertSame( 404, $this->rest( 'POST', 'settings/backups/restore', array( 'id' => 'nope' ) )->get_status() );
	}

	public function test_import_rejects_other_files(): void {
		wp_set_current_user( $this->admin );
		$this->assertSame( 400, $this->rest( 'POST', 'settings/import', array( 'document' => array( 'a' => 1 ) ) )->get_status() );
	}

	public function test_modules_switch_over_rest(): void {
		wp_set_current_user( $this->admin );
		$data = $this->rest(
			'POST',
			'modules',
			array(
				'id' => 'back-to-top',
				'on' => false,
			)
		)->get_data();
		$this->assertFalse( Modules::enabled( 'back-to-top' ) );
		$module = array_values( array_filter( $data['modules'], static fn ( $m ) => 'back-to-top' === $m['id'] ) )[0];
		$this->assertFalse( $module['on'] );
		$this->assertGreaterThan( 0, $module['bytes'] );
		$this->assertSame( 404, $this->rest( 'POST', 'modules', array( 'id' => 'nope' ) )->get_status() );
	}

	public function test_boot_data_of_the_settings_page(): void {
		wp_set_current_user( $this->admin );
		$data = Admin::data( 'settings' );
		$this->assertArrayHasKey( 'sections', $data['settings'] );
		$this->assertArrayHasKey( 'save', $data['strings'] );
		$this->assertTrue( $data['tour'] );
		$this->rest( 'POST', 'tour', array( 'page' => 'settings' ) );
		$this->assertFalse( Admin::data( 'settings' )['tour'] );
	}

	public function test_duplicate_copies_content_terms_and_meta(): void {
		wp_set_current_user( $this->admin );
		$category = self::factory()->category->create();
		$post     = self::factory()->post->create(
			array(
				'post_title'    => 'Original',
				'post_content'  => 'Body',
				'post_category' => array( $category ),
			)
		);
		add_post_meta( $post, '_elementor_data', '[{"id":"a"}]' );
		add_post_meta( $post, '_edit_lock', '1' );
		$copy = Duplicate::copy( get_post( $post ) );
		$this->assertIsInt( $copy );
		$this->assertSame( 'draft', get_post_status( $copy ) );
		$this->assertSame( 'Original (copy)', get_the_title( $copy ) );
		$this->assertSame( 'Body', get_post_field( 'post_content', $copy ) );
		$this->assertSame( array( $category ), wp_get_post_categories( $copy ) );
		$this->assertSame( '[{"id":"a"}]', get_post_meta( $copy, '_elementor_data', true ) );
		$this->assertSame( '', get_post_meta( $copy, '_edit_lock', true ) );
	}

	public function test_duplicate_needs_the_right_to_create(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );
		$this->assertNull( Duplicate::copy( get_post( self::factory()->post->create() ) ) );
	}

	public function test_custom_code_prints_site_and_page_code(): void {
		Store::save( array( 'code_css' => 'body{color:red}</style><b>' ) );
		$post = self::factory()->post->create();
		update_post_meta( $post, CustomCode::META_CSS, '.page{margin:0}' );
		$this->go_to( get_permalink( $post ) );
		ob_start();
		CustomCode::head();
		$head = (string) ob_get_clean();
		$this->assertStringContainsString( 'body{color:red}', $head );
		$this->assertStringContainsString( '.page{margin:0}', $head );
		$this->assertStringNotContainsString( '<b>', $head );
		$this->assertSame( 1, substr_count( $head, '</style>' ) );
	}

	public function test_system_status_reports_every_check(): void {
		$checks = SystemStatus::checks();
		$ids    = array_column( $checks, 'id' );
		foreach ( array( 'php', 'core', 'memory', 'time', 'extensions', 'overlaps', 'templates' ) as $id ) {
			$this->assertContains( $id, $ids );
		}
		$summary = SystemStatus::summary( $checks );
		$this->assertSame( count( $checks ), array_sum( $summary ) );
		$this->assertStringContainsString( 'Ultra Max system status', SystemStatus::report( $checks ) );
	}

	public function test_major_versions_and_changelog(): void {
		$this->assertSame( '0.2', Products::major( '0.2.5' ) );
		$this->assertSame( '1', Products::major( '1.4.0' ) );
		$products = array(
			array(
				'slug'      => 'a',
				'name'      => 'A',
				'type'      => 'theme',
				'file'      => 'a',
				'version'   => '1.0.0',
				'active'    => true,
				'installed' => true,
				'update'    => '2.0.0',
			),
			array(
				'slug'      => 'b',
				'name'      => 'B',
				'type'      => 'plugin',
				'file'      => 'b',
				'version'   => '1.0.0',
				'active'    => true,
				'installed' => true,
				'update'    => '',
			),
			array(
				'slug'      => 'c',
				'name'      => 'C',
				'type'      => 'plugin',
				'file'      => 'c',
				'version'   => '1.2.0',
				'active'    => true,
				'installed' => true,
				'update'    => '',
			),
		);
		$this->assertSame( array( 'A' ), Products::mismatched( $products ) );
		$log = Products::changelog();
		$this->assertNotEmpty( $log );
		$this->assertNotEmpty( $log[0]['items'] );
	}
}
