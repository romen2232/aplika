import { getBgClass, getTextClass, getBorderClass } from '@/lib/colors';
import type { ThemeColor } from '../types';

interface BadgeProps {
  text: string;
  bgColor?: ThemeColor;
  textColor?: ThemeColor;
  borderColor?: ThemeColor;
  className?: string;
}

export function Badge({ text, bgColor, textColor, borderColor, className = '' }: BadgeProps) {
  const badgeClasses = [
    'px-2 py-1 rounded-full text-sm font-medium inline-block border',
    getBgClass(bgColor),
    getTextClass(textColor),
    getBorderClass(borderColor),
    className,
  ]
    .filter(Boolean)
    .join(' ');

  return <span className={badgeClasses}>{text}</span>;
}
