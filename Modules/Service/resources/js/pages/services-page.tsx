import { ArrowDownIcon, ArrowUpIcon, PlusIcon, Trash2Icon } from 'lucide-react'
import { useEffect, useState } from 'react'

import { ConfirmButton } from '@/components/confirm-button'
import { Field } from '@/components/field'
import { ImageUpload } from '@/components/image-upload'
import { PageHeader } from '@/components/page-header'
import { Alert } from '@/components/ui/alert'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Switch } from '@/components/ui/switch'
import { Textarea } from '@/components/ui/textarea'
import { api, ApiError } from '@/lib/api'

interface Service {
  slug: string
  title: string
  title_bn: string
  summary: string
  summary_bn: string
  description: string
  description_bn: string
  benefits: string[]
  benefits_bn: string[]
  image: string
  gallery: string[]
  active: boolean
}

const blank = (): Service => ({ slug: '', title: '', title_bn: '', summary: '', summary_bn: '', description: '', description_bn: '', benefits: [], benefits_bn: [], image: '', gallery: [], active: true })
const lines = (list: string[]) => (list ?? []).join('\n')
const toList = (text: string) => text.split('\n')

// Site → Other services: Car, Solar, Lift and any others. They show only in the footer ("Other services") and on /services/{slug}.
export function ServicesPage() {
  const [items, setItems] = useState<Service[] | null>(null)
  const [busy, setBusy] = useState(false)
  const [message, setMessage] = useState<string | null>(null)

  useEffect(() => {
    api<{ items: Service[] }>('/admin/services').then(({ items }) => setItems(items ?? [])).catch(() => setItems([]))
  }, [])

  const set = (i: number, patch: Partial<Service>) => setItems((list) => list!.map((s, n) => (n === i ? { ...s, ...patch } : s)))
  const move = (i: number, d: number) =>
    setItems((list) => {
      const next = [...list!]
      const [item] = next.splice(i, 1)
      next.splice(i + d, 0, item!)
      return next
    })

  const save = async () => {
    if (!items) return
    setBusy(true)
    setMessage(null)
    try {
      const res = await api<{ items: Service[] }>('/admin/services', { method: 'PUT', body: JSON.stringify({ items }) })
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
    <div className="grid gap-6">
      <PageHeader
        title="Other services"
        description="Car, Solar, Lift and the like. They are listed in the footer under “Other services” and get a simple page each; turn one off to hide it."
        actions={<Button onClick={() => void save()} disabled={busy}>{busy ? 'Saving…' : 'Save services'}</Button>}
      />
      {message && <Alert variant={message === 'Saved.' ? 'success' : 'destructive'}>{message}</Alert>}

      {items.map((s, i) => (
        <Card key={s.slug || `new-${i}`}>
          <CardHeader className="flex-row items-start justify-between gap-4">
            <div>
              <CardTitle>{s.title || 'New service'}</CardTitle>
              <CardDescription>{s.slug ? `/services/${s.slug}` : 'The address is made from the title when you save.'}</CardDescription>
            </div>
            <div className="flex items-center gap-2">
              <label className="flex items-center gap-2 text-sm"><Switch checked={s.active} onCheckedChange={(v) => set(i, { active: v })} /> {s.active ? 'Shown' : 'Hidden'}</label>
              <Button type="button" size="icon" variant="ghost" disabled={i === 0} onClick={() => move(i, -1)} aria-label="Move up"><ArrowUpIcon /></Button>
              <Button type="button" size="icon" variant="ghost" disabled={i === items.length - 1} onClick={() => move(i, 1)} aria-label="Move down"><ArrowDownIcon /></Button>
              <ConfirmButton variant="ghost" size="icon" aria-label="Delete" onConfirm={() => setItems((list) => list!.filter((_, n) => n !== i))} title="Delete this service?" description="It disappears from the footer and its page stops working once you save."><Trash2Icon /></ConfirmButton>
            </div>
          </CardHeader>
          <CardContent className="grid gap-5">
            <div className="grid gap-4 sm:grid-cols-2">
              <Field id={`t-${i}`} label="Title (English)"><Input id={`t-${i}`} value={s.title} onChange={(e) => set(i, { title: e.target.value })} /></Field>
              <Field id={`tb-${i}`} label="Title (বাংলা)"><Input id={`tb-${i}`} lang="bn" value={s.title_bn} onChange={(e) => set(i, { title_bn: e.target.value })} /></Field>
              <Field id={`s-${i}`} label="Short description (English)"><Textarea id={`s-${i}`} rows={2} value={s.summary} onChange={(e) => set(i, { summary: e.target.value })} /></Field>
              <Field id={`sb-${i}`} label="Short description (বাংলা)"><Textarea id={`sb-${i}`} lang="bn" rows={2} value={s.summary_bn} onChange={(e) => set(i, { summary_bn: e.target.value })} /></Field>
              <Field id={`d-${i}`} label="Details (English)" hint="A blank line starts a new paragraph."><Textarea id={`d-${i}`} rows={5} value={s.description} onChange={(e) => set(i, { description: e.target.value })} /></Field>
              <Field id={`db-${i}`} label="Details (বাংলা)"><Textarea id={`db-${i}`} lang="bn" rows={5} value={s.description_bn} onChange={(e) => set(i, { description_bn: e.target.value })} /></Field>
              <Field id={`b-${i}`} label="Benefits (English)" hint="One per line, 3–4 works best."><Textarea id={`b-${i}`} rows={4} value={lines(s.benefits)} onChange={(e) => set(i, { benefits: toList(e.target.value) })} /></Field>
              <Field id={`bb-${i}`} label="Benefits (বাংলা)"><Textarea id={`bb-${i}`} lang="bn" rows={4} value={lines(s.benefits_bn)} onChange={(e) => set(i, { benefits_bn: toList(e.target.value) })} /></Field>
            </div>
            <Field id={`i-${i}`} label="Hero image" hint="Wide photo; text sits on its left side."><ImageUpload value={s.image} onChange={(image) => set(i, { image })} /></Field>
            <div className="grid gap-2">
              <p className="text-sm font-medium">Gallery (optional)</p>
              <div className="grid gap-4 sm:grid-cols-3">
                {s.gallery.map((g, n) => (
                  <div key={`${g}-${n}`} className="grid gap-2">
                    <ImageUpload value={g} aspect="aspect-[4/3]" onChange={(path) => set(i, { gallery: s.gallery.map((x, m) => (m === n ? path : x)) })} />
                    <Button type="button" size="sm" variant="ghost" onClick={() => set(i, { gallery: s.gallery.filter((_, m) => m !== n) })}><Trash2Icon /> Remove</Button>
                  </div>
                ))}
              </div>
              {s.gallery.length < 8 && <div><Button type="button" size="sm" variant="outline" onClick={() => set(i, { gallery: [...s.gallery, ''] })}><PlusIcon /> Add photo</Button></div>}
            </div>
          </CardContent>
        </Card>
      ))}

      <div className="flex gap-3">
        <Button type="button" variant="outline" onClick={() => setItems((list) => [...list!, blank()])}><PlusIcon /> Add a service</Button>
        <Button onClick={() => void save()} disabled={busy}>{busy ? 'Saving…' : 'Save services'}</Button>
      </div>
    </div>
  )
}
