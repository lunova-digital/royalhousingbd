import { SettingsIcon } from 'lucide-react'

import type { AdminModule } from '@/modules/admin/types'

export const settingsAdmin: AdminModule = {
  id: 'settings',
  order: 5,
  menu: [{ group: 'Site', label: 'Settings', icon: SettingsIcon, path: '/admin/settings' }],
}
