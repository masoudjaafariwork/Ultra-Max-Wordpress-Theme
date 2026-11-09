# PageHeader

ناحیه عنوان صفحه با مسیر راهنما، توضیح و خط اطلاعات (CORE-08).

```php
UI::page_header( array( 'title' => get_the_title(), 'variant' => 'soft' ) )->print();
```

| ورودی | نوع | پیش‌فرض | توضیح |
| --- | --- | --- | --- |
| `title` | متن یا Html | لازم | |`n| `description`، `meta`، `breadcrumbs` | Html | — | |`n| `variant` | `plain`، `soft`، `brand` | `plain` | |`n| `align` | `start`، `center` | `start` | |`n| `show_title` | بولی | `true` | |

`class` در همه اجزا فقط کلاس چیدمان می‌پذیرد.
