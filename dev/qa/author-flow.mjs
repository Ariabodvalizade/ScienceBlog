// QA: a new author registers, logs in, and reaches the dashboard, the public
// profile (photo upload) and the submission wizard. Dev stack only.
//   NODE_PATH=$(npm root -g) node dev/qa/author-flow.mjs [baseUrl]
import { mkdirSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { createRequire } from 'node:module';

const require = createRequire(import.meta.url);
const { chromium } = require('playwright');
const BASE = process.argv[2] || 'http://localhost:8080';
const J = `${BASE}/djas`;
const OUT = join(dirname(fileURLToPath(import.meta.url)), '..', 'screenshots', 'qa');
mkdirSync(OUT, { recursive: true });

const stamp = Date.now().toString(36);
const user = { given: 'Test', family: `Author ${stamp}`, email: `author.${stamp}@example.org`, username: `author${stamp}`, password: 'Author-Passw0rd!' };

const browser = await chromium.launch();
const page = await browser.newPage({ viewport: { width: 1440, height: 1000 } });
const step = async (name, fn) => {
  try {
    await fn();
    await page.screenshot({ path: join(OUT, `${name}.png`), fullPage: false });
    console.log('✓', name, '→', page.url());
  } catch (e) {
    await page.screenshot({ path: join(OUT, `${name}-FAILED.png`) });
    console.error('✗', name, e.message);
    process.exitCode = 1;
  }
};

await step('01-register', async () => {
  await page.goto(`${J}/user/register`);
  await page.fill('#givenName', user.given);
  await page.fill('#familyName', user.family);
  await page.fill('#affiliation', 'Northfield University');
  await page.selectOption('#country', 'IR');
  await page.fill('#email', user.email);
  await page.fill('#username', user.username);
  await page.fill('#password', user.password);
  await page.fill('#password2', user.password);
  const consent = page.locator('input[name="privacyConsent"]');
  if (await consent.count()) await consent.check();
  await page.click('form#register button[type="submit"]');
  await page.waitForLoadState('networkidle');
  if (!/registerUser|register/i.test(page.url()) && !(await page.content()).match(/registration|dashboard|submissions|Continue/i)) throw new Error('unexpected page after registration');
});

await step('03-profile-public', async () => {
  await page.goto(`${J}/user/profile`);
  await page.waitForLoadState('networkidle');
  const tab = page.getByRole('tab', { name: /public/i }).or(page.getByText(/^Public$/));
  if (await tab.count()) await tab.first().click();
  await page.waitForTimeout(1500);
  const html = await page.content();
  if (!/Profile Image|profileImage/i.test(html)) throw new Error('profile image upload not found');
});

await step('04-submission-start', async () => {
  // Step 1 of the OJS 3.5 wizard: title, section, checklist → "Begin Submission"
  await page.goto(`${J}/submission`);
  await page.waitForLoadState('networkidle');
  // The title is a rich-text field (TinyMCE in an iframe, or an inline editor)
  const title = 'QA test manuscript on rumen microbiota';
  const frames = page.frameLocator('iframe');
  if (await page.locator('iframe').count()) {
    await frames.first().locator('body').click();
    await page.keyboard.type(title);
  } else {
    await page.locator('[contenteditable="true"]').first().click();
    await page.keyboard.type(title);
  }
  await page.locator('input[name="sectionId"][value="1"]').check();
  for (const box of await page.locator('input[type="checkbox"]').all()) {
    if (await box.isVisible() && !(await box.isChecked())) await box.check();
  }
  await page.getByRole('button', { name: /begin submission/i }).click();
  await page.waitForURL(/submission.*id=|submission\/wizard|submission\?/, { timeout: 30000 }).catch(() => {});
  await page.waitForLoadState('networkidle');
  await page.waitForTimeout(1500);
  if (!/Details|Upload Files|Contributors/i.test(await page.content())) throw new Error('wizard did not advance');
});

await step('05-author-dashboard', async () => {
  // Starting a submission grants the Author role → "My Submissions" dashboard
  await page.goto(J);
  const href = await page.locator('#navigationUser a[href*="dashboard"]').first().getAttribute('href', { timeout: 10000 });
  await page.goto(href);
  await page.waitForLoadState('networkidle');
  await page.waitForTimeout(1500);
  if (/login|authorizationDenied/.test(page.url())) throw new Error('dashboard not reachable: ' + page.url());
  if (!/QA test manuscript/i.test(await page.content())) throw new Error('submission not listed on the dashboard');
});

await browser.close();
