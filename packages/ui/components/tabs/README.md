# Tabs

زبانه‌ها، مثل معرفی، مشخصات و دیدگاه‌ها در صفحه محصول.

```php
UI::tabs(
	array(
		'id'    => 'product-info',
		'label' => __( 'Product information', 'ultra-max' ),
		'items' => array(
			array( 'label' => __( 'Description', 'ultra-max' ), 'content' => $description ),
			array( 'label' => __( 'Specifications', 'ultra-max' ), 'content' => $specs ),
		),
	)
)->print();
```

| ورودی | نوع | پیش‌فرض | توضیح |
| --- | --- | --- | --- |
| `id` | شناسه HTML | لازم | |
| `label` | متن | لازم | نام دسترس‌پذیر فهرست زبانه‌ها |
| `items` | فهرست `label` و `content` | لازم | |
| `selected` | عدد | `0` | زبانه باز |

**صفحه‌کلید:** کلیدهای جهت (در راست‌چین قرینه)، Home و End. فقط زبانه انتخاب‌شده در ترتیب Tab است.

**آزمون:** `tabs.test.ts`.