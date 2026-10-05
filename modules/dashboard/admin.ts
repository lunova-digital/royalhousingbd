import { GaugeIcon } from 'lucide-react'

import type { AdminModule } from '@/modules/admin/types'

import { Widgets } from './admin/widgets'

/** Owns the dashboard page's menu entry and fills it with the live counts. */
export const dashboardAdmin: AdminModule = {
  id: 'dashboard',
  order: 1,
  menu: [{ group: 'Overview', label: 'Dashboard', icon: GaugeIcon, path: '/admin/dashboard' }],
  widgets: Widgets,
}
