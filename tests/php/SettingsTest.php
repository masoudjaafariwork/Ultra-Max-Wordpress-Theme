<?php
/**
 * Tests of the settings store, sanitising, backups and modules.
 *
 * @package UltraMax\Tests
 */

use UltraMax\Core\Lib\Settings\Modules;
use UltraMax\Core\Lib\Settings\Sanitizer;
use UltraMax\Core\Lib\Settings\Schema;
use UltraMax\Core\Lib\Settings\Store;

/**
 * Settings (ADM-01, ADM-02, ADM-07).
 */
final class SettingsTest extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();
		delete_option( Store::OPTION );
		delete_option( Store::BACKUPS );
		delete_option( Modules::OPTION );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
	}

	public function test_schema_merges_sections_of_every_product(): void {
		$ids = array_column( Schema::sections(), 'id' );
		$this->assertContains( 'general', $ids );
		$this->assertContains( 'appearance', $ids );
		$this->assertContains( 'code', $ids );
		$this->assertArrayHasKey( 'color_preset', Schema::fields() );
		$this->assertArrayNotHasKey( 'appearance_preview', Schema::fields() );
	}

	public function test_defaults_are_returned_until_a_value_is_saved(): void {
		$this->assertSame( 'indigo', Store::get( 'color_preset' ) );
		$this->assertSame( 14, Store::get( 'radius' ) );
		$this->assertSame( 'fallback', Store::get( 'unknown', 'fallback' ) );
	}

	public function test_save_sanitises_and_keeps_only_changes(): void {
		$result = Store::save(
			array(
				'radius'       => '30',
				'color_preset' => 'ocean',
				'color_accent' => 'not a colour',
				'digits'       => 'fa',
				'nobody'       => 'x',
			)
		);
		$this->assertSame( array( 'radius', 'color_preset', 'digits' ), $result['saved'] );
		$this->assertSame( array( 'color_accent' ), $result['rejected'] );
		$this->assertSame( 24, Store::get( 'radius' ) );
		$this->assertSame(
			array(
				'radius'       => 24,
				'color_preset' => 'ocean',
			),
			get_option( Store::OPTION )
		);
	}

	public function test_sanitizer_by_type(): void {
		$this->assertTrue( Sanitizer::sanitize( array( 'type' => 'toggle' ), '1' ) );
		$this->assertSame( '1405-08-18', Sanitizer::sanitize( array( 'type' => 'jdate' ), '۱۴۰۵/۸/۱۸' ) );
		$this->assertNull( Sanitizer::sanitize( array( 'type' => 'jdate' ), '1405/13/01' ) );
		$this->assertSame(
			array(
				'desktop' => true,
				'tablet'  => false,
				'mobile'  => true,
			),
			Sanitizer::sanitize( array( 'type' => 'devices' ), array( 'desktop' => 'on', 'mobile' => true ) )
		);
		$this->assertSame( 'body{color:red}', Sanitizer::sanitize( array( 'type' => 'code', 'language' => 'css' ), 'body{color:red}</style><script>' ) );
		$rows = Sanitizer::sanitize(
			array(
				'type'   => 'repeater',
				'fields' => array(
					array(
						'id'      => 'type',
						'type'    => 'select',
						'default' => 'phone',
						'options' => array( array( 'value' => 'phone', 'label' => 'Phone' ) ),
					),
					array(
						'id'   => 'value',
						'type' => 'text',
					),
				),
			),
			array(
				array(
					'type'  => 'bogus',
					'value' => '<b>0912</b>',
				),
			)
		);
		$this->assertSame(
			array(
				array(
					'type'  => 'phone',
					'value' => '0912',
				),
			),
			$rows
		);
	}

	public function test_scripts_need_unfiltered_html(): void {
		$field = array(
			'type'     => 'code',
			'language' => 'js',
		);
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
		if ( ! current_user_can( 'unfiltered_html' ) ) {
			$this->assertNull( Sanitizer::sanitize( $field, 'alert(1)' ) );
		}
		grant_super_admin( get_current_user_id() );
		$this->assertSame( 'alert(1)', Sanitizer::sanitize( $field, 'alert(1)' ) );
	}

	public function test_reset_a_section_takes_a_backup_first(): void {
		Store::save(
			array(
				'radius'      => 4,
				'notice_text' => 'Hello',
			)
		);
		Store::reset( 'appearance' );
		$this->assertSame( 14, Store::get( 'radius' ) );
		$this->assertSame( 'Hello', Store::get( 'notice_text' ) );
		$backups = Store::backups();
		$this->assertCount( 1, $backups );
		$this->assertSame( 4, $backups[0]['settings']['radius'] );
		$this->assertTrue( Store::restore( $backups[0]['id'] ) );
		$this->assertSame( 4, Store::get( 'radius' ) );
	}

	public function test_export_and_import(): void {
		Store::save( array( 'digits' => 'en' ) );
		$document = json_decode( (string) wp_json_encode( Store::export() ), true );
		Store::reset();
		$this->assertSame( 'fa', Store::get( 'digits' ) );
		$this->assertNotNull( Store::import( $document ) );
		$this->assertSame( 'en', Store::get( 'digits' ) );
		$this->assertNull( Store::import( array( 'format' => 'other' ) ) );
	}

	public function test_backups_keep_the_newest_ten(): void {
		for ( $i = 0; $i < 12; $i++ ) {
			Store::backup( 'b' . $i );
		}
		$backups = Store::backups();
		$this->assertCount( 10, $backups );
		$this->assertSame( 'b11', $backups[0]['label'] );
	}

	public function test_modules_are_declared_and_switched(): void {
		$modules = Modules::all();
		$this->assertArrayHasKey( 'notice-bar', $modules );
		$this->assertArrayHasKey( 'custom-code', $modules );
		$this->assertTrue( Modules::enabled( 'notice-bar' ) );
		Modules::set( 'notice-bar', false );
		$this->assertFalse( Modules::enabled( 'notice-bar' ) );
		$this->assertFalse( \UltraMax\Theme\Lib\Settings\Modules::enabled( 'notice-bar' ) );
	}
}
