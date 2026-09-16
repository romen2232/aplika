import type { ThemeColor } from '../types';

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
  className = '',
}: DataCardProps) {
  const cardClasses = `rounded-lg p-4 border bg-${bgColor ?? 'neutral'} text-${textColor} border-${borderColor ?? 'tertiary'} ${className}`;

  return <div className={cardClasses}>{children}</div>;
}
