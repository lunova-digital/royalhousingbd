import { BuildingIcon, ShapesIcon, SparklesIcon } from 'lucide-react'
import { registerModule } from '@admin/js'

import { addToGroup } from '@/lib/menu'

import { FeatureListPage } from './pages/feature-list-page'
import { ListingFormPage } from './pages/listing-form-page'
import { ListingListPage } from './pages/listing-list-page'
import { ListingTypeListPage } from './pages/listing-type-list-page'

registerModule({
  id: 'listing',
  order: 2,
  menuFilter: (menu) =>
    addToGroup(
      addToGroup(addToGroup(menu, 'Content', { label: 'Listings', icon: BuildingIcon, path: '/listings' }), 'Content', { label: 'Listing types', icon: ShapesIcon, path: '/listing-types' }),
      'Content',
      { label: 'Features', icon: SparklesIcon, path: '/features' },
    ),
  routes: [
    { path: 'listings', element: <ListingListPage /> },
    { path: 'listings/new', element: <ListingFormPage /> },
    { path: 'listings/:id', element: <ListingFormPage /> },
    { path: 'listing-types', element: <ListingTypeListPage /> },
    { path: 'features', element: <FeatureListPage /> },
  ],
})
