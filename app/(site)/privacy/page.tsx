import { legalMetadata, LegalPageView } from '@/modules/home/pages/legal'

export const generateMetadata = () => legalMetadata('privacy')

export default function PrivacyPage() {
  return <LegalPageView page="privacy" />
}
