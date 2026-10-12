import { CheckIcon, ExternalLinkIcon, MessageCircleIcon, PhoneIcon, Trash2Icon, XIcon } from 'lucide-react'
import { useCallback, useEffect, useState } from 'react'
import { Link, useSearchParams } from 'react-router'

import { ConfirmButton } from '@/components/confirm-button'
import { Field } from '@/components/field'
import { PageHeader } from '@/components/page-header'
import { Alert } from '@/components/ui/alert'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { Textarea } from '@/components/ui/textarea'
import { api, ApiError } from '@/lib/api'

import { MODE_LABELS, type Consultation, type Counts } from '../types'

const FILTERS = [
  { key: 'upcoming', label: 'Upcoming' },
  { key: 'pending', label: 'To confirm' },
  { key: 'past', label: 'Past' },
  { key: 'cancelled', label: 'Cancelled' },
  { key: 'all', label: 'All' },
] as const

const STATUS_BADGE: Record<Consultation['status'], { label: string; variant: 'default' | 'secondary' | 'success' | 'outline' | 'destructive' }> = {
  pending: { label: 'To confirm', variant: 'default' },
  confirmed: { label: 'Confirmed', variant: 'success' },
  completed: { label: 'Done', variant: 'secondary' },
  cancelled: { label: 'Cancelled', variant: 'outline' },
}

// Every consultation visitors booked: the site's lead inbox. Confirm, finish or cancel each
// one and keep notes; the visitor is contacted by phone or WhatsApp, not from here.
export function ConsultationListPage() {
  const [params, setParams] = useSearchParams()
  const filter = (FILTERS.find((f) => f.key === params.get('filter'))?.key ?? 'upcoming') as (typeof FILTERS)[number]['key']
  const [rows, setRows] = useState<Consultation[] | null>(null)
  const [counts, setCounts] = useState<Counts | null>(null)
  const [open, setOpen] = useState<Consultation | null>(null)
  const [error, setError] = useState<string | null>(null)

  const load = useCallback(async () => {
    try {
      const data = await api<{ consultations: Consultation[]; counts: Counts }>(`/admin/consultations?filter=${filter}&per_page=100`)
      setRows(data.consultations)
      setCounts(data.counts)
    } catch {
      setError('Could not load consultations.')
    }
  }, [filter])

  useEffect(() => {
    void load()
  }, [load])

  const patch = async (c: Consultation, body: Partial<Pick<Consultation, 'status' | 'admin_notes'>>) => {
    setError(null)
    try {
      const data = await api<{ consultation: Consultation; counts: Counts }>(`/admin/consultations/${c.id}`, { method: 'PUT', body: JSON.stringify(body) })
      setRows((rows) => rows?.map((r) => (r.id === c.id ? data.consultation : r)) ?? null)
      setCounts(data.counts)
      if (open?.id === c.id) setOpen(data.consultation)
      return data.consultation
    } catch (err) {
      setError(err instanceof ApiError ? err.message : 'Could not update the consultation.')
      return null
    }
  }

  const remove = async (c: Consultation) => {
    await api(`/admin/consultations/${c.id}`, { method: 'DELETE' })
    setOpen(null)
    await load()
  }

  return (
    <div className="grid gap-6">
      <PageHeader
        title="Consultations"
        description={counts ? `${counts.pending} to confirm · ${counts.upcoming} upcoming. Visitors book from the floating button on every page.` : 'Free consultations booked from the website.'}
        actions={<Button variant="outline" nativeButton={false} render={<Link to="/consultations/availability" />}>Availability</Button>}
      />

      {error && <Alert variant="destructive">{error}</Alert>}

      <Tabs value={filter} onValueChange={(value) => setParams({ filter: String(value) })}>
        <TabsList>
          {FILTERS.map((f) => (
            <TabsTrigger key={f.key} value={f.key}>
              {f.label}
              {f.key === 'pending' && counts && counts.pending > 0 ? <span className="bg-primary text-primary-foreground ml-1.5 rounded-full px-1.5 text-[10px] font-semibold tabular-nums">{counts.pending}</span> : null}
            </TabsTrigger>
          ))}
        </TabsList>
      </Tabs>

      <div className="rounded-lg border">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>When</TableHead>
              <TableHead>Visitor</TableHead>
              <TableHead>About</TableHead>
              <TableHead>How</TableHead>
              <TableHead>Status</TableHead>
              <TableHead className="w-44 text-right">Actions</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {rows === null ? (
              <TableRow><TableCell colSpan={6} className="text-muted-foreground py-8 text-center">Loading…</TableCell></TableRow>
            ) : rows.length === 0 ? (
              <TableRow><TableCell colSpan={6} className="text-muted-foreground py-8 text-center">Nothing here yet.</TableCell></TableRow>
            ) : (
              rows.map((c) => (
                <TableRow key={c.id} className="cursor-pointer" onClick={() => setOpen(c)}>
                  <TableCell>
                    <div className="font-medium">{c.date}</div>
                    <div className="text-muted-foreground text-xs">{c.time}</div>
                  </TableCell>
                  <TableCell>
                    <div className="font-medium">{c.name}</div>
                    <div className="text-muted-foreground text-xs">{c.phone}{c.locale === 'bn' ? ' · বাংলা' : ''}</div>
                  </TableCell>
                  <TableCell className="max-w-64">
                    <div className="truncate">{c.listing?.title ?? c.topic ?? '—'}</div>
                    {c.message && <div className="text-muted-foreground truncate text-xs">{c.message}</div>}
                  </TableCell>
                  <TableCell>{MODE_LABELS[c.mode]}</TableCell>
                  <TableCell><Badge variant={STATUS_BADGE[c.status].variant}>{STATUS_BADGE[c.status].label}</Badge></TableCell>
                  <TableCell className="text-right" onClick={(e) => e.stopPropagation()}>
                    <div className="inline-flex items-center gap-1">
                      <Button variant="ghost" size="icon" nativeButton={false} render={<a href={`tel:${c.phone}`} aria-label="Call" />}><PhoneIcon /></Button>
                      {c.whatsapp_url && <Button variant="ghost" size="icon" nativeButton={false} render={<a href={c.whatsapp_url} target="_blank" rel="noreferrer" aria-label="WhatsApp" />}><MessageCircleIcon /></Button>}
                      {c.status === 'pending' && <Button variant="outline" size="sm" onClick={() => void patch(c, { status: 'confirmed' })}><CheckIcon /> Confirm</Button>}
                      {c.status === 'confirmed' && c.is_past && <Button variant="outline" size="sm" onClick={() => void patch(c, { status: 'completed' })}><CheckIcon /> Done</Button>}
                    </div>
                  </TableCell>
                </TableRow>
              ))
            )}
          </TableBody>
        </Table>
      </div>

      <DetailDialog consultation={open} onClose={() => setOpen(null)} onPatch={patch} onDelete={remove} />
    </div>
  )
}

function DetailDialog({
  consultation: c,
  onClose,
  onPatch,
  onDelete,
}: {
  consultation: Consultation | null
  onClose: () => void
  onPatch: (c: Consultation, body: Partial<Pick<Consultation, 'status' | 'admin_notes'>>) => Promise<Consultation | null>
  onDelete: (c: Consultation) => Promise<void>
}) {
  const [notes, setNotes] = useState('')
  const [busy, setBusy] = useState(false)

  useEffect(() => {
    setNotes(c?.admin_notes ?? '')
  }, [c])

  if (!c) return <Dialog open={false} onOpenChange={() => undefined} />

  const act = async (body: Partial<Pick<Consultation, 'status' | 'admin_notes'>>) => {
    setBusy(true)
    try {
      await onPatch(c, body)
    } finally {
      setBusy(false)
    }
  }

  const row = (label: string, value: React.ReactNode) =>
    value ? (
      <div className="grid grid-cols-[7rem_1fr] gap-2 text-sm">
        <dt className="text-muted-foreground">{label}</dt>
        <dd className="min-w-0 break-words">{value}</dd>
      </div>
    ) : null

  return (
    <Dialog open onOpenChange={(open) => !open && onClose()}>
      <DialogContent className="max-h-[90svh] overflow-y-auto sm:max-w-lg">
        <DialogHeader>
          <DialogTitle className="flex flex-wrap items-center gap-2">{c.name} <Badge variant={STATUS_BADGE[c.status].variant}>{STATUS_BADGE[c.status].label}</Badge></DialogTitle>
          <DialogDescription>{c.date} · {c.time} · {MODE_LABELS[c.mode]} · Ref {c.reference}</DialogDescription>
        </DialogHeader>
        <dl className="grid gap-2">
          {row('Phone', <span className="inline-flex flex-wrap items-center gap-2"><a className="underline underline-offset-4" href={`tel:${c.phone}`}>{c.phone}</a>{c.whatsapp_url && <a className="underline underline-offset-4" href={c.whatsapp_url} target="_blank" rel="noreferrer">WhatsApp</a>}</span>)}
          {row('Email', c.email && <a className="underline underline-offset-4" href={`mailto:${c.email}`}>{c.email}</a>)}
          {row('Interested in', c.topic)}
          {row('Listing', c.listing && <a className="inline-flex items-center gap-1 underline underline-offset-4" href={c.listing.url} target="_blank" rel="noreferrer">{c.listing.title} <ExternalLinkIcon className="size-3" /></a>)}
          {row('Message', c.message && <span className="whitespace-pre-wrap">{c.message}</span>)}
          {row('Booked', `${c.created_at}${c.locale === 'bn' ? ' · in Bangla' : ''}`)}
          {row('From page', c.source_url && <a className="text-muted-foreground truncate underline underline-offset-4" href={c.source_url} target="_blank" rel="noreferrer">{c.source_url.replace(/^https?:\/\/[^/]+/, '') || '/'}</a>)}
        </dl>
        <Field id="admin_notes" label="Your notes" hint="Only you see these.">
          <Textarea id="admin_notes" rows={3} value={notes} onChange={(e) => setNotes(e.target.value)} onBlur={() => notes !== (c.admin_notes ?? '') && void act({ admin_notes: notes })} />
        </Field>
        <DialogFooter className="flex-wrap gap-2 sm:justify-between">
          <ConfirmButton variant="ghost" size="sm" title={`Delete ${c.name}'s booking?`} description="The slot frees up and the record is gone." onConfirm={() => onDelete(c)}><Trash2Icon /> Delete</ConfirmButton>
          <div className="flex flex-wrap gap-2">
            {c.status !== 'cancelled' && c.status !== 'completed' && <Button variant="outline" size="sm" disabled={busy} onClick={() => void act({ status: 'cancelled' })}><XIcon /> Cancel booking</Button>}
            {c.status === 'pending' && <Button size="sm" disabled={busy} onClick={() => void act({ status: 'confirmed' })}><CheckIcon /> Confirm</Button>}
            {c.status === 'confirmed' && <Button size="sm" disabled={busy} onClick={() => void act({ status: 'completed' })}><CheckIcon /> Mark done</Button>}
            {(c.status === 'cancelled' || c.status === 'completed') && <Button variant="outline" size="sm" disabled={busy} onClick={() => void act({ status: 'confirmed' })}>Reopen</Button>}
          </div>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}
