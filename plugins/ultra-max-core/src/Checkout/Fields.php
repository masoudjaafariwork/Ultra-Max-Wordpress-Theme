<?php
/**
 * The checkout's fields (CHK-03 to CHK-06, CHK-08, CHK-10).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Checkout;

use UltraMax\Core\Lib\Settings\Store;

/**
 * One list of fields for both checkouts.
 *
 * - The address fields (WooCommerce's own) follow the store's field editor
 *   (CHK-06): label, required, optional or hidden, and order. The classic
 *   checkout reads it from WooCommerce's field filters; the block checkout
 *   from the Iranian address locale and its three visibility options.
 * - Our own fields: the buyer type with national code or company details
 *   (CHK-05), the marketing consent (CHK-08) and the store's own fields
 *   (CHK-06). The block checkout gets them through WooCommerce's
 *   additional checkout fields; the classic checkout as `um_…` fields.
 *   Both save them under the same order meta (`_wc_other/ultra-max/…` or
 *   `_wc_billing/ultra-max/…`), so WooCommerce shows them on the order
 *   screen and in emails, and later features read them in one place.
 * - Values are checked by the Iranian rules (CHK-04): mobile, postal code,
 *   national code, company id and economic code; digits typed in Persian
 *   are stored in Latin.
 *
 * @phpstan-type Spec array{id: string, label: string, type: 'text'|'select'|'checkbox'|'textarea', place: 'contact'|'address'|'order', required: bool, when: 'always'|'person'|'company', options: array<string, string>, check: string, ltr: bool}
 */
final class Fields {

	public const NAMESPACE = 'ultra-max';
	public const CORE      = array( 'first_name', 'last_name', 'phone', 'email', 'company', 'state', 'city', 'address_1', 'address_2', 'postcode' );

	/**
	 * Hooks both checkouts.
	 */
	public static function register(): void {
		add_filter( 'woocommerce_default_address_fields', array( self::class, 'address_fields' ), 30 );
		add_filter( 'woocommerce_get_country_locale', array( self::class, 'locale' ), 30 );
		add_filter( 'woocommerce_billing_fields', array( self::class, 'billing_fields' ), 30 );
		add_filter( 'woocommerce_shipping_fields', array( self::class, 'shipping_fields' ), 30 );
		add_filter( 'woocommerce_checkout_fields', array( self::class, 'checkout_fields' ), 30 );
		foreach ( array( 'phone', 'company', 'address_2' ) as $field ) {
			add_filter( 'option_woocommerce_checkout_' . $field . '_field', array( self::class, 'visibility_option' ), 10, 2 );
			add_filter( 'default_option_woocommerce_checkout_' . $field . '_field', array( self::class, 'visibility_option' ), 10, 2 );
		}
		foreach ( array( 'billing_phone', 'billing_postcode', 'shipping_postcode', 'shipping_phone' ) as $key ) {
			add_filter( 'woocommerce_process_checkout_field_' . $key, array( self::class, 'latin' ) );
		}
		add_filter( 'woocommerce_checkout_posted_data', array( self::class, 'posted' ) );
		add_action( 'woocommerce_after_checkout_validation', array( self::class, 'validate_classic' ), 10, 2 );
		add_action( 'woocommerce_checkout_create_order', array( self::class, 'save_classic' ), 10, 2 );
		add_filter( 'woocommerce_checkout_get_value', array( self::class, 'saved_value' ), 10, 2 );
		add_action( 'woocommerce_init', array( self::class, 'register_blocks' ) );
		add_action( 'woocommerce_validate_additional_field', array( self::class, 'validate_block_field' ), 10, 3 );
		add_action( 'woocommerce_blocks_validate_location_address_fields', array( self::class, 'validate_block_address' ), 10, 2 );
		add_action( 'woocommerce_store_api_checkout_update_order_from_request', array( self::class, 'check_block_order' ), 20, 1 );
	}

	/**
	 * The default rows of the field editor: an Iranian order.
	 *
	 * @return list<array{field: string, label: string, show: string}>
	 */
	public static function default_rows(): array {
		$rows = array();
		foreach ( array(
			'first_name' => 'required',
			'last_name'  => 'required',
			'phone'      => 'required',
			'state'      => 'required',
			'city'       => 'required',
			'address_1'  => 'required',
			'postcode'   => 'required',
			'email'      => 'optional',
			'address_2'  => 'hidden',
			'company'    => 'hidden',
		) as $field => $show ) {
			$rows[] = array(
				'field' => $field,
				'label' => '',
				'show'  => $show,
			);
		}
		return $rows;
	}

	/**
	 * Names of the address fields for the editor.
	 *
	 * @return array<string, string>
	 */
	public static function core_labels(): array {
		return array(
			'first_name' => __( 'First name', 'ultra-max-core' ),
			'last_name'  => __( 'Last name', 'ultra-max-core' ),
			'phone'      => __( 'Mobile', 'ultra-max-core' ),
			'email'      => __( 'Email', 'ultra-max-core' ),
			'company'    => __( 'Company', 'ultra-max-core' ),
			'state'      => __( 'Province', 'ultra-max-core' ),
			'city'       => __( 'City', 'ultra-max-core' ),
			'address_1'  => __( 'Postal address', 'ultra-max-core' ),
			'address_2'  => __( 'Unit, floor (optional)', 'ultra-max-core' ),
			'postcode'   => __( 'Postal code', 'ultra-max-core' ),
		);
	}

	/**
	 * The editor's rules for the address fields, in their order.
	 *
	 * @return array<string, array{label: string, show: string, priority: int}>
	 */
	public static function rules(): array {
		$rows = Store::get( 'checkout_fields', array() );
		$rows = is_array( $rows ) && array() !== $rows ? $rows : self::default_rows();
		$out  = array();
		$at   = 10;
		foreach ( $rows as $row ) {
			$field = is_array( $row ) && is_string( $row['field'] ?? null ) ? $row['field'] : '';
			if ( ! in_array( $field, self::CORE, true ) || isset( $out[ $field ] ) ) {
				continue;
			}
			$show          = is_string( $row['show'] ?? null ) && in_array( $row['show'], array( 'required', 'optional', 'hidden' ), true ) ? $row['show'] : 'required';
			$out[ $field ] = array(
				'label'    => is_string( $row['label'] ?? null ) ? trim( $row['label'] ) : '',
				'show'     => $show,
				'priority' => $at,
			);
			$at           += 10;
		}
		// Fields missing from the editor keep WooCommerce's way, after the others.
		foreach ( self::CORE as $field ) {
			if ( ! isset( $out[ $field ] ) ) {
				$out[ $field ] = array(
					'label'    => '',
					'show'     => '',
					'priority' => $at,
				);
				$at           += 10;
			}
		}
		return $out;
	}

	/**
	 * Applies a rule to one WooCommerce field; null when it is hidden.
	 *
	 * @param array<mixed>                                           $field Field.
	 * @param array{label: string, show: string, priority: int}|null $rule  Rule.
	 * @param string                                                 $name  Field name without group, for the defaults.
	 * @return array<mixed>|null
	 */
	private static function apply_rule( array $field, ?array $rule, string $name ): ?array {
		if ( null === $rule ) {
			return $field;
		}
		if ( 'hidden' === $rule['show'] ) {
			return null;
		}
		$labels            = self::core_labels();
		$field['label']    = '' !== $rule['label'] ? $rule['label'] : ( 'phone' === $name || 'address_1' === $name ? $labels[ $name ] : ( $field['label'] ?? '' ) );
		$field['priority'] = $rule['priority'];
		if ( '' !== $rule['show'] ) {
			$field['required'] = 'required' === $rule['show'];
		}
		if ( 'address_1' === $name ) {
			$field['placeholder'] = __( 'Street, alley, building number', 'ultra-max-core' );
		}
		return $field;
	}

	/**
	 * The address fields of both groups (classic, and blocks' defaults).
	 *
	 * @param mixed $fields Fields.
	 * @return mixed
	 */
	public static function address_fields( mixed $fields ): mixed {
		if ( ! is_array( $fields ) ) {
			return $fields;
		}
		$rules = self::rules();
		foreach ( $fields as $name => $field ) {
			if ( ! is_array( $field ) || ! isset( $rules[ (string) $name ] ) ) {
				continue;
			}
			$applied = self::apply_rule( $field, $rules[ (string) $name ], (string) $name );
			if ( null === $applied ) {
				// The block checkout hides it through the locale instead.
				unset( $fields[ $name ] );
				continue;
			}
			$fields[ $name ] = $applied;
		}
		return $fields;
	}

	/**
	 * The Iranian address locale follows the editor too: WooCommerce's own
	 * locale for Iran sets an order that would win over the defaults.
	 *
	 * @param mixed $locales Locales by country.
	 * @return mixed
	 */
	public static function locale( mixed $locales ): mixed {
		if ( ! is_array( $locales ) ) {
			return $locales;
		}
		$rules = self::rules();
		$iran  = is_array( $locales['IR'] ?? null ) ? $locales['IR'] : array();
		foreach ( array( 'first_name', 'last_name', 'company', 'state', 'city', 'address_1', 'address_2', 'postcode', 'phone' ) as $name ) {
			$rule  = $rules[ $name ];
			$entry = is_array( $iran[ $name ] ?? null ) ? $iran[ $name ] : array();
			unset( $entry['priority'] );
			$entry['priority'] = $rule['priority'];
			$entry['index']    = $rule['priority'];
			if ( '' !== $rule['label'] ) {
				$entry['label'] = $rule['label'];
			}
			if ( 'hidden' === $rule['show'] ) {
				$entry['hidden']   = true;
				$entry['required'] = false;
			} elseif ( '' !== $rule['show'] ) {
				$entry['hidden']   = false;
				$entry['required'] = 'required' === $rule['show'];
			}
			$iran[ $name ] = $entry;
		}
		$iran['postcode']['label']  = '' !== $rules['postcode']['label'] ? $rules['postcode']['label'] : self::core_labels()['postcode'];
		$iran['address_1']['label'] = '' !== $rules['address_1']['label'] ? $rules['address_1']['label'] : self::core_labels()['address_1'];
		$iran['state']['label']     = '' !== $rules['state']['label'] ? $rules['state']['label'] : self::core_labels()['state'];
		$iran['phone']['label']     = '' !== $rules['phone']['label'] ? $rules['phone']['label'] : self::core_labels()['phone'];
		$locales['IR']              = $iran;
		return $locales;
	}

	/**
	 * The block checkout's visibility of phone, company and the second
	 * address line follows the editor.
	 *
	 * @param mixed  $value  Option value.
	 * @param string $option Option name.
	 * @return mixed
	 */
	public static function visibility_option( mixed $value, string $option ): mixed {
		$name = str_replace( array( 'woocommerce_checkout_', '_field' ), '', $option );
		$show = self::rules()[ $name ]['show'] ?? '';
		return '' === $show ? $value : $show;
	}

	/**
	 * Phone and email of the classic checkout, and the city list.
	 *
	 * @param mixed $fields Billing fields.
	 * @return mixed
	 */
	public static function billing_fields( mixed $fields ): mixed {
		if ( ! is_array( $fields ) ) {
			return $fields;
		}
		$rules = self::rules();
		foreach ( array( 'phone', 'email' ) as $name ) {
			$key = 'billing_' . $name;
			if ( ! is_array( $fields[ $key ] ?? null ) ) {
				continue;
			}
			$applied = self::apply_rule( $fields[ $key ], $rules[ $name ], $name );
			if ( null === $applied ) {
				unset( $fields[ $key ] );
				continue;
			}
			if ( 'phone' === $name ) {
				$applied['type']         = 'tel';
				$applied['autocomplete'] = 'tel';
				$applied['class']        = array( 'form-row-wide', 'um-ltr' );
			}
			$fields[ $key ] = $applied;
		}
		return self::city_select( $fields, 'billing' );
	}

	/**
	 * The city list of the shipping address.
	 *
	 * @param mixed $fields Shipping fields.
	 * @return mixed
	 */
	public static function shipping_fields( mixed $fields ): mixed {
		return is_array( $fields ) ? self::city_select( $fields, 'shipping' ) : $fields;
	}

	/**
	 * The city of the classic checkout as a list of the province's cities.
	 *
	 * @param array<mixed> $fields Fields of a group.
	 * @param string       $group  billing or shipping.
	 * @return array<mixed>
	 */
	private static function city_select( array $fields, string $group ): array {
		$key = $group . '_city';
		if ( ! Store::bool( 'checkout_city_list' ) || ! is_array( $fields[ $key ] ?? null ) || ! function_exists( 'WC' ) || ! WC()->customer instanceof \WC_Customer ) {
			return $fields;
		}
		$country = 'billing' === $group ? WC()->customer->get_billing_country() : WC()->customer->get_shipping_country();
		if ( '' !== $country && 'IR' !== $country ) {
			return $fields;
		}
		$state  = 'billing' === $group ? WC()->customer->get_billing_state() : WC()->customer->get_shipping_state();
		$city   = 'billing' === $group ? WC()->customer->get_billing_city() : WC()->customer->get_shipping_city();
		$cities = Cities::of( '' === $state ? WC()->countries->get_base_state() : $state );
		if ( array() === $cities ) {
			return $fields;
		}
		$options = array( '' => __( 'Choose a city', 'ultra-max-core' ) );
		foreach ( $cities as $name ) {
			$options[ $name ] = $name;
		}
		if ( '' !== $city && ! isset( $options[ $city ] ) ) {
			$options[ $city ] = $city;
		}
		$fields[ $key ]['type']    = 'select';
		$fields[ $key ]['options'] = $options;
		$fields[ $key ]['class']   = array_merge( is_array( $fields[ $key ]['class'] ?? null ) ? $fields[ $key ]['class'] : array(), array( 'um-city' ) );
		return $fields;
	}

	/**
	 * Our fields, from the settings.
	 *
	 * @return list<Spec>
	 */
	public static function own(): array {
		$out = array();
		if ( Store::bool( 'checkout_buyer' ) ) {
			$out[]    = self::spec(
				'customer-type',
				__( 'Buyer', 'ultra-max-core' ),
				'select',
				'contact',
				true,
				'always',
				array(
					'person'  => __( 'Person', 'ultra-max-core' ),
					'company' => __( 'Company', 'ultra-max-core' ),
				)
			);
			$national = Store::string( 'checkout_national_code' );
			if ( 'hidden' !== $national ) {
				$out[] = self::spec( 'national-code', __( 'National code', 'ultra-max-core' ), 'text', 'contact', 'required' === $national, 'person', array(), 'national_code', true );
			}
			$out[] = self::spec( 'company-name', __( 'Company name', 'ultra-max-core' ), 'text', 'contact', true, 'company' );
			$out[] = self::spec( 'company-id', __( 'National id of the company', 'ultra-max-core' ), 'text', 'contact', true, 'company', array(), 'company_id', true );
			$out[] = self::spec( 'economic-code', __( 'Economic code', 'ultra-max-core' ), 'text', 'contact', 'required' === Store::string( 'checkout_economic_code' ), 'company', array(), 'economic_code', true );
			$out[] = self::spec( 'registration', __( 'Registration number', 'ultra-max-core' ), 'text', 'contact', false, 'company', array(), 'digits', true );
		}
		$custom = Store::get( 'checkout_custom', array() );
		foreach ( is_array( $custom ) ? $custom : array() as $row ) {
			if ( ! is_array( $row ) || ! is_string( $row['label'] ?? null ) || '' === trim( $row['label'] ) ) {
				continue;
			}
			$key     = is_string( $row['key'] ?? null ) ? sanitize_key( $row['key'] ) : '';
			$key     = '' === $key ? 'f' . substr( md5( $row['label'] ), 0, 6 ) : $key;
			$type    = is_string( $row['type'] ?? null ) && in_array( $row['type'], array( 'text', 'select', 'checkbox', 'textarea' ), true ) ? $row['type'] : 'text';
			$options = array();
			foreach ( explode( ',', is_string( $row['options'] ?? null ) ? $row['options'] : '' ) as $option ) {
				$option = trim( $option );
				if ( '' !== $option ) {
					$options[ $option ] = $option;
				}
			}
			if ( 'select' === $type && array() === $options ) {
				$type = 'text';
			}
			$place = is_string( $row['place'] ?? null ) && in_array( $row['place'], array( 'contact', 'address', 'order' ), true ) ? $row['place'] : 'order';
			$when  = is_string( $row['when'] ?? null ) && in_array( $row['when'], array( 'person', 'company' ), true ) && Store::bool( 'checkout_buyer' ) ? $row['when'] : 'always';
			$out[] = self::spec( 'c-' . $key, trim( $row['label'] ), $type, $place, ( $row['required'] ?? false ) === true, $when, $options );
		}
		if ( Store::bool( 'checkout_marketing' ) ) {
			$text  = Store::string( 'checkout_marketing_text' );
			$out[] = self::spec( 'marketing', '' === $text ? __( 'Send me offers and discounts by text message.', 'ultra-max-core' ) : $text, 'checkbox', 'order', false, 'always' );
		}
		return $out;
	}

	/**
	 * Builds one field.
	 *
	 * @param string                $id       Id.
	 * @param string                $label    Label.
	 * @param string                $type     Type.
	 * @param string                $place    contact, address or order.
	 * @param bool                  $required Required.
	 * @param string                $when     always, person or company.
	 * @param array<string, string> $options  Options of a list.
	 * @param string                $check    Rule.
	 * @param bool                  $ltr      Typed left to right.
	 * @return Spec
	 */
	private static function spec( string $id, string $label, string $type, string $place, bool $required, string $when, array $options = array(), string $check = '', bool $ltr = false ): array {
		return array(
			'id'       => $id,
			'label'    => $label,
			'type'     => in_array( $type, array( 'text', 'select', 'checkbox', 'textarea' ), true ) ? $type : 'text',
			'place'    => in_array( $place, array( 'contact', 'address', 'order' ), true ) ? $place : 'order',
			'required' => $required,
			'when'     => in_array( $when, array( 'person', 'company' ), true ) ? $when : 'always',
			'options'  => $options,
			'check'    => $check,
			'ltr'      => $ltr,
		);
	}

	/**
	 * The classic checkout's name of one of our fields.
	 *
	 * @param string $id Field id.
	 */
	public static function classic_key( string $id ): string {
		return 'um_' . str_replace( '-', '_', $id );
	}

	/**
	 * The block checkout's id of one of our fields.
	 *
	 * @param string $id Field id.
	 */
	public static function block_id( string $id ): string {
		return self::NAMESPACE . '/' . $id;
	}

	/**
	 * The order meta key of one of our fields.
	 *
	 * @param array  $spec  Field.
	 * @param string $group billing or shipping, for address fields.
	 * @phpstan-param Spec $spec
	 */
	public static function meta_key( array $spec, string $group = 'billing' ): string {
		$prefix = 'address' === $spec['place'] ? '_wc_' . $group . '/' : '_wc_other/';
		return $prefix . self::block_id( $spec['id'] );
	}

	/**
	 * One of our fields by id.
	 *
	 * @param string $id Field id.
	 * @return Spec|null
	 */
	public static function find( string $id ): ?array {
		foreach ( self::own() as $spec ) {
			if ( $spec['id'] === $id ) {
				return $spec;
			}
		}
		return null;
	}

	/**
	 * The value of one of our fields on an order, from either checkout.
	 *
	 * @param \WC_Order $order Order.
	 * @param string    $id    Field id, such as national-code.
	 */
	public static function value( \WC_Order $order, string $id ): string {
		$spec  = self::find( $id ) ?? self::spec( $id, '', 'text', 'contact', false, 'always' );
		$value = $order->get_meta( self::meta_key( $spec ) );
		return is_scalar( $value ) ? (string) $value : '';
	}

	/**
	 * Latin digits for a phone or postal code.
	 *
	 * @param mixed $value Posted value.
	 * @return mixed
	 */
	public static function latin( mixed $value ): mixed {
		if ( ! is_string( $value ) ) {
			return $value;
		}
		$mobile = Iran::mobile( $value );
		return null !== $mobile ? $mobile : Iran::digits( $value );
	}

	/**
	 * Latin digits in our fields of a classic order, such as a national code.
	 *
	 * @param mixed $data Posted data.
	 * @return mixed
	 */
	public static function posted( mixed $data ): mixed {
		if ( ! is_array( $data ) ) {
			return $data;
		}
		foreach ( $data as $key => $value ) {
			if ( is_string( $key ) && str_starts_with( $key, 'um_' ) ) {
				$data[ $key ] = self::latin_if_number( $value );
			}
		}
		return $data;
	}

	/**
	 * Latin digits for a value made of digits, such as a national code.
	 *
	 * @param mixed $value Posted value.
	 * @return mixed
	 */
	public static function latin_if_number( mixed $value ): mixed {
		return is_string( $value ) && 1 === preg_match( '/^[\s\d۰-۹٠-٩\-]+$/u', $value ) ? Iran::digits( $value ) : $value;
	}

	/**
	 * Our fields in the classic checkout: contact and address fields with
	 * the billing details, order fields with the notes.
	 *
	 * @param mixed $fields Fields by group.
	 * @return mixed
	 */
	public static function checkout_fields( mixed $fields ): mixed {
		if ( ! is_array( $fields ) ) {
			return $fields;
		}
		$at = 200;
		foreach ( self::own() as $spec ) {
			$group = 'order' === $spec['place'] ? 'order' : 'billing';
			$field = array(
				'type'     => 'customer-type' === $spec['id'] ? 'radio' : $spec['type'],
				'label'    => $spec['label'],
				// Fields shown for one buyer type are checked in validate_classic().
				// The buyer type always has a value; a required mark would sit on each choice.
				'required' => $spec['required'] && 'always' === $spec['when'] && 'customer-type' !== $spec['id'],
				'priority' => $at,
				'class'    => array( 'form-row-wide', 'um-' . $spec['place'] . '-field', 'um-when-' . $spec['when'] ),
				'default'  => 'customer-type' === $spec['id'] ? 'person' : '',
			);
			$at   += 10;
			if ( $spec['ltr'] ) {
				$field['class'][]           = 'um-ltr';
				$field['custom_attributes'] = array( 'inputmode' => 'numeric' );
			}
			if ( 'always' !== $spec['when'] && $spec['required'] ) {
				$field['label_class'] = array( 'um-required' );
			}
			if ( array() !== $spec['options'] ) {
				$field['options'] = 'select' === $spec['type'] && 'customer-type' !== $spec['id'] ? array( '' => __( 'Choose', 'ultra-max-core' ) ) + $spec['options'] : $spec['options'];
			}
			if ( ! is_array( $fields[ $group ] ?? null ) ) {
				$fields[ $group ] = array();
			}
			$fields[ $group ][ self::classic_key( $spec['id'] ) ] = $field;
		}
		return $fields;
	}

	/**
	 * Why a value of one of our fields is not valid, or an empty text.
	 *
	 * @param array  $spec  Field.
	 * @param string $value Value.
	 * @phpstan-param Spec $spec
	 */
	public static function problem( array $spec, string $value ): string {
		if ( '' === trim( $value ) ) {
			return '';
		}
		$ok = match ( $spec['check'] ) {
			'national_code' => Iran::national_code( $value ),
			'company_id'    => Iran::company_id( $value ),
			'economic_code' => Iran::economic_code( $value ),
			'digits'        => 1 === preg_match( '/^\d{1,20}$/', Iran::digits( $value ) ),
			default         => true,
		};
		if ( $ok ) {
			return '';
		}
		return match ( $spec['check'] ) {
			'national_code' => __( 'The national code is not valid. Check its ten digits again.', 'ultra-max-core' ),
			'company_id'    => __( 'The national id of the company is not valid. It has eleven digits.', 'ultra-max-core' ),
			'economic_code' => __( 'The economic code is not valid. Enter its digits only.', 'ultra-max-core' ),
			/* translators: %s: field label. */
			default         => sprintf( __( '%s may contain digits only.', 'ultra-max-core' ), $spec['label'] ),
		};
	}

	/**
	 * Why an address value is not valid by the Iranian rules, or an empty text.
	 *
	 * @param string $name  Field name: phone, postcode or city.
	 * @param string $value Value.
	 */
	public static function address_problem( string $name, string $value ): string {
		if ( '' === trim( $value ) ) {
			return '';
		}
		if ( 'phone' === $name && null === Iran::mobile( $value ) ) {
			return __( 'The mobile number is not valid. Enter it as 09123456789.', 'ultra-max-core' );
		}
		if ( 'postcode' === $name && ! Iran::postcode( $value ) ) {
			return __( 'The postal code must be ten digits, without dashes.', 'ultra-max-core' );
		}
		return '';
	}

	/**
	 * Whether a field applies to the chosen buyer type.
	 *
	 * @param array  $spec Field.
	 * @param string $type person or company.
	 * @phpstan-param Spec $spec
	 */
	public static function applies( array $spec, string $type ): bool {
		$wanted = 'company' === $type ? 'company' : 'person';
		return 'always' === $spec['when'] || $wanted === $spec['when'];
	}

	/**
	 * Checks the classic checkout: the Iranian rules, and the fields of the
	 * chosen buyer type.
	 *
	 * @param mixed $data   Posted data.
	 * @param mixed $errors Errors.
	 */
	public static function validate_classic( mixed $data, mixed $errors ): void {
		if ( ! is_array( $data ) || ! $errors instanceof \WP_Error ) {
			return;
		}
		$text = static fn ( string $key ): string => is_scalar( $data[ $key ] ?? null ) ? (string) $data[ $key ] : '';
		foreach ( array( 'billing', 'shipping' ) as $group ) {
			$country = $text( $group . '_country' );
			if ( '' !== $country && 'IR' !== $country ) {
				continue;
			}
			foreach ( array( 'phone', 'postcode' ) as $name ) {
				$problem = self::address_problem( $name, $text( $group . '_' . $name ) );
				if ( '' !== $problem ) {
					$errors->add( $group . '_' . $name . '_validation', $problem, array( 'id' => $group . '_' . $name ) );
				}
			}
		}
		$type = $text( self::classic_key( 'customer-type' ) );
		foreach ( self::own() as $spec ) {
			$key   = self::classic_key( $spec['id'] );
			$value = $text( $key );
			if ( ! self::applies( $spec, $type ) ) {
				continue;
			}
			if ( $spec['required'] && 'always' !== $spec['when'] && '' === trim( $value ) ) {
				/* translators: %s: field label. */
				$errors->add( $key . '_required', sprintf( __( '%s is a required field.', 'ultra-max-core' ), '<strong>' . esc_html( $spec['label'] ) . '</strong>' ), array( 'id' => $key ) );
				continue;
			}
			$problem = self::problem( $spec, $value );
			if ( '' !== $problem ) {
				$errors->add( $key . '_validation', $problem, array( 'id' => $key ) );
			}
		}
	}

	/**
	 * Saves our fields of a classic order where the block checkout keeps
	 * them, and on the customer for the next order.
	 *
	 * @param mixed $order Order.
	 * @param mixed $data  Posted data.
	 */
	public static function save_classic( mixed $order, mixed $data ): void {
		if ( ! $order instanceof \WC_Order || ! is_array( $data ) ) {
			return;
		}
		$type     = is_string( $data[ self::classic_key( 'customer-type' ) ] ?? null ) ? $data[ self::classic_key( 'customer-type' ) ] : '';
		$customer = $order->get_customer_id() > 0 ? new \WC_Customer( $order->get_customer_id() ) : null;
		foreach ( self::own() as $spec ) {
			$key = self::classic_key( $spec['id'] );
			if ( ! array_key_exists( $key, $data ) || ! self::applies( $spec, $type ) ) {
				continue;
			}
			$value = $data[ $key ];
			$value = 'checkbox' === $spec['type'] ? ( ! empty( $value ) ? '1' : '0' ) : ( is_scalar( $value ) ? (string) $value : '' );
			$order->update_meta_data( self::meta_key( $spec ), $value );
			if ( 'address' === $spec['place'] ) {
				$order->update_meta_data( self::meta_key( $spec, 'shipping' ), $value );
			}
			if ( null !== $customer && 'order' !== $spec['place'] ) {
				$customer->update_meta_data( self::meta_key( $spec ), $value );
			}
		}
		if ( null !== $customer ) {
			$customer->save_meta_data();
		}
	}

	/**
	 * The classic checkout fills our fields from the customer's last order.
	 *
	 * @param mixed $value Value so far.
	 * @param mixed $input Field name.
	 * @return mixed
	 */
	public static function saved_value( mixed $value, mixed $input ): mixed {
		if ( null !== $value || ! is_string( $input ) || ! str_starts_with( $input, 'um_' ) || ! is_user_logged_in() ) {
			return $value;
		}
		foreach ( self::own() as $spec ) {
			if ( self::classic_key( $spec['id'] ) === $input && 'order' !== $spec['place'] ) {
				$saved = get_user_meta( get_current_user_id(), self::meta_key( $spec ), true );
				return is_scalar( $saved ) && '' !== (string) $saved ? (string) $saved : $value;
			}
		}
		return $value;
	}

	/**
	 * The rule that hides a field unless a buyer type is chosen, for the
	 * block checkout.
	 *
	 * @param string $type person or company.
	 * @return array<string, mixed>
	 */
	private static function unless( string $type ): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'customer' => array(
					'properties' => array(
						'additional_fields' => array(
							'properties' => array(
								self::block_id( 'customer-type' ) => array( 'not' => array( 'const' => $type ) ),
							),
						),
					),
				),
			),
		);
	}

	/**
	 * The rule that requires a field when a buyer type is chosen.
	 *
	 * @param string $type person or company.
	 * @return array<string, mixed>
	 */
	private static function when( string $type ): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'customer' => array(
					'properties' => array(
						'additional_fields' => array(
							'properties' => array(
								self::block_id( 'customer-type' ) => array( 'const' => $type ),
							),
							'required'   => array( self::block_id( 'customer-type' ) ),
						),
					),
				),
			),
		);
	}

	/**
	 * Registers our fields with the block checkout.
	 */
	public static function register_blocks(): void {
		if ( ! function_exists( 'woocommerce_register_additional_checkout_field' ) ) {
			return;
		}
		foreach ( self::own() as $spec ) {
			$field = array(
				'id'       => self::block_id( $spec['id'] ),
				'label'    => $spec['label'],
				'location' => $spec['place'],
				'type'     => 'textarea' === $spec['type'] ? 'text' : $spec['type'],
				'required' => $spec['required'] && 'always' === $spec['when'],
			);
			if ( 'always' !== $spec['when'] ) {
				$field['hidden'] = self::unless( $spec['when'] );
				if ( $spec['required'] ) {
					$field['required'] = self::when( $spec['when'] );
				}
			}
			if ( 'select' === $spec['type'] ) {
				$options = array();
				foreach ( $spec['options'] as $value => $label ) {
					$options[] = array(
						'value' => (string) $value,
						'label' => $label,
					);
				}
				$field['options'] = $options;
			}
			woocommerce_register_additional_checkout_field( $field );
		}
	}

	/**
	 * Checks one of our fields in the block checkout.
	 *
	 * @param mixed $errors Errors.
	 * @param mixed $key    Field id.
	 * @param mixed $value  Value.
	 */
	public static function validate_block_field( mixed $errors, mixed $key, mixed $value ): void {
		if ( ! $errors instanceof \WP_Error || ! is_string( $key ) || ! str_starts_with( $key, self::NAMESPACE . '/' ) ) {
			return;
		}
		$spec = self::find( substr( $key, strlen( self::NAMESPACE ) + 1 ) );
		if ( null === $spec || ! is_scalar( $value ) ) {
			return;
		}
		$problem = self::problem( $spec, (string) $value );
		if ( '' !== $problem ) {
			$errors->add( 'ultra_max_invalid_' . $spec['id'], $problem );
		}
	}

	/**
	 * Checks the mobile and postal code of a block checkout address.
	 *
	 * @param mixed $errors Errors.
	 * @param mixed $fields Values of the address.
	 */
	public static function validate_block_address( mixed $errors, mixed $fields ): void {
		if ( ! $errors instanceof \WP_Error || ! is_array( $fields ) ) {
			return;
		}
		$country = is_string( $fields['country'] ?? null ) ? $fields['country'] : '';
		if ( '' !== $country && 'IR' !== $country ) {
			return;
		}
		foreach ( array( 'phone', 'postcode' ) as $name ) {
			$problem = self::address_problem( $name, is_scalar( $fields[ $name ] ?? null ) ? (string) $fields[ $name ] : '' );
			if ( '' !== $problem ) {
				$errors->add( 'ultra_max_invalid_' . $name, $problem );
			}
		}
	}

	/**
	 * The last check of a block order: the Iranian rules on the addresses,
	 * and digits saved in Latin.
	 *
	 * @param mixed $order Order.
	 * @throws \Throwable WooCommerce's route error when a value is not valid.
	 */
	public static function check_block_order( mixed $order ): void {
		if ( ! $order instanceof \WC_Order ) {
			return;
		}
		foreach ( array( 'billing', 'shipping' ) as $group ) {
			$country = (string) $order->{"get_{$group}_country"}();
			if ( '' !== $country && 'IR' !== $country ) {
				continue;
			}
			foreach ( array( 'phone', 'postcode' ) as $name ) {
				$value = (string) $order->{"get_{$group}_{$name}"}();
				if ( '' === $value ) {
					continue;
				}
				$problem = self::address_problem( $name, $value );
				if ( '' !== $problem ) {
					$class = '\Automattic\WooCommerce\StoreApi\Exceptions\RouteException';
					$error = class_exists( $class ) ? new $class( 'ultra_max_invalid_' . esc_attr( $name ), esc_html( $problem ), 400 ) : null;
					throw $error instanceof \Throwable ? $error : new \RuntimeException( esc_html( $problem ) );
				}
				$clean = self::latin( $value );
				$order->{"set_{$group}_{$name}"}( is_string( $clean ) ? $clean : $value );
			}
		}
	}
}
