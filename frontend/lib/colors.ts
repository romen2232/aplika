import type { ThemeColor } from '@/components/common/types';

export const colorToHex: Record<ThemeColor, string> = {
  primary: "#f0a500",
  secondary: "#e45826",
  tertiary: "#e6d5b8",
  neutral: "#1b1a17",
  success: "#22c55e",
};

export function getBgClass(color?: ThemeColor): string {
  return color ? `bg-${color}` : '';
}

export function getTextClass(color?: ThemeColor): string {
  return color ? `text-${color}` : '';
}

export function getBorderClass(color?: ThemeColor): string {
  return color ? `border-${color}` : '';
}
