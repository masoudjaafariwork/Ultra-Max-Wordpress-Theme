# SearchForm

فرم جستجوی سایت با برچسب پنهان و دکمه ارسال.

```php
UI::search_form( array( 'id' => 's1', 'action' => home_url( '/' ), 'label' => 'جستجو', 'submit' => 'جستجو' ) )->print();
```

| ورودی | نوع | پیش‌فرض | توضیح |
| --- | --- | --- | --- |
| `id`، `action`، `label`، `submit` | متن | لازم | |`n| `value`، `placeholder` | متن | — | |`n| `variant` | `soft`، `outline` | `soft` | سربرگ یا محتوا |`n| `hidden` | آرایه | — | فیلدهای پنهان، مثل `post_type` |

`class` در همه اجزا فقط کلاس چیدمان می‌پذیرد.
