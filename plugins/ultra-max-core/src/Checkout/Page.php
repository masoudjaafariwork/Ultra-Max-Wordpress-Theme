<?php
/**
 * The classic checkout in our design (CHK-01 to CHK-09, BLD-06).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Checkout;

use UltraMax\Core\Builder\Builder;
use UltraMax\Core\Cart\Limits;
use UltraMax\Core\Cart\Totals;
use UltraMax\Core\Lib\Settings\Store;
use UltraMax\Core\Lib\UI\Alert;
use UltraMax\Core\Lib\UI\Button;
use UltraMax\Core\Lib\UI\CartSummary;
use UltraMax\Core\Lib\UI\CheckoutBar;
use UltraMax\Core\Lib\UI\CheckoutSection;
use UltraMax\Core\Lib\UI\CheckoutSteps;
use UltraMax\Core\Lib\UI\CouponForm;
use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Lib\UI\ItemThumbs;
use UltraMax\Core\Lib\UI\PaymentMethods;
use UltraMax\Core\Lib\UI\ShippingOptions;
use UltraMax\Core\Lib\UI\UI;
use UltraMax\Core\Product\Delivery;
use UltraMax\Core\Store\Format;

/**
 * One renderer for every piece of the classic checkout: the built-in page
 * (design/checkout.html), the checkout widgets (BLD-06) and the pieces
 * WooCommerce's script asks for again whenever the address or a method
 * changes (`woocommerce_update_order_review_fragments`).
 *
 * The form stays WooCommerce's: its fields come from woocommerce_form_field()
 * with WooCommerce's names, its hooks fire where its template fires them,
 * and WooCommerce's script sends it. So payment gateways, field plugins and
 * order attribution work as with WooCommerce's own checkout.
 */
final class Page {

	public const PARTS = array( 'steps', 'address', 'buyer', 'shipping', 'notes', 'payment', 'coupon', 'summary', 'bar' );

	/**
	 * The current checkout.
	 */
	private static function checkout(): \WC_Checkout {
		return WC()->checkout();
	}

	/**
	 * Whether the checkout goes in steps (CHK-01).
	 */
	public static function steps(): bool {
		return 'single' !== Store::string( 'checkout_mode' );
	}

	/**
	 * The form, with a checkout layout designed with Elementor or the
	 * built-in arrangement inside.
	 */
	public static function form(): Html {
		$left = array(
			'desktop' => true,
			'tablet'  => true,
			'mobile'  => true,
		);
		ob_start();
		if ( Builder::enabled() ) {
			/** This filter is documented in themes/ultra-max/src/Template.php */
			$covered = apply_filters( 'ultra_max_do_location', array_map( '__return_false', $left ), Checkout::LOCATION );
			$covered = is_array( $covered ) ? $covered : array();
			foreach ( array_keys( $left ) as $device ) {
				$left[ $device ] = true !== ( $covered[ $device ] ?? false );
			}
		}
		$parts = array( Html::wp( (string) ob_get_clean() ) );
		if ( in_array( true, $left, true ) ) {
			$parts[] = self::arrangement( UI::visibility( $left ) );
		}
		return Html::trusted(
			sprintf(
				'<form name="checkout" method="post" class="checkout woocommerce-checkout um-checkout-form grid min-w-0 gap-5" action="%1$s" enctype="multipart/form-data" aria-label="%2$s" data-um-component="checkout" data-mode="%3$s" data-messages="%4$s"><script type="application/json" data-um-cities>%5$s</script>%6$s</form>',
				esc_url( wc_get_checkout_url() ),
				esc_attr__( 'Checkout', 'ultra-max-core' ),
				self::steps() ? 'steps' : 'single',
				esc_attr( (string) wp_json_encode( self::messages() ) ),
				Store::bool( 'checkout_city_list' ) ? (string) wp_json_encode( Cities::all(), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG ) : '{}',
				Html::from( $parts )
			)
		);
	}

	/**
	 * Texts of the checkout behaviour.
	 *
	 * @return array<string, string>
	 */
	public static function messages(): array {
		return array(
			'required'      => __( 'Fill in this field.', 'ultra-max-core' ),
			'mobile'        => Fields::address_problem( 'phone', 'x' ),
			'postcode'      => Fields::address_problem( 'postcode', 'x' ),
			'national_code' => __( 'The national code is not valid. Check its ten digits again.', 'ultra-max-core' ),
			'company_id'    => __( 'The national id of the company is not valid. It has eleven digits.', 'ultra-max-core' ),
			'economic_code' => __( 'The economic code is not valid. Enter its digits only.', 'ultra-max-core' ),
			'digits'        => __( 'Enter digits only.', 'ultra-max-core' ),
			'choose_city'   => __( 'Choose a city', 'ultra-max-core' ),
			'coupon_empty'  => __( 'Enter a discount code.', 'ultra-max-core' ),
		);
	}

	/**
	 * The built-in arrangement of design/checkout.html.
	 *
	 * @param string $classes Visibility classes for the devices it covers.
	 */
	public static function arrangement( string $classes = '' ): Html {
		$main = array(
			self::part( 'address' ),
			self::part( 'buyer' ),
			self::part( 'shipping' ),
			self::part( 'notes' ),
			self::part( 'payment' ),
			self::part( 'coupon' ),
		);
		return Html::trusted(
			sprintf(
				'<div class="%1$s">%2$s<div class="grid min-w-0 items-start gap-5 lg:grid-cols-[minmax(0,1fr)_340px]"><div class="grid min-w-0 gap-4">%3$s</div><div id="order_review" class="woocommerce-checkout-review-order min-w-0 lg:sticky lg:top-[calc(var(--wp-admin--admin-bar--height,0px)+var(--um-header-offset,0px)+16px)]">%4$s</div></div>%5$s</div>',
				esc_attr( trim( 'grid min-w-0 gap-5 ' . $classes ) ),
				self::part( 'steps' ),
				Html::from( $main ),
				self::part( 'summary' ),
				self::part( 'bar' )
			)
		);
	}

	/**
	 * One named part, wrapped so WooCommerce's script can draw it again.
	 *
	 * @param string $name Part name, one of PARTS.
	 */
	public static function part( string $name ): Html {
		$inner = self::draw( $name );
		if ( $inner->is_empty() ) {
			return Html::none();
		}
		// The summary and the payment methods are found by WooCommerce's own classes.
		if ( in_array( $name, array( 'summary', 'payment' ), true ) ) {
			return $inner;
		}
		return Html::trusted( sprintf( '<div class="min-w-0" data-um-checkout-part="%1$s">%2$s</div>', esc_attr( $name ), $inner ) );
	}

	/**
	 * The inside of a part.
	 *
	 * @param string $name Part name.
	 */
	public static function draw( string $name ): Html {
		return match ( $name ) {
			'steps'    => self::steps_bar(),
			'address'  => self::address(),
			'buyer'    => self::buyer(),
			'shipping' => self::shipping(),
			'notes'    => self::notes(),
			'payment'  => self::payment_section(),
			'coupon'   => self::coupon(),
			'summary'  => self::summary(),
			'bar'      => self::bar(),
			default    => Html::none(),
		};
	}

	/**
	 * Why the cart cannot be ordered, with the way back to the cart.
	 */
	public static function cart_errors(): Html {
		$reasons = array();
		foreach ( wc_get_notices( 'error' ) as $notice ) {
			$text = is_array( $notice ) && is_string( $notice['notice'] ?? null ) ? wp_strip_all_tags( $notice['notice'] ) : '';
			if ( '' !== $text ) {
				$reasons[] = Alert::render(
					array(
						'tone'     => 'warning',
						'children' => $text,
					)
				);
			}
		}
		wc_clear_notices();
		return Html::trusted(
			sprintf(
				'<div class="um-checkout-page grid min-w-0 justify-items-start gap-3">%1$s%2$s</div>',
				Html::from( $reasons ),
				Button::render(
					array(
						'children' => __( 'Back to the cart', 'ultra-max-core' ),
						'href'     => wc_get_cart_url(),
					)
				)
			)
		);
	}

	/**
	 * The steps above the form (CHK-01).
	 */
	public static function steps_bar(): Html {
		if ( ! self::steps() ) {
			return Html::none();
		}
		return CheckoutSteps::render(
			array(
				'label' => __( 'Checkout steps', 'ultra-max-core' ),
				'steps' => array(
					array(
						'key'   => 'cart',
						'label' => __( 'Cart', 'ultra-max-core' ),
						'icon'  => 'cart',
						'state' => 'done',
						'href'  => wc_get_cart_url(),
					),
					array(
						'key'   => 'shipping',
						'label' => __( 'Shipping details', 'ultra-max-core' ),
						'icon'  => 'truck',
						'state' => 'current',
					),
					array(
						'key'   => 'payment',
						'label' => __( 'Payment', 'ultra-max-core' ),
						'icon'  => 'card',
						'state' => 'todo',
					),
				),
			)
		);
	}

	/**
	 * Prints WooCommerce fields as markup.
	 *
	 * @param array<mixed> $fields Fields by key.
	 */
	private static function fields( array $fields ): Html {
		$checkout = self::checkout();
		$checks   = array(
			'billing_phone'     => 'mobile',
			'shipping_phone'    => 'mobile',
			'billing_postcode'  => 'postcode',
			'shipping_postcode' => 'postcode',
		);
		foreach ( Fields::own() as $spec ) {
			if ( '' !== $spec['check'] ) {
				$checks[ Fields::classic_key( $spec['id'] ) ] = $spec['check'];
			}
		}
		ob_start();
		foreach ( $fields as $key => $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}
			$key = (string) $key;
			if ( isset( $checks[ $key ] ) ) {
				$field['custom_attributes']                  = is_array( $field['custom_attributes'] ?? null ) ? $field['custom_attributes'] : array();
				$field['custom_attributes']['data-um-check'] = $checks[ $key ];
			}
			woocommerce_form_field( $key, $field, $checkout->get_value( $key ) );
		}
		$markup = (string) ob_get_clean();
		return '' === trim( $markup ) ? Html::none() : Html::trusted( '<div class="um-fields">' . $markup . '</div>' );
	}

	/**
	 * The billing fields of one place.
	 *
	 * @param string $place address, contact or order.
	 * @return array<string, array<mixed>>
	 */
	private static function group( string $place ): array {
		$fields = self::checkout()->get_checkout_fields( 'order' === $place ? 'order' : 'billing' );
		$out    = array();
		foreach ( is_array( $fields ) ? $fields : array() as $key => $field ) {
			$key = (string) $key;
			if ( ! is_array( $field ) ) {
				continue;
			}
			$class = is_array( $field['class'] ?? null ) ? $field['class'] : array();
			$ours  = str_starts_with( $key, 'um_' );
			$where = match ( true ) {
				'order' === $place                                   => 'order',
				$ours && in_array( 'um-contact-field', $class, true ) => 'contact',
				$ours && in_array( 'um-address-field', $class, true ) => 'address',
				'billing_email' === $key                             => 'contact',
				default                                              => 'address',
			};
			if ( $where === $place ) {
				$out[ $key ] = $field;
			}
		}
		return $out;
	}

	/**
	 * The delivery address (CHK-02, CHK-03): the saved addresses, then the
	 * address fields; a different shipping address and account creation
	 * as WooCommerce has them.
	 */
	public static function address(): Html {
		$checkout = self::checkout();
		ob_start();
		/** This action is documented in WooCommerce's checkout/form-checkout.php template. */
		do_action( 'woocommerce_checkout_before_customer_details' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce's own hook.
		/** This action is documented in WooCommerce's checkout/form-billing.php template. */
		do_action( 'woocommerce_before_checkout_billing_form', $checkout ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce's own hook.
		$before = Html::wp( (string) ob_get_clean() );
		$book   = AddressBook::cards( AddressBook::current() );
		$fields = self::fields( self::group( 'address' ) );
		ob_start();
		/** This action is documented in WooCommerce's checkout/form-billing.php template. */
		do_action( 'woocommerce_after_checkout_billing_form', $checkout ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce's own hook.
		$after = Html::wp( (string) ob_get_clean() );
		$edit  = $book->is_empty() ? null : Button::render(
			array(
				'children' => __( 'Edit the address', 'ultra-max-core' ),
				'variant'  => 'ghost',
				'size'     => 'sm',
				'attrs'    => array( 'data-um-address-edit' => true ),
			)
		);
		$props = array(
			'title'    => __( 'Delivery address', 'ultra-max-core' ),
			'step'     => 'shipping',
			'id'       => 'um-checkout-address',
			'class'    => 'woocommerce-billing-fields',
			'children' => array(
				$before,
				$book,
				Html::trusted( sprintf( '<div class="um-address-fields%1$s">%2$s</div>', $book->is_empty() ? '' : ' mt-4', $fields ) ),
				self::ship_elsewhere(),
				$after,
				self::account(),
			),
		);
		if ( null !== $edit ) {
			$props['action'] = $edit;
		}
		return CheckoutSection::render( $props );
	}

	/**
	 * WooCommerce's "ship to a different address" box, when the store
	 * allows it.
	 */
	private static function ship_elsewhere(): Html {
		if ( ! WC()->cart->needs_shipping_address() || wc_ship_to_billing_address_only() ) {
			return Html::none();
		}
		$checkout = self::checkout();
		/** This filter is documented in WooCommerce's checkout/form-shipping.php template. */
		$checked = apply_filters( 'woocommerce_ship_to_different_address_checked', 'shipping' === get_option( 'woocommerce_ship_to_destination' ) ? 1 : 0 ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce's own filter.
		ob_start();
		/** This action is documented in WooCommerce's checkout/form-shipping.php template. */
		do_action( 'woocommerce_before_checkout_shipping_form', $checkout ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce's own hook.
		$before = (string) ob_get_clean();
		ob_start();
		/** This action is documented in WooCommerce's checkout/form-shipping.php template. */
		do_action( 'woocommerce_after_checkout_shipping_form', $checkout ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce's own hook.
		$after  = (string) ob_get_clean();
		$fields = $checkout->get_checkout_fields( 'shipping' );
		return Html::trusted(
			sprintf(
				'<div class="woocommerce-shipping-fields mt-4 grid gap-4"><p id="ship-to-different-address" class="form-row m-0"><label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox"><input id="ship-to-different-address-checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" type="checkbox" name="ship_to_different_address" value="1"%1$s><span>%2$s</span></label></p><div class="shipping_address grid gap-4">%3$s%4$s%5$s</div></div>',
				checked( (bool) $checked, true, false ),
				esc_html__( 'Ship to a different address', 'ultra-max-core' ),
				$before,
				self::fields( is_array( $fields ) ? $fields : array() ),
				$after
			)
		);
	}

	/**
	 * WooCommerce's account creation for guests, when the store allows it.
	 */
	private static function account(): Html {
		$checkout = self::checkout();
		if ( is_user_logged_in() || ! $checkout->is_registration_enabled() ) {
			return Html::none();
		}
		$fields = $checkout->get_checkout_fields( 'account' );
		ob_start();
		/** This action is documented in WooCommerce's checkout/form-billing.php template. */
		do_action( 'woocommerce_before_checkout_registration_form', $checkout ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce's own hook.
		$before = (string) ob_get_clean();
		ob_start();
		/** This action is documented in WooCommerce's checkout/form-billing.php template. */
		do_action( 'woocommerce_after_checkout_registration_form', $checkout ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce's own hook.
		$after  = (string) ob_get_clean();
		$create = '';
		if ( ! $checkout->is_registration_required() ) {
			/** This filter is documented in WooCommerce's checkout/form-billing.php template. */
			$checked = (bool) apply_filters( 'woocommerce_create_account_default_checked', false ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce's own filter.
			$create  = sprintf(
				'<p class="form-row form-row-wide create-account m-0"><label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox"><input class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" id="createaccount" type="checkbox" name="createaccount" value="1"%1$s><span>%2$s</span></label></p>',
				checked( ( true === $checkout->get_value( 'createaccount' ) ) || $checked, true, false ),
				esc_html__( 'Create an account', 'ultra-max-core' )
			);
		}
		return Html::trusted(
			sprintf(
				'<div class="woocommerce-account-fields mt-4 grid gap-4">%1$s%2$s<div class="create-account grid gap-4">%3$s</div>%4$s</div>',
				$create,
				$before,
				is_array( $fields ) ? self::fields( $fields ) : '',
				$after
			)
		);
	}

	/**
	 * The buyer and invoice details (CHK-05): buyer type, national code or
	 * company details, email, and the store's contact fields.
	 */
	public static function buyer(): Html {
		$fields = self::fields( self::group( 'contact' ) );
		if ( $fields->is_empty() ) {
			return Html::none();
		}
		return CheckoutSection::render(
			array(
				'title'    => __( 'Buyer and invoice', 'ultra-max-core' ),
				'step'     => 'shipping',
				'id'       => 'um-checkout-buyer',
				'children' => $fields,
			)
		);
	}

	/**
	 * The working days of a shipping method, from its delivery fields.
	 *
	 * @param \WC_Shipping_Rate $rate    Rate.
	 * @param int               $prepare Days the cart needs before it leaves.
	 */
	private static function arrival( \WC_Shipping_Rate $rate, int $prepare ): string {
		$instance = $rate->get_instance_id();
		$method   = $instance > 0 ? \WC_Shipping_Zones::get_shipping_method( $instance ) : false;
		if ( ! $method instanceof \WC_Shipping_Method ) {
			return '';
		}
		$min = $method->get_option( Delivery::MIN, '' );
		$max = $method->get_option( Delivery::MAX, '' );
		if ( ! is_numeric( $min ) && ! is_numeric( $max ) ) {
			return '';
		}
		$min      = is_numeric( $min ) ? (int) $min : (int) $max;
		$max      = is_numeric( $max ) ? max( $min, (int) $max ) : $min;
		$today    = new \DateTimeImmutable( 'now', wp_timezone() );
		$holidays = Delivery::holidays();
		/* translators: %s: delivery dates. */
		return sprintf( __( 'Delivery %s', 'ultra-max-core' ), Delivery::span( Delivery::add_working_days( $today, $prepare + $min, $holidays ), Delivery::add_working_days( $today, $prepare + $max, $holidays ) ) );
	}

	/**
	 * The shipping methods of every parcel (SHIP-01, SHIP-02) and the
	 * pictures of what ships (CHK-09).
	 */
	public static function shipping(): Html {
		$cart = WC()->cart;
		if ( ! $cart->needs_shipping() ) {
			return Html::none();
		}
		$prepare = 0;
		foreach ( $cart->get_cart() as $item ) {
			$product = is_array( $item ) ? ( $item['data'] ?? null ) : null;
			if ( $product instanceof \WC_Product ) {
				$prepare = max( $prepare, Delivery::preparation( $product ) );
			}
		}
		$chosen   = WC()->session->get( 'chosen_shipping_methods', array() );
		$chosen   = is_array( $chosen ) ? $chosen : array();
		$tax      = $cart->display_prices_including_tax();
		$sections = array();
		foreach ( WC()->shipping()->get_packages() as $index => $package ) {
			$rates   = is_array( $package['rates'] ?? null ) ? $package['rates'] : array();
			$current = is_string( $chosen[ $index ] ?? null ) ? $chosen[ $index ] : (string) array_key_first( $rates );
			$options = array();
			foreach ( $rates as $rate ) {
				if ( ! $rate instanceof \WC_Shipping_Rate ) {
					continue;
				}
				$cost      = (float) $rate->get_cost() + ( $tax ? (float) $rate->get_shipping_tax() : 0.0 );
				$options[] = array(
					'value'  => $rate->get_id(),
					'title'  => wp_strip_all_tags( $rate->get_label() ),
					'price'  => $cost > 0 ? Totals::money( $cost ) : __( 'Free', 'ultra-max-core' ),
					'note'   => self::arrival( $rate, $prepare ),
					'icon'   => match ( $rate->get_method_id() ) {
						'um_courier'   => 'zap',
						'local_pickup', 'pickup_location' => 'store',
						default        => 'truck',
					},
					'chosen' => $rate->get_id() === $current,
				);
			}
			$sections[] = ShippingOptions::render(
				array(
					'index'   => (int) $index,
					'options' => $options,
					'label'   => __( 'Shipping methods', 'ultra-max-core' ),
					'empty'   => WC()->customer->has_calculated_shipping()
						? __( 'No shipping method reaches this address. Check the province and city, or contact us.', 'ultra-max-core' )
						: __( 'Enter your address to see the shipping methods.', 'ultra-max-core' ),
				)
			);
		}
		return CheckoutSection::render(
			array(
				'title'    => __( 'Shipping method', 'ultra-max-core' ),
				'step'     => 'shipping',
				'id'       => 'um-checkout-shipping',
				'children' => array( Html::from( $sections ), self::thumbs() ),
			)
		);
	}

	/**
	 * The pictures of the cart's products (CHK-09).
	 */
	public static function thumbs(): Html {
		$cart  = WC()->cart;
		$items = array();
		foreach ( $cart->get_cart() as $item ) {
			$product = is_array( $item ) ? ( $item['data'] ?? null ) : null;
			if ( ! $product instanceof \WC_Product ) {
				continue;
			}
			$quantity = is_numeric( $item['quantity'] ?? null ) ? (int) $item['quantity'] : 1;
			$items[]  = array(
				'image'         => Html::wp( $product->get_image( 'woocommerce_gallery_thumbnail', array( 'alt' => '' ) ) ),
				'title'         => wp_strip_all_tags( $product->get_name() ),
				'quantity'      => $quantity,
				'quantity_text' => Format::number( $quantity ),
			);
		}
		$count = (int) $cart->get_cart_contents_count();
		return ItemThumbs::render(
			array(
				/* translators: %s: number of items. */
				'title' => sprintf( _n( 'Your parcel (%s item)', 'Your parcel (%s items)', $count, 'ultra-max-core' ), Format::number( $count ) ),
				'items' => $items,
				'class' => 'mt-4',
			)
		);
	}

	/**
	 * The order notes, the store's order fields and the marketing consent (CHK-08).
	 */
	public static function notes(): Html {
		$fields = self::fields( self::group( 'order' ) );
		ob_start();
		/** This action is documented in WooCommerce's checkout/form-shipping.php template. */
		do_action( 'woocommerce_before_order_notes', self::checkout() ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce's own hook.
		$before = Html::wp( (string) ob_get_clean() );
		ob_start();
		/** This action is documented in WooCommerce's checkout/form-shipping.php template. */
		do_action( 'woocommerce_after_order_notes', self::checkout() ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce's own hook.
		/** This action is documented in WooCommerce's checkout/form-checkout.php template. */
		do_action( 'woocommerce_checkout_after_customer_details' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce's own hook.
		$after = Html::wp( (string) ob_get_clean() );
		if ( $fields->is_empty() && $before->is_empty() && $after->is_empty() ) {
			return Html::none();
		}
		return CheckoutSection::render(
			array(
				'title'    => __( 'Notes', 'ultra-max-core' ),
				'step'     => 'shipping',
				'id'       => 'um-checkout-notes',
				'class'    => 'woocommerce-additional-fields',
				'children' => array( $before, $fields, $after ),
			)
		);
	}

	/**
	 * The payment methods as WooCommerce's script expects them.
	 */
	public static function payment(): Html {
		$cart     = WC()->cart;
		$gateways = $cart->needs_payment() ? WC()->payment_gateways()->get_available_payment_gateways() : array();
		WC()->payment_gateways()->set_current_gateway( $gateways );
		$methods = array();
		foreach ( $gateways as $gateway ) {
			if ( ! $gateway instanceof \WC_Payment_Gateway ) {
				continue;
			}
			$method = array(
				'id'     => (string) $gateway->id,
				'title'  => Html::wp( $gateway->get_title() . ' ' . $gateway->get_icon() ),
				'chosen' => (bool) $gateway->chosen,
				'button' => (string) $gateway->order_button_text,
			);
			if ( $gateway->has_fields() || '' !== $gateway->get_description() ) {
				ob_start();
				$gateway->payment_fields();
				$method['box'] = Html::wp( (string) ob_get_clean() );
			}
			$methods[] = $method;
		}
		ob_start();
		wc_get_template( 'checkout/terms.php' );
		$terms = (string) ob_get_clean();
		/** This filter is documented in WooCommerce's checkout/payment.php template. */
		$none = apply_filters( 'woocommerce_no_available_payment_methods_message', WC()->customer->get_billing_country() ? __( 'No payment method is available for this order. Please contact us.', 'ultra-max-core' ) : __( 'Fill in your details above to see the payment methods.', 'ultra-max-core' ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce's own filter.
		return PaymentMethods::render(
			array(
				'methods'       => $methods,
				'label'         => __( 'Payment methods', 'ultra-max-core' ),
				'needs_payment' => $cart->needs_payment(),
				'empty'         => Html::trusted( sprintf( '<p class="woocommerce-info m-0">%s</p>', esc_html( is_string( $none ) ? $none : '' ) ) ),
				'after'         => Html::trusted(
					sprintf(
						'<div class="form-row place-order grid gap-3"><noscript><button type="submit" name="woocommerce_checkout_update_totals" value="1">%1$s</button></noscript>%2$s%3$s</div>',
						esc_html__( 'Update totals', 'ultra-max-core' ),
						$terms,
						wp_nonce_field( 'woocommerce-process_checkout', 'woocommerce-process-checkout-nonce', true, false )
					)
				),
			)
		);
	}

	/**
	 * The box of the payment methods.
	 */
	public static function payment_section(): Html {
		ob_start();
		if ( ! wp_doing_ajax() ) {
			/** This action is documented in WooCommerce's checkout/payment.php template. */
			do_action( 'woocommerce_review_order_before_payment' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce's own hook.
		}
		$before = Html::wp( (string) ob_get_clean() );
		ob_start();
		if ( ! wp_doing_ajax() ) {
			/** This action is documented in WooCommerce's checkout/payment.php template. */
			do_action( 'woocommerce_review_order_after_payment' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce's own hook.
		}
		$after = Html::wp( (string) ob_get_clean() );
		return CheckoutSection::render(
			array(
				'title'    => __( 'Payment method', 'ultra-max-core' ),
				'step'     => 'payment',
				'id'       => 'um-checkout-payment',
				'children' => array( $before, self::payment(), $after ),
			)
		);
	}

	/**
	 * The discount code, sent in place by the checkout behaviour.
	 */
	public static function coupon(): Html {
		$cart = WC()->cart;
		if ( ! wc_coupons_enabled() ) {
			return Html::none();
		}
		$chips = array();
		foreach ( $cart->get_coupons() as $code => $coupon ) {
			$chips[] = array(
				'code'   => (string) $code,
				'remove' => add_query_arg( 'remove_coupon', rawurlencode( (string) $code ), wc_get_checkout_url() ),
				'amount' => '-' . Totals::money( (float) $cart->get_coupon_discount_amount( (string) $code, ! $cart->display_prices_including_tax() ) ),
			);
		}
		return CheckoutSection::render(
			array(
				'title'    => __( 'Discount code', 'ultra-max-core' ),
				'step'     => 'payment',
				'id'       => 'um-checkout-coupon',
				'children' => CouponForm::render(
					array(
						'id'      => 'um-checkout-coupon-form',
						'label'   => __( 'Discount code', 'ultra-max-core' ),
						'submit'  => __( 'Apply', 'ultra-max-core' ),
						/* translators: %s: coupon code. */
						'remove'  => __( 'Remove code %s', 'ultra-max-core' ),
						'coupons' => $chips,
						'inline'  => true,
					)
				),
			)
		);
	}

	/**
	 * The rows of the order summary.
	 *
	 * @return list<array{label: string|Html, value: string|Html, tone?: 'save'|'total'|'strong', key?: string}>
	 */
	public static function rows(): array {
		$cart    = WC()->cart;
		$amounts = Totals::of( $cart );
		$tax     = $cart->display_prices_including_tax();
		$rows    = array(
			array(
				/* translators: %s: number of items. */
				'label' => sprintf( __( 'Items (%s)', 'ultra-max-core' ), Format::number( $amounts['count'] ) ),
				'value' => Totals::money( $amounts['regular'] ),
				'key'   => 'regular',
			),
			array(
				'label' => __( 'Cart total', 'ultra-max-core' ),
				'value' => Totals::money( $amounts['total'] ),
				'key'   => 'total',
			),
		);
		foreach ( $cart->get_fees() as $fee ) {
			$amount = (float) $fee->total + ( $tax ? (float) $fee->tax : 0.0 );
			if ( $amount > 0 ) {
				$rows[] = array(
					'label' => wp_strip_all_tags( (string) $fee->name ),
					'value' => Totals::money( $amount ),
				);
			}
		}
		if ( $cart->needs_shipping() ) {
			$shipping = (float) $cart->get_shipping_total() + ( $tax ? (float) $cart->get_shipping_tax() : 0.0 );
			$known    = WC()->customer->has_calculated_shipping() && array() !== array_filter( (array) WC()->session->get( 'chosen_shipping_methods', array() ) );
			$rows[]   = array(
				'label' => __( 'Shipping', 'ultra-max-core' ),
				'value' => ! $known ? __( 'After the address', 'ultra-max-core' ) : ( $shipping > 0 ? Totals::money( $shipping ) : __( 'Free', 'ultra-max-core' ) ),
				'key'   => 'shipping',
			);
		}
		if ( wc_tax_enabled() && ! $tax && (float) $cart->get_taxes_total() > 0 ) {
			$rows[] = array(
				'label' => WC()->countries->tax_or_vat(),
				'value' => Totals::money( (float) $cart->get_taxes_total() ),
				'key'   => 'tax',
			);
		}
		if ( Store::bool( 'cart_savings' ) && $amounts['saving'] > 0 ) {
			$rows[] = array(
				'label' => __( 'Your saving', 'ultra-max-core' ),
				'value' => Totals::saving_text( $amounts ),
				'tone'  => 'save',
				'key'   => 'saving',
			);
		}
		$rows[] = array(
			'label' => __( 'To pay', 'ultra-max-core' ),
			'value' => Totals::money( (float) $cart->get_total( 'edit' ) ),
			'tone'  => 'total',
			'key'   => 'pay',
		);
		return $rows;
	}

	/**
	 * WooCommerce's order button, through its own filters.
	 *
	 * @param string $classes Extra classes.
	 */
	public static function place_button( string $classes = '' ): Html {
		/** This filter is documented in WooCommerce's checkout/payment.php template. */
		$text = apply_filters( 'woocommerce_order_button_text', __( 'Place order', 'ultra-max-core' ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce's own filter.
		$text = is_string( $text ) ? $text : __( 'Place order', 'ultra-max-core' );
		$html = (string) Button::render(
			array(
				'children' => $text,
				'type'     => 'submit',
				'size'     => 'lg',
				'block'    => true,
				'id'       => 'place_order',
				'class'    => trim( 'button alt ' . $classes ),
				'attrs'    => array(
					'name'       => 'woocommerce_checkout_place_order',
					'value'      => $text,
					'data-value' => $text,
				),
			)
		);
		/** This filter is documented in WooCommerce's checkout/payment.php template. */
		$filtered = apply_filters( 'woocommerce_order_button_html', $html ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce's own filter.
		return Html::wp( is_string( $filtered ) ? $filtered : $html );
	}

	/**
	 * The buttons of the summary: on, back and place the order.
	 *
	 * @param bool $bar The phone bar's smaller set.
	 * @return list<Html>
	 */
	private static function actions( bool $bar = false ): array {
		$blocked = '' !== Limits::problem();
		$out     = array();
		if ( self::steps() ) {
			$out[] = Button::render(
				array(
					'children' => $bar ? __( 'Continue', 'ultra-max-core' ) : __( 'Continue to payment', 'ultra-max-core' ),
					'size'     => 'lg',
					'block'    => true,
					'class'    => $bar ? '' : 'max-md:hidden',
					'disabled' => $blocked,
					'attrs'    => array(
						'data-um-next' => true,
						'data-um-only' => 'shipping',
					),
				)
			);
		}
		if ( $bar ) {
			$out[] = Html::trusted(
				sprintf(
					'<span class="contents"%1$s>%2$s</span>',
					self::steps() ? ' data-um-only="payment"' : '',
					Button::render(
						array(
							'children' => __( 'Place order', 'ultra-max-core' ),
							'type'     => 'submit',
							'size'     => 'lg',
							'block'    => true,
							'disabled' => $blocked,
							'attrs'    => array( 'name' => 'woocommerce_checkout_place_order' ),
						)
					)
				)
			);
			return $out;
		}
		ob_start();
		/** This action is documented in WooCommerce's checkout/payment.php template. */
		do_action( 'woocommerce_review_order_before_submit' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce's own hook.
		$before = Html::wp( (string) ob_get_clean() );
		ob_start();
		/** This action is documented in WooCommerce's checkout/payment.php template. */
		do_action( 'woocommerce_review_order_after_submit' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce's own hook.
		$after = Html::wp( (string) ob_get_clean() );
		$out[] = Html::trusted(
			sprintf(
				'<div class="grid gap-2 max-md:hidden"%1$s>%2$s%3$s%4$s</div>',
				self::steps() ? ' data-um-only="payment"' : '',
				$before,
				$blocked ? Button::render(
					array(
						'children' => __( 'Place order', 'ultra-max-core' ),
						'size'     => 'lg',
						'block'    => true,
						'disabled' => true,
					)
				) : self::place_button(),
				$after
			)
		);
		if ( self::steps() ) {
			$out[] = Button::render(
				array(
					'children' => __( 'Back to shipping details', 'ultra-max-core' ),
					'variant'  => 'ghost',
					'size'     => 'sm',
					'block'    => true,
					'attrs'    => array(
						'data-um-back' => true,
						'data-um-only' => 'payment',
					),
				)
			);
		}
		return $out;
	}

	/**
	 * The order summary beside the form; WooCommerce's script draws it
	 * again after every change.
	 */
	public static function summary(): Html {
		ob_start();
		/** This action is documented in WooCommerce's checkout/form-checkout.php template. */
		do_action( 'woocommerce_checkout_before_order_review' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce's own hook.
		$before = Html::wp( (string) ob_get_clean() );
		ob_start();
		/** This action is documented in WooCommerce's checkout/form-checkout.php template. */
		do_action( 'woocommerce_checkout_after_order_review' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce's own hook.
		$after = Html::wp( (string) ob_get_clean() );
		return CartSummary::render(
			array(
				'rows'    => self::rows(),
				'extras'  => array( $before, Totals::free_bar( WC()->cart ), \UltraMax\Core\Cart\Page::limit_alert() ),
				'actions' => array_merge( self::actions(), array( $after ) ),
				'note'    => array(
					'icon' => 'shield',
					'text' => __( 'Card details are entered only on the bank\'s payment page.', 'ultra-max-core' ),
				),
				'sticky'  => false,
				'label'   => __( 'Your order', 'ultra-max-core' ),
				'class'   => 'woocommerce-checkout-review-order-table',
			)
		);
	}

	/**
	 * The phone bar with the amount to pay.
	 */
	public static function bar(): Html {
		return CheckoutBar::render(
			array(
				'label'  => __( 'To pay', 'ultra-max-core' ),
				'amount' => Totals::money_html( (float) WC()->cart->get_total( 'edit' ) ),
				'action' => Html::from( self::actions( true ) ),
			)
		);
	}

	/**
	 * The parts WooCommerce's script draws again after a change.
	 *
	 * @param mixed $fragments Fragments by selector.
	 * @return mixed
	 */
	public static function fragments( mixed $fragments ): mixed {
		if ( ! is_array( $fragments ) || ! Checkout::ours() ) {
			return $fragments;
		}
		$fragments['.woocommerce-checkout-review-order-table'] = (string) self::summary();
		$fragments['.woocommerce-checkout-payment']            = (string) self::payment();
		foreach ( array( 'shipping', 'coupon', 'bar' ) as $name ) {
			$fragments[ '[data-um-checkout-part="' . $name . '"]' ] = (string) self::part( $name );
		}
		return $fragments;
	}
}
