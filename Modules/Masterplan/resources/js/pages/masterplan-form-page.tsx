import { ExternalLinkIcon, MapIcon, UploadIcon } from 'lucide-react'
import { useEffect, useRef, useState, type FormEvent } from 'react'
import { Link, useNavigate, useParams } from 'react-router'

import { Field } from '@/components/field'
import { PageHeader } from '@/components/page-header'
import { Alert } from '@/components/ui/alert'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Switch } from '@/components/ui/switch'
import { Textarea } from '@/components/ui/textarea'
import { api, ApiError, assetUrl, upload } from '@/lib/api'

import type { Masterplan, MasterplanInput } from '../types'

export function MasterplanFormPage() {
  const { id } = useParams()
  const navigate = useNavigate()
  const isNew = id === undefined
  const [plan, setPlan] = useState<Masterplan | null>(null)
  const [form, setForm] = useState<MasterplanInput>({ name: '', description: '', published: false })
  const [errors, setErrors] = useState<Record<string, string>>({})
  const [message, setMessage] = useState<string | null>(null)
  const [busy, setBusy] = useState(false)
  const [uploading, setUploading] = useState(false)
  const [loading, setLoading] = useState(!isNew)
  const fileInput = useRef<HTMLInputElement>(null)

  useEffect(() => {
    if (isNew) return
    setLoading(true)
    api<{ masterplan: Masterplan }>(`/admin/masterplans/${id}`)
      .then(({ masterplan }) => {
        setPlan(masterplan)
        setForm({ name: masterplan.name, description: masterplan.description, published: masterplan.published })
      })
      .catch(() => navigate('/masterplans', { replace: true }))
      .finally(() => setLoading(false))
  }, [id, isNew, navigate])

  const submit = async (event: FormEvent) => {
    event.preventDefault()
    setBusy(true)
    setErrors({})
    setMessage(null)
    try {
      if (isNew) {
        const { masterplan } = await api<{ masterplan: Masterplan }>('/admin/masterplans', { method: 'POST', body: JSON.stringify(form) })
        navigate(`/masterplans/${masterplan.id}`, { replace: true })
      } else {
        const { masterplan } = await api<{ masterplan: Masterplan }>(`/admin/masterplans/${id}`, { method: 'PUT', body: JSON.stringify(form) })
        setPlan(masterplan)
        setMessage('Saved.')
      }
    } catch (err) {
      if (err instanceof ApiError && err.status === 422) setErrors(err.fieldErrors())
      else setMessage(err instanceof ApiError ? err.message : 'Something went wrong, please try again.')
    } finally {
      setBusy(false)
    }
  }

  const uploadImage = async (file: File | undefined) => {
    if (!file || !plan) return
    setUploading(true)
    setErrors({})
    try {
      const body = new FormData()
      body.append('file', file)
      const { masterplan } = await upload<{ masterplan: Masterplan }>(`/admin/masterplans/${plan.id}/image`, body)
      setPlan(masterplan)
      setMessage('Image uploaded. Open the builder to place plots.')
    } catch (err) {
      setErrors({ file: err instanceof ApiError ? (err.fieldError('file') ?? err.message) : 'Upload failed.' })
    } finally {
      setUploading(false)
      if (fileInput.current) fileInput.current.value = ''
    }
  }

  if (loading) return <p className="text-muted-foreground text-sm">Loading…</p>

  return (
    <form onSubmit={submit} className="grid gap-6">
      <PageHeader
        title={isNew ? 'New masterplan' : plan?.name ?? 'Edit masterplan'}
        description={isNew ? 'Name the project, save, then upload the layout image.' : 'Project layout details and image.'}
        actions={
          <>
            {plan?.published && plan.image_path && (
              <Button variant="outline" nativeButton={false} render={<a href={`/map/${plan.slug}`} target="_blank" rel="noreferrer" />}><ExternalLinkIcon /> View on site</Button>
            )}
            {plan?.image_path && (
              <Button variant="outline" nativeButton={false} render={<Link to={`/masterplans/${plan.id}/plots`} />}><MapIcon /> Open builder</Button>
            )}
            <Button variant="ghost" nativeButton={false} render={<Link to="/masterplans" />}>Back</Button>
            <Button type="submit" disabled={busy}>{busy ? 'Saving…' : isNew ? 'Create' : 'Save changes'}</Button>
          </>
        }
      />
      {message && <Alert variant="success">{message}</Alert>}

      <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
        <div className="grid gap-6">
          <Card>
            <CardHeader><CardTitle>Details</CardTitle></CardHeader>
            <CardContent className="grid gap-4">
              <Field id="name" label="Project name" error={errors.name}>
                <Input id="name" value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} />
              </Field>
              <Field id="description" label="Description">
                <Textarea id="description" rows={4} value={form.description} onChange={(e) => setForm({ ...form, description: e.target.value })} />
              </Field>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Layout image</CardTitle>
              <CardDescription>
                {isNew ? 'Create the masterplan first, then upload its image.' : 'A high-resolution image of the project layout. Plots are drawn over it in the builder.'}
              </CardDescription>
            </CardHeader>
            {!isNew && plan && (
              <CardContent className="grid gap-3">
                {plan.image_path ? (
                  <div className="bg-muted overflow-hidden rounded-md border">
                    <img src={assetUrl(plan.image_path)} alt="" className="max-h-96 w-full object-contain" />
                  </div>
                ) : null}
                <div className="flex items-center gap-3">
                  <input ref={fileInput} type="file" accept="image/*" hidden onChange={(e) => void uploadImage(e.target.files?.[0])} />
                  <Button type="button" variant="outline" disabled={uploading} onClick={() => fileInput.current?.click()}>
                    <UploadIcon /> {uploading ? 'Uploading…' : plan.image_path ? 'Replace image' : 'Upload image'}
                  </Button>
                  {plan.image_path && <span className="text-muted-foreground text-xs">{plan.image_width} × {plan.image_height} px</span>}
                </div>
                {plan.image_path && (plan.plots?.length ?? 0) > 0 && (
                  <p className="text-muted-foreground text-xs">Replacing the image with one of a different size will misplace existing plots; redraw them in the builder afterwards.</p>
                )}
                {errors.file && <p className="text-destructive text-sm">{errors.file}</p>}
              </CardContent>
            )}
          </Card>
        </div>

        <div className="grid content-start gap-6">
          <Card>
            <CardHeader><CardTitle>Visibility</CardTitle></CardHeader>
            <CardContent>
              <label className="flex items-center justify-between gap-4 text-sm">
                <span>
                  <span className="font-medium">Published</span>
                  <span className="text-muted-foreground block text-xs">Visible under Interactive Map.</span>
                </span>
                <Switch checked={form.published} onCheckedChange={(v) => setForm({ ...form, published: v })} />
              </label>
            </CardContent>
          </Card>
          {plan && (
            <Card>
              <CardHeader><CardTitle>Plots</CardTitle></CardHeader>
              <CardContent className="text-sm">
                <p>{plan.plots?.length ?? 0} plots placed.</p>
                {plan.image_path ? (
                  <Button variant="outline" size="sm" className="mt-3" nativeButton={false} render={<Link to={`/masterplans/${plan.id}/plots`} />}><MapIcon /> Open builder</Button>
                ) : (
                  <p className="text-muted-foreground mt-1 text-xs">Upload the layout image to start placing plots.</p>
                )}
              </CardContent>
            </Card>
          )}
        </div>
      </div>
    </form>
  )
}
