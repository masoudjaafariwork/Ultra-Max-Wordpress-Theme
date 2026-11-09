<?php
/**
 * Every string of the React panel, translated in PHP.
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Admin;

/**
 * The panel bundles its own React, so it does not use WordPress's script
 * translations. All its text comes from here, through the plugin's normal
 * .po file. `%s`, `%1$s` and `%2$s` are filled in by the panel.
 */
final class Strings {

	/**
	 * Strings by key.
	 *
	 * @return array<string, string>
	 */
	public static function all(): array {
		return array(
			// Common.
			'errorGeneric'          => __( 'Something went wrong. Please try again.', 'ultra-max-core' ),
			'cancel'                => __( 'Cancel', 'ultra-max-core' ),
			'on'                    => __( 'On', 'ultra-max-core' ),
			'off'                   => __( 'Off', 'ultra-max-core' ),
			'inactive'              => __( 'inactive', 'ultra-max-core' ),
			'desktop'               => __( 'Desktop', 'ultra-max-core' ),
			'tablet'                => __( 'Tablet', 'ultra-max-core' ),
			'mobile'                => __( 'Mobile', 'ultra-max-core' ),
			'colorCode'             => __( 'Colour code', 'ultra-max-core' ),
			/* translators: %s: contrast ratio such as 4.8. */
			'contrastGood'          => __( 'White text is easy to read on this colour (ratio %s to 1).', 'ultra-max-core' ),
			/* translators: %s: contrast ratio such as 2.1. */
			'contrastPoor'          => __( 'White text is hard to read on this colour (ratio %s to 1). Buttons will use dark text instead.', 'ultra-max-core' ),
			'jdateInvalid'          => __( 'Write a Jalali date such as 1405/08/18.', 'ultra-max-core' ),
			'mediaChoose'           => __( 'Choose a file', 'ultra-max-core' ),
			'mediaReplace'          => __( 'Replace', 'ultra-max-core' ),
			'mediaRemove'           => __( 'Remove', 'ultra-max-core' ),
			'mediaNone'             => __( 'No file chosen', 'ultra-max-core' ),
			'rowAdd'                => __( 'Add a button', 'ultra-max-core' ),
			'rowRemove'             => __( 'Remove', 'ultra-max-core' ),
			'rowUp'                 => __( 'Move up', 'ultra-max-core' ),
			'rowDown'               => __( 'Move down', 'ultra-max-core' ),
			/* translators: %d: row number. */
			'rowLabel'              => __( 'Button %d', 'ultra-max-core' ),

			// Settings.
			'settingsTitle'         => __( 'Settings', 'ultra-max-core' ),
			'sections'              => __( 'Settings sections', 'ultra-max-core' ),
			'searchLabel'           => __( 'Search all settings', 'ultra-max-core' ),
			'searchPlaceholder'     => __( 'Search all settings… for example "font" or "sidebar"', 'ultra-max-core' ),
			'searchEmpty'           => __( 'Nothing found.', 'ultra-max-core' ),
			'backupAndReset'        => __( 'Backup and reset', 'ultra-max-core' ),
			/* translators: %s: number of changes. */
			'unsaved'               => __( '%s unsaved changes', 'ultra-max-core' ),
			'allSaved'              => __( 'All changes are saved', 'ultra-max-core' ),
			'save'                  => __( 'Save changes', 'ultra-max-core' ),
			'saving'                => __( 'Saving…', 'ultra-max-core' ),
			'saved'                 => __( 'Settings saved', 'ultra-max-core' ),
			/* translators: %s: list of setting names. */
			'savedSome'             => __( 'Saved, except: %s. Check those values.', 'ultra-max-core' ),
			'fixErrors'             => __( 'Fix the highlighted values first.', 'ultra-max-core' ),
			'discard'               => __( 'Discard changes', 'ultra-max-core' ),
			'resetSection'          => __( 'Reset this section', 'ultra-max-core' ),
			/* translators: %s: section name. */
			'resetSectionConfirm'   => __( 'Reset "%s" to its defaults?', 'ultra-max-core' ),
			'sectionReset'          => __( 'The section was reset', 'ultra-max-core' ),
			'confirmBackupNote'     => __( 'A backup of your current settings is taken first, so you can undo this from Backup and reset.', 'ultra-max-core' ),
			'moduleOffNote'         => __( 'This module is off, so these settings do nothing on the site.', 'ultra-max-core' ),
			'turnOn'                => __( 'Turn on', 'ultra-max-core' ),
			'preview'               => __( 'Preview', 'ultra-max-core' ),
			'previewCardTitle'      => __( 'Sample card', 'ultra-max-core' ),
			'previewCardText'       => __( 'Corners, colours and fonts follow your settings.', 'ultra-max-core' ),
			'previewAdd'            => __( 'Add to cart', 'ultra-max-core' ),
			'previewAll'            => __( 'View all', 'ultra-max-core' ),
			'previewSort'           => __( 'Sort', 'ultra-max-core' ),
			'previewRelevant'       => __( 'Most relevant', 'ultra-max-core' ),
			'previewNewest'         => __( 'Newest', 'ultra-max-core' ),
			'previewSwitch'         => __( 'Sample switch', 'ultra-max-core' ),

			// Backups.
			'exportTitle'           => __( 'Export', 'ultra-max-core' ),
			'exportHelp'            => __( 'Download every setting as a file, to keep or to move to another site.', 'ultra-max-core' ),
			'export'                => __( 'Download settings', 'ultra-max-core' ),
			'exported'              => __( 'The settings file was downloaded', 'ultra-max-core' ),
			'importTitle'           => __( 'Import', 'ultra-max-core' ),
			'importHelp'            => __( 'Replace the current settings with a downloaded file.', 'ultra-max-core' ),
			'import'                => __( 'Upload a settings file', 'ultra-max-core' ),
			'importInvalid'         => __( 'This file is not an Ultra Max settings export.', 'ultra-max-core' ),
			'imported'              => __( 'Settings imported', 'ultra-max-core' ),
			/* translators: %s: number of values. */
			'importedSome'          => __( 'Settings imported; %s values were skipped because they are not valid here.', 'ultra-max-core' ),
			'resetAllTitle'         => __( 'Reset everything', 'ultra-max-core' ),
			'resetAllHelp'          => __( 'Return every setting to its default.', 'ultra-max-core' ),
			'resetAll'              => __( 'Reset all settings', 'ultra-max-core' ),
			'resetAllConfirm'       => __( 'Reset every setting to its default?', 'ultra-max-core' ),
			'resetDone'             => __( 'All settings were reset', 'ultra-max-core' ),
			'backupAuto'            => __( 'A backup is taken automatically before every reset, import, restore and the setup wizard. The newest ten are kept.', 'ultra-max-core' ),
			'backupsTitle'          => __( 'Backups', 'ultra-max-core' ),
			'backupNow'             => __( 'Take a backup now', 'ultra-max-core' ),
			'backupTaken'           => __( 'Backup taken', 'ultra-max-core' ),
			'backupsEmpty'          => __( 'No backup yet.', 'ultra-max-core' ),
			'backupWhy'             => __( 'Reason', 'ultra-max-core' ),
			'backupWhen'            => __( 'Date', 'ultra-max-core' ),
			'backupCount'           => __( 'Changed settings', 'ultra-max-core' ),
			'backupAction'          => __( 'Action', 'ultra-max-core' ),
			'restore'               => __( 'Restore', 'ultra-max-core' ),
			'restoreConfirm'        => __( 'Restore this backup?', 'ultra-max-core' ),
			'restored'              => __( 'Backup restored', 'ultra-max-core' ),

			// Tour.
			'tourNavTitle'          => __( 'Sections', 'ultra-max-core' ),
			'tourNavText'           => __( 'Settings are grouped by part of the store. Pick a section here.', 'ultra-max-core' ),
			'tourSearchTitle'       => __( 'Search every setting', 'ultra-max-core' ),
			'tourSearchText'        => __( 'Type part of a name, such as "colour", to jump to it. Ctrl+K opens the search from anywhere on this page.', 'ultra-max-core' ),
			'tourGroupTitle'        => __( 'Each setting explains itself', 'ultra-max-core' ),
			'tourGroupText'         => __( 'Read the short note next to a setting to see what it changes on your store.', 'ultra-max-core' ),
			'tourSaveTitle'         => __( 'Save when you are ready', 'ultra-max-core' ),
			'tourSaveText'          => __( 'Changes apply only after you save. You can reset a section; a backup is taken first.', 'ultra-max-core' ),
			'tourNext'              => __( 'Next', 'ultra-max-core' ),
			'tourBack'              => __( 'Back', 'ultra-max-core' ),
			'tourDone'              => __( 'Got it', 'ultra-max-core' ),
			'tourSkip'              => __( 'Skip the tour', 'ultra-max-core' ),
			/* translators: 1: step number, 2: number of steps. */
			'tourProgress'          => __( 'Step %1$s of %2$s', 'ultra-max-core' ),

			// Dashboard.
			'dashboardTitle'        => __( 'Dashboard', 'ultra-max-core' ),
			/* translators: 1: version, 2: number of active plugins. */
			'versionLine'           => __( 'Version %1$s · theme and %2$s plugins', 'ultra-max-core' ),
			'license'               => __( 'Licence', 'ultra-max-core' ),
			'market'                => __( 'Marketplace', 'ultra-max-core' ),
			'site'                  => __( 'Site', 'ultra-max-core' ),
			'licenseNote'           => __( 'Updates and support follow the licence of the marketplace you bought from.', 'ultra-max-core' ),
			'license_active'        => __( 'Active', 'ultra-max-core' ),
			'license_development'   => __( 'Development copy', 'ultra-max-core' ),
			'license_inactive'      => __( 'Not activated', 'ultra-max-core' ),
			'license_invalid'       => __( 'Invalid', 'ultra-max-core' ),
			'license_not-available' => __( 'Unavailable', 'ultra-max-core' ),
			'market_dev'            => __( 'Development', 'ultra-max-core' ),
			'market_rtl-theme'      => __( 'Rtl-theme', 'ultra-max-core' ),
			'market_zhaket'         => __( 'Zhaket', 'ultra-max-core' ),
			'updates'               => __( 'Updates', 'ultra-max-core' ),
			'updateAvailable'       => __( 'Update available', 'ultra-max-core' ),
			'upToDate'              => __( 'Up to date', 'ultra-max-core' ),
			/* translators: %s: product names. */
			'mismatch'              => __( 'After updating, %s would be on a different major version. Update the theme and every Ultra Max plugin together.', 'ultra-max-core' ),
			'backupBeforeUpdate'    => __( 'Take a backup of your settings before every update; Backup and reset keeps the newest ten.', 'ultra-max-core' ),
			'health'                => __( 'Site health', 'ultra-max-core' ),
			/* translators: %s: number of warnings. */
			'warnings'              => __( '%s warnings', 'ultra-max-core' ),
			'allGood'               => __( 'All good', 'ultra-max-core' ),
			/* translators: %s: number of checks. */
			'checksGood'            => __( '%s healthy', 'ultra-max-core' ),
			/* translators: %s: number of checks. */
			'checksWarning'         => __( '%s warnings', 'ultra-max-core' ),
			/* translators: %s: number of checks. */
			'checksCritical'        => __( '%s errors', 'ultra-max-core' ),
			'viewStatus'            => __( 'View system status', 'ultra-max-core' ),
			'storeAtGlance'         => __( 'Your store', 'ultra-max-core' ),
			'products'              => __( 'Published products', 'ultra-max-core' ),
			'ordersTwoWeeks'        => __( 'Orders in 14 days', 'ultra-max-core' ),
			'pendingComments'       => __( 'Comments to review', 'ultra-max-core' ),
			'ordersChart'           => __( 'Orders per day in the last 14 days', 'ultra-max-core' ),
			/* translators: 1: number of orders, 2: date. */
			'ordersOn'              => __( '%1$s orders on %2$s', 'ultra-max-core' ),
			'showTable'             => __( 'Show the data as a table', 'ultra-max-core' ),
			'noWoo'                 => __( 'Order numbers appear here once WooCommerce is active.', 'ultra-max-core' ),
			'changelog'             => __( 'Recent changes', 'ultra-max-core' ),
			'systemStatus'          => __( 'System status', 'ultra-max-core' ),
			'copyReport'            => __( 'Copy report for support', 'ultra-max-core' ),
			'reportCopied'          => __( 'Report copied. Paste it into your support ticket.', 'ultra-max-core' ),
			'reportCopyFailed'      => __( 'The browser blocked copying. Select the table and copy it by hand.', 'ultra-max-core' ),
			'statusGood'            => __( 'Healthy', 'ultra-max-core' ),
			'statusWarning'         => __( 'Warning', 'ultra-max-core' ),
			'statusCritical'        => __( 'Error', 'ultra-max-core' ),
			'helpTitle'             => __( 'Help', 'ultra-max-core' ),

			// Modules.
			'modulesTitle'          => __( 'Modules', 'ultra-max-core' ),
			'modulesSubtitle'       => __( 'A module that is off loads no CSS or JS file and runs no extra query.', 'ultra-max-core' ),
			'filter'                => __( 'Show', 'ultra-max-core' ),
			/* translators: %s: number of modules. */
			'filterAll'             => __( 'All (%s)', 'ultra-max-core' ),
			/* translators: %s: number of modules. */
			'filterOn'              => __( 'On (%s)', 'ultra-max-core' ),
			/* translators: %s: number of modules. */
			'filterOff'             => __( 'Off (%s)', 'ultra-max-core' ),
			/* translators: %s: size in kilobytes. */
			'modulesWeight'         => __( 'The modules that are on add %s KB of CSS and JS to the pages that use them. Turning a module off removes its share.', 'ultra-max-core' ),
			/* translators: %s: module name. */
			'toggleModule'          => __( 'Turn %s on or off', 'ultra-max-core' ),
			/* translators: %s: size in kilobytes. */
			'kb'                    => __( '%s KB', 'ultra-max-core' ),
			'noAssets'              => __( 'No front-end file', 'ultra-max-core' ),
			'settingsLink'          => __( 'Settings', 'ultra-max-core' ),
			'moduleOn'              => __( 'Module turned on', 'ultra-max-core' ),
			'moduleOff'             => __( 'Module turned off', 'ultra-max-core' ),

			// Wizard.
			'wizardTitle'           => __( 'Setup wizard', 'ultra-max-core' ),
			'wizWelcome'            => __( 'Welcome', 'ultra-max-core' ),
			'wizPlugins'            => __( 'Required plugins', 'ultra-max-core' ),
			'wizDemo'               => __( 'Choose a demo', 'ultra-max-core' ),
			'wizBrand'              => __( 'Logo and colour', 'ultra-max-core' ),
			'wizDone'               => __( 'Done', 'ultra-max-core' ),
			'wizLater'              => __( 'I will do this later', 'ultra-max-core' ),
			/* translators: %s: product name. */
			'wizWelcomeTitle'       => __( 'Welcome to %s', 'ultra-max-core' ),
			'wizWelcomeText'        => __( 'In a few minutes your store will be ready. You can skip any step and continue later from the dashboard.', 'ultra-max-core' ),
			'wizNoEncoding'         => __( 'No encoded code', 'ultra-max-core' ),
			'wizNoEncodingText'     => __( 'No decoder or loader is needed; it installs on any ordinary host.', 'ultra-max-core' ),
			'wizElementorFree'      => __( 'Free Elementor is enough', 'ultra-max-core' ),
			'wizElementorFreeText'  => __( 'Every demo is built without Elementor Pro.', 'ultra-max-core' ),
			'wizAutoBackup'         => __( 'Automatic backup', 'ultra-max-core' ),
			'wizAutoBackupText'     => __( 'Your current settings are backed up before every large change.', 'ultra-max-core' ),
			'wizPluginsText'        => __( 'These plugins will be installed and activated. The optional ones can stay off.', 'ultra-max-core' ),
			'wizInstalling'         => __( 'Installing…', 'ultra-max-core' ),
			'wizReady'              => __( 'Ready', 'ultra-max-core' ),
			'wizWillInstall'        => __( 'Will be installed', 'ultra-max-core' ),
			'wizWillActivate'       => __( 'Will be activated', 'ultra-max-core' ),
			/* translators: %s: plugin name. */
			'wizInstallThis'        => __( 'Install %s', 'ultra-max-core' ),
			'wizDemoTitle'          => __( 'Choose a demo', 'ultra-max-core' ),
			'wizDemoText'           => __( 'Every demo is made with free Elementor and has clearly licensed images.', 'ultra-max-core' ),
			'wizDemoLater'          => __( 'Importing demos arrives with the demo importer in a later version. Your choice is shown here so you can see the styles; continue to set your logo and colour.', 'ultra-max-core' ),
			'wizBrandTitle'         => __( 'Logo and colour of your store', 'ultra-max-core' ),
			'wizBrandText'          => __( 'You can change all of these later in Settings.', 'ultra-max-core' ),
			'wizStoreName'          => __( 'Store name', 'ultra-max-core' ),
			'wizLogo'               => __( 'Logo', 'ultra-max-core' ),
			'wizLogoHelp'           => __( 'A PNG or SVG about 320 by 96 pixels works best.', 'ultra-max-core' ),
			'wizColor'              => __( 'Main colour', 'ultra-max-core' ),
			'wizDoneTitle'          => __( 'Your store is ready!', 'ultra-max-core' ),
			'wizDoneText'           => __( 'Now add your products and menus. You can open this wizard again from the dashboard at any time.', 'ultra-max-core' ),
			'wizViewSite'           => __( 'View the store', 'ultra-max-core' ),
			'wizToDashboard'        => __( 'Go to the dashboard', 'ultra-max-core' ),
			'wizMenus'              => __( 'Build the menus', 'ultra-max-core' ),
			'wizBack'               => __( 'Back', 'ultra-max-core' ),
			'wizSkip'               => __( 'Skip this step', 'ultra-max-core' ),
			'wizStart'              => __( 'Start', 'ultra-max-core' ),
			'wizInstallContinue'    => __( 'Install and continue', 'ultra-max-core' ),
			'wizContinue'           => __( 'Continue', 'ultra-max-core' ),
			'wizSaveContinue'       => __( 'Save and finish', 'ultra-max-core' ),
			'wizWorking'            => __( 'Please wait…', 'ultra-max-core' ),

			// Help.
			'helpSubtitle'          => __( 'Step-by-step guides for every part of the store, right here in your dashboard.', 'ultra-max-core' ),
			'helpSearch'            => __( 'Search the help', 'ultra-max-core' ),
			'helpArticles'          => __( 'Help articles', 'ultra-max-core' ),
			'helpEmpty'             => __( 'The help articles are missing from this build.', 'ultra-max-core' ),
		);
	}
}
