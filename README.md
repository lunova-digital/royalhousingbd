# Royalhousingbd.com

Real estate platform from the SRS (`tmp/Final SRS - Royalhousingbd.com.pdf`):
a public website (Blade), a single-admin React panel, and Android/iOS WebView
shells. Laravel 13 / PHP 8.3+, sqlite by default, Vite + React 19 + Tailwind v4.

The public site uses the "Ivory & Ink" theme: a warm ivory page, white bordered cards,
near-black ink for headings and dark bands, one brass accent, Plus Jakarta Sans headings
and the Royal Housing logo (public/images/brand), applied to these
pages — Home, About, Projects (+ Property / Plot / Flat filters), Project Map
(project cards → interactive layout with legend, zoom and a plot side panel),
Gallery (derived from listing and masterplan photos), Contact (info cards + a
form that opens WhatsApp), Privacy and Terms. All copy and assets are our own;
theme tokens live in `resources/css/app.css` under `@theme`.

## Quickstart

```sh
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed      # admin + demo content (demo is skipped in production)
php artisan storage:link        # uploads at storage/app/public → /storage

pnpm install
pnpm build                      # or `pnpm dev` alongside the server for HMR
php artisan serve               # http://localhost:8000  (admin: /admin, login admin@royalhousingbd.com / password)
```

`ADMIN_EMAIL` / `ADMIN_PASSWORD` in `.env` set the seeded login. Image
optimisation uses ImageMagick (`magick` on PATH); without it originals are
stored as-is.

## Layout

The app is built from [laramod](https://github.com/protibimbok/laramod) modules,
listed by hand in `bootstrap/modules.php`. A module is a class
(`Modules/<Name>/<Name>Module.php`) and what it provides is declared by the
contracts it implements: nothing is discovered from the file system. The admin
panel is [laramod-admin](https://github.com/protibimbok/laramod-admin), itself a
module. `php artisan laramod:list` shows every module and what it provides.

```
Modules/<Name>/          one feature each; StudlyCase dirs hold classes, lowercase the rest
  Site/                  Site — shared page data (settings, nav, meta, WhatsApp links), Support/ (YouTube id parsing, WhatsApp link builder),
                         the public layout + partials (`site::layouts.app`, `site::partials.*`) and the site.ts island
  Settings/              Setting, Settings (key/value: WhatsApp, contact, social, SEO, brand, hero), SettingsController
  Media/                 ImageStore (resize + thumbnails via ImageMagick), DocumentStore (PDF brochures), UploadController
  Auth/                  AuthController — Blade login (`login` route), `logout`, password change; AdminUserSeeder
  Dashboard/             DashboardController (counts) + the widgets that fill the admin's dashboard
  Listing/               Listing (optionally linked to a Masterplan for live availability; optional PDF brochure), ListingType (admin-managed kinds: /{slug}), Feature (amenities, many-to-many),
                         ListingImage, ListingService (search: keyword, feature and budget filters), Support/Price (Taka amounts for the budget filter), ListingRequest, Listing/ListingType/FeatureController (admin API), ListingPages (public)
  Masterplan/            Masterplan (an SVG layout), Plot (details of one `g.plan-plot`), MasterplanService, LayoutSvg (SVG validation), MasterplanRequest, PlotsRequest, MasterplanController, MasterplanPages, plan.ts island
  Content/               TickerItem, Video, ContentController (homepage ticker + YouTube list); TextOverride + SiteTexts + TextOverrideLoader
                         (Site → Site text: every public string, rewritable per language), TextController
  Consultation/          Free consultation booking: Availability (the admin's hours, from settings), Scheduler (slot calendar, double-booking guard), Consultation (a booking),
                         ConsultationPages (public: /consultation, slot feed, booking, confirmation + .ics), ConsultationController (admin: bookings list, availability),
                         ConsultationBooked mail, the floating launcher partial + consult.ts widget
  Home/                  HomeController — homepage, About, Gallery, Contact, Privacy/Terms, sitemap.xml, robots.txt

inside a module:
  Http/Controllers, Http/Requests, Models, Services, Database/{Migrations,Seeders}, Tests/Feature
  routes/web.php         public pages (the `web` group)
  routes/api.php         admin JSON under /api/admin (see "How things connect")
  resources/views/       Blade, namespaced by module: view('listing::show')
  resources/js/          dashboard.tsx (the module's admin pages) + pages/, and public islands

app/Models/User.php      the admin account (Laravel default)
app/Providers/           AppServiceProvider — login rate limiter, layout view composer
bootstrap/modules.php    the module list; its order is the wiring order
config/laramod.php       module path/namespace and the route groups
database/migrations/     only the framework's tables; feature tables live in their module
database/seeders/        DatabaseSeeder (module seeders + demo), DemoSeeder (+ assets/layouts/{nw,ne,sw,se}.svg)
resources/js/            what admin pages share: components/ (ui = shadcn), hooks/, lib/ (api.ts, menu.ts)
resources/views/errors/  404 / 500 inside the public layout
mobile/                  Capacitor WebView shell (see mobile/README.md)
tests/Feature/           cross-module tests: public pages + admin API
```

## How things connect

- Public pages: `…Pages` / `HomeController` call `Site::page($meta, $data)` and render
  their module's views, which `@extends('site::layouts.app')`. A page's script is a
  Vite entry its module declares in `viteEntries()` and loads with
  `{{ Modules::vite('masterplan', 'resources/js/plan.ts') }}`.
- The public site is bilingual (English default, Bangla): `?lang=bn` on any URL is
  remembered in a plain `lang` cookie by `Modules\Site\Http\Middleware\SetLocale`
  (a global middleware the Site module declares), which calls `app()->setLocale()`.
  Only the chrome and static copy are translated: Blade uses `__()` / `trans_choice()`
  with short keys. Each module ships `lang/{en,bn}/<page>.php` through
  `ProvidesTranslations`, namespaced by module (`__('listing::index.browse_by_location')`);
  strings shared across modules live in `site::common`, and `lang/{en,bn}/errors.php`
  at the root covers the error pages. English is the source: add a key to the `en`
  file and its translation to `bn`.
  Admin-entered content stays as entered. For `bn` the layout also loads Noto Sans
  Bengali and `app.css` swaps the font tokens under `html:lang(bn)`.
- Every one of those strings can be rewritten from the admin (Site → Site text), so
  public copy must live in a lang file, never inline in Blade. `AppServiceProvider`
  wraps the translation loader in `Modules\Content\Translation\TextOverrideLoader`,
  which lays `text_overrides` rows (locale + key such as `home::index.faq`) over a
  lang file as it loads; `SiteTexts` builds the admin's catalogue from the modules'
  `lang/en` files and flushes the translator after a save. An empty override means
  the built-in text.
- Listing prices are free text; `price_amount` (Taka) drives the budget filter on the
  projects grid (`budget_min` / `budget_max`, 5 lakh to 100 crore). The admin can type
  it, otherwise `Price::parse` reads it from the price text ("৳ 4.5 Crore", "35 Lakh /
  katha", Bangla digits); "call for price" listings have none and drop out of a budget
  search.
- Listing types and features are rows the admin manages (Content → Listing types /
  Features), seeded by the Listing module's seeders. A type's slug is its public page
  (`/plot`, `/plot/{listing}`), so the Listing module is wired last (`order() = -1`) and
  registers `/{type}` as fallback routes: every static page and the admin match first,
  and an unknown slug is a 404. `Site::types()` feeds the header, footer and search.
- Free consultations: every public page carries the Consultation module's floating launcher
  (`consultation::partials.launcher`, included by the site layout) which opens the booking widget in a
  `<dialog>`; `/consultation` has it inline. The widget fetches `/consultation/availability` (the next
  days and their slots in Bangladesh time, labelled in the visitor's language) and posts to
  `/consultation`; the slot is checked against the admin's hours (Leads → Availability, stored as
  `consult_*` settings) and existing bookings inside a transaction. Bookings land in Leads →
  Consultations and an email goes to the site's contact address; the visitor gets a reference,
  an `.ics` file and a WhatsApp link. Times are stored in UTC and shown in `Asia/Dhaka`.
- Admin: laramod-admin answers `/admin/*` (behind `auth`) and mounts the React shell.
  A module adds pages with a dashboard script: `resources/js/dashboard.tsx`, listed in
  both `viteEntries()` and `dashboardScripts()`, calls `registerModule({ id, order,
  menuFilter, routes, fills })` from `@admin/js`. Routes and menu paths are relative
  to `/admin`. The Dashboard module has no page: it fills `<DashboardWidgets.Fill>`.
- Sign in/out is the application's, not the admin's: the Auth module owns the Blade
  login page (route `login`, where `auth` sends guests) and the `logout` route the
  admin's user menu posts to.
- Admin API: JSON under `/api/admin/*`. `config/laramod.php` runs the modules' `api`
  group with the `web` middleware, so it is the session cookie + `auth`.
  `resources/js/lib/api.ts` sends Laravel's `XSRF-TOKEN` cookie back as the
  `X-XSRF-TOKEN` header, so the normal CSRF middleware applies.
- Vite: `laramod-vite-plugin` wraps `laravel-vite-plugin`, builds every declared entry
  and gives each module an `@<module>` alias onto its `resources/` (`@listing/js/types`),
  mirrored into `tsconfig.app.json`. It asks `php artisan laramod:vite`, so PHP must be
  available where Vite runs.
- shadcn/ui: the admin is written against the base-ui variants of ten components
  (`php artisan admin:init` copies them into `resources/js/components/ui`). A button
  that is a link is `<Button nativeButton={false} render={<Link to="…" />}>`, not `asChild`.
- A masterplan is an SVG in which every plot is a `<g class="plan-plot">`; on
  upload each group is keyed `plot-N` by document order (written into its `id`,
  replacing any). Adding one is three steps in the admin: upload
  the layout, fill in the info, then click plots in the builder to enter their
  details. `LayoutSvg` validates and normalises the upload (viewBox, a
  `data-plot` key per group, scripts stripped); a `Plot` row holds the details
  of one group, matched by `svg_key`, and groups without a row show greyed out.
  `Modules/Masterplan/resources/js/plan-map.ts` places the SVG as a Leaflet
  (CRS.Simple) overlay and paints each group by status with classes; it is the
  core shared by the public map and the admin builder.

## Adding to a module

```sh
php artisan make:module Blog                     # new module, listed in bootstrap/modules.php
php artisan make:model Post -m --module=Blog     # every make:* takes --module
```

## Gates

```sh
php artisan test && vendor/bin/pint --test && pnpm build
```
