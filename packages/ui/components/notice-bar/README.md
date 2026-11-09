# NoticeBar

نوار اطلاع‌رسانی بالای سایت (CORE-10). رفتار در `notice-bar.behavior.ts`: پنهان کردن خارج از بازه تاریخ حتی از کش، و به خاطر سپردن بستن.

```php
UI::notice_bar( array( 'id' => 'n', 'text' => 'ارسال رایگان', 'close_label' => 'بستن' ) )->print();
```

| ورودی | نوع | پیش‌فرض | توضیح |
| --- | --- | --- | --- |
| `id`، `text`، `close_label` | متن | لازم | |`n| `link` | `{label, href}` | — | |`n| `tone` | `brand`، `accent`، `dark` | `brand` | |`n| `dismissible` | بولی | `true` | |`n| `start`، `end` | زمان یونیکس | — | |`n`n**رویداد:** `um:close`. **آزمون:** `tests/e2e/phase2.spec.ts` و `tests/unit/phase2.test.ts`.

`class` در همه اجزا فقط کلاس چیدمان می‌پذیرد.
