// Create and configure the demo journal through the OJS web API (dev only).
//
//   node dev/seed/seed.mjs [baseUrl]
//
// Logs in as the site admin, creates the journal, applies settings, activates
// the Meridian theme and enables plugins. The native XML import itself is run
// by dev/seed.sh inside the container.

import { announcements, journal } from './content.mjs';

const BASE = process.argv[2] || 'http://localhost:8080';
const USER = process.env.ADMIN_USER || 'admin';
const PASS = process.env.ADMIN_PASSWORD || 'admin-dev-Password1';

const jar = new Map();
function storeCookies(res) {
  for (const c of res.headers.getSetCookie?.() || []) {
    const [pair] = c.split(';');
    const [k, v] = pair.split('=');
    jar.set(k.trim(), v);
  }
}
const cookieHeader = () => [...jar].map(([k, v]) => `${k}=${v}`).join('; ');

async function req(path, { method = 'GET', body, headers = {}, form } = {}) {
  const opts = { method, headers: { Cookie: cookieHeader(), ...headers }, redirect: 'manual' };
  if (form) {
    opts.body = new URLSearchParams(form);
    opts.headers['Content-Type'] = 'application/x-www-form-urlencoded';
  } else if (body !== undefined) {
    opts.body = JSON.stringify(body);
    opts.headers['Content-Type'] = 'application/json';
  }
  const res = await fetch(BASE + path, opts);
  storeCookies(res);
  return res;
}

async function csrf(path) {
  const html = await (await req(path)).text();
  const m = html.match(/name="csrf-token" content="([^"]+)"/) || html.match(/name="csrfToken" value="([^"]+)"/);
  if (!m) throw new Error(`No CSRF token on ${path}`);
  return m[1];
}

async function api(path, method, body, token, ctx = 'index') {
  const res = await req(`/${ctx}/api/v1${path}`, { method, body, headers: { 'X-Csrf-Token': token } });
  const text = await res.text();
  if (!res.ok) throw new Error(`${method} ${path} → ${res.status}: ${text.slice(0, 500)}`);
  return text ? JSON.parse(text) : null;
}

// 1. Log in
const loginToken = await csrf('/index/login');
const login = await req('/index/login/signIn', {
  method: 'POST',
  form: { csrfToken: loginToken, username: USER, password: PASS, remember: '1' },
});
if (![302, 303].includes(login.status)) throw new Error(`Login failed (${login.status})`);
console.log('✓ Logged in as', USER);

const token = await csrf('/index/admin');

// 2. Create the journal (or reuse it)
const existing = await api('/contexts?isEnabled=true', 'GET', undefined, token);
let context = existing.items.find((c) => c.urlPath === journal.path);
if (!context) {
  context = await api('/contexts', 'POST', {
    name: { en: journal.name },
    acronym: { en: journal.acronym },
    abbreviation: { en: journal.abbreviation },
    urlPath: journal.path,
    primaryLocale: 'en',
    supportedLocales: ['en'],
    enabled: true,
    contactName: journal.contactName,
    contactEmail: journal.contactEmail,
    country: 'GB',
  }, token);
  console.log('✓ Created journal', context.id, context.urlPath);
} else {
  console.log('• Journal exists', context.id, context.urlPath);
}

// 3. Plugins (the theme must be enabled before it can be selected)
const journalToken = await csrf(`/${journal.path}/management/settings/website`);
const plugins = [
  ['themes', 'meridianplugin'],
  ['generic', 'scholarlyreaderplugin'],
  ['generic', 'authorpagesplugin'],
  ['generic', 'googlescholarplugin'],
  ['generic', 'dublincoremetaplugin'],
  ['generic', 'pdfjsviewerplugin'],
  ['generic', 'htmlarticlegalleyplugin'],
  ['generic', 'citationstylelanguageplugin'],
  ['generic', 'staticpagesplugin'],
  ['generic', 'webfeedplugin'],
  ['generic', 'recommendbyauthorplugin'],
  ['generic', 'jatstemplateplugin'],
  ['oaiMetadataFormats', 'OAIMetadataFormatPlugin_JATS'],
];
for (const [category, plugin] of plugins) {
  const res = await req(
    `/${journal.path}/$$$call$$$/grid/settings/plugins/settings-plugin-grid/enable?plugin=${plugin}&category=${category}`,
    { method: 'POST', form: { csrfToken: journalToken }, headers: { 'X-Requested-With': 'XMLHttpRequest' } },
  );
  const out = await res.text();
  console.log(res.ok && out.includes('"status":true') ? '✓' : '!', 'enable', plugin, res.ok ? '' : res.status);
}

// 4. Settings
await api(`/contexts/${context.id}`, 'PUT', {
  description: { en: journal.description },
  about: { en: journal.about },
  onlineIssn: journal.onlineIssn,
  publisherInstitution: journal.publisher,
  contactName: journal.contactName,
  contactEmail: journal.contactEmail,
  supportName: 'Technical Support',
  supportEmail: 'support@example.org',
  licenseUrl: 'https://creativecommons.org/licenses/by/4.0/',
  copyrightHolderType: 'author',
  copyrightYearBasis: 'issue',
  themePluginPath: 'meridian',
  itemsPerPage: 25,
  numAnnouncementsHomepage: 3,
  enableAnnouncements: true,
  keywords: 'request',
  citations: 'request',
  enableOai: true,
}, token, journal.path);
console.log('✓ Journal settings applied, theme = meridian');

// 5. Theme options
await api(`/contexts/${context.id}/theme`, 'PUT', {
  themePluginPath: 'meridian',
  tagline: { en: journal.tagline },
  indexingLogos: [
    'Google Scholar | | https://scholar.google.com',
    'Crossref | | https://www.crossref.org',
    'ORCID | | https://orcid.org',
    'DOAJ (in preparation) | | https://doaj.org',
    'PKP Preservation Network | | https://pkp.sfu.ca/pkp-pn/',
  ].join('\n'),
}, token, journal.path).catch((e) => console.warn('! Theme options:', e.message));

// 6. Announcements (skipped when some already exist)
try {
  const existingNews = await api('/announcements', 'GET', undefined, token, journal.path);
  if (!existingNews.itemsMax) {
    for (const a of announcements) {
      await api('/announcements', 'POST', {
        title: { en: a.title },
        descriptionShort: { en: a.short },
        description: { en: a.short },
      }, token, journal.path);
    }
    console.log('✓ Announcements created');
  }
} catch (e) {
  console.warn('! Announcements:', e.message);
}

console.log(`\nJournal: ${BASE}/${journal.path}`);
