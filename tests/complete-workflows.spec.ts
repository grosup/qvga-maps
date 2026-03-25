import { test, expect } from '@playwright/test';

test.describe('Complete User Workflows', () => {
  test.beforeEach(async ({ page }) => {
    await page.context().clearCookies();
    await page.goto('/');
  });

  test('full search and navigate workflow', async ({ page }) => {
    // Start at Berlin
    const getCoords = async () => {
      const text = await page.locator('p:has-text("Lat:")').textContent();
      const latMatch = text?.match(/Lat: ([\d.-]+)/);
      const lonMatch = text?.match(/Lon: ([\d.-]+)/);
      return {
        lat: latMatch ? parseFloat(latMatch[1]) : null,
        lon: lonMatch ? parseFloat(lonMatch[1]) : null
      };
    };

    const initial = await getCoords();
    expect(initial!.lat).toBeCloseTo(52.52, 1);
    expect(initial!.lon).toBeCloseTo(13.4, 1);

    // Search "Eiffel Tower"
    await page.getByTestId('search-address').fill('Eiffel Tower');
    await page.getByTestId('search-submit').click();
    await page.waitForLoadState('domcontentloaded');

    // Select first result
    await page.getByTestId('search-result-item-0').click();
    await page.waitForURL('**/index.php');
    await page.waitForLoadState('domcontentloaded');

    // Now at Paris
    const afterSearch = await getCoords();
    expect(afterSearch.lat).toBeCloseTo(48.86, 1);
    expect(afterSearch.lon).toBeCloseTo(2.29, 1);

    // Zoom in twice
    await page.getByTestId('map-zoom-in').click();
    await page.waitForLoadState('domcontentloaded');
    await page.getByTestId('map-zoom-in').click();
    await page.waitForLoadState('domcontentloaded');

    const afterZoom = await getCoords();
    expect(afterZoom.lat).toBeCloseTo(afterSearch.lat, 2);
    expect(afterZoom.lon).toBeCloseTo(afterSearch.lon, 2);

    // Pan left
    const beforePan = await getCoords();
    await page.getByTestId('map-left').click();
    await page.waitForLoadState('domcontentloaded');

    const afterPan = await getCoords();
    expect(afterPan.lon).toBeLessThan(beforePan.lon!);

    // Switch to Satellite
    await page.getByTestId('satellite-v9').click();
    await page.waitForLoadState('domcontentloaded');

    const title = await page.title();
    expect(title.toLowerCase()).toMatch(/satellite/);

    // Still at Paris coords
    const afterStyle = await getCoords();
    expect(afterStyle.lat).toBeCloseTo(afterPan.lat, 2);
    expect(afterStyle.lon).toBeCloseTo(afterPan.lon, 2);
  });

  test('multiple searches in one session', async ({ page }) => {
    const searchSelectAndVerifyCity = async (city: string, expectedLat: number, expectedLon: number) => {
      await page.getByTestId('search-address').fill(city);
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

  test('navigation works after search selection', async ({ page }) => {
    // Search London
    await page.getByTestId('search-address').fill('London');
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

    const before = await getCoords();

    // Zoom in
    await page.getByTestId('map-zoom-in').click();
    await page.waitForLoadState('domcontentloaded');

    // Pan right
    await page.getByTestId('map-right').click();
    await page.waitForLoadState('domcontentloaded');

    const after = await getCoords();
    expect(after.lon).toBeGreaterThan(before.lon!);
    // Latitude might change slightly depending on projection, but lon should def increase
  });

  test('style switching after search works', async ({ page }) => {
    await page.getByTestId('search-address').fill('Grand Canyon');
    await page.getByTestId('search-submit').click();
    await page.waitForLoadState('domcontentloaded');
    await page.getByTestId('search-result-item-0').click();
    await page.waitForURL('**/index.php');
    await page.waitForLoadState('domcontentloaded');

    // Switch to Outdoors
    await page.getByTestId('outdoors-v12').click();
    await page.waitForLoadState('domcontentloaded');

    const outdoors = page.getByTestId('outdoors-v12');
    await expect(outdoors).toHaveClass(/active|font-weight-bold/i);

    // Switch to Satellite
    await page.getByTestId('satellite-v9').click();
    await page.waitForLoadState('domcontentloaded');

    const satellite = page.getByTestId('satellite-v9');
    await expect(satellite).toHaveClass(/active|font-weight-bold/i);
  });

  test('search refinement workflow', async ({ page }) => {
    // Broad search
    await page.getByTestId('search-address').fill('restaurant');
    await page.getByTestId('search-submit').click();
    await page.waitForLoadState('domcontentloaded');

    // Should have results
    await expect(page.getByTestId('search-result-item-0')).toBeVisible();

    // Select one
    await page.getByTestId('search-result-item-0').click();
    await page.waitForURL('**/index.php');
    await page.waitForLoadState('domcontentloaded');

    // Should be on map
    await expect(page.getByTestId('map')).toBeVisible();

    // Search again
    await page.getByTestId('search-address').fill('Brandenburg Gate');
    await page.getByTestId('search-submit').click();
    await page.waitForLoadState('domcontentloaded');
    await page.getByTestId('search-result-item-0').click();
    await page.waitForURL('**/index.php');
    await page.waitForLoadState('domcontentloaded');

    // Should be at Brandenburg Gate (Berlin coordinates)
    const text = await page.locator('p:has-text("Lat:")').textContent();
    const lat = parseFloat(text?.match(/Lat: ([\d.-]+)/)?.[1] || '0');
    const lon = parseFloat(text?.match(/Lon: ([\d.-]+)/)?.[1] || '0');

    expect(lat).toBeCloseTo(52.516, 2); // Brandenburg Gate area
    expect(lon).toBeCloseTo(13.377, 2);
  });
});