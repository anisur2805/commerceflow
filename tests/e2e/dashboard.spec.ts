/**
 * E2E: Dashboard loads and shows metrics.
 *
 * Requires a running WordPress + WooCommerce (HPOS) instance with
 * CommerceFlow activated. Set WP_BASE_URL to the wp-admin URL.
 *
 * @package CommerceFlow
 */
import { test, expect } from "@playwright/test";

test.describe("CommerceFlow Dashboard", () => {
  test.beforeEach(async ({ page }) => {
    await page.goto("/admin.php?page=commerceflow");
  });

  test("SPA shell renders navigation", async ({ page }) => {
    await expect(page.locator("#commerceflow-root")).toBeVisible({
      timeout: 10000,
    });
    await expect(
      page.getByRole("navigation", { name: /commerceflow navigation/i }),
    ).toBeVisible();
  });

  test("Dashboard page loads metric cards", async ({ page }) => {
    await page.waitForSelector("text=/Dashboard/i", { timeout: 10000 });
    const cards = page.locator("h3");
    await expect(cards.first()).toBeVisible();
  });

  test("Settings page shows cache TTL input field", async ({ page }) => {
    await page.click("text=Settings");
    await page.waitForSelector("text=/Cache TTL/i", { timeout: 10000 });
    const ttlInput = page.locator('input[type="number"]');
    await expect(ttlInput).toBeVisible();
  });

  test("Settings save shows success toast", async ({ page }) => {
    await page.click("text=Settings");
    await page.waitForSelector("text=/Save Settings/i", { timeout: 10000 });

    const saveButton = page.getByRole("button", { name: /save settings/i });
    await saveButton.click();

    // Toast/notice should appear after save attempt.
    await expect(page.getByText(/Settings saved/i)).toBeVisible({
      timeout: 10000,
    });
  });
});
