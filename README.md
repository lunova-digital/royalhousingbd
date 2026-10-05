# realestate1-next

The Royalhousingbd.com real estate platform
rebuilt on **Next.js 16** (App Router, React 19, Tailwind v4) with **Drizzle ORM on Postgres**:
a public website, a single-admin panel, and a JSON admin API, all in one app.

The public site keeps the layout and interaction patterns of the benchmark (royalhousingnepal.com):
dark navy theme, Playfair Display headings with a red accent word, and these pages: Home, About,
Projects (+ Property / Plot / Flat filters), Project Map (project cards → interactive Leaflet layout
with legend, zoom and a plot side panel), Gallery, Contact (info cards + a form that opens WhatsApp),
Privacy and Terms. All copy and assets are our own; theme tokens live in `app/globals.css` under `@theme`.

## Quickstart

```sh
cp .env.example .env          # APP_URL, DATABASE_URL (Postgres), ADMIN_EMAIL / ADMIN_PASSWORD
createdb realestate1          # or any Postgres database DATABASE_URL points at
pnpm install
pnpm db:migrate               # applies db/migrations
pnpm db:seed                  # admin account + demo content (demo is skipped when NODE_ENV=production)
pnpm dev                      # http://localhost:3000  (admin: /admin, sign in at /login)
```

Uploads are stored under `storage/uploads` (override with `STORAGE_PATH`) and served from `/storage/…`
by a route handler; they are resized and re-encoded with sharp on the way in.

## Layout

The app is built from **modules** under `modules/<name>/`: one feature each, with its tables, services,
validation, API handlers, public pages and admin pages side by side. The `app/` directory is Next's
file-system router and holds **only re-exports**: a route file imports the implementation from its module
(`export { default, generateMetadata } from '@/modules/home/pages/about'`), so the URL map is visible in
`app/` while the code lives with its feature. Nothing is discovered from the file system: the admin
shell's module list is written by hand in `modules/admin/registry.ts`, and `db/schema.ts` gathers every
module's tables for the Drizzle client.

```
app/                      the routes, each a re-export from a module
  (site)/                 public pages under the public layout (modules/site/layout.tsx)
  (admin)/                a second root layout with the admin tokens: /login and /admin/*
  api/admin/*             the admin JSON API (session cookie + requireAdmin), api/auth/password
  storage/[...path]       uploaded files
  sitemap.ts, robots.ts, not-found.tsx, globals.css
modules/<name>/
  schema.ts               Drizzle tables (+ relations); drizzle.config.ts globs these for migrations
  types.ts                row types and the module's input/option types, shared by server and client
  service.ts              queries and writes (what the Laravel services + models did)
  validation.ts           zod schemas + normalisers for the admin API
  api/*.ts                route handlers (GET/POST/PUT/DELETE), wrapped by lib/http.ts `handle()`
  pages/*.tsx             public server components + their metadata
  components/*.tsx        public UI (client components where interaction needs it)
  admin/*.tsx             admin pages (client components talking to /api/admin)
  admin.ts                what the module adds to the admin shell: menu entries, dashboard widgets

  site/        settings-driven chrome (header, footer, bottom nav, WhatsApp float, lightbox), page metadata,
               nav list, icon set, YouTube/WhatsApp helpers; shared.ts is the client-safe subset
  settings/    key/value store (closed KEYS list, DEFAULTS), /api/admin/settings, the Settings admin page
  media/       sharp image store, /api/admin/uploads, the /storage file server
  auth/        users + sessions tables, scrypt passwords, login/logout server actions, requireAdmin guard,
               rate limiter, the /login page, /api/auth/password, admin seed
  dashboard/   /api/admin/dashboard counts and the dashboard widgets
  listing/     Property / Plot / Flat listings with galleries, linked masterplan availability;
               public grids and detail pages; admin list, form and gallery
  masterplan/  interactive layouts: image + plot polygons (pixel coordinates); Leaflet core (plan-map.ts),
               public map, admin list/form and the plot builder (plan-editor.ts)
  content/     homepage ticker + YouTube list, and the Homepage admin page (hero, featured, ticker, videos)
  home/        homepage, About, Gallery, Contact, Privacy/Terms, sitemap and robots
  admin/       the admin shell: module registry, sidebar, header, shared form components, the fetch client

db/            Drizzle client (index.ts), schema.ts (all tables), migrations/, migrate.ts, seed.ts, demo-seed.ts,
               test.ts (PGlite for the test suite)
components/ui  shadcn (base-ui) components; link buttons use render={<Link/>} + nativeButton={false}
lib/           http.ts (handle/validate/json helpers), text.ts (slugify…), utils.ts (cn)
proxy.ts       sends guests asking for /admin to /login before anything renders
tests/         vitest: services on an in-memory Postgres (PGlite) with the real migrations
```

## How things connect

- **Public pages** are async server components. They read settings through `getSettings()` (one query per
  request) and describe their `<head>` with `pageMetadata({ title, description, image, path })`. Both root
  layouts export `dynamic = 'force-dynamic'`, so every page renders on demand from the database.
- **Admin**: `app/(admin)/admin/layout.tsx` re-exports `modules/admin/layout.tsx`, which checks the session
  and renders the shell. The sidebar and dashboard come from `modules/admin/registry.ts`, an ordered list of
  each module's `admin.ts` (`{ id, order, menu, widgets }`). Pages are client components under
  `modules/<name>/admin/` that call the API through `modules/admin/lib/api.ts`.
- **Auth**: a `rh_session` HttpOnly cookie names a row in `sessions`. Server components use `getViewer()`;
  route handlers call `requireAdmin(request)` (401 without a session, 403 for cross-origin writes).
  `/login` is a server action form; sign-out is the `logout` action.
- **API shape** mirrors the Laravel one: 422 responses are `{ message, errors: { field: [msg] } }`
  (paths like `plots.0.polygon`), resources are wrapped (`{ listing }`, `{ masterplans }`, `{ items }`).
- **Masterplan plots** are polygons in image-pixel coordinates (x right, y down) stored as JSON.
  `modules/masterplan/plan-map.ts` is the Leaflet (CRS.Simple) core shared by the public map and the
  builder; both load Leaflet only in the browser (`next/dynamic` with `ssr: false`).

## Adding a module

1. `modules/<name>/schema.ts` with the tables, and add them to `db/schema.ts`; `pnpm db:generate` writes
   the migration.
2. Service, validation and `api/*.ts` handlers; `app/api/admin/<name>/route.ts` re-exports them.
3. Public pages under `pages/`, re-exported from `app/(site)/…/page.tsx`.
4. Admin pages under `admin/`, re-exported from `app/(admin)/admin/…/page.tsx`, and an `admin.ts`
   manifest added to `modules/admin/registry.ts`.

## Gates

```sh
pnpm test && pnpm lint && pnpm typecheck && pnpm build
```
