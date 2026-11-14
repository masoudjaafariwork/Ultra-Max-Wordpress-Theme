<?php
/**
 * Tests of the layout builder (BLD-01, BLD-02, BLD-03, BLD-09, HDR-04).
 *
 * @package UltraMax\Tests
 */

use UltraMax\Core\Builder\Context;
use UltraMax\Core\Builder\Layouts;
use UltraMax\Core\Builder\Library;
use UltraMax\Core\Builder\LibraryStrings;
use UltraMax\Core\Builder\Resolver;
use UltraMax\Core\Builder\Rules;
use UltraMax\Core\Builder\Types;
use UltraMax\Core\Elementor\Data;
use UltraMax\Theme\Template;

/**
 * Display conditions, the per-device choice, the index and the library.
 */
final class BuilderTest extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		foreach ( get_posts(
			array(
				'post_type'      => Layouts::POST_TYPE,
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		) as $id ) {
			wp_delete_post( (int) $id, true );
		}
		Layouts::rebuild();
		Resolver::reset();
	}

	/**
	 * A layout entry for Resolver::pick().
	 *
	 * @param int                  $id         Id.
	 * @param string               $type       Type.
	 * @param array<string, mixed> $conditions Conditions, merged into the defaults.
	 * @return array<string, mixed>
	 */
	private function entry( int $id, string $type, array $conditions = array() ): array {
		return array(
			'id'         => $id,
			'type'       => $type,
			'title'      => 'L' . $id,
			'status'     => 'publish',
			'conditions' => Layouts::sanitize( array_merge( Layouts::defaults( $type ), $conditions ), $type ),
			'modified'   => $id,
		);
	}

	public function test_only_ready_types_can_be_made(): void {
		$this->assertTrue( Types::is_ready( 'header' ) );
		$this->assertTrue( Types::is_ready( 'footer' ) );
		$this->assertTrue( Types::is_ready( 'block' ) );
		$this->assertTrue( Types::is_ready( 'single-product' ), 'Product layouts arrived with their widgets in phase 4, part B.' );
		$this->assertFalse( Types::is_ready( 'cart' ), 'The cart layout arrives with its widgets in phase 4, part C.' );
		$this->assertInstanceOf( WP_Error::class, Layouts::create( 'cart', 'X' ) );
	}

	public function test_rules_match_the_right_pages(): void {
		$front   = new Context( 'front', 'page', 5 );
		$post    = new Context( 'singular', 'post', 9, '', array( 'category' => array( 3, 1 ) ) );
		$product = new Context( 'singular', 'product', 12, '', array( 'product_cat' => array( 7, 2 ) ), 'variable' );
		$archive = new Context( 'archive', 'product', 7, 'product_cat', array( 'product_cat' => array( 7, 2 ) ) );

		$this->assertTrue( Rules::matches( array( 'target' => 'all' ), $post ) );
		$this->assertTrue( Rules::matches( array( 'target' => 'front' ), $front ) );
		$this->assertTrue(
			Rules::matches(
				array(
					'target' => 'page-id',
					'values' => array( 5 ),
				),
				$front
			)
		);
		$this->assertFalse( Rules::matches( array( 'target' => 'front' ), $post ) );
		$this->assertTrue( Rules::matches( array( 'target' => 'posts' ), $post ) );
		$this->assertTrue(
			Rules::matches(
				array(
					'target' => 'category',
					'values' => array( 1 ),
				),
				$post
			),
			'A parent category covers its children.'
		);
		$this->assertTrue(
			Rules::matches(
				array(
					'target' => 'product_cat',
					'values' => array( 2 ),
				),
				$product
			)
		);
		$this->assertTrue(
			Rules::matches(
				array(
					'target' => 'product_cat',
					'values' => array( 7 ),
				),
				$archive
			)
		);
		$this->assertTrue(
			Rules::matches(
				array(
					'target' => 'product-type',
					'values' => array( 'variable' ),
				),
				$product
			)
		);
		$this->assertFalse(
			Rules::matches(
				array(
					'target' => 'product-type',
					'values' => array( 'simple' ),
				),
				$product
			)
		);
		$this->assertTrue( Rules::matches( array( 'target' => 'shop' ), $archive ) );
		$this->assertFalse( Rules::matches( array( 'target' => 'shop' ), $product ) );
	}

	public function test_exclusions_and_roles_remove_a_layout(): void {
		$conditions = Layouts::sanitize(
			array(
				'include' => array( array( 'target' => 'all' ) ),
				'exclude' => array( array( 'target' => 'front' ) ),
				'roles'   => array( 'customer' ),
			),
			'header'
		);
		$this->assertNull( Rules::score( $conditions, new Context( 'front', 'page', 5, roles: array( 'customer' ) ) ) );
		$this->assertNull( Rules::score( $conditions, new Context( 'search', roles: array( 'guest' ) ) ) );
		$this->assertSame( 0, Rules::score( $conditions, new Context( 'search', roles: array( 'customer' ) ) ) );
	}

	public function test_priority_then_specificity_picks_the_winner(): void {
		$context = new Context( 'singular', 'post', 9, '', array( 'category' => array( 3 ) ) );
		$entries = array(
			$this->entry( 1, 'header' ),
			$this->entry(
				2,
				'header',
				array(
					'include' => array(
						array(
							'target' => 'category',
							'values' => array( 3 ),
						),
					),
				)
			),
		);
		$this->assertSame( 2, Resolver::pick( $entries, 'header', $context )[0]['id'], 'The category rule is more specific than "entire site".' );

		$entries[0]['conditions']['priority'] = 50;
		$this->assertSame( 1, Resolver::pick( $entries, 'header', $context )[0]['id'], 'A higher priority wins over a more specific rule.' );
	}

	public function test_each_device_gets_its_own_winner(): void {
		$entries = array(
			$this->entry(
				1,
				'header',
				array(
					'devices' => array(
						'desktop' => true,
						'tablet'  => false,
						'mobile'  => false,
					),
				)
			),
			$this->entry(
				2,
				'header',
				array(
					'devices' => array(
						'desktop' => false,
						'tablet'  => true,
						'mobile'  => true,
					),
				)
			),
		);
		$picks   = Resolver::pick( $entries, 'header', new Context( 'front' ) );
		$this->assertCount( 2, $picks );
		$this->assertSame(
			array(
				'desktop' => true,
				'tablet'  => false,
				'mobile'  => false,
			),
			$picks[0]['devices']
		);
		$this->assertSame(
			array(
				'desktop' => false,
				'tablet'  => true,
				'mobile'  => true,
			),
			$picks[1]['devices']
		);

		$only_mobile = Resolver::pick( array( $entries[1] ), 'header', new Context( 'front' ) );
		$this->assertSame(
			array(
				'desktop' => false,
				'tablet'  => true,
				'mobile'  => true,
			),
			$only_mobile[0]['devices'],
			'Uncovered devices stay with the theme.'
		);
	}

	public function test_footer_layouts_do_not_answer_for_the_header(): void {
		$this->assertSame( array(), Resolver::pick( array( $this->entry( 1, 'footer' ) ), 'header', new Context( 'front' ) ) );
	}

	public function test_sanitize_drops_unknown_rules_and_empty_value_rules(): void {
		$clean = Layouts::sanitize(
			array(
				'include'  => array(
					array( 'target' => 'nonsense' ),
					array(
						'target' => 'post-id',
						'values' => array(),
					),
					array(
						'target' => 'post-id',
						'values' => array( '4', 'x', 4 ),
					),
				),
				'priority' => 900,
				'sticky'   => 'wobbly',
			),
			'header'
		);
		$this->assertSame(
			array(
				array(
					'target' => 'post-id',
					'values' => array( 4 ),
				),
			),
			$clean['include']
		);
		$this->assertSame( 100, $clean['priority'] );
		$this->assertSame( 'none', $clean['sticky'] );
	}

	public function test_index_holds_only_published_layouts_and_costs_no_query(): void {
		$on  = Layouts::create( 'header', 'On' );
		$off = Layouts::create( 'footer', 'Off' );
		$this->assertIsInt( $on );
		$this->assertIsInt( $off );
		wp_update_post(
			array(
				'ID'          => $off,
				'post_status' => 'draft',
			)
		);

		$ids = array_column( Layouts::index(), 'id' );
		$this->assertSame( array( $on ), $ids );

		global $wpdb;
		$queries = $wpdb->num_queries;
		Resolver::reset();
		Resolver::location( 'header' );
		$this->assertSame( $queries, $wpdb->num_queries, 'Choosing the header reads the autoloaded index only.' );
	}

	public function test_location_prints_the_layout_and_reports_covered_devices(): void {
		$id = Layouts::create(
			'header',
			'Mobile header',
			null,
			array_merge(
				Layouts::defaults( 'header' ),
				array(
					'devices' => array(
						'desktop' => false,
						'tablet'  => true,
						'mobile'  => true,
					),
				)
			)
		);
		$this->assertIsInt( $id );
		wp_update_post(
			array(
				'ID'           => $id,
				'post_content' => '<p>Header content</p>',
			)
		);
		$this->go_to( home_url( '/' ) );
		Resolver::reset();

		ob_start();
		$left = Template::location( 'header' );
		$html = (string) ob_get_clean();

		$this->assertStringContainsString( 'Header content', $html );
		$this->assertStringContainsString( 'um-layout-header', $html );
		$this->assertStringContainsString( 'lg:hidden', $html, 'Hidden on desktop, which keeps the theme header.' );
		$this->assertSame(
			array(
				'desktop' => true,
				'tablet'  => false,
				'mobile'  => false,
			),
			$left
		);
	}

	public function test_elementor_pro_location_wins_over_the_builder(): void {
		// Elementor Pro's Theme Builder prints its own header first (BLD-09).
		if ( ! function_exists( 'elementor_theme_do_location' ) ) {
			// phpcs:ignore Universal.Files.SeparateFunctionsFromOO.Mixed -- Test double of Elementor Pro's function.
			eval( 'function elementor_theme_do_location( $location ) { if ( "header" === $location && ! empty( $GLOBALS["um_fake_pro"] ) ) { echo "PRO HEADER"; return true; } return false; }' ); // phpcs:ignore Squiz.PHP.Eval.Discouraged
		}
		Layouts::create( 'header', 'Ours' );
		$this->go_to( home_url( '/' ) );
		Resolver::reset();
		$GLOBALS['um_fake_pro'] = true;
		ob_start();
		$left = Template::location( 'header' );
		$html = (string) ob_get_clean();
		unset( $GLOBALS['um_fake_pro'] );

		$this->assertSame( 'PRO HEADER', $html );
		$this->assertSame(
			array(
				'desktop' => false,
				'tablet'  => false,
				'mobile'  => false,
			),
			$left
		);
	}

	public function test_layouts_are_hidden_from_visitors(): void {
		$id = Layouts::create( 'block', 'Secret block' );
		$this->assertIsInt( $id );
		wp_set_current_user( 0 );
		$this->go_to( get_permalink( $id ) );
		do_action( 'template_redirect' );
		$this->assertTrue( is_404() );
	}

	public function test_block_shortcode_prints_blocks_only_and_stops_loops(): void {
		$block = Layouts::create( 'block', 'Promo' );
		$this->assertIsInt( $block );
		wp_update_post(
			array(
				'ID'           => $block,
				'post_content' => 'Promo [ultra_max_layout id="' . $block . '"]',
			)
		);
		$header = Layouts::create( 'header', 'Header' );

		$this->assertSame( 1, substr_count( do_shortcode( '[ultra_max_layout id="' . $block . '"]' ), 'Promo' ), 'A block that includes itself is printed once.' );
		$this->assertSame( '', do_shortcode( '[ultra_max_layout id="' . $header . '"]' ), 'Only reusable blocks print through the shortcode.' );
	}

	public function test_library_has_the_promised_templates(): void {
		$this->assertCount( 16, Library::all( 'header' ), '8 desktop and 8 phone headers (HDR-03).' );
		$this->assertGreaterThanOrEqual( 3, count( Library::all( 'footer' ) ), 'At least three footers (BLD-03).' );
		$this->assertGreaterThanOrEqual( 15, count( Library::all( 'section' ) ) );
		$this->assertGreaterThanOrEqual( 3, count( Library::all( 'page' ) ) );
		$this->assertNull( Library::get( '../../wp-config' ) );
	}

	public function test_library_widgets_exist_and_images_point_at_the_plugin(): void {
		$known = array_map( static fn ( string $id ): string => 'um-' . $id, array_keys( UltraMax\Core\Widgets\Widgets::all() ) );
		foreach ( array( 'header', 'footer', 'section', 'page' ) as $kind ) {
			foreach ( Library::all( $kind ) as $item ) {
				$template = Library::get( $item['id'] );
				$this->assertNotNull( $template );
				$json = (string) wp_json_encode( LibraryStrings::translate( $template['content'] ) );
				preg_match_all( '/"widgetType":"([a-z0-9-]+)"/', $json, $types );
				foreach ( $types[1] as $type ) {
					$this->assertContains( $type, $known, "{$item['id']} uses an unknown widget $type." );
				}
				$this->assertStringNotContainsString( '{library}', $json );
			}
		}
	}

	public function test_import_gives_new_element_ids(): void {
		$id = self::factory()->post->create();
		Data::import(
			$id,
			array(
				array(
					'id'       => 'aaaaaaa',
					'elType'   => 'container',
					'elements' => array(
						array(
							'id'     => 'aaaaaaa',
							'elType' => 'widget',
						),
					),
				),
			)
		);
		$saved = json_decode( (string) get_post_meta( $id, '_elementor_data', true ), true );
		$this->assertNotSame( 'aaaaaaa', $saved[0]['id'] );
		$this->assertNotSame( $saved[0]['id'], $saved[0]['elements'][0]['id'] );
		$this->assertSame( 'builder', get_post_meta( $id, '_elementor_edit_mode', true ) );
	}

	public function test_rest_needs_design_rights_and_creates_layouts(): void {
		$request = new WP_REST_Request( 'POST', '/ultra-max/v1/layouts' );
		$request->set_body_params(
			array(
				'type'  => 'footer',
				'title' => 'From REST',
			)
		);
		$response = rest_do_request( $request );
		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'From REST', $response->get_data()['layout']['title'] );

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
		$this->assertSame( 403, rest_do_request( new WP_REST_Request( 'GET', '/ultra-max/v1/layouts' ) )->get_status() );
	}
}
