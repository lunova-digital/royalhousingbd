import { contentAdmin } from '@/modules/content/admin'
import { dashboardAdmin } from '@/modules/dashboard/admin'
import { listingAdmin } from '@/modules/listing/admin'
import { masterplanAdmin } from '@/modules/masterplan/admin'
import { settingsAdmin } from '@/modules/settings/admin'

import type { AdminModule, MenuGroup, MenuItem } from './types'

/** The module list, by hand (like bootstrap/modules.php was): nothing is discovered from the file system. */
const registered: AdminModule[] = [dashboardAdmin, listingAdmin, masterplanAdmin, contentAdmin, settingsAdmin]

function ordered(): AdminModule[] {
  const indexed = registered.map((module, i) => ({ module, order: module.order ?? 0, i }))
  const byOrder = (a: (typeof indexed)[number], b: (typeof indexed)[number]) => a.order - b.order || a.i - b.i
  return [...indexed.filter((e) => e.order >= 0).sort(byOrder), ...indexed.filter((e) => e.order < 0).sort(byOrder)].map((e) => e.module)
}

export const adminModules: readonly AdminModule[] = ordered()

export function menuGroups(): MenuGroup[] {
  const groups: MenuGroup[] = []
  for (const mod of adminModules) {
    for (const item of mod.menu ?? []) {
      const group = groups.find((g) => g.label === item.group)
      if (group) group.items.push(item)
      else groups.push({ label: item.group, items: [item] })
    }
  }
  return groups
}

export function menuItems(): MenuItem[] {
  return menuGroups().flatMap((g) => g.items)
}

/** Where /admin lands: the first page offered. */
export function homePath(): string {
  return menuItems()[0]?.path ?? '/admin/dashboard'
}

export function isUnder(pathname: string, path: string): boolean {
  return pathname === path || pathname.startsWith(`${path}/`)
}

/** The one item a page belongs to: the deepest that covers it. */
export function activeMenuPath(pathname: string): string | null {
  let match: string | null = null
  for (const item of menuItems()) {
    if (isUnder(pathname, item.path) && (!match || item.path.length > match.length)) match = item.path
  }
  return match
}

export function findMenuLocation(pathname: string): { group: MenuGroup; item: MenuItem } | null {
  const active = activeMenuPath(pathname)
  if (!active) return null
  for (const group of menuGroups()) {
    const item = group.items.find((i) => i.path === active)
    if (item) return { group, item }
  }
  return null
}
