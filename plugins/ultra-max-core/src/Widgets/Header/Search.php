<?php
/**
 * Search widget (HDR-02).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Header;

use UltraMax\Core\Lib\UI\Dialog;
use UltraMax\Core\Lib\UI\HeaderAction;
use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Lib\UI\UI;
use UltraMax\Core\Search\Search as LiveSearch;
use UltraMax\Core\Widgets\Values;

/**
 * The store search: a wide field for desktop headers, or a search icon
 * that opens the field in a window, for phones ("mobile search"). It
 * searches products when WooCommerce is active. With the live search
 * module, the field suggests while typing (SRCH-01) and the phone icon
 * opens the full screen search window (SRCH-04).
 */
final class Search extends HeaderWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'search';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Search', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-search';
	}

	/**
	 * Controls.
	 */
	public function controls(): array {
		return array(
			array(
				'id'     => 'search_section',
				'label'  => __( 'Search', 'ultra-max-core' ),
				'fields' => array(
					array(
						'id'      => 'layout',
						'type'    => 'select',
						'label'   => __( 'Show as', 'ultra-max-core' ),
						'default' => 'field',
						'options' => array(
							'field' => __( 'Search field', 'ultra-max-core' ),
							'icon'  => __( 'Icon that opens the field (mobile search)', 'ultra-max-core' ),
						),
					),
					array(
						'id'      => 'placeholder',
						'type'    => 'text',
						'label'   => __( 'Placeholder', 'ultra-max-core' ),
						/* translators: %s: store name. */
						'default' => sprintf( __( 'Search in %s', 'ultra-max-core' ), get_bloginfo( 'name' ) ),
					),
					array(
						'id'      => 'products',
						'type'    => 'switch',
						'label'   => __( 'Search products only', 'ultra-max-core' ),
						'default' => true,
					),
				),
			),
		);
	}

	/**
	 * Features.
	 *
	 * @return list<string>
	 */
	public function features(): array {
		return array( 'HDR-02', 'SRCH-01', 'SRCH-04' );
	}

	/**
	 * Assets: the live search's too, when its module is on.
	 *
	 * @return list<string>
	 */
	public function assets(): array {
		return LiveSearch::enabled() ? array( 'header', 'search' ) : array( 'header' );
	}

	/**
	 * Renders.
	 *
	 * @param Values $settings Settings.
	 */
	public function render( Values $settings ): Html {
		static $count = 0;
		++$count;
		$id    = 'um-search-' . $count;
		$props = array(
			'id'          => $id . '-field',
			'action'      => home_url( '/' ),
			'label'       => __( 'Search', 'ultra-max-core' ),
			'submit'      => __( 'Search', 'ultra-max-core' ),
			'value'       => get_search_query(),
			'placeholder' => $settings->string( 'placeholder' ),
			'variant'     => 'soft',
		);
		if ( $settings->bool( 'products' ) && post_type_exists( 'product' ) ) {
			$props['hidden'] = array( 'post_type' => 'product' );
		}
		$live = LiveSearch::enabled();
		if ( 'icon' !== $settings->string( 'layout' ) ) {
			if ( $live ) {
				$props['live'] = LiveSearch::live();
			}
			return UI::search_form( $props );
		}
		if ( $live ) {
			// The full screen search window with recent and popular searches (SRCH-04).
			return Html::from(
				array(
					HeaderAction::render(
						array(
							'label' => __( 'Search', 'ultra-max-core' ),
							'icon'  => 'search',
							'open'  => LiveSearch::DIALOG,
						)
					),
					LiveSearch::window( $settings->string( 'placeholder' ) ),
				)
			);
		}
		$props['variant'] = 'outline';
		return Html::from(
			array(
				HeaderAction::render(
					array(
						'label' => __( 'Search', 'ultra-max-core' ),
						'icon'  => 'search',
						'open'  => $id,
					)
				),
				Dialog::render(
					array(
						'id'       => $id,
						'title'    => __( 'Search', 'ultra-max-core' ),
						'children' => UI::search_form( $props ),
						'size'     => 'md',
					)
				),
			)
		);
	}
}
