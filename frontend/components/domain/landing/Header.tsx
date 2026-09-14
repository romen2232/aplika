import { Logo } from "@/components/common/atoms/Logo";
import { getDictionary } from "@/app/[lang]/dictionaries";
import Link from "next/link";

export async function Header() {
  const dict = await getDictionary();

  const navLinks = [
    { label: dict.header.nav.features, href: "#features" },
    { label: dict.header.nav.howItWorks, href: "#how-it-works" },
    { label: dict.header.nav.pricing, href: "#pricing" },
    { label: dict.header.nav.support, href: "#support" },
  ];

  return (
    <header className="hidden min-[1400px]:flex items-center justify-between h-[10vh] px-15 bg-neutral text-tertiary/80">
      <div className="flex items-center gap-25">
        <Logo jobColor="tertiary" logColor="primary" />
        <span className="text-sm font-normal text-tertiary/80">
          {dict.header.slogan}
        </span>
      </div>

      <nav className="flex gap-4">
        {navLinks.map((link) => (
          <a key={link.href} href={link.href} className="hover:text-primary">
            {link.label}
          </a>
        ))}
      </nav>

      <div className="flex items-center gap-4">
        <Link href="/login" className="text-lg text-tertiary hover:text-primary">
          {dict.header.login}
        </Link>
        <Link
          href="/signup"
          className="bg-primary text-neutral px-4 py-2 rounded hover:bg-primary/90 font-semibold"
        >
          {dict.header.signup}
        </Link>
      </div>
    </header>
  );
}
