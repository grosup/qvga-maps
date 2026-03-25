import { test, expect } from '@playwright/test';

test.describe('Search Functionality', () => {
  test.beforeEach(async ({ page }) => {
    await page.context().clearCookies();
    await page.goto('/');
  });

  test('valid search "Berlin" returns results', async ({ page }) => {
    // Enter search (uses data-test-id in HTML)
    await page.getByTestId('search-address').fill('Berlin');
    await page.getByTestId('search-submit').click();
    await page.waitForLoadState('domcontentloaded');

    // Should be on results page
    const heading = page.locator('h2:has-text("Search Results")');
    await expect(heading).toBeVisible();

    // Should have results (up to 5)
    const result0 = page.getByTestId('search-result-item-0');
    await expect(result0).toBeVisible();

    // Check "Back to Map" links exist
    await expect(page.getByTestId('search-result-back-header')).toBeVisible();
    await expect(page.getByTestId('search-result-back-footer')).toBeVisible();
  });

  test('empty search shows "No results" message', async ({ page }) => {
    // Submit empty
    await page.getByTestId('search-address').fill('');
    await page.getByTestId('search-submit').click();
    await page.waitForLoadState('domcontentloaded');

    // Should show no results message
    const noResults = page.locator('.error:has-text("No results found for")');
    await expect(noResults).toBeVisible();
  });

  test('invalid location shows "No results"', async ({ page }) => {
    await page.getByTestId('search-address').fill('xyz123nonexistent');
    await page.getByTestId('search-submit').click();
    await page.waitForLoadState('domcontentloaded');

    const noResults = page.locator('.error:has-text("xyz123nonexistent")');
    await expect(noResults).toBeVisible();
  });

  test('search with special characters works', async ({ page }) => {
    await page.getByTestId('search-address').fill('São Paulo');
    await page.getByTestId('search-submit').click();
    await page.waitForLoadState('domcontentloaded');

    // Should have results without garbled text
    const heading = page.locator('h2:has-text("Search Results")');
    await expect(heading).toBeVisible();

    const result0 = page.getByTestId('search-result-item-0');
    await expect(result0).toBeVisible();

    // Check that São Paulo appears properly (not mangled)
    const resultText = await result0.textContent();
    expect(resultText).toContain('São Paulo');
  });

  test('search returns maximum 5 results', async ({ page }) => {
    await page.getByTestId('search-address').fill('avenida de la paz');
    await page.getByTestId('search-submit').click();
    await page.waitForLoadState('domcontentloaded');

    // Check that we have items 0-4 (5 total)
    await expect(page.getByTestId('search-result-item-0')).toBeVisible();
    await expect(page.getByTestId('search-result-item-1')).toBeVisible();
    await expect(page.getByTestId('search-result-item-2')).toBeVisible();
    await expect(page.getByTestId('search-result-item-3')).toBeVisible();
    await expect(page.getByTestId('search-result-item-4')).toBeVisible();

    // Item 6 should NOT exist
    await expect(page.getByTestId('search-result-item-5')).not.toBeVisible();
  });

  test('complex address search returns relevant results', async ({ page }) => {
    await page.getByTestId('search-address').fill('Brandenburg Gate, Berlin');
    await page.getByTestId('search-submit').click();
    await page.waitForLoadState('domcontentloaded');

    const result0 = page.getByTestId('search-result-item-0');
    await expect(result0).toBeVisible();

    const resultText = await result0.textContent();
    // Should contain Brandenburg Gate or Pariser Platz
    expect(resultText!.toLowerCase()).toMatch(/brandenburg|pariser platz/);
  });
});