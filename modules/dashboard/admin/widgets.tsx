'use client'

import { useEffect, useState } from 'react'
import Link from 'next/link'

import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { api } from '@/modules/admin/lib/api'

interface Stats {
  listings: Record<'property' | 'plot' | 'flat', number>
  maps: number
  plots: Record<'available' | 'booked' | 'sold', number>
}

function Stat({ label, value, href }: { label: string; value: number | string; href: string }) {
  return (
    <Link href={href} className="block">
      <Card className="h-full transition-shadow hover:shadow-md">
        <CardHeader>
          <CardDescription>{label}</CardDescription>
          <CardTitle className="text-3xl tabular-nums">{value}</CardTitle>
        </CardHeader>
      </Card>
    </Link>
  )
}

// What is live on the website right now. Only mounted while the dashboard is shown.
export function Widgets() {
  const [stats, setStats] = useState<Stats | null>(null)

  useEffect(() => {
    api<Stats>('/admin/dashboard').then(setStats).catch(() => setStats(null))
  }, [])

  const n = (v: number | undefined) => (stats ? (v ?? 0) : '…')

  return (
    <>
      <Stat label="Property listings" value={n(stats?.listings.property)} href="/admin/listings?type=property" />
      <Stat label="Plot listings" value={n(stats?.listings.plot)} href="/admin/listings?type=plot" />
      <Stat label="Flat listings" value={n(stats?.listings.flat)} href="/admin/listings?type=flat" />
      <Stat label="Masterplans" value={n(stats?.maps)} href="/admin/masterplans" />
      <Stat label="Plots available" value={n(stats?.plots.available)} href="/admin/masterplans" />
      <Stat label="Plots booked" value={n(stats?.plots.booked)} href="/admin/masterplans" />
      <Stat label="Plots sold" value={n(stats?.plots.sold)} href="/admin/masterplans" />
      <Card className="sm:col-span-2">
        <CardHeader>
          <CardTitle>Quick links</CardTitle>
          <CardDescription>Everything a visitor sees is managed from here.</CardDescription>
        </CardHeader>
        <CardContent className="flex flex-wrap gap-3 text-sm">
          <Link className="underline underline-offset-4" href="/admin/listings/new">Add a listing</Link>
          <Link className="underline underline-offset-4" href="/admin/masterplans">Map builder</Link>
          <Link className="underline underline-offset-4" href="/admin/homepage">Homepage content</Link>
          <Link className="underline underline-offset-4" href="/admin/settings">Settings</Link>
          <a className="underline underline-offset-4" href="/" target="_blank" rel="noreferrer">Open website</a>
        </CardContent>
      </Card>
    </>
  )
}
