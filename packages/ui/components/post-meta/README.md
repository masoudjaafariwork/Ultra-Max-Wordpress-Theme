# PostMeta

خط اطلاعات نوشته با آیکون؛ تاریخ با `time` و `datetime` میلادی.

```php
UI::post_meta( array( 'items' => array( array( 'icon' => 'calendar', 'label' => '۱۸ آبان ۱۴۰۵', 'datetime' => '2026-11-09' ) ) ) )->print();
```

| ورودی | نوع | پیش‌فرض | توضیح |
| --- | --- | --- | --- |
| `items` | فهرست `{icon, label, href?, datetime?, hidden_label?}` | لازم | |

`class` در همه اجزا فقط کلاس چیدمان می‌پذیرد.
