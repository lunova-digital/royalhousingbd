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

import { useLocations } from '../api'
import type { Location } from '../types'

// Locations: the areas a listing can be in, picked on the listing form and listed on the website's filters.
export function LocationListPage() {
  const [locations, setLocations] = useLocations()
  const [name, setName] = useState('')
  const [editing, setEditing] = useState<{ id: number; name: string } | null>(null)
  const [error, setError] = useState<string | null>(null)
  const [busy, setBusy] = useState(false)

  const run = async (request: () => Promise<{ locations: Location[] }>) => {
    setBusy(true)
    setError(null)
    try {
      const { locations } = await request()
      setLocations(locations)
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
    if (await run(() => api('/admin/locations', { method: 'POST', body: JSON.stringify({ name }) }))) setName('')
  }

  const rename = async () => {
    if (!editing) return
    if (await run(() => api(`/admin/locations/${editing.id}`, { method: 'PUT', body: JSON.stringify({ name: editing.name }) }))) setEditing(null)
  }

  return (
    <div className="grid gap-6">
      <PageHeader title="Locations" description="The areas you work in, such as Uttara or Mirpur. Every listing is in one, and visitors browse projects by them." />

      <Card>
        <CardHeader>
          <CardTitle>Add a location</CardTitle>
          <CardDescription>Use the name visitors know, e.g. the area and, if needed, the district in brackets.</CardDescription>
        </CardHeader>
        <CardContent>
          <form onSubmit={add} className="flex flex-wrap gap-2">
            <Input className="w-64" placeholder="e.g. Hasara (Munshiganj)" value={name} onChange={(e) => setName(e.target.value)} />
            <Button type="submit" disabled={busy || !name.trim()}><PlusIcon /> Add location</Button>
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
            {locations === null ? (
              <TableRow><TableCell colSpan={3} className="text-muted-foreground py-8 text-center">Loading…</TableCell></TableRow>
            ) : locations.length === 0 ? (
              <TableRow><TableCell colSpan={3} className="text-muted-foreground py-8 text-center">No locations yet. Add one above.</TableCell></TableRow>
            ) : (
              locations.map((f) => (
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
                        disabled={Boolean(f.listings_count)}
                        title={`Delete "${f.name}"?`}
                        description="It disappears from the website's filters and the listing form."
                        onConfirm={() => run(() => api(`/admin/locations/${f.id}`, { method: 'DELETE' })).then(() => undefined)}
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
