// Screenshots for the client handover guide (docs/handover/guide.html).
// Runs against the demo journal (local-demo) and writes JPEGs with numbered
// markers into docs/handover/shots/. The markers match the numbered steps in
// the guide. Dev only.
//
//   NODE_PATH=$(npm root -g) node docs/handover/capture.mjs [baseUrl] [shotId ...]
//
// Note: the "adm-wf-review" shot records a real "Send for Review" decision on
// demo submission #10, so run it on a demo you can reset (local-demo/reset.sh).
import { mkdirSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { createRequire } from 'node:module';

const require = createRequire(import.meta.url);
const { chromium } = require('playwright');

const [, , BASE = 'http://localhost:8080', ...only] = process.argv;
const SITE = 'https://raazpublishnp.com';
const J = `${BASE}/djas`;
const OUT = join(dirname(fileURLToPath(import.meta.url)), 'shots');
mkdirSync(OUT, { recursive: true });

const LOGINS = {
  admin: ['admin', 'admin-dev-Password1'],
  author: ['sarah.mitchell', 'author-demo-Password1'],
};

const settle = async (p, ms = 900) => {
  await p.waitForLoadState('networkidle').catch(() => {});
  await p.waitForTimeout(ms);
};
const go = (path) => async (p) => { await p.goto(J + path); await settle(p); };
// Tabs are role="tab" in some screens and plain buttons in others
const tab = (p, name) => p.getByRole('tab', { name, exact: true }).or(p.getByRole('button', { name, exact: true })).first();
const modalMenu = (p) => p.locator('[data-pc-name=panelmenu]').last();
const clickModalItem = (name) => async (p) => { await modalMenu(p).getByText(name, { exact: true }).click(); await settle(p, 1200); };

// m(n, target, side): target is a CSS selector, {text}, {role, name} or a function(page) → Locator
const m = (n, target, side = 'tl') => ({ n, target, side });

const SHOTS = [
  // ------------------------------------------------ public website
  { id: 'pub-home', as: null, h: 900, go: go(''), marks: [
    m(1, '.m-header__nav', 'tl'), m(2, '.m-header__user', 'tl'), m(3, '[data-m-open="m-search"]', 'bl'),
    m(4, '.m-home-hero__actions', 'tl'), m(5, { text: 'Current Issue' }, 'tl')] },
  { id: 'pub-article', as: null, h: 1000, go: async (p) => { await go('/article/view/5')(p); await p.evaluate(() => scrollTo(0, 150)); await p.waitForTimeout(300); }, marks: [
    m(1, '.m-article-hero__authors', 'tl'), m(2, '.m-article-left', 'tl'), m(3, '.m-abstract', 'tl'),
    m(4, (p) => p.locator('.m-article-right a').filter({ hasText: /pdf/i }).first(), 'tl'), m(5, { text: 'How to Cite' }, 'tl')] },
  { id: 'pub-cite', as: null, h: 900, go: async (p) => {
      await go('/article/view/5')(p);
      const x = p.locator('a.sr-xref').nth(3);
      await x.scrollIntoViewIfNeeded();
      await p.evaluate(() => scrollBy(0, 250));
      await x.hover(); await p.waitForTimeout(500);
    }, marks: [m(1, (p) => p.locator('a.sr-xref').nth(3), 'tl'), m(2, '#sr-popover', 'tr')] },
  { id: 'pub-pdf', as: null, h: 900, go: async (p) => {
      await go('/article/view/5')(p);
      await p.locator('.m-article-right a').filter({ hasText: /pdf/i }).first().click();
      await settle(p, 2500);
    }, marks: [] },
  { id: 'pub-archive', as: null, h: 900, go: go('/issue/archive'), marks: [] },
  { id: 'pub-authors', as: null, h: 900, go: go('/authors'), marks: [] },
  { id: 'pub-author', as: null, h: 900, go: async (p) => {
      await go('/authors')(p);
      await p.getByText('Sarah Mitchell').first().click();
      await settle(p);
    }, marks: [] },
  { id: 'pub-team', as: null, h: 900, go: go('/about/editorialMasthead'), marks: [] },
  { id: 'pub-login', as: null, h: 880, go: go('/login'), marks: [
    m(1, 'input[name=username]', 'tl'), m(2, 'input[name=password]', 'tl'),
    m(3, 'form#login button[type=submit]', 'tl'), m(4, { text: 'Forgot your password?' }, 'tr')] },
  { id: 'pub-register', as: null, h: 900, go: go('/user/register'), marks: [] },

  // ------------------------------------------------ admin panel
  { id: 'adm-home', as: 'admin', h: 1300, go: go('/workspace'), marks: [
    m(1, '#app-nav', 'tr'), m(2, '.ma-attention', 'tl'), m(3, '.ma-pipeline', 'tl'),
    m(4, (p) => p.locator('.ma-grid > .ma-panel').first(), 'tl'), m(5, '.ma-issue', 'tl'),
    m(6, (p) => p.locator('.ma-side > .ma-panel').last(), 'tl'), m(7, '.ma-siteLink', 'bl'),
    m(8, '.ma-hero__actions a', 'tl')] },
  { id: 'adm-submissions', as: 'admin', h: 900, go: go('/dashboard/editorial?currentViewId=active'), marks: [
    m(1, (p) => p.locator('#app-nav').getByText('Needs editor'), 'tr'), m(2, { role: 'button', name: 'Filters' }, 'tl'),
    m(3, 'input[type=search], .pkpSearch__input', 'tl'), m(4, { role: 'button', name: 'Assign Editor' }, 'tl'),
    m(5, (p) => p.getByRole('link', { name: 'View' }).first(), 'tr')] },
  { id: 'adm-wf-submission', as: 'admin', h: 900, go: go('/dashboard/editorial?workflowSubmissionId=9'), marks: [
    m(1, modalMenu, 'tl'), m(2, (p) => p.getByText('manuscript-101.pdf'), 'tl'),
    m(3, { role: 'button', name: 'Send for Review' }, 'tl'), m(4, { role: 'button', name: 'Decline Submission' }, 'tl'),
    m(5, { role: 'button', name: 'Assign' }, 'tr')] },
  { id: 'adm-decision', as: 'admin', h: 760, go: async (p) => {
      await go('/dashboard/editorial?workflowSubmissionId=9')(p);
      await p.getByRole('button', { name: 'Send for Review' }).click();
      await settle(p, 1500);
    }, marks: [
      m(1, (p) => p.getByText('Notify Authors', { exact: true }).first(), 'tl'),
      m(2, (p) => p.getByText('Subject:').first(), 'tl')] },
  { id: 'adm-wf-review', as: 'admin', h: 1180, go: async (p) => {
      // Demo #10 starts in the submission stage: send it to review first (changes the demo data)
      await go('/dashboard/editorial?workflowSubmissionId=10')(p);
      const send = p.getByRole('button', { name: 'Send for Review' });
      if (await send.count()) {
        await send.click(); await settle(p, 1500);
        await p.getByRole('button', { name: 'Record Decision' }).click(); await settle(p, 2500);
        await go('/dashboard/editorial?workflowSubmissionId=10')(p);
      }
    }, marks: [
    m(1, { role: 'button', name: 'Add Reviewer' }, 'tl'), m(2, { role: 'button', name: 'Request Revisions' }, 'tl'),
    m(3, { role: 'button', name: 'Accept Submission' }, 'tl'), m(4, { role: 'button', name: 'Decline Submission' }, 'tl')] },
  { id: 'adm-wf-copyedit', as: 'admin', h: 900, go: go('/dashboard/editorial?workflowSubmissionId=11'), marks: [
    m(1, { role: 'button', name: 'Send To Production' }, 'tl'), m(2, (p) => p.getByText('Margaret Ellison').first(), 'tl')] },
  { id: 'adm-galleys', as: 'admin', h: 900, go: async (p) => {
      await go('/dashboard/editorial?workflowSubmissionId=12')(p);
      await clickModalItem('Galleys')(p);
    }, marks: [
      m(1, (p) => modalMenu(p).getByText('Galleys', { exact: true }), 'tl'),
      m(2, (p) => p.getByRole('button', { name: /Add galley/i }).first(), 'tl'),
      m(3, { role: 'button', name: 'Schedule For Publication' }, 'tl')] },
  { id: 'adm-references', as: 'admin', h: 900, go: async (p) => {
      await go('/dashboard/editorial?workflowSubmissionId=5')(p);
      await clickModalItem('References')(p);
    }, marks: [m(1, (p) => modalMenu(p).getByText('References', { exact: true }), 'tl'), m(2, 'textarea', 'tl')] },
  { id: 'adm-issues', as: 'admin', h: 520, go: go('/manageIssues'), marks: [
    m(1, (p) => tab(p, 'Future Issues'), 'tl'), m(2, (p) => tab(p, 'Back Issues'), 'tr'),
    m(3, (p) => p.getByText('Create Issue', { exact: true }).first(), 'tl')] },
  { id: 'adm-create-issue', as: 'admin', h: 1000, go: async (p) => {
      await p.goto(J + '/manageIssues'); await settle(p);
      await p.getByText('Create Issue', { exact: true }).first().click();
      await settle(p, 1500);
    }, marks: [
      m(1, 'input[name=volume]', 'tl'), m(2, 'input[name=number]', 'tl'), m(3, 'input[name=year]', 'tl'),
      m(4, (p) => p.getByRole('button', { name: /Upload File/ }).first(), 'tl'), m(5, (p) => p.getByRole('button', { name: 'Save' }).last(), 'tl')] },
  { id: 'adm-back-issues', as: 'admin', h: 560, go: async (p) => {
      await p.goto(J + '/manageIssues'); await settle(p);
      await tab(p, 'Back Issues').click(); await settle(p, 1500);
    }, marks: [] },
  { id: 'adm-users', as: 'admin', h: 900, go: go('/management/settings/access'), marks: [
    m(1, { role: 'button', name: 'Invite to a role' }, 'tl'), m(2, (p) => p.getByPlaceholder(/user's name/), 'tl'),
    m(3, (p) => p.getByText('Margaret Ellison').first(), 'tl'), m(4, (p) => p.locator('table tbody tr').nth(1).locator('button').last(), 'tr')] },
  { id: 'adm-roles', as: 'admin', h: 900, go: async (p) => {
      await go('/management/settings/access')(p);
      await tab(p, 'Roles').click(); await settle(p, 1500);
    }, marks: [] },
  { id: 'adm-theme', as: 'admin', h: 900, go: go('/management/settings/website'), marks: [
      m(1, (p) => p.getByText('Accent colour').first(), 'tl'), m(2, (p) => p.getByText('Home page sections').first(), 'tl'),
      m(3, (p) => p.getByText('Tagline', { exact: true }).first(), 'tl'), m(4, (p) => p.getByText('Indexing and membership logos').first(), 'tl'),
      m(5, (p) => p.getByRole('button', { name: 'Save' }).first(), 'tl')],
    clip: async (p) => {
      const top = await p.getByText('Accent colour').first().boundingBox();
      const save = await p.getByRole('button', { name: 'Save' }).first().boundingBox();
      const y = top.y + (await p.evaluate(() => scrollY)) - 40;
      return { x: 264, y, width: 1440 - 264, height: save.y + (await p.evaluate(() => scrollY)) + save.height + 40 - y };
    } },
  { id: 'adm-menus', as: 'admin', h: 900, go: async (p) => {
      await go('/management/settings/website')(p);
      await tab(p, 'Setup').click(); await settle(p, 800);
      await tab(p, 'Navigation').click(); await settle(p, 1500);
    }, marks: [] },
  { id: 'adm-static', as: 'admin', h: 700, go: async (p) => {
      await go('/management/settings/website')(p);
      await tab(p, 'Static Pages').click(); await settle(p, 1500);
    }, marks: [m(1, (p) => p.getByRole('link', { name: /Add Static Page/i }).first(), 'tl')] },
  { id: 'adm-announcements', as: 'admin', h: 640, go: go('/management/settings/announcements'), marks: [
    m(1, { role: 'button', name: 'Add Announcement' }, 'tl'), m(2, (p) => p.getByRole('button', { name: 'Edit' }).first(), 'tl')] },
  { id: 'adm-journal', as: 'admin', h: 900, go: go('/management/settings/context'), marks: [] },
  { id: 'adm-emails', as: 'admin', h: 900, go: async (p) => {
      await go('/management/settings/workflow')(p);
      await tab(p, 'Emails').click(); await settle(p, 1500);
    }, marks: [] },
  { id: 'adm-dois', as: 'admin', h: 900, go: go('/dois'), marks: [] },
  { id: 'adm-stats', as: 'admin', h: 900, go: go('/stats/publications/publications'), marks: [] },
  { id: 'adm-password', as: 'admin', h: 700, go: go('/user/profile#changePassword'), marks: [
    m(1, 'input[name=oldPassword]', 'tl'), m(2, 'input[name=password]', 'tl'), m(3, 'input[name=password2]', 'tl'),
    m(4, (p) => p.locator('#changePasswordForm button[type=submit], form button:has-text("Save")').last(), 'tl')] },
  { id: 'adm-cache', as: 'admin', h: 700, go: async (p) => { await p.goto(`${BASE}/index/admin`); await settle(p); }, marks: [
    m(1, (p) => p.getByText('Delete Data Caches').first(), 'tl'), m(2, (p) => p.getByText('Delete Template Cache').first(), 'bl')] },

  // ------------------------------------------------ author panel
  { id: 'aut-home', as: 'author', h: 760, go: go('/workspace'), marks: [
    m(1, '.ma-hero__actions a', 'tl'), m(2, (p) => p.locator('.ma-grid > .ma-panel').first().locator('.ma-list'), 'tl'),
    m(3, '.ma-profile .ma-checks', 'tl'), m(4, (p) => p.getByText('View your author page'), 'bl')] },
  { id: 'aut-submit', as: 'author', h: 1100, go: go('/submission'), marks: [] },
  { id: 'aut-profile', as: 'author', h: 760, go: go('/user/profile#publicProfile'), marks: [
    m(1, (p) => p.getByText('Profile Image').first(), 'tl'), m(2, (p) => p.getByRole('button', { name: /Upload File/ }).first(), 'tr')] },
];

async function resolve(p, target) {
  if (typeof target === 'function') return target(p);
  if (typeof target === 'string') return p.locator(target).first();
  if (target.role) return p.getByRole(target.role, { name: target.name, exact: true }).first();
  return p.getByText(target.text, { exact: true }).first();
}

// The guide is for the live site: show its address instead of the demo's
async function scrub(p) {
  await p.evaluate((site) => {
    const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
    for (let n = walker.nextNode(); n; n = walker.nextNode()) {
      if (n.nodeValue.includes('localhost')) {
        n.nodeValue = n.nodeValue.replace(/https?:\/\/localhost(:\d+)?/g, site).replace(/localhost(:\d+)?/g, site.replace(/^https?:\/\//, ''));
      }
    }
  }, SITE);
}

async function drawMarks(p, marks, id) {
  const boxes = [];
  for (const mk of marks) {
    try {
      const loc = await resolve(p, mk.target);
      const bb = await loc.boundingBox({ timeout: 4000 });
      if (!bb) throw new Error('not visible');
      boxes.push({ ...bb, n: mk.n, side: mk.side });
    } catch (e) {
      console.warn(`  ! ${id}: marker ${mk.n} not found (${e.message.split('\n')[0]})`);
    }
  }
  await p.evaluate((boxes) => {
    const layer = document.createElement('div');
    layer.id = 'hg-layer';
    layer.style.cssText = 'position:absolute;left:0;top:0;width:0;height:0;z-index:2147483647;pointer-events:none';
    for (const b of boxes) {
      const x = b.x + scrollX;
      const y = b.y + scrollY;
      const ring = document.createElement('div');
      ring.style.cssText = `position:absolute;left:${x - 4}px;top:${y - 4}px;width:${b.width + 8}px;height:${b.height + 8}px;border:2.5px solid #d9480f;border-radius:8px;box-shadow:0 0 0 4px rgba(217,72,15,.16)`;
      layer.appendChild(ring);
      // Badges sit just outside the frame so they never cover the label
      const pos = {
        tl: [x - 32, y - 14], tr: [x + b.width + 4, y - 14],
        bl: [x - 32, y + b.height - 14], br: [x + b.width + 4, y + b.height - 14],
      }[b.side] || [x - 32, y - 14];
      pos[0] = Math.min(Math.max(2, pos[0]), scrollX + innerWidth - 32);
      const dot = document.createElement('div');
      dot.textContent = b.n;
      dot.style.cssText = `position:absolute;left:${pos[0]}px;top:${Math.max(2, pos[1])}px;width:28px;height:28px;border-radius:50%;background:#d9480f;color:#fff;font:700 15px/28px system-ui,-apple-system,Segoe UI,sans-serif;text-align:center;box-shadow:0 0 0 2.5px #fff,0 2px 6px rgba(0,0,0,.35)`;
      layer.appendChild(dot);
    }
    document.body.appendChild(layer);
  }, boxes);
}

const browser = await chromium.launch();
const pages = {};
async function pageFor(as) {
  const key = as || 'public';
  if (pages[key]) return pages[key];
  const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 }, deviceScaleFactor: 1 });
  const p = await ctx.newPage();
  p.on('pageerror', (e) => console.warn('  JS error:', e.message));
  if (as) {
    const [user, pass] = LOGINS[as];
    await p.goto(`${J}/login`);
    await p.fill('input[name=username]', user);
    await p.fill('input[name=password]', pass);
    await Promise.all([p.waitForNavigation(), p.click('form#login button[type=submit]')]);
    await settle(p);
  }
  pages[key] = p;
  return p;
}

for (const shot of SHOTS) {
  if (only.length && !only.includes(shot.id)) continue;
  const p = await pageFor(shot.as);
  await p.setViewportSize({ width: 1440, height: shot.h });
  try {
    await shot.go(p);
    await scrub(p);
    await drawMarks(p, shot.marks, shot.id);
    const clip = shot.clip ? await shot.clip(p) : undefined;
    await p.screenshot({ path: join(OUT, `${shot.id}.jpg`), type: 'jpeg', quality: 84, fullPage: !!clip, clip });
    await p.evaluate(() => document.getElementById('hg-layer')?.remove());
    if (shot.after) await shot.after(p);
    console.log('✓', shot.id);
  } catch (e) {
    console.warn('✗', shot.id, e.message.split('\n')[0]);
  }
}
await browser.close();
