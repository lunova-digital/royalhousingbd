import { CalendarCheckIcon, ClockIcon } from 'lucide-react'
import { registerModule } from '@admin/js'

import { addToGroup } from '@/lib/menu'

import { AvailabilityPage } from './pages/availability-page'
import { ConsultationListPage } from './pages/consultation-list-page'

registerModule({
  id: 'consultation',
  order: 3,
  menuFilter: (menu) =>
    [
      { label: 'Consultations', icon: CalendarCheckIcon, path: '/consultations' },
      { label: 'Availability', icon: ClockIcon, path: '/consultations/availability' },
    ].reduce((m, item) => addToGroup(m, 'Leads', item), menu),
  routes: [
    { path: 'consultations', element: <ConsultationListPage /> },
    { path: 'consultations/availability', element: <AvailabilityPage /> },
  ],
})
