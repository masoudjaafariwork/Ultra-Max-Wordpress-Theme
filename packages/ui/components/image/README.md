# Image

تصویر ساده از کتابخانه رسانه، یا از یک نشانی وقتی ابزارک فقط نشانی دارد (مثل تصویر طرح‌های آماده). همیشه بارگذاری تنبل دارد، مگر تصویر اصلی صفحه باشد (PERF-03).

```php
Image::render( array( 'id' => $attachment_id, 'size' => 'woocommerce_thumbnail', 'alt' => $name ) )->print();
Image::render( array( 'url' => $url, 'priority' => true, 'width' => 600, 'height' => 400 ) )->print();
```

| ورودی | نوع | پیش‌فرض | توضیح |
| --- | --- | --- | --- |
| `id` | شناسه پیوست | — | با `srcset` و اندازه‌های وردپرس |
| `url` | نشانی | — | وقتی `id` نیست |
| `size` | نام اندازه وردپرس | `medium` | |
| `alt` | متن | `''` | متن جایگزین؛ خالی یعنی تزئینی |
| `priority` | بولی | `false` | بدون بارگذاری تنبل و با `fetchpriority="high"` |
| `width`، `height` | عدد | — | برای جلوگیری از جابه‌جایی صفحه (CLS) |
| `class` | | — | فقط کلاس چیدمان |

**آزمون:** `tests/php/WidgetsTest.php`.
