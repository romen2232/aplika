import { expect, test } from '@playwright/test';

test('home page shows the product name', async ({ page }) => {
  await page.goto('/');

  await expect(page.getByRole('heading', { name: 'Aplika' })).toBeVisible();
});
