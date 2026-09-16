import type { ThemeColor } from '../types';

interface InputFieldProps {
  label: string;
  type?: string;
  value: string;
  onChange: (e: React.ChangeEvent<HTMLInputElement>) => void;
  placeholder?: string;
  actionRight?: React.ReactNode;
  bgColor?: ThemeColor;
  textColor?: ThemeColor;
  borderColor?: ThemeColor;
  className?: string;
}

export function InputField({
  label,
  type = 'text',
  value,
  onChange,
  placeholder,
  actionRight,
  bgColor,
  textColor,
  borderColor,
  className = '',
}: InputFieldProps) {
  const inputId = `input-${label.toLowerCase().replace(/\s+/g, '-')}`;
  const inputClasses = `w-full px-4 py-2 rounded-md border bg-${bgColor} text-${textColor} border-${borderColor} ${className}`;

  return (
    <div className="flex flex-col gap-2">
      <div className="flex items-center justify-between">
        <label htmlFor={inputId} className="font-medium">
          {label}
        </label>
        {actionRight && <div>{actionRight}</div>}
      </div>
      <input
        id={inputId}
        type={type}
        value={value}
        onChange={onChange}
        placeholder={placeholder}
        className={inputClasses}
      />
    </div>
  );
}
