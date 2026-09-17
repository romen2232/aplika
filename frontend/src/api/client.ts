import type { operations } from './generated/types';

type MeResponse = operations['get_api_me']['responses'][200]['content']['application/json'];

export type User = Required<Pick<MeResponse, 'id' | 'email' | 'fullName' | 'roles'>>;

interface ErrorEnvelope {
  error?: string | { code?: string; message?: string };
}

export class ApiError extends Error {
  readonly status: number;
  readonly code?: string;

  constructor(message: string, status: number, code?: string) {
    super(message);
    this.name = 'ApiError';
    this.status = status;
    this.code = code;
  }
}

export class NetworkError extends Error {
  constructor(message = 'Unable to reach the server. Please try again.') {
    super(message);
    this.name = 'NetworkError';
  }
}

const AUTH_PATHS_WITHOUT_REFRESH = ['/api/auth/login', '/api/auth/register', '/api/auth/refresh'];

function parseErrorEnvelope(body: unknown, status: number): ApiError {
  const envelope = (body ?? {}) as ErrorEnvelope;
  const error = envelope.error;

  if (typeof error === 'string') {
    return new ApiError(error, status, error);
  }

  if (error && typeof error === 'object') {
    return new ApiError(error.message ?? 'Request failed', status, error.code);
  }

  return new ApiError('Request failed', status);
}

async function parseBody(response: Response): Promise<unknown> {
  const contentType = response.headers.get('content-type') ?? '';
  if (!contentType.includes('application/json')) {
    return null;
  }

  try {
    return await response.json();
  } catch {
    return null;
  }
}

export class ApiClient {
  constructor(private readonly baseUrl = '') {}

  private async rawRequest(path: string, init?: RequestInit): Promise<Response> {
    try {
      return await fetch(`${this.baseUrl}${path}`, {
        ...init,
        credentials: 'include',
        headers: {
          Accept: 'application/json',
          ...(init?.body ? { 'Content-Type': 'application/json' } : {}),
          ...init?.headers,
        },
      });
    } catch {
      throw new NetworkError();
    }
  }

  private async request<T>(path: string, init?: RequestInit, allowRefresh = true): Promise<T> {
    let response = await this.rawRequest(path, init);

    if (response.status === 401 && allowRefresh && !AUTH_PATHS_WITHOUT_REFRESH.includes(path)) {
      const refreshed = await this.refresh().then(
        () => true,
        () => false,
      );

      if (refreshed) {
        response = await this.rawRequest(path, init);
      }
    }

    const body = await parseBody(response);

    if (!response.ok) {
      throw parseErrorEnvelope(body, response.status);
    }

    return body as T;
  }

  async login(email: string, password: string): Promise<User> {
    await this.request<void>(
      '/api/auth/login',
      { method: 'POST', body: JSON.stringify({ email, password }) },
      false,
    );

    return this.me();
  }

  async register(email: string, fullName: string, password: string): Promise<User> {
    await this.request<void>(
      '/api/auth/register',
      { method: 'POST', body: JSON.stringify({ email, fullName, password }) },
      false,
    );

    return this.me();
  }

  async refresh(): Promise<void> {
    await this.request<void>('/api/auth/refresh', { method: 'POST' }, false);
  }

  async logout(): Promise<void> {
    await this.request<void>('/api/auth/logout', { method: 'POST' }, false);
  }

  async me(): Promise<User> {
    return this.request<User>('/api/me', { method: 'GET' });
  }
}

export const apiClient = new ApiClient();

export function toDisplayMessage(
  error: unknown,
  fallback: { network: string; generic: string },
): string {
  if (error instanceof NetworkError) {
    return fallback.network;
  }

  if (error instanceof ApiError) {
    return error.message;
  }

  return fallback.generic;
}
