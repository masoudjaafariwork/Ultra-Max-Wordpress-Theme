<?php
/**
 * The kinds of layout the builder makes (BLD-01).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Builder;

/**
 * Each type names the place of the page it replaces (its location) and
 * whether it can be used yet. Header, footer and reusable blocks work now;
 * the store and blog types become usable in phase 4 together with their
 * widgets (BLD-04 to BLD-07), so the panel does not offer them before.
 *
 * @phpstan-type LayoutType array{label: string, plural: string, description: string, icon: string, location: string, ready: bool, sticky: bool}
 */
final class Types {

	/**
	 * Every type, keyed by id.
	 *
	 * @return array<string, LayoutType>
	 */
	public static function all(): array {
		$types = array(
			'header'           => array(
				'label'       => __( 'Header', 'ultra-max-core' ),
				'plural'      => __( 'Headers', 'ultra-max-core' ),
				'description' => __( 'The top of every page: logo, menus, search and cart. Make one for desktop and one for mobile, or one for both.', 'ultra-max-core' ),
				'icon'        => 'layers',
				'location'    => 'header',
				'ready'       => true,
				'sticky'      => true,
			),
			'footer'           => array(
				'label'       => __( 'Footer', 'ultra-max-core' ),
				'plural'      => __( 'Footers', 'ultra-max-core' ),
				'description' => __( 'The bottom of every page: links, trust badges, payment marks and app links.', 'ultra-max-core' ),
				'icon'        => 'layers',
				'location'    => 'footer',
				'ready'       => true,
				'sticky'      => false,
			),
			'block'            => array(
				'label'       => __( 'Reusable block', 'ultra-max-core' ),
				'plural'      => __( 'Reusable blocks', 'ultra-max-core' ),
				'description' => __( 'A piece of design you can show in a mega menu, a slider or anywhere with a shortcode.', 'ultra-max-core' ),
				'icon'        => 'puzzle',
				'location'    => '',
				'ready'       => true,
				'sticky'      => false,
			),
			'single-product'   => self::later( __( 'Single product', 'ultra-max-core' ), __( 'Single products', 'ultra-max-core' ), 'single' ),
			'shop-archive'     => self::later( __( 'Shop archive', 'ultra-max-core' ), __( 'Shop archives', 'ultra-max-core' ), 'archive' ),
			'product-card'     => self::later( __( 'Product card', 'ultra-max-core' ), __( 'Product cards', 'ultra-max-core' ), '' ),
			'cart'             => self::later( __( 'Cart', 'ultra-max-core' ), __( 'Carts', 'ultra-max-core' ), '' ),
			'checkout-form'    => self::later( __( 'Checkout form', 'ultra-max-core' ), __( 'Checkout forms', 'ultra-max-core' ), '' ),
			'checkout-content' => self::later( __( 'Checkout content', 'ultra-max-core' ), __( 'Checkout contents', 'ultra-max-core' ), '' ),
			'thank-you'        => self::later( __( 'Thank you page', 'ultra-max-core' ), __( 'Thank you pages', 'ultra-max-core' ), '' ),
			'login'            => self::later( __( 'Login and register', 'ultra-max-core' ), __( 'Login and register pages', 'ultra-max-core' ), '' ),
			'lost-password'    => self::later( __( 'Lost password', 'ultra-max-core' ), __( 'Lost password pages', 'ultra-max-core' ), '' ),
			'account'          => self::later( __( 'My account', 'ultra-max-core' ), __( 'Account pages', 'ultra-max-core' ), '' ),
			'single-post'      => self::later( __( 'Single post', 'ultra-max-core' ), __( 'Single posts', 'ultra-max-core' ), 'single' ),
			'blog-archive'     => self::later( __( 'Blog archive', 'ultra-max-core' ), __( 'Blog archives', 'ultra-max-core' ), 'archive' ),
		);
		/**
		 * Filters the layout types. A later phase marks its types ready.
		 *
		 * @param array<string, array<string, mixed>> $types Types keyed by id.
		 */
		$filtered = apply_filters( 'ultra_max_layout_types', $types );
		$out      = array();
		foreach ( is_array( $filtered ) ? $filtered : array() as $id => $type ) {
			if ( is_string( $id ) && isset( $types[ $id ] ) && is_array( $type ) ) {
				$out[ $id ]          = $types[ $id ];
				$out[ $id ]['ready'] = (bool) ( $type['ready'] ?? false );
			}
		}
		return $out;
	}

	/**
	 * Types that can be made now.
	 *
	 * @return array<string, LayoutType>
	 */
	public static function ready(): array {
		return array_filter( self::all(), static fn ( array $type ): bool => $type['ready'] );
	}

	/**
	 * Whether a type exists and can be made now.
	 *
	 * @param string $type Type id.
	 */
	public static function is_ready( string $type ): bool {
		return isset( self::ready()[ $type ] );
	}

	/**
	 * A type that becomes usable with its widgets in a later phase.
	 *
	 * @param string $label    Name.
	 * @param string $plural   Name of many.
	 * @param string $location Page location it replaces.
	 * @return LayoutType
	 */
	private static function later( string $label, string $plural, string $location ): array {
		return array(
			'label'       => $label,
			'plural'      => $plural,
			'description' => '',
			'icon'        => 'layers',
			'location'    => $location,
			'ready'       => false,
			'sticky'      => false,
		);
	}
}
