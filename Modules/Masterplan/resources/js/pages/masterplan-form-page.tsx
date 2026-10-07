import { ArrowLeftIcon, ArrowRightIcon, ExternalLinkIcon, MapIcon, UploadIcon } from 'lucide-react'
import { useEffect, useState, type FormEvent } from 'react'
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

import { LayoutPicker, type PickedLayout } from '../components/layout-picker'
import { Steps } from '../components/steps'
import type { Masterplan, MasterplanInput } from '../types'

const EMPTY: MasterplanInput = { name: '', description: '', published: false }

const LAYOUT_HELP = (
  <>
    An SVG drawing of the project. Every plot must be a <code className="bg-muted rounded px-1 py-0.5 text-xs">&lt;g class="plan-plot"&gt;</code> group;
    roads, the lake and other surroundings are plain shapes. Plots are identified automatically when the file is saved, and the builder recolours them by status.
  </>
)

export function MasterplanFormPage() {
  const { id } = useParams()
  return id === undefined ? <NewMasterplanWizard /> : <EditMasterplan id={id} />
}

/** Steps 1 and 2 of adding a masterplan; step 3 is the builder. */
function NewMasterplanWizard() {
  const navigate = useNavigate()
  const [step, setStep] = useState<1 | 2>(1)
  const [layout, setLayout] = useState<PickedLayout | null>(null)
  const [form, setForm] = useState<MasterplanInput>(EMPTY)
  const [errors, setErrors] = useState<Record<string, string>>({})
  const [message, setMessage] = useState<string | null>(null)
  const [busy, setBusy] = useState(false)

  const layoutReady = layout !== null && layout.keys.length > 0 && layout.width > 0 && layout.height > 0

  const create = async (event: FormEvent) => {
    event.preventDefault()
    if (!layout) return
    setBusy(true)
    setErrors({})
    setMessage(null)
    const body = new FormData()
    body.append('layout', layout.file)
    body.append('name', form.name)
    body.append('description', form.description)
    body.append('published', form.published ? '1' : '0')
    try {
      const { masterplan } = await upload<{ masterplan: Masterplan }>('/admin/masterplans', body)
      navigate(`/masterplans/${masterplan.id}/plots`, { replace: true, state: { created: true } })
    } catch (err) {
      if (err instanceof ApiError && err.status === 422) {
        const fieldErrors = err.fieldErrors()
        setErrors(fieldErrors)
        if (fieldErrors.layout) setStep(1)
      } else {
        setMessage(err instanceof ApiError ? err.message : 'Something went wrong, please try again.')
      }
    } finally {
      setBusy(false)
    }
  }

  return (
    <form onSubmit={create} className="grid gap-6">
      <PageHeader
        title="New masterplan"
        description="Upload the layout, describe the project, then fill in the plots."
        actions={<Button variant="ghost" nativeButton={false} render={<Link to="/masterplans" />}>Cancel</Button>}
      />
      <Steps current={step} />
      {message && <Alert variant="destructive">{message}</Alert>}

      {step === 1 ? (
        <Card>
          <CardHeader>
            <CardTitle>Step 1 · Layout</CardTitle>
            <CardDescription>{LAYOUT_HELP}</CardDescription>
          </CardHeader>
          <CardContent className="grid gap-4">
            <LayoutPicker value={layout} onChange={setLayout} />
            {errors.layout && <p className="text-destructive text-sm">{errors.layout}</p>}
            <div className="flex justify-end">
              <Button type="button" disabled={!layoutReady} onClick={() => setStep(2)}>
                Continue <ArrowRightIcon />
              </Button>
            </div>
          </CardContent>
        </Card>
      ) : (
        <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
          <Card>
            <CardHeader>
              <CardTitle>Step 2 · Info</CardTitle>
              <CardDescription>How the project appears on the map list.</CardDescription>
            </CardHeader>
            <CardContent className="grid gap-4">
              <Field id="name" label="Project name" error={errors.name}>
                <Input id="name" value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} autoFocus placeholder="e.g. Royal City Phase 1" />
              </Field>
              <Field id="description" label="Description" error={errors.description}>
                <Textarea id="description" rows={4} value={form.description} onChange={(e) => setForm({ ...form, description: e.target.value })} placeholder="Location or a line about the project" />
              </Field>
              <label className="flex items-center justify-between gap-4 text-sm">
                <span>
                  <span className="font-medium">Published</span>
                  <span className="text-muted-foreground block text-xs">Visible under Interactive Map. You can also publish later.</span>
                </span>
                <Switch checked={form.published} onCheckedChange={(v) => setForm({ ...form, published: v })} />
              </label>
              <div className="flex justify-between">
                <Button type="button" variant="outline" onClick={() => setStep(1)}>
                  <ArrowLeftIcon /> Back
                </Button>
                <Button type="submit" disabled={busy || !layoutReady}>
                  {busy ? 'Creating…' : 'Create and open builder'} <ArrowRightIcon />
                </Button>
              </div>
            </CardContent>
          </Card>
          {layout && (
            <Card className="h-fit">
              <CardHeader>
                <CardTitle>Layout</CardTitle>
                <CardDescription>{layout.file.name} · {layout.keys.length} plots</CardDescription>
              </CardHeader>
              <CardContent>
                <LayoutPreview file={layout.file} />
              </CardContent>
            </Card>
          )}
        </div>
      )}
    </form>
  )
}

function LayoutPreview({ file }: { file: File }) {
  const [url, setUrl] = useState('')
  useEffect(() => {
    const u = URL.createObjectURL(file)
    setUrl(u)
    return () => URL.revokeObjectURL(u)
  }, [file])
  return <div className="bg-muted overflow-hidden rounded-md border">{url && <img src={url} alt="" className="max-h-64 w-full object-contain" />}</div>
}

function EditMasterplan({ id }: { id: string }) {
  const navigate = useNavigate()
  const [plan, setPlan] = useState<Masterplan | null>(null)
  const [form, setForm] = useState<MasterplanInput>(EMPTY)
  const [errors, setErrors] = useState<Record<string, string>>({})
  const [message, setMessage] = useState<{ kind: 'success' | 'destructive'; text: string } | null>(null)
  const [busy, setBusy] = useState(false)
  const [replacing, setReplacing] = useState(false)
  const [replacement, setReplacement] = useState<PickedLayout | null>(null)
  const [uploading, setUploading] = useState(false)

  useEffect(() => {
    api<{ masterplan: Masterplan }>(`/admin/masterplans/${id}`)
      .then(({ masterplan }) => {
        setPlan(masterplan)
        setForm({ name: masterplan.name, description: masterplan.description, published: masterplan.published })
      })
      .catch(() => navigate('/masterplans', { replace: true }))
  }, [id, navigate])

  const submit = async (event: FormEvent) => {
    event.preventDefault()
    setBusy(true)
    setErrors({})
    setMessage(null)
    try {
      const { masterplan } = await api<{ masterplan: Masterplan }>(`/admin/masterplans/${id}`, { method: 'PUT', body: JSON.stringify(form) })
      setPlan(masterplan)
      setMessage({ kind: 'success', text: 'Saved.' })
    } catch (err) {
      if (err instanceof ApiError && err.status === 422) setErrors(err.fieldErrors())
      else setMessage({ kind: 'destructive', text: err instanceof ApiError ? err.message : 'Something went wrong, please try again.' })
    } finally {
      setBusy(false)
    }
  }

  const replaceLayout = async () => {
    if (!plan || !replacement) return
    setUploading(true)
    setErrors({})
    setMessage(null)
    const body = new FormData()
    body.append('layout', replacement.file)
    try {
      const before = plan.plots.length
      const { masterplan } = await upload<{ masterplan: Masterplan }>(`/admin/masterplans/${plan.id}/layout`, body)
      setPlan(masterplan)
      setReplacing(false)
      setReplacement(null)
      const dropped = before - masterplan.plots.length
      setMessage({ kind: 'success', text: `Layout replaced: ${masterplan.layout_keys.length} plots.${dropped > 0 ? ` Details of ${dropped} ${dropped === 1 ? 'plot' : 'plots'} not in the new file were removed.` : ''}` })
    } catch (err) {
      setErrors({ layout: err instanceof ApiError ? (err.fieldError('layout') ?? err.message) : 'Upload failed.' })
    } finally {
      setUploading(false)
    }
  }

  if (!plan) return <p className="text-muted-foreground text-sm">Loading…</p>

  const keys = plan.layout_keys ?? []
  const detailed = plan.plots.filter((p) => keys.includes(p.svg_key)).length
  const replacementReady = replacement !== null && replacement.keys.length > 0 && replacement.width > 0
  const keptOnReplace = replacement ? plan.plots.filter((p) => replacement.keys.includes(p.svg_key)).length : 0

  return (
    <form onSubmit={submit} className="grid gap-6">
      <PageHeader
        title={plan.name}
        description={`${keys.length} plots in the layout · ${detailed} with details`}
        actions={
          <>
            {plan.published && (
              <Button variant="outline" nativeButton={false} render={<a href={`/map/${plan.slug}`} target="_blank" rel="noreferrer" />}><ExternalLinkIcon /> View on site</Button>
            )}
            <Button variant="outline" nativeButton={false} render={<Link to={`/masterplans/${plan.id}/plots`} />}><MapIcon /> Open builder</Button>
            <Button variant="ghost" nativeButton={false} render={<Link to="/masterplans" />}>Back</Button>
            <Button type="submit" disabled={busy}>{busy ? 'Saving…' : 'Save changes'}</Button>
          </>
        }
      />
      {message && <Alert variant={message.kind}>{message.text}</Alert>}

      <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
        <div className="grid gap-6">
          <Card>
            <CardHeader><CardTitle>Info</CardTitle></CardHeader>
            <CardContent className="grid gap-4">
              <Field id="name" label="Project name" error={errors.name}>
                <Input id="name" value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} />
              </Field>
              <Field id="description" label="Description" error={errors.description}>
                <Textarea id="description" rows={4} value={form.description} onChange={(e) => setForm({ ...form, description: e.target.value })} />
              </Field>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Layout</CardTitle>
              <CardDescription>{LAYOUT_HELP}</CardDescription>
            </CardHeader>
            <CardContent className="grid gap-3">
              {replacing ? (
                <>
                  <LayoutPicker value={replacement} onChange={setReplacement} />
                  {replacement && (
                    <p className="text-muted-foreground text-xs">
                      Plot details are kept where the new file has a group with the same key: {keptOnReplace} of {detailed} kept
                      {detailed - keptOnReplace > 0 && <span className="text-amber-700">, {detailed - keptOnReplace} removed</span>}.
                    </p>
                  )}
                  {errors.layout && <p className="text-destructive text-sm">{errors.layout}</p>}
                  <div className="flex gap-2">
                    <Button type="button" size="sm" disabled={!replacementReady || uploading} onClick={() => void replaceLayout()}>
                      <UploadIcon /> {uploading ? 'Uploading…' : 'Replace layout'}
                    </Button>
                    <Button type="button" size="sm" variant="ghost" onClick={() => { setReplacing(false); setReplacement(null); setErrors({}) }}>Cancel</Button>
                  </div>
                </>
              ) : (
                <>
                  <div className="bg-muted overflow-hidden rounded-md border">
                    <img src={assetUrl(plan.image_path)} alt="" className="max-h-96 w-full object-contain" />
                  </div>
                  <div className="flex flex-wrap items-center gap-3">
                    <Button type="button" variant="outline" size="sm" onClick={() => setReplacing(true)}><UploadIcon /> Replace layout</Button>
                    <span className="text-muted-foreground text-xs">{keys.length} plot groups · {plan.image_width} × {plan.image_height}</span>
                  </div>
                </>
              )}
            </CardContent>
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
          <Card>
            <CardHeader><CardTitle>Plots</CardTitle></CardHeader>
            <CardContent className="text-sm">
              <p>{detailed} of {keys.length} plots have details.</p>
              {detailed < keys.length && <p className="text-muted-foreground mt-1 text-xs">Plots without details appear greyed out and cannot be selected on the site.</p>}
              <Button variant="outline" size="sm" className="mt-3" nativeButton={false} render={<Link to={`/masterplans/${plan.id}/plots`} />}><MapIcon /> Open builder</Button>
            </CardContent>
          </Card>
        </div>
      </div>
    </form>
  )
}
