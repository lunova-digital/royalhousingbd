import { HomeIcon } from 'lucide-react'

import type { AdminModule } from '@/modules/admin/types'

export const contentAdmin: AdminModule = {
  id: 'content',
  order: 4,
  menu: [{ group: 'Site', label: 'Homepage', icon: HomeIcon, path: '/admin/homepage' }],
}
