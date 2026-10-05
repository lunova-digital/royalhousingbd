import { z } from 'zod'

import { handle, json, readJson, validate } from '@/lib/http'
import { requireAdmin } from '@/modules/auth/guard'

import { allSettings, KEYS, saveSettings } from '../settings'

async function payload() {
  return json({ settings: await allSettings(), keys: KEYS })
}

/** GET /api/admin/settings */
export const GET = handle(async (request) => {
  await requireAdmin(request)
  return payload()
})

const schema = z.object({ settings: z.record(z.string(), z.unknown()) })

/** PUT /api/admin/settings: { settings: { key: value } } */
export const PUT = handle(async (request) => {
  await requireAdmin(request)
  const data = validate(schema, await readJson(request))
  await saveSettings(data.settings)
  return payload()
})
