# Media

تصویر شاخص با گوشه گرد و توضیح. `priority` تصویر بالای صفحه را زودتر بار می‌کند (PERF-03).

```php
UI::media( array( 'image_id' => 9, 'priority' => true ) )->print();
```

| ورودی | نوع | پیش‌فرض | توضیح |
| --- | --- | --- | --- |
| `image_id` | عدد | لازم | |`n| `size`، `alt`، `caption`، `sizes` | متن | — | |`n| `priority` | بولی | `false` | |

`class` در همه اجزا فقط کلاس چیدمان می‌پذیرد.
