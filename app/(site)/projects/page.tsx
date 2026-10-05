import { indexMetadata, ListingIndexPage } from '@/modules/listing/pages/index'

type Props = { searchParams: Promise<Record<string, string | string[] | undefined>> }

/** /projects: every type; ?type= narrows like the per-type pages. */
export async function generateMetadata({ searchParams }: Props) {
  return indexMetadata({ type: null, searchParams: await searchParams })
}

export default async function ProjectsPage({ searchParams }: Props) {
  return <ListingIndexPage type={null} searchParams={await searchParams} />
}
