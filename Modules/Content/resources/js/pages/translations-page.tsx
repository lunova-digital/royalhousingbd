import { SearchIcon } from 'lucide-react'
import { useEffect, useMemo, useState, type FormEvent } from 'react'

import { PageHeader } from '@/components/page-header'
import { Alert } from '@/components/ui/alert'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { Textarea } from '@/components/ui/textarea'
import { api, ApiError } from '@/lib/api'

interface Row {
  group: string
  key: string
  field: string
  label: string
  en: string
  bn: string
}

interface Section {
  id: string
  label: string
  long: boolean
  rows: Row[]
}

const id = (r: Row) => `${r.group}|${r.key}|${r.field}`

// Site → Bangla content: the Bangla version of everything typed in English elsewhere in the admin
// (listing titles, locations, features, announcements, hero text…). An empty box shows the English.
export function TranslationsPage() {
  const [sections, setSections] = useState<Section[] | null>(null)
  const [saved, setSaved] = useState<Record<string, string>>({})
  const [draft, setDraft] = useState<Record<string, string>>({})
  const [query, setQuery] = useState('')
  const [onlyMissing, setOnlyMissing] = useState(false)
  const [busy, setBusy] = useState(false)
  const [message, setMessage] = useState<string | null>(null)
  const [error, setError] = useState<string | null>(null)

  const load = (data: Section[]) => {
    setSections(data)
    const next: Record<string, string> = {}
    for (const s of data) for (const r of s.rows) next[id(r)] = r.bn
    setSaved(next)
    setDraft(next)
  }

  useEffect(() => {
    api<{ sections: Section[] }>('/admin/translations')
      .then(({ sections }) => load(sections))
      .catch(() => setError('Could not load the content.'))
  }, [])

  const dirty = useMemo(() => Object.keys(draft).filter((k) => draft[k] !== saved[k]), [draft, saved])
  const missing = (s: Section) => s.rows.filter((r) => (draft[id(r)] ?? '').trim() === '').length

  const submit = async (e: FormEvent) => {
    e.preventDefault()
    if (!sections) return
    setBusy(true)
    setMessage(null)
    try {
      const rows = sections.flatMap((s) => s.rows).filter((r) => dirty.includes(id(r)))
      const items = rows.map((r) => ({ group: r.group, key: r.key, field: r.field, value: draft[id(r)] ?? '' }))
      const { sections: fresh } = await api<{ sections: Section[] }>('/admin/translations', { method: 'PUT', body: JSON.stringify({ items }) })
      load(fresh)
      setMessage('Saved. The বাং site shows the new text right away.')
    } catch (err) {
      setMessage(err instanceof ApiError ? err.message : 'Could not save.')
    } finally {
      setBusy(false)
    }
  }

  if (error) return <Alert variant="destructive">{error}</Alert>
  if (!sections) return <p className="text-muted-foreground text-sm">Loading…</p>
  if (!sections.length) return <p className="text-muted-foreground text-sm">There is no content to translate yet.</p>

  const needle = query.trim().toLowerCase()
  const visible = (s: Section) =>
    s.rows.filter((r) => (!onlyMissing || (draft[id(r)] ?? '').trim() === '') && (!needle || r.en.toLowerCase().includes(needle) || (draft[id(r)] ?? '').toLowerCase().includes(needle) || r.label.toLowerCase().includes(needle)))
  const totalMissing = sections.reduce((n, s) => n + missing(s), 0)

  return (
    <form onSubmit={submit} className="grid gap-6">
      <PageHeader
        title="Bangla content"
        description="The Bangla version of the content you type in English: listings, locations, features, announcements, homepage and About text. Where a box is empty the site shows the English."
        actions={<Button type="submit" disabled={busy || dirty.length === 0}>{busy ? 'Saving…' : dirty.length ? `Save ${dirty.length} change${dirty.length === 1 ? '' : 's'}` : 'Save'}</Button>}
      />
      {message && <Alert variant={message.startsWith('Saved') ? 'success' : 'destructive'}>{message}</Alert>}

      <div className="flex flex-wrap items-center gap-3">
        <label className="relative min-w-64 flex-1 sm:max-w-sm">
          <SearchIcon className="text-muted-foreground pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2" />
          <Input value={query} onChange={(e) => setQuery(e.target.value)} placeholder="Search…" className="pl-8" aria-label="Search content" />
        </label>
        <label className="flex items-center gap-2 text-sm">
          <input type="checkbox" checked={onlyMissing} onChange={(e) => setOnlyMissing(e.target.checked)} /> Only missing
          <Badge variant="secondary">{totalMissing}</Badge>
        </label>
      </div>

      <Tabs defaultValue={sections[0]!.id}>
        <TabsList className="h-auto flex-wrap justify-start">
          {sections.map((s) => (
            <TabsTrigger key={s.id} value={s.id} className="flex-none">
              {s.label}
              {missing(s) > 0 && <Badge variant="secondary" className="ml-1 px-1.5 py-0 text-[10px]">{missing(s)}</Badge>}
            </TabsTrigger>
          ))}
        </TabsList>
        {sections.map((s) => {
          const rows = visible(s)
          return (
            <TabsContent key={s.id} value={s.id}>
              <Card>
                <CardHeader>
                  <CardTitle>{s.label}</CardTitle>
                  <CardDescription>{s.rows.length - missing(s)} of {s.rows.length} translated.</CardDescription>
                </CardHeader>
                <CardContent className="grid gap-5">
                  {rows.length === 0 && <p className="text-muted-foreground text-sm">Nothing to show.</p>}
                  {rows.map((r) => {
                    const long = s.long && (r.en.length > 70 || r.en.includes('\n'))
                    const Control = long ? Textarea : Input
                    const value = draft[id(r)] ?? ''
                    return (
                      <div key={id(r)} className="grid gap-2 border-b pb-5 last:border-b-0 last:pb-0 sm:grid-cols-2">
                        <div>
                          <p className="text-muted-foreground text-xs font-medium">{r.label}</p>
                          <p className="mt-1 text-sm leading-snug whitespace-pre-line">{r.en}</p>
                        </div>
                        <Control
                          value={value}
                          onChange={(e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => setDraft((d) => ({ ...d, [id(r)]: e.target.value }))}
                          rows={long ? 4 : undefined}
                          lang="bn"
                          placeholder="বাংলা…"
                          aria-label={`Bangla for ${r.label}`}
                          className={value ? 'border-primary/60' : ''}
                        />
                      </div>
                    )
                  })}
                </CardContent>
              </Card>
            </TabsContent>
          )
        })}
      </Tabs>
    </form>
  )
}
