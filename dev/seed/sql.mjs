// Generate dev-only SQL for demo users on the editorial masthead and the
// policy static pages. Output: dev/out/demo.sql (applied by dev/seed.sh).
import { writeFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { board, inProgress, staticPages } from './content.mjs';

const OUT = join(dirname(fileURLToPath(import.meta.url)), '..', 'out', 'demo.sql');
const q = (s) => `'${String(s).replace(/\\/g, '\\\\').replace(/'/g, "''")}'`;
const lines = ['SET NAMES utf8mb4;', "SET @ctx = (SELECT journal_id FROM journals WHERE path = 'djas');"];

board.forEach((m, i) => {
  const id = 101 + i;
  const username = `${m.given}.${m.family}`.toLowerCase().normalize('NFKD').replace(/[^a-z.]/g, '');
  lines.push(
    `DELETE FROM user_user_groups WHERE user_id = ${id};`,
    `DELETE FROM user_settings WHERE user_id = ${id};`,
    `DELETE FROM users WHERE user_id = ${id};`,
    `INSERT INTO users (user_id, username, password, email, country, locales, date_registered, date_validated, disabled, inline_help) VALUES (${id}, ${q(username)}, '!disabled-demo-account', ${q(username + '@example.org')}, ${q(m.country)}, '[]', NOW(), NOW(), 0, 1);`,
    `INSERT INTO user_settings (user_id, locale, setting_name, setting_value) VALUES (${id}, 'en', 'givenName', ${q(m.given)}), (${id}, 'en', 'familyName', ${q(m.family)}), (${id}, 'en', 'affiliation', ${q(m.affiliation)})${m.bio ? `, (${id}, 'en', 'biography', ${q('<p>' + m.bio + '</p>')})` : ''};`,
    `INSERT INTO user_user_groups (user_group_id, user_id, date_start, masthead) SELECT ug.user_group_id, ${id}, '2026-01-01', 1 FROM user_groups ug JOIN user_group_settings s ON s.user_group_id = ug.user_group_id AND s.setting_name = 'name' AND s.locale = 'en' WHERE ug.context_id = @ctx AND s.setting_value = ${q(m.group)} LIMIT 1;`,
  );
});

// A registered author account with a profile photo (matches the demo author
// Sarah Mitchell by e-mail) to demonstrate author pages and the author panel.
// Demo login: sarah.mitchell / author-demo-Password1 (bcrypt hash below).
lines.push(
  'DELETE FROM user_user_groups WHERE user_id = 201;',
  'DELETE FROM user_settings WHERE user_id = 201;',
  'DELETE FROM users WHERE user_id = 201;',
  "INSERT INTO users (user_id, username, password, email, url, country, locales, date_registered, date_validated, disabled, inline_help) VALUES (201, 'sarah.mitchell', '$2y$10$3JRxr21Ax.2XAcCPOoPTpOBdVNZAYKtRqemwwHg1GP10gwoRD0xDW', 'sarah.mitchell@example.org', 'https://example.org/~mitchell', 'GB', '[]', NOW(), NOW(), 0, 1);",
  `INSERT INTO user_settings (user_id, locale, setting_name, setting_value) VALUES (201, 'en', 'givenName', 'Sarah'), (201, 'en', 'familyName', 'Mitchell'), (201, 'en', 'affiliation', 'Department of Animal Science, Northfield University'), (201, '', 'profileImage', ${q(JSON.stringify({ name: 'photo.png', uploadName: 'profileImage-201.png', width: 150, height: 150, dateUploaded: '2026-06-01 10:00:00' }))});`,
  "INSERT INTO user_user_groups (user_group_id, user_id, masthead) SELECT ug.user_group_id, 201, 0 FROM user_groups ug JOIN user_group_settings s ON s.user_group_id = ug.user_group_id AND s.setting_name = 'name' AND s.locale = 'en' WHERE ug.context_id = @ctx AND s.setting_value = 'Author' LIMIT 1;",
);

// Sarah sees her own articles (published and in progress) in My Submissions
const groupId = (name) => `(SELECT ug.user_group_id FROM user_groups ug JOIN user_group_settings s ON s.user_group_id = ug.user_group_id AND s.setting_name = 'name' AND s.locale = 'en' WHERE ug.context_id = @ctx AND s.setting_value = ${q(name)} LIMIT 1)`;
lines.push(
  'DELETE FROM stage_assignments WHERE user_id = 201;',
  `INSERT INTO stage_assignments (submission_id, user_group_id, user_id, date_assigned, recommend_only, can_change_metadata)
   SELECT DISTINCT p.submission_id, ${groupId('Author')}, 201, NOW(), 0, 1
   FROM authors a JOIN publications p ON p.publication_id = a.publication_id JOIN submissions s ON s.submission_id = p.submission_id
   WHERE a.email = 'sarah.mitchell@example.org' AND s.context_id = @ctx;`,
);

// Editors assigned to the manuscripts further along the workflow
inProgress.filter((m) => m.editor).forEach((m) => {
  lines.push(
    `INSERT INTO stage_assignments (submission_id, user_group_id, user_id, date_assigned, recommend_only, can_change_metadata)
     SELECT p.submission_id, ${groupId('Journal editor')}, u.user_id, NOW(), 0, 1
     FROM publication_settings ps JOIN publications p ON p.publication_id = ps.publication_id JOIN users u ON u.username = ${q(m.editor)}
     WHERE ps.setting_name = 'title' AND ps.setting_value = ${q(m.title)}
       AND NOT EXISTS (SELECT 1 FROM stage_assignments sa WHERE sa.submission_id = p.submission_id AND sa.user_id = u.user_id);`,
  );
});

// "Plugin enabled" messages left over from seeding would greet the first login
lines.push('DELETE FROM notifications WHERE level = 1;');

staticPages.forEach((p) => {
  lines.push(
    `DELETE s FROM static_page_settings s JOIN static_pages p ON p.static_page_id = s.static_page_id WHERE p.path = ${q(p.path)} AND p.context_id = @ctx;`,
    `DELETE FROM static_pages WHERE path = ${q(p.path)} AND context_id = @ctx;`,
    `INSERT INTO static_pages (path, context_id) VALUES (${q(p.path)}, @ctx);`,
    `SET @sp = LAST_INSERT_ID();`,
    `INSERT INTO static_page_settings (static_page_id, locale, setting_name, setting_value, setting_type) VALUES (@sp, 'en', 'title', ${q(p.title)}, 'string'), (@sp, 'en', 'content', ${q(p.content)}, 'string');`,
  );
});

// Primary navigation per docs/PRD.md §4 (dev only; production menus are
// configured in Settings › Website › Navigation Menus).
const BASE = process.argv[2] || 'http://localhost:8080';
const J = `${BASE}/djas`;
const custom = [
  [101, 'Aims & Scope', `${J}/aims-and-scope`],
  [102, 'Journal Policies', `${J}/policies`],
  [103, 'Indexing & Abstracting', `${J}/indexing`],
  [104, 'For Authors', `${J}/about/submissions`],
  [105, 'Submit a Manuscript', `${J}/submission`],
  [106, 'Authors', `${J}/authors`],
];
lines.push(
  "SET @menu = (SELECT navigation_menu_id FROM navigation_menus WHERE context_id = @ctx AND area_name = 'primary' LIMIT 1);",
  `DELETE FROM navigation_menu_item_assignments WHERE navigation_menu_item_id IN (${custom.map((c) => c[0]).join(',')});`,
  `DELETE FROM navigation_menu_item_settings WHERE navigation_menu_item_id IN (${custom.map((c) => c[0]).join(',')});`,
  `DELETE FROM navigation_menu_items WHERE navigation_menu_item_id IN (${custom.map((c) => c[0]).join(',')});`,
);
for (const [id, title, url] of custom) {
  lines.push(
    `INSERT INTO navigation_menu_items (navigation_menu_item_id, context_id, path, type) VALUES (${id}, @ctx, '', 'NMI_TYPE_REMOTE_URL');`,
    `INSERT INTO navigation_menu_item_settings (navigation_menu_item_id, locale, setting_name, setting_value, setting_type) VALUES (${id}, 'en', 'title', ${q(title)}, 'string'), (${id}, 'en', 'remoteUrl', ${q(url)}, 'string');`,
  );
}
const typeId = (type) => `(SELECT navigation_menu_item_id FROM navigation_menu_items WHERE context_id = @ctx AND type = '${type}' ORDER BY navigation_menu_item_id LIMIT 1)`;
const typeIdLast = (type) => `(SELECT navigation_menu_item_id FROM navigation_menu_items WHERE context_id = @ctx AND type = '${type}' ORDER BY navigation_menu_item_id DESC LIMIT 1)`;
lines.push(
  'DELETE FROM navigation_menu_item_assignments WHERE navigation_menu_id = @menu;',
  `SET @about = ${typeId('NMI_TYPE_ABOUT')};`,
  `INSERT INTO navigation_menu_item_assignments (navigation_menu_id, navigation_menu_item_id, parent_id, seq) VALUES
    (@menu, ${typeId('NMI_TYPE_CURRENT')}, NULL, 0),
    (@menu, ${typeId('NMI_TYPE_ARCHIVES')}, NULL, 1),
    (@menu, @about, NULL, 2),
      (@menu, ${typeIdLast('NMI_TYPE_ABOUT')}, @about, 0),
      (@menu, 101, @about, 1),
      (@menu, ${typeId('NMI_TYPE_MASTHEAD')}, @about, 2),
      (@menu, 106, @about, 2),
      (@menu, 102, @about, 3),
      (@menu, 103, @about, 4),
      (@menu, ${typeId('NMI_TYPE_CONTACT')}, @about, 5),
      (@menu, ${typeId('NMI_TYPE_PRIVACY')}, @about, 6),
    (@menu, 104, NULL, 3),
      (@menu, ${typeId('NMI_TYPE_SUBMISSIONS')}, 104, 0),
      (@menu, 105, 104, 1),
    (@menu, ${typeId('NMI_TYPE_ANNOUNCEMENTS')}, NULL, 4);`,
);

writeFileSync(OUT, lines.join('\n') + '\n');
console.log('✓ Wrote', OUT);
