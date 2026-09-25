# Academic Journal Website (OJS 3.5)

A modern, responsive academic journal website built on **Open Journal Systems 3.5 LTS**, with:

- **Meridian**, a custom theme (`plugins/themes/meridian`): a clean editorial design with serif titles, white space,
  a 3-column article layout and full responsiveness.
- **scholarlyReader**, a generic plugin (`plugins/generic/scholarlyReader`): reference linking, inline HTML full text
  with clickable citations, and JSON-LD/OpenGraph metadata.
- **authorPages**, a generic plugin (`plugins/generic/authorPages`): an author directory and a public page per author
  with photo, affiliation, ORCID, biography and articles.
- A **Docker deployment kit** (`deploy/`): nginx + Let's Encrypt, OJS, MariaDB, a job worker and scheduler, and backups.

OJS core is never modified. Everything here is a plugin or deployment configuration.

## Documentation

| Doc | Purpose |
|---|---|
| [docs/PRD.md](docs/PRD.md) | Product requirements and acceptance criteria |
| [docs/design-system.md](docs/design-system.md) | Visual language, tokens, components, page blueprints |
| [docs/architecture.md](docs/architecture.md) | Stack, theme/plugin design, jobs, upgrades |
| [docs/ojs-configuration.md](docs/ojs-configuration.md) | Exact OJS admin configuration |
| [docs/indexing-readiness.md](docs/indexing-readiness.md) | Google Scholar, DOAJ, EBSCO, CABI, Scopus, WoS checklists |
| [docs/deployment-vps.md](docs/deployment-vps.md) | VPS setup, deploy, backups, updates, security |
| [docs/roadmap.md](docs/roadmap.md) | Milestones |
| [docs/client-inputs.md](docs/client-inputs.md) | What the client must provide |

## Quick start (development)

```bash
cd deploy
cp .env.example .env.dev
docker compose -f docker-compose.dev.yml --env-file .env.dev up -d
# open http://localhost:8080 and complete the installer (or run ../dev/install.sh)
```

See [docs/deployment-vps.md](docs/deployment-vps.md) for production.
