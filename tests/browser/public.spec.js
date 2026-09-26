const { test, expect } = require('@playwright/test');

test('public homepage and about page render', async ({ page }) => {
  await page.goto('/');
  await expect(page).toHaveTitle(/ImmuniCare/);
  await expect(page.getByRole('heading', { name: /Healthier Tomorrow/i })).toBeVisible();

  await page.goto('/about.php');
  await expect(page).toHaveTitle(/About ImmuniCare/i);
  await expect(page.getByRole('heading', { name: /Every Vaccination Journey/i })).toBeVisible();
});

test('public pages have no horizontal overflow', async ({ page }) => {
  for (const width of [320, 390, 768, 1024, 1440]) {
    await page.setViewportSize({ width, height: 900 });
    await page.goto('/');
    const overflow = await page.evaluate(
      () => document.documentElement.scrollWidth > document.documentElement.clientWidth
    );
    expect(overflow, `horizontal overflow at ${width}px`).toBeFalsy();
  }
});
