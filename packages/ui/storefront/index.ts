/**
 * Storefront entry of the component library. Exposes `window.UltraMaxUI`
 * with `mount( root )` for markup added later (for example by AJAX) and
 * `get( element )` to control a component from code.
 */
import { createRuntime } from './runtime';
import { accordion } from '../components/accordion/accordion.behavior';
import { dialog } from '../components/dialog/dialog.behavior';
import { drawer } from '../components/drawer/drawer.behavior';
import { dropdown } from '../components/dropdown/dropdown.behavior';
import { tabs } from '../components/tabs/tabs.behavior';
import { tooltip } from '../components/tooltip/tooltip.behavior';

declare global {
	interface Window {
		UltraMaxUI?: ReturnType<typeof createRuntime>;
	}
}

window.UltraMaxUI ??= createRuntime( {
	accordion,
	dialog,
	drawer,
	dropdown,
	tabs,
	tooltip,
} );
