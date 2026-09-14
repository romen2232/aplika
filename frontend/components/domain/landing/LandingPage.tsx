import { Header } from './Header';
import { InfoPanel } from './InfoPanel';
import { AuthPanel } from '@/components/domain/auth/AuthPanel';

export function LandingPage() {
  return (
    <div className="h-screen w-full flex flex-col overflow-hidden">
      <Header />
      <main className="flex-1 min-h-0 grid grid-cols-[7fr_3fr] items-center">
        <InfoPanel />
        <AuthPanel />
      </main>
    </div>
  );
}
