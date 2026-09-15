import type { Metadata } from 'next';
import { locales, defaultLocale } from '@/i18n/config';
import '../globals.css';

export async function generateStaticParams() {
  return locales.map((lang) => ({ lang }));
}

export const metadata: Metadata = {
  title: 'Aplika',
  description: 'A personal job-search management platform.',
};

export default async function RootLayout({ children, params }: LayoutProps<'/[lang]'>) {
  const { lang } = await params;
  const htmlLang = locales.includes(lang as (typeof locales)[number]) ? lang : defaultLocale;

  return (
    <html lang={htmlLang}>
      <body>{children}</body>
    </html>
  );
}
