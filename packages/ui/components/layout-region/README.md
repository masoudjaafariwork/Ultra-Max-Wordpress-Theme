# LayoutRegion

قاب دور یک چیدمان سازنده چیدمان (BLD-01): ناحیه `header`، `footer` یا `div` که می‌تواند هنگام اسکرول بالای صفحه بماند.

```php
UI::layout_region(
	array(
		'location' => 'header',
		'id'       => $layout_id,
		'sticky'   => 'smart',
		'children' => $content,
	)
)->print();
```

| ورودی | نوع | پیش‌فرض | توضیح |
| --- | --- | --- | --- |
| `location` | `header`، `footer`، یا نام دیگر | لازم | سربرگ و پابرگ عنصر هم‌نام می‌گیرند، بقیه `div` |
| `children` | Html | لازم | محتوای چیدمان |
| `id` | شناسه چیدمان | — | |
| `sticky` | `none`، `sticky`، `smart` | `none` | `smart`: با پایین رفتن پنهان، با اولین بالا آمدن نمایان (HDR-05) |
| `class` | | — | فقط کلاس چیدمان |

**رفتار:** `smart-header.behavior.ts`، فقط در صفحه‌ای که حالت `smart` دارد بار می‌شود. **آزمون:** `tests/e2e/phase3.spec.ts`.
