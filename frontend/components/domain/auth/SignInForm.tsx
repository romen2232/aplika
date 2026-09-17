'use client';

import { useMemo, useState, type FormEvent } from 'react';
import { useRouter } from 'next/navigation';
import Link from 'next/link';
import { Button } from '@/components/common/atoms/Button';
import { InputField } from '@/components/common/atoms/InputField';
import { CheckboxField } from '@/components/common/atoms/CheckboxField';
import { useTranslation } from '@/i18n/DictionaryProvider';
import { useAuth } from '@/src/contexts/AuthContext';
import { toDisplayMessage } from '@/src/api/client';
import { createLoginSchema, type ValidationMessages } from '@/lib/validation/auth';

export function SignInForm() {
  const { t } = useTranslation();
  const router = useRouter();
  const { login } = useAuth();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [rememberMe, setRememberMe] = useState(true);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [validationError, setValidationError] = useState<string | null>(null);

  const validationLabels: ValidationMessages = useMemo(
    () => ({
      fullNameRequired: t('auth.validation.fullNameRequired'),
      emailRequired: t('auth.validation.emailRequired'),
      emailInvalid: t('auth.validation.emailInvalid'),
      passwordRequired: t('auth.validation.passwordRequired'),
      passwordMin: t('auth.validation.passwordMin'),
    }),
    [t],
  );

  const schema = useMemo(() => createLoginSchema(validationLabels), [validationLabels]);

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();

    if (isSubmitting) {
      return;
    }

    const result = schema.safeParse({ email, password });
    if (!result.success) {
      setValidationError(result.error.issues[0]?.message ?? null);
      return;
    }

    setValidationError(null);
    setIsSubmitting(true);
    setError(null);

    try {
      await login(email, password);
      router.replace('/en/dashboard');
    } catch (caught) {
      setError(
        toDisplayMessage(caught, {
          network: t('auth.errors.network'),
          generic: t('auth.errors.generic'),
        }),
      );
    } finally {
      setIsSubmitting(false);
    }
  }

  const displayError = validationError ?? error;

  return (
    <form onSubmit={handleSubmit} noValidate>
      <div className="flex flex-col gap-5">
        <header className="flex flex-col gap-1">
          <h2 className="text-2xl font-bold text-white">{t('auth.welcomeBack')}</h2>
          <p className="text-sm text-gray-400">{t('auth.enterCredentials')}</p>
        </header>

        <div className="flex flex-col gap-3">
          <Button
            text={t('auth.google')}
            onClick={() => {}}
            color="neutral"
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
            actionRight={<span className="text-primary text-sm">{t('auth.forgot')}</span>}
            bgColor="neutral"
            textColor="tertiary"
            borderColor="tertiary"
          />
        </div>

        <CheckboxField
          label={t('auth.rememberMe')}
          checked={rememberMe}
          onChange={(e) => setRememberMe(e.target.checked)}
          textColor="tertiary"
          borderColor="tertiary"
        />

        {displayError ? (
          <p role="alert" data-testid="signin-error" className="text-sm text-red-400">
            {displayError}
          </p>
        ) : null}

        <Button
          text={isSubmitting ? t('auth.login.submitting') : t('auth.submitSignIn')}
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
    </form>
  );
}
