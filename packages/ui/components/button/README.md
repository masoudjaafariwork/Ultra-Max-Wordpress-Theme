# Button

دکمه، یا پیوندی که شکل دکمه دارد.

```php
UI::button( array( 'children' => __( 'Add to cart', 'ultra-max' ) ) )->print();
UI::button( array( 'variant' => 'outline', 'href' => wc_get_cart_url(), 'children' => __( 'Cart', 'ultra-max' ) ) )->print();
UI::button( array( 'opens' => 'mini-cart', 'children' => __( 'Cart', 'ultra-max' ) ) )->print();
```

| ورودی | نوع | پیش‌فرض | توضیح |
| --- | --- | --- | --- |
| `children` | متن، Html یا فهرستی از آن‌ها | لازم | متن ساده همیشه escape می‌شود |
| `variant` | `primary`، `outline`، `soft`، `ghost`، `neutral`، `accent`، `danger` | `primary` | |
| `size` | `sm`، `md`، `lg` | `md` | اندازه `md` دست‌کم ۴۴ پیکسل ارتفاع دارد |
| `href` | نشانی | — | اگر باشد، خروجی `a` است |
| `type` | `button`، `submit`، `reset` | `button` | فقط برای `button` |
| `disabled` | بولی | `false` | پیوند غیرفعال `aria-disabled` می‌گیرد |
| `block` | بولی | `false` | تمام‌عرض |
| `opens` | شناسه Dialog یا Drawer | — | با کلیک آن را باز می‌کند |
| `label` | متن | — | نام دسترس‌پذیر، برای دکمه فقط-آیکون |
| `id`، `attrs`، `class` | | — | `class` فقط کلاس چیدمان |

**حالت‌ها:** عادی، hover، فوکوس با حلقه دیده‌شدنی، فشرده، غیرفعال.

**آزمون:** `button.test.ts`.