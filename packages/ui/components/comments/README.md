# Comments

یک دیدگاه برای `wp_list_comments()` و ورودی `comment_form()` که از Field و ظاهر دکمه ما استفاده می‌کند.

```php
UI::comment( $props )->print(); comment_form( Comments::form_args( wp_get_current_commenter(), true ) );
```

| ورودی | نوع | پیش‌فرض | توضیح |
| --- | --- | --- | --- |
| `id`، `author`، `date`، `datetime` | | لازم | |`n| `content` | Html | لازم | |`n| `author_url`، `avatar`، `edit_url` | نشانی | — | |`n| `reply` | Html | — | |`n| `pending`، `by_post_author` | بولی | `false` | |

`class` در همه اجزا فقط کلاس چیدمان می‌پذیرد.
