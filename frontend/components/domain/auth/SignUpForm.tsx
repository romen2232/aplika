'use client';

import { useTranslation } from '@/i18n/DictionaryProvider';

export function SignUpForm() {
  const { t } = useTranslation();

  return <div>{t('auth.signUpPlaceholder')}</div>;
}
