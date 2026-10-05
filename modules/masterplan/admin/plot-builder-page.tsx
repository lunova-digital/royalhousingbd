'use client'

import dynamic from 'next/dynamic'

// The builder drives Leaflet directly, which needs `window`: browser only.
export const PlotBuilderPage = dynamic(() => import('./plot-builder').then((m) => m.PlotBuilder), {
  ssr: false,
  loading: () => <p className="text-muted-foreground text-sm">Loading…</p>,
})
