import { handle, json, readJson, validate } from '@/lib/http'
import { requireAdmin } from '@/modules/auth/guard'

import * as listings from '../service'
import { listingSchema, normalize } from '../validation'

/** GET /api/admin/listings?type=&q=&page=&per_page= */
export const GET = handle(async (request) => {
  await requireAdmin(request)
  const url = new URL(request.url)
  const page = await listings.search(
    { type: url.searchParams.get('type'), q: url.searchParams.get('q') },
    Number(url.searchParams.get('per_page')) || 20,
    Number(url.searchParams.get('page')) || 1,
  )
  return json({ listings: page.items, total: page.total })
})

/** POST /api/admin/listings */
export const POST = handle(async (request) => {
  await requireAdmin(request)
  const input = normalize(validate(listingSchema, await readJson(request)))
  return json({ listing: await listings.create(input) }, 201)
})
