<?php
/**
 * Video widget (HOME-12).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\General;

use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Lib\UI\Video as Component;
use UltraMax\Core\Widgets\Values;

/**
 * A video that loads only when played: a video file from the media
 * library, or the embed address of a video site such as Aparat.
 */
final class Video extends GeneralWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'video';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Video', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-youtube';
	}

	/**
	 * Assets.
	 *
	 * @return list<string>
	 */
	public function assets(): array {
		return array( 'video' );
	}

	/**
	 * Controls.
	 */
	public function controls(): array {
		return $this->one(
			__( 'Video', 'ultra-max-core' ),
			array(
				array(
					'id'    => 'file',
					'type'  => 'media',
					'label' => __( 'Video file (MP4)', 'ultra-max-core' ),
				),
				array(
					'id'    => 'embed',
					'type'  => 'url',
					'label' => __( 'Or the embed address', 'ultra-max-core' ),
					'help'  => __( 'The address inside the embed code of the video site, starting with https.', 'ultra-max-core' ),
				),
				array(
					'id'    => 'cover',
					'type'  => 'media',
					'label' => __( 'Cover image', 'ultra-max-core' ),
				),
				array(
					'id'      => 'title',
					'type'    => 'text',
					'label'   => __( 'Video title', 'ultra-max-core' ),
					'default' => __( 'Introduction video', 'ultra-max-core' ),
				),
				array(
					'id'      => 'ratio',
					'type'    => 'select',
					'label'   => __( 'Shape', 'ultra-max-core' ),
					'default' => '16/9',
					'options' => array(
						'16/9' => __( 'Wide (16:9)', 'ultra-max-core' ),
						'4/3'  => '4:3',
						'1/1'  => __( 'Square', 'ultra-max-core' ),
						'9/16' => __( 'Tall (phone)', 'ultra-max-core' ),
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
		$file  = $settings->media( 'file' )['url'];
		$embed = $settings->href( 'embed' );
		if ( '' === $file && ! str_starts_with( $embed, 'https://' ) ) {
			return Html::none();
		}
		$ratio = $settings->string( 'ratio' );
		return Component::render(
			array(
				'label' => $settings->string( 'title' ),
				'src'   => '' !== $file ? $file : $embed,
				'kind'  => '' !== $file ? 'file' : 'embed',
				'cover' => $this->image( $settings->media( 'cover' ), 'large' ),
				/* translators: %s: video title. */
				'play'  => sprintf( __( 'Play: %s', 'ultra-max-core' ), $settings->string( 'title' ) ),
				'ratio' => in_array( $ratio, array( '16/9', '4/3', '1/1', '9/16' ), true ) ? $ratio : '16/9',
			)
		);
	}
}
