import { redirect } from 'next/navigation'

import { homePath } from '@/modules/admin/registry'

/** No module owns /admin: it lands on the first page the menu offers. */
export default function AdminIndex() {
  redirect(homePath())
}
