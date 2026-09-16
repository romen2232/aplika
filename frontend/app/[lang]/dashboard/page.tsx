import { getDictionary } from '../dictionaries';
import { DashboardContent } from './DashboardContent';

export default async function DashboardPage({ params }: { params: Promise<{ lang: string }> }) {
  const { lang } = await params;
  const dict = await getDictionary(lang);

  return (
    <DashboardContent
      lang={lang}
      labels={dict.auth.dashboard}
      errorLabels={{ sessionExpired: dict.auth.errors.sessionExpired }}
    />
  );
}
