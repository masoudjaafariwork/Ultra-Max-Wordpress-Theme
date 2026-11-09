/**
 * Admin component library. Admin screens import only from here
 * (`@ultra-max/ui/admin`), never from shadcn/ui or Radix directly, so the
 * underlying library can change without touching any screen.
 */
export { Button, type ButtonProps } from './components/Button';
export { Dialog, type DialogProps } from './components/Dialog';
export { Tabs, type TabsProps, type TabItem } from './components/Tabs';
export { Switch, type SwitchProps } from './components/Switch';
export { TextField, type TextFieldProps } from './components/TextField';
export { AdminRoot, type AdminRootProps } from './components/AdminRoot';
