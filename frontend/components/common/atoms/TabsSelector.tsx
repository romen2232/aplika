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
  activeClassName?: string;
  inactiveClassName?: string;
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
  activeClassName = '',
  inactiveClassName = '',
  className = '',
}: TabsSelectorProps) {
  return (
    <div className="flex gap-2">
      {options.map((option) => {
        const isActive = option.value === currentValue;
        const tabClasses = `px-4 py-2 rounded cursor-pointer border bg-${isActive ? activeBgColor : inactiveBgColor} text-${isActive ? activeTextColor : inactiveTextColor} border-${borderColor} ${isActive ? activeClassName : inactiveClassName} ${className}`;

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
