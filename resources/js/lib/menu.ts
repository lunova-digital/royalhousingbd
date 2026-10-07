import type { MenuGroup, MenuItem } from '@admin/js'

// Adds an item to a sidebar group, creating the group when no module has yet.
export function addToGroup(menu: MenuGroup[], label: string, item: MenuItem): MenuGroup[] {
  if (!menu.some((group) => group.label === label)) {
    return [...menu, { label, items: [item] }]
  }
  return menu.map((group) => (group.label === label ? { ...group, items: [...group.items, item] } : group))
}
