# Product Requirements Document — Academic Journal Website (OJS)

| Item | Value |
|---|---|
| Product | Online academic journal website (peer-reviewed, open access) |
| Platform | Open Journal Systems (OJS) **3.5 LTS** (3.5.0-5 at time of writing) |
| Site language | English |
| Hosting | Customer VPS (outside Iran), Docker-based |
| Status | Ready for development |
| Journal identity | Placeholder `[Journal Name]` until the client supplies it — see [client-inputs.md](client-inputs.md) |

Related documents: [design-system.md](design-system.md) · [architecture.md](architecture.md) ·
[ojs-configuration.md](ojs-configuration.md) · [indexing-readiness.md](indexing-readiness.md) ·
[deployment-vps.md](deployment-vps.md) · [roadmap.md](roadmap.md) · [client-inputs.md](client-inputs.md)

---

## 1. Background & goals

The client is launching a new peer-reviewed journal. They need a professional journal website on OJS that works
**like** the reference journal <https://jlar.rovedar.com/index.php/JLAR> (standard OJS 3 structure and editorial
workflow), but looks **cleaner, more modern and more professional**. The look is inspired by a minimalist editorial
design: generous white space, large serif titles, small uppercase meta labels, hairline rules, and a three-column
reading layout.

References and linking are a stated priority. Published articles must have clickable references and linked terms,
in the style of ScienceDirect (<https://www.sciencedirect.com/journal/veterinary-and-animal-science/vol/34/suppl/C>).

**Goals**

1. A complete OJS editorial platform: authors submit online, editors run peer review, and articles are published in issues.
2. A distinctive, modern, fully responsive reader experience for desktop, tablet and mobile.
3. First-class scholarly metadata, so the journal is technically ready for Google Scholar and later indexing
   applications (DOAJ, EBSCO, CABI, Scopus, Web of Science).
4. A secure, maintainable production deployment on a VPS that can be upgraded with OJS LTS releases.

**Non-goals**

- Forking or modifying OJS core. All customisation lives in plugins, so upgrades stay safe.
- Writing the journal's editorial content (policies, board bios, guidelines). The client supplies it; we provide templates.
- Guaranteeing acceptance by any indexing service (see §8).

## 2. Personas

| Persona | Needs |
|---|---|
| **Reader / researcher** | Find, read, cite and download articles quickly. Follow references. Use a good mobile layout. |
| **Author** | Register, read the guidelines, submit a manuscript, track status, respond to revisions, link ORCID. |
| **Reviewer** | Receive invitations, accept or decline, download files, submit a review (with a review form) on time. |
| **Section Editor** | Screen submissions, assign reviewers, make recommendations, communicate with authors. |
| **Editor-in-Chief** | Make final decisions, schedule publication, oversee the workflow. |
| **Journal Manager** | Configure the journal, users, emails, issues, DOIs, menus and static pages. |
| **Site Administrator** | Server, OJS upgrades, backups, security. |

## 3. Scope — functional requirements

Each item maps 1:1 to the contract. Priority: **P0** is needed for launch, **P1** is expected at launch, **P2** is nice to have.

### F1 — OJS installation and base configuration (P0)
*As a Journal Manager, I want a correctly installed and configured OJS, so the journal can operate.*
- OJS 3.5 LTS runs in Docker (official `pkpofficial/ojs` image) with MariaDB 11.4.
- Clean URLs (`restful_urls = On`), UTC time zone, English UI, a strong `app_key`, and `files_dir` outside the web root.
- **Acceptance:** fresh install completes; the site admin can log in; the journal is created; no PHP errors in the logs.

### F2 — Custom UI (theme "Meridian") (P0)
*As a reader, I want a clean modern interface that makes articles pleasant to read and easy to cite.*
- A child theme of the OJS Default theme (upgrade-safe). The design follows [design-system.md](design-system.md).
- Custom layouts for: Home, Current Issue / Issue TOC, Archives, Article page, About / static pages, Editorial Team,
  Submissions, Search, Announcements, Login / Register, and Errors.
- **Acceptance:** all listed pages match the design system; no page from the default theme looks broken;
  theme options (accent colour, logo/wordmark, homepage sections) are editable from the OJS admin.

### F3 — Responsive design (P0)
- Breakpoints: desktop ≥ 1025px, tablet 641–1024px, mobile ≤ 640px.
- **Acceptance:** at 1440, 834 and 390px widths, there is no horizontal scrolling; navigation works through the
  drawer; the article page reflows from 3 columns to 2 to 1; tap targets are ≥ 44px.

### F4 — Author registration and login (P0)
- OJS registration with the privacy-consent checkbox, email validation, password reset, and **ALTCHA** captcha
  (self-hosted, no Google dependency) on registration, login and lost password.
- **Acceptance:** a new user can register, receives the validation email, can log in and reset a password; bots are
  blocked by the captcha.

### F5 — Online manuscript submission (P0)
- The OJS 3.5 submission wizard, including a submission checklist, section selection, file upload, contributors,
  metadata (keywords and references required), and the author's comments to the editor.
- **Acceptance:** an author completes a submission end to end and receives the acknowledgement email; the editor
  sees it in the dashboard.

### F6 — Roles and workflow accounts (P0)
- Roles configured: Journal Manager, Journal Editor (Editor-in-Chief), Section Editor, Reviewer, Author,
  Copyeditor, Layout Editor, Proofreader.
- **Acceptance:** each role sees only its permitted stages; test accounts exist for each role on staging.

### F7 — Peer review, acceptance and publication (P0)
- **Double-anonymous** review by default. A review form with standard criteria, review deadlines, reminders, and
  editor decisions (accept / minor / major revisions / decline).
- After acceptance: copyediting → production (galleys) → schedule for publication in an issue.
- **Acceptance:** a full cycle — submit → assign 2 reviewers → reviews in → revisions → accept → PDF galley → publish
  — runs end to end on staging.

### F8 — Current Issue, Archives and Article pages (P0)
- **Current Issue** and **Issue TOC:** issue header (volume, number, year, cover, description), articles grouped by
  section. Each article row shows: type, title, authors, pages, DOI, PDF link, an inline "Abstract" toggle, and
  citation export. Modelled on the ScienceDirect issue page.
- **Archives:** issues grouped by year, then volume and issue, shown as cover cards; paginated.
- **Article page:** 3-column layout (see the design system). Contents: title, authors with affiliations and ORCID,
  abstract, keywords, full text (optional HTML), references, How to Cite, license, dates, DOI, downloads, "More from
  this issue", related articles, and author bios.
- **Acceptance:** every published article has its own stable URL with all of the above; all links work.

### F9 — PDF view and download (P0)
- A PDF galley on every article. Inline viewing through the PDF.js viewer plugin, plus a direct download link.
- **Acceptance:** PDFs open in the browser viewer and download with a readable file name; the article page carries
  `citation_pdf_url`.

### F10 — Search and standard OJS features (P0)
- Full-text search with the OJS search index (PDFs indexed), advanced filters (authors, dates), keyword links, RSS/Atom
  feeds, announcements, sitemap, and "How to Cite" (CSL: APA, Vancouver, Harvard, …) with RIS/BibTeX export.
- **Acceptance:** searching for a word that appears inside a PDF returns the article; clicking a keyword chip
  runs a search for that keyword.

### F11 — Email notifications (P0)
- SMTP delivery (a client-provided account), a branded email signature/header, and OJS email templates reviewed for
  submission acknowledgement, review requests and reminders, decisions, and publication notices.
- SPF, DKIM and DMARC guidance for the sending domain.
- **Acceptance:** each workflow email arrives in a Gmail inbox (not spam) on staging.

### F12 — DOI / Crossref (P0, once credentials are supplied)
- The DOI plugin is enabled with the client's prefix and a suffix pattern, e.g. `10.XXXXX/jname.YYYY.V.I.N`.
- Crossref registration with automatic deposit; references are included in the deposit (reference linking).
- **Acceptance:** a published test article gets a DOI; the deposit status is "Registered"; `https://doi.org/<doi>`
  resolves to the article page.

### F13 — Basic ORCID integration (P0)
- The ORCID integration in OJS 3.5 core, using the Public API (or Member API if the client has it). Authors can
  authenticate their iD, and a green iD badge appears next to author names on the article page.
- **Acceptance:** an author links an ORCID iD through the email request flow; the badge links to the ORCID record.

### F14 — Google Scholar readiness (P0)
- The Google Scholar plugin outputs `citation_*` meta tags. Each article has an HTML landing page with its abstract.
  PDFs are public, with no login wall. The first PDF page shows the title and authors. The site has a sitemap and a
  `robots.txt` that allows crawling.
- Also: Dublin Core meta tags, schema.org **JSON-LD** (`ScholarlyArticle`), and OpenGraph tags.
- **Acceptance:** an article page passes the Google Scholar inclusion guideline checks
  ([indexing-readiness.md](indexing-readiness.md)); the JSON-LD validates.

### F15 — OAI-PMH and metadata (P0)
- The OAI endpoint `/index.php/<journal>/oai` is enabled with the `oai_dc`, `marcxml` and `jats` formats, plus a
  repository identifier and admin email.
- **Acceptance:** `?verb=Identify`, `ListMetadataFormats`, `ListRecords&metadataPrefix=oai_dc` return valid XML.

### F16 — Preparation for DOAJ, EBSCO, CABI, Scopus and WoS (P1)
- Technical and structural readiness: policy pages, an editorial board with affiliations, ISSN display, license
  statements, the DOAJ export plugin, and PKP Preservation Network (digital archiving).
- A written checklist per index covering what the client must accumulate: history, article counts, regularity. See
  [indexing-readiness.md](indexing-readiness.md).

### F17 — Reference linking and linked terms (P0 — client priority)
- References are numbered with anchors (`#ref-N`); DOIs and URLs become links; each reference gets **Crossref** and
  **Google Scholar** look-up links.
- Keywords are clickable (they run a search). Editors can hyperlink any word in the abstract or HTML full text
  through the rich-text editor.
- **Optional HTML full text** (per article): when an HTML galley is uploaded, it renders **inline** on the article
  page. In-text citations (`[1]`, `[2–4]`, `(Smith et al., 2020)`) link to the reference list and show an accessible
  popover. The "On this page" outline is built from its headings.
- **Acceptance:** on a demo article with an HTML galley, clicking `[3]` scrolls to reference 3; hovering or focusing
  shows the reference text; every DOI in the references links to `doi.org`.

### F18 — SSL and basic security (P0)
- Let's Encrypt TLS with automatic renewal, HTTP → HTTPS redirect, HSTS, security headers, and `force_ssl`.
- Login rate limiting at Nginx, captcha, least-privilege containers, firewall (22/80/443), automatic OS security updates.
- Daily encrypted-at-rest backups (database + files) with 14-day retention and an optional off-site copy.
- **Acceptance:** SSL Labs grade A or better; securityheaders.com grade A or better; a backup restore is tested once on staging.

### F19 — Final testing and launch (P0)
- A QA checklist (§6), a UAT session with the client, go-live on the production domain, and a post-launch check.

## 4. Information architecture

**Primary menu:** Home · Current · Archives · About ▾ · For Authors ▾ · Announcements

- **About ▾**
  - About the Journal
  - Aims & Scope
  - Editorial Team (the OJS 3.5 masthead)
  - Journal Policies (static page with anchors: Peer Review, Open Access, Publication Ethics & Malpractice,
    Plagiarism, Copyright & Licensing, Archiving, Article Processing Charges, Corrections & Retractions,
    Conflicts of Interest, Data Sharing, AI use)
  - Indexing & Abstracting
  - Contact
- **For Authors ▾**
  - Author Guidelines
  - Submit a Manuscript
  - Submission Checklist
  - Publication Charges
  - Reviewer Guidelines

**User menu:** Login / Register, or, when logged in: Dashboard · Profile · Logout

**Footer:** journal name and ISSN(s), publisher, license statement (CC BY 4.0), quick links, OAI-PMH link, RSS,
indexing logos, and contact.

## 5. Non-functional requirements

| Area | Requirement |
|---|---|
| Performance | Lighthouse Performance ≥ 90 (mobile) on Home and Article pages; LCP < 2.5 s; self-hosted fonts in woff2 (latin subsets) with `font-display: swap`; images lazy-loaded |
| Accessibility | WCAG 2.1 AA: contrast ≥ 4.5:1 for body text, visible focus, skip links, keyboard-operable drawer and popovers, ARIA landmarks; Lighthouse a11y ≥ 95 |
| SEO | Lighthouse SEO 100 on the article page; canonical URLs; meta descriptions from abstracts |
| Browser support | Last 2 versions of Chrome, Firefox, Safari and Edge; iOS Safari 16+; Android Chrome |
| Security | See F18; no secrets in the repository; `.env` on the server only |
| Maintainability | No core modifications; theme and plugins versioned in git; upgrade path documented |
| Privacy | No third-party trackers by default; no Google Fonts CDN; Google Analytics only if the client asks for it |
| Availability | Target 99.5% monthly; Docker `restart: unless-stopped`; uptime monitor recommended |

## 6. QA checklist (launch gate)

1. All F-items pass their acceptance criteria.
2. Screenshots of all key pages at 1440, 834 and 390px, approved by the client.
3. A full editorial workflow run with test accounts (F7).
4. Metadata: Google Scholar tags, JSON-LD, OAI-PMH and sitemap validated.
5. Email deliverability test (F11).
6. SSL, headers, backups and restore test (F18).
7. Test/demo content removed from production; admin passwords rotated.

## 7. Dependencies on the client

The project needs the client to supply the items below. Until they arrive, placeholders are used — see
[client-inputs.md](client-inputs.md).
- journal identity, logo and ISSN
- policies and guidelines text
- the editorial board
- DOI prefix and Crossref membership credentials
- ORCID API credentials
- an SMTP account
- the domain and DNS
- VPS access

## 8. Assumptions, constraints and disclaimers

- **Indexing disclaimer.** The contractor prepares the website technically for Google Scholar and academic indexing
  services, and gives guidance for DOAJ, EBSCO, CABI, Scopus and Web of Science where possible. Because the journal is
  new, several databases require a publishing history, a minimum number of articles, regularly published issues,
  editorial quality, or other conditions before they review or accept a title. **The contractor gives no guarantee
  of acceptance or indexing** in Google Scholar, DOAJ, EBSCO, CABI, Scopus, Web of Science or any other external
  database. The final decision rests solely with the relevant organisation or database.
- DOI registration requires the client's own Crossref membership (or a sponsoring organisation), and its fees.
- ORCID Member API features (writing works to ORCID records) require an ORCID membership; the Public API covers
  authentication and display.
- Email notifications are limited to what OJS supports natively.
- Content (text, images, board photos) is supplied by the client in English.
