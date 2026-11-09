<?php
/**
 * Contact services of the floating buttons (CORE-11).
 *
 * @package UltraMax\Theme
 */

declare(strict_types=1);

namespace UltraMax\Theme;

use UltraMax\Theme\Lib\Foundation\Digits;
use UltraMax\Theme\Lib\Settings\Store;

/**
 * Builds the links of the contact buttons from what the buyer typed: a
 * phone number, a username or a full link.
 */
final class ContactLinks {

	/**
	 * Services, their default label, icon and link pattern.
	 *
	 * @return array<string, array{label: string, icon: string}>
	 */
	public static function catalog(): array {
		return array(
			'whatsapp' => array(
				'label' => __( 'WhatsApp', 'ultra-max' ),
				'icon'  => 'chat',
			),
			'telegram' => array(
				'label' => __( 'Telegram', 'ultra-max' ),
				'icon'  => 'send',
			),
			'eitaa'    => array(
				'label' => __( 'Eitaa', 'ultra-max' ),
				'icon'  => 'chat',
			),
			'bale'     => array(
				'label' => __( 'Bale', 'ultra-max' ),
				'icon'  => 'chat',
			),
			'phone'    => array(
				'label' => __( 'Call us', 'ultra-max' ),
				'icon'  => 'phone',
			),
			'sms'      => array(
				'label' => __( 'Text message', 'ultra-max' ),
				'icon'  => 'chat',
			),
			'email'    => array(
				'label' => __( 'Email', 'ultra-max' ),
				'icon'  => 'mail',
			),
			'link'     => array(
				'label' => __( 'Contact us', 'ultra-max' ),
				'icon'  => 'link',
			),
		);
	}

	/**
	 * Options for the settings panel.
	 *
	 * @return list<array{value: string, label: string}>
	 */
	public static function services(): array {
		$options = array();
		foreach ( self::catalog() as $value => $service ) {
			$options[] = array(
				'value' => $value,
				'label' => $service['label'],
			);
		}
		return $options;
	}

	/**
	 * A phone number in international form for wa.me: 0912… becomes 98912….
	 *
	 * @param string $value Typed number.
	 */
	public static function international( string $value ): string {
		$digits = (string) preg_replace( '/\D+/', '', Digits::to_latin( $value ) );
		if ( str_starts_with( $digits, '00' ) ) {
			return substr( $digits, 2 );
		}
		if ( str_starts_with( $digits, '0' ) ) {
			return '98' . substr( $digits, 1 );
		}
		return $digits;
	}

	/**
	 * The link of one service.
	 *
	 * @param string $type  Service.
	 * @param string $value Number, username or link.
	 */
	public static function href( string $type, string $value ): string {
		$value = trim( $value );
		if ( '' === $value ) {
			return '';
		}
		if ( 1 === preg_match( '#^https?://#i', $value ) ) {
			return $value;
		}
		$user  = ltrim( $value, '@' );
		$phone = (string) preg_replace( '/[^\d+]/', '', Digits::to_latin( $value ) );
		return match ( $type ) {
			'whatsapp' => 'https://wa.me/' . self::international( $value ),
			'telegram' => 'https://t.me/' . rawurlencode( $user ),
			'eitaa'    => 'https://eitaa.com/' . rawurlencode( $user ),
			'bale'     => 'https://ble.ir/' . rawurlencode( $user ),
			'phone'    => 'tel:' . $phone,
			'sms'      => 'sms:' . $phone,
			'email'    => is_email( $value ) ? 'mailto:' . $value : '',
			default    => '',
		};
	}

	/**
	 * The buttons from the settings, ready for the component.
	 *
	 * @return list<array{label: string, href: string, icon: string}>
	 */
	public static function items(): array {
		$catalog = self::catalog();
		$items   = array();
		foreach ( Store::rows( 'contact_items' ) as $row ) {
			$type    = is_string( $row['type'] ?? null ) ? $row['type'] : '';
			$service = $catalog[ $type ] ?? null;
			$href    = self::href( $type, is_string( $row['value'] ?? null ) ? $row['value'] : '' );
			if ( null === $service || '' === $href ) {
				continue;
			}
			$label   = is_string( $row['label'] ?? null ) && '' !== trim( $row['label'] ) ? trim( $row['label'] ) : $service['label'];
			$items[] = array(
				'label' => $label,
				'href'  => $href,
				'icon'  => $service['icon'],
			);
		}
		return $items;
	}
}
