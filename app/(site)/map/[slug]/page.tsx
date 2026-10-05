import { MasterplanShowPage, showMetadata } from '@/modules/masterplan/pages/show'

type Props = { params: Promise<{ slug: string }> }

export async function generateMetadata({ params }: Props) {
  return showMetadata(await params)
}

export default async function MapDetailPage({ params }: Props) {
  return <MasterplanShowPage {...await params} />
}
