import { render, screen, fireEvent } from '@testing-library/react';
import { describe, it, expect, vi } from 'vitest';
import { InputField } from '@/components/common/atoms/InputField';

describe('InputField', () => {
  it('renders with a label', () => {
    render(<InputField label="Email" value="" onChange={() => {}} />);
    expect(screen.getByLabelText('Email')).toBeInTheDocument();
  });

  it('renders an input with default type text', () => {
    render(<InputField label="Username" value="" onChange={() => {}} />);
    const input = screen.getByLabelText('Username');
    expect(input).toHaveAttribute('type', 'text');
  });

  it('renders an input with custom type', () => {
    render(<InputField label="Password" type="password" value="" onChange={() => {}} />);
    const input = screen.getByLabelText('Password');
    expect(input).toHaveAttribute('type', 'password');
  });

  it('displays the provided value', () => {
    render(<InputField label="Email" value="test@example.com" onChange={() => {}} />);
    const input = screen.getByLabelText('Email') as HTMLInputElement;
    expect(input.value).toBe('test@example.com');
  });

  it('calls onChange when input value changes', () => {
    const handleChange = vi.fn();
    render(<InputField label="Email" value="" onChange={handleChange} />);
    const input = screen.getByLabelText('Email');

    fireEvent.change(input, { target: { value: 'new@example.com' } });
    expect(handleChange).toHaveBeenCalledTimes(1);
  });

  it('renders actionRight when provided', () => {
    render(
      <InputField
        label="Password"
        type="password"
        value=""
        onChange={() => {}}
        actionRight={<button>Forgot?</button>}
      />,
    );
    expect(screen.getByText('Forgot?')).toBeInTheDocument();
  });

  it('applies bgColor class when provided', () => {
    render(<InputField label="Email" value="" onChange={() => {}} bgColor="primary" />);
    const input = screen.getByLabelText('Email');
    expect(input.className).toContain('bg-primary');
  });

  it('applies textColor class when provided', () => {
    render(<InputField label="Email" value="" onChange={() => {}} textColor="secondary" />);
    const input = screen.getByLabelText('Email');
    expect(input.className).toContain('text-secondary');
  });

  it('applies borderColor class when provided', () => {
    render(<InputField label="Email" value="" onChange={() => {}} borderColor="tertiary" />);
    const input = screen.getByLabelText('Email');
    expect(input.className).toContain('border-tertiary');
  });

  it('applies custom className when provided', () => {
    render(<InputField label="Email" value="" onChange={() => {}} className="custom-class" />);
    const input = screen.getByLabelText('Email');
    expect(input.className).toContain('custom-class');
  });

  it('applies all color props together', () => {
    render(
      <InputField
        label="Email"
        value=""
        onChange={() => {}}
        bgColor="neutral"
        textColor="primary"
        borderColor="secondary"
      />,
    );
    const input = screen.getByLabelText('Email');
    expect(input.className).toContain('bg-neutral');
    expect(input.className).toContain('text-primary');
    expect(input.className).toContain('border-secondary');
  });
});
