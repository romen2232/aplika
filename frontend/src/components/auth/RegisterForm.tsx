'use client';

import { useMemo, useState, type FormEvent } from 'react';
import { createRegisterSchema, type ValidationMessages } from '@/lib/validation/auth';

export interface RegisterFormLabels {
  email: string;
  password: string;
  confirmPassword: string;
  submit: string;
  submitting: string;
  passwordMismatch: string;
}

export interface RegisterFormProps {
  labels: RegisterFormLabels;
  validationLabels: ValidationMessages;
  onSubmit: (email: string, password: string) => void | Promise<void>;
  isLoading?: boolean;
  error?: string | null;
}

export function RegisterForm({
  labels,
  validationLabels,
  onSubmit,
  isLoading = false,
  error = null,
}: RegisterFormProps) {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [validationError, setValidationError] = useState<string | null>(null);

  const schema = useMemo(() => createRegisterSchema(validationLabels), [validationLabels]);

  function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();

    if (isLoading) {
      return;
    }

    const result = schema.safeParse({ email, password });
    if (!result.success) {
      setValidationError(result.error.issues[0]?.message ?? null);
      return;
    }

    if (password !== confirmPassword) {
      setValidationError(labels.passwordMismatch);
      return;
    }

    setValidationError(null);
    void onSubmit(email, password);
  }

  const displayError = validationError ?? error;

  return (
    <form onSubmit={handleSubmit} noValidate>
      <div>
        <label htmlFor="register-email">{labels.email}</label>
        <input
          id="register-email"
          name="email"
          type="email"
          autoComplete="email"
          required
          value={email}
          onChange={(event) => setEmail(event.target.value)}
        />
      </div>

      <div>
        <label htmlFor="register-password">{labels.password}</label>
        <input
          id="register-password"
          name="password"
          type="password"
          autoComplete="new-password"
          required
          value={password}
          onChange={(event) => setPassword(event.target.value)}
        />
      </div>

      <div>
        <label htmlFor="register-confirm-password">{labels.confirmPassword}</label>
        <input
          id="register-confirm-password"
          name="confirmPassword"
          type="password"
          autoComplete="new-password"
          required
          value={confirmPassword}
          onChange={(event) => setConfirmPassword(event.target.value)}
        />
      </div>

      {displayError ? (
        <p role="alert" data-testid="register-error">
          {displayError}
        </p>
      ) : null}

      <button type="submit" disabled={isLoading}>
        {isLoading ? labels.submitting : labels.submit}
      </button>
    </form>
  );
}
