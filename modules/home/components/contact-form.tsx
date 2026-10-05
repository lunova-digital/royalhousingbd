'use client'

import type { FormEvent } from 'react'

import { Icon } from '@/modules/site/components/icon'

/** The message form composes a WhatsApp chat (no enquiries are stored, per SRS §9). */
export function ContactForm({ whatsappNumber }: { whatsappNumber: string }) {
  const submit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    const data = new FormData(event.currentTarget)
    const lines = [
      `Subject: ${data.get('subject')}`,
      `Name: ${data.get('name')}`,
      data.get('phone') ? `Phone: ${data.get('phone')}` : '',
      data.get('email') ? `Email: ${data.get('email')}` : '',
      '',
      String(data.get('message') ?? ''),
    ].filter((line, i) => line !== '' || i === 4)
    const number = whatsappNumber.replace(/\D+/g, '')
    const url = number
      ? `https://wa.me/${number}?text=${encodeURIComponent(lines.join('\n'))}`
      : `mailto:?subject=${encodeURIComponent(String(data.get('subject')))}&body=${encodeURIComponent(lines.join('\n'))}`
    window.open(url, '_blank', 'noopener')
  }

  return (
    <form onSubmit={submit} className="card p-6 sm:p-8">
      <h2 className="font-display text-3xl font-bold">Send Us a Message</h2>
      <p className="mt-1 text-sm text-slate-400">Your message opens in WhatsApp so we can reply right away.</p>
      <div className="mt-6 grid gap-5 sm:grid-cols-2">
        <label className="grid gap-2 text-sm font-medium"><span>Full Name *</span><input name="name" required className="input" /></label>
        <label className="grid gap-2 text-sm font-medium"><span>Email Address</span><input name="email" type="email" className="input" /></label>
        <label className="grid gap-2 text-sm font-medium"><span>Phone Number</span><input name="phone" type="tel" className="input" /></label>
        <label className="grid gap-2 text-sm font-medium"><span>Subject *</span>
          <select name="subject" required defaultValue="" className="input">
            <option value="">Select a subject</option>
            <option>Property Inquiry</option><option>Schedule Site Visit</option><option>Investment Consultation</option><option>Customer Support</option><option>Other</option>
          </select>
        </label>
        <label className="grid gap-2 text-sm font-medium sm:col-span-2"><span>Your Message *</span><textarea name="message" rows={6} required className="input h-auto py-3" /></label>
      </div>
      <button type="submit" className="btn-primary mt-6 h-12 w-full text-base"><Icon name="send" className="size-4" /> Send Message</button>
    </form>
  )
}
