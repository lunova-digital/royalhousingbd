'use client'

import { useEffect, useState, type ComponentProps, type FormEvent } from 'react'

import { Field } from '@/modules/admin/components/field'
import { ImageUpload } from '@/modules/admin/components/image-upload'
import { JsonList } from '@/modules/admin/components/json-list'
import { PageHeader } from '@/modules/admin/components/page-header'
import { Alert } from '@/components/ui/alert'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { Textarea } from '@/components/ui/textarea'
import { api, ApiError } from '@/modules/admin/lib/api'

import { useSettings, type Settings } from './use-settings'

export function SettingsPage() {
  const { settings, error, save } = useSettings()
  const [form, setForm] = useState<Settings>({})
  const [busy, setBusy] = useState(false)
  const [message, setMessage] = useState<string | null>(null)

  useEffect(() => {
    if (settings) setForm(settings)
  }, [settings])

  const set = (key: string, value: string) => setForm((f) => ({ ...f, [key]: value }))
  const text = (key: string, label: string, props: ComponentProps<typeof Input> = {}, hint?: string) => (
    <Field id={key} label={label} hint={hint}>
      <Input id={key} value={form[key] ?? ''} onChange={(e) => set(key, e.target.value)} {...props} />
    </Field>
  )

  const submit = async (e: FormEvent) => {
    e.preventDefault()
    setBusy(true)
    setMessage(null)
    try {
      await save(form)
      setMessage('Saved.')
    } catch (err) {
      setMessage(err instanceof ApiError ? err.message : 'Could not save.')
    } finally {
      setBusy(false)
    }
  }

  if (error) return <Alert variant="destructive">{error}</Alert>
  if (!settings) return <p className="text-muted-foreground text-sm">Loading…</p>

  return (
    <div className="grid gap-6">
      <form onSubmit={submit} className="grid gap-6">
        <PageHeader
          title="Settings"
          description="WhatsApp number, contact details, social links, SEO and brand."
          actions={<Button type="submit" disabled={busy}>{busy ? 'Saving…' : 'Save settings'}</Button>}
        />
        {message && <Alert variant={message === 'Saved.' ? 'success' : 'destructive'}>{message}</Alert>}

        <Tabs defaultValue="contact">
          <TabsList>
            <TabsTrigger value="contact">Contact</TabsTrigger>
            <TabsTrigger value="social">Social</TabsTrigger>
            <TabsTrigger value="seo">SEO & Brand</TabsTrigger>
            <TabsTrigger value="about">About page</TabsTrigger>
            <TabsTrigger value="legal">Legal</TabsTrigger>
          </TabsList>

          <TabsContent value="contact">
            <Card>
              <CardHeader>
                <CardTitle>Contact</CardTitle>
                <CardDescription>The WhatsApp number receives every enquiry from the site and the app.</CardDescription>
              </CardHeader>
              <CardContent className="grid gap-4">
                {text('whatsapp_number', 'WhatsApp number', { placeholder: '8801XXXXXXXXX', inputMode: 'tel' }, 'International format without + or spaces, e.g. 8801712345678.')}
                <div className="grid gap-4 sm:grid-cols-2">
                  {text('phone', 'Phone', { placeholder: '+880 17XX-XXXXXX' })}
                  {text('email', 'Email', { type: 'email' })}
                </div>
                <Field id="address" label="Address">
                  <Textarea id="address" rows={3} value={form.address ?? ''} onChange={(e) => set('address', e.target.value)} />
                </Field>
                {text('working_hours', 'Working hours', { placeholder: 'Saturday - Thursday: 9:00 AM - 6:00 PM' })}
                {text('map_embed_url', 'Google Maps embed URL (optional)', { placeholder: 'https://www.google.com/maps/embed?pb=…' }, 'From Google Maps → Share → Embed a map → copy the src of the iframe.')}
              </CardContent>
            </Card>
          </TabsContent>

          <TabsContent value="social">
            <Card>
              <CardHeader><CardTitle>Social links</CardTitle><CardDescription>Shown in the footer when filled in.</CardDescription></CardHeader>
              <CardContent className="grid gap-4 sm:grid-cols-2">
                {text('facebook_url', 'Facebook', { placeholder: 'https://facebook.com/…' })}
                {text('youtube_url', 'YouTube', { placeholder: 'https://youtube.com/@…' })}
                {text('instagram_url', 'Instagram', { placeholder: 'https://instagram.com/…' })}
                {text('tiktok_url', 'TikTok', { placeholder: 'https://tiktok.com/@…' })}
              </CardContent>
            </Card>
          </TabsContent>

          <TabsContent value="seo">
            <div className="grid gap-6">
              <Card>
                <CardHeader><CardTitle>Brand</CardTitle></CardHeader>
                <CardContent className="grid gap-4">
                  {text('site_name', 'Site name')}
                  <Field id="logo" label="Logo" hint="Shown in the header; the site name is used when empty.">
                    <ImageUpload value={form.logo ?? ''} onChange={(path) => set('logo', path)} aspect="aspect-[3/1]" />
                  </Field>
                </CardContent>
              </Card>
              <Card>
                <CardHeader><CardTitle>Basic SEO</CardTitle><CardDescription>Defaults for pages that do not set their own.</CardDescription></CardHeader>
                <CardContent className="grid gap-4">
                  {text('meta_title', 'Homepage title')}
                  <Field id="meta_description" label="Meta description">
                    <Textarea id="meta_description" rows={3} value={form.meta_description ?? ''} onChange={(e) => set('meta_description', e.target.value)} />
                  </Field>
                  <Field id="og_image" label="Share image" hint="Used when a page is shared on WhatsApp or Facebook.">
                    <ImageUpload value={form.og_image ?? ''} onChange={(path) => set('og_image', path)} />
                  </Field>
                </CardContent>
              </Card>
            </div>
          </TabsContent>

          <TabsContent value="about">
            <div className="grid gap-6">
              <Card>
                <CardHeader><CardTitle>Our story</CardTitle><CardDescription>The About page headline, story and the four stat boxes.</CardDescription></CardHeader>
                <CardContent className="grid gap-4">
                  {text('about_title', 'Headline', {}, 'The last word is highlighted in red.')}
                  <Field id="about_story" label="Story" hint="Blank lines separate paragraphs.">
                    <Textarea id="about_story" rows={8} value={form.about_story ?? ''} onChange={(e) => set('about_story', e.target.value)} />
                  </Field>
                  <Field label="Stats">
                    <JsonList<{ value: string; label: string }> value={form.about_stats ?? '[]'} onChange={(v) => set('about_stats', v)} empty={{ value: '', label: '' }} addLabel="Add stat"
                      render={(item, setItem) => (<><Input className="w-32" placeholder="150+" value={item.value} onChange={(e) => setItem({ value: e.target.value })} /><Input className="flex-1" placeholder="Projects delivered" value={item.label} onChange={(e) => setItem({ label: e.target.value })} /></>)} />
                  </Field>
                </CardContent>
              </Card>
              <Card>
                <CardHeader><CardTitle>Team</CardTitle><CardDescription>Leaders appear in the large cards; everyone else under &quot;Our Team&quot;.</CardDescription></CardHeader>
                <CardContent>
                  <JsonList<{ name: string; role: string; photo: string; leader: boolean }> value={form.team_members ?? '[]'} onChange={(v) => set('team_members', v)} empty={{ name: '', role: '', photo: '', leader: false }} addLabel="Add member"
                    render={(item, setItem) => (
                      <div className="grid flex-1 gap-3 sm:grid-cols-[1fr_1fr_auto]">
                        <Input placeholder="Name" value={item.name} onChange={(e) => setItem({ name: e.target.value })} />
                        <Input placeholder="Role" value={item.role} onChange={(e) => setItem({ role: e.target.value })} />
                        <label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={item.leader} onChange={(e) => setItem({ leader: e.target.checked })} /> Leadership</label>
                        <div className="sm:col-span-3"><ImageUpload value={item.photo} onChange={(photo) => setItem({ photo })} aspect="aspect-[4/5] max-w-40" /></div>
                      </div>
                    )} />
                </CardContent>
              </Card>
              <Card>
                <CardHeader><CardTitle>Partners</CardTitle></CardHeader>
                <CardContent>
                  <JsonList<{ name: string; role: string; logo: string }> value={form.partners ?? '[]'} onChange={(v) => set('partners', v)} empty={{ name: '', role: '', logo: '' }} addLabel="Add partner"
                    render={(item, setItem) => (
                      <div className="grid flex-1 gap-3 sm:grid-cols-2">
                        <Input placeholder="Partner name" value={item.name} onChange={(e) => setItem({ name: e.target.value })} />
                        <Input placeholder="Role, e.g. Legal partner" value={item.role} onChange={(e) => setItem({ role: e.target.value })} />
                        <div className="sm:col-span-2"><ImageUpload value={item.logo} onChange={(logo) => setItem({ logo })} aspect="aspect-square max-w-32" /></div>
                      </div>
                    )} />
                </CardContent>
              </Card>
            </div>
          </TabsContent>

          <TabsContent value="legal">
            <Card>
              <CardHeader><CardTitle>Legal pages</CardTitle><CardDescription>Shown at /privacy and /terms.</CardDescription></CardHeader>
              <CardContent className="grid gap-4">
                <Field id="privacy_text" label="Privacy Policy"><Textarea id="privacy_text" rows={10} value={form.privacy_text ?? ''} onChange={(e) => set('privacy_text', e.target.value)} /></Field>
                <Field id="terms_text" label="Terms of Service"><Textarea id="terms_text" rows={10} value={form.terms_text ?? ''} onChange={(e) => set('terms_text', e.target.value)} /></Field>
              </CardContent>
            </Card>
          </TabsContent>
        </Tabs>
      </form>

      <PasswordCard />
    </div>
  )
}

function PasswordCard() {
  const [current, setCurrent] = useState('')
  const [next, setNext] = useState('')
  const [errors, setErrors] = useState<Record<string, string>>({})
  const [message, setMessage] = useState<string | null>(null)
  const [busy, setBusy] = useState(false)

  const submit = async (e: FormEvent) => {
    e.preventDefault()
    setBusy(true)
    setErrors({})
    setMessage(null)
    try {
      await api('/auth/password', { method: 'PUT', body: JSON.stringify({ current_password: current, password: next }) })
      setCurrent('')
      setNext('')
      setMessage('Password updated.')
    } catch (err) {
      if (err instanceof ApiError && err.status === 422) setErrors(err.fieldErrors())
      else setMessage(err instanceof ApiError ? err.message : 'Could not update the password.')
    } finally {
      setBusy(false)
    }
  }

  return (
    <Card>
      <CardHeader><CardTitle>Admin password</CardTitle><CardDescription>Change the password used to sign in here.</CardDescription></CardHeader>
      <CardContent>
        <form onSubmit={submit} className="grid max-w-md gap-4">
          <Field id="current_password" label="Current password" error={errors.current_password}>
            <Input id="current_password" type="password" autoComplete="current-password" value={current} onChange={(e) => setCurrent(e.target.value)} required />
          </Field>
          <Field id="new_password" label="New password" error={errors.password} hint="At least 8 characters.">
            <Input id="new_password" type="password" autoComplete="new-password" value={next} onChange={(e) => setNext(e.target.value)} required minLength={8} />
          </Field>
          {message && <Alert variant={message === 'Password updated.' ? 'success' : 'destructive'}>{message}</Alert>}
          <div><Button type="submit" variant="outline" disabled={busy}>{busy ? 'Updating…' : 'Update password'}</Button></div>
        </form>
      </CardContent>
    </Card>
  )
}
