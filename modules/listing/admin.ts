import { BuildingIcon } from 'lucide-react'

import type { AdminModule } from '@/modules/admin/types'

export const listingAdmin: AdminModule = {
  id: 'listing',
  order: 2,
  menu: [{ group: 'Content', label: 'Listings', icon: BuildingIcon, path: '/admin/listings' }],
}
