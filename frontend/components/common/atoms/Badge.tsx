import type { ThemeColor } from '../types';

interface BadgeProps {
  text: string;
  bgColor?: ThemeColor;
  textColor?: ThemeColor;
  borderColor?: ThemeColor;
  className?: string;
}

export function Badge({ text, bgColor, textColor, borderColor, className = '' }: BadgeProps) {
  const badgeClasses = `bg-${bgColor} text-${textColor} border-${borderColor} ${className} px-2 py-1 rounded-full text-sm font-medium inline-block border`; 
  return <span className={badgeClasses}>{text}</span>;
}
