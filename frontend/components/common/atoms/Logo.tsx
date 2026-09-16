import { getTextClass } from '@/lib/colors';
import type { ThemeColor } from '../types';

interface LogoProps {
  firstColor: ThemeColor;
  secondColor: ThemeColor;
}

export function Logo({ firstColor, secondColor }: LogoProps) {
  return (
    <span className="text-3xl font-bold">
      <span className={getTextClass(firstColor)}>Apli</span>
      <span className={getTextClass(secondColor)}>k</span>
      <span className={getTextClass(firstColor)}>a</span>
    </span>
  );
}
