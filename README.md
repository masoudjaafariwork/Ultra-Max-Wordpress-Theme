# Ultra Max — مونو‌ریپوی وردپرس

محیط مشترک برای توسعهٔ چند قالب و افزونه، با npm workspaces، ساخت assetها با esbuild و وردپرس محلی با ابزار رسمی `@wordpress/env`.

## ساختار

```text
themes/
  ultra-max/           # قالب بلوکی، قابل ویرایش با Site Editor
    src/               # CSS منبع
    build/             # خروجی خودکار، خارج از Git
    templates/         # الگوهای index، single و page
    parts/             # سربرگ و پابرگ
    theme.json         # تنظیمات و سبک‌های ویرایشگر
plugins/
  ultra-max-core/      # افزونهٔ مستقل از قالب
    src/               # JS و CSS منبع
    build/             # خروجی خودکار، خارج از Git
scripts/               # ابزارهای مشترک ساخت، lint و بسته‌بندی
dist/                  # ZIPهای نصب‌شدنی، خارج از Git
.wp-env.json           # محیط توسعهٔ وردپرس
```

قالب و افزونه مستقل نصب می‌شوند؛ منطق سایت را در افزونه و ظاهر را در قالب نگه دارید. فایل‌های هستهٔ وردپرس، دیتابیس، uploads و وابستگی‌ها در Git ذخیره نمی‌شوند.

## شروع توسعه

پیش‌نیازها: **Node.js 22+**، **npm 10+**، **Docker با Compose و daemon فعال**. برای بررسی نحو PHP روی سیستم میزبان، **PHP CLI 8.2+** هم لازم است.

از ریشهٔ ریپازیتوری اجرا کنید:

```sh
npm ci
npm run build
npm run wp:start
npm run wp:activate
npm run dev
```

وردپرس در `http://localhost:8888` و مدیریت در `http://localhost:8888/wp-admin` باز می‌شود. حساب پیش‌فرض محلی wp-env: نام کاربری `admin` و رمز `password`. این محیط و حساب فقط برای توسعهٔ محلی هستند.

`npm run dev` تغییرات JS و CSS موجود در `src` همهٔ workspaceها را دنبال می‌کند. خروجی در `build` نوشته می‌شود؛ مرورگر را برای مشاهدهٔ تغییرات refresh کنید. تغییرات PHP و قالب‌های HTML مستقیماً در محیط محلی دیده می‌شوند. پس از اضافه‌کردن یک فایل ورودی جدید یا workspace جدید، watcher را دوباره اجرا کنید.

برای آزمایش افزونه، در یک برگه از بلوک Shortcode استفاده کنید:

```text
[ultra_max_hello name="وردپرس"]
```

برای توقف وردپرس:

```sh
npm run wp:stop
```

## دستورهای روزمره

| دستور | کاربرد |
| --- | --- |
| `npm run build` | ساخت فشردهٔ assetهای همهٔ بسته‌ها |
| `npm run dev` | ساخت و دنبال‌کردن تغییرات همهٔ بسته‌ها |
| `npm run lint` | ESLint و بررسی نحو همهٔ فایل‌های PHP |
| `npm run lint:js` | بررسی JavaScript بدون نیاز به PHP |
| `npm run lint:php` | بررسی نحو PHP با PHP CLI میزبان |
| `npm run package` | ساخت assetها و ZIP جداگانه برای هر بسته |
| `npm run build -w @ultra-max/theme` | ساخت فقط قالب |
| `npm run build -w @ultra-max/core` | ساخت فقط افزونه |
| `npm run wp -- run cli plugin list` | اجرای WP-CLI در محیط محلی |

`npm run lint` استاندارد کامل WordPress Coding Standards یا تحلیل نوع PHP نیست؛ JavaScript و نحو PHP را بررسی می‌کند.

## نصب و انتشار بسته‌ها

```sh
npm run package
```

خروجی‌ها:

```text
dist/ultra-max-0.1.0.zip
dist/ultra-max-core-0.1.0.zip
```

هر ZIP یک پوشه با slug همان محصول دارد و از بخش بارگذاری قالب/افزونه در وردپرس نصب می‌شود. فایل‌های منبع `src`، sourcemapها، ابزارهای توسعه و `node_modules` داخل ZIP قرار نمی‌گیرند. فیلد `files` در `package.json` هر workspace فهرست مجاز فایل‌های انتشار است؛ پوشه‌های جدید PHP، تصاویر، ترجمه‌ها یا سایر فایل‌های لازم را به آن اضافه کنید.

نسخهٔ هر محصول مستقل است. هنگام انتشار، `version` در `package.json` همان محصول و هدر `Version` در `style.css` یا فایل اصلی افزونه را با هم تغییر دهید. برای انتشار فقط یک محصول:

```sh
npm run build -w @ultra-max/core
npm run package -w @ultra-max/core
```

## افزودن قالب یا افزونهٔ جدید

1. پوشهٔ محصول را در `themes/<slug>` یا `plugins/<slug>` بسازید؛ می‌توانید از نمونهٔ موجود شروع کنید.
2. در `package.json` محصول، نام workspace را یکتا کنید و `version` و `files` را تنظیم کنید. مسیر scriptهای مشترک مانند نمونه‌ها باقی می‌ماند. ابزار ساخت فایل‌های `.js` و `.css` سطح اول `src` را به‌عنوان ورودی می‌گیرد؛ فایل‌های دیگر را از آن‌ها import کنید.
3. هدر وردپرس، نام فایل اصلی افزونه، text domain، namespace و handleهای asset را متناسب با slug تغییر دهید تا تداخل ایجاد نشود.
4. مسیر محصول را به `themes` یا `plugins` در `.wp-env.json` اضافه کنید. شناسایی آن در npm خودکار است، اما wp-env به فهرست صریح نیاز دارد.
5. `npm install` را برای به‌روزرسانی lockfile اجرا کنید؛ سپس `npm run build` و `npm run wp:start` را دوباره اجرا کنید.
6. محصول جدید را با WP-CLI یا پیشخوان فعال کنید. دستور `wp:activate` مخصوص دو محصول نمونه است.

## تنظیم محیط محلی

تنظیمات شخصی را در `.wp-env.override.json` قرار دهید؛ این فایل در Git ثبت نمی‌شود. مثلاً برای پورت متفاوت:

```json
{
  "port": 8890,
  "testsPort": 8891
}
```

نسخهٔ PHP محیط توسعه در `.wp-env.json` روی 8.2 تنظیم شده است. مقدار `core: null` آخرین نسخهٔ پایدار وردپرس را دریافت می‌کند. برای آزمون با نسخهٔ ثابت، مقدار `core` را مثلاً به `"WordPress/WordPress#6.6.2"` تغییر دهید و `npm run wp -- start --update` اجرا کنید. حداقل نسخهٔ اعلام‌شدهٔ محصولات 6.6 است؛ سازگاری با نسخه‌های مختلف را پیش از انتشار جداگانه بررسی کنید.

ویرایش‌های Site Editor در دیتابیس ذخیره می‌شوند. برای ثبت آن‌ها در Git، خروجی HTML الگوها و تنظیمات را به فایل‌های قالب منتقل کنید. اطلاعات محلی با توقف محیط باقی می‌مانند؛ دستور `npm run wp -- destroy` محیط و داده‌های محلی آن را حذف می‌کند.

## CI

GitHub Actions روی push و pull request، JavaScript و نحو PHP را با PHP 8.2 و 8.3 بررسی می‌کند، ZIPها را می‌سازد و به‌عنوان artifact نگه می‌دارد. یک job جداگانه وردپرس را با Docker بالا می‌آورد، قالب و افزونه را فعال می‌کند و خروجی و escape شدن ورودی shortcode را بررسی می‌کند. این job به دسترسی شبکه برای دریافت وردپرس و imageهای Docker نیاز دارد.

## مجوز

کد نمونه تحت GPL-2.0-or-later است؛ متن مجوز در `LICENSE` قرار دارد و در ZIP هر محصول نیز اضافه می‌شود.
