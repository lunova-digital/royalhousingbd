import { handle, json, readJson, validate } from '@/lib/http'
import { requireAdmin } from '@/modules/auth/guard'

import * as masterplans from '../service'
import { masterplanSchema, normalizeMasterplan } from '../validation'

/** GET /api/admin/masterplans */
export const GET = handle(async (request) => {
  await requireAdmin(request)
  return json({ masterplans: await masterplans.all() })
})

/** POST /api/admin/masterplans */
export const POST = handle(async (request) => {
  await requireAdmin(request)
  const input = normalizeMasterplan(validate(masterplanSchema, await readJson(request)))
  return json({ masterplan: await masterplans.create(input) }, 201)
})
