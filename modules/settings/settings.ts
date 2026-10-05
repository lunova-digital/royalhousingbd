import { sql } from 'drizzle-orm'

import { db } from '@/db'

import { settings } from './schema'

export type SettingsMap = Record<string, string>

/**
 * The admin-editable key/value store behind FR-ADM-005/006: WhatsApp number,
 * contact info, social links, SEO, brand and the homepage hero.
 * KEYS is the closed list the admin may write; anything else is dropped.
 */
export const KEYS = [
  // Brand
  'site_name', 'logo',
  // Contact (FR-COMM-001/002)
  'whatsapp_number', 'phone', 'email', 'address', 'map_embed_url', 'about_text',
  // Social links
  'facebook_url', 'youtube_url', 'instagram_url', 'tiktok_url',
  // Basic SEO
  'meta_title', 'meta_description', 'og_image',
  // Homepage hero (FR-HOME-001)
  'hero_title', 'hero_subtitle', 'hero_image', 'hero_video_url', 'hero_cta_text', 'hero_cta_link',
  'hero_badge', 'hero_stats', 'trust_badges', 'working_hours',
  // About page (JSON lists are edited in the admin About tab)
  'about_title', 'about_story', 'about_stats', 'team_members', 'partners',
  // Legal pages
  'privacy_text', 'terms_text',
] as const

export type SettingKey = (typeof KEYS)[number]

export const DEFAULTS: SettingsMap = {
  site_name: 'Royal Housing BD',
  hero_title: 'Find your next property, plot or flat',
  hero_subtitle: 'Browse listings and explore our project masterplans. Contact us on WhatsApp to book a visit.',
  hero_cta_text: 'Browse listings',
  hero_cta_link: '/projects',
  hero_badge: 'Trusted since 2010',
  hero_stats: '[{"value":"150+","label":"Projects completed"},{"value":"5000+","label":"Happy families"},{"value":"50+","label":"Locations"}]',
  trust_badges: '',
  working_hours: 'Saturday - Thursday: 9:00 AM - 6:00 PM',
  about_title: 'Building Dreams Into Reality',
  about_story:
    'Royal Housing BD was founded with a vision to bring transparency, quality and innovation to property development in Bangladesh.\n\nWe have delivered residential plots and flats to families across Dhaka, and our interactive masterplan lets you pick your plot with confidence.',
  about_stats: '[{"value":"150+","label":"Projects delivered"},{"value":"5000+","label":"Happy families"},{"value":"14+","label":"Years experience"},{"value":"100%","label":"Legal compliance"}]',
  team_members: '[]',
  partners: '[]',
  privacy_text: '',
  terms_text: '',
}

/** A JSON list setting, decoded; [] when empty or invalid. */
export function settingList<T extends object>(values: SettingsMap, key: string): T[] {
  try {
    const decoded: unknown = JSON.parse(values[key] || '[]')
    return Array.isArray(decoded) ? (decoded.filter((row) => row && typeof row === 'object') as T[]) : []
  } catch {
    return []
  }
}

/** Every setting merged over the defaults. */
export async function allSettings(): Promise<SettingsMap> {
  const rows = await db.select({ key: settings.key, value: settings.value }).from(settings)
  return { ...DEFAULTS, ...Object.fromEntries(rows.map((r) => [r.key, r.value])) }
}

/** Upsert the allowed keys present in `values`; unknown keys are ignored. */
export async function saveSettings(values: Record<string, unknown>): Promise<void> {
  const rows = KEYS.filter((key) => key in values).map((key) => ({ key, value: String(values[key] ?? '') }))
  if (rows.length === 0) return
  await db
    .insert(settings)
    .values(rows)
    .onConflictDoUpdate({
      target: settings.key,
      set: { value: sql`excluded.value`, updated_at: new Date().toISOString() },
    })
}
