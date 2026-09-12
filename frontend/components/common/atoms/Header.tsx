import { getBgClass, getTextClass, getBorderClass } from "@/lib/colors";
import type { ThemeColor } from "../types";

interface NavLink {
  label: string;
  href: string;
}

interface HeaderProps {
  logo: React.ReactNode | string;
  navLinks?: NavLink[];
  actions?: React.ReactNode;
  bgColor?: ThemeColor;
  textColor?: ThemeColor;
  borderColor?: ThemeColor;
  className?: string;
}

export function Header({
  logo,
  navLinks,
  actions,
  bgColor,
  textColor,
  borderColor,
  className = "",
}: HeaderProps) {
  const headerClasses = [
    "flex items-center justify-between px-4 py-3 border-b",
    getBgClass(bgColor),
    getTextClass(textColor),
    getBorderClass(borderColor),
    className,
  ]
    .filter(Boolean)
    .join(" ");

  return (
    <header className={headerClasses}>
      <div>{logo}</div>
      {navLinks && (
        <nav className="flex gap-4">
          {navLinks.map((link) => (
            <a key={link.href} href={link.href}>
              {link.label}
            </a>
          ))}
        </nav>
      )}
      {actions && <div>{actions}</div>}
    </header>
  );
}
