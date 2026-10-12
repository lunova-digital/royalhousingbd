import { useEffect, useState } from 'react'

import { api } from '@/lib/api'

import type { Feature, ListingType, Location } from './types'

// The admin-managed lists the listing form and filters are built from.
export function useListingTypes(): ListingType[] | null {
  const [types, setTypes] = useState<ListingType[] | null>(null)
  useEffect(() => {
    api<{ types: ListingType[] }>('/admin/listing-types').then(({ types }) => setTypes(types)).catch(() => setTypes([]))
  }, [])
  return types
}

export function useFeatures(): [Feature[] | null, (features: Feature[]) => void] {
  const [features, setFeatures] = useState<Feature[] | null>(null)
  useEffect(() => {
    api<{ features: Feature[] }>('/admin/features').then(({ features }) => setFeatures(features)).catch(() => setFeatures([]))
  }, [])
  return [features, setFeatures]
}

export function useLocations(): [Location[] | null, (locations: Location[]) => void] {
  const [locations, setLocations] = useState<Location[] | null>(null)
  useEffect(() => {
    api<{ locations: Location[] }>('/admin/locations').then(({ locations }) => setLocations(locations)).catch(() => setLocations([]))
  }, [])
  return [locations, setLocations]
}
