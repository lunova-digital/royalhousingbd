See README.md for the layout. Conventions:

- Feature code lives in `modules/<name>/` (schema, types, service, validation, api/, pages/, components/, admin/, admin.ts). Files under `app/` are routes that only re-export from a module; never put logic there.
- Nothing is discovered from the file system: a new module's tables go in `db/schema.ts`, its admin menu/widgets in `modules/admin/registry.ts`, its routes as re-export files in `app/`.
- Database: Drizzle on Postgres (`pg`); tables use snake_case keys so the API payload matches the old Laravel shape. `pnpm db:generate` after schema changes, `pnpm db:migrate` to apply. Tests run on PGlite (`db/test.ts`), never on the real database.
- API handlers are `handle(async (request, params) => …)` from `lib/http.ts`; validate with zod through `validate()`, which throws the 422 shape the admin expects. Every admin handler starts with `await requireAdmin(request)`.
- Client components import only client-safe modules: `modules/site/shared.ts` (not `site.ts`), `modules/admin/lib/api.ts`, types. Leaflet code loads through `next/dynamic` with `ssr: false`.
- `components/ui` holds base-ui shadcn components: link buttons use `render={<Link/>}` + `nativeButton={false}`, never `asChild`.
- Gates before done: `pnpm test && pnpm lint && pnpm typecheck && pnpm build`.
- The public theme is placeholder neutral Tailwind (`--color-brand` tokens in `app/globals.css`); the client's brand comes later.
- Public design follows royalhousingnepal.com's structure (dark navy, red accent word in headings, Playfair Display + Inter). Never copy that site's text, photos or logo; the copy here is original.
