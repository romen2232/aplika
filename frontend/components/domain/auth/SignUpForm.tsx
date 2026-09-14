'use client';

import { useState } from 'react';
import Link from 'next/link';
import { Button } from '@/components/common/atoms/Button';
import { InputField } from '@/components/common/atoms/InputField';
import { CheckboxField } from '@/components/common/atoms/CheckboxField';
import { useTranslation } from '@/i18n/DictionaryProvider';

export function SignUpForm() {
  const { t } = useTranslation();
  const [fullName, setFullName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [acceptTerms, setAcceptTerms] = useState(true);

  return (
    <div className="flex flex-col gap-5">
      <header className="flex flex-col gap-1">
        <h2 className="text-2xl font-bold text-white">{t('auth.createAccount')}</h2>
        <p className="text-sm text-gray-400">{t('auth.signUpSubtitle')}</p>
      </header>

      <div className="flex flex-col gap-3">
        <Button
          text={t('auth.google')}
          onClick={() => {}}
          color="neutral"
          borderColor="tertiary"
          borderSize={2}
          className="w-full text-tertiary"
        />
        <Button
          text={t('auth.linkedin')}
          onClick={() => {}}
          color="neutral"
          borderColor="tertiary"
          borderSize={2}
          className="w-full text-tertiary"
        />
      </div>

      <div className="flex items-center gap-3">
        <span className="h-px flex-1 bg-tertiary/30" />
        <span className="text-xs text-gray-400">{t('auth.orWithEmail')}</span>
        <span className="h-px flex-1 bg-tertiary/30" />
      </div>

      <div className="flex flex-col gap-4">
        <InputField
          label={t('auth.fullName')}
          value={fullName}
          onChange={(e) => setFullName(e.target.value)}
          placeholder="Sarah Connor"
          bgColor="neutral"
          textColor="tertiary"
          borderColor="tertiary"
        />
        <InputField
          label={t('auth.email')}
          type="email"
          value={email}
          onChange={(e) => setEmail(e.target.value)}
          placeholder="sarah@example.com"
          bgColor="neutral"
          textColor="tertiary"
          borderColor="tertiary"
        />
        <InputField
          label={t('auth.password')}
          type="password"
          value={password}
          onChange={(e) => setPassword(e.target.value)}
          bgColor="neutral"
          textColor="tertiary"
          borderColor="tertiary"
        />
      </div>

      <CheckboxField
        label={t('auth.acceptTerms')}
        checked={acceptTerms}
        onChange={(e) => setAcceptTerms(e.target.checked)}
        textColor="tertiary"
        borderColor="tertiary"
      />

      <Button
        text={t('auth.submitSignUp')}
        onClick={() => {}}
        gradient={{ from: 'primary', to: 'secondary' }}
        type="submit"
        className="w-full text-neutral"
      />

      <p className="text-xs text-gray-400">
        {t('auth.termsAgreement')}{' '}
        <Link href="/terms" className="text-primary hover:underline">
          {t('auth.termsOfService')}
        </Link>{' '}
        ·{' '}
        <Link href="/privacy" className="text-primary hover:underline">
          {t('auth.privacyPolicy')}
        </Link>
      </p>
    </div>
  );
}
