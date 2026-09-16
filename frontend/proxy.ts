import { NextResponse } from 'next/server';
import type { NextRequest } from 'next/server';
import { match } from '@formatjs/intl-localematcher';
import Negotiator from 'negotiator';
import { locales, defaultLocale } from './i18n/config';

const PROTECTED_SEGMENTS = ['dashboard'] as const;

function getLocale(request: NextRequest): string {
  const headers = { 'accept-language': request.headers.get('accept-language') ?? 'en' };
  const languages = new Negotiator({ headers }).languages();
  return match(languages, locales, defaultLocale);
}

function isProtectedPath(rest: string): boolean {
  return PROTECTED_SEGMENTS.some(
    (segment) => rest === `/${segment}` || rest.startsWith(`/${segment}/`),
  );
}

export function proxy(request: NextRequest) {
  const { pathname } = request.nextUrl;

  // API requests are forwarded to the backend by next.config rewrites.
  // They must never receive a locale prefix or an auth redirect.
  if (pathname === '/api' || pathname.startsWith('/api/')) {
    return NextResponse.next();
  }

  const pathnameHasLocale = locales.some(
    (locale) => pathname.startsWith(`/${locale}/`) || pathname === `/${locale}`,
  );

  if (!pathnameHasLocale) {
    const locale = getLocale(request);
    const url = request.nextUrl.clone();
    url.pathname = `/${locale}${pathname}`;
    return NextResponse.redirect(url);
  }

  // Optimistic auth guard: only checks cookie presence, never validates tokens.
  // An expired access token with a valid refresh token is left to the client,
  // which performs a silent refresh.
  const locale = pathname.split('/')[1];
  const rest = pathname.slice(locale.length + 1);

  if (isProtectedPath(rest)) {
    const hasSession = request.cookies.has('access_token') || request.cookies.has('refresh_token');

    if (!hasSession) {
      const url = request.nextUrl.clone();
      url.pathname = `/${locale}/login`;
      url.search = `?returnUrl=${encodeURIComponent(pathname)}`;
      return NextResponse.redirect(url);
    }
  }

  return NextResponse.next();
}

export const config = {
  matcher: ['/((?!_next).*)'],
};
