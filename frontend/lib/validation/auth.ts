import { z } from 'zod';

export const MIN_PASSWORD_LENGTH = 8;

export interface ValidationMessages {
  emailRequired: string;
  emailInvalid: string;
  passwordRequired: string;
  passwordMin: string;
}

export const defaultValidationMessages: ValidationMessages = {
  emailRequired: 'Email is required',
  emailInvalid: 'Invalid email format',
  passwordRequired: 'Password is required',
  passwordMin: `Password must be at least ${MIN_PASSWORD_LENGTH} characters`,
};

function emailField(messages: ValidationMessages) {
  return z
    .string({ error: messages.emailRequired })
    .min(1, { error: messages.emailRequired })
    .pipe(z.email({ error: messages.emailInvalid }));
}

function passwordField(messages: ValidationMessages) {
  return z
    .string({ error: messages.passwordRequired })
    .min(1, { error: messages.passwordRequired });
}

export function createRegisterSchema(messages: ValidationMessages = defaultValidationMessages) {
  return z.object({
    email: emailField(messages),
    password: passwordField(messages).pipe(
      z.string().min(MIN_PASSWORD_LENGTH, { error: messages.passwordMin }),
    ),
  });
}

export function createLoginSchema(messages: ValidationMessages = defaultValidationMessages) {
  return z.object({
    email: emailField(messages),
    password: passwordField(messages),
  });
}

export const registerSchema = createRegisterSchema();
export const loginSchema = createLoginSchema();

export type RegisterInput = z.infer<typeof registerSchema>;
export type LoginInput = z.infer<typeof loginSchema>;
