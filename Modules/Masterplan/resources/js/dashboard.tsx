import { MapIcon } from 'lucide-react'
import { registerModule } from '@admin/js'

import { addToGroup } from '@/lib/menu'

import { MasterplanFormPage } from './pages/masterplan-form-page'
import { MasterplanListPage } from './pages/masterplan-list-page'
import { PlotBuilderPage } from './pages/plot-builder-page'

registerModule({
  id: 'masterplan',
  order: 3,
  menuFilter: (menu) => addToGroup(menu, 'Content', { label: 'Masterplans', icon: MapIcon, path: '/masterplans' }),
  routes: [
    { path: 'masterplans', element: <MasterplanListPage /> },
    { path: 'masterplans/new', element: <MasterplanFormPage /> },
    { path: 'masterplans/:id', element: <MasterplanFormPage /> },
    { path: 'masterplans/:id/plots', element: <PlotBuilderPage /> },
  ],
})
