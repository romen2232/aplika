import { z } from 'zod';

export const MIN_PASSWORD_LENGTH = 8;

export interface ValidationMessages {
  fullNameRequired: string;
  emailRequired: string;
  emailInvalid: string;
  passwordRequired: string;
  passwordMin: string;
  passwordComplexity: string;
}

export const defaultValidationMessages: ValidationMessages = {
  fullNameRequired: 'Full name is required',
  emailRequired: 'Email is required',
  emailInvalid: 'Invalid email format',
  passwordRequired: 'Password is required',
  passwordMin: `Password must be at least ${MIN_PASSWORD_LENGTH} characters`,
  passwordComplexity: 'Password must contain both letters and numbers',
};

function fullNameField(messages: ValidationMessages) {
  return z
    .string({ error: messages.fullNameRequired })
    .min(1, { error: messages.fullNameRequired });
}

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

function passwordComplexityCheck(messages: ValidationMessages) {
  return z
    .string()
    .refine((val) => /[a-zA-Z]/.test(val) && /[0-9]/.test(val), {
      message: messages.passwordComplexity,
    });
}

export function createRegisterSchema(messages: ValidationMessages = defaultValidationMessages) {
  return z.object({
    fullName: fullNameField(messages),
    email: emailField(messages),
    password: passwordField(messages)
      .pipe(z.string().min(MIN_PASSWORD_LENGTH, { error: messages.passwordMin }))
      .pipe(passwordComplexityCheck(messages)),
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
