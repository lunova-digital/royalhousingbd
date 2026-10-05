'use client'

import Link from 'next/link'
import { usePathname } from 'next/navigation'

import { Icon } from './icon'
import { NAV, navKeyFor } from '../shared'

/** The desktop pill navigation. */
export function MainNav() {
  const active = navKeyFor(usePathname())
  return (
    <nav className="hidden items-center gap-1 lg:flex" aria-label="Main">
      {NAV.map((item) => (
        <Link key={item.key} href={item.href} className={`pill-nav ${active === item.key ? 'pill-nav-active' : ''}`}>
          {item.label}
        </Link>
      ))}
    </nav>
  )
}

/** Bottom tab bar on phones and tablets. */
export function BottomNav() {
  const active = navKeyFor(usePathname())
  return (
    <nav className="fixed inset-x-0 bottom-0 z-50 border-t border-navy-500 bg-navy-900/95 backdrop-blur-xl lg:hidden" aria-label="Quick navigation" style={{ paddingBottom: 'env(safe-area-inset-bottom, 0px)' }}>
      <div className="flex h-16 items-center justify-around px-2">
        {NAV.filter((i) => i.key !== 'gallery').map((item) => (
          <Link key={item.key} href={item.href} className={`flex h-full flex-1 flex-col items-center justify-center rounded-lg px-1 py-1 transition-all duration-300 active:scale-95 ${active === item.key ? 'text-brand' : 'text-muted-fg'}`}>
            <span className={`flex size-10 items-center justify-center rounded-xl ${active === item.key ? 'bg-brand/10' : ''}`}><Icon name={item.icon} className="size-5" /></span>
            <span className="mt-0.5 max-w-[60px] truncate text-center text-[10px] font-medium">{item.label}</span>
          </Link>
        ))}
      </div>
    </nav>
  )
}
