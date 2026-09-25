# Roadmap & Milestones

Each milestone ends with a commit and push to the development branch, plus screenshots shared for review.

| # | Milestone | Deliverables | Contract items |
|---|---|---|---|
| M1 | **PRD package** | `docs/` (this set) | Planning |
| M2 | **Dev environment** | `deploy/docker-compose.dev.yml`, installer automation, demo journal content | OJS installation |
| M3 | **Theme foundation** | Meridian plugin skeleton, tokens, fonts, header/drawer/search, footer, Home page | UI design, Responsive |
| M4 | **Journal pages** | Issue TOC, Archives, Article page (3-column), article list items | Current Issue, Archives, Article pages, PDF access |
| M5 | **scholarlyReader** | Reference linking, inline HTML full text, citation popovers, JSON-LD/OpenGraph | Reference linking, Google Scholar metadata |
| M6 | **Secondary pages & polish** | About/static pages, Editorial Team, Search, Login/Register, Announcements, errors; responsive + a11y pass | UI, Responsive, Search |
| M7 | **Production kit** | Production compose, Dockerfile, nginx/TLS, config renderer, worker + scheduler, backup/restore/update scripts | SSL & security, deployment |
| M8 | **QA & handover** | Screenshot suite, metadata/OAI validation, Lighthouse, `admin-guide.md` | Final testing |
| M9 | **Go-live** (needs client inputs) | Configure the real journal per `ojs-configuration.md`, DOI/Crossref, ORCID, SMTP, domain, launch | DOI, ORCID, Email, Launch |

## Dependencies

- M9 is blocked by the items in [client-inputs.md](client-inputs.md): domain, VPS access, SMTP, Crossref and ORCID
  credentials, ISSN, texts, and the editorial board.
- M1 through M8 do not depend on the client; placeholders are used.
