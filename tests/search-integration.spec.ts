import { test, expect } from '@playwright/test';

test.describe('Search to Map Integration', () => {
  test.beforeEach(async ({ page }) => {
    await page.context().clearCookies();
    await page.goto('/');
  });

  test('selecting search result moves map to that location', async ({ page }) => {
    // Get initial coords (Berlin)
    const getCoords = async () => {
      const text = await page.locator('p:has-text("Lat:")').textContent();
      const latMatch = text?.match(/Lat: ([\d.-]+)/);
      const lonMatch = text?.match(/Lon: ([\d.-]+)/);
      return {
        lat: latMatch ? parseFloat(latMatch[1]) : null,
        lon: lonMatch ? parseFloat(lonMatch[1]) : null
      };
    };

    const initialCoords = await getCoords();

    // Search for Paris
    await page.getByTestId('search-address').fill('Paris');
    await page.waitForTimeout(1000); // Delay to avoid Nominatim rate limiting
    await page.getByTestId('search-submit').click();
    await page.waitForLoadState('domcontentloaded');

    // Click first result
    await page.getByTestId('search-result-item-0').click();
    await page.waitForURL('**/index.php');
    await page.waitForLoadState('domcontentloaded');

    // Verify new coordinates (Paris should be ~48.85, 2.35)
    const newCoords = await getCoords();
    expect(newCoords.lat).toBeCloseTo(48.85, 1);
    expect(newCoords.lon).toBeCloseTo(2.35, 1);

    // Verify coords actually changed from Berlin
    expect(newCoords.lat).not.toBeCloseTo(initialCoords.lat!, 1);
    expect(newCoords.lon).not.toBeCloseTo(initialCoords.lon!, 1);
  });

  test('selection preserves map style', async ({ page }) => {
    // Set style to Satellite first
    await page.getByTestId('satellite-v9').click();
    await page.waitForLoadState('domcontentloaded');

    // Search and select
    await page.getByTestId('search-address').fill('Tokyo');
    await page.waitForTimeout(1000); // Delay to avoid Nominatim rate limiting
    await page.getByTestId('search-submit').click();
    await page.waitForLoadState('domcontentloaded');
    await page.getByTestId('search-result-item-0').click();
    await page.waitForURL('**/index.php');
    await page.waitForLoadState('domcontentloaded');

    // Check Satellite is still active (should be non-link, not clickable as "Satellite")
    const satelliteLink = page.getByTestId('satellite-v9');
    await expect(satelliteLink).toHaveClass(/active|font-weight-bold/i);
  });

  test('back to map link preserves current state', async ({ page }) => {
    // First, change coordinates by search+select
    await page.getByTestId('search-address').fill('New York');
    await page.getByTestId('search-submit').click();
    await page.waitForLoadState('domcontentloaded');
    await page.getByTestId('search-result-item-0').click();
    await page.waitForURL('**/index.php');
    await page.waitForLoadState('domcontentloaded');

    const coordsAfterSelect = await page.locator('p:has-text("Lat:")').textContent();

    // Now go to results again (search anything)
    await page.getByTestId('search-address').fill('London');
    await page.getByTestId('search-submit').click();
    await page.waitForLoadState('domcontentloaded');

    // Click "Back to Map"
    await page.getByTestId('search-result-back-header').click();
    await page.waitForURL('**/index.php');
    await page.waitForLoadState('domcontentloaded');

    // Should be back at New York coordinates
    const coordsBack = await page.locator('p:has-text("Lat:")').textContent();
    expect(coordsBack).toBe(coordsAfterSelect);
  });

  test('can navigate after selecting search result', async ({ page }) => {
    // Search and select Tokyo
    await page.getByTestId('search-address').fill('Tokyo');
    await page.waitForTimeout(1000); // Delay to avoid Nominatim rate limiting
    await page.getByTestId('search-submit').click();
    await page.waitForLoadState('domcontentloaded');
    await page.getByTestId('search-result-item-0').click();
    await page.waitForURL('**/index.php');
    await page.waitForLoadState('domcontentloaded');

    const getCoords = async () => {
      const text = await page.locator('p:has-text("Lat:")').textContent();
      const latMatch = text?.match(/Lat: ([\d.-]+)/);
      const lonMatch = text?.match(/Lon: ([\d.-]+)/);
      return {
        lat: latMatch ? parseFloat(latMatch[1]) : null,
        lon: lonMatch ? parseFloat(lonMatch[1]) : null
      };
    };

    const beforeNav = await getCoords();

    // Pan right
    await page.getByTestId('map-right').click();
    await page.waitForLoadState('domcontentloaded');

    const afterNav = await getCoords();
    expect(afterNav.lon).toBeGreaterThan(beforeNav.lon!);
    expect(afterNav.lat).toBeCloseTo(beforeNav.lat!, 5); // lat unchanged
  });

  test('multiple searches in one session', async ({ page }) => {
    const searchSelectAndVerifyCity = async (city: string, expectedLat: number, expectedLon: number) => {
      await page.getByTestId('search-address').fill(city);
      await page.waitForTimeout(1000); // Delay to avoid Nominatim rate limiting
      await page.getByTestId('search-submit').click();
      await page.waitForLoadState('domcontentloaded');
      await page.getByTestId('search-result-item-0').click();
      await page.waitForURL('**/index.php');
      await page.waitForLoadState('domcontentloaded');

      const text = await page.locator('p:has-text("Lat:")').textContent();
      const lat = parseFloat(text?.match(/Lat: ([\d.-]+)/)?.[1] || '0');
      const lon = parseFloat(text?.match(/Lon: ([\d.-]+)/)?.[1] || '0');

      expect(lat).toBeCloseTo(expectedLat, 1);
      expect(lon).toBeCloseTo(expectedLon, 1);
    };

    // Berlin default → Tokyo
    await searchSelectAndVerifyCity('Tokyo', 35.67, 139.76);

    // Tokyo → New York
    await searchSelectAndVerifyCity('New York', 40.7, -74.0);

    // New York → Sydney
    await searchSelectAndVerifyCity('Sydney', -33.86, 151.2);
  });
});