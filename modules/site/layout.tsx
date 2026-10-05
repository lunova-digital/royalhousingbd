import type { Metadata } from 'next'
import { Inter, Playfair_Display } from 'next/font/google'
import type { ReactNode } from 'react'

import { SiteShell } from './components/shell'
import { getSettings, pageMetadata } from './site'

const inter = Inter({ subsets: ['latin'], variable: '--font-inter', display: 'swap' })
const playfair = Playfair_Display({ subsets: ['latin'], weight: ['600', '700', '800'], variable: '--font-playfair', display: 'swap' })

/** The <html> of every public page (also used by the global not-found page). */
export function SiteDocument({ children }: { children: ReactNode }) {
  return (
    <html lang="en" className={`${inter.variable} ${playfair.variable} bg-navy-900`}>
      <body className="min-h-svh bg-navy-900 pb-16 font-body text-base text-fg antialiased lg:pb-0">{children}</body>
    </html>
  )
}

export async function generateMetadata(): Promise<Metadata> {
  return pageMetadata({ path: '/' })
}

export default async function SiteLayout({ children }: { children: ReactNode }) {
  const settings = await getSettings()
  return (
    <SiteDocument>
      <SiteShell settings={settings}>{children}</SiteShell>
    </SiteDocument>
  )
}
