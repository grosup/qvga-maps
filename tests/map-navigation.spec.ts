import { test, expect } from '@playwright/test';

test.describe('Map Navigation', () => {
  test.beforeEach(async ({ page }) => {
    // Fresh session for each test
    await page.context().clearCookies();
    await page.goto('/');
  });

  test('page loads with default Berlin map', async ({ page }) => {
    // Check map image
    const map = page.getByTestId('map');
    await expect(map).toBeVisible();
    await expect(map).toHaveAttribute('alt', 'MAP');

    // Check search input (uses data-test-id in HTML)
    const searchInput = page.getByTestId('search-address');
    await expect(searchInput).toBeVisible();
    await expect(searchInput).toHaveAttribute('placeholder', 'Street, City');

    // Check navigation buttons
    await expect(page.getByTestId('map-left')).toBeVisible();
    await expect(page.getByTestId('map-right')).toBeVisible();
    await expect(page.getByTestId('map-up')).toBeVisible();
    await expect(page.getByTestId('map-down')).toBeVisible();
    await expect(page.getByTestId('map-zoom-in')).toBeVisible();
    await expect(page.getByTestId('map-zoom-out')).toBeVisible();

    // Check style links
    await expect(page.getByTestId('streets-v12')).toContainText('Streets');
    await expect(page.getByTestId('outdoors-v12')).toContainText('Outdoors');
    await expect(page.getByTestId('satellite-v9')).toContainText('Satellite');

    // Check coordinates show Berlin defaults
    const coordsText = await page.locator('p:has-text("Lat:")').textContent();
    expect(coordsText).toContain('Lat:');
    expect(coordsText).toContain('Lon: 13.4');
  });

  test('move right increases longitude', async ({ page }) => {
    // Get initial longitude
    const getLon = async () => {
      const text = await page.locator('p:has-text("Lat:")').textContent();
      const match = text?.match(/Lon: ([\d.-]+)/);
      return match ? parseFloat(match[1]) : null;
    };

    const initialLon = await getLon();

    // Click right
    await page.getByTestId('map-right').click();
    await page.waitForLoadState('domcontentloaded');

    const newLon = await getLon();
    expect(newLon).toBeGreaterThan(initialLon!);
  });

  test('zoom in increases zoom level', async ({ page }) => {
    const getZoom = async () => {
      const text = await page.locator('p:has-text("Lat:")').textContent();
      const match = text?.match(/Zoom: (\d+)/);
      return match ? parseInt(match[1]) : null;
    };

    const initialZoom = await getZoom();

    await page.getByTestId('map-zoom-in').click();
    await page.waitForLoadState('domcontentloaded');

    const newZoom = await getZoom();
    expect(newZoom).toBe(initialZoom! + 1);
  });

  test('zoom out decreases zoom level', async ({ page }) => {
    const getZoom = async () => {
      const text = await page.locator('p:has-text("Lat:")').textContent();
      const match = text?.match(/Zoom: (\d+)/);
      return match ? parseInt(match[1]) : null;
    };

    const initialZoom = await getZoom();

    await page.getByTestId('map-zoom-out').click();
    await page.waitForLoadState('domcontentloaded');

    const newZoom = await getZoom();
    expect(newZoom).toBe(initialZoom! - 1);
  });

  test('zoom respects minimum boundary (1)', async ({ page }) => {
    const getZoom = async () => {
      const text = await page.locator('p:has-text("Lat:")').textContent();
      const match = text?.match(/Zoom: (\d+)/);
      return match ? parseInt(match[1]) : null;
    };

    // Zoom all the way out
    let zoom = await getZoom();
    while (zoom! > 1) {
      await page.getByTestId('map-zoom-out').click();
      await page.waitForLoadState('domcontentloaded');
      zoom = await getZoom();
    }

    // Try to zoom out more - should stay at 1
    await page.getByTestId('map-zoom-out').click();
    await page.waitForLoadState('domcontentloaded');
    expect(await getZoom()).toBe(1);
  });

  test('zoom respects maximum boundary (22)', async ({ page }) => {
    const getZoom = async () => {
      const text = await page.locator('p:has-text("Lat:")').textContent();
      const match = text?.match(/Zoom: (\d+)/);
      return match ? parseInt(match[1]) : null;
    };

    // Zoom all the way in
    let zoom = await getZoom();
    while (zoom! < 22) {
      await page.getByTestId('map-zoom-in').click();
      await page.waitForLoadState('domcontentloaded');
      zoom = await getZoom();
    }

    // Try to zoom in more - should stay at 22
    await page.getByTestId('map-zoom-in').click();
    await page.waitForLoadState('domcontentloaded');
    expect(await getZoom()).toBe(22);
  });
});