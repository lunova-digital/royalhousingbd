import { PLOT_STATUSES, type MasterplanWithPlots, type PlotStatus } from './types'

export function masterplanUrl(m: { slug: string }): string {
  return `/map/${m.slug}`
}

/** Quoted in the WhatsApp message (FR-MAP-008), e.g. "MAP-2/PLOT-A14". */
export function plotReference(plot: { masterplan_id: number; number: string }): string {
  return `MAP-${plot.masterplan_id}/PLOT-${plot.number.toUpperCase()}`
}

/** Plots per status, for the legend. */
export function statusCounts(m: Pick<MasterplanWithPlots, 'plots'>): Record<PlotStatus, number> {
  const counts = Object.fromEntries(PLOT_STATUSES.map((s) => [s, 0])) as Record<PlotStatus, number>
  for (const plot of m.plots) counts[plot.status] = (counts[plot.status] ?? 0) + 1
  return counts
}
