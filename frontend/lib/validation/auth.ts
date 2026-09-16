import { z } from 'zod';

export const MIN_PASSWORD_LENGTH = 8;

function requiredString(message: string) {
  return z.string({ error: message }).min(1, { error: message });
}

const emailField = requiredString('Email is required').pipe(
  z.email({ error: 'Invalid email format' }),
);

const passwordField = requiredString('Password is required');

export const registerSchema = z.object({
  email: emailField,
  password: passwordField.pipe(
    z.string().min(MIN_PASSWORD_LENGTH, {
      error: `Password must be at least ${MIN_PASSWORD_LENGTH} characters`,
    }),
  ),
});

export const loginSchema = z.object({
  email: emailField,
  password: passwordField,
});

export type RegisterInput = z.infer<typeof registerSchema>;
export type LoginInput = z.infer<typeof loginSchema>;
