import { z } from 'zod'

import { handle, json, readJson, validate } from '@/lib/http'
import { requireAdmin } from '@/modules/auth/guard'
import { youtubeId } from '@/modules/site/support/youtube'

import * as content from '../service'

const schema = z.object({
  items: z.array(
    z.object({
      title: z.string().max(200).optional().nullable(),
      url: z.string().max(255).refine((v) => youtubeId(v) !== null, 'Every video needs a YouTube link.'),
    }),
  ),
})

/** GET /api/admin/videos */
export const GET = handle(async (request) => {
  await requireAdmin(request)
  return json({ items: await content.allVideos() })
})

/** PUT /api/admin/videos: { items: [{ title, url }] } replaces the list. */
export const PUT = handle(async (request) => {
  await requireAdmin(request)
  const data = validate(schema, await readJson(request))
  const items = await content.saveVideos(data.items.map((i) => ({ title: (i.title ?? '').trim(), url: i.url.trim() })))
  return json({ items })
})
