import { copyFile, mkdir } from 'node:fs/promises'
import path from 'node:path'
import { eq } from 'drizzle-orm'

import type { Db } from '@/db'
import * as content from '@/modules/content/service'
import { listings } from '@/modules/listing/schema'
import * as listingService from '@/modules/listing/service'
import { masterplans } from '@/modules/masterplan/schema'
import * as masterplanService from '@/modules/masterplan/service'
import type { PlotInput, PlotStatus } from '@/modules/masterplan/types'
import { STORAGE_ROOT } from '@/modules/media/image-store'
import { saveSettings } from '@/modules/settings/settings'

/**
 * Sample content: one listing per type, a masterplan with a layout image and
 * plots, ticker items, a video and contact settings. Runs only while there
 * are no listings, so it never touches real data.
 */
export async function seedDemo(db: Db): Promise<void> {
  if (await db.query.listings.findFirst({ columns: { id: true } })) return

  await saveSettings({
    whatsapp_number: '8801700000000',
    phone: '+880 1700-000000',
    email: 'info@royalhousingbd.com',
    address: 'House 12, Road 5, Bashundhara R/A, Dhaka 1229',
    working_hours: 'Saturday - Thursday: 9:00 AM - 6:00 PM',
    team_members: JSON.stringify([
      { name: 'Tanjim Islam', role: 'Founder & Chairman', photo: '', leader: true },
      { name: 'Managing Director', role: 'Managing Director', photo: '', leader: true },
      { name: 'Sales Manager', role: 'Sales', photo: '', leader: false },
    ]),
    about_text: 'Royal Housing BD develops residential plots and flats around Dhaka. Browse our listings, explore the project masterplan, and message us on WhatsApp to arrange a visit.',
    facebook_url: 'https://facebook.com/',
    youtube_url: 'https://youtube.com/',
    meta_title: 'Royal Housing BD — Property, Plots & Flats in Dhaka',
    meta_description: 'Browse property, plots and flats from Royal Housing BD. Explore the interactive project masterplan and contact us on WhatsApp.',
  })

  const base = { price: '', call_for_price: false, land_area: '', built_area: '', size: '', bedrooms: 0, baths: 0, floor: '', video_url: '', published: true, masterplan_id: null }
  const ids: number[] = []
  for (const attributes of [
    { type: 'property' as const, title: 'Duplex house at Bashundhara R/A', location: 'Bashundhara R/A, Dhaka', price: '৳ 4.5 Crore', land_area: '5 katha', built_area: '4,200 sqft', video_url: 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', facilities: ['Car parking', 'Generator', 'Rooftop garden', '24/7 security'], description: 'A south-facing duplex on a corner plot with a private garden.\nReady to move in.' },
    { type: 'plot' as const, title: 'Corner plot, Block C', location: 'Purbachal, Dhaka', price: '৳ 35 Lakh / katha', size: '3 katha', facilities: ['Boundary wall', 'Paved road', 'Electricity line', 'Gas line'], description: 'Ready plot with all utilities, 200 m from the main road.' },
    { type: 'flat' as const, title: '1450 sqft flat with lake view', location: 'Uttara Sector 7, Dhaka', call_for_price: true, size: '1450 sqft', bedrooms: 3, baths: 3, floor: '5th of 9', facilities: ['Lift', 'Generator', 'CCTV', 'Community hall'], description: 'Three-bedroom flat with a lake-facing balcony and covered parking.' },
  ]) {
    ids.push((await listingService.create({ ...base, ...attributes })).id)
  }
  await listingService.setFeatured(ids)

  await content.saveTicker([
    { text: 'New plots released in Block C — book your site visit today', link: '/plot', active: true },
    { text: 'Eid offer: 5% discount on full payment for flats', link: '/flat', active: true },
    { text: 'Office closed on Friday', link: '', active: true },
  ])
  await content.saveVideos([{ title: 'Project walkthrough', url: 'https://www.youtube.com/watch?v=dQw4w9WgXcQ' }])

  // A generated layout image (1600×1000) with 16 plots over its blocks.
  const stored = 'uploads/masterplans/demo-layout.png'
  await mkdir(path.dirname(path.join(STORAGE_ROOT, stored)), { recursive: true })
  await copyFile(path.join(import.meta.dirname, 'assets/demo-layout.png'), path.join(STORAGE_ROOT, stored))
  const plan = await masterplanService.create({ name: 'Royal City Phase 1', description: 'Demo layout — replace the image and plots in the admin map builder.', published: true })
  await db.update(masterplans).set({ image_path: stored, image_width: 1600, image_height: 1000 }).where(eq(masterplans.id, plan.id))

  const plots: PlotInput[] = []
  let number = 1
  const statuses: PlotStatus[] = ['available', 'booked', 'sold']
  for (const [x0, y0, x1, y1] of [[80, 80, 700, 400], [900, 80, 1520, 400], [80, 600, 700, 920], [900, 600, 1520, 920]] as const) {
    const cellW = (x1 - x0) / 4
    for (let c = 0; c < 4; c++) {
      const left = x0 + c * cellW + 6
      const right = x0 + (c + 1) * cellW - 6
      plots.push({
        number: `A${number}`,
        size: '3 katha',
        price: '৳ 35 Lakh / katha',
        position: `Block A, facing ${['north', 'east', 'south', 'west'][c]}`,
        status: statuses[number % 3]!,
        polygon: [{ x: left, y: y0 }, { x: right, y: y0 }, { x: right, y: y1 }, { x: left, y: y1 }],
      })
      number++
    }
  }
  await masterplanService.savePlots((await masterplanService.find(plan.id))!, plots)

  // The plot listing is a development on this map, so its card shows availability.
  await db.update(listings).set({ masterplan_id: plan.id }).where(eq(listings.type, 'plot'))
}
