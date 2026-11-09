# SiteHeader

سربرگ پیش‌فرض: دکمه منوی موبایل، لوگو، جستجو، عملیات و نوار منو. سازنده سربرگ فاز ۳ جای آن را می‌گیرد.

```php
UI::site_header( array( 'logo' => $logo, 'menu_label' => 'منو', 'nav' => $menu, 'drawer' => 'um-menu-drawer' ) )->print();
```

| ورودی | نوع | پیش‌فرض | توضیح |
| --- | --- | --- | --- |
| `logo` | Html | لازم | |`n| `menu_label` | متن | لازم | نام دکمه منوی موبایل |`n| `search`، `nav`، `actions` | Html | — | `SiteHeader::action()` دکمه گرد حساب و سبد را می‌سازد |`n| `drawer` | شناسه Drawer | — | کشوی منوی موبایل |`n| `sticky` | بولی | `true` | CORE-12 |

`class` در همه اجزا فقط کلاس چیدمان می‌پذیرد.
