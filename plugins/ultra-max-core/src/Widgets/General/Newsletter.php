<?php
/**
 * Newsletter widget (HOME-12, HDR-11).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\General;

use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Lib\UI\SiteForm;
use UltraMax\Core\Store\Forms;
use UltraMax\Core\Widgets\Values;

/**
 * One field for an email or mobile number and a button, to hear about
 * offers first. Numbers and emails are kept for the store to use.
 */
final class Newsletter extends ContactForm {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'newsletter';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Newsletter', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-email-field';
	}

	/**
	 * Features.
	 *
	 * @return list<string>
	 */
	public function features(): array {
		return array( 'HOME-12', 'HDR-11' );
	}

	/**
	 * Controls.
	 */
	public function controls(): array {
		return $this->one(
			__( 'Form', 'ultra-max-core' ),
			array(
				array(
					'id'      => 'title',
					'type'    => 'text',
					'label'   => __( 'Title', 'ultra-max-core' ),
					'default' => __( 'Hear about offers and new arrivals first', 'ultra-max-core' ),
				),
				array(
					'id'      => 'placeholder',
					'type'    => 'text',
					'label'   => __( 'Placeholder', 'ultra-max-core' ),
					'default' => __( 'Email or mobile number', 'ultra-max-core' ),
				),
				array(
					'id'      => 'button',
					'type'    => 'text',
					'label'   => __( 'Button text', 'ultra-max-core' ),
					'default' => __( 'Join', 'ultra-max-core' ),
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
		$id      = $this->form_id( $settings );
		$props   = array(
			'id'     => $id,
			'action' => Forms::action_url(),
			'kind'   => 'newsletter',
			'title'  => $settings->string( 'title' ),
			'fields' => array(
				array(
					'name'        => 'contact',
					'label'       => __( 'Email or mobile number', 'ultra-max-core' ),
					'placeholder' => $settings->string( 'placeholder' ),
					'required'    => true,
					'dir'         => 'ltr',
				),
			),
			'hidden' => Forms::hidden( Forms::ACTION_NEWSLETTER, $id ),
			'submit' => $settings->string( 'button' ),
		);
		$message = Forms::message( $id );
		if ( null !== $message ) {
			$props['message'] = $message;
		}
		return SiteForm::render( $props );
	}
}
