# Drawer

کشوی کناری، مثل منوی موبایل یا سبد کوچک. همان رفتار Dialog را دارد.

```php
UI::drawer( array( 'id' => 'mini-cart', 'title' => __( 'Cart', 'ultra-max' ), 'children' => $items, 'footer' => $checkout ) )->print();
```

| ورودی | نوع | پیش‌فرض | توضیح |
| --- | --- | --- | --- |
| `id`، `title`، `children`، `footer` | | | مثل Dialog |
| `side` | `start`، `end` | `start` | منطقی: `start` در فارسی سمت راست و در انگلیسی سمت چپ است |

**آزمون:** `drawer.test.ts`، شامل لبه درست در راست‌چین.