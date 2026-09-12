import { render, screen, fireEvent } from "@testing-library/react";
import { describe, it, expect, vi } from "vitest";
import { TabsSelector } from "@/components/common/atoms/TabsSelector";

describe("TabsSelector", () => {
  const options = [
    { label: "Tab 1", value: "tab1" },
    { label: "Tab 2", value: "tab2" },
    { label: "Tab 3", value: "tab3" },
  ];

  it("renders all options", () => {
    render(<TabsSelector options={options} currentValue="tab1" onChange={() => {}} />);
    expect(screen.getByText("Tab 1")).toBeInTheDocument();
    expect(screen.getByText("Tab 2")).toBeInTheDocument();
    expect(screen.getByText("Tab 3")).toBeInTheDocument();
  });

  it("calls onChange when a tab is clicked", () => {
    const handleChange = vi.fn();
    render(<TabsSelector options={options} currentValue="tab1" onChange={handleChange} />);
    
    fireEvent.click(screen.getByText("Tab 2"));
    expect(handleChange).toHaveBeenCalledWith("tab2");
  });

  it("applies active styling to current tab", () => {
    render(
      <TabsSelector
        options={options}
        currentValue="tab2"
        onChange={() => {}}
        activeBgColor="primary"
      />
    );
    const activeTab = screen.getByText("Tab 2");
    expect(activeTab.className).toContain("bg-primary");
  });

  it("applies inactive styling to non-active tabs", () => {
    render(
      <TabsSelector
        options={options}
        currentValue="tab2"
        onChange={() => {}}
        inactiveBgColor="neutral"
      />
    );
    const inactiveTab = screen.getByText("Tab 1");
    expect(inactiveTab.className).toContain("bg-neutral");
  });

  it("applies textColor to active tab", () => {
    render(
      <TabsSelector
        options={options}
        currentValue="tab1"
        onChange={() => {}}
        activeTextColor="secondary"
      />
    );
    const activeTab = screen.getByText("Tab 1");
    expect(activeTab.className).toContain("text-secondary");
  });

  it("applies textColor to inactive tabs", () => {
    render(
      <TabsSelector
        options={options}
        currentValue="tab1"
        onChange={() => {}}
        inactiveTextColor="tertiary"
      />
    );
    const inactiveTab = screen.getByText("Tab 2");
    expect(inactiveTab.className).toContain("text-tertiary");
  });

  it("applies borderColor when provided", () => {
    render(
      <TabsSelector
        options={options}
        currentValue="tab1"
        onChange={() => {}}
        borderColor="primary"
      />
    );
    const tab = screen.getByText("Tab 1");
    expect(tab.className).toContain("border-primary");
  });

  it("applies custom className when provided", () => {
    render(
      <TabsSelector
        options={options}
        currentValue="tab1"
        onChange={() => {}}
        className="custom-class"
      />
    );
    const tab = screen.getByText("Tab 1");
    expect(tab.className).toContain("custom-class");
  });

  it("applies all color props together", () => {
    render(
      <TabsSelector
        options={options}
        currentValue="tab2"
        onChange={() => {}}
        activeBgColor="primary"
        activeTextColor="neutral"
        inactiveBgColor="neutral"
        inactiveTextColor="tertiary"
        borderColor="secondary"
      />
    );
    const activeTab = screen.getByText("Tab 2");
    expect(activeTab.className).toContain("bg-primary");
    expect(activeTab.className).toContain("text-neutral");
    expect(activeTab.className).toContain("border-secondary");

    const inactiveTab = screen.getByText("Tab 1");
    expect(inactiveTab.className).toContain("bg-neutral");
    expect(inactiveTab.className).toContain("text-tertiary");
  });

  it("does not apply active styles to inactive tabs", () => {
    render(
      <TabsSelector
        options={options}
        currentValue="tab1"
        onChange={() => {}}
        activeBgColor="primary"
        inactiveBgColor="neutral"
      />
    );
    const inactiveTab = screen.getByText("Tab 2");
    expect(inactiveTab.className).not.toContain("bg-primary");
    expect(inactiveTab.className).toContain("bg-neutral");
  });
});
