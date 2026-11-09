<?php
/**
 * Tests of the shared component library, through ultra-max-core's copy.
 *
 * @package UltraMax\Tests
 */

use UltraMax\Core\Lib\UI\Attributes;
use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Lib\UI\UI;

/**
 * Component output, escaping and prop checks.
 */
final class UiComponentsTest extends WP_UnitTestCase {

	public function test_plain_children_are_escaped_and_html_children_are_kept(): void {
		$html = (string) UI::button( array( 'children' => array( '<b>x</b>', Html::trusted( '<i>y</i>' ) ) ) );
		$this->assertStringContainsString( '&lt;b&gt;x&lt;/b&gt;', $html );
		$this->assertStringContainsString( '<i>y</i>', $html );
	}

	public function test_button_renders_a_link_when_href_is_given(): void {
		$html = (string) UI::button( array( 'href' => 'https://example.org/?a=1&b=2', 'children' => 'Go' ) );
		$this->assertStringStartsWith( '<a ', $html );
		$this->assertStringContainsString( 'href="https://example.org/?a=1&#038;b=2"', $html );
		$this->assertStringNotContainsString( 'type=', $html );
	}

	public function test_button_defaults_to_a_primary_type_button(): void {
		$html = (string) UI::button( array( 'children' => 'Buy' ) );
		$this->assertStringContainsString( 'type="button"', $html );
		$this->assertStringContainsString( 'bg-primary', $html );
	}

	public function test_button_opens_a_dialog(): void {
		$html = (string) UI::button( array( 'children' => 'Open', 'opens' => 'cart' ) );
		$this->assertStringContainsString( 'data-um-open="cart"', $html );
		$this->assertStringContainsString( 'aria-haspopup="dialog"', $html );
	}

	public function test_invalid_variant_is_reported_and_falls_back(): void {
		$this->setExpectedIncorrectUsage( 'UI::button' );
		// @phpstan-ignore argument.type (deliberately invalid prop)
		$html = (string) UI::button( array( 'variant' => 'pink', 'children' => 'x' ) );
		$this->assertStringContainsString( 'bg-primary', $html );
	}

	public function test_dialog_is_labelled_by_its_title_and_has_a_named_close_button(): void {
		$html = (string) UI::dialog( array( 'id' => 'd1', 'title' => 'Title', 'children' => 'Body' ) );
		$this->assertStringStartsWith( '<dialog ', $html );
		$this->assertStringContainsString( 'aria-labelledby="d1-title"', $html );
		$this->assertStringContainsString( 'id="d1-title"', $html );
		$this->assertStringContainsString( 'aria-label="Close"', $html );
		$this->assertStringContainsString( 'data-um-close', $html );
	}

	public function test_dialog_requires_a_valid_id(): void {
		$this->setExpectedIncorrectUsage( 'UI::dialog' );
		UI::dialog( array( 'id' => '1 bad', 'title' => 'T', 'children' => 'B' ) );
	}

	public function test_tabs_link_tabs_and_panels(): void {
		$html = (string) UI::tabs(
			array(
				'id'       => 't',
				'label'    => 'Product',
				'selected' => 1,
				'items'    => array(
					array( 'label' => 'A', 'content' => 'a' ),
					array( 'label' => 'B', 'content' => 'b' ),
				),
			)
		);
		$this->assertStringContainsString( 'role="tablist" aria-label="Product"', $html );
		$this->assertStringContainsString( 'id="t-tab-1" class="', $html );
		$this->assertMatchesRegularExpression( '/id="t-tab-1"[^>]*aria-selected="true"[^>]*tabindex="0"/', $html );
		$this->assertMatchesRegularExpression( '/id="t-tab-0"[^>]*aria-selected="false"[^>]*tabindex="-1"/', $html );
		$this->assertMatchesRegularExpression( '/id="t-panel-0"[^>]*class="[^"]*hidden/', $html );
	}

	public function test_accordion_marks_open_items(): void {
		$html = (string) UI::accordion(
			array(
				'id'    => 'faq',
				'items' => array(
					array( 'title' => 'Q1', 'content' => 'A1', 'open' => true ),
					array( 'title' => 'Q2', 'content' => 'A2' ),
				),
			)
		);
		$this->assertMatchesRegularExpression( '/id="faq-button-0"[^>]*aria-expanded="true"/', $html );
		$this->assertMatchesRegularExpression( '/id="faq-button-1"[^>]*aria-expanded="false"/', $html );
	}

	public function test_dropdown_trigger_controls_the_menu(): void {
		$html = (string) UI::dropdown(
			array(
				'id'    => 'acc',
				'label' => 'Account',
				'items' => array( array( 'label' => 'Orders', 'href' => '/orders', 'current' => true ) ),
			)
		);
		$this->assertStringContainsString( 'aria-controls="acc-menu"', $html );
		$this->assertStringContainsString( 'aria-expanded="false"', $html );
		$this->assertStringContainsString( 'aria-current="page"', $html );
	}

	public function test_attributes_skip_false_and_escape_values(): void {
		$this->assertSame( ' a="&quot;x&quot;" b', Attributes::render( array( 'a' => '"x"', 'b' => true, 'c' => false, 'd' => null, 'bad name' => 'y' ) ) );
		$this->assertSame( 'a b', Attributes::classes( 'a  b', null, 'a' ) );
	}

	public function test_each_product_has_its_own_copy_of_the_library(): void {
		$this->assertTrue( class_exists( \UltraMax\Core\Lib\UI\UI::class ) );
		$this->assertTrue( class_exists( \UltraMax\Seo\Lib\UI\UI::class ) );
		$this->assertNotSame( \UltraMax\Core\Lib\UI\UI::class, \UltraMax\Seo\Lib\UI\UI::class );
	}
}