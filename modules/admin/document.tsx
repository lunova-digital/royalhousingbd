import type { ReactNode } from 'react'

/** The <html> for the admin and the login page: the neutral shadcn tokens, not the public theme. */
export default function AdminDocument({ children }: { children: ReactNode }) {
  return (
    <html lang="en">
      <body className="bg-background text-foreground antialiased">{children}</body>
    </html>
  )
}
