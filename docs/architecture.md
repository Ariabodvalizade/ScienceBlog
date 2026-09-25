# Architecture

## 1. Overview

```
                 Internet (HTTPS 443 / HTTP 80 → redirect)
                                │
                     ┌──────────▼───────────┐
                     │  web  (nginx 1.27)   │  TLS (Let's Encrypt), HSTS, security headers,
                     │                      │  rate-limit /login, gzip, static caching
                     └──────────┬───────────┘
                                │ http://ojs:80  (X-Forwarded-Proto: https)
                     ┌──────────▼───────────┐
                     │  ojs  (Apache+PHP8.3)│  FROM pkpofficial/ojs:3_5_0-5
                     │  + plugins/themes/meridian
                     │  + plugins/generic/scholarlyReader
                     └───┬──────────────┬───┘
                         │              │ volumes: ojs-files (/var/www/files, private)
                         │              │          ojs-public (/var/www/html/public)
                ┌────────▼─────┐   ┌────▼──────────────────────────┐
                │ db (MariaDB  │   │ worker    (same image)        │  jobs.php work (queue)
                │ 11.4 LTS)    │   │ scheduler (same image)        │  scheduler.php work
                └──────────────┘   └───────────────────────────────┘
                     certbot (webroot renew, twice daily)
```

- **OJS core is never modified.** The official PKP image supplies OJS; our Dockerfile only adds two plugins and a
  PHP ini override.
- **Configuration** (`config.inc.php`) is generated inside the container at every start by `deploy/ojs/configure.php`,
  from the stock `config.TEMPLATE.inc.php` of the running OJS version plus values from `deploy/.env`. Upgrades pick up
  new keys automatically, and no secrets live in the image or in git.
- **State** lives only in Docker volumes: `db-data`, `ojs-files`, `ojs-public`, and `letsencrypt`. These are what we back up.

## 2. Repository layout

```
docs/                          PRD and companion documents (this folder)
plugins/themes/meridian/       Child theme of OJS "default" theme
plugins/generic/scholarlyReader/  Reference linking, inline HTML full text, JSON-LD
deploy/
  docker-compose.yml           Production stack (web, ojs, worker, scheduler, db, certbot)
  docker-compose.dev.yml       Local development stack (plugins bind-mounted)
  .env.example                 All tunables (domain, DB, SMTP, OJS secrets)
  ojs/Dockerfile               Our OJS image (official image + theme + plugin + PHP/Apache tweaks)
  ojs/configure.php            Renders config.inc.php from env at container start
  nginx/                       Site template, TLS, proxy and security-header snippets
  scripts/                     generate-secrets, init-ssl, install-ojs, backup, restore, update-ojs, deploy, clear-cache
dev/
  demo-content/                Demo journal import (native XML + generated PDFs) — never used in production
  screenshots.mjs              Playwright screenshots at 1440/834/390
```

## 3. Theme "meridian" (child theme)

- Class `APP\plugins\themes\meridian\MeridianThemePlugin extends PKP\plugins\ThemePlugin`.
- `init()` calls `$this->setParent('defaultthemeplugin')`. **Every OJS frontend page keeps working**, because any
  template we don't override falls back to the parent theme, then to OJS core templates.
- Styles: parent's LESS is kept for the base (forms, grids used by core pages); our LESS is appended with
  `modifyStyle('stylesheet', ['addLess' => [...]])`, so we can reuse and override parent variables. Theme options
  (accent colour) are injected with `addLessVariables` and as CSS custom properties.
- Fonts are self-hosted in `fonts/` (woff2). The parent's font option is ignored or overridden.
- Scripts: small vanilla JS (`js/meridian.js`) for the drawer, search overlay, abstract toggles and sticky outline.
  We keep the parent's jQuery and Bootstrap dropdown because core frontend widgets rely on them.
- Menu areas: `primary`, `user` (same as parent), so the OJS *Navigation Menus* admin works unchanged.
- Template overrides live at `plugins/themes/meridian/templates/frontend/...` and mirror the core path. Override
  only what the design needs:

| Template | Why |
|---|---|
| `frontend/components/header.tpl` | New header (hamburger drawer, centered wordmark, search overlay) |
| `frontend/components/footer.tpl` | New three-column footer |
| `frontend/pages/indexJournal.tpl` | Home blueprint |
| `frontend/pages/issue.tpl`, `frontend/objects/issue_toc.tpl` | ScienceDirect-style TOC |
| `frontend/objects/article_summary.tpl` | Article list item with abstract toggle |
| `frontend/pages/issueArchive.tpl`, `frontend/objects/issue_summary.tpl` | Year-grouped archive grid |
| `frontend/pages/article.tpl`, `frontend/objects/article_details.tpl` | 3-column article page |
| `frontend/components/icons.tpl` (new) | Inline SVG sprite |

- **Upgrade rule:** when OJS is upgraded, diff each overridden template against the new core version (the list above
  is the complete diff surface).

## 4. Plugin "scholarlyReader" (generic)

Responsibilities, each implemented with OJS hooks (no core changes):

| Feature | Mechanism |
|---|---|
| Inline HTML full text | Hook `Templates::Article::Main` (article page). If the publication has an HTML galley, read the file via `Services`/`Repo::submissionFile()`, sanitize it with OJS's HTML purifier (`PKP\core\PKPString::stripUnsafeHtml`), rewrite relative image URLs to the galley's dependent files (same approach as the core `htmlArticleGalley` plugin), and render it inside `<section id="fulltext">`. |
| Reference list enhancement | The theme renders `$parsedCitations` with `getCitationWithLinks()` (auto-links DOIs and URLs). The plugin's JS adds `id="ref-N"` plus **Crossref** (`https://search.crossref.org/?q=`) and **Google Scholar** (`https://scholar.google.com/scholar?q=`) look-up links to each item. |
| In-text citation linking | `js/citations.js` scans `#fulltext` for numeric markers (`[1]`, `[1,2]`, `[2–4]`) and author–year markers, wraps them in `<a href="#ref-N" class="xref">`, and attaches an accessible popover (`role="tooltip"`, focus/hover, Esc). HTML galleys that already contain `<a href="#ref-…">` or JATS-style `xref` links are respected as is. |
| JSON-LD + OpenGraph | Hook `TemplateManager::display` → `addHeader()` with `ScholarlyArticle` (headline, author + ORCID `sameAs`, datePublished, isPartOf `PublicationIssue`/`PublicationVolume`/`Periodical` with ISSN, identifier DOI, license, keywords, abstract, `encoding` PDF). |
| Outline | Headings (`h2`/`h3`) of the inline full text are exposed to the theme's "On this page" list via a template variable. |

Core/bundled plugins we **reuse instead of rebuilding**:

| Need | OJS 3.5 component |
|---|---|
| Google Scholar tags | `plugins/generic/googleScholar` |
| Dublin Core tags | `plugins/generic/dublinCoreMeta` |
| DOI + Crossref deposit (incl. references) | Core DOI manager + `plugins/generic/crossref` |
| ORCID | Core ORCID integration (3.5 moved it into core) |
| OAI-PMH | Core OAI + `plugins/oaiMetadataFormats/{dc,marcxml,oaiJats}` |
| PDF viewing | `plugins/generic/pdfJsViewer` |
| How to Cite | `plugins/generic/citationStyleLanguage` |
| Static policy pages | `plugins/generic/staticPages` |
| Feeds | `plugins/generic/webFeed`, `announcementFeed` |
| DOAJ export | `plugins/importexport/doaj` |
| Captcha | Core ALTCHA (`[captcha] altcha = on`) |
| Digital preservation | PKP PN plugin (from the Plugin Gallery) |

## 4b. Plugin "authorPages" (generic)

| Feature | Mechanism |
|---|---|
| Routes `/authors` and `/authors/view/{key}/{slug}` | Hook `LoadHandler` → `AuthorPagesHandler` (`index`, `view`) |
| Author identity | Contributors of published articles grouped by ORCID iD, else e-mail (SHA-1 prefix in the URL, the address is never shown), else name |
| Photo, bio, website | From the user account with the same e-mail (`profileImage`, `biography`, `url`), which authors edit in *Profile › Public* |
| Cache | `cache/authorPages/directory-{journal}.json`, 1 h TTL, invalidated on `Publication::publish/unpublish/edit` |
| Theme integration | On article pages it assigns `$authorPageUrls` and `$authorPagePhotos` (keyed by contributor id); Meridian links names and shows photos |
| SEO | schema.org `Person` JSON-LD on profiles; canonical URL includes the name slug |

## 5. Scheduled tasks and jobs

OJS 3.5 replaced `runScheduledTasks.php` with `lib/pkp/tools/scheduler.php`, and added a job queue. In production:

- `config.inc.php`: `[schedule] task_runner = Off`, `[queues] job_runner = Off`, so web requests never run background work.
- Two services run from the same image:
  - `scheduler`: `php lib/pkp/tools/scheduler.php work` (runs due scheduled tasks every minute: review reminders,
    DOI deposits, statistics, …)
  - `worker`: `php lib/pkp/tools/jobs.php work --max-time=3600` (emails, search indexing, deposits). It exits hourly
    and Docker restarts it.

## 6. Data flow — publishing an article

1. Author submits (wizard) → files stored in `ojs-files` → emails queued → the worker service sends them.
2. Review, copyediting and production happen in the OJS backend.
3. Production: the layout editor uploads a **PDF galley** (required) and optionally an **HTML galley** (+ images as
   dependent files).
4. The editor schedules the article into an issue and publishes → a DOI is assigned → the Crossref deposit is queued.
5. The article page renders through Meridian and scholarlyReader; Google Scholar, OAI and sitemap pick it up.

## 7. Environments

| Env | Where | Notes |
|---|---|---|
| dev | Developer machine / CI sandbox | `docker-compose.dev.yml`, plain HTTP on :8080, demo content, Mailpit catches emails |
| staging (optional) | Same VPS under `staging.` subdomain or local | UAT with client |
| production | Client VPS | `docker-compose.yml`, HTTPS, real SMTP |

## 8. Upgrade strategy

1. Read PKP release notes; set `OJS_VERSION` in `deploy/.env` (stay on the 3.5 LTS line).
2. `scripts/update-ojs.sh` (backup → `docker compose build ojs` → `tools/upgrade.php upgrade` → restart).
3. Diff overridden templates (list in §3) against the new core versions; run the screenshot suite; compare.
