# Inputs Needed from the Client

Development proceeds with placeholders. These items are needed before **go-live** (M9).

## Identity and content

| # | Item | Notes |
|---|---|---|
| 1 | Journal full name, abbreviation and initials | Used in the masthead, DOIs, citations and meta tags |
| 2 | Logo (SVG preferred) and favicon | Until then Meridian shows a typographic wordmark |
| 3 | Subject field, Aims & Scope text | Homepage teaser + About |
| 4 | ISSN (online; print if any) | Needed for DOAJ, PKP PN, Crossref and meta tags |
| 5 | Publisher name and address | Footer, Crossref, About |
| 6 | Publication frequency, e.g. quarterly | About page, indexing |
| 7 | Editorial board: name, role, affiliation, country, email, ORCID, photo (optional) | Masthead / Editorial Team page |
| 8 | Author guidelines, submission checklist, manuscript template (.docx) | For Authors pages |
| 9 | Policies: peer review, ethics (COPE), plagiarism, OA, copyright/license, archiving, APC, corrections/retractions, conflicts of interest, data sharing, AI use | Templates will be provided for the client to edit |
| 10 | Article Processing Charge (amount or "no charges") | Required by DOAJ even when free |
| 11 | Cover image style for issues (optional) | Otherwise a generated typographic cover is used |

## Accounts and credentials (sent privately, never in chat or email threads)

| # | Item | Notes |
|---|---|---|
| 12 | Domain + DNS access | A/AAAA records, SPF/DKIM/DMARC |
| 13 | VPS: IP, SSH access (key-based) | Ubuntu 24.04, ≥ 4 GB RAM |
| 14 | SMTP account (e.g. Zoho, Mailgun, Amazon SES, Google Workspace) | host, port, user, password, sender address |
| 15 | Crossref membership: DOI prefix, depositor username/password | Membership fees are the client's |
| 16 | ORCID API client ID/secret (Public API is free; Member API optional) | Register at orcid.org developer tools |
| 17 | Principal contact and technical support emails | Shown on the Contact page |
| 18 | Off-site backup target (optional): S3/Backblaze bucket credentials | For `rclone` |
