import { ExternalLinkIcon, PencilIcon, PlusIcon, Trash2Icon } from 'lucide-react'
import { useCallback, useEffect, useState, type FormEvent } from 'react'
import { Link, useSearchParams } from 'react-router'

import { ConfirmButton } from '@/components/confirm-button'
import { PageHeader } from '@/components/page-header'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { NativeSelect } from '@/components/ui/native-select'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { api, assetUrl } from '@/lib/api'

import { LISTING_TYPES, listingUrl, typeLabel, type Listing } from '../types'

const PER_PAGE = 20

export function ListingListPage() {
  const [params, setParams] = useSearchParams()
  const type = params.get('type') ?? ''
  const page = Number(params.get('page') ?? '1') || 1
  const query = params.get('q') ?? ''
  const [draft, setDraft] = useState(query)
  const [rows, setRows] = useState<Listing[] | null>(null)
  const [total, setTotal] = useState(0)

  const load = useCallback(async () => {
    const search = new URLSearchParams({ page: String(page), per_page: String(PER_PAGE) })
    if (type) search.set('type', type)
    if (query) search.set('q', query)
    const data = await api<{ listings: Listing[]; total: number }>(`/admin/listings?${search}`)
    setRows(data.listings)
    setTotal(data.total)
  }, [type, page, query])

  useEffect(() => {
    void load()
  }, [load])

  const update = (next: Record<string, string>) => {
    const merged = new URLSearchParams(params)
    for (const [k, v] of Object.entries(next)) {
      if (v) merged.set(k, v)
      else merged.delete(k)
    }
    if (!('page' in next)) merged.delete('page')
    setParams(merged)
  }

  const search = (e: FormEvent) => {
    e.preventDefault()
    update({ q: draft })
  }

  const remove = async (id: number) => {
    await api(`/admin/listings/${id}`, { method: 'DELETE' })
    await load()
  }

  const pages = Math.max(1, Math.ceil(total / PER_PAGE))

  return (
    <div className="grid gap-6">
      <PageHeader
        title="Listings"
        description="Property, plot and flat listings shown on the website."
        actions={
          <Button nativeButton={false} render={<Link to="/listings/new" />}><PlusIcon /> New listing</Button>
        }
      />

      <form onSubmit={search} className="flex flex-wrap gap-2">
        <NativeSelect className="w-40" value={type} onChange={(e) => update({ type: e.target.value })}>
          <option value="">All types</option>
          {LISTING_TYPES.map((t) => (
            <option key={t.value} value={t.value}>
              {t.label}
            </option>
          ))}
        </NativeSelect>
        <Input className="w-64" placeholder="Search title or location" value={draft} onChange={(e) => setDraft(e.target.value)} />
        <Button type="submit" variant="outline">
          Search
        </Button>
      </form>

      <div className="rounded-lg border">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead className="w-16"></TableHead>
              <TableHead>Title</TableHead>
              <TableHead>Type</TableHead>
              <TableHead>Location</TableHead>
              <TableHead>Price</TableHead>
              <TableHead>Status</TableHead>
              <TableHead className="w-32 text-right">Actions</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {rows === null ? (
              <TableRow>
                <TableCell colSpan={7} className="text-muted-foreground py-8 text-center">
                  Loading…
                </TableCell>
              </TableRow>
            ) : rows.length === 0 ? (
              <TableRow>
                <TableCell colSpan={7} className="text-muted-foreground py-8 text-center">
                  No listings yet.
                </TableCell>
              </TableRow>
            ) : (
              rows.map((l) => (
                <TableRow key={l.id}>
                  <TableCell>
                    <div className="bg-muted size-12 overflow-hidden rounded-md">
                      {l.images[0] && <img src={assetUrl(l.images[0].thumb_path || l.images[0].path)} alt="" className="size-full object-cover" />}
                    </div>
                  </TableCell>
                  <TableCell className="font-medium">
                    <Link to={`/listings/${l.id}`} className="hover:underline">
                      {l.title}
                    </Link>
                  </TableCell>
                  <TableCell>{typeLabel(l.type)}</TableCell>
                  <TableCell>{l.location}</TableCell>
                  <TableCell>{l.call_for_price || !l.price ? 'Call for Price' : l.price}</TableCell>
                  <TableCell>
                    <div className="flex gap-1">
                      {l.published ? <Badge variant="success">Published</Badge> : <Badge variant="secondary">Draft</Badge>}
                      {l.featured && <Badge variant="outline">Featured</Badge>}
                    </div>
                  </TableCell>
                  <TableCell className="text-right">
                    <div className="inline-flex items-center gap-1">
                      {l.published && (
                        <Button variant="ghost" size="icon" nativeButton={false} render={<a href={listingUrl(l)} target="_blank" rel="noreferrer" aria-label="View on site" />}><ExternalLinkIcon /></Button>
                      )}
                      <Button variant="ghost" size="icon" nativeButton={false} render={<Link to={`/listings/${l.id}`} aria-label="Edit" />}><PencilIcon /></Button>
                      <ConfirmButton
                        variant="ghost"
                        size="icon"
                        aria-label="Delete"
                        title={`Delete "${l.title}"?`}
                        description="The listing and its photos are removed from the website. This cannot be undone."
                        onConfirm={() => remove(l.id)}
                      >
                        <Trash2Icon />
                      </ConfirmButton>
                    </div>
                  </TableCell>
                </TableRow>
              ))
            )}
          </TableBody>
        </Table>
      </div>

      {pages > 1 && (
        <div className="flex items-center justify-between text-sm">
          <span className="text-muted-foreground">
            Page {page} of {pages} · {total} listings
          </span>
          <div className="flex gap-2">
            <Button variant="outline" size="sm" disabled={page <= 1} onClick={() => update({ page: String(page - 1) })}>
              Previous
            </Button>
            <Button variant="outline" size="sm" disabled={page >= pages} onClick={() => update({ page: String(page + 1) })}>
              Next
            </Button>
          </div>
        </div>
      )}
    </div>
  )
}
