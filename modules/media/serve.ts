import { createReadStream } from 'node:fs'
import { stat } from 'node:fs/promises'
import path from 'node:path'
import { Readable } from 'node:stream'

import { handle } from '@/lib/http'

import { STORAGE_ROOT } from './image-store'

const TYPES: Record<string, string> = {
  '.jpg': 'image/jpeg',
  '.jpeg': 'image/jpeg',
  '.png': 'image/png',
  '.gif': 'image/gif',
  '.webp': 'image/webp',
  '.svg': 'image/svg+xml',
}

/**
 * GET /storage/[...path]: serves uploaded files from the storage directory.
 * Next only serves `public/` files known at build time, so uploads go through here.
 * File names are unique, so they cache forever.
 */
export const GET = handle<{ path: string[] }>(async (_request, params) => {
  const relative = path.posix.normalize(params.path.join('/'))
  if (!relative.startsWith('uploads/') || relative.includes('..')) {
    return new Response('Not found', { status: 404 })
  }
  const file = path.join(STORAGE_ROOT, relative)
  const info = await stat(file).catch(() => null)
  if (!info?.isFile()) return new Response('Not found', { status: 404 })

  const stream = Readable.toWeb(createReadStream(file)) as ReadableStream
  return new Response(stream, {
    headers: {
      'Content-Type': TYPES[path.extname(file).toLowerCase()] ?? 'application/octet-stream',
      'Content-Length': String(info.size),
      'Cache-Control': 'public, max-age=31536000, immutable',
    },
  })
})
