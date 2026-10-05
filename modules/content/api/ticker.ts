import { z } from 'zod'

import { handle, json, readJson, validate } from '@/lib/http'
import { requireAdmin } from '@/modules/auth/guard'

import * as content from '../service'

const schema = z.object({
  items: z.array(
    z.object({
      text: z.string().trim().min(1, 'Every ticker item needs text.').max(300),
      link: z.string().max(255).optional().nullable(),
      active: z.boolean().optional(),
    }),
  ),
})

/** GET /api/admin/ticker */
export const GET = handle(async (request) => {
  await requireAdmin(request)
  return json({ items: await content.ticker() })
})

/** PUT /api/admin/ticker: { items: [{ text, link, active }] } replaces the list. */
export const PUT = handle(async (request) => {
  await requireAdmin(request)
  const data = validate(schema, await readJson(request))
  const items = await content.saveTicker(
    data.items.map((i) => ({ text: i.text.trim(), link: (i.link ?? '').trim(), active: i.active ?? true })),
  )
  return json({ items })
})
