// Demo peer review (dev only): sends demo submission #10 to review, invites
// the two demo reviewers, and has Helen Carter decline with a reason, a comment
// and two suggested reviewers (Review Decline Reasons plugin). Peter Lawson's
// request stays open so the decline form can be tried with his login.
//   NODE_PATH=$(npm root -g) node dev/seed/review-demo.mjs [baseUrl]
import { createRequire } from 'node:module';

const require = createRequire(import.meta.url);
const { chromium } = require('playwright');
const BASE = process.argv[2] || 'http://localhost:8080';
const J = `${BASE}/djas`;

const browser = await chromium.launch();

async function login(username, password) {
  const page = await (await browser.newContext({ viewport: { width: 1440, height: 1000 } })).newPage();
  await page.goto(`${J}/login`);
  await page.fill('input[name=username]', username);
  await page.fill('input[name=password]', password);
  await Promise.all([page.waitForNavigation(), page.click('form#login button[type=submit]')]);
  return page;
}
const settle = async (page, ms = 1500) => {
  await page.waitForLoadState('networkidle').catch(() => {});
  await page.waitForTimeout(ms);
};

// 1. Editor: send #10 to review and invite both reviewers
const editor = await login('admin', 'admin-dev-Password1');
await editor.goto(`${J}/dashboard/editorial?workflowSubmissionId=10`);
await settle(editor);
const send = editor.getByRole('button', { name: 'Send for Review' });
if (await send.count()) {
  await send.click();
  await settle(editor);
  await editor.getByRole('button', { name: 'Record Decision' }).click();
  await settle(editor, 2500);
  console.log('✓ #10 sent to review');
}
for (const reviewer of ['Peter Lawson', 'Helen Carter']) {
  await editor.goto(`${J}/dashboard/editorial?workflowSubmissionId=10`);
  await settle(editor);
  await editor.getByRole('button', { name: 'Add Reviewer' }).click();
  await settle(editor, 2000);
  const select = editor.getByRole('button', { name: `Select ${reviewer}` });
  if (!(await select.count())) {
    console.log('•', reviewer, 'already invited');
    continue;
  }
  await select.click();
  await settle(editor, 2500);
  await editor.locator('button:has-text("Add Reviewer")').last().click();
  await settle(editor, 3000);
  console.log('✓ invited', reviewer);
}

// 2. Reviewer: decline with reason, comment and suggestions
const reviewer = await login('helen.carter', 'reviewer-demo-Password1');
await reviewer.goto(`${J}/reviewer/submission/10`);
await settle(reviewer);
const decline = reviewer.getByRole('button', { name: /Decline Review Request/i }).first();
if (await decline.count()) {
  await decline.click();
  await settle(reviewer, 2000);
  await reviewer.check('input[name=declineReason][value=expertise]');
  await reviewer.fill('#declineComments', 'The manuscript focuses on rumen microbiology, which is outside my area. Dr Ahmed and Dr Novak work on methane mitigation in small ruminants and would be well placed to review it.');
  await reviewer.check('input[name=suggestAlternatives][value=yes]');
  const people = [
    ['Samir Ahmed', 'samir.ahmed@example.org', 'Institute of Animal Science, Eastbrook College'],
    ['Laura Novak', 'laura.novak@example.org', 'Faculty of Agriculture, Southgate University'],
  ];
  for (const [i, [name, email, affiliation]] of people.entries()) {
    await reviewer.locator('input[name="suggestName[]"]').nth(i).fill(name);
    await reviewer.locator('input[name="suggestEmail[]"]').nth(i).fill(email);
    await reviewer.locator('input[name="suggestAffiliation[]"]').nth(i).fill(affiliation);
  }
  await reviewer.locator('#declineReviewForm button[type=submit]').click();
  await settle(reviewer, 3000);
  console.log('✓ Helen Carter declined with a reason and 2 suggestions');
} else {
  console.log('• Helen Carter has no open request');
}

await browser.close();
