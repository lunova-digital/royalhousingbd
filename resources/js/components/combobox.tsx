import { Combobox as ComboboxPrimitive } from '@base-ui/react/combobox'
import { cn } from 'cn'
import { CheckIcon, ChevronsUpDownIcon } from 'lucide-react'

export interface ComboboxOption {
  value: number | string
  label: string
}

// A type-to-search single select: filters the options as you type, picks with the keyboard or mouse.
export function Combobox({
  id,
  options,
  value,
  onChange,
  placeholder,
  emptyText = 'No matches.',
  disabled,
  className,
}: {
  id?: string
  options: ComboboxOption[]
  value: number | string | null
  onChange: (value: ComboboxOption['value'] | null) => void
  placeholder?: string
  emptyText?: string
  disabled?: boolean
  className?: string
}) {
  const selected = options.find((o) => o.value === value) ?? null

  return (
    <ComboboxPrimitive.Root<ComboboxOption>
      items={options}
      value={selected}
      onValueChange={(option) => onChange(option?.value ?? null)}
      itemToStringLabel={(o) => o.label}
      itemToStringValue={(o) => String(o.value)}
      disabled={disabled}
    >
      <div className={cn('relative', className)}>
        <ComboboxPrimitive.Input
          id={id}
          placeholder={placeholder}
          className="h-8 w-full min-w-0 rounded-lg border border-input bg-transparent py-1 pr-8 pl-2.5 text-base transition-colors outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50 disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm dark:bg-input/30"
        />
        <ComboboxPrimitive.Trigger
          aria-label="Open"
          className="text-muted-foreground absolute inset-y-0 right-0 flex w-8 items-center justify-center rounded-r-lg outline-none hover:text-foreground"
        >
          <ComboboxPrimitive.Icon>
            <ChevronsUpDownIcon className="size-4" />
          </ComboboxPrimitive.Icon>
        </ComboboxPrimitive.Trigger>
      </div>
      <ComboboxPrimitive.Portal>
        <ComboboxPrimitive.Positioner className="isolate z-50 outline-none" sideOffset={4}>
          <ComboboxPrimitive.Popup className="max-h-(--available-height) w-(--anchor-width) min-w-32 origin-(--transform-origin) overflow-y-auto rounded-lg bg-popover p-1 text-popover-foreground shadow-md ring-1 ring-foreground/10 outline-none data-open:animate-in data-open:fade-in-0 data-open:zoom-in-95 data-closed:animate-out data-closed:fade-out-0 data-closed:zoom-out-95">
            <ComboboxPrimitive.Empty className="text-muted-foreground px-2 py-1.5 text-sm empty:hidden">{emptyText}</ComboboxPrimitive.Empty>
            <ComboboxPrimitive.List>
              {(option: ComboboxOption) => (
                <ComboboxPrimitive.Item
                  key={option.value}
                  value={option}
                  className="relative flex cursor-default items-center gap-2 rounded-md py-1.5 pr-2 pl-7 text-sm outline-none select-none data-highlighted:bg-accent data-highlighted:text-accent-foreground data-selected:font-medium"
                >
                  <ComboboxPrimitive.ItemIndicator className="absolute left-2 flex items-center">
                    <CheckIcon className="size-4" />
                  </ComboboxPrimitive.ItemIndicator>
                  {option.label}
                </ComboboxPrimitive.Item>
              )}
            </ComboboxPrimitive.List>
          </ComboboxPrimitive.Popup>
        </ComboboxPrimitive.Positioner>
      </ComboboxPrimitive.Portal>
    </ComboboxPrimitive.Root>
  )
}
