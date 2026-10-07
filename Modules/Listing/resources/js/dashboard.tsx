import { BuildingIcon } from 'lucide-react'
import { registerModule } from '@admin/js'

import { addToGroup } from '@/lib/menu'

import { ListingFormPage } from './pages/listing-form-page'
import { ListingListPage } from './pages/listing-list-page'

registerModule({
  id: 'listing',
  order: 2,
  menuFilter: (menu) => addToGroup(menu, 'Content', { label: 'Listings', icon: BuildingIcon, path: '/listings' }),
  routes: [
    { path: 'listings', element: <ListingListPage /> },
    { path: 'listings/new', element: <ListingFormPage /> },
    { path: 'listings/:id', element: <ListingFormPage /> },
  ],
})
