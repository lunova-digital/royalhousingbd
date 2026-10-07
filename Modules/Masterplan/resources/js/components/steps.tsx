import { CheckIcon } from 'lucide-react'

export const MASTERPLAN_STEPS = [
  { title: 'Layout', text: 'Upload the SVG' },
  { title: 'Info', text: 'Name and description' },
  { title: 'Builder', text: 'Fill in each plot' },
] as const

/** The three steps of adding a masterplan; `current` is 1-based. */
export function Steps({ current }: { current: 1 | 2 | 3 }) {
  return (
    <ol className="flex flex-wrap items-center gap-x-6 gap-y-2 text-sm">
      {MASTERPLAN_STEPS.map((step, i) => {
        const n = i + 1
        const state = n < current ? 'done' : n === current ? 'current' : 'todo'
        return (
          <li key={step.title} className="flex items-center gap-2">
            <span
              className={`inline-flex size-7 shrink-0 items-center justify-center rounded-full text-xs font-semibold ${
                state === 'done' ? 'bg-primary text-primary-foreground' : state === 'current' ? 'border-primary text-primary border-2' : 'bg-muted text-muted-foreground'
              }`}
              aria-current={state === 'current' ? 'step' : undefined}
            >
              {state === 'done' ? <CheckIcon className="size-4" /> : n}
            </span>
            <span className="leading-tight">
              <span className={`block font-medium ${state === 'todo' ? 'text-muted-foreground' : ''}`}>{step.title}</span>
              <span className="text-muted-foreground block text-xs">{step.text}</span>
            </span>
            {n < MASTERPLAN_STEPS.length && <span className="bg-border ml-4 hidden h-px w-8 sm:block" aria-hidden />}
          </li>
        )
      })}
    </ol>
  )
}
