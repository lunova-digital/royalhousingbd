import { z } from 'zod'

import { youtubeId } from '@/modules/site/support/youtube'

import { LISTING_TYPES, type ListingInput } from './types'

const str = (max: number) => z.string().max(max).optional().nullable()

/** The admin form (FR-ADM-003); `normalize` turns the validated payload into row attributes. */
export const listingSchema = z
  .object({
    type: z.enum(LISTING_TYPES, { error: 'Type must be property, plot or flat.' }),
    title: z.string().trim().min(1, 'The title field is required.').max(200),
    location: z.string().trim().min(1, 'The location field is required.').max(255),
    price: str(255),
    call_for_price: z.boolean().optional(),
    land_area: str(255),
    built_area: str(255),
    size: str(255),
    bedrooms: z.coerce.number().int().min(0).optional().nullable(),
    baths: z.coerce.number().int().min(0).optional().nullable(),
    floor: str(255),
    video_url: str(255).refine((v) => !v || v.trim() === '' || youtubeId(v) !== null, 'Enter a YouTube video link.'),
    facilities: z.array(z.string().max(255).nullable()).optional(),
    description: z.string().optional().nullable(),
    published: z.boolean().optional(),
    masterplan_id: z.number().int().optional().nullable(),
  })
  .superRefine((data, ctx) => {
    if (!data.call_for_price && (data.price ?? '').trim() === '') {
      ctx.addIssue({ code: 'custom', path: ['price'], message: 'Enter a price or tick Call for Price.' })
    }
  })

export type ListingPayload = z.infer<typeof listingSchema>

export function normalize(data: ListingPayload): ListingInput {
  return {
    type: data.type,
    title: data.title.trim(),
    location: data.location.trim(),
    price: (data.price ?? '').trim(),
    call_for_price: Boolean(data.call_for_price),
    land_area: (data.land_area ?? '').trim(),
    built_area: (data.built_area ?? '').trim(),
    size: (data.size ?? '').trim(),
    bedrooms: data.bedrooms ?? 0,
    baths: data.baths ?? 0,
    floor: (data.floor ?? '').trim(),
    video_url: (data.video_url ?? '').trim(),
    facilities: (data.facilities ?? []).map((f) => (f ?? '').trim()).filter((f) => f !== ''),
    description: (data.description ?? '').trim(),
    published: Boolean(data.published),
    masterplan_id: data.masterplan_id ?? null,
  }
}
