// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * ERB Lite — Full Booking Flow Test
 *
 * Tests the complete customer journey:
 * Calendar → Player Selection → Details → Payment → Confirmation
 */

test.describe('Calendar Display', () => {

    test('Calendar loads and shows slots', async ({ page }) => {
        await page.goto('/detective/');

        // The calendar div has data-game-id attribute
        await expect(page.locator('[data-game-id]')).toBeVisible({ timeout: 15000 });

        // Loading spinner should disappear
        await expect(page.locator('.erb-calendar__loading')).toBeHidden({ timeout: 15000 });

        // Should have at least one slot
        await expect(page.locator('.erb-slot').first()).toBeVisible();

        console.log('✅ Calendar loaded successfully');
    });

    test('Prev/Next week navigation works', async ({ page }) => {
        await page.goto('/detective/');

        await expect(page.locator('[data-game-id]')).toBeVisible({ timeout: 15000 });
        await expect(page.locator('.erb-calendar__loading')).toBeHidden({ timeout: 15000 });

        // Click Next week button
        const nextBtn = page.locator('a.erb-btn').filter({ hasText: 'Next' }).first();
        await expect(nextBtn).toBeVisible();
        await nextBtn.click();

        // Wait for page to reload with new week
        await expect(page).toHaveURL(/eerb_week=1/, { timeout: 10000 });

        // Calendar should reload
        await expect(page.locator('[data-game-id]')).toBeVisible({ timeout: 15000 });
        await expect(page.locator('.erb-calendar__loading')).toBeHidden({ timeout: 15000 });

        console.log('✅ Week navigation works');
    });

});

test.describe('Full Booking Flow', () => {

    test('Customer can complete a booking end-to-end', async ({ page }) => {

        // ── Step 1: Visit Detective calendar page ─────────────────────────────
        await page.goto('/detective/');

        // Wait for calendar to load
        await expect(page.locator('[data-game-id]')).toBeVisible({ timeout: 15000 });
        await expect(page.locator('.erb-calendar__loading')).toBeHidden({ timeout: 15000 });

        // Find an available slot — navigate forward weeks if needed
        const MAX_WEEKS = 8;
        let availableSlot = null;

        for (let week = 0; week < MAX_WEEKS; week++) {
            await expect(page.locator('.erb-calendar__loading')).toBeHidden({ timeout: 10000 });
            const slots = page.locator('.erb-slot--available');
            if (await slots.count() > 0) {
                availableSlot = slots.first();
                break;
            }
            // No slots this week — try next week
            const nextBtn = page.locator('a.erb-btn').filter({ hasText: 'Next' }).first();
            await nextBtn.click();
        }

        // If no slot found within 8 weeks, skip gracefully
        if (!availableSlot) {
            test.skip(true, 'No available slots within 8 weeks — check booking horizon or game hours');
        }

        const slotTime = await availableSlot.textContent();
        await availableSlot.click();

        // ── Step 2: Booking page — Player selection ───────────────────────────
        await page.waitForURL('**/book**', { timeout: 10000 });
        await expect(page.locator('#erb-step-1')).toBeVisible({ timeout: 10000 });

        // Select 4 players
        const playerBtn = page.locator('.erb-player-btn[data-players="4"]');
        await expect(playerBtn).toBeVisible();
        await playerBtn.click();

        // Price box should appear
        await expect(page.locator('#erb-price-box')).toBeVisible({ timeout: 5000 });
        await expect(page.locator('#erb-price-total')).not.toBeEmpty();

        // Click Continue
        await page.locator('#erb-step1-next').click();

        // ── Step 3: Customer details ──────────────────────────────────────────
        await expect(page.locator('#erb-step-2')).toBeVisible({ timeout: 5000 });

        await page.locator('#erb-first-name').fill('Sarah');
        await page.locator('#erb-last-name').fill('Mitchell');
        await page.locator('#erb-email').fill('sarah.mitchell@example.com');
        await page.locator('#erb-mobile').fill('07700900123');

        // Click Continue to Payment
        await page.locator('#erb-step2-next').click();

        // ── Step 4: Payment ───────────────────────────────────────────────────
        await expect(page.locator('#erb-step-3')).toBeVisible({ timeout: 5000 });

        // Wait for Stripe iframe
        await page.waitForSelector('#erb-stripe-card-element iframe', { timeout: 15000 });
        const stripeFrame = page.frameLocator('#erb-stripe-card-element iframe').first();

        await stripeFrame.locator('[placeholder="Card number"]').fill('4242424242424242');
        await stripeFrame.locator('[placeholder="MM / YY"]').fill('1227');
        await stripeFrame.locator('[placeholder="CVC"]').fill('123');

        // Click Pay Now
        await page.locator('#erb-pay-btn').click();

        // ── Step 5: Booking confirmed ─────────────────────────────────────────
        await expect(page.locator('#erb-step-success')).toBeVisible({ timeout: 30000 });

        const bookingRef = page.locator('#erb-success-ref');
        await expect(bookingRef).toBeVisible();
        await expect(bookingRef).toContainText('ERB-');

        console.log('✅ Booking completed successfully');
        console.log('Slot:', slotTime?.trim());
        console.log('Ref:', await bookingRef.textContent());
    });

});
