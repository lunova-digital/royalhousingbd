import { describe, expect, it } from 'vitest'

import { whatsappLink } from './whatsapp'
import { youtubeId } from './youtube'

describe('youtubeId', () => {
  it('reads every usual url shape', () => {
    for (const url of [
      'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
      'https://youtu.be/dQw4w9WgXcQ?t=10',
      'https://youtube.com/shorts/dQw4w9WgXcQ',
      'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ',
      'https://m.youtube.com/live/dQw4w9WgXcQ',
      'dQw4w9WgXcQ',
    ]) {
      expect(youtubeId(url)).toBe('dQw4w9WgXcQ')
    }
  })

  it('rejects anything else', () => {
    expect(youtubeId('not a link')).toBeNull()
    expect(youtubeId('https://vimeo.com/123456')).toBeNull()
    expect(youtubeId('')).toBeNull()
    expect(youtubeId(null)).toBeNull()
  })
})

describe('whatsappLink', () => {
  it('keeps digits only and percent-encodes the text', () => {
    expect(whatsappLink('+880 1700-000000', 'Hello there')).toBe('https://wa.me/8801700000000?text=Hello%20there')
    expect(whatsappLink('', '')).toBe('https://wa.me/')
  })
})
