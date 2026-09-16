import { expect, test } from '@playwright/test';

function uniqueEmail(): string {
  return `e2e-${Date.now()}-${Math.random().toString(36).slice(2, 8)}@aplika.test`;
}

async function registerThroughUi(page: import('@playwright/test').Page, email: string) {
  await page.goto('/en/register');
  await page.getByLabel('Email').fill(email);
  await page.getByLabel('Password', { exact: true }).fill('password123');
  await page.getByLabel('Confirm password').fill('password123');
  await page.getByRole('button', { name: 'Create account' }).click();
}

test.describe('auth flow', () => {
  test('unauthenticated visitor is redirected from the dashboard to login', async ({ page }) => {
    await page.goto('/en/dashboard');

    await expect(page).toHaveURL(/\/en\/login\?returnUrl=/);
    await expect(page.getByRole('heading', { name: 'Sign in' })).toBeVisible();
  });

  test('register, land on the dashboard, then logout', async ({ page }) => {
    const email = uniqueEmail();

    await registerThroughUi(page, email);

    await expect(page).toHaveURL(/\/en\/dashboard/);
    await expect(page.getByRole('heading', { name: 'Dashboard' })).toBeVisible();
    await expect(page.getByText(email)).toBeVisible();

    await page.getByRole('button', { name: 'Sign out' }).click();

    await expect(page).toHaveURL(/\/en\/login/);
    await expect(page.getByRole('heading', { name: 'Sign in' })).toBeVisible();
  });

  test('invalid credentials surface an error and keep the user on the login page', async ({
    page,
  }) => {
    await page.goto('/en/login');
    await page.getByLabel('Email').fill('missing@aplika.test');
    await page.getByLabel('Password').fill('wrong-password');
    await page.getByRole('button', { name: 'Sign in' }).click();

    await expect(page.getByTestId('login-error')).toContainText('Invalid credentials');
    await expect(page).toHaveURL(/\/en\/login/);
  });

  test('login sets host-only first-party cookies through the rewrite proxy', async ({
    page,
    context,
  }) => {
    const email = uniqueEmail();

    await registerThroughUi(page, email);
    await expect(page).toHaveURL(/\/en\/dashboard/);

    const cookies = await context.cookies();
    const accessToken = cookies.find((cookie) => cookie.name === 'access_token');
    const refreshToken = cookies.find((cookie) => cookie.name === 'refresh_token');

    expect(accessToken, 'access_token cookie should exist').toBeDefined();
    expect(refreshToken, 'refresh_token cookie should exist').toBeDefined();
    expect(accessToken?.httpOnly).toBe(true);
    expect(accessToken?.sameSite).toBe('Lax');

    // A full page load proves the cookie round-trips back through the proxy.
    await page.goto('/en/dashboard');
    await expect(page.getByRole('heading', { name: 'Dashboard' })).toBeVisible();
    await expect(page.getByText(email)).toBeVisible();
  });
});
