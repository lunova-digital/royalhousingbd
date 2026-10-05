import type { Viewport } from 'next'

import '@/app/globals.css'

// Every public page reads settings and content from the database on each request.
export const dynamic = 'force-dynamic'

export const viewport: Viewport = { themeColor: '#0f1729', width: 'device-width', initialScale: 1, viewportFit: 'cover' }

export { default, generateMetadata } from '@/modules/site/layout'
