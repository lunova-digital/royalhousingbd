import { handle, json } from '@/lib/http'
import { requireAdmin } from '@/modules/auth/guard'
import { countByType } from '@/modules/listing/service'
import { LISTING_TYPES } from '@/modules/listing/types'
import { counts as mapCounts } from '@/modules/masterplan/service'
import { PLOT_STATUSES } from '@/modules/masterplan/types'

/** GET /api/admin/dashboard: basic counts for the admin landing screen (FR-ADM-002). */
export const GET = handle(async (request) => {
  await requireAdmin(request)
  const [byType, maps] = await Promise.all([countByType(), mapCounts()])
  return json({
    listings: Object.fromEntries(LISTING_TYPES.map((t) => [t, byType[t] ?? 0])),
    maps: maps.maps,
    plots: Object.fromEntries(PLOT_STATUSES.map((s) => [s, maps.plots[s] ?? 0])),
  })
})
