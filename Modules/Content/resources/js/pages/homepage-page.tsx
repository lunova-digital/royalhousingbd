import { ArrowDownIcon, ArrowUpIcon, PlusIcon, Trash2Icon } from 'lucide-react'
import { useEffect, useState, type FormEvent } from 'react'

import { Field } from '@/components/field'
import { ImageUpload } from '@/components/image-upload'
import { JsonList } from '@/components/json-list'
import { PageHeader } from '@/components/page-header'
import { Alert } from '@/components/ui/alert'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Switch } from '@/components/ui/switch'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { Textarea } from '@/components/ui/textarea'
import { api, ApiError, assetUrl } from '@/lib/api'
import type { Listing } from '@listing/js/types'
import { useSettings, type Settings } from '@settings/js/public'

export function HomepagePage() {
  return (
    <div className="grid gap-6">
      <PageHeader title="Homepage" description="Hero banner, featured listings, announcement ticker and video showcase." />
      <Tabs defaultValue="hero">
        <TabsList>
          <TabsTrigger value="hero">Hero</TabsTrigger>
          <TabsTrigger value="featured">Featured listings</TabsTrigger>
          <TabsTrigger value="ticker">Ticker</TabsTrigger>
          <TabsTrigger value="videos">Videos</TabsTrigger>
        </TabsList>
        <TabsContent value="hero"><HeroTab /></TabsContent>
        <TabsContent value="featured"><FeaturedTab /></TabsContent>
        <TabsContent value="ticker"><TickerTab /></TabsContent>
        <TabsContent value="videos"><VideosTab /></TabsContent>
      </Tabs>
    </div>
  )
}

// ---------------------------------------------------------------- Hero (FR-HOME-001)

const HERO_KEYS = ['hero_title', 'hero_subtitle', 'hero_image', 'hero_video_url', 'hero_cta_text', 'hero_cta_link', 'hero_badge', 'hero_stats', 'trust_badges'] as const

function HeroTab() {
  const { settings, error, save } = useSettings()
  const [form, setForm] = useState<Settings>({})
  const [busy, setBusy] = useState(false)
  const [message, setMessage] = useState<string | null>(null)

  useEffect(() => {
    if (!settings) return
    const next: Settings = {}
    for (const key of HERO_KEYS) next[key] = settings[key] ?? ''
    setForm(next)
  }, [settings])

  const set = (key: string, value: string) => setForm((f) => ({ ...f, [key]: value }))

  const submit = async (e: FormEvent) => {
    e.preventDefault()
    setBusy(true)
    setMessage(null)
    try {
      await save(form)
      setMessage('Saved.')
    } catch (err) {
      setMessage(err instanceof ApiError ? err.message : 'Could not save.')
    } finally {
      setBusy(false)
    }
  }

  if (error) return <Alert variant="destructive">{error}</Alert>
  if (!settings) return <p className="text-muted-foreground text-sm">Loading…</p>

  return (
    <form onSubmit={submit}>
      <Card>
        <CardHeader>
          <CardTitle>Hero banner</CardTitle>
          <CardDescription>Shown at the top of the homepage. A video link takes precedence over the image.</CardDescription>
        </CardHeader>
        <CardContent className="grid gap-4">
          <Field id="hero_badge" label="Badge" hint="Small pill above the headline, e.g. Trusted since 2010."><Input id="hero_badge" value={form.hero_badge ?? ''} onChange={(e) => set('hero_badge', e.target.value)} /></Field>
          <Field id="hero_title" label="Headline" hint="Put one word between *asterisks* to set it in italic serif, e.g. Find your *perfect* plot."><Input id="hero_title" value={form.hero_title ?? ''} onChange={(e) => set('hero_title', e.target.value)} /></Field>
          <Field id="hero_subtitle" label="Sub-headline"><Textarea id="hero_subtitle" rows={2} value={form.hero_subtitle ?? ''} onChange={(e) => set('hero_subtitle', e.target.value)} /></Field>
          <div className="grid gap-4 sm:grid-cols-2">
            <Field id="hero_cta_text" label="Button text"><Input id="hero_cta_text" value={form.hero_cta_text ?? ''} onChange={(e) => set('hero_cta_text', e.target.value)} /></Field>
            <Field id="hero_cta_link" label="Button link" hint="e.g. /listings, /plot or /map"><Input id="hero_cta_link" value={form.hero_cta_link ?? ''} onChange={(e) => set('hero_cta_link', e.target.value)} /></Field>
          </div>
          <Field id="hero_image" label="Background image"><ImageUpload value={form.hero_image ?? ''} onChange={(p) => set('hero_image', p)} /></Field>
          <Field id="hero_video_url" label="Background video (YouTube link, optional)"><Input id="hero_video_url" value={form.hero_video_url ?? ''} onChange={(e) => set('hero_video_url', e.target.value)} placeholder="https://www.youtube.com/watch?v=…" /></Field>
          <Field label="Stats row" hint="Three numbers under the hero.">
            <JsonList<{ value: string; label: string }> value={form.hero_stats ?? '[]'} onChange={(v) => set('hero_stats', v)} empty={{ value: '', label: '' }} addLabel="Add stat"
              render={(item, setItem) => (<><Input className="w-32" placeholder="150+" value={item.value} onChange={(e) => setItem({ value: e.target.value })} /><Input className="flex-1" placeholder="Projects completed" value={item.label} onChange={(e) => setItem({ label: e.target.value })} /></>)} />
          </Field>
          <Field id="trust_badges" label="Trust badges" hint="Comma-separated, shown under the closing call to action (e.g. RAJUK approved, ISO 9001). Leave empty to hide."><Input id="trust_badges" value={form.trust_badges ?? ''} onChange={(e) => set('trust_badges', e.target.value)} /></Field>
          {message && <Alert variant={message === 'Saved.' ? 'success' : 'destructive'}>{message}</Alert>}
          <div><Button type="submit" disabled={busy}>{busy ? 'Saving…' : 'Save hero'}</Button></div>
        </CardContent>
      </Card>
    </form>
  )
}

// ---------------------------------------------------------- Featured (FR-HOME-002)

function FeaturedTab() {
  const [featured, setFeatured] = useState<Listing[] | null>(null)
  const [query, setQuery] = useState('')
  const [results, setResults] = useState<Listing[]>([])
  const [busy, setBusy] = useState(false)
  const [message, setMessage] = useState<string | null>(null)

  useEffect(() => {
    api<{ listings: Listing[] }>('/admin/listings/featured').then(({ listings }) => setFeatured(listings)).catch(() => setFeatured([]))
  }, [])

  useEffect(() => {
    const handle = setTimeout(() => {
      const search = new URLSearchParams({ per_page: '8' })
      if (query) search.set('q', query)
      api<{ listings: Listing[] }>(`/admin/listings?${search}`).then(({ listings }) => setResults(listings)).catch(() => setResults([]))
    }, 250)
    return () => clearTimeout(handle)
  }, [query])

  const move = (index: number, delta: number) => {
    if (!featured) return
    const to = index + delta
    if (to < 0 || to >= featured.length) return
    const next = [...featured]
    const [item] = next.splice(index, 1)
    next.splice(to, 0, item!)
    setFeatured(next)
  }

  const save = async () => {
    if (!featured) return
    setBusy(true)
    setMessage(null)
    try {
      const { listings } = await api<{ listings: Listing[] }>('/admin/listings/featured', { method: 'PUT', body: JSON.stringify({ ids: featured.map((l) => l.id) }) })
      setFeatured(listings)
      setMessage('Saved.')
    } catch (err) {
      setMessage(err instanceof ApiError ? err.message : 'Could not save.')
    } finally {
      setBusy(false)
    }
  }

  if (!featured) return <p className="text-muted-foreground text-sm">Loading…</p>
  const ids = new Set(featured.map((l) => l.id))

  const row = (l: Listing) => (
    <span className="flex min-w-0 items-center gap-3">
      <span className="bg-muted size-10 shrink-0 overflow-hidden rounded">{l.images?.[0] && <img src={assetUrl(l.images[0].thumb_path || l.images[0].path)} alt="" className="size-full object-cover" />}</span>
      <span className="min-w-0">
        <span className="block truncate text-sm font-medium">{l.title}</span>
        <span className="text-muted-foreground block truncate text-xs">{l.type_label} · {l.location}{!l.published && ' · draft (hidden)'}</span>
      </span>
    </span>
  )

  return (
    <div className="grid gap-6 lg:grid-cols-2">
      <Card>
        <CardHeader>
          <CardTitle>Featured row</CardTitle>
          <CardDescription>Shown on the homepage in this order. Only published listings appear.</CardDescription>
        </CardHeader>
        <CardContent className="grid gap-3">
          {featured.length === 0 && <p className="text-muted-foreground text-sm">Nothing featured yet. Add listings from the right.</p>}
          <ul className="divide-y rounded-md border">
            {featured.map((l, i) => (
              <li key={l.id} className="flex items-center justify-between gap-2 p-2">
                {row(l)}
                <span className="flex shrink-0 gap-1">
                  <Button size="icon" variant="ghost" aria-label="Move up" disabled={i === 0} onClick={() => move(i, -1)}><ArrowUpIcon /></Button>
                  <Button size="icon" variant="ghost" aria-label="Move down" disabled={i === featured.length - 1} onClick={() => move(i, 1)}><ArrowDownIcon /></Button>
                  <Button size="icon" variant="ghost" aria-label="Remove" onClick={() => setFeatured(featured.filter((f) => f.id !== l.id))}><Trash2Icon /></Button>
                </span>
              </li>
            ))}
          </ul>
          {message && <Alert variant={message === 'Saved.' ? 'success' : 'destructive'}>{message}</Alert>}
          <div><Button onClick={() => void save()} disabled={busy}>{busy ? 'Saving…' : 'Save featured'}</Button></div>
        </CardContent>
      </Card>
      <Card>
        <CardHeader>
          <CardTitle>Add listings</CardTitle>
          <CardDescription>Search by title or location.</CardDescription>
        </CardHeader>
        <CardContent className="grid gap-3">
          <Input placeholder="Search…" value={query} onChange={(e) => setQuery(e.target.value)} />
          <ul className="divide-y rounded-md border">
            {results.map((l) => (
              <li key={l.id} className="flex items-center justify-between gap-2 p-2">
                {row(l)}
                <Button size="sm" variant="outline" disabled={ids.has(l.id)} onClick={() => setFeatured([...featured, l])}><PlusIcon /> Add</Button>
              </li>
            ))}
            {results.length === 0 && <li className="text-muted-foreground p-3 text-sm">No listings found.</li>}
          </ul>
        </CardContent>
      </Card>
    </div>
  )
}

// ------------------------------------------------------------ Ticker (FR-HOME-003)

interface TickerItem { text: string; link: string; active: boolean }

function TickerTab() {
  const [items, setItems] = useState<TickerItem[] | null>(null)
  const [busy, setBusy] = useState(false)
  const [message, setMessage] = useState<string | null>(null)

  useEffect(() => {
    api<{ items: TickerItem[] }>('/admin/ticker').then(({ items }) => setItems(items ?? [])).catch(() => setItems([]))
  }, [])

  const save = async () => {
    if (!items) return
    setBusy(true)
    setMessage(null)
    try {
      const res = await api<{ items: TickerItem[] }>('/admin/ticker', { method: 'PUT', body: JSON.stringify({ items }) })
      setItems(res.items ?? [])
      setMessage('Saved.')
    } catch (err) {
      setMessage(err instanceof ApiError ? (Object.values(err.fieldErrors())[0] ?? err.message) : 'Could not save.')
    } finally {
      setBusy(false)
    }
  }

  if (!items) return <p className="text-muted-foreground text-sm">Loading…</p>

  return (
    <Card>
      <CardHeader>
        <CardTitle>Announcement ticker</CardTitle>
        <CardDescription>Scrolls across the top of the homepage: new listings, offers, notices.</CardDescription>
      </CardHeader>
      <CardContent className="grid gap-3">
        <EditableList
          items={items}
          onChange={setItems}
          empty={{ text: '', link: '', active: true }}
          render={(item, set) => (
            <>
              <Input className="flex-1" placeholder="Announcement text" value={item.text} onChange={(e) => set({ text: e.target.value })} />
              <Input className="w-56" placeholder="Link (optional), e.g. /plot" value={item.link} onChange={(e) => set({ link: e.target.value })} />
              <label className="flex items-center gap-2 text-sm"><Switch checked={item.active} onCheckedChange={(v) => set({ active: v })} /> On</label>
            </>
          )}
        />
        {message && <Alert variant={message === 'Saved.' ? 'success' : 'destructive'}>{message}</Alert>}
        <div><Button onClick={() => void save()} disabled={busy}>{busy ? 'Saving…' : 'Save ticker'}</Button></div>
      </CardContent>
    </Card>
  )
}

// ------------------------------------------------------------ Videos (FR-HOME-004)

interface Video { title: string; url: string }

function VideosTab() {
  const [items, setItems] = useState<Video[] | null>(null)
  const [busy, setBusy] = useState(false)
  const [message, setMessage] = useState<string | null>(null)

  useEffect(() => {
    api<{ items: Video[] }>('/admin/videos').then(({ items }) => setItems(items ?? [])).catch(() => setItems([]))
  }, [])

  const save = async () => {
    if (!items) return
    setBusy(true)
    setMessage(null)
    try {
      const res = await api<{ items: Video[] }>('/admin/videos', { method: 'PUT', body: JSON.stringify({ items }) })
      setItems(res.items ?? [])
      setMessage('Saved.')
    } catch (err) {
      setMessage(err instanceof ApiError ? (Object.values(err.fieldErrors())[0] ?? err.message) : 'Could not save.')
    } finally {
      setBusy(false)
    }
  }

  if (!items) return <p className="text-muted-foreground text-sm">Loading…</p>

  return (
    <Card>
      <CardHeader>
        <CardTitle>YouTube showcase</CardTitle>
        <CardDescription>Videos embedded on the homepage, in this order.</CardDescription>
      </CardHeader>
      <CardContent className="grid gap-3">
        <EditableList
          items={items}
          onChange={setItems}
          empty={{ title: '', url: '' }}
          render={(item, set) => (
            <>
              <Input className="w-56" placeholder="Title (optional)" value={item.title} onChange={(e) => set({ title: e.target.value })} />
              <Input className="flex-1" placeholder="https://www.youtube.com/watch?v=…" value={item.url} onChange={(e) => set({ url: e.target.value })} />
            </>
          )}
        />
        {message && <Alert variant={message === 'Saved.' ? 'success' : 'destructive'}>{message}</Alert>}
        <div><Button onClick={() => void save()} disabled={busy}>{busy ? 'Saving…' : 'Save videos'}</Button></div>
      </CardContent>
    </Card>
  )
}

// An ordered list of small rows with add / remove / move.
function EditableList<T>({
  items,
  onChange,
  empty,
  render,
}: {
  items: T[]
  onChange: (items: T[]) => void
  empty: T
  render: (item: T, set: (patch: Partial<T>) => void) => React.ReactNode
}) {
  const move = (index: number, delta: number) => {
    const to = index + delta
    if (to < 0 || to >= items.length) return
    const next = [...items]
    const [item] = next.splice(index, 1)
    next.splice(to, 0, item!)
    onChange(next)
  }
  return (
    <div className="grid gap-2">
      {items.map((item, i) => (
        <div key={i} className="flex flex-wrap items-center gap-2 rounded-md border p-2">
          {render(item, (patch) => onChange(items.map((it, j) => (j === i ? { ...it, ...patch } : it))))}
          <span className="ml-auto flex gap-1">
            <Button size="icon" variant="ghost" aria-label="Move up" disabled={i === 0} onClick={() => move(i, -1)}><ArrowUpIcon /></Button>
            <Button size="icon" variant="ghost" aria-label="Move down" disabled={i === items.length - 1} onClick={() => move(i, 1)}><ArrowDownIcon /></Button>
            <Button size="icon" variant="ghost" aria-label="Remove" onClick={() => onChange(items.filter((_, j) => j !== i))}><Trash2Icon /></Button>
          </span>
        </div>
      ))}
      <div><Button variant="outline" size="sm" onClick={() => onChange([...items, empty])}><PlusIcon /> Add row</Button></div>
    </div>
  )
}
