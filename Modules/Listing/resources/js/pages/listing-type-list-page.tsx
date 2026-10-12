import { ArrowDownIcon, ArrowUpIcon, CheckIcon, ExternalLinkIcon, PencilIcon, PlusIcon, Trash2Icon, XIcon } from 'lucide-react'
import { useEffect, useState, type FormEvent } from 'react'

import { ConfirmButton } from '@/components/confirm-button'
import { PageHeader } from '@/components/page-header'
import { Alert } from '@/components/ui/alert'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { api, ApiError } from '@/lib/api'

import type { ListingType } from '../types'

// Listing types: add, rename, reorder (the public navigation order) and delete when unused.
export function ListingTypeListPage() {
  const [types, setTypes] = useState<ListingType[] | null>(null)
  const [name, setName] = useState('')
  const [editing, setEditing] = useState<{ id: number; name: string } | null>(null)
  const [error, setError] = useState<string | null>(null)
  const [busy, setBusy] = useState(false)

  useEffect(() => {
    api<{ types: ListingType[] }>('/admin/listing-types').then(({ types }) => setTypes(types)).catch(() => setTypes([]))
  }, [])

  const run = async (request: () => Promise<{ types: ListingType[] }>) => {
    setBusy(true)
    setError(null)
    try {
      const { types } = await request()
      setTypes(types)
      return true
    } catch (err) {
      setError(err instanceof ApiError ? (Object.values(err.fieldErrors())[0] ?? err.message) : 'Something went wrong, please try again.')
      return false
    } finally {
      setBusy(false)
    }
  }

  const add = async (e: FormEvent) => {
    e.preventDefault()
    if (await run(() => api('/admin/listing-types', { method: 'POST', body: JSON.stringify({ name }) }))) setName('')
  }

  const rename = async () => {
    if (!editing) return
    if (await run(() => api(`/admin/listing-types/${editing.id}`, { method: 'PUT', body: JSON.stringify({ name: editing.name }) }))) setEditing(null)
  }

  const move = async (index: number, delta: number) => {
    if (!types) return
    const to = index + delta
    if (to < 0 || to >= types.length) return
    const next = [...types]
    const [item] = next.splice(index, 1)
    next.splice(to, 0, item!)
    setTypes(next)
    await run(() => api('/admin/listing-types/order', { method: 'PUT', body: JSON.stringify({ ids: next.map((t) => t.id) }) }))
  }

  return (
    <div className="grid gap-6">
      <PageHeader title="Listing types" description="The kinds of listing on the website: each has its own page and a place in the menu, in this order." />

      <Card>
        <CardHeader>
          <CardTitle>Add a type</CardTitle>
          <CardDescription>The name becomes its address, e.g. “Resort” is shown at /resort. The address cannot change later.</CardDescription>
        </CardHeader>
        <CardContent>
          <form onSubmit={add} className="flex flex-wrap gap-2">
            <Input className="w-64" placeholder="e.g. Commercial space" value={name} onChange={(e) => setName(e.target.value)} />
            <Button type="submit" disabled={busy || !name.trim()}><PlusIcon /> Add type</Button>
          </form>
        </CardContent>
      </Card>

      {error && <Alert variant="destructive">{error}</Alert>}

      <div className="rounded-lg border">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead className="w-24">Order</TableHead>
              <TableHead>Name</TableHead>
              <TableHead>Address</TableHead>
              <TableHead className="w-28 text-right">Listings</TableHead>
              <TableHead className="w-36 text-right">Actions</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {types === null ? (
              <TableRow><TableCell colSpan={5} className="text-muted-foreground py-8 text-center">Loading…</TableCell></TableRow>
            ) : types.length === 0 ? (
              <TableRow><TableCell colSpan={5} className="text-muted-foreground py-8 text-center">No listing types yet. Add one above.</TableCell></TableRow>
            ) : (
              types.map((t, i) => (
                <TableRow key={t.id}>
                  <TableCell>
                    <span className="inline-flex gap-1">
                      <Button size="icon" variant="ghost" aria-label="Move up" disabled={busy || i === 0} onClick={() => void move(i, -1)}><ArrowUpIcon /></Button>
                      <Button size="icon" variant="ghost" aria-label="Move down" disabled={busy || i === types.length - 1} onClick={() => void move(i, 1)}><ArrowDownIcon /></Button>
                    </span>
                  </TableCell>
                  <TableCell className="font-medium">
                    {editing?.id === t.id ? (
                      <form onSubmit={(e) => { e.preventDefault(); void rename() }} className="flex gap-2">
                        <Input className="w-56" autoFocus value={editing.name} onChange={(e) => setEditing({ id: t.id, name: e.target.value })} />
                        <Button type="submit" size="icon" variant="outline" aria-label="Save" disabled={busy}><CheckIcon /></Button>
                        <Button type="button" size="icon" variant="ghost" aria-label="Cancel" onClick={() => setEditing(null)}><XIcon /></Button>
                      </form>
                    ) : t.name}
                  </TableCell>
                  <TableCell>
                    <a href={`/${t.slug}`} target="_blank" rel="noreferrer" className="text-muted-foreground inline-flex items-center gap-1 hover:underline">/{t.slug} <ExternalLinkIcon className="size-3.5" /></a>
                  </TableCell>
                  <TableCell className="text-right tabular-nums">{t.listings_count ?? 0}</TableCell>
                  <TableCell className="text-right">
                    <span className="inline-flex items-center gap-1">
                      <Button variant="ghost" size="icon" aria-label="Rename" onClick={() => setEditing({ id: t.id, name: t.name })}><PencilIcon /></Button>
                      <ConfirmButton
                        variant="ghost"
                        size="icon"
                        aria-label="Delete"
                        disabled={(t.listings_count ?? 0) > 0}
                        title={`Delete "${t.name}"?`}
                        description="The type and its page are removed from the website."
                        onConfirm={() => run(() => api(`/admin/listing-types/${t.id}`, { method: 'DELETE' })).then(() => undefined)}
                      >
                        <Trash2Icon />
                      </ConfirmButton>
                    </span>
                  </TableCell>
                </TableRow>
              ))
            )}
          </TableBody>
        </Table>
      </div>
      <p className="text-muted-foreground text-sm">A type with listings cannot be deleted: move its listings to another type first.</p>
    </div>
  )
}
