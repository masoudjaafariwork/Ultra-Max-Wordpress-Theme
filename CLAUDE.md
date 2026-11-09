# Ultra Max: instructions for Claude

## What this project is

A monorepo for Ultra Max, a Digikala-style Persian WooCommerce theme with companion plugins. It will be sold on Rtl-theme and Zhaket. The product documents are the source of truth:

- `docs/ROADMAP.fa.md`: goal, positioning, architecture, phases, definition of done, open decisions.
- `docs/FEATURES.fa.md`: the feature catalog. Every feature has an ID such as `PDP-11`, a priority and a source.
- `docs/MARKET.fa.md`: competitor data, market gaps and marketplace rules.
- `docs/PROGRESS.fa.md`: phase status, finished slices, open questions and the next step.
- `docs/PROMPTS.fa.md`: the prompts the user sends to start or continue each phase.

Current status: phases 0 and 1 closed on 2026-11-09. Phase 2 (theme core and settings panel) is next and has not started. The HTML prototypes in `design/` are the approved visual reference (see `design/README.fa.md`). Market, brand and logo decisions are deferred until before phase 11; keep the internal code name `ultra-max` and read the display name and logo from one setting. Update this line when a phase changes.

## Hard rules

- Everything in this repository is authored by Claude from scratch. Never copy files, code or decisions from the previous v0.2 work that existed before 2026-11-09. It is outside the project and must not be referenced, restored or pushed.
- One exception, approved by the owner on 2026-11-09: well-known, tested open-source libraries such as shadcn/ui, Radix UI, Tailwind CSS and Flowbite, installed through their official package or CLI. Their licenses must be GPL-compatible, their versions pinned, and each one recorded in `THIRD-PARTY.md`. Never build from scratch a base component that one of these libraries already provides.
- The references in `D:\Wordpress\Assets` are feature checklists only. Never copy code, CSS, JS, images or fonts from the Woodmart backup or from Digits, and never decode Digits' protected files.
- Never use Digikala's name, logo, images or brand colors in the product or the demos.
- Every demo image and font must be owned or openly licensed, with its provenance recorded.
- No ionCube or other code encoding. Only the per-marketplace license module is added at build time.
- Rtl-theme rules apply to every feature:
  - Every date shown on the front end uses the Jalali calendar.
  - No personal links or contact details in the product, demos or help.
  - Each demo ships as a Duplicator package.
  - A Persian `help.pdf` ships with the package.

## Architecture

- `themes/ultra-max` is a classic theme, not a block theme, and holds presentation only. `themes/ultra-max-child` is its child theme.
- `plugins/ultra-max-core` holds the Elementor layout builder, widgets, store modules and the demo importer.
- `plugins/ultra-max-auth` holds OTP login, SMS gateways and notifications.
- `plugins/ultra-max-invoice` holds invoices, labels and order documents, and later the Moadian integration.
- `plugins/ultra-max-seo` holds meta, sitemaps, structured data, redirects and SEO analysis. It must coexist with Yoast and Rank Math without printing anything twice.
- Every UI element comes from the central component library in `packages/ui` (CORE-20): shadcn/ui (Radix) in the admin, Tailwind CSS 4 with Flowbite (free MIT core) on the storefront, all restyled with our shared design tokens. Storefront HTML is rendered by PHP; only commerce-specific pieces such as the product card are ours, composed from library parts.
- Build components the way you would in React. Each component is a typed function with props, composes smaller components, keeps no global state, and has one name across PHP, TypeScript, the admin, the catalog and the docs. Each component keeps three layers apart: its API (ours, stable), its look (Tailwind classes, inside the component) and its behavior (a small TypeScript module, the only place that calls Flowbite).
- Libraries must stay replaceable. Outside `packages/ui`, never use Flowbite names or `data-` attributes, or Tailwind classes for looks; pages, templates, Elementor widgets and shortcodes call component APIs and use only layout classes. Admin screens import from `packages/ui/admin`, never from shadcn directly. A lint rule (`npm run lint:boundaries`) enforces this.
- Shared code lives in `packages/` and is namespace-prefixed at build time.
- Each feature has one renderer, shared by the default templates, the Elementor widgets and the shortcodes.
- Every module has an on/off switch. A disabled module loads no assets and runs no queries.
- WooCommerce template overrides stay minimal. Everything must work with HPOS and with block cart and checkout.
- Source strings are English, with a full `fa_IR` translation. Support PHP 8.1 and later.
- Elementor Free must be enough for every demo. Elementor Pro is optional and must stay compatible.

## Session workflow

1. At the start of every session, read `docs/PROGRESS.fa.md` and the current phase in `docs/ROADMAP.fa.md`.
2. Work only on the feature IDs of the current phase, in small slices.
3. After each slice, run `npm run verify:quick`, commit with the feature IDs in the message, and update `docs/PROGRESS.fa.md`.
4. When a step needs a decision, an account or data from the user, finish everything that does not depend on it. Record the question in `docs/PROGRESS.fa.md` and ask all questions together at the end of the session.
5. At the end of the session, run the full `npm run verify` and push `main` to `origin` only when it passes. Then report in Persian which files changed, what was verified, what is next, and the open questions.
6. A phase closes only after the user approves its visible output and every exit criterion is checked. Then update the status here and in `docs/PROGRESS.fa.md`.

## How to work

- Work phase by phase as the roadmap defines. Cite feature IDs in commit messages.
- A feature is done only when it meets the definition of done in the roadmap. Mark it done in the catalog.
- Any scope change goes into `docs/FEATURES.fa.md` first.
- The user is the product owner, not a developer. Talk to them in Persian, in short plain sentences. Always say exactly which files changed, and never leave unexplained changes in the working tree.
- Project documents are written in Persian.

## Environment

- Windows 11. The Bash tool fails here with an fnm profile error, so use PowerShell.
- Node 24 and npm 11 are installed. There is no PHP on PATH; PHP and Composer run in Docker (`composer:2.8`, `wordpress:cli-php8.1`). Docker Desktop must be running for the site, PHP tools and packaging.
- The remote is `origin` on GitHub. `main` is the working branch. GitHub only stores the code. The owner decided on 2026-11-09 that nothing runs on GitHub: no Actions or other hosted CI. Never add a workflow; every check runs on this computer through `npm run verify`.
- Network limits on this machine (Iran): repo.packagist.org, deb.debian.org and Playwright's browser download are blocked; Docker Hub, npm, GitHub and wordpress.org work. So:
  - The site uses Docker Compose with official images, not wp-env (wp-env builds images with apt from Debian).
  - Playwright uses the installed Microsoft Edge (`channel: msedge`). Screenshot tests run in Playwright's Linux Docker image, which already contains its browsers.
  - Packagist needs the owner's VPN (decided on 2026-11-09). With the VPN on, `npm run composer -- install` installs the PHP dev tools (PHPCS, PHPStan, PHPUnit). Once they are in `vendor/`, the VPN is not needed to run them. Never point Composer at a mirror.
- Admin screens bundle React 19 (shadcn/ui needs it); WordPress 7.1 still ships React 18.

## Commands

All commands run from the repository root. `npm ci` once after cloning.

| Command | What it does |
| --- | --- |
| `npm run dev` | One command for a working environment: build everything, start the Docker site, print the URLs. |
| `npm run verify` | Every check of the project, on this computer: build, all linters, types, unit tests, PHPCS, PHPStan, PHPUnit on PHP 8.1 and 8.4, Flowbite acceptance, end-to-end, screenshots, Plugin/Theme Check, both marketplace packages built and installed. Prints a pass/fail summary. Run before every push. `verify:quick` runs only the checks that need no site. |
| `npm run build` | Copy shared PHP packages into each product under its namespace, refresh autoloaders, build JS/CSS (RTL + generated LTR) into each product's `assets/build`. `--dev` keeps source maps. |
| `npm run watch` | Rebuild on every change. |
| `npm run env:start` / `env:stop` / `env:reset` | Start (and prepare on first run), stop, or wipe and recreate the site at http://localhost:8888 (admin / password). |
| `npm run wp -- <args>` | WP-CLI on the site, for example `npm run wp -- plugin list`. |
| `npm run env:logs` | Follow the PHP debug log. |
| `npm run lint` | ESLint, Stylelint, library boundary rule, PHPCS (WordPress standards) and PHPStan level 9. |
| `npm run typecheck` | TypeScript, no emit. |
| `npm run test:unit` | Vitest unit tests of the build scripts. |
| `npm run test:php` | PHPUnit on real WordPress in the Docker site (needs `composer install`). |
| `npm run test:e2e` | All Playwright suites; needs the site. Single suite: `npx playwright test --project=e2e` (also `acceptance`, `components-desktop`, `components-mobile`). |
| `npm run test:visual` | Component tests with screenshot comparison in Playwright's Linux image. `test:visual:update` approves the current look. |
| `npm run check:wp` | Plugin Check on each plugin and Theme Check on the theme. |
| `npm run i18n` | Extract `.pot`, merge into `fa_IR.po`, compile `.mo`, `.l10n.php` and `.json`. Needs the site. Translators edit only `languages/*.po`. |
| `npm run help:pdf` | Build `dist/help.pdf` from `docs/help/fa`. |
| `npm run package` | Build one bundle per marketplace in `dist/<market>/` (`-- --market=zhaket` for one). |
| `npm run test:package` | Install a built package on a fresh throwaway site and check it (`-- --market=rtl-theme`). |
| `npm run screenshot` | Regenerate the theme's `screenshot.png`. |

Live component catalog (dev-only plugin `tools/catalog`, never shipped): storefront at http://localhost:8888/?um-catalog, admin at Tools > Ultra Max components.
