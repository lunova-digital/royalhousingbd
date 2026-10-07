import { SettingsIcon } from 'lucide-react'
import { registerModule } from '@admin/js'

import { addToGroup } from '@/lib/menu'

import { SettingsPage } from './pages/settings-page'

registerModule({
  id: 'settings',
  order: 5,
  menuFilter: (menu) => addToGroup(menu, 'Site', { label: 'Settings', icon: SettingsIcon, path: '/settings' }),
  routes: [{ path: 'settings', element: <SettingsPage /> }],
})
