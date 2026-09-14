import { render, screen } from '@testing-library/react';
import { describe, it, expect } from 'vitest';
import { DataCard } from '@/components/common/atoms/DataCard';

describe('DataCard', () => {
  it('renders children', () => {
    render(<DataCard>Card content</DataCard>);
    expect(screen.getByText('Card content')).toBeInTheDocument();
  });

  it('has default dark background', () => {
    render(<DataCard>Content</DataCard>);
    const card = screen.getByText('Content');
    expect(card.className).toContain('bg-neutral');
  });

  it('has default subtle border', () => {
    render(<DataCard>Content</DataCard>);
    const card = screen.getByText('Content');
    expect(card.className).toContain('border');
  });

  it('applies bgColor when provided (overrides default)', () => {
    render(<DataCard bgColor="primary">Content</DataCard>);
    const card = screen.getByText('Content');
    expect(card.className).toContain('bg-primary');
    expect(card.className).not.toContain('bg-neutral');
  });

  it('applies textColor when provided', () => {
    render(<DataCard textColor="tertiary">Content</DataCard>);
    const card = screen.getByText('Content');
    expect(card.className).toContain('text-tertiary');
  });

  it('applies borderColor when provided (overrides default)', () => {
    render(<DataCard borderColor="secondary">Content</DataCard>);
    const card = screen.getByText('Content');
    expect(card.className).toContain('border-secondary');
  });

  it('applies custom className when provided', () => {
    render(<DataCard className="custom-class">Content</DataCard>);
    const card = screen.getByText('Content');
    expect(card.className).toContain('custom-class');
  });

  it('has base styling classes', () => {
    render(<DataCard>Content</DataCard>);
    const card = screen.getByText('Content');
    expect(card.className).toContain('rounded-lg');
    expect(card.className).toContain('p-4');
  });

  it('applies all color props together', () => {
    render(
      <DataCard bgColor="primary" textColor="neutral" borderColor="secondary">
        Content
      </DataCard>,
    );
    const card = screen.getByText('Content');
    expect(card.className).toContain('bg-primary');
    expect(card.className).toContain('text-neutral');
    expect(card.className).toContain('border-secondary');
  });

  it('renders complex children', () => {
    render(
      <DataCard>
        <h2>Title</h2>
        <p>Description</p>
      </DataCard>,
    );
    expect(screen.getByText('Title')).toBeInTheDocument();
    expect(screen.getByText('Description')).toBeInTheDocument();
  });
});
