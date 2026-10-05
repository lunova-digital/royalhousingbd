import { asc, eq, type InferSelectModel } from 'drizzle-orm'

import { db } from '@/db'

import { tickerItems, videos } from './schema'

export type TickerItem = InferSelectModel<typeof tickerItems>
export type Video = InferSelectModel<typeof videos>

export interface TickerInput {
  text: string
  link: string
  active: boolean
}

export interface VideoInput {
  title: string
  url: string
}

export async function ticker(activeOnly = false): Promise<TickerItem[]> {
  return db.query.tickerItems.findMany({
    where: activeOnly ? eq(tickerItems.active, true) : undefined,
    orderBy: [asc(tickerItems.sort_order), asc(tickerItems.id)],
  })
}

/** Each save replaces the whole list, in order. */
export async function saveTicker(items: TickerInput[]): Promise<TickerItem[]> {
  await db.transaction(async (tx) => {
    await tx.delete(tickerItems)
    if (items.length) await tx.insert(tickerItems).values(items.map((item, i) => ({ ...item, sort_order: i })))
  })
  return ticker()
}

export async function allVideos(): Promise<Video[]> {
  return db.query.videos.findMany({ orderBy: [asc(videos.sort_order), asc(videos.id)] })
}

export async function saveVideos(items: VideoInput[]): Promise<Video[]> {
  await db.transaction(async (tx) => {
    await tx.delete(videos)
    if (items.length) await tx.insert(videos).values(items.map((item, i) => ({ ...item, sort_order: i })))
  })
  return allVideos()
}
