import type { ThemeColor } from '@/components/common/types';

export const colorToHex: Record<ThemeColor, string> = {
  primary: '#F0A500',
  secondary: '#E45826',
  tertiary: '#E6D5B8',
  neutral: '#1B1A17',
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
