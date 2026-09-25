# QA Report — development build

Environment:
- OJS 3.5.0-5 (official PKP image) with Meridian, scholarlyReader and authorPages
- MariaDB 11.4
- demo journal: 2 issues, 8 fictional articles, 1 HTML full text
- date: 2026-09-25

Reproduce with the commands in the last column; the dev stack is described in the README.

## Summary

| Area | Result | How it was checked |
|---|---|---|
| Install (dev + production stack) | ✅ | `dev/install.sh`; `deploy/scripts/install-ojs.sh` against the production compose on localhost with a self-signed certificate |
| HTTPS, redirect, security headers | ✅ HSTS, nosniff, SAMEORIGIN, Referrer-Policy, Permissions-Policy, CSP frame-ancestors | `curl -I` through nginx |
| Container recreation (no 502) | ✅ | Recreated `ojs` behind nginx, request still 200 |
| Backup → restore | ✅ DB, files, public files, encrypted `.env`, checksums | `scripts/backup.sh`, then `scripts/restore.sh` |
| Worker and scheduler | ✅ running ("Running scheduled tasks every minute") | `docker compose logs` |
| Author registration → profile photo upload → start submission → *My Submissions* dashboard | ✅ | `dev/qa/author-flow.mjs` |
| Article page metadata | ✅ Google Scholar `citation_*` tags (title, authors, institutions, date, volume/issue, pages, PDF and full-text URLs, references), Dublin Core, JSON-LD `ScholarlyArticle` (valid JSON, issue/volume/periodical with ISSN, encodings, citations), OpenGraph, meta description | parsed page source |
| In-text citation linking | ✅ 15 links on the demo article; numeric markers and ranges; popover shows the reference and DOI | Playwright hover screenshot |
| References | ✅ DOIs and URLs linked (including DOIs with parentheses), Google Scholar and Crossref look-ups | unit test of `ReferenceFormatter` + page check |
| OAI-PMH | ✅ `Identify`, `ListMetadataFormats` (oai_dc, marcxml, jats, …), `ListRecords` oai_dc and jats → 8 records each, valid XML | `curl` + XML parse |
| Sitemap / robots.txt | ✅ `/sitemap` lists 31 URLs | `curl` |
| JavaScript errors | ✅ none on home, article, issue and authors pages; header dropdowns work | Playwright console capture |
| Responsive layouts | ✅ 1440 / 834 / 390 px screenshots of every key page | `dev/screenshots.mjs` |

## Lighthouse (dev server, no CDN)

| Page | Device | Performance | Accessibility | Best practices | SEO |
|---|---|---|---|---|---|
| Home | Mobile | 92–94 | 100 | 100 | 100 |
| Home | Desktop | 100 | 100 | 100 | 100 |
| Article | Mobile | 82–85 | 100 | 100 | 100 |
| Article | Desktop | 100 | 100 | 100 | 100 |

**Open item — article page on mobile.** The score is below the 90 target under Lighthouse's simulated slow-4G
throttling. The largest paint is the article title; it waits on the render-blocking CSS chain: the theme stylesheet
plus CSS files from OJS core and bundled plugins (Font Awesome, Citation Style Language). Already done: fonts merged
into the main stylesheet and preloaded; the carousel library is loaded only when home-page highlights exist.

Next steps, to measure on the production server first (HTTP/2 and nginx caching change the result):
- inline critical CSS for the article hero
- defer the plugin stylesheets

## Not yet verified (needs client inputs or a live server)

- Crossref DOI deposit (needs Crossref credentials)
- ORCID authentication flow (needs ORCID API credentials)
- E-mail delivery to real inboxes (needs SMTP)
- Let's Encrypt issuance (needs the domain's DNS)
- SSL Labs and securityheaders.com grades (need the public server)
- A full review cycle by real users (submit → reviewers → decision → publish). The editorial screens are standard
  OJS 3.5; the demo content was published through the Native XML import.
