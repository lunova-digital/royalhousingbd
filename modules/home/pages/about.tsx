import type { Metadata } from 'next'

import { paragraphs } from '@/lib/text'
import { Icon } from '@/modules/site/components/icon'
import { Multiline, PageHero, SectionHeading, splitHeading } from '@/modules/site/components/partials'
import { assetUrl, getSettings, pageMetadata, siteList, type IconName } from '@/modules/site/site'

interface Stat { value?: string; label?: string }
interface Member { name?: string; role?: string; photo?: string; leader?: boolean }
interface Partner { name?: string; role?: string; logo?: string }

export async function generateMetadata(): Promise<Metadata> {
  const s = await getSettings()
  return pageMetadata({ title: 'About Us', description: s.about_title, path: '/about' })
}

const STAT_ICONS: IconName[] = ['building', 'users', 'award', 'check']

/** /about: story, stats, team and partners, all from settings. */
export default async function AboutPage() {
  const [settings, stats, team, partners] = await Promise.all([getSettings(), siteList<Stat>('about_stats'), siteList<Member>('team_members'), siteList<Partner>('partners')])
  const title = splitHeading(settings.about_title ?? '')
  const leaders = team.filter((m) => m.leader)
  const members = team.filter((m) => !m.leader)

  return (
    <>
      <PageHero eyebrow="About Us" title={title.head} accent={title.accent} text="Delivering premium properties and creating thriving communities." />

      <section className="mx-auto grid max-w-7xl items-center gap-10 px-4 py-12 sm:px-6 lg:grid-cols-2 lg:py-20">
        <div>
          <h2 className="font-display text-4xl font-bold">Our Story</h2>
          <div className="mt-5 grid gap-4 leading-relaxed text-slate-400">
            {paragraphs(settings.about_story ?? '').map((p, i) => <p key={i}><Multiline text={p} /></p>)}
          </div>
        </div>
        {stats.length > 0 && (
          <div className="grid grid-cols-2 gap-4">
            {stats.map((stat, i) => (
              <div key={i} className="card p-6 text-center"><span className="text-brand"><Icon name={STAT_ICONS[i % 4]!} className="mx-auto size-7" /></span><p className="mt-3 text-3xl font-bold">{stat.value ?? ''}</p><p className="mt-1 text-sm text-slate-400">{stat.label ?? ''}</p></div>
            ))}
          </div>
        )}
      </section>

      {team.length > 0 && (
        <section id="team" className="section-glow border-y border-white/5">
          <div className="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:py-20">
            <SectionHeading title="Meet Our Leadership" text="Experienced professionals dedicated to making your property dreams a reality." />
            {leaders.length > 0 && (
              <div className="mx-auto mt-10 grid max-w-4xl gap-6 sm:grid-cols-3">
                {leaders.map((member, i) => (
                  <figure key={i} className="card relative overflow-hidden">
                    <span className="absolute top-3 right-3 z-10 inline-flex size-8 items-center justify-center rounded-full bg-brand text-white"><Icon name="award" className="size-4" /></span>
                    <div className="aspect-[4/5] bg-navy-700">{member.photo && <img src={assetUrl(member.photo)} alt={member.name ?? ''} loading="lazy" className="size-full object-cover" />}</div>
                    <figcaption className="absolute inset-x-0 bottom-0 bg-gradient-to-t from-navy-950 to-transparent p-5 text-center"><p className="font-display text-xl font-semibold">{member.name ?? ''}</p><span className="chip mt-2 border-brand/30 bg-brand/15 text-brand">{member.role ?? ''}</span></figcaption>
                  </figure>
                ))}
              </div>
            )}
            {members.length > 0 && (
              <>
                <h3 className="mt-14 flex items-center gap-4 font-display text-2xl font-semibold before:h-px before:flex-1 before:bg-white/10 after:h-px after:flex-1 after:bg-white/10">Our Team</h3>
                <div className="mx-auto mt-8 grid max-w-3xl gap-5 sm:grid-cols-3">
                  {members.map((member, i) => (
                    <figure key={i} className="card overflow-hidden text-center">
                      <div className="aspect-[4/5] bg-navy-700">{member.photo && <img src={assetUrl(member.photo)} alt={member.name ?? ''} loading="lazy" className="size-full object-cover" />}</div>
                      <figcaption className="p-4"><p className="font-display font-semibold">{member.name ?? ''}</p><p className="mt-1 text-xs text-brand">{member.role ?? ''}</p></figcaption>
                    </figure>
                  ))}
                </div>
              </>
            )}
          </div>
        </section>
      )}

      {partners.length > 0 && (
        <section className="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:py-20">
          <SectionHeading title="Our Partners" text="We collaborate with industry leaders to deliver excellence." />
          <div className="mx-auto mt-10 flex max-w-3xl flex-wrap justify-center gap-8">
            {partners.map((partner, i) => (
              <figure key={i} className="text-center">
                <div className="mx-auto flex size-24 items-center justify-center overflow-hidden rounded-2xl bg-white p-3">{partner.logo && <img src={assetUrl(partner.logo)} alt={partner.name ?? ''} className="size-full object-contain" />}</div>
                <figcaption className="mt-3 text-sm font-medium">{partner.name ?? ''}</figcaption>
                {partner.role && <span className="chip mt-1">{partner.role}</span>}
              </figure>
            ))}
          </div>
        </section>
      )}
    </>
  )
}
