import { and, asc, count, desc, eq, ne, notInArray } from 'drizzle-orm'

import { db } from '@/db'
import { slugify } from '@/lib/text'
import { deleteStored, MAX_PLAN_UPLOAD_BYTES, MAX_PLAN_WIDTH, storeImage } from '@/modules/media/image-store'

import { masterplans, plots } from './schema'
import type { MasterplanInput, MasterplanWithPlots, PlotInput } from './types'

const withPlots = { plots: { orderBy: [asc(plots.id)] } }

export async function all(): Promise<MasterplanWithPlots[]> {
  return db.query.masterplans.findMany({ with: withPlots, orderBy: [asc(masterplans.id)] })
}

export async function published(order: 'asc' | 'desc' = 'asc'): Promise<MasterplanWithPlots[]> {
  return db.query.masterplans.findMany({
    where: eq(masterplans.published, true),
    with: withPlots,
    orderBy: [order === 'asc' ? asc(masterplans.id) : desc(masterplans.id)],
  })
}

export async function find(id: number): Promise<MasterplanWithPlots | undefined> {
  return db.query.masterplans.findFirst({ where: eq(masterplans.id, id), with: withPlots })
}

export async function findPublished(slug: string): Promise<MasterplanWithPlots | undefined> {
  return db.query.masterplans.findFirst({
    where: and(eq(masterplans.published, true), eq(masterplans.slug, slug)),
    with: withPlots,
  })
}

export async function create(input: MasterplanInput): Promise<MasterplanWithPlots> {
  const [row] = await db
    .insert(masterplans)
    .values({ ...input, slug: await uniqueSlug(input.name) })
    .returning()
  return (await find(row!.id))!
}

export async function update(plan: MasterplanWithPlots, input: MasterplanInput): Promise<MasterplanWithPlots> {
  const slug = plan.name !== input.name ? await uniqueSlug(input.name, plan.id) : plan.slug
  await db
    .update(masterplans)
    .set({ ...input, slug, updated_at: new Date().toISOString() })
    .where(eq(masterplans.id, plan.id))
  return (await find(plan.id))!
}

export async function remove(plan: MasterplanWithPlots): Promise<void> {
  await deleteStored(plan.image_path)
  await db.delete(masterplans).where(eq(masterplans.id, plan.id))
}

/**
 * Replace the layout image. Plot polygons are pixel coordinates of the
 * stored image, so a different-sized image misplaces them; the builder
 * warns and the admin redraws.
 */
export async function setImage(plan: MasterplanWithPlots, file: File): Promise<MasterplanWithPlots> {
  const stored = await storeImage(file, 'file', 'masterplans', { maxWidth: MAX_PLAN_WIDTH, maxBytes: MAX_PLAN_UPLOAD_BYTES })
  await deleteStored(plan.image_path)
  await db
    .update(masterplans)
    .set({ image_path: stored.path, image_width: stored.width, image_height: stored.height, updated_at: new Date().toISOString() })
    .where(eq(masterplans.id, plan.id))
  return (await find(plan.id))!
}

/**
 * The builder's bulk save: rows with an id are updated, rows without are
 * created, and rows no longer present are deleted.
 */
export async function savePlots(plan: MasterplanWithPlots, input: PlotInput[]): Promise<MasterplanWithPlots> {
  await db.transaction(async (tx) => {
    const keep: number[] = []
    for (const p of input) {
      const attributes = { number: p.number, size: p.size, price: p.price, position: p.position, status: p.status, polygon: p.polygon }
      const existing = p.id ? await tx.query.plots.findFirst({ where: and(eq(plots.id, p.id), eq(plots.masterplan_id, plan.id)) }) : undefined
      if (existing) {
        await tx.update(plots).set({ ...attributes, updated_at: new Date().toISOString() }).where(eq(plots.id, existing.id))
        keep.push(existing.id)
      } else {
        const [row] = await tx.insert(plots).values({ ...attributes, masterplan_id: plan.id }).returning({ id: plots.id })
        keep.push(row!.id)
      }
    }
    await tx.delete(plots).where(and(eq(plots.masterplan_id, plan.id), keep.length ? notInArray(plots.id, keep) : undefined))
  })
  return (await find(plan.id))!
}

export async function publishedSlugs(): Promise<{ slug: string; updated_at: string }[]> {
  return db.select({ slug: masterplans.slug, updated_at: masterplans.updated_at }).from(masterplans).where(eq(masterplans.published, true))
}

/** For the dashboard: how many maps, and plots per status. */
export async function counts(): Promise<{ maps: number; plots: Record<string, number> }> {
  const [[mapsRow], byStatus] = await Promise.all([
    db.select({ maps: count() }).from(masterplans),
    db.select({ status: plots.status, n: count() }).from(plots).groupBy(plots.status),
  ])
  return { maps: mapsRow?.maps ?? 0, plots: Object.fromEntries(byStatus.map((r) => [r.status, r.n])) }
}

async function uniqueSlug(name: string, selfId?: number): Promise<string> {
  const base = slugify(name) || 'map'
  let slug = base
  for (let i = 2; ; i++) {
    const [taken] = await db
      .select({ id: masterplans.id })
      .from(masterplans)
      .where(and(eq(masterplans.slug, slug), selfId ? ne(masterplans.id, selfId) : undefined))
      .limit(1)
    if (!taken) return slug
    slug = `${base}-${i}`
  }
}
