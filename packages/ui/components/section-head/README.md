# SectionHead

ردیف عنوان یک بخش صفحه اصلی: عنوان با آیکون اختیاری، پیوند «مشاهده همه» و جا برای زبانه یا فیلتر در انتها.

```php
SectionHead::render(
	array(
		'title' => __( 'Best sellers', 'ultra-max-core' ),
		'icon'  => 'fire',
		'more'  => array( 'label' => __( 'See all', 'ultra-max-core' ), 'href' => $url ),
	)
)->print();

SectionHead::section( $head, $content )->print(); // ردیف عنوان و بعد محتوای بخش
```

| ورودی | نوع | پیش‌فرض | توضیح |
| --- | --- | --- | --- |
| `title` | متن | لازم | |
| `icon` | نام آیکون | — | |
| `more` | `label` و `href` | — | پیوند «مشاهده همه» |
| `extra` | Html | — | زبانه‌ها یا دکمه‌های فیلتر در انتهای ردیف |
| `align` | `start`، `center` | `start` | |
| `level` | `2`، `3` | `2` | سطح عنوان |
| `id`، `class` | | — | `class` فقط کلاس چیدمان |

**آزمون:** `tests/php/WidgetsTest.php`.
