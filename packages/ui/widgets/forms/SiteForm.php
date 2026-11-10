<?php
/**
 * SiteForm component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * The contact form and the newsletter form (HOME-12, HDR-11). Both post to
 * the site itself with a security token and a hidden trap field for spam
 * bots, and work without JavaScript; the server sends the visitor back
 * with a message. With JavaScript the form is sent in place and the
 * message appears under it.
 *
 * - `contact`: name, email or phone, and message, stacked.
 * - `newsletter`: one field and a button in a row.
 *
 * @phpstan-type SiteFormField array{name: string, label: string, type?: 'text'|'email'|'tel'|'textarea', required?: bool, placeholder?: string, autocomplete?: string, dir?: 'ltr'|'rtl'|'auto'}
 * @phpstan-type SiteFormProps array{
 *     id: string,
 *     action: string,
 *     kind: 'contact'|'newsletter',
 *     fields: list<SiteFormField>,
 *     hidden: array<string, string>,
 *     submit: string,
 *     message?: array{tone: 'success'|'danger', text: string},
 *     title?: string,
 *     class?: string,
 * }
 */
final class SiteForm {

	/**
	 * Renders the form.
	 *
	 * @param array $props Props.
	 * @phpstan-param SiteFormProps $props
	 */
	public static function render( array $props ): Html {
		$id     = Props::id( 'SiteForm', $props );
		$inline = 'newsletter' === $props['kind'];
		$fields = '';
		foreach ( $props['fields'] as $field ) {
			$field_props = array(
				'id'           => $id . '-' . $field['name'],
				'name'         => $field['name'],
				'label'        => $field['label'],
				'type'         => $field['type'] ?? 'text',
				'required'     => $field['required'] ?? false,
				'placeholder'  => $field['placeholder'] ?? '',
				'autocomplete' => $field['autocomplete'] ?? 'off',
				'dir'          => $field['dir'] ?? 'auto',
			);
			if ( $inline ) {
				$field_props['class'] = 'min-w-0 flex-1 [&_label]:sr-only';
			}
			$fields .= Field::render( $field_props );
		}
		$hidden = '<input type="text" name="um_website" value="" tabindex="-1" autocomplete="off" class="sr-only" aria-hidden="true">';
		foreach ( $props['hidden'] as $name => $value ) {
			$hidden .= sprintf( '<input type="hidden" name="%1$s" value="%2$s">', esc_attr( $name ), esc_attr( $value ) );
		}
		$message = '';
		if ( isset( $props['message'] ) ) {
			$message = Alert::render(
				array(
					'tone'     => 'success' === $props['message']['tone'] ? 'success' : 'danger',
					'children' => $props['message']['text'],
				)
			);
		}
		$button = Button::render(
			array(
				'children' => $props['submit'],
				'type'     => 'submit',
				'class'    => $inline ? 'shrink-0' : 'w-fit',
			)
		);
		return Html::trusted(
			sprintf(
				'<form%1$s>%2$s<div class="%3$s">%4$s%5$s</div>%6$s<div class="text-sm data-[tone=danger]:text-danger-text data-[tone=success]:text-success-text" role="status" aria-live="polite" data-status>%7$s</div></form>',
				Attributes::render(
					array(
						'id'                => $id,
						'action'            => $props['action'],
						'method'            => 'post',
						'class'             => Attributes::classes( 'um-site-form relative grid gap-3', $props['class'] ?? null ),
						'data-um-component' => 'site-form',
					)
				),
				isset( $props['title'] ) && '' !== $props['title'] ? sprintf( '<h3 class="m-0 text-md font-bold">%s</h3>', esc_html( $props['title'] ) ) : '',
				$inline ? 'flex items-end gap-2' : 'grid gap-3',
				$fields,
				$inline ? $button : '',
				$hidden . ( $inline ? '' : $button ),
				$message
			)
		);
	}
}
