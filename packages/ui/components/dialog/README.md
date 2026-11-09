# Dialog

پنجره روی صفحه، بر پایه عنصر بومی `dialog` مرورگر. Flowbite در آزمون پذیرش برای پنجره رد شد؛ دلیل در `packages/ui/acceptance/RESULTS.fa.md`.

```php
UI::button( array( 'opens' => 'review', 'children' => __( 'Write a review', 'ultra-max' ) ) )->print();
UI::dialog(
	array(
		'id'       => 'review',
		'title'    => __( 'Your review', 'ultra-max' ),
		'children' => $form,
		'footer'   => UI::button( array( 'type' => 'submit', 'children' => __( 'Send', 'ultra-max' ) ) ),
	)
)->print();
```

| ورودی | نوع | پیش‌فرض | توضیح |
| --- | --- | --- | --- |
| `id` | شناسه HTML | لازم | دکمه با `opens` همین شناسه را باز می‌کند |
| `title` | متن یا Html | لازم | عنوان، که نام دسترس‌پذیر پنجره هم هست |
| `children` | متن، Html یا فهرست | لازم | بدنه |
| `footer` | متن، Html یا فهرست | — | دکمه‌های پایین |
| `size` | `sm`، `md`، `lg` | `md` | ۴۰۰، ۵۶۰ و ۸۰۰ پیکسل |

**رفتار:** تمرکز به پنجره می‌رود و داخل آن می‌چرخد، Esc و کلیک روی پس‌زمینه می‌بندند، تمرکز به دکمه بازکننده برمی‌گردد، صفحه پشت اسکرول نمی‌شود. هر عنصر داخل پنجره با `data-um-close` آن را می‌بندد. رویدادهای `um:open` و `um:close`. از کد: `UltraMaxUI.get( element ).open()`.

**آزمون:** `dialog.test.ts`.