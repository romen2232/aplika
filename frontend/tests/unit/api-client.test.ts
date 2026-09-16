import { afterEach, describe, expect, it, vi } from 'vitest';
import { ApiClient, ApiError, NetworkError, toDisplayMessage } from '@/src/api/client';

function jsonResponse(body: unknown, status = 200): Response {
  return new Response(JSON.stringify(body), {
    status,
    headers: { 'content-type': 'application/json' },
  });
}

describe('ApiClient', () => {
  afterEach(() => {
    vi.restoreAllMocks();
    vi.unstubAllGlobals();
  });

  it('sends credentialed same-origin requests and returns typed data', async () => {
    const fetchMock = vi
      .fn()
      .mockResolvedValue(
        jsonResponse({ id: 'u1', email: 'user@aplika.test', roles: ['ROLE_USER'] }),
      );
    vi.stubGlobal('fetch', fetchMock);

    const client = new ApiClient();
    const user = await client.me();

    expect(user).toEqual({ id: 'u1', email: 'user@aplika.test', roles: ['ROLE_USER'] });
    expect(fetchMock).toHaveBeenCalledWith(
      '/api/me',
      expect.objectContaining({ credentials: 'include' }),
    );
  });

  it('parses the flat error envelope into an ApiError', async () => {
    vi.stubGlobal(
      'fetch',
      vi.fn().mockResolvedValue(jsonResponse({ error: 'Invalid credentials' }, 401)),
    );

    const client = new ApiClient();

    await expect(client.login('user@aplika.test', 'wrong')).rejects.toMatchObject({
      name: 'ApiError',
      status: 401,
      message: 'Invalid credentials',
    });
  });

  it('parses the nested error envelope into an ApiError', async () => {
    vi.stubGlobal(
      'fetch',
      vi
        .fn()
        .mockResolvedValue(
          jsonResponse(
            { error: { code: 'VALIDATION_ERROR', message: 'Invalid email format' } },
            400,
          ),
        ),
    );

    const client = new ApiClient();

    await expect(client.register('bad', 'password123')).rejects.toMatchObject({
      status: 400,
      message: 'Invalid email format',
      code: 'VALIDATION_ERROR',
    });
  });

  it('silently refreshes and retries a protected request that returns 401', async () => {
    const fetchMock = vi
      .fn()
      .mockResolvedValueOnce(jsonResponse({ error: 'Unauthorized' }, 401))
      .mockResolvedValueOnce(jsonResponse({ message: 'Tokens refreshed' }, 200))
      .mockResolvedValueOnce(
        jsonResponse({ id: 'u1', email: 'user@aplika.test', roles: ['ROLE_USER'] }, 200),
      );
    vi.stubGlobal('fetch', fetchMock);

    const client = new ApiClient();
    const user = await client.me();

    expect(user.email).toBe('user@aplika.test');
    expect(fetchMock).toHaveBeenCalledTimes(3);
    expect(fetchMock.mock.calls[1][0]).toBe('/api/auth/refresh');
    expect(fetchMock.mock.calls[2][0]).toBe('/api/me');
  });

  it('does not retry when a login request returns 401', async () => {
    const fetchMock = vi
      .fn()
      .mockResolvedValue(jsonResponse({ error: 'Invalid credentials' }, 401));
    vi.stubGlobal('fetch', fetchMock);

    const client = new ApiClient();

    await expect(client.login('user@aplika.test', 'wrong')).rejects.toBeInstanceOf(ApiError);
    expect(fetchMock).toHaveBeenCalledTimes(1);
  });

  it('maps transport failures to a NetworkError', async () => {
    vi.stubGlobal('fetch', vi.fn().mockRejectedValue(new TypeError('failed to fetch')));

    const client = new ApiClient();

    await expect(client.me()).rejects.toBeInstanceOf(NetworkError);
  });
});

describe('toDisplayMessage', () => {
  const fallback = { network: 'network down', generic: 'generic failure' };

  it('uses the API message for ApiError', () => {
    expect(toDisplayMessage(new ApiError('Email already registered', 409), fallback)).toBe(
      'Email already registered',
    );
  });

  it('uses the network message for NetworkError', () => {
    expect(toDisplayMessage(new NetworkError(), fallback)).toBe('network down');
  });

  it('falls back to the generic message for unknown errors', () => {
    expect(toDisplayMessage(new Error('boom'), fallback)).toBe('generic failure');
  });
});
