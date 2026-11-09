# Dropdown

دکمه‌ای که فهرست پیوند باز می‌کند، مثل منوی حساب کاربری. الگوی disclosure است، نه منوی برنامه.

```php
UI::dropdown(
	array(
		'id'    => 'account',
		'label' => __( 'My account', 'ultra-max' ),
		'items' => array( array( 'label' => __( 'Orders', 'ultra-max' ), 'href' => $orders_url, 'current' => true ) ),
	)
)->print();
```

| ورودی | نوع | پیش‌فرض | توضیح |
| --- | --- | --- | --- |
| `id` | شناسه HTML | لازم | |
| `label` | متن یا Html | لازم | متن دکمه |
| `items` | فهرست `label`، `href` و `current` | لازم | `current` صفحه فعلی را با `aria-current` نشان می‌دهد |
| `variant` | مثل Button | `neutral` | |

**رفتار:** زیر دکمه و هم‌تراز با لبه شروع باز می‌شود و اگر جا نباشد بالای آن. Esc می‌بندد و تمرکز را برمی‌گرداند. کلیک بیرون می‌بندد.

**آزمون:** `dropdown.test.ts`.