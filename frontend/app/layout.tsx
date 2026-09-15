import type { Metadata } from 'next';
import './globals.css';

export const metadata: Metadata = {
  title: 'Aplika',
  description: 'A personal job-search management platform.',
};

export default function RootLayout({ children }: LayoutProps<'/'>) {
  return (
    <html lang="en">
      <body>{children}</body>
    </html>
  );
}
