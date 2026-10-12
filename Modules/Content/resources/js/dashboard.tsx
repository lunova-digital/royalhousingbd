import { HomeIcon, LanguagesIcon } from 'lucide-react'
import { registerModule } from '@admin/js'

import { addToGroup } from '@/lib/menu'

import { HomepagePage } from './pages/homepage-page'
import { TextsPage } from './pages/texts-page'

registerModule({
  id: 'content',
  order: 4,
  menuFilter: (menu) =>
    [
      { label: 'Homepage', icon: HomeIcon, path: '/homepage' },
      { label: 'Site text', icon: LanguagesIcon, path: '/texts' },
    ].reduce((m, item) => addToGroup(m, 'Site', item), menu),
  routes: [
    { path: 'homepage', element: <HomepagePage /> },
    { path: 'texts', element: <TextsPage /> },
  ],
})
