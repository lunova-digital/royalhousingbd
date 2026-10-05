'use client'

import { useState } from 'react'

import { useLightbox } from '@/modules/site/components/lightbox'
import { assetUrl } from '@/modules/site/shared'

import type { ListingImage } from '../types'

/** FR-LIST-003: the detail page gallery; thumbnails swap the main image, which opens the lightbox. */
export function ListingGallery({ images, title }: { images: ListingImage[]; title: string }) {
  const [index, setIndex] = useState(0)
  const open = useLightbox()
  const main = images[index] ?? images[0]

  return (
    <div>
      <div className="relative aspect-[16/11] overflow-hidden rounded-2xl border border-white/8 bg-navy-800">
        {main ? (
          <img src={assetUrl(main.path)} alt={title} className="size-full cursor-zoom-in object-cover" onClick={() => open(assetUrl(main.path))} />
        ) : (
          <div className="flex size-full items-center justify-center text-slate-500">No photos yet</div>
        )}
      </div>
      {images.length > 1 && (
        <div className="mt-3 grid grid-cols-4 gap-3">
          {images.slice(0, 8).map((img, i) => (
            <button key={img.id} type="button" onClick={() => setIndex(i)} className={`aspect-[4/3] overflow-hidden rounded-xl border-2 ${i === index ? 'border-brand' : 'border-transparent'}`} aria-label={`Photo ${i + 1}`}>
              <img src={assetUrl(img.thumb_path || img.path)} alt="" loading="lazy" className="size-full object-cover" />
            </button>
          ))}
        </div>
      )}
    </div>
  )
}
