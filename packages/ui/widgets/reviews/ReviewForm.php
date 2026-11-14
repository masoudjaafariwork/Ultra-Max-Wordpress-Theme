<?php
/**
 * ReviewForm component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * The form to review a product (PDP-08, PDP-09): stars, a score for each
 * criterion, a title, the text, strengths and weaknesses one per line,
 * whether the shopper recommends the product, and photos.
 *
 * It posts to WordPress's own comment handler, so moderation, spam checks
 * and WooCommerce's rating rules apply as to any review. It folds open
 * under a summary line, which works without JavaScript.
 *
 * @phpstan-type ReviewFormProps array{
 *     id: string,
 *     action: string,
 *     summary: string,
 *     rating: array{legend: string, labels: list<string>, required: bool},
 *     labels: array{title: string, text: string, pros: string, cons: string, lines: string, recommend: string, submit: string},
 *     recommend: array<string, string>,
 *     hidden: array<string, string>,
 *     criteria?: list<array{name: string, label: string}>,
 *     guest?: array{name: string, email: string, name_value: string, email_value: string, required: bool},
 *     images?: array{label: string, help: string, max: int},
 *     extra?: Html,
 *     open?: bool,
 *     class?: string,
 * }
 */
final class ReviewForm {

	/**
	 * Renders the form.
	 *
	 * @param array $props Props.
	 * @phpstan-param ReviewFormProps $props
	 */
	public static function render( array $props ): Html {
		$id     = Props::id( 'ReviewForm', $props );
		$field  = 'w-full rounded-md border border-border-strong bg-surface px-3 py-2 text-sm text-fg focus-visible:outline-3 focus-visible:outline-primary/40';
		$label  = 'grid gap-1 text-sm font-bold';
		$hidden = '';
		foreach ( $props['hidden'] as $name => $value ) {
			$hidden .= sprintf( '<input type="hidden" name="%1$s" value="%2$s">', esc_attr( $name ), esc_attr( $value ) );
		}
		$criteria = '';
		foreach ( $props['criteria'] ?? array() as $criterion ) {
			$criteria .= Stars::input( $criterion['name'], $criterion['label'], $props['rating']['labels'], false, 'size-6' );
		}
		$guest = '';
		if ( isset( $props['guest'] ) ) {
			$guest = sprintf(
				'<div class="grid gap-3 md:grid-cols-2"><label class="%1$s">%2$s<input class="%3$s font-regular" type="text" name="author" value="%4$s" autocomplete="name"%5$s></label><label class="%1$s">%6$s<input class="%3$s font-regular" type="email" name="email" value="%7$s" autocomplete="email" dir="ltr"%5$s></label></div>',
				esc_attr( $label ),
				esc_html( $props['guest']['name'] ),
				esc_attr( $field ),
				esc_attr( $props['guest']['name_value'] ),
				$props['guest']['required'] ? ' required' : '',
				esc_html( $props['guest']['email'] ),
				esc_attr( $props['guest']['email_value'] )
			);
		}
		$recommend = '';
		foreach ( $props['recommend'] as $value => $text ) {
			$recommend .= sprintf(
				'<label class="inline-flex cursor-pointer items-center gap-2 rounded-pill border border-border px-3 py-1.5 text-sm font-regular has-checked:border-primary has-checked:bg-primary-soft has-checked:text-primary has-focus-visible:outline-3 has-focus-visible:outline-primary/40"><input type="radio" class="sr-only" name="um_recommend" value="%1$s">%2$s</label>',
				esc_attr( $value ),
				esc_html( $text )
			);
		}
		$images = isset( $props['images'] ) ? sprintf(
			'<label class="%1$s">%2$s<input class="text-sm font-regular file:me-3 file:rounded-sm file:border-0 file:bg-surface-3 file:px-3 file:py-1.5 file:text-sm file:font-bold" type="file" name="um_review_images[]" accept="image/jpeg,image/png,image/webp" multiple data-max="%3$d"><span class="text-xs font-regular text-fg-3">%4$s</span></label>',
			esc_attr( $label ),
			esc_html( $props['images']['label'] ),
			$props['images']['max'],
			esc_html( $props['images']['help'] )
		) : '';
		return Html::trusted(
			sprintf(
				'<details id="%1$s" class="%2$s"%3$s><summary class="flex cursor-pointer list-none items-center justify-between gap-2 py-3 text-md font-bold [&::-webkit-details-marker]:hidden">%4$s%5$s</summary><form class="grid gap-4 pb-4" method="post" action="%6$s" enctype="multipart/form-data">%7$s%8$s%9$s<div class="grid gap-3 md:grid-cols-2">%10$s</div><label class="%11$s">%12$s<input class="%13$s font-regular" type="text" name="um_title" maxlength="120"></label><label class="%11$s">%14$s<textarea class="%13$s min-h-28 font-regular" name="comment" required maxlength="5000"></textarea></label><div class="grid gap-3 md:grid-cols-2"><label class="%11$s">%15$s<textarea class="%13$s min-h-20 font-regular" name="um_pros" maxlength="600"></textarea><span class="text-xs font-regular text-fg-3">%16$s</span></label><label class="%11$s">%17$s<textarea class="%13$s min-h-20 font-regular" name="um_cons" maxlength="600"></textarea><span class="text-xs font-regular text-fg-3">%16$s</span></label></div><fieldset class="m-0 grid gap-2 border-0 p-0"><legend class="mb-2 p-0 text-sm font-bold">%18$s</legend><div class="flex flex-wrap gap-2">%19$s</div></fieldset>%20$s%21$s<button type="submit" class="inline-flex min-h-11 items-center justify-center justify-self-start rounded-md border-0 bg-primary px-6 text-md font-bold text-on-primary cursor-pointer hover:bg-primary-hover focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-primary/40">%22$s</button></form></details>',
				esc_attr( $id ),
				esc_attr( Attributes::classes( 'um-review-form group/form border-t border-border', $props['class'] ?? null ) ),
				( $props['open'] ?? false ) ? ' open' : '',
				esc_html( $props['summary'] ),
				Icon::render( 'chevron-down', 'size-5 text-fg-3 transition-transform group-open/form:rotate-180' ),
				esc_url( $props['action'] ),
				$hidden,
				$guest,
				Stars::input( 'rating', $props['rating']['legend'], $props['rating']['labels'], $props['rating']['required'] ),
				$criteria,
				esc_attr( $label ),
				esc_html( $props['labels']['title'] ),
				esc_attr( $field ),
				esc_html( $props['labels']['text'] ),
				esc_html( $props['labels']['pros'] ),
				esc_html( $props['labels']['lines'] ),
				esc_html( $props['labels']['cons'] ),
				esc_html( $props['labels']['recommend'] ),
				$recommend,
				$images,
				isset( $props['extra'] ) ? $props['extra'] : '',
				esc_html( $props['labels']['submit'] )
			)
		);
	}
}
