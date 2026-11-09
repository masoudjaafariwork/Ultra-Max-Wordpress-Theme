# کتابخانه‌ها و منابع بیرونی

هر کتابخانه‌ای که در محصول یا در ابزار ساخت به کار می‌رود اینجا ثبت می‌شود: نام، نسخه قفل‌شده، مجوز و جای استفاده. همه مجوزها با GPL وردپرس سازگارند. نسخه‌ها در `package-lock.json` و `composer.lock` قفل‌اند و فقط پس از سبز شدن همه تست‌ها به‌روز می‌شوند.

## همراه محصول (به دست خریدار می‌رسد)

| نام | نسخه | مجوز | کجا |
| --- | --- | --- | --- |
| Flowbite (بخش رایگان) | 4.0.2 | MIT | رفتار منوی بازشو، زبانه، آکاردئون و راهنمای کوچک در `packages/ui`. فقط داخل فایل‌های `*.behavior.ts` |
| Popper.js (وابستگی Flowbite) | 2.11.x | MIT | جای‌گذاری منو و راهنمای کوچک |
| Tailwind CSS (خروجی CSS) | 4.3.3 | MIT | فقط کلاس‌های به‌کاررفته در CSS ساخته‌شده؛ شامل ریست preflight که به `.um-scope` محدود شده است |
| فونت وزیرمتن | 33.0.3 | SIL OFL 1.1 | `packages/ui/fonts/vazirmatn` همراه متن مجوز |
| فونت استعداد (فایل متغیر) | 8.5 | SIL OFL 1.1 | `themes/ultra-max/assets/fonts/estedad` همراه متن مجوز. از انتشار رسمی مخزن aminabedi68/Estedad در گیت‌هاب دریافت شد |
| shadcn/ui (کد اجزا، با ابزار رسمی shadcn 4.22.2 و سبک radix-nova و حالت راست‌چین) | — | MIT | `packages/ui/admin/components/ui`. این فایل‌ها کپی رسمی shadcn هستند و فقط از طریق `packages/ui/admin` استفاده می‌شوند. در فاز ۲ این اجزا اضافه شد: card، select، slider، checkbox، radio-group، toggle-group، textarea، badge، alert، separator، progress، sonner، table، popover، command |
| Radix UI | 1.8.1 | MIT | رفتار و دسترس‌پذیری اجزای پنل |
| React و React DOM | 19.1.10 | MIT | فقط صفحه‌های پنل ما. وردپرس ۷.۱ هنوز React 18 دارد و اجزای امروزی shadcn به React 19 نیاز دارند، پس React 19 همراه همان صفحه‌ها بسته‌بندی می‌شود |
| class-variance-authority | 0.7.1 | Apache-2.0 | نسخه‌های ظاهری اجزای shadcn |
| cn (از نگهدارنده shadcn) | 0.5.0 | MIT | ترکیب کلاس‌ها در اجزای shadcn |
| lucide-react | 1.61.0 | ISC | آیکون‌های اجزای shadcn |
| tw-animate-css | 1.4.0 | MIT | انیمیشن باز و بسته شدن اجزای پنل |
| sonner | 2.0.8 | MIT | پیام‌های کوتاه پنل، از طریق جزء Toaster در `packages/ui/admin`. وابستگی next-themes جزء رسمی shadcn حذف شد چون پیشخوان همیشه روشن است |
| cmdk | 1.1.1 | MIT | وابستگی جزء command در shadcn؛ فعلاً در صفحه‌ها استفاده نمی‌شود |

## فقط ابزار توسعه (در بسته خریدار نیست)

| نام | نسخه | مجوز | کار |
| --- | --- | --- | --- |
| esbuild | 0.28.2 | MIT | ساخت JS و TS |
| PostCSS | 8.5.31 | MIT | پردازش CSS |
| @tailwindcss/postcss | 4.3.3 | MIT | ساخت CSS |
| rtlcss | 4.3.0 | MIT | ساخت خودکار نسخه چپ‌چین |
| TypeScript | 6.0.3 | Apache-2.0 | بررسی نوع |
| ESLint، typescript-eslint | 10.13.0، 8.72.1 | MIT | قواعد JS و TS |
| Stylelint، stylelint-config-standard | 17.16.0، 40.0.0 | MIT | قواعد CSS |
| Playwright | 1.64.1 | Apache-2.0 | تست مرورگر و ساخت help.pdf |
| axe-core برای Playwright | 4.14.0 | MPL-2.0 | تست دسترس‌پذیری |
| Vitest | 5.0.5 | MIT | تست واحد اسکریپت‌ها |
| marked | 18.1.1 | MIT | تبدیل راهنمای Markdown به HTML برای help.pdf |
| PHP_CodeSniffer و WordPress Coding Standards | ^3 | BSD-3-Clause، MIT | قواعد PHP |
| PHPCompatibilityWP | ^2.1 | LGPL-3.0 | سازگاری با PHP 8.1 به بالا |
| PHPStan، phpstan-wordpress، woocommerce-stubs | ^2 | MIT | تحلیل ایستای PHP |
| PHPUnit، phpunit-polyfills، wp-phpunit | ^9.6، ^4، ^6.8 | BSD-3-Clause، GPL | تست PHP روی وردپرس واقعی |

## ایمیج‌های Docker (فقط محیط توسعه)

| ایمیج | کار |
| --- | --- |
| `wordpress:php8.1-apache` | سایت آزمایشی؛ هسته وردپرس هنگام راه‌اندازی به آخرین نسخه به‌روز می‌شود |
| `wordpress:cli-php8.1` | WP-CLI و اجرای PHPUnit |
| `mariadb:11.8` | پایگاه داده |
| `composer:2.8` | Composer و PHP بدون نصب PHP روی ویندوز |

افزونه‌های WooCommerce، Elementor، Plugin Check و Theme Check فقط روی سایت آزمایشی از wordpress.org نصب می‌شوند و جزو مخزن یا بسته نیستند.
