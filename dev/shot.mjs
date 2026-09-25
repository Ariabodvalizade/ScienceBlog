import { createRequire } from 'node:module';
const require = createRequire(import.meta.url);
const { chromium } = require('playwright');
const [,, url, out, width = '1440', action = ''] = process.argv;
const b = await chromium.launch();
const p = await b.newPage({ viewport: { width: Number(width), height: 1100 } });
await p.goto(url, { waitUntil: 'networkidle' });
if (action.startsWith('#')) { await p.locator(action).scrollIntoViewIfNeeded(); await p.evaluate(s => document.querySelector(s).scrollIntoView({block:'start'}), action); await p.waitForTimeout(300); }
if (action === 'hover-xref') { const l = p.locator('a.sr-xref').nth(3); await l.scrollIntoViewIfNeeded(); await p.evaluate(() => window.scrollBy(0, -300)); await l.hover(); await p.waitForTimeout(400); }
await p.screenshot({ path: out });
await b.close();
