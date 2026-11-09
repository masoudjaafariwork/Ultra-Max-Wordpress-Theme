/**
 * The Ultra Max admin panel: one React app, one page per menu item.
 * PHP passes everything a page needs in window.ultraMaxAdmin.
 */
import { createRoot } from 'react-dom/client';
import { AdminRoot, Toaster } from '@ultra-max/ui/admin';
import { boot } from './boot';
import { DashboardPage } from './pages/DashboardPage';
import { ModulesPage } from './pages/ModulesPage';
import { WizardPage } from './pages/WizardPage';
import { HelpPage } from './pages/HelpPage';
import { SettingsPage } from './settings/SettingsPage';

const PAGES = {
	dashboard: DashboardPage,
	settings: SettingsPage,
	modules: ModulesPage,
	wizard: WizardPage,
	help: HelpPage,
};

const mount = document.getElementById( 'um-admin-app' );
if ( mount && boot ) {
	const Page = PAGES[ boot.page ] ?? DashboardPage;
	createRoot( mount ).render(
		<AdminRoot>
			<Page />
			<Toaster />
		</AdminRoot>
	);
}
