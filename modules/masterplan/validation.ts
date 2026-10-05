import { z } from 'zod'

import { PLOT_STATUSES, type MasterplanInput, type PlotInput } from './types'

export const masterplanSchema = z.object({
  name: z.string().trim().min(1, 'The name field is required.').max(150),
  description: z.string().optional().nullable(),
  published: z.boolean().optional(),
})

export function normalizeMasterplan(data: z.infer<typeof masterplanSchema>): MasterplanInput {
  return { name: data.name.trim(), description: (data.description ?? '').trim(), published: Boolean(data.published) }
}

const point = z.object({ x: z.coerce.number(), y: z.coerce.number() })

/** The builder's bulk save: every plot of the map, in one request. */
export const plotsSchema = z
  .object({
    plots: z.array(
      z.object({
        id: z.number().int().optional().nullable(),
        number: z.string().trim().min(1, 'Every plot needs a number.').max(50),
        size: z.string().max(255).optional().nullable(),
        price: z.string().max(255).optional().nullable(),
        position: z.string().max(255).optional().nullable(),
        status: z.enum(PLOT_STATUSES, { error: 'A plot has an unknown status.' }),
        polygon: z.array(point).min(3, 'Every plot needs at least three points.'),
      }),
    ),
  })
  .superRefine((data, ctx) => {
    const seen = new Map<string, number>()
    data.plots.forEach((plot, i) => {
      const key = plot.number.trim().toLowerCase()
      if (seen.has(key)) ctx.addIssue({ code: 'custom', path: ['plots', i, 'number'], message: 'Plot numbers must be unique on a map.' })
      seen.set(key, i)
    })
  })

export function normalizePlots(data: z.infer<typeof plotsSchema>): PlotInput[] {
  return data.plots.map((p) => ({
    id: p.id ?? null,
    number: p.number.trim(),
    size: (p.size ?? '').trim(),
    price: (p.price ?? '').trim(),
    position: (p.position ?? '').trim(),
    status: p.status,
    polygon: p.polygon.map((pt) => ({ x: Number(pt.x), y: Number(pt.y) })),
  }))
}
