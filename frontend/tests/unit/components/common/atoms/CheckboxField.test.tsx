import { render, screen, fireEvent } from "@testing-library/react";
import { describe, it, expect, vi } from "vitest";
import { CheckboxField } from "@/components/common/atoms/CheckboxField";

describe("CheckboxField", () => {
  it("renders the label", () => {
    render(<CheckboxField label="Accept terms" checked={false} onChange={() => {}} />);
    expect(screen.getByLabelText("Accept terms")).toBeInTheDocument();
  });

  it("renders a checkbox input", () => {
    render(<CheckboxField label="Accept terms" checked={false} onChange={() => {}} />);
    const checkbox = screen.getByLabelText("Accept terms");
    expect(checkbox).toHaveAttribute("type", "checkbox");
  });

  it("checkbox is checked when checked prop is true", () => {
    render(<CheckboxField label="Accept terms" checked={true} onChange={() => {}} />);
    const checkbox = screen.getByLabelText("Accept terms") as HTMLInputElement;
    expect(checkbox.checked).toBe(true);
  });

  it("checkbox is unchecked when checked prop is false", () => {
    render(<CheckboxField label="Accept terms" checked={false} onChange={() => {}} />);
    const checkbox = screen.getByLabelText("Accept terms") as HTMLInputElement;
    expect(checkbox.checked).toBe(false);
  });

  it("calls onChange when checkbox is clicked", () => {
    const handleChange = vi.fn();
    render(<CheckboxField label="Accept terms" checked={false} onChange={handleChange} />);
    const checkbox = screen.getByLabelText("Accept terms");
    
    fireEvent.click(checkbox);
    expect(handleChange).toHaveBeenCalledTimes(1);
  });

  it("clicking the label also triggers onChange (clickable area wraps both)", () => {
    const handleChange = vi.fn();
    render(<CheckboxField label="Accept terms" checked={false} onChange={handleChange} />);
    const label = screen.getByText("Accept terms");
    
    fireEvent.click(label);
    expect(handleChange).toHaveBeenCalledTimes(1);
  });

  it("applies bgColor to checkbox when provided", () => {
    render(<CheckboxField label="Accept terms" checked={false} onChange={() => {}} bgColor="primary" />);
    const checkbox = screen.getByLabelText("Accept terms");
    expect(checkbox.className).toContain("bg-primary");
  });

  it("applies textColor to label when provided", () => {
    render(<CheckboxField label="Accept terms" checked={false} onChange={() => {}} textColor="secondary" />);
    const label = screen.getByText("Accept terms");
    expect(label.className).toContain("text-secondary");
  });

  it("applies borderColor to checkbox when provided", () => {
    render(<CheckboxField label="Accept terms" checked={false} onChange={() => {}} borderColor="tertiary" />);
    const checkbox = screen.getByLabelText("Accept terms");
    expect(checkbox.className).toContain("border-tertiary");
  });

  it("applies custom className when provided", () => {
    render(<CheckboxField label="Accept terms" checked={false} onChange={() => {}} className="custom-class" />);
    const checkbox = screen.getByLabelText("Accept terms");
    expect(checkbox.className).toContain("custom-class");
  });

  it("applies all color props together", () => {
    render(
      <CheckboxField
        label="Accept terms"
        checked={false}
        onChange={() => {}}
        bgColor="neutral"
        textColor="primary"
        borderColor="secondary"
      />
    );
    const checkbox = screen.getByLabelText("Accept terms");
    const label = screen.getByText("Accept terms");
    expect(checkbox.className).toContain("bg-neutral");
    expect(checkbox.className).toContain("border-secondary");
    expect(label.className).toContain("text-primary");
  });
});
