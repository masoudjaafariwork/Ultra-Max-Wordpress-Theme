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