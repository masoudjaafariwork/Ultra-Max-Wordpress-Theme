# Accordion

پرسش و پاسخ تاشو.

```php
UI::accordion( array( 'id' => 'faq', 'items' => array( array( 'title' => $q, 'content' => $a ) ) ) )->print();
```

| ورودی | نوع | پیش‌فرض | توضیح |
| --- | --- | --- | --- |
| `id` | شناسه HTML | لازم | |
| `items` | فهرست `title`، `content` و `open` | لازم | |
| `multiple` | بولی | `false` | چند مورد هم‌زمان باز بماند |
| `heading` | `h2`، `h3`، `h4` | `h3` | سطح تیتر هر پرسش، مطابق ساختار صفحه |

**آزمون:** `accordion.test.ts`.