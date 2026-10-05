import { indexMetadata, ListingIndexPage } from '@/modules/listing/pages/index'

type Props = { params: Promise<{ type: string }>; searchParams: Promise<Record<string, string | string[] | undefined>> }

/** /property, /plot, /flat (anything else is a 404). */
export async function generateMetadata({ params, searchParams }: Props) {
  return indexMetadata({ type: (await params).type, searchParams: await searchParams })
}

export default async function ListingTypePage({ params, searchParams }: Props) {
  return <ListingIndexPage type={(await params).type} searchParams={await searchParams} />
}
