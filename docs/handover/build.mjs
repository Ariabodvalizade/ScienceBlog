// Build the client handover guide PDF from guide.html and shots/*.jpg.
//   NODE_PATH=$(npm root -g) node docs/handover/build.mjs
// Needs Playwright (Chromium) and Python with pypdf (merges the cover and the
// body, and reads chapter page numbers for the table of contents).
import { execFileSync } from 'node:child_process';
import { readFileSync, writeFileSync, rmSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { createRequire } from 'node:module';

const require = createRequire(import.meta.url);
const { chromium } = require('playwright');
const HERE = dirname(fileURLToPath(import.meta.url));

// ------------------------------------------------ edit these for each handover
const CONFIG = {
  SITE: 'https://raazpublishnp.com',
  USER: 'admin',
  PASS: 'admin-dev-Password1',
  DATE: 'September 2026',
  OUT: 'Journal-Website-Guide.pdf',
};
CONFIG.SITE_HOST = CONFIG.SITE.replace(/^https?:\/\//, '').replace(/\/$/, '');

const TMP_HTML = join(HERE, '_build.html');
const COVER = join(HERE, '_cover.pdf');
const BODY = join(HERE, '_body.pdf');

function renderHtml(pageNumbers = {}) {
  let html = readFileSync(join(HERE, 'guide.html'), 'utf8');
  html = html.replace(/\{\{(\w+)\}\}/g, (m, key) => (key in CONFIG ? CONFIG[key] : m));
  html = html.replace(/<span class="page" data-page="(\w+)"><\/span>/g, (m, id) => `<span class="page" data-page="${id}">${pageNumbers[id] ?? ''}</span>`);
  writeFileSync(TMP_HTML, html);
}

const footer = `<div style="width:100%;padding:0 16mm;display:flex;justify-content:space-between;font:7.5pt 'Helvetica Neue',Arial,sans-serif;color:#6b7473">
  <span>Journal Website Guide · ${CONFIG.SITE_HOST}</span><span><span class="pageNumber"></span> / <span class="totalPages"></span></span></div>`;

async function print(page, bodyClass, path, options) {
  await page.goto('file://' + TMP_HTML);
  await page.evaluate((cls) => document.body.classList.add(cls), bodyClass);
  await page.evaluate(() => document.fonts.ready);
  await page.evaluate(() => Promise.all([...document.images].map((i) => i.decode().catch(() => {}))));
  await page.pdf({ path, format: 'A4', printBackground: true, ...options });
}

function chapterPages() {
  const py = `
import json, re, sys
from pypdf import PdfReader
pages = {}
for i, page in enumerate(PdfReader(sys.argv[1]).pages, start=1):
    # letter-spaced headings extract as "C H A P T E R  0 1"
    for m in re.finditer(r'C ?H ?A ?P ?T ?E ?R\\s+(\\d) ?(\\d)', page.extract_text() or ''):
        pages.setdefault('ch' + str(int(m.group(1) + m.group(2))), i)
print(json.dumps(pages))`;
  return JSON.parse(execFileSync('python3', ['-c', py, BODY]).toString());
}

const browser = await chromium.launch();
const page = await browser.newPage();

// Pass 1: body, to find where each chapter starts
renderHtml();
const bodyOptions = {
  margin: { top: '16mm', bottom: '18mm', left: '16mm', right: '16mm' },
  displayHeaderFooter: true, headerTemplate: '<span></span>', footerTemplate: footer,
  outline: true, tagged: true,
};
await print(page, 'body-only', BODY, bodyOptions);
const pages = chapterPages();
console.log('chapters start on pages', pages);

// Pass 2: body with page numbers in the contents, and the full-bleed cover
renderHtml(pages);
await print(page, 'body-only', BODY, bodyOptions);
await print(page, 'cover-only', COVER, { margin: { top: 0, bottom: 0, left: 0, right: 0 }, pageRanges: '1' });
await browser.close();

const out = join(HERE, CONFIG.OUT);
execFileSync('python3', ['-c', `
import sys
from pypdf import PdfWriter, PdfReader
w = PdfWriter()
w.append(PdfReader(sys.argv[1]))
w.append(PdfReader(sys.argv[2]))
w.add_metadata({'/Title': 'Journal Website Guide', '/Subject': 'Administrator and editor guide for ' + sys.argv[4], '/Author': sys.argv[4]})
w.page_mode = '/UseOutlines'
with open(sys.argv[3], 'wb') as f:
    w.write(f)
`, COVER, BODY, out, CONFIG.SITE_HOST]);
for (const f of [TMP_HTML, COVER, BODY]) rmSync(f, { force: true });
console.log('✓ Wrote', out);
