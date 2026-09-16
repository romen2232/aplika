'use client';

import { useEffect, useState } from 'react';
import Link from 'next/link';
import { useRouter, useSearchParams } from 'next/navigation';
import { LoginForm, type LoginFormLabels } from '@/src/components/auth/LoginForm';
import { toDisplayMessage } from '@/src/api/client';
import { useAuth } from '@/src/contexts/AuthContext';
import type { ValidationMessages } from '@/lib/validation/auth';

export interface LoginLabels extends LoginFormLabels {
  title: string;
  noAccount: string;
  registerLink: string;
}

export interface LoginErrorLabels {
  network: string;
  generic: string;
  sessionExpired: string;
}

interface LoginPageContentProps {
  lang: string;
  labels: LoginLabels;
  errorLabels: LoginErrorLabels;
  validationLabels: ValidationMessages;
}

export function LoginPageContent({
  lang,
  labels,
  errorLabels,
  validationLabels,
}: LoginPageContentProps) {
  const router = useRouter();
  const searchParams = useSearchParams();
  const { user, isLoading: isAuthLoading, login } = useAuth();
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const returnUrl = searchParams.get('returnUrl') ?? `/${lang}/dashboard`;
  const sessionExpired = searchParams.get('expired') === '1';

  useEffect(() => {
    if (!isAuthLoading && user) {
      router.replace(returnUrl);
    }
  }, [isAuthLoading, user, router, returnUrl]);

  async function handleSubmit(email: string, password: string) {
    setIsSubmitting(true);
    setError(null);

    try {
      await login(email, password);
      router.replace(returnUrl);
    } catch (caught) {
      setError(
        toDisplayMessage(caught, { network: errorLabels.network, generic: errorLabels.generic }),
      );
    } finally {
      setIsSubmitting(false);
    }
  }

  return (
    <main>
      <h1>{labels.title}</h1>

      {sessionExpired && !error ? <p role="status">{errorLabels.sessionExpired}</p> : null}

      <LoginForm
        labels={labels}
        validationLabels={validationLabels}
        onSubmit={handleSubmit}
        isLoading={isSubmitting}
        error={error}
      />

      <p>
        {labels.noAccount} <Link href={`/${lang}/register`}>{labels.registerLink}</Link>
      </p>
    </main>
  );
}
