'use client';

import { useMemo, useState, type FormEvent } from 'react';
import { createLoginSchema, type ValidationMessages } from '@/lib/validation/auth';

export interface LoginFormLabels {
  email: string;
  password: string;
  submit: string;
  submitting: string;
}

export interface LoginFormProps {
  labels: LoginFormLabels;
  validationLabels: ValidationMessages;
  onSubmit: (email: string, password: string) => void | Promise<void>;
  isLoading?: boolean;
  error?: string | null;
}

export function LoginForm({
  labels,
  validationLabels,
  onSubmit,
  isLoading = false,
  error = null,
}: LoginFormProps) {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [validationError, setValidationError] = useState<string | null>(null);

  const schema = useMemo(() => createLoginSchema(validationLabels), [validationLabels]);

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

    setValidationError(null);
    void onSubmit(email, password);
  }

  const displayError = validationError ?? error;

  return (
    <form onSubmit={handleSubmit} noValidate>
      <div>
        <label htmlFor="login-email">{labels.email}</label>
        <input
          id="login-email"
          name="email"
          type="email"
          autoComplete="email"
          required
          value={email}
          onChange={(event) => setEmail(event.target.value)}
        />
      </div>

      <div>
        <label htmlFor="login-password">{labels.password}</label>
        <input
          id="login-password"
          name="password"
          type="password"
          autoComplete="current-password"
          required
          value={password}
          onChange={(event) => setPassword(event.target.value)}
        />
      </div>

      {displayError ? (
        <p role="alert" data-testid="login-error">
          {displayError}
        </p>
      ) : null}

      <button type="submit" disabled={isLoading}>
        {isLoading ? labels.submitting : labels.submit}
      </button>
    </form>
  );
}
