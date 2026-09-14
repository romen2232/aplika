import { render, screen } from '@testing-library/react';
import { describe, it, expect } from 'vitest';
import { Badge } from '@/components/common/atoms/Badge';

describe('Badge', () => {
  it('renders the text', () => {
    render(<Badge text="Active" />);
    expect(screen.getByText('Active')).toBeInTheDocument();
  });

  it('has pill shape by default', () => {
    render(<Badge text="Status" />);
    const badge = screen.getByText('Status');
    expect(badge.className).toContain('rounded-full');
  });

  it('applies bgColor when provided', () => {
    render(<Badge text="Active" bgColor="primary" />);
    const badge = screen.getByText('Active');
    expect(badge.className).toContain('bg-primary');
  });

  it('applies textColor when provided', () => {
    render(<Badge text="Active" textColor="secondary" />);
    const badge = screen.getByText('Active');
    expect(badge.className).toContain('text-secondary');
  });

  it('applies borderColor when provided', () => {
    render(<Badge text="Active" borderColor="tertiary" />);
    const badge = screen.getByText('Active');
    expect(badge.className).toContain('border-tertiary');
  });

  it('applies custom className when provided', () => {
    render(<Badge text="Active" className="custom-class" />);
    const badge = screen.getByText('Active');
    expect(badge.className).toContain('custom-class');
  });

  it('applies all color props together', () => {
    render(<Badge text="Status" bgColor="neutral" textColor="primary" borderColor="secondary" />);
    const badge = screen.getByText('Status');
    expect(badge.className).toContain('bg-neutral');
    expect(badge.className).toContain('text-primary');
    expect(badge.className).toContain('border-secondary');
  });

  it('has base styling classes', () => {
    render(<Badge text="Status" />);
    const badge = screen.getByText('Status');
    expect(badge.className).toContain('px-2');
    expect(badge.className).toContain('py-1');
    expect(badge.className).toContain('text-sm');
    expect(badge.className).toContain('font-medium');
    expect(badge.className).toContain('inline-block');
  });
});
