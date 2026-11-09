# Field

فیلد متن یا متن چندخطی با برچسب و توضیح.

```php
UI::field( array( 'id' => 'email', 'name' => 'email', 'label' => 'ایمیل', 'type' => 'email' ) )->print();
```

| ورودی | نوع | پیش‌فرض | توضیح |
| --- | --- | --- | --- |
| `id`، `name`، `label` | متن | لازم | |`n| `type` | `text`، `email`، `url`، `tel`، `password`، `search`، `number`، `textarea` | `text` | |`n| `value`، `placeholder`، `autocomplete`، `help` | متن | — | |`n| `required` | بولی | `false` | |`n| `dir` | `ltr`، `rtl`، `auto` | — | |

`class` در همه اجزا فقط کلاس چیدمان می‌پذیرد.
