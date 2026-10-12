import { BuildingIcon, MapPinIcon, ShapesIcon, SparklesIcon } from 'lucide-react'
import { registerModule } from '@admin/js'

import { addToGroup } from '@/lib/menu'

import { FeatureListPage } from './pages/feature-list-page'
import { ListingFormPage } from './pages/listing-form-page'
import { ListingListPage } from './pages/listing-list-page'
import { ListingTypeListPage } from './pages/listing-type-list-page'
import { LocationListPage } from './pages/location-list-page'

registerModule({
  id: 'listing',
  order: 2,
  menuFilter: (menu) =>
    [
      { label: 'Listings', icon: BuildingIcon, path: '/listings' },
      { label: 'Listing types', icon: ShapesIcon, path: '/listing-types' },
      { label: 'Features', icon: SparklesIcon, path: '/features' },
      { label: 'Locations', icon: MapPinIcon, path: '/locations' },
    ].reduce((m, item) => addToGroup(m, 'Content', item), menu),
  routes: [
    { path: 'listings', element: <ListingListPage /> },
    { path: 'listings/new', element: <ListingFormPage /> },
    { path: 'listings/:id', element: <ListingFormPage /> },
    { path: 'listing-types', element: <ListingTypeListPage /> },
    { path: 'features', element: <FeatureListPage /> },
    { path: 'locations', element: <LocationListPage /> },
  ],
})
