import { expect, test } from '@playwright/test';

function uniqueEmail(): string {
  return `e2e-${Date.now()}-${Math.random().toString(36).slice(2, 8)}@aplika.test`;
}

async function registerThroughLanding(
  page: import('@playwright/test').Page,
  fullName: string,
  email: string,
) {
  await page.goto('/en');
  await page.getByRole('button', { name: 'Sign up' }).click();
  await page.getByLabel('Full name').fill(fullName);
  await page.getByLabel('Email').fill(email);
  await page.getByLabel('Password', { exact: true }).fill('password123');
  await page.getByRole('button', { name: 'Create account' }).click();
}

test.describe('landing page auth flow', () => {
  test('landing page shows sign in and sign up tabs', async ({ page }) => {
    await page.goto('/en');

    await expect(page.getByRole('button', { name: 'Sign in' })).toBeVisible();
    await expect(page.getByRole('button', { name: 'Sign up' })).toBeVisible();
  });

  test('register from the landing page, land on the dashboard, then logout', async ({ page }) => {
    const email = uniqueEmail();

    await registerThroughLanding(page, 'Jane Doe', email);

    await expect(page).toHaveURL(/\/en\/dashboard/);
    await expect(page.getByRole('heading', { name: 'Dashboard' })).toBeVisible();
    await expect(page.getByText(email)).toBeVisible();

    await page.getByRole('button', { name: 'Log out' }).click();

    await expect(page).toHaveURL(/\/en/);
  });

  test('invalid credentials surface an error on the landing page sign in tab', async ({
    page,
  }) => {
    await page.goto('/en');
    await page.getByLabel('Email').fill('missing@aplika.test');
    await page.getByLabel('Password', { exact: true }).fill('wrong-password');
    await page.getByRole('button', { name: 'Sign in' }).click();

    await expect(page.getByTestId('signin-error')).toContainText('Invalid credentials');
  });

  test('unauthenticated visitor is redirected from the dashboard to the landing page', async ({
    page,
  }) => {
    await page.goto('/en/dashboard');

    await expect(page).toHaveURL(/\/en/);
  });

  test('register requires full name, email and password', async ({ page }) => {
    await page.goto('/en');
    await page.getByRole('button', { name: 'Sign up' }).click();
    await page.getByRole('button', { name: 'Create account' }).click();

    await expect(page.getByTestId('signup-error')).toContainText('Full name is required');
  });

  test('login sets host-only first-party cookies through the rewrite proxy', async ({
    page,
    context,
  }) => {
    const email = uniqueEmail();

    await registerThroughLanding(page, 'Jane Doe', email);
    await expect(page).toHaveURL(/\/en\/dashboard/);

    const cookies = await context.cookies();
    const accessToken = cookies.find((cookie) => cookie.name === 'access_token');
    const refreshToken = cookies.find((cookie) => cookie.name === 'refresh_token');

    expect(accessToken, 'access_token cookie should exist').toBeDefined();
    expect(refreshToken, 'refresh_token cookie should exist').toBeDefined();
    expect(accessToken?.httpOnly).toBe(true);
    expect(accessToken?.sameSite).toBe('Lax');

    await page.goto('/en/dashboard');
    await expect(page.getByRole('heading', { name: 'Dashboard' })).toBeVisible();
    await expect(page.getByText(email)).toBeVisible();
  });
});
