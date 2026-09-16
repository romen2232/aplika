import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';
import { LoginForm } from '@/src/components/auth/LoginForm';
import type { ValidationMessages } from '@/lib/validation/auth';

const labels = {
  email: 'Email',
  password: 'Password',
  submit: 'Sign in',
  submitting: 'Signing in...',
};

const validationLabels: ValidationMessages = {
  emailRequired: 'Email is required',
  emailInvalid: 'Invalid email format',
  passwordRequired: 'Password is required',
  passwordMin: 'Password must be at least 8 characters',
};

describe('LoginForm', () => {
  it('renders the email and password fields', () => {
    render(<LoginForm labels={labels} validationLabels={validationLabels} onSubmit={vi.fn()} />);

    expect(screen.getByLabelText('Email')).toBeInTheDocument();
    expect(screen.getByLabelText('Password')).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Sign in' })).toBeInTheDocument();
  });

  it('submits the entered credentials', async () => {
    const onSubmit = vi.fn();
    const user = userEvent.setup();
    render(<LoginForm labels={labels} validationLabels={validationLabels} onSubmit={onSubmit} />);

    await user.type(screen.getByLabelText('Email'), 'user@aplika.test');
    await user.type(screen.getByLabelText('Password'), 'password123');
    await user.click(screen.getByRole('button', { name: 'Sign in' }));

    expect(onSubmit).toHaveBeenCalledWith('user@aplika.test', 'password123');
  });

  it('disables the submit button and shows progress while loading', () => {
    render(
      <LoginForm
        labels={labels}
        validationLabels={validationLabels}
        onSubmit={vi.fn()}
        isLoading
      />,
    );

    expect(screen.getByRole('button', { name: 'Signing in...' })).toBeDisabled();
  });

  it('does not submit while a request is already in flight', async () => {
    const onSubmit = vi.fn();
    const user = userEvent.setup();
    render(
      <LoginForm
        labels={labels}
        validationLabels={validationLabels}
        onSubmit={onSubmit}
        isLoading
      />,
    );

    await user.click(screen.getByRole('button', { name: 'Signing in...' }));

    expect(onSubmit).not.toHaveBeenCalled();
  });

  it('surfaces the error message without clearing the form', async () => {
    const user = userEvent.setup();
    const { rerender } = render(
      <LoginForm labels={labels} validationLabels={validationLabels} onSubmit={vi.fn()} />,
    );

    await user.type(screen.getByLabelText('Email'), 'user@aplika.test');

    rerender(
      <LoginForm
        labels={labels}
        validationLabels={validationLabels}
        onSubmit={vi.fn()}
        error="Invalid credentials"
      />,
    );

    expect(screen.getByRole('alert')).toHaveTextContent('Invalid credentials');
    expect(screen.getByLabelText('Email')).toHaveValue('user@aplika.test');
  });

  it('blocks submission and reports the required email', async () => {
    const onSubmit = vi.fn();
    const user = userEvent.setup();
    render(<LoginForm labels={labels} validationLabels={validationLabels} onSubmit={onSubmit} />);

    await user.click(screen.getByRole('button', { name: 'Sign in' }));

    expect(screen.getByRole('alert')).toHaveTextContent('Email is required');
    expect(onSubmit).not.toHaveBeenCalled();
  });

  it('blocks submission and reports a malformed email', async () => {
    const onSubmit = vi.fn();
    const user = userEvent.setup();
    render(<LoginForm labels={labels} validationLabels={validationLabels} onSubmit={onSubmit} />);

    await user.type(screen.getByLabelText('Email'), 'not-an-email');
    await user.type(screen.getByLabelText('Password'), 'password123');
    await user.click(screen.getByRole('button', { name: 'Sign in' }));

    expect(screen.getByRole('alert')).toHaveTextContent('Invalid email format');
    expect(onSubmit).not.toHaveBeenCalled();
  });

  it('does not enforce the registration password policy on login', async () => {
    const onSubmit = vi.fn();
    const user = userEvent.setup();
    render(<LoginForm labels={labels} validationLabels={validationLabels} onSubmit={onSubmit} />);

    await user.type(screen.getByLabelText('Email'), 'user@aplika.test');
    await user.type(screen.getByLabelText('Password'), 'x');
    await user.click(screen.getByRole('button', { name: 'Sign in' }));

    expect(onSubmit).toHaveBeenCalledWith('user@aplika.test', 'x');
  });

  it('renders the localized validation messages it is given', async () => {
    const user = userEvent.setup();
    render(
      <LoginForm
        labels={labels}
        validationLabels={{
          emailRequired: 'El correo electrónico es obligatorio',
          emailInvalid: 'El formato del correo electrónico no es válido',
          passwordRequired: 'La contraseña es obligatoria',
          passwordMin: 'La contraseña debe tener al menos 8 caracteres',
        }}
        onSubmit={vi.fn()}
      />,
    );

    await user.click(screen.getByRole('button', { name: 'Sign in' }));

    expect(screen.getByRole('alert')).toHaveTextContent('El correo electrónico es obligatorio');
  });
});
