import { beforeAll, describe, expect, it } from 'vitest'

import { db } from '@/db'
import { seedDemo } from '@/db/demo-seed'
import { ValidationError, validate } from '@/lib/http'
import { seedAdmin } from '@/modules/auth/seed'
import { tokenFromRequest, viewerForToken } from '@/modules/auth/session'
import { requireAdmin } from '@/modules/auth/guard'
import { GET as dashboard } from '@/modules/dashboard/api'
import * as listings from '@/modules/listing/service'
import { listingSchema, normalize } from '@/modules/listing/validation'
import * as masterplans from '@/modules/masterplan/service'
import { normalizePlots, plotsSchema } from '@/modules/masterplan/validation'
import { allSettings, saveSettings } from '@/modules/settings/settings'

beforeAll(async () => {
  await seedAdmin(db as never)
  await seedDemo(db as never)
})

describe('settings', () => {
  it('only accepts known keys and merges over the defaults', async () => {
    await saveSettings({ phone: '+880 1999', bogus: 'x' })
    const all = await allSettings()
    expect(all.phone).toBe('+880 1999')
    expect(all).not.toHaveProperty('bogus')
    expect(all.site_name).toBe('Royal Housing BD')
  })
})

describe('listings', () => {
  it('validates the admin form like the Laravel request', () => {
    expect(() => validate(listingSchema, { type: 'flat', title: '', location: '', price: '' })).toThrowError(ValidationError)
    try {
      validate(listingSchema, { type: 'flat', title: '', location: '', price: '' })
    } catch (e) {
      expect(Object.keys((e as ValidationError).errors).sort()).toEqual(['location', 'price', 'title'])
    }
  })

  it('creates with a unique slug and trimmed facilities, then renames and deletes', async () => {
    const input = normalize(validate(listingSchema, {
      type: 'flat', title: 'Test flat', location: 'Mirpur, Dhaka', call_for_price: true, size: '1200 sqft', bedrooms: 3, baths: 2,
      facilities: ['Lift', '', ' CCTV '], description: 'Hi', published: true, video_url: 'https://youtu.be/dQw4w9WgXcQ',
    }))
    const created = await listings.create(input)
    expect(created.slug).toBe('test-flat')
    expect(created.facilities).toEqual(['Lift', 'CCTV'])
    expect((await listings.findPublished('flat', 'test-flat'))?.id).toBe(created.id)

    const again = await listings.create(input)
    expect(again.slug).toBe('test-flat-2')

    const renamed = await listings.update(created, { ...input, title: 'Renamed flat', published: false })
    expect(renamed.slug).toBe('renamed-flat')
    expect(await listings.findPublished('flat', 'renamed-flat')).toBeUndefined()

    await listings.setFeatured([renamed.id, again.id])
    expect((await listings.featured(false)).map((l) => l.id)).toEqual([renamed.id, again.id])
    expect((await listings.featured(true)).map((l) => l.id)).toEqual([again.id])

    await listings.remove(renamed)
    await listings.remove(again)
    expect(await listings.find(renamed.id)).toBeUndefined()
  })

  it('filters the public grid by location and search', async () => {
    const byLocation = await listings.search({ location: 'Purbachal, Dhaka', published: true })
    expect(byLocation.items.map((l) => l.title)).toEqual(['Corner plot, Block C'])
    const bySearch = await listings.search({ type: 'flat', q: 'LAKE', published: true })
    expect(bySearch.total).toBe(1)
    expect((await listings.search({ type: 'flat', q: 'nothing-matches', published: true })).items).toHaveLength(0)
    expect(await listings.locations('plot')).toEqual(['Purbachal, Dhaka'])
  })
})

describe('masterplans', () => {
  it('rejects a plot with fewer than three points, with the path of the field', () => {
    try {
      validate(plotsSchema, { plots: [{ id: 0, number: 'X1', status: 'sold', polygon: [{ x: 1, y: 1 }, { x: 2, y: 2 }] }] })
      expect.unreachable()
    } catch (e) {
      expect((e as ValidationError).errors).toHaveProperty('plots.0.polygon')
    }
  })

  it('bulk save upserts and deletes', async () => {
    const [map] = await masterplans.all()
    const first = map!.plots[0]!
    expect(map!.plots).toHaveLength(16)

    const saved = await masterplans.savePlots(map!, normalizePlots(validate(plotsSchema, { plots: [
      { id: first.id, number: 'A1', status: 'booked', size: '4 katha', polygon: first.polygon },
      { id: 0, number: 'Z9', status: 'available', polygon: [{ x: 1, y: 1 }, { x: 2, y: 1 }, { x: 2, y: 2 }] },
    ] })))
    expect(saved.plots).toHaveLength(2)
    expect(saved.plots[0]).toMatchObject({ id: first.id, status: 'booked', size: '4 katha' })
    expect(saved.plots[1]!.number).toBe('Z9')
  })
})

describe('auth', () => {
  it('refuses the admin API without a session', async () => {
    const response = await dashboard(new Request('http://localhost/api/admin/dashboard'), { params: Promise.resolve({}) })
    expect(response.status).toBe(401)
    expect(await viewerForToken(tokenFromRequest(new Request('http://localhost', { headers: { cookie: 'rh_session=nope' } })))).toBeNull()
    await expect(requireAdmin(new Request('http://localhost'))).rejects.toMatchObject({ status: 401 })
  })
})
