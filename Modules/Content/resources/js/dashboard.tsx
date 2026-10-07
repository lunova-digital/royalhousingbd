import { HomeIcon } from 'lucide-react'
import { registerModule } from '@admin/js'

import { addToGroup } from '@/lib/menu'

import { HomepagePage } from './pages/homepage-page'

registerModule({
  id: 'content',
  order: 4,
  menuFilter: (menu) => addToGroup(menu, 'Site', { label: 'Homepage', icon: HomeIcon, path: '/homepage' }),
  routes: [{ path: 'homepage', element: <HomepagePage /> }],
})
