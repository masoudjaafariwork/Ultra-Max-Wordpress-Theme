<?php
/**
 * Product questions and answers (PDP-10).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Product;

use UltraMax\Core\Lib\Settings\Store;
use UltraMax\Core\Lib\UI\Alert;
use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Lib\UI\QuestionList;
use UltraMax\Core\Store\Format;

/**
 * Questions are comments of the product with the type `um_question`;
 * answers are replies to them, from the store in the admin (Products,
 * Questions) or from shoppers on the page. Both go through WordPress's own
 * comment checks: flood and spam protection, the disallowed words list,
 * and moderation.
 *
 * When an answer is approved, the shopper who asked and ticked "tell me"
 * gets an email; the `ultra_max_question_answered` action lets the SMS
 * module send a text too.
 *
 * @phpstan-import-type QuestionItem from QuestionList
 */
final class Questions {

	public const TYPE   = 'um_question';
	public const ANSWER = 'um_answer';
	public const ACTION = 'ultra_max_question';
	public const NOTIFY = 'um_notify';
	public const SENT   = '_um_notified';

	/**
	 * Hooks questions into WordPress and the product page.
	 */
	public static function register(): void {
		add_action( 'admin_post_' . self::ACTION, array( self::class, 'receive' ) );
		add_action( 'admin_post_nopriv_' . self::ACTION, array( self::class, 'receive' ) );
		add_action( 'wp_insert_comment', array( self::class, 'inserted' ), 20, 2 );
		add_action( 'transition_comment_status', array( self::class, 'transition' ), 20, 3 );
		add_filter( 'ultra_max_product_sections', array( self::class, 'section_item' ), 30, 2 );
		add_filter( 'ultra_max_product_heading', array( self::class, 'heading' ), 20, 2 );
		add_filter( 'comments_list_table_query_args', array( self::class, 'admin_list' ), 30 );
		add_action( 'admin_menu', array( self::class, 'menu' ) );
		add_filter( 'admin_comment_types_dropdown', array( self::class, 'types' ) );
		add_filter( 'preprocess_comment', array( self::class, 'answer_type' ), 30 );
	}

	/**
	 * A reply to a question, such as the store's reply from the comments
	 * screen, is an answer.
	 *
	 * @param mixed $data Comment data.
	 * @return mixed
	 */
	public static function answer_type( mixed $data ): mixed {
		$parent = is_array( $data ) && is_numeric( $data['comment_parent'] ?? null ) ? (int) $data['comment_parent'] : 0;
		if ( $parent > 0 && is_array( $data ) ) {
			$question = get_comment( $parent );
			if ( $question instanceof \WP_Comment && self::TYPE === $question->comment_type ) {
				$data['comment_type'] = self::ANSWER;
			}
		}
		return $data;
	}

	/**
	 * Whether a product takes questions.
	 *
	 * @param \WC_Product $product Product.
	 */
	public static function open( \WC_Product $product ): bool {
		return Store::bool( 'product_questions' ) && 'publish' === $product->get_status();
	}

	/**
	 * Whether the current visitor may ask.
	 */
	public static function can_ask(): bool {
		return is_user_logged_in() || Store::bool( 'product_questions_guests' );
	}

	/**
	 * Whether the current visitor may answer on the page.
	 */
	public static function can_answer(): bool {
		return is_user_logged_in() && ( current_user_can( 'manage_woocommerce' ) || Store::bool( 'product_answers_users' ) ); // phpcs:ignore WordPress.WP.Capabilities.Unknown -- WooCommerce's own capability.
	}

	/**
	 * Approved questions of a product, newest first.
	 *
	 * @param int $product_id Product id.
	 * @param int $number     How many.
	 * @param int $offset     How many to skip.
	 * @return list<\WP_Comment>
	 */
	public static function questions( int $product_id, int $number = 5, int $offset = 0 ): array {
		$found = get_comments(
			array(
				'post_id' => $product_id,
				'type'    => self::TYPE,
				'status'  => 'approve',
				'parent'  => 0,
				'number'  => $number,
				'offset'  => $offset,
				'orderby' => 'comment_date_gmt',
				'order'   => 'DESC',
			)
		);
		return is_array( $found ) ? array_values( array_filter( $found, static fn ( mixed $item ): bool => $item instanceof \WP_Comment ) ) : array();
	}

	/**
	 * How many approved questions a product has.
	 *
	 * @param int $product_id Product id.
	 */
	public static function count( int $product_id ): int {
		$count = get_comments(
			array(
				'post_id' => $product_id,
				'type'    => self::TYPE,
				'status'  => 'approve',
				'parent'  => 0,
				'count'   => true,
			)
		);
		return is_numeric( $count ) ? (int) $count : 0;
	}

	/**
	 * The badge of an answer: the store's, a buyer's, or none.
	 *
	 * @param \WP_Comment $answer     Answer.
	 * @param int         $product_id Product id.
	 * @return array{label: string, tone: 'brand'|'neutral'}|null
	 */
	private static function badge( \WP_Comment $answer, int $product_id ): ?array {
		$user = (int) $answer->user_id;
		if ( $user > 0 && user_can( $user, 'manage_woocommerce' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown -- WooCommerce's own capability.
			return array(
				'label' => __( 'Store', 'ultra-max-core' ),
				'tone'  => 'brand',
			);
		}
		if ( wc_customer_bought_product( $answer->comment_author_email, $user, $product_id ) ) {
			return array(
				'label' => __( 'Buyer', 'ultra-max-core' ),
				'tone'  => 'neutral',
			);
		}
		return null;
	}

	/**
	 * The props of one question.
	 *
	 * @param \WP_Comment $question Question.
	 * @return QuestionItem
	 */
	public static function item( \WP_Comment $question ): array {
		$id         = (int) $question->comment_ID;
		$product_id = (int) $question->comment_post_ID;
		$answers    = array();
		$replies    = get_comments(
			array(
				'parent' => $id,
				'status' => 'approve',
				'order'  => 'ASC',
			)
		);
		foreach ( is_array( $replies ) ? $replies : array() as $reply ) {
			if ( ! $reply instanceof \WP_Comment ) {
				continue;
			}
			$time   = (int) strtotime( $reply->comment_date_gmt . ' UTC' );
			$answer = array(
				'author' => '' === trim( $reply->comment_author ) ? __( 'Shopper', 'ultra-max-core' ) : $reply->comment_author,
				'text'   => Html::wp( wpautop( esc_html( $reply->comment_content ) ) ),
				'date'   => Format::date( $time ),
			);
			$badge  = self::badge( $reply, $product_id );
			if ( null !== $badge ) {
				$answer['badge'] = $badge;
				if ( 'brand' === $badge['tone'] ) {
					$answer['author'] = get_bloginfo( 'name' );
				}
			}
			$answers[] = $answer;
		}
		$time  = (int) strtotime( $question->comment_date_gmt . ' UTC' );
		$count = count( $answers );
		$item  = array(
			'id'       => $id,
			'text'     => $question->comment_content,
			'author'   => '' === trim( $question->comment_author ) ? __( 'Shopper', 'ultra-max-core' ) : $question->comment_author,
			'date'     => Format::date( $time ),
			'datetime' => gmdate( 'c', $time ),
			/* translators: %s: number of answers. */
			'count'    => 0 === $count ? __( 'No answer yet', 'ultra-max-core' ) : sprintf( _n( '%s answer', '%s answers', $count, 'ultra-max-core' ), Format::number( $count ) ),
			'answers'  => $answers,
		);
		if ( self::can_answer() ) {
			$item['answer'] = QuestionList::form(
				array(
					'action'      => admin_url( 'admin-post.php' ),
					'hidden'      => array(
						'action'   => self::ACTION,
						'product'  => (string) $product_id,
						'parent'   => (string) $id,
						'_wpnonce' => wp_create_nonce( self::ACTION ),
						'_um_back' => (string) get_permalink( $product_id ),
					),
					'label'       => __( 'Your answer', 'ultra-max-core' ),
					'placeholder' => __( 'Write your answer', 'ultra-max-core' ),
					'submit'      => __( 'Send answer', 'ultra-max-core' ),
					'rows'        => 2,
				)
			);
		}
		return $item;
	}

	/**
	 * The questions section.
	 *
	 * @param \WC_Product $product Product.
	 * @param int         $count   How many questions to show.
	 */
	public static function section( \WC_Product $product, int $count = 5 ): Html {
		if ( ! self::open( $product ) ) {
			return Html::none();
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only paging and a thank-you note.
		$shown = isset( $_GET['questions'] ) && is_numeric( $_GET['questions'] ) ? min( 100, max( $count, (int) $_GET['questions'] ) ) : $count;
		$total = self::count( $product->get_id() );
		$props = array(
			'id'     => 'um-questions-' . $product->get_id(),
			'items'  => array_map( array( self::class, 'item' ), self::questions( $product->get_id(), $shown ) ),
			'labels' => array(
				'empty'   => __( 'No question yet. Ask the first one.', 'ultra-max-core' ),
				'answer'  => __( 'Answer this question', 'ultra-max-core' ),
				'answers' => __( 'Answers', 'ultra-max-core' ),
			),
		);
		if ( self::can_ask() ) {
			$form = array(
				'action'      => admin_url( 'admin-post.php' ),
				'hidden'      => array(
					'action'   => self::ACTION,
					'product'  => (string) $product->get_id(),
					'_wpnonce' => wp_create_nonce( self::ACTION ),
					'_um_back' => (string) $product->get_permalink(),
				),
				'label'       => __( 'Your question', 'ultra-max-core' ),
				'placeholder' => __( 'Ask about this product', 'ultra-max-core' ),
				'submit'      => __( 'Send question', 'ultra-max-core' ),
				'notify'      => __( 'Email me when it is answered', 'ultra-max-core' ),
			);
			if ( ! is_user_logged_in() ) {
				$form['guest'] = array(
					'name'  => __( 'Name', 'ultra-max-core' ),
					'email' => __( 'Email', 'ultra-max-core' ),
				);
			}
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Only picks the note after a form came back.
			$came         = isset( $_GET['um_question'] ) && is_string( $_GET['um_question'] ) ? sanitize_key( wp_unslash( $_GET['um_question'] ) ) : '';
			$note         = '' === $came ? Html::none() : Alert::render(
				array(
					'tone'     => 'sent' === $came ? 'success' : 'danger',
					'children' => 'sent' === $came ? __( 'Thank you. It shows here once it is approved.', 'ultra-max-core' ) : __( 'It could not be sent. Please try again.', 'ultra-max-core' ),
				)
			);
			$props['ask'] = Html::from( array( $note, QuestionList::form( $form ) ) );
		} else {
			$props['login'] = array(
				'text'  => __( 'Log in to ask a question.', 'ultra-max-core' ),
				'label' => __( 'Log in', 'ultra-max-core' ),
				'href'  => wp_login_url( (string) $product->get_permalink() . '#questions' ),
			);
		}
		if ( $total > $shown ) {
			$props['more'] = array(
				/* translators: %s: number of questions left. */
				'label' => sprintf( __( 'Show more questions (%s more)', 'ultra-max-core' ), Format::number( $total - $shown ) ),
				'href'  => add_query_arg( 'questions', $shown + 10, (string) $product->get_permalink() ) . '#questions',
			);
		}
		return QuestionList::render( $props );
	}

	/**
	 * Adds the questions to the product page's sections.
	 *
	 * @param mixed $items   Sections.
	 * @param mixed $product Product.
	 * @return mixed
	 */
	public static function section_item( mixed $items, mixed $product ): mixed {
		if ( ! is_array( $items ) || ! $product instanceof \WC_Product || ! self::open( $product ) ) {
			return $items;
		}
		$count   = self::count( $product->get_id() );
		$items[] = array(
			'id'      => 'questions',
			'title'   => __( 'Questions', 'ultra-max-core' ),
			'content' => self::section( $product ),
			'count'   => $count > 0 ? Format::number( $count ) : '',
		);
		return $items;
	}

	/**
	 * Adds the questions link to the product heading.
	 *
	 * @param mixed $props   Heading props.
	 * @param mixed $product Product.
	 * @return mixed
	 */
	public static function heading( mixed $props, mixed $product ): mixed {
		if ( is_array( $props ) && $product instanceof \WC_Product && self::open( $product ) ) {
			$count              = self::count( $product->get_id() );
			$props['questions'] = array(
				/* translators: %s: number of questions. */
				'label' => $count > 0 ? sprintf( _n( '%s question', '%s questions', $count, 'ultra-max-core' ), Format::number( $count ) ) : __( 'Ask a question', 'ultra-max-core' ),
				'href'  => '#questions',
			);
		}
		return $props;
	}

	/**
	 * A posted text field.
	 *
	 * @param string $key Field name.
	 */
	private static function posted( string $key ): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- receive() checks the nonce; callers sanitize.
		$value = isset( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : '';
		return is_string( $value ) ? $value : '';
	}

	/**
	 * Answers a form: JSON for the behaviour, or back to the page.
	 *
	 * @param bool   $success Whether it worked.
	 * @param string $message Message.
	 * @param string $back    Product address.
	 */
	private static function reply( bool $success, string $message, string $back ): void {
		$accept = isset( $_SERVER['HTTP_ACCEPT'] ) && is_string( $_SERVER['HTTP_ACCEPT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_ACCEPT'] ) ) : '';
		if ( str_contains( $accept, 'application/json' ) ) {
			wp_send_json(
				array(
					'success' => $success,
					'message' => $message,
				)
			);
		}
		if ( '' === $back ) {
			wp_die( esc_html( $message ), '', array( 'back_link' => true ) );
		}
		wp_safe_redirect( add_query_arg( 'um_question', $success ? 'sent' : 'failed', $back ) . '#questions' );
		exit;
	}

	/**
	 * Receives a question or an answer.
	 */
	public static function receive(): void {
		$product = wc_get_product( absint( self::posted( 'product' ) ) );
		$back    = $product instanceof \WC_Product ? (string) $product->get_permalink() : '';
		if ( ! wp_verify_nonce( sanitize_text_field( self::posted( '_wpnonce' ) ), self::ACTION ) ) {
			self::reply( false, __( 'This page has expired. Reload it and try again.', 'ultra-max-core' ), $back );
			return;
		}
		if ( ! $product instanceof \WC_Product || ! self::open( $product ) || '' !== $product->get_post_password() || ! $product->is_visible() ) {
			self::reply( false, __( 'This product could not be found.', 'ultra-max-core' ), $back );
			return;
		}
		$parent = absint( self::posted( 'parent' ) );
		$text   = trim( sanitize_textarea_field( self::posted( 'um_text' ) ) );
		if ( mb_strlen( $text ) < 5 ) {
			self::reply( false, __( 'Please write a little more.', 'ultra-max-core' ), $back );
			return;
		}
		if ( $parent > 0 ) {
			$question = get_comment( $parent );
			if ( ! self::can_answer() || ! $question instanceof \WP_Comment || self::TYPE !== $question->comment_type || (int) $question->comment_post_ID !== $product->get_id() || '1' !== $question->comment_approved ) {
				self::reply( false, __( 'You cannot answer this question.', 'ultra-max-core' ), $back );
				return;
			}
		} elseif ( ! self::can_ask() ) {
			self::reply( false, __( 'Log in to ask a question.', 'ultra-max-core' ), $back );
			return;
		}
		$user = wp_get_current_user();
		$data = array(
			'comment_post_ID'      => $product->get_id(),
			'comment_content'      => mb_substr( $text, 0, 1000 ),
			'comment_type'         => $parent > 0 ? self::ANSWER : self::TYPE,
			'comment_parent'       => $parent,
			'user_id'              => $user->ID,
			'comment_author'       => $user->exists() ? $user->display_name : sanitize_text_field( self::posted( 'author' ) ),
			'comment_author_email' => $user->exists() ? $user->user_email : sanitize_email( self::posted( 'email' ) ),
			'comment_author_url'   => '',
		);
		if ( ! $user->exists() && ( '' === $data['comment_author'] || ! is_email( $data['comment_author_email'] ) ) ) {
			self::reply( false, __( 'Please write your name and email.', 'ultra-max-core' ), $back );
			return;
		}
		$moderate = Store::bool( 'product_questions_moderate' ) && ! current_user_can( 'manage_woocommerce' ); // phpcs:ignore WordPress.WP.Capabilities.Unknown -- WooCommerce's own capability.
		if ( $moderate ) {
			add_filter( 'pre_comment_approved', array( self::class, 'hold' ), 99 );
		}
		$id = wp_new_comment( $data, true );
		remove_filter( 'pre_comment_approved', array( self::class, 'hold' ), 99 );
		if ( is_wp_error( $id ) ) {
			self::reply( false, wp_strip_all_tags( $id->get_error_message() ), $back );
			return;
		}
		if ( 0 === $parent && '1' === self::posted( 'um_notify' ) ) {
			update_comment_meta( (int) $id, self::NOTIFY, 1 );
		}
		$approved = 'approved' === wp_get_comment_status( (int) $id );
		self::reply(
			true,
			$approved
				? ( $parent > 0 ? __( 'Thank you. Your answer is published.', 'ultra-max-core' ) : __( 'Thank you. Your question is published.', 'ultra-max-core' ) )
				: ( $parent > 0 ? __( 'Thank you. Your answer shows once it is approved.', 'ultra-max-core' ) : __( 'Thank you. Your question shows once it is approved.', 'ultra-max-core' ) ),
			$back
		);
	}

	/**
	 * Holds a question or answer for moderation, unless spam checks already
	 * marked it as spam or trash.
	 *
	 * @param mixed $approved Status.
	 * @return mixed
	 */
	public static function hold( mixed $approved ): mixed {
		return in_array( $approved, array( 'spam', 'trash' ), true ) || is_wp_error( $approved ) ? $approved : 0;
	}

	/**
	 * A comment added: an approved answer notifies the asker.
	 *
	 * @param mixed $id      Comment id.
	 * @param mixed $comment Comment.
	 */
	public static function inserted( mixed $id, mixed $comment ): void {
		if ( $comment instanceof \WP_Comment && '1' === $comment->comment_approved ) {
			self::notify( $comment );
		}
	}

	/**
	 * A comment approved later: an answer notifies the asker.
	 *
	 * @param mixed $next    New status.
	 * @param mixed $old     Old status.
	 * @param mixed $comment Comment.
	 */
	public static function transition( mixed $next, mixed $old, mixed $comment ): void {
		if ( 'approved' === $next && $comment instanceof \WP_Comment ) {
			self::notify( $comment );
		}
	}

	/**
	 * Emails the asker once about the first approved answer.
	 *
	 * @param \WP_Comment $answer Answer.
	 */
	private static function notify( \WP_Comment $answer ): void {
		$parent = (int) $answer->comment_parent;
		if ( $parent <= 0 ) {
			return;
		}
		$question = get_comment( $parent );
		if ( ! $question instanceof \WP_Comment || self::TYPE !== $question->comment_type ) {
			return;
		}
		/**
		 * Fires when an answer to a product question is published, for example
		 * to send the asker a text message.
		 *
		 * @param \WP_Comment $question Question.
		 * @param \WP_Comment $answer   Answer.
		 */
		do_action( 'ultra_max_question_answered', $question, $answer );
		$id = (int) $question->comment_ID;
		if ( ! get_comment_meta( $id, self::NOTIFY, true ) || get_comment_meta( $id, self::SENT, true ) || ! is_email( $question->comment_author_email ) ) {
			return;
		}
		update_comment_meta( $id, self::SENT, time() );
		$product = get_the_title( (int) $question->comment_post_ID );
		$link    = (string) get_permalink( (int) $question->comment_post_ID ) . '#question-' . $id;
		wp_mail(
			$question->comment_author_email,
			/* translators: %s: product name. */
			sprintf( __( 'Your question about %s is answered', 'ultra-max-core' ), wp_strip_all_tags( $product ) ),
			sprintf(
				/* translators: 1: question, 2: answer, 3: link. */
				__( "Your question:\n%1\$s\n\nAnswer:\n%2\$s\n\nSee it on the store: %3\$s", 'ultra-max-core' ),
				$question->comment_content,
				$answer->comment_content,
				$link
			)
		);
	}

	/**
	 * Products, Questions: the comments screen with our questions, which
	 * WooCommerce otherwise hides there with every product comment.
	 */
	public static function menu(): void {
		add_submenu_page(
			'edit.php?post_type=product',
			__( 'Questions', 'ultra-max-core' ),
			__( 'Questions', 'ultra-max-core' ),
			'moderate_comments',
			'edit-comments.php?comment_type=' . self::TYPE
		);
	}

	/**
	 * Lets the comments screen list product questions and answers.
	 *
	 * @param mixed $args Query arguments.
	 * @return mixed
	 */
	public static function admin_list( mixed $args ): mixed {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only screen filter.
		$type = isset( $_GET['comment_type'] ) && is_string( $_GET['comment_type'] ) ? sanitize_key( wp_unslash( $_GET['comment_type'] ) ) : '';
		if ( is_array( $args ) && in_array( $type, array( self::TYPE, self::ANSWER ), true ) ) {
			$args['post_type'] = 'product';
			$args['type__in']  = array( self::TYPE, self::ANSWER );
			unset( $args['type'] );
		}
		return $args;
	}

	/**
	 * Adds questions to the type menu of the comments screen.
	 *
	 * @param mixed $types Types.
	 * @return mixed
	 */
	public static function types( mixed $types ): mixed {
		if ( is_array( $types ) ) {
			$types[ self::TYPE ] = __( 'Product questions', 'ultra-max-core' );
		}
		return $types;
	}
}
