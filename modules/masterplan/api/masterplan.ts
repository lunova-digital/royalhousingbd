import { handle, intParam, json, notFound, readJson, validate, ValidationError } from '@/lib/http'
import { requireAdmin } from '@/modules/auth/guard'

import * as masterplans from '../service'
import { masterplanSchema, normalizeMasterplan, normalizePlots, plotsSchema } from '../validation'

async function load(id: string) {
  return (await masterplans.find(intParam(id))) ?? notFound('No such masterplan.')
}

/** GET /api/admin/masterplans/:id */
export const GET = handle<{ id: string }>(async (request, { id }) => {
  await requireAdmin(request)
  return json({ masterplan: await load(id) })
})

/** PUT /api/admin/masterplans/:id */
export const PUT = handle<{ id: string }>(async (request, { id }) => {
  await requireAdmin(request)
  const plan = await load(id)
  const input = normalizeMasterplan(validate(masterplanSchema, await readJson(request)))
  return json({ masterplan: await masterplans.update(plan, input) })
})

/** DELETE /api/admin/masterplans/:id */
export const DELETE = handle<{ id: string }>(async (request, { id }) => {
  await requireAdmin(request)
  await masterplans.remove(await load(id))
  return json({ message: 'Deleted.' })
})

/** POST /api/admin/masterplans/:id/image (multipart, file) */
export const setImage = handle<{ id: string }>(async (request, { id }) => {
  await requireAdmin(request)
  const plan = await load(id)
  const form = await request.formData().catch(() => null)
  const file = form?.get('file')
  if (!(file instanceof File) || file.size === 0) throw ValidationError.of('file', 'Choose an image to upload.')
  return json({ masterplan: await masterplans.setImage(plan, file) })
})

/** PUT /api/admin/masterplans/:id/plots: the builder's bulk save. */
export const savePlots = handle<{ id: string }>(async (request, { id }) => {
  await requireAdmin(request)
  const plan = await load(id)
  const input = normalizePlots(validate(plotsSchema, await readJson(request)))
  return json({ masterplan: await masterplans.savePlots(plan, input) })
})
