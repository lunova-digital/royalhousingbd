import type { InferSelectModel } from 'drizzle-orm'

import type { masterplans, plots } from './schema'

import { PLOT_STATUSES } from './schema'

export type { Point } from './schema'
export { PLOT_STATUSES }
export type PlotStatus = (typeof PLOT_STATUSES)[number]

export const STATUS_OPTIONS: { value: PlotStatus; label: string }[] = [
  { value: 'available', label: 'Available' },
  { value: 'booked', label: 'Booked' },
  { value: 'sold', label: 'Sold' },
]

export type MasterplanRow = InferSelectModel<typeof masterplans>
export type Plot = InferSelectModel<typeof plots>

/** A masterplan with its plots, as the API returns it. */
export type MasterplanWithPlots = MasterplanRow & { plots: Plot[] }
export type Masterplan = MasterplanWithPlots

export interface MasterplanInput {
  name: string
  description: string
  published: boolean
}

/** One plot of the builder's bulk save; id 0 / missing means new. */
export interface PlotInput {
  id?: number | null
  number: string
  size: string
  price: string
  position: string
  status: PlotStatus
  polygon: { x: number; y: number }[]
}
