# Ultra Max: instructions for Claude

## What this project is

A monorepo for Ultra Max, a Digikala-style Persian WooCommerce theme with companion plugins. It will be sold on Rtl-theme and Zhaket. The product documents are the source of truth:

- `docs/ROADMAP.fa.md`: goal, positioning, architecture, phases, definition of done, open decisions.
- `docs/FEATURES.fa.md`: the feature catalog. Every feature has an ID such as `PDP-11`, a priority and a source.
- `docs/MARKET.fa.md`: competitor data, market gaps and marketplace rules.
- `docs/PROGRESS.fa.md`: phase status, finished slices, open questions and the next step.
- `docs/PROMPTS.fa.md`: the prompts the user sends to start or continue each phase.

Current status: phase 0, awaiting owner approval. Research and design are done; the HTML prototypes in `design/` are the visual reference for later phases (see `design/README.fa.md`). No product code has been written yet. Update this line when a phase changes.

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
- Libraries must stay replaceable. Outside `packages/ui`, never use Flowbite names or `data-` attributes, or Tailwind classes for looks; pages, templates, Elementor widgets and shortcodes call component APIs and use only layout classes. Admin screens import from `packages/ui/admin`, never from shadcn directly. A lint rule enforces this in CI.
- Shared code lives in `packages/` and is namespace-prefixed at build time.
- Each feature has one renderer, shared by the default templates, the Elementor widgets and the shortcodes.
- Every module has an on/off switch. A disabled module loads no assets and runs no queries.
- WooCommerce template overrides stay minimal. Everything must work with HPOS and with block cart and checkout.
- Source strings are English, with a full `fa_IR` translation. Support PHP 8.1 and later.
- Elementor Free must be enough for every demo. Elementor Pro is optional and must stay compatible.

## Session workflow

1. At the start of every session, read `docs/PROGRESS.fa.md` and the current phase in `docs/ROADMAP.fa.md`.
2. Work only on the feature IDs of the current phase, in small slices.
3. After each slice, run lint and tests, commit with the feature IDs in the message, and update `docs/PROGRESS.fa.md`.
4. When a step needs a decision, an account or data from the user, finish everything that does not depend on it. Record the question in `docs/PROGRESS.fa.md` and ask all questions together at the end of the session.
5. At the end of the session, push `main` to `origin`. Then report in Persian which files changed, what was verified, what is next, and the open questions.
6. A phase closes only after the user approves its visible output and every exit criterion is checked. Then update the status here and in `docs/PROGRESS.fa.md`.

## How to work

- Work phase by phase as the roadmap defines. Cite feature IDs in commit messages.
- A feature is done only when it meets the definition of done in the roadmap. Mark it done in the catalog.
- Any scope change goes into `docs/FEATURES.fa.md` first.
- The user is the product owner, not a developer. Talk to them in Persian, in short plain sentences. Always say exactly which files changed, and never leave unexplained changes in the working tree.
- Project documents are written in Persian.

## Environment

- Windows 11. The Bash tool fails here with an fnm profile error, so use PowerShell.
- Node 24 and npm 11 are installed. There is no PHP on PATH. Docker Desktop is not running by default.
- The remote is `origin` on GitHub. `main` is the working branch.

## Commands

None yet. Phase 1 adds the build, test and packaging commands here.
