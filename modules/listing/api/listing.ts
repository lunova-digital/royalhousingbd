import { handle, intParam, json, notFound, readJson, validate } from '@/lib/http'
import { requireAdmin } from '@/modules/auth/guard'

import * as listings from '../service'
import { listingSchema, normalize } from '../validation'

async function load(id: string) {
  return (await listings.find(intParam(id))) ?? notFound('No such listing.')
}

/** GET /api/admin/listings/:id */
export const GET = handle<{ id: string }>(async (request, { id }) => {
  await requireAdmin(request)
  return json({ listing: await load(id) })
})

/** PUT /api/admin/listings/:id */
export const PUT = handle<{ id: string }>(async (request, { id }) => {
  await requireAdmin(request)
  const listing = await load(id)
  const input = normalize(validate(listingSchema, await readJson(request)))
  return json({ listing: await listings.update(listing, input) })
})

/** DELETE /api/admin/listings/:id */
export const DELETE = handle<{ id: string }>(async (request, { id }) => {
  await requireAdmin(request)
  await listings.remove(await load(id))
  return json({ message: 'Deleted.' })
})
