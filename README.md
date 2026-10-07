# Royalhousingbd.com

Real estate platform from the SRS (`tmp/Final SRS - Royalhousingbd.com.pdf`):
a public website (Blade), a single-admin React panel, and Android/iOS WebView
shells. Laravel 13 / PHP 8.3+, sqlite by default, Vite + React 19 + Tailwind v4.

The public site follows the layout and interaction patterns of evaly.com.bd:
a light, high-contrast marketplace look (white bordered cards, red accent,
near-black hero panels and footer, Plus Jakarta Sans headings) applied to these
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
  Media/                 ImageStore (resize + thumbnails via ImageMagick), UploadController
  Auth/                  AuthController — Blade login (`login` route), `logout`, password change; AdminUserSeeder
  Dashboard/             DashboardController (counts) + the widgets that fill the admin's dashboard
  Listing/               Listing (optionally linked to a Masterplan for live availability), ListingImage, ListingService, ListingRequest, ListingController (admin API), ListingPages (public)
  Masterplan/            Masterplan (an SVG layout), Plot (details of one `g.plan-plot`), MasterplanService, LayoutSvg (SVG validation), MasterplanRequest, PlotsRequest, MasterplanController, MasterplanPages, plan.ts island
  Content/               TickerItem, Video, ContentController (homepage ticker + YouTube list)
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
