import { describe, expect, it } from 'vitest';

import { MIN_PASSWORD_LENGTH, loginSchema, registerSchema } from '@/lib/validation/auth';

type ParseResult = { success: true } | { success: false; error: { issues: { message: string }[] } };

function issueMessages(result: ParseResult): string[] {
  return result.success ? [] : result.error.issues.map((issue) => issue.message);
}

describe('registerSchema', () => {
  it('accepts valid registration input', () => {
    const result = registerSchema.safeParse({
      email: 'user@example.com',
      password: 'SecurePass123',
    });

    expect(result.success).toBe(true);
  });

  it('rejects a missing email', () => {
    const result = registerSchema.safeParse({ password: 'SecurePass123' });

    expect(issueMessages(result)).toEqual(['Email is required']);
  });

  it('rejects an empty email', () => {
    const result = registerSchema.safeParse({
      email: '',
      password: 'SecurePass123',
    });

    expect(issueMessages(result)).toEqual(['Email is required']);
  });

  it('rejects a malformed email', () => {
    const result = registerSchema.safeParse({
      email: 'not-an-email',
      password: 'SecurePass123',
    });

    expect(issueMessages(result)).toEqual(['Invalid email format']);
  });

  it('rejects a missing password', () => {
    const result = registerSchema.safeParse({ email: 'user@example.com' });

    expect(issueMessages(result)).toEqual(['Password is required']);
  });

  it('rejects an empty password', () => {
    const result = registerSchema.safeParse({
      email: 'user@example.com',
      password: '',
    });

    expect(issueMessages(result)).toEqual(['Password is required']);
  });

  it('rejects a password shorter than the minimum length', () => {
    const result = registerSchema.safeParse({
      email: 'user@example.com',
      password: 'a'.repeat(MIN_PASSWORD_LENGTH - 1),
    });

    expect(issueMessages(result)).toEqual([
      `Password must be at least ${MIN_PASSWORD_LENGTH} characters`,
    ]);
  });

  it('accepts a password at the minimum length', () => {
    const result = registerSchema.safeParse({
      email: 'user@example.com',
      password: 'a'.repeat(MIN_PASSWORD_LENGTH),
    });

    expect(result.success).toBe(true);
  });
});

describe('loginSchema', () => {
  it('accepts valid credentials', () => {
    const result = loginSchema.safeParse({
      email: 'user@example.com',
      password: 'SecurePass123',
    });

    expect(result.success).toBe(true);
  });

  it('rejects a missing email', () => {
    const result = loginSchema.safeParse({ password: 'SecurePass123' });

    expect(issueMessages(result)).toEqual(['Email is required']);
  });

  it('rejects a malformed email', () => {
    const result = loginSchema.safeParse({
      email: 'not-an-email',
      password: 'SecurePass123',
    });

    expect(issueMessages(result)).toEqual(['Invalid email format']);
  });

  it('rejects a missing password', () => {
    const result = loginSchema.safeParse({ email: 'user@example.com' });

    expect(issueMessages(result)).toEqual(['Password is required']);
  });

  it('does not enforce the registration password policy', () => {
    const result = loginSchema.safeParse({
      email: 'user@example.com',
      password: 'x',
    });

    expect(result.success).toBe(true);
  });
});
