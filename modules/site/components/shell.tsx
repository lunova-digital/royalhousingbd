import Link from 'next/link'
import type { ReactNode } from 'react'

import type { SettingsMap } from '@/modules/settings/settings'

import { Icon, WhatsAppIcon } from './icon'
import { LightboxProvider } from './lightbox'
import { BottomNav, MainNav } from './nav'
import { assetUrl, whatsapp, type IconName } from '../site'

function Brand({ settings, sub }: { settings: SettingsMap; sub: string }) {
  const name = settings.site_name ?? ''
  return (
    <Link href="/" className="group flex items-center gap-2 lg:gap-3">
      {settings.logo ? (
        <img src={assetUrl(settings.logo)} alt={name} className="size-10 rounded-full bg-white object-contain p-1 transition-transform duration-300 group-hover:scale-105 lg:size-12" />
      ) : (
        <span className="inline-flex size-10 items-center justify-center rounded-full bg-white font-display text-lg font-bold text-brand transition-transform duration-300 group-hover:scale-105 lg:size-12 lg:text-xl">R</span>
      )}
      <span className="hidden sm:block">
        <span className="block font-display text-base font-bold leading-tight text-fg lg:text-lg">{name}</span>
        <span className="block text-xs text-muted-fg">&amp; {sub}</span>
      </span>
    </Link>
  )
}

const SOCIAL: [string, IconName][] = [['facebook_url', 'facebook'], ['instagram_url', 'instagram'], ['youtube_url', 'youtube'], ['tiktok_url', 'tiktok']]

/** The public chrome: header, footer, bottom tab bar, floating WhatsApp and the lightbox. */
export function SiteShell({ settings, children }: { settings: SettingsMap; children: ReactNode }) {
  const siteName = settings.site_name ?? ''
  const whatsappFloat = whatsapp(settings, `Hello, I found you on ${siteName} and I'd like to know more.`)

  return (
    <LightboxProvider>
      <header className="sticky top-0 z-50 border-b border-navy-500/60 bg-navy-900/90 backdrop-blur-xl">
        <div className="container-x">
          <div className="flex h-16 items-center justify-between lg:h-20">
            <Brand settings={settings} sub="Real Estate" />
            <MainNav />
            <div className="flex items-center gap-2 lg:gap-3">
              {settings.phone && (
                <a href={`tel:${settings.phone}`} className="btn-primary hidden md:inline-flex"><Icon name="phone" className="size-4" /> Get In Touch</a>
              )}
            </div>
          </div>
        </div>
      </header>

      <main>{children}</main>

      <footer className="border-t border-navy-500 bg-navy-950">
        <div className="container-x py-16">
          <div className="grid grid-cols-1 gap-12 md:grid-cols-2 lg:grid-cols-5">
            <div className="lg:col-span-2">
              <div className="mb-6 flex items-center gap-3">
                {settings.logo ? (
                  <img src={assetUrl(settings.logo)} alt={siteName} className="size-12 rounded-full bg-white object-contain p-1" />
                ) : (
                  <span className="inline-flex size-12 items-center justify-center rounded-full bg-white font-display text-xl font-bold text-brand">R</span>
                )}
                <Link href="/"><h2 className="font-display text-xl font-bold text-fg">{siteName}</h2><p className="text-sm text-muted-fg">&amp; Real Estate</p></Link>
              </div>
              <p className="mb-6 max-w-md text-muted-fg">Your trusted partner for residential plots, flats and property development. Browse our projects, explore the interactive project map and reach us on WhatsApp.</p>
              <div className="flex gap-3">
                {SOCIAL.map(([key, icon]) => settings[key] ? (
                  <a key={key} href={settings[key]} target="_blank" rel="noreferrer" aria-label={icon} className="flex size-10 items-center justify-center rounded-lg bg-navy-600 text-muted-fg transition-all duration-300 hover:bg-brand hover:text-fg"><Icon name={icon} className="size-5" /></a>
                ) : null)}
              </div>
            </div>
            <div>
              <h3 className="mb-4 font-display text-lg font-semibold text-fg">Company</h3>
              <ul className="space-y-3">
                <li><Link className="text-muted-fg transition-colors hover:text-brand" href="/about">About Us</Link></li>
                <li><Link className="text-muted-fg transition-colors hover:text-brand" href="/about#team">Our Team</Link></li>
                <li><Link className="text-muted-fg transition-colors hover:text-brand" href="/projects">Our Projects</Link></li>
                <li><Link className="text-muted-fg transition-colors hover:text-brand" href="/gallery">Gallery</Link></li>
              </ul>
            </div>
            <div>
              <h3 className="mb-4 font-display text-lg font-semibold text-fg">Contact</h3>
              <ul className="space-y-4">
                {settings.address && <li className="flex items-start gap-3"><Icon name="pin" className="size-5 shrink-0 text-brand" /><span className="text-sm text-muted-fg">{settings.address}</span></li>}
                {settings.phone && <li className="flex items-center gap-3"><Icon name="phone" className="size-5 shrink-0 text-brand" /><a href={`tel:${settings.phone}`} className="text-sm text-muted-fg hover:text-brand">{settings.phone}</a></li>}
                {settings.email && <li className="flex items-center gap-3"><Icon name="mail" className="size-5 shrink-0 text-brand" /><a href={`mailto:${settings.email}`} className="text-sm text-muted-fg hover:text-brand">{settings.email}</a></li>}
                {settings.whatsapp_number && <li className="flex items-center gap-3"><Icon name="whatsapp" className="size-5 shrink-0 text-brand" /><a href={whatsappFloat} target="_blank" rel="noreferrer" className="text-sm text-muted-fg hover:text-brand">WhatsApp {settings.whatsapp_number}</a></li>}
              </ul>
            </div>
          </div>
        </div>
        <div className="border-t border-navy-500">
          <div className="container-x flex flex-col items-center justify-between gap-4 py-6 text-sm text-muted-fg md:flex-row">
            <p>© {new Date().getFullYear()} {siteName}. All rights reserved.</p>
            <div className="flex gap-6"><Link href="/privacy" className="hover:text-brand">Privacy Policy</Link><Link href="/terms" className="hover:text-brand">Terms of Service</Link></div>
          </div>
        </div>
      </footer>

      <BottomNav />

      {/* FR-COMM-001: floating WhatsApp icon on every page */}
      {settings.whatsapp_number && (
        <a href={whatsappFloat} target="_blank" rel="noreferrer" aria-label="Chat on WhatsApp" className="fixed right-4 bottom-20 z-40 inline-flex size-14 items-center justify-center rounded-full bg-whatsapp text-white shadow-lg shadow-black/40 transition-transform hover:scale-105 lg:right-6 lg:bottom-6">
          <WhatsAppIcon />
        </a>
      )}
    </LightboxProvider>
  )
}
