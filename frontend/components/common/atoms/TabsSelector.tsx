import { getBgClass, getTextClass, getBorderClass } from '@/lib/colors';
import type { ThemeColor } from '../types';

interface TabOption {
  label: string;
  value: string;
}

interface TabsSelectorProps {
  options: TabOption[];
  currentValue: string;
  onChange: (value: string) => void;
  activeBgColor?: ThemeColor;
  inactiveBgColor?: ThemeColor;
  activeTextColor?: ThemeColor;
  inactiveTextColor?: ThemeColor;
  borderColor?: ThemeColor;
  className?: string;
}

export function TabsSelector({
  options,
  currentValue,
  onChange,
  activeBgColor,
  inactiveBgColor,
  activeTextColor,
  inactiveTextColor,
  borderColor,
  className = '',
}: TabsSelectorProps) {
  return (
    <div className="flex gap-2">
      {options.map((option) => {
        const isActive = option.value === currentValue;
        const tabClasses = [
          'px-4 py-2 rounded-md cursor-pointer border',
          isActive ? getBgClass(activeBgColor) : getBgClass(inactiveBgColor),
          isActive ? getTextClass(activeTextColor) : getTextClass(inactiveTextColor),
          getBorderClass(borderColor),
          className,
        ]
          .filter(Boolean)
          .join(' ');

        return (
          <button
            key={option.value}
            type="button"
            className={tabClasses}
            onClick={() => onChange(option.value)}
          >
            {option.label}
          </button>
        );
      })}
    </div>
  );
}
