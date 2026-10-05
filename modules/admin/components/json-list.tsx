'use client'

import { ArrowDownIcon, ArrowUpIcon, PlusIcon, Trash2Icon } from 'lucide-react'
import type { ReactNode } from 'react'

import { Button } from '@/components/ui/button'

// An ordered list of rows stored as a JSON string setting. `render` draws one
// row's fields; `empty` is a new row.
export function JsonList<T extends Record<string, unknown>>({
  value,
  onChange,
  empty,
  render,
  addLabel = 'Add row',
}: {
  value: string
  onChange: (json: string) => void
  empty: T
  render: (item: T, set: (patch: Partial<T>) => void) => ReactNode
  addLabel?: string
}) {
  const items = parse<T>(value)
  const commit = (next: T[]) => onChange(JSON.stringify(next))
  const move = (index: number, delta: number) => {
    const to = index + delta
    if (to < 0 || to >= items.length) return
    const next = [...items]
    const [item] = next.splice(index, 1)
    next.splice(to, 0, item!)
    commit(next)
  }
  return (
    <div className="grid gap-2">
      {items.map((item, i) => (
        <div key={i} className="flex flex-wrap items-start gap-2 rounded-md border p-3">
          {render(item, (patch) => commit(items.map((it, j) => (j === i ? { ...it, ...patch } : it))))}
          <span className="ml-auto flex gap-1">
            <Button type="button" size="icon" variant="ghost" aria-label="Move up" disabled={i === 0} onClick={() => move(i, -1)}><ArrowUpIcon /></Button>
            <Button type="button" size="icon" variant="ghost" aria-label="Move down" disabled={i === items.length - 1} onClick={() => move(i, 1)}><ArrowDownIcon /></Button>
            <Button type="button" size="icon" variant="ghost" aria-label="Remove" onClick={() => commit(items.filter((_, j) => j !== i))}><Trash2Icon /></Button>
          </span>
        </div>
      ))}
      <div><Button type="button" variant="outline" size="sm" onClick={() => commit([...items, empty])}><PlusIcon /> {addLabel}</Button></div>
    </div>
  )
}

function parse<T>(value: string): T[] {
  try {
    const parsed = JSON.parse(value || '[]')
    return Array.isArray(parsed) ? (parsed as T[]) : []
  } catch {
    return []
  }
}
