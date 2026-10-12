See README.md for the layout. Conventions:

- The app is laramod modules (`protibimbok/laramod`), listed in `bootstrap/modules.php`; the admin is `protibimbok/laramod-admin`. All logic lives in `Modules/<Name>/` in laramod's layout (`Models`, `Services`, `Http/Controllers`, `Http/Requests`, `routes/{web,api}.php`). Use `php artisan make:* --module=<Name>`. No repositories or interfaces unless a second implementation exists.
- A module only provides what its module class declares through a `Laramod\Contracts\Provides*` contract: new views, routes, migrations or Vite entries need the contract, not just the directory.
- Migrations are native Laravel in `Modules/<Name>/Database/Migrations/`.
- Public pages are Blade in `Modules/<Name>/resources/views/` (`view('listing::show')`, layout `site::layouts.app`); admin pages are React in `Modules/<Name>/resources/js/`, registered from `dashboard.tsx` with `registerModule()` from `@admin/js`. Shared admin code stays in `resources/js/{components,lib,hooks}`.
- `resources/js/components/ui` holds base-ui shadcn components owned by `admin:init`: link buttons use `render={<Link/>}` + `nativeButton={false}`, never `asChild`.
- Public copy goes in the module's `lang/{en,bn}/<page>.php` and is rendered with `__()`; the admin can rewrite any of it (Site → Site text), so no inline English in Blade or in the JS islands (labels travel from Blade as JSON).
- Gates before done: `php artisan test && vendor/bin/pint --test && pnpm build`.
- The public theme is placeholder Tailwind tokens (`@theme` block in resources/css/app.css: `ink-*`, `page`, `fill`, `line`, `brand`, `success`, `info`); the client's brand comes later.
- Public design follows evaly.com.bd's structure (light page, white bordered cards, red accent, near-black hero panels and footer, Plus Jakarta Sans headings + Inter body, product-style listing cards, mobile bottom tab bar). Never copy that site's text, photos or logo; the copy here is original.
