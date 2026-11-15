# Checkbox

چک‌باکس با برچسب کنار آن، مثل «مرا به خاطر بسپار».

```php
UI::checkbox( array( 'id' => 'rememberme', 'name' => 'rememberme', 'value' => 'forever', 'label' => 'مرا به خاطر بسپار' ) )->print();
```

| ورودی | نوع | پیش‌فرض | توضیح |
| --- | --- | --- | --- |
| `id`، `name`، `label` | متن | لازم | `label` می‌تواند Html باشد |
| `value` | متن | `1` | |
| `checked`، `required` | بولی | `false` | |

`class` فقط کلاس چیدمان می‌پذیرد.
