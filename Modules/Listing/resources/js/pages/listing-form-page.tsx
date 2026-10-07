import { ExternalLinkIcon } from 'lucide-react'
import { useEffect, useState, type FormEvent } from 'react'
import { Link, useNavigate, useParams } from 'react-router'

import { Field } from '@/components/field'
import { PageHeader } from '@/components/page-header'
import { Alert } from '@/components/ui/alert'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardAction, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { NativeSelect } from '@/components/ui/native-select'
import { Switch } from '@/components/ui/switch'
import { Textarea } from '@/components/ui/textarea'
import { api, ApiError } from '@/lib/api'

import { Gallery } from '../components/gallery'
import { EMPTY_INPUT, LISTING_TYPES, listingUrl, type Listing, type ListingInput, type ListingType } from '../types'

export function ListingFormPage() {
  const { id } = useParams()
  const navigate = useNavigate()
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
        const { id: _id, slug: _slug, featured: _f, featured_order: _fo, images: _i, created_at: _c, updated_at: _u, ...input } = listing
        setForm(input)
        setFacilitiesText((listing.facilities ?? []).join('\n'))
      })
      .catch(() => navigate('/listings', { replace: true }))
      .finally(() => setLoading(false))
  }, [id, isNew, navigate])

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
        navigate(`/listings/${listing.id}`, { replace: true, state: { created: true } })
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
            <Button variant="ghost" nativeButton={false} render={<Link to="/listings" />}>Back</Button>
            <Button type="submit" disabled={busy}>
              {busy ? 'Saving…' : isNew ? 'Create listing' : 'Save changes'}
            </Button>
          </>
        }
      />

      {message && <Alert variant={message === 'Saved.' ? 'success' : 'destructive'}>{message}</Alert>}

      <div className="grid gap-6 lg:grid-cols-[minmax(0,1fr)_400px] xl:grid-cols-[minmax(0,1fr)_440px]">
        <div className="grid content-start gap-6">
          <Card>
            <CardHeader>
              <CardTitle>Details</CardTitle>
              <CardDescription>What it is, where it is and what it costs.</CardDescription>
            </CardHeader>
            <CardContent className="grid gap-4">
              <Field id="title" label="Title" error={errors.title}>
                <Input id="title" value={form.title} onChange={(e) => set('title', e.target.value)} placeholder="e.g. South-facing 3-bed flat in Bashundhara" />
              </Field>
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
              <div className="grid gap-4 sm:grid-cols-[minmax(0,1fr)_auto]">
                <Field id="price" label="Price" error={errors.price} hint="Free text, shown as typed (e.g. ৳ 45 Lakh, ৳ 35 Lakh / katha).">
                  <Input id="price" value={form.price} onChange={(e) => set('price', e.target.value)} disabled={form.call_for_price} placeholder="৳ 45 Lakh" />
                </Field>
                <label className="bg-muted/40 flex h-9 items-center gap-3 self-start rounded-md border px-3 text-sm sm:mt-6">
                  <Switch id="call_for_price" checked={form.call_for_price} onCheckedChange={(v) => set('call_for_price', v)} />
                  <span className="font-medium whitespace-nowrap">Call for price</span>
                </label>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Specifications</CardTitle>
              <CardDescription>
                {form.type === 'property' && 'Land and built-up area of the property.'}
                {form.type === 'plot' && 'Plot size in katha or sqft.'}
                {form.type === 'flat' && 'Size, rooms and floor of the flat.'}
              </CardDescription>
            </CardHeader>
            <CardContent className="grid gap-4">
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
                <Field id="size" label="Size" hint="Katha or sqft." className="sm:max-w-xs">
                  <Input id="size" value={form.size} onChange={(e) => set('size', e.target.value)} placeholder="e.g. 3 katha" />
                </Field>
              )}
              {form.type === 'flat' && (
                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
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
              <Field id="facilities" label="Facilities" hint="One per line; shown as a checklist on the detail page.">
                <Textarea id="facilities" rows={4} value={facilitiesText} onChange={(e) => setFacilitiesText(e.target.value)} placeholder={'Car parking\nGenerator\n24/7 security'} />
              </Field>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Description &amp; video</CardTitle>
              <CardDescription>The long text and optional YouTube embed on the detail page.</CardDescription>
            </CardHeader>
            <CardContent className="grid gap-4">
              <Field id="description" label="Description">
                <Textarea id="description" rows={10} value={form.description} onChange={(e) => set('description', e.target.value)} />
              </Field>
              <Field id="video_url" label="YouTube video" error={errors.video_url} hint="Paste a YouTube link; it is embedded on the detail page.">
                <Input id="video_url" value={form.video_url} onChange={(e) => set('video_url', e.target.value)} placeholder="https://www.youtube.com/watch?v=…" />
              </Field>
            </CardContent>
          </Card>
        </div>

        <div className="grid content-start gap-6 lg:sticky lg:top-6 lg:self-start">
          <Card>
            <CardHeader>
              <CardTitle>Photos</CardTitle>
              <CardDescription>{isNew ? 'Create the listing first, then add photos here.' : 'Gallery shown on the detail page. The first photo is the cover.'}</CardDescription>
              {!isNew && listing && (
                <CardAction>
                  <Badge variant="secondary">{listing.images?.length ?? 0} {listing.images?.length === 1 ? 'photo' : 'photos'}</Badge>
                </CardAction>
              )}
            </CardHeader>
            {!isNew && listing && (
              <CardContent>
                <Gallery listingId={listing.id} images={listing.images ?? []} onChange={(images) => setListing({ ...listing, images })} />
              </CardContent>
            )}
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Visibility</CardTitle>
            </CardHeader>
            <CardContent className="grid gap-4">
              <label className="flex items-center justify-between gap-4 rounded-md border p-3 text-sm">
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
                  <Link to="/homepage" className="underline underline-offset-4">
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
