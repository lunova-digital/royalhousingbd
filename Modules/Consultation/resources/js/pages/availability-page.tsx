import { useEffect, useState, type FormEvent } from 'react'

import { Field } from '@/components/field'
import { PageHeader } from '@/components/page-header'
import { Alert } from '@/components/ui/alert'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { NativeSelect } from '@/components/ui/native-select'
import { Switch } from '@/components/ui/switch'
import { Textarea } from '@/components/ui/textarea'
import { api, ApiError } from '@/lib/api'

import { MODE_LABELS } from '../types'

type Values = Record<string, string>

// ISO weekday numbers in the Bangladeshi week order.
const DAYS = [
  [6, 'Sat'], [7, 'Sun'], [1, 'Mon'], [2, 'Tue'], [3, 'Wed'], [4, 'Thu'], [5, 'Fri'],
] as const

const has = (csv: string, value: string) => csv.split(',').includes(value)
const toggle = (csv: string, value: string, on: boolean) => {
  const set = new Set(csv.split(',').filter(Boolean))
  if (on) set.add(value)
  else set.delete(value)
  return [...set].join(',')
}

// When the consultant takes bookings: days, hours, slot length, notice, and how to meet.
export function AvailabilityPage() {
  const [form, setForm] = useState<Values | null>(null)
  const [modes, setModes] = useState<string[]>([])
  const [errors, setErrors] = useState<Record<string, string>>({})
  const [message, setMessage] = useState<string | null>(null)
  const [busy, setBusy] = useState(false)

  useEffect(() => {
    api<{ availability: Values; modes: string[] }>('/admin/consultations/availability')
      .then((data) => {
        setForm(data.availability)
        setModes(data.modes)
      })
      .catch(() => setMessage('Could not load availability.'))
  }, [])

  if (!form) return message ? <Alert variant="destructive">{message}</Alert> : <p className="text-muted-foreground text-sm">Loading…</p>

  const set = (key: string, value: string) => setForm((f) => ({ ...f!, [key]: value }))

  const submit = async (e: FormEvent) => {
    e.preventDefault()
    setBusy(true)
    setErrors({})
    setMessage(null)
    try {
      const data = await api<{ availability: Values }>('/admin/consultations/availability', { method: 'PUT', body: JSON.stringify(form) })
      setForm(data.availability)
      setMessage('Saved.')
    } catch (err) {
      if (err instanceof ApiError && err.status === 422) setErrors(err.fieldErrors())
      setMessage(err instanceof ApiError ? err.message : 'Could not save.')
    } finally {
      setBusy(false)
    }
  }

  const enabled = form.consult_enabled === '1'

  return (
    <form onSubmit={submit} className="grid gap-6">
      <PageHeader
        title="Availability"
        description="When visitors can book a free consultation. Times are Bangladesh time; each booking takes one slot."
        actions={<Button type="submit" disabled={busy}>{busy ? 'Saving…' : 'Save'}</Button>}
      />
      {message && <Alert variant={message === 'Saved.' ? 'success' : 'destructive'}>{message}</Alert>}

      <Card>
        <CardHeader>
          <CardTitle>Bookings</CardTitle>
          <CardDescription>Switching off hides the floating button and the booking form; existing bookings stay in the list.</CardDescription>
        </CardHeader>
        <CardContent className="grid gap-4">
          <label className="flex items-center gap-3 text-sm font-medium">
            <Switch checked={enabled} onCheckedChange={(on) => set('consult_enabled', on ? '1' : '0')} /> {enabled ? 'Taking bookings' : 'Paused'}
          </label>
          <Field id="consult_note" label="Note shown in the form (optional)" hint="Replaces the standard intro, e.g. a holiday notice." error={errors.consult_note}>
            <Textarea id="consult_note" rows={2} maxLength={300} value={form.consult_note ?? ''} onChange={(e) => set('consult_note', e.target.value)} />
          </Field>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Days and hours</CardTitle>
          <CardDescription>Slots run from the start time in steps of the slot length until the end time.</CardDescription>
        </CardHeader>
        <CardContent className="grid gap-4">
          <Field label="Days" error={errors.consult_days}>
            <div className="flex flex-wrap gap-2">
              {DAYS.map(([n, label]) => {
                const on = has(form.consult_days ?? '', String(n))
                return (
                  <label key={n} className={`cursor-pointer rounded-md border px-3 py-1.5 text-sm font-medium select-none ${on ? 'bg-primary text-primary-foreground border-primary' : 'bg-background'}`}>
                    <input type="checkbox" className="sr-only" checked={on} onChange={(e) => set('consult_days', toggle(form.consult_days ?? '', String(n), e.target.checked))} />
                    {label}
                  </label>
                )
              })}
            </div>
          </Field>
          <div className="grid gap-4 sm:grid-cols-3">
            <Field id="consult_start" label="First slot starts" error={errors.consult_start}>
              <Input id="consult_start" type="time" step={300} value={form.consult_start ?? ''} onChange={(e) => set('consult_start', e.target.value)} />
            </Field>
            <Field id="consult_end" label="Last slot ends by" error={errors.consult_end}>
              <Input id="consult_end" type="time" step={300} value={form.consult_end ?? ''} onChange={(e) => set('consult_end', e.target.value)} />
            </Field>
            <Field id="consult_slot_minutes" label="Slot length" error={errors.consult_slot_minutes}>
              <NativeSelect id="consult_slot_minutes" value={form.consult_slot_minutes ?? '30'} onChange={(e) => set('consult_slot_minutes', e.target.value)}>
                {[15, 20, 30, 45, 60, 90].map((m) => <option key={m} value={m}>{m} minutes</option>)}
              </NativeSelect>
            </Field>
          </div>
          <div className="grid gap-4 sm:grid-cols-2">
            <Field id="consult_lead_hours" label="Minimum notice" hint="Slots sooner than this are not offered." error={errors.consult_lead_hours}>
              <NativeSelect id="consult_lead_hours" value={form.consult_lead_hours ?? '3'} onChange={(e) => set('consult_lead_hours', e.target.value)}>
                {[0, 1, 2, 3, 6, 12, 24, 48].map((h) => <option key={h} value={h}>{h === 0 ? 'None' : h === 1 ? '1 hour' : h < 24 ? `${h} hours` : `${h / 24} day${h > 24 ? 's' : ''}`}</option>)}
              </NativeSelect>
            </Field>
            <Field id="consult_horizon_days" label="How far ahead" hint="How many days the calendar shows." error={errors.consult_horizon_days}>
              <NativeSelect id="consult_horizon_days" value={form.consult_horizon_days ?? '14'} onChange={(e) => set('consult_horizon_days', e.target.value)}>
                {[7, 14, 21, 30, 60].map((d) => <option key={d} value={d}>{d} days</option>)}
              </NativeSelect>
            </Field>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Ways to meet</CardTitle>
          <CardDescription>The visitor picks one of these when booking.</CardDescription>
        </CardHeader>
        <CardContent>
          <Field label="Offered" error={errors.consult_modes}>
            <div className="flex flex-wrap gap-2">
              {modes.map((mode) => {
                const on = has(form.consult_modes ?? '', mode)
                return (
                  <label key={mode} className={`cursor-pointer rounded-md border px-3 py-1.5 text-sm font-medium select-none ${on ? 'bg-primary text-primary-foreground border-primary' : 'bg-background'}`}>
                    <input type="checkbox" className="sr-only" checked={on} onChange={(e) => set('consult_modes', toggle(form.consult_modes ?? '', mode, e.target.checked))} />
                    {MODE_LABELS[mode as keyof typeof MODE_LABELS] ?? mode}
                  </label>
                )
              })}
            </div>
          </Field>
        </CardContent>
      </Card>
    </form>
  )
}
