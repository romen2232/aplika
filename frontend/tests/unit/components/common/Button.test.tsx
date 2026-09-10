import { render, screen, fireEvent } from "@testing-library/react";
import { describe, it, expect, vi } from "vitest";
import { Button } from "@/components/common/Button";

describe("Button", () => {
  it("renders with text", () => {
    render(<Button text="Click me" onClick={() => {}} />);
    expect(screen.getByText("Click me")).toBeInTheDocument();
  });

  it("calls onClick when clicked", () => {
    const handleClick = vi.fn();
    render(<Button text="Click me" onClick={handleClick} />);
    
    fireEvent.click(screen.getByText("Click me"));
    expect(handleClick).toHaveBeenCalledTimes(1);
  });

  it("applies single color to background", () => {
    render(<Button text="Click me" onClick={() => {}} color="primary" />);
    const button = screen.getByText("Click me");
    expect(button.className).toContain("bg-primary");
  });

  it("applies gradient when provided", () => {
    render(
      <Button 
        text="Click me" 
        onClick={() => {}} 
        gradient={{ from: "primary", to: "secondary" }} 
      />
    );
    const button = screen.getByText("Click me");
    expect(button.style.backgroundImage).toContain("linear-gradient");
  });

  it("has rounded borders", () => {
    render(<Button text="Click me" onClick={() => {}} />);
    const button = screen.getByText("Click me");
    expect(button.className).toContain("rounded-md");
  });

  it("has centered text", () => {
    render(<Button text="Click me" onClick={() => {}} />);
    const button = screen.getByText("Click me");
    expect(button.className).toContain("text-center");
    expect(button.className).toContain("justify-center");
  });

  it("applies border color when provided", () => {
    render(<Button text="Click me" onClick={() => {}} borderColor="secondary" />);
    const button = screen.getByText("Click me");
    expect(button.className).toContain("border-secondary");
  });

  it("applies border size when provided", () => {
    render(<Button text="Click me" onClick={() => {}} borderSize={2} />);
    const button = screen.getByText("Click me");
    expect(button.className).toContain("border-2");
  });
});
