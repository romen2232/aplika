import { Header } from './Header';
import { InfoPanel } from './InfoPanel';
import { AuthPanel } from './AuthPanel';

export function LandingPage() {
  return (
    <div className="h-screen flex flex-col">
      <Header />
      <main className="flex flex-1">
        <InfoPanel />
        <AuthPanel />
      </main>
    </div>
  );
}
