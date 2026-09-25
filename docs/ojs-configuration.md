# OJS 3.5 Configuration Specification

This is the exact configuration to apply after installation. Paths refer to the OJS 3.5 backend menus.
`[…]` marks client-supplied values (see [client-inputs.md](client-inputs.md)).

## 1. Server configuration (`config.inc.php`, rendered from `deploy/.env`)

| Section | Key | Value |
|---|---|---|
| general | `base_url` | `https://[domain]` |
| general | `restful_urls` | `On` (clean URLs, no `index.php`) |
| general | `allowed_hosts` | `'["[domain]"]'` |
| general | `trust_x_forwarded_for` | `On` (behind nginx) |
| general | `time_zone` | `UTC` |
| database | `driver` / `host` / `name` | `mysqli` / `db` / `ojs` |
| files | `files_dir` | `/var/www/files` (volume, not web-accessible) |
| security | `force_ssl` | `On` |
| security | `session_check_ip`, `encryption` | defaults |
| email | `default` | `smtp`, with `smtp_server`, `smtp_port = 587`, `smtp_auth = tls` from `.env` |
| email | `force_default_envelope_sender` / `default_envelope_sender` | `On` / `no-reply@[domain]` (for DMARC alignment) |
| email | `force_dmarc_compliant_from` | `On` |
| captcha | `altcha`, `altcha_on_register`, `altcha_on_login`, `altcha_on_lost_password` | `on` |
| captcha | `altcha_hmackey` | random 64-character secret |
| queues | `job_runner` | `Off` (handled by the `worker` service) |
| schedule | `task_runner` | `Off` (handled by the `scheduler` service) |
| debug | `display_errors` | `Off` |
| oai | `oai`, `repository_id` | `On`, `[domain]` |

## 2. Journal setup — *Settings › Journal*

- **Masthead:** Journal title `[Journal Name]`, initials, abbreviation `[Abbrev.]`, publisher `[Publisher]`, online ISSN `[eISSN]`, print ISSN `[pISSN]` (if any), summary (1–2 sentences → homepage hero), *About the Journal* text, Editorial masthead roles in display order.
- **Contact:** principal contact (Editor-in-Chief), technical support contact, mailing address.
- **Sections** (each with its own policy text and abstract word limit):

| Section | Abbrev | Peer reviewed | Abstract required | Word limit |
|---|---|---|---|---|
| Original Research | ART | yes | yes | 300 |
| Review Article | REV | yes | yes | 300 |
| Short Communication | SC | yes | yes | 250 |
| Case Report | CR | yes | yes | 250 |
| Editorial | ED | no | no | — |

## 3. Website — *Settings › Website*

- **Appearance › Theme:** `Meridian`. Theme options:
  - accent `#0f5c63`
  - show the wordmark (until a logo is supplied)
  - enable the homepage sections: Current issue, Latest articles, Aims & Scope, Announcements, Indexing strip
- **Appearance › Setup:** logo, favicon, homepage image (optional), page footer (empty — Meridian renders the footer), sidebar: *none* (Meridian has its own rails).
- **Setup › Information:** For Readers / For Authors / For Librarians text.
- **Setup › Languages:** English (UI, forms, submissions).
- **Setup › Navigation Menus:** build the IA from [PRD §4](PRD.md#4-information-architecture):
  - **Primary:** Home (custom URL `/`), Current, Archives, About ▾ (About the Journal, Aims & Scope [static page], Editorial Team [masthead], Journal Policies [static page], Indexing & Abstracting [static page], Contact), For Authors ▾ (Author Guidelines [submissions#authorGuidelines], Submit a Manuscript [submissions], Publication Charges [static page], Reviewer Guidelines [static page]), Announcements.
  - **User:** Register, Login, Dashboard, View Profile, Administration, Logout (the OJS defaults).
- **Setup › Announcements:** enabled; show the 3 latest on the homepage.
- **Setup › Privacy statement:** client text (GDPR-style template provided).
- **Setup › Date & Time:** `F j, Y` (long), `Y-m-d` (short).

### Static pages (plugin *Static Pages*)
| Path | Title |
|---|---|
| `aims-and-scope` | Aims & Scope |
| `policies` | Journal Policies — sections: Peer Review Process · Open Access · Publication Ethics & Malpractice (COPE) · Plagiarism (Similarity check) · Copyright & Licensing · Archiving · Article Processing Charges · Corrections, Retractions & Expressions of Concern · Conflicts of Interest · Data Sharing · Use of AI tools · Complaints & Appeals |
| `indexing` | Indexing & Abstracting |
| `publication-charges` | Publication Charges (APC) |
| `reviewer-guidelines` | Reviewer Guidelines |

## 4. Workflow — *Settings › Workflow*

- **Submission › Metadata:**
  - **required** from authors: Keywords, References
  - **enabled**: Subjects, Supporting agencies (funding), Data availability (3.5), Coverage off, Rights off, Source off, Type off
- **Submission › Components:** Article Text, Research Instrument, Research Materials, Research Results, Transcripts, Data Analysis, Data Set, Source Texts, Other, **Title Page** (a separate file so the review stays anonymous), **Cover Letter**, **Figures**, **Tables**.
- **Submission › Checklist:** original and unpublished; formatted per the guidelines; anonymised manuscript (no author info); references with DOIs where available; ethics approval stated (for animal/human studies); conflicts and funding declared; ORCID provided for all authors (recommended).
- **Submission › Author Guidelines:** client text (a template is provided in `docs/templates/` at the content stage).
- **Review › Setup:**
  - default review mode **Double anonymous**
  - response deadline 7 days; completion deadline 21 days
  - automated reminders: 3 days before the response deadline, 7 days before the completion deadline
  - "one-click reviewer access" on
  - restrict reviewer file access to the review files
- **Review › Reviewer Guidance:** competing interests statement, review guidelines.
- **Review › Review Forms:** *Standard Research Article Review* with these criteria:
  - originality
  - methodology soundness
  - results and interpretation
  - ethics
  - references
  - language
  - figures and tables
  - recommendation
  - confidential comments to the editor
- **Emails:**
  - sender = journal name
  - signature = journal name + URL + ISSN
  - review and adjust templates: *Submission Acknowledgement*, *Reviewer Request*, *Reviewer Reminder*, *Editor Decision (Accept / Revisions / Decline)*, *Publication Notify*

## 5. Distribution — *Settings › Distribution*

- **License:** copyright holder = Author; license **CC BY 4.0**; copyright year = issue publication year. Add the license URL to article metadata.
- **DOIs:**
  - enable DOIs for Articles and Issues
  - prefix `[10.XXXXX]`
  - suffix pattern: custom `%j.%Y.%v.%i.%a` (journal initials · year · volume · issue · article id), which gives e.g. `10.XXXXX/jname.2026.1.1.12`
  - automatic assignment **on publication**
  - registration agency **Crossref**
- **Crossref plugin** (*Settings › Distribution › DOIs › Registration*):
  - depositor name/email and username/password `[Crossref credentials]`
  - automatic deposit on
  - enable depositing references (reference linking)
- **Search indexing:** description (meta), custom tags: none; the sitemap is at `/sitemap`.
- **Access:** open access (no subscriptions).
- **Archiving:** enable the PKP PN plugin once the ISSN is registered (it requires an ISSN).

## 6. Users & Roles

| Role | Permission level | Stages |
|---|---|---|
| Journal Manager | Manager | all |
| Journal Editor (Editor-in-Chief) | Manager | all |
| Section Editor | Section editor | Submission, Review, Copyediting, Production (recommend-only: **off** by default, decided with the client) |
| Guest Editor | Section editor | as assigned |
| Reviewer | Reviewer | Review |
| Author | Author | Submission (+ view) |
| Copyeditor | Assistant | Copyediting |
| Layout Editor | Assistant | Production |
| Proofreader | Assistant | Production |

- Self-registration allowed as **Reader**, **Author** and **Reviewer** (reviewer interests captured at registration).
- The Editorial masthead (3.5) shows Editor-in-Chief, Associate Editors and Editorial Board with affiliations and ORCID.

## 7. ORCID (core in 3.5) — *Settings › Website › Plugins/ORCID*

- API type: **Public** (production) → `[client ID / secret]`; the Member API if the client has a membership.
- Author ORCID request emails on; show an "authenticated ORCID iD" badge on article pages.
- Test with the ORCID **Sandbox** on staging.

## 8. Plugins — *Settings › Website › Plugins*

| Plugin | State |
|---|---|
| Meridian theme | **enabled (active theme)** |
| scholarlyReader | **enabled** |
| Author Pages | **enabled** — also add an *Authors* item (custom URL `/[journal]/authors`) under About in the primary menu |
| Google Scholar Indexing | enabled |
| Dublin Core 1.1 meta-data | enabled |
| PDF.js PDF Viewer | enabled |
| HTML Article Galley | enabled (fallback galley view page) |
| Citation Style Language | enabled; primary APA; also Vancouver, Harvard, Chicago, IEEE; downloads RIS + BibTeX |
| Static Pages | enabled |
| Custom Block Manager | enabled (optional rail blocks) |
| Web Feed | enabled (Atom/RSS, homepage only) |
| Announcement Feed | enabled |
| Usage Statistics | enabled (COUNTER R5; GeoIP off by default for privacy) |
| Crossref | enabled (after credentials) |
| OAI: DC, MARCXML, JATS | enabled (JATS also needs the *JATS Template* generic plugin) |
| DOAJ export | enabled (for later metadata upload) |
| Recommend Articles by Author / Similarity | enabled (feeds "Related articles") |
| Google Analytics | off unless requested |
| Lens galley | off (superseded by the inline full text) |
| Browse, Developed By, Information, Language Toggle blocks | off (Meridian's rails replace them) |
