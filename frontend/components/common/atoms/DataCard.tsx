import { getBgClass, getTextClass, getBorderClass } from "@/lib/colors";
import type { ThemeColor } from "../types";

interface DataCardProps {
  children: React.ReactNode;
  bgColor?: ThemeColor;
  textColor?: ThemeColor;
  borderColor?: ThemeColor;
  className?: string;
}

export function DataCard({
  children,
  bgColor,
  textColor,
  borderColor,
  className = "",
}: DataCardProps) {
  const cardClasses = [
    "rounded-lg p-4 border",
    bgColor ? getBgClass(bgColor) : "bg-neutral",
    getTextClass(textColor),
    borderColor ? getBorderClass(borderColor) : "border-tertiary",
    className,
  ]
    .filter(Boolean)
    .join(" ");

  return <div className={cardClasses}>{children}</div>;
}
