# Homepage redesign — feature inventory

Stack note: the public site is server-rendered **Blade** (`Modules/Home`, `Modules/Site`, `Modules/Listing`,
`Modules/Consultation`) with Tailwind v4 tokens in `resources/css/app.css` and small TS islands — not React/Inertia.
The redesign is presentation-only: no routes, controllers, models, migrations or API responses change.

The *After* column is where each item lives now; every row was checked against the built page (EN + বাং, 375/768/1440).

## Layout (every public page)

| # | Feature (before) | Data / source | After |
|---|---|---|---|
| L1 | Utility strip: address | `settings.address` | Footer → Contact column |
| L2 | Utility strip: Project Map / Gallery / Help & contact links | static routes | Header nav (Project Map, Gallery) · footer Company (Contact) |
| L3 | Utility strip: phone (tel:) | `settings.phone` | Footer Contact column + top block |
| L4 | Utility strip: email (mailto:) | `settings.email` | Footer Contact column |
| L5 | EN / বাং language toggle (`?lang=`) | `Site::LOCALES`, `Site::localeUrl` | Header right side (desktop) + mobile Menu sheet |
| L6 | Brand (logo or initial + site name + "Real Estate") | `settings.logo`, `site_name` | Header (mark + name), footer |
| L7 | Header search: type select + keyword → `/projects?type=&q=` | `Site::types()` | Search icon → command-style overlay (same form/fields); hero search pill on home; mobile Menu |
| L8 | Header icon buttons: Call, WhatsApp, Map | phone, `whatsapp_float` | WhatsApp → contact FAB/sheet; call → footer + contact sheet; Map → nav |
| L9 | "All Projects" button | `/projects` | Nav "Projects" |
| L10 | Category nav row: type links (Plot, Flat, …) + Home/About/Projects/Map/Gallery/Contact | `Site::NAV`, `Site::types()` | Nav (Projects · Listings dropdown of types · Project Map · Gallery · About); Contact + Home in footer / mobile |
| L11 | "100% Verified Documents" badge | static copy | Removed from header (Why Royal Housing → "Verified documents") |
| L12 | Footer: logo, blurb (`about_text`), contact list | settings | Footer top block + Contact column |
| L13 | Footer: "Talk to us" WhatsApp + call buttons | settings | One WhatsApp + one consult CTA pair in the footer top block; hotline in Contact column |
| L14 | Footer columns: Projects (all, per-type, map), Company (about, team, gallery, contact), Support (consultation, site visit, WhatsApp, privacy, terms) | `Site::types()` | Listings · Company · Support · Contact columns; privacy/terms in the bottom bar |
| L15 | Footer social links (Facebook, Instagram, YouTube, TikTok) | settings `*_url` | Footer Contact column |
| L16 | Footer "By appointment" pill, "Site visits" | static | Removed (duplicate of consultation) |
| L17 | Copyright | `date('Y')`, site name | Footer bottom bar |
| L18 | Mobile bottom tab bar (Home, About, Projects, Map, Contact) | `Site::NAV` | Home · Listings · **Consult** (raised) · Map · Menu (bottom sheet holds About, Gallery, Contact, Privacy, Terms, language, search) |
| L19 | Floating consultation launcher (pulsing) + dialog with booking widget | `consultation::partials.launcher`, `consult.ts` | One ConsultSheet dialog (same widget, same `/consultation` POST), opened from header, FAB, mobile nav, band |
| L20 | Floating WhatsApp round button | `whatsapp_float` | Inside the FAB card (desktop) / Consult sheet (mobile) |
| L21 | Image lightbox | `[data-lightbox]`, `site.ts` | unchanged |
| L22 | SEO meta, canonical, og tags, favicon | layout `<head>` | unchanged |
| L23 | Fonts: Inter + Plus Jakarta Sans (+ Noto Sans Bengali for `bn`) | bunny.net | Inter + Plus Jakarta Sans; Hind Siliguri for `bn` |

## Homepage

| # | Feature (before) | Data / source | After |
|---|---|---|---|
| H1 | Announcement ticker (marquee, per item link) | `TickerItem` active | Slim single-line fading rotator above header, dismissible, hidden if empty |
| H2 | Hero: badge | `settings.hero_badge` | Hero eyebrow |
| H3 | Hero: title | `settings.hero_title` | Hero H1 |
| H4 | Hero: subtitle | `settings.hero_subtitle` | Hero sub-line |
| H5 | Hero: background image | `settings.hero_image` | Hero image (fallback: local placeholder) |
| H6 | Hero: stats | `hero_stats` list | Plain stats row under the search pill |
| H7 | Hero: primary CTA (`hero_cta_text` / `hero_cta_link`) | settings | Ghost "text →" link under the hero buttons |
| H8 | Hero: "Browse project map" | `/map` | Category tile "Project Map" + header nav |
| H9 | Hero: spotlight card (first featured listing) | `featured[0]` | First card of the "Available now" grid |
| H10 | Side card "100% Legal Assurance" | static | Why Royal Housing → Verified documents |
| H11 | Side card "Interactive Project Map" | static | Section 6 (live masterplan) |
| H12 | Maroon "Plots available now" band: counts Open/Booked/Sold + View map | `statusCounts()` totals | Section 6 summary chips |
| H13 | Featured listings (8) | `ListingService::featured()` | Merged into "Available now" |
| H14 | Project maps tile grid: name + "X of Y available" → `/map/{slug}` | `Masterplan` | Section 6 cards with thumbnail, availability bar, "Open map →" |
| H15 | New arrivals (8) | latest published | Merged into "Available now" (featured first, then latest, de-duplicated) |
| H16 | Trust strip (prime locations, legal docs, flexible payment, book over WhatsApp) | static copy | Why Royal Housing (4 items) |
| H17 | Investor CTA "Book a free site visit" → `/contact` | static | Consultation band |
| H18 | Dark contact band: WhatsApp + phone | settings | Consultation band (WhatsApp); phone in footer |
| H19 | YouTube showcase (click-to-load) | `Video` | "See our projects" carousel + modal player; hidden if none |
| H20 | Why choose us (6 values + 4 pill links to projects/map/gallery/contact) | static | 4 items, no pill row (links live in nav/footer) |
| H21 | FAQ (6 Q&A, `<details>`) | `home::index.faq_*` lang keys | Two-column FAQ, same keys |
| H22 | Trust badges ("Trusted by …") | `settings.trust_badges` | Kept as a muted line under the FAQ |
| H23 | Listing card: cover image, type/availability badge, arrow button, location + verified, title, price, size, type chip, beds/baths, taken count, availability bar, WhatsApp "Book", "Details" | `Listing` | New card (see brief); the same data, each shown once |
| H24 | Listing types Plot / Flat / Resort / Five Star Hotel / Property | `ListingType` registry | Tabs in Available now, category tiles, nav dropdown, search select |
| H25 | Gallery link | `/gallery` | Header nav + footer Company |
| H26 | Contact / email / address / socials | settings | Footer |
| H27 | Public text overrides (Site → Site text) | lang keys via `__()` | Unchanged mechanism; all new copy goes through lang files (en + bn) |

## Out of scope (unchanged)

Inner pages (listing index/show, map, about, contact, gallery, consultation) keep their markup; they inherit the new
tokens, header and footer. The booking widget markup, `consult.ts` and `/consultation` endpoints are reused as-is.

---

# Phase 2 — every other page

Same rule: nothing removed, only merged or moved. *After* is where it lives now.

## Projects grid (`/projects`, `/{type}`)

| Feature (before) | After |
|---|---|
| Dark intro panel with count + trust pills | Breadcrumb + H1; the live count is in the results line (trust pills were repeats of the homepage trust items) |
| "Browse by location" card grid | Location filter (searchable checkbox list; chip on phones, accordion on desktop) |
| Filter sidebar: keyword, budget min/max (+ "call for price excluded" note), features, type links, location links, Apply | One form: sticky search bar, Type pills, Budget selects (note is an info tooltip), Location, Features, **Sort** (new); instant apply, URL state, skeletons |
| Result count line + active-filter text | "Showing X of Y" + removable chips under the bar |
| Pagination | Same, swapped in place |
| "No projects match" | Advisor empty state with WhatsApp + consult |

## Listing detail

| Feature (before) | After |
|---|---|
| Gallery with thumbs + lightbox | Swipeable scroll-snap gallery, counter, thumbs (desktop), lightbox with arrows/swipe/keys |
| Availability badge / verified badge / ref | Type pill on the photo; verified tick + ref under the title; availability bar in the price block |
| Price + size chip | Price block ("Price on request — ask an advisor"); size in the facts grid |
| Legal note, prime location, visits note, 3 trust tiles | "Documents & verification" aside (the legal/verified/instalment items; location and visits notes were repeats) |
| Beds/baths/floor/areas bullet list | Facts grid |
| Call now / Book now (WhatsApp, pre-filled with the reference) / Map icon | WhatsApp (same pre-filled link) + Book consultation (carries the listing id); Call, View on map and Brochure as ghost links; sticky bar on phones |
| Free consultation about this listing | Same button (`data-consult-listing`) |
| PDF brochure | Ghost link |
| Tabs: description / amenities / project map / video | One scrolling page, same four blocks |
| Related listings | Carousel / 3-up |
| "Need more information?" CTA band | The sticky bar + top CTAs (a third copy removed) |

## Project map (`/map`, `/map/{slug}`)

| Feature (before) | After |
|---|---|
| Dark header, legend buttons filtering statuses | Light header, legend chips (still filter) |
| Zoom −/+/reset + percentage | Compact vertical control over the map, 44px targets |
| Selected-plots panel, clear, per-plot remove, ref, WhatsApp (all plots in one message), call | Side card on desktop, bottom sheet on phones; WhatsApp + consultation + call link |
| Project cards (count, price tier, status chips, "View Map") | Cards with thumbnail, availability bar, "Open map →" |
| Plot colours | Status tokens: green / amber / **grey for sold** (was red) |

## Gallery · About · Contact · Legal · Consultation · Errors

| Page | Features kept |
|---|---|
| Gallery | Category chips, masonry grid, per-photo link to its listing, lightbox (now arrows/swipe/keys), empty states |
| About | Story, "what makes us different", stats, leadership + team, partners; contact details moved to the footer and Contact page; closing CTA pair |
| Contact | Hotline, email, address, hours, map embed, WhatsApp form (opens a chat), both CTAs |
| Privacy / Terms | Admin text, 720px article, empty state |
| Consultation page + dialog | Details → Day → Time → Confirm with progress; same slot feed, booking POST, success screen (.ics was already removed), booking reference page |
| 404 / 500 | Back to home, browse projects, both CTAs |

---

# Plot map rebuild (Part B)

## How the map was rendered before
- **Source:** each published `Masterplan` stores an uploaded **SVG** (`image_path`, validated by `LayoutSvg`); every
  `<g class="plan-plot">` is one plot. A `Plot` row (number, size, price, position, status, reference) is matched to its
  group by `svg_key` (`data-plot` or `id`). Plot data reaches the page as JSON in `<script id="plan-data">`.
- **Rendering:** Leaflet in `CRS.Simple` with the SVG as an `svgOverlay` (`plan-map.ts`, `plan.ts`), shown at max 75vh inside a
  padded card; the details panel was a normal block next to / under the map. Taps were Leaflet `click` events.
- **Why it felt bad:** the landscape plan was shrunk to ~16px plots on phones; Leaflet's drag and `click` raced; plot
  numbers were 12 drawing units (a few px on screen); the panel scrolled the page.
- The admin plot builder still uses `plan-map.ts` (Leaflet) and is untouched.

## After
Own renderer on the `panzoom` library (inertia, pinch, wheel/trackpad at cursor, double-tap): inline SVG in a transformed
layer, portrait phones rotate the whole drawing 90° (text counter-rotated), tap = pointer moved < 6px and < 300ms,
details in a three-snap bottom sheet (phones) / sticky 380px side panel (desktop). All previous behaviour kept:
multi-select, status colours + legend filter, plot details, "ask about them together" (WhatsApp, now also the consultation
booking), zoom +/−/reset, per-project maps (now switchable without a reload).

---

# Phase 3 — accounts, saved properties, sharing, services, premium theme & motion

## What existed before
- **Auth:** only the single-admin login (`Modules/Auth`, session guard `web`, `users` table, Blade page at `/login`). No Breeze,
  Fortify, Sanctum or Socialite. The admin (laramod-admin) is a React SPA under `/admin` on a JSON API; the public site is Blade.
- **No SSR concern:** public pages are server-rendered Blade, so Open Graph tags are in the initial HTML.
- **Floating button:** the desktop FAB + admin toggles (`float_whatsapp`, `float_consult`) from Phase 2.

## What was added
| Area | Where |
|---|---|
| Other services (Car, Solar, Lift) | `Modules/Service` (model, admin CRUD under Site → Other services, `/services/{slug}`, footer column) |
| Accounts | `Modules/Account`: `members` table + `member` guard + `members` password broker (separate from the admin); admin door moved to `/admin/login` |
| Google | Socialite, `config/services.php`, `.env.example` |
| Saved + profile | `saved_listings`, `/account/saved`, `/account/profile`, avatar cropper, admin "Saves" column |
| Share | `site::partials.share-*`, `share.ts`, `/og/{type}/{slug}.jpg`, OG/Twitter meta in the layout |
| FAB | `.fab*` in `public.css`, nudge in `site.ts` |
| Theme / fonts / motion | tokens + 3 themes in `public.css`, `Settings → SEO & Brand → Site colours`, Cormorant Garamond, `motion.ts` (lazy) |
