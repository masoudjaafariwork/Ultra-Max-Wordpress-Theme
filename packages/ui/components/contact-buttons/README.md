# ContactButtons

دکمه‌های تماس شناور (CORE-11). با یک مورد، دکمه گرد همان پیوند است. رفتار در `contact-buttons.behavior.ts`: باز و بسته شدن، Escape و کلیک بیرون.

```php
UI::contact_buttons( array( 'id' => 'c', 'label' => 'تماس', 'items' => $items ) )->print();
```

| ورودی | نوع | پیش‌فرض | توضیح |
| --- | --- | --- | --- |
| `id`، `label` | متن | لازم | |`n| `items` | فهرست `{label, href, icon}` | لازم | |`n| `side` | `end`، `start` | `end` | |`n`n**رویدادها:** `um:open`، `um:close`.

`class` در همه اجزا فقط کلاس چیدمان می‌پذیرد.
