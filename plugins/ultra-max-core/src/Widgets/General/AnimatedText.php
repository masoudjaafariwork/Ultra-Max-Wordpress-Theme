<?php
/**
 * Animated text widget (HOME-12).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\General;

use UltraMax\Core\Lib\UI\AnimatedText as Component;
use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Widgets\Values;

/**
 * A heading whose highlighted word changes in turn.
 */
final class AnimatedText extends GeneralWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'animated-text';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Animated text', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-animated-headline';
	}

	/**
	 * Assets.
	 *
	 * @return list<string>
	 */
	public function assets(): array {
		return array( 'animated-text' );
	}

	/**
	 * Controls.
	 */
	public function controls(): array {
		return $this->one(
			__( 'Text', 'ultra-max-core' ),
			array(
				array(
					'id'      => 'before',
					'type'    => 'text',
					'label'   => __( 'Before the words', 'ultra-max-core' ),
					'default' => __( 'Shopping that is', 'ultra-max-core' ),
				),
				array(
					'id'      => 'words',
					'type'    => 'textarea',
					'label'   => __( 'Changing words, one per line', 'ultra-max-core' ),
					'default' => __( "fast\ngenuine\naffordable", 'ultra-max-core' ),
				),
				array(
					'id'    => 'after',
					'type'  => 'text',
					'label' => __( 'After the words', 'ultra-max-core' ),
				),
				array(
					'id'      => 'tag',
					'type'    => 'select',
					'label'   => __( 'Heading level', 'ultra-max-core' ),
					'default' => 'h2',
					'options' => array(
						'h2' => 'H2',
						'h3' => 'H3',
						'p'  => __( 'Paragraph', 'ultra-max-core' ),
					),
				),
			)
		);
	}

	/**
	 * Renders.
	 *
	 * @param Values $settings Settings.
	 */
	public function render( Values $settings ): Html {
		$words = array_values( array_filter( array_map( 'trim', explode( "\n", $settings->string( 'words' ) ) ), static fn ( string $word ): bool => '' !== $word ) );
		$tag   = $settings->string( 'tag' );
		return Component::render(
			array(
				'before' => $settings->string( 'before' ),
				'words'  => $words,
				'after'  => $settings->string( 'after' ),
				'tag'    => in_array( $tag, array( 'h2', 'h3', 'p' ), true ) ? $tag : 'h2',
			)
		);
	}
}
