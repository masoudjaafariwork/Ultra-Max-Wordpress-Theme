<?php
/**
 * Contact and newsletter forms (HOME-12, HDR-11).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Store;

/**
 * Receives the contact and newsletter forms. Every submission needs the
 * form's security token, an empty spam trap field and at most five sends a
 * minute from one address. Contact messages are emailed to the store;
 * newsletter emails or phone numbers are kept in a list the store can
 * export, and the SMS module of phase 5 will use.
 */
final class Forms {

	public const ACTION_CONTACT    = 'ultra_max_contact';
	public const ACTION_NEWSLETTER = 'ultra_max_newsletter';
	public const SUBSCRIBERS       = 'ultra_max_subscribers';
	public const MAX_SUBSCRIBERS   = 20000;

	/**
	 * Registers the handlers.
	 */
	public static function register(): void {
		foreach ( array( self::ACTION_CONTACT, self::ACTION_NEWSLETTER ) as $action ) {
			add_action( 'admin_post_' . $action, array( self::class, 'handle' ) );
			add_action( 'admin_post_nopriv_' . $action, array( self::class, 'handle' ) );
		}
	}

	/**
	 * Address the forms post to.
	 */
	public static function action_url(): string {
		return admin_url( 'admin-post.php' );
	}

	/**
	 * Hidden fields of a form.
	 *
	 * @param string $action Action.
	 * @param string $form   Form id, to show the answer under the right form.
	 * @return array<string, string>
	 */
	public static function hidden( string $action, string $form ): array {
		return array(
			'action'    => $action,
			'um_form'   => $form,
			'um_nonce'  => wp_create_nonce( $action ),
			'um_return' => self::current_url(),
		);
	}

	/**
	 * Address of the current page.
	 */
	private static function current_url(): string {
		$uri = isset( $_SERVER['REQUEST_URI'] ) && is_string( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/';
		return home_url( remove_query_arg( array( 'um_form', 'um_status' ), $uri ) );
	}

	/**
	 * The answer shown under a form after a send without JavaScript.
	 *
	 * @param string $form Form id.
	 * @return array{tone: 'success'|'danger', text: string}|null
	 */
	public static function message( string $form ): ?array {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Only reads which message to show.
		$which  = isset( $_GET['um_form'] ) && is_string( $_GET['um_form'] ) ? sanitize_key( wp_unslash( $_GET['um_form'] ) ) : '';
		$status = isset( $_GET['um_status'] ) && is_string( $_GET['um_status'] ) ? sanitize_key( wp_unslash( $_GET['um_status'] ) ) : '';
		// phpcs:enable
		if ( sanitize_key( $form ) !== $which || '' === $status ) {
			return null;
		}
		$messages = self::messages();
		return array(
			'tone' => in_array( $status, array( 'sent', 'subscribed' ), true ) ? 'success' : 'danger',
			'text' => $messages[ $status ] ?? $messages['error'],
		);
	}

	/**
	 * Every answer.
	 *
	 * @return array<string, string>
	 */
	public static function messages(): array {
		return array(
			'sent'       => __( 'Thank you. Your message was sent and we will answer soon.', 'ultra-max-core' ),
			'subscribed' => __( 'Thank you. You will hear about our offers first.', 'ultra-max-core' ),
			'invalid'    => __( 'Please fill in the fields correctly.', 'ultra-max-core' ),
			'busy'       => __( 'Too many tries. Please wait a minute and try again.', 'ultra-max-core' ),
			'error'      => __( 'The form could not be sent. Please try again.', 'ultra-max-core' ),
		);
	}

	/**
	 * A posted text field.
	 *
	 * @param string $name      Field name.
	 * @param bool   $multiline Whether line breaks are kept.
	 */
	private static function field( string $name, bool $multiline = false ): string {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Checked in process().
		if ( ! isset( $_POST[ $name ] ) || ! is_string( $_POST[ $name ] ) ) {
			return '';
		}
		return $multiline ? sanitize_textarea_field( wp_unslash( $_POST[ $name ] ) ) : sanitize_text_field( wp_unslash( $_POST[ $name ] ) );
		// phpcs:enable
	}

	/**
	 * Handles a submission.
	 */
	public static function handle(): void {
		$action = self::field( 'action' );
		$form   = sanitize_key( self::field( 'um_form' ) );
		$status = self::process( $action );
		if ( '' !== self::field( 'um_ajax' ) ) {
			$messages = self::messages();
			$answer   = array( 'message' => $messages[ $status ] ?? $messages['error'] );
			if ( in_array( $status, array( 'sent', 'subscribed' ), true ) ) {
				wp_send_json_success( $answer );
			}
			wp_send_json_error( $answer, 'invalid' === $status ? 400 : 429 );
		}
		$back = wp_validate_redirect( self::field( 'um_return' ), home_url( '/' ) );
		wp_safe_redirect(
			add_query_arg(
				array(
					'um_form'   => $form,
					'um_status' => $status,
				),
				$back
			)
		);
		exit;
	}

	/**
	 * Checks and stores or sends a submission.
	 *
	 * @param string $action Action.
	 * @return string Status: sent, subscribed, invalid, busy or error.
	 */
	public static function process( string $action ): string {
		if ( ! wp_verify_nonce( self::field( 'um_nonce' ), $action ) || '' !== self::field( 'um_website' ) ) {
			return 'error';
		}
		if ( ! self::allowed() ) {
			return 'busy';
		}
		if ( self::ACTION_NEWSLETTER === $action ) {
			return self::subscribe( self::field( 'contact' ) );
		}
		if ( self::ACTION_CONTACT !== $action ) {
			return 'error';
		}
		$name    = self::field( 'name' );
		$contact = self::field( 'contact' );
		$message = self::field( 'message', true );
		if ( '' === $name || '' === $message || ( ! is_email( $contact ) && ! self::phone( $contact ) ) ) {
			return 'invalid';
		}
		$to = get_option( 'admin_email' );
		/* translators: 1: store name, 2: sender name. */
		$subject = sprintf( __( '[%1$s] New message from %2$s', 'ultra-max-core' ), get_bloginfo( 'name' ), $name );
		$body    = sprintf( "%s\n%s\n\n%s", $name, $contact, $message );
		$headers = is_email( $contact ) ? array( 'Reply-To: ' . $name . ' <' . $contact . '>' ) : array();
		return is_string( $to ) && wp_mail( $to, $subject, $body, $headers ) ? 'sent' : 'error';
	}

	/**
	 * Whether a text is an Iranian mobile number, in Persian or Latin digits.
	 *
	 * @param string $value Text.
	 */
	public static function phone( string $value ): bool {
		$digits = strtr( $value, array_combine( array( '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹' ), array( '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' ) ) );
		$digits = (string) preg_replace( '/[\s\-]/', '', $digits );
		return 1 === preg_match( '/^(\+98|0098|0)?9\d{9}$/', $digits );
	}

	/**
	 * Adds an email or phone number to the newsletter list.
	 *
	 * @param string $contact Email or phone.
	 * @return string Status.
	 */
	public static function subscribe( string $contact ): string {
		$contact = trim( $contact );
		if ( ! is_email( $contact ) && ! self::phone( $contact ) ) {
			return 'invalid';
		}
		$list = get_option( self::SUBSCRIBERS, array() );
		$list = is_array( $list ) ? $list : array();
		$key  = strtolower( $contact );
		if ( ! isset( $list[ $key ] ) ) {
			$list[ $key ] = time();
			if ( count( $list ) > self::MAX_SUBSCRIBERS ) {
				$list = array_slice( $list, -self::MAX_SUBSCRIBERS, null, true );
			}
			update_option( self::SUBSCRIBERS, $list, false );
		}
		return 'subscribed';
	}

	/**
	 * At most five sends a minute from one address.
	 */
	private static function allowed(): bool {
		$ip  = isset( $_SERVER['REMOTE_ADDR'] ) && is_string( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
		$key = 'um_form_' . md5( $ip );
		$hit = get_transient( $key );
		$hit = is_numeric( $hit ) ? (int) $hit : 0;
		if ( $hit >= 5 ) {
			return false;
		}
		set_transient( $key, $hit + 1, MINUTE_IN_SECONDS );
		return true;
	}
}
