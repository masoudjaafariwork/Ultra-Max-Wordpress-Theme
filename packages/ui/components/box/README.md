# Box

سطح سفید با حاشیه برای پنل‌ها و ابزارک‌ها. `Box::widget()` و `Box::footer_widget()` ورودی `register_sidebar()` را می‌سازند.

```php
UI::box( array( 'title' => 'عنوان', 'children' => $html ) )->print();
```

| ورودی | نوع | پیش‌فرض | توضیح |
| --- | --- | --- | --- |
| `children` | متن یا Html | لازم | |`n| `title` | متن | — | |`n| `tag` | `div`، `section`، `aside`، `article` | `div` | |`n| `padding` | `md`، `lg`، `none` | `md` | |

`class` در همه اجزا فقط کلاس چیدمان می‌پذیرد.
