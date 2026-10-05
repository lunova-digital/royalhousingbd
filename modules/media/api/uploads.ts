import { handle, json, ValidationError } from '@/lib/http'
import { requireAdmin } from '@/modules/auth/guard'

import { storeImage } from '../image-store'

/** POST /api/admin/uploads: a generic image upload for hero, brand and SEO images. */
export const POST = handle(async (request) => {
  await requireAdmin(request)
  const form = await request.formData().catch(() => null)
  const file = form?.get('file')
  if (!(file instanceof File) || file.size === 0) throw ValidationError.of('file', 'Choose an image to upload.')
  return json(await storeImage(file, 'file', 'site'), 201)
})
