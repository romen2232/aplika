import { getTextClass } from '@/lib/colors';
import type { ThemeColor } from '../types';

interface LogoProps {
  jobColor: ThemeColor;
  logColor: ThemeColor;
}

export function Logo({ jobColor, logColor }: LogoProps) {
  return (
    <span className="text-2xl font-bold">
      <span className={getTextClass(jobColor)}>Job</span>
      <span className={getTextClass(logColor)}>log</span>
    </span>
  );
}
