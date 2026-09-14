import { Header } from "@/components/common/atoms/Header";
import { InfoPanel } from "./InfoPanel";
import { AuthPanel } from "./AuthPanel";

export function LandingPage() {
  return (
    <div className="min-h-screen flex flex-col">
      <Header
        logo="Joblog"
        bgColor="neutral"
        textColor="primary"
        borderColor="primary"
      />
      <main className="flex flex-1">
        <InfoPanel />
        <AuthPanel />
      </main>
    </div>
  );
}
