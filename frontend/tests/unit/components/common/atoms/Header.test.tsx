import { render, screen } from "@testing-library/react";
import { describe, it, expect } from "vitest";
import { Header } from "@/components/common/atoms/Header";

describe("Header", () => {
  const navLinks = [
    { label: "Home", href: "/" },
    { label: "About", href: "/about" },
    { label: "Contact", href: "/contact" },
  ];

  it("renders logo as string", () => {
    render(<Header logo="JobLog" navLinks={navLinks} />);
    expect(screen.getByText("JobLog")).toBeInTheDocument();
  });

  it("renders logo as node", () => {
    render(<Header logo={<img src="/logo.png" alt="Logo" />} navLinks={navLinks} />);
    expect(screen.getByAltText("Logo")).toBeInTheDocument();
  });

  it("renders navLinks", () => {
    render(<Header logo="JobLog" navLinks={navLinks} />);
    expect(screen.getByText("Home")).toBeInTheDocument();
    expect(screen.getByText("About")).toBeInTheDocument();
    expect(screen.getByText("Contact")).toBeInTheDocument();
  });

  it("each navLink has correct href", () => {
    render(<Header logo="JobLog" navLinks={navLinks} />);
    const homeLink = screen.getByText("Home");
    const aboutLink = screen.getByText("About");
    const contactLink = screen.getByText("Contact");
    
    expect(homeLink.closest("a")).toHaveAttribute("href", "/");
    expect(aboutLink.closest("a")).toHaveAttribute("href", "/about");
    expect(contactLink.closest("a")).toHaveAttribute("href", "/contact");
  });

  it("renders actions node when provided", () => {
    render(
      <Header
        logo="JobLog"
        navLinks={navLinks}
        actions={<button>Login</button>}
      />
    );
    expect(screen.getByText("Login")).toBeInTheDocument();
  });

  it("applies bgColor when provided", () => {
    render(<Header logo="JobLog" navLinks={navLinks} bgColor="neutral" />);
    const header = screen.getByRole("banner");
    expect(header.className).toContain("bg-neutral");
  });

  it("applies textColor when provided", () => {
    render(<Header logo="JobLog" navLinks={navLinks} textColor="primary" />);
    const header = screen.getByRole("banner");
    expect(header.className).toContain("text-primary");
  });

  it("applies borderColor when provided", () => {
    render(<Header logo="JobLog" navLinks={navLinks} borderColor="secondary" />);
    const header = screen.getByRole("banner");
    expect(header.className).toContain("border-secondary");
  });

  it("applies custom className when provided", () => {
    render(<Header logo="JobLog" navLinks={navLinks} className="custom-class" />);
    const header = screen.getByRole("banner");
    expect(header.className).toContain("custom-class");
  });

  it("applies all color props together", () => {
    render(
      <Header
        logo="JobLog"
        navLinks={navLinks}
        bgColor="neutral"
        textColor="primary"
        borderColor="secondary"
      />
    );
    const header = screen.getByRole("banner");
    expect(header.className).toContain("bg-neutral");
    expect(header.className).toContain("text-primary");
    expect(header.className).toContain("border-secondary");
  });

  it("has base styling classes", () => {
    render(<Header logo="JobLog" navLinks={navLinks} />);
    const header = screen.getByRole("banner");
    expect(header.className).toContain("flex");
    expect(header.className).toContain("items-center");
    expect(header.className).toContain("justify-between");
    expect(header.className).toContain("px-4");
    expect(header.className).toContain("py-3");
  });

  it("renders without navLinks", () => {
    render(<Header logo="JobLog" />);
    expect(screen.getByText("JobLog")).toBeInTheDocument();
  });

  it("renders without actions", () => {
    render(<Header logo="JobLog" navLinks={navLinks} />);
    expect(screen.getByText("JobLog")).toBeInTheDocument();
  });
});
