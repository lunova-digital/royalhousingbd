import { WrenchIcon } from 'lucide-react'
import { registerModule } from '@admin/js'

import { addToGroup } from '@/lib/menu'

import { ServicesPage } from './pages/services-page'

registerModule({
  id: 'service',
  order: 5,
  menuFilter: (menu) => addToGroup(menu, 'Site', { label: 'Other services', icon: WrenchIcon, path: '/services' }),
  routes: [{ path: 'services', element: <ServicesPage /> }],
})
