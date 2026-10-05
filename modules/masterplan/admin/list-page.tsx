'use client'

import { ExternalLinkIcon, MapIcon, PencilIcon, PlusIcon, Trash2Icon } from 'lucide-react'
import { useCallback, useEffect, useState } from 'react'
import Link from 'next/link'

import { ConfirmButton } from '@/modules/admin/components/confirm-button'
import { PageHeader } from '@/modules/admin/components/page-header'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { api, assetUrl } from '@/modules/admin/lib/api'

import type { Masterplan } from '../types'

export function MasterplanListPage() {
  const [rows, setRows] = useState<Masterplan[] | null>(null)

  const load = useCallback(async () => {
    const { masterplans } = await api<{ masterplans: Masterplan[] }>('/admin/masterplans')
    setRows(masterplans)
  }, [])

  useEffect(() => {
    void load()
  }, [load])

  const remove = async (id: number) => {
    await api(`/admin/masterplans/${id}`, { method: 'DELETE' })
    await load()
  }

  const counts = (m: Masterplan) => {
    const c = { available: 0, booked: 0, sold: 0 }
    for (const p of m.plots ?? []) c[p.status] = (c[p.status] ?? 0) + 1
    return c
  }

  return (
    <div className="grid gap-6">
      <PageHeader
        title="Masterplans"
        description="Interactive project layouts. Upload the image, then place plots in the builder."
        actions={
          <Button nativeButton={false} render={<Link href="/admin/masterplans/new" />}><PlusIcon /> New masterplan</Button>
        }
      />
      <div className="rounded-lg border">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead className="w-20"></TableHead>
              <TableHead>Name</TableHead>
              <TableHead>Plots</TableHead>
              <TableHead>Status</TableHead>
              <TableHead className="w-44 text-right">Actions</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {rows === null ? (
              <TableRow>
                <TableCell colSpan={5} className="text-muted-foreground py-8 text-center">Loading…</TableCell>
              </TableRow>
            ) : rows.length === 0 ? (
              <TableRow>
                <TableCell colSpan={5} className="text-muted-foreground py-8 text-center">No masterplans yet.</TableCell>
              </TableRow>
            ) : (
              rows.map((m) => {
                const c = counts(m)
                return (
                  <TableRow key={m.id}>
                    <TableCell>
                      <div className="bg-muted aspect-[16/10] w-16 overflow-hidden rounded-md">
                        {m.image_path && <img src={assetUrl(m.image_path)} alt="" className="size-full object-cover" />}
                      </div>
                    </TableCell>
                    <TableCell className="font-medium">
                      <Link href={`/admin/masterplans/${m.id}`} className="hover:underline">{m.name}</Link>
                      {!m.image_path && <span className="text-muted-foreground block text-xs">No image uploaded yet</span>}
                    </TableCell>
                    <TableCell className="text-sm">
                      {m.plots?.length ?? 0} total · <span className="text-emerald-700">{c.available} available</span> · {c.booked} booked · {c.sold} sold
                    </TableCell>
                    <TableCell>{m.published ? <Badge variant="success">Published</Badge> : <Badge variant="secondary">Draft</Badge>}</TableCell>
                    <TableCell className="text-right">
                      <div className="inline-flex items-center gap-1">
                        {m.published && (
                          <Button variant="ghost" size="icon" nativeButton={false} render={<a href={`/map/${m.slug}`} target="_blank" rel="noreferrer" aria-label="View on site" />}><ExternalLinkIcon /></Button>
                        )}
                        <Button variant="outline" size="sm" disabled={!m.image_path} nativeButton={false} render={<Link href={`/admin/masterplans/${m.id}/plots`} />}><MapIcon /> Builder</Button>
                        <Button variant="ghost" size="icon" nativeButton={false} render={<Link href={`/admin/masterplans/${m.id}`} aria-label="Edit" />}><PencilIcon /></Button>
                        <ConfirmButton
                          variant="ghost"
                          size="icon"
                          aria-label="Delete"
                          title={`Delete "${m.name}"?`}
                          description="The map, its image and all its plots are removed. This cannot be undone."
                          onConfirm={() => remove(m.id)}
                        >
                          <Trash2Icon />
                        </ConfirmButton>
                      </div>
                    </TableCell>
                  </TableRow>
                )
              })
            )}
          </TableBody>
        </Table>
      </div>
    </div>
  )
}
