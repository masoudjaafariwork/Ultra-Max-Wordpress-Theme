# PostCard

نوشته در فهرست، با تصویر، برچسب، عنوان، خلاصه و اطلاعات. کل کارت از طریق پیوند عنوان کلیک‌پذیر است.

```php
UI::post_card( array( 'title' => 'عنوان', 'href' => $url, 'image_id' => 9, 'priority' => true ) )->print();
```

| ورودی | نوع | پیش‌فرض | توضیح |
| --- | --- | --- | --- |
| `title`، `href` | متن | لازم | |`n| `image_id` | عدد | — | |`n| `excerpt`، `badge` | متن | — | |`n| `meta` | Html | — | |`n| `layout` | `grid`، `list` | `grid` | |`n| `priority` | بولی | `false` | تصویر اولین کارت زودتر بار می‌شود (PERF-03) |

`class` در همه اجزا فقط کلاس چیدمان می‌پذیرد.
