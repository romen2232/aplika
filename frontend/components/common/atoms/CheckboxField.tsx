import { getBgClass, getTextClass, getBorderClass } from "@/lib/colors";
import type { ThemeColor } from "../types";

interface CheckboxFieldProps {
  label: string;
  checked: boolean;
  onChange: (e: React.ChangeEvent<HTMLInputElement>) => void;
  bgColor?: ThemeColor;
  textColor?: ThemeColor;
  borderColor?: ThemeColor;
  className?: string;
}

export function CheckboxField({
  label,
  checked,
  onChange,
  bgColor,
  textColor,
  borderColor,
  className = "",
}: CheckboxFieldProps) {
  const inputId = `checkbox-${label.toLowerCase().replace(/\s+/g, "-")}`;
  const checkboxClasses = [
    "w-4 h-4 rounded border cursor-pointer",
    getBgClass(bgColor),
    getBorderClass(borderColor),
    className,
  ]
    .filter(Boolean)
    .join(" ");

  const labelClasses = ["cursor-pointer", getTextClass(textColor)]
    .filter(Boolean)
    .join(" ");

  return (
    <label htmlFor={inputId} className="flex items-center gap-2 cursor-pointer">
      <input
        id={inputId}
        type="checkbox"
        checked={checked}
        onChange={onChange}
        className={checkboxClasses}
      />
      <span className={labelClasses}>{label}</span>
    </label>
  );
}
