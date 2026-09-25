// Build demo content (dev only): article PDFs, one HTML full-text galley,
// issue covers, and an OJS Native XML file for tools/importExport.php.
//
//   NODE_PATH=$(npm root -g) node dev/seed/build.mjs
//
// Output: dev/out/demo-issues.xml and dev/out/demo-inprogress.xml (+ the
// generated files, embedded as base64)

import { mkdirSync, readFileSync, writeFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { createRequire } from 'node:module';
import { inProgress, issues, journal, refs, sections } from './content.mjs';

const require = createRequire(import.meta.url);
const { chromium } = require('playwright');

const HERE = dirname(fileURLToPath(import.meta.url));
const OUT = join(HERE, '..', 'out');
mkdirSync(OUT, { recursive: true });

const esc = (s) => String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
const cdata = (s) => `<![CDATA[${String(s).replace(/]]>/g, ']]]]><![CDATA[>')}]]>`;
const monthName = (iso) => new Date(iso).toLocaleString('en-GB', { month: 'long', year: 'numeric' });

// ---------------------------------------------------------------- templates

const pdfCss = `
  @page { size: A4; margin: 22mm 20mm; }
  body { font-family: 'Source Serif 4', Georgia, serif; font-size: 10.5pt; line-height: 1.5; color: #111; }
  .masthead { display: flex; justify-content: space-between; border-bottom: 1px solid #111; padding-bottom: 6px; font: 600 8pt/1.2 Arial, sans-serif; letter-spacing: .08em; text-transform: uppercase; }
  .type { margin-top: 18px; font: 600 8pt Arial, sans-serif; letter-spacing: .08em; text-transform: uppercase; color: #0f5c63; }
  h1 { font-family: 'Playfair Display', Georgia, serif; font-weight: 400; font-size: 20pt; line-height: 1.2; margin: 8px 0 10px; }
  .authors { font-size: 11pt; } .aff { font-size: 8.5pt; color: #555; margin-top: 4px; }
  .abstract { background: #f5f5f3; padding: 10px 12px; margin: 14px 0; font-size: 9.5pt; }
  .abstract p { margin: 4px 0; }
  .kw { font-size: 9pt; } h2 { font-size: 12pt; margin: 16px 0 4px; }
  .cols { column-count: 2; column-gap: 8mm; text-align: justify; }
  ol.refs { font-size: 8.5pt; padding-left: 16px; }
  .note { margin-top: 16px; font: 8pt Arial, sans-serif; color: #777; border-top: 1px solid #ddd; padding-top: 6px; }
`;

function filler(topic) {
  return [
    `<h2>1. Introduction</h2><p>Research on ${topic} has expanded considerably over the last decade, driven by concerns for animal health, welfare and sustainable production. Well-designed studies that follow established reporting standards remain essential for reproducibility and for translating findings into practice.</p>`,
    `<h2>2. Materials and methods</h2><p>All procedures were approved by the institutional animal ethics committee and are reported in accordance with the ARRIVE guidelines. Sample sizes were calculated a priori, and animals were randomly allocated to treatments. Data were analysed with linear mixed-effects models, with animal as a random effect; P values were adjusted for multiple comparisons.</p>`,
    `<h2>3. Results</h2><p>All animals completed the study. The main outcomes are summarised in the tables, and no adverse events attributable to the interventions were recorded. Effect sizes were consistent across sensitivity analyses.</p>`,
    `<h2>4. Discussion</h2><p>Our findings agree with previous reports and extend them to a new population. Limitations include the single-site design and the relatively short observation period. Future multicentre studies should confirm these results.</p>`,
    `<h2>5. Conclusions</h2><p>The results provide practical evidence for veterinarians and animal scientists and support further investigation of ${topic}.</p>`,
  ].join('');
}

function pdfHtml(issue, article, index) {
  const authors = article.authors.map((a) => `${a.given} ${a.family}`).join(', ');
  const affs = [...new Set(article.authors.map((a) => a.affiliation))].join('; ');
  const refsList = article.references.map((r) => `<li>${esc(refs[r])}</li>`).join('');
  return `<!doctype html><html><head><meta charset="utf-8"><style>${pdfCss}</style></head><body>
    <div class="masthead"><span>${esc(journal.name)}</span><span>Vol. ${issue.volume}, No. ${issue.number} (${issue.year}) · pp. ${esc(article.pages)}</span></div>
    <div class="type">${esc(sections.find((s) => s.ref === article.section).title)}</div>
    <h1>${esc(article.title)}</h1>
    <div class="authors">${esc(authors)}</div><div class="aff">${esc(affs)}</div>
    <div class="abstract"><strong>Abstract</strong>${article.abstract}</div>
    <div class="kw"><strong>Keywords:</strong> ${esc(article.keywords.join('; '))}</div>
    <div class="cols">${filler(article.keywords[0])}<h2>References</h2><ol class="refs">${refsList}</ol></div>
    <div class="note">Demo article ${index} — fictional content generated for website design review. © ${issue.year} The Authors. CC BY 4.0.</div>
  </body></html>`;
}

// Full-text HTML galley with numbered in-text citations (reference order = article.references).
function galleyHtml(article) {
  return `<!doctype html>
<html lang="en"><head><meta charset="utf-8"><title>${esc(article.title)}</title></head>
<body>
<h2>1. Introduction</h2>
<p>Heat stress is one of the most important environmental constraints on dairy production worldwide. When the temperature–humidity index (THI) exceeds approximately 68, high-yielding cows begin to reduce feed intake and milk yield, and the effects are expected to intensify as climate change raises summer temperatures. Robust experimental designs and transparent reporting are essential for interpreting animal studies in this field [1, 2].</p>
<p>Most previous work has attributed production losses to reduced dry matter intake. However, direct effects of heat load on rumen fermentation have been less well characterised. The aim of this study was to quantify changes in rumen fermentation, milk yield and milk composition under controlled heat stress, while accounting for differences in intake [2, 3].</p>
<h2>2. Materials and methods</h2>
<h3>2.1. Animals and design</h3>
<p>Sixteen multiparous Holstein cows (days in milk 95 ± 12; body weight 642 ± 38 kg) were enrolled in a randomized crossover design with two 21-day periods separated by a 14-day washout. The sample size was calculated a priori to detect a 2.5 kg/day difference in milk yield with 80% power [3]. The study adhered to the principles of the 3Rs [8].</p>
<h3>2.2. Measurements</h3>
<p>Rumen fluid was collected by oro-ruminal tube on days 19–21 of each period. Volatile fatty acids were measured by gas chromatography and rumen papillae were imaged for morphometric analysis using ImageJ [6].</p>
<h3>2.3. Statistical analysis</h3>
<p>Data were analysed with linear mixed-effects models including treatment, period and sequence as fixed effects and cow as a random effect [4], using R [7]. P values for multiple outcomes were adjusted with the Benjamini–Hochberg procedure [5].</p>
<h2>3. Results</h2>
<p>Heat stress increased rectal temperature and respiration rate (both P &lt; 0.001) and reduced dry matter intake by 12%. Milk yield decreased by 3.9 kg/day, and milk protein concentration decreased slightly (Table 1).</p>
<table>
  <caption>Table 1. Production and rumen variables under thermoneutral (TN) and heat stress (HS) conditions (least-squares means).</caption>
  <thead><tr><th>Variable</th><th>TN</th><th>HS</th><th>SEM</th><th>P</th></tr></thead>
  <tbody>
    <tr><td>Dry matter intake (kg/d)</td><td>24.1</td><td>21.2</td><td>0.6</td><td>&lt;0.01</td></tr>
    <tr><td>Milk yield (kg/d)</td><td>38.6</td><td>34.7</td><td>0.9</td><td>&lt;0.01</td></tr>
    <tr><td>Milk protein (%)</td><td>3.21</td><td>3.08</td><td>0.04</td><td>0.03</td></tr>
    <tr><td>Total VFA (mmol/L)</td><td>118.4</td><td>104.9</td><td>3.1</td><td>&lt;0.01</td></tr>
    <tr><td>Ruminal pH</td><td>6.12</td><td>6.31</td><td>0.05</td><td>0.02</td></tr>
  </tbody>
</table>
<blockquote>Reduced volatile fatty acid production under heat stress was evident even after adjusting for dry matter intake.</blockquote>
<h2>4. Discussion</h2>
<p>The decline in total volatile fatty acids accompanied by a higher ruminal pH indicates reduced fermentative activity, consistent with lower intake but also with direct effects of heat load on the rumen microbiota [2, 4–6]. These findings support nutritional strategies that target rumen function during hot seasons, such as buffers and yeast cultures.</p>
<p>Limitations include the controlled-environment setting, which may not capture diurnal temperature variation on commercial farms. Future studies should include larger herds and longer exposure periods [1].</p>
<h2>5. Conclusions</h2>
<p>Heat stress impairs rumen fermentation and milk production in Holstein cows. Mitigation should combine environmental cooling with dietary strategies that support rumen function.</p>
</body></html>`;
}

function coverHtml(issue) {
  return `<!doctype html><html><head><meta charset="utf-8"><style>
    html,body{margin:0} body{width:600px;height:800px;background:#f4f1ea;font-family:Georgia,serif;color:#111;position:relative;overflow:hidden}
    .top{position:absolute;top:48px;left:48px;right:48px;border-top:2px solid #111;padding-top:14px;font:600 13px Arial,sans-serif;letter-spacing:.14em;text-transform:uppercase}
    .name{position:absolute;top:110px;left:48px;right:48px;font-size:54px;line-height:1.05}
    .art{position:absolute;left:48px;right:48px;top:360px;height:280px}
    .meta{position:absolute;bottom:48px;left:48px;right:48px;border-top:1px solid #111;padding-top:14px;display:flex;justify-content:space-between;font:600 14px Arial,sans-serif;letter-spacing:.12em;text-transform:uppercase}
  </style></head><body>
    <div class="top">Peer-reviewed · Open access</div>
    <div class="name">Demo Journal<br>of Animal<br>Science</div>
    <svg class="art" viewBox="0 0 504 280" fill="none" stroke="${issue.number === 1 ? '#0f5c63' : '#8a3b12'}" stroke-width="1.2">
      ${Array.from({ length: 14 }, (_, i) => `<circle cx="${252 + (issue.number === 1 ? -60 : 60)}" cy="140" r="${10 + i * 11}"/>`).join('')}
    </svg>
    <div class="meta"><span>Vol. ${issue.volume} · No. ${issue.number}</span><span>${monthName(issue.datePublished)}</span></div>
  </body></html>`;
}

// ---------------------------------------------------------------- build

const browser = await chromium.launch();
const page = await browser.newPage();

const b64 = (buf) => Buffer.from(buf).toString('base64');
let fileId = 1;
let articleCounter = 0;
let issueXml = '';

for (const issue of issues) {
  await page.setViewportSize({ width: 600, height: 800 });
  await page.setContent(coverHtml(issue));
  const cover = await page.screenshot({ type: 'png' });
  const coverName = `cover_v${issue.volume}_n${issue.number}.png`;
  writeFileSync(join(OUT, coverName), cover);

  let articlesXml = '';
  let seq = 0;
  for (const article of issue.articles) {
    articleCounter++;
    await page.setContent(pdfHtml(issue, article, articleCounter));
    const pdf = await page.pdf({ format: 'A4', printBackground: true });
    const pdfName = `djas-${issue.volume}-${issue.number}-${seq + 1}.pdf`;
    writeFileSync(join(OUT, pdfName), pdf);

    const files = [{ id: fileId++, name: pdfName, ext: 'pdf', data: pdf, galley: 'PDF' }];
    if (article.html) {
      const html = Buffer.from(galleyHtml(article), 'utf8');
      const htmlName = `djas-${issue.volume}-${issue.number}-${seq + 1}.html`;
      writeFileSync(join(OUT, htmlName), html);
      files.push({ id: fileId++, name: htmlName, ext: 'html', data: html, galley: 'Full Text' });
    }

    const submissionFilesXml = files.map((f) => `
        <submission_file id="${f.id}" file_id="${f.id}" stage="proof" genre="Article Text" uploader="admin" created_at="${issue.datePublished}" updated_at="${issue.datePublished}" viewable="false">
          <name locale="en">${esc(f.name)}</name>
          <file id="${f.id}" filesize="${f.data.length}" extension="${f.ext}">
            <embed encoding="base64">${b64(f.data)}</embed>
          </file>
        </submission_file>`).join('');

    const authorsXml = article.authors.map((a, i) => `
            <author include_in_browse="true" user_group_ref="Author" seq="${i}" id="${articleCounter * 10 + i}"${i === 0 ? ' primary_contact="true"' : ''}>
              <givenname locale="en">${esc(a.given)}</givenname>
              <familyname locale="en">${esc(a.family)}</familyname>
              <affiliation><name locale="en">${esc(a.affiliation)}</name></affiliation>
              <country>${a.country}</country>
              <email>${a.given.toLowerCase()}.${a.family.toLowerCase()}@example.org</email>${a.orcid ? `
              <orcid>${a.orcid}</orcid>` : ''}${a.bio ? `
              <biography locale="en">${cdata(`<p>${esc(a.bio)}</p>`)}</biography>` : ''}
            </author>`).join('');

    const galleysXml = files.map((f, i) => `
          <article_galley locale="en" approved="false">
            <name locale="en">${f.galley}</name>
            <seq>${i}</seq>
            <submission_file_ref id="${f.id}"/>
          </article_galley>`).join('');

    articlesXml += `
      <article locale="en" date_submitted="${article.dateSubmitted}" status="3" submission_progress="" current_publication_id="${articleCounter}" stage="production">
        <id type="internal" advice="ignore">${articleCounter}</id>
        ${submissionFilesXml}
        <publication version="1" status="3" seq="${seq}" date_published="${issue.datePublished}" section_ref="${article.section}" access_status="0">
          <id type="internal" advice="ignore">${articleCounter}</id>
          <title locale="en">${esc(article.title)}</title>
          <abstract locale="en">${cdata(article.abstract)}</abstract>
          <licenseUrl>https://creativecommons.org/licenses/by/4.0/</licenseUrl>
          <copyrightHolder locale="en">The Authors</copyrightHolder>
          <copyrightYear>${issue.year}</copyrightYear>
          <keywords locale="en">${article.keywords.map((k) => `<keyword><name>${esc(k)}</name></keyword>`).join('')}</keywords>
          <authors>${authorsXml}
          </authors>${galleysXml}
          <citations>${article.references.map((r) => `<citation>${esc(refs[r])}</citation>`).join('')}</citations>
          <pages>${esc(article.pages)}</pages>
        </publication>
      </article>`;
    seq++;
  }

  const sectionsXml = sections.map((s) => `
      <section ref="${s.ref}" seq="${s.seq}" editor_restricted="0" meta_indexed="1" meta_reviewed="1" abstracts_not_required="0" hide_title="0" hide_author="0" abstract_word_count="300">
        <abbrev locale="en">${s.ref}</abbrev>
        <title locale="en">${esc(s.title)}</title>
      </section>`).join('');

  issueXml += `
  <issue published="1" current="${issue.current ? 1 : 0}" access_status="1">
    <description locale="en">${cdata(issue.description)}</description>
    <issue_identification>
      <volume>${issue.volume}</volume>
      <number>${issue.number}</number>
      <year>${issue.year}</year>
    </issue_identification>
    <date_published>${issue.datePublished}</date_published>
    <last_modified>${issue.datePublished}</last_modified>
    <sections>${sectionsXml}
    </sections>
    <covers>
      <cover locale="en">
        <cover_image>${coverName}</cover_image>
        <cover_image_alt_text>Cover of Volume ${issue.volume}, Issue ${issue.number}</cover_image_alt_text>
        <embed encoding="base64">${b64(cover)}</embed>
      </cover>
    </covers>
    <articles>${articlesXml}
    </articles>
  </issue>`;
}

// Manuscripts still in the workflow: a submitted PDF each, no issue
let inProgressXml = '';
for (const [i, m] of inProgress.entries()) {
  const id = 101 + i;
  await page.setContent(`<!doctype html><html><head><meta charset="utf-8"><style>${pdfCss} h1{font-size:18pt}</style></head><body>
    <div class="masthead"><span>Manuscript submitted to ${esc(journal.name)}</span><span>${esc(m.dateSubmitted)}</span></div>
    <h1>${esc(m.title)}</h1>
    <div class="abstract"><strong>Abstract</strong>${m.abstract}</div>
    <div class="kw"><strong>Keywords:</strong> ${esc(m.keywords.join('; '))}</div>
    <div class="cols">${filler(m.keywords[0])}</div>
    <div class="note">Demo manuscript — fictional content generated for website design review. Author details removed for anonymous review.</div>
  </body></html>`);
  const pdf = await page.pdf({ format: 'A4', printBackground: true });
  const authorsXml = m.authors.map((a, j) => `
            <author include_in_browse="true" user_group_ref="Author" seq="${j}" id="${id * 10 + j}"${j === 0 ? ' primary_contact="true"' : ''}>
              <givenname locale="en">${esc(a.given)}</givenname>
              <familyname locale="en">${esc(a.family)}</familyname>
              <affiliation><name locale="en">${esc(a.affiliation)}</name></affiliation>
              <country>${a.country}</country>
              <email>${a.given.toLowerCase()}.${a.family.toLowerCase()}@example.org</email>
            </author>`).join('');
  inProgressXml += `
  <article locale="en" date_submitted="${m.dateSubmitted}" status="1" submission_progress="" current_publication_id="${id}" stage="${m.stage}">
    <id type="internal" advice="ignore">${id}</id>
    <submission_file id="${fileId}" file_id="${fileId}" stage="submission" genre="Article Text" uploader="admin" created_at="${m.dateSubmitted}" updated_at="${m.dateSubmitted}" viewable="false">
      <name locale="en">manuscript-${id}.pdf</name>
      <file id="${fileId}" filesize="${pdf.length}" extension="pdf">
        <embed encoding="base64">${b64(pdf)}</embed>
      </file>
    </submission_file>
    <publication version="1" status="1" seq="0" section_ref="${m.section}" access_status="0">
      <id type="internal" advice="ignore">${id}</id>
      <title locale="en">${esc(m.title)}</title>
      <abstract locale="en">${cdata(m.abstract)}</abstract>
      <keywords locale="en">${m.keywords.map((k) => `<keyword><name>${esc(k)}</name></keyword>`).join('')}</keywords>
      <authors>${authorsXml}
      </authors>
    </publication>
  </article>`;
  fileId++;
}

// Illustrative author avatar (not a real person) for the author-page demo
await page.setViewportSize({ width: 150, height: 150 });
await page.setContent(`<!doctype html><html><body style="margin:0"><svg width="150" height="150" viewBox="0 0 150 150" xmlns="http://www.w3.org/2000/svg">
  <defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#d9e6e4"/><stop offset="1" stop-color="#9dbfbb"/></linearGradient></defs>
  <rect width="150" height="150" fill="url(#g)"/>
  <circle cx="75" cy="60" r="27" fill="#0f5c63" opacity=".85"/>
  <path d="M22 150c4-34 26-52 53-52s49 18 53 52z" fill="#0f5c63" opacity=".85"/>
</svg></body></html>`);
writeFileSync(join(OUT, 'profileImage-201.png'), await page.screenshot({ type: 'png' }));

await browser.close();

const xml = `<?xml version="1.0" encoding="utf-8"?>
<issues xmlns="http://pkp.sfu.ca" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://pkp.sfu.ca native.xsd">${issueXml}
</issues>
`;
writeFileSync(join(OUT, 'demo-issues.xml'), xml);
writeFileSync(join(OUT, 'demo-inprogress.xml'), `<?xml version="1.0" encoding="utf-8"?>
<articles xmlns="http://pkp.sfu.ca" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://pkp.sfu.ca native.xsd">${inProgressXml}
</articles>
`);
console.log(`✓ Wrote ${join(OUT, 'demo-issues.xml')} (${(xml.length / 1024).toFixed(0)} KB, ${articleCounter} articles)`);
console.log(`✓ Wrote ${join(OUT, 'demo-inprogress.xml')} (${inProgress.length} manuscripts in the workflow)`);
