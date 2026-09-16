'use client';

import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useReducer,
  type ReactNode,
} from 'react';
import { apiClient, type User } from '@/src/api/client';

export interface AuthState {
  user: User | null;
  isLoading: boolean;
}

export type AuthAction =
  { type: 'loading' } | { type: 'authenticated'; user: User } | { type: 'unauthenticated' };

export const initialAuthState: AuthState = { user: null, isLoading: true };

export function authReducer(state: AuthState, action: AuthAction): AuthState {
  switch (action.type) {
    case 'loading':
      return { ...state, isLoading: true };
    case 'authenticated':
      return { user: action.user, isLoading: false };
    case 'unauthenticated':
      return { user: null, isLoading: false };
    default:
      return state;
  }
}

export interface AuthContextValue extends AuthState {
  login: (email: string, password: string) => Promise<User>;
  register: (email: string, password: string) => Promise<User>;
  logout: () => Promise<void>;
}

export const AuthContext = createContext<AuthContextValue | null>(null);

export function AuthProvider({ children }: { children: ReactNode }) {
  const [state, dispatch] = useReducer(authReducer, initialAuthState);

  useEffect(() => {
    let cancelled = false;

    apiClient
      .me()
      .then((user) => {
        if (!cancelled) dispatch({ type: 'authenticated', user });
      })
      .catch(() => {
        if (!cancelled) dispatch({ type: 'unauthenticated' });
      });

    return () => {
      cancelled = true;
    };
  }, []);

  const login = useCallback(async (email: string, password: string) => {
    dispatch({ type: 'loading' });
    const user = await apiClient.login(email, password);
    dispatch({ type: 'authenticated', user });
    return user;
  }, []);

  const register = useCallback(async (email: string, password: string) => {
    dispatch({ type: 'loading' });
    const user = await apiClient.register(email, password);
    dispatch({ type: 'authenticated', user });
    return user;
  }, []);

  const logout = useCallback(async () => {
    try {
      await apiClient.logout();
    } finally {
      dispatch({ type: 'unauthenticated' });
    }
  }, []);

  const value = useMemo<AuthContextValue>(
    () => ({ ...state, login, register, logout }),
    [state, login, register, logout],
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth(): AuthContextValue {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
}
