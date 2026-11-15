# Field

فیلد متن یا متن چندخطی با برچسب و توضیح.

```php
UI::field( array( 'id' => 'email', 'name' => 'email', 'label' => 'ایمیل', 'type' => 'email' ) )->print();

// گذرواژه با آیکن، دکمه‌ی نمایش و پیوند «فراموشی»
UI::field( array(
    'id'     => 'password',
    'name'   => 'password',
    'label'  => 'گذرواژه',
    'type'   => 'password',
    'icon'   => 'lock',
    'reveal' => true,
    'link'   => array( 'label' => 'فراموش کرده‌اید؟', 'href' => wp_lostpassword_url() ),
) )->print();
```

| ورودی | نوع | پیش‌فرض | توضیح |
| --- | --- | --- | --- |
| `id`، `name`، `label` | متن | لازم | |
| `type` | `text`، `email`، `url`، `tel`، `password`، `search`، `number`، `textarea` | `text` | |
| `value`، `placeholder`، `autocomplete`، `help` | متن | — | |
| `required` | بولی | `false` | |
| `dir` | `ltr`، `rtl`، `auto` | — | |
| `icon` | نام آیکن | — | آیکن در ابتدای کادر |
| `reveal` | بولی | `false` | فقط برای گذرواژه: دکمه‌ی نمایش و پنهان کردن متن (رفتار: `field.behavior.ts`) |
| `link` | `label` و `href` | — | پیوند کوچک در انتهای ردیف برچسب |

`class` در همه اجزا فقط کلاس چیدمان می‌پذیرد.
