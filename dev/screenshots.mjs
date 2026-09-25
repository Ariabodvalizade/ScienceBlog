// Full-page screenshots of key pages at desktop, tablet and mobile widths.
//
//   NODE_PATH=$(npm root -g) node dev/screenshots.mjs [baseUrl] [filter]
//
// Output: dev/screenshots/<page>-<width>.png

import { mkdirSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { createRequire } from 'node:module';

const require = createRequire(import.meta.url);
const { chromium } = require('playwright');

const BASE = process.argv[2] || 'http://localhost:8080';
const FILTER = process.argv[3] || '';
const OUT = join(dirname(fileURLToPath(import.meta.url)), 'screenshots');
mkdirSync(OUT, { recursive: true });

const J = `${BASE}/djas`;
export const pages = {
  home: `${J}`,
  issue: `${J}/issue/current`,
  archive: `${J}/issue/archive`,
  article: `${J}/article/view/5`,
  'article-pdf-only': `${J}/article/view/2`,
  about: `${J}/about`,
  editorial: `${J}/about/editorialMasthead`,
  submissions: `${J}/about/submissions`,
  search: `${J}/search/search?query=heat`,
  login: `${J}/login`,
  register: `${J}/user/register`,
  announcements: `${J}/announcement`,
  notfound: `${J}/article/view/9999`,
};
const widths = (process.env.WIDTHS || '1440,834,390').split(',').map(Number);

const browser = await chromium.launch();
for (const [name, url] of Object.entries(pages)) {
  if (FILTER && !name.includes(FILTER)) continue;
  for (const width of widths) {
    const page = await browser.newPage({ viewport: { width, height: 900 }, deviceScaleFactor: 1 });
    await page.goto(url, { waitUntil: 'networkidle' });
    const file = join(OUT, `${name}-${width}.png`);
    // CLIP=<px> limits the capture height (useful for reviewing the top of long pages)
    const clip = process.env.CLIP ? { x: 0, y: Number(process.env.CLIP_Y || 0), width, height: Number(process.env.CLIP) } : undefined;
    await page.screenshot({ path: file, fullPage: true, clip });
    await page.close();
    console.log('✓', file);
  }
}
await browser.close();
