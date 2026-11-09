# اجزای پنل مدیریت

صفحه‌های پنل فقط از اینجا import می‌کنند:

```tsx
import { AdminRoot, Button, Dialog, Switch, Tabs, TextField } from '@ultra-max/ui/admin';
```

هر صفحه داخل `AdminRoot` رندر می‌شود؛ جهت راست‌چین را به Radix می‌دهد و محدوده استایل (`um-scope`) را می‌سازد.

| جزء | ورودی‌ها |
| --- | --- |
| `Button` | `variant`: `primary`، `outline`، `soft`، `ghost`، `danger`؛ `size`: `sm`، `md`، `lg`؛ همه ویژگی‌های `button` |
| `Dialog` | `title`، `description`، `trigger`، `children`، `footer`، `open`، `onOpenChange` |
| `Tabs` | `label`، `items` (`value`، `label`، `content`)، `defaultValue` |
| `Switch` | `id`، `label`، `checked`، `defaultChecked`، `disabled`، `onCheckedChange` |
| `TextField` | `id`، `label`، `help` و همه ویژگی‌های `input` |

فایل‌های `components/ui` کپی رسمی shadcn/ui هستند و با `npx shadcn@4.22.2 add <name>` در پوشه `packages/ui` اضافه می‌شوند. صفحه‌ها هرگز مستقیم از آن‌ها import نمی‌کنند؛ قاعده lint این را می‌گیرد.

این صفحه‌ها React 19 همراه دارند، چون وردپرس ۷.۱ هنوز React 18 دارد. وقتی وردپرس React 19 را آورد، فقط ورودی ساخت عوض می‌شود.

**آزمون:** `tests/e2e/admin-catalog.spec.ts`.
## اجزای پنل الترا مکس (فاز ۲)

همه از `@ultra-max/ui/admin` وارد می‌شوند. صفحه‌های پنل در `plugins/ultra-max-core/assets/src/admin` فقط کلاس چیدمان دارند.

| جزء | کار | ورودی‌های اصلی |
| --- | --- | --- |
| `Icon`، `LogoMark` | آیکون‌های همین پروژه و نشان محصول | `name`، `directional` |
| `PageHeader` | نوار بالای هر صفحه پنل | `title`، `subtitle`، `actions`، `children` |
| `Card` | پنل سفید با سر و پانویس | `title`، `icon`، `aside`، `footer` |
| `SideNav` | فهرست بخش‌ها؛ در صفحه باریک افقی | `items`، `current`، `onSelect` |
| `SettingRow` | یک تنظیم: برچسب و توضیح کنار کنترل | `label`، `help`، `token`، `group` |
| `SaveBar` | نوار چسبان ذخیره | `message`، `dirty` |
| `Heading`، `Text`، `TextLink` | متن پنل | `size`، `tone` |
| `Select`، `Segmented`، `ChoiceCards`، `PresetPicker` | انتخاب یکی از چند گزینه | `value`، `onChange`، `options` |
| `ColorField` | رنگ با کد و بررسی کنتراست | `contrastCheck` |
| `RangeField` | لغزنده با عدد | `min`، `max`، `step`، `unit` |
| `DeviceToggles` | نمایش در دسکتاپ، تبلت و موبایل | `labels` |
| `CodeField`، `TextInput` | کد چپ‌چین و متن | `language`، `error` |
| `MediaField` | انتخاب فایل از کتابخانه رسانه وردپرس | `accept` |
| `Repeater` | ردیف‌های قابل افزودن و جابه‌جایی | `renderRow`، `newRow` |
| `Badge`، `Alert`، `Progress` | وضعیت | `tone` |
| `Toaster`، `notify` | پیام کوتاه، روی sonner | |
| `StatusList` | جدول وضعیت سیستم | `items` |
| `BarChart` | نمودار ستونی همراه جدول داده | `bars` |
| `Steps` | مرحله‌های جادوی راه‌اندازی | `steps`، `current` |
| `SearchBox` | جستجو با فهرست نتیجه و Ctrl+K، الگوی combobox | `search`، `onPick` |
| `ModuleCard` | یک ماژول با کلید | `on`، `onToggle`، `weight` |
| `DataTable` | جدول ساده | `columns`، `rows` |
| `DemoCard` | کارت انتخاب دمو | `selected` |
| `RichText` | متن راهنما | `html` |
| `Tour` | راهنمای قدم‌به‌قدم صفحه | `steps`، `onFinish` |
| `PreviewFrame` | قاب پیش‌نمایش زنده | `style` |

**تبدیل حالت Radix:** نسخه ۱.۸ Radix حالت را با `data-state` و `data-orientation` می‌نویسد، ولی سبک‌های نصب‌شده shadcn انتظار `data-checked` و `data-horizontal` دارند. `admin.css` این‌ها را با `@custom-variant` به هم وصل می‌کند.

**آزمون:** `tests/e2e/phase2.spec.ts` (پنل) و `tests/unit/phase2.test.ts` (رنگ).
