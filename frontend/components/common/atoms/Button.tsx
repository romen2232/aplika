import { colorToHex } from "@/lib/colors";
import type { ThemeColor } from "./types";

interface Gradient {
  from: ThemeColor;
  to: ThemeColor;
}

interface ButtonProps {
  text: string;
  onClick: () => void;
  color?: ThemeColor;
  gradient?: Gradient;
  borderColor?: ThemeColor;
  borderSize?: 0 | 2 | 4 | 8;
}

export function Button({ text, onClick, color, gradient, borderColor, borderSize }: ButtonProps) {
  const baseClasses = "rounded-md text-center justify-center px-4 py-2 font-medium cursor-pointer";

  const style: React.CSSProperties = gradient
    ? {
        backgroundImage: `linear-gradient(to right, ${colorToHex[gradient.from]}, ${colorToHex[gradient.to]})`,
      }
    : {};

  const colorClass = color ? `bg-${color}` : "";
  const borderClass = borderColor ? `border-${borderColor}` : "";
  const borderSizeClass = borderSize !== undefined ? `border-${borderSize}` : "";

  return (
    <button
      className={`${baseClasses} ${colorClass} ${borderClass} ${borderSizeClass}`}
      style={style}
      onClick={onClick}
    >
      {text}
    </button>
  );
}
