<?php
/**
 * QuestionList component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * Questions and answers of a product (PDP-10): a box to ask, with "tell me
 * when it is answered", then each question with its answers, marked as the
 * store's or a buyer's, and a way to answer.
 *
 * The forms post to the store and come back to the page, so they work
 * without JavaScript; the questions behaviour sends them in place and
 * shows the reply.
 *
 * @phpstan-type QuestionAnswer array{author: string, text: Html, date: string, badge?: array{label: string, tone: 'brand'|'neutral'}}
 * @phpstan-type QuestionItem array{id: int, text: string, author: string, date: string, datetime: string, count: string, answers: list<QuestionAnswer>, answer?: Html}
 * @phpstan-type QuestionListProps array{
 *     id: string,
 *     items: list<QuestionItem>,
 *     labels: array{empty: string, answer: string, answers: string},
 *     ask?: Html,
 *     login?: array{text: string, label: string, href: string},
 *     more?: array{label: string, href: string},
 *     class?: string,
 * }
 */
final class QuestionList {

	/**
	 * Renders the list.
	 *
	 * @param array $props Props.
	 * @phpstan-param QuestionListProps $props
	 */
	public static function render( array $props ): Html {
		$id    = Props::id( 'QuestionList', $props );
		$items = '';
		foreach ( $props['items'] as $item ) {
			$items .= self::item( $item, $props['labels'] );
		}
		if ( '' === $items ) {
			$items = sprintf( '<p class="m-0 border-t border-border py-6 text-center text-sm text-fg-3">%s</p>', esc_html( $props['labels']['empty'] ) );
		}
		$login = isset( $props['login'] ) ? sprintf(
			'<p class="m-0 rounded-md bg-surface-2 p-3 text-sm text-fg-2">%1$s <a href="%2$s" class="font-bold text-link">%3$s</a></p>',
			esc_html( $props['login']['text'] ),
			esc_url( $props['login']['href'] ),
			esc_html( $props['login']['label'] )
		) : '';
		$more  = isset( $props['more'] ) ? sprintf(
			'<a href="%1$s" class="inline-flex items-center gap-1 justify-self-start text-sm font-bold text-link no-underline hover:underline" rel="nofollow">%2$s%3$s</a>',
			esc_url( $props['more']['href'] ),
			esc_html( $props['more']['label'] ),
			Icon::render( 'chevron-down', 'size-4' )
		) : '';
		return Html::trusted(
			sprintf(
				'<div%1$s>%2$s%3$s<div class="grid">%4$s</div>%5$s</div>',
				Attributes::render(
					array(
						'id'                => $id,
						'class'             => Attributes::classes( 'um-question-list grid gap-4', $props['class'] ?? null ),
						'data-um-component' => 'questions',
					)
				),
				isset( $props['ask'] ) ? $props['ask'] : '',
				$login,
				$items,
				$more
			)
		);
	}

	/**
	 * A form to ask or to answer.
	 *
	 * @param array $props Form props.
	 * @phpstan-param array{action: string, hidden: array<string, string>, label: string, placeholder: string, submit: string, notify?: string, guest?: array{name: string, email: string}, rows?: int} $props
	 */
	public static function form( array $props ): Html {
		$field  = 'w-full rounded-md border border-border-strong bg-surface px-3 py-2 text-sm text-fg focus-visible:outline-3 focus-visible:outline-primary/40';
		$hidden = '';
		foreach ( $props['hidden'] as $name => $value ) {
			$hidden .= sprintf( '<input type="hidden" name="%1$s" value="%2$s">', esc_attr( $name ), esc_attr( $value ) );
		}
		$guest  = isset( $props['guest'] ) ? sprintf(
			'<div class="grid gap-2 md:grid-cols-2"><input class="%1$s" type="text" name="author" placeholder="%2$s" aria-label="%2$s" autocomplete="name" required><input class="%1$s" type="email" name="email" placeholder="%3$s" aria-label="%3$s" autocomplete="email" dir="ltr" required></div>',
			esc_attr( $field ),
			esc_attr( $props['guest']['name'] ),
			esc_attr( $props['guest']['email'] )
		) : '';
		$notify = isset( $props['notify'] ) ? sprintf(
			'<label class="inline-flex cursor-pointer items-center gap-2 text-sm"><input type="checkbox" name="um_notify" value="1" checked class="size-4 accent-primary">%s</label>',
			esc_html( $props['notify'] )
		) : '<span></span>';
		return Html::trusted(
			sprintf(
				'<form class="grid gap-2" method="post" action="%1$s" data-um-qa-form>%2$s<label class="sr-only" for="%3$s">%4$s</label><textarea id="%3$s" class="%5$s" name="um_text" rows="%6$d" placeholder="%7$s" required minlength="5" maxlength="1000"></textarea>%8$s<div class="flex flex-wrap items-center justify-between gap-2">%9$s<button type="submit" class="inline-flex min-h-9 cursor-pointer items-center justify-center rounded-sm border-0 bg-primary px-4 text-sm font-bold text-on-primary hover:bg-primary-hover focus-visible:outline-3 focus-visible:outline-primary/40 aria-busy:opacity-60">%10$s</button></div><p class="m-0 hidden text-sm data-shown:block data-[tone=danger]:text-danger-text data-[tone=success]:text-success-text" role="status" data-um-qa-status></p></form>',
				esc_url( $props['action'] ),
				$hidden,
				esc_attr( 'um-qa-' . md5( (string) wp_json_encode( $props['hidden'] ) ) ),
				esc_html( $props['label'] ),
				esc_attr( $field ),
				max( 2, $props['rows'] ?? 3 ),
				esc_attr( $props['placeholder'] ),
				$guest,
				$notify,
				esc_html( $props['submit'] )
			)
		);
	}

	/**
	 * One question with its answers.
	 *
	 * @param array $item   Question.
	 * @param array $labels Texts.
	 * @phpstan-param QuestionItem $item
	 * @phpstan-param array{empty: string, answer: string, answers: string} $labels
	 */
	private static function item( array $item, array $labels ): string {
		$answers = '';
		foreach ( $item['answers'] as $answer ) {
			$badge    = isset( $answer['badge'] ) ? sprintf(
				'<span class="inline-flex h-5.5 shrink-0 items-center rounded-pill px-2 text-xs font-bold %1$s">%2$s</span>',
				'brand' === $answer['badge']['tone'] ? 'bg-primary-soft text-primary' : 'bg-surface-3 text-fg-2',
				esc_html( $answer['badge']['label'] )
			) : '';
			$answers .= sprintf(
				'<div class="flex items-start gap-2 ps-7 text-sm text-fg-2">%1$s<div class="min-w-0 [&_p]:m-0"><div>%2$s</div><span class="text-xs text-fg-3">%3$s · %4$s</span></div></div>',
				$badge,
				$answer['text'],
				esc_html( $answer['author'] ),
				esc_html( $answer['date'] )
			);
		}
		$reply = isset( $item['answer'] ) && ! $item['answer']->is_empty() ? sprintf(
			'<details class="ps-7"><summary class="cursor-pointer list-none text-xs font-bold text-link [&::-webkit-details-marker]:hidden">%1$s</summary><div class="pt-2">%2$s</div></details>',
			esc_html( $labels['answer'] ),
			$item['answer']
		) : '';
		return sprintf(
			'<article id="question-%1$d" class="grid gap-2 border-t border-border py-4"><div class="flex items-start gap-2">%2$s<b class="text-md">%3$s</b></div>%4$s<div class="flex flex-wrap gap-2 ps-7 text-xs text-fg-3"><span>%5$s</span><span aria-hidden="true">·</span><time datetime="%6$s">%7$s</time><span aria-hidden="true">·</span><span>%8$s</span></div>%9$s</article>',
			$item['id'],
			Icon::render( 'info', 'mt-0.5 size-5 text-primary' ),
			esc_html( $item['text'] ),
			$answers,
			esc_html( $item['author'] ),
			esc_attr( $item['datetime'] ),
			esc_html( $item['date'] ),
			esc_html( $item['count'] ),
			$reply
		);
	}
}
