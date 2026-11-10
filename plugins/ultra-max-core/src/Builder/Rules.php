<?php
/**
 * Display conditions of layouts (BLD-02).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Builder;

/**
 * Where a layout shows. A layout has rules that include pages and rules
 * that exclude pages, a list of visitor roles and a priority:
 *
 * - It shows when any include rule matches and no exclude rule does.
 * - With roles, it shows only to visitors with one of them ("guest" means
 *   logged out).
 * - When several layouts match, the higher priority wins; on equal
 *   priority, the more specific rule wins (a single product beats its
 *   category, which beats "all products", which beats "entire site").
 *
 * Devices are not a rule: every device gets its own winner, and the
 * renderer hides each layout on the devices it did not win, so pages stay
 * cacheable.
 *
 * @phpstan-type Rule array{target: string, values?: list<int|string>}
 * @phpstan-type Target array{label: string, group: string, values: ''|'post'|'page'|'product'|'term'|'product_type', taxonomy?: string}
 * @phpstan-import-type Conditions from Layouts
 */
final class Rules {

	/**
	 * Targets that are a taxonomy; the target id is the taxonomy name.
	 */
	public const TAXONOMIES = array( 'category', 'post_tag', 'product_cat', 'product_tag', 'product_brand' );

	/**
	 * Every target a rule can point at.
	 *
	 * @return array<string, Target>
	 */
	public static function targets(): array {
		static $cache = array();
		$locale       = determine_locale();
		if ( isset( $cache[ $locale ] ) ) {
			return $cache[ $locale ];
		}
		$targets = array(
			'all'           => array(
				'label'  => __( 'Entire site', 'ultra-max-core' ),
				'group'  => 'general',
				'values' => '',
			),
			'front'         => array(
				'label'  => __( 'Front page', 'ultra-max-core' ),
				'group'  => 'general',
				'values' => '',
			),
			'search'        => array(
				'label'  => __( 'Search results', 'ultra-max-core' ),
				'group'  => 'general',
				'values' => '',
			),
			'404'           => array(
				'label'  => __( 'Page not found (404)', 'ultra-max-core' ),
				'group'  => 'general',
				'values' => '',
			),
			'pages'         => array(
				'label'  => __( 'All pages', 'ultra-max-core' ),
				'group'  => 'general',
				'values' => '',
			),
			'page-id'       => array(
				'label'  => __( 'Specific pages', 'ultra-max-core' ),
				'group'  => 'general',
				'values' => 'page',
			),
			'blog'          => array(
				'label'  => __( 'Blog and post archives', 'ultra-max-core' ),
				'group'  => 'blog',
				'values' => '',
			),
			'posts'         => array(
				'label'  => __( 'All posts', 'ultra-max-core' ),
				'group'  => 'blog',
				'values' => '',
			),
			'post-id'       => array(
				'label'  => __( 'Specific posts', 'ultra-max-core' ),
				'group'  => 'blog',
				'values' => 'post',
			),
			'category'      => array(
				'label'    => __( 'Post category', 'ultra-max-core' ),
				'group'    => 'blog',
				'values'   => 'term',
				'taxonomy' => 'category',
			),
			'post_tag'      => array(
				'label'    => __( 'Post tag', 'ultra-max-core' ),
				'group'    => 'blog',
				'values'   => 'term',
				'taxonomy' => 'post_tag',
			),
			'shop'          => array(
				'label'  => __( 'Shop and product archives', 'ultra-max-core' ),
				'group'  => 'store',
				'values' => '',
			),
			'products'      => array(
				'label'  => __( 'All products', 'ultra-max-core' ),
				'group'  => 'store',
				'values' => '',
			),
			'product-id'    => array(
				'label'  => __( 'Specific products', 'ultra-max-core' ),
				'group'  => 'store',
				'values' => 'product',
			),
			'product_cat'   => array(
				'label'    => __( 'Product category', 'ultra-max-core' ),
				'group'    => 'store',
				'values'   => 'term',
				'taxonomy' => 'product_cat',
			),
			'product_tag'   => array(
				'label'    => __( 'Product tag', 'ultra-max-core' ),
				'group'    => 'store',
				'values'   => 'term',
				'taxonomy' => 'product_tag',
			),
			'product_brand' => array(
				'label'    => __( 'Brand', 'ultra-max-core' ),
				'group'    => 'store',
				'values'   => 'term',
				'taxonomy' => 'product_brand',
			),
			'product-type'  => array(
				'label'  => __( 'Product type', 'ultra-max-core' ),
				'group'  => 'store',
				'values' => 'product_type',
			),
		);
		if ( ! taxonomy_exists( 'product_brand' ) ) {
			unset( $targets['product_brand'] );
		}
		if ( ! post_type_exists( 'product' ) ) {
			unset( $targets['shop'], $targets['products'], $targets['product-id'], $targets['product_cat'], $targets['product_tag'], $targets['product-type'] );
		}
		/**
		 * The targets left for this site.
		 *
		 * @var array<string, Target> $targets
		 */
		$cache[ $locale ] = $targets;
		return $targets;
	}

	/**
	 * Product types offered by the "product type" rule.
	 *
	 * @return array<string, string>
	 */
	public static function product_types(): array {
		if ( function_exists( 'wc_get_product_types' ) ) {
			$types = wc_get_product_types();
			return array_filter( $types, 'is_string' );
		}
		return array(
			'simple'   => __( 'Simple product', 'ultra-max-core' ),
			'variable' => __( 'Variable product', 'ultra-max-core' ),
			'grouped'  => __( 'Grouped product', 'ultra-max-core' ),
			'external' => __( 'External/Affiliate product', 'ultra-max-core' ),
		);
	}

	/**
	 * Whether a rule matches a request.
	 *
	 * @param array   $rule    Rule.
	 * @param Context $context Request.
	 * @phpstan-param Rule $rule
	 */
	public static function matches( array $rule, Context $context ): bool {
		$values = array_map( 'strval', $rule['values'] ?? array() );
		$ids    = array_map( 'intval', $values );
		switch ( $rule['target'] ) {
			case 'all':
				return true;
			case 'front':
				return 'front' === $context->kind;
			case 'search':
				return 'search' === $context->kind;
			case '404':
				return '404' === $context->kind;
			case 'pages':
				return self::singular( $context, 'page' );
			case 'page-id':
			case 'post-id':
			case 'product-id':
				return ( 'singular' === $context->kind || 'front' === $context->kind ) && $context->object_id > 0 && in_array( $context->object_id, $ids, true );
			case 'blog':
				return 'blog' === $context->kind || ( 'archive' === $context->kind && 'post' === $context->post_type );
			case 'posts':
				return self::singular( $context, 'post' );
			case 'shop':
				return 'archive' === $context->kind && 'product' === $context->post_type;
			case 'products':
				return self::singular( $context, 'product' );
			case 'product-type':
				return self::singular( $context, 'product' ) && in_array( $context->product_type, $values, true );
		}
		if ( ! in_array( $rule['target'], self::TAXONOMIES, true ) ) {
			return false;
		}
		$terms = $context->terms[ $rule['target'] ] ?? array();
		return array() !== array_intersect( $ids, $terms );
	}

	/**
	 * Whether the request shows one post of a type, front page included.
	 *
	 * @param Context $context   Request.
	 * @param string  $post_type Post type.
	 */
	private static function singular( Context $context, string $post_type ): bool {
		return in_array( $context->kind, array( 'singular', 'front' ), true ) && $post_type === $context->post_type;
	}

	/**
	 * How specific a rule is; higher wins between layouts of equal priority.
	 *
	 * @param array $rule Rule.
	 * @phpstan-param Rule $rule
	 */
	public static function specificity( array $rule ): int {
		if ( in_array( $rule['target'], array( 'page-id', 'post-id', 'product-id' ), true ) ) {
			return 30;
		}
		if ( in_array( $rule['target'], self::TAXONOMIES, true ) ) {
			return 20;
		}
		if ( 'product-type' === $rule['target'] ) {
			return 15;
		}
		return 'all' === $rule['target'] ? 0 : 10;
	}

	/**
	 * Whether a layout's conditions match a request, and how specific the
	 * best matching rule is.
	 *
	 * @param array   $conditions Conditions.
	 * @param Context $context    Request.
	 * @phpstan-param Conditions $conditions
	 * @return int|null Specificity of the best include rule, or null when the layout does not apply.
	 */
	public static function score( array $conditions, Context $context ): ?int {
		if ( array() !== $conditions['roles'] && array() === array_intersect( $conditions['roles'], $context->roles ) ) {
			return null;
		}
		foreach ( $conditions['exclude'] as $rule ) {
			if ( self::matches( $rule, $context ) ) {
				return null;
			}
		}
		$best = null;
		foreach ( $conditions['include'] as $rule ) {
			if ( self::matches( $rule, $context ) ) {
				$best = max( $best ?? 0, self::specificity( $rule ) );
			}
		}
		return $best;
	}

	/**
	 * Cleans submitted rules: known targets only, values only where the
	 * target takes them.
	 *
	 * @param mixed $rules Submitted rules.
	 * @return list<Rule>
	 */
	public static function sanitize( mixed $rules ): array {
		$targets = self::targets();
		$out     = array();
		foreach ( is_array( $rules ) ? $rules : array() as $rule ) {
			if ( ! is_array( $rule ) || ! is_string( $rule['target'] ?? null ) || ! isset( $targets[ $rule['target'] ] ) ) {
				continue;
			}
			$kind  = $targets[ $rule['target'] ]['values'];
			$clean = array( 'target' => $rule['target'] );
			if ( '' !== $kind ) {
				$values = array();
				foreach ( is_array( $rule['values'] ?? null ) ? $rule['values'] : array() as $value ) {
					if ( 'product_type' === $kind ) {
						if ( is_string( $value ) && '' !== sanitize_key( $value ) ) {
							$values[] = sanitize_key( $value );
						}
					} elseif ( is_numeric( $value ) && (int) $value > 0 ) {
						$values[] = (int) $value;
					}
				}
				if ( array() === $values ) {
					continue;
				}
				$clean['values'] = array_values( array_unique( $values ) );
			}
			$out[] = $clean;
		}
		return $out;
	}
}
