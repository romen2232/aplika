import { getDictionary } from './dictionaries';

export default async function Home({ params }: { params: Promise<{ lang: string }> }) {
  const { lang } = await params;
  const dict = await getDictionary(lang);

  return (
    <main>
      <h1>{dict.home.title}</h1>
      <p>{dict.home.subtitle}</p>
    </main>
  );
}
