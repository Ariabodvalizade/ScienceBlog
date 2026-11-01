// Read-only smoke test: node dev/qa/reference-layout.mjs https://journal.example/djas
import assert from 'node:assert/strict';
import { createRequire } from 'node:module';
import { mkdir } from 'node:fs/promises';
import { fileURLToPath } from 'node:url';
const { chromium } = createRequire(import.meta.url)('playwright');
const url = process.argv[2] || 'http://localhost:8080/djas';
const output = new URL('../screenshots/', import.meta.url);
await mkdir(output, { recursive: true });
const browser = await chromium.launch({ executablePath: '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome' });
try {
  for (const width of [390, 768, 1440]) {
    const page = await browser.newPage({ viewport: { width, height: 1000 } });
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    const response = await page.goto(url, { waitUntil: 'networkidle' });
    assert.equal(response.status(), 200);
    const text = await page.locator('.m-reference-home').innerText();
    assert(!text.includes('Array'), 'Localized journal metadata must be plain text');
    assert(!text.includes('##'), 'All translations must resolve');
    assert.match(text, /Frequency: Not announced/);
    assert.equal(await page.locator('.m-reference-statistics p').count(), 7);
    assert.match(text, /Pending verification/);
    assert.match(text, /Template not yet available/);
    assert.equal(await page.locator('.m-reference-action--pending a').count(), 0);
    assert.equal(await page.locator('[role="main"] [role="main"]').count(), 0);
    assert(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), `Horizontal overflow at ${width}px`);
    const main = await page.locator('.m-reference-main').boundingBox();
    const sidebar = await page.locator('.m-reference-sidebar').boundingBox();
    assert(width > 1024 ? sidebar.x >= main.x + main.width : sidebar.y >= main.y + main.height, `Sidebar placement at ${width}px`);
    if (width <= 1024) {
      const footer = await page.locator('.m-footer__grid').boundingBox();
      const identity = await page.locator('.m-footer__identity').boundingBox();
      assert(Math.abs(identity.width - footer.width) < 1, 'Footer identity must span the grid without creating an implicit column');
    }
    await page.screenshot({ path: fileURLToPath(new URL(`reference-${width}.png`, output)), fullPage: true });
    if (width === 390) {
      await page.locator('[data-m-open="m-drawer"]').click();
      assert(await page.locator('#m-drawer').isVisible());
      await page.keyboard.press('Escape');
      assert(await page.locator('#m-drawer').isHidden());
    }
    await page.locator('[data-m-open="m-search"]').click();
    assert(await page.locator('#m-search-query').isVisible());
    await page.keyboard.press('Escape');
    assert(await page.locator('#m-search').isHidden());
    assert.deepEqual(errors, [], 'Browser JavaScript errors');
    console.log(`PASS ${width}px: content, layout, navigation, search, no JS errors`);
    await page.close();
  }
} finally {
  await browser.close();
}
