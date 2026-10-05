'use client'

import dynamic from 'next/dynamic'

// Leaflet touches `window` when it loads, so the map only renders in the browser.
export const PlanMap = dynamic(() => import('./plan-map-view').then((m) => m.PlanMapView), {
  ssr: false,
  loading: () => <div className="aspect-video w-full animate-pulse rounded-xl bg-navy-700/50" />,
})
