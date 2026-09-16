'use client';

import { Badge } from '@/components/common/atoms/Badge';
import { DataCard } from '@/components/common/atoms/DataCard';
import { useTranslation } from '@/i18n/DictionaryProvider';
import type { ThemeColor } from '@/components/common/types';

interface JobRow {
  avatar: string;
  title: string;
  company: string;
  status: string;
  badge: string;
  badgeColor: ThemeColor;
}

const jobs: JobRow[] = [
  {
    avatar: 'S',
    title: 'dashboard.jobs.row1.title',
    company: 'dashboard.jobs.row1.company',
    status: 'dashboard.jobs.row1.status',
    badge: 'dashboard.jobs.row1.badge',
    badgeColor: 'secondary',
  },
  {
    avatar: 'A',
    title: 'dashboard.jobs.row2.title',
    company: 'dashboard.jobs.row2.company',
    status: 'dashboard.jobs.row2.status',
    badge: 'dashboard.jobs.row2.badge',
    badgeColor: 'primary',
  },
  {
    avatar: 'N',
    title: 'dashboard.jobs.row3.title',
    company: 'dashboard.jobs.row3.company',
    status: 'dashboard.jobs.row3.status',
    badge: 'dashboard.jobs.row3.badge',
    badgeColor: 'success',
  },
];

const stats = [
  { value: '24', label: 'dashboard.stats.applications', color: 'text-primary' },
  { value: '6', label: 'dashboard.stats.interviews', color: 'text-secondary' },
  { value: '2', label: 'dashboard.stats.offers', color: 'text-success' },
  { value: '25%', label: 'dashboard.stats.response', color: 'text-gray-500' },
];

interface Step {
  index: string;
  label: string;
  title: string;
  desc: string;
  accentClass?: string;
  borderColor?: ThemeColor;
}

const steps: Step[] = [
  { index: '01', label: 'steps.card1.label', title: 'steps.card1.title', desc: 'steps.card1.desc' },
  { index: '02', label: 'steps.card2.label', title: 'steps.card2.title', desc: 'steps.card2.desc' },
  {
    index: '03',
    label: 'steps.card3.label',
    title: 'steps.card3.title',
    desc: 'steps.card3.desc',
    accentClass: 'text-primary',
  },
  {
    index: '04',
    label: 'steps.card4.label',
    title: 'steps.card4.title',
    desc: 'steps.card4.desc',
    accentClass: 'text-secondary',
    borderColor: 'secondary',
  },
];

export function InfoPanel() {
  const { t } = useTranslation();

  // TODO: change the infopannel and infopannel data as the app develops
  return (
    <section className="h-full flex flex-col justify-between bg-neutral/99 p-12">
      <div className="mx-auto flex w-full max-w-4xl flex-col">
        <h1 className="text-5xl font-bold text-white">
          {t('hero.titleMain')} <span className="text-primary">{t('hero.titleHighlight')}</span>{' '}
          {t('hero.titleSuffix')}
        </h1>

        <p className="mt-4 text-lg text-gray-400">{t('hero.subtitle')}</p>

        <DataCard className="mt-12 border-gray-800! p-6" bgColor="neutral">
          <div className="flex items-center justify-between border-b border-gray-800 pb-4">
            <div className="flex flex-col gap-1">
              <p className="text-lg text-white">{t('dashboard.pipeline.title')}</p>
              <p className="text-sm text-gray-500">{t('dashboard.pipeline.subtitle')}</p>
            </div>

            <div className="flex items-center gap-6">
              {stats.map((stat) => (
                <div key={stat.label} className="flex flex-col items-end">
                  <span className={`text-lg font-semibold ${stat.color}`}>{stat.value}</span>
                  <span className="text-xs text-gray-500">{t(stat.label)}</span>
                </div>
              ))}
            </div>
          </div>

          <div className="flex flex-col gap-4 pt-4">
            {jobs.map((job) => (
              <div
                key={job.avatar}
                className="flex items-center justify-between rounded-md p-2 hover:bg-white/5"
              >
                <div className="flex items-center gap-3">
                  <div className="flex h-10 w-10 items-center justify-center rounded-md bg-white/10 font-semibold text-white">
                    {job.avatar}
                  </div>
                  <div className="flex flex-col">
                    <p className="text-white">{t(job.title)}</p>
                    <p className="text-sm text-gray-500">{t(job.company)}</p>
                  </div>
                </div>

                <div className="flex items-center gap-4">
                  <span className="text-sm text-gray-500">{t(job.status)}</span>
                  <Badge text={t(job.badge)} textColor={job.badgeColor} />
                </div>
              </div>
            ))}
          </div>
        </DataCard>
      </div>

      <div className="mx-auto mt-auto w-full max-w-4xl pt-8">
        <div className="mb-4 flex justify-between text-sm text-gray-500">
          <span>{t('steps.header.left')}</span>
          <span className="text-primary">{t('steps.header.right')}</span>
        </div>

        <div className="grid grid-cols-4 gap-4">
          {steps.map((step) => (
            <DataCard
              key={step.index}
              borderColor={step.borderColor}
              className="flex flex-col gap-1 p-4"
            >
              <span className={`text-xs ${step.accentClass ?? 'text-gray-500'}`}>
                {step.index} • {t(step.label)}
              </span>
              <p className={`text-sm font-semibold ${step.accentClass ?? 'text-white'}`}>
                {t(step.title)}
              </p>
              <p className="text-xs text-gray-500">{t(step.desc)}</p>
            </DataCard>
          ))}
        </div>

        <div className="mt-4 flex justify-between text-xs text-gray-500">
          <span>{t('footer.leftText')}</span>
          <span>
            {t('footer.privacy')} • {t('footer.encrypted')} • {t('footer.help')}
          </span>
        </div>
      </div>
    </section>
  );
}
