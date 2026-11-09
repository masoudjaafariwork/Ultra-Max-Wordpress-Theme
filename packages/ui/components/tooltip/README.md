# Tooltip

راهنمای کوتاه روی یک دکمه. با hover و با فوکوس صفحه‌کلید دیده می‌شود و صفحه‌خوان آن را توضیح دکمه می‌خواند.

```php
UI::tooltip(
	array(
		'id'       => 'compare-hint',
		'text'     => __( 'Add to compare list', 'ultra-max' ),
		'children' => UI::button( array( 'variant' => 'ghost', 'label' => __( 'Compare', 'ultra-max' ), 'children' => $icon ) ),
	)
)->print();
```

| ورودی | نوع | پیش‌فرض | توضیح |
| --- | --- | --- | --- |
| `id` | شناسه HTML | لازم | |
| `text` | متن | لازم | |
| `children` | Html | لازم | باید یک عنصر فوکوس‌پذیر داشته باشد |
| `placement` | `top`، `bottom`، `start`، `end` | `top` | منطقی، در راست‌چین قرینه می‌شود |

راهنمای کوچک اطلاعات ضروری نگه نمی‌دارد، چون در گوشی hover نیست.

**آزمون:** `tooltip.test.ts`.