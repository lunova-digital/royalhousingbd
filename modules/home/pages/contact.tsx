import type { Metadata } from 'next'

import { Icon } from '@/modules/site/components/icon'
import { PageHero, WhatsAppButton } from '@/modules/site/components/partials'
import { getSettings, pageMetadata, whatsapp, type IconName } from '@/modules/site/site'

import { ContactForm } from '../components/contact-form'

export async function generateMetadata(): Promise<Metadata> {
  const s = await getSettings()
  return pageMetadata({ title: 'Contact Us', description: `Get in touch with ${s.site_name} by phone, email or WhatsApp.`, path: '/contact' })
}

const REASONS = ['Free property consultation', 'Expert guidance on investments', 'Personalised site visits', 'Flexible payment options']

/** /contact (FR-COMM-002): info cards, the WhatsApp form, map embed. */
export default async function ContactPage() {
  const settings = await getSettings()
  const wa = whatsapp(settings, "Hello, I'd like to get in touch about your listings.")
  const cards: [IconName, string, string][] = [
    ['pin', 'Visit Us', settings.address ?? ''],
    ['phone', 'Call Us', settings.phone ?? ''],
    ['mail', 'Email Us', settings.email ?? ''],
    ['clock', 'Working Hours', settings.working_hours ?? ''],
  ]

  return (
    <>
      <PageHero eyebrow="Contact Us" title="Let's Start a" accent="Conversation" text="Have questions about our properties? Ready to invest? Our team is here to help you every step of the way." />

      <section className="mx-auto max-w-7xl px-4 sm:px-6">
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          {cards.map(([icon, heading, value]) => value ? (
            <div key={heading} className="card p-6 text-center"><span className="icon-box"><Icon name={icon} className="size-5" /></span><h3 className="mt-4 font-display text-lg font-semibold">{heading}</h3><p className="mt-1 text-sm text-slate-400">{value}</p></div>
          ) : null)}
        </div>
      </section>

      <section className="mx-auto grid max-w-7xl gap-6 px-4 py-14 sm:px-6 lg:grid-cols-2">
        <ContactForm whatsappNumber={settings.whatsapp_number ?? ''} />

        <div className="grid content-start gap-6">
          {settings.map_embed_url && (
            <div className="card overflow-hidden"><h3 className="p-5 font-display text-lg font-semibold">Main Office</h3><iframe src={settings.map_embed_url} className="aspect-[16/9] w-full" loading="lazy" referrerPolicy="no-referrer-when-downgrade" title="Location map" allowFullScreen /></div>
          )}
          {settings.whatsapp_number && (
            <div className="card flex flex-wrap items-center gap-4 p-6">
              <span className="inline-flex size-14 items-center justify-center rounded-xl bg-plot-available/15 text-plot-available"><Icon name="whatsapp" className="size-7" /></span>
              <span className="flex-1"><span className="block font-display text-lg font-semibold">Chat on WhatsApp</span><span className="block text-sm text-slate-400">Get instant responses to your queries</span></span>
              <WhatsAppButton href={wa} label="Chat Now" className="h-11" />
            </div>
          )}
          <div className="card p-6">
            <h3 className="font-display text-lg font-semibold">Why Contact Us?</h3>
            <ul className="mt-4 grid gap-3 text-sm text-slate-300">
              {REASONS.map((item) => <li key={item} className="flex items-center gap-3"><Icon name="check" className="size-5 text-plot-available" />{item}</li>)}
            </ul>
          </div>
        </div>
      </section>
    </>
  )
}
