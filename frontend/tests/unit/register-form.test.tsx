import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';
import { RegisterForm } from '@/src/components/auth/RegisterForm';
import type { ValidationMessages } from '@/lib/validation/auth';

const labels = {
  email: 'Email',
  password: 'Password',
  confirmPassword: 'Confirm password',
  submit: 'Create account',
  submitting: 'Creating account...',
  passwordMismatch: 'Passwords do not match',
};

const validationLabels: ValidationMessages = {
  emailRequired: 'Email is required',
  emailInvalid: 'Invalid email format',
  passwordRequired: 'Password is required',
  passwordMin: 'Password must be at least 8 characters',
};

async function fill(values: { email?: string; password?: string; confirmPassword?: string } = {}) {
  const user = userEvent.setup();

  if (values.email !== undefined) {
    await user.type(screen.getByLabelText('Email'), values.email);
  }
  if (values.password !== undefined) {
    await user.type(screen.getByLabelText('Password'), values.password);
  }
  if (values.confirmPassword !== undefined) {
    await user.type(screen.getByLabelText('Confirm password'), values.confirmPassword);
  }

  return user;
}

describe('RegisterForm', () => {
  it('renders the email, password and confirmation fields', () => {
    render(<RegisterForm labels={labels} validationLabels={validationLabels} onSubmit={vi.fn()} />);

    expect(screen.getByLabelText('Email')).toBeInTheDocument();
    expect(screen.getByLabelText('Password')).toBeInTheDocument();
    expect(screen.getByLabelText('Confirm password')).toBeInTheDocument();
  });

  it('submits when the input is valid and the passwords match', async () => {
    const onSubmit = vi.fn();
    render(
      <RegisterForm labels={labels} validationLabels={validationLabels} onSubmit={onSubmit} />,
    );

    const user = await fill({
      email: 'user@aplika.test',
      password: 'password123',
      confirmPassword: 'password123',
    });
    await user.click(screen.getByRole('button', { name: 'Create account' }));

    expect(onSubmit).toHaveBeenCalledWith('user@aplika.test', 'password123');
  });

  it('blocks submission and reports a malformed email', async () => {
    const onSubmit = vi.fn();
    render(
      <RegisterForm labels={labels} validationLabels={validationLabels} onSubmit={onSubmit} />,
    );

    const user = await fill({
      email: 'not-an-email',
      password: 'password123',
      confirmPassword: 'password123',
    });
    await user.click(screen.getByRole('button', { name: 'Create account' }));

    expect(screen.getByRole('alert')).toHaveTextContent('Invalid email format');
    expect(onSubmit).not.toHaveBeenCalled();
  });

  it('blocks submission and reports a password below the minimum length', async () => {
    const onSubmit = vi.fn();
    render(
      <RegisterForm labels={labels} validationLabels={validationLabels} onSubmit={onSubmit} />,
    );

    const user = await fill({
      email: 'user@aplika.test',
      password: 'short',
      confirmPassword: 'short',
    });
    await user.click(screen.getByRole('button', { name: 'Create account' }));

    expect(screen.getByRole('alert')).toHaveTextContent('Password must be at least 8 characters');
    expect(onSubmit).not.toHaveBeenCalled();
  });

  it('blocks submission and reports a mismatched confirmation', async () => {
    const onSubmit = vi.fn();
    render(
      <RegisterForm labels={labels} validationLabels={validationLabels} onSubmit={onSubmit} />,
    );

    const user = await fill({
      email: 'user@aplika.test',
      password: 'password123',
      confirmPassword: 'password456',
    });
    await user.click(screen.getByRole('button', { name: 'Create account' }));

    expect(screen.getByRole('alert')).toHaveTextContent('Passwords do not match');
    expect(onSubmit).not.toHaveBeenCalled();
  });

  it('renders the localized validation messages it is given', async () => {
    render(
      <RegisterForm
        labels={{ ...labels, passwordMismatch: 'Las contraseñas no coinciden' }}
        validationLabels={{
          emailRequired: 'El correo electrónico es obligatorio',
          emailInvalid: 'El formato del correo electrónico no es válido',
          passwordRequired: 'La contraseña es obligatoria',
          passwordMin: 'La contraseña debe tener al menos 8 caracteres',
        }}
        onSubmit={vi.fn()}
      />,
    );

    const user = await fill({
      email: 'user@aplika.test',
      password: 'password123',
      confirmPassword: 'password456',
    });
    await user.click(screen.getByRole('button', { name: 'Create account' }));

    expect(screen.getByRole('alert')).toHaveTextContent('Las contraseñas no coinciden');
  });
});
