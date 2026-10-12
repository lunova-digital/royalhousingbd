import { useEffect, useState } from 'react'
import { Link } from 'react-router'

import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { api } from '@/lib/api'

interface Stats {
  listings: { slug: string; name: string; count: number }[]
  maps: number
  plots: Record<'available' | 'booked' | 'sold', number>
  consultations: { pending: number; upcoming: number }
}

function Stat({ label, value, to }: { label: string; value: number | string; to: string }) {
  return (
    <Link to={to} className="block">
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
      <Stat label="Consultations to confirm" value={n(stats?.consultations.pending)} to="/consultations?filter=pending" />
      <Stat label="Consultations upcoming" value={n(stats?.consultations.upcoming)} to="/consultations" />
      {stats === null ? (
        <Stat label="Listings" value="…" to="/listings" />
      ) : (
        stats.listings.map((t) => <Stat key={t.slug} label={`${t.name} listings`} value={t.count} to={`/listings?type=${t.slug}`} />)
      )}
      <Stat label="Masterplans" value={n(stats?.maps)} to="/masterplans" />
      <Stat label="Plots available" value={n(stats?.plots.available)} to="/masterplans" />
      <Stat label="Plots booked" value={n(stats?.plots.booked)} to="/masterplans" />
      <Stat label="Plots sold" value={n(stats?.plots.sold)} to="/masterplans" />
      <Card className="sm:col-span-2">
        <CardHeader>
          <CardTitle>Quick links</CardTitle>
          <CardDescription>Everything a visitor sees is managed from here.</CardDescription>
        </CardHeader>
        <CardContent className="flex flex-wrap gap-3 text-sm">
          <Link className="underline underline-offset-4" to="/listings/new">Add a listing</Link>
          <Link className="underline underline-offset-4" to="/listing-types">Listing types</Link>
          <Link className="underline underline-offset-4" to="/features">Features</Link>
          <Link className="underline underline-offset-4" to="/masterplans">Map builder</Link>
          <Link className="underline underline-offset-4" to="/consultations/availability">Consultation hours</Link>
          <Link className="underline underline-offset-4" to="/homepage">Homepage content</Link>
          <Link className="underline underline-offset-4" to="/settings">Settings</Link>
          <a className="underline underline-offset-4" href="/" target="_blank" rel="noreferrer">Open website</a>
        </CardContent>
      </Card>
    </>
  )
}
