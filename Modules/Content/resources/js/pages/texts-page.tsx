import { RotateCcwIcon, SearchIcon } from 'lucide-react'
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

interface TextItem {
  key: string
  en: string
  bn: string
  en_override: string
  bn_override: string
}

interface TextGroup {
  id: string
  label: string
  items: TextItem[]
}

type Draft = Record<string, { en: string; bn: string }>

const LOCALES = [
  ['en', 'English'],
  ['bn', 'বাংলা'],
] as const

// Site → Site text: every string of the public site, with the admin's replacement in each
// language. An empty box means the built-in text is shown.
export function TextsPage() {
  const [groups, setGroups] = useState<TextGroup[] | null>(null)
  const [draft, setDraft] = useState<Draft>({})
  const [query, setQuery] = useState('')
  const [onlyChanged, setOnlyChanged] = useState(false)
  const [busy, setBusy] = useState(false)
  const [message, setMessage] = useState<string | null>(null)
  const [error, setError] = useState<string | null>(null)

  const load = (data: TextGroup[]) => {
    setGroups(data)
    const next: Draft = {}
    for (const group of data) for (const item of group.items) next[item.key] = { en: item.en_override, bn: item.bn_override }
    setDraft(next)
  }

  useEffect(() => {
    api<{ groups: TextGroup[] }>('/admin/texts')
      .then(({ groups }) => load(groups))
      .catch(() => setError('Could not load the site text.'))
  }, [])

  const set = (key: string, locale: 'en' | 'bn', value: string) => setDraft((d) => ({ ...d, [key]: { ...(d[key] ?? { en: '', bn: '' }), [locale]: value } }))
  const changed = (key: string) => (draft[key]?.en ?? '') !== '' || (draft[key]?.bn ?? '') !== ''

  const submit = async (e: FormEvent) => {
    e.preventDefault()
    setBusy(true)
    setMessage(null)
    try {
      const { groups } = await api<{ groups: TextGroup[] }>('/admin/texts', { method: 'PUT', body: JSON.stringify({ texts: draft }) })
      load(groups)
      setMessage('Saved. The site shows the new text right away.')
    } catch (err) {
      setMessage(err instanceof ApiError ? err.message : 'Could not save.')
    } finally {
      setBusy(false)
    }
  }

  // A search (or "only changed") flattens every group into one list of matches.
  const needle = query.trim().toLowerCase()
  const filtered = useMemo(() => {
    if (!groups || (!needle && !onlyChanged)) return null
    const matches = (item: TextItem) => {
      const d = draft[item.key] ?? { en: '', bn: '' }
      if (onlyChanged && d.en === '' && d.bn === '') return false
      return !needle || [item.en, item.bn, d.en, d.bn, item.key].some((s) => s.toLowerCase().includes(needle))
    }
    return groups.map((g) => ({ ...g, items: g.items.filter(matches) })).filter((g) => g.items.length)
  }, [groups, needle, onlyChanged, draft])

  if (error) return <Alert variant="destructive">{error}</Alert>
  if (!groups) return <p className="text-muted-foreground text-sm">Loading…</p>

  const changedCount = Object.keys(draft).filter(changed).length

  return (
    <form onSubmit={submit} className="grid gap-6">
      <PageHeader
        title="Site text"
        description="Every sentence and label of the public site, in English and Bangla. Type over a text to change it; clear the box to go back to the built-in one."
        actions={<Button type="submit" disabled={busy}>{busy ? 'Saving…' : 'Save text'}</Button>}
      />
      {message && <Alert variant={message.startsWith('Saved') ? 'success' : 'destructive'}>{message}</Alert>}

      <div className="flex flex-wrap items-center gap-3">
        <label className="relative min-w-64 flex-1 sm:max-w-sm">
          <SearchIcon className="text-muted-foreground pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2" />
          <Input value={query} onChange={(e) => setQuery(e.target.value)} placeholder="Search any text…" className="pl-8" aria-label="Search text" />
        </label>
        <label className="flex items-center gap-2 text-sm">
          <input type="checkbox" checked={onlyChanged} onChange={(e) => setOnlyChanged(e.target.checked)} /> Only changed
          {changedCount > 0 && <Badge variant="secondary">{changedCount}</Badge>}
        </label>
      </div>

      {filtered ? (
        filtered.length ? (
          filtered.map((group) => <GroupCard key={group.id} group={group} draft={draft} set={set} />)
        ) : (
          <p className="text-muted-foreground text-sm">Nothing matches.</p>
        )
      ) : (
        <Tabs defaultValue={groups[0]!.id}>
          <TabsList className="h-auto flex-wrap justify-start">
            {groups.map((group) => {
              const n = group.items.filter((i) => changed(i.key)).length
              return (
                <TabsTrigger key={group.id} value={group.id} className="flex-none">
                  {group.label}
                  {n > 0 && <Badge variant="secondary" className="ml-1 px-1.5 py-0 text-[10px]">{n}</Badge>}
                </TabsTrigger>
              )
            })}
          </TabsList>
          {groups.map((group) => (
            <TabsContent key={group.id} value={group.id}>
              <GroupCard group={group} draft={draft} set={set} />
            </TabsContent>
          ))}
        </Tabs>
      )}
    </form>
  )
}

function GroupCard({ group, draft, set }: { group: TextGroup; draft: Draft; set: (key: string, locale: 'en' | 'bn', value: string) => void }) {
  return (
    <Card>
      <CardHeader>
        <CardTitle>{group.label}</CardTitle>
        <CardDescription>{group.items.length} {group.items.length === 1 ? 'text' : 'texts'}. Keep any <code>:name</code> parts, the site fills them in; <code>|</code> separates the singular and plural forms.</CardDescription>
      </CardHeader>
      <CardContent className="grid gap-5">
        {group.items.map((item) => {
          const d = draft[item.key] ?? { en: '', bn: '' }
          const long = item.en.length > 70 || item.en.includes('\n')
          const Control = long ? Textarea : Input
          return (
            <div key={item.key} className="grid gap-2 border-b pb-5 last:border-b-0 last:pb-0">
              <div className="flex items-start justify-between gap-3">
                <p className="text-sm leading-snug font-medium">{item.en}</p>
                {(d.en || d.bn) && (
                  <Button type="button" size="sm" variant="ghost" onClick={() => { set(item.key, 'en', ''); set(item.key, 'bn', '') }}>
                    <RotateCcwIcon /> Reset
                  </Button>
                )}
              </div>
              <div className="grid gap-3 sm:grid-cols-2">
                {LOCALES.map(([locale, label]) => (
                  <label key={locale} className="grid gap-1">
                    <span className="text-muted-foreground text-xs font-medium">{label}</span>
                    <Control
                      value={d[locale]}
                      onChange={(e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => set(item.key, locale, e.target.value)}
                      placeholder={item[locale] || '—'}
                      rows={long ? 3 : undefined}
                      lang={locale}
                      className={d[locale] ? 'border-primary/60' : ''}
                    />
                  </label>
                ))}
              </div>
            </div>
          )
        })}
      </CardContent>
    </Card>
  )
}
