import { Header } from './Header';
import { InfoPanel } from './InfoPanel';
import { AuthPanel } from '@/components/domain/auth/AuthPanel';

export function LandingPage() {
  return (
    <div className="w-full min-h-screen flex flex-col">
      <Header />
      <main className="flex-1 min-h-0 grid grid-cols-1 min-[1100px]:grid-cols-[7fr_3fr] items-center">
        <InfoPanel />
        <AuthPanel />
      </main>
    </div>
  );
}
