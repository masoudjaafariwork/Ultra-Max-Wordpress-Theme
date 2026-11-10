<?php
/**
 * Contact form widget (HOME-12).
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
 * Name, email or mobile number and message, emailed to the store's
 * address. Protected against spam bots without a puzzle for visitors.
 */
class ContactForm extends GeneralWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'contact-form';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Contact form', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-form-horizontal';
	}

	/**
	 * Assets.
	 *
	 * @return list<string>
	 */
	public function assets(): array {
		return array( 'forms' );
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
					'default' => __( 'Send us a message', 'ultra-max-core' ),
				),
				array(
					'id'      => 'button',
					'type'    => 'text',
					'label'   => __( 'Button text', 'ultra-max-core' ),
					'default' => __( 'Send', 'ultra-max-core' ),
				),
			)
		);
	}

	/**
	 * Form id from the settings, stable between page views.
	 *
	 * @param Values $settings Settings.
	 */
	protected function form_id( Values $settings ): string {
		return 'um-form-' . substr( md5( $this->id() . wp_json_encode( $settings->all() ) ), 0, 8 );
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
			'kind'   => 'contact',
			'title'  => $settings->string( 'title' ),
			'fields' => array(
				array(
					'name'         => 'name',
					'label'        => __( 'Your name', 'ultra-max-core' ),
					'required'     => true,
					'autocomplete' => 'name',
				),
				array(
					'name'         => 'contact',
					'label'        => __( 'Email or mobile number', 'ultra-max-core' ),
					'required'     => true,
					'autocomplete' => 'email',
					'dir'          => 'ltr',
				),
				array(
					'name'     => 'message',
					'label'    => __( 'Message', 'ultra-max-core' ),
					'type'     => 'textarea',
					'required' => true,
				),
			),
			'hidden' => Forms::hidden( Forms::ACTION_CONTACT, $id ),
			'submit' => $settings->string( 'button' ),
		);
		$message = Forms::message( $id );
		if ( null !== $message ) {
			$props['message'] = $message;
		}
		return SiteForm::render( $props );
	}
}
