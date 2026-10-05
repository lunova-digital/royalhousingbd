'use client'

import { useState } from 'react'

/** A click-to-load YouTube embed: thumbnail first, iframe on demand (NFR-002). */
export function YouTubeEmbed({ id }: { id: string }) {
  const [playing, setPlaying] = useState(false)

  if (playing) {
    return (
      <iframe
        src={`https://www.youtube-nocookie.com/embed/${encodeURIComponent(id)}?autoplay=1&rel=0&playsinline=1`}
        title="YouTube video"
        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
        allowFullScreen
        className="aspect-video w-full rounded-2xl"
      />
    )
  }

  return (
    <button type="button" onClick={() => setPlaying(true)} className="group relative block aspect-video w-full overflow-hidden rounded-2xl border border-white/8 bg-navy-950" aria-label="Play video">
      <img src={`https://i.ytimg.com/vi/${id}/hqdefault.jpg`} alt="" loading="lazy" className="size-full object-cover opacity-80 transition-opacity group-hover:opacity-100" />
      <span className="absolute inset-0 flex items-center justify-center">
        <span className="inline-flex size-16 items-center justify-center rounded-full bg-brand text-white shadow-lg">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" className="ml-1 size-8" fill="currentColor"><path d="M8 5v14l11-7z" /></svg>
        </span>
      </span>
    </button>
  )
}
