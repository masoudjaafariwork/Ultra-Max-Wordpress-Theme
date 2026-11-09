/**
 * Admin component library. Admin screens import only from here
 * (`@ultra-max/ui/admin`), never from shadcn/ui or Radix directly, so the
 * underlying library can change without touching any screen.
 */
export { Button, type ButtonProps } from './components/Button';
export { Dialog, type DialogProps } from './components/Dialog';
export { Tabs, type TabsProps, type TabItem } from './components/Tabs';
export { Switch, FieldLabel, type SwitchProps } from './components/Switch';
export { TextField, type TextFieldProps } from './components/TextField';
export { AdminRoot, type AdminRootProps } from './components/AdminRoot';
export { Icon, LogoMark, type IconProps, type IconName } from './components/Icon';
export { PageHeader, Card, SideNav, SettingRow, SaveBar, Tile, KeyValue, Heading, Text, TextLink, PreviewFrame, DemoCard, RichText, type PageHeaderProps, type CardProps, type SideNavProps, type SideNavItem, type SettingRowProps, type SaveBarProps, type TileProps, type KeyValueProps } from './components/Panel';
export { Select, Segmented, ChoiceCards, PresetPicker, ColorField, RangeField, DeviceToggles, CodeField, TextInput, type Choice } from './components/Fields';
export { Badge, Alert, Progress, Toaster, notify, StatusList, BarChart, Steps, type StatusItem } from './components/Feedback';
export { SearchBox, ModuleCard, MediaField, Repeater, DataTable, Tour, type SearchResult, type TourStep } from './components/Advanced';
export { ramp, mix, contrast, on as textOn, normalize as normalizeColor } from './lib/color';
