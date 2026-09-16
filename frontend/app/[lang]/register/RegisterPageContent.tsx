'use client';

import { useEffect, useState } from 'react';
import Link from 'next/link';
import { useRouter } from 'next/navigation';
import { RegisterForm, type RegisterFormLabels } from '@/src/components/auth/RegisterForm';
import { toDisplayMessage } from '@/src/api/client';
import { useAuth } from '@/src/contexts/AuthContext';
import type { ValidationMessages } from '@/lib/validation/auth';

export interface RegisterLabels extends RegisterFormLabels {
  title: string;
  haveAccount: string;
  loginLink: string;
}

export interface RegisterErrorLabels {
  network: string;
  generic: string;
}

interface RegisterPageContentProps {
  lang: string;
  labels: RegisterLabels;
  errorLabels: RegisterErrorLabels;
  validationLabels: ValidationMessages;
}

export function RegisterPageContent({
  lang,
  labels,
  errorLabels,
  validationLabels,
}: RegisterPageContentProps) {
  const router = useRouter();
  const { user, isLoading: isAuthLoading, register } = useAuth();
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!isAuthLoading && user) {
      router.replace(`/${lang}/dashboard`);
    }
  }, [isAuthLoading, user, router, lang]);

  async function handleSubmit(email: string, password: string) {
    setIsSubmitting(true);
    setError(null);

    try {
      await register(email, password);
      router.replace(`/${lang}/dashboard`);
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

      <RegisterForm
        labels={labels}
        validationLabels={validationLabels}
        onSubmit={handleSubmit}
        isLoading={isSubmitting}
        error={error}
      />

      <p>
        {labels.haveAccount} <Link href={`/${lang}/login`}>{labels.loginLink}</Link>
      </p>
    </main>
  );
}
