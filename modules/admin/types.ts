import type { LucideIcon } from 'lucide-react'
import type { ComponentType } from 'react'

export interface MenuItem {
  /** The sidebar group the item sits in; created when no module has made it yet. */
  group: string
  label: string
  icon: LucideIcon
  /** Absolute, e.g. /admin/listings. */
  path: string
}

/**
 * What a module contributes to the admin shell: sidebar entries and dashboard
 * widgets. Pages themselves are files under app/(admin)/admin that re-export
 * the module's page component.
 */
export interface AdminModule {
  id: string
  /** Non-negative: from the start (0 = first). Negative: from the end (-1 = last). */
  order?: number
  menu?: MenuItem[]
  /** Rendered on the dashboard, in module order. */
  widgets?: ComponentType
}

export interface MenuGroup {
  label: string
  items: MenuItem[]
}
