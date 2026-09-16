import type { ThemeColor } from '../types';

interface LogoProps {
  firstColor: ThemeColor;
  secondColor: ThemeColor;
}

export function Logo({ firstColor, secondColor }: LogoProps) {
  return (
    <span className="text-3xl font-bold">
      <span className={`text-${firstColor}`}>Apli</span>
      <span className={`text-${secondColor}`}>k</span>
      <span className={`text-${firstColor}`}>a</span>
    </span>
  );
}
