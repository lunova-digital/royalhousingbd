import { randomBytes } from 'node:crypto'
import { mkdir, unlink, writeFile } from 'node:fs/promises'
import path from 'node:path'
import sharp from 'sharp'

import { ValidationError } from '@/lib/http'

/**
 * Stores uploaded images under storage/uploads (served at /storage/…) and
 * optimises them on the way in (NFR-002) with sharp: re-encoded, capped in
 * width, with an optional thumbnail for grids.
 */
export const MAX_WIDTH = 1920
/** Masterplan images keep more detail so plots stay legible when zoomed. */
export const MAX_PLAN_WIDTH = 4096
export const THUMB_WIDTH = 640
export const MAX_UPLOAD_BYTES = 20 * 1024 * 1024
export const MAX_PLAN_UPLOAD_BYTES = 30 * 1024 * 1024

/** Where stored files live on disk; the stored path ("uploads/…/x.jpg") is relative to it. */
export const STORAGE_ROOT = path.resolve(/* turbopackIgnore: true */ process.cwd(), process.env.STORAGE_PATH ?? 'storage')

const FORMATS: Record<string, 'jpg' | 'png'> = {
  jpeg: 'jpg',
  png: 'png',
  gif: 'png',
  webp: 'jpg',
}

export interface Stored {
  path: string
  thumb_path: string
  width: number
  height: number
}

export async function storeImage(
  file: File,
  field: string,
  dir: string,
  options: { maxWidth?: number; thumb?: boolean; maxBytes?: number } = {},
): Promise<Stored> {
  const maxBytes = options.maxBytes ?? MAX_UPLOAD_BYTES
  if (file.size > maxBytes) {
    throw ValidationError.of(field, `The image may not be larger than ${Math.round(maxBytes / 1024)} kilobytes.`)
  }

  const source = Buffer.from(await file.arrayBuffer())
  const meta = await sharp(source).metadata().catch(() => null)
  const ext = meta?.format ? FORMATS[meta.format] : undefined
  if (!meta || !ext) {
    throw ValidationError.of(field, 'Only JPEG, PNG, GIF or WebP images are supported.')
  }

  const now = new Date()
  const folder = `uploads/${dir.replace(/^\/+|\/+$/g, '')}/${now.getFullYear()}/${String(now.getMonth() + 1).padStart(2, '0')}`
  await mkdir(path.join(STORAGE_ROOT, folder), { recursive: true })
  const name = `${Math.floor(now.getTime() / 1000)}${randomBytes(8).toString('hex')}`

  const stored = `${folder}/${name}.${ext}`
  const { width, height } = await write(source, stored, options.maxWidth ?? MAX_WIDTH, ext)

  let thumb = ''
  if (options.thumb) {
    thumb = `${folder}/${name}_thumb.${ext}`
    await write(source, thumb, THUMB_WIDTH, ext)
  }

  return { path: stored, thumb_path: thumb, width, height }
}

/** Remove stored files, ignoring ones already gone. */
export async function deleteStored(...paths: (string | null | undefined)[]): Promise<void> {
  for (const stored of paths) {
    if (!stored || stored.includes('..')) continue
    await unlink(path.join(STORAGE_ROOT, stored)).catch(() => undefined)
  }
}

async function write(source: Buffer, stored: string, maxWidth: number, ext: 'jpg' | 'png') {
  let image = sharp(source, { animated: false }).rotate().resize({ width: maxWidth, withoutEnlargement: true })
  image = ext === 'jpg' ? image.jpeg({ quality: 82, mozjpeg: true }) : image.png({ compressionLevel: 9 })
  const { data, info } = await image.toBuffer({ resolveWithObject: true })
  await writeFile(path.join(STORAGE_ROOT, stored), data)
  return { width: info.width, height: info.height }
}
