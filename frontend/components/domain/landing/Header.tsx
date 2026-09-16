'use client';

import { Logo } from '@/components/common/atoms/Logo';
import { useTranslation } from '@/i18n/DictionaryProvider';

export function Header() {
  const { t } = useTranslation();

  const linkClasses = 'text-sm text-gray-300 hover:text-white';

  return (
    <header className="bg-neutral p-3">
      {/* Mobile/Tablet layout - below 1100px */}
      <div className="flex min-[1100px]:hidden justify-between items-center w-full px-10">
        <Logo firstColor="tertiary" secondColor="primary" />
        <button className="text-sm bg-primary text-neutral px-4 py-2 rounded hover:bg-primary/90 font-semibold">
          {t('nav.signIn')}
        </button>
      </div>

      {/* Desktop layout - 1100px and above */}
      <div className="hidden min-[1100px]:flex items-center gap-12 w-full">
        <div className="flex-1 flex justify-end gap-8">
          <a href="#features" className={linkClasses}>
            {t('nav.features')}
          </a>
          <a href="#how-it-works" className={linkClasses}>
            {t('nav.howItWorks')}
          </a>
        </div>

        <div className="flex flex-col items-center justify-center text-center">
          <Logo firstColor="tertiary" secondColor="primary" />
          <span className="mt-1 text-xs text-gray-500">{t('nav.findAJob')}</span>
        </div>

        <div className="flex-1 flex justify-start gap-8">
          <a href="#pricing" className={linkClasses}>
            {t('nav.pricing')}
          </a>
          <a href="#support" className={linkClasses}>
            {t('nav.support')}
          </a>
        </div>
      </div>
    </header>
  );
}
