export interface Consultation {
  id: number
  reference: string
  name: string
  phone: string
  email: string | null
  whatsapp_url: string | null
  mode: 'phone' | 'whatsapp' | 'office' | 'video'
  topic: string | null
  message: string | null
  listing: { id: number; title: string; url: string } | null
  scheduled_at: string
  date: string
  time: string
  is_past: boolean
  duration_minutes: number
  status: 'pending' | 'confirmed' | 'completed' | 'cancelled'
  locale: string
  source_url: string | null
  admin_notes: string | null
  created_at: string
}

export type Counts = { pending: number; upcoming: number }

export const MODE_LABELS: Record<Consultation['mode'], string> = {
  phone: 'Phone call',
  whatsapp: 'WhatsApp call',
  office: 'At the office',
  video: 'Video call',
}
