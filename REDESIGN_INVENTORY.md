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
