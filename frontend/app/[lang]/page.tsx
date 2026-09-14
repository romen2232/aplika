import { getDictionary } from './dictionaries';

export default async function Home() {
  const dict = await getDictionary();

  return (
    <main>
      <h1>{dict.home.title}</h1>
      <p>{dict.home.subtitle}</p>
    </main>
  );
}
