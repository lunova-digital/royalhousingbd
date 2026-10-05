import { ListingShowPage, showMetadata } from '@/modules/listing/pages/show'

type Props = { params: Promise<{ type: string; slug: string }> }

export async function generateMetadata({ params }: Props) {
  return showMetadata(await params)
}

export default async function ListingDetailPage({ params }: Props) {
  return <ListingShowPage {...await params} />
}
