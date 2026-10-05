import { z } from 'zod'

import { handle, intParam, json, notFound, readJson, validate, ValidationError } from '@/lib/http'
import { requireAdmin } from '@/modules/auth/guard'

import * as listings from '../service'

async function load(id: string) {
  return (await listings.find(intParam(id))) ?? notFound('No such listing.')
}

/** POST /api/admin/listings/:id/images (multipart, files[]) */
export const POST = handle<{ id: string }>(async (request, { id }) => {
  await requireAdmin(request)
  const listing = await load(id)
  const form = await request.formData().catch(() => null)
  const files = [...(form?.getAll('files[]') ?? []), ...(form?.getAll('files') ?? [])].filter((f): f is File => f instanceof File && f.size > 0)
  if (files.length === 0) throw ValidationError.of('files', 'Choose at least one image.')
  return json({ images: await listings.addImages(listing, files) }, 201)
})

const orderSchema = z.object({ ids: z.array(z.number().int()).min(1, 'The ids field is required.') })

/** PUT /api/admin/listings/:id/images/order: { ids: [] } */
export const reorder = handle<{ id: string }>(async (request, { id }) => {
  await requireAdmin(request)
  const listing = await load(id)
  const data = validate(orderSchema, await readJson(request))
  await listings.reorderImages(listing, data.ids)
  return json({ listing: await listings.find(listing.id) })
})

/** DELETE /api/admin/listings/:id/images/:image */
export const destroy = handle<{ id: string; image: string }>(async (request, { id, image }) => {
  await requireAdmin(request)
  const listing = await load(id)
  const row = (await listings.findImage(listing, intParam(image, 'image'))) ?? notFound('No such image.')
  await listings.deleteImage(row)
  return json({ message: 'Deleted.' })
})
