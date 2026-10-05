'use client'

import { createContext, useCallback, useContext, useEffect, useState, type ComponentProps, type ReactNode } from 'react'

import { Icon } from './icon'

const LightboxContext = createContext<(src: string) => void>(() => undefined)

/** Wraps a page: anything rendered with <LightboxTrigger src> opens full size. */
export function LightboxProvider({ children }: { children: ReactNode }) {
  const [src, setSrc] = useState<string | null>(null)
  const close = useCallback(() => setSrc(null), [])

  useEffect(() => {
    if (!src) return
    document.body.style.overflow = 'hidden'
    const onKey = (event: KeyboardEvent) => event.key === 'Escape' && close()
    document.addEventListener('keydown', onKey)
    return () => {
      document.body.style.overflow = ''
      document.removeEventListener('keydown', onKey)
    }
  }, [src, close])

  return (
    <LightboxContext.Provider value={setSrc}>
      {children}
      {src && (
        <div className="fixed inset-0 z-[60] flex items-center justify-center bg-black/90 p-4" onClick={close}>
          <button type="button" aria-label="Close" className="absolute top-4 right-4 inline-flex size-11 items-center justify-center rounded-full bg-white/10 text-white hover:bg-white/20" onClick={close}>
            <Icon name="x" className="size-5" />
          </button>
          <img src={src} alt="" className="max-h-[90svh] max-w-full rounded-lg object-contain" onClick={(e) => e.stopPropagation()} />
        </div>
      )}
    </LightboxContext.Provider>
  )
}

export function useLightbox() {
  return useContext(LightboxContext)
}

/** A button that opens `src` in the lightbox. */
export function LightboxTrigger({ src, children, ...props }: ComponentProps<'button'> & { src: string }) {
  const open = useLightbox()
  return (
    <button type="button" {...props} onClick={() => open(src)}>
      {children}
    </button>
  )
}
