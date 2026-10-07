import type { PlotStatus } from './plan-map'

export type { PlotStatus }

/** The details the admin filled in for one `<g class="plan-plot">` of the layout. */
export interface Plot {
  id: number
  masterplan_id: number
  svg_key: string
  number: string
  size: string
  price: string
  position: string
  status: PlotStatus
}

export interface Masterplan {
  id: number
  name: string
  slug: string
  description: string
  /** The SVG layout (served under /storage). */
  image_path: string
  image_width: number
  image_height: number
  /** data-plot keys of the layout's plot groups, in document order. */
  layout_keys: string[]
  published: boolean
  plots: Plot[]
  created_at: string
  updated_at: string
}

export interface MasterplanInput {
  name: string
  description: string
  published: boolean
}

export const STATUS_OPTIONS: { value: PlotStatus; label: string }[] = [
  { value: 'available', label: 'Available' },
  { value: 'booked', label: 'Booked' },
  { value: 'sold', label: 'Sold' },
]
