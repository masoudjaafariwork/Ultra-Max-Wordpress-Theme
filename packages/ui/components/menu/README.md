# Menu

منوی ناوبری از درختی دوسطحی از پیوندها.

```php
UI::menu( array( 'label' => __( 'Main menu', 'ultra-max' ), 'items' => $items, 'variant' => 'bar' ) )->print();
```

| ورودی | نوع | پیش‌فرض | توضیح |
| --- | --- | --- | --- |
| `label` | متن | لازم | نام دسترس‌پذیر `nav` |`n| `items` | فهرست `{label, href, current?, children?}` | لازم | |`n| `variant` | `bar`، `list`، `tree` | `bar` | سربرگ، پابرگ، کشوی موبایل |

`class` در همه اجزا فقط کلاس چیدمان می‌پذیرد.
