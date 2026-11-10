/**
 * Playwright E2E Test untuk ZAYTRIX
 * Alur: Register -> Login -> Dashboard -> Market -> Add Portfolio
 * 
 * Jalankan: npx playwright test tests/e2e/user-flow.spec.js
 * Catatan: Playwright butuh browser yang mungkin tidak tersedia di Termux.
 * File ini dibuat sebagai referensi untuk CI/CD di GitHub Actions.
 */

import { test, expect } from '@playwright/test';

test.describe('ZAYTRIX User Flow', () => {
    const baseURL = process.env.BASE_URL || 'http://localhost:3000';

    test('Register -> Login -> Dashboard -> Market -> Add Portfolio', async ({ page }) => {
        // 1. Register
        await page.goto(`${baseURL}/register`);
        await page.fill('input[name="name"]', 'Test User');
        await page.fill('input[name="email"]', `test${Date.now()}@example.com`);
        await page.fill('input[name="password"]', 'password123');
        await page.fill('input[name="password_confirmation"]', 'password123');
        await page.click('button[type="submit"]');
        
        // Wait for redirect to dashboard
        await page.waitForURL('**/dashboard');
        await expect(page).toHaveURL(/.*dashboard/);

        // 2. Verify Dashboard loads
        await expect(page.locator('h1')).toContainText('Dashboard');
        await expect(page.locator('text=Portfolio Value')).toBeVisible();

        // 3. Navigate to Market
        await page.click('a[href="/market"]');
        await page.waitForURL('**/market');
        await expect(page.locator('text=Market Overview')).toBeVisible();
        
        // Check market data loads (wait for table)
        await page.waitForSelector('table', { timeout: 10000 });
        const marketRows = await page.locator('tbody tr').count();
        expect(marketRows).toBeGreaterThan(0);

        // 4. Navigate to Portfolio
        await page.click('a[href="/portfolio"]');
        await page.waitForURL('**/portfolio');
        await expect(page.locator('text=Portfolio')).toBeVisible();

        // 5. Add a transaction (Buy BTC)
        await page.fill('select[name="symbol"]', 'BTC');
        await page.fill('select[name="type"]', 'buy');
        await page.fill('input[name="amount"]', '0.1');
        await page.fill('input[name="price"]', '50000');
        await page.fill('input[name="fee"]', '5');
        await page.click('button:has-text("Tambah Transaksi")');

        // Wait for success
        await page.waitForSelector('text=Transaksi berhasil', { timeout: 5000 });

        // 6. Verify portfolio shows the position
        await expect(page.locator('text=BTC')).toBeVisible();
        await expect(page.locator('text=0.1')).toBeVisible();

        // 7. Navigate to Settings
        await page.click('a[href="/settings"]');
        await page.waitForURL('**/settings');
        await expect(page.locator('text=Settings')).toBeVisible();

        // 8. Test API Health tab
        await page.click('button:has-text("API Health")');
        await expect(page.locator('text=9router')).toBeVisible();
    });

    test('Paper Trading: Open Long Position', async ({ page }) => {
        const email = `paper${Date.now()}@example.com`;
        
        await page.goto(`${baseURL}/register`);
        await page.fill('input[name="name"]', 'Paper Trader');
        await page.fill('input[name="email"]', email);
        await page.fill('input[name="password"]', 'password123');
        await page.fill('input[name="password_confirmation"]', 'password123');
        await page.click('button[type="submit"]');
        await page.waitForURL('**/dashboard');

        await page.click('a[href="/paper-trading"]');
        await page.waitForURL('**/paper-trading');
        await expect(page.locator('text=Paper Trading')).toBeVisible();

        // Open long position
        await page.fill('select[name="symbol"]', 'BTC');
        await page.fill('select[name="side"]', 'long');
        await page.fill('input[name="entry_price"]', '50000');
        await page.fill('input[name="sl_price"]', '48000');
        await page.fill('input[name="tp_price"]', '55000');
        await page.fill('input[name="amount"]', '1');
        await page.click('button:has-text("Buka Posisi")');

        await page.waitForSelector('text=Posisi berhasil dibuka', { timeout: 5000 });
        
        // Verify position shows in table
        await expect(page.locator('text=BTC')).toBeVisible();
        await expect(page.locator('text=long')).toBeVisible();
        await expect(page.locator('text=2.5')).toBeVisible(); // RRR
    });

    test('News: Filter High Impact', async ({ page }) => {
        await page.goto(`${baseURL}/news`);
        await page.waitForURL('**/news');
        await expect(page.locator('text=Crypto News')).toBeVisible();

        // Filter high impact
        await page.selectOption('select', 'high');
        
        // Check high impact badge exists (if any)
        const highImpactCount = await page.locator('text=HIGH IMPACT').count();
        // Just verify filter works without error
    });

    test('Settings: Change Theme', async ({ page }) => {
        const email = `theme${Date.now()}@example.com`;
        
        await page.goto(`${baseURL}/register`);
        await page.fill('input[name="name"]', 'Theme Tester');
        await page.fill('input[name="email"]', email);
        await page.fill('input[name="password"]', 'password123');
        await page.fill('input[name="password_confirmation"]', 'password123');
        await page.click('button[type="submit"]');
        await page.waitForURL('**/dashboard');

        await page.click('a[href="/settings"]');
        await page.waitForURL('**/settings');
        await expect(page.locator('text=Settings')).toBeVisible();

        await page.click('button:has-text("Tema")');
        await expect(page.locator('text=Light')).toBeVisible();
        await expect(page.locator('text=Dark')).toBeVisible();

        await page.click('button:has-text("Dark")');
        await expect(page.locator('button[style*="border-blue-500"]').locator('text=Dark')).toBeVisible();
    });
});
