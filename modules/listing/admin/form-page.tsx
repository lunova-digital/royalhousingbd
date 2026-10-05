'use client'

import { ExternalLinkIcon } from 'lucide-react'
import { useEffect, useState, type FormEvent } from 'react'
import Link from 'next/link'
import { useParams, useRouter } from 'next/navigation'

import { Field } from '@/modules/admin/components/field'
import { PageHeader } from '@/modules/admin/components/page-header'
import { Alert } from '@/components/ui/alert'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { NativeSelect } from '@/components/ui/native-select'
import { Switch } from '@/components/ui/switch'
import { Textarea } from '@/components/ui/textarea'
import { api, ApiError } from '@/modules/admin/lib/api'

import { Gallery } from './gallery'
import { listingUrl } from '../model'
import { EMPTY_INPUT, LISTING_TYPE_OPTIONS as LISTING_TYPES, type Listing, type ListingInput, type ListingType } from '../types'

export function ListingFormPage() {
  const { id } = useParams<{ id?: string }>()
  const router = useRouter()
  const isNew = id === undefined
  const [listing, setListing] = useState<Listing | null>(null)
  const [form, setForm] = useState<ListingInput>(EMPTY_INPUT)
  const [facilitiesText, setFacilitiesText] = useState('')
  const [errors, setErrors] = useState<Record<string, string>>({})
  const [message, setMessage] = useState<string | null>(null)
  const [busy, setBusy] = useState(false)
  const [loading, setLoading] = useState(!isNew)
  const [masterplans, setMasterplans] = useState<{ id: number; name: string }[]>([])

  useEffect(() => {
    api<{ masterplans: { id: number; name: string }[] }>('/admin/masterplans').then(({ masterplans }) => setMasterplans(masterplans)).catch(() => setMasterplans([]))
  }, [])

  useEffect(() => {
    if (isNew) return
    setLoading(true)
    api<{ listing: Listing }>(`/admin/listings/${id}`)
      .then(({ listing }) => {
        setListing(listing)
        setForm({
          type: listing.type as ListingType, title: listing.title, location: listing.location, price: listing.price, call_for_price: listing.call_for_price,
          land_area: listing.land_area, built_area: listing.built_area, size: listing.size, bedrooms: listing.bedrooms, baths: listing.baths, floor: listing.floor,
          video_url: listing.video_url, facilities: listing.facilities, description: listing.description, published: listing.published, masterplan_id: listing.masterplan_id,
        })
        setFacilitiesText((listing.facilities ?? []).join('\n'))
      })
      .catch(() => router.replace('/admin/listings'))
      .finally(() => setLoading(false))
  }, [id, isNew, router])

  const set = <K extends keyof ListingInput>(key: K, value: ListingInput[K]) => setForm((f) => ({ ...f, [key]: value }))

  const submit = async (event: FormEvent) => {
    event.preventDefault()
    setBusy(true)
    setErrors({})
    setMessage(null)
    const payload: ListingInput = {
      ...form,
      facilities: facilitiesText.split('\n').map((s) => s.trim()).filter(Boolean),
    }
    try {
      if (isNew) {
        const { listing } = await api<{ listing: Listing }>('/admin/listings', { method: 'POST', body: JSON.stringify(payload) })
        router.replace(`/admin/listings/${listing.id}`)
      } else {
        const { listing } = await api<{ listing: Listing }>(`/admin/listings/${id}`, { method: 'PUT', body: JSON.stringify(payload) })
        setListing((current) => ({ ...listing, images: current?.images ?? listing.images }))
        setMessage('Saved.')
      }
    } catch (err) {
      if (err instanceof ApiError && err.status === 422) setErrors(err.fieldErrors())
      else setMessage(err instanceof ApiError ? err.message : 'Something went wrong, please try again.')
    } finally {
      setBusy(false)
    }
  }

  if (loading) return <p className="text-muted-foreground text-sm">Loading…</p>

  return (
    <form onSubmit={submit} className="grid gap-6">
      <PageHeader
        title={isNew ? 'New listing' : listing?.title ?? 'Edit listing'}
        description={isNew ? 'Fill in the details, save, then add photos.' : `${LISTING_TYPES.find((t) => t.value === listing?.type)?.label} · ${listing?.location}`}
        actions={
          <>
            {listing?.published && (
              <Button variant="outline" nativeButton={false} render={<a href={listingUrl(listing)} target="_blank" rel="noreferrer" />}><ExternalLinkIcon /> View on site</Button>
            )}
            <Button variant="ghost" nativeButton={false} render={<Link href="/admin/listings" />}>Back</Button>
            <Button type="submit" disabled={busy}>
              {busy ? 'Saving…' : isNew ? 'Create listing' : 'Save changes'}
            </Button>
          </>
        }
      />

      {message && <Alert variant={message === 'Saved.' ? 'success' : 'destructive'}>{message}</Alert>}

      <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
        <div className="grid gap-6">
          <Card>
            <CardHeader>
              <CardTitle>Details</CardTitle>
            </CardHeader>
            <CardContent className="grid gap-4">
              <div className="grid gap-4 sm:grid-cols-2">
                <Field id="type" label="Type" error={errors.type}>
                  <NativeSelect id="type" value={form.type} onChange={(e) => set('type', e.target.value as ListingType)}>
                    {LISTING_TYPES.map((t) => (
                      <option key={t.value} value={t.value}>
                        {t.label}
                      </option>
                    ))}
                  </NativeSelect>
                </Field>
                <Field id="location" label="Location" error={errors.location}>
                  <Input id="location" value={form.location} onChange={(e) => set('location', e.target.value)} placeholder="e.g. Bashundhara R/A, Dhaka" />
                </Field>
              </div>
              <Field id="title" label="Title" error={errors.title}>
                <Input id="title" value={form.title} onChange={(e) => set('title', e.target.value)} />
              </Field>
              <div className="grid gap-4 sm:grid-cols-[1fr_auto]">
                <Field id="price" label="Price" error={errors.price} hint="Free text, shown as typed (e.g. ৳ 45 Lakh, ৳ 35 Lakh / katha).">
                  <Input id="price" value={form.price} onChange={(e) => set('price', e.target.value)} disabled={form.call_for_price} />
                </Field>
                <Field id="call_for_price" label="Call for Price">
                  <div className="flex h-9 items-center">
                    <Switch id="call_for_price" checked={form.call_for_price} onCheckedChange={(v) => set('call_for_price', v)} />
                  </div>
                </Field>
              </div>

              {form.type === 'property' && (
                <div className="grid gap-4 sm:grid-cols-2">
                  <Field id="land_area" label="Land area">
                    <Input id="land_area" value={form.land_area} onChange={(e) => set('land_area', e.target.value)} placeholder="e.g. 5 katha" />
                  </Field>
                  <Field id="built_area" label="Built area">
                    <Input id="built_area" value={form.built_area} onChange={(e) => set('built_area', e.target.value)} placeholder="e.g. 4,200 sqft" />
                  </Field>
                </div>
              )}
              {form.type === 'plot' && (
                <Field id="size" label="Size" hint="Katha or sqft.">
                  <Input id="size" value={form.size} onChange={(e) => set('size', e.target.value)} placeholder="e.g. 3 katha" />
                </Field>
              )}
              {form.type === 'flat' && (
                <div className="grid gap-4 sm:grid-cols-4">
                  <Field id="size" label="Size (sqft)">
                    <Input id="size" value={form.size} onChange={(e) => set('size', e.target.value)} placeholder="e.g. 1450 sqft" />
                  </Field>
                  <Field id="bedrooms" label="Bedrooms">
                    <Input id="bedrooms" type="number" min={0} value={form.bedrooms} onChange={(e) => set('bedrooms', Number(e.target.value) || 0)} />
                  </Field>
                  <Field id="baths" label="Baths">
                    <Input id="baths" type="number" min={0} value={form.baths} onChange={(e) => set('baths', Number(e.target.value) || 0)} />
                  </Field>
                  <Field id="floor" label="Floor">
                    <Input id="floor" value={form.floor} onChange={(e) => set('floor', e.target.value)} placeholder="e.g. 5th of 9" />
                  </Field>
                </div>
              )}

              <Field id="facilities" label="Facilities" hint="One per line.">
                <Textarea id="facilities" rows={4} value={facilitiesText} onChange={(e) => setFacilitiesText(e.target.value)} placeholder={'Car parking\nGenerator\n24/7 security'} />
              </Field>
              <Field id="description" label="Description">
                <Textarea id="description" rows={8} value={form.description} onChange={(e) => set('description', e.target.value)} />
              </Field>
              <Field id="video_url" label="YouTube video" error={errors.video_url} hint="Paste a YouTube link; it is embedded on the detail page.">
                <Input id="video_url" value={form.video_url} onChange={(e) => set('video_url', e.target.value)} placeholder="https://www.youtube.com/watch?v=…" />
              </Field>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Photos</CardTitle>
              <CardDescription>{isNew ? 'Create the listing first, then add photos here.' : 'Gallery shown on the detail page.'}</CardDescription>
            </CardHeader>
            {!isNew && listing && (
              <CardContent>
                <Gallery listingId={listing.id} images={listing.images ?? []} onChange={(images) => setListing({ ...listing, images })} />
              </CardContent>
            )}
          </Card>
        </div>

        <div className="grid content-start gap-6">
          <Card>
            <CardHeader>
              <CardTitle>Visibility</CardTitle>
            </CardHeader>
            <CardContent className="grid gap-4">
              <label className="flex items-center justify-between gap-4 text-sm">
                <span>
                  <span className="font-medium">Published</span>
                  <span className="text-muted-foreground block text-xs">Visible on the website.</span>
                </span>
                <Switch checked={form.published} onCheckedChange={(v) => set('published', v)} />
              </label>
              <Field id="masterplan_id" label="Linked project map" hint="Cards and the detail page then show live plot availability from the map.">
                <NativeSelect id="masterplan_id" value={form.masterplan_id ?? ''} onChange={(e) => set('masterplan_id', e.target.value ? Number(e.target.value) : null)}>
                  <option value="">None</option>
                  {masterplans.map((m) => <option key={m.id} value={m.id}>{m.name}</option>)}
                </NativeSelect>
              </Field>
              {listing && (
                <p className="text-muted-foreground text-xs">
                  {listing.featured ? 'Shown in the homepage featured row.' : 'Not in the homepage featured row.'}{' '}
                  <Link href="/admin/homepage" className="underline underline-offset-4">
                    Manage featured
                  </Link>
                </p>
              )}
            </CardContent>
          </Card>
        </div>
      </div>
    </form>
  )
}
