import { test, expect } from '@playwright/test';

test.describe('Map Style Switching', () => {
  test.beforeEach(async ({ page }) => {
    await page.context().clearCookies();
    await page.goto('/');
  });

  test('switch to Satellite style', async ({ page }) => {
    // Click Satellite
    await page.getByTestId('satellite-v9').click();
    await page.waitForLoadState('domcontentloaded');

    // Check Satellite is active (non-link or has active class)
    const satellite = page.getByTestId('satellite-v9');
    await expect(satellite).toHaveClass(/active|font-weight-bold/i);

    // Map should still be visible
    await expect(page.getByTestId('map')).toBeVisible();

    // Page title should show Satellite
    const title = await page.title();
    expect(title.toLowerCase()).toMatch(/satellite/);
  });

  test('switch to Outdoors style', async ({ page }) => {
    await page.getByTestId('outdoors-v12').click();
    await page.waitForLoadState('domcontentloaded');

    const outdoors = page.getByTestId('outdoors-v12');
    await expect(outdoors).toHaveClass(/active|font-weight-bold/i);
    await expect(page.getByTestId('map')).toBeVisible();

    const title = await page.title();
    expect(title.toLowerCase()).toMatch(/outdoors/);
  });

  test('switch back to Streets', async ({ page }) => {
    // Cycle: Streets → Satellite → Outdoors → Streets
    await page.getByTestId('satellite-v9').click();
    await page.waitForLoadState('domcontentloaded');

    await page.getByTestId('outdoors-v12').click();
    await page.waitForLoadState('domcontentloaded');

    await page.getByTestId('streets-v12').click();
    await page.waitForLoadState('domcontentloaded');

    const streets = page.getByTestId('streets-v12');
    await expect(streets).toHaveClass(/active|font-weight-bold/i);

    const title = await page.title();
    expect(title.toLowerCase()).toMatch(/streets/);
  });

  test('style persists after search selection', async ({ page }) => {
    // Set to Satellite
    await page.getByTestId('satellite-v9').click();
    await page.waitForLoadState('domcontentloaded');

    // Search and select
    await page.getByTestId('search-address').fill('Grand Canyon');
    await page.waitForTimeout(1000); // Delay to avoid Nominatim rate limiting
    await page.getByTestId('search-submit').click();
    await page.waitForLoadState('domcontentloaded');
    await page.getByTestId('search-result-item-0').click();
    await page.waitForURL('**/index.php');
    await page.waitForLoadState('domcontentloaded');

    // Satellite should still be active
    const satellite = page.getByTestId('satellite-v9');
    await expect(satellite).toHaveClass(/active|font-weight-bold/i);
  });

  test('coordinates unchanged when switching styles', async ({ page }) => {
    // Get initial coords
    const getCoordsText = async () => {
      return await page.locator('p:has-text("Lat:")').textContent();
    };

    const initialCoords = await getCoordsText();

    // Switch style
    await page.getByTestId('outdoors-v12').click();
    await page.waitForLoadState('domcontentloaded');

    const newCoords = await getCoordsText();
    expect(newCoords).toBe(initialCoords);
  });
});