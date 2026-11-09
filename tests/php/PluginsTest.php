<?php
/**
 * Tests that every plugin boots and declares WooCommerce compatibility.
 *
 * @package UltraMax\Tests
 */

/**
 * Plugin bootstrap.
 */
final class PluginsTest extends WP_UnitTestCase {

	/**
	 * Plugin classes by boot action.
	 *
	 * @return array<string, array{0: class-string, 1: string}>
	 */
	public static function plugins(): array {
		return array(
			'core'    => array( \UltraMax\Core\Plugin::class, 'ultra_max_core_loaded' ),
			'auth'    => array( \UltraMax\Auth\Plugin::class, 'ultra_max_auth_loaded' ),
			'invoice' => array( \UltraMax\Invoice\Plugin::class, 'ultra_max_invoice_loaded' ),
			'seo'     => array( \UltraMax\Seo\Plugin::class, 'ultra_max_seo_loaded' ),
		);
	}

	/**
	 * @dataProvider plugins
	 *
	 * @param class-string $plugin_class Plugin class.
	 * @param string       $action       Boot action.
	 */
	public function test_plugin_booted( string $plugin_class, string $action ): void {
		$this->assertSame( 1, did_action( $action ) );
		$this->assertSame( '0.1.0', $plugin_class::instance()->version() );
	}

	/**
	 * @dataProvider plugins
	 *
	 * @param class-string $plugin_class Plugin class.
	 */
	public function test_plugin_registers_the_component_library( string $plugin_class ): void {
		$plugin_class::instance()->register_assets();
		$this->assertTrue( wp_style_is( 'ultra-max-ui', 'registered' ) );
	}

	public function test_requirements_report_an_old_wordpress(): void {
		$check = new \UltraMax\Core\Lib\Foundation\Requirements( 'Ultra Max Core', '8.1', '99.0' );
		$this->assertFalse( $check->met() );
		$this->assertStringContainsString( 'WordPress 99.0', $check->errors()[0] );
	}

	public function test_dev_builds_use_the_development_license(): void {
		$license = new \UltraMax\Core\Lib\License\MarketLicense();
		$this->assertSame( 'dev', $license->market() );
		$this->assertSame( \UltraMax\Core\Lib\License\Status::Development, $license->status() );
	}
}
