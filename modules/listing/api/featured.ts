import { z } from 'zod'

import { handle, json, readJson, validate } from '@/lib/http'
import { requireAdmin } from '@/modules/auth/guard'

import * as listings from '../service'

/** GET /api/admin/listings/featured: the homepage row, drafts included. */
export const GET = handle(async (request) => {
  await requireAdmin(request)
  return json({ listings: await listings.featured(false) })
})

const schema = z.object({ ids: z.array(z.number().int()).optional() })

/** PUT /api/admin/listings/featured: { ids: [] } replaces the row, in order. */
export const PUT = handle(async (request) => {
  await requireAdmin(request)
  const data = validate(schema, await readJson(request))
  await listings.setFeatured(data.ids ?? [])
  return json({ listings: await listings.featured(false) })
})
