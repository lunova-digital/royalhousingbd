'use client'

import { usePathname } from 'next/navigation'
import type { ReactNode } from 'react'

import { Breadcrumb, BreadcrumbItem, BreadcrumbList, BreadcrumbPage, BreadcrumbSeparator } from '@/components/ui/breadcrumb'
import { Separator } from '@/components/ui/separator'
import { SidebarInset, SidebarProvider, SidebarTrigger } from '@/components/ui/sidebar'
import { TooltipProvider } from '@/components/ui/tooltip'
import type { Viewer } from '@/modules/auth/session'

import { findMenuLocation } from '../registry'
import { AppSidebar } from './app-sidebar'

function PageBreadcrumb({ appName }: { appName: string }) {
  const location = findMenuLocation(usePathname())
  return (
    <Breadcrumb>
      <BreadcrumbList>
        {location && (
          <>
            <BreadcrumbItem className="hidden md:block">{location.group.label}</BreadcrumbItem>
            <BreadcrumbSeparator className="hidden md:block" />
          </>
        )}
        <BreadcrumbItem>
          <BreadcrumbPage>{location?.item.label ?? appName}</BreadcrumbPage>
        </BreadcrumbItem>
      </BreadcrumbList>
    </Breadcrumb>
  )
}

/** The admin frame: sidebar, header with breadcrumb, and the page area that scrolls. */
export function AdminShell({ appName, viewer, children }: { appName: string; viewer: Viewer; children: ReactNode }) {
  return (
    <TooltipProvider>
      {/* Exactly one viewport tall: pages scroll inside the content area, never the window. */}
      <SidebarProvider className="h-svh overflow-hidden">
        <AppSidebar appName={appName} viewer={viewer} />
        <SidebarInset className="min-w-0 overflow-hidden">
          <header className="flex h-16 shrink-0 items-center gap-2 border-b px-4 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12">
            <SidebarTrigger className="-ml-1" />
            <Separator orientation="vertical" className="mr-2 self-center! data-[orientation=vertical]:h-4" />
            <PageBreadcrumb appName={appName} />
          </header>
          <div className="flex min-h-0 flex-1 flex-col gap-4 overflow-auto p-4 md:p-6">{children}</div>
        </SidebarInset>
      </SidebarProvider>
    </TooltipProvider>
  )
}
