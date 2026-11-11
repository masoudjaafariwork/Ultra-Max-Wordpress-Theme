# BottomNav

نوار اپ‌مانند پایین گوشی و تبلت (HDR-10): حداکثر شش آیکون با برچسب، صفحه فعلی مشخص، و عدد روی آیتم‌هایی مثل سبد خرید.

```php
BottomNav::render(
	array(
		'label' => __( 'Bottom navigation', 'ultra-max' ),
		'items' => array(
			array( 'label' => __( 'Home', 'ultra-max' ), 'href' => home_url( '/' ), 'icon' => 'home', 'current' => is_front_page() ),
			array( 'label' => __( 'Cart', 'ultra-max' ), 'href' => wc_get_cart_url(), 'icon' => 'cart', 'count' => 2, 'count_text' => '۲', 'count_key' => 'cart' ),
		),
	)
)->print();
```

| ورودی | نوع | پیش‌فرض | توضیح |
| --- | --- | --- | --- |
| `label` | متن | لازم | نام دسترس‌پذیر ناحیه `nav` |
| `items` | فهرست آیتم | لازم | هر آیتم: `label`، `href`، `icon`، و اختیاری `current`، `count`، `count_text`، `count_key`، `opens` |
| `class` | | — | فقط کلاس چیدمان |

- `count_key` عدد را با بخش‌های سبد ووکامرس زنده به‌روز می‌کند.
- `opens` یک کشو را باز می‌کند (مثلاً منوی موبایل) و اگر صفحه آن کشو را نداشته باشد، پیوند آیتم باز می‌شود.

**رفتار:** `bottom-nav.behavior.ts`. **آزمون:** `tests/e2e/phase3.spec.ts`.
