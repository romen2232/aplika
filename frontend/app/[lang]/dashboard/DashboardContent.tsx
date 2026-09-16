'use client';

import { useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { useAuth } from '@/src/contexts/AuthContext';

export interface DashboardLabels {
  title: string;
  welcome: string;
  logout: string;
  loading: string;
}

export interface DashboardErrorLabels {
  sessionExpired: string;
}

interface DashboardContentProps {
  lang: string;
  labels: DashboardLabels;
  errorLabels: DashboardErrorLabels;
}

export function DashboardContent({ lang, labels }: DashboardContentProps) {
  const router = useRouter();
  const { user, isLoading, logout } = useAuth();

  useEffect(() => {
    if (!isLoading && !user) {
      const returnUrl = encodeURIComponent(`/${lang}/dashboard`);
      router.replace(`/${lang}/login?expired=1&returnUrl=${returnUrl}`);
    }
  }, [isLoading, user, router, lang]);

  async function handleLogout() {
    await logout();
    router.replace(`/${lang}/login`);
  }

  if (isLoading) {
    return (
      <main>
        <p role="status">{labels.loading}</p>
      </main>
    );
  }

  if (!user) {
    return null;
  }

  return (
    <main>
      <h1>{labels.title}</h1>
      <p>
        {labels.welcome} <strong>{user.email}</strong>
      </p>
      <button type="button" onClick={handleLogout}>
        {labels.logout}
      </button>
    </main>
  );
}
