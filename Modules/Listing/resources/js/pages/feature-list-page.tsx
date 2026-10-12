import { CheckIcon, PencilIcon, PlusIcon, Trash2Icon, XIcon } from 'lucide-react'
import { useState, type FormEvent } from 'react'

import { ConfirmButton } from '@/components/confirm-button'
import { PageHeader } from '@/components/page-header'
import { Alert } from '@/components/ui/alert'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { api, ApiError } from '@/lib/api'

import { useFeatures } from '../api'
import type { Feature } from '../types'

// Features (amenities): the checklist on the listing form and the filters on the website.
export function FeatureListPage() {
  const [features, setFeatures] = useFeatures()
  const [name, setName] = useState('')
  const [editing, setEditing] = useState<{ id: number; name: string } | null>(null)
  const [error, setError] = useState<string | null>(null)
  const [busy, setBusy] = useState(false)

  const run = async (request: () => Promise<{ features: Feature[] }>) => {
    setBusy(true)
    setError(null)
    try {
      const { features } = await request()
      setFeatures(features)
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
    if (await run(() => api('/admin/features', { method: 'POST', body: JSON.stringify({ name }) }))) setName('')
  }

  const rename = async () => {
    if (!editing) return
    if (await run(() => api(`/admin/features/${editing.id}`, { method: 'PUT', body: JSON.stringify({ name: editing.name }) }))) setEditing(null)
  }

  return (
    <div className="grid gap-6">
      <PageHeader title="Property features" description="Amenities a listing can have, such as lift, parking or generator. Visitors filter projects by them." />

      <Card>
        <CardHeader>
          <CardTitle>Add a feature</CardTitle>
          <CardDescription>Keep names short; they appear as a checklist on every listing form.</CardDescription>
        </CardHeader>
        <CardContent>
          <form onSubmit={add} className="flex flex-wrap gap-2">
            <Input className="w-64" placeholder="e.g. Solar backup" value={name} onChange={(e) => setName(e.target.value)} />
            <Button type="submit" disabled={busy || !name.trim()}><PlusIcon /> Add feature</Button>
          </form>
        </CardContent>
      </Card>

      {error && <Alert variant="destructive">{error}</Alert>}

      <div className="rounded-lg border">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Name</TableHead>
              <TableHead className="w-28 text-right">Listings</TableHead>
              <TableHead className="w-36 text-right">Actions</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {features === null ? (
              <TableRow><TableCell colSpan={3} className="text-muted-foreground py-8 text-center">Loading…</TableCell></TableRow>
            ) : features.length === 0 ? (
              <TableRow><TableCell colSpan={3} className="text-muted-foreground py-8 text-center">No features yet. Add one above.</TableCell></TableRow>
            ) : (
              features.map((f) => (
                <TableRow key={f.id}>
                  <TableCell className="font-medium">
                    {editing?.id === f.id ? (
                      <form onSubmit={(e) => { e.preventDefault(); void rename() }} className="flex gap-2">
                        <Input className="w-56" autoFocus value={editing.name} onChange={(e) => setEditing({ id: f.id, name: e.target.value })} />
                        <Button type="submit" size="icon" variant="outline" aria-label="Save" disabled={busy}><CheckIcon /></Button>
                        <Button type="button" size="icon" variant="ghost" aria-label="Cancel" onClick={() => setEditing(null)}><XIcon /></Button>
                      </form>
                    ) : f.name}
                  </TableCell>
                  <TableCell className="text-right tabular-nums">{f.listings_count ?? 0}</TableCell>
                  <TableCell className="text-right">
                    <span className="inline-flex items-center gap-1">
                      <Button variant="ghost" size="icon" aria-label="Rename" onClick={() => setEditing({ id: f.id, name: f.name })}><PencilIcon /></Button>
                      <ConfirmButton
                        variant="ghost"
                        size="icon"
                        aria-label="Delete"
                        title={`Delete "${f.name}"?`}
                        description={`It is removed from ${f.listings_count ?? 0} ${f.listings_count === 1 ? 'listing' : 'listings'} and from the website's filters.`}
                        onConfirm={() => run(() => api(`/admin/features/${f.id}`, { method: 'DELETE' })).then(() => undefined)}
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
    </div>
  )
}
