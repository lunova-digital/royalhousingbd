import type { Metadata } from 'next'
import { redirect } from 'next/navigation'
import type { ReactNode } from 'react'

import { getViewer } from '@/modules/auth/session'
import { getSettings } from '@/modules/site/site'

import { AdminShell } from './components/shell'

export const metadata: Metadata = {
  title: { default: 'Admin', template: '%s · Admin' },
  robots: { index: false },
}

/** Behind `auth`: a guest is sent to the login page (proxy.ts does the same before the render). */
export default async function AdminLayout({ children }: { children: ReactNode }) {
  const viewer = await getViewer()
  if (!viewer) redirect('/login')
  const settings = await getSettings()

  return (
    <AdminShell appName={settings.site_name ?? 'Admin'} viewer={viewer}>
      {children}
    </AdminShell>
  )
}
