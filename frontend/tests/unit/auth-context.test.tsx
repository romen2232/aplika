import { describe, expect, it } from 'vitest';
import { authReducer, initialAuthState, type AuthState } from '@/src/contexts/AuthContext';

const user = { id: 'u1', email: 'user@aplika.test', fullName: 'Jane Doe', roles: ['ROLE_USER'] };

describe('authReducer', () => {
  it('starts in a loading state without a user', () => {
    expect(initialAuthState).toEqual({ user: null, isLoading: true });
  });

  it('moves to loading while an auth operation is in progress', () => {
    const authenticated: AuthState = { user, isLoading: false };

    expect(authReducer(authenticated, { type: 'loading' })).toEqual({ user, isLoading: true });
  });

  it('sets the user when authenticated', () => {
    const next = authReducer(initialAuthState, { type: 'authenticated', user });

    expect(next).toEqual({ user, isLoading: false });
  });

  it('clears the user when unauthenticated', () => {
    const next = authReducer({ user, isLoading: false }, { type: 'unauthenticated' });

    expect(next).toEqual({ user: null, isLoading: false });
  });
});
