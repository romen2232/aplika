import { Suspense } from 'react';
import { getDictionary } from '../dictionaries';
import { RegisterPageContent } from './RegisterPageContent';

export default async function RegisterPage({ params }: { params: Promise<{ lang: string }> }) {
  const { lang } = await params;
  const dict = await getDictionary();

  return (
    <Suspense fallback={<main />}>
      <RegisterPageContent
        lang={lang}
        labels={dict.auth.register}
        errorLabels={dict.auth.errors}
        validationLabels={dict.auth.validation}
      />
    </Suspense>
  );
}
