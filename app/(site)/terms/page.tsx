import { legalMetadata, LegalPageView } from '@/modules/home/pages/legal'

export const generateMetadata = () => legalMetadata('terms')

export default function TermsPage() {
  return <LegalPageView page="terms" />
}
