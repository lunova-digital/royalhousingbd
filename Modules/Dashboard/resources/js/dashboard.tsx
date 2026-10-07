import { DashboardWidgets, registerModule } from '@admin/js'

import { Widgets } from './widgets'

// The admin owns the dashboard page; this module only fills it.
registerModule({
  id: 'stats',
  order: 1,
  fills: (
    <DashboardWidgets.Fill>
      <Widgets />
    </DashboardWidgets.Fill>
  ),
})
