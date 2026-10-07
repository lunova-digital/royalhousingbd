import type { PlotStatus, Point } from './plan-map'

export type { PlotStatus, Point }

export interface Plot {
  id: number
  masterplan_id: number
  number: string
  size: string
  price: string
  position: string
  status: PlotStatus
  polygon: Point[]
}

export interface Masterplan {
  id: number
  name: string
  slug: string
  description: string
  image_path: string
  image_width: number
  image_height: number
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
