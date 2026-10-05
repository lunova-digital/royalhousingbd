import Link from 'next/link'

export default function AdminNotFound() {
  return (
    <div className="flex flex-1 flex-col items-center justify-center gap-2 text-center">
      <h1 className="text-lg font-semibold">Page not found</h1>
      <p className="text-sm text-muted-foreground">No module of the admin has a page at this address.</p>
      <Link href="/admin" className="text-sm underline underline-offset-4">Back to the start</Link>
    </div>
  )
}
