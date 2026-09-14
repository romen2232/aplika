import { colorToHex } from '@/lib/colors';
import type { ThemeColor } from '../types';

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
  type?: 'button' | 'submit';
  className?: string;
}

export function Button({
  text,
  onClick,
  color,
  gradient,
  borderColor,
  borderSize,
  type = 'button',
  className = '',
}: ButtonProps) {
  const baseClasses = 'rounded-md text-center justify-center px-4 py-2 font-medium cursor-pointer';

  const style: React.CSSProperties = gradient
    ? {
        backgroundImage: `linear-gradient(to right, ${colorToHex[gradient.from]}, ${colorToHex[gradient.to]})`,
      }
    : {};

  const colorClass = color ? `bg-${color}` : '';
  const borderClass = borderColor ? `border-${borderColor}` : '';
  const borderSizeClass = borderSize !== undefined ? `border-${borderSize}` : '';

  return (
    <button
      type={type}
      className={`${baseClasses} ${colorClass} ${borderClass} ${borderSizeClass} ${className}`}
      style={style}
      onClick={onClick}
    >
      {text}
    </button>
  );
}
