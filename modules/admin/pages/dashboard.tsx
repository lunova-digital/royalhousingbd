import { adminModules } from '../registry'

/** The landing page: every module's widgets, in module order. */
export default function DashboardPage() {
  return (
    <>
      <h1 className="text-2xl font-semibold tracking-tight">Dashboard</h1>
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        {adminModules.map((mod) => (mod.widgets ? <mod.widgets key={mod.id} /> : null))}
      </div>
    </>
  )
}
