import { MapIcon } from 'lucide-react'

import type { AdminModule } from '@/modules/admin/types'

export const masterplanAdmin: AdminModule = {
  id: 'masterplan',
  order: 3,
  menu: [{ group: 'Content', label: 'Masterplans', icon: MapIcon, path: '/admin/masterplans' }],
}
