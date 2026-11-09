# Alert

جعبه پیام با آیکون؛ رنگ هرگز تنها نشانه نیست.

```php
UI::alert( array( 'tone' => 'warning', 'children' => 'پیام' ) )->print();
```

| ورودی | نوع | پیش‌فرض | توضیح |
| --- | --- | --- | --- |
| `children` | متن یا Html | لازم | |`n| `tone` | `info`، `success`، `warning`، `danger` | `info` | |`n| `title` | متن | — | |`n| `role` | `status`، `alert`، `none` | `none` | |

`class` در همه اجزا فقط کلاس چیدمان می‌پذیرد.
