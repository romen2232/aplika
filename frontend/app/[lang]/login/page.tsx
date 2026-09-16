import { Suspense } from 'react';
import { getDictionary } from '../dictionaries';
import { LoginPageContent } from './LoginPageContent';

export default async function LoginPage({ params }: { params: Promise<{ lang: string }> }) {
  const { lang } = await params;
  const dict = await getDictionary();

  return (
    <Suspense fallback={<main />}>
      <LoginPageContent
        lang={lang}
        labels={dict.auth.login}
        errorLabels={dict.auth.errors}
        validationLabels={dict.auth.validation}
      />
    </Suspense>
  );
}
